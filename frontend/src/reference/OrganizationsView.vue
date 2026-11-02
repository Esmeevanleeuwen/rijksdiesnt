<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { getOrganization, getOrganizations, safeUrl } from "../api";
import type { Organization } from "../api";
import Icon from "../components/Icon.vue";
import GovernmentMark from "../components/GovernmentMark.vue";

const props = defineProps<{ organizationId?: string; initialQuery?: string }>();
const emit = defineEmits<{ navigate: [route: string] }>();
const query = ref(props.initialQuery ?? "");
const submittedQuery = ref(props.initialQuery ?? "");
const type = ref("");
const records = ref<Organization[]>([]);
const organization = ref<Organization | null>(null);
const loading = ref(false);
const error = ref("");
const activeTab = ref("Overzicht");
const saved = ref(false);
const feedback = ref("");
const sharing = ref(false);
const moreOpen = ref(false);
const provenancePanel = ref<HTMLElement | null>(null);
let controller: AbortController | undefined;
let requestId = 0;
const bookmarkKey = "overheidsinformatie.saved";
const tabs = [
  "Overzicht",
  "Organisatie",
  "Beleid & Plannen",
  "Data & API's",
  "Nieuws",
  "Samenwerkingen",
];
const bzk: Organization = {
  id: "bzk",
  official_name: "Ministerie van Binnenlandse Zaken en Koninkrijksrelaties",
  display_name: null,
  organization_type: "Ministerie",
  description:
    "Het ministerie van Binnenlandse Zaken en Koninkrijksrelaties (BZK) is verantwoordelijk voor het binnenlands bestuur, de democratie, het constitutiebestel en de Koninkrijksrelaties.",
  website_url:
    "https://www.rijksoverheid.nl/ministeries/ministerie-van-binnenlandse-zaken-en-koninkrijksrelaties",
  provenance: [],
};
const isShowcase = computed(() => props.organizationId === "bzk");
const selected = computed(() => (isShowcase.value ? bzk : organization.value));
const title = computed(() => selected.value?.official_name ?? "Organisatie");
const showShowcase = computed(() => {
  const term = submittedQuery.value.toLocaleLowerCase("nl");
  return (
    (!term ||
      `${bzk.official_name} BZK`.toLocaleLowerCase("nl").includes(term)) &&
    (!type.value || type.value === "Ministerie")
  );
});
const types = computed(() =>
  [
    ...new Set([
      "Ministerie",
      ...records.value
        .map((record) => record.organization_type)
        .filter((value): value is string => Boolean(value)),
    ]),
  ].sort(),
);
const visibleRecords = computed(() =>
  records.value.filter(
    (record) => !type.value || record.organization_type === type.value,
  ),
);
const displayWebsite = computed(() => {
  const url = safeUrl(selected.value?.website_url ?? null);
  return url
    ? new URL(url).hostname.replace(/^www\./, "")
    : "Nog niet beschikbaar";
});

function savedItems(): { id: string; name: string }[] {
  const stored: unknown = JSON.parse(localStorage.getItem(bookmarkKey) ?? "[]");
  return Array.isArray(stored)
    ? stored.filter(
        (item): item is { id: string; name: string } =>
          Boolean(item) &&
          typeof item.id === "string" &&
          typeof item.name === "string",
      )
    : [];
}
function readSaved() {
  try {
    saved.value = savedItems().some((item) => item.id === props.organizationId);
  } catch {
    saved.value = false;
  }
}
function toggleSaved() {
  if (!selected.value) return;
  try {
    const next = saved.value
      ? savedItems().filter((item) => item.id !== props.organizationId)
      : [
          ...savedItems().filter((item) => item.id !== props.organizationId),
          { id: selected.value.id, name: selected.value.official_name },
        ];
    localStorage.setItem(bookmarkKey, JSON.stringify(next));
    saved.value = !saved.value;
    window.dispatchEvent(new Event("saved-organizations-changed"));
    feedback.value = saved.value
      ? "Organisatie opgeslagen in Mijn overzicht."
      : "Organisatie verwijderd uit Mijn overzicht.";
  } catch {
    feedback.value = "Opslaan is in deze browser niet beschikbaar.";
  }
}
async function share() {
  sharing.value = false;
  try {
    if (!navigator.clipboard?.writeText)
      throw new Error("Clipboard unavailable");
    await navigator.clipboard.writeText(window.location.href);
    feedback.value = "Link gekopieerd. U kunt deze nu delen.";
  } catch {
    sharing.value = true;
    feedback.value = "Kopieer de onderstaande link om deze pagina te delen.";
  }
}
function shareUrl() {
  return window.location.href;
}
async function showProvenance() {
  activeTab.value = "Overzicht";
  moreOpen.value = false;
  await nextTick();
  provenancePanel.value?.focus();
  provenancePanel.value?.scrollIntoView?.({ block: "nearest" });
}
async function load() {
  controller?.abort();
  controller = new AbortController();
  const id = ++requestId;
  error.value = "";
  organization.value = null;
  feedback.value = "";
  sharing.value = false;
  moreOpen.value = false;
  activeTab.value = "Overzicht";
  readSaved();
  if (isShowcase.value) {
    loading.value = false;
    return;
  }
  loading.value = true;
  try {
    if (props.organizationId) {
      const response = await getOrganization(
        props.organizationId,
        controller.signal,
      );
      if (id === requestId) organization.value = response.data;
    } else {
      const response = await getOrganizations(
        submittedQuery.value,
        controller.signal,
      );
      if (id === requestId) records.value = response.data;
    }
  } catch (cause) {
    if (
      id === requestId &&
      !(cause instanceof DOMException && cause.name === "AbortError")
    )
      error.value =
        cause instanceof Error && cause.message !== "Failed to fetch"
          ? cause.message
          : "Geen verbinding met de gegevensdienst. Probeer het opnieuw.";
  } finally {
    if (id === requestId) loading.value = false;
  }
}
function search() {
  submittedQuery.value = query.value.trim();
  type.value = "";
  void load();
}
function formatDate(value: string) {
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? "Niet bekend"
    : new Intl.DateTimeFormat("nl-NL", {
        dateStyle: "long",
        timeZone: "Europe/Amsterdam",
      }).format(date);
}
watch(
  () => props.organizationId,
  () => {
    void load();
  },
  { immediate: true },
);
watch(
  () => props.initialQuery,
  (value) => {
    if (props.organizationId) return;
    query.value = value ?? "";
    submittedQuery.value = value ?? "";
    void load();
  },
);
onBeforeUnmount(() => {
  controller?.abort();
  requestId++;
});
</script>

