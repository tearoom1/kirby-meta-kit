# Meta Kit Area Setup

## ✅ Created Files

### Backend (PHP)
- **`src/areas/meta-kit.php`** - Area configuration
- **`src/MetaKitController.php`** - Controller with all logic

### Frontend (Vue.js)
- **`js/components/MetaKitView.vue`** - Main view component
- **`js/index.js`** - Updated to register the component
- **`js/index.css`** - Added Meta Kit styles

### API Routes Added
- `GET /api/meta-kit/pages` - List all pages with metadata status
- `POST /api/meta-kit/generate-description` - Generate description for one page
- `POST /api/meta-kit/generate-all` - Generate descriptions for all pages
- `GET /api/meta-kit/detect-legacy` - Detect legacy SEO fields
- `POST /api/meta-kit/convert-legacy` - Convert legacy data to Meta Kit format

## 🔧 Build Required

**IMPORTANT:** After making any changes, you must rebuild the JavaScript:

```bash
cd site/plugins/kirby-seo-ai
npm run build
```

Or for development with watch mode:

```bash
npm run dev
```

**Note:** Changes to `.vue` or `.css` files won't appear until you rebuild!

## 🎯 Features

### 1. **Page Overview Table**
Shows all pages with columns:
- Page title (links to panel)
- Template name
- Meta Title (with character count)
- Meta Description (with length validation)
- OG Image (check icon)
- NoIndex status (check icon)
- Generate button (per page)

### 2. **Stats Cards**
- Total pages
- Pages with descriptions
- Pages with OG images
- Pages with noIndex

### 3. **Bulk Actions**
- **Generate All Missing Descriptions** - AI-generates descriptions for all pages without one
- **Refresh** - Reload page data
- **Detect Legacy Data** - Scan for old SEO plugin fields

### 4. **Legacy Data Migration**
Automatically detects and converts metadata from other SEO plugins:
- `metatitle` → `seo.metaTitle`
- `metadescription` → `seo.metaDescription`
- `customtitle` → `seo.metaTitle`
- `ogimage` → `seo.ogImage`

Converts individually or shows a list with convert buttons.

### 5. **Visual Indicators**
- Character count with color coding (orange for < 50 or > 160)
- Check icons for present fields
- Loading spinners for async operations
- Success/error notifications

## 📍 Access

After building, access the Meta Kit area at:

**Panel → Meta Kit** (in the left sidebar)

## 🎨 Styling

All styles follow Kirby's design system with:
- CSS custom properties for theming
- Dark mode support
- Responsive grid layouts
- Consistent spacing and typography
