<template>
  <div v-if="meter" class="k-mk2-meter-row">
    <span class="k-mk2-meter" aria-hidden="true">
      <span
        v-for="(zone, index) in meter.zones"
        :key="index"
        :class="['k-mk2-zone', 'is-' + zone.level]"
        :style="{ width: zone.width + '%' }"
      ></span>
      <span :class="['k-mk2-marker', 'is-' + meter.level]" :style="{ left: meter.position + '%' }"></span>
    </span>
    <span>
      <strong :class="'is-' + meter.level">{{ $t('meta-kit.chars', { count: length }) }}</strong>
      · {{ meter.verdict }} · {{ $t('meta-kit.v2.review.optimal', { range: meter.optimal }) }}
    </span>
  </div>
</template>

<script>
// Length bar: zones from the validation ranges, marker at the current length
export default {
  props: {
    length: { type: Number, default: 0 },
    // { optimal: { min, max }, warning: { min, max } }
    ranges: { type: Object, default: null }
  },
  computed: {
    meter() {
      if (!this.ranges || !this.length) return null;

      const { optimal, warning } = this.ranges;
      const scale = Math.max(warning.max * 1.2, this.length);
      const pct = (value) => (value / scale) * 100;

      let level = 'good';
      let verdict = 'meta-kit.v2.review.verdict.good';
      if (this.length < warning.min) { level = 'error'; verdict = 'meta-kit.v2.review.verdict.tooShort'; }
      else if (this.length < optimal.min) { level = 'warning'; verdict = 'meta-kit.v2.review.verdict.short'; }
      else if (this.length > warning.max) { level = 'error'; verdict = 'meta-kit.v2.review.verdict.tooLong'; }
      else if (this.length > optimal.max) { level = 'warning'; verdict = 'meta-kit.v2.review.verdict.long'; }

      return {
        level,
        verdict: this.$t(verdict),
        optimal: `${optimal.min}–${optimal.max}`,
        position: Math.min(pct(this.length), 99),
        zones: [
          { level: 'error', width: pct(warning.min) },
          { level: 'warning', width: pct(optimal.min - warning.min) },
          { level: 'good', width: pct(optimal.max - optimal.min) },
          { level: 'warning', width: pct(warning.max - optimal.max) },
          { level: 'error', width: 100 - pct(warning.max) }
        ]
      };
    }
  }
};
</script>
