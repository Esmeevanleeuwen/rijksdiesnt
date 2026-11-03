<?php

namespace Tests\Feature;

use App\Domain\DataSources\CbsCatalogueInspector;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CbsSourceInspectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /**
     * Synthetic records, not live statistics. The field contract comes from the
     * official statistiekcbs/cbsodata4 R/cbs4_get_datasets.R and README.md.
     */
    private function catalogue(): array
    {
        return [
            '@odata.context' => 'https://datasets.cbs.nl/odata/$metadata#Datasets',
            'value' => [[
                'Identifier' => 'fixture-cbs-001',
                'Title' => 'Fictieve testdataset voor metadata-inspectie',
                'Catalog' => 'CBS',
                'Modified' => '2024-01-01T00:00:00Z',
                'ObservationsModified' => null,
                'UnmappedSourceField' => ['kept' => true],
            ]],
        ];
    }

    private function fakeResponse(array|string $body, int $status = 200, array $headers = []): void
    {
        // Http::fake adds callbacks; fresh factories keep looped failure cases isolated.
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response($body, $status, $headers));
    }

    public function test_it_uses_the_documented_cbs4_catalogue_and_preserves_raw_metadata_only(): void
    {
        $fixture = $this->catalogue();
        $fixture['@odata.nextLink'] = 'https://example.org/untrusted-next-page';
        Http::fake(fn () => Http::response($fixture));
        $this->travelTo(now()->setDate(2026, 11, 3)->startOfDay());

        $exit = Artisan::call('data:discover-cbs', ['--limit' => '3', '--json' => true]);
        $result = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertTrue($result['not_imported']);
        $this->assertFalse($result['catalog_complete']);
        $this->assertSame('catalogue_metadata_only', $result['scope']);
        $this->assertSame('inspected', $result['status']);
        $this->assertSame($fixture['value'], $result['datasets']);
        $this->assertSame($fixture['@odata.nextLink'], $result['next_page_url']);
        $this->assertSame(3, $result['requested_limit']);
        $this->assertSame(1, $result['returned_count']);
        $this->assertSame(now()->toIso8601String(), $result['retrieved_at']);
        $this->assertArrayNotHasKey('observations', $result);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return $request->method() === 'GET'
                && strtok($request->url(), '?') === CbsCatalogueInspector::DATASETS_URL
                && $query === ['$top' => '3']
                && str_contains($request->header('Accept')[0], 'application/json');
        });
    }

    public function test_it_prints_a_human_catalogue_summary_without_claiming_a_complete_search(): void
    {
        $this->fakeResponse($this->catalogue());
        $this->assertSame(0, Artisan::call('data:discover-cbs'));
        $output = Artisan::output();
        $this->assertStringContainsString('fixture-cbs-001', $output);
        $this->assertStringContainsString('Fictieve testdataset', $output);
        $this->assertStringContainsString('nog niet onderzocht of geïmporteerd', $output);
        $this->assertStringContainsString('geen volledige catalogus of globale zoekfunctie', $output);
    }

    public function test_json_output_preserves_source_titles_that_resemble_console_formatting(): void
    {
        $fixture = $this->catalogue();
        $fixture['value'][0]['Title'] = 'Fictieve <info>bronwaarde</info> & <error>geen opmaak</error>';
        $this->fakeResponse($fixture);
        $this->assertSame(0, Artisan::call('data:discover-cbs', ['--json' => true]));
        $result = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($fixture['value'][0]['Title'], $result['datasets'][0]['Title']);
    }

    public function test_invalid_limits_fail_before_any_network_request(): void
    {
        Http::fake();
        foreach (['0', '21', '-1', '1.5', 'invalid', '01'] as $limit) {
            $this->assertSame(2, Artisan::call('data:discover-cbs', ['--limit' => $limit]));
        }
        Http::assertNothingSent();
    }

    public function test_an_empty_well_formed_catalogue_is_reported_honestly(): void
    {
        Http::fake(fn () => Http::response(['value' => []]));
        $this->assertSame(0, Artisan::call('data:discover-cbs', ['--limit' => '20', '--json' => true]));
        $result = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $result['datasets']);
        $this->assertSame(0, $result['returned_count']);
        $this->assertFalse($result['catalog_complete']);
    }

    public function test_network_failure_is_actionable_without_leaking_exception_details(): void
    {
        Http::fake(fn () => throw new ConnectionException('Synthetic confidential upstream diagnostic'));
        $this->assertSame(1, Artisan::call('data:discover-cbs'));
        $output = Artisan::output();
        $this->assertStringContainsString('datasets.cbs.nl', $output);
        $this->assertStringContainsString('netwerktoegang', $output);
        $this->assertStringNotContainsString('confidential', $output);
    }

    public function test_http_errors_and_redirects_are_not_parsed_as_data_or_followed(): void
    {
        foreach ([302, 403, 429, 500] as $status) {
            $this->fakeResponse('Synthetic upstream diagnostic', $status, ['Location' => 'https://example.org/elsewhere']);
            $this->assertSame(1, Artisan::call('data:discover-cbs', ['--json' => true]));
            $output = Artisan::output();
            $this->assertStringContainsString('HTTP '.$status, $output);
            $this->assertStringNotContainsString('Synthetic upstream', $output);
            Http::assertSentCount(1);
        }
    }

    public function test_a_thrown_proxy_request_exception_is_reported_without_its_response_body(): void
    {
        $response = new Response(new PsrResponse(403, ['Content-Type' => 'text/html'], 'Synthetic private proxy diagnostic'));
        $attempts = 0;
        Http::fake(function () use ($response, &$attempts) {
            $attempts++;
            throw new RequestException($response);
        });

        $this->assertSame(1, Artisan::call('data:discover-cbs', ['--json' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('HTTP 403', $output);
        $this->assertStringContainsString('niets geïmporteerd', $output);
        $this->assertStringNotContainsString('private proxy diagnostic', $output);
        $this->assertSame(1, $attempts);
    }

    public function test_invalid_json_and_unexpected_odata_shapes_do_not_produce_success(): void
    {
        foreach (['not-json', ['value' => null], ['value' => ['not-a-list' => true]], ['value' => ['not-an-object']]] as $payload) {
            $this->fakeResponse($payload);
            $this->assertSame(1, Artisan::call('data:discover-cbs', ['--json' => true]));
            $this->assertStringNotContainsString('"status": "inspected"', Artisan::output());
        }
    }

    public function test_missing_or_invalid_core_metadata_fields_are_rejected(): void
    {
        foreach (['Identifier' => '', 'Title' => null, 'Catalog' => [], 'Modified' => 123] as $field => $invalid) {
            $fixture = $this->catalogue();
            $fixture['value'][0][$field] = $invalid;
            $this->fakeResponse($fixture);
            $this->assertSame(1, Artisan::call('data:discover-cbs'));
        }
    }

    public function test_a_source_ignoring_the_requested_row_limit_is_rejected(): void
    {
        $fixture = $this->catalogue();
        $fixture['value'][] = $fixture['value'][0];
        Http::fake(fn () => Http::response($fixture));
        $this->artisan('data:discover-cbs', ['--limit' => 1])
            ->expectsOutputToContain('overschrijdt het aangevraagde maximum')
            ->assertFailed();
    }

    public function test_nonstring_pagination_metadata_is_rejected(): void
    {
        $fixture = $this->catalogue();
        $fixture['@odata.nextLink'] = ['url' => 'https://example.org'];
        Http::fake(fn () => Http::response($fixture));
        $this->artisan('data:discover-cbs')
            ->expectsOutputToContain('pagineringsmetadata')
            ->assertFailed();
    }

    public function test_tls_verification_stays_enabled_and_redirects_disabled_with_optional_cloud_trust(): void
    {
        foreach ([null, '/run/test-public-ca.pem'] as $bundle) {
            config(['services.government_data.ca_bundle' => $bundle]);
            Http::swap(new Factory);
            Http::preventStrayRequests();
            $seenOptions = [];
            Http::fake(function (Request $request, array $options) use (&$seenOptions) {
                $seenOptions = $options;

                return Http::response($this->catalogue());
            });
            $this->assertSame(0, Artisan::call('data:discover-cbs'));
            $this->assertSame($bundle ?? true, $seenOptions['verify']);
            $this->assertFalse($seenOptions['allow_redirects']);
        }
    }
}
