<template>
  <k-panel-inside class="k-meta-kit-view">
    <!-- License Warning -->
    <div v-if="!hasValidLicense" class="k-meta-kit-warning">
      <k-box theme="negative">
        <k-icon type="alert"/>
        <span><strong>No valid license:</strong> AI generation and saving changes are disabled. Meta tags are limited to 20 characters. Please activate your license to use all features.</span>
      </k-box>
    </div>

    <!-- Language Switcher -->
    <div v-if="languages && languages.length > 1" class="k-meta-kit-language-bar">
      <k-button-group>
        <k-button
          v-for="lang in languages"
          :key="lang.code"
          :theme="lang.code === language ? 'positive' : ''"
          size="xs"
          @click="goToLanguage(lang.code)"
        >
          {{ lang.code.toUpperCase() }}
        </k-button>
      </k-button-group>
      <k-button v-if="legacyMigration" icon="download" size="xs" @click="showLegacyDialog">Legacy Migration</k-button>
    </div>

    <!-- Legacy Detection Warning -->
    <div v-if="legacyMigration && legacyDetection.show && legacyDetection.found > 0" class="k-meta-kit-warning">
      <k-box theme="info">
        <k-icon type="info"/>
        <span>Found {{ legacyDetection.found }} pages with legacy SEO metadata</span>
        <k-button icon="download" @click="showLegacyDialog">View & Convert</k-button>
        <k-button icon="cancel" @click="dismissLegacyWarning">Dismiss</k-button>
      </k-box>
    </div>

    <!-- Stats Cards -->
    <meta-kit-stats
      :filtered-count="filteredPages.length"
      :total-count="pagesData.length"
      :filtered-with-description="filteredPagesWithDescription"
      :total-with-description="pagesWithDescription"
      :filtered-with-image="filteredPagesWithOgImage"
      :total-with-image="pagesWithOgImage"
      :filtered-no-index="filteredPagesNoIndex"
      :total-no-index="pagesNoIndex"
      :search-active="!!searchQuery"
    />

    <!-- Actions & Filters -->
    <meta-kit-actions
      :selected-count="selectedPages.length"
      :ai-enabled="aiEnabled"
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
          :metadata-filter.sync="metadataFilter"
          :page-size="pageSize"
          :page-size-options="pageSizeOptions"
          @change-page-size="changePageSize"
        />
      </template>
    </meta-kit-actions>

    <!-- Pages Table -->
    <meta-kit-table
      :pages="paginatedPages"
      :start-index="(currentPage - 1) * pageSize"
      :selected-pages="selectedPages"
      :is-all-selected="isAllCurrentPageSelected"
      :show-preview="showPreviewInTable"
      :preview-mode="previewMode"
      :ai-enabled="aiEnabled"
      @toggle-select-all="toggleSelectAllCurrentPage"
      @toggle-page="togglePageSelection"
      @edit-page="editSinglePageMetadata"
      @generate-description="generateDescription"
    />

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="k-meta-kit-pagination">
      <k-button
        icon="angle-left"
        :disabled="currentPage === 1"
        @click="previousPage"
      />
      <span class="k-meta-kit-pagination-info">
        Page {{ currentPage }} of {{ totalPages }}
        <template v-if="searchQuery">({{ filteredPages.length }} of {{ pagesData.length }})</template>
        <template v-else>({{ pagesData.length }} total)</template>
      </span>
      <k-button
        icon="angle-right"
        :disabled="currentPage === totalPages"
        @click="nextPage"
      />
    </div>

    <!-- Legacy Migration Dialog -->
    <meta-kit-legacy-dialog
      ref="legacyDialog"
      :summary="legacySummary"
      :is-loading="isLoadingLegacy"
      :is-migrating="isMigratingAll"
      @load-summary="loadLegacySummary"
      @migrate="migrateAllLanguages"
    />

    <!-- Bulk Edit Dialog -->
    <meta-kit-bulk-edit-dialog
      ref="allPagesDialog"
      :api="$api"
      :site-settings="siteSettings"
      :ai-enabled="aiEnabled"
      @saved="refreshPages"
    />

    <!-- Single Page Edit Dialog -->
    <meta-kit-single-page-dialog
      ref="singlePageDialog"
      :api="$api"
      :site-settings="siteSettings"
      :ai-enabled="aiEnabled"
      @saved="refreshPages"
    />

    <!-- Bulk Generation Dialog -->
    <meta-kit-bulk-generate-dialog
      ref="bulkGenerateDialog"
      :selected-count="selectedPages.length"
      @generate="performBulkGeneration"
    />
  </k-panel-inside>
