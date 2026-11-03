<?php

namespace App\Console\Commands;

use App\Domain\DataSources\CbsCatalogueInspector;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

class DiscoverCbsDatasets extends Command
{
    protected $signature = 'data:discover-cbs {--limit=5 : Maximaal 1 tot 20 datasets uit de catalogus} {--json : Toon oorspronkelijke metadatavelden als JSON}';

    protected $description = 'Bekijk echte CBS OData 4-catalogusmetadata zonder statistieken te importeren';

    public function handle(CbsCatalogueInspector $inspector): int
    {
        $limit = (string) $this->option('limit');
        if (! preg_match('/\A(?:[1-9]|1[0-9]|20)\z/', $limit)) {
            $this->error('De limiet moet een geheel getal van 1 tot en met 20 zijn.');

            return self::INVALID;
        }
        try {
            $inspection = $inspector->discover((int) $limit);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->output->writeln(
                json_encode($inspection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                OutputInterface::OUTPUT_RAW
            );
        } else {
            $this->info('CBS StatLine — beperkte catalogusinspectie');
            $this->table(['Identifier', 'Title', 'Catalog', 'Modified', 'ObservationsModified'], array_map(
                fn (array $dataset) => array_map(
                    fn (string $key) => OutputFormatter::escape(preg_replace('/[\x00-\x1F\x7F]/u', '', (string) ($dataset[$key] ?? ''))),
                    ['Identifier', 'Title', 'Catalog', 'Modified', 'ObservationsModified']
                ),
                $inspection['datasets']
            ));
            $this->comment($inspection['note']);
            $this->line('Gebruik --json voor de volledige ontvangen metadata. Dit is geen volledige catalogus of globale zoekfunctie.');
        }

        return self::SUCCESS;
    }
}
