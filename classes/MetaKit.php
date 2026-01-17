<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Exception\Exception;
use GuzzleHttp\Client;
use Kirby\Http\Response;

class MetaKit
{
    protected $kirby;
    protected $options;
    protected $httpClient;

    /**
     * Check if plugin has a valid license
     */
    public static function hasValidLicense(): bool
    {
        $plugin = kirby()->plugin('tearoom1/meta-kit');
        if (!$plugin) {
            return false;
        }

        $license = $plugin->license();
        return $license && $license->isValid();
    }

    /**
     * Check if AI integration is enabled
     */
    public static function isAiEnabled(): bool
    {
        $kirby = kirby();

        // Check explicit disable in config
        if (!option('tearoom1.meta-kit.ai.enabled', true)) {
            return false;
        }

        // Check if model is configured (either in config or site settings)
        $configModel = option('tearoom1.meta-kit.api.model');
        $configKey = option('tearoom1.meta-kit.api.key');

        // Get site settings
        $openrouter = \TearoomOne\MetaHelper::getSeoData($kirby->site()->metaKitOpenrouter());
        $siteModel = $openrouter ? $openrouter->model()->value() : null;
        $siteKey = $openrouter ? $openrouter->apiKey()->value() : null;

        // AI is disabled if both model and key are empty
        $hasModel = !empty($configModel) || !empty($siteModel);
        $hasKey = !empty($configKey) || !empty($siteKey);

        return $hasModel && $hasKey;
    }

    public function __construct(Kirby $kirby)
    {
        $this->kirby = $kirby;

        // Default options (lowest priority)
        $defaults = [
            'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api.model' => 'meta-llama/llama-3.2-3b-instruct:free',
            'api.temperature' => 0.7,
            'ai.tone' => 'formal',
            'maxDescriptionLength' => 160,
            'ai.prompt.title' => "Write a clear, direct meta title {optimal_length} in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be specific and factual. Focus on what the page is actually about. {tone} Write ONLY the title, nothing else.\n\nTitle:",
            'ai.prompt.description' => "Write a clear, informative meta description {optimal_length} in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be direct and specific. Describe what the page actually contains. {tone} Write ONLY the description, nothing else.\n\nDescription:",
        ];

        // Site settings from panel (middle priority)
        $siteSettings = [];
        $openrouter = \TearoomOne\MetaHelper::getSeoData($kirby->site()->metaKitOpenrouter());
        if ($openrouter) {
            if ($openrouter->apiKey()->isNotEmpty()) {
                $siteSettings['api.key'] = $openrouter->apiKey()->value();
            }
            if ($openrouter->model()->isNotEmpty()) {
                $siteSettings['api.model'] = $openrouter->model()->value();
            }
            if ($openrouter->temperature()->isNotEmpty()) {
                $siteSettings['api.temperature'] = $openrouter->temperature()->toFloat();
            }
        }

        // Config.php settings (highest priority)
        $configSettings = $kirby->option('tearoom1.meta-kit', []);

        // Merge: defaults < site settings < config
        $this->options = array_merge($defaults, $siteSettings, $configSettings);

        $this->httpClient = new Client([
            'timeout' => 30,
        ]);
    }

    /**
     * Get tone instruction based on configuration
     */
    protected function getToneInstruction(): string
    {
        $tone = $this->options['ai.tone'] ?? 'formal';

        if ($tone === 'informal') {
            return "Use informal language (e.g., 'du' in German, 'tu' in French).";
        }

        return "Use formal language (e.g., 'Sie' in German, 'vous' in French).";
    }

    /**
     * Get validation ranges for a specific field type and template
     */
    protected function getValidationRanges(string $fieldType, ?string $template = null): array
    {
        $validation = $this->options['validation'] ?? [];
        $ranges = $validation['ranges'] ?? [];
        $templates = $validation['templates'] ?? [];

        // Map field types to config keys
        $fieldKey = match ($fieldType) {
            'title' => 'title',
            'ogTitle' => 'ogTitle',
            'description' => 'description',
            'ogDescription' => 'ogDescription',
            default => 'title'
        };

        // Check for template-specific ranges
        if ($template && isset($templates[$template][$fieldKey])) {
            return $templates[$template][$fieldKey];
        }

        // Fall back to default ranges
        return $ranges[$fieldKey] ?? [
            'optimal' => ['min' => 20, 'max' => 60],
            'warning' => ['min' => 15, 'max' => 75]
        ];
    }

