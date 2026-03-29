<template>
  <k-dialog ref="dialog" size="large" cancelButton="Close" submitButton="" @submit.prevent="save">
    <k-headline v-if="page">Edit: {{ page.title }}</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Loading page...</span>
    </div>

    <div v-else-if="page" class="k-meta-kit-single-edit">
      <!-- Meta Title -->
      <div class="k-meta-kit-single-field">
        <meta-kit-title-field
          label="Meta Title"
          :value="editedFields.metaTitle"
          @input="editedFields.metaTitle = $event"
          :page-id="page.id"
          :page-title="page.title"
          :site-settings="siteSettings"
          :ai-enabled="aiEnabled"
          :is-generating="generating.metaTitle"
          @generate="generate('metaTitle')"
          :placeholder="page.metaTitle || page.title || 'No meta title set'"
          button-size="sm"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- Meta Description -->
      <div class="k-meta-kit-single-field">
        <meta-kit-description-field
          label="Meta Description"
          :value="editedFields.metaDescription"
          @input="editedFields.metaDescription = $event"
          :ai-enabled="aiEnabled"
          :is-generating="generating.metaDescription"
          @generate="generate('metaDescription')"
          :placeholder="page.metaDescription || siteSettings.siteMetaDescription || 'No meta description set'"
          button-size="sm"
          :rows="3"
          buttons="false"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Title -->
      <div class="k-meta-kit-single-field">
        <meta-kit-title-field
          label="OG Title"
          :value="editedFields.ogTitle"
          @input="editedFields.ogTitle = $event"
          :page-id="page.id"
          :page-title="page.title"
          :meta-title="page.metaTitle || editedFields.metaTitle"
          :site-settings="siteSettings"
          :ai-enabled="aiEnabled"
          :is-generating="generating.ogTitle"
          @generate="generate('ogTitle')"
          :placeholder="page.ogTitle || page.metaTitle || page.title || 'No OG title'"
          type="og"
          button-size="sm"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Description -->
      <div class="k-meta-kit-single-field">
        <meta-kit-description-field
          label="OG Description"
          :value="editedFields.ogDescription"
          @input="editedFields.ogDescription = $event"
          :ai-enabled="aiEnabled"
          :is-generating="generating.ogDescription"
          @generate="generate('ogDescription')"
          :placeholder="page.ogDescription || page.metaDescription || siteSettings.siteMetaDescription || 'No OG description'"
          type="og"
          button-size="sm"
          :rows="3"
          buttons="false"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Image -->
      <div class="k-meta-kit-single-field">
        <label class="k-meta-kit-dialog-field-label">OG Image</label>
        <div class="k-meta-kit-single-field-content">
          <div v-if="page.ogImage" class="k-meta-kit-og-image-current">
            <img :src="page.ogImage.url" :alt="page.ogImage.filename"/>
            <span class="k-meta-kit-og-image-filename">{{ page.ogImage.filename }}</span>
          </div>
          <div v-else class="k-meta-kit-og-image-empty">
            No OG image set
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="k-meta-kit-single-actions">
        <k-button icon="open" @click="editInPanel">Edit in Panel</k-button>
        <k-button v-if="hasChanges" icon="check" theme="positive" @click="save">
          Save {{ changedFieldCount }} {{ changedFieldCount === 1 ? 'Field' : 'Fields' }}
        </k-button>
      </div>
    </div>
  </k-dialog>
</template>

<script>
import MetaKitTitleField from '../field/MetaKitTitleField.vue';
import MetaKitDescriptionField from '../field/MetaKitDescriptionField.vue';
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
      page: null,
      isLoading: false,
      editedFields: {
        metaTitle: '',
        metaDescription: '',
        ogTitle: '',
        ogDescription: ''
      },
      generating: {
        metaTitle: false,
        metaDescription: false,
        ogTitle: false,
        ogDescription: false
      }
    };
  },
  computed: {
    changedFields() {
      if (!this.page) return [];
      const fields = ['metaTitle', 'metaDescription', 'ogTitle', 'ogDescription'];
      return fields.filter(f => this.editedFields[f] !== (this.page[f] || ''));
    },
    changedFieldCount() {
      return this.changedFields.length;
    },
    hasChanges() {
      return this.changedFieldCount > 0;
    }
  },
  methods: {
    async open(pageId) {
      this.isLoading = true;
      this.$refs.dialog.open();

      try {
        const response = await this.api.get('meta-kit/single-page', { pageId });
        if (response.status === 'success') {
          this.page = response.data;
          this.editedFields.metaTitle = this.page.metaTitle || '';
          this.editedFields.metaDescription = this.page.metaDescription || '';
          this.editedFields.ogTitle = this.page.ogTitle || '';
          this.editedFields.ogDescription = this.page.ogDescription || '';
        }
      } catch (error) {
        window.panel.notification.error('Failed to load page');
      } finally {
        this.isLoading = false;
      }
    },

    close() {
      this.$refs.dialog.close();
      this.page = null;
    },

    async generate(fieldName) {
      if (!this.page) return;

      this.generating[fieldName] = true;
      try {
        const response = await this.api.post('meta-kit/generate-field', {
          pageId: this.page.id,
          fieldName
        });
        if (response.status === 'success' && response.content) {
          this.editedFields[fieldName] = response.content;
          window.panel.notification.success('AI content generated successfully');
        } else {
          window.panel.notification.error(response.message || 'Failed to generate content');
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate content');
      } finally {
        this.generating[fieldName] = false;
      }
    },

    async save() {
      if (!this.page || !this.hasChanges) return;

      const changedFields = this.changedFields.map(name => ({
        name,
        value: this.editedFields[name]
      }));
      const successfulResponses = [];
      const failedResults = [];

      for (const field of changedFields) {
        try {
          const response = await applySingleFieldUpdate(this.api, {
            pageId: this.page.id,
            fieldName: field.name,
            value: field.value
          });
          successfulResponses.push(response);
        } catch (error) {
          failedResults.push(error);
        }
      }

      const savedCount = successfulResponses.length;

      if (failedResults.length > 0) {
        const firstError = failedResults[0]?.message;
        window.panel.notification.error(
          firstError || `Failed to update ${failedResults.length} field${failedResults.length > 1 ? 's' : ''}`
        );
      }

      if (savedCount > 0) {
        const latestResponse = successfulResponses[successfulResponses.length - 1];
        const latestPage = latestResponse?.data?.page || null;
        const latestSiteSettings = latestResponse?.data?.siteSettings || null;

        window.panel.notification.success(`Updated ${savedCount} field${savedCount > 1 ? 's' : ''}`);
        if (latestPage) {
          this.page = latestPage;
          this.editedFields.metaTitle = this.page.metaTitle || '';
          this.editedFields.metaDescription = this.page.metaDescription || '';
          this.editedFields.ogTitle = this.page.ogTitle || '';
          this.editedFields.ogDescription = this.page.ogDescription || '';
        }

        this.$emit('saved', {
          page: latestPage,
          siteSettings: latestSiteSettings
        });
      }
    },

    editInPanel() {
      if (this.page && this.page.panelUrl) {
        this.close();
        window.panel.view.open(this.page.panelUrl);
      }
    }
  }
};
</script>
