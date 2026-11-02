# Frontend volgens het schermvoorbeeld

De frontend volgt het aangeleverde samengestelde schermontwerp: marineblauwe navigatie, een compacte zoekbalk met profiel, lichtblauwe kaarten, tabbladen, nieuwslijsten, een regiokaart en een volledig mobiel menu. Home heeft op desktop een vaste zijbalk; de verdiepende schermen gebruiken de compacte kop uit het voorbeeld. Op mobiel zijn er een donkere kop, drie hoofdcategorieën, gestapelde thema’s, een onderste navigatie en het uitschuifmenu met feedbackkaart.

| Route na `#` | Scherm |
| --- | --- |
| `/` | Home met zoeken, categorieën, thema’s en nieuws |
| `/organisaties` | Organisatiezoekfunctie met Laravel-gegevens en BZK-ontwerpkaart |
| `/organisatie/bzk` | Ministerieprofiel uit het ontwerp, gemarkeerd als ontwerpvoorbeeld |
| `/organisatie/{uuid}` | Organisatieprofiel uit de bestaande Laravel-API, inclusief bronvermelding |
| `/data` | Datasets, tabbladen, themafilter, zoekfunctie en detaildialoog |
| `/nieuws` | Nieuws met bestuurslaagfilters en artikelweergave |
| `/kaarten` | Schematische kaart, voorbeeldlocaties, legenda, zoeken, filters en zoomen |
| `/mijn-overzicht` | Lokaal opgeslagen organisaties |
| `/beleid` | Thematische ingang tot beschikbare voorbeelddata |
| `/instellingen` | Tekstgrootte tijdens het bezoek |
| `/help` | Uitleg en lokale feedback |

Themalinks gebruiken `/data?thema=wonen`. Nieuwslinks gebruiken bijvoorbeeld `/nieuws?bericht=woningbouw`. Bestaande links `#organisatie/{uuid}` blijven ondersteund.

## Interacties en gegevens

- Globaal en vanuit Home zoeken leidt naar de bestaande organisatie-API. Zoekopdrachten blijven URL-gecodeerd; verouderde verzoeken worden afgebroken.
- Organisaties opslaan werkt via `localStorage` en wordt meteen zichtbaar in Mijn overzicht. Delen kopieert de huidige URL, met een kopieerbaar veld als het klembord niet beschikbaar is.
- Dataset- en nieuwsfilters en detaildialogen werken lokaal. Het zijn geen aangesloten live databronnen; de interface vermeldt dit.
- De kaart bevat 24 voorbeeldlocaties. Geolocatie wordt alleen na een klik aangevraagd. De detailslink zoekt de geselecteerde organisatie op; ze verwijst niet naar een ander organisatieprofiel.
- Feedback wordt uitsluitend op het apparaat bewaard. Er is geen verzending naar een externe partij en geen echte gebruikersaanmelding.
- Menu en dialogen ondersteunen Escape, toetsenbordfocus en focusbegrenzing. Navigatie, invoervelden en pictogramknoppen hebben toegankelijke namen. De schermen zijn gecontroleerd op horizontale overloop bij 320, 390, 700, 768, 1024 en 1440 pixels.

## Afbeeldingen en nauwkeurigheid

De compositie en componenten zijn nagebouwd aan de hand van de referentie. De originele fotografische bestanden, exacte lettertypebestanden en het oorspronkelijke logo zijn niet meegeleverd. De huidige SVG-afbeeldingen en het gestileerde wapensymbool zijn vervangende illustraties, geen exacte reconstructies of officiële branding. Voor een volledig identieke visuele versie moeten die oorspronkelijke assets worden vervangen.

Zie `frontend/public/images/SOURCES.md` voor de illustraties. De lokale kaartgeometrie komt van MapSVG via `@svg-maps/netherlands` en valt onder CC BY 4.0; de bronvermelding staat in de SVG en op de kaart. De applicatie doet geen externe afbeeldings- of kaarttegelverzoeken.

## Ontwikkelen en controleren

Gebruik de bestaande `scripts/setup`, `scripts/compose` en `scripts/check`. Er zijn geen extra runtime-afhankelijkheden toegevoegd. Frontendtests: `cd frontend && npm test`; productiebuild met TypeScript-controle: `npm run build`. De frontend bevat 22 tests voor navigatie, backendzoekroutes, bronvermelding, bookmarkintegratie, verouderde verzoeken, dataset- en nieuwsfilters, dialogen en kaartbediening.
