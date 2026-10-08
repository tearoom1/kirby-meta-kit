<template>
  <div class="k-meta-kit-table" :class="{ 'k-meta-kit-table-preview': showPreview }">
    <table>
      <thead>
      <tr>
        <th class="k-meta-kit-table-checkbox">
          <input
            type="checkbox"
            :checked="isAllSelected"
            @change="$emit('toggle-select-all')"
          />
        </th>
        <th>#</th>
        <th>{{ $t('meta-kit.table.page') }}</th>
        <th v-if="!showPreview">{{ $t('meta-kit.field.slug') }}</th>
        <th v-if="showPreview">{{ previewMode === 'og' ? $t('meta-kit.field.ogTitle') : $t('meta-kit.field.metaTitle') }}</th>
        <th v-if="showPreview">{{ previewMode === 'og' ? $t('meta-kit.field.ogDescription.short') : $t('meta-kit.field.metaDescription.short') }}</th>
        <th v-if="!showPreview">{{ $t('meta-kit.field.metaTitle') }}</th>
        <th v-if="!showPreview">{{ $t('meta-kit.field.metaDescription.short') }}</th>
        <th v-if="!showPreview">{{ $t('meta-kit.field.ogTitle') }}</th>
        <th v-if="!showPreview">{{ $t('meta-kit.field.ogDescription.short') }}</th>
        <th v-if="!showPreview || previewMode === 'og'">{{ $t('meta-kit.field.ogImage.short') }}</th>
        <th v-if="!showPreview">{{ $t('meta-kit.table.robots') }}</th>
        <th>{{ $t('meta-kit.table.actions') }}</th>
      </tr>
      </thead>
      <tbody>
      <tr
        v-for="(page, index) in pages"
        :key="page.id"
        :class="{ 'k-meta-kit-row-selected': isPageSelected(page.id) }"
      >
        <td class="k-meta-kit-table-checkbox">
          <input
            type="checkbox"
            :checked="isPageSelected(page.id)"
            @change="$emit('toggle-page', page.id)"
          />
        </td>
        <td>{{ startIndex + index + 1 }}</td>
        <td>
          <div class="k-meta-kit-table-page">
              <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
          <div class="k-meta-kit-page-title-wrapper">
            <span class="k-meta-kit-table-page-id">{{ page.template }}</span>
            <span :class="['k-meta-kit-status-dot', getStatusDotClass(page)]" :title="getStatusLabel(page)"></span>

          </div></div>
        </td>
        <td v-if="!showPreview">
          <Tooltip :content="getSlugTooltip(page)">
            <span
              :class="[getSlugStatusClass(page), 'k-meta-kit-table-tooltip']"
            >
              {{ page.id }}
            </span>
          </Tooltip>
        </td>

        <!-- Title Column (Meta or OG based on mode) -->
        <td v-if="showPreview">
          <template v-if="previewMode === 'meta'">
            <Tooltip :content="getTitleTooltip(page, false)">
              <span
                :class="['k-meta-kit-table-preview-indicator',
                  'k-meta-kit-table-tooltip']"
                :data-status="getStatusValue(getTableTitleStatusClass(page))"
              >
                <span :class="isTitleInherited(page) ? 'k-meta-kit-inherited-preview' : ''">
                  {{ getFullTitlePreview(page, 'meta') }}
                  </span>
              </span>
            </Tooltip>
          </template>
          <template v-else>
            <Tooltip :content="getOgTitleTooltip(page, false)">
              <span
                :class="['k-meta-kit-table-preview-indicator',
                  'k-meta-kit-table-tooltip',
                  isOgTitleInherited(page) ? 'k-meta-kit-inherited-preview' : '']"
                :data-status="getStatusValue(getTableOgTitleStatusClass(page))"
              >
                  <template v-if="page.hasOgTitle">
                    {{ getFullTitlePreview(page, 'og') }}
                  </template>
                  <template v-else>
                    <span class="k-meta-kit-table-preview-fallback">
                      {{ getFullTitlePreview(page, 'og') }}
                    </span>
                  </template>
              </span>
            </Tooltip>
          </template>
        </td>

        <!-- Description Column (Meta or OG based on mode) -->
        <td v-if="showPreview">
          <template v-if="previewMode === 'meta'">
            <Tooltip :content="getDescriptionTooltip(page, false)">
              <span class="k-meta-kit-table-preview-indicator k-meta-kit-table-tooltip"
                    :data-status="getStatusValue(getDescriptionStatusClass(page))"
              >
                  <template v-if="page.hasMetaDescription">
                    {{ page.metaDescription }}
                  </template>
                  <template v-else-if="siteSettings.siteMetaDescription">
                    <span class="k-meta-kit-table-preview-fallback">
                      {{ siteSettings.siteMetaDescription }}
                    </span>
                  </template>
                  <template v-else>
                    —
                  </template>
              </span>
            </Tooltip>
          </template>
          <template v-else>
            <Tooltip :content="getOgDescriptionTooltip(page, false)">
              <span class="k-meta-kit-table-preview-indicator k-meta-kit-table-tooltip"
                    :data-status="getStatusValue(getOgDescriptionStatusClass(page))"
              >
                  <template v-if="page.hasOgDescription">
                    {{ page.ogDescription }}
                  </template>
                  <template v-else-if="page.hasMetaDescription">
                    <span class="k-meta-kit-table-preview-fallback">
                      {{ page.metaDescription }}
                    </span>
                  </template>
                  <template v-else-if="siteSettings.siteMetaDescription">
                    <span class="k-meta-kit-table-preview-fallback">
                      {{ siteSettings.siteMetaDescription }}
                    </span>
                  </template>
                  <template v-else>
                    —
                  </template>
              </span>
            </Tooltip>
          </template>
        </td>

        <!-- Title Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getTitleTooltip(page)">
              <span class="k-meta-kit-table-value-group k-meta-kit-table-tooltip">
                <span :class="[
                  getTableTitleStatusClass(page),
                  getTableTitleStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                  isTitleInherited(page) ? 'k-meta-kit-inherited' : ''
                ]">
                  {{ getTitleDisplay(page) }}
                </span>
                <span v-if="getInheritanceBadgeLabel(page, 'metaTitle')" class="k-meta-kit-table-source-marker">
                  {{ getInheritanceBadgeLabel(page, 'metaTitle') }}
                </span>
              </span>
          </Tooltip>
        </td>

        <!-- Description Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getDescriptionTooltip(page)">
              <span class="k-meta-kit-table-value-group k-meta-kit-table-tooltip">
                <span
                  :class="[
                    getDescriptionStatusClass(page),
                    getDescriptionStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                    isDescriptionInherited(page) ? 'k-meta-kit-inherited' : ''
                  ]">
                  {{ getDescriptionDisplay(page) }}
                </span>
                <span v-if="getInheritanceBadgeLabel(page, 'metaDescription')" class="k-meta-kit-table-source-marker">
                  {{ getInheritanceBadgeLabel(page, 'metaDescription') }}
                </span>
              </span>
          </Tooltip>
        </td>

        <!-- OG Title Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getOgTitleTooltip(page)">
              <span class="k-meta-kit-table-value-group k-meta-kit-table-tooltip">
                <span :class="[
                  getTableOgTitleStatusClass(page),
                  getTableOgTitleStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                  isOgTitleInherited(page) ? 'k-meta-kit-inherited' : ''
                  ]">
                  {{ getOgTitleDisplay(page) }}
                </span>
                <span v-if="getInheritanceBadgeLabel(page, 'ogTitle')" class="k-meta-kit-table-source-marker">
                  {{ getInheritanceBadgeLabel(page, 'ogTitle') }}
                </span>
              </span>
          </Tooltip>
        </td>

        <!-- OG Description Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getOgDescriptionTooltip(page)">
              <span class="k-meta-kit-table-value-group k-meta-kit-table-tooltip">
                <span
                  :class="[
                    getOgDescriptionStatusClass(page),
                    getOgDescriptionStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                    isOgDescriptionInherited(page) ? 'k-meta-kit-inherited' : ''
                  ]">
                  {{ getOgDescriptionDisplay(page) }}
                </span>
                <span v-if="getInheritanceBadgeLabel(page, 'ogDescription')" class="k-meta-kit-table-source-marker">
                  {{ getInheritanceBadgeLabel(page, 'ogDescription') }}
                </span>
              </span>
          </Tooltip>
        </td>

        <!-- OG Image (only in OG mode) -->
        <td class="k-meta-kit-table-center" v-if="!showPreview || previewMode === 'og'">
          <template v-if="page.hasOgImage">
            <Tooltip :content="$t('meta-kit.ogImage.own')">
              <span class="k-meta-kit-og-image-indicator">
                <k-icon type="check" class="k-meta-kit-icon-success"/>
              </span>
            </Tooltip>
          </template>
          <template v-else-if="!page.hasOgImage && siteSettings.siteHasOgImage">
            <Tooltip :content="$t('meta-kit.ogImage.site')">
              <span class="k-meta-kit-og-image-indicator k-meta-kit-inherited">
                <k-icon type="check" class="k-meta-kit-icon-success"/>
              </span>
            </Tooltip>
          </template>
          <template v-else>
            <Tooltip :content="$t('meta-kit.ogImage.none')">
              <span>—</span>
            </Tooltip>
          </template>
        </td>

        <!-- Robots (only in meta mode when not showing preview) -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <template v-if="page.robots && page.robots.includes('noindex')">
            <Tooltip :content="getRobotsTooltip(page)">
              <span class="k-meta-kit-robots-noindex k-meta-kit-table-tooltip">{{ getRobotsDisplay(page) }}</span>
            </Tooltip>
          </template>
          <span v-else>—</span>
        </td>
        <td class="k-meta-kit-table-center">
          <div class="k-meta-kit-table-actions">
            <k-button
              icon="edit"
              size="sm"
              @click="$emit('edit-page', page.id)"
              :title="$t('meta-kit.table.edit')"
            />
            <k-button
              v-if="aiEnabled"
              icon="sparkling"
              class="k-meta-kit-button-ai-generate"
              size="sm"
              @click="$emit('generate-page', page.id)"
              :title="$t('meta-kit.table.generate')"
            />
            <k-button
              v-if="canReviewPage(page)"
              icon="preview"
              size="sm"
              @click="$emit('review-page', page.id, $t('meta-kit.review.titleFor', { page: page.title }))"
              :title="$t('meta-kit.table.review')"
            />
          </div>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import Tooltip from '../common/Tooltip.vue';
