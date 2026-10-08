<template>
  <k-panel-inside class="k-meta-kit-view k-mk2">
    <!-- Top Bar: Language Switcher + Sponsor (right-aligned) -->
    <div class="k-meta-kit-topbar">
      <div
        v-if="languages && languages.length > 1"
        class="k-button-group k-language-selector k-meta-kit-language-bar"
        data-layout="collapsed"
        :aria-label="$t('meta-kit.languages')"
      >
        <k-button
          v-for="lang in languages"
          :key="lang.code"
          :aria-current="lang.code === language ? 'true' : undefined"
          :aria-label="lang.code"
          :title="lang.name"
          :theme="lang.code === language ? 'dark' : 'empty'"
          variant="filled"
          size="sm"
          responsive="true"
          @click="goToLanguage(lang.code)"
        >
          {{ lang.code }}
        </k-button>
      </div>

      <div class="k-meta-kit-sponsor">
        <k-button
          class="k-meta-kit-sponsor-button"
          icon="heart"
          variant="filled"
          size="sm"
          theme="empty"
          :title="$t('meta-kit.sponsor.support')"
          :aria-label="$t('meta-kit.sponsor.support')"
          @click="$refs.sponsorDropdown.toggle()"
        />
        <k-dropdown-content
          ref="sponsorDropdown"
          align-x="end"
        >
          <div class="k-meta-kit-sponsor-text">
            {{ sponsorText }}
          </div>
          <k-dropdown-item
            icon="heart"
            link="https://github.com/sponsors/tearoom1"
            target="_blank"
          >
            {{ $t('meta-kit.sponsor.github') }}
          </k-dropdown-item>
          <k-dropdown-item
            icon="heart"
            link="https://buymeacoffee.com/tearoom1"
            target="_blank"
          >
            {{ $t('meta-kit.sponsor.coffee') }}
          </k-dropdown-item>
        </k-dropdown-content>
      </div>
    </div>

    <!-- Stats tiles -->
    <meta-kit-overview
      :cards="statsCards"
      :total-count="pagesData.length"
      :active-area="activeArea"
      :filtered-count="filteredPages.length"
      @select-area="selectArea"
    />

    <!-- Actions & Filters -->
    <meta-kit-actions
      :selected-count="actionPageIds.length"
      :has-selection="selectedPages.length > 0"
      :is-filtered="!!(searchQuery || activeFilters.length)"
      :ai-enabled="aiEnabled"
      :review-enabled="reviewEnabled"
      :is-generating="isGeneratingAll"
      @edit-selected="showSelectedPagesDialog"
      @generate-missing="generateAllDescriptions"
      @refresh="refreshPages"
    >
      <template #filters>
        <meta-kit-filters
          :show-preview.sync="showPreviewInTable"
          :preview-mode.sync="previewMode"
          :search-query.sync="searchQuery"
          :active-filters.sync="activeFilters"
          :sort-by.sync="sortBy"
          :inheritance.sync="inheritance"
          :page-size.sync="pageSize"
        />
      </template>
    </meta-kit-actions>

    <!-- Pages Table -->
    <meta-kit-table
      :inheritance="inheritance"
      :show-preview="showPreviewInTable"
      :preview-mode="previewMode"
      :pages="paginatedPages"
      :start-index="(currentPage - 1) * pageSize"
      :selected-pages="selectedPages"
      :is-all-selected="isAllCurrentPageSelected"
      :ai-enabled="aiEnabled"
      :review-enabled="reviewEnabled"
      :site-settings="siteSettingsData"
      :validation-settings="validationSettingsData"
      :duplicates="duplicates"
      :all-pages="pagesData"
      @toggle-select-all="toggleSelectAllCurrentPage"
      @toggle-page="togglePageSelection"
      @review-page="reviewSinglePage"
      @edit-page="editSinglePageMetadata"
      @generate-page="openSinglePageGenerate"
    />

    <!-- Pagination (the page size lives in the Display menu) -->
    <div class="k-meta-kit-pagination">
      <div class="k-meta-kit-pagination-nav">
        <template v-if="totalPages > 1">
          <k-button
            icon="angle-left"
            :disabled="currentPage === 1"
            @click="previousPage"
          />
          <span class="k-meta-kit-pagination-info">
            {{ $t('meta-kit.pagination.page', { page: currentPage, pages: totalPages }) }}
            <template v-if="searchQuery || activeFilters.length">{{ $t('meta-kit.pagination.filtered', { count: filteredPages.length, total: pagesData.length }) }}</template>
            <template v-else>{{ $t('meta-kit.pagination.total', { total: pagesData.length }) }}</template>
          </span>
          <k-button
            icon="angle-right"
            :disabled="currentPage === totalPages"
            @click="nextPage"
          />
        </template>
      </div>
    </div>

    <!-- Bulk Edit Dialog -->
    <meta-kit-bulk-edit-dialog
      ref="allPagesDialog"
      :validation-settings="validationSettingsData"
      :api="$api"
      :site-settings="siteSettingsData"
      :ai-enabled="aiEnabled"
      @saved="handleSavedUpdates"
    />

    <!-- Single Page Edit Dialog -->
    <meta-kit-single-page-dialog
      ref="singlePageDialog"
      :validation-settings="validationSettingsData"
      :api="$api"
      :site-settings="siteSettingsData"
      :ai-enabled="aiEnabled"
      @saved="handleSavedUpdates"
    />

    <meta-kit-review-dialog
      ref="reviewDialog"
      :api="$api"
    />

    <!-- Bulk Generation Dialog (used for both bulk and single-page AI generate) -->
    <meta-kit-bulk-generate-dialog
      ref="bulkGenerateDialog"
      :selected-count="singleGeneratePageId ? 1 : actionPageIds.length"
      @generate="performBulkGeneration"
    />

    <!-- Review generated suggestions before saving -->
    <meta-kit-suggestions-dialog
      ref="suggestionsDialog"
      :language="language"
      :pages="pagesData"
      :site-settings="siteSettingsData"
      :validation-settings="validationSettingsData"
      @save="saveSuggestions"
    />

    <!-- Actions for the selected pages -->
    <meta-kit-selection-bar
      v-if="selectedPages.length > 0"
      :count="selectedPages.length"
      :ai-enabled="aiEnabled"
      @edit="showSelectedPagesDialog"
      @generate="generateAllDescriptions"
      @clear="selectedPages = []"
    />

    <!-- Loading Overlay -->
    <div v-if="isGeneratingAll" class="k-meta-kit-loading-overlay">
      <div class="k-meta-kit-loading-content">
        <div class="k-meta-kit-loading-spinner">
          <k-icon type="loader" />
        </div>
        <div class="k-meta-kit-loading-text">{{ loadingLabel }}</div>
        <div v-if="loadingProgress" class="k-meta-kit-loading-progress">
          {{ loadingProgress }}
        </div>
        <div v-if="progressTotal" class="k-mk2-progress" aria-hidden="true">
          <span :style="{ width: (progressDone / progressTotal) * 100 + '%' }"></span>
        </div>
        <k-button
          v-if="canCancelGeneration"
          icon="cancel"
          variant="filled"
          size="sm"
          :disabled="cancelRequested"
          @click="cancelRequested = true"
        >
          {{ cancelRequested ? $t('meta-kit.generate.stopping') : $t('cancel') }}
        </k-button>
      </div>
    </div>
  </k-panel-inside>
