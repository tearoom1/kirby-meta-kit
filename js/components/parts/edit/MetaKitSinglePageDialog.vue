<template>
  <k-dialog ref="dialog" size="large" cancelButton="Close" submitButton="">
    <k-headline v-if="page">Edit: {{ page.title }}</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Loading page...</span>
    </div>

    <div v-else-if="page" class="k-meta-kit-single-edit">
      <!-- Meta Title -->
      <div class="k-meta-kit-single-field">
        <label class="k-meta-kit-single-field-label">Meta Title</label>
        <meta-kit-title-field
          :value="metaTitle"
          @input="$emit('update:meta-title', $event)"
          :page-id="page.id"
          :page-title="page.title"
          :site-settings="siteSettings"
          :ai-enabled="aiEnabled"
          :is-generating="isGenerating.metaTitle"
          @generate="$emit('generate', { pageId: page.id, fieldName: 'metaTitle' })"
          :placeholder="page.metaTitle || 'No meta title set'"
          button-size="sm"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- Meta Description -->
      <div class="k-meta-kit-single-field">
        <label class="k-meta-kit-single-field-label">Meta Description</label>
        <meta-kit-description-field
          :value="metaDescription"
          @input="$emit('update:meta-description', $event)"
          :ai-enabled="aiEnabled"
          :is-generating="isGenerating.metaDescription"
          @generate="$emit('generate', { pageId: page.id, fieldName: 'metaDescription' })"
          :placeholder="page.metaDescription || 'No meta description set'"
          button-size="sm"
          :rows="4"
          buttons="false"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Title -->
      <div class="k-meta-kit-single-field">
        <label class="k-meta-kit-single-field-label">OG Title</label>
        <meta-kit-og-title-field
          :value="ogTitle"
          @input="$emit('update:og-title', $event)"
          :ai-enabled="aiEnabled"
          :is-generating="isGenerating.ogTitle"
          @generate="$emit('generate', { pageId: page.id, fieldName: 'ogTitle' })"
          :placeholder="page.ogTitle || 'No OG title set'"
          button-size="sm"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Description -->
      <div class="k-meta-kit-single-field">
        <label class="k-meta-kit-single-field-label">OG Description</label>
        <meta-kit-og-description-field
          :value="ogDescription"
          @input="$emit('update:og-description', $event)"
          :ai-enabled="aiEnabled"
          :is-generating="isGenerating.ogDescription"
          @generate="$emit('generate', { pageId: page.id, fieldName: 'ogDescription' })"
          :placeholder="page.ogDescription || 'No OG description set'"
          button-size="sm"
          :rows="4"
          buttons="false"
          field-class="k-meta-kit-single-field-content"
        />
      </div>

      <!-- OG Image -->
      <div class="k-meta-kit-single-field">
        <label class="k-meta-kit-single-field-label">OG Image</label>
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
        <k-button
          icon="open"
          @click="$emit('edit-in-panel', page)"
        >
          Edit in Panel
        </k-button>
        <k-button
          v-if="hasChanges"
          icon="check"
          theme="positive"
          @click="$emit('apply-changes')"
        >
          Apply Changes
        </k-button>
      </div>
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
    page: {
      type: Object,
      default: null
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    hasChanges: {
      type: Boolean,
      default: false
    },
    metaTitle: {
      type: String,
      default: ''
    },
    metaDescription: {
      type: String,
      default: ''
    },
    ogTitle: {
      type: String,
      default: ''
    },
    ogDescription: {
      type: String,
      default: ''
    },
    siteSettings: {
      type: Object,
      required: true
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    isGenerating: {
      type: Object,
      default: () => ({
        metaTitle: false,
        metaDescription: false,
        ogTitle: false,
        ogDescription: false
      })
    }
  },
  methods: {
    open() {
      this.$refs.dialog.open();
    },
    close() {
      this.$refs.dialog.close();
    }
  }
};
</script>
