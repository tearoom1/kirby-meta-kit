<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Exception\Exception;
use Kirby\Http\Remote;

class MetaKit
{
    protected $kirby;
    protected $options;

    /**
     * Extra output tokens on top of each call's answer budget. Reasoning models
     * (GPT-5/6, Gemini 3, …) count their thinking against max_tokens and would
     * otherwise run out before writing any text. Only used tokens are billed.
     */
    public const REASONING_TOKEN_BUDGET = 4000;

    private static ?bool $aiEnabledCache = null;

    public static function getConfiguredAiModel(): ?string
    {
        $settings = ConfigHelper::getAiSettings();
        $model = $settings['api.model'] ?? null;

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }

    /**
     * Replaces the after-response runner, e.g. in tests
     * @var \Closure(\Closure): void|null
     */
    public static ?\Closure $deferHandler = null;

    /**
     * Run a task after the response was sent, so slow API calls don't keep
     * the editor waiting. On PHP-FPM the request is finished first; on other
     * SAPIs the task still runs at shutdown, after the page output.
     */
    public static function afterResponse(\Closure $task): void
    {
        if (self::$deferHandler !== null) {
            (self::$deferHandler)($task);
            return;
        }

        register_shutdown_function(static function () use ($task) {
            ignore_user_abort(true);
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            try {
                $task();
            } catch (\Throwable $e) {
                self::log('Meta Kit deferred task error: ' . $e->getMessage());
            }
        });
    }

    public static function log(string $message): void
    {
        if (function_exists('kirbylog')) {
            kirbylog($message);
        }
    }

    public static function getAiAccessErrorMessage(): string
    {
        if (!self::isAiEnabled()) {
            return 'Configure an AI provider API key and model to use AI generation.';
        }

        if (self::getConfiguredAiModel() === null) {
            return 'Configure an AI model to use AI generation.';
        }

        return '';
    }

    /**
     * Check if AI integration is enabled
     */
    public static function isAiEnabled(): bool
    {
        if (self::$aiEnabledCache !== null) {
            return self::$aiEnabledCache;
        }

        // Check explicit disable in config
        if (!option('tearoom1.meta-kit.ai.enabled', true)) {
            return self::$aiEnabledCache = false;
        }

        // AI needs a key, a model and an endpoint (from config or site settings)
        $settings = ConfigHelper::getAiSettings();

        return self::$aiEnabledCache = !empty($settings['api.key'])
            && !empty($settings['api.model'])
            && !empty($settings['api.endpoint']);
    }

    public static function isReviewEnabled(): bool
    {
        if (!option('tearoom1.meta-kit.review.enabled', false)) {
            return false;
        }

        return self::isAiEnabled();
    }

    public static function canReviewPage(string $pageId): bool
    {
        return self::isReviewEnabled();
    }