<template>
  <section class="org-page">
    <nav class="org-breadcrumbs" aria-label="Kruimelpad">
      <a href="#/" @click.prevent="emit('navigate', '/')">Home</a
      ><Icon name="chevron-right" :size="12" /><a
        v-if="organizationId"
        href="#/organisaties"
        @click.prevent="emit('navigate', '/organisaties')"
        >Organisaties</a
      ><span v-else>Organisaties</span
      ><template v-if="organizationId"
        ><Icon name="chevron-right" :size="12" /><span>{{
          title
        }}</span></template
      >
    </nav>
    <template v-if="!organizationId">
      <div class="org-directory-heading">
        <div>
          <h1><Icon name="organizations" :size="25" /> Organisaties</h1>
          <p>
            Vind overheidsorganisaties en ontdek hoe zij met elkaar verbonden
            zijn.
          </p>
        </div>
        <span class="org-demo-label">Voorbeeldomgeving</span>
      </div>
      <form class="org-directory-search" role="search" @submit.prevent="search">
        <Icon name="search" :size="19" /><label
          class="org-sr-only"
          for="org-search"
          >Zoek een organisatie</label
        ><input
          id="org-search"
          v-model="query"
          type="search"
          maxlength="200"
          placeholder="Zoek op organisatie, onderwerp of locatie…"
        /><button type="submit">
          Zoeken <Icon name="arrow-right" :size="16" />
        </button>
      </form>
      <div class="org-directory-toolbar">
        <p v-if="!loading && !error">
          {{ visibleRecords.length + (showShowcase ? 1 : 0) }} organisaties
          <span v-if="submittedQuery">voor “{{ submittedQuery }}”</span>
        </p>
        <span v-else></span
        ><label
          >Organisatietype
          <select v-model="type">
            <option value="">Alle typen</option>
            <option v-for="item in types" :key="item">{{ item }}</option>
          </select></label
        >
      </div>
      <div v-if="loading" class="org-state" role="status">
        Organisaties laden…
      </div>
      <div v-else-if="error" class="org-state org-error" role="alert">
        <h2>Ophalen is niet gelukt</h2>
        <p>{{ error }}</p>
        <button type="button" @click="load">Opnieuw proberen</button>
      </div>
      <div v-else class="org-directory-grid">
        <button
          v-if="showShowcase"
          type="button"
          class="org-directory-card org-showcase-card"
          @click="emit('navigate', '/organisatie/bzk')"
        >
          <span class="org-card-icon"><Icon name="building" :size="24" /></span
          ><span class="org-card-content"
            ><small
              >Ministerie
              <span class="org-demo-label">Ontwerpvoorbeeld</span></small
            ><strong>{{ bzk.official_name }}</strong
            ><span
              >Bekijk het organisatieprofiel, kerngegevens en gerelateerde
              informatie.</span
            ></span
          ><Icon name="chevron-right" :size="17" />
        </button>
        <button
          v-for="record in visibleRecords"
          :key="record.id"
          type="button"
          class="org-directory-card"
          @click="emit('navigate', `/organisatie/${record.id}`)"
        >
          <span class="org-card-icon"><Icon name="building" :size="24" /></span
          ><span class="org-card-content"
            ><small
              >{{ record.organization_type || "Organisatie" }}
              <span class="org-demo-label">Fictief voorbeeld</span></small
            ><strong>{{ record.display_name || record.official_name }}</strong
            ><span>{{ record.description }}</span
            ><span class="org-card-source"
              ><Icon name="document" :size="12" />
              {{ record.provenance.length }}
              {{
                record.provenance.length === 1
                  ? "bron gekoppeld"
                  : "bronnen gekoppeld"
              }}</span
            ></span
          ><Icon name="chevron-right" :size="17" />
        </button>
        <div v-if="!visibleRecords.length && !showShowcase" class="org-state">
          <h2>Geen organisaties gevonden</h2>
          <p>Probeer een andere zoekterm of een ander organisatietype.</p>
          <button
            type="button"
            @click="
              query = '';
              search();
            "
          >
            Toon alle organisaties
          </button>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="org-top-actions">
        <a
          href="#/organisaties"
          class="org-back"
          @click.prevent="emit('navigate', '/organisaties')"
          ><Icon name="arrow-left" :size="14" /> Terug naar overzicht</a
        >
        <div class="org-action-buttons">
          <button
            type="button"
            class="org-save"
            :aria-pressed="saved"
            @click="toggleSaved"
          >
            <Icon name="star" :size="15" />
            {{ saved ? "Opgeslagen" : "Opslaan" }}</button
          ><button type="button" @click="share">
            <Icon name="share" :size="15" /> Delen
          </button>
          <div class="org-more">
            <button
              type="button"
              class="org-more-button"
              aria-label="Meer opties"
              :aria-expanded="moreOpen"
              @click="moreOpen = !moreOpen"
            >
              <Icon name="more" :size="16" />
            </button>
            <div v-if="moreOpen" class="org-more-menu">
              <button type="button" @click="showProvenance">
                Broninformatie bekijken
              </button>
            </div>
          </div>
        </div>
      </div>
      <p v-if="feedback" class="org-feedback" role="status">{{ feedback }}</p>
      <input
        v-if="sharing"
        class="org-share-input"
        aria-label="Link om te delen"
        :value="shareUrl()"
        readonly
        @focus="($event.target as HTMLInputElement).select()"
      />
      <div v-if="loading" class="org-state" role="status">
        Organisatie laden…
      </div>
      <div v-else-if="error" class="org-state org-error" role="alert">
        <h1>Organisatie niet beschikbaar</h1>
        <p>{{ error }}</p>
        <button type="button" @click="load">Opnieuw proberen</button>
      </div>
      <template v-else-if="selected">
        <header class="org-profile-header">
          <div class="org-crest"><GovernmentMark /></div>
          <div class="org-profile-title">
            <h1>{{ title }}</h1>
            <span class="org-government-label">{{
              isShowcase
                ? "Rijksoverheid"
                : selected.organization_type || "Organisatie"
            }}</span
            ><span class="org-demo-label">{{
              isShowcase ? "Ontwerpvoorbeeld" : "Fictief voorbeeld"
            }}</span>
            <p>
              {{
                selected.description ||
                "Er is nog geen beschrijving beschikbaar."
              }}
            </p>
          </div>
        </header>
        <div class="org-contact-grid">
          <div>
            <Icon name="pin" :size="16" /><span
              ><strong>{{
                isShowcase ? "Rijkskantoor BZK" : "Vestigingsadres"
              }}</strong
              ><small>Adres nog niet geverifieerd</small></span
            >
          </div>
          <div>
            <Icon name="phone" :size="16" /><span
              >Telefoon nog niet beschikbaar</span
            >
          </div>
          <div>
            <Icon name="globe" :size="16" /><a
              v-if="safeUrl(selected.website_url)"
              :href="safeUrl(selected.website_url)"
              target="_blank"
              rel="noopener noreferrer"
              >{{ displayWebsite
              }}<span class="org-sr-only"> (opent in nieuw tabblad)</span></a
            ><span v-else>Website nog niet beschikbaar</span>
          </div>
          <div>
            <Icon name="mail" :size="16" /><span
              >E-mail nog niet beschikbaar</span
            >
          </div>
        </div>
        <div
          class="org-tabs"
          role="tablist"
          aria-label="Organisatie-informatie"
        >
          <button
            v-for="tab in tabs"
            :id="`org-tab-${tabs.indexOf(tab)}`"
            :key="tab"
            type="button"
            role="tab"
            :aria-selected="activeTab === tab"
            aria-controls="org-tab-panel"
            @click="activeTab = tab"
          >
            {{ tab }}
          </button>
        </div>
        <div
          id="org-tab-panel"
          role="tabpanel"
          :aria-labelledby="`org-tab-${tabs.indexOf(activeTab)}`"
        >
          <div v-if="activeTab === 'Overzicht'" class="org-profile-columns">
            <div class="org-main-column">
              <section class="org-panel">
                <h2>Kerngegevens</h2>
                <dl class="org-facts">
                  <div>
                    <dt>Organisatietype</dt>
                    <dd>{{ selected.organization_type || "Niet bekend" }}</dd>
                  </div>
                  <div>
                    <dt>ROO URI</dt>
                    <dd class="org-fact-muted">Nog niet gekoppeld</dd>
                  </div>
                  <div>
                    <dt>Bestuurslaag</dt>
                    <dd>{{ isShowcase ? "Rijk" : "Niet geverifieerd" }}</dd>
                  </div>
                  <div>
                    <dt>Werkgebied</dt>
                    <dd>
                      {{ isShowcase ? "Landelijk" : "Niet geverifieerd" }}
                    </dd>
                  </div>
                  <div>
                    <dt>Rechtsvorm</dt>
                    <dd>Niet geverifieerd</dd>
                  </div>
                  <div>
                    <dt>Website(s)</dt>
                    <dd>
                      <a
                        v-if="safeUrl(selected.website_url)"
                        :href="safeUrl(selected.website_url)"
                        target="_blank"
                        rel="noopener noreferrer"
                        >{{ displayWebsite }}
                        <Icon name="external" :size="11" /></a
                      ><span v-else>Niet beschikbaar</span>
                    </dd>
                  </div>
                  <div>
                    <dt>Gerelateerde organisaties</dt>
                    <dd>Nog niet gekoppeld</dd>
                  </div>
                </dl>
              </section>
              <section class="org-panel org-description">
                <h2>Beschrijving</h2>
                <p>
                  {{
                    selected.description ||
                    "Er is nog geen beschrijving beschikbaar."
                  }}
                </p>
                <p v-if="isShowcase" class="org-description-note">
                  Dit profiel is een ontwerpvoorbeeld. Identificaties en
                  contactgegevens zijn nog niet gekoppeld aan een gecontroleerde
                  bron.
                </p>
              </section>
              <section
                ref="provenancePanel"
                class="org-panel org-provenance"
                tabindex="-1"
              >
                <h2>Bronvermelding</h2>
                <template v-if="selected.provenance.length"
                  ><div
                    v-for="(source, index) in selected.provenance"
                    :key="`${source.external_id}-${index}`"
                    class="org-source"
                  >
                    <span class="org-demo-label">{{
                      source.is_demo ? "Demobron" : "Bron"
                    }}</span>
                    <h3>{{ source.name }}</h3>
                    <dl>
                      <div>
                        <dt>Bronidentificatie</dt>
                        <dd>{{ source.external_id }}</dd>
                      </div>
                      <div>
                        <dt>Laatst verwerkt</dt>
                        <dd>{{ formatDate(source.retrieved_at) }}</dd>
                      </div>
                    </dl>
                    <a
                      v-if="safeUrl(source.url)"
                      :href="safeUrl(source.url)"
                      target="_blank"
                      rel="noopener noreferrer"
                      >Bekijk de oorspronkelijke bron
                      <Icon name="external" :size="12"
                    /></a></div
                ></template>
                <p v-else>
                  Het ontwerp is gebaseerd op het aangeleverde schermvoorbeeld.
                  Er zijn nog geen ROO-bronrecords aan dit profiel gekoppeld.
                </p>
              </section>
            </div>
            <aside class="org-panel org-quick-links">
              <h2>Snelle links</h2>
              <button type="button" @click="emit('navigate', '/data')">
                <span class="org-quick-icon"
                  ><Icon name="data" :size="18" /></span
                ><span
                  ><strong>Bekijk gerelateerde datasets</strong
                  ><small>Data.overheid.nl</small></span
                ><Icon name="chevron-right" :size="13" /></button
              ><button type="button" @click="activeTab = `Data & API's`">
                <span class="org-quick-icon"
                  ><Icon name="api" :size="18" /></span
                ><span
                  ><strong>Bekijk beschikbare API's</strong
                  ><small>API-register</small></span
                ><Icon name="chevron-right" :size="13" /></button
              ><button type="button" @click="activeTab = 'Beleid & Plannen'">
                <span class="org-quick-icon"
                  ><Icon name="document" :size="18" /></span
                ><span
                  ><strong>Bekijk open documenten</strong
                  ><small>Woo-index</small></span
                ><Icon name="chevron-right" :size="13" /></button
              ><button type="button" @click="showProvenance">
                <span class="org-quick-icon"
                  ><Icon name="globe" :size="18" /></span
                ><span
                  ><strong>Bekijk domeinen en websites</strong
                  ><small>Broninformatie</small></span
                ><Icon name="chevron-right" :size="13" />
              </button>
            </aside>
          </div>
          <section
            v-else-if="activeTab === 'Organisatie'"
            class="org-panel org-secondary-panel"
          >
            <h2>Organisatie</h2>
            <p>{{ selected.official_name }}</p>
            <dl class="org-facts">
              <div>
                <dt>Interne identificatie</dt>
                <dd class="org-identifier">{{ selected.id }}</dd>
              </div>
              <div>
                <dt>Organisatietype</dt>
                <dd>{{ selected.organization_type || "Niet bekend" }}</dd>
              </div>
              <div>
                <dt>Gegevensstatus</dt>
                <dd>
                  {{
                    isShowcase
                      ? "Ontwerpvoorbeeld"
                      : "Fictieve voorbeeldgegevens"
                  }}
                </dd>
              </div>
            </dl>
            <p>
              Onderliggende organisaties worden hier getoond zodra hun relatie
              uit een gecontroleerde bron is vastgesteld.
            </p>
          </section>
          <section
            v-else-if="activeTab === `Data & API's`"
            class="org-panel org-secondary-panel"
          >
            <h2>Data &amp; API's</h2>
            <p>
              Er zijn nog geen geverifieerde datasets of API's aan deze
              organisatie gekoppeld.
            </p>
            <button
              type="button"
              class="org-inline-link"
              @click="emit('navigate', '/data')"
            >
              Verken alle voorbeelddata
              <Icon name="arrow-right" :size="15" /></button
            ><a
              class="org-inline-link"
              href="https://developer.overheid.nl/"
              target="_blank"
              rel="noopener noreferrer"
              >Bekijk het officiële API-register
              <Icon name="external" :size="13"
            /></a>
          </section>
          <section
            v-else-if="activeTab === 'Nieuws'"
            class="org-panel org-secondary-panel"
          >
            <h2>Nieuws</h2>
            <p>
              Er zijn nog geen nieuwsberichten uit een gecontroleerde bron aan
              dit profiel gekoppeld.
            </p>
            <button
              type="button"
              class="org-inline-link"
              @click="emit('navigate', '/nieuws')"
            >
              Bekijk het nieuwsvoorbeeld <Icon name="arrow-right" :size="15" />
            </button>
          </section>
          <section
            v-else-if="activeTab === 'Beleid & Plannen'"
            class="org-panel org-secondary-panel"
          >
            <h2>Beleid &amp; Plannen</h2>
            <p>
              Beleidsdossiers en openbare documenten verschijnen hier wanneer
              hun bron en relatie met deze organisatie zijn gecontroleerd.
            </p>
            <a
              class="org-inline-link"
              href="https://open.overheid.nl/"
              target="_blank"
              rel="noopener noreferrer"
              >Zoek in openbare overheidsdocumenten
              <Icon name="external" :size="13"
            /></a>
          </section>
          <section v-else class="org-panel org-secondary-panel">
            <h2>Samenwerkingen</h2>
            <p>
              Er zijn nog geen gecontroleerde samenwerkingsrelaties
              geregistreerd. Relaties worden alleen getoond met een herleidbare
              bron.
            </p>
            <button
              type="button"
              class="org-inline-link"
              @click="emit('navigate', '/organisaties')"
            >
              Verken organisaties <Icon name="arrow-right" :size="15" />
            </button>
          </section>
        </div>
      </template>
    </template>
  </section>
