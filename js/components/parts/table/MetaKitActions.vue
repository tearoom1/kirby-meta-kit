<template>
  <div class="k-meta-kit-actions">
    <k-button-group>
      <k-button
        icon="edit"
        :title="variant === 'v2' && !hasSelection ? scopeHint : null"
        :disabled="selectedCount === 0"
        @click="$emit('edit-selected')"
      >
        {{ variant === 'v2' ? $t('meta-kit.v2.edit') : $t('meta-kit.actions.edit') }}<span v-if="selectedCount > 0"> ({{ selectedCount }})</span>
      </k-button>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        class="k-meta-kit-button-ai-generate"
        :title="variant === 'v2' && !hasSelection ? scopeHint : null"
        :disabled="isGenerating || selectedCount === 0"
        :progress="isGenerating"
        @click="$emit('generate-missing')"
      >
        {{ $t('meta-kit.actions.generate') }}<span v-if="selectedCount > 0"> ({{ selectedCount }})</span>
      </k-button>
      <k-button icon="refresh" :title="$t('meta-kit.actions.refresh')" @click="$emit('refresh')"></k-button>
    </k-button-group>

    <slot name="filters"></slot>
  </div>
</template>

<script>
export default {
  props: {
    selectedCount: {
      type: Number,
      default: 0
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    reviewEnabled: {
      type: Boolean,
      default: false
    },
    isGenerating: {
      type: Boolean,
      default: false
    },
    // Temporary design comparison: v2 acts on the filtered pages without a selection
    variant: {
      type: String,
      default: 'v1'
    },
    hasSelection: {
      type: Boolean,
      default: false
    },
    isFiltered: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    scopeHint() {
      return this.$t(this.isFiltered ? 'meta-kit.v2.scope.filtered' : 'meta-kit.v2.scope.all');
    }
  }
};
</script>
