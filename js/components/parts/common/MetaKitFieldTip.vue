<template>
  <div class="k-mk2-tip-body">
    <p v-if="text" class="k-mk2-tip-text" :class="{ 'is-inherited': !!source }">{{ text }}</p>
    <p v-else class="k-mk2-tip-text is-empty">{{ $t('meta-kit.v2.tip.empty') }}</p>

    <meta-kit-length-meter v-if="text" :length="length" :ranges="ranges" />

    <p v-if="source" class="k-mk2-tip-line">
      <span class="k-mk2-pill">{{ source.label }}</span>
      {{ source.help }}
    </p>
    <p v-for="note in notes" :key="note" class="k-mk2-tip-line is-note">{{ note }}</p>
  </div>
</template>

<script>
import MetaKitLengthMeter from './MetaKitLengthMeter.vue';

// Rich tooltip for one SEO field: value, length meter, origin, notes
export default {
  components: { MetaKitLengthMeter },
  props: {
    text: { type: String, default: '' },
    length: { type: Number, default: 0 },
    ranges: { type: Object, default: null },
    // { label, help } when the value is inherited
    source: { type: Object, default: null },
    notes: { type: Array, default: () => [] }
  }
};
</script>