import {
  getStatusClass,
  getStatusValue,
  getLengthValidationReason,
  STATUS_CLASSES
} from '../../../composables/useValidation.js';
import {
  isTitleInherited,
  isDescriptionInherited,
  isOgTitleInherited,
  isOgDescriptionInherited,
  getEffectiveTitle,
  getEffectiveDescription,
  getInheritanceSource,
  isInheritedFromLanguage,
  buildTooltipText
} from '../../../composables/useInheritance.js';
import {
  shouldAppendSiteName,
  buildTitleWithSiteName,
  getTableTitleDisplay
} from '../../../composables/panelDisplay.js';
import { classifyPageField, getSlugAnalysis } from '../../../composables/panelState.js';

const LEVEL_CLASSES = {
  good: STATUS_CLASSES.optimal,
  warning: STATUS_CLASSES.warning,
  error: STATUS_CLASSES.error
};

export default {
  components: {
    Tooltip
  },
  props: {
    pages: {
      type: Array,
      required: true
    },
    // All pages (not only this page of the table), to name duplicates
    allPages: {
      type: Array,
      default: () => []
    },
    duplicates: {
      type: Object,
      default: () => ({ title: {}, description: {} })
    },
    startIndex: {
      type: Number,
      default: 0
    },
    selectedPages: {
      type: Array,
      default: () => []
    },
    isAllSelected: {
      type: Boolean,
      default: false
    },
    showPreview: {
      type: Boolean,
      default: false
    },
    previewMode: {
      type: String,
      default: 'meta',
      validator: value => ['meta', 'og'].includes(value)
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    reviewEnabled: {
      type: Boolean,
      default: false
    },
    siteSettings: {
      type: Object,
      default: () => ({})
    },
    validationSettings: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      // Status mappings
      statusMappings: {
        listed: { label: 'meta-kit.status.listed', dotClass: 'k-meta-kit-status-dot-listed' },
        unlisted: { label: 'meta-kit.status.unlisted', dotClass: 'k-meta-kit-status-dot-unlisted' },
        draft: { label: 'meta-kit.status.draft', dotClass: 'k-meta-kit-status-dot-draft' }
      }
    };
  },
  computed: {
    classifierContext() {
      return {
        siteSettings: this.siteSettings,
        validationSettings: this.validationSettings,
        duplicates: this.duplicates
      };
    }
  },
  methods: {
    shouldAppendSiteName(type) {
      return shouldAppendSiteName(this.siteSettings, type);
    },

    isPageSelected(pageId) {
      return this.selectedPages.includes(pageId);
    },

    canReviewPage(page) {
      return this.reviewEnabled;
    },

    // Delegate to composables with proper context
    getStatusClass(page, length, type) {
      return getStatusClass(page, length, type, this.validationSettings);
    },

    getStatusValue(statusClass) {
      return getStatusValue(statusClass);
    },

    getLengthValidationReason(page, type, length) {
      return getLengthValidationReason(page, type, length, this.validationSettings);
    },

    // Inheritance helpers - delegate to composables
    isTitleInherited(page) {
      return isTitleInherited(page);
    },

    isDescriptionInherited(page) {
      return isDescriptionInherited(page, this.siteSettings);
    },

    isOgTitleInherited(page) {
      return isOgTitleInherited(page);
    },

    isOgDescriptionInherited(page) {
      return isOgDescriptionInherited(page, this.siteSettings);
    },

    // Title length calculator with site name appending
    getTitleLength(page, type = 'meta') {
      return getTableTitleDisplay(page, this.siteSettings, type).charCount;
    },

    getFullTitlePreview(page, type) {
      const preview = getTableTitleDisplay(page, this.siteSettings, type).fullTitle;
      return preview || '—';
    },

    // Status colours come from the shared classifier (panelState.js);
    // fields without a value stay uncoloured and show "—"
    fieldStatusClass(page, field, length) {
      if (!length) return '';
      return LEVEL_CLASSES[classifyPageField(page, field, this.classifierContext)];
    },

    getTableTitleStatusClass(page) {
      return this.fieldStatusClass(page, 'title', this.getTitleLength(page, 'meta'));
    },

    getTableOgTitleStatusClass(page) {
      return this.fieldStatusClass(page, 'ogTitle', this.getTitleLength(page, 'og'));
    },

    // Tooltip methods
    tooltipText(content, inheritanceSource, showContent) {
      return buildTooltipText(content, inheritanceSource, showContent);
    },

    // Join tooltip parts with newlines only when both have content
    joinTooltipParts(...parts) {
      return parts.filter(Boolean).join('\n\n');
    },

    getReasonSeverity(reason) {
      if (!reason) return '';
      if (reason.startsWith('Error:')) return 'error';
      if (reason.startsWith('Warning:')) return 'warning';
      return '';
    },

    combineReasonParts(...reasons) {
      const grouped = {
        error: [],
        warning: [],
        info: []
      };

      reasons.filter(Boolean).forEach((reason) => {
        const severity = this.getReasonSeverity(reason);
        const body = reason.replace(/^(Warning|Error):\n?/, '').trim();

        if (severity === 'error') {
          grouped.error.push(body);
          return;
        }

        if (severity === 'warning') {
          grouped.warning.push(body);
          return;
        }

        grouped.info.push(reason.trim());
      });

      const sections = [];

      if (grouped.error.length > 0) {
        sections.push(`${this.$t('meta-kit.tooltip.error')}\n${grouped.error.join('\n')}`);
      }

      if (grouped.warning.length > 0) {
        sections.push(`${this.$t('meta-kit.tooltip.warning')}\n${grouped.warning.join('\n')}`);
      }

      if (grouped.info.length > 0) {
        sections.push(grouped.info.join('\n\n'));
      }

      return sections.join('\n\n');
    },

    getInheritanceWarningReason(page, fieldType) {
      if (isInheritedFromLanguage(page, fieldType, this.siteSettings)) {
        return `Warning:\n${this.$t('meta-kit.reason.mainLanguage')}`;
      }

      if (
        fieldType === 'ogTitle' &&
        !page.hasOgTitle &&
        isInheritedFromLanguage(page, 'metaTitle', this.siteSettings)
      ) {
        return `Warning:\n${this.$t('meta-kit.reason.mainLanguage')}`;
      }

      if (
        fieldType === 'ogDescription' &&
        !page.hasOgDescription &&
        isInheritedFromLanguage(page, 'metaDescription', this.siteSettings)
      ) {
        return `Warning:\n${this.$t('meta-kit.reason.mainLanguage')}`;
      }

      return '';
    },

    getTitleTooltip(page, showContent = true) {
      if (!page.title && !page.metaTitle) return this.$t('meta-kit.noTitle');
      if (page.id === 'site') {
        return showContent ? (page.hasMetaTitle ? page.metaTitle : page.title) : '';
      }

      const source = getInheritanceSource(page, 'metaTitle', this.siteSettings);
      const tooltip = buildTitleWithSiteName(
        getTableTitleDisplay(page, this.siteSettings, 'meta').effectiveTitle,
        this.siteSettings,
        'meta'
      );

      const base = this.tooltipText(tooltip, source, showContent);
      const inheritanceReason = this.getInheritanceWarningReason(page, 'metaTitle');
      const lengthReason = this.getLengthValidationReason(page, 'title', this.getTitleLength(page, 'meta'));
      return this.joinTooltipParts(base, this.combineReasonParts(inheritanceReason, lengthReason), this.getDuplicateReason(page, 'title'));
    },

    // "Same text as: …" for pages sharing their own title/description
    getDuplicateReason(page, field) {
      const ids = this.duplicates?.[field]?.[page.id];
      if (!ids?.length) return '';

      const titles = ids.map((id) => this.allPages.find((other) => other.id === id)?.title || id);
      return `Warning:\n${this.$t(field === 'title' ? 'meta-kit.reason.duplicateTitle' : 'meta-kit.reason.duplicateDescription', { pages: titles.join(', ') })}`;
    },

    getDescriptionTooltip(page, showContent = true) {
      const text = getEffectiveDescription(page, 'meta', this.siteSettings);
      if (!text) return this.$t('meta-kit.noMetaDescription');

      const source = getInheritanceSource(page, 'metaDescription', this.siteSettings);
      const base = this.tooltipText(text, source, showContent);
      const inheritanceReason = this.getInheritanceWarningReason(page, 'metaDescription');
      const lengthReason = this.getLengthValidationReason(page, 'description', text.length);
      return this.joinTooltipParts(base, this.combineReasonParts(inheritanceReason, lengthReason), this.getDuplicateReason(page, 'description'));
    },

    getOgTitleTooltip(page, showContent = true) {
      if (!page.title && !page.ogTitle && !page.metaTitle) return this.$t('meta-kit.noOgTitle');
      if (page.id === 'site') {
        const source = getInheritanceSource(page, 'ogTitle', this.siteSettings);
        const content = getEffectiveTitle(page, 'og');
        const base = this.tooltipText(content, source, showContent);
        const inheritanceReason = this.getInheritanceWarningReason(page, 'ogTitle');
        const lengthReason = this.getLengthValidationReason(page, 'ogTitle', this.getTitleLength(page, 'og'));
        return this.joinTooltipParts(base, this.combineReasonParts(inheritanceReason, lengthReason));
      }

      const source = getInheritanceSource(page, 'ogTitle', this.siteSettings);
      const tooltip = buildTitleWithSiteName(
        getTableTitleDisplay(page, this.siteSettings, 'og').effectiveTitle,
        this.siteSettings,
        'og'
      );

      const base = this.tooltipText(tooltip, source, showContent);
      const inheritanceReason = this.getInheritanceWarningReason(page, 'ogTitle');
      const lengthReason = this.getLengthValidationReason(page, 'ogTitle', this.getTitleLength(page, 'og'));
      return this.joinTooltipParts(base, this.combineReasonParts(inheritanceReason, lengthReason));
    },

    getOgDescriptionTooltip(page, showContent = true) {
      const text = getEffectiveDescription(page, 'og', this.siteSettings);
      if (!text) return this.$t('meta-kit.noOgDescription');

      const source = getInheritanceSource(page, 'ogDescription', this.siteSettings);
      const base = this.tooltipText(text, source, showContent);
      const inheritanceReason = this.getInheritanceWarningReason(page, 'ogDescription');
      const lengthReason = this.getLengthValidationReason(page, 'ogDescription', text.length);
      return this.joinTooltipParts(base, this.combineReasonParts(inheritanceReason, lengthReason));
    },

    getInheritanceBadgeLabel(page, fieldType) {
      const source = getInheritanceSource(page, fieldType, this.siteSettings);

      switch (source) {
        case 'site':
          return 's';
        case 'page title':
          return 't';
        case 'meta title':
          return 'm';
        case 'meta description':
          return 'm';
        default:
          return source ? source.charAt(0).toLowerCase() : '';
      }
    },

    // Display methods
    getTitleDisplay(page) {
      const length = this.getTitleLength(page, 'meta');
      return length ? (this.isTitleInherited(page) ? `${length}` : length) : '—';
    },

    getDescriptionDisplay(page) {
      const desc = getEffectiveDescription(page, 'meta', this.siteSettings);
      return desc ? desc.length : '—';
    },

    getDescriptionStatusClass(page) {
      const desc = getEffectiveDescription(page, 'meta', this.siteSettings);
      return this.fieldStatusClass(page, 'description', desc?.length || 0);
    },

    getOgTitleDisplay(page) {
      const length = this.getTitleLength(page, 'og');
      return length ? (this.isOgTitleInherited(page) ? `${length}` : length) : '—';
    },

    getOgDescriptionDisplay(page) {
      const desc = getEffectiveDescription(page, 'og', this.siteSettings);
      return desc ? desc.length : '—';
    },

    getOgDescriptionStatusClass(page) {
      const desc = getEffectiveDescription(page, 'og', this.siteSettings);
      return this.fieldStatusClass(page, 'ogDescription', desc?.length || 0);
    },

    // Slug methods
    getSlug(page) {
      return getSlugAnalysis(page, this.validationSettings).slug;
    },

    getSlugStatusClass(page) {
      return LEVEL_CLASSES[classifyPageField(page, 'slug', this.classifierContext)];
    },

    getSlugTooltip(page) {
      if (page.id === 'site') return this.$t('meta-kit.slug.siteRoot');

      const { slug, wordCount, length, numSlashes, cfg, issues } = getSlugAnalysis(page, this.validationSettings);
      const statusClass = this.getSlugStatusClass(page);
      const status = this.$t(statusClass === 'k-meta-kit-status-error' ? 'meta-kit.slug.status.error'
        : (statusClass === 'k-meta-kit-status-warning' ? 'meta-kit.slug.status.warning' : 'meta-kit.slug.status.ok'));
      const label = (key) => this.$t(`meta-kit.slug.${key.toLowerCase()}`);
      const range = (rule) => `${rule.optimal.min}-${rule.optimal.max} / ${rule.warning.min}-${rule.warning.max}`;

      const reasons = issues.length
        ? `\n\n${this.$t('meta-kit.slug.why', { status })}\n` + issues
          .map((issue) => this.$t(issue.severity === 'warning' ? 'meta-kit.slug.issue.warning' : 'meta-kit.slug.issue.error', {
            key: label(issue.key),
            value: issue.value,
            optimal: issue.optimal,
            warning: issue.warning
          }))
          .join('\n')
        : '';

      return [
        `${this.$t('meta-kit.field.slug')}: ${slug}`,
        '',
        `${label('Depth')}: ${numSlashes}`,
        `${label('Words')}: ${wordCount}`,
        `${label('Length')}: ${this.$t('meta-kit.chars', { count: length })}`,
        '',
        this.$t('meta-kit.slug.ranges'),
        '',
        `${label('Depth')}: ${range(cfg.depth)}`,
        `${label('Words')}: ${range(cfg.words)}`,
        `${label('Length')}: ${range(cfg.length)}`,
        `${this.$t('meta-kit.slug.wordlength')}: ${range(cfg.wordLength)}`
      ].join('\n') + reasons;
    },

    // Status display helpers
    getStatusLabel(page) {
      if (!page.status) return '—';
      const labelKey = this.statusMappings[page.status]?.label;
      return (labelKey && this.$t(labelKey)) ||
             page.status.charAt(0).toUpperCase() + page.status.slice(1);
    },

    getStatusDotClass(page) {
      return page.status ? (this.statusMappings[page.status]?.dotClass || '') : '';
    },

    getRobotsDisplay(page) {
      if (!page.robots) return '—';
      return page.robots.includes('noindex') ? 'nidx' : page.robots;
    },

    getRobotsTooltip(page) {
      return page.robots || this.$t('meta-kit.robots.notSet');
    }
  }
};
</script>
