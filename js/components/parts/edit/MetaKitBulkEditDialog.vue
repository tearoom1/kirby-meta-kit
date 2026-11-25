<template>
  <k-dialog :visible="visible" class="k-mk2-dialog k-meta-kit-dialog k-meta-kit-dialog-bulk" size="huge" :cancel-button="false" :submit-button="false" @submit="saveAll">
    <k-headline>{{ $t('meta-kit.bulkEdit.title', { count: pages.length }) }}</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>{{ $t('meta-kit.loading.pages') }}</span>
    </div>

    <div v-else-if="pages.length > 0">
      <!-- Tabs -->
      <div class="k-meta-kit-tabs">
        <k-button
          :class="activeTab === 'meta' ? 'active' : ''"
          @click="activeTab = 'meta'"
          size="sm"
        >
          {{ $t('meta-kit.bulkEdit.tab.meta') }}
        </k-button>
        <k-button
          :class="activeTab === 'og' ? 'active' : ''"
          @click="activeTab = 'og'"
          size="sm"
        >
          {{ $t('meta-kit.bulkEdit.tab.og') }}
        </k-button>
      </div>

      <!-- Meta Tab -->
      <div v-if="activeTab === 'meta'" class="k-meta-kit-dialog-table-wrapper">
        <div v-for="page in pages" :key="`meta-${page.id}`" class="k-meta-kit-dialog-table-page">
          <div class="k-meta-kit-dialog-page-info">
            <h2><a :href="page.panelUrl" class="k-meta-kit-page-id">{{ page.title }}</a></h2>
            <a :href="page.panelUrl" class="k-meta-kit-page-id">{{ page.id }}</a>
          </div>

          <!-- Meta Title -->
          <meta-kit-title-field
            :template="page.template"
            :validation-settings="validationSettings"
            :label="$t('meta-kit.field.metaTitle')"
            :value="editedFields[page.id].metaTitle"
            @input="editedFields[page.id].metaTitle = $event"
            :page-id="page.id"
            :page-title="page.title"
            :site-settings="siteSettings"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].metaTitle"
            @generate="generate(page.id, 'metaTitle')"
            :placeholder="page.metaTitle || page.title || $t('meta-kit.noMetaTitle')"
            type="meta"
          />

          <!-- Meta Description -->
          <meta-kit-description-field
            :template="page.template"
            :validation-settings="validationSettings"
            :label="$t('meta-kit.field.metaDescription')"
            :value="editedFields[page.id].metaDescription"
            @input="editedFields[page.id].metaDescription = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].metaDescription"
            @generate="generate(page.id, 'metaDescription')"
            :placeholder="page.metaDescription || siteSettings.siteMetaDescription || $t('meta-kit.noMetaDescription')"
            :rows="3"
          />
        </div>
      </div>

      <!-- OG Tab -->
      <div v-if="activeTab === 'og'" class="k-meta-kit-dialog-table-wrapper">
        <div v-for="page in editableOgPages" :key="`og-${page.id}`" class="k-meta-kit-dialog-table-page">
          <div class="k-meta-kit-dialog-page-info">
            <h2><a :href="page.panelUrl" class="k-meta-kit-page-id">{{ page.title }}</a></h2>
            <a :href="page.panelUrl" class="k-meta-kit-page-id">{{ page.id }}</a>
          </div>

          <!-- OG Title -->
          <meta-kit-title-field
            :template="page.template"
            :validation-settings="validationSettings"
            :label="$t('meta-kit.field.ogTitle')"
            :value="editedFields[page.id].ogTitle"
            @input="editedFields[page.id].ogTitle = $event"
            :page-id="page.id"
            :page-title="page.title"
            :meta-title="page.metaTitle || editedFields[page.id].metaTitle"
            :site-settings="siteSettings"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].ogTitle"
            @generate="generate(page.id, 'ogTitle')"
            :placeholder="page.ogTitle || page.metaTitle || page.title || $t('meta-kit.noOgTitle')"
            type="og"
          />

          <!-- OG Description -->
          <meta-kit-description-field
            :template="page.template"
            :validation-settings="validationSettings"
            :label="$t('meta-kit.field.ogDescription')"
            :value="editedFields[page.id].ogDescription"
            @input="editedFields[page.id].ogDescription = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].ogDescription"
            @generate="generate(page.id, 'ogDescription')"
            :placeholder="page.ogDescription || page.metaDescription || siteSettings.siteMetaDescription || $t('meta-kit.noOgDescription')"
            type="og"
            :rows="3"
          />
        </div>
        <div v-if="editableOgPages.length === 0" class="k-meta-kit-empty">
          <p>{{ $t('meta-kit.bulkEdit.noOg') }}</p>
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
          <k-button @click="close">{{ $t('meta-kit.close') }}</k-button>
          <k-button v-if="hasAnyChanges" icon="check" theme="positive" @click="saveAll">{{ $t('meta-kit.bulkEdit.apply') }}</k-button>
        </div>
      </div>
    </div>

    <div v-else class="k-meta-kit-empty">
      <k-icon type="check"/>
      <p>{{ $t('meta-kit.bulkEdit.none') }}</p>
    </div>
  </k-dialog>
