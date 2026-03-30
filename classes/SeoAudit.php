<?php

namespace TearoomOne;

class SeoAudit
{
    public static function auditPageId(string $pageId): ?array
    {
        $model = self::getModel($pageId);
        if (!$model) {
            return null;
        }

        return self::auditModel($model);
    }

    public static function auditModel($model): array
    {
        $page = PageDataBuilder::fromModel($model, ['includeOgImage' => true]);
        $siteSettings = ConfigHelper::getSiteSettings();
        $validationSettings = option('tearoom1.meta-kit.validation', []);

        $checks = [];

        if ($page['id'] !== 'site') {
            $checks[] = self::auditSlug($page, $validationSettings);
        }

        $checks[] = self::auditTitle($page, $siteSettings, $validationSettings, 'meta');
        $checks[] = self::auditDescription($page, $siteSettings, $validationSettings, 'meta');

        if ($page['id'] !== 'site') {
            $checks[] = self::auditTitle($page, $siteSettings, $validationSettings, 'og');
            $checks[] = self::auditDescription($page, $siteSettings, $validationSettings, 'og');
        }

        $checks[] = self::auditOgImage($page, $siteSettings);
        $checks[] = self::auditRobots($page);

        $issues = [];
        $counts = ['good' => 0, 'review' => 0, 'fix' => 0];

        foreach ($checks as $check) {
            $counts[$check['status']]++;
            if ($check['status'] !== 'good') {
                $issues[] = $check;
            }
        }

        usort($issues, function ($a, $b) {
            $severityWeight = ['fix' => 0, 'review' => 1, 'good' => 2];
            $aWeight = $severityWeight[$a['status']] ?? 9;
            $bWeight = $severityWeight[$b['status']] ?? 9;

            if ($aWeight === $bWeight) {
                return strcmp($a['field'], $b['field']);
            }

            return $aWeight <=> $bWeight;
        });

        return [
            'page' => [
                'id' => $page['id'],
                'title' => $page['title'],
                'template' => $page['template'],
                'panelUrl' => $page['panelUrl'],
                'language' => $page['language'] ?? null,
            ],
            'counts' => $counts,
            'status' => $counts['fix'] > 0 ? 'fix' : ($counts['review'] > 0 ? 'review' : 'good'),
            'checks' => $checks,
            'issues' => $issues,
        ];
    }

    public static function auditPageIds(array $pageIds): array
    {
        $audits = [];
        foreach ($pageIds as $pageId) {
            $audit = self::auditPageId($pageId);
            if ($audit) {
                $audits[] = $audit;
            }
        }

        return self::aggregateAudits($audits, 'selection');
    }

    public static function auditEntireSite(): array
    {
        $pagesData = MetaKitController::getPages()['pages'] ?? [];
        $audits = [];

        foreach ($pagesData as $pageData) {
            $audit = self::auditPageId($pageData['id']);
            if ($audit) {
                $audits[] = $audit;
            }
        }

        return self::aggregateAudits($audits, 'site');
    }

