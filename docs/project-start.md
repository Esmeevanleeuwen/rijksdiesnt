# Projectstart Rijksdienst

De aangeleverde projectpitch is de inhoudelijke referentie. Deze eerste stap bewijst een kleine doorsnede: PostgreSQL → Laravel-API → Vue-interface met zoeken en herkomst. Het platform is een onafhankelijk prototype en geen officiële overheidsdienst.

## Besluiten voor deze versie

- Laravel 12 / PHP 8.4, PostgreSQL 17, Vue 3 / TypeScript en Node 24, als modulaire monoliet.
- Interne identiteiten zijn UUID's. Organisatiegegevens staan apart van de centrale entiteit.
- Bronrecords en hun ontvangen inhoud worden afzonderlijk bewaard. Een herhaalde import van hetzelfde voorbeeldrecord maakt geen dubbele organisatie; gewijzigde inhoud krijgt een nieuwe bronrecordversie.
- Identificatie gebeurt binnen een bron en identificatieschema. Namen zijn geen koppelsleutel. Cross-source resolutie en hergebruik van historische identificaties volgen pas met expliciete regels.
- De enige import is een lokale fixture met fictieve organisaties. Dit is geen onderzocht ROO-importformaat. De API is alleen-lezen; er zijn geen publieke import- of beheerendpoints.
- PostgreSQL-tests gebruiken de aparte, wegwerpbare database `rijksdienst_testing`, gescheiden van `rijksdienst`.
- Redis, OpenSearch, PostGIS, gebruikersbeheer, een beheerportaal en AI volgen pas voor een concrete functie.

## Eerstvolgende mijlpalen

1. ROO onderzoeken: officieel endpoint/export, stabiele identificaties, paginering, wijzigingsgedrag en hergebruiklicentie vastleggen. Een mapping maken aan de hand van echte voorbeeldrecords.
2. Een connector bouwen met foutregistratie en importverslag. Bewijzen dat een mislukte of gedeeltelijke import geen bestaande organisaties intrekt. Eerst staging/review, dan publicatie.
3. Publicatievoorwaarden, conflictafhandeling en entiteitsversies toevoegen. De huidige bronrecordhistorie is nog geen volledige historie van ieder canoniek veld.
4. Getypeerde relaties toevoegen met toegestane entiteittypen, onderbouwing en beoordeling.
5. Een eerste echt organisatiedossier met bronverwijzingen valideren, vervolgens onderwerpen en documenten toevoegen.

## Grenzen

De ontwikkelstack is lokaal: ontwikkelservers, lokale database-inloggegevens en debug-instellingen. Productie vraagt onder andere geheimenbeheer, toegangscontrole voor beheer, monitoring, back-ups, beveiligings- en toegankelijkheidstoetsing, publicatieregels en een releaseproces. De voorbeeldimport is geen algemene importmotor voor gelijktijdige, externe imports.
