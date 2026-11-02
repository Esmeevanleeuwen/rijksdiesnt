import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import App from "./App.vue";
import { safeUrl } from "./api";
import type { Organization } from "./api";

const organization: Organization = {
  id: "a0000000-0000-4000-8000-000000000001",
  official_name: "Gemeente Voorbeeldstad",
  display_name: null,
  organization_type: "Gemeente",
  description: "Fictieve gemeente voor deze demonstratie.",
  website_url: null,
  provenance: [{
    name: "Lokale voorbeeldgegevens",
    url: null,
    external_id: "demo-gemeente",
    retrieved_at: "2026-01-01T12:00:00Z",
    is_demo: true,
  }],
};

function response(data: unknown) {
  return { ok: true, json: async () => data } as Response;
}

function mockBackend() {
  const fetch = vi.fn(async (path: string) => response({
    data: path.includes("/organizations/") ? organization : [organization],
    meta: { total: 1, data_mode: "demo" },
  }));
  vi.stubGlobal("fetch", fetch);
  return fetch;
}

const wrappers: ReturnType<typeof mount>[] = [];
async function render(hash = "#/") {
  window.history.replaceState(null, "", `/${hash}`);
  const wrapper = mount(App, { attachTo: document.body });
  wrappers.push(wrapper);
  await flushPromises();
  return wrapper;
}

// Explicitly dispatch hashchange: jsdom does not emulate browser anchor navigation
// consistently. The href still comes from the rendered link under test.
async function follow(href: string | undefined) {
  expect(href).toBeDefined();
  window.history.replaceState(null, "", href!);
  window.dispatchEvent(new HashChangeEvent("hashchange"));
  await flushPromises();
}