</template>

<style scoped>
.org-page {
  padding: 22px 30px 34px;
  max-width: 1440px;
  margin: auto;
  color: #23466c;
  font-size: 13px;
}
.org-breadcrumbs {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  color: #7790aa;
  margin-bottom: 20px;
  min-width: 0;
}
.org-breadcrumbs a {
  color: #2580dc;
  text-decoration: none;
  white-space: nowrap;
}
.org-breadcrumbs > span:last-child {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.org-breadcrumbs svg {
  flex-shrink: 0;
}
.org-top-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 15px;
}
.org-back {
  font-size: 11px;
  color: #3685d4;
  text-decoration: none;
  display: inline-flex;
  gap: 8px;
  align-items: center;
}
.org-action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}
.org-action-buttons button {
  border: 1px solid #dce6f1;
  border-radius: 5px;
  background: #fff;
  color: #44658b;
  padding: 8px 12px;
  font: inherit;
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
  min-height: 33px;
}
.org-action-buttons .org-save {
  background: #0879dc;
  border-color: #0879dc;
  color: white;
}
.org-save[aria-pressed="true"] {
  background: #0d548f;
}
.org-more {
  position: relative;
}
.org-action-buttons .org-more-button {
  padding: 8px;
}
.org-more-menu {
  position: absolute;
  right: 0;
  top: 39px;
  background: white;
  border: 1px solid #dce6f1;
  box-shadow: 0 5px 15px #173a6017;
  z-index: 5;
  width: 195px;
  padding: 5px;
  border-radius: 5px;
}
.org-more-menu button {
  width: 100%;
  border: 0;
  text-align: left;
}
.org-feedback {
  padding: 9px 12px;
  background: #eff7fc;
  border: 1px solid #dbeaf5;
  border-radius: 4px;
  color: #2871ad;
  font-size: 11px;
  margin: 0 0 12px;
}
.org-share-input {
  width: 100%;
  padding: 9px;
  border: 1px solid #dce6f1;
  border-radius: 5px;
  margin-bottom: 12px;
  font: inherit;
  color: #3b5f84;
}
.org-profile-header {
  display: flex;
  gap: 17px;
  align-items: flex-start;
}
.org-crest {
  width: 54px;
  height: 69px;
  background: #0b416f;
  display: grid;
  place-items: center;
  border-radius: 2px;
  color: white;
  flex-shrink: 0;
}
.org-crest > svg {
  width: 37px;
  height: 48px;
}
.org-profile-title {
  flex: 1;
  min-width: 0;
}
.org-profile-title h1 {
  font-size: 24px;
  line-height: 1.2;
  font-weight: 700;
  letter-spacing: -0.5px;
  max-width: 690px;
  margin: 0 0 8px;
  color: #193e66;
}
.org-government-label {
  display: inline-block;
  background: #f0f5fb;
  color: #587392;
  font-size: 9px;
  padding: 3px 7px;
  border-radius: 3px;
  margin-right: 7px;
}
.org-demo-label {
  display: inline-block;
  background: #edf5fc;
  border: 1px solid #dceaf6;
  color: #6a87a5;
  font-size: 9px;
  font-weight: 500;
  border-radius: 3px;
  padding: 2px 6px;
  vertical-align: middle;
  white-space: nowrap;
}
.org-profile-title p {
  margin: 10px 0 0;
  color: #57718e;
  font-size: 12px;
  line-height: 1.75;
  max-width: 860px;
}
.org-contact-grid {
  padding-left: 71px;
  margin: 17px 0 25px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: 38px;
  row-gap: 13px;
  max-width: 850px;
  color: #6c86a1;
  font-size: 10px;
}
.org-contact-grid > div {
  display: flex;
  align-items: flex-start;
  gap: 9px;
}
.org-contact-grid svg {
  color: #6f8caa;
  flex-shrink: 0;
}
.org-contact-grid strong {
  font-weight: 500;
  display: block;
  font-size: 10px;
  color: #587392;
}
.org-contact-grid small {
  display: block;
  font-size: 9px;
  line-height: 1.7;
  color: #8198ae;
}
.org-contact-grid a {
  color: #3b8dd8;
  text-decoration: none;
  overflow-wrap: anywhere;
}
.org-tabs {
  display: flex;
  border-bottom: 1px solid #dce8f4;
  margin-bottom: 20px;
  gap: 21px;
  overflow-x: auto;
  scrollbar-width: thin;
}
.org-tabs button {
  position: relative;
  flex-shrink: 0;
  border: 0;
  background: none;
  font: inherit;
  font-size: 11px;
  font-weight: 600;
  color: #6b829e;
  padding: 13px 5px 14px;
  cursor: pointer;
  white-space: nowrap;
}
.org-tabs button[aria-selected="true"] {
  color: #157cde;
}
.org-tabs button[aria-selected="true"]::after {
  content: "";
  height: 2px;
  background: #177fe4;
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  border-radius: 3px 3px 0 0;
}
.org-profile-columns {
  display: grid;
  grid-template-columns: minmax(0, 1.9fr) minmax(245px, 1fr);
  gap: 20px;
  align-items: start;
}
.org-main-column {
  display: flex;
  flex-direction: column;
  gap: 18px;
  min-width: 0;
}
.org-panel {
  border: 1px solid #e0eaf4;
  background: linear-gradient(135deg, #fbfdff, #f5f9fd);
  border-radius: 7px;
  padding: 19px 20px;
}
.org-panel h2 {
  margin: 0 0 15px;
  font-size: 14px;
  font-weight: 700;
  color: #264b72;
}
.org-facts {
  margin: 0;
  font-size: 11px;
}
.org-facts > div {
  display: grid;
  grid-template-columns: minmax(105px, 34%) minmax(0, 1fr);
  gap: 10px;
  margin: 12px 0;
  line-height: 1.6;
}
.org-facts dt {
  color: #86a0ba;
}
.org-facts dd {
  margin: 0;
  color: #486b8d;
  overflow-wrap: anywhere;
}
.org-facts a {
  color: #368bdb;
  text-decoration: none;
  display: inline-flex;
  gap: 5px;
  align-items: center;
  word-break: break-word;
}
.org-facts .org-fact-muted {
  color: #8299b2;
}
.org-description p,
.org-provenance > p,
.org-secondary-panel > p {
  font-size: 11px;
  color: #5f7c98;
  line-height: 1.85;
  margin: 0;
}
.org-description .org-description-note {
  font-size: 10px;
  border-top: 1px solid #e4edf5;
  padding-top: 11px;
  margin-top: 13px;
  color: #8a9daf;
}
.org-provenance h2 {
  margin-bottom: 10px;
}
.org-source + .org-source {
  border-top: 1px solid #dce6ef;
  margin-top: 15px;
  padding-top: 15px;
}
.org-source h3 {
  font-size: 12px;
  margin: 8px 0;
}
.org-source dl {
  font-size: 10px;
  color: #7c95ae;
  margin: 9px 0;
}
.org-source dl > div {
  display: grid;
  grid-template-columns: 130px minmax(0, 1fr);
  margin: 6px 0;
  gap: 8px;
}
.org-source dd {
  margin: 0;
  color: #5c7a96;
  overflow-wrap: anywhere;
}
.org-source a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #368bdb;
  text-decoration: none;
  font-size: 10px;
}
.org-quick-links h2 {
  margin-bottom: 10px;
}
.org-quick-links button {
  width: 100%;
  border: 0;
  background: none;
  text-align: left;
  padding: 13px 0;
  display: flex;
  gap: 12px;
  align-items: center;
  color: #64819e;
  cursor: pointer;
  font: inherit;
}
.org-quick-icon {
  height: 33px;
  width: 33px;
  display: grid;
  place-items: center;
  color: #6d96bb;
  background: #edf4fb;
  border: 1px solid #e4eef7;
  border-radius: 7px;
  flex-shrink: 0;
}
.org-quick-links button > span:nth-child(2) {
  flex: 1;
  min-width: 0;
}
.org-quick-links strong {
  display: block;
  font-size: 10px;
  line-height: 1.55;
  font-weight: 600;
  color: #577797;
}
.org-quick-links small {
  display: block;
  color: #4891d6;
  font-size: 9px;
  margin-top: 3px;
}
.org-quick-links button > svg {
  color: #87add0;
}
.org-quick-links button:hover strong {
  color: #117ed8;
}
.org-secondary-panel {
  max-width: 850px;
  min-height: 205px;
}
.org-secondary-panel .org-facts {
  margin-top: 22px;
}
.org-inline-link {
  display: flex;
  align-items: center;
  gap: 7px;
  width: fit-content;
  font: inherit;
  font-size: 11px;
  color: #167fd6;
  text-decoration: none;
  margin-top: 20px;
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
}
.org-identifier {
  font-family: ui-monospace, monospace;
  font-size: 10px;
}
.org-directory-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 25px 0;
}
.org-directory-heading h1 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 25px;
  line-height: 1.3;
  letter-spacing: -0.4px;
  margin: 0;
}
.org-directory-heading p {
  margin: 10px 0 0;
  color: #7a93ac;
  font-size: 12px;
}
.org-directory-search {
  display: flex;
  align-items: center;
  border: 1px solid #dbe6f0;
  background: #fff;
  border-radius: 6px;
  padding: 6px 7px 6px 14px;
  gap: 10px;
  max-width: 850px;
  color: #9ab0c6;
}
.org-directory-search input {
  flex: 1;
  min-width: 0;
  border: 0;
  background: transparent;
  font: inherit;
  color: #42678b;
  font-size: 12px;
  padding: 6px;
}
.org-directory-search input::placeholder {
  color: #a0b4c8;
}
.org-directory-search > button {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #087dde;
  color: white;
  border: 0;
  border-radius: 4px;
  font: inherit;
  font-size: 11px;
  padding: 9px 15px;
  cursor: pointer;
}
.org-directory-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin: 22px 0 15px;
  color: #7290ac;
  font-size: 11px;
}
.org-directory-toolbar label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 10px;
}
.org-directory-toolbar select {
  border: 1px solid #dee8f1;
  background: #fff;
  color: #63819d;
  font: inherit;
  font-size: 10px;
  padding: 7px 25px 7px 9px;
  border-radius: 4px;
  max-width: 220px;
}
.org-directory-grid {
  display: grid;
  gap: 12px;
}
.org-directory-card {
  display: flex;
  align-items: center;
  gap: 15px;
  width: 100%;
  padding: 20px;
  border: 1px solid #dfe9f3;
  background: white;
  border-radius: 7px;
  font: inherit;
  text-align: left;
  color: #49719a;
  cursor: pointer;
}
.org-directory-card:hover {
  border-color: #91bfe5;
  background: #fbfdff;
}
.org-card-icon {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  background: #eaf3fc;
  color: #3382c5;
  border-radius: 8px;
  flex-shrink: 0;
}
.org-showcase-card .org-card-icon {
  background: #104a78;
  color: white;
}
.org-card-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}
.org-card-content small {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
  font-size: 9px;
  color: #829bb4;
}
.org-card-content strong {
  font-size: 14px;
  font-weight: 600;
  color: #294f76;
}
.org-card-content > span:not(.org-card-source) {
  font-size: 11px;
  color: #7a94ad;
  line-height: 1.7;
  max-width: 820px;
}
.org-card-source {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 9px;
  color: #88a1b9;
  margin-top: 3px;
}
.org-state {
  border: 1px solid #e0eaf3;
  background: #f8fbfe;
  text-align: center;
  padding: 35px 20px;
  border-radius: 6px;
  font-size: 12px;
  color: #7793ac;
  grid-column: 1/-1;
}
.org-state h1,
.org-state h2 {
  font-size: 17px;
  margin: 0 0 9px;
  color: #3c5f7d;
}
.org-state p {
  margin: 8px 0;
}
.org-state button {
  font: inherit;
  font-size: 11px;
  color: #197bc9;
  background: #eff7fe;
  border: 1px solid #cce2f5;
  padding: 8px 12px;
  border-radius: 4px;
  margin-top: 9px;
  cursor: pointer;
}
.org-error {
  background: #fffaf6;
  border-color: #eee0d5;
  color: #956b51;
}
.org-sr-only {
  position: absolute;
  overflow: hidden;
  width: 1px;
  height: 1px;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}