</template>

<script>
// Field components
import MetaKitTitleField from './parts/field/MetaKitTitleField.vue';
import MetaKitDescriptionField from './parts/field/MetaKitDescriptionField.vue';

// Table component
import MetaKitStats from './parts/table/MetaKitStats.vue';
import MetaKitFilters from './parts/table/MetaKitFilters.vue';
import MetaKitActions from './parts/table/MetaKitActions.vue';
import MetaKitTable from './parts/table/MetaKitTable.vue';

// Edit/Dialog components
import MetaKitBulkGenerateDialog from './parts/edit/MetaKitBulkGenerateDialog.vue';
import MetaKitSinglePageDialog from './parts/edit/MetaKitSinglePageDialog.vue';
import MetaKitBulkEditDialog from './parts/edit/MetaKitBulkEditDialog.vue';
import MetaKitLegacyDialog from './parts/edit/MetaKitLegacyDialog.vue';

export default {
  components: {
    MetaKitTitleField,
    MetaKitDescriptionField,
    MetaKitTable,
    MetaKitBulkGenerateDialog,
    MetaKitSinglePageDialog,
    MetaKitBulkEditDialog,
    MetaKitStats,
    MetaKitFilters,
    MetaKitActions,
    MetaKitLegacyDialog
  },
  provide() {
    return {
      siteSettings: this.siteSettings
    };
  },
  props: {
    pages: Array,
    language: String,
    languages: Array,
    legacyMigration: {
      type: Boolean,
      default: false
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    hasValidLicense: {
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
      isLoadingLegacy: false,
      isMigratingAll: false,
      pagesData: this.pages || [],
      legacyPages: [],
      legacySummary: {total: 0, byLanguage: []},
      legacyDetection: {
        show: false,
        found: 0
      },
      fieldChoices: {}, // { pageId: { fieldName: 'legacy|current|manual|ai', manualValue: '...' } }
      generatingFields: {}, // { pageId: { fieldName: true } }

      // Pagination & Selection
      selectedPages: [],
      currentPage: 1,
      pageSize: 25,
      pageSizeOptions: [
        {value: 25, text: '25/page'},
        {value: 50, text: '50/page'},
        {value: 100, text: '100/page'},
        {value: 99999, text: 'All'}
      ],
      searchQuery: '',
      metadataFilter: 'all',
      showPreviewInTable: false,
      previewMode: 'meta'
    };
  },
  computed: {
    filteredPages() {
      let pages = this.pagesData;

      // Apply metadata filter
      if (this.metadataFilter !== 'all') {
        pages = pages.filter(page => {
          switch (this.metadataFilter) {
            case 'missing-title':
              return !page.hasMetaTitle;
            case 'missing-description':
              return !page.hasMetaDescription;
            case 'missing-og-title':
              return !page.hasOgTitle;
            case 'missing-og-description':
              return !page.hasOgDescription;
            case 'missing-og-image':
              return !page.hasOgImage;
            case 'complete':
              return page.hasMetaTitle && page.hasMetaDescription && page.hasOgImage;
            default:
              return true;
          }
        });
      }

      // Apply search query
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        pages = pages.filter(page => {
          return page.title.toLowerCase().includes(query) ||
            page.id.toLowerCase().includes(query) ||
            page.template.toLowerCase().includes(query) ||
            (page.metaDescription && page.metaDescription.toLowerCase().includes(query));
        });
      }

      return pages;
    },
    paginatedPages() {
      if (this.pageSize >= 99999) {
        return this.filteredPages;
      }
      const start = (this.currentPage - 1) * this.pageSize;
      const end = start + this.pageSize;
      return this.filteredPages.slice(start, end);
    },
    totalPages() {
      if (this.pageSize >= 99999) {
        return 1;
      }
      return Math.ceil(this.filteredPages.length / this.pageSize);
    },
    isAllCurrentPageSelected() {
      if (this.paginatedPages.length === 0) return false;
      return this.paginatedPages.every(page => this.selectedPages.includes(page.id));
    },
    pagesWithDescription() {
      return this.pagesData.filter(p => p.hasMetaDescription).length;
    },
    pagesWithOgImage() {
      return this.pagesData.filter(p => p.hasOgImage).length;
    },
    pagesNoIndex() {
      return this.pagesData.filter(p => p.robots && p.robots.includes('noindex')).length;
    },
    filteredPagesWithDescription() {
      return this.filteredPages.filter(p => p.hasMetaDescription).length;
    },
    filteredPagesWithOgImage() {
      return this.filteredPages.filter(p => p.hasOgImage).length;
    },
    filteredPagesNoIndex() {
      return this.filteredPages.filter(p => p.robots && p.robots.includes('noindex')).length;
    }
  },
  watch: {
    searchQuery() {
      // Reset to first page when search changes
      this.currentPage = 1;
    }
  },
  created() {
  },
  methods: {
    getFullTitle(pageTitle) {
      if (!pageTitle || !this.siteSettings.appendSiteName) {
        return pageTitle || '';
      }

      const separator = this.siteSettings.titleSeparator || '|';
      const siteName = this.siteSettings.siteMetaTitle || '';

      if (!siteName) {
        return pageTitle;
      }

      return `${pageTitle} ${separator} ${siteName}`;
    },
    getStatusClass(hasField, length, fieldType = 'description', pageTitle = null) {
      if (!hasField) return '';

      let optimal, warning;

      if (fieldType === 'title') {
        // For site page (pageTitle is null), no strict validation - just show the length
        if (pageTitle === null) {
          return ''; // No color coding for site meta title
        }

        // For regular pages, calculate final length if site name appending is enabled
        let finalLength = length;
        if (pageTitle && this.siteSettings.appendSiteName) {
          finalLength = this.getFullTitle(pageTitle).length;
        }

        // Title: optimal 50-60, warning ±10% (45-66), error outside
        optimal = { min: 50, max: 60 };
        warning = { min: 45, max: 66 }; // 10% tolerance

        // Green: within optimal range
        if (finalLength >= optimal.min && finalLength <= optimal.max) {
          return 'k-meta-kit-status-success';
        }

        // Yellow: within warning range (10% around optimal)
        if (finalLength >= warning.min && finalLength <= warning.max) {
          return 'k-meta-kit-status-warning';
        }

        // Red: outside acceptable range
        return 'k-meta-kit-status-error';
      } else {
        // Description: optimal 140-160, warning ±10% (126-176), error outside
        optimal = { min: 140, max: 160 };
        warning = { min: 126, max: 176 }; // 10% tolerance

        // Green: within optimal range
        if (length >= optimal.min && length <= optimal.max) {
          return 'k-meta-kit-status-success';
        }

        // Yellow: within warning range (10% around optimal)
        if (length >= warning.min && length <= warning.max) {
          return 'k-meta-kit-status-warning';
        }

        // Red: outside acceptable range
        return 'k-meta-kit-status-error';
      }
    },
    async refreshPages() {
      this.isLoadingPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages');
        if (response.status === 'success') {
          this.pagesData = response.data;
        }
      } catch (error) {
        window.panel.notification.error('Failed to refresh pages');
      } finally {
        this.isLoadingPages = false;
      }
    },
    async generateDescription(pageId) {
      try {
        const response = await this.$api.post('meta-kit/generate-description', {pageId});
        if (response.status === 'success') {
          window.panel.notification.success(response.message || 'Description generated');
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message || 'Failed to generate description');
        }
      } catch (error) {
        let errorMessage = 'Failed to generate description';
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        }
        window.panel.notification.error(errorMessage);
        console.error('Generation error:', error);
      }
    },
    generateAllDescriptions() {
      // Open the dialog (options are reset in the dialog component)
      this.$refs.bulkGenerateDialog.open();
    },

    async performBulkGeneration(options) {
      // Validate at least one option is selected
      if (!options.title && !options.description && !options.ogTitle && !options.ogDescription) {
        window.panel.notification.error('Please select at least one field to generate');
        return;
      }

      this.isGeneratingAll = true;

      // Build field list for message
      const fields = [];
      if (options.title) fields.push('meta titles');
      if (options.description) fields.push('meta descriptions');
      if (options.ogTitle) fields.push('OG titles');
      if (options.ogDescription) fields.push('OG descriptions');
      const fieldText = fields.join(', ');

      // Show loading notification
      const loadingNotification = window.panel.notification.open({
        message: `Generating ${fieldText} with AI...`,
        type: 'info',
        timeout: 0 // Don't auto-close
      });

      try {
        const response = await this.$api.post('meta-kit/generate-all', {
          generateTitle: options.title,
          generateDescription: options.description,
          generateOgTitle: options.ogTitle,
          generateOgDescription: options.ogDescription,
          pageIds: this.selectedPages
        });

        // Close loading notification
        if (loadingNotification && loadingNotification.close) {
          loadingNotification.close();
        }

        if (response.status === 'success') {
          const details = `Generated: ${response.generated || 0}, Skipped: ${response.skipped || 0}, Failed: ${response.failed || 0}`;
          window.panel.notification.success(`${response.message || 'Generation completed!'} ${details}`);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message || 'Generation failed');
        }
      } catch (error) {
        // Close loading notification
        if (loadingNotification && loadingNotification.close) {
          loadingNotification.close();
        }

        // Extract detailed error message
        let errorMessage = `Failed to generate ${fieldText}`;
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        } else if (typeof error === 'string') {
          errorMessage += `: ${error}`;
        }

        window.panel.notification.error(errorMessage);
        console.error('Generation error details:', error);
      } finally {
        this.isGeneratingAll = false;
      }
    },

    async loadLegacySummary() {
      this.isLoadingLegacy = true;
      try {
        const res = await this.$api.get('meta-kit/legacy-summary');
        if (res && res.status === 'success') {
          this.$set(this, 'legacySummary', res);
        } else {
          this.$set(this, 'legacySummary', {total: 0, byLanguage: []});
        }
      } catch (e) {
        window.panel.notification.error('Failed to load legacy summary');
        this.$set(this, 'legacySummary', {total: 0, byLanguage: []});
      } finally {
        this.isLoadingLegacy = false;
      }
    },
    async showLegacyDialog() {
      this.$refs.legacyDialog.open();
      await this.loadLegacySummary();
    },
    async migrateAllLanguages() {
      if (this.legacySummary.total === 0) return;
      if (!confirm('This will migrate legacy SEO fields across all languages (default first). Continue?')) {
        return;
      }
      this.isMigratingAll = true;
      const loading = window.panel.notification.open({
        message: 'Migrating legacy fields across all languages...',
        type: 'info',
        timeout: 0
      });
      try {
        const res = await this.$api.post('meta-kit/convert-legacy-all-languages');
        if (loading && loading.close) loading.close();
        if (res && res.status === 'success') {
          window.panel.notification.success(res.message || 'Migration completed');
          await this.refreshPages();
          await this.loadLegacySummary();
        } else {
          window.panel.notification.error(res.message || 'Migration failed');
        }
      } catch (e) {
        if (loading && loading.close) loading.close();
        window.panel.notification.error('Migration failed');
      } finally {
        this.isMigratingAll = false;
      }
    },

    dismissLegacyWarning() {
      this.legacyDetection.show = false;
      sessionStorage.setItem('metaKitLegacyDismissed', 'true');
    },

    getManualValue(pageId, fieldName) {
      return this.fieldChoices[pageId]?.[fieldName]?.manualValue || '';
    },
    setManualValue(pageId, fieldName, value) {
      if (!this.fieldChoices[pageId]) {
        this.$set(this.fieldChoices, pageId, {});
      }
      if (!this.fieldChoices[pageId][fieldName]) {
        this.$set(this.fieldChoices[pageId], fieldName, {});
      }
      this.$set(this.fieldChoices[pageId][fieldName], 'manualValue', value);
    },
    getEditableValue(pageId, fieldName, currentValue) {
      // Check if a manual value has been explicitly set (even if it's empty)
      if (this.fieldChoices[pageId]?.[fieldName]?.hasOwnProperty('manualValue')) {
        return this.fieldChoices[pageId][fieldName].manualValue;
      }
      return currentValue || '';
    },
    // Helper methods for title field rendering
    isSitePage(pageId) {
      return pageId === 'site';
    },
    shouldShowTitlePreview(pageId, value) {
      return !this.isSitePage(pageId) &&
             value &&
             this.siteSettings.appendSiteName &&
             this.siteSettings.siteMetaTitle;
    },
    getTitleCharCount(pageId, value) {
      if (!value) return 0;

      if (this.isSitePage(pageId)) {
        return value.length;
      }

      if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
        return this.getFullTitle(value).length;
      }

      return value.length;
    },
    getTitleStatusClass(pageId, value) {
      if (!value) return '';

      const pageTitle = this.isSitePage(pageId) ? null : value;
      return this.getStatusClass(true, value.length, 'title', pageTitle);
    },
    // Get all title field data at once to avoid multiple getEditableValue calls
    getTitleFieldData(pageId, currentValue) {
      const value = this.getEditableValue(pageId, 'metaTitle', currentValue);
      return {
        value,
        showPreview: this.shouldShowTitlePreview(pageId, value),
        fullTitle: this.getFullTitle(value),
        charCount: this.getTitleCharCount(pageId, value),
        statusClass: this.getTitleStatusClass(pageId, value)
      };
    },
    // Helper for table title status (uses page object)
    getFullTitleLength(page) {
      // For site page, just return the meta title length or page title length
      if (page.id === 'site') {
        if (page.hasMetaTitle) {
          return page.metaTitleLength;
        }
        // Fallback to page title
        return page.title ? page.title.length : 0;
      }

      // For regular pages, use meta title or fallback to page title
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
      if (!titleToUse) return 0;

      // Calculate full length with site name if enabled
      if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        const fullTitle = `${titleToUse} ${separator} ${siteName}`;
        return fullTitle.length;
      }

      return titleToUse.length;
    },

    getFullTitlePreview(page) {
      // For site page, just return the title
      if (page.id === 'site') {
        return page.hasMetaTitle ? page.metaTitle : page.title;
      }

      // For regular pages, use meta title or fallback to page title
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
      if (!titleToUse) return '—';

      // Build full title with site name if enabled
      if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        return `${titleToUse} ${separator} ${siteName}`;
      }

      return titleToUse;
    },

    getTableTitleStatusClass(page) {
      // For site page, no color coding
      if (page.id === 'site') {
        return '';
      }

      // For regular pages, use the full title length (with site name)
      // This includes pages using the page title as fallback
      const fullLength = this.getFullTitleLength(page);
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
      return this.getStatusClass(true, fullLength, 'title', titleToUse || '');
    },

    getStatusValue(statusClass) {
      // Extract status value from class name
      if (!statusClass) return '';
      const match = statusClass.match(/k-meta-kit-status-(\w+)/);
      return match ? match[1] : '';
    },

    getTitleTooltip(page) {
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;

      if (!titleToUse) {
        return 'No title';
      }

      // For site page, just show the title
      if (page.id === 'site') {
        if (page.hasMetaTitle && page.metaTitle) {
          return `${page.metaTitle}`;
        }
        return `${page.title}`;
      }

      // Build tooltip for regular pages
      let tooltip = '';
      if (page.hasMetaTitle && page.metaTitle) {
        tooltip = `${page.metaTitle}`;
      } else {
        tooltip = `${page.title}`;
      }

      // Add preview if site name is appended
      if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        const preview = `${titleToUse} ${separator} ${siteName}`;
        tooltip = `${preview}`;
      }

      return tooltip;
    },

    getDescriptionTooltip(page) {
      if (!page.hasMetaDescription || !page.metaDescription) {
        return 'No meta description';
      }

      // Show full description or truncated
      const desc = page.metaDescription;
      if (desc.length > 200) {
        return desc.substring(0, 200) + '...';
      }

      return desc;
    },
    async editSinglePageMetadata(pageId) {
      this.$refs.singlePageDialog.open(pageId);
    },
    goToLanguage(langCode) {
      if (langCode === this.language) return;

      // Force full page reload with new language
      const baseUrl = window.location.origin + window.location.pathname.split('?')[0];
      window.location.href = baseUrl + '?language=' + langCode;
    },

    // Pagination methods
    changePageSize(newSize) {
      this.pageSize = parseInt(newSize);
      this.currentPage = 1; // Reset to first page
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

    // Selection methods
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
      const allSelected = this.isAllCurrentPageSelected;
      this.paginatedPages.forEach(page => {
        const index = this.selectedPages.indexOf(page.id);
        if (allSelected) {
          // Deselect all on current page
          if (index > -1) {
            this.selectedPages.splice(index, 1);
          }
        } else {
          // Select all on current page
          if (index === -1) {
            this.selectedPages.push(page.id);
          }
        }
      });
    },
    async showSelectedPagesDialog() {
      if (this.selectedPages.length === 0) return;
      this.$refs.allPagesDialog.open(this.selectedPages);
    }
  }
};
</script>
