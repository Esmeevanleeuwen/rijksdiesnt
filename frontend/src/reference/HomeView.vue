<script setup lang="ts">
import { ref } from "vue";
import Icon from "../components/Icon.vue";
import { themes, newsItems } from "./content";
const emit = defineEmits<{ search: [query: string] }>();
const query = ref("");
const categories = [
  {
    title: "Organisaties",
    description: "Overheidsinstanties, ministeries, gemeenten en meer",
    icon: "organizations",
    route: "/organisaties",
  },
  {
    title: "Beleid & Plannen",
    description: "Wetsvoorstellen, beleidsplannen, kabinetsplannen",
    icon: "policy",
    route: "/beleid",
  },
  {
    title: "Data & Statistieken",
    description: "Open data, rapporten, cijfers en onderzoeken",
    icon: "chart",
    route: "/data",
  },
  {
    title: "Nieuws",
    description: "Actueel over de overheid, beleid en ontwikkelingen",
    icon: "news",
    route: "/nieuws",
  },
  {
    title: "Kaarten",
    description: "Ontdek in uw eigen regio en samenwerkingen",
    icon: "map",
    route: "/kaarten",
  },
];
</script>

<template>
  <div class="home-view">
    <section class="home-hero" aria-labelledby="home-title">
      <div class="home-hero-content">
        <h1 id="home-title">Eén platform, alle overheidsinformatie</h1>
        <p>
          Vind eenvoudig organisaties, plannen, data en nieuws van de
          Nederlandse overheid. Voor een transparante, toegankelijke en
          betrouwbare overheid.
        </p>
        <form
          class="home-search"
          role="search"
          @submit.prevent="emit('search', query.trim())"
        >
          <Icon name="search" :size="19" />
          <label class="home-sr-only" for="home-search-input"
            >Zoek overheidsinformatie</label
          >
          <input
            id="home-search-input"
            v-model="query"
            type="search"
            maxlength="200"
            placeholder="Zoek op organisatie, onderwerp, locatie of trefwoord…"
          />
          <button type="submit" aria-label="Zoeken">
            <Icon name="search" :size="20" />
          </button>
        </form>
      </div>
    </section>
    <div class="home-content">
      <nav class="home-categories" aria-label="Verken overheidsinformatie">
        <a
          v-for="(category, index) in categories"
          :key="category.route"
          :href="`#${category.route}`"
          class="home-category"
          :class="{ 'home-category-extra': index > 2 }"
        >
          <span class="home-category-icon"
            ><Icon :name="category.icon" :size="30"
          /></span>
          <strong>{{ category.title }}</strong>
          <span class="home-category-description">{{
            category.description
          }}</span>
        </a>
      </nav>
      <div class="home-lower-grid">
        <section class="home-themes" aria-labelledby="home-themes-title">
          <h2 id="home-themes-title">Actuele thema’s</h2>
          <div class="home-theme-grid">
            <a
              v-for="(theme, index) in themes.slice(0, 4)"
              :key="theme.id"
              :href="`#/data?thema=${theme.id}`"
              class="home-theme"
              :class="[
                `home-theme-${theme.color}`,
                { 'home-theme-mobile': index === 3 },
              ]"
            >
              <span class="home-theme-icon"
                ><Icon :name="theme.icon" :size="26"
              /></span>
              <div class="home-theme-copy">
                <h3>{{ theme.title }}</h3>
                <p>{{ theme.description }}</p>
              </div>
              <Icon name="arrow-right" :size="17" class="home-theme-arrow" />
            </a>
          </div>
        </section>
        <section class="home-news" aria-labelledby="home-news-title">
          <header>
            <h2 id="home-news-title">Laatste nieuws</h2>
            <a href="#/nieuws"
              >Bekijk alle nieuwsberichten <Icon name="arrow-right" :size="13"
            /></a>
          </header>
          <a
            v-for="(item, index) in newsItems.slice(0, 3)"
            :key="item.id"
            :href="`#/nieuws?bericht=${item.id}`"
            class="home-news-row"
          >
            <span class="home-news-icon"
              ><Icon
                :name="['building', 'shield', 'document'][index] || 'document'"
                :size="21"
            /></span>
            <div>
              <h3>{{ item.title }}</h3>
              <p>
                {{ item.category }}
                <span>· {{ item.time }} · {{ item.source }}</span>
              </p>
            </div>
          </a>
        </section>
      </div>
      <p class="home-prototype-note">
        Ontwerpvoorbeeld · Nieuws, thema’s en datasets bevatten voorbeeldinhoud.
      </p>
    </div>
  </div>
</template>

