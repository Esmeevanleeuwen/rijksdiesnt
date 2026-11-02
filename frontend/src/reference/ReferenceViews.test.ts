import { afterEach, describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import DataView from "./DataView.vue";
import NewsView from "./NewsView.vue";

const mounted: VueWrapper[] = [];
afterEach(() => { mounted.forEach(wrapper => wrapper.unmount()); mounted.length = 0; });

describe("reference dataset screen", () => {
  it("searches and narrows datasets by theme and freshness", async () => {
    const wrapper = mount(DataView); mounted.push(wrapper);
    expect(wrapper.findAll(".data-row")).toHaveLength(5);
    await wrapper.get('input[aria-label="Zoek in alle data"]').setValue("stikstof");
    expect(wrapper.findAll(".data-row")).toHaveLength(1);
    expect(wrapper.get(".data-row h2").text()).toBe("Stikstofdepositie");
    await wrapper.get('input[aria-label="Zoek in alle data"]').setValue("");
    await wrapper.findAll('[role="tab"]').find(tab => tab.text() === "Nieuwe datasets")!.trigger("click");
    expect(wrapper.findAll(".data-row")).toHaveLength(2);
    await wrapper.findAll('[aria-pressed]').find(button => button.text() === "Woningcrisis")!.trigger("click");
    expect(wrapper.text()).toContain("Geen datasets gevonden");
    await wrapper.get(".data-empty button").trigger("click");
    expect(wrapper.findAll(".data-row")).toHaveLength(5);
  });

  it("opens transparent dataset details and closes them with Escape", async () => {
    const wrapper = mount(DataView); mounted.push(wrapper);
    await wrapper.get(".data-open").trigger("click");
    expect(wrapper.get('[role="dialog"]').text()).toContain("Woningvoorraad Nederland");
    expect(wrapper.get('[role="dialog"]').text()).toContain("nog geen gegevensbestand");
    await wrapper.get('[role="dialog"]').trigger("keydown", { key: "Escape" });
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
  });
});

describe("reference news screen", () => {
  it("filters by government level and reveals all example news", async () => {
    const wrapper = mount(NewsView); mounted.push(wrapper);
    expect(wrapper.findAll(".news-row")).toHaveLength(5);
    await wrapper.get(".news-all").trigger("click");
    expect(wrapper.findAll(".news-row")).toHaveLength(7);
    await wrapper.findAll('[role="tab"]').find(tab => tab.text() === "Provincies")!.trigger("click");
    expect(wrapper.findAll(".news-row")).toHaveLength(1);
    expect(wrapper.get(".news-row").text()).toContain("Provincies werken samen");
    await wrapper.findAll('[role="tab"]').find(tab => tab.text() === "Waterschappen")!.trigger("click");
    expect(wrapper.findAll(".news-row")).toHaveLength(1);
    expect(wrapper.get(".news-row").text()).toContain("Waterschappen bereiden");
  });

  it("opens an article without pretending that it is live official news", async () => {
    const wrapper = mount(NewsView); mounted.push(wrapper);
    await wrapper.get(".news-row").trigger("click");
    expect(wrapper.get('[role="dialog"]').text()).toContain("Kabinet presenteert nieuwe plannen voor woningbouw");
    expect(wrapper.get('[role="dialog"]').text()).toContain("geen originele publicatie");
    await wrapper.get('button[aria-label="Nieuwsbericht sluiten"]').trigger("click");
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
  });
});