    /**
     * Calculate target title length accounting for site name appending
     */
    protected function calculateTargetTitleLength(string $fieldType = 'title', ?string $template = null, ?Page $page = null): string
    {
        // Get validation ranges
        $ranges = $this->getValidationRanges($fieldType, $template);
        $optimalMin = $ranges['optimal']['min'] ?? 20;
        $optimalMax = $ranges['optimal']['max'] ?? 60;

        $site = $this->kirby->site();

        // Check if site name should be appended (flat field)
        $appendSiteName = $site->appendSiteName()->isNotEmpty()
            ? $site->appendSiteName()->toBool()
            : true;

        // Check which field types should have site name appended (flat field, checkboxes stored as array)
        $appendSiteNameTo = $site->appendSiteNameTo()->isNotEmpty()
            ? $site->appendSiteNameTo()->value()
            : 'meta,og';
        $appendToTypes = array_map('trim', explode(',', $appendSiteNameTo));

        // Determine if this field type should have site name appended
        $shouldAppend = $appendSiteName;
        if ($fieldType === 'title' && !in_array('meta', $appendToTypes)) {
            $shouldAppend = false;
        } elseif ($fieldType === 'ogTitle' && !in_array('og', $appendToTypes)) {
            $shouldAppend = false;
        }

        if (!$shouldAppend) {
            // No site name appending, use full optimal range
            return $optimalMin . '-' . $optimalMax;
        }

        // Get site name and separator (flat fields)
        $siteMetaTitle = $site->metaTitle()->isNotEmpty()
            ? $site->metaTitle()->value()
            : $site->title()->value();

        $separator = $site->titleSeparator()->isNotEmpty()
            ? $site->titleSeparator()->value()
            : '|';

        // Calculate space taken by site name (including spaces around separator)
        $siteNameLength = mb_strlen($siteMetaTitle) + mb_strlen($separator) + 2; // +2 for spaces

        // Reserve space for site name
        $minTarget = max(25, $optimalMin - $siteNameLength); // Minimum 25 chars for page title
        $maxTarget = max(30, $optimalMax - $siteNameLength); // Minimum 30 chars for page title

        return $minTarget . '-' . $maxTarget;
    }

