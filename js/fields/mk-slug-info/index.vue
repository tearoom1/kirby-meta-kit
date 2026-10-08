<template>
  <k-field v-bind="$props" class="k-mk-slug-info-field">
    <template #default>
      <k-box :theme="validation.theme" class="k-mk-slug-validation-box">
      <div class="k-mk-slug-stats">
        <div class="k-mk-slug-stat k-mk-slug-stat-slug">
          <span class="k-mk-slug-stat-label">{{ $t('meta-kit.field.slug') }}:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.status">{{ displaySlug }}</span>
        </div>
        <div class="k-mk-slug-stat">
          <span class="k-mk-slug-stat-label">{{ $t('meta-kit.slug.words') }}:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.wordsStatus">{{ wordCount }}</span>
        </div>
        <div class="k-mk-slug-stat">
          <span class="k-mk-slug-stat-label">{{ $t('meta-kit.slug.length') }}:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.lengthStatus">{{ $t('meta-kit.chars', { count: slugLength }) }}</span>
        </div>
        <div class="k-mk-slug-stat">
          <span class="k-mk-slug-stat-label">{{ $t('meta-kit.slug.depth') }}:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.depthStatus">{{ $t('meta-kit.slug.levels', { count: depth }) }}</span>
        </div>
      </div>

        <div v-if="validation.message" class="k-mk-slug-info">
      <div v-if="validation.message" class="k-mk-slug-message">
        {{ validation.message }}
      </div>

      <details class="k-mk-slug-guidelines">
        <summary>{{ $t('meta-kit.slug.guidelines') }}{{ templateInfo }}</summary>
        <ul>
          <li><strong>{{ $t('meta-kit.slug.words') }}:</strong> {{ wordsGuideline }}</li>
          <li><strong>{{ $t('meta-kit.slug.length') }}:</strong> {{ $t('meta-kit.chars', { count: lengthGuideline }) }}</li>
          <li><strong>{{ $t('meta-kit.slug.nesting') }}:</strong> {{ $t('meta-kit.slug.nesting.help', { count: depthGuideline }) }}</li>
          <li><strong>{{ $t('meta-kit.slug.bestPractices') }}:</strong> {{ $t('meta-kit.slug.bestPractices.help') }}</li>
        </ul>
      </details>
        </div>
    </k-box>
    </template>
  </k-field>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    currentSlug: String,
    disabled: Boolean,
    label: String,
    help: String,
    validationSettings: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    displaySlug() {
      return this.currentSlug || '—';
    },
    wordCount() {
      if (!this.currentSlug) return 0;
      const lastPart = this.getLastSlugPart();
      return lastPart.split(/[-_]/).filter(word => word.length > 0).length;
    },
    slugLength() {
      if (!this.currentSlug) return 0;
      return this.getLastSlugPart().length;
    },
    depth() {
      if (!this.currentSlug) return 0;
      return (this.currentSlug.match(/\//g) || []).length;
    },
    templateInfo() {
      const template = this.validationSettings.template;
      return template ? ` (${template})` : '';
    },
    wordsGuideline() {
      const ranges = this.validationSettings.words;
      if (!ranges) return '1-8';
      const min = ranges.optimal.min;
      const max = ranges.optimal.max;
      return min === max ? `${min}` : `${min}-${max}`;
    },
    lengthGuideline() {
      const ranges = this.validationSettings.length;
      if (!ranges) return '1-60';
      const min = ranges.optimal.min;
      const max = ranges.optimal.max;
      return min === max ? `${min}` : `${min}-${max}`;
    },
    depthGuideline() {
      const ranges = this.validationSettings.depth;
      if (!ranges) return '2';
      return ranges.optimal.max;
    },
    validation() {
      if (!this.currentSlug) {
        return {
          status: '',
          theme: 'info',
          message: this.$t('meta-kit.slug.none')
        };
      }

      const settings = this.validationSettings;
      const depth = this.depth;
      const words = this.wordCount;
      const length = this.slugLength;

      let messages = [];
      let overallStatus = 'optimal';
      let theme = 'positive';

      // Check depth
      const depthStatus = this.getStatus(depth, settings.depth);
      if (depthStatus === 'warning') {
        messages.push(this.$t('meta-kit.slug.msg.depthWarning', { count: depth }));
        overallStatus = 'warning';
        theme = 'notice';
      } else if (depthStatus === 'error') {
        messages.push(this.$t('meta-kit.slug.msg.depthError', { count: settings.depth.optimal.max }));
        overallStatus = 'error';
        theme = 'negative';
      }

      // Check words
      const wordsStatus = this.getStatus(words, settings.words);
      if (wordsStatus === 'warning') {
        if (words < settings.words.optimal.min) {
          messages.push(this.$t('meta-kit.slug.msg.moreWords'));
        } else {
          messages.push(this.$t('meta-kit.slug.msg.fewerWords', { count: words }));
        }
        if (overallStatus === 'optimal') {
          overallStatus = 'warning';
          theme = 'notice';
        }
      } else if (wordsStatus === 'error') {
        messages.push(this.$t('meta-kit.slug.msg.wordsError', { count: settings.words.optimal.max }));
        overallStatus = 'error';
        theme = 'negative';
      }

      // Check length
      const lengthStatus = this.getStatus(length, settings.length);
      if (lengthStatus === 'warning') {
        messages.push(this.$t('meta-kit.slug.msg.lengthWarning', { count: length, max: settings.length.optimal.max }));
        if (overallStatus === 'optimal') {
          overallStatus = 'warning';
          theme = 'notice';
        }
      } else if (lengthStatus === 'error') {
        messages.push(this.$t('meta-kit.slug.msg.lengthError', { count: settings.length.optimal.max }));
        overallStatus = 'error';
        theme = 'negative';
      }

      return {
        status: overallStatus,
        theme: theme,
        message: messages.length > 0 ? messages.join('. ') : this.$t('meta-kit.slug.msg.ok'),
        depthStatus: depthStatus,
        wordsStatus: wordsStatus,
        lengthStatus: lengthStatus
      };
    }
  },
  methods: {
    getLastSlugPart() {
      if (!this.currentSlug) return '';
      const parts = this.currentSlug.split('/');
      return parts[parts.length - 1];
    },
    getStatus(value, ranges) {
      if (!ranges) return 'optimal';

      const optimal = ranges.optimal || {};
      const warning = ranges.warning || {};

      if (value >= optimal.min && value <= optimal.max) {
        return 'optimal';
      }

      if (value >= warning.min && value <= warning.max) {
        return 'warning';
      }

      return 'error';
    }
  }
};
</script>

