# Sitemap, robots.txt, Redirects, llms.txt and Schema.org

[← Back to the README](../README.md)

Everything Meta Kit serves outside the Panel: the XML sitemap, a dynamic robots.txt, redirects after slug changes, llms.txt, structured data and article metadata.


## Sitemap Generation

**Automatic Creation:**
XML sitemap available at `/sitemap.xml` with:
- All published pages (filtered by template and status)
- Multilingual support with hreflang
- Configurable priorities
- Last modified dates
- Page images (image sitemap)
- Styled XML view for human readability
- Cached; the cache is cleared whenever pages, files or the site change

**Configuration:**

```php
'sitemap.enabled' => true,
'sitemap.exclude' => ['error', 'drafts', 'admin'],  // Page IDs to exclude
'sitemap.includeUnlisted' => false,  // Include unlisted pages (default: false)
'sitemap.images' => true,  // List each page's images as <image:image> (default: true)
'sitemap.cache' => true,  // Cache the XML (default: true)
'sitemap.cacheDuration' => 60,  // Minutes; only matters for changes made outside Kirby, e.g. via FTP

// Change frequency configuration
'sitemap.changefreq.default' => 'monthly',  // Default for all pages
'sitemap.changefreq.templates' => [
    'home' => 'daily',
    'news' => 'weekly',
    'article' => 'weekly',
    'blog' => 'weekly',
    'imprint' => 'yearly',
    'privacy' => 'yearly',
],
'sitemap.changefreq.slugs' => [
    'impressum' => 'yearly',
    'datenschutz' => 'yearly',
    'contact' => 'monthly',
],

// Priority configuration
'sitemap.priority.templates' => [
    'home' => 1.0,
    'news' => 0.9,
    'article' => 0.8,
    'blog' => 0.9,
    'imprint' => 0.3,
    'privacy' => 0.3,
],
'sitemap.priority.slugs' => [
    'impressum' => 0.3,
    'datenschutz' => 0.3,
    'contact' => 0.5,
],
```

**Priority & Change Frequency Logic:**
- **Page-level override** (most specific) - Set in page SEO tab
- **Slug-based rules** - Matches page slug
- **Template-based rules** - Matches page template
- **Site defaults** - Set in panel or config
- **Fallback** - Built-in defaults

**Panel Settings (Site):**
- Visual page selector for exclusions
- Include unlisted pages toggle
- Default change frequency dropdown
- Homepage priority (0.1 - 1.0)
- Default page priority (0.0 - 1.0)

**Panel Settings (Per Page):**
- Sitemap Priority field - Override default for specific page
- Sitemap Change Frequency field - Override default for specific page
- Both fields optional - leave empty to use defaults

## Robots.txt Management

**Dynamic Generation:**
robots.txt available at `/robots.txt` with:
- User agent specific rules
- Bad bot blocking (AhrefsBot, SemrushBot, etc.)
- AI training crawler blocking (optional, see below)
- Sitemap reference
- Crawl delay configuration
- Custom directives

**Panel Configuration:**
1. Go to Site → Robots.txt
2. Enable "Custom Robots.txt"
3. Add user agent rules
4. Configure allowed/disallowed paths

**Config Override:**

```php
'robots' => [
    'enabled' => true,
    'blockBadBots' => true,
    'defaultRules' => true,
    'includeSitemap' => true,
    'rules' => [
        [
            'userAgent' => 'Googlebot',
            'allow' => ['/images/', '/assets/'],
            'disallow' => ['/panel/', '/api/'],
        ],
        [
            'userAgent' => 'AhrefsBot',
            'disallow' => ['/'],  // Block completely
        ],
    ],
]
```

**AI crawlers:** "Block AI Training Crawlers" (panel, Advanced tab) or `'robots' => ['blockAiCrawlers' => true]` disallows crawlers that collect content for training AI models: GPTBot, ClaudeBot, anthropic-ai, CCBot, Google-Extended, Applebot-Extended, Meta-ExternalAgent, Bytespider, Amazonbot and a few more. Assistants that fetch a page to answer a question and link to it (OAI-SearchBot, ChatGPT-User, Claude-SearchBot, Claude-User, Perplexity-User) stay allowed, so the site can still be cited in AI answers. Add them as custom rules if you want to block those as well.

## Redirects

When a page gets a new URL — a new slug in any language or a move to another parent — Meta Kit records the old path and redirects it (and the paths of all subpages) with **301** to the page's current URL. The target is stored as page UUID, so renaming a page several times never builds redirect chains, and renaming it back removes the now unused entry.

Entries are listed under **Site → SEO & Sitemap → Redirects**, where editors can also delete them or add their own. Redirects only apply when no page exists at the requested URL.

```php
'redirects.enabled' => true,  // default; set to false if another plugin handles redirects
```

## llms.txt

With "Provide llms.txt" (panel, Robots.txt → Advanced) or `'llms.enabled' => true`, Meta Kit serves [`/llms.txt`](https://llmstxt.org): a Markdown overview for AI assistants with the site title, the default description and a link list of the same pages as the sitemap (meta title and own meta description per page), grouped by top-level section. It is off by default and cached together with the sitemap.

## Schema.org Structured Data

**Automatic JSON-LD:**
- Organization data (site-wide)
- WebSite with site search
- WebPage with breadcrumbs
- Article markup for article templates (see below)

**Enable/Disable:**

```php
'schema.enabled' => true,
```

## Articles

Pages with an article template get `og:type` `article`, `article:published_time` (from their date field), `article:modified_time` and an `Article` schema with author (from Meta Author) and publisher:

```php
'opengraph.articleTemplates' => ['article', 'post'],  // default
'opengraph.dateField' => 'date',                      // default
```

All pages also get `og:site_name`, `og:image:alt`/`twitter:image:alt` from the image's alt text, and a `summary` Twitter card when there is no image (`summary_large_image` otherwise).
