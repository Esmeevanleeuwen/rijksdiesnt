# Govern koppelen aan Rijksdienst

Rijksdienst blijft PostgreSQL gebruiken. Laravel schrijft gegevens via de bestaande projectverbinding. Govern krijgt een eigen leesaccount voor dezelfde database en toont de tabellen, kolommen, records en relaties. Er wordt geen tweede kopie van de Rijksdienst-datasets gemaakt.

## Vereisten

- Rijksdienst is geïnstalleerd en gestart met `start.cmd` of `scripts/setup`.
- Govern is geïnstalleerd, gestart en bevat de PostgreSQL-uitbreiding (`app/src/PostgresAdapter.php`). Gebruik je samengevoegde Govern-versie met deze ondersteuning; een checkout zonder deze adapter wordt door de helper geweigerd.
- Beide projecten gebruiken dezelfde Docker Desktop-installatie met Linux-containers.
- De huidige Govern PostgreSQL-uitbreiding ondersteunt alleen bekijken en zoeken. Laravel blijft verantwoordelijk voor imports en wijzigingen.

De helper wisselt geen Govern-branch en herschrijft geen Govern-bronbestanden of bestaande `.env`. Er is geen wijziging aan de Govern-repository nodig.

## Windows PowerShell

Start Govern eerst vanuit de Govern-map. Open vervolgens PowerShell in de bestaande Rijksdienst-map:

```powershell
git pull origin main
.\connect-govern.cmd -GovernPath "C:\Users\esmee\govern"
```

Staan `govern` en `rijksdiesnt` naast elkaar onder dezelfde gebruikersmap, dan is dit voldoende:

```powershell
.\connect-govern.cmd
```

Na **Klaar** open je het bestaande Govern-venster, ververs je de pagina en log je zo nodig opnieuw in. Kies **rijksdienst · public · PostgreSQL** in de databasekiezer. Onder Gegevens zie je de Rijksdienst-tabellen; onder Schema de foreign keys en relaties. In Explore kun je vanuit een startrecord de gekoppelde records volgen. In deze ontwikkelversie staan daar nog de gemarkeerde voorbeeldorganisaties; het CBS-verkenningscommando heeft geen cijfers geïmporteerd.

Dezelfde PowerShell-helper kan ook met PowerShell 7 op Linux/macOS worden gestart:

```sh
pwsh -NoProfile -File scripts/connect-govern.ps1 -GovernPath /pad/naar/govern
```

## Wat de helper uitvoert

1. Controleert Docker, de Govern-map, PostgreSQL-ondersteuning en de bestaande Govern-database/gateway.
2. Start de bestaande Rijksdienst-database en voert eventuele projectmigraties uit.
3. Bereidt een afzonderlijke rol `rijksdienst_govern_reader` voor met CONNECT, USAGE op `public` en SELECT. De normale Laravel-verbinding blijft gescheiden.
4. Leest alleen het containeradres en het bestaande Docker-netwerk uit. De databasepoort krijgt geen publieke hostbinding.
5. Past `compose.govern.yaml` als lokale overlay toe op uitsluitend de Govern-webapp. De webapp krijgt naast zijn bestaande netwerk toegang tot het Rijksdienst-netwerk.
6. Controleert via de echte Govern-configuratie en PostgreSQL-adapter dat de juiste database, leesrol en Rijksdienst-tabellen zichtbaar zijn. Een UPDATE zonder passende records moet ook bij uitgeschakelde sessie-readonly door de databaserechten worden geweigerd.

De helper start geen extra Govern-PostgreSQL-container of diens bootstrapdienst. Govern ondersteunt momenteel één geconfigureerde PostgreSQL-verbinding; de overlay kiest daarvoor Rijksdienst. Een eventuele bestaande `govern_pg`-database en zijn volume worden behouden.

## Lokaal verbindingsprofiel

De automatisch gegenereerde leesgegevens staan onder:

```text
backend/storage/app/private/govern/reader.env
backend/storage/app/private/govern/network.env
```

Deze bestanden staan buiten de webroot en worden door Git genegeerd. Het wachtwoord wordt niet in console-uitvoer opgenomen. De helper geeft de bestanden rechtstreeks aan Docker Compose door; je hoeft geen wachtwoord in chat of broncode te plakken.

Herhalen behoudt het wachtwoord. Als de rol al bestaat maar het profiel ontbreekt, de bestaande rol niet van deze koppeling is of het profiel niet klopt, stopt de voorbereiding. Het script neemt geen andere rol over en roteert geen wachtwoorden automatisch.

SELECT op toekomstige tabellen geldt voor tabellen die dezelfde huidige Laravel-databasegebruiker in `public` maakt. Een andere tabelmaker of een ander schema vraagt expliciete aanvullende leesrechten. Bestaande rechten van PUBLIC en andere gebruikers worden niet ingetrokken. Onverwachte persistente schrijf- of aanmaakrechten van de lezer laten de voorbereiding stoppen. Eventuele bestaande PUBLIC-TEMP-rechten worden niet veranderd; Govern exposeert voor PostgreSQL uitsluitend zijn leesfuncties.

## Opnieuw starten

De overlay wordt toegepast door `connect-govern.cmd`. Een gewone start vanuit Govern met zijn eigen `start.cmd` gebruikt zijn eigen instellingen. Voer daarna de Rijksdienst-koppeling opnieuw uit om weer de Rijksdienst-verbinding te selecteren. Doe hetzelfde nadat de Rijksdienst-databasecontainer opnieuw is gemaakt.

Gebruik voor deze koppeling geen volume-verwijdering of databaseherstel. De helper voert die handelingen niet uit.

## Controleren

```powershell
docker compose run --rm --no-deps app php artisan govern:prepare-reader --json
```

Deze uitvoer bevat alleen niet-geheime metadata, zoals database, schema, rol en bestandslocatie. Voor een volledige netwerk- en Govern-controle gebruik je opnieuw `connect-govern.cmd`.

De geautomatiseerde controles omvatten echte PostgreSQL-leesrechten en geweigerde INSERT/UPDATE/DELETE/DDL in de afzonderlijke testdatabase, wachtwoordbehoud, rolconflicten en gesimuleerde PowerShell-succes- en foutpaden.

De volledige helper is ook met PowerShell 7 en Docker op Linux uitgevoerd tegen een afzonderlijke Govern-installatie op `feature/postgresql-demo-data` (`fccb4b3`). De echte Govern-adapter zag acht Rijksdienst-tabellen en weigerde schrijven. In de browser zijn de databasekeuze, voorbeeldrecords, leesmodus, foreign keys in Schema en behoud van de selectie na verversen gecontroleerd. Een test in deze cloud betekent niet dat een verbinding op jouw Windows-computer al is ingesteld; Docker Desktop op Windows is hier niet uitgevoerd.

De koppeling is daarna opnieuw uitgevoerd met de samengevoegde Govern-versie `integrate/all-govern-updates` (`d94d6e5`). De bestaande Rijksdienst-leesrol en het bestaande Govern-account bleven bruikbaar. De adapter zag opnieuw acht tabellen en weigerde schrijven, ook met uitgeschakelde sessie-readonly. De browsercontrole bevestigde records, het schemadiagram, Explore met de exacte UUID-sleutel van een organisatie en behoud van de databasekeuze na herladen, zonder browser- of API-fouten. Deze herkoppeling vereiste geen wijziging aan de helper of de Govern-broncode.