    public static function aggregateAudits(array $audits, string $scope = 'selection'): array
    {
        $counts = ['good' => 0, 'review' => 0, 'fix' => 0];
        $issueTypes = [];
        $pages = [];
        $siteAudit = null;

        foreach ($audits as $audit) {
            $counts[$audit['status']]++;

            if (($audit['page']['id'] ?? null) === 'site') {
                $siteAudit = $audit;
                continue;
            }

            $pages[] = [
                'id' => $audit['page']['id'],
                'title' => $audit['page']['title'],
                'template' => $audit['page']['template'],
                'panelUrl' => $audit['page']['panelUrl'],
                'status' => $audit['status'],
                'fixCount' => $audit['counts']['fix'],
                'reviewCount' => $audit['counts']['review'],
                'topIssue' => $audit['issues'][0]['message'] ?? null,
            ];

            foreach ($audit['issues'] as $issue) {
                $key = $issue['field'] . ':' . $issue['code'];
                if (!isset($issueTypes[$key])) {
                    $issueTypes[$key] = [
                        'field' => $issue['field'],
                        'code' => $issue['code'],
                        'status' => $issue['status'],
                        'message' => $issue['message'],
                        'suggestion' => $issue['suggestion'],
                        'count' => 0,
                        'pages' => [],
                    ];
                }

                $issueTypes[$key]['count']++;
                $issueTypes[$key]['pages'][] = $audit['page']['title'];
            }
        }

        usort($pages, function ($a, $b) {
            return [$b['fixCount'], $b['reviewCount'], $a['title']] <=> [$a['fixCount'], $a['reviewCount'], $b['title']];
        });

        $issueTypes = array_values($issueTypes);
        usort($issueTypes, function ($a, $b) {
            $severityWeight = ['fix' => 0, 'review' => 1, 'good' => 2];
            return [$severityWeight[$a['status']] ?? 9, -$a['count']] <=> [$severityWeight[$b['status']] ?? 9, -$b['count']];
        });

        return [
            'scope' => $scope,
            'counts' => $counts,
            'pagesReviewed' => count(array_filter($audits, fn ($audit) => ($audit['page']['id'] ?? null) !== 'site')),
            'siteAudit' => $siteAudit,
            'topPages' => array_slice($pages, 0, 10),
            'topIssues' => array_slice($issueTypes, 0, 10),
            'audits' => $audits,
        ];
    }

    private static function getModel(string $pageId)
    {
        $kirby = kirby();
        if ($pageId === 'site') {
            return $kirby->site();
        }

        return $kirby->page($pageId);
    }

    private static function auditTitle(array $page, array $siteSettings, array $validationSettings, string $type): array
    {
        $fieldLabel = $type === 'og' ? 'OG Title' : 'Meta Title';
        $fieldKey = $type === 'og' ? 'ogTitle' : 'metaTitle';
        $effectiveTitle = self::getEffectiveTitle($page, $type);
        $length = self::getTitleLength($page, $siteSettings, $type);
        $status = self::getStatusForLength($page, $length, $type === 'og' ? 'ogTitle' : 'title', $validationSettings);
        $issues = [];

        if (!$effectiveTitle) {
            $issues[] = [
                'status' => 'fix',
                'code' => 'missing',
                'message' => 'No effective title is available.',
                'suggestion' => 'Add a clear, specific title for this page.',
            ];
        } elseif ($status === 'error' || $status === '') {
            $issues[] = [
                'status' => 'fix',
                'code' => 'length-error',
                'message' => self::getLengthMessage($page, $type === 'og' ? 'ogTitle' : 'title', $length, $validationSettings, true),
                'suggestion' => 'Rewrite the title so it lands inside the optimal range.',
            ];
        } else {
            if (self::isInheritedFromLanguage($page, $fieldKey, $siteSettings)) {
                $issues[] = [
                    'status' => 'review',
                    'code' => 'language-inheritance',
                    'message' => 'Inherited from the main language.',
                    'suggestion' => 'Add a localized title for this language.',
                ];
            }

            if ($status === 'warning') {
                $issues[] = [
                    'status' => 'review',
                    'code' => 'length-warning',
                    'message' => self::getLengthMessage($page, $type === 'og' ? 'ogTitle' : 'title', $length, $validationSettings, false),
                    'suggestion' => 'Tighten or expand the title to move it into the optimal range.',
                ];
            }
        }

        return self::makeCheck($fieldLabel, $issues, [
            'value' => $effectiveTitle,
            'length' => $length,
            'source' => self::getInheritanceSource($page, $fieldKey, $siteSettings),
        ]);
    }

