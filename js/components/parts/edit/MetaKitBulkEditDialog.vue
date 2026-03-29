<template>
  <k-dialog ref="dialog" size="huge" :cancel-button="false" submitButton="" @submit.prevent="saveAll">
    <k-headline>Edit Selected Pages ({{ pages.length }})</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Loading pages...</span>
    </div>

    <div v-else-if="pages.length > 0">
      <!-- Tabs -->
      <div class="k-meta-kit-tabs">
        <k-button
          :theme="activeTab === 'meta' ? 'positive' : ''"
          @click="activeTab = 'meta'"
          size="sm"
        >
          Meta Tags
        </k-button>
        <k-button
          :theme="activeTab === 'og' ? 'positive' : ''"
          @click="activeTab = 'og'"
          size="sm"
        >
          Social Media (OG)
        </k-button>
      </div>

      <!-- Meta Tab -->
      <div v-if="activeTab === 'meta'" class="k-meta-kit-dialog-table-wrapper">
        <div v-for="page in pages" :key="`meta-${page.id}`" class="k-meta-kit-dialog-table-page">
          <div class="k-meta-kit-dialog-page-info">
            <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
            <a :href="page.panelUrl" class="k-link k-meta-kit-page-id">{{ page.id }}</a>
          </div>

          <!-- Meta Title -->
          <meta-kit-title-field
            label="Meta Title"
            :value="editedFields[page.id].metaTitle"
            @input="editedFields[page.id].metaTitle = $event"
            :page-id="page.id"
            :page-title="page.title"
            :site-settings="siteSettings"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].metaTitle"
            @generate="generate(page.id, 'metaTitle')"
            :placeholder="page.metaTitle || page.title || 'No meta title'"
            type="meta"
          />

          <!-- Meta Description -->
          <meta-kit-description-field
            label="Meta Description"
            :value="editedFields[page.id].metaDescription"
            @input="editedFields[page.id].metaDescription = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].metaDescription"
            @generate="generate(page.id, 'metaDescription')"
            :placeholder="page.metaDescription || siteSettings.siteMetaDescription || 'No meta description'"
            :rows="3"
          />
        </div>
      </div>

      <!-- OG Tab -->
      <div v-if="activeTab === 'og'" class="k-meta-kit-dialog-table-wrapper">
        <div v-for="page in pages" :key="`og-${page.id}`" class="k-meta-kit-dialog-table-page">
          <div class="k-meta-kit-dialog-page-info">
            <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
            <a :href="page.panelUrl" class="k-link k-meta-kit-page-id">{{ page.id }}</a>
          </div>

          <!-- OG Title -->
          <meta-kit-title-field
            label="OG Title"
            :value="editedFields[page.id].ogTitle"
            @input="editedFields[page.id].ogTitle = $event"
            :page-id="page.id"
            :page-title="page.title"
            :meta-title="page.metaTitle || editedFields[page.id].metaTitle"
            :site-settings="siteSettings"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].ogTitle"
            @generate="generate(page.id, 'ogTitle')"
            :placeholder="page.ogTitle || page.metaTitle || page.title || 'No OG title'"
            type="og"
          />

          <!-- OG Description -->
          <meta-kit-description-field
            label="OG Description"
            :value="editedFields[page.id].ogDescription"
            @input="editedFields[page.id].ogDescription = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].ogDescription"
            @generate="generate(page.id, 'ogDescription')"
            :placeholder="page.ogDescription || page.metaDescription || siteSettings.siteMetaDescription || 'No OG description'"
            type="og"
            :rows="3"
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="k-meta-kit-dialog-footer">
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-start"></div>
        <div class="k-meta-kit-dialog-footer-meta">
          <span
            v-if="saveFeedback.text"
            :class="['k-meta-kit-dialog-feedback', `k-meta-kit-dialog-feedback-${saveFeedback.type}`]"
          >
            {{ saveFeedback.text }}
          </span>
        </div>
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-end">
          <k-button @click="close">Close</k-button>
          <k-button v-if="hasAnyChanges" icon="check" theme="positive" @click="saveAll">Apply All Changes</k-button>
        </div>
      </div>
    </div>

    <div v-else class="k-meta-kit-empty">
      <k-icon type="check"/>
      <p>No pages selected!</p>
    </div>
  </k-dialog>
</template>

<script>
import MetaKitTitleField from '../field/MetaKitTitleField.vue';
import MetaKitDescriptionField from '../field/MetaKitDescriptionField.vue';
import { hasAnyBulkChanges } from '../../../composables/panelState.js';
import { applySingleFieldUpdate } from '../../../composables/saveFields.js';

