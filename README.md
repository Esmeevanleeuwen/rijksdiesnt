# Rijksdienst

**De overheid in samenhang.** Een werkend prototype op basis van de projectpitch en het aangeleverde schermontwerp. De frontend toont Home, organisaties, organisatieprofielen, data, nieuws en een regiokaart in de stijl van **Overheidsinformatie Nederland**. Onafhankelijk project, geen officiële overheidsdienst.

De organisatiezoekfunctie en UUID-profielen gebruiken de Laravel-API met **fictieve voorbeeldgegevens**. Daarnaast is er een herkenbaar BZK-ontwerpvoorbeeld. Nieuws en datasets zijn lokale ontwerpinhoud, de kaart is schematisch. Er is nog geen koppeling met het Register van Overheidsorganisaties (ROO).

Zie [Frontend en schermroutes](docs/frontend.md) voor de nagebouwde schermen, interacties en afbeeldingsbronnen.

## Starten

Vereisten: Docker Engine met Compose v2, Git en een POSIX-shell. PHP, Composer, Node en PostgreSQL draaien in containers. Node 24 is alleen nodig wanneer je de frontend buiten Docker ontwikkelt.

```sh
git clone https://github.com/Esmeevanleeuwen/rijksdiesnt.git
cd rijksdiesnt
./scripts/setup
```

Heb je de repository al geopend in de cloudomgeving, ga dan naar `/workspace/rijksdiesnt` en voer daar `./scripts/setup` uit.

Setup bouwt PHP 8.4, installeert de vastgelegde afhankelijkheden, maakt ontbrekende lokale `.env`-bestanden aan, genereert een applicatiesleutel als die ontbreekt, start PostgreSQL 17, voert migraties en de herhaalbare voorbeeldseeder uit en start Laravel en Vite. Bestaande `.env`-bestanden blijven behouden. Gebruik deze setup alleen voor ontwikkeling.

De lokale poorten zijn 5173 voor de interface en 8000 voor de API. Vite stuurt `/api` door naar Laravel; de browser hoeft geen rechtstreeks database- of backendadres te kennen. De scripts herkennen de beheerde cloudomgeving en gebruiken daar de bestaande proxy en certificaten via `compose.cloud.yaml`.

Na een herstart van de omgeving:

```sh
./scripts/compose up -d --wait db
./scripts/compose up -d app frontend
```

## Controleren

```sh
./scripts/check
curl --fail http://127.0.0.1:5173/api/v1/organizations
curl --fail 'http://127.0.0.1:5173/api/v1/organizations?q=Voorbeeldstad'
./scripts/compose logs --tail=50 app frontend
```

De controles voeren PostgreSQL-integratie/API-tests, PHP-stijlcontrole, Vue-interactietests en de TypeScript/productiebuild uit. Backendtests gebruiken uitsluitend de afzonderlijke database `rijksdienst_testing`; een controle vóór de databaseverversing blokkeert iedere andere database. Gebruik nooit een productiedatabase voor deze tests. Beide databases worden aangemaakt bij het eerste gebruik van het lokale PostgreSQL-volume. Bij een ouder bestaand volume zonder testdatabase: `./scripts/compose exec db createdb -U rijksdienst rijksdienst_testing`.

## Structuur

- `backend/`: Laravel 12, `/api/v1`, UUID-kernschema, versieerbare bronrecords en voorbeeldimport.
- `frontend/`: Vue 3, TypeScript, Vite en Vitest.
- `docker/`, `compose.yaml`: lokale ontwikkelomgeving.
- `scripts/`: herhaalbare setup, Compose-wrapper en controles.
- `.github/workflows/checks.yml`: dezelfde backend- en frontendcontroles bij pushes en pull requests.
- [Projectbesluiten en volgende stappen](docs/project-start.md).
- [Eerste deel: opgeleverd en afbakening](docs/eerste-deel.md).

## API

`GET /api/v1/organizations` ondersteunt `q`, `type`, `page` en `per_page`. `GET /api/v1/organizations/{uuid}` toont één organisatie. Antwoorden bevatten `data` en `meta`, inclusief de demostatus. Ongeldige zoekparameters geven 422; een onbekende of ongeldige organisatie-ID geeft 404.

## Stoppen

```sh
./scripts/compose down
```

De database blijft in het Docker-volume bewaard. Verwijder dat volume alleen als je de lokale gegevens bewust wilt wissen. De ontwikkelservers zijn aan loopback gebonden en zijn geen productieconfiguratie.

De Laravel-projectbasis is afkomstig van `laravel/laravel` v12.12.2 (MIT); de licentieverwijzing staat in [de oorspronkelijke projectdocumentatie](https://github.com/laravel/laravel/tree/v12.12.2#license).
