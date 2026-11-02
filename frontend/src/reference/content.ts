export interface Theme {
  id: string;
  title: string;
  description: string;
  icon: string;
  color: string;
}

export interface Dataset {
  id: string;
  title: string;
  source: string;
  period: string;
  description: string;
  tags: string[];
  theme: string;
  icon: string;
  color: string;
  isNew: boolean;
}

export interface NewsItem {
  id: string;
  title: string;
  category: string;
  color: string;
  time: string;
  source: string;
  tab: string;
  image: string;
  excerpt: string;
}

export const themes: Theme[] = [
  {
    id: "migratie",
    title: "Migratie",
    description: "Beleid, cijfers en ontwikkelingen rondom migratie.",
    icon: "people",
    color: "cyan",
  },
  {
    id: "wonen",
    title: "Woningcrisis",
    description: "Inzicht in plannen, bouwprojecten en woningvoorraad.",
    icon: "home",
    color: "pink",
  },
  {
    id: "veiligheid",
    title: "Oorlog & Veiligheid",
    description: "Nieuws, beleid en internationale ontwikkelingen.",
    icon: "shield",
    color: "teal",
  },
  {
    id: "klimaat",
    title: "Klimaat & Duurzaamheid",
    description: "Maatregelen, data en projecten.",
    icon: "leaf",
    color: "green",
  },
  {
    id: "economie",
    title: "Economie & Financiën",
    description: "Begrotingen en economische ontwikkelingen.",
    icon: "chart",
    color: "blue",
  },
  {
    id: "onderwijs",
    title: "Onderwijs",
    description: "Kennis, onderwijs en onderzoek.",
    icon: "document",
    color: "blue",
  },
  {
    id: "gezondheid",
    title: "Gezondheid",
    description: "Publieke gezondheid en zorg.",
    icon: "people",
    color: "pink",
  },
  {
    id: "ruimtelijk",
    title: "Ruimtelijke Ordening",
    description: "De inrichting van Nederland.",
    icon: "map",
    color: "purple",
  },
];

export const datasets: Dataset[] = [
  {
    id: "woningvoorraad",
    title: "Woningvoorraad Nederland",
    source: "CBS",
    period: "Nederland · 2024",
    description: "Informatie over de woningvoorraad, nieuwbouw en leegstand.",
    tags: ["Woningcrisis", "Wonen"],
    theme: "wonen",
    icon: "data",
    color: "cyan",
    isNew: false,
  },
  {
    id: "migratie",
    title: "Migratiecijfers",
    source: "IND",
    period: "Alle jaren · 2000–2024",
    description: "Cijfers over asiel, verblijf en naturalisatie.",
    tags: ["Migratie", "Bevolking"],
    theme: "migratie",
    icon: "people",
    color: "purple",
    isNew: false,
  },
  {
    id: "stikstof",
    title: "Stikstofdepositie",
    source: "RIVM",
    period: "Milieu · 2024",
    description: "Kaarten en cijfers over stikstofdepositie in Nederland.",
    tags: ["Klimaat", "Milieu"],
    theme: "klimaat",
    icon: "leaf",
    color: "green",
    isNew: true,
  },
  {
    id: "overheidsuitgaven",
    title: "Overheidsuitgaven",
    source: "CBS",
    period: "Financiën · 2024",
    description: "Uitgaven van de Rijksoverheid per ministerie.",
    tags: ["Financiën", "Rijksoverheid"],
    theme: "economie",
    icon: "document",
    color: "blue",
    isNew: false,
  },
  {
    id: "bouwlocaties",
    title: "Bouwlocaties en woningbouwplannen",
    source: "Rijksoverheid",
    period: "Wonen · 2024",
    description: "Locaties en plannen voor nieuwbouwprojecten.",
    tags: ["Wonen", "Ruimtelijke ordening"],
    theme: "ruimtelijk",
    icon: "data",
    color: "teal",
    isNew: true,
  },
];

export const newsItems: NewsItem[] = [
  {
    id: "woningbouw",
    title: "Kabinet presenteert nieuwe plannen voor woningbouw",
    category: "Wonen",
    color: "blue",
    time: "Vandaag · 10:24",
    source: "Rijksoverheid",
    tab: "Rijksoverheid",
    image: "/images/binnenhof.svg",
    excerpt:
      "Ontdek hoe informatie over woningbouwplannen, betrokken organisaties en openbare documenten in één overzicht kan samenkomen. Dit bericht is voorbeeldinhoud voor het ontwerp van het platform.",
  },
  {
    id: "oekraine",
    title: "Nederland versterkt steun aan Oekraïne",
    category: "Buitenland",
    color: "cyan",
    time: "Vandaag · 09:32",
    source: "Ministerie van Buitenlandse Zaken",
    tab: "Rijksoverheid",
    image: "/images/canal.svg",
    excerpt:
      "Dit voorbeeld laat zien hoe internationaal nieuws aan beleidsinformatie en verantwoordelijke organisaties kan worden gekoppeld. Het is geen actuele of gecontroleerde nieuwsrapportage.",
  },
  {
    id: "stikstof",
    title: "Nieuwe data over stikstof beschikbaar",
    category: "Milieu",
    color: "green",
    time: "Gisteren · 16:45",
    source: "Data.overheid.nl",
    tab: "Rijksoverheid",
    image: "/images/nature.svg",
    excerpt:
      "Een voorbeeld van een bericht bij een nieuwe dataset. In een volgende versie worden de daadwerkelijke bron, meetperiode, definities en publicatiedatum rechtstreeks bij de gegevens getoond.",
  },
  {
    id: "jeugdzorg",
    title: "Gemeenten ontvangen extra middelen voor jeugdzorg",
    category: "Zorg",
    color: "pink",
    time: "Gisteren · 14:30",
    source: "VNG",
    tab: "Gemeenten",
    image: "/images/care.svg",
    excerpt:
      "In dit ontwerpvoorbeeld komen gemeentelijk nieuws en informatie over publieke dienstverlening samen. De inhoud is uitsluitend bedoeld om de interface te demonstreren.",
  },
  {
    id: "economie",
    title: "Nederlandse economie groeit licht in derde kwartaal",
    category: "Economie",
    color: "pink",
    time: "Gisteren · 12:00",
    source: "CBS",
    tab: "Rijksoverheid",
    image: "/images/skyline.svg",
    excerpt:
      "Een voorbeeldbericht over economische statistieken. Er worden hier geen feitelijke groeicijfers of conclusies gepubliceerd; een echte koppeling aan de bron volgt later.",
  },
  {
    id: "provincies",
    title: "Provincies werken samen aan een duurzame leefomgeving",
    category: "Klimaat",
    color: "green",
    time: "Voorbeeld · 11:15",
    source: "Provincies",
    tab: "Provincies",
    image: "/images/nature.svg",
    excerpt:
      "Voorbeeldinhoud voor de provinciale nieuwsselectie. Hier kunnen later gecontroleerde berichten van provincies worden weergegeven.",
  },
  {
    id: "waterschappen",
    title: "Waterschappen bereiden zich voor op het nieuwe seizoen",
    category: "Water",
    color: "cyan",
    time: "Voorbeeld · 09:00",
    source: "Waterschappen",
    tab: "Waterschappen",
    image: "/images/canal.svg",
    excerpt:
      "Voorbeeldinhoud voor de nieuwsselectie van waterschappen. Publicatie en herkomst van echte berichten worden bij een bronkoppeling vastgelegd.",
  },
];
