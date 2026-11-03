<?php

namespace App\Domain\Govern;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

/** Prepare one explicitly scoped account; never rotate existing credentials. */
final class GovernReaderProvisioner
{
    public const ROLE = 'rijksdienst_govern_reader';

    public const MARKER = 'rijksdienst-govern-reader-v1';

    public function prepare(): array
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'pgsql') {
            throw new RuntimeException('De Govern-leesverbinding vereist PostgreSQL.');
        }
        $role = config('services.govern_reader.role', self::ROLE);
        $path = config('services.govern_reader.path', storage_path('app/private/govern/reader.env'));
        if (! is_string($role) || ! preg_match('/\A[a-z][a-z0-9_]{0,62}\z/', $role)
            || ! is_string($path) || $path === '') {
            throw new RuntimeException('De serverconfiguratie voor de Govern-lezer is ongeldig.');
        }
        $directory = dirname($path);
        if (is_link($path) || is_link($directory) || is_link($path.'.lock')
            || (! is_dir($directory) && ! @mkdir($directory, 0700, true))) {
            throw new RuntimeException('De private Govern-verbindingsmap kan niet worden voorbereid.');
        }
        @chmod($directory, 0700);
        $lock = @fopen($path.'.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Het private Govern-verbindingsbestand kan niet worden vergrendeld.');
        }
        @chmod($path.'.lock', 0600);
        if (! flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new RuntimeException('De Govern-verbindingsconfiguratie is in gebruik. Probeer opnieuw.');
        }

        $pdo = null;
        $profileCreated = false;
        $commitAttempted = false;
        $transactionStarted = false;
        $temporary = null;
        try {
            $pdo = $connection->getPdo();
            if ($pdo->inTransaction()) {
                throw new RuntimeException('Bereid de Govern-lezer buiten een bestaande transactie voor.');
            }
            $profileExists = is_file($path);
            $password = $profileExists ? $this->readPassword($path) : bin2hex(random_bytes(32));
            $pdo->beginTransaction();
            $transactionStarted = true;
            $database = (string) $pdo->query('SELECT current_database()')->fetchColumn();
            $owner = (string) $pdo->query('SELECT current_user')->fetchColumn();
            if (! $this->truthy($pdo->query('SELECT rolsuper OR rolcreaterole FROM pg_roles WHERE rolname = current_user')->fetchColumn())) {
                throw new RuntimeException('De huidige PostgreSQL-gebruiker mag geen afzonderlijke leesrol voorbereiden.');
            }
            if ($role === $owner) {
                throw new RuntimeException('Govern vereist een afzonderlijke leesrol.');
            }
            $lockQuery = $pdo->prepare('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))');
            $lockQuery->execute([self::MARKER.':'.$database.':'.$role]);
            $roleQuery = $pdo->prepare("SELECT r.*, shobj_description(r.oid, 'pg_authid') AS marker FROM pg_roles r WHERE rolname = ?");
            $roleQuery->execute([$role]);
            $existing = $roleQuery->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if (! $profileExists) {
                    throw new RuntimeException('De Govern-leesrol bestaat al, maar het private verbindingsbestand ontbreekt. Herstel dat bestand; het wachtwoord is niet gewijzigd.');
                }
                if ($existing['marker'] !== self::MARKER) {
                    throw new RuntimeException('De bestaande rol is niet door deze Rijksdienst-koppeling beheerd. Er is niets overgenomen.');
                }
                $this->assertUnprivileged($pdo, $existing);
                $this->verifyLogin($connection->getConfig(), $role, $password, $database);
            } else {
                $pdo->exec('CREATE ROLE '.$this->identifier($role).' LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOREPLICATION NOBYPASSRLS PASSWORD '.$pdo->quote($password));
                $pdo->exec('COMMENT ON ROLE '.$this->identifier($role).' IS '.$pdo->quote(self::MARKER));
            }

            $quotedRole = $this->identifier($role);
            $quotedDatabase = $this->identifier($database);
            $pdo->exec('GRANT CONNECT ON DATABASE '.$quotedDatabase.' TO '.$quotedRole);
            $pdo->exec('GRANT USAGE ON SCHEMA public TO '.$quotedRole);
            $pdo->exec('GRANT SELECT ON ALL TABLES IN SCHEMA public TO '.$quotedRole);
            $pdo->exec('ALTER DEFAULT PRIVILEGES FOR ROLE '.$this->identifier($owner).' IN SCHEMA public GRANT SELECT ON TABLES TO '.$quotedRole);
            $pdo->exec('ALTER ROLE '.$quotedRole.' IN DATABASE '.$quotedDatabase.' SET default_transaction_read_only = on');
            $this->assertNoPersistentWrites($pdo, $role, $database);

            if (! $profileExists) {
                $temporary = tempnam($directory, '.reader-');
                if ($temporary === false) {
                    throw new RuntimeException('Het private Govern-verbindingsbestand kan niet worden aangemaakt.');
                }
                @chmod($temporary, 0600);
                $contents = "# Private local integration credential. Never commit or display this file.\nRIJKSDIENST_GOVERN_READ_PASSWORD=".$password."\n";
                if (file_put_contents($temporary, $contents) !== strlen($contents)
                    || file_exists($path) || ! @rename($temporary, $path)) {
                    throw new RuntimeException('Het private Govern-verbindingsbestand kon niet veilig worden opgeslagen.');
                }
                $temporary = null;
                $profileCreated = true;
                @chmod($path, 0600);
            }
            $commitAttempted = true;
            $pdo->commit();
            $transactionStarted = false;
            $this->verifyLogin($connection->getConfig(), $role, $password, $database);

            return [
                'status' => 'ready',
                'database' => $database,
                'schema' => 'public',
                'reader_role' => $role,
                'role_created' => ! $existing,
                'profile_created' => $profileCreated,
                'profile_file' => $path,
                'note' => 'Govern krijgt SELECT op public; bestaande gegevens en wachtwoorden zijn behouden. Andere tabellenmakers moeten hun eigen standaardleesrechten instellen.',
            ];
        } catch (Throwable $exception) {
            if ($transactionStarted && $pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Keep credentials on an uncertain commit outcome so a retry can recover.
            if ($profileCreated && ! $commitAttempted) {
                @unlink($path);
            }
            if ($exception instanceof RuntimeException && ! $exception instanceof \PDOException) {
                throw $exception;
            }
            // SQL/driver diagnostics may contain credential-bearing role statements.
            throw new RuntimeException('De Govern-leesrol kon niet veilig worden voorbereid. Controleer de PostgreSQL-beheerdersrechten en de private verbindingsconfiguratie.');
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function readPassword(string $path): string
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException('Het private Govern-verbindingsbestand kan niet worden gelezen.');
        }
        $matches = array_values(array_filter($lines, fn (string $line) => str_starts_with($line, 'RIJKSDIENST_GOVERN_READ_PASSWORD=')));
        if (count($matches) !== 1 || ! preg_match('/\ARIJKSDIENST_GOVERN_READ_PASSWORD=([a-f0-9]{64})\z/', $matches[0], $match)) {
            throw new RuntimeException('Het private Govern-verbindingsbestand is ongeldig. Het bestaande wachtwoord wordt niet vervangen.');
        }

        return $match[1];
    }

    private function assertUnprivileged(PDO $pdo, array $role): void
    {
        foreach (['rolsuper', 'rolcreatedb', 'rolcreaterole', 'rolreplication', 'rolbypassrls', 'rolinherit'] as $flag) {
            if ($this->truthy($role[$flag])) {
                throw new RuntimeException('De bestaande Govern-rol heeft verhoogde rechten. De koppeling is gestopt.');
            }
        }
        if (! $this->truthy($role['rolcanlogin'])) {
            throw new RuntimeException('De bestaande Govern-rol kan niet aanmelden. Het account is niet gewijzigd.');
        }
        $query = $pdo->prepare("SELECT EXISTS(SELECT 1 FROM pg_auth_members WHERE member = ? OR roleid = ?) OR EXISTS(SELECT 1 FROM pg_shdepend WHERE refclassid = 'pg_authid'::regclass AND refobjid = ? AND deptype = 'o')");
        $query->execute([$role['oid'], $role['oid'], $role['oid']]);
        if ($this->truthy($query->fetchColumn())) {
            throw new RuntimeException('De bestaande Govern-rol heeft lidmaatschappen of eigendom. De koppeling is gestopt.');
        }
    }

    private function assertNoPersistentWrites(PDO $pdo, string $role, string $database): void
    {
        $query = $pdo->prepare("SELECT has_database_privilege(?, ?, 'CREATE') OR EXISTS (
            SELECT 1 FROM pg_namespace n WHERE n.nspname !~ '^pg_' AND n.nspname <> 'information_schema'
            AND has_schema_privilege(?, n.oid, 'CREATE')) OR EXISTS (
            SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname !~ '^pg_' AND n.nspname <> 'information_schema' AND c.relkind IN ('r', 'p', 'v', 'm', 'f')
            AND (has_table_privilege(?, c.oid, 'INSERT') OR has_table_privilege(?, c.oid, 'UPDATE')
              OR has_table_privilege(?, c.oid, 'DELETE') OR has_table_privilege(?, c.oid, 'TRUNCATE')
              OR has_table_privilege(?, c.oid, 'REFERENCES') OR has_table_privilege(?, c.oid, 'TRIGGER')
              OR has_any_column_privilege(?, c.oid, 'INSERT') OR has_any_column_privilege(?, c.oid, 'UPDATE')
              OR has_any_column_privilege(?, c.oid, 'REFERENCES'))) OR EXISTS (
            SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname !~ '^pg_' AND n.nspname <> 'information_schema' AND c.relkind = 'S'
            AND (has_sequence_privilege(?, c.oid, 'USAGE') OR has_sequence_privilege(?, c.oid, 'UPDATE')))");
        $query->execute([$role, $database, $role, $role, $role, $role, $role, $role, $role, $role, $role, $role, $role, $role]);
        if ($this->truthy($query->fetchColumn())) {
            throw new RuntimeException('De leesrol erft schrijf- of aanmaakrechten. De koppeling is gestopt zonder rechten van PUBLIC of andere gebruikers te wijzigen.');
        }
    }

    private function verifyLogin(#[\SensitiveParameter] array $configuration, string $role, #[\SensitiveParameter] string $password, string $database): void
    {
        try {
            unset($configuration['url']);
            $configuration['username'] = $role;
            $configuration['password'] = $password;
            $reader = (new PostgresConnector)->connect($configuration);
            if ($reader->query('SELECT current_database()')->fetchColumn() !== $database) {
                throw new RuntimeException('Unexpected database');
            }
        } catch (Throwable) {
            throw new RuntimeException('Aanmelden met de bewaarde Govern-leesgegevens is niet gelukt. Het wachtwoord is niet vervangen.');
        }
    }

    private function identifier(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 't', '1', 1], true);
    }
}
