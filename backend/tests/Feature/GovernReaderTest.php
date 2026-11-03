<?php

namespace Tests\Feature;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Tests\TestCase;

class GovernReaderTest extends TestCase
{
    private string $role;

    private string $table;

    private string $secondTable;

    private string $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $suffix = bin2hex(random_bytes(8));
        $this->role = 'rgreader_'.$suffix;
        $this->table = 'rgtable_'.$suffix;
        $this->secondTable = 'rgfuture_'.$suffix;
        $this->profile = sys_get_temp_dir().'/rijksdienst-govern-test-'.$suffix.'/reader.env';
        config(['services.govern_reader.role' => $this->role, 'services.govern_reader.path' => $this->profile]);
        DB::statement('CREATE TABLE public."'.$this->table.'" (id integer PRIMARY KEY, label text NOT NULL)');
        DB::table($this->table)->insert(['id' => 1, 'label' => 'Isolated test record']);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->role)) {
                // Only this test's random role and tables are ever cleaned up.
                DB::statement('DROP TABLE IF EXISTS public."'.$this->secondTable.'"');
                DB::statement('DROP TABLE IF EXISTS public."'.$this->table.'"');
                if (DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [$this->role])) {
                    DB::statement('DROP OWNED BY "'.$this->role.'"');
                    DB::statement('DROP ROLE "'.$this->role.'"');
                }
                foreach ([$this->profile, $this->profile.'.lock'] as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                if (is_dir(dirname($this->profile))) {
                    rmdir(dirname($this->profile));
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    private function prepare(): array
    {
        $this->assertSame(0, Artisan::call('govern:prepare-reader', ['--json' => true]));

        return json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function password(): string
    {
        preg_match('/^RIJKSDIENST_GOVERN_READ_PASSWORD=([a-f0-9]{64})$/m', file_get_contents($this->profile), $match);

        return $match[1];
    }

    private function reader(): PDO
    {
        $configuration = DB::connection()->getConfig();
        unset($configuration['url']);
        $configuration['username'] = $this->role;
        $configuration['password'] = $this->password();

        return (new PostgresConnector)->connect($configuration);
    }

    private function assertDenied(callable $operation): void
    {
        try {
            $operation();
            $this->fail('The test reader unexpectedly received persistent write access.');
        } catch (PDOException $exception) {
            $this->assertSame('42501', $exception->getCode());
        }
    }

    public function test_real_reader_can_select_but_cannot_change_records_or_schema_even_after_disabling_read_only(): void
    {
        $result = $this->prepare();
        $this->assertSame('ready', $result['status']);
        $this->assertSame('rijksdienst_testing', $result['database']);
        $this->assertSame($this->role, $result['reader_role']);
        $this->assertTrue($result['role_created']);
        $this->assertTrue($result['profile_created']);
        $password = $this->password();
        $this->assertFalse(str_contains(Artisan::output(), $password), 'A credential appeared in command output.');
        $reader = $this->reader();
        $this->assertSame('on', $reader->query('SHOW default_transaction_read_only')->fetchColumn());
        $this->assertSame('Isolated test record', $reader->query('SELECT label FROM public."'.$this->table.'"')->fetchColumn());
        $reader->exec('SET default_transaction_read_only = off');
        $this->assertDenied(fn () => $reader->exec('INSERT INTO public."'.$this->table.'" VALUES (2, \'Denied\')'));
        $this->assertDenied(fn () => $reader->exec('UPDATE public."'.$this->table.'" SET label = \'Denied\' WHERE id = 1'));
        $this->assertDenied(fn () => $reader->exec('DELETE FROM public."'.$this->table.'" WHERE id = 1'));
        $this->assertDenied(fn () => $reader->exec('CREATE TABLE public."'.$this->secondTable.'" (id integer)'));
        $this->assertSame('Isolated test record', DB::table($this->table)->value('label'));
        $reader = null;
    }

    public function test_repeating_preparation_preserves_the_profile_and_password_and_grants_future_owner_tables(): void
    {
        $this->prepare();
        $before = hash_file('sha256', $this->profile);
        $result = $this->prepare();
        $this->assertFalse($result['role_created']);
        $this->assertFalse($result['profile_created']);
        $this->assertSame($before, hash_file('sha256', $this->profile));
        DB::statement('CREATE TABLE public."'.$this->secondTable.'" (id integer)');
        DB::table($this->secondTable)->insert(['id' => 7]);
        $reader = $this->reader();
        $this->assertSame(7, (int) $reader->query('SELECT id FROM public."'.$this->secondTable.'"')->fetchColumn());
        $reader = null;
    }

    public function test_existing_unmanaged_role_is_not_taken_over_or_given_a_new_password(): void
    {
        DB::statement('CREATE ROLE "'.$this->role.'" LOGIN');
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('verbindingsbestand ontbreekt', Artisan::output());
        $this->assertFileDoesNotExist($this->profile);
        $this->assertNull(DB::selectOne('SELECT rolpassword FROM pg_authid WHERE rolname = ?', [$this->role])->rolpassword);
    }

    public function test_existing_privileged_managed_role_is_rejected_without_mutation(): void
    {
        $this->prepare();
        $before = hash_file('sha256', $this->profile);
        DB::statement('ALTER ROLE "'.$this->role.'" CREATEDB');
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('verhoogde rechten', Artisan::output());
        $this->assertSame($before, hash_file('sha256', $this->profile));
        $this->assertTrue(DB::selectOne('SELECT rolcreatedb FROM pg_roles WHERE rolname = ?', [$this->role])->rolcreatedb);
    }

    public function test_lost_profile_and_wrong_credentials_are_never_replaced(): void
    {
        $this->prepare();
        $original = file_get_contents($this->profile);
        unlink($this->profile);
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertFileDoesNotExist($this->profile);
        $incorrect = 'RIJKSDIENST_GOVERN_READ_PASSWORD='.str_repeat('a', 64)."\n";
        file_put_contents($this->profile, $incorrect);
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('wachtwoord is niet vervangen', Artisan::output());
        $this->assertSame(hash('sha256', $incorrect), hash_file('sha256', $this->profile));
        file_put_contents($this->profile, $original);
        $this->prepare();
    }

    public function test_unsafe_existing_schema_creation_privilege_is_not_silently_revoked(): void
    {
        $this->prepare();
        DB::statement('GRANT CREATE ON SCHEMA public TO "'.$this->role.'"');
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('schrijf- of aanmaakrechten', Artisan::output());
        $this->assertTrue(DB::selectOne("SELECT has_schema_privilege(?, 'public', 'CREATE') AS allowed", [$this->role])->allowed);
    }

    public function test_column_level_write_grants_are_detected_without_revoking_them(): void
    {
        $this->prepare();
        DB::statement('GRANT UPDATE (label) ON public."'.$this->table.'" TO "'.$this->role.'"');
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('schrijf- of aanmaakrechten', Artisan::output());
        $this->assertTrue(DB::selectOne("SELECT has_any_column_privilege(?, ?, 'UPDATE') AS allowed", [$this->role, 'public.'.$this->table])->allowed);
    }

    public function test_role_ownership_and_unmanaged_marker_changes_are_rejected(): void
    {
        $this->prepare();
        DB::statement('ALTER TABLE public."'.$this->table.'" OWNER TO "'.$this->role.'"');
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('eigendom', Artisan::output());
        DB::statement('ALTER TABLE public."'.$this->table.'" OWNER TO "'.DB::connection()->getConfig('username').'"');
        DB::statement('COMMENT ON ROLE "'.$this->role.'" IS \'Owned elsewhere\'');
        $this->assertSame(1, Artisan::call('govern:prepare-reader'));
        $this->assertStringContainsString('niet door deze Rijksdienst-koppeling beheerd', Artisan::output());
    }

    public function test_an_existing_transaction_is_refused_without_rolling_it_back(): void
    {
        DB::beginTransaction();
        try {
            DB::table($this->table)->where('id', 1)->update(['label' => 'Pending user work']);
            $this->assertSame(1, Artisan::call('govern:prepare-reader'));
            $this->assertStringContainsString('bestaande transactie', Artisan::output());
            $this->assertSame(1, DB::transactionLevel());
            $this->assertTrue(DB::connection()->getPdo()->inTransaction());
            $this->assertSame('Pending user work', DB::table($this->table)->value('label'));
        } finally {
            DB::rollBack();
        }
        $this->assertSame('Isolated test record', DB::table($this->table)->value('label'));
    }

    public function test_invalid_private_profile_is_preserved_and_driver_errors_are_not_leaked(): void
    {
        mkdir(dirname($this->profile), 0700, true);
        $invalid = "RIJKSDIENST_GOVERN_READ_PASSWORD=not-a-supported-private-profile\n";
        file_put_contents($this->profile, $invalid);
        $this->assertSame(1, Artisan::call('govern:prepare-reader', ['--json' => true]));
        $this->assertSame(hash('sha256', $invalid), hash_file('sha256', $this->profile));
        $this->assertFalse(str_contains(Artisan::output(), 'not-a-supported-private-profile'));
        $this->assertFalse((bool) DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [$this->role]));
    }
}
