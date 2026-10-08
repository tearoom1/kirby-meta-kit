<template>
  <section class="k-mk2-overview">
    <div class="k-mk2-tiles">
      <button
        v-for="card in attentionCards"
        :key="card.key"
        type="button"
        class="k-mk2-tile"
        :class="{ 'is-active': activeArea === card.key }"
        :aria-pressed="activeArea === card.key ? 'true' : 'false'"
        @click="$emit('select-area', card.key)"
      >
        <span class="k-mk2-tile-head">
          <span>{{ card.label }}</span>
          <span v-if="activeArea === card.key" class="k-mk2-tile-badge">{{ $t('meta-kit.v2.filtered') }}</span>
        </span>
        <span class="k-mk2-tile-value">
          <strong>{{ card.totalAttention }}</strong>
          <span>{{ $t('meta-kit.v2.open') }}</span>
        </span>
        <span class="k-mk2-bar" aria-hidden="true">
          <span class="k-mk2-bar-fix" :style="{ width: percent(card.totalFix) }"></span>
          <span class="k-mk2-bar-review" :style="{ width: percent(card.totalReview) }"></span>
        </span>
        <span class="k-mk2-tile-legend">
          <span v-if="card.totalFix"><i class="k-mk2-dot is-error"></i>{{ $t('meta-kit.v2.fix', { count: card.totalFix }) }}</span>
          <span v-if="card.totalReview"><i class="k-mk2-dot is-warning"></i>{{ $t('meta-kit.v2.review', { count: card.totalReview }) }}</span>
        </span>
      </button>

      <div v-if="goodCards.length" class="k-mk2-ok">
        <strong>✓ {{ $t('meta-kit.v2.ok') }}</strong>
        <span v-for="card in goodCards" :key="card.key">{{ card.label }} · 0</span>
      </div>
    </div>

    <div v-if="activeCard" class="k-mk2-filter-row">
      <button type="button" class="k-mk2-chip" @click="$emit('select-area', null)">
        {{ activeCard.label }} · {{ $t('meta-kit.v2.needsAttention') }}
        <span class="k-mk2-chip-x" :aria-label="$t('meta-kit.filter.clear')">✕</span>
      </button>
      <span class="k-mk2-muted">{{ $t('meta-kit.v2.ofPagesActions', { count: filteredCount, total: totalCount }) }}</span>
    </div>
  </section>
</template>

<script>
export default {
  props: {
    cards: { type: Array, required: true },
    totalCount: { type: Number, required: true },
    filteredCount: { type: Number, default: 0 },
    activeArea: { type: String, default: null }
  },
  computed: {
    attentionCards() {
      return this.cards.filter((card) => card.totalAttention > 0);
    },
    goodCards() {
      return this.cards.filter((card) => card.totalAttention === 0);
    },
    activeCard() {
      return this.cards.find((card) => card.key === this.activeArea) || null;
    }
  },
  methods: {
    percent(count) {
      return this.totalCount ? `${(count / this.totalCount) * 100}%` : '0%';
    }
  }
};
</script>
