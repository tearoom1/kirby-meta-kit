<template>
  <div class="k-meta-kit-actions">
    <k-button-group>
      <k-button
        icon="edit"
        :disabled="selectedCount === 0"
        @click="$emit('edit-selected')"
      >
        Edit Selected<span v-if="selectedCount > 0"> ({{ selectedCount }})</span>
      </k-button>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        :disabled="isGenerating || selectedCount === 0"
        :progress="isGenerating"
        @click="$emit('generate-missing')"
      >
        Generate Missing<span v-if="selectedCount > 0"> ({{ selectedCount }})</span>
      </k-button>
      <k-button
        v-if="aiEnabled && selectedCount > 0"
        icon="sparkling"
        @click="$emit('review-selected')"
      >
        Review Selected
      </k-button>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        @click="$emit('review-site')"
      >
        Review Site
      </k-button>
      <k-button icon="refresh" @click="$emit('refresh')"></k-button>
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
    isGenerating: {
      type: Boolean,
      default: false
    }
  }
};
</script>
