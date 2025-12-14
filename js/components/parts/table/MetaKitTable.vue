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
            <div class="k-meta-kit-page-title-wrapper">
              <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
              <Tooltip :content="getStatusLabel(page)">
                <span :class="['k-meta-kit-status-dot', getStatusDotClass(page)]"></span>
              </Tooltip>
            </div>
            <span class="k-meta-kit-table-page-id">{{ page.template }}</span>
          </div>
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
              v-if="aiEnabled"
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
    }
  },
  inject: ['siteSettings'],
  methods: {
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

    getFullTitleLength(page) {
      if (page.id === 'site') {
        if (page.hasMetaTitle) {
          return page.metaTitleLength;
        }
        return page.title ? page.title.length : 0;
      }

      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
      if (!titleToUse) return 0;

      if (this.shouldAppendSiteName('meta') && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        const fullTitle = `${titleToUse} ${separator} ${siteName}`;
        return fullTitle.length;
      }

      return titleToUse.length;
    },

    getFullOgTitleLength(page) {
      if (page.id === 'site') {
        if (page.hasOgTitle) {
          return page.ogTitleLength;
        }
        return page.title ? page.title.length : 0;
      }

      // todo make sure to go recursive to fetch meta title
      const titleToUse = page.hasOgTitle ? page.ogTitle : page.hasMetaTitle ? page.metaTitle : page.title;
      if (!titleToUse) return 0;

      if (this.shouldAppendSiteName('og') && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        const fullTitle = `${titleToUse} ${separator} ${siteName}`;
        return fullTitle.length;
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

      const fullLength = this.getFullTitleLength(page);
      return this.getStatusClass(fullLength, 'title');
    },

    getTableOgTitleStatusClass(page) {
      if (page.id === 'site') {
        return '';
      }

      const fullLength = this.getFullOgTitleLength(page);
      return this.getStatusClass(fullLength, 'ogTitle');
    },

    getStatusClass(length, type) {
      if (!length || length === 0) return '';

      let ranges;
      if (type === 'title') {
        ranges = {optimal: {min: 20, max: 60}, warning: {min: 15, max: 75}};
      } else if (type === 'ogTitle') {
        ranges = {optimal: {min: 20, max: 60}, warning: {min: 15, max: 75}};
      } else if (type === 'ogDescription') {
        ranges = {optimal: {min: 150, max: 250}, warning: {min: 135, max: 300}};
      } else {
        // description
        ranges = {optimal: {min: 140, max: 160}, warning: {min: 126, max: 176}};
      }

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

      return this.tooltipText(tooltip, prefix, showContent);
    },

    getDescriptionTooltip(page, showContent = true) {
      if (page.hasMetaDescription && page.metaDescription) {
        return this.tooltipText(page.metaDescription, false, showContent);
      } else if (this.siteSettings.siteMetaDescription) {
        return this.tooltipText(this.siteSettings.siteMetaDescription, 'site', showContent);
      } else {
        return 'No meta description';
      }
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

      return this.tooltipText(tooltip, prefix, showContent);
    },

    getOgDescriptionTooltip(page, showContent = true) {
      if (page.hasOgDescription && page.ogDescription) {
        return this.tooltipText(page.ogDescription, false, showContent);
      } else if (page.hasMetaDescription && page.metaDescription) {
        return this.tooltipText(page.metaDescription, 'meta description', showContent);
      } else if (this.siteSettings.siteMetaDescription) {
        return this.tooltipText(this.siteSettings.siteMetaDescription, 'site', showContent);
      } else {
        return 'No OG description';
      }
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

    getSlugStatusClass(page) {
      if (page.id === 'site') {
        return '';
      }

      const slug = this.getSlug(page);
      const wordCount = this.getSlugWordCount(slug);
      const length = slug.length;
      const isCore = page.id.indexOf('/') === -1;
      const numSlashes = page.id.split('/').length - 1;

      // Optimal: 1-5 keywords AND under 60 characters
      // Warning: 6 keywords OR 60-70 characters
      // Error: 7+ keywords OR over 70 characters

      if (numSlashes <= 2 && wordCount <= 8 && length <= wordCount * 15 && length <= 60) {
        return '';
      }

      return 'k-meta-kit-status-warning';
    },

    getSlugTooltip(page) {
      if (page.id === 'site') {
        return 'Site root';
      }

      const slug = this.getSlug(page);
      const wordCount = this.getSlugWordCount(slug);
      const length = slug.length;

      return `Slug: ${slug}\nWords: ${wordCount}\nLength: ${length} characters\n\nGeneral recommendation:\n\nCore pages: 1 word, ≤ 15 chars.\nArticles: 4-8 words, ≤ 60 chars. \nNesting: <= 2 levels.`;
    },

    getStatusLabel(page) {
      if (!page.status) return '—';

      const status = page.status;

      // Map Kirby status to display labels
      if (status === 'listed') return 'Published';
      if (status === 'unlisted') return 'Unlisted';
      if (status === 'draft') return 'Draft';
      if (status === 'published') return 'Published';

      return status.charAt(0).toUpperCase() + status.slice(1);
    },

    getStatusDotClass(page) {
      if (!page.status) return '';

      const status = page.status;

      // Return appropriate CSS class based on status
      if (status === 'listed') {
        return 'k-meta-kit-status-dot-listed';
      }
      if (status === 'unlisted') {
        return 'k-meta-kit-status-dot-unlisted';
      }
      if (status === 'draft') {
        return 'k-meta-kit-status-dot-draft';
      }

      return '';
    },

    // Title display and inheritance
    getTitleDisplay(page) {
      const length = this.getFullTitleLength(page);
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
      return this.getStatusClass(length, 'description');
    },

    // OG Title display and inheritance
    getOgTitleDisplay(page) {
      const length = this.getFullOgTitleLength(page);
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
      return this.getStatusClass(length, 'ogDescription');
    }
  }
};
</script>
