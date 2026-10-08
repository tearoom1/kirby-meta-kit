<template>
  <div class="k-mk2-table-wrap">
    <div class="k-mk2-table-scroll">
      <table class="k-mk2-table" :class="{ 'is-content': showPreview }">
        <thead>
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
            <template v-if="!showPreview">
              <th>{{ $t('meta-kit.field.slug') }}</th>
              <th>{{ $t('meta-kit.field.metaTitle') }}</th>
              <th>{{ $t('meta-kit.field.metaDescription.short') }}</th>
              <th>{{ $t('meta-kit.field.ogTitle') }}</th>
              <th>{{ $t('meta-kit.field.ogDescription.short') }}</th>
              <th>{{ $t('meta-kit.field.ogImage.short') }}</th>
            </template>
            <template v-else>
              <th>{{ $t(isOg ? 'meta-kit.field.ogTitle' : 'meta-kit.field.metaTitle') }}</th>
              <th>{{ $t(isOg ? 'meta-kit.field.ogDescription' : 'meta-kit.field.metaDescription') }}</th>
              <th v-if="isOg">{{ $t('meta-kit.field.ogImage.short') }}</th>
            </template>
            <th class="k-mk2-actions">
              <Tooltip>
                <template #tip>
                  <div class="k-mk2-tip-body">
                    <p class="k-mk2-tip-line"><i class="k-mk2-dot is-error"></i>{{ $t('meta-kit.v2.level.error') }} · <i class="k-mk2-dot is-warning"></i>{{ $t('meta-kit.v2.level.warning') }} · <i class="k-mk2-dot is-good"></i>{{ $t('meta-kit.v2.level.good') }}</p>
                    <p class="k-mk2-tip-line">{{ $t('meta-kit.v2.legend.sources') }}</p>
                  </div>
                </template>
                <span class="k-mk2-info" tabindex="0" :aria-label="$t('meta-kit.v2.legend.sources')">ⓘ</span>
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
                <k-icon v-if="page.id === 'site'" type="globe" class="k-mk2-status is-site" />
                <k-icon
                  v-else-if="statusIcon(page)"
                  :type="statusIcon(page)"
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

            <!-- Count view -->
            <template v-if="!showPreview">
              <td class="k-mk2-slug-col">
                <Tooltip>
                  <template #tip>
                    <div class="k-mk2-tip-body">
                      <p class="k-mk2-tip-text">{{ page.id === 'site' ? '/' : page.id }}</p>
                      <p v-for="row in slugRows(page)" :key="row.key" class="k-mk2-tip-line">
                        <i :class="['k-mk2-dot', 'is-' + row.level]"></i>{{ row.label }}: <strong>{{ row.value }}</strong>
                        <span class="k-mk2-muted"> · {{ $t('meta-kit.v2.review.optimal', { range: row.optimal }) }}</span>
                      </p>
                    </div>
                  </template>
                  <span class="k-mk2-slug">
                    <i :class="['k-mk2-dot', dot(page, 'slug')]"></i>
                    <span><span class="k-mk2-slug-parent">{{ slugParent(page) }}</span>{{ slugName(page) }}</span>
                  </span>
                </Tooltip>
              </td>
              <td v-for="field in COUNT_FIELDS" :key="field">
                <Tooltip>
                  <template #tip><meta-kit-field-tip v-bind="tip(page, field)" /></template>
                  <span class="k-mk2-cell">
                    <i :class="['k-mk2-dot', dot(page, CLASSIFY[field])]"></i>{{ tip(page, field).length || '—' }}
                    <span v-if="tip(page, field).source" class="k-mk2-pill">{{ tip(page, field).source.label }}</span>
                  </span>
                </Tooltip>
              </td>
              <td>
                <Tooltip>
                  <template #tip><div class="k-mk2-tip-body"><p class="k-mk2-tip-line">{{ imageTip(page) }}</p></div></template>
                  <span class="k-mk2-cell">
                    <i :class="['k-mk2-dot', dot(page, 'ogImage')]"></i><span v-if="imageLabel(page)">{{ imageLabel(page) }}</span>
                  </span>
                </Tooltip>
              </td>
            </template>

            <!-- Content views (meta / OG texts) -->
            <template v-else>
              <td v-for="field in contentFields" :key="field" class="k-mk2-content">
                <Tooltip>
                  <template #tip><meta-kit-field-tip v-bind="tip(page, field)" /></template>
                  <div class="k-mk2-content-text" :class="{ 'is-inherited': !!tip(page, field).source }">
                    <i :class="['k-mk2-dot', dot(page, CLASSIFY[field])]"></i>
                    <span>{{ tip(page, field).text || '—' }}</span>
                  </div>
                  <div v-if="tip(page, field).length" class="k-mk2-sub k-mk2-content-meta">
                    {{ $t('meta-kit.chars', { count: tip(page, field).length }) }}
                    <span v-if="tip(page, field).source" class="k-mk2-pill">{{ tip(page, field).source.label }}</span>
                  </div>
                </Tooltip>
              </td>
              <td v-if="isOg">
                <span class="k-mk2-cell">
                  <i :class="['k-mk2-dot', dot(page, 'ogImage')]"></i><span v-if="imageLabel(page)">{{ imageLabel(page) }}</span>
                </span>
              </td>
            </template>

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
// Temporary design comparison: same data as MetaKitTable, new layout
// (grouped headers, status dots, readable source markers, rich tooltips)
import MetaKitTable from '../table/MetaKitTable.vue';
import MetaKitFieldTip from './MetaKitFieldTip.vue';
import { classifyPageField, getSlugAnalysis } from '../../../composables/panelState.js';
import { getEffectiveDescription, getInheritanceSource } from '../../../composables/useInheritance.js';
import { getRangesForPageAndType, isOutsideRange } from '../../../composables/useValidation.js';
import { getTableTitleDisplay } from '../../../composables/panelDisplay.js';

