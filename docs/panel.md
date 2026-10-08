# Panel Interface

[← Back to the README](../README.md)

The Meta Kit area in the Panel and the SEO tab in the page editor.


## Meta Kit Area

Access via the main menu (wand icon).

### Overview tiles
- One tile per area: slug, meta title, meta description, OG image, duplicates, noindex pages
- Each tile shows how many pages are open, split into **fix** (red) and **review** (orange); areas with nothing open are listed as "in order"
- Click a tile to filter the table to that area, click again to clear the filter
- **Duplicates**: pages whose own meta title or description is the same as another page's; the tooltip in the table names the other pages

### Pages table
- **Count view** shows the length of every field; **Meta content** and **OG content** show the texts themselves
- Only problems get a dot: red for fix, orange for review, nothing when the value is fine
- **Inherited values are dimmed**. The *Inherited* switch decides how they appear: hidden (a dash, so you see what the page really sets), dimmed, or dimmed with the source written beneath (Title, Meta, Site, or the main language)
- Hover any value for the full text, a length meter with the optimal range, and where an inherited value comes from
- Slugs show their parent path dimmed; the tooltip lists depth, word count and length against the configured ranges
- **Filters** by state, field, status and complete metadata; a text search; and sorting by attention, name, level, status or template
- **Edit** and **Generate Missing** act on the selected pages, or on all filtered pages when nothing is selected. The button says which: "Edit all (75)", "Edit filtered (12)" or "Edit 3 selected"
- **Bulk generation** runs page by page with progress and a cancel button; generated texts are shown for review (edit, deselect) before anything is saved

### Dialogs
- **Single page** and **bulk edit** dialogs with a length meter under every field and an AI button per field
- **Content review** (opt-in): an AI verdict with keyphrases, strengths, problems and next steps, printable

### Features
- **Panel Languages**: English and German, following each user's panel language. Texts live in `translations/en.json` and `translations/de.json`; another language is one more JSON file with the same keys (registered in `index.php`)
- **Template Awareness**: Different validation ranges for different page types
- **Language Support**: Works with multilingual sites, with a switch for the content language
- **Dark mode**: follows the Panel theme
- **Quick Navigation**: Jump to the page editor from the table

## Page Editor

When you add the `meta-kit/page` tab to a page blueprint:

**SEO Tab:**
- **Slug Validation**: Check URL structure, depth, word count
- **Meta Title**: With AI generation button and character counter
- **Meta Description**: With AI generation and validation
- **Meta Author**: Optional author name
- **Canonical URL**: Custom canonical if needed
- **Robots**: Set indexing behavior per page

**Social Media Section:**
- **OG Title**: Separate title for social sharing
- **OG Description**: Separate description for social sharing
- **OG Image**: Upload social media image (1200×630px recommended)

**Real-time Feedback:**
- Character counters update as you type
- Validation messages show what's optimal
- Title preview shows the final title including site name if applicable
- Color-coded indicators: green (optimal), orange (acceptable), red (fix needed)