    private static function auditDescription(array $page, array $siteSettings, array $validationSettings, string $type): array
    {
        $fieldLabel = $type === 'og' ? 'OG Description' : 'Meta Description';
        $fieldKey = $type === 'og' ? 'ogDescription' : 'metaDescription';
        $effectiveDescription = self::getEffectiveDescription($page, $type, $siteSettings);
        $length = $effectiveDescription ? mb_strlen($effectiveDescription) : 0;
        $status = self::getStatusForLength($page, $length, $type === 'og' ? 'ogDescription' : 'description', $validationSettings);
        $issues = [];

        if (!$effectiveDescription) {
            $issues[] = [
                'status' => 'fix',
                'code' => 'missing',
                'message' => 'No effective description is available.',
                'suggestion' => 'Add a concise description that explains what this page contains.',
            ];
        } elseif ($status === 'error' || $status === '') {
            $issues[] = [
                'status' => 'fix',
                'code' => 'length-error',
                'message' => self::getLengthMessage($page, $type === 'og' ? 'ogDescription' : 'description', $length, $validationSettings, true),
                'suggestion' => 'Rewrite the description so it stays inside the configured range.',
            ];
        } else {
            if ($type === 'meta' && self::isInheritedFromSite($page, $fieldKey, $siteSettings)) {
                $issues[] = [
                    'status' => 'review',
                    'code' => 'site-inheritance',
                    'message' => 'Inherited from the site default description.',
                    'suggestion' => 'Add a unique page description to avoid repeating the same message site-wide.',
                ];
            }

            if (self::isInheritedFromLanguage($page, $fieldKey, $siteSettings)) {
                $issues[] = [
                    'status' => 'review',
                    'code' => 'language-inheritance',
                    'message' => 'Inherited from the main language.',
                    'suggestion' => 'Add a localized description for this language.',
                ];
            }

            if ($status === 'warning') {
                $issues[] = [
                    'status' => 'review',
                    'code' => 'length-warning',
                    'message' => self::getLengthMessage($page, $type === 'og' ? 'ogDescription' : 'description', $length, $validationSettings, false),
                    'suggestion' => 'Adjust the description length so it moves into the optimal range.',
                ];
            }
        }

        return self::makeCheck($fieldLabel, $issues, [
            'value' => $effectiveDescription,
            'length' => $length,
            'source' => self::getInheritanceSource($page, $fieldKey, $siteSettings),
        ]);
    }

    private static function auditOgImage(array $page, array $siteSettings): array
    {
        $issues = [];

        if (!($page['hasOgImage'] ?? false) && !($siteSettings['siteHasOgImage'] ?? false)) {
            $issues[] = [
                'status' => 'fix',
                'code' => 'missing',
                'message' => 'No OG image is available.',
                'suggestion' => 'Add a 1200×630 social image for stronger sharing previews.',
            ];
        } elseif (!($page['hasOgImage'] ?? false) && ($siteSettings['siteHasOgImage'] ?? false)) {
            $issues[] = [
                'status' => 'review',
                'code' => 'site-inheritance',
                'message' => 'Using the site-wide OG image.',
                'suggestion' => 'Consider a page-specific OG image if this page is important for sharing.',
            ];
        }

        return self::makeCheck('OG Image', $issues, [
            'value' => $page['ogImage']['filename'] ?? null,
            'source' => ($page['hasOgImage'] ?? false) ? 'page image' : (($siteSettings['siteHasOgImage'] ?? false) ? 'site' : null),
        ]);
    }

    private static function auditRobots(array $page): array
    {
        $issues = [];
        $robots = $page['robots'] ?? 'index, follow';

        if (str_contains($robots, 'noindex')) {
            $issues[] = [
                'status' => 'review',
                'code' => 'noindex',
                'message' => 'This page is set to noindex.',
                'suggestion' => 'Confirm that this page really should stay out of search results.',
            ];
        }

        return self::makeCheck('Robots', $issues, ['value' => $robots]);
    }