</template>

<script>
// Table area
import MetaKitOverview from './parts/table/MetaKitOverview.vue';
import MetaKitFilters from './parts/table/MetaKitFilters.vue';
import MetaKitActions from './parts/table/MetaKitActions.vue';
import MetaKitTable from './parts/table/MetaKitTable.vue';
import MetaKitSelectionBar from './parts/table/MetaKitSelectionBar.vue';

// Edit/Dialog components
import MetaKitBulkGenerateDialog from './parts/edit/MetaKitBulkGenerateDialog.vue';
import MetaKitSinglePageDialog from './parts/edit/MetaKitSinglePageDialog.vue';
import MetaKitBulkEditDialog from './parts/edit/MetaKitBulkEditDialog.vue';
import MetaKitReviewDialog from './parts/edit/MetaKitReviewDialog.vue';
import MetaKitSuggestionsDialog from './parts/edit/MetaKitSuggestionsDialog.vue';
import {
  GENERATION_FIELDS,
  planGeneration,
  runGeneration,
  applySuggestions,
  generateFieldSuggestion
} from '../composables/bulkGeneration.js';
import { applySingleFieldUpdate } from '../composables/saveFields.js';
import {
  filterPages,
  sortPages,
  paginatePages,
  getTotalPages,
  isAllCurrentPageSelected as isAllSelectedOnPage,
  toggleSelectAllCurrentPage as toggleSelectAllOnPage,
  classifyPageField,
  findDuplicates
} from '../composables/panelState.js';

