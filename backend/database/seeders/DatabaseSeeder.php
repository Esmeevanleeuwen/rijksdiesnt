<?php

namespace Database\Seeders;

use App\Domain\Organizations\DemoOrganizationImporter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public static function examples(): array
    {
        return [
            [
                'external_id' => 'demo-gemeente-001',
                'official_name' => 'Gemeente Voorbeeldstad',
                'display_name' => 'Gemeente Voorbeeldstad',
                'organization_type' => 'Gemeente',
                'description' => 'Fictieve gemeente voor het verkennen van lokale publieke informatie, dienstverlening en ruimtelijke ontwikkeling. Deze organisatie bestaat alleen in dit prototype.',
                'website_url' => null,
            ],
            [
                'external_id' => 'demo-bureau-001',
                'official_name' => 'Bureau Publieke Informatie',
                'display_name' => 'Bureau Publieke Informatie',
                'organization_type' => 'Uitvoeringsorganisatie',
                'description' => 'Fictieve uitvoeringsorganisatie die laat zien hoe organisatieprofielen en hun herkomst samenkomen. Dit is geen officiële overheidsorganisatie.',
                'website_url' => null,
            ],
            [
                'external_id' => 'demo-instituut-001',
                'official_name' => 'Instituut voor Omgevingsdata',
                'display_name' => 'Instituut voor Omgevingsdata',
                'organization_type' => 'Kennisinstituut',
                'description' => 'Fictief kennisinstituut rond leefomgeving en open data, uitsluitend bedoeld om de eerste versie van Rijksdienst te demonstreren.',
                'website_url' => null,
            ],
        ];
    }

    public function run(DemoOrganizationImporter $importer): void
    {
        $importer->import(self::examples());
    }
}
