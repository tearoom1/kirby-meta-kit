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
        <th v-if="showPreview">{{ previewMode === 'og' ? 'OG Title' : 'Title' }}</th>
        <th v-if="showPreview">{{ previewMode === 'og' ? 'OG Description' : 'Description' }}</th>
        <th v-if="!showPreview">Title</th>
        <th v-if="!showPreview">Desc.</th>
        <th v-if="!showPreview">OG Title</th>
        <th v-if="!showPreview">OG Desc.</th>
        <th>OG Img</th>
        <th v-if="!showPreview && previewMode === 'meta'">Robots</th>
        <th>Actions</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(page, index) in pages" :key="page.id">
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
              v-if="aiEnabled && false"
              icon="sparkling"
              size="sm"
              :disabled="page.hasMetaDescription"
              @click="$emit('generate-description', page.id)"
              title="Generate Description"
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
      // SEO length ranges for different field types
      seoRanges: {
        title: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
        ogTitle: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
        description: { optimal: { min: 140, max: 160 }, warning: { min: 126, max: 176 } },
        ogDescription: { optimal: { min: 150, max: 250 }, warning: { min: 135, max: 300 } }
      },
      // Status mappings
      statusMappings: {
        listed: { label: 'Listed', dotClass: 'k-meta-kit-status-dot-listed' },
        unlisted: { label: 'Unlisted', dotClass: 'k-meta-kit-status-dot-unlisted' },
        draft: { label: 'Draft', dotClass: 'k-meta-kit-status-dot-draft' }
      }
    };
  },
  methods: {
    getRangeConfigForPage(page) {
      const defaults = this.validationSettings?.ranges || {};
      const templates = this.validationSettings?.templates || {};

      const templateName = page?.template;
      const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};

      const templateRanges = templateConfig?.ranges || {};
      return {
        ...this.seoRanges,
        ...defaults,
        ...templateRanges
      };
    },

    getRangesForPageAndType(page, type) {
      const merged = this.getRangeConfigForPage(page);
      return merged?.[type];
    },

    // Helper method to check if site name should be appended based on type and settings
    shouldAppendSiteName(type) {
      if (this.siteSettings.appendSiteNameTo === undefined ||
        this.siteSettings.appendSiteNameTo === null ||
        this.siteSettings.appendSiteNameTo === '') {
        // Fallback to old behavior if appendSiteNameTo is not set
        return !!this.siteSettings.appendSiteName;
      }

      // appendSiteNameTo is a comma-separated string like "meta,og" or "meta" or "og"
      const setting = this.siteSettings.appendSiteNameTo;

      // Check if the type is in the comma-separated list
      return setting.split(',').map(s => s.trim()).includes(type);
    },

    isPageSelected(pageId) {
      return this.selectedPages.includes(pageId);
    },

    // Unified title length calculator
    getTitleLength(page, type = 'meta') {
      const isOg = type === 'og';

      if (page.id === 'site') {
        if (isOg && page.hasOgTitle) return page.ogTitleLength;
        if (!isOg && page.hasMetaTitle) return page.metaTitleLength;
        return page.title ? page.title.length : 0;
      }

      const titleToUse = isOg
        ? (page.hasOgTitle ? page.ogTitle : (page.hasMetaTitle ? page.metaTitle : page.title))
        : (page.hasMetaTitle ? page.metaTitle : page.title);

      if (!titleToUse) return 0;

      if (this.shouldAppendSiteName(type) && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        return `${titleToUse} ${separator} ${siteName}`.length;
      }

      return titleToUse.length;
    },

    getFullTitlePreview(page, type) {
      if (page.id === 'site') {
        return page.hasMetaTitle ? page.metaTitle : page.title;
      }

      var titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
      if (type === 'og' && page.hasOgTitle) {
        titleToUse = page.ogTitle;
      }
      if (!titleToUse) return '—';

      if (this.shouldAppendSiteName(type) && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        return `${titleToUse} ${separator} ${siteName}`;
      }

      return titleToUse;
    },

    getTableTitleStatusClass(page) {
      if (page.id === 'site') {
        return '';
      }

      const fullLength = this.getTitleLength(page, 'meta');
      return this.getStatusClass(page, fullLength, 'title');
    },

    getTableOgTitleStatusClass(page) {
      if (page.id === 'site') {
        return '';
      }

      const fullLength = this.getTitleLength(page, 'og');
      return this.getStatusClass(page, fullLength, 'ogTitle');
    },

    getStatusClass(page, length, type) {
      if (!length || length === 0) return '';

      const ranges = this.getRangesForPageAndType(page, type);
      if (!ranges) return '';

      if (length >= ranges.optimal.min && length <= ranges.optimal.max) {
        return '';
      }

      if (length >= ranges.warning.min && length <= ranges.warning.max) {
        return 'k-meta-kit-status-warning';
      }

      return 'k-meta-kit-status-error';
    },

    getStatusValue(statusClass) {
      if (!statusClass) return '';
      const match = statusClass.match(/k-meta-kit-status-(\w+)/);
      return match ? match[1] : '';
    },

    isOutsideRange(value, range) {
      if (!range) return false;
      if (typeof range.min === 'number' && value < range.min) return true;
      if (typeof range.max === 'number' && value > range.max) return true;
      return false;
    },

    getLengthValidationReason(page, type, length) {
      const ranges = this.getRangesForPageAndType(page, type);
      if (!ranges || !length) return '';

      const statusClass = this.getStatusClass(page, length, type);
      if (!statusClass) return '';

      const optimal = `${ranges.optimal.min}-${ranges.optimal.max}`;
      const warning = `${ranges.warning.min}-${ranges.warning.max}`;

      if (statusClass === 'k-meta-kit-status-warning') {
        return `\n\nWhy warning:\nLength ${length} is outside optimal (${optimal}), but within warning (${warning}).`;
      }

      return `\n\nWhy error:\nLength ${length} is outside warning (${warning}). Optimal is ${optimal}.`;
    },

    getTitleTooltip(page, showContent = true) {
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;

      if (!titleToUse) {
        return 'No title';
      }

      if (page.id === 'site') {
        if (!showContent) {
          return '';
        }
        if (page.hasMetaTitle && page.metaTitle) {
          return `${page.metaTitle}`;
        }
        return `${page.title}`;
      }

      let tooltip = '';
      let prefix = '';

      if (page.hasMetaTitle && page.metaTitle) {
        tooltip = page.metaTitle;
      } else {
        prefix = 'page title';
        tooltip = page.title;
      }

      if (this.shouldAppendSiteName('meta') && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        tooltip = `${titleToUse} ${separator} ${siteName}`;
      }

      const base = this.tooltipText(tooltip, prefix, showContent);
      const length = this.getTitleLength(page, 'meta');
      return `${base}${this.getLengthValidationReason(page, 'title', length)}`;
    },

    getDescriptionTooltip(page, showContent = true) {
      let text = null;
      let inheritance = false;
      if (page.hasMetaDescription && page.metaDescription) {
        text = page.metaDescription;
      } else if (this.siteSettings.siteMetaDescription) {
        text = this.siteSettings.siteMetaDescription;
        inheritance = 'site';
      }

      if (!text) return 'No meta description';

      const base = this.tooltipText(text, inheritance, showContent);
      const length = text.length;
      return `${base}${this.getLengthValidationReason(page, 'description', length)}`;
    },

    getOgTitleTooltip(page, showContent = true) {
      const titleToUse = page.hasOgTitle ? page.ogTitle : page.hasMetaTitle ? page.metaTitle : page.title;

      if (!titleToUse) {
        return 'No OG title';
      }

      if (page.id === 'site') {
        if (!showContent) {
          return '';
        }
        if (page.hasOgTitle && page.ogTitle) {
          return `${page.ogTitle}`;
        }
        return `${page.title}`;
      }

      let tooltip = '';
      let prefix = '';

      if (page.hasOgTitle && page.ogTitle) {
        tooltip = page.ogTitle;
      } else if (page.hasMetaTitle && page.metaTitle) {
        prefix = 'meta title';
        tooltip = page.metaTitle;
      } else {
        prefix = 'page title';
        tooltip = page.title;
      }

      if (this.shouldAppendSiteName('og') && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        tooltip = `${titleToUse} ${separator} ${siteName}`;
      }

      const base = this.tooltipText(tooltip, prefix, showContent);
      const length = this.getTitleLength(page, 'og');
      return `${base}${this.getLengthValidationReason(page, 'ogTitle', length)}`;
    },

    getOgDescriptionTooltip(page, showContent = true) {
      let text = null;
      let inheritance = false;
      if (page.hasOgDescription && page.ogDescription) {
        text = page.ogDescription;
      } else if (page.hasMetaDescription && page.metaDescription) {
        text = page.metaDescription;
        inheritance = 'meta description';
      } else if (this.siteSettings.siteMetaDescription) {
        text = this.siteSettings.siteMetaDescription;
        inheritance = 'site';
      }

      if (!text) return 'No OG description';

      const base = this.tooltipText(text, inheritance, showContent);
      const length = text.length;
      return `${base}${this.getLengthValidationReason(page, 'ogDescription', length)}`;
    },

    tooltipText(desc, inheritance, showContent) {
      let prefix = '';
      if (desc && desc.length > 200) {
        desc = desc.substring(0, 200) + '...';
      }
      if (inheritance) {
        prefix = 'Inherited from ' + inheritance;
      }
      if (showContent) {
        desc = (inheritance ? ':\n\n' : '') + desc;
        return prefix + desc;
      }
      return prefix;
    },

    getSlug(page) {
      // Extract the actual slug (last part) from the full page ID
      if (page.id === 'site') {
        return '';
      }

      const parts = page.id.split('/');
      return parts[parts.length - 1];
    },

    getSlugWordCount(slug) {
      // Count words in slug (separated by hyphens or underscores)
      if (!slug) return 0;
      return slug.split(/[-_]/).filter(word => word.length > 0).length;
    },

    getSlugValidationConfigForPage(page) {
      const defaults = this.validationSettings?.slug || {};
      const templates = this.validationSettings?.templates || {};

      const templateName = page?.template;
      const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
      const templateSlug = templateConfig?.slug || {};

      const mergeRule = (key, fallbackOptimal, fallbackWarning) => {
        const raw = { ...(defaults[key] || {}), ...(templateSlug[key] || {}) };

        // New format
        if (raw.optimal && raw.warning) {
          return {
            optimal: { ...fallbackOptimal, ...(raw.optimal || {}) },
            warning: { ...fallbackWarning, ...(raw.warning || {}) }
          };
        }

        // Backward compatibility: warningMax/errorMax
        const warningMax = typeof raw.warningMax === 'number' ? raw.warningMax : fallbackWarning.max;
        const errorMax = typeof raw.errorMax === 'number' ? raw.errorMax : fallbackWarning.max;
        return {
          optimal: { ...fallbackOptimal, max: warningMax },
          warning: { ...fallbackWarning, max: errorMax }
        };
      };

      return {
        depth: mergeRule('depth', { min: 0, max: 2 }, { min: 0, max: 3 }),
        words: mergeRule('words', { min: 1, max: 8 }, { min: 1, max: 10 }),
        length: mergeRule('length', { min: 1, max: 60 }, { min: 1, max: 70 }),
        wordLength: mergeRule('wordLength', { min: 1, max: 15 }, { min: 1, max: 20 })
      };
    },

    getSlugStatusClass(page) {
      if (page.id === 'site') {
        return '';
      }

      const slug = this.getSlug(page);
      const wordCount = this.getSlugWordCount(slug);
      const length = slug.length;
      const numSlashes = page.id.split('/').length - 1;

      const cfg = this.getSlugValidationConfigForPage(page);
      const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;

      const issues = this.getSlugValidationIssues({
        numSlashes,
        wordCount,
        length,
        avgWordLength,
        cfg
      });

      if (issues.some(issue => issue.severity === 'error')) return 'k-meta-kit-status-error';
      if (issues.some(issue => issue.severity === 'warning')) return 'k-meta-kit-status-warning';
      return '';
    },

    getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg }) {
      const checks = [
        { key: 'Depth', value: numSlashes, optimal: cfg.depth?.optimal, warning: cfg.depth?.warning },
        { key: 'Words', value: wordCount, optimal: cfg.words?.optimal, warning: cfg.words?.warning },
        { key: 'Length', value: length, optimal: cfg.length?.optimal, warning: cfg.length?.warning },
      ];

      const issues = [];
      for (const check of checks) {
        const optimal = `${check.optimal.min}-${check.optimal.max}`;
        const warning = `${check.warning.min}-${check.warning.max}`;

        if (this.isOutsideRange(check.value, check.warning)) {
          issues.push({
            severity: 'error',
            key: check.key,
            value: check.value,
            optimal,
            warning
          });
          continue;
        }

        if (this.isOutsideRange(check.value, check.optimal)) {
          issues.push({
            severity: 'warning',
            key: check.key,
            value: check.value,
            optimal,
            warning
          });
        }
      }

      return issues;
    },

    getSlugTooltip(page) {
      if (page.id === 'site') {
        return 'Site root';
      }

      const slug = this.getSlug(page);
      const wordCount = this.getSlugWordCount(slug);
      const length = slug.length;

      const cfg = this.getSlugValidationConfigForPage(page);

      const numSlashes = page.id.split('/').length - 1;
      const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
      const issues = this.getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });

      const statusClass = this.getSlugStatusClass(page);
      const status = statusClass === 'k-meta-kit-status-error'
        ? 'error'
        : (statusClass === 'k-meta-kit-status-warning' ? 'warning' : 'ok');

      const reasons = issues.length
        ? `\n\nWhy ${status}:\n` + issues
          .map(i => {
            if (i.severity === 'warning') {
              return `${i.key} ${i.value} is outside optimal (${i.optimal}), but within warning (${i.warning}).`;
            }
            return `${i.key} ${i.value} is outside warning (${i.warning}). Optimal is ${i.optimal}.`;
          })
          .join('\n')
        : '';

      return `Slug: ${slug}\n\nDepth: ${numSlashes}\nWords: ${wordCount}\nLength: ${length} characters\n\nRanges (optimal / warning):\n\nDepth: ${cfg.depth.optimal.min}-${cfg.depth.optimal.max} / ${cfg.depth.warning.min}-${cfg.depth.warning.max}\nWords: ${cfg.words.optimal.min}-${cfg.words.optimal.max} / ${cfg.words.warning.min}-${cfg.words.warning.max}\nLength: ${cfg.length.optimal.min}-${cfg.length.optimal.max} / ${cfg.length.warning.min}-${cfg.length.warning.max}\nAvg word length: ${cfg.wordLength.optimal.min}-${cfg.wordLength.optimal.max} / ${cfg.wordLength.warning.min}-${cfg.wordLength.warning.max}${reasons}`;
    },

    getStatusLabel(page) {
      if (!page.status) return '—';

      return this.statusMappings[page.status]?.label ||
             page.status.charAt(0).toUpperCase() + page.status.slice(1);
    },

    getStatusDotClass(page) {
      if (!page.status) return '';

      return this.statusMappings[page.status]?.dotClass || '';
    },

    // Title display and inheritance
    getTitleDisplay(page) {
      const length = this.getTitleLength(page, 'meta');
      if (!length) return '—';
      return this.isTitleInherited(page) ? `${length}` : length;
    },

    isTitleInherited(page) {
      if (page.id === 'site') return false;
      return !page.hasMetaTitle;
    },

    // Description display and inheritance
    getDescriptionDisplay(page) {
      if (page.hasMetaDescription) {
        return page.metaDescriptionLength;
      }
      // Inherit from site
      if (this.siteSettings.siteMetaDescription) {
        return `${this.siteSettings.siteMetaDescription.length}`;
      }
      return '—';
    },

    isDescriptionInherited(page) {
      return !page.hasMetaDescription && this.siteSettings.siteMetaDescription;
    },

    getDescriptionStatusClass(page) {
      const length = page.hasMetaDescription
        ? page.metaDescriptionLength
        : (this.siteSettings.siteMetaDescription ? this.siteSettings.siteMetaDescription.length : 0);
      return this.getStatusClass(page, length, 'description');
    },

    // OG Title display and inheritance
    getOgTitleDisplay(page) {
      const length = this.getTitleLength(page, 'og');
      if (!length) return '—';
      return this.isOgTitleInherited(page) ? `${length}` : length;
    },

    isOgTitleInherited(page) {
      if (page.id === 'site') return false;
      return !page.hasOgTitle;
    },

    // OG Description display and inheritance
    getOgDescriptionDisplay(page) {
      if (page.hasOgDescription) {
        return page.ogDescriptionLength;
      }
      if (page.hasMetaDescription) {
        return `${page.metaDescriptionLength}`;
      }
      // Inherit from site
      if (this.siteSettings.siteMetaDescription) {
        return `${this.siteSettings.siteMetaDescription.length}`;
      }
      return '—';
    },

    isOgDescriptionInherited(page) {
      if (page.hasOgDescription) return false;
      return page.hasMetaDescription || this.siteSettings.siteMetaDescription;
    },

    getOgDescriptionStatusClass(page) {
      let length = 0;
      if (page.hasOgDescription) {
        length = page.ogDescriptionLength;
      } else if (page.hasMetaDescription) {
        length = page.metaDescriptionLength;
      } else if (this.siteSettings.siteMetaDescription) {
        length = this.siteSettings.siteMetaDescription.length;
      }
      return this.getStatusClass(page, length, 'ogDescription');
    }
  }
};
</script>