    public function generateTitle(string $content, array $context = []): ?string
    {
        $apiKey = $this->options['api.key'] ?? null;

        if (empty($apiKey)) {
            throw new Exception('OpenRouter API key is not configured');
        }

        // Get context parameters
        $language = $context['language'] ?? 'en';
        $fieldType = $context['fieldType'] ?? 'title'; // 'title' or 'ogTitle'
        $template = $context['template'] ?? null;
        $page = $context['page'] ?? null;

        $languageNames = [
            'de' => 'German',
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
        ];
        $languageName = $languageNames[$language] ?? 'English';

        // Calculate target title length based on field type and template
        $targetLength = $this->calculateTargetTitleLength($fieldType, $template, $page);

        // Limit content length to avoid token limits
        $contentPreview = mb_substr(strip_tags($content), 0, 1000);

        // Get prompt template and replace placeholders
        $promptTemplate = $this->options['ai.prompt.title'];

        // Update prompt to include target length
        $promptTemplate = str_replace('30-65 characters', $targetLength . ' characters', $promptTemplate);

        $prompt = str_replace(
            ['{language}', '{content}', '{tone}'],
            [$languageName, $contentPreview, $this->getToneInstruction()],
            $promptTemplate
        );

        try {
            $response = $this->httpClient->post($this->options['api.endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $this->kirby->url(),
                ],
                'json' => [
                    'model' => $this->options['api.model'],
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 50,
                    'temperature' => $this->options['api.temperature'] ?? 0.7,
                ]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Log the response for debugging
            kirbylog('OpenRouter Response: ' . substr($body, 0, 500));

            if (!isset($data['choices'][0]['message']['content'])) {
                $errorMsg = $data['error']['message'] ?? 'Unknown API error';
                kirbylog('OpenRouter API Error: ' . $errorMsg);
                throw new Exception('OpenRouter API error: ' . $errorMsg);
            }

            $title = $data['choices'][0]['message']['content'];

            if ($title) {
                return $this->sanitizeTitle($title);
            }

            return null;

        } catch (\Exception $e) {
            kirbylog('Meta Kit Error: ' . $e->getMessage());
            throw $e; // Re-throw to show error to user
        }
    }

    public function generateDescription(string $content, array $context = []): ?string
    {
        $apiKey = $this->options['api.key'] ?? null;

        if (empty($apiKey)) {
            throw new Exception('OpenRouter API key is not configured');
        }

        // Get context parameters
        $languageCode = $context['language'] ?? 'en';
        $fieldType = $context['fieldType'] ?? 'description'; // 'description' or 'ogDescription'
        $template = $context['template'] ?? null;

        // Get the language names from kirby
        $languages = $this->kirby->languages();
        $languageNames = [];
        foreach ($languages as $language) {
            $languageNames[$language->code()] = $language->name();
        }
        $languageName = $languageNames[$languageCode] ?? 'English';

        // Get target description length based on field type and template
        $ranges = $this->getValidationRanges($fieldType, $template);
        $optimalMin = $ranges['optimal']['min'] ?? 140;
        $optimalMax = $ranges['optimal']['max'] ?? 160;
        $targetLength = $optimalMin . '-' . $optimalMax;

        // Limit content length to avoid token limits
        $contentPreview = mb_substr(strip_tags($content), 0, 1000);

        // Get prompt template and replace placeholders
        $promptTemplate = $this->options['ai.prompt.description'];

        // Update prompt to include target length
        $promptTemplate = str_replace('{optimal_length}', $targetLength . ' characters', $promptTemplate);

        $prompt = str_replace(
            ['{language}', '{content}', '{tone}'],
            [$languageName, $contentPreview, $this->getToneInstruction()],
            $promptTemplate
        );

        try {
            $response = $this->httpClient->post($this->options['api.endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $this->kirby->url(),
                ],
                'json' => [
                    'model' => $this->options['api.model'],
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 100,
                    'temperature' => $this->options['api.temperature'] ?? 0.7,
                ]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Log the response for debugging
            kirbylog('OpenRouter Response: ' . substr($body, 0, 500));

            if (!isset($data['choices'][0]['message']['content'])) {
                $errorMsg = $data['error']['message'] ?? 'Unknown API error';
                kirbylog('OpenRouter API Error: ' . $errorMsg);
                throw new Exception('OpenRouter API error: ' . $errorMsg);
            }

            $description = $data['choices'][0]['message']['content'];

            if ($description) {
                return $this->sanitizeDescription($description, $fieldType, $template);
            }

            return null;

        } catch (\Exception $e) {
            kirbylog('Meta Kit Error: ' . $e->getMessage());
            throw $e; // Re-throw to show error to user
        }
    }

    protected function sanitizeDescription(string $description, string $fieldType = 'description', ?string $template = null): string
    {
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description, " \t\n\r\0\x0B\"'`");

        // Get validation ranges for this field type and template
        $ranges = $this->getValidationRanges($fieldType, $template);
        $maxLength = $ranges['optimal']['max'] ?? 160;
        $flexibleLength = $maxLength + 20; // Allow up to 20 extra chars

        if (mb_strlen($description) > $flexibleLength) {
            // Only truncate if significantly over limit
            $shortened = mb_substr($description, 0, $maxLength);

            // Look for last sentence ending (. ! ?) within reasonable range
            if (preg_match('/^(.+[.!?])(?:\s|$)/u', $shortened, $matches)) {
                $description = $matches[1];
            } else {
                // No sentence boundary, look for last word boundary
                $lastSpace = mb_strrpos($shortened, ' ');

                if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
                    // Cut at last space (but only if we're not losing too much text)
                    $description = mb_substr($shortened, 0, $lastSpace);

                    // Clean up trailing punctuation if incomplete
                    $description = rtrim($description, ',-:;');
                } else {
                    // Fallback: hard cut at word boundary
                    $description = $shortened;
                }
            }
        }

        return $description;
    }

    protected function sanitizeTitle(string $title, string $fieldType = 'title', ?string $template = null, ?Page $page = null): string
    {
        $title = strip_tags($title);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title, " \t\n\r\0\x0B\"'`");

        // Get target length range
        $targetRange = $this->calculateTargetTitleLength($fieldType, $template, $page);
        list($minLength, $maxLength) = array_map('intval', explode('-', $targetRange));

        // Allow some flexibility (+5 chars) before truncating
        $flexibleMax = $maxLength + 5;
        $currentLength = mb_strlen($title);

        // If title is too long, truncate at word boundary
        if ($currentLength > $flexibleMax) {
            $shortened = mb_substr($title, 0, $maxLength);
            $lastSpace = mb_strrpos($shortened, ' ');

            if ($lastSpace !== false && $lastSpace > $minLength) {
                $title = mb_substr($shortened, 0, $lastSpace);
            } else {
                $title = $shortened;
            }
        }

        // If title is too short, return as is and let the AI retry or user manually adjust
        // We don't want to artificially pad titles

        return $title;
    }

}
