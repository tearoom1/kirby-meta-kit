<template>
  <k-panel-inside class="k-meta-kit-view">
    <!-- Legacy Detection Warning -->
    <div v-if="legacyDetection.show && legacyDetection.found > 0" class="k-meta-kit-warning">
      <k-box theme="info">
        <k-icon type="info"/>
        <span>Found {{ legacyDetection.found }} pages with legacy SEO metadata</span>
        <k-button icon="download" @click="showLegacyDialog">View & Convert</k-button>
        <k-button icon="cancel" @click="dismissLegacyWarning">Dismiss</k-button>
      </k-box>
    </div>

    <!-- Stats Cards -->
    <div class="k-meta-kit-stats">
      <div class="k-meta-kit-stats-card">
        <h3>Total Pages</h3>
        <p>{{ pages.length }}</p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>With Description</h3>
        <p>{{ pagesWithDescription }}</p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>With OG Image</h3>
        <p>{{ pagesWithOgImage }}</p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>NoIndex</h3>
        <p>{{ pagesNoIndex }}</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="k-meta-kit-actions">
      <k-button-group>
        <k-button
          icon="magic"
          :disabled="isGeneratingAll"
          :progress="isGeneratingAll"
          @click="generateAllDescriptions"
        >
          Generate All Missing Descriptions
        </k-button>
        <k-button icon="refresh" @click="refreshPages">Refresh</k-button>
        <k-button icon="download" @click="detectLegacyMetadata">Detect Legacy Data</k-button>
      </k-button-group>
    </div>

    <!-- Pages Table -->
    <div class="k-meta-kit-table">
      <table>
        <thead>
        <tr>
          <th>#</th>
          <th>Page</th>
          <th>Template</th>
          <th>Meta Title</th>
          <th>Description</th>
          <th>OG Image</th>
          <th>NoIndex</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(page, index) in pagesData" :key="page.id">
          <td>{{ index + 1 }}</td>
          <td>
            <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
          </td>
          <td>{{ page.template }}</td>
          <td class="k-meta-kit-table-center">
              <span :class="getStatusClass(page.hasMetaTitle, page.metaTitleLength)">
                {{ page.hasMetaTitle ? page.metaTitleLength : '—' }}
              </span>
          </td>
          <td class="k-meta-kit-table-center">
              <span :class="getStatusClass(page.hasMetaDescription, page.metaDescriptionLength)">
                {{ page.hasMetaDescription ? page.metaDescriptionLength : '—' }}
              </span>
          </td>
          <td class="k-meta-kit-table-center">
            <k-icon v-if="page.hasOgImage" type="check" class="k-meta-kit-icon-success"/>
            <span v-else>—</span>
          </td>
          <td class="k-meta-kit-table-center">
            <k-icon v-if="page.noIndex" type="check" class="k-meta-kit-icon-warning"/>
            <span v-else>—</span>
          </td>
          <td class="k-meta-kit-table-center">
            <k-button
              icon="magic"
              size="sm"
              :disabled="page.hasMetaDescription"
              @click="generateDescription(page.id)"
            />
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Legacy Data Dialog -->
    <k-dialog ref="legacyDialog" size="large">
      <k-headline>Legacy SEO Metadata</k-headline>

      <div v-if="isLoadingLegacy" class="k-meta-kit-loading">
        <k-icon class="k-meta-kit-spinner" type="loader"/>
        <span>Scanning for legacy metadata...</span>
      </div>

      <div v-else-if="legacyPages.length > 0" class="k-meta-kit-legacy-list">
        <p>Found {{ legacyPages.length }} pages with legacy SEO fields:</p>

        <div v-for="page in legacyPages" :key="page.id" class="k-meta-kit-legacy-item">
          <div class="k-meta-kit-legacy-item-header">
            <strong>{{ page.title }}</strong>
            <k-button
              icon="download"
              size="sm"
              @click="convertLegacyPage(page.id)"
            >
              Convert
            </k-button>
          </div>
          
          <div class="k-meta-kit-legacy-item-content">
            <div v-for="(value, key) in page.fields" :key="key" class="k-meta-kit-legacy-field">
              <span class="k-meta-kit-legacy-field-label">{{ formatFieldName(key) }}:</span>
              <div class="k-meta-kit-legacy-field-values">
                <div class="k-meta-kit-legacy-field-old">
                  <span class="k-meta-kit-legacy-badge">Legacy</span>
                  <span class="k-meta-kit-legacy-field-value">{{ formatFieldValue(value) }}</span>
                </div>
                <div v-if="page.current && page.current[key]" class="k-meta-kit-legacy-field-new">
                  <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-warning">Will be overwritten</span>
                  <span class="k-meta-kit-legacy-field-value-current">{{ formatFieldValue(page.current[key]) }}</span>
                </div>
                <div v-else class="k-meta-kit-legacy-field-new">
                  <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-new">New field</span>
                  <span class="k-meta-kit-legacy-field-value-empty">No current value</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <k-button-group slot="footer">
          <k-button icon="check" @click="$refs.legacyDialog.close()">Close</k-button>
        </k-button-group>
      </div>

      <div v-else class="k-meta-kit-empty">
        <k-icon type="check"/>
        <p>No legacy SEO metadata found!</p>
      </div>
    </k-dialog>
  </k-panel-inside>
