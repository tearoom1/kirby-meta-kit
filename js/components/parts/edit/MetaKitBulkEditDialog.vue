<template>
  <k-dialog ref="dialog" size="huge" cancelButton="Close" submitButton="">
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
            :value="editedFields[page.id].metaTitle"
            @input="editedFields[page.id].metaTitle = $event"
            :page-id="page.id"
            :page-title="page.title"
            :site-settings="siteSettings"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].metaTitle"
            @generate="generate(page.id, 'metaTitle')"
            :placeholder="page.metaTitle || 'No meta title'"
          />

          <!-- Meta Description -->
          <meta-kit-description-field
            :value="editedFields[page.id].metaDescription"
            @input="editedFields[page.id].metaDescription = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].metaDescription"
            @generate="generate(page.id, 'metaDescription')"
            :placeholder="page.metaDescription || 'No meta description'"
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
          <meta-kit-og-title-field
            :value="editedFields[page.id].ogTitle"
            @input="editedFields[page.id].ogTitle = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].ogTitle"
            @generate="generate(page.id, 'ogTitle')"
            :placeholder="page.ogTitle || 'No OG title'"
          />

          <!-- OG Description -->
          <meta-kit-og-description-field
            :value="editedFields[page.id].ogDescription"
            @input="editedFields[page.id].ogDescription = $event"
            :ai-enabled="aiEnabled"
            :is-generating="generating[page.id].ogDescription"
            @generate="generate(page.id, 'ogDescription')"
            :placeholder="page.ogDescription || 'No OG description'"
          />
        </div>
      </div>

      <!-- Actions -->
      <div v-if="hasAnyChanges" class="k-meta-kit-single-actions">
        <k-button icon="check" theme="positive" @click="saveAll">Apply All Changes</k-button>
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
import MetaKitOgTitleField from '../field/MetaKitOgTitleField.vue';
import MetaKitOgDescriptionField from '../field/MetaKitOgDescriptionField.vue';

export default {
  components: {
    MetaKitTitleField,
    MetaKitDescriptionField,
    MetaKitOgTitleField,
    MetaKitOgDescriptionField
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
      generating: {}
    };
  },
  computed: {
    hasAnyChanges() {
      return this.pages.some(page => {
        const edited = this.editedFields[page.id];
        if (!edited) return false;

        return edited.metaTitle !== (page.metaTitle || '') ||
          edited.metaDescription !== (page.metaDescription || '') ||
          edited.ogTitle !== (page.ogTitle || '') ||
          edited.ogDescription !== (page.ogDescription || '');
      });
    }
  },
  methods: {
    async open(pageIds) {
      this.isLoading = true;
      this.activeTab = 'meta';
      this.$refs.dialog.open();

      try {
        const response = await this.api.get('meta-kit/pages-with-content', { pageIds });
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
      this.$refs.dialog.close();
      this.pages = [];
      this.editedFields = {};
      this.generating = {};
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
              await this.api.post('meta-kit/apply-single-field', {
                pageId: page.id,
                fieldName: field.name,
                value: field.value
              });
              totalSaved++;
            } catch (error) {
              window.panel.notification.error(`Failed to update ${field.name} for ${page.title}`);
            }
          }
        }
      }

      if (totalSaved > 0) {
        window.panel.notification.success(`Updated ${totalSaved} field${totalSaved > 1 ? 's' : ''} across ${this.pages.length} page${this.pages.length > 1 ? 's' : ''}`);
        this.$emit('saved');
        this.close();
      }
    }
  }
};
</script>
