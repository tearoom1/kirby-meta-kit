<template>
  <k-field v-bind="$props" class="k-mk-slug-info-field">
    <template #default>
      <k-box :theme="validation.theme" class="k-mk-slug-validation-box">
      <div class="k-mk-slug-stats">
        <div class="k-mk-slug-stat k-mk-slug-stat-slug">
          <span class="k-mk-slug-stat-label">Slug:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.status">{{ displaySlug }}</span>
        </div>
        <div class="k-mk-slug-stat">
          <span class="k-mk-slug-stat-label">Words:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.wordsStatus">{{ wordCount }}</span>
        </div>
        <div class="k-mk-slug-stat">
          <span class="k-mk-slug-stat-label">Length:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.lengthStatus">{{ slugLength }} chars</span>
        </div>
        <div class="k-mk-slug-stat">
          <span class="k-mk-slug-stat-label">Depth:</span>
          <span :class="'k-mk-slug-stat-value k-mk-validation-status-' + validation.depthStatus">{{ depth }} levels</span>
        </div>
      </div>

        <div v-if="validation.message" class="k-mk-slug-info">
      <div v-if="validation.message" class="k-mk-slug-message">
        {{ validation.message }}
      </div>

      <details class="k-mk-slug-guidelines">
        <summary>SEO Guidelines</summary>
        <ul>
          <li><strong>Core pages:</strong> 1 word, ≤ 15 characters (e.g., /about, /services)</li>
          <li><strong>Articles:</strong> 4-8 words, ≤ 60 characters</li>
          <li><strong>Nesting:</strong> ≤ 2 levels deep for best crawling</li>
          <li><strong>Best practices:</strong> Use hyphens, lowercase, descriptive keywords</li>
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
    validation() {
      if (!this.currentSlug) {
        return {
          status: '',
          theme: 'info',
          message: 'No slug available yet'
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
        messages.push(`Consider reducing nesting depth (currently ${depth} levels)`);
        overallStatus = 'warning';
        theme = 'notice';
      } else if (depthStatus === 'error') {
        messages.push(`Too deeply nested! Reduce to ${settings.depth.optimal.max} levels or less`);
        overallStatus = 'error';
        theme = 'negative';
      }

      // Check words
      const wordsStatus = this.getStatus(words, settings.words);
      if (wordsStatus === 'warning') {
        if (words < settings.words.optimal.min) {
          messages.push(`Consider adding more descriptive words`);
        } else {
          messages.push(`Consider shortening the slug (${words} words)`);
        }
        if (overallStatus === 'optimal') {
          overallStatus = 'warning';
          theme = 'notice';
        }
      } else if (wordsStatus === 'error') {
        messages.push(`Slug too long! Reduce to ${settings.words.optimal.max} words or less`);
        overallStatus = 'error';
        theme = 'negative';
      }

      // Check length
      const lengthStatus = this.getStatus(length, settings.length);
      if (lengthStatus === 'warning') {
        messages.push(`Slug is ${length} characters (${settings.length.optimal.max} recommended)`);
        if (overallStatus === 'optimal') {
          overallStatus = 'warning';
          theme = 'notice';
        }
      } else if (lengthStatus === 'error') {
        messages.push(`Slug too long! Reduce to ${settings.length.optimal.max} characters or less`);
        overallStatus = 'error';
        theme = 'negative';
      }

      return {
        status: overallStatus,
        theme: theme,
        message: messages.length > 0 ? messages.join('. ') : 'Slug fulfills the validation rules',
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

.k-panel[data-color-scheme="dark"] .k-mk-slug-stat-label {
  color: var(--color-gray-400);
}

.k-panel[data-color-scheme="dark"] .k-mk-validation-status-optimal {
  color: var(--color-green-400);
}

.k-panel[data-color-scheme="dark"] .k-mk-validation-status-warning {
  color: var(--color-orange-400);
}

.k-panel[data-color-scheme="dark"] .k-mk-validation-status-error {
  color: var(--color-red-400);
}

.k-panel[data-color-scheme="dark"] .k-mk-slug-message {
  background: var(--color-black);
}

.k-panel[data-color-scheme="dark"] .k-mk-slug-guidelines summary {
  color: var(--color-gray-400);
}

.k-panel[data-color-scheme="dark"] .k-mk-slug-guidelines summary:hover {
  color: var(--color-text);
}
</style>
