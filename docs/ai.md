# AI Generation and Review

[← Back to the README](../README.md)

How AI generation reads your pages, which providers and models work, how to tune or disable it, and the experimental content review.


Meta Kit's AI features are designed to save time while maintaining quality and consistency.

## What the AI Reads

The AI only reads fields that are defined in the page's blueprint, so leftovers from an earlier blueprint version in the content file are ignored. Pages without a blueprint of their own use all their fields. For the site, the home page's content is used.

If a page has less than `ai.minContentLength` characters of text (default 50, title excluded), generation is skipped with a message instead of letting the model make something up — e.g. for a home page that only lists other pages.

## How AI Works With Validation

**The Smart Part:** AI automatically generates content that matches your validation ranges.

When you click "Generate," Meta Kit:
1. Looks up the validation rules for this field type and template
2. Adjusts for site name appending (if applicable)
3. Tells the AI exactly what character range to target
4. Generates content that's already in the green zone

**Example:**
- **Template**: Article
- **Field**: Meta Title
- **Validation Range**: 40-70 characters
- **Site Name**: "My Blog" (7 chars + separator)
- **AI Target**: 30-60 characters (reserves space for site name)
- **Result**: AI generates a 45-character title that becomes 54 characters with site name appended ✅

## Configuring AI

### Required Settings