.org-page button,
.org-page a,
.org-page select,
.org-page input {
  outline-offset: 4px;
}
.org-page :focus-visible {
  outline: 2px solid #2386dc;
}
.org-provenance:focus {
  outline: 2px solid #90bbe0;
  outline-offset: 3px;
}
@media (min-width: 1500px) {
  .org-page {
    padding: 25px 40px 40px;
  }
  .org-profile-title h1 {
    font-size: 27px;
  }
  .org-profile-title p {
    font-size: 13px;
  }
  .org-tabs button {
    font-size: 12px;
  }
  .org-panel h2 {
    font-size: 15px;
  }
  .org-facts {
    font-size: 12px;
  }
}
@media (max-width: 1050px) {
  .org-page {
    padding: 18px 24px 30px;
  }
  .org-profile-columns {
    gap: 15px;
    grid-template-columns: minmax(0, 1.7fr) minmax(220px, 1fr);
  }
  .org-panel {
    padding: 16px;
  }
  .org-tabs {
    gap: 13px;
  }
  .org-tabs button {
    font-size: 10px;
  }
  .org-profile-title h1 {
    font-size: 22px;
  }
  .org-quick-links button {
    gap: 8px;
  }
  .org-facts > div {
    grid-template-columns: 110px minmax(0, 1fr);
    gap: 6px;
  }
  .org-contact-grid {
    column-gap: 18px;
  }
}
@media (max-width: 760px) {
  .org-page {
    padding: 17px 18px 25px;
  }
  .org-profile-title h1 {
    font-size: 20px;
    line-height: 1.3;
  }
  .org-profile-title p {
    font-size: 11px;
  }
  .org-crest {
    width: 43px;
    height: 58px;
  }
  .org-crest svg {
    width: 29px;
    height: 39px;
  }
  .org-profile-header {
    gap: 12px;
  }
  .org-contact-grid {
    padding-left: 55px;
    grid-template-columns: 1fr;
    gap: 10px;
    margin-bottom: 18px;
  }
  .org-contact-grid > div:nth-child(2) {
    order: 3;
  }
  .org-profile-columns {
    grid-template-columns: minmax(0, 1fr);
  }
  .org-quick-links {
    order: -1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 12px;
  }
  .org-quick-links h2 {
    grid-column: 1/-1;
  }
  .org-quick-links button {
    padding: 9px 0;
  }
  .org-quick-links button > svg {
    display: none;
  }
  .org-quick-links strong {
    font-size: 9px;
  }
  .org-quick-links small {
    font-size: 8px;
  }
  .org-quick-icon {
    width: 28px;
    height: 29px;
  }
  .org-top-actions {
    margin-bottom: 18px;
  }
  .org-action-buttons {
    gap: 5px;
  }
  .org-action-buttons button {
    padding: 7px 9px;
    font-size: 10px;
  }
  .org-tabs {
    margin-top: 6px;
    gap: 12px;
  }
  .org-tabs button {
    padding: 13px 2px;
    font-size: 10px;
  }
  .org-breadcrumbs {
    font-size: 9px;
    gap: 4px;
    margin-bottom: 16px;
  }
  .org-directory-heading {
    align-items: flex-start;
    margin: 17px 0 20px;
  }
  .org-directory-heading h1 {
    font-size: 22px;
  }
  .org-directory-heading p {
    font-size: 11px;
    line-height: 1.65;
    max-width: 260px;
  }
  .org-directory-heading > .org-demo-label {
    font-size: 8px;
    margin-top: 6px;
  }
  .org-directory-search {
    gap: 5px;
    padding-left: 10px;
  }
  .org-directory-search input {
    font-size: 11px;
  }
  .org-directory-search > button {
    padding: 8px 11px;
    font-size: 10px;
  }
  .org-directory-search > button svg {
    display: none;
  }
  .org-directory-toolbar {
    gap: 10px;
    font-size: 10px;
    align-items: flex-start;
  }
  .org-directory-toolbar label {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
    max-width: 160px;
  }
  .org-directory-toolbar select {
    max-width: 160px;
  }
  .org-directory-toolbar > p {
    padding-top: 12px;
    line-height: 1.7;
  }
  .org-directory-card {
    padding: 15px 12px;
    gap: 10px;
  }
  .org-card-icon {
    width: 36px;
    height: 39px;
  }
  .org-card-icon svg {
    width: 20px;
  }
  .org-card-content strong {
    font-size: 12px;
    line-height: 1.5;
  }
  .org-card-content > span:not(.org-card-source) {
    font-size: 10px;
  }
  .org-card-content small {
    font-size: 8px;
    gap: 4px;
  }
  .org-card-content .org-demo-label {
    font-size: 8px;
  }
  .org-directory-card > svg {
    width: 13px;
  }
  .org-facts > div {
    grid-template-columns: 110px minmax(0, 1fr);
  }
  .org-source dl > div {
    grid-template-columns: 105px minmax(0, 1fr);
  }
}
</style>