    public function __construct(Kirby $kirby)
    {
        $this->kirby = $kirby;
        $this->options = ConfigHelper::getAiSettings();
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
     * Resolve a language code to a human-readable name using Kirby's languages,
     * falling back to a capitalised version of the code for unknown languages.
     */
    protected function resolveLanguageName(string $languageCode): string
    {
        foreach ($this->kirby->languages() as $language) {
            if ($language->code() === $languageCode) {
                return $language->name();
            }
        }

        return ucfirst($languageCode);
    }

    /**
     * Get validation ranges for a specific field type and template
     */
    protected function getValidationRanges(string $fieldType, ?string $template = null): array
    {
        return ConfigHelper::getValidationRanges($fieldType, $template);
    }

    /**
     * Calculate target title length accounting for site name appending
     */
    protected function calculateTargetTitleLength(string $fieldType = 'title', ?string $template = null): string
    {
        $ranges = $this->getValidationRanges($fieldType, $template);
        $optimalMin = $ranges['optimal']['min'] ?? 20;
        $optimalMax = $ranges['optimal']['max'] ?? 60;

        $settings = ConfigHelper::getSiteSettings();

        // Determine if this field type should have site name appended
        $appendToTypes = array_map('trim', explode(',', $settings['appendSiteNameTo']));
        $shouldAppend = $settings['appendSiteName'];
        if ($fieldType === 'title' && !in_array('meta', $appendToTypes)) {
            $shouldAppend = false;
        } elseif ($fieldType === 'ogTitle' && !in_array('og', $appendToTypes)) {
            $shouldAppend = false;
        }

        if (!$shouldAppend) {
            return $optimalMin . '-' . $optimalMax;
        }

        // Calculate space taken by site name (including spaces around separator)
        $siteNameLength = mb_strlen((string)($settings['siteMetaTitle'] ?? '')) + mb_strlen((string)($settings['titleSeparator'] ?? '')) + 2;

        // Reserve space for site name
        $minTarget = max(25, $optimalMin - $siteNameLength);
        $maxTarget = max(30, $optimalMax - $siteNameLength);

        return $minTarget . '-' . $maxTarget;
    }

    /**
     * Send a prompt to the configured AI provider and return the raw text response.
     *
     * Shared by generateTitle() and generateDescription() to avoid duplication.
     *
     * @throws Exception When the API key is missing or the API returns an error
     */
    protected function callApi(string $prompt, int $maxTokens): string
    {
        $provider = $this->options['api.provider'] ?? 'openrouter';
        $label = ConfigHelper::AI_PROVIDERS[$provider]['label'] ?? 'AI';
        $endpoint = $this->options['api.endpoint'] ?? null;
        $apiKey = $this->options['api.key'] ?? null;
        $model = is_string($this->options['api.model'] ?? null)
            ? trim($this->options['api.model'])
            : null;

        if (empty($apiKey)) {
            throw new Exception($label . ' API key is not configured');
        }

        if ($model === null || $model === '') {
            throw new Exception($label . ' model is not configured');
        }

        if (empty($endpoint)) {
            throw new Exception($label . ' API endpoint is not configured');
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $maxTokens + self::REASONING_TOKEN_BUDGET,
            'temperature' => $this->options['api.temperature'] ?? 0.7,
        ];

        // Only sent when configured: on hybrid models (e.g. Claude) any effort
        // switches reasoning on, so leaving it out keeps the model's default.
        // Not sent to Mistral, which doesn't document an effort parameter.
        $reasoning = $this->options['api.reasoning'] ?? null;
        if (is_string($reasoning) && trim($reasoning) !== '') {
            if ($provider === 'openrouter') {
                $payload['reasoning'] = ['effort' => trim($reasoning)];
            } elseif ($provider === 'custom') {
                $payload['reasoning_effort'] = trim($reasoning);
            }
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];
        if ($provider === 'openrouter') {
            // Attribution for OpenRouter's app rankings
            $headers['HTTP-Referer'] = $this->kirby->url();
        }

        // Kirby's built-in HTTP client; it doesn't throw on 4xx/5xx responses
        $response = Remote::request($endpoint, [
            'method'  => 'POST',
            'timeout' => 60,
            'headers' => $headers,
            'data' => json_encode($payload),
        ]);

        $body = (string)$response->content();
        $data = json_decode($body, true);

        if ($response->code() >= 400) {
            $errorMsg = $this->formatApiError($data, $body, $model);
            self::log($label . ' API Error: ' . $errorMsg);
            throw new Exception($label . ' API error: ' . $errorMsg);
        }

        if (!isset($data['choices'][0])) {
            $errorMsg = $data['error']['message'] ?? 'Unknown API error';
            self::log($label . ' API Error: ' . $errorMsg);
            throw new Exception($label . ' API error: ' . $errorMsg);
        }

        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            $finishReason = $data['choices'][0]['finish_reason'] ?? null;
            $errorMsg = $finishReason === 'length'
                ? 'The model used up its token limit before returning any text'
                : 'The model returned an empty response';
            $errorMsg .= ' (model: ' . $model . ')';
            self::log($label . ' API Error: ' . $errorMsg);
            throw new Exception($label . ' API error: ' . $errorMsg);
        }

        return $content;
    }