</template>

<script>
// Opened with openDialog(panel, 'bulk-edit', { pageIds, … }, { saved })
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
    pageIds: {
      type: Array,
      default: () => []
    },
    validationSettings: {
      type: Object,
      default: () => ({})
    },
    siteSettings: {
      type: Object,
      required: true
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    visible: {
      type: Boolean,
      default: false
    }
  },
  // The Panel's own dialog listeners (submit, input …) must not reach <k-dialog>
  inheritAttrs: false,
  emits: ['cancel', 'close', 'saved'],
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
    },
    editableOgPages() {
      return this.pages.filter(page => page.id !== 'site');
    }
  },
  created() {
    this.load();
  },
  methods: {
    async load() {
      this.isLoading = true;

      try {
        const response = await this.$api.get('meta-kit/pages-with-content', {
          pageIds: this.pageIds.join(',')
        });
        if (response.status === 'success' && response.data) {
          this.pages = response.data;

          // Initialize edited fields and generating state for each page
          this.editedFields = {};
          this.generating = {};

          this.pages.forEach(page => {
            this.editedFields[page.id] = {
              metaTitle: page.metaTitle || '',
              metaDescription: page.metaDescription || '',
              ogTitle: page.ogTitle || '',
              ogDescription: page.ogDescription || ''
            };

            this.generating[page.id] = {
              metaTitle: false,
              metaDescription: false,
              ogTitle: false,
              ogDescription: false
            };
          });
        }
      } catch (error) {
        window.panel.notification.error(this.$t('meta-kit.error.loadPages'));
      } finally {
        this.isLoading = false;
      }
    },

    close() {
      this.$panel.dialog.close();
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

    async generate(pageId, fieldName) {
      if (!this.generating[pageId]) return;

      this.generating[pageId][fieldName] = true;
      try {
        const response = await this.$api.post('meta-kit/generate-field', {
          pageId,
          fieldName
        });
        if (response.status === 'success' && response.content) {
          this.editedFields[pageId][fieldName] = response.content;
          window.panel.notification.success(this.$t('meta-kit.generated'));
        } else {
          window.panel.notification.error(response.message || this.$t('meta-kit.generate.contentFailed'));
        }
      } catch (error) {
        window.panel.notification.error(error?.message || this.$t('meta-kit.generate.contentFailed'));
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
          { name: 'metaDescription', value: edited.metaDescription, original: page.metaDescription || '' }
        ];

        if (page.id !== 'site') {
          fields.push(
            { name: 'ogTitle', value: edited.ogTitle, original: page.ogTitle || '' },
            { name: 'ogDescription', value: edited.ogDescription, original: page.ogDescription || '' }
          );
        }

        for (const field of fields) {
          if (field.value !== field.original) {
            try {
              const response = await applySingleFieldUpdate(this.$api, {
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
              this.setSaveFeedback('error', error?.message || this.$t('meta-kit.error.updateField', { field: field.name, page: page.title }));
              window.panel.notification.error(error?.message || this.$t('meta-kit.error.updateField', { field: field.name, page: page.title }));
            }
          }
        }
      }

      if (totalSaved > 0) {
        this.pages = this.pages.map(page => updatedPages.get(page.id) || page);

        this.pages.forEach(page => {
          if (!this.editedFields[page.id]) {
            return;
          }

          this.editedFields[page.id] = {
            metaTitle: page.metaTitle || '',
            metaDescription: page.metaDescription || '',
            ogTitle: page.ogTitle || '',
            ogDescription: page.ogDescription || ''
          };
        });

        this.setSaveFeedback('success', this.$t('meta-kit.bulkEdit.saved', { count: totalSaved, pages: this.pages.length }));
        this.$emit('saved', {
          pages: this.pages,
          siteSettings: latestSiteSettings
        });
      }
    }
  },
  // Cmd+S submits the dialog's form, which calls saveAll()
  beforeUnmount() {
    this.resetSaveFeedback();
  }
};
</script>