    private static function auditSlug(array $page, array $validationSettings): array
    {
        $slug = explode('/', $page['id']);
        $slug = end($slug) ?: '';
        $numSlashes = substr_count($page['id'], '/');
        $wordCount = count(array_filter(preg_split('/[-_]/', $slug)));
        $length = mb_strlen($slug);
        $avgWordLength = $wordCount > 0 ? (int)ceil($length / $wordCount) : $length;
        $cfg = self::getSlugValidationConfig($page, $validationSettings);
        $issues = [];

        foreach ([
            ['key' => 'Depth', 'value' => $numSlashes, 'config' => $cfg['depth']],
            ['key' => 'Words', 'value' => $wordCount, 'config' => $cfg['words']],
            ['key' => 'Length', 'value' => $length, 'config' => $cfg['length']],
        ] as $check) {
            if (self::isOutsideRange($check['value'], $check['config']['warning'])) {
                $issues[] = [
                    'status' => 'fix',
                    'code' => strtolower($check['key']) . '-error',
                    'message' => "{$check['key']} {$check['value']} is outside warning range {$check['config']['warning']['min']}-{$check['config']['warning']['max']}.",
                    'suggestion' => 'Shorten or simplify this path structure.',
                ];
                continue;
            }

            if (self::isOutsideRange($check['value'], $check['config']['optimal'])) {
                $issues[] = [
                    'status' => 'review',
                    'code' => strtolower($check['key']) . '-warning',
                    'message' => "{$check['key']} {$check['value']} is outside optimal range {$check['config']['optimal']['min']}-{$check['config']['optimal']['max']}.",
                    'suggestion' => 'Refine the slug so it is shorter, flatter, and easier to scan.',
                ];
            }
        }

        return self::makeCheck('Slug', $issues, [
            'value' => $page['id'],
            'length' => $length,
            'wordCount' => $wordCount,
            'avgWordLength' => $avgWordLength,
        ]);
    }

    private static function makeCheck(string $field, array $issues, array $meta = []): array
    {
        $status = 'good';
        if (array_filter($issues, fn ($issue) => $issue['status'] === 'fix')) {
            $status = 'fix';
        } elseif (!empty($issues)) {
            $status = 'review';
        }

        return array_merge([
            'field' => $field,
            'status' => $status,
            'issues' => $issues,
            'code' => $issues[0]['code'] ?? 'ok',
            'message' => $issues[0]['message'] ?? 'Looks good.',
            'suggestion' => $issues[0]['suggestion'] ?? '',
        ], $meta);
    }

    private static function getEffectiveTitle(array $page, string $type = 'meta'): ?string
    {
        $isOg = $type === 'og';
        $inheritance = $isOg ? ($page['ogTitleInheritance'] ?? null) : ($page['metaTitleInheritance'] ?? null);
        $inheritedMetaTitle = $page['metaTitleInheritance']['inheritedValue'] ?? null;

        if (($inheritance['inherited'] ?? false) && !empty($inheritance['inheritedValue'])) {
            return $inheritance['inheritedValue'];
        }

        if ($isOg) {
            return ($page['hasOgTitle'] ?? false)
                ? ($page['ogTitle'] ?? null)
                : (($page['hasMetaTitle'] ?? false ? ($page['metaTitle'] ?? null) : $inheritedMetaTitle) ?: ($page['title'] ?? null));
        }

        return (($page['hasMetaTitle'] ?? false ? ($page['metaTitle'] ?? null) : $inheritedMetaTitle) ?: ($page['title'] ?? null));
    }

    private static function getEffectiveDescription(array $page, string $type = 'meta', array $siteSettings = []): ?string
    {
        $isOg = $type === 'og';
        $inheritance = $isOg ? ($page['ogDescriptionInheritance'] ?? null) : ($page['metaDescriptionInheritance'] ?? null);
        $inheritedMetaDescription = $page['metaDescriptionInheritance']['inheritedValue'] ?? null;

        if (($inheritance['inherited'] ?? false) && !empty($inheritance['inheritedValue'])) {
            return $inheritance['inheritedValue'];
        }

        if ($isOg) {
            if ($page['hasOgDescription'] ?? false) {
                return $page['ogDescription'] ?? null;
            }
            if (($page['hasMetaDescription'] ?? false) || $inheritedMetaDescription) {
                return $page['metaDescription'] ?? $inheritedMetaDescription;
            }
            return $siteSettings['siteMetaDescription'] ?? null;
        }

        return ($page['hasMetaDescription'] ?? false ? ($page['metaDescription'] ?? null) : $inheritedMetaDescription)
            ?: ($siteSettings['siteMetaDescription'] ?? null);
    }