Get a free API key from [OpenRouter.ai](https://openrouter.ai/):

```php
'api.key' => 'sk-or-v1-YOUR-KEY',
'api.model' => 'google/gemma-4-31b-it:free',
```

### Choosing a Provider

All providers use the same OpenAI-compatible chat completions format. Set the provider in the Panel (AI Settings) or in config.php:

| `api.provider` | Endpoint | Default model | Notes |
|---|---|---|---|
| `openrouter` (default) | `https://openrouter.ai/api/v1/chat/completions` | `google/gemma-4-31b-it:free` | Hundreds of models from all major vendors, free tier available |
| `mistral` | `https://api.mistral.ai/v1/chat/completions` | `mistral-small-latest` | EU company with EU data processing, useful for GDPR-sensitive projects |
| `custom` | Set `api.endpoint` | Set `api.model` | Any OpenAI-compatible API, e.g. EU hosters like IONOS, Scaleway or OVHcloud, or a self-hosted Ollama/vLLM server |

```php
// Mistral (EU)
'api.provider' => 'mistral',
'api.key' => env('MISTRAL_API_KEY'),
'api.model' => 'mistral-medium-latest',

// Any OpenAI-compatible endpoint
'api.provider' => 'custom',
'api.endpoint' => 'https://llm.example.com/v1/chat/completions',
'api.key' => env('LLM_API_KEY'),  // Use any placeholder if your server needs no key
'api.model' => 'llama3.3',
```

`api.endpoint` always wins over the provider's preset endpoint. Model IDs differ between providers: an OpenRouter ID like `openai/gpt-6-luna` won't work with Mistral.

### OpenRouter Models

Pick any model from OpenRouter — free or paid. The plugin sends the configured model name to OpenRouter as-is, so any model your API key can reach will work, including reasoning models such as GPT-6 or Gemini 3. In the Panel, choose **Other model** in the model dropdown to enter any model ID that isn't listed.

### Sample of available Models as of October 2026

**Free Tier (No cost):**
- `google/gemma-4-31b-it:free` (default)
- `google/gemma-4-26b-a4b-it:free`
- `nvidia/nemotron-3-super-120b-a12b:free`
- Many more available [here](https://openrouter.ai/collections/free-models)

**Paid Models (Higher quality):**
- `openai/gpt-6-luna` or `openai/gpt-5-mini`
- `anthropic/claude-haiku-5.5` or `anthropic/claude-sonnet-5.5`
- `google/gemini-3.5-flash`
- `mistralai/mistral-large-4-0`
- `deepseek/deepseek-v4-flash`
- Find more on OpenRouter. See also the [rankings](https://openrouter.ai/rankings)

### AI Behavior Settings

**Temperature** (0.1 - 1.0):
Controls creativity and variation in generated content. Models that don't support it (e.g. GPT-6) ignore it.

```php
'api.temperature' => 0.7,  // Default: balanced

// Examples:
0.3  // Very focused, consistent, factual (good for product descriptions)
0.7  // Balanced (recommended for most use cases)
0.9  // Creative, varied (good for blog posts, social media)
```

**Reasoning Effort** (`none`, `minimal`, `low`, `medium`, `high`):
Controls how long reasoning models (GPT-5/6, Gemini 3, DeepSeek R-series, …) think before answering. Short texts like titles and descriptions rarely need much thinking, so `low` or `none` makes generation noticeably faster and cheaper.

```php
'api.reasoning' => null,  // Default: the model's own default effort

// Examples:
'none'  // Fastest, no thinking (where the model allows turning it off)
'low'   // Recommended for GPT-6 and similar models
'high'  // Slowest, most thorough
```

The value is sent to OpenRouter as `reasoning.effort`, to custom endpoints as `reasoning_effort` (the OpenAI parameter), and not at all to Mistral. Models without reasoning ignore it. Leave it empty for hybrid models like Claude: setting any value switches their reasoning on, which makes them slower and more expensive. It can also be set in the Panel under AI Settings.

**Tone** (formal vs informal):
Controls language formality in multilingual content.

```php
'ai.tone' => 'formal',  // Use Sie (German), vous (French), usted (Spanish)
'ai.tone' => 'informal',  // Use du (German), tu (French), tú (Spanish)
```

## Custom AI Prompts

Tailor AI generation to your specific needs:

```php
'ai.prompt.title' => "Write a compelling meta title ({optimal_length} characters) in {language} for:\n\n{content}\n\n{tone} Focus on benefits and include power words. Write ONLY the title.",

'ai.prompt.description' => "Write an engaging meta description ({optimal_length} characters) in {language} for:\n\n{content}\n\n{tone} Include a call-to-action and primary keyword. Write ONLY the description.",
```

**Available Placeholders:**
- `{optimal_length}` - Automatically filled with validation ranges (e.g., "40-60 characters")
- `{language}` - Current language name (e.g., "German", "English")
- `{content}` - Page content for AI context
- `{tone}` - Automatically replaced with tone instruction

## AI Features in Panel

**Individual Field Generation:**
- Click "Generate" button next to any title or description field
- AI analyzes page content and current language
- Generates content matching validation rules for that template
- Instant feedback with character count and validation status

**Bulk Generation:**
- Select multiple pages in Meta Kit area
- Choose which fields to generate (meta title, OG description, etc.)
- AI processes all pages using appropriate template rules
- Review and apply changes

**Smart Behavior:**
- Skips pages that already have content (unless you force regenerate)
- Uses page content for context (title, text fields, structured content)
- Respects language settings (de, en, fr, es, it)
- Accounts for site name appending in title length

## Experimental AI Content Review

Meta Kit also includes an experimental AI content review for single pages.

- It is meant as a fast editorial aid, not a final SEO verdict
- Use it to spot weak positioning, vague copy, thin content, and possible keyphrases
- Treat its output with care and review suggestions manually before making content decisions
- Review is disabled by default and only appears when `'review.enabled' => true`

### Adding the review button to a page blueprint

Add the `mk-review` field anywhere in your page blueprint. It renders as a single right-aligned button that opens the full review dialog. All state (AI enabled and review enabled) is computed server-side, so no extra options are required:

```yaml
# site/blueprints/pages/default.yml
tabs:
  seo:
    label: SEO
    sections:
      seo:
        type: fields
        fields:
          review:
            type: mk-review
          metaTitle:
            type: mk-title
            # ...
```

A good place is directly above the `seo-preview` section so the button appears right before the live preview. The button is automatically hidden when AI is not configured or `review.enabled` is `false`.

## Disabling AI

AI features are automatically disabled if:
- No API key is configured
- No model is selected
- `ai.enabled` is set to `false`

To hide only the experimental content review from the Panel while keeping AI generation available, set:

```php
'tearoom1.meta-kit' => [
    'review.enabled' => false
]
```

When disabled:
- Generate buttons are hidden
- AI routes are not registered
- Plugin still works for manual metadata management
