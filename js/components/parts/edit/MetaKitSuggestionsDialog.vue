<template>
  <k-dialog ref="dialog" class="k-meta-kit-dialog k-meta-kit-suggestions-dialog" size="large">
    <k-headline>Review Generated Metadata</k-headline>
    <k-text>
      Nothing is saved yet. Edit or deselect suggestions, then save the ones you want to keep.
      <template v-if="cancelled"><br>Generation was cancelled; only the finished suggestions are listed.</template>
    </k-text>

    <ul v-if="suggestions.length" class="k-meta-kit-suggestions">
      <li
        v-for="(suggestion, index) in suggestions"
        :key="suggestion.pageId + ':' + suggestion.field"
        class="k-meta-kit-suggestion"
        :class="{ 'is-deselected': !suggestion.selected }"
      >
        <label class="k-meta-kit-suggestion-header">
          <input v-model="suggestion.selected" type="checkbox" />
          <strong>{{ suggestion.pageTitle }}</strong>
          <span class="k-meta-kit-suggestion-field">{{ suggestion.label }}</span>
          <span class="k-meta-kit-suggestion-count">{{ (suggestion.value || '').length }} chars</span>
        </label>
        <textarea
          v-model="suggestion.value"
          class="k-meta-kit-suggestion-text"
          :rows="suggestion.field.endsWith('Title') ? 1 : 3"
          :disabled="!suggestion.selected"
          :aria-label="`${suggestion.label} for ${suggestion.pageTitle}`"
        ></textarea>
      </li>
    </ul>

    <k-box v-if="errors.length" theme="negative" class="k-meta-kit-suggestions-errors">
      <strong>{{ errors.length }} field(s) could not be generated:</strong>
      <ul>
        <li v-for="error in errors" :key="error.pageId + ':' + error.field">
          {{ error.pageTitle }} – {{ error.label }}: {{ error.message }}
        </li>
      </ul>
    </k-box>

    <template #footer>
      <k-button-group class="k-meta-kit-bulk-buttons">
        <k-button @click="close()">Discard</k-button>
        <k-button
          icon="check"
          theme="positive"
          :disabled="selectedCount === 0"
          @click="save"
        >
          Save selected ({{ selectedCount }})
        </k-button>
      </k-button-group>
    </template>
  </k-dialog>
</template>

<script>
export default {
  data() {
    return {
      suggestions: [],
      errors: [],
      cancelled: false
    };
  },
  computed: {
    selectedCount() {
      return this.suggestions.filter((suggestion) => suggestion.selected && suggestion.value?.trim()).length;
    }
  },
  methods: {
    open({ suggestions = [], errors = [], cancelled = false } = {}) {
      this.suggestions = suggestions.map((suggestion) => ({ ...suggestion }));
      this.errors = errors;
      this.cancelled = cancelled;
      this.$refs.dialog.open();
    },
    close() {
      this.$refs.dialog.close();
    },
    save() {
      this.$emit('save', this.suggestions);
      this.close();
    }
  }
};
</script>