beforeEach(() => {
  localStorage.clear();
  vi.spyOn(window, "scrollTo").mockImplementation(() => {});
  window.history.replaceState(null, "", "/#/");
});
afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
  document.body.innerHTML = "";
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe("platformnavigatie volgens het schermvoorbeeld", () => {
  it("opens on Home with the sidebar and links its five categories to real screens", async () => {
    const fetch = mockBackend();
    const wrapper = await render();
    expect(wrapper.get("h1").text()).toBe("Eén platform, alle overheidsinformatie");
    expect(wrapper.get('.desktop-sidebar a[aria-current="page"]').text()).toBe("Home");
    expect(wrapper.findAll(".home-category")).toHaveLength(5);
    expect(fetch).not.toHaveBeenCalled();
    await follow(wrapper.get('.home-category[href="#/data"]').attributes("href"));
    expect(wrapper.get("h1").text()).toBe("Data & Statistieken");
    expect(wrapper.findAll(".data-row")).toHaveLength(5);
    expect(wrapper.find(".desktop-sidebar").exists()).toBe(false);
  });

  it("routes a global search to the organization API with its encoded query", async () => {
    const fetch = mockBackend();
    const wrapper = await render();
    await wrapper.get('input[aria-label="Zoeken in alle overheidsinformatie"]').setValue("water & klimaat");
    await wrapper.get("form.global-search").trigger("submit");
    window.dispatchEvent(new HashChangeEvent("hashchange"));
    await flushPromises();
    expect(window.location.hash).toBe("#/organisaties?q=water%20%26%20klimaat");
    expect(fetch).toHaveBeenCalledWith(
      "/api/v1/organizations?q=water%20%26%20klimaat",
      expect.objectContaining({ signal: expect.any(AbortSignal) }),
    );
    expect(wrapper.get(".org-directory-search input").element).toHaveProperty("value", "water & klimaat");
    expect(wrapper.text()).toContain("Gemeente Voorbeeldstad");
  });

  it("preserves legacy organization deep links and their backend provenance", async () => {
    const fetch = mockBackend();
    const wrapper = await render(`#organisatie/${organization.id}`);
    expect(fetch).toHaveBeenCalledWith(
      `/api/v1/organizations/${organization.id}`,
      expect.objectContaining({ signal: expect.any(AbortSignal) }),
    );
    expect(wrapper.get("h1").text()).toBe("Gemeente Voorbeeldstad");
    expect(wrapper.get(".org-provenance").text()).toContain("demo-gemeente");
    expect(wrapper.get(".org-provenance").text()).toContain("Demobron");
  });

  it("opens the mobile navigation with correct ARIA state and closes it with Escape", async () => {
    mockBackend();
    const wrapper = await render();
    const trigger = wrapper.get('button[aria-label="Menu openen"]');
    expect(trigger.attributes("aria-expanded")).toBe("false");
    await trigger.trigger("click");
    await flushPromises();
    const drawer = wrapper.get('[role="dialog"][aria-label="Navigatiemenu"]');
    expect(drawer.attributes("aria-modal")).toBe("true");
    expect(trigger.attributes("aria-expanded")).toBe("true");
    expect(wrapper.get(".workspace").attributes("inert")).toBeDefined();
    expect(drawer.findAll(".side-nav a")).toHaveLength(6);
    expect(document.activeElement).toBe(wrapper.get('button[aria-label="Menu sluiten"]').element);
    window.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
    await flushPromises();
    expect(wrapper.find('[role="dialog"][aria-label="Navigatiemenu"]').exists()).toBe(false);
    expect(trigger.attributes("aria-expanded")).toBe("false");
    expect(document.activeElement).toBe(trigger.element);
  });

  it("shares a saved backend organization with Mijn overzicht and restores its saved state", async () => {
    mockBackend();
    const wrapper = await render(`#/organisatie/${organization.id}`);
    await wrapper.get(".org-save").trigger("click");
    expect(wrapper.get(".org-save").attributes("aria-pressed")).toBe("true");
    expect(JSON.parse(localStorage.getItem("overheidsinformatie.saved")!)).toEqual([
      { id: organization.id, name: organization.official_name },
    ]);
    await follow("#/mijn-overzicht");
    expect(wrapper.get(".saved-card").text()).toContain("Gemeente Voorbeeldstad");
    await follow(wrapper.get(".saved-card").attributes("href"));
    expect(wrapper.get("h1").text()).toBe("Gemeente Voorbeeldstad");
    expect(wrapper.get(".org-save").attributes("aria-pressed")).toBe("true");
    await wrapper.get(".org-save").trigger("click");
    await follow("#/mijn-overzicht");
    expect(wrapper.text()).toContain("Nog geen organisaties opgeslagen");
  });

  it("opens a Home theme with its matching dataset filter applied", async () => {
    mockBackend();
    const wrapper = await render();
    await follow(wrapper.get('.home-theme[href="#/data?thema=migratie"]').attributes("href"));
    expect(wrapper.get(".data-filter-label").text()).toContain("Migratie");
    expect(wrapper.findAll(".data-row")).toHaveLength(1);
    expect(wrapper.get(".data-row h2").text()).toBe("Migratiecijfers");
    expect(wrapper.findAll(".data-themes button").find(button => button.text() === "Migratie")!.attributes("aria-pressed")).toBe("true");
  });

  it("opens the specific example article linked from the Home news preview", async () => {
    mockBackend();
    const wrapper = await render();
    const firstArticle = wrapper.get(".home-news-row");
    const title = firstArticle.get("h3").text();
    await follow(firstArticle.attributes("href"));
    expect(wrapper.get("h1").text()).toBe("Nieuws");
    const article = wrapper.get('[role="dialog"][aria-labelledby="news-dialog-title"]');
    expect(article.get("h2").text()).toBe(title);
    expect(article.text()).toContain("geen originele publicatie");
    await article.get('button[aria-label="Nieuwsbericht sluiten"]').trigger("click");
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    expect(wrapper.findAll(".news-row")).toHaveLength(5);
  });
});

describe("bronlinks", () => {
  it("accepts web URLs and rejects executable or invalid URLs", () => {
    expect(safeUrl("https://example.org/source")).toBe("https://example.org/source");
    expect(safeUrl("javascript:alert(1)")).toBeUndefined();
    expect(safeUrl("data:text/html,test")).toBeUndefined();
    expect(safeUrl("ongeldig")).toBeUndefined();
    expect(safeUrl(null)).toBeUndefined();
  });
});