export default {
  components: {
    MetaKitTitleField,
    MetaKitDescriptionField
  },
  props: {
    api: {
      type: Object,
      required: true
    },
    siteSettings: {
      type: Object,
      required: true
    },
    aiEnabled: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      pages: [],
      isLoading: false,
      activeTab: 'meta',
      editedFields: {},
      generating: {},
      saveFeedback: {
        type: '',
        text: ''
      },
      saveFeedbackTimer: null
    };
  },
  computed: {
    hasAnyChanges() {
      return hasAnyBulkChanges(this.pages, this.editedFields);
    }
  },
  methods: {
    async open(pageIds) {
      this.isLoading = true;
      this.activeTab = 'meta';
      this.resetSaveFeedback();
      this.$refs.dialog.open();
      document.addEventListener('keydown', this.handleKeydown);

      try {
        const response = await this.api.get('meta-kit/pages-with-content', {
          pageIds: Array.isArray(pageIds) ? pageIds.join(',') : pageIds
        });
        if (response.status === 'success' && response.data) {
          this.pages = response.data;

          // Initialize edited fields and generating state for each page
          this.editedFields = {};
          this.generating = {};

          this.pages.forEach(page => {
            this.$set(this.editedFields, page.id, {
              metaTitle: page.metaTitle || '',
              metaDescription: page.metaDescription || '',
              ogTitle: page.ogTitle || '',
              ogDescription: page.ogDescription || ''
            });

            this.$set(this.generating, page.id, {
              metaTitle: false,
              metaDescription: false,
              ogTitle: false,
              ogDescription: false
            });
          });
        }
      } catch (error) {
        window.panel.notification.error('Failed to load pages');
      } finally {
        this.isLoading = false;
      }
    },

    close() {
      document.removeEventListener('keydown', this.handleKeydown);
      this.resetSaveFeedback();
      this.$refs.dialog.close();
      this.pages = [];
      this.editedFields = {};
      this.generating = {};
    },

    resetSaveFeedback() {
      if (this.saveFeedbackTimer) {
        clearTimeout(this.saveFeedbackTimer);
        this.saveFeedbackTimer = null;
      }

      this.saveFeedback = { type: '', text: '' };
    },

    setSaveFeedback(type, text) {
      this.resetSaveFeedback();
      this.saveFeedback = { type, text };
      this.saveFeedbackTimer = setTimeout(() => {
        this.saveFeedback = { type: '', text: '' };
        this.saveFeedbackTimer = null;
      }, 3200);
    },

    handleKeydown(event) {
      if (!(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 's') {
        return;
      }

      event.preventDefault();

      if (this.pages.length > 0 && this.hasAnyChanges) {
        this.saveAll();
      }
    },

    async generate(pageId, fieldName) {
      if (!this.generating[pageId]) return;

      this.generating[pageId][fieldName] = true;
      try {
        const response = await this.api.post('meta-kit/generate-field', {
          pageId,
          fieldName
        });
        if (response.status === 'success' && response.content) {
          this.editedFields[pageId][fieldName] = response.content;
          window.panel.notification.success('AI content generated successfully');
        } else {
          window.panel.notification.error(response.message || 'Failed to generate content');
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate content');
      } finally {
        this.generating[pageId][fieldName] = false;
      }
    },

    async saveAll() {
      if (!this.hasAnyChanges) return;

      let totalSaved = 0;
      const updatedPages = new Map();
      let latestSiteSettings = null;

      for (const page of this.pages) {
        const edited = this.editedFields[page.id];
        const fields = [
          { name: 'metaTitle', value: edited.metaTitle, original: page.metaTitle || '' },
          { name: 'metaDescription', value: edited.metaDescription, original: page.metaDescription || '' },
          { name: 'ogTitle', value: edited.ogTitle, original: page.ogTitle || '' },
          { name: 'ogDescription', value: edited.ogDescription, original: page.ogDescription || '' }
        ];

        for (const field of fields) {
          if (field.value !== field.original) {
            try {
              const response = await applySingleFieldUpdate(this.api, {
                pageId: page.id,
                fieldName: field.name,
                value: field.value
              });
              if (response?.data?.page) {
                updatedPages.set(response.data.page.id, response.data.page);
              }
              if (response?.data?.siteSettings) {
                latestSiteSettings = response.data.siteSettings;
              }
              totalSaved++;
            } catch (error) {
              this.setSaveFeedback('error', error?.message || `Failed to update ${field.name} for ${page.title}`);
              window.panel.notification.error(error?.message || `Failed to update ${field.name} for ${page.title}`);
            }
          }
        }
      }

      if (totalSaved > 0) {
        this.setSaveFeedback('success', `Saved ${totalSaved} field${totalSaved > 1 ? 's' : ''} across ${this.pages.length} page${this.pages.length > 1 ? 's' : ''}`);
        this.$emit('saved', {
          pages: Array.from(updatedPages.values()),
          siteSettings: latestSiteSettings
        });
      }
    }
  },
  beforeDestroy() {
    document.removeEventListener('keydown', this.handleKeydown);
    this.resetSaveFeedback();
  }
};
</script>
