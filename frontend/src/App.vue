<script setup lang="ts">
import {
  computed,
  nextTick,
  onMounted,
  onBeforeUnmount,
  ref,
  watch,
} from "vue";
import Icon from "./components/Icon.vue";
import GovernmentMark from "./components/GovernmentMark.vue";
import HomeView from "./reference/HomeView.vue";
import OrganizationsView from "./reference/OrganizationsView.vue";
import DataView from "./reference/DataView.vue";
import NewsView from "./reference/NewsView.vue";
import MapView from "./reference/MapView.vue";
import { themes } from "./reference/content";
const route = ref("/"),
  searchQuery = ref(""),
  drawer = ref(false),
  notification = ref(false),
  profile = ref(false),
  feedback = ref(false),
  feedbackText = ref(""),
  feedbackSaved = ref(false),
  notice = ref("");
const routeQuery = computed(
  () => new URLSearchParams(route.value.split("?")[1] || ""),
);
const page = computed(() => route.value.split("?")[0]?.split("/")[1] || "home");
const organizationId = computed(() =>
  page.value === "organisatie"
    ? route.value.split("?")[0]?.split("/")[2]
    : undefined,
);
const isHome = computed(() => page.value === "home");
const saved = ref<{ id: string; name: string }[]>([]);
const navigation = [
  { label: "Home", icon: "home", route: "/" },
  { label: "Organisaties", icon: "organizations", route: "/organisaties" },
  { label: "Beleid & Plannen", icon: "policy", route: "/beleid" },
  { label: "Data & Statistieken", icon: "data", route: "/data" },
  { label: "Nieuws", icon: "news", route: "/nieuws" },
  { label: "Kaarten", icon: "map", route: "/kaarten" },
  { label: "Mijn overzicht", icon: "user", route: "/mijn-overzicht" },
];
const activeNav = (path: string) =>
  path === "/"
    ? isHome.value
    : route.value.startsWith(path) ||
      (path === "/organisaties" && page.value === "organisatie");
