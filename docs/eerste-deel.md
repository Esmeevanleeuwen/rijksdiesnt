# Eerste deel — werkend fundament

Deze eerste versie legt de basis voor Rijksdienst en maakt het aangeleverde frontendontwerp navigeerbaar. De verticale doorsnede bestaat uit een bronrecord, een organisatie in PostgreSQL, de Laravel-API en een organisatieprofiel in Vue.

## Opgeleverd

- Docker Compose met PostgreSQL 17, PHP 8.4/Laravel 12 en Node 24/Vue 3 met TypeScript.
- UUID-kernschema voor entiteiten, organisaties, bronnen, bronrecords, bronrecordversies, externe identificaties en herkomstkoppelingen.
- Een herhaalbare, transactionele voorbeeldimport: dezelfde identificatie houdt dezelfde organisatie; gewijzigde broninhoud krijgt een nieuwe versie; ontbrekende records worden niet verwijderd.
- Zoek- en detail-API met validatie, begrensde paginering en bronvermelding.
- Home, organisaties, organisatieprofiel, data, nieuws, een schematische regiokaart en het mobiele menu volgens het schermvoorbeeld.
- Werkende navigatie, lokale dataset- en nieuwsfilters, detaildialogen en lokaal opgeslagen organisaties.
- Opstart- en controlescripts, lockbestanden en automatische GitHub-controles voor backend en frontend.

## Gecontroleerd

`./scripts/check` voert 19 PostgreSQL/API/importtests, PHP-stijlcontrole, 22 frontendtests en de TypeScript/productiebuild uit. De backendtests zijn vóór het verversen van de database beperkt tot `rijksdienst_testing`.

De interface is daarnaast in Chromium doorlopen op desktop en mobiel, inclusief zoeken via de Laravel-API, bronvermelding, thema- en nieuwslinks, kaartfilters, het mobiele menu en de lokale feedbackdialoog.

## Volgend deel

De volgende stap is het onderzoeken van het echte Register van Overheidsorganisaties: endpoint of export, stabiele identificaties, wijzigingen, paginering en hergebruikvoorwaarden. Daarna volgt een connector met foutregistratie, expliciete mapping en publicatiecontroles.

De huidige versie bevat fictieve organisaties en aanvullende ontwerpvoorbeelden. Het BZK-profiel, nieuws en datasets zijn geen gekoppelde live registraties. De regiokaart is schematisch. Voor volledige visuele overeenkomst moeten de vervangende illustraties worden vervangen door de oorspronkelijke foto-, logo- en lettertypebestanden. Deze ontwikkelversie is niet ingericht voor publieke productie.
