<template>
  <div class="k-meta-kit-stats">

    <div class="k-meta-kit-stats-card">
      <div class="k-meta-kit-stats-label">Total Pages</div>
      <div class="k-meta-kit-stats-row">
        <span class="k-meta-kit-stats-value">
          {{ filteredCount }}<span v-if="searchActive" class="k-meta-kit-stats-sub"> / {{ totalCount }}</span>
        </span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div class="k-meta-kit-stats-bar-fill k-meta-kit-stats-neutral" style="width: 100%"></div>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <div class="k-meta-kit-stats-label">Meta Title</div>
      <div class="k-meta-kit-stats-row">
        <span class="k-meta-kit-stats-value">
          {{ filteredCustomTitle }}<span v-if="searchActive" class="k-meta-kit-stats-sub"> / {{ totalCustomTitle }}</span>
        </span>
        <span v-if="denominator > 0" class="k-meta-kit-stats-pct" :class="getPercentClass(filteredCustomTitle, denominator)">
          {{ percent(filteredCustomTitle, denominator) }}%
        </span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-green"
          :style="{ width: percent(filteredCustomTitle, denominator) + '%' }"
        ></div>
        <div
          v-if="filteredPageFallback > 0"
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-amber"
          :style="{ width: percent(filteredPageFallback, denominator) + '%' }"
        ></div>
      </div>
      <div v-if="filteredPageFallback > 0" class="k-meta-kit-stats-hint">
        <span class="k-meta-kit-stats-amber">{{ filteredPageFallback }} from page title</span>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <div class="k-meta-kit-stats-label">Meta Description</div>
      <div class="k-meta-kit-stats-row">
        <span class="k-meta-kit-stats-value">
          {{ filteredWithDescription }}<span v-if="searchActive" class="k-meta-kit-stats-sub"> / {{ totalWithDescription }}</span>
        </span>
        <span v-if="denominator > 0" class="k-meta-kit-stats-pct" :class="getPercentClass(filteredWithDescription, denominator)">
          {{ percent(filteredWithDescription, denominator) }}%
        </span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-green"
          :style="{ width: percent(filteredWithDescription, denominator) + '%' }"
        ></div>
        <div
          v-if="filteredDescriptionFromSite > 0"
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-amber"
          :style="{ width: percent(filteredDescriptionFromSite, denominator) + '%' }"
        ></div>
        <div
          v-if="filteredMissingDescription > 0"
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-red"
          :style="{ width: percent(filteredMissingDescription, denominator) + '%' }"
        ></div>
      </div>
      <div v-if="filteredDescriptionFromSite > 0 || filteredMissingDescription > 0" class="k-meta-kit-stats-hint">
        <span v-if="filteredDescriptionFromSite > 0" class="k-meta-kit-stats-amber">{{ filteredDescriptionFromSite }} from site</span>
        <span v-if="filteredDescriptionFromSite > 0 && filteredMissingDescription > 0"> · </span>
        <span v-if="filteredMissingDescription > 0" class="k-meta-kit-stats-red">{{ filteredMissingDescription }} missing</span>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <div class="k-meta-kit-stats-label">OG Image</div>
      <div class="k-meta-kit-stats-row">
        <span class="k-meta-kit-stats-value">
          {{ filteredWithImage }}<span v-if="searchActive" class="k-meta-kit-stats-sub"> / {{ totalWithImage }}</span>
        </span>
        <span v-if="denominator > 0" class="k-meta-kit-stats-pct" :class="getPercentClass(filteredWithImage, denominator)">
          {{ percent(filteredWithImage, denominator) }}%
        </span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-green"
          :style="{ width: percent(filteredWithImage, denominator) + '%' }"
        ></div>
        <div
          v-if="filteredImageFromSite > 0"
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-amber"
          :style="{ width: percent(filteredImageFromSite, denominator) + '%' }"
        ></div>
        <div
          v-if="filteredMissingImage > 0"
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-red"
          :style="{ width: percent(filteredMissingImage, denominator) + '%' }"
        ></div>
      </div>
      <div v-if="filteredImageFromSite > 0 || filteredMissingImage > 0" class="k-meta-kit-stats-hint">
        <span v-if="filteredImageFromSite > 0" class="k-meta-kit-stats-amber">{{ filteredImageFromSite }} from site</span>
        <span v-if="filteredImageFromSite > 0 && filteredMissingImage > 0"> · </span>
        <span v-if="filteredMissingImage > 0" class="k-meta-kit-stats-red">{{ filteredMissingImage }} missing</span>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <div class="k-meta-kit-stats-label">Noindex Pages</div>
      <div class="k-meta-kit-stats-row">
        <span class="k-meta-kit-stats-value">
          {{ filteredNoIndex }}<span v-if="searchActive" class="k-meta-kit-stats-sub"> / {{ totalNoIndex }}</span>
        </span>
        <span v-if="denominator > 0 && filteredNoIndex > 0" class="k-meta-kit-stats-pct k-meta-kit-stats-amber">
          {{ percent(filteredNoIndex, denominator) }}%
        </span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-amber"
          :style="{ width: percent(filteredNoIndex, denominator) + '%' }"
        ></div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  props: {
    filteredCount: { type: Number, required: true },
    totalCount: { type: Number, required: true },
    filteredCustomTitle: { type: Number, required: true },
    totalCustomTitle: { type: Number, required: true },
    filteredPageFallback: { type: Number, required: true },
    totalPageFallback: { type: Number, required: true },
    filteredWithDescription: { type: Number, required: true },
    totalWithDescription: { type: Number, required: true },
    filteredDescriptionFromSite: { type: Number, required: true },
    totalDescriptionFromSite: { type: Number, required: true },
    filteredMissingDescription: { type: Number, required: true },
    totalMissingDescription: { type: Number, required: true },
    filteredWithImage: { type: Number, required: true },
    totalWithImage: { type: Number, required: true },
    filteredImageFromSite: { type: Number, required: true },
    totalImageFromSite: { type: Number, required: true },
    filteredMissingImage: { type: Number, required: true },
    totalMissingImage: { type: Number, required: true },
    filteredNoIndex: { type: Number, required: true },
    totalNoIndex: { type: Number, required: true },
    searchActive: { type: Boolean, default: false }
  },
  computed: {
    denominator() {
      return this.searchActive ? this.filteredCount : this.totalCount;
    }
  },
  methods: {
    percent(count, total) {
      if (!total) return 0;
      return Math.round((count / total) * 100);
    },
    getPercentClass(count, total) {
      const p = this.percent(count, total);
      if (p >= 80) return 'k-meta-kit-stats-green';
      if (p >= 50) return 'k-meta-kit-stats-amber';
      return 'k-meta-kit-stats-red';
    }
  }
};
</script>
