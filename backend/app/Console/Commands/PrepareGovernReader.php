<?php

namespace App\Console\Commands;

use App\Domain\Govern\GovernReaderProvisioner;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareGovernReader extends Command
{
    protected $signature = 'govern:prepare-reader {--json : Toon alleen niet-geheime verbindingsmetadata}';

    protected $description = 'Bereid een afzonderlijke Govern-lezer voor de huidige PostgreSQL-database voor';

    public function handle(GovernReaderProvisioner $provisioner): int
    {
        try {
            $result = $provisioner->prepare();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        if ($this->option('json')) {
            $this->output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
        } else {
            $this->info('Govern-leesverbinding voorbereid voor '.$result['database'].' / public.');
            $this->line('Leesrol: '.$result['reader_role']);
            $this->line('Private configuratie: '.$result['profile_file']);
            $this->comment($result['note']);
        }

        return self::SUCCESS;
    }
}