    protected function formatApiError(?array $data, string $body, ?string $model = null): string
    {
        if (!is_array($data)) {
            return $this->compactErrorText($body) ?: 'Unknown API error';
        }

        $error = is_array($data['error'] ?? null) ? $data['error'] : [];
        $metadata = is_array($error['metadata'] ?? null) ? $error['metadata'] : [];

        $message = $error['message'] ?? $data['message'] ?? 'Unknown API error';
        $context = [];

        if (!empty($model)) {
            $context[] = 'model: ' . $model;
        }

        if (!empty($metadata['provider_name'])) {
            $context[] = 'provider: ' . $metadata['provider_name'];
        }

        if (!empty($error['code'])) {
            $context[] = 'code: ' . $error['code'];
        }

        $raw = $this->extractRawApiError($metadata['raw'] ?? null);
        $message = $this->compactErrorText($message);
        if ($raw && $raw !== $message) {
            $message .= ': ' . $raw;
        }

        if ($context === []) {
            return $this->compactErrorText($message);
        }

        return $this->compactErrorText($message . ' (' . implode(', ', $context) . ')');
    }

    protected function extractRawApiError($raw): ?string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $message = $decoded['error']['message']
                ?? $decoded['message']
                ?? $decoded['error']
                ?? null;

            if (is_string($message) && trim($message) !== '') {
                return $this->compactErrorText($message);
            }
        }

        return $this->compactErrorText($raw);
    }

    protected function compactErrorText(string $message, int $maxLength = 500): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message));

        if (mb_strlen($message) <= $maxLength) {
            return $message;
        }

        return mb_substr($message, 0, $maxLength - 1) . '…';
    }

    protected function decodeJsonResponse(string $response): ?array
    {
        $trimmed = trim($response);
        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/sU', $trimmed, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/(\{.*\})/sU', $trimmed, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function normalizeStringList($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : '',
            $value
        )));
    }

    protected function normalizeObjectList($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }

    public function generateSeoReview(array $payload): array
    {
        $page = $payload['page'] ?? null;
        if (!$page) {
            return [
                'summary' => 'Error: No page provided',
            ];
        }

        $prompt = "You are an experienced SEO content strategist and content critic.\n" .
            "Analyze the full page content and current metadata. Be candid, not polite.\n" .
            "Judge the page according to its actual page type and purpose.\n" .
            "A demo page, landing page, product page, feature page, or workflow page does not need to read like a long editorial article to be good.\n" .
            "Do not call content placeholder-like just because it is concise, branded, or focused on explaining a product feature.\n" .
            "Only call content weak if it is genuinely vague, incoherent, repetitive, empty, generic, or lacking a clear audience benefit.\n" .
            "If the page clearly explains a focused topic, intended audience, and practical benefit, treat that as a real strength.\n" .
            "If the content is weak, generic, placeholder-like, mixed-topic, incoherent, repetitive, or not SEO-ready, say so clearly.\n" .
            "Treat lorem ipsum, filler copy, test content, generic headings, and off-topic pasted text as serious quality problems.\n" .
            "Do not focus on character-count validation.\n" .
            "Do not invent strengths if the page is poor. It is acceptable to say there are few real strengths.\n" .
            "When evaluating depth, consider whether the content is sufficient for the page's purpose, not whether it is long.\n" .
            "Only suggest keyphrases that are actually supported by the page content. If the content is too weak, say the page needs rewriting before keyword targeting.\n" .
            "Return valid JSON only.\n\n" .
            "Return this exact JSON shape:\n" .
            "{\n" .
            '  "summary": "short paragraph",'. "\n" .
            '  "overallQuality": "High|Medium|Low",'. "\n" .
            '  "verdict": "clear blunt verdict",'. "\n" .
            '  "searchIntent": "short description",'. "\n" .
            '  "needsRewrite": true,'. "\n" .
            '  "keyphrases": [' . "\n" .
            '    { "phrase": "keyphrase", "fit": "strong|medium|weak", "reason": "why it fits or why it is weak" }'. "\n" .
            '  ],'. "\n" .
            '  "strengths": ["strength 1", "strength 2"],'. "\n" .
            '  "contentProblems": ["problem 1", "problem 2", "problem 3"],'. "\n" .
            '  "improvements": ["improvement 1", "improvement 2", "improvement 3"],'. "\n" .
            '  "metadataFit": ["short note about title/description fit"],'. "\n" .
            '  "nextSteps": ["next step 1", "next step 2"]'. "\n" .
            "}\n\n" .
            "Page context:\n" . json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $response = $this->callApi($prompt, 700);
            $decoded = $this->decodeJsonResponse($response);

            if ($decoded) {
                return [
                    'summary' => trim((string)($decoded['summary'] ?? '')),
                    'overallQuality' => trim((string)($decoded['overallQuality'] ?? '')),
                    'verdict' => trim((string)($decoded['verdict'] ?? '')),
                    'searchIntent' => trim((string)($decoded['searchIntent'] ?? '')),
                    'needsRewrite' => (bool)($decoded['needsRewrite'] ?? false),
                    'keyphrases' => $this->normalizeObjectList($decoded['keyphrases'] ?? []),
                    'strengths' => $this->normalizeStringList($decoded['strengths'] ?? []),
                    'contentProblems' => $this->normalizeStringList($decoded['contentProblems'] ?? []),
                    'improvements' => $this->normalizeStringList($decoded['improvements'] ?? []),
                    'metadataFit' => $this->normalizeStringList($decoded['metadataFit'] ?? []),
                    'nextSteps' => $this->normalizeStringList($decoded['nextSteps'] ?? []),
                ];
            }
        } catch (\Throwable $e) {
            self::log('Meta Kit Review Error: ' . $e->getMessage());
            return [
                'summary' => 'Error: ' . $e->getMessage()
            ];
        }
        return [
            'summary' => 'Error: Something went wrong.'
        ];
    }

    public function generateTitle(string $content, array $context = []): ?string
    {
        $language = $context['language'] ?? MetaHelper::currentLanguageCode($this->kirby);
        $fieldType = $context['fieldType'] ?? 'title'; // 'title' or 'ogTitle'
        $template = $context['template'] ?? null;

        $languageName = $this->resolveLanguageName($language);
        $targetLength = $this->calculateTargetTitleLength($fieldType, $template);
        $contentPreview = mb_substr(strip_tags($content), 0, 1000);

        $promptTemplate = str_replace(
            '{optimal_length}',
            $targetLength . ' characters',
            $this->options['ai.prompt.title']
        );
        $prompt = str_replace(
            ['{language}', '{content}', '{tone}'],
            [$languageName, $contentPreview, $this->getToneInstruction()],
            $promptTemplate
        );

        try {
            $title = $this->callApi($prompt, 50);
            return $title ? $this->sanitizeTitle($title, $fieldType, $template) : null;
        } catch (\Throwable $e) {
            self::log('Meta Kit Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generateDescription(string $content, array $context = []): ?string
    {
        $languageCode = $context['language'] ?? MetaHelper::currentLanguageCode($this->kirby);
        $fieldType = $context['fieldType'] ?? 'description'; // 'description' or 'ogDescription'
        $template = $context['template'] ?? null;

        $languageName = $this->resolveLanguageName($languageCode);
        $ranges = $this->getValidationRanges($fieldType, $template);
        $targetLength = ($ranges['optimal']['min'] ?? 140) . '-' . ($ranges['optimal']['max'] ?? 160);
        $contentPreview = mb_substr(strip_tags($content), 0, 1000);

        $promptTemplate = str_replace(
            '{optimal_length}',
            $targetLength . ' characters',
            $this->options['ai.prompt.description']
        );
        $prompt = str_replace(
            ['{language}', '{content}', '{tone}'],
            [$languageName, $contentPreview, $this->getToneInstruction()],
            $promptTemplate
        );

        try {
            $description = $this->callApi($prompt, 100);
            if (!$description) {
                return null;
            }

            $normalizedDescription = $this->normalizeGeneratedText($description);
            $optimalMin = $ranges['optimal']['min'] ?? 140;
            $optimalMax = $ranges['optimal']['max'] ?? 160;

            $attempts = 0;
            while (
                $attempts < 2 &&
                !$this->isWithinOptimalRange($normalizedDescription, $optimalMin, $optimalMax)
            ) {
                $retryPrompt = $this->buildDescriptionRetryPrompt(
                    $contentPreview,
                    $languageName,
                    $normalizedDescription,
                    $optimalMin,
                    $optimalMax
                );

                try {
                    $description = $this->callApi($retryPrompt, 100);
                } catch (\Throwable $e) {
                    // Keep the first usable description if a retry fails
                    self::log('Meta Kit Retry Error: ' . $e->getMessage());
                    break;
                }
                if (!$description) {
                    break;
                }

                $normalizedDescription = $this->normalizeGeneratedText($description);
                $attempts++;
            }

            return $this->sanitizeDescription($normalizedDescription, $fieldType, $template);
        } catch (\Throwable $e) {
            self::log('Meta Kit Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function normalizeGeneratedText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text, " \t\n\r\0\x0B\"'`");
    }

    protected function isWithinOptimalRange(string $text, int $optimalMin, int $optimalMax): bool
    {
        $length = mb_strlen($text);
        return $length >= $optimalMin && $length <= $optimalMax;
    }

    protected function buildDescriptionRetryPrompt(
        string $contentPreview,
        string $languageName,
        string $currentDescription,
        int $optimalMin,
        int $optimalMax
    ): string {
        $currentLength = mb_strlen($currentDescription);

        return "Rewrite this meta description in {$languageName} so it is between {$optimalMin} and {$optimalMax} characters.\n\n" .
            "Current description ({$currentLength} characters):\n{$currentDescription}\n\n" .
            "Source content:\n{$contentPreview}\n\n" .
            "Keep the meaning specific and factual. Avoid filler. Do not explain your changes. " .
            "Return ONLY the rewritten description.";
    }

    protected function truncateDescriptionToLength(string $description, int $maxLength): string
    {
        $shortened = mb_substr($description, 0, $maxLength);

        if (preg_match('/^(.+[.!?])(?:\s|$)/u', $shortened, $matches)) {
            return trim($matches[1]);
        }

        $lastSpace = mb_strrpos($shortened, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLength * 0.75) {
            return rtrim(mb_substr($shortened, 0, $lastSpace), ',-:; ');
        }

        return rtrim($shortened, ',-:; ');
    }

    protected function sanitizeDescription(string $description, string $fieldType = 'description', ?string $template = null): string
    {
        $description = $this->normalizeGeneratedText($description);

        // Get validation ranges for this field type and template
        $ranges = $this->getValidationRanges($fieldType, $template);
        $maxLength = $ranges['optimal']['max'] ?? 160;
        if (mb_strlen($description) > $maxLength) {
            $description = $this->truncateDescriptionToLength($description, $maxLength);
        }

        return $description;
    }

    protected function sanitizeTitle(string $title, string $fieldType = 'title', ?string $template = null): string
    {
        $title = strip_tags($title);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title, " \t\n\r\0\x0B\"'`");

        // Get target length range
        $targetRange = $this->calculateTargetTitleLength($fieldType, $template);
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
