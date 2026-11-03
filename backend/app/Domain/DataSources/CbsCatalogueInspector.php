<?php

namespace App\Domain\DataSources;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

/** Metadata-only discovery, following the official CBS cbsodata4 client. */
final class CbsCatalogueInspector
{
    public const DATASETS_URL = 'https://datasets.cbs.nl/odata/Datasets';

    public function discover(int $limit): array
    {
        if ($limit < 1 || $limit > 20) {
            throw new InvalidArgumentException('De limiet moet een geheel getal van 1 tot en met 20 zijn.');
        }

        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)
                ->withOptions([
                    'verify' => config('services.government_data.ca_bundle') ?: true,
                    'allow_redirects' => false,
                ])->get(self::DATASETS_URL, ['$top' => $limit]);
        } catch (ConnectionException) {
            throw new RuntimeException('CBS is niet bereikbaar. Controleer netwerktoegang, proxy en certificaten voor datasets.cbs.nl.');
        } catch (RequestException $exception) {
            // Proxy CONNECT failures can throw before get() returns a response.
            // Keep diagnostics limited to the status, never the response body.
            throw new RuntimeException('CBS gaf HTTP '.$exception->response->status().'. Controleer de toegang of probeer later opnieuw; er is niets geïmporteerd.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('CBS gaf HTTP '.$response->status().'. Controleer de toegang of probeer later opnieuw; er is niets geïmporteerd.');
        }
        if (strlen($response->body()) > 5 * 1024 * 1024) {
            throw new RuntimeException('Het CBS-antwoord is te groot voor deze beperkte metadata-inspectie.');
        }
        try {
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('CBS gaf geen geldig JSON-antwoord; er is niets geïmporteerd.');
        }
        if (! is_array($payload) || ! is_array($payload['value'] ?? null) || ! array_is_list($payload['value'])) {
            throw new RuntimeException('Het CBS-antwoord bevat geen verwachte OData value-lijst.');
        }
        if (count($payload['value']) > $limit) {
            throw new RuntimeException('Het CBS-antwoord overschrijdt het aangevraagde maximum; inspectie gestopt.');
        }
        foreach ($payload['value'] as $dataset) {
            if (! is_array($dataset) || ! is_string($dataset['Identifier'] ?? null)
                || trim($dataset['Identifier']) === '' || ! is_string($dataset['Title'] ?? null)
                || trim($dataset['Title']) === '' || ! is_string($dataset['Catalog'] ?? null)
                || trim($dataset['Catalog']) === '') {
                throw new RuntimeException('De CBS-datasetmetadata heeft een onverwachte structuur.');
            }
            foreach (['Modified', 'ObservationsModified'] as $dateField) {
                if (isset($dataset[$dateField]) && ! is_string($dataset[$dateField])) {
                    throw new RuntimeException('Een wijzigingsdatum in de CBS-metadata heeft een onverwacht formaat.');
                }
            }
        }
        $nextPage = $payload['@odata.nextLink'] ?? null;
        if ($nextPage !== null && ! is_string($nextPage)) {
            throw new RuntimeException('De CBS-pagineringsmetadata heeft een onverwachte structuur.');
        }

        return [
            'status' => 'inspected',
            'not_imported' => true,
            'scope' => 'catalogue_metadata_only',
            'catalog_complete' => false,
            'publisher' => 'Centraal Bureau voor de Statistiek',
            'source' => 'CBS StatLine OData 4',
            'source_url' => self::DATASETS_URL,
            'documentation_url' => 'https://github.com/statistiekcbs/cbsodata4/blob/master/R/cbs4_get_datasets.R',
            'retrieved_at' => now()->toIso8601String(),
            'requested_limit' => $limit,
            'returned_count' => count($payload['value']),
            'datasets' => $payload['value'],
            // Preserve source metadata; never follow an arbitrary URL automatically.
            'next_page_url' => $nextPage,
            'note' => 'Beperkte catalogusinspectie. Waarnemingen, eenheden, perioden en actuele datasetlicenties zijn nog niet onderzocht of geïmporteerd.',
        ];
    }
}
