import './index.css';
import MetaKitView from './components/MetaKitView.vue';
import MetaKitBulkEditDialog from './components/parts/edit/MetaKitBulkEditDialog.vue';
import MetaKitBulkGenerateDialog from './components/parts/edit/MetaKitBulkGenerateDialog.vue';
import MetaKitReviewDialog from './components/parts/edit/MetaKitReviewDialog.vue';
import MetaKitSinglePageDialog from './components/parts/edit/MetaKitSinglePageDialog.vue';
import MetaKitSuggestionsDialog from './components/parts/edit/MetaKitSuggestionsDialog.vue';
import MkTitle from './fields/mk-title/index.vue';
import MkDescription from './fields/mk-description/index.vue';
import MkSlugInfo from './fields/mk-slug-info/index.vue';
import MkReview from './fields/mk-review/index.vue';
import SeoPreview from './fields/seo-preview/index.vue';

panel.plugin('tearoom1/meta-kit', {
  components: {
    'meta-kit-view': MetaKitView,

    // Dialogs are opened through the Panel's dialog state
    // (see composables/dialogs.js), so they are global components
    'k-meta-kit-bulk-edit-dialog': MetaKitBulkEditDialog,
    'k-meta-kit-bulk-generate-dialog': MetaKitBulkGenerateDialog,
    'k-meta-kit-review-dialog': MetaKitReviewDialog,
    'k-meta-kit-single-page-dialog': MetaKitSinglePageDialog,
    'k-meta-kit-suggestions-dialog': MetaKitSuggestionsDialog
  },
  fields: {
    'mk-title': MkTitle,
    'mk-description': MkDescription,
    'mk-slug-info': MkSlugInfo,
    'mk-review': MkReview,
    'seo-preview': SeoPreview
  }
});
