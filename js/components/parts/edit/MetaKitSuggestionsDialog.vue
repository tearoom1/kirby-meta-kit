<template>
  <k-dialog ref="dialog" class="k-meta-kit-dialog k-meta-kit-suggestions-dialog" size="large">
    <k-headline>{{ $t('meta-kit.suggestions.title') }}</k-headline>
    <k-text>
      {{ $t('meta-kit.suggestions.intro') }}
      <template v-if="cancelled"><br>{{ $t('meta-kit.suggestions.cancelled') }}</template>
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
          <span class="k-meta-kit-suggestion-count">{{ $t('meta-kit.chars', { count: (suggestion.value || '').length }) }}</span>
        </label>
        <textarea
          v-model="suggestion.value"
          class="k-meta-kit-suggestion-text"
          :rows="suggestion.field.endsWith('Title') ? 1 : 3"
          :disabled="!suggestion.selected"
          :aria-label="$t('meta-kit.suggestions.field', { field: suggestion.label, page: suggestion.pageTitle })"
        ></textarea>
      </li>
    </ul>

    <k-box v-if="errors.length" theme="negative" class="k-meta-kit-suggestions-errors">
      <strong>{{ $t('meta-kit.suggestions.errors', { count: errors.length }) }}</strong>
      <ul>
        <li v-for="error in errors" :key="error.pageId + ':' + error.field">
          {{ error.pageTitle }} – {{ error.label }}: {{ error.message }}
        </li>
      </ul>
    </k-box>

    <template #footer>
      <k-button-group class="k-meta-kit-bulk-buttons">
        <k-button @click="close()">{{ $t('meta-kit.suggestions.discard') }}</k-button>
        <k-button
          icon="check"
          theme="positive"
          :disabled="selectedCount === 0"
          @click="save"
        >
          {{ $t('meta-kit.suggestions.save', { count: selectedCount }) }}
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
