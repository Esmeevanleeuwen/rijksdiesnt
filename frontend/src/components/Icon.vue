<script setup lang="ts">
withDefaults(defineProps<{ name: string; size?: number | string }>(), {
  size: 20,
});
const paths: Record<string, string> = {
  home: "m3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9",
  organizations: "m3 8 9-5 9 5H3Zm2 3v8m5-8v8m4-8v8m5-8v8M3 21h18",
  building:
    "M4 21V3h12v18M8 7h1m3 0h1M8 11h1m3 0h1M8 15h1m3 0h1M9 21v-3h3v3m4-10h4v10H2",
  policy: "M6 3h13v18H6V3Zm-3 4h3m-3 5h3m-3 5h3M10 7h5m-5 4h5m-5 4h3",
  document: "M6 3h9l4 4v14H6V3Zm9 0v5h4M9 12h7m-7 4h7",
  data: "M4 21V3h16v18H4Zm4-5v-4m4 4V8m4 8v-6M7 19h10",
  chart: "M4 18v-5m5 5V9m5 9V5m5 13V9M3 21h19M4 8l5-3 5 1 6-4",
  news: "M5 4h15v17H5V4Zm0 4H2v11a2 2 0 0 0 2 2m4-13h8M8 12h3v4H8v-4Zm6 0h3m-3 4h3",
  map: "m3 5 6-2 6 2 6-2v16l-6 2-6-2-6 2V5Zm6-2v16m6-14v16",
  user: "M20 21a8 8 0 0 0-16 0M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z",
  people:
    "M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6 21v-3a6 6 0 0 1 12 0v3M4 10a2.5 2.5 0 0 1 0-5m16 5a2.5 2.5 0 0 0 0-5M2 19v-3a4 4 0 0 1 3-4m17 7v-3a4 4 0 0 0-3-4",
  shield: "m12 3 8 3v5c0 5-4 8-8 10-4-2-8-5-8-10V6l8-3Zm-3 9 2 2 4-5",
  leaf: "M20 3C10 2 3 8 5 15s15 5 15-12ZM3 21 16 8",
  globe:
    "M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z",
  pin: "M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Zm-4.5 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z",
  search: "M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Zm-2 5 6 6",
  bell: "M18 8a6 6 0 0 0-12 0c0 8-3 8-3 10h18c0-2-3-2-3-10Zm-8 13h4M12 1v1",
  settings:
    "m10 3-1 3-3 1-3 3 2 3-1 3 3 3 3-1 3 2 3-2 3-3-1-3 2-3-3-3-3-1-1-3h-3Zm5 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
  help: "M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.5 8a2.5 2.5 0 1 1 3.5 2.3c-1 .5-1 1.2-1 2.7m0 3v.1",
  menu: "M4 6h16M4 12h16M4 18h16",
  close: "m6 6 12 12M6 18 18 6",
  "arrow-right": "M4 12h16m-6-6 6 6-6 6",
  "arrow-left": "M20 12H4m6-6-6 6 6 6",
  "chevron-right": "m9 5 7 7-7 7",
  "chevron-down": "m5 9 7 7 7-7",
  external: "M14 3h7v7m0-7L10 14M10 3H3v18h18v-7",
  star: "m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2-5.5-3-5.5 3 1-6.2L2 9.6l6.2-.9L12 3Z",
  share:
    "M18 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2.6-4.5 6.8-5m-6.8 8 6.8 5",
  more: "M5 12h.01M12 12h.01M19 12h.01",
  phone:
    "m5 3 4 4-2 3c2 4 3 5 7 7l3-2 4 4c-1 3-3 3-5 2C9 19 5 15 3 8c-1-2-1-4 2-5Z",
  mail: "M3 5h18v14H3V5Zm0 1 9 7 9-7",
  api: "m8 7-5 5 5 5m8-10 5 5-5 5m-3-13-2 20",
  plus: "M5 12h14M12 5v14",
  minus: "M5 12h14",
  crosshair:
    "M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 1v5m0 12v5M1 12h5m12 0h5m-6-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
  check: "m5 12 4 4L19 6",
  lightbulb: "M8 16c0-2-3-3-3-7a7 7 0 1 1 14 0c0 4-3 5-3 7H8Zm0 3h8m-7 3h6",
  download: "M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4",
  lock: "M6 10V7a6 6 0 0 1 12 0v3M4 10h16v11H4V10Zm8 5v2",
};
</script>
<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    class="ui-icon"
  >
    <path :d="paths[name] || paths.document" />
  </svg>
</template>
<style scoped>
.ui-icon {
  display: inline-block;
  flex-shrink: 0;
  vertical-align: middle;
}
</style>
