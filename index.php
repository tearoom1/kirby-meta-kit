<?php

use TearoomOne\SeoAi;
use Kirby\Http\Response;

@include_once __DIR__ . '/vendor/autoload.php';

load([
    'TearoomOne\SeoAi' => 'src/SeoAi.php',
], __DIR__);

Kirby::plugin('tearoom1/seo-ai', [
    'options' => [
        'api.key' => null,
        'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
        'api.model' => 'mistralai/mistral-7b-instruct',
        'api.temperature' => 0.7,
        'maxDescriptionLength' => 160,
        'sitemap.include' => 'all',
        'sitemap.exclude' => ['error'],
        'autoGenerate' => false,
    ],
    'blueprints' => [
        'seo-ai/site' => __DIR__ . '/blueprints/site.yml',
        'seo-ai/page' => __DIR__ . '/blueprints/page.yml',
        'seo-ai/fields/og-image' => __DIR__ . '/blueprints/fields/og-image.php',
    ],
    'sections' => [
        'seo-preview' => __DIR__ . '/sections/preview.php',
    ],
    'fields' => [
        'seo-ai-generator' => [],
    ],
    'snippets' => [
        'seo/meta' => __DIR__ . '/snippets/meta.php',
        'seo/opengraph' => __DIR__ . '/snippets/opengraph.php',
        'seo/schema' => __DIR__ . '/snippets/schema.php',
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'seo-ai/generate',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    $kirby = kirby();
                    $data = $kirby->request()->body()->toArray();
                    $text = $data['text'] ?? '';
                    $language = $data['language'] ?? ($kirby->language()?->code() ?? 'en');
                    
                    if (empty($text)) {
                        return [
                            'status' => 'error',
                            'message' => 'No text provided'
                        ];
                    }

                    try {
                        $seoAi = new SeoAi($kirby);
                        $description = $seoAi->generateDescription($text, ['language' => $language]);
                        
                        if (empty($description)) {
                            return [
                                'status' => 'error',
                                'message' => 'AI returned empty description. Check your API key and logs.'
                            ];
                        }
                        
                        return [
                            'status' => 'success',
                            'description' => $description
                        ];
                    } catch (Exception $e) {
                        // Log the error
                        kirbylog('SEO-AI API Error: ' . $e->getMessage());
                        
                        return [
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ];
                    }
                }
            ]
        ]
    ],
    'routes' => [
        [
            'pattern' => 'sitemap.xsl',
            'action' => function () {
                $file = __DIR__ . '/sitemap.xsl';
                if (file_exists($file)) {
                    return new Response(file_get_contents($file), 'application/xslt+xml', 200, [
                        'Content-Type' => 'application/xslt+xml; charset=utf-8'
                    ]);
                }
                return new Response('XSL file not found', 'text/plain', 404);
            }
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                // Check if sitemap is enabled in config
                if (option('tearoom1.seo-ai.sitemap.enabled', true) === false) {
                    return new Response('Sitemap is disabled', 'text/plain', 404);
                }
                
                $seoAi = new SeoAi(kirby());
                $sitemap = $seoAi->getSitemap();
                
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $xml .= '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n";
                
                // Check if multilanguage site
                if (kirby()->multilang()) {
                    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
                } else {
                    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
                }
                
                foreach ($sitemap as $item) {
                    $xml .= "\n    <url>";
                    $xml .= "\n        <loc>" . htmlspecialchars($item['url']) . "</loc>";
                    $xml .= "\n        <lastmod>" . $item['lastmod'] . "</lastmod>";
                    $xml .= "\n        <changefreq>" . $item['changefreq'] . "</changefreq>";
                    $xml .= "\n        <priority>" . $item['priority'] . "</priority>";
                    
                    // Add alternate language links
                    if (!empty($item['alternates'])) {
                        foreach ($item['alternates'] as $alternate) {
                            $xml .= "\n        <xhtml:link rel=\"alternate\" hreflang=\"" . 
                                   htmlspecialchars($alternate['lang']) . "\" href=\"" . 
                                   htmlspecialchars($alternate['url']) . "\" />";
                        }
                    }
                    
                    $xml .= "\n    </url>";
                }
                
                $xml .= "\n</urlset>";
                
                return new Response($xml, 'application/xml');
            }
        ]
    ],
    'pageMethods' => [
        'generateSeoDescription' => function (string $content = null, string $languageCode = null) {
            $seoAi = new SeoAi(kirby());
            $languageCode = $languageCode ?? kirby()->language()?->code() ?? 'en';
            $content = $content ?? $this->text()->toString();
            
            if (empty($content)) {
                return null;
            }
            
            return $seoAi->generateDescription($content, ['language' => $languageCode]);
        }
    ],
    'fieldMethods' => [
        'toSeoDescription' => function ($field) {
            $seoAi = new SeoAi(kirby());
            $languageCode = kirby()->language()?->code() ?? 'en';
            return $seoAi->generateDescription($field->value(), ['language' => $languageCode]);
        }
    ],
    'hooks' => [
        'page.update:after' => function ($newPage, $oldPage) {
            // Auto-generate description if enabled and field is empty
            $autoGenerate = option('tearoom1.seo-ai.autoGenerate', false);
            
            if (!$autoGenerate || $newPage->intendedTemplate()->name() === 'error') {
                return;
            }
            
            try {
                if ($newPage->metaDescription()->isEmpty()) {
                    $content = $newPage->text()->toString();
                    if (!empty($content)) {
                        $seoAi = new SeoAi(kirby());
                        $languageCode = kirby()->language()?->code() ?? 'en';
                        $description = $seoAi->generateDescription($content, ['language' => $languageCode]);
                        
                        if ($description) {
                            $newPage->update(['metaDescription' => $description], kirby()->language()?->code());
                        }
                    }
                }
            } catch (Exception $e) {
                // Silently fail - don't break the save operation
                kirbylog('SEO-AI auto-generate error: ' . $e->getMessage());
            }
        }
    ],
    'assets' => [
        'js' => [
            __DIR__ . '/src/index.js',
        ],
        'css' => [
            __DIR__ . '/src/index.css',
        ]
    ]
]);
