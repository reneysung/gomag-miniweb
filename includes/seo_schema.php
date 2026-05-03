<?php
// ============================================================
// includes/seo_schema.php  ─  SEO/AEO 結構化資料產生器
// 輸出 JSON-LD Schema.org 標記，供前台頁面引用
// ============================================================

/**
 * 產生該頁面的 Canonical URL
 */
function getCanonicalUrl(string $sub, string $pageKey): string {
    if (IS_LOCAL || IS_STAGING) {
        return siteUrl($sub, $pageKey === 'home' ? '' : $pageKey);
    }
    $base = 'https://' . $sub . '.gomag.com.tw';
    if ($pageKey === 'home') return $base . '/';
    return $base . '/' . $pageKey;
}

/**
 * LocalBusiness Schema（每頁都輸出）
 */
function schemaLocalBusiness(array $client, array $social, array $services): array {
    $schema = [
        '@type'   => 'LocalBusiness',
        '@id'     => '#business',
        'name'    => $client['brand_name'],
        'url'     => (IS_LOCAL || IS_STAGING)
            ? BASE_URL . '/site/index.php?sub=' . ($client['subdomain'] ?? $client['slug'])
            : 'https://' . ($client['subdomain'] ?? $client['slug']) . '.gomag.com.tw/',
    ];

    if (!empty($client['tagline'])) {
        $schema['slogan'] = $client['tagline'];
    }
    if (!empty($client['about_text'])) {
        $schema['description'] = mb_strimwidth($client['about_text'], 0, 300, '…');
    }
    if (!empty($client['phone'])) {
        $schema['telephone'] = $client['phone'];
    }
    if (!empty($client['email'])) {
        $schema['email'] = $client['email'];
    }
    if (!empty($client['address'])) {
        $schema['address'] = [
            '@type'          => 'PostalAddress',
            'addressCountry' => 'TW',
            'addressLocality' => $client['address'],
        ];
    }
    if (!empty($client['industry'])) {
        $schema['additionalType'] = $client['industry'];
    }
    if (!empty($client['logo_path'])) {
        $schema['logo'] = BASE_URL . '/' . $client['logo_path'];
        $schema['image'] = BASE_URL . '/' . $client['logo_path'];
    }

    // 社群連結
    $sameAs = [];
    if (!empty($social['fb_page_url']))  $sameAs[] = $social['fb_page_url'];
    if (!empty($social['instagram_url'])) $sameAs[] = $social['instagram_url'];
    if (!empty($social['youtube_url']))  $sameAs[] = $social['youtube_url'];
    if (!empty($social['line_url']))     $sameAs[] = $social['line_url'];
    if ($sameAs) $schema['sameAs'] = $sameAs;

    // 服務項目（hasOfferCatalog）
    if ($services) {
        $offers = [];
        foreach ($services as $svc) {
            $offer = [
                '@type'       => 'Offer',
                'itemOffered' => [
                    '@type'       => 'Service',
                    'name'        => $svc['name'],
                    'description' => $svc['short_desc'] ?? '',
                ],
            ];
            if (!empty($svc['price_text'])) {
                $offer['itemOffered']['offers'] = [
                    '@type'         => 'Offer',
                    'priceSpecification' => [
                        '@type' => 'UnitPriceSpecification',
                        'priceCurrency' => 'TWD',
                        'description'   => $svc['price_text'],
                    ],
                ];
            }
            $offers[] = $offer;
        }
        $schema['hasOfferCatalog'] = [
            '@type'           => 'OfferCatalog',
            'name'            => $client['brand_name'] . '服務項目',
            'itemListElement' => $offers,
        ];
    }

    return $schema;
}

/**
 * AggregateRating Schema（評價彙總）
 */
function schemaAggregateRating(array $testimonials): ?array {
    if (empty($testimonials)) return null;
    $count = count($testimonials);
    $total = 0;
    foreach ($testimonials as $t) {
        $total += (int)($t['rating'] ?? 5);
    }
    return [
        '@type'       => 'AggregateRating',
        'ratingValue' => round($total / $count, 1),
        'bestRating'  => 5,
        'ratingCount' => $count,
    ];
}

/**
 * Review Schema（個別評價）
 */
