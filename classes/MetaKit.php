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
            'ai.prompt.title' => "Write a clear, direct meta title (30-65 characters) in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be specific and factual. Focus on what the page is actually about. {tone} Write ONLY the title, nothing else.\n\nTitle:",
            'ai.prompt.description' => "Write a clear, informative meta description (max 160 characters) in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be direct and specific. Describe what the page actually contains. {tone} Write ONLY the description, nothing else.\n\nDescription:",
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
     * Calculate target title length accounting for site name appending
     * Target: 50-60 chars total including site name
     */
    protected function calculateTargetTitleLength(): string
    {
        $site = $this->kirby->site();
        $siteSeo = \TearoomOne\MetaHelper::getSeoData($site->metaKitSeo());

        // Check if site name should be appended
        $appendSiteName = $siteSeo && $siteSeo->appendSiteName()->isNotEmpty()
            ? $siteSeo->appendSiteName()->toBool()
            : true;

        if (!$appendSiteName) {
            // No site name appending, use full range
            return '50-60';
        }

        // Get site name and separator
        $siteMetaTitle = $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
            ? $siteSeo->metaTitle()->value()
            : $site->title()->value();

        $separator = $siteSeo && $siteSeo->titleSeparator()->isNotEmpty()
            ? $siteSeo->titleSeparator()->value()
            : '|';

        // Calculate space taken by site name (including spaces around separator)
        $siteNameLength = mb_strlen($siteMetaTitle) + mb_strlen($separator) + 2; // +2 for spaces

        // Target total: 50-60 chars
        // Reserve space for site name
        $minTarget = max(25, 50 - $siteNameLength); // Minimum 25 chars for page title
        $maxTarget = max(30, 60 - $siteNameLength); // Minimum 30 chars for page title

        return $minTarget . '-' . $maxTarget;
    }

    public function generateTitle(string $content, array $context = []): ?string
    {
        $apiKey = $this->options['api.key'] ?? null;

        if (empty($apiKey)) {
            throw new Exception('OpenRouter API key is not configured');
        }

        // Get language from context
        $language = $context['language'] ?? 'en';
        $languageNames = [
            'de' => 'German',
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
        ];
        $languageName = $languageNames[$language] ?? 'English';

        // Calculate target title length accounting for site name
        $targetLength = $this->calculateTargetTitleLength();

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

        // Get language from context
        $languageCode = $context['language'] ?? 'en';
        // get the language names from kirby
        $languages = $this->kirby->languages();
        $languageNames = [];
        foreach ($languages as $language) {
            $languageNames[$language->code()] = $language->name();
        }
        $languageName = $languageNames[$languageCode] ?? 'English';

        // Limit content length to avoid token limits
        $contentPreview = mb_substr(strip_tags($content), 0, 1000);

        // Get prompt template and replace placeholders
        $promptTemplate = $this->options['ai.prompt.description'];
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
                return $this->sanitizeDescription($description);
            }

            return null;

        } catch (\Exception $e) {
            kirbylog('Meta Kit Error: ' . $e->getMessage());
            throw $e; // Re-throw to show error to user
        }
    }

    protected function sanitizeDescription(string $description): string
    {
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description, " \t\n\r\0\x0B\"'`");

        $maxLength = $this->options['maxDescriptionLength'];
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

    protected function sanitizeTitle(string $title): string
    {
        $title = strip_tags($title);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title, " \t\n\r\0\x0B\"'`");

        // Get target length range
        $targetRange = $this->calculateTargetTitleLength();
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
