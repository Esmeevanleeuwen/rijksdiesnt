<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import Icon from "../components/Icon.vue";

type Category =
  | "Gemeente"
  | "Provincie"
  | "Waterschap"
  | "Rijksoverheid"
  | "Samenwerkingsverband"
  | "Overig";
interface MapLocation {
  id: string;
  city: string;
  name: string;
  type: Category;
  level: string;
  province: string;
  x: number;
  y: number;
  latitude: number;
  longitude: number;
}
const emit = defineEmits<{ "open-organization": [name: string] }>();
const categories: Category[] = [
  "Gemeente",
  "Provincie",
  "Waterschap",
  "Rijksoverheid",
  "Samenwerkingsverband",
  "Overig",
];
const colors: Record<Category, string> = {
  Gemeente: "#2199dd",
  Provincie: "#f0aa27",
  Waterschap: "#62b9b5",
  Rijksoverheid: "#1668b7",
  Samenwerkingsverband: "#9474d9",
  Overig: "#a8b55a",
};
const locations: MapLocation[] = [
  {
    id: "amsterdam",
    city: "Amsterdam",
    name: "Gemeente Amsterdam",
    type: "Gemeente",
    level: "Lokaal",
    province: "Noord-Holland",
    x: 242,
    y: 306,
    latitude: 52.37,
    longitude: 4.89,
  },
  {
    id: "haarlem",
    city: "Haarlem",
    name: "Provincie Noord-Holland",
    type: "Provincie",
    level: "Provinciaal",
    province: "Noord-Holland",
    x: 203,
    y: 310,
    latitude: 52.38,
    longitude: 4.64,
  },
  {
    id: "zaandam",
    city: "Zaandam",
    name: "Gemeente Zaanstad",
    type: "Gemeente",
    level: "Lokaal",
    province: "Noord-Holland",
    x: 226,
    y: 284,
    latitude: 52.44,
    longitude: 4.82,
  },
  {
    id: "alkmaar",
    city: "Alkmaar",
    name: "Gemeente Alkmaar",
    type: "Gemeente",
    level: "Lokaal",
    province: "Noord-Holland",
    x: 230,
    y: 237,
    latitude: 52.63,
    longitude: 4.75,
  },
  {
    id: "waternet",
    city: "Amstelveen",
    name: "Voorbeeld waterschapsorganisatie",
    type: "Waterschap",
    level: "Regionaal",
    province: "Noord-Holland",
    x: 238,
    y: 328,
    latitude: 52.3,
    longitude: 4.86,
  },
  {
    id: "utrecht",
    city: "Utrecht",
    name: "Gemeente Utrecht",
    type: "Gemeente",
    level: "Lokaal",
    province: "Utrecht",
    x: 279,
    y: 379,
    latitude: 52.09,
    longitude: 5.12,
  },
  {
    id: "utrecht-provincie",
    city: "Utrecht",
    name: "Provincie Utrecht",
    type: "Provincie",
    level: "Provinciaal",
    province: "Utrecht",
    x: 292,
    y: 374,
    latitude: 52.09,
    longitude: 5.15,
  },
  {
    id: "den-haag",
    city: "Den Haag",
    name: "Voorbeeld rijksorganisatie",
    type: "Rijksoverheid",
    level: "Rijk",
    province: "Zuid-Holland",
    x: 149,
    y: 381,
    latitude: 52.08,
    longitude: 4.3,
  },
  {
    id: "leiden",
    city: "Leiden",
    name: "Gemeente Leiden",
    type: "Gemeente",
    level: "Lokaal",
    province: "Zuid-Holland",
    x: 177,
    y: 354,
    latitude: 52.16,
    longitude: 4.5,
  },
  {
    id: "delft",
    city: "Delft",
    name: "Regionaal samenwerkingsverband Delft",
    type: "Samenwerkingsverband",
    level: "Regionaal",
    province: "Zuid-Holland",
    x: 163,
    y: 402,
    latitude: 52.01,
    longitude: 4.36,
  },
  {
    id: "rotterdam",
    city: "Rotterdam",
    name: "Gemeente Rotterdam",
    type: "Gemeente",
    level: "Lokaal",
    province: "Zuid-Holland",
    x: 177,
    y: 422,
    latitude: 51.92,
    longitude: 4.48,
  },
  {
    id: "gouda",
    city: "Gouda",
    name: "Voorbeeld waterbeheer Gouda",
    type: "Waterschap",
    level: "Regionaal",
    province: "Zuid-Holland",
    x: 216,
    y: 399,
    latitude: 52.01,
    longitude: 4.71,
  },
  {
    id: "dordrecht",
    city: "Dordrecht",
    name: "Gemeente Dordrecht",
    type: "Gemeente",
    level: "Lokaal",
    province: "Zuid-Holland",
    x: 210,
    y: 452,
    latitude: 51.81,
    longitude: 4.67,
  },
  {
    id: "almere",
    city: "Almere",
    name: "Regionaal samenwerkingsverband Almere",
    type: "Samenwerkingsverband",
    level: "Regionaal",
    province: "Flevoland",
    x: 290,
    y: 302,
    latitude: 52.35,
    longitude: 5.26,
  },
  {
    id: "lelystad",
    city: "Lelystad",
    name: "Provincie Flevoland",
    type: "Provincie",
    level: "Provinciaal",
    province: "Flevoland",
    x: 325,
    y: 271,
    latitude: 52.51,
    longitude: 5.47,
  },
  {
    id: "amersfoort",
    city: "Amersfoort",
    name: "Gemeente Amersfoort",
    type: "Gemeente",
    level: "Lokaal",
    province: "Utrecht",
    x: 325,
    y: 350,
    latitude: 52.16,
    longitude: 5.38,
  },
  {
    id: "arnhem",
    city: "Arnhem",
    name: "Samenwerkingsverband Arnhem",
    type: "Samenwerkingsverband",
    level: "Regionaal",
    province: "Gelderland",
    x: 402,
    y: 407,
    latitude: 51.98,
    longitude: 5.91,
  },
  {
    id: "apeldoorn",
    city: "Apeldoorn",
    name: "Voorbeeld publieke instelling Apeldoorn",
    type: "Overig",
    level: "Regionaal",
    province: "Gelderland",
    x: 404,
    y: 356,
    latitude: 52.21,
    longitude: 5.97,
  },
  {
    id: "nijmegen",
    city: "Nijmegen",
    name: "Gemeente Nijmegen",
    type: "Gemeente",
    level: "Lokaal",
    province: "Gelderland",
    x: 398,
    y: 450,
    latitude: 51.84,
    longitude: 5.85,
  },
  {
    id: "breda",
    city: "Breda",
    name: "Voorbeeld publieke instelling Breda",
    type: "Overig",
    level: "Lokaal",
    province: "Noord-Brabant",
    x: 240,
    y: 511,
    latitude: 51.59,
    longitude: 4.78,
  },
  {
    id: "den-bosch",
    city: "Den Bosch",
    name: "Voorbeeld rijksorganisatie Den Bosch",
    type: "Rijksoverheid",
    level: "Rijk",
    province: "Noord-Brabant",
    x: 312,
    y: 480,
    latitude: 51.7,
    longitude: 5.3,
  },
  {
    id: "tilburg",
    city: "Tilburg",
    name: "Voorbeeld waterbeheer Tilburg",
    type: "Waterschap",
    level: "Regionaal",
    province: "Noord-Brabant",
    x: 286,
    y: 528,
    latitude: 51.56,
    longitude: 5.09,
  },
  {
    id: "eindhoven",
    city: "Eindhoven",
    name: "Gemeente Eindhoven",
    type: "Gemeente",
    level: "Lokaal",
    province: "Noord-Brabant",
    x: 334,
    y: 546,
    latitude: 51.44,
    longitude: 5.47,
  },
  {
    id: "zwolle",
    city: "Zwolle",
    name: "Gemeente Zwolle",
    type: "Gemeente",
    level: "Lokaal",
    province: "Overijssel",
    x: 434,
    y: 271,
    latitude: 52.51,
    longitude: 6.1,
  },
];
const cities = [
  { name: "Amsterdam", x: 251, y: 306 },
  { name: "Utrecht", x: 288, y: 391 },
  { name: "Den Haag", x: 109, y: 380 },
  { name: "Rotterdam", x: 184, y: 438 },
  { name: "Eindhoven", x: 346, y: 549 },
  { name: "Arnhem", x: 411, y: 411 },
  { name: "Nijmegen", x: 408, y: 457 },
  { name: "Zwolle", x: 445, y: 274 },
  { name: "Breda", x: 213, y: 526 },
  { name: "Tilburg", x: 293, y: 538 },
  { name: "Leeuwarden", x: 379, y: 108 },
  { name: "Groningen", x: 509, y: 97 },
  { name: "Enschede", x: 544, y: 349 },
  { name: "Maastricht", x: 342, y: 687 },
];
const locationQuery = ref("");
const appliedQuery = ref("");
const organizationType = ref("");
const level = ref("");
const province = ref("");
const selectedId = ref("amsterdam");
const zoom = ref(1);
const center = ref({ x: 306.5, y: 421 });
const notice = ref("");
const locating = ref(false);
const canvas = ref<HTMLElement | null>(null);
const size = ref({ width: 820, height: 545 });
let observer: ResizeObserver | undefined;
const provinces = [...new Set(locations.map((item) => item.province))].sort();
const visibleLocations = computed(() =>
  locations.filter(
    (item) =>
      (!organizationType.value || item.type === organizationType.value) &&
      (!level.value || item.level === level.value) &&
      (!province.value || item.province === province.value) &&
      (!appliedQuery.value ||
        `${item.city} ${item.name} ${item.province}`
          .toLocaleLowerCase("nl")
          .includes(appliedQuery.value.toLocaleLowerCase("nl"))),
  ),
);
const selected = computed(() =>
  visibleLocations.value.find((item) => item.id === selectedId.value),
);
const bounds = computed(() => ({
  x: center.value.x - 306.5 / zoom.value,
  y: center.value.y - 250 / zoom.value,
  width: 613 / zoom.value,
  height: 500 / zoom.value,
}));
const viewBox = computed(
  () =>
    `${bounds.value.x} ${bounds.value.y} ${bounds.value.width} ${bounds.value.height}`,
);
const popupStyle = computed(() => {
  const item = selected.value;
  if (!item) return {};
  const scale = Math.max(
    size.value.width / bounds.value.width,
    size.value.height / bounds.value.height,
  );
  const x =
    (size.value.width - bounds.value.width * scale) / 2 +
    (item.x - bounds.value.x) * scale;
  const y =
    (size.value.height - bounds.value.height * scale) / 2 +
    (item.y - bounds.value.y) * scale;
  return {
    left: `${Math.max(12, Math.min(x - 203, size.value.width - 229))}px`,
    top: `${Math.max(13, Math.min(y - 118, size.value.height - 135))}px`,
  };
});
function searchLocation() {
  appliedQuery.value = locationQuery.value.trim();
  notice.value = "";
  const first = visibleLocations.value[0];
  if (first) {
    selectedId.value = first.id;
    if (appliedQuery.value) {
      center.value = { x: first.x + 25, y: first.y + 45 };
      zoom.value = 1.35;
    }
  } else
    notice.value =
      "Geen voorbeeldlocaties gevonden. Probeer een andere plaats of pas de filters aan.";
}
function selectLocation(item: MapLocation) {
  selectedId.value = item.id;
  notice.value = "";
}
function changeZoom(delta: number) {
  zoom.value = Math.min(
    2.5,
    Math.max(0.8, Math.round((zoom.value + delta) * 100) / 100),
  );
}
function resetView() {
  center.value = { x: 306.5, y: 421 };
  zoom.value = 1;
  locationQuery.value = "";
  appliedQuery.value = "";
  organizationType.value = "";
  level.value = "";
  province.value = "";
  selectedId.value = "amsterdam";
  notice.value = "";
}
function locate() {
  if (!navigator.geolocation) {
    notice.value =
      "Je browser kan je locatie niet bepalen. Zoek hierboven op een plaatsnaam.";
    return;
  }
  locating.value = true;
  navigator.geolocation.getCurrentPosition(
    ({ coords }) => {
      const nearest = [...locations].sort(
        (a, b) =>
          Math.hypot(
            a.latitude - coords.latitude,
            a.longitude - coords.longitude,
          ) -
          Math.hypot(
            b.latitude - coords.latitude,
            b.longitude - coords.longitude,
          ),
      )[0]!;
      resetView();
      selectedId.value = nearest.id;
      center.value = { x: nearest.x + 25, y: nearest.y + 45 };
      zoom.value = 1.35;
      notice.value = `Dichtstbijzijnde voorbeeldlocatie: ${nearest.city}. De kaart toont geen live organisatiegegevens.`;
      locating.value = false;
    },
    () => {
      notice.value =
        "Je locatie is niet beschikbaar of toegang is geweigerd. Je kunt ook op plaatsnaam zoeken.";
      locating.value = false;
    },
    { timeout: 8000, maximumAge: 60000 },
  );
}
onMounted(() => {
  const updateSize = () => {
    if (canvas.value?.clientWidth)
      size.value = {
        width: canvas.value.clientWidth,
        height: canvas.value.clientHeight,
      };
  };
  updateSize();
  if (typeof ResizeObserver !== "undefined" && canvas.value) {
    observer = new ResizeObserver(updateSize);
    observer.observe(canvas.value);
  }
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
  <section class="region-view" aria-labelledby="region-title">
    <header class="region-header">
      <h1 id="region-title">
        <Icon name="map" :size="23" /> Overheidsorganisaties in uw regio
      </h1>
      <form
        class="region-search"
        role="search"
        @submit.prevent="searchLocation"
      >
        <Icon name="search" :size="18" />
        <label class="region-sr-only" for="location-search"
          >Zoek op locatie</label
        >
        <input
          id="location-search"
          v-model="locationQuery"
          type="search"
          placeholder="Zoek op locatie..."
          maxlength="120"
        />
        <button type="submit">Zoeken</button>
      </form>
    </header>
    <div ref="canvas" class="region-canvas">
      <svg
        class="region-geography"
        :viewBox="viewBox"
        preserveAspectRatio="xMidYMid slice"
        aria-label="Schematische kaart met voorbeeldlocaties van overheidsorganisaties"
      >
        <image
          href="/images/netherlands-map.svg"
          x="0"
          y="0"
          width="613"
          height="724"
        />
        <g class="region-city-labels" aria-hidden="true">
          <text v-for="city in cities" :key="city.name" :x="city.x" :y="city.y">
            {{ city.name }}
          </text>
        </g>
        <g
          v-for="item in visibleLocations"
          :key="item.id"
          class="region-marker"
          :class="{ 'is-selected': selectedId === item.id }"
          :style="{ color: colors[item.type] }"
          :transform="`translate(${item.x} ${item.y})`"
          role="button"
          tabindex="0"
          :aria-label="`${item.name}, voorbeeldlocatie ${item.city}`"
          :aria-pressed="selectedId === item.id"
          @click="selectLocation(item)"
          @keydown.enter.prevent="selectLocation(item)"
          @keydown.space.prevent="selectLocation(item)"
        >
          <title>{{ item.name }} — voorbeeldlocatie</title>
          <circle class="region-marker-hit" r="12" />
          <template v-if="selectedId === item.id">
            <path
              class="region-pin"
              d="M0 5C-3 1-8-3-8-8a8 8 0 0 1 16 0C8-3 3 1 0 5Z"
            />
            <circle class="region-pin-center" cy="-8" r="3" />
          </template>
          <template v-else>
            <circle class="region-marker-ring" r="5.5" />
            <circle class="region-marker-center" r="1.8" />
          </template>
        </g>
      </svg>
      <aside
        v-if="selected"
        class="region-popup"
        :style="popupStyle"
        aria-label="Geselecteerde voorbeeldlocatie"
      >
        <button
          class="region-popup-close"
          aria-label="Locatie sluiten"
          @click="selectedId = ''"
        >
          <Icon name="close" :size="13" />
        </button>
        <strong>{{ selected.name }}</strong>
        <span
          >{{ selected.type }}
          <span class="region-popup-demo">· voorbeeldlocatie</span></span
        >
        <button
          class="region-details-link"
          @click="emit('open-organization', selected.name)"
        >
          <Icon name="document" :size="14" /> Bekijk details
          <Icon name="arrow-right" :size="14" />
        </button>
      </aside>
      <div class="region-legend" aria-label="Legenda organisatietypen">
        <button
          v-for="category in categories"
          :key="category"
          :class="{ 'is-active': organizationType === category }"
          :aria-pressed="organizationType === category"
          @click="
            organizationType = organizationType === category ? '' : category
          "
        >
          <span :style="{ background: colors[category] }"></span>{{ category }}
        </button>
      </div>
      <div class="region-controls" aria-label="Kaartbediening">
        <div class="region-zoom">
          <button
            aria-label="Inzoomen"
            :disabled="zoom >= 2.5"
            @click="changeZoom(0.25)"
          >
            <Icon name="plus" :size="20" /></button
          ><button
            aria-label="Uitzoomen"
            :disabled="zoom <= 0.8"
            @click="changeZoom(-0.25)"
          >
            <Icon name="minus" :size="20" />
          </button>
        </div>
        <button
          aria-label="Mijn locatie gebruiken"
          :disabled="locating"
          @click="locate"
        >
          <Icon name="crosshair" :size="19" />
        </button>
        <button aria-label="Kaart herstellen" @click="resetView">
          <Icon name="map" :size="18" />
        </button>
      </div>
      <p class="region-map-caption">Schematische kaart · voorbeeldlocaties</p>
      <a
        class="region-attribution"
        href="https://creativecommons.org/licenses/by/4.0/"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Provinciekaart MapSVG, licentie Creative Commons BY 4.0 (nieuw tabblad)"
        >MapSVG · CC BY 4.0</a
      >
    </div>
    <div class="region-filters">
      <div class="region-filter-heading">
        <h2>Filters</h2>
        <span>{{ visibleLocations.length }} voorbeeldlocaties</span
        ><button
          v-if="organizationType || level || province || appliedQuery"
          @click="resetView"
        >
          Filters wissen
        </button>
      </div>
      <div class="region-filter-grid">
        <label
          >Organisatietype<select
            v-model="organizationType"
            aria-label="Organisatietype"
          >
            <option value="">Alle types</option>
            <option v-for="category in categories" :key="category">
              {{ category }}
            </option>
          </select></label
        >
        <label
          >Bestuurslaag<select v-model="level" aria-label="Bestuurslaag">
            <option value="">Alle lagen</option>
            <option>Rijk</option>
            <option>Provinciaal</option>
            <option>Regionaal</option>
            <option>Lokaal</option>
          </select></label
        >
        <label
          >Provincie<select v-model="province" aria-label="Provincie">
            <option value="">Alle provincies</option>
            <option v-for="item in provinces" :key="item">{{ item }}</option>
          </select></label
        >
      </div>
      <p v-if="notice" class="region-notice" role="status">{{ notice }}</p>
      <p
        v-else-if="!visibleLocations.length"
        class="region-notice"
        role="status"
      >
        Geen voorbeeldlocaties voor deze filters. Pas je selectie aan of wis de
        filters.
      </p>
    </div>
  </section>
</template>

<style scoped>
.region-view {
  overflow: hidden;
  width: 100%;
  background: #fff;
  border: 1px solid #dce8f2;
  border-radius: 11px;
  box-shadow: 0 2px 8px #163c6310;
  color: #173d5d;
}
.region-header {
  padding: 22px 24px 16px;
}
.region-header h1 {
  display: flex;
  align-items: center;
  gap: 11px;
  margin: 0 0 17px;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.35;
  letter-spacing: -0.035em;
}
.region-header h1 > svg {
  color: #235376;
  flex-shrink: 0;
}
.region-search {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 6px 5px 12px;
  border: 1px solid #d3e2ef;
  border-radius: 7px;
  box-shadow: 0 1px 4px #20639b08;
  background: #fff;
}
.region-search > svg {
  color: #85a4be;
  flex-shrink: 0;
}
.region-search input {
  width: 100%;
  min-width: 0;
  padding: 7px 0;
  border: 0;
  outline-offset: 2px;
  font: inherit;
  font-size: 13px;
  color: #294f70;
  background: transparent;
}
.region-search input::placeholder {
  color: #93a9bc;
}
.region-search > button {
  border: 0;
  border-radius: 5px;
  padding: 7px 12px;
  color: #fff;
  background: #087be8;
  font: inherit;
  font-size: 11px;
  font-weight: 650;
  cursor: pointer;
}
.region-search > button:hover {
  background: #086ac4;
}
.region-canvas {
  position: relative;
  height: clamp(425px, calc(100vh - 302px), 670px);
  min-height: 425px;
  overflow: hidden;
  background: #d2eaf4;
  border-top: 1px solid #e1ebf3;
  border-bottom: 1px solid #dce6ed;
}
.region-geography {
  display: block;
  width: 100%;
  height: 100%;
}
.region-city-labels text {
  font-family: inherit;
  font-size: 8px;
  font-weight: 600;
  fill: #527087;
  paint-order: stroke;
  stroke: #f6f8ec;
  stroke-width: 2.7px;
  stroke-linejoin: round;
}
.region-marker {
  cursor: pointer;
  outline: none;
}
.region-marker-hit {
  fill: transparent;
  stroke: none;
}
.region-marker-ring {
  fill: #fff;
  stroke: currentColor;
  stroke-width: 1.9;
  filter: drop-shadow(0 1px 2px #366f6c20);
}
.region-marker-center {
  fill: currentColor;
}
.region-marker:hover .region-marker-ring,
.region-marker:focus-visible .region-marker-ring {
  stroke-width: 3;
  filter: drop-shadow(0 0 4px #057dfb80);
}
.region-marker:focus-visible .region-marker-hit {
  stroke: #086cc8;
  stroke-width: 1;
  stroke-dasharray: 2 2;
}
.region-pin {
  fill: #ee6381;
  stroke: #fff;
  stroke-width: 1.3;
  filter: drop-shadow(0 2px 2px #a2375d30);
}
.region-pin-center {
  fill: #fff;
}
.region-popup {
  position: absolute;
  z-index: 2;
  width: 205px;
  padding: 14px 16px 10px;
  border: 1px solid #d8e5ef;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 5px 17px #3155741d;
}
.region-popup::after {
  content: "";
  position: absolute;
  right: 23px;
  bottom: -7px;
  width: 12px;
  height: 12px;
  transform: rotate(45deg);
  background: #fff;
  border-right: 1px solid #d8e5ef;
  border-bottom: 1px solid #d8e5ef;
}
.region-popup strong {
  display: block;
  padding-right: 8px;
  font-size: 12px;
  line-height: 1.5;
  font-weight: 700;
  letter-spacing: -0.02em;
}
.region-popup > span {
  display: block;
  margin-top: 3px;
  color: #6a849a;
  font-size: 10px;
}
.region-popup-demo {
  font-size: 9px;
}
.region-popup-close {
  position: absolute;
  top: 5px;
  right: 4px;
  padding: 3px;
  border: 0;
  background: transparent;
  color: #90a5b8;
  cursor: pointer;
}
.region-details-link {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  margin-top: 11px;
  padding: 9px 0 0;
  border: 0;
  border-top: 1px solid #edf3f8;
  background: transparent;
  color: #087be8;
  font: inherit;
  font-size: 10px;
  font-weight: 650;
  text-align: left;
  cursor: pointer;
}
.region-details-link > svg:last-child {
  margin-left: auto;
}
.region-legend {
  position: absolute;
  top: 17px;
  right: 17px;
  display: grid;
  gap: 7px;
  min-width: 166px;
  padding: 13px 14px;
  border: 1px solid #dce8ee;
  border-radius: 8px;
  background: #ffffffef;
  box-shadow: 0 2px 9px #34618410;
}
.region-legend button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #537087;
  font: inherit;
  font-size: 10px;
  line-height: 1.45;
  text-align: left;
  cursor: pointer;
}
.region-legend button:hover,
.region-legend button.is-active {
  color: #126bbe;
  font-weight: 650;
}
.region-legend button > span {
  width: 6px;
  height: 6px;
  flex-shrink: 0;
  border-radius: 100%;
  box-shadow: 0 0 0 2px #fff;
}
.region-controls {
  position: absolute;
  right: 16px;
  bottom: 32px;
  display: grid;
  gap: 9px;
}
.region-controls button {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  padding: 0;
  border: 1px solid #d7e3eb;
  border-radius: 6px;
  background: #fff;
  color: #456986;
  box-shadow: 0 2px 5px #304c6914;
  cursor: pointer;
}
.region-controls button:hover {
  background: #f1f8fe;
  color: #087be8;
}
.region-controls button:disabled {
  opacity: 0.5;
  cursor: default;
}
.region-zoom {
  overflow: hidden;
  border: 1px solid #d7e3eb;
  border-radius: 6px;
  background: #fff;
  box-shadow: 0 2px 5px #304c6914;
}
.region-zoom button {
  border: 0;
  border-radius: 0;
  box-shadow: none;
}
.region-zoom button + button {
  border-top: 1px solid #dfe9f0;
}
.region-map-caption {
  position: absolute;
  left: 12px;
  bottom: 10px;
  margin: 0;
  padding: 3px 6px;
  border-radius: 3px;
  background: #ffffffda;
  color: #648194;
  font-size: 9px;
}
.region-attribution {
  position: absolute;
  right: 0;
  bottom: 0;
  padding: 2px 5px;
  background: #ffffffd9;
  color: #6c8598;
  font-size: 8px;
  text-decoration: none;
}
.region-filters {
  padding: 18px 24px 22px;
}
.region-filter-heading {
  display: flex;
  align-items: center;
  gap: 13px;
  margin-bottom: 13px;
}
.region-filter-heading h2 {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: -0.02em;
}
.region-filter-heading > span {
  margin-left: auto;
  color: #8aa0b2;
  font-size: 10px;
}
.region-filter-heading > button {
  padding: 0;
  border: 0;
  background: transparent;
  color: #087be8;
  font: inherit;
  font-size: 10px;
  cursor: pointer;
}
.region-filter-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 15px;
}
.region-filter-grid label {
  display: grid;
  gap: 7px;
  color: #5c7891;
  font-size: 11px;
  font-weight: 500;
}
.region-filter-grid select {
  width: 100%;
  min-width: 0;
  padding: 9px 28px 9px 10px;
  border: 1px solid #d9e6f1;
  border-radius: 5px;
  background: #fff;
  color: #7190a9;
  font: inherit;
  font-size: 11px;
  cursor: pointer;
}
.region-notice {
  margin: 13px 0 0;
  padding: 9px 11px;
  border-radius: 5px;
  background: #eef6ff;
  color: #426c91;
  font-size: 11px;
  line-height: 1.6;
}
.region-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}
button:focus-visible,
select:focus-visible,
input:focus-visible {
  outline: 3px solid #80bafa;
  outline-offset: 3px;
}
@media (min-width: 1500px) {
  .region-canvas {
    height: calc(100vh - 310px);
    max-height: 740px;
  }
}
@media (max-width: 700px) {
  .region-view {
    border-radius: 8px;
  }
  .region-header {
    padding: 18px 16px 14px;
  }
  .region-header h1 {
    font-size: 18px;
    gap: 8px;
  }
  .region-header h1 > svg {
    width: 20px;
  }
  .region-canvas {
    height: 510px;
    min-height: 400px;
  }
  .region-legend {
    top: 11px;
    right: 10px;
    min-width: 143px;
    padding: 10px 11px;
    gap: 6px;
  }
  .region-legend button {
    font-size: 9px;
  }
  .region-popup {
    width: 180px;
    padding: 11px 12px 9px;
  }
  .region-popup strong {
    font-size: 11px;
  }
  .region-popup > span {
    font-size: 9px;
  }
  .region-popup-demo {
    font-size: 8px;
  }
  .region-filters {
    padding: 16px;
  }
  .region-filter-grid {
    gap: 10px;
  }
  .region-filter-grid label,
  .region-filter-grid select {
    font-size: 10px;
  }
  .region-filter-grid select {
    padding: 9px 16px 9px 7px;
  }
  .region-controls {
    right: 11px;
    bottom: 33px;
  }
  .region-controls button {
    width: 32px;
    height: 32px;
  }
  .region-map-caption {
    font-size: 8px;
    left: 7px;
    bottom: 8px;
  }
  .region-filter-heading > span {
    font-size: 9px;
  }
}
@media (max-width: 400px) {
  .region-filter-grid {
    grid-template-columns: 1fr;
  }
  .region-filter-grid label {
    grid-template-columns: 100px 1fr;
    align-items: center;
  }
  .region-canvas {
    height: 470px;
  }
  .region-header h1 {
    font-size: 16px;
  }
  .region-popup {
    max-width: 173px;
  }
  .region-legend {
    min-width: 132px;
    gap: 5px;
  }
  .region-legend button {
    font-size: 8px;
  }
  .region-filter-heading {
    gap: 8px;
  }
}
</style>
