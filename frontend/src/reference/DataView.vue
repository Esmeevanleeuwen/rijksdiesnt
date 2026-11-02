<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import Icon from "../components/Icon.vue";
import { trapDialogFocus } from "./dialogFocus";
import { datasets, themes } from "./content";
import type { Dataset } from "./content";

const props = defineProps<{ initialTheme?: string }>();
const tabs = ["Populaire datasets", "Nieuwe datasets", "Alle datasets"];
const tab = ref(tabs[0]);
const query = ref("");
const theme = ref(
  themes.some((item) => item.id === props.initialTheme)
    ? props.initialTheme!
    : "",
);
const selected = ref<Dataset | null>(null);
const dialog = ref<HTMLElement | null>(null);
let opener: HTMLElement | null = null;
const visibleDatasets = computed(() =>
  datasets.filter((item) => {
    const text =
      `${item.title} ${item.description} ${item.source} ${item.tags.join(" ")}`.toLocaleLowerCase(
        "nl",
      );
    return (
      (!query.value.trim() ||
        text.includes(query.value.trim().toLocaleLowerCase("nl"))) &&
      (!theme.value || item.theme === theme.value) &&
      (tab.value !== "Nieuwe datasets" || item.isNew)
    );
  }),
);

function selectTheme(id: string) {
  theme.value = theme.value === id ? "" : id;
}
async function openDataset(item: Dataset, event: MouseEvent) {
  opener = event.currentTarget as HTMLElement;
  selected.value = item;
  await nextTick();
  dialog.value?.focus();
}
function closeDataset() {
  selected.value = null;
  opener?.focus();
}
</script>

<template>
  <section class="data-page" aria-labelledby="data-title">
    <header class="data-heading">
      <Icon name="data" :size="29" />
      <div>
        <h1 id="data-title">Data &amp; Statistieken</h1>
        <p>Ontdek en gebruik open data van de overheid</p>
      </div>
      <span class="data-demo">Voorbeeldinhoud</span>
    </header>
    <div class="data-layout">
      <div class="data-main">
        <div class="data-tabs" role="tablist" aria-label="Datasetselectie">
          <button
            v-for="item in tabs"
            :key="item"
            type="button"
            role="tab"
            :aria-selected="tab === item"
            :class="{ active: tab === item }"
            @click="tab = item"
          >
            {{ item }}
          </button>
        </div>
        <div v-if="theme" class="data-filter-label">
          Thema: {{ themes.find((item) => item.id === theme)?.title }}
          <button @click="theme = ''" aria-label="Themafilter verwijderen">
            <Icon name="close" :size="13" />
          </button>
        </div>
        <div class="data-list" aria-live="polite">
          <article
            v-for="item in visibleDatasets"
            :key="item.id"
            class="data-row"
          >
            <span class="data-icon" :class="`data-color-${item.color}`"
              ><Icon :name="item.icon" :size="23"
            /></span>
            <div class="data-row-content">
              <h2>{{ item.title }}</h2>
              <p class="data-source">
                {{ item.source }} <span>·</span> {{ item.period }}
              </p>
              <p class="data-description">{{ item.description }}</p>
              <div class="data-tags">
                <span v-for="tag in item.tags" :key="tag">{{ tag }}</span>
              </div>
            </div>
            <button class="data-open" @click="openDataset(item, $event)">
              Bekijk dataset <Icon name="arrow-right" :size="14" />
            </button>
          </article>
          <div v-if="!visibleDatasets.length" class="data-empty">
            <Icon name="search" :size="26" />
            <h2>Geen datasets gevonden</h2>
            <p>Probeer een andere zoekterm of een ander thema.</p>
            <button
              @click="
                query = '';
                theme = '';
                tab = tabs[0];
              "
            >
              Toon alle datasets
            </button>
          </div>
        </div>
        <p class="data-footnote">
          Voorbeeldweergave · Deze datasets zijn nog niet gekoppeld aan een
          databron.
        </p>
      </div>
      <aside class="data-sidebar" aria-label="Datasets zoeken en verkennen">
        <div class="data-side-panel">
          <h2>Zoek in alle data</h2>
          <label class="data-search"
            ><Icon name="search" :size="15" /><input
              v-model="query"
              type="search"
              aria-label="Zoek in alle data"
              placeholder="Zoek op dataset, onderwerp of organisatie…"
          /></label>
          <h3>Populaire thema’s</h3>
          <div class="data-themes">
            <button
              v-for="item in themes"
              :key="item.id"
              :class="{ active: theme === item.id }"
              :aria-pressed="theme === item.id"
              @click="selectTheme(item.id)"
            >
              <span :class="`data-theme-${item.color}`"
                ><Icon :name="item.icon" :size="15" /></span
              >{{ item.title }}
            </button>
          </div>
        </div>
        <div class="data-side-panel data-direct">
          <h2>Direct naar</h2>
          <a
            href="https://data.overheid.nl"
            target="_blank"
            rel="noopener noreferrer"
            ><Icon name="data" :size="21" /><span
              >Data.overheid.nl<small>Open dataportaal</small></span
            ><Icon name="external" :size="12" /></a
          ><a
            href="https://developer.overheid.nl/apis"
            target="_blank"
            rel="noopener noreferrer"
            ><Icon name="globe" :size="21" /><span
              >API-register<small>Overheids-API’s</small></span
            ><Icon name="external" :size="12" /></a
          ><a
            href="https://open.overheid.nl"
            target="_blank"
            rel="noopener noreferrer"
            ><Icon name="document" :size="21" /><span
              >Woo-index<small>Openbare documenten</small></span
            ><Icon name="external" :size="12"
          /></a>
        </div>
      </aside>
    </div>
    <div
      v-if="selected"
      class="data-modal-backdrop"
      @click.self="closeDataset"
      @keydown="trapDialogFocus($event, dialog, closeDataset)"
    >
      <section
        ref="dialog"
        class="data-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dataset-dialog-title"
        tabindex="-1"
      >
        <button
          class="data-modal-close"
          aria-label="Dataset sluiten"
          @click="closeDataset"
        >
          <Icon name="close" :size="21" /></button
        ><span class="data-modal-label">Voorbeelddataset</span>
        <h2 id="dataset-dialog-title">{{ selected.title }}</h2>
        <p>{{ selected.description }}</p>
        <dl>
          <div>
            <dt>Aanbieder in dit ontwerp</dt>
            <dd>{{ selected.source }}</dd>
          </div>
          <div>
            <dt>Periode in dit ontwerp</dt>
            <dd>{{ selected.period }}</dd>
          </div>
          <div>
            <dt>Onderwerpen</dt>
            <dd>{{ selected.tags.join(" · ") }}</dd>
          </div>
        </dl>
        <div class="data-modal-note">
          Dit is voorbeeldinhoud om de interface te demonstreren. Er is nog geen
          gegevensbestand of onderzochte bronkoppeling beschikbaar.
        </div>
        <a
          class="data-modal-link"
          href="https://data.overheid.nl"
          target="_blank"
          rel="noopener noreferrer"
          >Verken het officiële dataportaal <Icon name="external" :size="15"
        /></a>
      </section>
    </div>
  </section>