const fontSize = ref("standaard");
function focusMain() {
  const main = document.getElementById("main-content");
  main?.focus();
  main?.scrollIntoView?.({ block: "start" });
}
function readSaved() {
  try {
    const v = JSON.parse(
      localStorage.getItem("overheidsinformatie.saved") || "[]",
    );
    saved.value = Array.isArray(v)
      ? v.filter(
          (x: { id?: string; name?: string }) =>
            typeof x?.id === "string" && typeof x?.name === "string",
        )
      : [];
  } catch {
    saved.value = [];
  }
}
function readRoute() {
  let hash = window.location.hash.slice(1) || "/";
  if (!hash.startsWith("/")) hash = "/" + hash;
  route.value = hash;
  drawer.value = false;
  notification.value = false;
  profile.value = false;
}
function navigate(path: string) {
  if (window.location.hash.slice(1) === path) drawer.value = false;
  else window.location.hash = path;
}
function search(value = searchQuery.value) {
  searchQuery.value = value.trim();
  navigate("/organisaties?q=" + encodeURIComponent(searchQuery.value));
}
function saveFeedback() {
  try {
    localStorage.setItem(
      "overheidsinformatie.feedback",
      feedbackText.value.trim(),
    );
    feedbackSaved.value = true;
  } catch {
    notice.value = "Opslaan op dit apparaat is niet beschikbaar.";
  }
}
function openMenu() {
  drawer.value = true;
  nextTick(() =>
    document.querySelector<HTMLButtonElement>(".drawer-close")?.focus(),
  );
}
function closeMenu() {
  drawer.value = false;
  nextTick(() =>
    document.querySelector<HTMLButtonElement>(".menu-trigger")?.focus(),
  );
}
function keydown(e: KeyboardEvent) {
  if (e.key === "Escape") {
    if (drawer.value) closeMenu();
    notification.value = false;
    profile.value = false;
    feedback.value = false;
  }
  if (e.key === "Tab" && (drawer.value || feedback.value)) {
    const nodes = document
      .querySelector(drawer.value ? ".drawer-panel" : ".feedback-dialog")
      ?.querySelectorAll<HTMLElement>(
        'a[href],button,input,textarea,select,[tabindex="0"]',
      );
    if (!nodes?.length) return;
    const first = nodes[0],
      last = nodes[nodes.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last?.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first?.focus();
    }
  }
}
watch(route, () => {
  window.scrollTo?.(0, 0);
  document.title =
    (navigation.find((n) => activeNav(n.route))?.label || "Organisatie") +
    " · Overheidsinformatie";
});
watch([drawer, feedback], () => {
  document.body.style.overflow = drawer.value || feedback.value ? "hidden" : "";
  if (feedback.value)
    nextTick(() =>
      document
        .querySelector<HTMLTextAreaElement>(".feedback-dialog textarea")
        ?.focus(),
    );
});
onMounted(() => {
  readRoute();
  readSaved();
  window.addEventListener("hashchange", readRoute);
  window.addEventListener("saved-organizations-changed", readSaved);
  window.addEventListener("keydown", keydown);
});
onBeforeUnmount(() => {
  window.removeEventListener("hashchange", readRoute);
  window.removeEventListener("saved-organizations-changed", readSaved);
  window.removeEventListener("keydown", keydown);
  document.body.style.overflow = "";
});
</script>
<template>
  <div
    class="platform"
    :class="{ 'home-layout': isHome, 'large-text': fontSize === 'groot' }"
  >
    <a class="skip-link" href="#main-content" @click.prevent="focusMain"
      >Direct naar inhoud</a
    >
    <aside v-if="isHome" class="desktop-sidebar" aria-label="Hoofdnavigatie">
      <a class="sidebar-brand" href="#/"
        ><GovernmentMark /><span
          >Overheidsinformatie<small>Nederland</small></span
        ></a
      >
      <nav class="side-nav">
        <a
          v-for="n in navigation"
          :key="n.route"
          :href="`#${n.route}`"
          :class="{ active: activeNav(n.route) }"
          :aria-current="activeNav(n.route) ? 'page' : undefined"
          ><Icon :name="n.icon" :size="20" />{{ n.label }}</a
        >
      </nav>
      <div class="side-bottom">
        <a href="#/instellingen"
          ><Icon name="settings" :size="18" />Instellingen</a
        ><a href="#/help"><Icon name="help" :size="18" />Help & Contact</a>
      </div>
    </aside>
    <div class="workspace" :inert="drawer || feedback">
      <header class="topbar">
        <div class="topbar-identity">
          <button
            class="icon-button menu-trigger"
            aria-label="Menu openen"
            :aria-expanded="drawer"
            @click="openMenu"
          >
            <Icon name="menu" :size="21" /></button
          ><a class="compact-brand" href="#/"
            ><span class="brand-emblem"><GovernmentMark /></span
            ><span>Overheidsinformatie</span></a
          >
        </div>
        <form
          class="global-search search-control"
          role="search"
          @submit.prevent="search()"
        >
          <Icon name="search" :size="16" /><input
            v-model="searchQuery"
            aria-label="Zoeken in alle overheidsinformatie"
            placeholder="Zoek op organisatie, onderwerp, locatie of trefwoord…"
            maxlength="200"
          /><button type="submit" class="sr-only">
            Zoeken in alle informatie
          </button>
        </form>
        <div class="topbar-actions">
          <button
            class="icon-button"
            aria-label="Meldingen"
            :aria-expanded="notification"
            @click="
              notification = !notification;
              profile = false;
            "
          >
            <Icon name="bell" :size="19" /></button
          ><button
            class="profile-button"
            :aria-expanded="profile"
            @click="
              profile = !profile;
              notification = false;
            "
          >
            <img src="/images/avatar.svg" alt="" /><span
              >Sanne de Vries<small>Burger</small></span
            ><Icon name="chevron-down" :size="12" />
          </button>
        </div>
        <div v-if="notification" class="header-popover" role="status">
          <strong>Uw meldingen</strong>
          <p>U heeft geen nieuwe meldingen.</p>
          <small>Voorbeeldomgeving</small>
        </div>
        <div v-if="profile" class="header-popover profile-popover">
          <strong>Sanne de Vries</strong>
          <p>Dit is het voorbeeldprofiel uit het ontwerp.</p>
          <a href="#/mijn-overzicht"
            >Naar mijn overzicht <Icon name="arrow-right" :size="16"
          /></a>
        </div>
      </header>
      <main id="main-content" :class="{ 'inner-main': !isHome }" tabindex="-1">
        <HomeView v-if="isHome" @search="search" />
        <OrganizationsView
          v-else-if="page === 'organisaties' || page === 'organisatie'"
          :key="route"
          :organization-id="organizationId"
          :initial-query="routeQuery.get('q') || ''"
          @navigate="navigate"
        />
        <DataView
          v-else-if="page === 'data'"
          :key="route"
          :initial-theme="routeQuery.get('thema') || ''"
        /><NewsView
          v-else-if="page === 'nieuws'"
          :key="route"
          :initial-article="routeQuery.get('bericht') || ''"
        /><MapView
          v-else-if="page === 'kaarten'"
          @open-organization="
            (name) => navigate('/organisaties?q=' + encodeURIComponent(name))
          "
        />
        <section v-else-if="page === 'mijn-overzicht'" class="utility-page">
          <div class="page-heading">
            <Icon name="user" :size="30" />
            <div>
              <h1>Mijn overzicht</h1>
              <p>Uw opgeslagen organisaties, altijd bij de hand.</p>
            </div>
          </div>
          <p class="info-strip">
            Uw selectie wordt op dit apparaat bewaard. Dit is een
            voorbeeldprofiel.
          </p>
          <div v-if="saved.length" class="saved-grid">
            <a
              v-for="item in saved"
              :key="item.id"
              :href="`#/organisatie/${item.id}`"
              class="saved-card"
              ><Icon name="organizations" :size="27" /><strong>{{
                item.name
              }}</strong
              ><Icon name="chevron-right"
            /></a>
          </div>
          <div v-else class="utility-empty">
            <Icon name="star" :size="38" />
            <h2>Nog geen organisaties opgeslagen</h2>
            <p>
              Open een organisatie en kies ‘Opslaan’ om uw overzicht samen te
              stellen.
            </p>
            <a class="primary-button" href="#/organisaties"
              >Organisaties bekijken</a
            >
          </div>
        </section>
        <section v-else-if="page === 'beleid'" class="utility-page">
          <div class="page-heading">
            <Icon name="policy" :size="30" />
            <div>
              <h1>Beleid & Plannen</h1>
              <p>Verken de onderwerpen die Nederland bewegen.</p>
            </div>
          </div>
          <div class="policy-grid">
            <a
              v-for="t in themes"
              :key="t.id"
              :href="`#/data?thema=${t.id}`"
              class="policy-card"
              :class="`tone-${t.color}`"
              ><Icon :name="t.icon" :size="28" />
              <h2>{{ t.title }}</h2>
              <p>{{ t.description }}</p>
              <span
                >Bekijk beschikbare voorbeelddata
                <Icon name="arrow-right" :size="15" /></span
            ></a>
          </div>
          <p class="demo-footnote">
            Ontwerpvoorbeeld · Officiële beleidsdocumenten worden later
            aangesloten.
          </p>
        </section>
        <section v-else-if="page === 'instellingen'" class="utility-page">
          <div class="page-heading">
            <Icon name="settings" :size="30" />
            <div>
              <h1>Instellingen</h1>
              <p>Pas uw weergave aan.</p>
            </div>
          </div>
          <div class="settings-panel">
            <label for="font-size">Tekstgrootte</label
            ><select id="font-size" v-model="fontSize">
              <option value="standaard">Standaard</option>
              <option value="groot">Groot</option>
            </select>
            <p>Deze instelling geldt tijdens uw huidige bezoek.</p>
          </div>
        </section>
        <section v-else-if="page === 'help'" class="utility-page">
          <div class="page-heading">
            <Icon name="help" :size="30" />
            <div>
              <h1>Help & Contact</h1>
              <p>We helpen u graag op weg.</p>
            </div>
          </div>
          <details open>
            <summary>Wat kan ik hier vinden?</summary>
            <p>
              Ontdek organisaties, voorbeeldnieuws, datasets en een schematische
              kaart van Nederland.
            </p>
          </details>
          <details>
            <summary>Is dit een officiële overheidswebsite?</summary>
            <p>
              Nee. Rijksdienst is een onafhankelijk prototype. De
              organisatiezoekfunctie is aangesloten op de lokale backend; de
              aanvullende schermen bevatten ontwerpvoorbeelden.
            </p>
          </details>
          <button
            class="primary-button"
            @click="
              feedback = true;
              feedbackSaved = false;
            "
          >
            Feedback geven
          </button>
        </section>
        <section v-else class="utility-page utility-empty">
          <h1>Deze pagina is niet gevonden</h1>
          <a href="#/" class="primary-button">Terug naar Home</a>
        </section>
      </main>
    </div>
    <nav
      :inert="drawer || feedback"
      class="mobile-bottom-nav"
      aria-label="Mobiele navigatie"
    >
      <a href="#/" :class="{ active: isHome }"
        ><Icon name="home" :size="22" />Home</a
      ><a href="#/organisaties" :class="{ active: page === 'organisaties' }"
        ><Icon name="search" :size="22" />Zoeken</a
      ><a href="#/mijn-overzicht" :class="{ active: page === 'mijn-overzicht' }"
        ><Icon name="user" :size="22" />Mijn overzicht</a
      ><button class="menu-trigger" @click="openMenu">
        <Icon name="more" :size="22" />Meer
      </button>
    </nav>
    <div v-if="drawer" class="drawer-backdrop" @click.self="closeMenu">
      <section
        class="drawer-panel"
        role="dialog"
        aria-modal="true"
        aria-label="Navigatiemenu"
      >
        <div class="drawer-heading">
          <a href="#/" class="sidebar-brand"
            ><GovernmentMark /><span
              >Overheidsinformatie<small>Nederland</small></span
            ></a
          ><button
            class="icon-button drawer-close"
            aria-label="Menu sluiten"
            @click="closeMenu"
          >
            <Icon name="close" />
          </button>
        </div>
        <div class="drawer-profile">
          <img src="/images/avatar.svg" alt="" />
          <div><strong>Sanne de Vries</strong><span>Burger</span></div>
        </div>
        <nav class="side-nav">
          <a
            v-for="n in navigation.slice(0, 6)"
            :key="n.route"
            :href="`#${n.route}`"
            :class="{ active: activeNav(n.route) }"
            @click="drawer = false"
            ><Icon :name="n.icon" />{{ n.label }}</a
          >
        </nav>
        <div class="drawer-utility">
          <a href="#/instellingen" @click="drawer = false"
            ><Icon name="settings" :size="18" />Instellingen</a
          ><a href="#/help" @click="drawer = false"
            ><Icon name="help" :size="18" />Help & Contact</a
          >
        </div>
        <div class="feedback-card">
          <span class="feedback-bulb"
            ><Icon name="lightbulb" :size="37"
          /></span>
          <div>
            <strong>Uw mening telt</strong>
            <p>Help ons het platform te verbeteren.</p>
          </div>
          <button
            @click="
              drawer = false;
              feedback = true;
              feedbackSaved = false;
            "
          >
            Feedback geven
          </button>
        </div>
      </section>
    </div>
    <div v-if="feedback" class="modal-backdrop" @click.self="feedback = false">
      <section
        class="feedback-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="feedback-title"
      >
        <button
          class="icon-button modal-close"
          aria-label="Feedback sluiten"
          @click="feedback = false"
        >
          <Icon name="close" /></button
        ><Icon name="lightbulb" :size="32" />
        <h2 id="feedback-title">Uw mening telt</h2>
        <p>Wat kunnen we verbeteren aan deze eerste versie?</p>
        <form v-if="!feedbackSaved" @submit.prevent="saveFeedback">
          <label for="feedback-text">Uw feedback</label
          ><textarea
            id="feedback-text"
            v-model="feedbackText"
            required
            maxlength="2000"
            rows="5"
          /><small>Dit voorbeeld bewaart feedback alleen op dit apparaat.</small
          ><button class="primary-button" type="submit">
            Feedback bewaren
          </button>
        </form>
        <p v-else role="status" class="feedback-success">
          <Icon name="check" />Bedankt! Uw feedback is op dit apparaat bewaard.
        </p>
      </section>
    </div>
    <p v-if="notice" role="status" class="toast">
      {{ notice
      }}<button aria-label="Melding sluiten" @click="notice = ''">×</button>
    </p>
  </div>
</template>
