<template>
  <div class="k-meta-kit-stats">

    <div class="k-meta-kit-stats-card">
      <h3>Total Pages</h3>
      <div class="k-meta-kit-stats-number">
        {{ filteredCount }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalCount }}</span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div class="k-meta-kit-stats-bar-fill k-meta-kit-stats-percent-neutral" style="width: 100%"></div>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <h3>With Title</h3>
      <div class="k-meta-kit-stats-number">
        {{ filteredWithTitle }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalWithTitle }}</span>
        <span v-if="denominator > 0" class="k-meta-kit-stats-pct">{{ percent(filteredWithTitle, denominator) }}%</span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill"
          :class="getPercentClass(filteredWithTitle, denominator)"
          :style="{ width: percent(filteredWithTitle, denominator) + '%' }"
        ></div>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <h3>With Description</h3>
      <div class="k-meta-kit-stats-number">
        {{ filteredWithDescription }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalWithDescription }}</span>
        <span v-if="denominator > 0" class="k-meta-kit-stats-pct">{{ percent(filteredWithDescription, denominator) }}%</span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill"
          :class="getPercentClass(filteredWithDescription, denominator)"
          :style="{ width: percent(filteredWithDescription, denominator) + '%' }"
        ></div>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <h3>With OG Image</h3>
      <div class="k-meta-kit-stats-number">
        {{ filteredWithImage }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalWithImage }}</span>
        <span v-if="denominator > 0" class="k-meta-kit-stats-pct">{{ percent(filteredWithImage, denominator) }}%</span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill"
          :class="getPercentClass(filteredWithImage, denominator)"
          :style="{ width: percent(filteredWithImage, denominator) + '%' }"
        ></div>
      </div>
    </div>

    <div class="k-meta-kit-stats-card">
      <h3>Noindex</h3>
      <div class="k-meta-kit-stats-number">
        {{ filteredNoIndex }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalNoIndex }}</span>
        <span v-if="denominator > 0 && filteredNoIndex > 0" class="k-meta-kit-stats-pct">{{ percent(filteredNoIndex, denominator) }}%</span>
      </div>
      <div class="k-meta-kit-stats-bar-track">
        <div
          class="k-meta-kit-stats-bar-fill k-meta-kit-stats-percent-noindex"
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
    filteredWithTitle: { type: Number, required: true },
    totalWithTitle: { type: Number, required: true },
    filteredWithDescription: { type: Number, required: true },
    totalWithDescription: { type: Number, required: true },
    filteredWithImage: { type: Number, required: true },
    totalWithImage: { type: Number, required: true },
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
      if (p >= 80) return 'k-meta-kit-stats-percent-high';
      if (p >= 50) return 'k-meta-kit-stats-percent-mid';
      return 'k-meta-kit-stats-percent-low';
    }
  }
};
</script>

<style>
.k-meta-kit-stats-bar-track {
  margin-top: 0.5rem;
  height: 4px;
  border-radius: 2px;
  background: var(--color-gray-200, #e8e8e8);
  overflow: hidden;
}

.k-meta-kit-stats-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.4s ease;
}

.k-meta-kit-stats-percent-high   { background: var(--color-green-400, #52b788); }
.k-meta-kit-stats-percent-mid    { background: var(--color-orange-400, #f4a261); }
.k-meta-kit-stats-percent-low    { background: var(--color-red-400, #e63946); }
.k-meta-kit-stats-percent-neutral { background: var(--color-gray-400, #aaa); }
.k-meta-kit-stats-percent-noindex { background: var(--color-orange-400, #f4a261); }

.k-meta-kit-stats-pct {
  margin-left: 0.4rem;
  font-size: 0.75rem;
  opacity: 0.6;
  font-weight: normal;
}
</style>