const COUNT_FIELDS = ['metaTitle', 'metaDescription', 'ogTitle', 'ogDescription'];
// Field → classifier / range type
const CLASSIFY = { metaTitle: 'title', metaDescription: 'description', ogTitle: 'ogTitle', ogDescription: 'ogDescription' };
const SOURCES = {
  'site': ['meta-kit.v2.source.site', 'meta-kit.v2.source.site.help'],
  'page title': ['meta-kit.v2.source.title', 'meta-kit.v2.source.title.help'],
  'meta title': ['meta-kit.v2.source.meta', 'meta-kit.field.metaTitle'],
  'meta description': ['meta-kit.v2.source.meta', 'meta-kit.field.metaDescription']
};

export default {
  extends: MetaKitTable,
  components: { MetaKitFieldTip },
  data() {
    return { COUNT_FIELDS, CLASSIFY };
  },
  computed: {
    isOg() {
      return this.previewMode === 'og';
    },
    contentFields() {
      return this.isOg ? ['ogTitle', 'ogDescription'] : ['metaTitle', 'metaDescription'];
    }
  },
  methods: {
    dot(page, field) {
      return `is-${classifyPageField(page, field, this.classifierContext)}`;
    },
    // Everything a cell and its tooltip show for one field
    tip(page, field) {
      const og = field.startsWith('og');
      const isTitle = field.endsWith('Title');
      const text = isTitle
        ? (getTableTitleDisplay(page, this.siteSettings, og ? 'og' : 'meta').fullTitle || '')
        : (getEffectiveDescription(page, og ? 'og' : 'meta', this.siteSettings) || '');
      const length = isTitle ? this.getTitleLength(page, og ? 'og' : 'meta') : text.length;

      const notes = [];
      if (!og) {
        const duplicates = this.duplicates?.[isTitle ? 'title' : 'description']?.[page.id];
        if (duplicates?.length) {
          const titles = duplicates.map((id) => this.allPages.find((other) => other.id === id)?.title || id);
          notes.push(this.$t(isTitle ? 'meta-kit.reason.duplicateTitle' : 'meta-kit.reason.duplicateDescription', { pages: titles.join(', ') }));
        }
      }

      return {
        text,
        length,
        ranges: page.id === 'site' && isTitle ? null : getRangesForPageAndType(page, CLASSIFY[field], this.validationSettings),
        source: this.source(page, field),
        notes
      };
    },
    source(page, field) {
      const source = getInheritanceSource(page, field, this.siteSettings);
      if (!source) return null;
      if (SOURCES[source]) {
        const [label, help] = SOURCES[source];
        return { label: this.$t(label), help: this.$t('meta-kit.v2.tip.inherited', { source: this.$t(help) }) };
      }
      // Inherited from the main language
      return { label: String(source).toUpperCase(), help: this.$t('meta-kit.v2.tip.mainLanguage', { language: String(source).toUpperCase() }) };
    },
    slugRows(page) {
      if (page.id === 'site') return [];
      const { wordCount, length, numSlashes, cfg } = getSlugAnalysis(page, this.validationSettings);
      const row = (key, value, rule) => ({
        key,
        label: this.$t(`meta-kit.slug.${key}`),
        value,
        optimal: `${rule.optimal.min}–${rule.optimal.max}`,
        level: isOutsideRange(value, rule.warning) ? 'error' : (isOutsideRange(value, rule.optimal) ? 'warning' : 'good')
      });
      return [row('depth', numSlashes, cfg.depth), row('words', wordCount, cfg.words), row('length', length, cfg.length)];
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
      return { listed: 'status-listed', unlisted: 'status-unlisted', draft: 'status-draft' }[page.status] || null;
    },
    // Only own and missing images get a word; the site image is the quiet default
    imageLabel(page) {
      if (page.hasOgImage) return this.$t('meta-kit.v2.image.own');
      if (this.siteSettings?.siteHasOgImage) return '';
      return this.$t('meta-kit.v2.image.none');
    },
    imageTip(page) {
      if (page.hasOgImage) return this.$t('meta-kit.ogImage.own');
      if (this.siteSettings?.siteHasOgImage) return this.$t('meta-kit.ogImage.site');
      return this.$t('meta-kit.ogImage.none');
    }
  }
};
</script>