    private static function getInheritanceSource(array $page, string $fieldType, array $siteSettings = [])
    {
        $hasInheritedMetaTitle = !empty($page['metaTitleInheritance']['inheritedValue']);
        $hasInheritedMetaDescription = !empty($page['metaDescriptionInheritance']['inheritedValue']);
        $inheritanceMap = [
            'metaTitle' => $page['metaTitleInheritance'] ?? null,
            'metaDescription' => $page['metaDescriptionInheritance'] ?? null,
            'ogTitle' => $page['ogTitleInheritance'] ?? null,
            'ogDescription' => $page['ogDescriptionInheritance'] ?? null,
        ];

        $inheritance = $inheritanceMap[$fieldType] ?? null;

        if (($inheritance['inherited'] ?? false) && !empty($inheritance['inheritedFrom'])) {
            return $inheritance['inheritedFrom'];
        }

        return match ($fieldType) {
            'metaTitle' => !($page['hasMetaTitle'] ?? false) ? 'page title' : false,
            'metaDescription' => !($page['hasMetaDescription'] ?? false) && !empty($siteSettings['siteMetaDescription']) ? 'site' : false,
            'ogTitle' => !($page['hasOgTitle'] ?? false) ? ((($page['hasMetaTitle'] ?? false) || $hasInheritedMetaTitle) ? 'meta title' : 'page title') : false,
            'ogDescription' => !($page['hasOgDescription'] ?? false)
                ? ((($page['hasMetaDescription'] ?? false) || $hasInheritedMetaDescription)
                    ? 'meta description'
                    : (!empty($siteSettings['siteMetaDescription']) ? 'site' : false))
                : false,
            default => false,
        };
    }

    private static function isInheritedFromSite(array $page, string $fieldType, array $siteSettings = []): bool
    {
        return self::getInheritanceSource($page, $fieldType, $siteSettings) === 'site';
    }

    private static function isInheritedFromLanguage(array $page, string $fieldType, array $siteSettings = []): bool
    {
        $source = self::getInheritanceSource($page, $fieldType, $siteSettings);
        return !empty($source) && !in_array($source, ['site', 'page title', 'meta title', 'meta description'], true);
    }

    private static function getTitleLength(array $page, array $siteSettings, string $type): int
    {
        $title = self::getEffectiveTitle($page, $type);
        if (!$title) {
            return 0;
        }

        $shouldAppend = !empty($siteSettings['appendSiteName']);
        $appendTo = array_map('trim', explode(',', $siteSettings['appendSiteNameTo'] ?? 'meta,og'));
        $target = $type === 'og' ? 'og' : 'meta';

        if (!$shouldAppend || !in_array($target, $appendTo, true)) {
            return mb_strlen($title);
        }

        $siteTitle = $siteSettings['siteMetaTitle'] ?? '';
        if (!$siteTitle) {
            return mb_strlen($title);
        }

        $separator = $siteSettings['titleSeparator'] ?? '|';
        return mb_strlen($title . ' ' . $separator . ' ' . $siteTitle);
    }

    private static function getStatusForLength(array $page, int $length, string $type, array $validationSettings): string
    {
        if ($length === 0) {
            return '';
        }

        $ranges = self::getRangesForType($page, $type, $validationSettings);
        if (!$ranges) {
            return '';
        }

        if ($length >= ($ranges['optimal']['min'] ?? 0) && $length <= ($ranges['optimal']['max'] ?? PHP_INT_MAX)) {
            return 'optimal';
        }

        if ($length >= ($ranges['warning']['min'] ?? 0) && $length <= ($ranges['warning']['max'] ?? PHP_INT_MAX)) {
            return 'warning';
        }

        return 'error';
    }

