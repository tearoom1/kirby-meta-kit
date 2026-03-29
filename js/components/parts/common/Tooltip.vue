<template>
  <div class="k-meta-kit-tooltip-wrapper" @mouseenter="show" @mouseleave="hide">
    <slot></slot>
    <div
      v-if="isVisible && content"
      class="k-meta-kit-tooltip-content"
      :style="tooltipStyle"
      v-html="formattedContent"
    ></div>
  </div>
</template>

<script>
function escapeHtml(value = '') {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export default {
  props: {
    content: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      isVisible: false,
      tooltipStyle: {}
    };
  },
  computed: {
    formattedContent() {
      const escaped = escapeHtml(this.content);

      return escaped
        .replace(/^Warning:/gm, '<span class="k-meta-kit-tooltip-label k-meta-kit-tooltip-label-warning">Warning:</span>')
        .replace(/^Error:/gm, '<span class="k-meta-kit-tooltip-label k-meta-kit-tooltip-label-error">Error:</span>')
        .replace(/\n/g, '<br>');
    }
  },
  methods: {
    show(event) {
      if (!this.content) return;
      this.isVisible = true;
      this.$nextTick(() => {
        this.updatePosition(event);
      });
    },
    hide() {
      this.isVisible = false;
    },
    updatePosition(event) {
      const tooltip = this.$el.querySelector('.k-meta-kit-tooltip-content');
      if (!tooltip) return;

      const rect = this.$el.getBoundingClientRect();
      const tooltipRect = tooltip.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
      let top = rect.bottom + 8;

      // Adjust if tooltip goes off right edge
      if (left + tooltipRect.width > viewportWidth - 10) {
        left = viewportWidth - tooltipRect.width - 10;
      }

      // Adjust if tooltip goes off left edge
      if (left < 10) {
        left = 10;
      }

      // Show above if no room below
      if (top + tooltipRect.height > viewportHeight - 10) {
        top = rect.top - tooltipRect.height - 8;
      }

      this.tooltipStyle = {
        left: `${left}px`,
        top: `${top}px`
      };
    }
  }
};
</script>

<style>
.k-meta-kit-tooltip-wrapper {
  position: relative;
  display: inline-block;
}

.k-meta-kit-tooltip-content {
  position: fixed;
  z-index: 9999;
  background: var(--color-gray-900);
  color: var(--color-white);
  padding: 0.8rem 1rem;
  border-radius: var(--rounded-sm);
  border: 1px solid var(--color-gray-800);
  font-size: 0.8125rem;
  line-height: 1.4;
  text-align: left;
  max-width: 350px;
  word-wrap: break-word;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  pointer-events: none;
  white-space: pre-wrap;
}

.k-meta-kit-tooltip-label {
  font-weight: 700;
}

.k-meta-kit-tooltip-label-warning {
  color: #f59e0b;
}

.k-meta-kit-tooltip-label-error {
  color: #ef4444;
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-tooltip-content {
  background: var(--color-gray-800);
  color: var(--color-white);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-tooltip-label-warning {
  color: #fbbf24;
}

.k-panel[data-color-scheme="dark"] .k-meta-kit-tooltip-label-error {
  color: #f87171;
}
</style>
