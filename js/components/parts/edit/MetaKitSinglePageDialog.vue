<template>
  <k-dialog :visible="visible" size="large" class="k-mk2-dialog k-meta-kit-dialog" :cancel-button="false" :submit-button="false" @submit="save">
    <k-headline v-if="page">{{ $t('meta-kit.edit.title', { page: page.title }) }}</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>{{ $t('meta-kit.loading.page') }}</span>
    </div>

    <div v-else-if="page" class="k-meta-kit-single-edit">
      <!-- Meta Title -->
      <div class="k-meta-kit-single-field">
        <meta-kit-title-field
            :template="page.template"
            :validation-settings="validationSettings"
          :label="$t('meta-kit.field.metaTitle')"
          :value="editedFields.metaTitle"
          @input="editedFields.metaTitle = $event"
          :page-id="page.id"
          :page-title="page.title"
          :site-settings="siteSettings"
          :ai-enabled="aiEnabled"
          :is-generating="generating.metaTitle"
          @generate="generate('metaTitle')"
          :placeholder="page.metaTitle || page.title || $t('meta-kit.noMetaTitle')"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- Meta Description -->
      <div class="k-meta-kit-single-field">
        <meta-kit-description-field
            :template="page.template"
            :validation-settings="validationSettings"
          :label="$t('meta-kit.field.metaDescription')"
          :value="editedFields.metaDescription"
          @input="editedFields.metaDescription = $event"
          :ai-enabled="aiEnabled"
          :is-generating="generating.metaDescription"
          @generate="generate('metaDescription')"
          :placeholder="page.metaDescription || siteSettings.siteMetaDescription || $t('meta-kit.noMetaDescription')"
          :rows="3"
          buttons="false"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Title -->
      <div v-if="!isSitePage" class="k-meta-kit-single-field">
        <meta-kit-title-field
            :template="page.template"
            :validation-settings="validationSettings"
          :label="$t('meta-kit.field.ogTitle')"
          :value="editedFields.ogTitle"
          @input="editedFields.ogTitle = $event"
          :page-id="page.id"
          :page-title="page.title"
          :meta-title="page.metaTitle || editedFields.metaTitle"
          :site-settings="siteSettings"
          :ai-enabled="aiEnabled"
          :is-generating="generating.ogTitle"
          @generate="generate('ogTitle')"
          :placeholder="page.ogTitle || page.metaTitle || page.title || $t('meta-kit.noOgTitle')"
          type="og"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Description -->
      <div v-if="!isSitePage" class="k-meta-kit-single-field">
        <meta-kit-description-field
            :template="page.template"
            :validation-settings="validationSettings"
          :label="$t('meta-kit.field.ogDescription')"
          :value="editedFields.ogDescription"
          @input="editedFields.ogDescription = $event"
          :ai-enabled="aiEnabled"
          :is-generating="generating.ogDescription"
          @generate="generate('ogDescription')"
          :placeholder="page.ogDescription || page.metaDescription || siteSettings.siteMetaDescription || $t('meta-kit.noOgDescription')"
          type="og"
          :rows="3"
          buttons="false"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Image -->
      <div v-if="!isSitePage" class="k-meta-kit-single-field">
        <label class="k-meta-kit-dialog-field-label">{{ $t('meta-kit.field.ogImage') }}</label>
        <div class="k-meta-kit-single-field-content">
          <div v-if="page.ogImage" class="k-meta-kit-og-image-current">
            <img :src="page.ogImage.url" :alt="page.ogImage.filename"/>
            <span class="k-meta-kit-og-image-filename">{{ page.ogImage.filename }}</span>
          </div>
          <div v-else class="k-meta-kit-og-image-empty">
            {{ siteSettings && siteSettings.siteHasOgImage ? $t('meta-kit.ogImage.site') : $t('meta-kit.noOgImage') }}
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="k-meta-kit-dialog-footer">
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-start">
          <a
            v-if="page && page.panelUrl"
            :href="page.panelUrl"
            class="k-link k-meta-kit-dialog-panel-link"
          >
            {{ $t('meta-kit.editInPanel') }}
          </a>
        </div>
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
          <k-button v-if="hasChanges" icon="check" theme="positive" @click="save">
            {{ $t('meta-kit.saveFields', { count: changedFieldCount }) }}
          </k-button>
        </div>
      </div>
    </div>
  </k-dialog>
</template>

<script>
// Opened with openDialog(panel, 'single-page', { pageId, … }, { saved })
import MetaKitTitleField from '../field/MetaKitTitleField.vue';
import MetaKitDescriptionField from '../field/MetaKitDescriptionField.vue';
import { applySingleFieldUpdate } from '../../../composables/saveFields.js';

export default {
  components: {
    MetaKitTitleField,
    MetaKitDescriptionField
  },
  props: {
    pageId: {
      type: String,
      required: true
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
      },
      saveFeedback: {
        type: '',
        text: ''
      },
      saveFeedbackTimer: null
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
    },
    isSitePage() {
      return this.page?.id === 'site';
    }
  },
  created() {
    this.load();
  },
  methods: {
    async load() {
      this.isLoading = true;

      try {
        const response = await this.$api.get('meta-kit/single-page', { pageId: this.pageId });
        if (response.status === 'success') {
          this.page = response.data;
          this.editedFields.metaTitle = this.page.metaTitle || '';
          this.editedFields.metaDescription = this.page.metaDescription || '';
          this.editedFields.ogTitle = this.page.ogTitle || '';
          this.editedFields.ogDescription = this.page.ogDescription || '';
        }
      } catch (error) {
        window.panel.notification.error(this.$t('meta-kit.error.loadPage'));
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

    async generate(fieldName) {
      if (!this.page) return;

      this.generating[fieldName] = true;
      try {
        const response = await this.$api.post('meta-kit/generate-field', {
          pageId: this.page.id,
          fieldName
        });
        if (response.status === 'success' && response.content) {
          this.editedFields[fieldName] = response.content;
          window.panel.notification.success(this.$t('meta-kit.generated'));
        } else {
          window.panel.notification.error(response.message || this.$t('meta-kit.generate.contentFailed'));
        }
      } catch (error) {
        window.panel.notification.error(error?.message || this.$t('meta-kit.generate.contentFailed'));
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
          const response = await applySingleFieldUpdate(this.$api, {
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
        this.setSaveFeedback('error', firstError || this.$t('meta-kit.error.updateFields', { count: failedResults.length }));
        window.panel.notification.error(
          firstError || this.$t('meta-kit.error.updateFields', { count: failedResults.length })
        );
      }

      if (savedCount > 0) {
        const latestResponse = successfulResponses[successfulResponses.length - 1];
        const latestPage = latestResponse?.data?.page || null;
        const latestSiteSettings = latestResponse?.data?.siteSettings || null;

        this.setSaveFeedback('success', this.$t('meta-kit.generate.saved', { count: savedCount }));
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
  },
  // Cmd+S submits the dialog's form, which calls save()
  beforeUnmount() {
    this.resetSaveFeedback();
  }
};
</script>
