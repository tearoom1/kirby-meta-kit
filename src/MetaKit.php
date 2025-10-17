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

    public function __construct(Kirby $kirby)
    {
        $this->kirby = $kirby;

        // Default options (lowest priority)
        $defaults = [
            'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api.model' => 'meta-llama/llama-3.2-3b-instruct:free',
            'api.temperature' => 0.7,
            'maxDescriptionLength' => 160,
        ];

        // Site settings from panel (middle priority)
        $siteSettings = [];
        $openrouter = $kirby->site()->openrouter()->toObject();
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

    public function generateDescription(string $content, array $context = []): ?string
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

        // Limit content length to avoid token limits
        $contentPreview = mb_substr(strip_tags($content), 0, 1000);

        $prompt = "Write a concise, engaging meta description (max 160 characters) in {$languageName} for the following content:\n\n" .
                 $contentPreview . "\n\n" .
                 "Focus on the main topic and include relevant keywords. " .
                 "Make it compelling for search results. " .
                 "Write ONLY the description, nothing else.\n\n" .
                 "Description:";

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

        if (mb_strlen($description) > $maxLength) {
            // Try to truncate at sentence boundary
            $shortened = mb_substr($description, 0, $maxLength - 3);

            // Look for last sentence ending (. ! ?)
            if (preg_match('/^(.+[.!?])\s+/u', $shortened, $matches)) {
                $description = $matches[1];
            } else {
                // No sentence boundary, look for last word boundary
                $lastSpace = mb_strrpos($shortened, ' ');

                if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
                    // Cut at last space (but only if we're not losing too much text)
                    $description = mb_substr($shortened, 0, $lastSpace);

                    // Clean up trailing punctuation if incomplete
                    $description = rtrim($description, ',-:;');
                    $description .= '...';
                } else {
                    // Fallback: hard cut
                    $description = $shortened . '...';
                }
            }
        }

        return $description;
    }

}
