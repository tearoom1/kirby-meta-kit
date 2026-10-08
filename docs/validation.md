# Validation

[← Back to the README](../README.md)

Length ranges for titles and descriptions, slug rules, and how to set them globally or per template.


The validation system is Meta Kit's secret weapon for maintaining SEO quality across your entire site.

## How It Works

1. **Visual Feedback**
   - 🟢 **Green**: Optimal length (recommended for best SEO performance)
   - 🟠 **Orange**: Acceptable length (will work, but not ideal)
   - 🔴 **Red**: Too short or too long (should be fixed)

2. **Real-Time Validation**
   - Character counters update as you type
   - Validation messages guide editors
   - Accounts for site name in title length

3. **Template-Specific Rules**
   - Different page types can have different requirements
   - Example: Blog posts need longer, keyword-rich titles
   - Example: Product pages need concise, action-oriented descriptions

## Setting Validation Ranges

### Global Defaults

Set baseline rules for all pages in `site/config/config.php`:

```php
'validation' => [
    'ranges' => [
        'title' => [
            'optimal' => ['min' => 20, 'max' => 60],  // Green zone
            'warning' => ['min' => 15, 'max' => 75],  // Orange zone (outside = red)
        ],
        'description' => [
            'optimal' => ['min' => 140, 'max' => 160],
            'warning' => ['min' => 126, 'max' => 176],
        ],
    ],
]
```

### Template-Specific Overrides

Customize rules for specific page templates:

```php
'validation' => [
    'templates' => [
        'article' => [  // For blog posts
            'title' => [
                'optimal' => ['min' => 40, 'max' => 70],  // Longer titles for articles
            ],
            'description' => [
                'optimal' => ['min' => 150, 'max' => 160],  // Detailed descriptions
            ],
        ],
        'product' => [  // For products
            'title' => [
                'optimal' => ['min' => 25, 'max' => 45],  // Shorter, punchier titles
            ],
            'ogDescription' => [
                'optimal' => ['min' => 120, 'max' => 160],  // Social sharing focus
            ],
        ],
    ],
]
```

## Slug Validation

Meta Kit also validates URL structure:

```php
'validation' => [
    'slug' => [
        'depth' => [
            'optimal' => ['min' => 0, 'max' => 2],  // Prefer /category/page
            'warning' => ['min' => 0, 'max' => 3],  // Allow /a/b/c/page
        ],
        'words' => [
            'optimal' => ['min' => 1, 'max' => 8],  // Keywords in URL
        ],
        'length' => [
            'optimal' => ['min' => 1, 'max' => 60],  // Total characters
        ],
    ],
]
```

Slug validation shows:
- **Depth**: How many `/` slashes (URL nesting level)
- **Words**: Number of hyphen-separated words
- **Length**: Total character count
- **Status**: Visual indicator for each metric

## Why This Matters for Editors

**Without validation:**
- Editors guess at ideal lengths
- Inconsistent quality across pages
- Some titles too short, others too long
- No feedback until after publish

**With Meta Kit validation:**
- Clear visual guidance (green/orange/red)
- Learn SEO best practices while editing
- Consistent quality across all pages
- Catch issues before publishing
- Template-aware: Different rules for different content types
