<template>
  <k-dialog ref="dialog" class="k-mk2-review" size="huge">
    <header class="k-mk2-review-head">
      <div>
        <h2>{{ $t('meta-kit.v2.review.title') }}</h2>
        <p>{{ $t('meta-kit.v2.review.intro') }}</p>
        <p v-if="cancelled" class="k-mk2-muted">{{ $t('meta-kit.suggestions.cancelled') }}</p>
      </div>
      <span class="k-mk2-muted">{{ $t('meta-kit.v2.review.selected', { count: selectedCount, total: suggestions.length }) }}</span>
      <button type="button" class="k-mk2-small-button" @click="selectAll(true)">{{ $t('meta-kit.v2.all') }}</button>
      <button type="button" class="k-mk2-small-button" @click="selectAll(false)">{{ $t('meta-kit.v2.none') }}</button>
    </header>

    <div class="k-mk2-review-body">
      <section v-for="group in groups" :key="group.pageId" class="k-mk2-review-page">
        <h3>{{ group.title }} <span class="k-mk2-muted">{{ group.pageId === 'site' ? '/' : group.pageId }}</span></h3>

        <div
          v-for="item in group.items"
          :key="item.suggestion.field"
          class="k-mk2-review-row"
          :class="{ 'is-deselected': !item.suggestion.selected }"
        >
          <input
            v-model="item.suggestion.selected"
            type="checkbox"
            :aria-label="$t('meta-kit.v2.review.take', { field: item.suggestion.label, page: group.title })"
          />
          <div class="k-mk2-review-label">
            <strong>{{ item.suggestion.label }}</strong>
            <span class="k-mk2-muted">{{ item.previous }}</span>
          </div>
          <div class="k-mk2-review-edit">
            <textarea
              v-model="item.suggestion.value"
              :rows="item.isTitle ? 1 : 3"
              :disabled="!item.suggestion.selected"
              :aria-label="$t('meta-kit.suggestions.field', { field: item.suggestion.label, page: group.title })"
            ></textarea>
            <meta-kit-length-meter v-bind="measure(item)" />
          </div>
        </div>
      </section>

      <p v-if="errors.length" class="k-mk2-skipped">
        <strong>{{ $t('meta-kit.v2.review.skipped', { count: errors.length }) }}</strong>
        <span v-for="error in errors" :key="error.pageId + ':' + error.field">
          {{ error.pageTitle }} – {{ error.label }}: {{ error.message }}
        </span>
      </p>
    </div>

    <template #footer>
      <footer class="k-mk2-review-foot">
        <span v-if="language" class="k-mk2-muted">{{ $t('meta-kit.v2.review.language', { language: language.toUpperCase() }) }}</span>
        <span class="k-mk2-legend-spacer"></span>
        <k-button variant="filled" @click="close()">{{ $t('meta-kit.suggestions.discard') }}</k-button>
        <k-button variant="filled" theme="dark" :disabled="selectedCount === 0" @click="save">
          {{ $t('meta-kit.v2.review.save', { count: selectedCount }) }}
        </k-button>
      </footer>
    </template>
  </k-dialog>
</template>

<script>
import MetaKitLengthMeter from './MetaKitLengthMeter.vue';
import { getRangesForPageAndType } from '../../../composables/useValidation.js';
import { getEffectiveDescription, getInheritanceSource } from '../../../composables/useInheritance.js';
import { buildTitleWithSiteName, getTableTitleDisplay } from '../../../composables/panelDisplay.js';

const RANGE_TYPES = {
  metaTitle: 'title',
  metaDescription: 'description',
  ogTitle: 'ogTitle',
  ogDescription: 'ogDescription'
};

const SOURCE_KEYS = {
  'site': 'meta-kit.v2.source.site.help',
  'page title': 'meta-kit.v2.source.title.help',
  'meta title': 'meta-kit.field.metaTitle',
  'meta description': 'meta-kit.field.metaDescription'
};

export default {
  components: { MetaKitLengthMeter },
  props: {
    pages: { type: Array, default: () => [] },
    siteSettings: { type: Object, default: () => ({}) },
    validationSettings: { type: Object, default: () => ({}) },
    language: { type: String, default: null }
  },
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
    },
    groups() {
      const groups = [];
      for (const suggestion of this.suggestions) {
        let group = groups.find((entry) => entry.pageId === suggestion.pageId);
        if (!group) {
          group = { pageId: suggestion.pageId, title: suggestion.pageTitle, items: [] };
          groups.push(group);
        }
        const page = this.pages.find((entry) => entry.id === suggestion.pageId) || { id: suggestion.pageId };
        group.items.push({
          suggestion,
          page,
          isTitle: suggestion.field.endsWith('Title'),
          previous: this.previous(page, suggestion.field)
        });
      }
      return groups;
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
    },
    selectAll(selected) {
      this.suggestions.forEach((suggestion) => { suggestion.selected = selected; });
    },
    // "Bisher: <source> · <length>" — what the page shows today
    previous(page, field) {
      const og = field.startsWith('og');
      const length = field.endsWith('Title')
        ? getTableTitleDisplay(page, this.siteSettings, og ? 'og' : 'meta').charCount
        : (getEffectiveDescription(page, og ? 'og' : 'meta', this.siteSettings) || '').length;
      if (!length) {
        return this.$t('meta-kit.v2.review.previousNone');
      }

      const source = getInheritanceSource(page, field, this.siteSettings);
      const label = !source
        ? this.$t('meta-kit.v2.review.own')
        : (SOURCE_KEYS[source] ? this.$t(SOURCE_KEYS[source]) : String(source).toUpperCase());

      return this.$t('meta-kit.v2.review.previous', { source: label, length });
    },
    // Length and ranges for the meter; titles are judged with the
    // appended site name, like in the table
    measure({ suggestion, page, isTitle }) {
      const text = isTitle
        ? buildTitleWithSiteName(suggestion.value || '', this.siteSettings, suggestion.field === 'ogTitle' ? 'og' : 'meta')
        : (suggestion.value || '');
      return {
        length: text.length,
        ranges: getRangesForPageAndType(page, RANGE_TYPES[suggestion.field], this.validationSettings)
      };
    }
  }
};
</script>