<style scoped>
.home-view {
  color: #173d62;
}
.home-hero {
  position: relative;
  min-height: 280px;
  display: flex;
  align-items: center;
  background: #326c98 url("/images/binnenhof.svg") center 61% / cover no-repeat;
  isolation: isolate;
}
.home-hero:before {
  position: absolute;
  content: "";
  inset: 0;
  z-index: -1;
  background: linear-gradient(
    90deg,
    rgba(22, 77, 114, 0.88),
    rgba(28, 93, 138, 0.56) 63%,
    rgba(30, 69, 99, 0.12)
  );
}
.home-hero-content {
  width: 100%;
  padding: 38px 44px 43px;
}
.home-hero h1 {
  font-size: 29px;
  line-height: 1.27;
  letter-spacing: 0.005em;
  font-weight: 700;
  color: white;
  margin: 0 0 16px;
}
.home-hero p {
  max-width: 710px;
  margin: 0 0 24px;
  color: #f0f7fe;
  font-size: 14px;
  line-height: 1.65;
}
.home-search {
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: 760px;
  min-height: 49px;
  padding: 5px 5px 5px 15px;
  background: #fff;
  border: 1px solid #d4e1ec;
  border-radius: 8px;
  box-shadow: 0 2px 7px #092b4312;
  color: #a1b8ce;
}
.home-search input {
  font: inherit;
  min-width: 0;
  flex: 1;
  border: 0;
  background: transparent;
  color: #1a3e60;
  font-size: 13px;
  outline-offset: 3px;
  padding: 9px 0;
}
.home-search input::placeholder {
  color: #8ba0b5;
}
.home-search button {
  display: grid;
  place-items: center;
  align-self: stretch;
  flex-shrink: 0;
  width: 43px;
  border: 0;
  border-radius: 6px;
  color: #fff;
  background: #0074d6;
  box-shadow: 0 1px 3px #005bba33;
  cursor: pointer;
}
.home-search button:hover {
  background: #005db8;
}
.home-content {
  padding: 34px 40px 20px;
}
.home-categories {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 20px;
}
.home-category {
  display: flex;
  align-items: center;
  flex-direction: column;
  text-align: center;
  padding: 25px 13px 21px;
  min-height: 178px;
  background: linear-gradient(135deg, #eef6ff, #f5f8fc);
  border: 1px solid #f0f5fb;
  border-radius: 9px;
  text-decoration: none;
  color: #244870;
  transition:
    background 0.15s,
    transform 0.15s;
}
.home-category:hover {
  background: #e4effc;
  transform: translateY(-2px);
}
.home-category-icon {
  display: grid;
  place-items: center;
  color: #0076d5;
  border-radius: 9px;
  background: #e3efff;
  width: 47px;
  height: 43px;
  margin-bottom: 15px;
  box-shadow: 0 5px 12px #006cd006;
}
.home-category strong {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.4;
  white-space: nowrap;
}
.home-category-description {
  font-size: 11px;
  line-height: 1.65;
  color: #6f8ba7;
  margin-top: 13px;
  max-width: 150px;
}
.home-lower-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.72fr) minmax(0, 1fr);
  gap: 30px;
  margin-top: 42px;
}
.home-lower-grid h2 {
  font-size: 16px;
  font-weight: 700;
  line-height: 1.4;
  margin: 0 0 17px;
}
.home-theme-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}
.home-theme {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: 22px 18px 38px;
  border-radius: 8px;
  background: #edf9fc;
  min-height: 208px;
  text-decoration: none;
  color: #1b5a78;
  border: 1px solid #e7f4f9;
  transition: filter 0.15s;
}
.home-theme:hover {
  filter: brightness(0.98);
}
.home-theme-pink {
  background: #fcf4fc;
  border-color: #f8effa;
  color: #c96f94;
}
.home-theme-teal {
  background: #effbf7;
  border-color: #e8f5ef;
  color: #1b7a77;
}
.home-theme-green {
  background: #f0fbf5;
  border-color: #e6f6ed;
  color: #30775c;
}
.home-theme-icon {
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  background: #d6f1f7;
  border-radius: 50%;
  margin-bottom: 18px;
}
.home-theme-pink .home-theme-icon {
  background: #ffe0ec;
  color: #e57093;
}
.home-theme-teal .home-theme-icon {
  background: #d9f3e9;
  color: #2f8983;
}
.home-theme-green .home-theme-icon {
  background: #dcf2e5;
  color: #418d64;
}
.home-theme h3 {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.4;
  color: #2d577c;
  margin: 0 0 11px;
}
.home-theme p {
  font-size: 11px;
  color: #7b94ab;
  line-height: 1.7;
  margin: 0;
}
.home-theme-arrow {
  position: absolute;
  right: 17px;
  bottom: 15px;
  color: #00a1cc;
}
.home-theme-pink .home-theme-arrow {
  color: #bd8acc;
}
.home-theme-mobile {
  display: none;
}
.home-news {
  border-left: 1px solid #e6eef6;
  padding-left: 26px;
}
.home-news header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 5px;
}
.home-news header h2 {
  white-space: nowrap;
  margin-bottom: 8px;
}
.home-news header > a {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  line-height: 1.8;
  color: #2585d8;
  text-decoration: none;
  white-space: nowrap;
}
.home-news-row {
  display: flex;
  gap: 12px;
  padding: 16px 0;
  border-bottom: 1px solid #e8eef6;
  color: #24476c;
  text-decoration: none;
}
.home-news-row:last-child {
  border-bottom: 0;
}
.home-news-row:hover h3 {
  color: #0077d8;
}
.home-news-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 33px;
  height: 36px;
  background: #eef5fc;
  color: #6890bb;
  border-radius: 7px;
}
.home-news-row h3 {
  font-size: 12px;
  line-height: 1.5;
  font-weight: 600;
  margin: 0 0 5px;
}
.home-news-row p {
  font-size: 10px;
  color: #7894af;
  line-height: 1.5;
  margin: 0;
}
.home-news-row p span {
  font-size: 9px;
}
.home-prototype-note {
  font-size: 10px;
  color: #8b9fb1;
  line-height: 1.6;
  margin: 24px 0 0;
}
.home-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
@media (max-width: 1200px) {
  .home-hero-content {
    padding-inline: 30px;
  }
  .home-content {
    padding-inline: 28px;
  }
  .home-categories {
    gap: 14px;
  }
  .home-category {
    padding-inline: 9px;
  }
  .home-category strong {
    font-size: 12px;
    white-space: normal;
  }
  .home-lower-grid {
    gap: 22px;
    grid-template-columns: minmax(0, 1.65fr) minmax(0, 1fr);
  }
  .home-theme-grid {
    gap: 13px;
  }
  .home-theme {
    padding-inline: 14px;
  }
  .home-news {
    padding-left: 21px;
  }
  .home-news header {
    display: block;
  }
  .home-news header > a {
    margin-top: -3px;
  }
  .home-theme h3 {
    font-size: 12px;
  }
}
@media (max-width: 900px) and (min-width: 701px) {
  .home-lower-grid {
    grid-template-columns: 1fr;
  }
  .home-news {
    border-left: 0;
    padding-left: 0;
  }
  .home-news header {
    display: flex;
  }
  .home-categories {
    gap: 10px;
  }
  .home-category-description {
    font-size: 10px;
  }
  .home-category {
    padding-inline: 6px;
  }
}
@media (max-width: 700px) {
  .home-hero {
    min-height: 144px;
    background-position: 65% 55%;
    background-color: #0e2f4d;
  }
  .home-hero:before {
    background: linear-gradient(90deg, #102f4df7, #143a57df);
  }
  .home-hero-content {
    padding: 16px 18px 24px;
  }
  .home-hero h1 {
    font-size: 16px;
    letter-spacing: 0;
    line-height: 1.5;
    margin: 0 0 15px;
  }
  .home-hero p {
    display: none;
  }
  .home-search {
    min-height: 42px;
    padding: 4px 4px 4px 11px;
    border-radius: 7px;
    max-width: none;
    gap: 7px;
  }
  .home-search > .ui-icon {
    display: none;
  }
  .home-search input {
    padding: 7px 0;
    font-size: 11px;
  }
  .home-search input::placeholder {
    color: transparent;
  }
  .home-search button {
    width: 35px;
    border-radius: 5px;
  }
  .home-content {
    padding: 20px 18px 22px;
  }
  .home-categories {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
  }
  .home-category {
    min-height: 99px;
    padding: 13px 6px 11px;
    border-radius: 8px;
  }
  .home-category-extra {
    display: none;
  }
  .home-category-icon {
    width: 37px;
    height: 31px;
    border-radius: 7px;
    margin-bottom: 9px;
    background: #e8f1ff;
  }
  .home-category-icon .ui-icon {
    width: 23px;
    height: 23px;
  }
  .home-category strong {
    font-size: 10px;
    line-height: 1.35;
    max-width: 85px;
    white-space: normal;
  }
  .home-category-description {
    display: none;
  }
  .home-lower-grid {
    display: block;
    margin-top: 27px;
  }
  .home-lower-grid h2 {
    font-size: 14px;
    margin-bottom: 12px;
  }
  .home-theme-grid {
    display: flex;
    flex-direction: column;
    gap: 11px;
  }
  .home-theme {
    display: flex;
    align-items: center;
    flex-direction: row;
    min-height: 66px;
    padding: 11px 30px 11px 12px;
    border-radius: 8px;
    gap: 12px;
  }
  .home-theme-mobile {
    display: flex;
  }
  .home-theme-icon {
    margin: 0;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
  }
  .home-theme-icon .ui-icon {
    width: 22px;
    height: 22px;
  }
  .home-theme h3 {
    font-size: 12px;
    line-height: 1.45;
    margin: 0 0 3px;
  }
  .home-theme p {
    font-size: 10px;
    line-height: 1.4;
    max-width: 240px;
  }
  .home-theme-arrow {
    right: 12px;
    bottom: 50%;
    transform: translateY(50%);
    width: 13px;
    height: 13px;
  }
  .home-news {
    display: none;
  }
  .home-prototype-note {
    font-size: 9px;
    margin-top: 20px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .home-category {
    transition: none;
  }
  .home-category:hover {
    transform: none;
  }
}
</style>