const DISPLAY_STORE = 'meta-kit:display';
const TABLE_STORE = 'meta-kit:table';

// Storage can be missing or blocked (private mode, disabled site data)
function readStore(storage, key) {
  try {
    const raw = storage.getItem(key);
    return raw ? JSON.parse(raw) : null;
  } catch (error) {
    return null;
  }
}
function writeStore(storage, key, value) {
  try {
    storage.setItem(key, JSON.stringify(value));
  } catch (error) {
    // nothing to do: the setting simply isn't remembered
  }
}

// Filters behind each stats tile ("needs attention" in that area)
const AREA_FILTERS = {
  slug: ['type-slug', 'warning', 'error'],
  title: ['type-title', 'warning', 'error'],
  description: ['type-description', 'warning', 'error'],
  ogImage: ['type-og-image', 'warning', 'error'],
  duplicates: ['type-duplicates', 'warning'],
  noindex: ['type-noindex', 'warning']
};

export default {
  components: {
    MetaKitTable,
    MetaKitBulkGenerateDialog,
    MetaKitSinglePageDialog,
    MetaKitBulkEditDialog,
    MetaKitReviewDialog,
    MetaKitSuggestionsDialog,
    MetaKitOverview,
    MetaKitSelectionBar,
    MetaKitFilters,
    MetaKitActions
  },
  props: {
    pages: Array,
    language: String,
    languages: Array,
    validationSettings: {
      type: Object,
      default: () => ({})
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    reviewEnabled: {
      type: Boolean,
      default: false
    },
    siteSettings: {
      type: Object,
      default: () => ({
        appendSiteName: true,
        siteMetaTitle: '',
        titleSeparator: '|'
      })
    }
  },
  data() {
    return {
      isLoadingPages: false,
      isGeneratingAll: false,
      pagesData: this.pages || [],
      siteSettingsData: this.siteSettings || {},
      validationSettingsData: this.validationSettings || {},

      // Single-page AI generate: null = bulk mode, string = single page ID
      singleGeneratePageId: null,

      // Pagination & Selection
      selectedPages: [],
      currentPage: 1,
      pageSize: 25,
      searchQuery: '',
      activeFilters: [],
      sortBy: 'default',
      showPreviewInTable: false,
      previewMode: 'meta',
      inheritance: 'dimmed',
      loadingProgress: '',
      loadingLabel: '',
      canCancelGeneration: false,
      cancelRequested: false,
      progressDone: 0,
      progressTotal: 0
    };
  },
  computed: {
    sponsorText() {
      return this.$t('meta-kit.sponsor.text');
    },

    // Bulk actions use the selection, or all filtered pages without one
    actionPageIds() {
      if (this.selectedPages.length > 0) {
        return this.selectedPages;
      }
      return this.filteredPages.map((page) => page.id);
    },
    // The stats area whose filter is active (set by clicking its tile)
    activeArea() {
      return Object.keys(AREA_FILTERS).find((area) => {
        const filters = AREA_FILTERS[area];
        return filters.length === this.activeFilters.length
          && filters.every((filter) => this.activeFilters.includes(filter));
      }) || null;
    },
    duplicates() {
      return findDuplicates(this.pagesData);
    },
    classifierContext() {
      return {
        siteSettings: this.siteSettingsData,
        validationSettings: this.validationSettingsData,
        duplicates: this.duplicates
      };
    },
    filteredPages() {
      const filtered = filterPages(this.pagesData, this.activeFilters, this.searchQuery, this.classifierContext);
      return sortPages(filtered, this.sortBy, this.classifierContext);
    },
    paginatedPages() {
      return paginatePages(this.filteredPages, this.currentPage, this.pageSize);
    },
    totalPages() {
      return getTotalPages(this.filteredPages, this.pageSize);
    },
    isAllCurrentPageSelected() {
      return isAllSelectedOnPage(this.paginatedPages, this.selectedPages);
    },
    statsCards() {
      return [
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'slug'), this.$t('meta-kit.field.slug'), {
          key: 'slug',
          detailLines: [
            this.$t('meta-kit.stats.slug.good'),
            this.$t('meta-kit.stats.slug.review'),
            this.$t('meta-kit.stats.slug.fix')
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'title'), this.$t('meta-kit.field.metaTitle'), {
          key: 'title',
          detailLines: [
            this.$t('meta-kit.stats.title.good'),
            this.$t('meta-kit.stats.title.review'),
            this.$t('meta-kit.stats.title.fix')
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'description'), this.$t('meta-kit.field.metaDescription'), {
          key: 'description',
          detailLines: [
            this.$t('meta-kit.stats.description.good'),
            this.$t('meta-kit.stats.description.review'),
            this.$t('meta-kit.stats.description.fix')
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'ogImage'), this.$t('meta-kit.field.ogImage'), {
          key: 'ogImage',
          detailLines: [
            this.$t('meta-kit.stats.ogImage.good'),
            this.$t('meta-kit.stats.ogImage.review')
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'duplicates'), this.$t('meta-kit.field.duplicates'), {
          key: 'duplicates',
          attentionStatuses: ['review'],
          detailLines: [
            this.$t('meta-kit.stats.duplicates.good'),
            this.$t('meta-kit.stats.duplicates.review')
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'noindex'), this.$t('meta-kit.field.noindex'), {
          key: 'noindex',
          attentionStatuses: ['review'],
          detailLines: [
            this.$t('meta-kit.stats.noindex.good'),
            this.$t('meta-kit.stats.noindex.review')
          ]
        })
      ];
    }
  },
  created() {
    // Display preferences come back in every session, the table state only
    // within this tab (so a jump into the page editor and back keeps it)
    const display = readStore(localStorage, DISPLAY_STORE);
    if (display) {
      if (['count', 'meta', 'og'].includes(display.view)) {
        this.showPreviewInTable = display.view !== 'count';
        this.previewMode = display.view === 'og' ? 'og' : 'meta';
      }
      if (['none', 'dimmed', 'marked'].includes(display.inheritance)) this.inheritance = display.inheritance;
      if (Number.isInteger(display.pageSize) && display.pageSize > 0) this.pageSize = display.pageSize;
    }
    const table = readStore(sessionStorage, TABLE_STORE);
    if (table) {
      if (typeof table.searchQuery === 'string') this.searchQuery = table.searchQuery;
      if (Array.isArray(table.activeFilters)) this.activeFilters = table.activeFilters.filter((f) => typeof f === 'string');
      if (typeof table.sortBy === 'string') this.sortBy = table.sortBy;
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
      this.saveTableState();
    },
    activeFilters() {
      this.currentPage = 1;
      this.saveTableState();
    },
    sortBy() {
      this.currentPage = 1;
      this.saveTableState();
    },
    pageSize() {
      this.currentPage = 1;
      this.saveDisplayState();
    },
    showPreviewInTable: 'saveDisplayState',
    previewMode: 'saveDisplayState',
    inheritance: 'saveDisplayState'
  },
  methods: {
    saveDisplayState() {
      writeStore(localStorage, DISPLAY_STORE, {
        view: this.showPreviewInTable ? this.previewMode : 'count',
        inheritance: this.inheritance,
        pageSize: this.pageSize
      });
    },
    saveTableState() {
      writeStore(sessionStorage, TABLE_STORE, {
        searchQuery: this.searchQuery,
        activeFilters: this.activeFilters,
        sortBy: this.sortBy
      });
    },

    buildStatusBuckets(allPages, filteredPages, classify, label, options = {}) {
      const attentionStatuses = options.attentionStatuses || ['review', 'fix'];
      const summarize = (pages) => pages.reduce((acc, page) => {
        const status = classify(page);
        acc[status]++;
        return acc;
      }, { good: 0, review: 0, fix: 0 });

      const total = summarize(allPages);
      const filtered = summarize(filteredPages);
      const filteredAttention = attentionStatuses.reduce((sum, status) => sum + filtered[status], 0);
      const totalAttention = attentionStatuses.reduce((sum, status) => sum + total[status], 0);

      return {
        key: options.key || label,
        label,
        filteredGood: filtered.good,
        filteredReview: filtered.review,
        filteredFix: filtered.fix,
        totalGood: total.good,
        totalReview: total.review,
        totalFix: total.fix,
        filteredAttention,
        totalAttention,
        tooltip: this.buildStatsTooltip({
          label,
          filtered,
          total,
          filteredAttention,
          totalAttention,
          detailLines: options.detailLines || []
        })
      };
    },

    buildStatsTooltip({ label, filtered, total, filteredAttention, totalAttention, detailLines }) {
      const hasScopedView = !!(this.searchQuery || this.activeFilters.length);
      const lines = [label];

      if (hasScopedView) {
        lines.push(this.$t('meta-kit.stats.tooltip.visible', { count: this.filteredPages.length, total: this.pagesData.length }));
        lines.push(this.$t('meta-kit.stats.tooltip.attentionHere', { count: filteredAttention }));
        lines.push(this.$t('meta-kit.stats.tooltip.attentionOverall', { count: totalAttention }));
      } else {
        lines.push(this.$t('meta-kit.stats.tooltip.attention', { count: filteredAttention, total: this.pagesData.length }));
      }

      lines.push(
        this.$t('meta-kit.stats.tooltip.good', { count: filtered.good }),
        this.$t('meta-kit.stats.tooltip.review', { count: filtered.review }),
        this.$t('meta-kit.stats.tooltip.fix', { count: filtered.fix })
      );

      if (detailLines.length > 0) {
        lines.push('', ...detailLines);
      }

      if (hasScopedView) {
        lines.push('', this.$t('meta-kit.stats.tooltip.overall', { good: total.good, review: total.review, fix: total.fix }));
      }

      return lines.join('\n');
    },

    mergeUpdatedPage(updatedPage) {
      if (!updatedPage || !updatedPage.id) return;

      const existingIndex = this.pagesData.findIndex((page) => page.id === updatedPage.id);
      if (existingIndex === -1) return;

      this.$set(this.pagesData, existingIndex, updatedPage);
    },

    handleSavedUpdates(payload = {}) {
      if (payload.page) {
        this.mergeUpdatedPage(payload.page);
      } else if (Array.isArray(payload.pages)) {
        payload.pages.forEach((page) => this.mergeUpdatedPage(page));
      } else {
        // Dialog didn't pass updated data — fall back to full refresh
        this.refreshPages();
      }

      if (payload.siteSettings) {
        this.siteSettingsData = payload.siteSettings;
      }
    },

    // Stats cards use the shared classifier, in their own wording
    classifyForStats(page, field) {
      const level = classifyPageField(page, field, this.classifierContext);
      return { good: 'good', warning: 'review', error: 'fix' }[level];
    },

    async refreshPages() {
      this.isLoadingPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages', {
          _ts: Date.now()
        });
        if (response.status === 'success') {
          this.pagesData = response.data;
          if (response.siteSettings) {
            this.siteSettingsData = response.siteSettings;
          }
          if (response.validationSettings) {
            this.validationSettingsData = response.validationSettings;
          }
        }
      } catch (error) {
        window.panel.notification.error(this.$t('meta-kit.error.refresh'));
      } finally {
        this.isLoadingPages = false;
      }
    },

    selectArea(area) {
      this.activeFilters = !area || this.activeArea === area ? [] : [...AREA_FILTERS[area]];
    },

    // Open the field-selection dialog for a single page's AI generation
    openSinglePageGenerate(pageId) {
      this.singleGeneratePageId = pageId;
      this.$refs.bulkGenerateDialog.open();
    },

    // Open the field-selection dialog for bulk (selected pages) generation
    generateAllDescriptions() {
      this.singleGeneratePageId = null;
      this.$refs.bulkGenerateDialog.open();
    },

    async performBulkGeneration(options) {
      if (!GENERATION_FIELDS.some(({ option }) => options[option])) {
        window.panel.notification.error(this.$t('meta-kit.generate.selectField'));
        return;
      }

      // Single-page mode when triggered from the table row AI button
      const pageIds = this.singleGeneratePageId
        ? [this.singleGeneratePageId]
        : this.actionPageIds;
      this.singleGeneratePageId = null;

      const pages = this.pagesData.filter((page) => pageIds.includes(page.id));
      const jobs = planGeneration(pages, options);

      if (jobs.length === 0) {
        window.panel.notification.success(this.$t('meta-kit.generate.nothing'));
        return;
      }

      this.isGeneratingAll = true;
      this.loadingLabel = this.$t('meta-kit.generate.running');
      this.canCancelGeneration = true;
      this.cancelRequested = false;

      let result;
      try {
        result = await runGeneration(jobs, {
          generate: (job) => generateFieldSuggestion(this.$api, job, this.language || null),
          onProgress: ({ done, total, job }) => {
            this.progressDone = done;
            this.progressTotal = total;
            this.loadingProgress = job
              ? this.$t('meta-kit.generate.progress', { current: done + 1, total, page: job.pageTitle, field: job.label })
              : '';
          },
          isCancelled: () => this.cancelRequested
        });
      } finally {
        this.isGeneratingAll = false;
        this.canCancelGeneration = false;
        this.loadingProgress = '';
        this.progressTotal = 0;
      }

      if (options.review && result.suggestions.length > 0) {
        this.$refs.suggestionsDialog.open(result);
        return;
      }

      await this.saveSuggestions(result.suggestions, result.errors);
    },

    async saveSuggestions(suggestions, generationErrors = []) {
      this.isGeneratingAll = true;
      this.loadingLabel = this.$t('meta-kit.generate.saving');

      let result;
      try {
        result = await applySuggestions(suggestions, (suggestion) => applySingleFieldUpdate(this.$api, {
          pageId: suggestion.pageId,
          fieldName: suggestion.field,
          value: suggestion.value
        }));
      } finally {
        this.isGeneratingAll = false;
      }

      const failed = [...generationErrors, ...result.errors];
      const summary = this.$t('meta-kit.generate.saved', { count: result.saved.length });

      if (failed.length > 0) {
        const first = failed[0];
        window.panel.notification.error(
          this.$t('meta-kit.generate.savedWithErrors', {
            summary,
            count: failed.length,
            page: first.pageTitle,
            field: first.label,
            message: first.message
          })
        );
      } else {
        window.panel.notification.success(summary);
      }

      await this.refreshPages();
    },

    async editSinglePageMetadata(pageId) {
      this.$refs.singlePageDialog.open(pageId);
    },

    reviewSinglePage(pageId, title = this.$t('meta-kit.review.title')) {
      this.$refs.reviewDialog.openPage(pageId, title);
    },

    goToLanguage(langCode) {
      if (langCode === this.language) return;
      const baseUrl = window.location.origin + window.location.pathname.split('?')[0];
      window.location.href = baseUrl + '?language=' + langCode;
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },
    previousPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },

    isPageSelected(pageId) {
      return this.selectedPages.includes(pageId);
    },
    togglePageSelection(pageId) {
      const index = this.selectedPages.indexOf(pageId);
      if (index > -1) {
        this.selectedPages.splice(index, 1);
      } else {
        this.selectedPages.push(pageId);
      }
    },
    toggleSelectAllCurrentPage() {
      this.selectedPages = toggleSelectAllOnPage(this.paginatedPages, this.selectedPages);
    },
    async showSelectedPagesDialog() {
      if (this.actionPageIds.length === 0) return;
      this.$refs.allPagesDialog.open(this.actionPageIds);
    }
  }
};
</script>