    private static function getRangesForType(array $page, string $type, array $validationSettings): array
    {
        $defaults = $validationSettings['ranges'] ?? [];
        $templates = $validationSettings['templates'] ?? [];
        $template = $page['template'] ?? null;
        $templateConfig = ($template && isset($templates[$template])) ? $templates[$template] : [];
        $templateRanges = $templateConfig['ranges'] ?? $templateConfig;

        $defaultRanges = [
            'title' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
            'ogTitle' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
            'description' => ['optimal' => ['min' => 140, 'max' => 160], 'warning' => ['min' => 126, 'max' => 176]],
            'ogDescription' => ['optimal' => ['min' => 150, 'max' => 250], 'warning' => ['min' => 135, 'max' => 300]],
        ];

        $merged = array_merge($defaultRanges, $defaults, $templateRanges);
        return $merged[$type] ?? $defaultRanges['title'];
    }

    private static function getSlugValidationConfig(array $page, array $validationSettings): array
    {
        $defaults = $validationSettings['slug'] ?? [];
        $templates = $validationSettings['templates'] ?? [];
        $template = $page['template'] ?? null;
        $templateConfig = ($template && isset($templates[$template])) ? $templates[$template] : [];
        $templateSlug = $templateConfig['slug'] ?? [];

        $base = [
            'depth' => ['optimal' => ['min' => 0, 'max' => 2], 'warning' => ['min' => 0, 'max' => 3]],
            'words' => ['optimal' => ['min' => 1, 'max' => 8], 'warning' => ['min' => 1, 'max' => 10]],
            'length' => ['optimal' => ['min' => 1, 'max' => 60], 'warning' => ['min' => 1, 'max' => 70]],
            'wordLength' => ['optimal' => ['min' => 1, 'max' => 15], 'warning' => ['min' => 1, 'max' => 20]],
        ];

        foreach ($base as $key => $fallback) {
            $raw = array_merge($defaults[$key] ?? [], $templateSlug[$key] ?? []);

            if (empty($raw)) {
                continue;
            }

            if (isset($raw['optimal'], $raw['warning'])) {
                $base[$key] = [
                    'optimal' => array_merge($fallback['optimal'], $raw['optimal'] ?? []),
                    'warning' => array_merge($fallback['warning'], $raw['warning'] ?? []),
                ];
                continue;
            }

            $warningMax = is_numeric($raw['warningMax'] ?? null) ? (int)$raw['warningMax'] : $fallback['warning']['max'];
            $errorMax = is_numeric($raw['errorMax'] ?? null) ? (int)$raw['errorMax'] : $fallback['warning']['max'];
            $base[$key] = [
                'optimal' => array_merge($fallback['optimal'], ['max' => $warningMax]),
                'warning' => array_merge($fallback['warning'], ['max' => $errorMax]),
            ];
        }

        return $base;
    }

    private static function isOutsideRange(int $value, array $range): bool
    {
        if (isset($range['min']) && $value < $range['min']) {
            return true;
        }

        if (isset($range['max']) && $value > $range['max']) {
            return true;
        }

        return false;
    }

    private static function getLengthMessage(array $page, string $type, int $length, array $validationSettings, bool $error): string
    {
        $ranges = self::getRangesForType($page, $type, $validationSettings);
        $optimal = ($ranges['optimal']['min'] ?? 0) . '-' . ($ranges['optimal']['max'] ?? 0);
        $warning = ($ranges['warning']['min'] ?? 0) . '-' . ($ranges['warning']['max'] ?? 0);

        if ($error) {
            return "Length {$length} is outside warning range {$warning}. Optimal is {$optimal}.";
        }

        return "Length {$length} is outside optimal range {$optimal}, but still inside warning range {$warning}.";
    }
}
