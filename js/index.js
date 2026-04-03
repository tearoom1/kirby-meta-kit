
import './index.css';
import SeoPreview from './sections/seo-preview.vue';
import MetaKitView from './components/MetaKitView.vue';
import MkTitle from './fields/mk-title/index.vue';
import MkDescription from './fields/mk-description/index.vue';
import MkSlugInfo from './fields/mk-slug-info/index.vue';
import MkReview from './fields/mk-review/index.vue';

panel.plugin('tearoom1/meta-kit', {
  components: {
    'meta-kit-view': MetaKitView
  },
  sections: {
    'seo-preview': SeoPreview
  },
  fields: {
    'mk-title': MkTitle,
    'mk-description': MkDescription,
    'mk-slug-info': MkSlugInfo,
    'mk-review': MkReview,
  }
});
