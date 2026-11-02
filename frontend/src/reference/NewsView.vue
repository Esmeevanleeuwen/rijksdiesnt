<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from "vue";
import Icon from "../components/Icon.vue";
import { trapDialogFocus } from "./dialogFocus";
import { newsItems } from "./content";
import type { NewsItem } from "./content";

const props = defineProps<{ initialArticle?: string }>();
const tabs = [
  "Alle berichten",
  "Rijksoverheid",
  "Gemeenten",
  "Provincies",
  "Waterschappen",
];
const tab = ref(tabs[0]);
const expanded = ref(false);
const selected = ref<NewsItem | null>(
  newsItems.find((item) => item.id === props.initialArticle) ?? null,
);
const dialog = ref<HTMLElement | null>(null);
let opener: HTMLElement | null = null;
const filtered = computed(() =>
  newsItems.filter((item) => tab.value === tabs[0] || item.tab === tab.value),
);
const visibleNews = computed(() =>
  expanded.value || tab.value !== tabs[0]
    ? filtered.value
    : filtered.value.slice(0, 5),
);
function selectTab(value: string) {
  tab.value = value;
  expanded.value = false;
}
async function openNews(item: NewsItem, event: MouseEvent) {
  opener = event.currentTarget as HTMLElement;
  selected.value = item;
  await nextTick();
  dialog.value?.focus();
}
function closeNews() {
  selected.value = null;
  opener?.focus();
}
onMounted(async () => {
  if (selected.value) {
    await nextTick();
    dialog.value?.focus();
  }
});
</script>

