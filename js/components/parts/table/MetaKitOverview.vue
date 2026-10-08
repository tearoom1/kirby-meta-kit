<template>
  <section class="k-mk2-overview">
    <div class="k-mk2-tiles">
      <button
        v-for="card in attentionCards"
        :key="card.key"
        type="button"
        class="k-mk2-tile"
        :class="{ 'is-active': activeArea === card.key, 'is-info': isInfo(card) }"
        :aria-pressed="activeArea === card.key ? 'true' : 'false'"
        @click="$emit('select-area', card.key)"
      >
        <span class="k-mk2-tile-head">
          <span>{{ card.label }}</span>
          <span v-if="activeArea === card.key" class="k-mk2-tile-badge">{{ $t('meta-kit.v2.filtered') }}</span>
        </span>
        <span class="k-mk2-tile-value">
          <strong>{{ card.totalAttention }}</strong>
          <span>{{ valueLabel(card) }}</span>
        </span>
        <template v-if="!isInfo(card)">
          <span class="k-mk2-bar" aria-hidden="true">
            <span class="k-mk2-bar-fix" :style="{ width: percent(card.totalFix) }"></span>
            <span class="k-mk2-bar-review" :style="{ width: percent(card.totalReview) }"></span>
          </span>
          <span v-if="card.totalFix && card.totalReview" class="k-mk2-tile-legend">
            <span><i class="k-mk2-dot is-error"></i>{{ $t('meta-kit.v2.fix', { count: card.totalFix }) }}</span>
            <span><i class="k-mk2-dot is-warning"></i>{{ $t('meta-kit.v2.review', { count: card.totalReview }) }}</span>
          </span>
        </template>
      </button>

      <div v-if="goodCards.length" class="k-mk2-ok">
        <strong>✓ {{ $t('meta-kit.v2.ok') }}</strong>
        <span v-for="card in goodCards" :key="card.key">{{ card.label }} · 0</span>
      </div>
    </div>

    <div v-if="activeCard" class="k-mk2-filter-row">
      <button type="button" class="k-mk2-chip" @click="$emit('select-area', null)">
        {{ activeCard.label }}<template v-if="!isInfo(activeCard)"> · {{ $t('meta-kit.v2.needsAttention') }}</template>
        <span class="k-mk2-chip-x" :aria-label="$t('meta-kit.filter.clear')">✕</span>
      </button>
      <span class="k-mk2-muted">{{ $t('meta-kit.v2.ofPagesActions', { count: filteredCount, total: totalCount }) }}</span>
    </div>
  </section>
</template>

<script>
// Inventory, not a problem: shown as a plain count without bar or "open"
const INFO_CARDS = ['noindex'];

export default {
  props: {
    cards: { type: Array, required: true },
    totalCount: { type: Number, required: true },
    filteredCount: { type: Number, default: 0 },
    activeArea: { type: String, default: null }
  },
  computed: {
    attentionCards() {
      return this.cards.filter((card) => card.totalAttention > 0 || this.isInfo(card));
    },
    goodCards() {
      return this.cards.filter((card) => card.totalAttention === 0 && !this.isInfo(card));
    },
    activeCard() {
      return this.cards.find((card) => card.key === this.activeArea) || null;
    }
  },
  methods: {
    isInfo(card) {
      return INFO_CARDS.includes(card.key);
    },
    // "40 open" when both levels occur, otherwise "71 to review" / "7 to fix"
    valueLabel(card) {
      if (this.isInfo(card)) return this.$t(`meta-kit.v2.info.${card.key}`);
      if (card.totalFix && card.totalReview) return this.$t('meta-kit.v2.open');
      return this.$t(card.totalFix ? 'meta-kit.v2.toFix' : 'meta-kit.v2.toReview');
    },
    percent(count) {
      return this.totalCount ? `${(count / this.totalCount) * 100}%` : '0%';
    }
  }
};
</script>
