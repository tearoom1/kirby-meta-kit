<template>
  <div class="k-mk2-selection" role="region" :aria-label="$t('meta-kit.v2.selected', { count })">
    <span class="k-mk2-selection-count">{{ $t('meta-kit.v2.selected', { count }) }}</span>
    <button type="button" class="k-mk2-selection-button" @click="$emit('edit')">✎ {{ $t('meta-kit.v2.edit') }}</button>
    <button v-if="aiEnabled" type="button" class="k-mk2-selection-button is-primary" @click="$emit('generate')">✦ {{ $t('meta-kit.actions.generate') }}</button>
    <button type="button" class="k-mk2-selection-button is-quiet" @click="$emit('clear')">{{ $t('meta-kit.v2.clearSelection') }}</button>
  </div>
</template>

<script>
export default {
  props: {
    count: { type: Number, required: true },
    aiEnabled: { type: Boolean, default: true }
  }
};
</script>
