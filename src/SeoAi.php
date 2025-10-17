<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Exception\Exception;
use GuzzleHttp\Client;
use Kirby\Http\Response;

class SeoAi
{
    protected $kirby;
    protected $options;
    protected $httpClient;

    public function __construct(Kirby $kirby)
    {
        $this->kirby = $kirby;
        $this->options = array_merge(
            [
                'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                'api.model' => 'mistralai/mistral-7b-instruct',
                'api.temperature' => 0.7,
                'maxDescriptionLength' => 160,
                'sitemap.include' => 'all',
                'sitemap.exclude' => ['error'],
            ],
            $kirby->option('tearoom1.meta-kit', [])
        );

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
            kirbylog('SEO AI Error: ' . $e->getMessage());
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

    public function getSitemap(): array
    {
        $sitemap = [];
        $pages = $this->kirby->site()->index();
        $isMultilang = $this->kirby->multilang();

        foreach ($pages as $page) {
            if (!$this->shouldIncludeInSitemap($page)) {
                continue;
            }

            // For multilanguage sites, add entry for each language
            if ($isMultilang) {
                $defaultLanguage = $this->kirby->defaultLanguage();

                // Get alternates for all languages
                $alternates = [];
                foreach ($this->kirby->languages() as $language) {
                    $alternates[] = [
                        'lang' => $language->code(),
                        'url' => $page->url($language->code())
                    ];
                }

                // Add URL for default language
                $sitemap[] = [
                    'url' => $page->url($defaultLanguage->code()),
                    'lastmod' => $page->modified('c'),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page),
                    'alternates' => $alternates
                ];
            } else {
                // Single language site
                $sitemap[] = [
                    'url' => $page->url(),
                    'lastmod' => $page->modified('c'),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page)
                ];
            }
        }

        return $sitemap;
    }

    protected function shouldIncludeInSitemap(Page $page): bool
    {
        // Skip unlisted and draft pages
        if ($page->isDraft() || $page->isUnlisted()) {
            return false;
        }

        // Check page's noIndex field
        $seoData = $page->seo()->toObject();
        if ($seoData && $seoData->noIndex()->toBool() === true) {
            return false;
        }

        // Check site blueprint's sitemapExclude field (pages selector)
        $siteExclude = $this->kirby->site()->sitemapExclude();
        if ($siteExclude->isNotEmpty()) {
            $excludedPages = $siteExclude->toPages();
            foreach ($excludedPages as $excludedPage) {
                if ($excludedPage->id() === $page->id()) {
                    return false;
                }
            }
        }

        // Check config exclude patterns
        $excludePatterns = $this->options['sitemap.exclude'] ?? [];
        $id = $page->id();

        foreach ($excludePatterns as $pattern) {
            if (preg_match("#{$pattern}#", $id)) {
                return false;
            }
        }

        return true;
    }

    protected function getChangeFrequency(Page $page): string
    {
        // Default change frequency based on page depth
        $depth = $page->depth();

        if ($depth <= 1) return 'weekly';
        if ($depth === 2) return 'monthly';
        return 'yearly';
    }

    protected function getPriority(Page $page): float
    {
        // Use site settings if available
        $site = $this->kirby->site();

        // Homepage gets priority from site settings or 1.0
        if ($page->isHomePage()) {
            return $site->sitemapPriorityHome()->toFloat() ?? 1.0;
        }

        // Other pages get default priority from site settings
        $defaultPriority = $site->sitemapPriorityDefault()->toFloat() ?? null;
        if ($defaultPriority !== null) {
            return $defaultPriority;
        }

        // Fallback: Priority based on page depth
        $depth = $page->depth();

        if ($depth <= 1) return 1.0;
        if ($depth === 2) return 0.8;
        if ($depth === 3) return 0.6;
        return 0.4;
    }
}