</template>

<style scoped>
.data-page {
  padding: 31px 29px 26px;
  color: #193e61;
}
.data-heading {
  display: flex;
  align-items: center;
  gap: 13px;
  margin-bottom: 23px;
}
.data-heading > svg {
  color: #174e80;
}
.data-heading h1 {
  font-size: 25px;
  line-height: 1.25;
  letter-spacing: -0.5px;
  margin: 0 0 6px;
  font-weight: 700;
}
.data-heading p {
  font-size: 12px;
  margin: 0;
  color: #62809b;
}
.data-demo {
  margin-left: auto;
  font-size: 10px;
  color: #7b91a5;
  background: #f3f7fa;
  padding: 4px 8px;
  border-radius: 4px;
  white-space: nowrap;
}
.data-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 252px;
  gap: 25px;
}
.data-tabs {
  display: flex;
  gap: 28px;
  border-bottom: 1px solid #dbe9f5;
}
.data-tabs button {
  border: 0;
  background: none;
  color: #7790a7;
  padding: 0 0 13px;
  font-size: 12px;
  cursor: pointer;
  position: relative;
  white-space: nowrap;
}
.data-tabs button.active {
  color: #087be5;
  font-weight: 600;
}
.data-tabs button.active:after {
  content: "";
  position: absolute;
  bottom: -1px;
  height: 2px;
  background: #087df0;
  left: 0;
  right: 0;
}
.data-list {
  margin-top: 15px;
}
.data-row {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  border-bottom: 1px solid #e5eef6;
  padding: 18px 0 17px;
  min-height: 128px;
}
.data-row:first-child {
  padding-top: 12px;
}
.data-row:last-child {
  border-bottom: 0;
}
.data-icon {
  width: 44px;
  height: 44px;
  flex: none;
  display: grid;
  place-items: center;
  border-radius: 8px;
  color: white;
  box-shadow: 0 3px 7px #0b6ca41a;
  background: linear-gradient(145deg, #26c3ed, #05a5db);
}
.data-color-purple {
  background: linear-gradient(145deg, #b9a6ed, #8169c7);
}
.data-color-green {
  background: linear-gradient(145deg, #62dab3, #16b77c);
}
.data-color-blue {
  background: linear-gradient(145deg, #149de8, #0872d2);
}
.data-color-teal {
  background: linear-gradient(145deg, #3bc3c7, #08a4b5);
}
.data-row-content {
  min-width: 0;
  flex: 1;
}
.data-row h2 {
  font-size: 13px;
  font-weight: 650;
  line-height: 1.45;
  margin: 0 0 3px;
  color: #234f73;
}
.data-source {
  font-size: 10px;
  color: #8da4b8;
  margin: 0 0 5px;
}
.data-source span {
  padding: 0 3px;
}
.data-description {
  font-size: 11px;
  color: #7391ac;
  line-height: 1.5;
  margin: 0 0 8px;
}
.data-tags {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}
.data-tags span {
  font-size: 9px;
  color: #4188b7;
  background: #eaf5fe;
  border-radius: 20px;
  padding: 3px 8px;
  line-height: 1.3;
}
.data-open {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f9fcff;
  border: 1px solid #dceafb;
  border-radius: 5px;
  color: #1885e5;
  padding: 6px 9px;
  font-size: 10px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
}
.data-open:hover {
  background: #eaf5ff;
}
.data-side-panel {
  background: linear-gradient(140deg, #f9fcff, #f4f8fc);
  border: 1px solid #e2edf7;
  border-radius: 7px;
  padding: 17px 16px;
}
.data-side-panel h2 {
  font-size: 13px;
  margin: 0 0 13px;
  font-weight: 650;
}
.data-search {
  display: flex;
  align-items: center;
  gap: 7px;
  border: 1px solid #dce8f3;
  border-radius: 5px;
  background: white;
  padding: 9px 8px;
  color: #83a4bf;
}
.data-search input {
  width: 100%;
  min-width: 0;
  border: 0;
  background: none;
  color: #2c5575;
  font: inherit;
  font-size: 10px;
  outline-offset: 5px;
}
.data-search input::placeholder {
  color: #90a9bf;
}
.data-side-panel h3 {
  font-size: 11px;
  margin: 24px 0 13px;
  font-weight: 650;
}
.data-themes {
  display: grid;
  gap: 8px;
}
.data-themes button {
  display: flex;
  gap: 9px;
  align-items: center;
  border: 0;
  background: none;
  color: #4a6d8a;
  font-size: 11px;
  text-align: left;
  padding: 2px 0;
  cursor: pointer;
  border-radius: 3px;
}
.data-themes button.active {
  color: #087fe2;
  font-weight: 650;
}
.data-themes button > span {
  display: grid;
  place-items: center;
  width: 24px;
  height: 24px;
  background: #e2edf8;
  color: #266086;
  border-radius: 50%;
  flex: none;
}
.data-themes .data-theme-cyan {
  background: #dcf6fa;
  color: #078fa7;
}
.data-themes .data-theme-pink {
  background: #ffe5ec;
  color: #ee7894;
}
.data-themes .data-theme-teal {
  background: #def3f2;
  color: #1c8f91;
}
.data-themes .data-theme-green {
  background: #e0f4e9;
  color: #429f7b;
}
.data-themes .data-theme-purple {
  background: #ece7fc;
  color: #8f74cc;
}
.data-direct {
  margin-top: 16px;
}
.data-direct a {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  font-size: 11px;
  color: #1984d8;
  padding: 8px 0;
}
.data-direct a > svg:last-child {
  margin-left: auto;
  color: #8babc3;
}
.data-direct small {
  display: block;
  font-size: 10px;
  color: #8aa2b7;
  margin-top: 3px;
}
.data-direct a:hover span {
  text-decoration: underline;
}
.data-footnote {
  font-size: 10px;
  color: #8da3b6;
  margin: 16px 0 0;
}
.data-filter-label {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #2888d0;
  font-size: 11px;
  margin-top: 13px;
}
.data-filter-label button {
  display: grid;
  place-items: center;
  border: 0;
  color: inherit;
  background: #eaf4fc;
  border-radius: 3px;
  cursor: pointer;
}
.data-empty {
  text-align: center;
  padding: 50px 20px;
  color: #7994ab;
  font-size: 12px;
}
.data-empty > svg {
  margin: auto;
  color: #60a4d3;
}
.data-empty h2 {
  font-size: 15px;
  margin-top: 12px;
}
.data-empty button {
  margin-top: 10px;
  border: 1px solid #d4e6f7;
  background: #f1f8fe;
  padding: 8px 12px;
  color: #117cce;
  border-radius: 4px;
  cursor: pointer;
}
.data-modal-backdrop {
  position: fixed;
  inset: 0;
  background: #102c4870;
  z-index: 90;
  display: grid;
  place-items: center;
  padding: 24px;
  backdrop-filter: blur(3px);
}
.data-modal {
  position: relative;
  background: white;
  border: 1px solid #deebf6;
  border-radius: 12px;
  padding: 32px;
  max-width: 550px;
  width: 100%;
  box-shadow: 0 18px 65px #0d345332;
  outline: none;
}
.data-modal-close {
  position: absolute;
  right: 16px;
  top: 16px;
  border: 0;
  color: #7895ad;
  background: none;
  cursor: pointer;
}
.data-modal-label {
  display: inline-block;
  color: #3b8cbb;
  background: #edf6fc;
  border-radius: 4px;
  font-size: 10px;
  padding: 4px 8px;
  margin-bottom: 16px;
}
.data-modal h2 {
  font-size: 23px;
  margin: 0 0 15px;
}
.data-modal p {
  font-size: 13px;
  line-height: 1.7;
  color: #64839b;
}
.data-modal dl {
  margin: 24px 0;
  display: grid;
  gap: 13px;
  font-size: 12px;
}
.data-modal dl > div {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.data-modal dt {
  color: #7b95a9;
}
.data-modal dd {
  margin: 0;
  color: #355e7e;
}
.data-modal-note {
  font-size: 12px;
  line-height: 1.7;
  padding: 14px;
  background: #f4f8fc;
  border-radius: 5px;
  color: #6b879e;
}
.data-modal-link {
  display: flex;
  gap: 9px;
  align-items: center;
  color: #1683de;
  font-size: 12px;
  margin-top: 22px;
  text-decoration: none;
}
@media (min-width: 1500px) {
  .data-row {
    min-height: 145px;
  }
  .data-row h2 {
    font-size: 15px;
  }
  .data-description {
    font-size: 12px;
  }
  .data-source,
  .data-open {
    font-size: 11px;
  }
  .data-layout {
    grid-template-columns: minmax(0, 1fr) 285px;
  }
}
@media (max-width: 1040px) {
  .data-layout {
    grid-template-columns: minmax(0, 1fr) 220px;
    gap: 18px;
  }
  .data-page {
    padding: 25px 22px;
  }
  .data-row {
    gap: 10px;
  }
  .data-open {
    font-size: 0;
    padding: 7px;
  }
  .data-open svg {
    width: 15px;
  }
  .data-tabs {
    gap: 19px;
  }
  .data-tabs button {
    font-size: 11px;
  }
  .data-icon {
    width: 37px;
    height: 37px;
  }
}
@media (max-width: 760px) {
  .data-page {
    padding: 25px 18px;
  }
  .data-heading {
    gap: 10px;
  }
  .data-heading h1 {
    font-size: 22px;
  }
  .data-heading p {
    font-size: 11px;
  }
  .data-demo {
    font-size: 8px;
    padding: 3px 5px;
  }
  .data-layout {
    grid-template-columns: 1fr;
  }
  .data-sidebar {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .data-direct {
    margin-top: 0;
  }
  .data-row {
    min-height: 126px;
  }
  .data-row h2 {
    font-size: 13px;
  }
  .data-description {
    font-size: 11px;
  }
  .data-open {
    font-size: 10px;
  }
  .data-tabs {
    gap: 24px;
  }
  .data-modal {
    padding: 27px 23px;
  }
}
@media (max-width: 440px) {
  .data-heading {
    flex-wrap: wrap;
  }
  .data-demo {
    margin-left: auto;
  }
  .data-open {
    font-size: 0;
  }
  .data-sidebar {
    grid-template-columns: 1fr;
  }
  .data-themes {
    grid-template-columns: 1fr 1fr;
  }
  .data-side-panel h3 {
    margin-top: 18px;
  }
  .data-tabs {
    justify-content: space-between;
    gap: 12px;
  }
  .data-tabs button {
    font-size: 10px;
  }
  .data-modal dl > div {
    grid-template-columns: 1fr;
    gap: 3px;
  }
}
</style>
