<template>
  <k-field v-bind="$props" class="k-mk-description-field">
    <template #default>
      <k-input type="textarea" class="k-mk-description-textarea"
        :value="value"
        @input="onInput"
        :placeholder="placeholder"
        :disabled="disabled"
        :buttons="false"
        :maxlength="maxlength"
        :counter="false"
      />
      <k-text v-if="validation.message" class="k-mk-validation-message" :theme="validation.theme">
        <span :class="'k-mk-validation-status-' + validation.status">{{ charCount }}</span> - {{ validation.message }}
      </k-text>
    </template>
  </k-field>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    value: String,
    fieldType: {
      type: String,
      default: 'meta'
    },
    maxlength: Number,
    disabled: Boolean,
    label: String,
    help: String,
    placeholder: String,
    validationSettings: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    charCount() {
      return this.value ? this.value.length : 0;
    },
    validation() {
      if (!this.value) {
        return {
          status: '',
          theme: '',
          message: ''
        };
      }

      const length = this.charCount;
      const ranges = this.validationSettings.ranges || {};
      const optimal = ranges.optimal || { min: 140, max: 160 };
      const warning = ranges.warning || { min: 126, max: 176 };

      if (length >= optimal.min && length <= optimal.max) {
        return {
          status: 'optimal',
          theme: 'positive',
          message: `Optimal length (${optimal.min}-${optimal.max} characters recommended)`
        };
      }

      if (length >= warning.min && length <= warning.max) {
        if (length < optimal.min) {
          return {
            status: 'warning',
            theme: 'notice',
            message: `Too short. Add ${optimal.min - length} more characters for optimal length (${optimal.min}-${optimal.max} recommended)`
          };
        } else {
          return {
            status: 'warning',
            theme: 'notice',
            message: `Slightly too long. Remove ${length - optimal.max} characters for optimal length (${optimal.min}-${optimal.max} recommended)`
          };
        }
      }

      if (length < warning.min) {
        return {
          status: 'error',
          theme: 'negative',
          message: `Much too short! Add at least ${warning.min - length} more characters (${optimal.min}-${optimal.max} recommended)`
        };
      }

      return {
        status: 'error',
        theme: 'negative',
        message: `Too long! Reduce by ${length - warning.max} characters (${optimal.min}-${optimal.max} recommended)`
      };
    }
  },
  methods: {
    onInput(value) {
      this.$emit('input', value);
    }
  }
};
</script>

<style>
.k-mk-description-field .k-mk-description-textarea {
  display: block;
}

.k-mk-description-field .k-mk-validation-message {
  display: block;
  margin-top: 0.5rem;
  padding: 0 1rem;
  font-size: 0.875rem;
}

.k-mk-validation-status-optimal {
  font-weight: 600;
  color: var(--color-green-600);
}

.k-mk-validation-status-warning {
  font-weight: 600;
  color: var(--color-orange-600);
}

.k-mk-validation-status-error {
  font-weight: 600;
  color: var(--color-red-600);
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
</style>