</template>

<script>
export default {
  props: {
    pages: Array
  },
  data() {
    return {
      isLoadingPages: false,
      isGeneratingAll: false,
      isLoadingLegacy: false,
      pagesData: this.pages || [],
      legacyPages: [],
      legacyDetection: {
        show: false,
        found: 0
      }
    };
  },
  computed: {
    pagesWithDescription() {
      return this.pagesData.filter(p => p.hasMetaDescription).length;
    },
    pagesWithOgImage() {
      return this.pagesData.filter(p => p.hasOgImage).length;
    },
    pagesNoIndex() {
      return this.pagesData.filter(p => p.noIndex).length;
    }
  },
  created() {
    this.checkLegacyOnLoad();
  },
  methods: {
    getStatusClass(hasField, length) {
      if (!hasField) return '';
      if (length < 50 || length > 160) {
        return 'k-meta-kit-status-warning';
      }
      return 'k-meta-kit-status-success';
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
          window.panel.notification.success(response.message);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate description');
      }
    },
    async generateAllDescriptions() {
      if (!confirm('This will generate descriptions for all pages without one. Continue?')) {
        return;
      }

      this.isGeneratingAll = true;
      try {
        const response = await this.$api.post('meta-kit/generate-all');
        if (response.status === 'success') {
          window.panel.notification.success(response.message);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate descriptions');
      } finally {
        this.isGeneratingAll = false;
      }
    },
    async detectLegacyMetadata() {
      this.$refs.legacyDialog.open();
      this.isLoadingLegacy = true;
      try {
        const response = await this.$api.get('meta-kit/detect-legacy');
        if (response.status === 'success') {
          this.legacyPages = response.pages || [];
        }
      } catch (error) {
        window.panel.notification.error('Failed to detect legacy metadata');
      } finally {
        this.isLoadingLegacy = false;
      }
    },
    async checkLegacyOnLoad() {
      const dismissed = sessionStorage.getItem('metaKitLegacyDismissed');
      if (dismissed) return;

      try {
        const response = await this.$api.get('meta-kit/detect-legacy');
        if (response.status === 'success' && response.found > 0) {
          this.legacyDetection.show = true;
          this.legacyDetection.found = response.found;
        }
      } catch (error) {
        // Silent fail
      }
    },
    dismissLegacyWarning() {
      this.legacyDetection.show = false;
      sessionStorage.setItem('metaKitLegacyDismissed', 'true');
    },
    showLegacyDialog() {
      this.detectLegacyMetadata();
    },
    async convertLegacyPage(pageId) {
      try {
        const response = await this.$api.post('meta-kit/convert-legacy', {pageId});
        if (response.status === 'success' || response.status === 'info') {
          window.panel.notification.success(response.message);
          await this.detectLegacyMetadata();
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to convert legacy data');
      }
    },
    formatFieldName(fieldName) {
      const names = {
        'metaTitle': 'Meta Title',
        'metaDescription': 'Meta Description',
        'ogImage': 'OG Image'
      };
      return names[fieldName] || fieldName;
    },
    formatFieldValue(value) {
      if (typeof value === 'string') {
        return value.length > 100 ? value.substring(0, 100) + '...' : value;
      }
      if (Array.isArray(value)) {
        return `${value.length} image(s)`;
      }
      if (typeof value === 'object') {
        return 'File';
      }
      return String(value);
    }
  }
};
</script>
<style>

/* Meta Kit View */
.k-meta-kit-view {
  padding: 1.5rem;
  color: var(--color-text);
}

.k-meta-kit-warning {
  margin-bottom: 1.5rem;
}

.k-meta-kit-warning .k-box {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.k-meta-kit-warning .k-box > span {
  flex: 1;
}

.k-meta-kit-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.k-meta-kit-stats-card {
  background: var(--color-back);
  border: 1px solid var(--color-border);
  border-radius: var(--rounded);
  padding: 1.5rem;
  text-align: center;
}

.k-meta-kit-stats-card h3 {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-gray-600);
  margin: 0 0 0.5rem 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.k-meta-kit-stats-card p {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-stats-card h3 {
  color: var(--color-gray-400);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-stats-card p {
  color: var(--color-white);
}

.k-meta-kit-actions {
  margin-bottom: 1.5rem;
}

.k-meta-kit-table {
  background: var(--color-back);
  border: 1px solid var(--color-border);
  border-radius: var(--rounded);
  overflow-x: auto;
}

.k-meta-kit-table table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

.k-meta-kit-table th:first-child,
.k-meta-kit-table td:first-child {
  width: 50px;
  text-align: center;
}

.k-meta-kit-table th:nth-child(2) {
  min-width: 200px;
}

.k-meta-kit-table th:nth-child(3) {
  width: 120px;
}

.k-meta-kit-table th:nth-child(4),
.k-meta-kit-table th:nth-child(5) {
  width: 100px;
}

.k-meta-kit-table th:nth-child(6),
.k-meta-kit-table th:nth-child(7),
.k-meta-kit-table th:nth-child(8) {
  width: 80px;
}

.k-meta-kit-table th {
  background: var(--color-back);
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
  border-bottom: 1px solid var(--color-border);
}

.k-meta-kit-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border);
  font-size: 0.875rem;
  color: var(--color-text);
  background: var(--color-back);
}

.k-meta-kit-table tbody tr:last-child td {
  border-bottom: none;
}

.k-meta-kit-table tbody tr:hover td {
  background: var(--color-back);
}

.k-meta-kit-table-center {
  text-align: center;
}

.k-meta-kit-table .k-link {
  color: var(--color-blue-600);
  text-decoration: none;
}

.k-meta-kit-table .k-link:hover {
  text-decoration: underline;
}

.k-meta-kit-status-success {
  color: var(--color-green-600);
  font-weight: 600;
}

.k-meta-kit-status-warning {
  color: var(--color-orange-600);
  font-weight: 600;
}

.k-meta-kit-icon-success {
  color: var(--color-green-600);
}

.k-meta-kit-icon-warning {
  color: var(--color-orange-600);
}

.k-meta-kit-loading,
.k-meta-kit-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  text-align: center;
  color: var(--color-gray-600);
}

.k-meta-kit-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.k-meta-kit-legacy-list {
  padding: 1rem;
}

.k-meta-kit-legacy-item {
  padding: 1rem;
  margin-bottom: 1rem;
  background: var(--color-back);
  border-radius: var(--rounded);
}

.k-meta-kit-legacy-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.k-meta-kit-legacy-item-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.k-meta-kit-legacy-field {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 1rem;
  align-items: start;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.k-meta-kit-legacy-field:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.k-meta-kit-legacy-field-label {
  font-weight: 600;
  color: var(--color-gray-600);
  font-size: 0.875rem;
  padding-top: 0.5rem;
}

.k-meta-kit-legacy-field-values {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.k-meta-kit-legacy-field-old,
.k-meta-kit-legacy-field-new {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.k-meta-kit-legacy-badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  background: var(--color-blue-100);
  color: var(--color-blue-700);
  border-radius: var(--rounded-xs);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  width: fit-content;
}

.k-meta-kit-legacy-badge-warning {
  background: var(--color-orange-100);
  color: var(--color-orange-700);
}

.k-meta-kit-legacy-badge-new {
  background: var(--color-green-100);
  color: var(--color-green-700);
}

.k-meta-kit-legacy-field-value,
.k-meta-kit-legacy-field-value-current {
  color: var(--color-text);
  font-size: 0.875rem;
  word-break: break-word;
  line-height: 1.5;
  padding: 0.5rem;
  background: var(--color-background);
  border-radius: var(--rounded-xs);
  border: 1px solid var(--color-border);
}

.k-meta-kit-legacy-field-value-current {
  text-decoration: line-through;
  opacity: 0.7;
}

.k-meta-kit-legacy-field-value-empty {
  color: var(--color-gray-500);
  font-size: 0.875rem;
  font-style: italic;
  padding: 0.5rem;
}

.k-tag {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  background: var(--color-back);
  color: var(--color-blue-700);
  border-radius: var(--rounded-xs);
  font-size: 0.75rem;
  font-weight: 500;
}

/* Dark mode support */
.k-panel[data-color-scheme="dark"] .k-meta-kit-stats-card {
  background: var(--color-black);
  border-color: var(--color-border);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-item {
  background: var(--color-black);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-table {
  background: var(--color-black);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-table th {
  background: var(--color-back);
  color: var(--color-text);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-table td {
  background: var(--color-black);
  color: var(--color-text);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-table tbody tr:hover td {
  background: var(--color-back);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-table .k-link {
  color: var(--color-blue-400);
}

.k-panel[data-color-scheme="dark"] .k-tag {
  background: var(--color-back);
  color: var(--color-blue-300);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-label {
  color: var(--color-gray-400);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value,
.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value-current {
  color: var(--color-text);
  background: var(--color-black);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge {
  background: var(--color-blue-900);
  color: var(--color-blue-300);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge-warning {
  background: var(--color-orange-900);
  color: var(--color-orange-300);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge-new {
  background: var(--color-green-900);
  color: var(--color-green-300);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value-empty {
  color: var(--color-gray-500);
}
</style>