<template>
  <section class="news-page" aria-labelledby="news-title">
    <header class="news-heading">
      <div>
        <h1 id="news-title">Nieuws</h1>
        <p>Actueel nieuws van en over de Nederlandse overheid.</p>
      </div>
      <span class="news-demo">Voorbeeldinhoud</span>
    </header>
    <div class="news-tabs" role="tablist" aria-label="Nieuws per bestuurslaag">
      <button
        v-for="item in tabs"
        :key="item"
        role="tab"
        :aria-selected="tab === item"
        :class="{ active: tab === item }"
        @click="selectTab(item)"
      >
        {{ item }}
      </button>
    </div>
    <div class="news-list" aria-live="polite">
      <button
        v-for="item in visibleNews"
        :key="item.id"
        class="news-row"
        @click="openNews(item, $event)"
      >
        <img :src="item.image" alt="" class="news-image" /><span
          class="news-row-content"
          ><strong>{{ item.title }}</strong
          ><span class="news-category" :class="`news-badge-${item.color}`">{{
            item.category
          }}</span
          ><span class="news-meta"
            >{{ item.time }} <span>·</span> {{ item.source }}</span
          ></span
        ><Icon name="chevron-right" :size="20" />
      </button>
    </div>
    <button
      v-if="!expanded && tab === tabs[0]"
      class="news-all"
      @click="expanded = true"
    >
      Bekijk alle nieuwsberichten <Icon name="arrow-right" :size="15" />
    </button>
    <p class="news-footnote">
      Voorbeeldweergave · Dit zijn ontwerpvoorbeelden, geen actuele
      nieuwsberichten.
    </p>
    <div
      v-if="selected"
      class="news-modal-backdrop"
      @click.self="closeNews"
      @keydown="trapDialogFocus($event, dialog, closeNews)"
    >
      <article
        ref="dialog"
        class="news-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="news-dialog-title"
        tabindex="-1"
      >
        <button
          class="news-modal-close"
          aria-label="Nieuwsbericht sluiten"
          @click="closeNews"
        >
          <Icon name="close" :size="21" /></button
        ><img :src="selected.image" alt="" />
        <div class="news-modal-body">
          <span class="news-modal-label">Voorbeeldnieuws</span>
          <h2 id="news-dialog-title">{{ selected.title }}</h2>
          <p class="news-modal-meta">
            {{ selected.category }} <span>·</span> {{ selected.source }}
          </p>
          <p>{{ selected.excerpt }}</p>
          <p class="news-modal-note">
            De getoonde titel, tijd en bronvermelding demonstreren de
            vormgeving. Aan dit voorbeeld is geen originele publicatie
            gekoppeld.
          </p>
          <button class="news-modal-back" @click="closeNews">
            Terug naar het nieuwsoverzicht
            <Icon name="arrow-right" :size="15" />
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.news-page {
  padding: 31px 29px;
  color: #193e61;
}
.news-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 25px;
}
.news-heading h1 {
  font-size: 27px;
  line-height: 1.3;
  margin: 0 0 8px;
  letter-spacing: -0.5px;
  font-weight: 700;
}
.news-heading p {
  font-size: 12px;
  color: #6a86a0;
  margin: 0;
}
.news-demo {
  font-size: 10px;
  color: #7b91a5;
  background: #f3f7fa;
  padding: 4px 8px;
  border-radius: 4px;
  white-space: nowrap;
}
.news-tabs {
  display: flex;
  gap: 30px;
  border-bottom: 1px solid #dbe9f5;
  margin-bottom: 22px;
}
.news-tabs button {
  position: relative;
  border: 0;
  background: none;
  color: #7993ab;
  font-size: 12px;
  padding: 0 0 13px;
  cursor: pointer;
  white-space: nowrap;
}
.news-tabs button.active {
  color: #087ce7;
  font-weight: 600;
}
.news-tabs button.active:after {
  content: "";
  height: 2px;
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  background: #087df0;
}
.news-list {
  display: grid;
  gap: 12px;
}
.news-row {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 17px;
  padding: 9px;
  border: 1px solid #deebf6;
  border-radius: 7px;
  background: linear-gradient(120deg, #fff, #fcfdff);
  text-align: left;
  cursor: pointer;
  color: #2477b2;
  transition:
    border-color 0.15s,
    background 0.15s;
}
.news-row:hover {
  border-color: #a9d1f3;
  background: #f7fbff;
}
.news-image {
  width: 132px;
  height: 81px;
  object-fit: cover;
  border-radius: 5px;
  flex: none;
  background: linear-gradient(135deg, #93c0d5, #c4dce6);
}
.news-row-content {
  display: flex;
  align-items: flex-start;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}
.news-row strong {
  font-weight: 600;
  font-size: 13px;
  line-height: 1.5;
  color: #2b5679;
  display: block;
  margin-bottom: 6px;
}
.news-category {
  font-size: 9px;
  font-weight: 600;
  line-height: 1.3;
  padding: 3px 8px;
  background: #e0f2fd;
  color: #3699d0;
  border-radius: 20px;
  margin-bottom: 6px;
}
.news-badge-cyan {
  background: #dff5fc;
  color: #25a4c5;
}
.news-badge-green {
  background: #e3f6ec;
  color: #4ab188;
}
.news-badge-pink {
  background: #fdebf2;
  color: #cd759e;
}
.news-meta {
  font-size: 10px;
  color: #8da5b9;
  line-height: 1.5;
}
.news-meta > span {
  margin: 0 4px;
}
.news-row > svg {
  margin: 0 8px;
  color: #198bec;
  flex: none;
}
.news-all {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 0;
  background: none;
  padding: 21px 0 0;
  color: #1586e4;
  font-size: 11px;
  font-weight: 550;
  cursor: pointer;
}
.news-all:hover {
  text-decoration: underline;
}
.news-footnote {
  font-size: 10px;
  color: #91a6b8;
  margin: 19px 0 0;
}
.news-modal-backdrop {
  position: fixed;
  inset: 0;
  background: #102c4870;
  z-index: 90;
  display: grid;
  place-items: center;
  padding: 24px;
  backdrop-filter: blur(3px);
}
.news-modal {
  position: relative;
  background: white;
  border: 1px solid #deebf6;
  border-radius: 12px;
  max-width: 620px;
  width: 100%;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 18px 65px #0d345332;
  outline: none;
}
.news-modal-close {
  position: absolute;
  z-index: 1;
  right: 14px;
  top: 14px;
  border: 0;
  border-radius: 50%;
  display: grid;
  place-items: center;
  width: 33px;
  height: 33px;
  color: #3e607c;
  background: #ffffffe8;
  cursor: pointer;
}
.news-modal > img {
  width: 100%;
  height: 220px;
  object-fit: cover;
  background: #d5e6ee;
}
.news-modal-body {
  padding: 26px 30px 30px;
}
.news-modal-label {
  display: inline-block;
  color: #3b8cbb;
  background: #edf6fc;
  border-radius: 4px;
  font-size: 10px;
  padding: 4px 8px;
  margin-bottom: 16px;
}
.news-modal h2 {
  font-size: 24px;
  line-height: 1.4;
  letter-spacing: -0.4px;
  margin: 0 0 13px;
}
.news-modal p {
  font-size: 13px;
  line-height: 1.8;
  color: #64839b;
}
.news-modal .news-modal-meta {
  font-size: 11px;
  color: #87a0b5;
}
.news-modal-meta > span {
  margin: 0 5px;
}
.news-modal .news-modal-note {
  background: #f3f8fc;
  border-radius: 6px;
  padding: 13px;
  font-size: 11px;
  margin-top: 19px;
}
.news-modal-back {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #1686dd;
  border: 0;
  background: none;
  font-size: 12px;
  padding: 10px 0 0;
  cursor: pointer;
}
@media (min-width: 1500px) {
  .news-row {
    padding: 12px;
    gap: 22px;
  }
  .news-image {
    width: 160px;
    height: 95px;
  }
  .news-row strong {
    font-size: 15px;
  }
  .news-meta {
    font-size: 11px;
  }
  .news-list {
    gap: 14px;
  }
}
@media (max-width: 1040px) {
  .news-page {
    padding: 25px 22px;
  }
  .news-tabs {
    gap: 22px;
  }
  .news-tabs button {
    font-size: 11px;
  }
  .news-image {
    width: 110px;
    height: 78px;
  }
  .news-row {
    gap: 14px;
  }
  .news-row strong {
    font-size: 12px;
  }
}
@media (max-width: 760px) {
  .news-page {
    padding: 25px 18px;
  }
  .news-heading h1 {
    font-size: 24px;
  }
  .news-heading p {
    font-size: 11px;
  }
  .news-tabs {
    gap: 23px;
    overflow: auto;
    margin-right: -18px;
    padding-right: 18px;
  }
  .news-tabs button {
    font-size: 11px;
  }
  .news-image {
    width: 102px;
    height: 80px;
  }
  .news-row {
    gap: 12px;
  }
  .news-row > svg {
    width: 16px;
    margin: 0 2px;
  }
  .news-meta {
    font-size: 9px;
  }
  .news-demo {
    font-size: 8px;
    padding: 3px 5px;
  }
  .news-modal > img {
    height: 175px;
  }
  .news-modal-body {
    padding: 22px;
  }
  .news-modal h2 {
    font-size: 22px;
  }
}
@media (max-width: 440px) {
  .news-image {
    width: 85px;
    height: 82px;
  }
  .news-row {
    padding: 7px;
    gap: 10px;
  }
  .news-row strong {
    font-size: 11px;
    line-height: 1.45;
    margin-bottom: 4px;
  }
  .news-category {
    font-size: 8px;
    padding: 2px 6px;
    margin-bottom: 4px;
  }
  .news-meta {
    font-size: 8px;
  }
  .news-row > svg {
    width: 12px;
    margin: 0;
  }
  .news-heading {
    align-items: flex-start;
  }
  .news-heading p {
    max-width: 225px;
    line-height: 1.5;
  }
  .news-list {
    gap: 10px;
  }
  .news-tabs {
    margin-bottom: 17px;
  }
}
</style>
