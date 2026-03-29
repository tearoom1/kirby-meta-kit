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
        <th>Page</th>
        <th v-if="!showPreview">Slug</th>
        <th v-if="showPreview">{{ previewMode === 'og' ? 'OG Title' : 'Meta Title' }}</th>
        <th v-if="showPreview">{{ previewMode === 'og' ? 'OG Desc.' : 'Meta Desc.' }}</th>
        <th v-if="!showPreview">Meta Title</th>
        <th v-if="!showPreview">Meta Desc.</th>
        <th v-if="!showPreview">OG Title</th>
        <th v-if="!showPreview">OG Desc.</th>
        <th>OG Image</th>
        <th v-if="!showPreview && previewMode === 'meta'">Robots</th>
        <th>Actions</th>
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
                  'k-meta-kit-table-tooltip',
                  isTitleInherited(page) ? 'k-meta-kit-inherited-preview' : '']"
                :data-status="getStatusValue(getTableTitleStatusClass(page))"
              >
                  {{ getFullTitlePreview(page, 'meta') }}
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
              <span :class="[
                getTableTitleStatusClass(page),
                getTableTitleStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                isTitleInherited(page) ? 'k-meta-kit-inherited' : '',
                'k-meta-kit-table-tooltip'
              ]">
                  {{ getTitleDisplay(page) }}
              </span>
          </Tooltip>
        </td>

        <!-- Description Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getDescriptionTooltip(page)">
              <span
                :class="[
                  getDescriptionStatusClass(page),
                  getDescriptionStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                  isDescriptionInherited(page) ? 'k-meta-kit-inherited' : '',
                  'k-meta-kit-table-tooltip'
                ]">
                  {{ getDescriptionDisplay(page) }}
              </span>
          </Tooltip>
        </td>

        <!-- OG Title Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getOgTitleTooltip(page)">
              <span :class="[
                getTableOgTitleStatusClass(page),
                getTableOgTitleStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                isOgTitleInherited(page) ? 'k-meta-kit-inherited' : '',
                'k-meta-kit-table-tooltip'
                ]">
                  {{ getOgTitleDisplay(page) }}
              </span>
          </Tooltip>
        </td>

        <!-- OG Description Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
          <Tooltip :content="getOgDescriptionTooltip(page)">
              <span
                :class="[
                  getOgDescriptionStatusClass(page),
                  getOgDescriptionStatusClass(page) === 'k-meta-kit-status-optimal' ? 'k-meta-kit-table-value-muted' : '',
                  isOgDescriptionInherited(page) ? 'k-meta-kit-inherited' : '',
                  'k-meta-kit-table-tooltip'
                ]">
                  {{ getOgDescriptionDisplay(page) }}
              </span>
          </Tooltip>
        </td>

        <!-- OG Image (only in OG mode) -->
        <td class="k-meta-kit-table-center">
          <template v-if="page.hasOgImage">
            <Tooltip content="Has OG image">
              <span class="k-meta-kit-og-image-indicator">
                <k-icon type="check" class="k-meta-kit-icon-success"/>
              </span>
            </Tooltip>
          </template>
          <template v-else-if="!page.hasOgImage && siteSettings.siteHasOgImage">
            <Tooltip content="OG image inherited from site">
              <span class="k-meta-kit-og-image-indicator k-meta-kit-inherited">
                <k-icon type="check" class="k-meta-kit-icon-success"/>
              </span>
            </Tooltip>
          </template>
          <template v-else>
            <Tooltip content="No OG image">
              <span>—</span>
            </Tooltip>
          </template>
        </td>

        <!-- Robots (only in meta mode when not showing preview) -->
        <td v-if="!showPreview && previewMode === 'meta'" class="k-meta-kit-table-center">
          <span v-if="page.robots && page.robots.includes('noindex')" class="k-meta-kit-robots-noindex">noindex</span>
          <span v-else>—</span>
        </td>
        <td class="k-meta-kit-table-center">
          <div class="k-meta-kit-table-actions">
            <k-button
              icon="edit"
              size="sm"
              @click="$emit('edit-page', page.id)"
              title="Edit Metadata"
            />
            <k-button
              v-if="aiEnabled"
              icon="sparkling"
              size="sm"
              @click="$emit('generate-page', page.id)"
              title="Generate with AI"
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
  getRangesForPageAndType,
  getSlugValidationConfig,
  isOutsideRange,
  getStatusClass,
  getStatusValue,
  getLengthValidationReason,
  getSlugValidationIssues
} from '../../../composables/useValidation.js';
import {
  isTitleInherited,
  isDescriptionInherited,
  isOgTitleInherited,
  isOgDescriptionInherited,
  getEffectiveDescription,
  getInheritanceSource,
  buildTooltipText
} from '../../../composables/useInheritance.js';
import {
  shouldAppendSiteName,
  buildTitleWithSiteName,
  getTableTitleDisplay
} from '../../../composables/panelDisplay.js';

