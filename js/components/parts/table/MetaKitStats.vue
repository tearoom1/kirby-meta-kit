<template>
  <div class="k-meta-kit-stats">
    <div class="k-meta-kit-stats-card k-meta-kit-stats-card-total">
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

    <Tooltip
      v-for="card in cards"
      :key="card.key"
      :content="card.tooltip"
      class="k-meta-kit-stats-tooltip"
    >
      <div class="k-meta-kit-stats-card k-meta-kit-stats-card-actionable">
        <div class="k-meta-kit-stats-label">{{ card.label }}</div>
        <div class="k-meta-kit-stats-row">
          <span class="k-meta-kit-stats-value" :class="card.attentionClass">
            {{ card.filteredAttention }}<span v-if="searchActive" class="k-meta-kit-stats-sub"> / {{ card.totalAttention }}</span>
          </span>
        </div>
        <div class="k-meta-kit-stats-bar-track">
          <div
            class="k-meta-kit-stats-bar-fill k-meta-kit-stats-fill-green"
            :style="{ width: percent(card.filteredGood, denominator) + '%' }"
          ></div>
          <div
            v-if="card.filteredReview > 0"
            class="k-meta-kit-stats-bar-fill k-meta-kit-stats-fill-amber"
            :style="{ width: percent(card.filteredReview, denominator) + '%' }"
          ></div>
          <div
            v-if="card.filteredFix > 0"
            class="k-meta-kit-stats-bar-fill k-meta-kit-stats-fill-red"
            :style="{ width: percent(card.filteredFix, denominator) + '%' }"
          ></div>
        </div>
        <div class="k-meta-kit-stats-hint">
          <span v-if="card.filteredGood > 0" class="k-meta-kit-stats-green">{{ card.filteredGood }} good</span>
          <span v-if="card.filteredGood > 0 && (card.filteredReview > 0 || card.filteredFix > 0)"> · </span>
          <span v-if="card.filteredReview > 0" class="k-meta-kit-stats-amber">{{ card.filteredReview }} review</span>
          <span v-if="card.filteredReview > 0 && card.filteredFix > 0"> · </span>
          <span v-if="card.filteredFix > 0" class="k-meta-kit-stats-red">{{ card.filteredFix }} fix</span>
        </div>
      </div>
    </Tooltip>
  </div>
</template>

<script>
import Tooltip from '../common/Tooltip.vue';

export default {
  components: {
    Tooltip
  },
  props: {
    filteredCount: { type: Number, required: true },
    totalCount: { type: Number, required: true },
    cards: { type: Array, required: true },
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
    }
  }
};
</script>