<style>
.k-mk-slug-info-field .k-mk-slug-validation-box {
}

.k-mk-slug-validation-box {
  border-radius: var(--rounded-xs);
  background: var(--color-background);
  display: flex;
  flex-direction: column;
  align-items: start;
}

.k-mk-slug-stats {
  width: 100%;
  display: flex;
  gap: 1rem;
}

.k-mk-slug-stat {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.k-mk-slug-stat-slug {
  flex: 4;
}


.k-mk-slug-stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-gray-600);
}

.k-mk-slug-stat-value {
  font-size: 0.875rem;
  font-weight: 600;
  font-family: var(--font-mono);
}

.k-mk-validation-status-optimal {
  color: var(--color-green-600);
}

.k-mk-validation-status-warning {
  color: var(--color-orange-600);
}

.k-mk-validation-status-error {
  color: var(--color-red-600);
}

.k-mk-slug-info {
  width: 100%;
  display: flex;
  justify-content: space-between;
  padding-top: 0.75rem;
  gap: 1rem;
}
.k-mk-slug-message {
  background: var(--color-background);
  border-radius: var(--rounded-xs);
  font-size: 0.875rem;
  color: var(--color-text);
}

.k-mk-slug-guidelines {
  color: var(--color-text);
}

.k-mk-slug-guidelines summary {
  text-align: right;
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--color-text);
  user-select: none;
}

.k-mk-slug-guidelines summary:hover {
  color: var(--color-text);
}

.k-mk-slug-guidelines ul {
  margin: 0.75rem 0 0 0;
  padding-left: 1.25rem;
  font-size: 0.875rem;
  line-height: 1.6;
}

.k-mk-slug-guidelines li {
  margin-bottom: 0.5rem;
}

.k-panel[data-theme="dark"] .k-mk-slug-stat-label {
  color: var(--color-gray-400);
}

.k-panel[data-theme="dark"] .k-mk-validation-status-optimal {
  color: var(--color-green-400);
}

.k-panel[data-theme="dark"] .k-mk-validation-status-warning {
  color: var(--color-orange-400);
}

.k-panel[data-theme="dark"] .k-mk-validation-status-error {
  color: var(--color-red-400);
}

.k-panel[data-theme="dark"] .k-mk-slug-message {
  background: var(--color-black);
}

.k-panel[data-theme="dark"] .k-mk-slug-guidelines summary {
  color: var(--color-gray-400);
}

.k-panel[data-theme="dark"] .k-mk-slug-guidelines summary:hover {
  color: var(--color-text);
}
</style>