export default {
  components: {
    Tooltip
  },
  props: {
    pages: {
      type: Array,
      required: true
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
    validationSettings: {
      type: Object,
      default: () => ({})
    }
  },
  inject: ['siteSettings'],
  data() {
    return {
      // Status mappings
      statusMappings: {
        listed: { label: 'Listed', dotClass: 'k-meta-kit-status-dot-listed' },
        unlisted: { label: 'Unlisted', dotClass: 'k-meta-kit-status-dot-unlisted' },
        draft: { label: 'Draft', dotClass: 'k-meta-kit-status-dot-draft' }
      }
    };
  },
  methods: {
    shouldAppendSiteName(type) {
      return shouldAppendSiteName(this.siteSettings, type);
    },

    isPageSelected(pageId) {
      return this.selectedPages.includes(pageId);
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

    getTableTitleStatusClass(page) {
      if (page.id === 'site') return '';
      return this.getStatusClass(page, this.getTitleLength(page, 'meta'), 'title');
    },

    getTableOgTitleStatusClass(page) {
      if (page.id === 'site') return '';
      return this.getStatusClass(page, this.getTitleLength(page, 'og'), 'ogTitle');
    },

    // Tooltip methods
    tooltipText(content, inheritanceSource, showContent) {
      return buildTooltipText(content, inheritanceSource, showContent);
    },

    // Join tooltip parts with newlines only when both have content
    joinTooltipParts(base, reason) {
      if (!base && !reason) return '';
      if (!base) return reason;
      if (!reason) return base;
      return `${base}\n\n${reason}`;
    },

    getTitleTooltip(page, showContent = true) {
      if (!page.title && !page.metaTitle) return 'No title';
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
      const reason = this.getLengthValidationReason(page, 'title', this.getTitleLength(page, 'meta'));
      return this.joinTooltipParts(base, reason);
    },

    getDescriptionTooltip(page, showContent = true) {
      const text = getEffectiveDescription(page, 'meta', this.siteSettings);
      if (!text) return 'No meta description';

      const source = getInheritanceSource(page, 'metaDescription', this.siteSettings);
      const base = this.tooltipText(text, source, showContent);
      const reason = this.getLengthValidationReason(page, 'description', text.length);
      return this.joinTooltipParts(base, reason);
    },

    getOgTitleTooltip(page, showContent = true) {
      if (!page.title && !page.ogTitle && !page.metaTitle) return 'No OG title';
      if (page.id === 'site') {
        return showContent ? (page.hasOgTitle ? page.ogTitle : page.title) : '';
      }

      const source = getInheritanceSource(page, 'ogTitle', this.siteSettings);
      const tooltip = buildTitleWithSiteName(
        getTableTitleDisplay(page, this.siteSettings, 'og').effectiveTitle,
        this.siteSettings,
        'og'
      );

      const base = this.tooltipText(tooltip, source, showContent);
      const reason = this.getLengthValidationReason(page, 'ogTitle', this.getTitleLength(page, 'og'));
      return this.joinTooltipParts(base, reason);
    },

    getOgDescriptionTooltip(page, showContent = true) {
      const text = getEffectiveDescription(page, 'og', this.siteSettings);
      if (!text) return 'No OG description';

      const source = getInheritanceSource(page, 'ogDescription', this.siteSettings);
      const base = this.tooltipText(text, source, showContent);
      const reason = this.getLengthValidationReason(page, 'ogDescription', text.length);
      return this.joinTooltipParts(base, reason);
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
      return this.getStatusClass(page, desc?.length || 0, 'description');
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
      return this.getStatusClass(page, desc?.length || 0, 'ogDescription');
    },

    // Slug methods
    getSlug(page) {
      if (page.id === 'site') return '';
      const parts = page.id.split('/');
      return parts[parts.length - 1];
    },

    getSlugWordCount(slug) {
      if (!slug) return 0;
      return slug.split(/[-_]/).filter(word => word.length > 0).length;
    },

    getSlugStatusClass(page) {
      if (page.id === 'site') return '';

      const slug = this.getSlug(page);
      const wordCount = this.getSlugWordCount(slug);
      const length = slug.length;
      const numSlashes = page.id.split('/').length - 1;
      const cfg = getSlugValidationConfig(page, this.validationSettings);
      const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;

      const issues = getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });

      if (issues.some(issue => issue.severity === 'error')) return 'k-meta-kit-status-error';
      if (issues.some(issue => issue.severity === 'warning')) return 'k-meta-kit-status-warning';
      return 'k-meta-kit-status-optimal';
    },

    getSlugTooltip(page) {
      if (page.id === 'site') return 'Site root';

      const slug = this.getSlug(page);
      const wordCount = this.getSlugWordCount(slug);
      const length = slug.length;
      const numSlashes = page.id.split('/').length - 1;
      const cfg = getSlugValidationConfig(page, this.validationSettings);
      const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
      const issues = getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });

      const statusClass = this.getSlugStatusClass(page);
      const status = statusClass === 'k-meta-kit-status-error' ? 'error'
        : (statusClass === 'k-meta-kit-status-warning' ? 'warning' : 'ok');

      const reasons = issues.length
        ? `\n\nWhy ${status}:\n` + issues
          .map(i => i.severity === 'warning'
            ? `${i.key} ${i.value} is outside optimal (${i.optimal}), but within warning (${i.warning}).`
            : `${i.key} ${i.value} is outside warning (${i.warning}). Optimal is ${i.optimal}.`
          ).join('\n')
        : '';

      return `Slug: ${slug}\n\nDepth: ${numSlashes}\nWords: ${wordCount}\nLength: ${length} characters\n\nRanges (optimal / warning):\n\nDepth: ${cfg.depth.optimal.min}-${cfg.depth.optimal.max} / ${cfg.depth.warning.min}-${cfg.depth.warning.max}\nWords: ${cfg.words.optimal.min}-${cfg.words.optimal.max} / ${cfg.words.warning.min}-${cfg.words.warning.max}\nLength: ${cfg.length.optimal.min}-${cfg.length.optimal.max} / ${cfg.length.warning.min}-${cfg.length.warning.max}\nAvg word length: ${cfg.wordLength.optimal.min}-${cfg.wordLength.optimal.max} / ${cfg.wordLength.warning.min}-${cfg.wordLength.warning.max}${reasons}`;
    },

    // Status display helpers
    getStatusLabel(page) {
      if (!page.status) return '—';
      return this.statusMappings[page.status]?.label ||
             page.status.charAt(0).toUpperCase() + page.status.slice(1);
    },

    getStatusDotClass(page) {
      return page.status ? (this.statusMappings[page.status]?.dotClass || '') : '';
    }
  }
};
</script>
