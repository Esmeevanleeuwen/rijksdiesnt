# Echte overheidsdata: eerste selectie

Onderzocht op **3 november 2026**. Dit dossier combineert primaire documentatie van CBS, PDOK en de officiële overheidsregisters. Het machineleesbare overzicht staat in [`data/sources.json`](../data/sources.json).

De overheidssites geven vanuit deze cloudomgeving momenteel een proxy-`CONNECT 403`. Daarom zijn de onderstaande API-contracten gecontroleerd in officiële bronrepositories, maar **geen actuele API-respons, cijferwaarde of ROO-export live gevalideerd**. Die beperking staat ook per bron in de inventaris. De frontendvoorbeelden zijn niet als echte data aangemerkt en niet vervangen door verzonnen importresultaten.

## De bronnen die we eerst nodig hebben

| Bron/dataset | Wat halen we eruit? | Eerste toepassing | Onderbouwing |
| --- | --- | --- | --- |
| [ROO / TOOI](https://organisaties.overheid.nl/) | Organisaties en officiële externe identificaties | Organisatieruggengraat en bronverwijzingen | Het officiële API-register resolveert organisatielabels uit TOOI; het OSS-register verlangt een organisaties.overheid.nl-URL. Export, actuele velden en licentie nog controleren. |
| [CBS StatLine](https://www.cbs.nl/nl-nl/onze-diensten/open-data) | Tabellen over onder meer bevolking, huishoudens, woningvoorraad en regio’s | Eén actuele tabel selecteren voor het eerste datadossier | Officiële CBS-clients en voorbeelden. Een specifieke tabel is nog niet aan een concreet ministeriebeleidsdocument getoetst. |
| [PDOK Bestuurlijke Gebieden](https://api.pdok.nl/kadaster/bestuurlijkegebieden/ogc/v1) — `provinciegebied` | Geometrie van provinciale gebieden | Eerste kleine geografische dataset voor de kaart | De officiële PDOK-leermodule gebruikt deze API en collectie. Productieschema, feature-ID’s en licentie nog controleren. |
| [BAG via PDOK](https://api.pdok.nl/kadaster/bag/ogc/v2/collections/adres/items) — `adres` | Adressen en de relatie met BAG-objecten | Later adres- en gebouwcontext toevoegen | Officiële PDOK-documentatie beschrijft BAG als onderdeel van de landelijke basisregistraties die overheidsorganisaties gebruiken. Begin met één gebied en een kleine selectie. |
| [Data.overheid.nl](https://data.overheid.nl/) | Datasetbeschrijvingen, uitgevers, downloadlocaties en licenties | Datasetcatalogus en bronverantwoording | Dit is een metadatacatalogus; de cijfers blijven bij de oorspronkelijke bron. Het actuele API-pad moet nog worden gecontroleerd. |
| [Tweede Kamer Open Data](https://opendata.tweedekamer.nl/documentatie/odata-api) | Parlementaire documenten, vergaderingen en gebeurtenissen | Later politieke en documentaire context | Officieel OData-voorbeeld in de overheidscatalogusstandaard. Dit is een parlementaire bron, geen ministeriebron. |

**Wat betekent “de Rijksoverheid gebruikt dit”?** Een officiële uitgever is niet automatisch bewijs dat ieder ministerie iedere tabel gebruikt. Concreet gecontroleerd: ROO/TOOI-identiteiten worden in officiële registers gebruikt; officiële PDOK-documentatie beschrijft het overheidsgebruik van basisregistraties; CBS laat zelf zien hoe zijn statistieken met PDOK-geodata worden verbonden. Voor een specifieke statistische indicator leggen we later ook het bijbehorende beleidsdocument of de publicatie vast.

## Wat is daadwerkelijk in officiële documentatie gevonden?

### ROO / TOOI: de eerste identiteitslaag

Het officiële [API-registerschema](https://github.com/developer-overheid-nl/don-register-site/blob/32b71919a57df1e9819a17b94f4c95e8f38d9c41/apps/api-register/src/types/api-schema.prod.ts) gebruikt `OrganisationInput.uri` en vermeldt letterlijk: **“The label is resolved from TOOI.”** De officiële [OSS-registerinstructie](https://github.com/developer-overheid-nl/don-register-site/blob/32b71919a57df1e9819a17b94f4c95e8f38d9c41/apps/oss-register/src/pages/repositories/toevoegen.md) vraagt om de organisatie-URL op organisaties.overheid.nl.

Bewaar onze interne UUID, de externe organisatie-URI en de bronidentificatie afzonderlijk. Een CBS-gemeentecode, BAG-object-ID en ROO-organisatie-ID zijn verschillende identificaties. Overeenkomende namen zijn geen bewijs dat ze dezelfde entiteit voorstellen.

### CBS: actuele tabellen ontdekken via OData4

De officiële CBS-client heeft zijn basisadres gewijzigd naar **`https://datasets.cbs.nl/odata`**. Dit staat in [NEWS.md](https://github.com/statistiekcbs/cbsodata4/blob/e064dfa5f3cdc33d5040813d53917d23c6c750c5/NEWS.md) en [R/package.R](https://github.com/statistiekcbs/cbsodata4/blob/e064dfa5f3cdc33d5040813d53917d23c6c750c5/R/package.R).

Het officiële [cataloguscontract](https://github.com/statistiekcbs/cbsodata4/blob/e064dfa5f3cdc33d5040813d53917d23c6c750c5/R/cbs4_get_datasets.R) gebruikt `/Datasets` en de velden `Identifier`, `Title`, `Catalog`, `Modified` en `ObservationsModified`. Voor een geselecteerde tabel volgen `/CBS/{Identifier}/Properties`, `Dimensions`, `MeasureCodes` en `Observations`. Het clientcontract verwerkt `@odata.nextLink`.

Geschikte onderwerpen om uit de actuele catalogus te selecteren: **Regionale kerncijfers Nederland**, **Kerncijfers wijken en buurten**, **woningvoorraad**, **bevolking en huishoudens**. We kiezen nog geen vermeende laatste jaarversie of gegokte tabelcode.

Er zijn wel echte, **historische** voorbeelden gecontroleerd: de officiële OData4-README gebruikt `81575NED` (vestigingen van bedrijven) en `82245NED` (bevolking en huishoudens per viercijferige postcode op 1 januari 2013). De officiële OData3-code gebruikt `37296ned`. Deze voorbeelden bewijzen het gebruik van de interfaces; ze zijn geen bewijs dat dit de beste of nieuwste tabellen voor ons platform zijn.

De officiële [CBS Open Data v3-documentatie](https://github.com/statistiekcbs/CBS-Open-Data-v3/blob/8a5c78e12b9e9d550be77fbbd9c382a15af4a9ed/README.md#licentie) noemt Creative Commons Naamsvermelding en verplichte CBS-bronvermelding. De actuele copyrightvoorwaarden en de geselecteerde tabel blijven onderdeel van de controle; een softwarelicentie is geen datalicentie.

### PDOK: eerst gebieden, daarna adressen

De officiële [PDOK-voorbeeldcode](https://github.com/PDOK/leermodule-ogc-api/blob/f627d99187c962321535b2302f155e53f3cdb031/docs/voorbeelden/features/main.js) gebruikt Bestuurlijke Gebieden en `provinciegebied`. Inspecteer eerst `/collections`, de collectiemetadata, het schema en de queryables voordat we eigenschappen aan ons model koppelen. Geografische gebieden krijgen een eigen type, geen organisatieprofiel.

De officiële [BAG-leermodule](https://github.com/PDOK/leermodule-ogc-api/blob/f627d99187c962321535b2302f155e53f3cdb031/docs/features/CQL-filtering%20met%20de%20BAG%20OGC%20API.md) noemt de nieuwere route `https://api.pdok.nl/kadaster/bag/ogc/v2/collections/adres/items`. Documentatievelden zijn onder meer `postcode`, `huisnummer`, `woonplaats_naam` en `openbare_ruimte_naam`. Postcodes worden in het voorbeeld zonder spatie gebruikt. Controleer de echte object-ID’s en licentie voordat dit een import wordt.

De uitleg over [basisregistraties en overheidsgebruik](https://github.com/PDOK/leermodule-ogc-api/blob/f627d99187c962321535b2302f155e53f3cdb031/docs/achtergrondinformatie/Wat%20is%20geo-informatie.md) en [Kadaster/PDOK](https://github.com/PDOK/leermodule-ogc-api/blob/f627d99187c962321535b2302f155e53f3cdb031/docs/achtergrondinformatie/Wat%20doet%20het%20Kadaster%20-%20PDOK.md) zijn de primaire onderbouwing. CC0 in een Gokoala-voorbeeldconfiguratie is geen bewijs voor de productielicentie van BAG of Bestuurlijke Gebieden.

### Catalogi en een concreet later themabestand

Gebruik voor catalogusmetadata de actuele **[DCAT-AP-NL 3.0](https://docs.geostandaarden.nl/dcat/dcat-ap-nl30/)** als standaardreferentie. Het officiële oude [DCAT-AP-NL-repository](https://github.com/dataoverheid/DCAT-AP-NL/blob/f210b727463c2315baf6023cb40f57ec35108e84/README.md) verwijst daarheen. Een oud CKAN-codepad is geen bewijs van het huidige productiepad.

Een specifiek aangetroffen bestand is **Score Leefbaarometer Buurt 2020**. Het officiële [distributievoorbeeld](https://github.com/dataoverheid/dcat-ap-donl/blob/73218678126b062468fb28ac154c73898c6700c0/docs/klassen/distribution/examples.md) beschrijft CSV en CC0. Dit is een historische gedocumenteerde release, geen gecontroleerde laatste download. Voor een woondossier onderzoeken we eerst een actuele release, de gebruikte buurtcodes en het bijbehorende beleidsgebruik.

Het huidige [API-register](https://apis.developer.overheid.nl/) heeft volgens de [officiële documentatie](https://github.com/developer-overheid-nl/don-register-site/blob/32b71919a57df1e9819a17b94f4c95e8f38d9c41/apps/api-register/src/pages/apis/toevoegen.md) ook voor lezen een eigen API-sleutel nodig. Dit is een latere bron, geen voorwaarde om met CBS/PDOK te beginnen. Er is geen sleutel overgenomen of aangevraagd.

## Eerste uitvoerbare stap: CBS-metadata inspecteren

Haal de wijziging op in je bestaande projectmap. In Windows PowerShell:

```powershell
git pull origin main
docker compose run --rm --no-deps app php artisan data:discover-cbs --limit=5 --json
```

Dit vraagt maximaal vijf catalogusrecords op bij CBS en toont hun oorspronkelijke metadatavelden, bron en ophaaltijd. Het is **geen globale zoekfunctie, volledige catalogusdownload of import van cijfers**. Het commando schrijft niets in de database en gebruikt geen fictieve gegevens als terugval. Bij een netwerk- of schemafout stopt het met een foutcode.

De HTTP-route en velden zijn in officiële documentatie gecontroleerd; de actuele productieaanroep is vanuit deze cloud nog geblokkeerd. Op je eigen computer geldt die cloudblokkade niet automatisch. Een geslaagde run toont pas wat de huidige catalogus werkelijk teruggeeft.

## Volgorde voor de eerste echte import

1. **Bron controleren:** ophaalmethode, actuele metadata, externe ID’s, rechten/licentie en eventuele beperkingen vastleggen.
2. **ROO-contract onderzoeken:** een kleine echte selectie organisaties ontvangen en expliciet mappen. Geen onbevestigde organisatievelden publiceren.
3. **Geografische selectie:** Bestuurlijke Gebieden inspecteren en de regio-identificaties apart bewaren. Alleen onderbouwde verwijzingen koppelen aan organisaties.
4. **Eén CBS-tabel kiezen:** titel, actualiteit, populatie, meeteenheid, perioden, regio’s, ontbrekende/onderdrukte waarden en hergebruikvoorwaarden controleren. Een ontbrekend of onderdrukt cijfer wordt nooit automatisch nul.
5. **Herhaalbare import:** ruwe bronrespons, ophaaltijd, bronversie, mappingversie en herkomst opslaan. Een tweede import mag geen duplicaten maken; een gedeeltelijke of mislukte import trekt geen bestaande gegevens in.
6. **Publiceren:** pas na deze controles echte waarden naar de API en frontend doorzetten, met bron, periode, eenheid en actualiteit zichtbaar.

## Toegang vanuit de cloud

Er zijn gerichte domeintoestemmingen als concept opgeslagen voor de onderzochte overheidsbronnen, waaronder `datasets.cbs.nl`, `organisaties.overheid.nl` en `api.pdok.nl`. De instellingen zijn nog niet in het actieve netwerk toegepast. Bekijk en sla de wijziging op in de omgevingsinstellingen en publiceer de omgeving. Daarna moet de live aanvraag opnieuw worden gecontroleerd. Een opgeslagen concept is geen bewijs van bereikbare of geïmporteerde data.
