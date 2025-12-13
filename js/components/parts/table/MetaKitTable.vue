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
        <th v-if="!showPreview">Description</th>
        <th v-if="!showPreview">OG Title</th>
        <th v-if="!showPreview">OG Description</th>
        <th>OG Image</th>
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
            <span class="k-meta-kit-table-page-id">{{ page.template }}</span>
          </div>
        </td>
        <td v-if="!showPreview">
          <span
            :class="[getSlugStatusClass(page), 'k-meta-kit-table-tooltip']"
            :title="getSlugTooltip(page)"
          >
            {{ page.id }}
          </span>
        </td>

        <!-- Title Column (Meta or OG based on mode) -->
        <td v-if="showPreview">
          <template v-if="previewMode === 'meta'">
            <span
              :class="['k-meta-kit-table-preview-indicator']"
              :data-status="getStatusValue(getTableTitleStatusClass(page))"
              :title="getTitleTooltip(page)"
            >
                {{ getFullTitlePreview(page, 'meta') }}
            </span>
          </template>
          <template v-else>
            <span
              :class="['k-meta-kit-table-preview-indicator']"
              :data-status="getStatusValue(getStatusClass(page.hasOgTitle || page.hasMetaTitle, page.hasOgTitle ? page.ogTitleLength : page.metaTitleLength, 'ogTitle'))"
              :title="getOgTitleTooltip(page)"
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
          </template>
        </td>

        <!-- Description Column (Meta or OG based on mode) -->
        <td v-if="showPreview">
          <template v-if="previewMode === 'meta'">
            <span class="k-meta-kit-table-preview-indicator"
                  :data-status="getStatusValue(getStatusClass(page.hasMetaDescription, page.metaDescriptionLength, 'description'))"
            >
                {{ page.metaDescription || '—' }}
            </span>
          </template>
          <template v-else>
            <span class="k-meta-kit-table-preview-indicator"
                  :data-status="getStatusValue(getStatusClass(
                    page.hasOgDescription || page.hasMetaDescription,
                  page.hasOgDescription? page.ogDescriptionLength :page.metaDescriptionLength,
                  'ogDescription'))"
            >
                <template v-if="page.hasOgDescription">
                  {{ page.ogDescription }}
                </template>
                <template v-else>
                  <span class="k-meta-kit-table-preview-fallback">
                    {{ page.metaDescription || '—' }}
                  </span>
                </template>
            </span>
          </template>
        </td>

        <!-- Title Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
            <span :class="[ getTableTitleStatusClass(page), 'k-meta-kit-table-tooltip']"
                  :title="getTitleTooltip(page)">
                {{ getFullTitleLength(page) }}
            </span>
        </td>

        <!-- Description Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
            <span
              :class="[getStatusClass(page.hasMetaDescription, page.metaDescriptionLength, 'description'), 'k-meta-kit-table-tooltip']"
              :title="getDescriptionTooltip(page)">
                {{ page.hasMetaDescription ? page.metaDescriptionLength : '—' }}
            </span>
        </td>

        <!-- OG Title Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
            <span :class="[getTableOgTitleStatusClass(page), 'k-meta-kit-table-tooltip']"
                  :title="getOgTitleTooltip(page)">
                {{ page.hasOgTitle ? getFullOgTitleLength(page) : '—' }}
            </span>
        </td>

        <!-- OG Description Column only when not preview -->
        <td v-if="!showPreview" class="k-meta-kit-table-center">
            <span
              :class="[getStatusClass(page.hasOgDescription, page.ogDescriptionLength, 'ogDescription'), 'k-meta-kit-table-tooltip']"
              :title="getOgTitleTooltip(page)">
                {{ page.hasOgDescription ? page.ogDescriptionLength : '—' }}
            </span>
        </td>

        <!-- OG Image (only in OG mode) -->
        <td class="k-meta-kit-table-center">
          <k-icon v-if="page.hasOgImage" type="check" class="k-meta-kit-icon-success"/>
          <span v-else>—</span>
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
export default {
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
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
      return this.getStatusClass(true, fullLength, 'title', titleToUse || '');
    },

    getTableOgTitleStatusClass(page) {
      if (page.id === 'site' || !page.hasOgTitle || page.ogTitle.length === 0) {
        return '';
      }

      const fullLength = this.getFullOgTitleLength(page);
      const titleToUse = page.hasOgTitle ? page.ogTitle : page.metaTitle;
      return this.getStatusClass(true, fullLength, 'ogTitle', titleToUse || '');
    },

    getStatusClass(hasValue, length, type) {
      if (!hasValue || !length || length === 0) return '';

      let ranges;
      if (type === 'title') {
        ranges = {optimal: {min: 50, max: 60}, warning: {min: 45, max: 66}};
      } else if (type === 'ogTitle') {
        ranges = {optimal: {min: 40, max: 60}, warning: {min: 35, max: 70}};
      } else if (type === 'ogDescription') {
        ranges = {optimal: {min: 150, max: 185}, warning: {min: 135, max: 200}};
      } else {
        // description
        ranges = {optimal: {min: 140, max: 160}, warning: {min: 126, max: 176}};
      }

      if (length >= ranges.optimal.min && length <= ranges.optimal.max) {
        return 'k-meta-kit-status-success';
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

    getTitleTooltip(page) {
      const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;

      if (!titleToUse) {
        return 'No title';
      }

      if (page.id === 'site') {
        if (page.hasMetaTitle && page.metaTitle) {
          return `${page.metaTitle}`;
        }
        return `${page.title}`;
      }

      let tooltip = '';
      if (page.hasMetaTitle && page.metaTitle) {
        tooltip = `${page.metaTitle}`;
      } else {
        tooltip = `${page.title}`;
      }

      if (this.shouldAppendSiteName('meta') && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        const preview = `${titleToUse} ${separator} ${siteName}`;
        tooltip = `${preview} (${tooltip})`;
      }

      return tooltip;
    },

    getDescriptionTooltip(page) {
      if (!page.hasMetaDescription || !page.metaDescription) {
        return 'No meta description';
      }

      const desc = page.metaDescription;
      if (desc.length > 200) {
        return desc.substring(0, 200) + '...';
      }

      return desc;
    },

    getOgTitleTooltip(page) {
      const titleToUse = page.hasOgTitle ? page.ogTitle : page.hasMetaTitle ? page.metaTitle : page.title;

      if (!titleToUse) {
        return 'No OG title';
      }

      if (page.id === 'site') {
        if (page.hasOgTitle && page.ogTitle) {
          return `${page.ogTitle}`;
        }
        return `${page.title}`;
      }

      let tooltip = '';
      if (page.hasOgTitle && page.ogTitle) {
        tooltip = `${page.ogTitle}`;
      } else if (page.hasMetaTitle && page.metaTitle) {
        tooltip = `${page.metaTitle}`;
      } else {
        tooltip = `${page.title}`;
      }

      if (this.shouldAppendSiteName('og') && this.siteSettings.siteMetaTitle) {
        const separator = this.siteSettings.titleSeparator || '|';
        const siteName = this.siteSettings.siteMetaTitle || '';
        const preview = `${titleToUse} ${separator} ${siteName}`;
        tooltip = `${preview} (${tooltip})`;
      }

      return tooltip;
    },

    getOgDescriptionTooltip(page) {
      if (!page.hasOgDescription || !page.ogDescription) {
        return 'No OG description';
      }

      const desc = page.ogDescription;
      if (desc.length > 200) {
        return desc.substring(0, 200) + '...';
      }

      return desc;
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

      return `Slug: ${slug}\nWords: ${wordCount}\nLength: ${length} characters\n\nRecommendation:\n\nCore pages: 1 word, ≤ 15 chars.\nArticles: 4-8 words, ≤ 60 chars. \nNesting: 2 levels.`;
    }
  }
};
</script>
