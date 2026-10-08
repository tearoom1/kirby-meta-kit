<template>
  <k-dialog :class="{ 'k-mk2-dialog': variant === 'v2' }" ref="dialog" class="k-meta-kit-dialog" size="medium">
    <k-headline>{{ $t('meta-kit.generate.title') }}</k-headline>
    <k-text>{{ $t(variant === 'v2' ? 'meta-kit.v2.generate.intro' : 'meta-kit.generate.intro', { count: selectedCount }) }}</k-text>

    <div class="k-meta-kit-bulk-options">
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.title"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>{{ $t('meta-kit.field.metaTitle') }}</strong>
          <span>{{ $t('meta-kit.generate.metaTitle.help') }}</span>
        </div>
      </label>
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.description"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>{{ $t('meta-kit.field.metaDescription') }}</strong>
          <span>{{ $t('meta-kit.generate.metaDescription.help') }}</span>
        </div>
      </label>
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.ogTitle"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>{{ $t('meta-kit.field.ogTitle') }}</strong>
          <span>{{ $t('meta-kit.generate.ogTitle.help') }}</span>
        </div>
      </label>
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.ogDescription"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>{{ $t('meta-kit.field.ogDescription') }}</strong>
          <span>{{ $t('meta-kit.generate.ogDescription.help') }}</span>
        </div>
      </label>
    </div>

    <label class="k-meta-kit-bulk-option k-meta-kit-bulk-review-option">
      <input
        type="checkbox"
        v-model="options.review"
      />
      <div class="k-meta-kit-bulk-option-content">
        <strong>{{ $t('meta-kit.generate.review') }}</strong>
        <span>{{ $t('meta-kit.generate.review.help') }}</span>
      </div>
    </label>

    <template #footer>
      <k-button-group class="k-meta-kit-bulk-buttons">
        <k-button @click="close()">{{ $t('cancel') }}</k-button>
        <k-button
          icon="sparkling"
          class="k-meta-kit-button-ai-generate"
          :disabled="!hasAnySelected"
          @click="generate"
        >
          {{ $t('meta-kit.generate.button') }}
        </k-button>
      </k-button-group>
    </template>
  </k-dialog>
</template>

<script>
export default {
  props: {
    // Temporary design comparison
    variant: {
      type: String,
      default: 'v1'
    },
    selectedCount: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      options: {
        title: false,
        description: true,
        ogTitle: false,
        ogDescription: false,
        review: true
      }
    };
  },
  computed: {
    hasAnySelected() {
      return this.options.title || this.options.description || this.options.ogTitle || this.options.ogDescription;
    }
  },
  methods: {
    open() {
      // Reset to defaults
      this.options.title = false;
      this.options.description = true;
      this.options.ogTitle = false;
      this.options.ogDescription = false;
      this.options.review = true;
      this.$refs.dialog.open();
    },
    close() {
      // Kirby 5: the dialog lives in the panel-wide dialog state; k-dialog.close() only emits
      this.$panel.dialog.close();
    },
    generate() {
      this.$emit('generate', { ...this.options });
      this.close();
    }
  }
};
</script>