function schemaReviews(array $testimonials): array {
    $reviews = [];
    foreach ($testimonials as $t) {
        $review = [
            '@type'        => 'Review',
            'author'       => [
                '@type' => 'Person',
                'name'  => $t['reviewer_name'],
            ],
            'reviewRating' => [
                '@type'       => 'Rating',
                'ratingValue' => (int)($t['rating'] ?? 5),
                'bestRating'  => 5,
            ],
            'reviewBody'   => $t['content'],
        ];
        if (!empty($t['source'])) {
            $review['publisher'] = ['@type' => 'Organization', 'name' => ucfirst($t['source'])];
        }
        $reviews[] = $review;
    }
    return $reviews;
}

/**
 * FAQPage Schema
 */
function schemaFAQ(array $services): ?array {
    $allFaqs = [];
    foreach ($services as $svc) {
        if (!empty($svc['faqs'])) {
            foreach ($svc['faqs'] as $faq) {
                $allFaqs[] = [
                    '@type'          => 'Question',
                    'name'           => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $faq['answer'],
                    ],
                ];
            }
        }
    }
    if (empty($allFaqs)) return null;
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $allFaqs,
    ];
}

/**
 * BreadcrumbList Schema
 */
function schemaBreadcrumb(string $sub, string $pageKey, string $brandName): array {
    $items = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => getCanonicalUrl($sub, 'home')],
    ];

    $pageNames = [
        'services'     => '服務項目',
        'cases'        => '施工案例',
        'testimonials' => '客戶評價',
        'contact'      => '聯絡我們',
    ];

    if ($pageKey !== 'home' && isset($pageNames[$pageKey])) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => $pageNames[$pageKey],
            'item'     => getCanonicalUrl($sub, $pageKey),
        ];
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}

/**
 * Service Schema（服務頁用）
 */
function schemaServiceList(array $services, array $client): array {
    $items = [];
    foreach ($services as $svc) {
        $item = [
            '@type'       => 'Service',
            'name'        => $svc['name'],
            'provider'    => ['@id' => '#business'],
            'areaServed'  => ['@type' => 'City', 'name' => $client['address'] ?? '台灣'],
        ];
        if (!empty($svc['short_desc'])) $item['description'] = $svc['short_desc'];
        if (!empty($svc['full_desc']))  $item['description'] = $svc['full_desc'];
        if (!empty($svc['image_path'])) $item['image'] = BASE_URL . '/' . $svc['image_path'];
        if (!empty($svc['price_text'])) {
            $item['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => 'TWD',
                'description'   => $svc['price_text'],
            ];
        }
        $items[] = $item;
    }
    return $items;
}

/**
 * 主輸出函式：根據頁面產生完整的 JSON-LD <script> 標籤
 */
function outputJsonLd(array $site, string $sub, string $pageKey): void {
    $client      = $site['client'];
    $social      = $site['social'];
    $services    = $site['services'];
    $testimonials = $site['testimonials'];

    $schemas = [];

    // 1. LocalBusiness（每頁都有）
    $biz = schemaLocalBusiness($client, $social, $services);

    // 加入評價
    $aggRating = schemaAggregateRating($testimonials);
    if ($aggRating) $biz['aggregateRating'] = $aggRating;

    // 首頁加入個別 reviews
    if ($pageKey === 'home' && $testimonials) {
        $biz['review'] = schemaReviews(array_slice($testimonials, 0, 5));
    }

    $schemas[] = array_merge(['@context' => 'https://schema.org'], $biz);

    // 2. BreadcrumbList（非首頁才有）
    if ($pageKey !== 'home') {
        $schemas[] = schemaBreadcrumb($sub, $pageKey, $client['brand_name']);
    }

    // 3. FAQPage（首頁 + 服務頁）
    if (in_array($pageKey, ['home', 'services'])) {
        $faq = schemaFAQ($services);
        if ($faq) $schemas[] = $faq;
    }

    // 4. 服務頁：個別 Service
    if ($pageKey === 'services') {
        $svcItems = schemaServiceList($services, $client);
        if ($svcItems) {
            $schemas[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'ItemList',
                'name'            => $client['brand_name'] . '服務項目',
                'itemListElement' => array_map(function($svc, $i) {
                    return [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'item'     => $svc,
                    ];
                }, $svcItems, array_keys($svcItems)),
            ];
        }
    }

    // 輸出
    foreach ($schemas as $s) {
        echo "\n<script type=\"application/ld+json\">\n";
        echo json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}
