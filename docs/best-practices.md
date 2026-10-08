# Best Practices

[← Back to the README](../README.md)

Habits that keep metadata in good shape, for editors and for developers.


## For Editors

**Meta Titles:**
- Aim for 50-60 characters total (including site name)
- Put primary keywords near the beginning
- Make it compelling and clickable
- Be specific about page content
- Avoid ALL CAPS unless it's your brand

**Meta Descriptions:**
- Target 150-160 characters
- Include primary keyword naturally
- Add a call-to-action
- Describe what readers will find
- Make it unique for each page

**OG Titles:**
- Can be slightly longer than meta titles (up to 70 chars)
- More conversational tone for social sharing
- Focus on curiosity and click-worthiness

**OG Descriptions:**
- Can be longer than meta descriptions (up to 200 chars)
- More promotional tone
- Emphasize benefits and value

**Images:**
- Use 1200×630px for best results
- Works for Facebook, Twitter, WhatsApp
- Avoid text-heavy images
- High contrast for small sizes
- Include brand elements

**URLs (Slugs):**
- Keep depth to 2-3 levels maximum
- Use 3-8 descriptive words
- Include primary keyword
- Use hyphens, not underscores
- Keep total length under 60 characters

## For Developers

**Validation Ranges:**
- Set realistic optimal ranges based on your content type
- Use warning ranges to allow flexibility
- Create template-specific rules for different content types
- Account for site name length in title calculations

**AI Configuration:**
- Start with free models (Gemini 2.0 Flash may be sufficient)
- Use temperature 0.3-0.5 for consistency
- Use temperature 0.7-0.9 for variety
- Set formal tone for professional sites
- Customize prompts to match brand voice

**Panel Setup:**
- Add meta-kit tabs to all main page blueprints
- Hide SEO tab from admin/system pages if needed
- Use excludeTemplates to hide utility pages from table
