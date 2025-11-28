<template>
  <div :class="fieldClass">
    <k-input
      :value="value"
      @input="$emit('input', $event)"
      :placeholder="placeholder"
      type="textarea"
      :rows="rows"
      :buttons="buttons"
    />
    <div class="k-meta-kit-dialog-field-meta">
      <span>
        <span v-if="value"
              class="k-meta-kit-field-length"
              :class="statusClass">
          {{ value.length }} chars
        </span>
      </span>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        :size="buttonSize"
        :disabled="isGenerating"
        @click="$emit('generate')"
        :title="buttonSize === 'xs' ? 'AI Generate' : undefined"
      >
        <template v-if="buttonSize !== 'xs'">AI Generate</template>
      </k-button>
    </div>
    <div v-if="isGenerating" class="k-meta-kit-dialog-generating">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Generating...</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: String,
    placeholder: {
      type: String,
      default: 'No OG description'
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    isGenerating: {
      type: Boolean,
      default: false
    },
    buttonSize: {
      type: String,
      default: 'xs'
    },
    rows: {
      type: Number,
      default: 3
    },
    buttons: {
      type: [Boolean, String],
      default: true
    },
    fieldClass: {
      type: String,
      default: 'k-meta-kit-dialog-table-field-desc'
    }
  },
  computed: {
    statusClass() {
      if (!this.value) return '';

      const length = this.value.length;
      // OG descriptions can be up to 185 characters
      const optimal = { min: 150, max: 185 };
      const warning = { min: 135, max: 200 };

      if (length >= optimal.min && length <= optimal.max) {
        return 'k-meta-kit-status-success';
      }

      if (length >= warning.min && length <= warning.max) {
        return 'k-meta-kit-status-warning';
      }

      return 'k-meta-kit-status-error';
    }
  }
};
</script>
