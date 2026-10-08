<template>
  <div class="k-mk2-table-wrap">
    <div class="k-mk2-table-scroll">
      <table class="k-mk2-table">
        <thead>
          <tr class="k-mk2-groups">
            <th colspan="3"></th>
            <th colspan="2">{{ $t('meta-kit.v2.group.search') }}</th>
            <th colspan="3">{{ $t('meta-kit.v2.group.share') }}</th>
            <th></th>
          </tr>
          <tr>
            <th class="k-mk2-check">
              <input
                type="checkbox"
                :checked="isAllSelected"
                :aria-label="$t('meta-kit.v2.selectAll')"
                @change="$emit('toggle-select-all')"
              />
            </th>
            <th>{{ $t('meta-kit.table.page') }}</th>
            <th>{{ $t('meta-kit.field.slug') }}</th>
            <th>{{ $t('meta-kit.v2.col.title') }}</th>
            <th>{{ $t('meta-kit.v2.col.description') }}</th>
            <th>{{ $t('meta-kit.v2.col.title') }}</th>
            <th>{{ $t('meta-kit.v2.col.description') }}</th>
            <th>{{ $t('meta-kit.v2.col.image') }}</th>
            <th class="k-mk2-actions">
              <Tooltip :content="legend">
                <span class="k-mk2-info" tabindex="0" :aria-label="legend">ⓘ</span>
              </Tooltip>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="page in pages"
            :key="page.id"
            :class="{ 'is-selected': isPageSelected(page.id) }"
          >
            <td class="k-mk2-check">
              <input
                type="checkbox"
                :checked="isPageSelected(page.id)"
                :aria-label="page.title"
                @change="$emit('toggle-page', page.id)"
              />
            </td>
            <td class="k-mk2-page">
              <span class="k-mk2-title">
                <k-icon
                  v-if="page.id === 'site'"
                  type="globe"
                  class="k-mk2-status is-site"
                />
                <k-icon
                  v-else-if="statusIcon(page)"
                  :type="statusIcon(page).icon"
                  :class="['k-mk2-status', 'is-' + page.status]"
                  :title="getStatusLabel(page)"
                  :aria-label="getStatusLabel(page)"
                />
                <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
              </span>
              <span class="k-mk2-sub">
                {{ page.template }}
                <span v-if="page.robots && page.robots.includes('noindex')" class="k-mk2-pill is-warning">noindex</span>
              </span>
            </td>
            <td class="k-mk2-slug-col">
              <Tooltip :content="getSlugTooltip(page)">
                <span class="k-mk2-slug">
                  <i :class="['k-mk2-dot', dot(page, 'slug')]"></i>
                  <span><span class="k-mk2-slug-parent">{{ slugParent(page) }}</span>{{ slugName(page) }}</span>
                </span>
              </Tooltip>
            </td>
            <td>
              <Tooltip :content="getTitleTooltip(page)">
                <span class="k-mk2-cell">
                  <i :class="['k-mk2-dot', dot(page, 'title')]"></i>{{ getTitleLength(page, 'meta') || '—' }}
                  <span v-if="sourcePill(page, 'metaTitle')" class="k-mk2-pill">{{ sourcePill(page, 'metaTitle') }}</span>
                </span>
              </Tooltip>
            </td>
            <td>
              <Tooltip :content="getDescriptionTooltip(page)">
                <span class="k-mk2-cell">
                  <i :class="['k-mk2-dot', dot(page, 'description')]"></i>{{ descriptionLength(page, 'meta') || '—' }}
                  <span v-if="sourcePill(page, 'metaDescription')" class="k-mk2-pill">{{ sourcePill(page, 'metaDescription') }}</span>
                </span>
              </Tooltip>
            </td>
            <td>
              <Tooltip :content="getOgTitleTooltip(page)">
                <span class="k-mk2-cell">
                  <i :class="['k-mk2-dot', dot(page, 'ogTitle')]"></i>{{ getTitleLength(page, 'og') || '—' }}
                  <span v-if="sourcePill(page, 'ogTitle')" class="k-mk2-pill">{{ sourcePill(page, 'ogTitle') }}</span>
                </span>
              </Tooltip>
            </td>
            <td>
              <Tooltip :content="getOgDescriptionTooltip(page)">
                <span class="k-mk2-cell">
                  <i :class="['k-mk2-dot', dot(page, 'ogDescription')]"></i>{{ descriptionLength(page, 'og') || '—' }}
                  <span v-if="sourcePill(page, 'ogDescription')" class="k-mk2-pill">{{ sourcePill(page, 'ogDescription') }}</span>
                </span>
              </Tooltip>
            </td>
            <td>
              <span class="k-mk2-cell">
                <i :class="['k-mk2-dot', dot(page, 'ogImage')]"></i>{{ imageLabel(page) }}
              </span>
            </td>
            <td class="k-mk2-actions">
              <k-button icon="edit" size="sm" :title="$t('meta-kit.table.edit')" @click="$emit('edit-page', page.id)" />
              <k-button v-if="aiEnabled" icon="sparkling" size="sm" :title="$t('meta-kit.table.generate')" @click="$emit('generate-page', page.id)" />
              <k-button
                v-if="canReviewPage(page)"
                icon="preview"
                size="sm"
                :title="$t('meta-kit.table.review')"
                @click="$emit('review-page', page.id, $t('meta-kit.review.titleFor', { page: page.title }))"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
// Temporary design comparison: same data and tooltips as MetaKitTable,
// new layout (grouped headers, status dots, readable source markers)
import MetaKitTable from '../table/MetaKitTable.vue';
import { classifyPageField } from '../../../composables/panelState.js';
import { getEffectiveDescription, getInheritanceSource } from '../../../composables/useInheritance.js';

const SOURCE_KEYS = {
  'site': 'meta-kit.v2.source.site',
  'page title': 'meta-kit.v2.source.title',
  'meta title': 'meta-kit.v2.source.meta',
  'meta description': 'meta-kit.v2.source.meta'
};

export default {
  extends: MetaKitTable,
  computed: {
    legend() {
      return [
        this.$t('meta-kit.v2.legend.levels'),
        '',
        this.$t('meta-kit.v2.legend.sources')
      ].join('\n');
    }
  },
  methods: {
    dot(page, field) {
      return `is-${classifyPageField(page, field, this.classifierContext)}`;
    },
    descriptionLength(page, type) {
      return getEffectiveDescription(page, type, this.siteSettings)?.length || 0;
    },
    sourcePill(page, fieldType) {
      const source = getInheritanceSource(page, fieldType, this.siteSettings);
      if (!source) return '';
      return SOURCE_KEYS[source] ? this.$t(SOURCE_KEYS[source]) : String(source).toUpperCase();
    },
    // Long paths wrap; the parent path is dimmed so the slug itself stands out
    slugParent(page) {
      if (page.id === 'site' || !page.id.includes('/')) return '';
      return page.id.slice(0, page.id.lastIndexOf('/') + 1);
    },
    slugName(page) {
      if (page.id === 'site') return '/';
      return page.id.slice(page.id.lastIndexOf('/') + 1);
    },
    // Same status icons as Kirby's page lists
    statusIcon(page) {
      const icons = { listed: 'status-listed', unlisted: 'status-unlisted', draft: 'status-draft' };
      return icons[page.status] ? { icon: icons[page.status] } : null;
    },
    imageLabel(page) {
      if (page.hasOgImage) return this.$t('meta-kit.v2.image.own');
      if (this.siteSettings?.siteHasOgImage) return this.$t('meta-kit.v2.source.site');
      return this.$t('meta-kit.v2.image.none');
    }
  }
};
</script>
