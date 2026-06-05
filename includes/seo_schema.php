<?php
// ============================================================
// includes/seo_schema.php  ─  SEO/AEO 結構化資料產生器
// 輸出 JSON-LD Schema.org 標記，供前台頁面引用
// ============================================================

/**
 * 產生該頁面的 Canonical URL
 */
function getCanonicalUrl(string $sub, string $pageKey): string {
    // IS_LOCAL/STAGING 走 query 模式 → 直接用 pageKey 對應 site/{pageKey}.php
    if (IS_LOCAL || IS_STAGING) {
        return siteUrl($sub, $pageKey === 'home' ? '' : $pageKey);
    }
    // PROD 走 pretty URL：articles 對應 /column 路徑（對齊旭森風格）
    $prodPathMap = [
        'home'         => '',
        'articles'     => 'column',
    ];
    $path = $prodPathMap[$pageKey] ?? $pageKey;
    $base = 'https://' . $sub . '.' . MINISITE_DOMAIN;
    if ($path === '') return $base . '/';
    return $base . '/' . $path;
}

/**
 * 依 industry 推斷 schema.org @type（LocalBusiness 子型）
 * 旭森參考：餐飲業用 Restaurant 比 LocalBusiness 對 Google rich result 更友善
 */
function localBusinessTypeFor(string $industry): string {
    $ind = $industry ?: '';
    if (preg_match('/(餐|食|料理|咖啡|甜點|甜品|烘焙|燒肉|牛排|火鍋|鍋物|壽司|麵|飯|披薩|拉麵|烤|飲料|手搖|茶飲|甜|蛋糕|麵包|食坊|食堂|宵夜)/u', $ind)) return 'Restaurant';
    if (preg_match('/(美容|美甲|美髮|美睫|紋繡|沙龍)/u', $ind))                                     return 'BeautySalon';
    if (preg_match('/(美髮|理髮)/u', $ind))                                                       return 'HairSalon';
    if (preg_match('/(汽車|機車|車體|烤漆|改裝|大燈|輪胎)/u', $ind))                                  return 'AutomotiveBusiness';
    if (preg_match('/(室內設計|室內裝修|室內裝潢|空間設計|空間規劃|裝潢設計|建築設計|景觀設計|商空設計|店面設計|室內空間)/u', $ind)) return 'HomeAndConstructionBusiness';
    if (preg_match('/(清潔|搬家|裝潢|防水|抓漏|電器|水電|油漆|害蟲|除蟲|消毒|園藝|綠美化)/u', $ind))    return 'HomeAndConstructionBusiness';
    return 'LocalBusiness';
}

/**
 * LocalBusiness Schema（每頁都輸出）
 *
 * @param array|null $menuBlock store_blocks 中 type='menu' 的 data（餐飲業用，含 groups/items）
 */
function schemaLocalBusiness(array $client, array $social, array $services, ?array $menuBlock = null): array {
    $bizType = localBusinessTypeFor($client['industry'] ?? '');
    $bizUrl  = (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/site/index.php?sub=' . ($client['subdomain'] ?? $client['slug'])
        : 'https://' . ($client['subdomain'] ?? $client['slug']) . '.' . MINISITE_DOMAIN . '/';
    $schema = [
        '@type'   => $bizType,
        '@id'     => '#business',
        'name'    => $client['brand_name'],
        'url'     => $bizUrl,
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

    // ── ContactPoint（schema.org 推薦：把電話/email/聯絡類型包成物件）──
    if (!empty($client['phone']) || !empty($client['email'])) {
        $cp = [
            '@type'       => 'ContactPoint',
            'contactType' => 'customer service',
        ];
        if (!empty($client['phone'])) $cp['telephone'] = $client['phone'];
        if (!empty($client['email'])) $cp['email']     = $client['email'];
        $schema['contactPoint'] = $cp;
    }

    // ── PostalAddress（新欄位優先，舊欄位 fallback）──
    // 旭森參考：streetAddress + addressLocality（行政區）+ addressRegion（縣市）+ postalCode
    $hasNewAddr = !empty($client['address_street']) || !empty($client['address_district'])
        || !empty($client['address_region']) || !empty($client['postal_code']);
    if ($hasNewAddr) {
        $addr = ['@type' => 'PostalAddress', 'addressCountry' => 'TW'];
        if (!empty($client['address_street']))   $addr['streetAddress']   = $client['address_street'];
        if (!empty($client['address_district'])) $addr['addressLocality'] = $client['address_district'];
        if (!empty($client['address_region']))   $addr['addressRegion']   = $client['address_region'];
        if (!empty($client['postal_code']))      $addr['postalCode']      = $client['postal_code'];
        $schema['address'] = $addr;
    } elseif (!empty($client['address'])) {
        // 舊欄位 fallback：把完整字串塞 addressLocality（保留現有行為）
        $schema['address'] = [
            '@type'           => 'PostalAddress',
            'addressCountry'  => 'TW',
            'addressLocality' => $client['address'],
        ];
    }

    // ── GeoCoordinates（有經緯度才出）──
    if (!empty($client['latitude']) && !empty($client['longitude'])) {
        $schema['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (string)$client['latitude'],
            'longitude' => (string)$client['longitude'],
        ];
    }

    // ── openingHoursSpecification（有 JSON 才出）──
    // 格式：{"mon":"09:00-18:00","tue":"09:00-18:00","sun":"closed"}
    if (!empty($client['opening_hours_json'])) {
        $hours = is_string($client['opening_hours_json'])
            ? json_decode($client['opening_hours_json'], true)
            : $client['opening_hours_json'];
        if (is_array($hours)) {
            $dayMap = [
                'mon' => 'Monday',  'tue' => 'Tuesday', 'wed' => 'Wednesday',
                'thu' => 'Thursday','fri' => 'Friday',  'sat' => 'Saturday', 'sun' => 'Sunday',
            ];
            $opens = [];
            foreach ($hours as $key => $range) {
                if (!isset($dayMap[$key]) || $range === 'closed' || empty($range)) continue;
                // "09:00-18:00" → opens/closes
                if (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $range, $m)) {
                    $opens[] = [
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => $dayMap[$key],
                        'opens'     => $m[1],
                        'closes'    => $m[2],
                    ];
                }
            }
            if ($opens) $schema['openingHoursSpecification'] = $opens;
        }
    }

    if (!empty($client['industry'])) {
        $schema['additionalType'] = $client['industry'];
    }
    // Schema.org LocalBusiness：
    //   - logo 走品牌 logo（向量/方形小圖）
    //   - image 走實景照片優先（hero / 店面），fallback 到 logo
    //   Google rich result 要的是「能識別店家的代表性圖片」，logo 通常不夠資訊量
    if (!empty($client['logo_path'])) {
        $schema['logo'] = BASE_URL . '/' . $client['logo_path'];
    }
    if (!empty($client['hero_image_path'])) {
        $schema['image'] = BASE_URL . '/' . $client['hero_image_path'];
    } elseif (!empty($client['logo_path'])) {
        $schema['image'] = BASE_URL . '/' . $client['logo_path'];
    }

    // 社群連結
    $sameAs = [];
    if (!empty($social['fb_page_url']))  $sameAs[] = $social['fb_page_url'];
    if (!empty($social['instagram_url'])) $sameAs[] = $social['instagram_url'];
    if (!empty($social['youtube_url']))  $sameAs[] = $social['youtube_url'];
    if (!empty($social['line_url']))     $sameAs[] = $social['line_url'];
    if ($sameAs) $schema['sameAs'] = $sameAs;

    // ── 服務項目 hasOfferCatalog 已移除 ──
    // 原因：LocalBusiness 同時帶 offers + review + aggregateRating 會觸發
    // Google 的 Product schema 嚴格規則檢查（self-serving review 警告）。
    // 拿掉 hasOfferCatalog 後不會少 SEO 信號 — 服務清單已在：
    //   1. services 頁的 ItemList schema（schemaServiceList()）
    //   2. service detail 頁的 Service schema（schemaServiceDetail()）
    // GSC 報告 wnc_10030260 對應的 review/aggregateRating 警告由此解。
    // 餐飲 Restaurant 的 hasMenu 保留 — Menu schema 不算 offers、不觸發 Product 規則。

    // ── hasMenu（餐飲業 Restaurant 專屬，schema.org Menu type）──
    // Google rich result：在 SERP 直接顯示「{店家} 菜單」卡片，含菜名 / 價格
    if ($bizType === 'Restaurant' && $menuBlock && !empty($menuBlock['groups'])) {
        $sections = [];
        foreach ($menuBlock['groups'] as $g) {
            if (empty($g['items'])) continue;
            $items = [];
            foreach ($g['items'] as $it) {
                if (empty($it['name'])) continue;
                $menuItem = [
                    '@type' => 'MenuItem',
                    'name'  => $it['name'],
                ];
                if (!empty($it['desc'])) $menuItem['description'] = $it['desc'];
                if (!empty($it['price'])) {
                    $menuItem['offers'] = [
                        '@type'         => 'Offer',
                        'price'         => (string)$it['price'],
                        'priceCurrency' => 'TWD',
                    ];
                }
                if (!empty($it['image'])) {
                    $menuItem['image'] = (str_starts_with($it['image'], 'http') ? $it['image'] : BASE_URL . '/' . $it['image']);
                }
                $items[] = $menuItem;
            }
            if ($items) {
                $sections[] = [
                    '@type'    => 'MenuSection',
                    'name'     => $g['name'] ?? '',
                    'hasMenuItem' => $items,
                ];
            }
        }
        if ($sections) {
            $schema['hasMenu'] = [
                '@type'        => 'Menu',
                'name'         => $menuBlock['title'] ?? ($client['brand_name'] . '菜單'),
                'hasMenuSection' => $sections,
            ];
        }
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
 *
 * @param array|null $extra 第三層麵包屑（用於 detail 頁），形如 ['name'=>'服務名','url'=>'...']
 */
function schemaBreadcrumb(string $sub, string $pageKey, string $brandName, ?array $extra = null): array {
    $items = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => getCanonicalUrl($sub, 'home')],
    ];

    $pageNames = [
        'services'     => '服務項目',
        'cases'        => '施工案例',
        'testimonials' => '網友分享',
        'contact'      => '聯絡我們',
        'articles'     => '專欄',
        // detail 頁 — 第二層連回列表
        'service_detail' => '服務項目',
        'case_detail'    => '施工案例',
        'article_detail' => '專欄',
    ];

    // detail 頁第二層用列表頁 URL
    $listKey = $pageKey;
    if ($pageKey === 'service_detail') $listKey = 'services';
    if ($pageKey === 'case_detail')    $listKey = 'cases';
    if ($pageKey === 'article_detail') $listKey = 'articles';

    if ($pageKey !== 'home' && isset($pageNames[$pageKey])) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => $pageNames[$pageKey],
            'item'     => getCanonicalUrl($sub, $listKey),
        ];
    }

    // 第三層（detail 頁的服務/案例名稱）
    if ($extra && !empty($extra['name'])) {
        $item = [
            '@type'    => 'ListItem',
            'position' => 3,
            'name'     => $extra['name'],
        ];
        if (!empty($extra['url'])) $item['item'] = $extra['url'];
        $items[] = $item;
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
 * WebSite Schema — 首頁專屬，給 Google Sitelinks Searchbox 用
 * 旭森參考做法：每個獨立官網都應該有一個 WebSite + SearchAction
 */
function schemaWebSite(array $client, string $sub): array {
    $base = (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/site/index.php?sub=' . $sub
        : 'https://' . $sub . '.' . MINISITE_DOMAIN;
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'url'      => $base . '/',
        'name'     => $client['brand_name'],
        'description' => !empty($client['tagline']) ? $client['tagline'] :
            (!empty($client['about_text']) ? mb_strimwidth(strip_tags($client['about_text']), 0, 200, '…') : ''),
        // 站內搜尋（mini-site 目前沒做搜尋，先放 placeholder pattern；未來實作站內搜尋時 URL 對齊即可）
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $base . '/?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
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

    // 0. WebSite + SearchAction（只在首頁輸出，避免重複）
    if ($pageKey === 'home') {
        $schemas[] = schemaWebSite($client, $sub);
    }

    // 餐飲業：撈 modular blocks 中 type='menu' 的資料，給 Restaurant.hasMenu schema 用
    $menuBlock = null;
    if (localBusinessTypeFor($client['industry'] ?? '') === 'Restaurant') {
        try {
            $_db = getDB();
            $_st = $_db->prepare("SELECT data FROM store_blocks WHERE client_id=? AND type='menu' AND is_active=1 ORDER BY sort_order LIMIT 1");
            $_st->execute([(int)$client['id']]);
            $_row = $_st->fetch(PDO::FETCH_ASSOC);
            if ($_row && !empty($_row['data'])) {
                $menuBlock = json_decode($_row['data'], true);
            }
        } catch (Exception $e) { /* swallow — menu schema 是 nice-to-have */ }
    }

    // 1. LocalBusiness（每頁都有；餐飲 client 變 Restaurant 並嵌 Menu schema）
    $biz = schemaLocalBusiness($client, $social, $services, $menuBlock);

    // 加入評價
    $aggRating = schemaAggregateRating($testimonials);
    if ($aggRating) $biz['aggregateRating'] = $aggRating;

    // 首頁 + testimonials 頁加入個別 reviews（testimonials 頁是評價彙整頁，理應帶 Review schema）
    if (in_array($pageKey, ['home', 'testimonials'], true) && $testimonials) {
        $biz['review'] = schemaReviews(array_slice($testimonials, 0, 10));
    }

    $schemas[] = array_merge(['@context' => 'https://schema.org'], $biz);

    // 2. BreadcrumbList（非首頁才有）
    if ($pageKey !== 'home') {
        // detail 頁第三層用 $site['_current_service'] / _current_case
        $extra = null;
        if ($pageKey === 'service_detail' && !empty($site['_current_service'])) {
            $svc = $site['_current_service'];
            $extra = [
                'name' => $svc['name'],
                'url'  => getCanonicalUrl($sub, 'services') . '/' . rawurlencode($svc['slug'] ?? ''),
            ];
        } elseif ($pageKey === 'case_detail' && !empty($site['_current_case'])) {
            $c = $site['_current_case'];
            $extra = [
                'name' => $c['title'] ?? $c['name'] ?? '',
                'url'  => getCanonicalUrl($sub, 'cases') . '/' . rawurlencode($c['slug'] ?? ''),
            ];
        } elseif ($pageKey === 'article_detail' && !empty($site['_current_article'])) {
            $a = $site['_current_article'];
            $extra = [
                'name' => $a['title'] ?? '',
                'url'  => getCanonicalUrl($sub, 'articles') . '/' . rawurlencode($a['slug'] ?? ''),
            ];
        }
        $schemas[] = schemaBreadcrumb($sub, $pageKey, $client['brand_name'], $extra);
    }

    // 2.5 Service schema — 服務詳細頁專屬
    if ($pageKey === 'service_detail' && !empty($site['_current_service'])) {
        $svc = $site['_current_service'];
        $svcSchema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'Service',
            'name'       => $svc['name'],
            'provider'   => [
                '@type' => 'LocalBusiness',
                'name'  => $client['brand_name'],
                '@id'   => '#business',
            ],
        ];
        if (!empty($svc['short_desc'])) $svcSchema['description'] = $svc['short_desc'];
        elseif (!empty($svc['full_desc'])) $svcSchema['description'] = mb_strimwidth(strip_tags($svc['full_desc']), 0, 300, '…');
        if (!empty($svc['image_path'])) $svcSchema['image'] = BASE_URL . '/' . $svc['image_path'];
        if (!empty($svc['price_text'])) {
            $svcSchema['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => 'TWD',
                'description'   => $svc['price_text'],
            ];
        }
        // 服務區域：用 client address_region 優先
        if (!empty($client['address_region'])) {
            $svcSchema['areaServed'] = ['@type' => 'City', 'name' => $client['address_region']];
        }
        // 該服務的 FAQ → mainEntityOfPage FAQPage（避免跟首頁 FAQPage 重複，用 hasPart）
        if (!empty($svc['faqs'])) {
            $faqs = [];
            foreach ($svc['faqs'] as $faq) {
                $faqs[] = [
                    '@type'          => 'Question',
                    'name'           => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ];
            }
            // 獨立輸出 FAQPage 給該服務（service detail 頁專屬）
            $schemas[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $faqs,
            ];
        }
        $schemas[] = $svcSchema;
    }

    // 2.6 Article schema — 案例詳細頁專屬
    if ($pageKey === 'case_detail' && !empty($site['_current_case'])) {
        $c = $site['_current_case'];
        $caseUrl = getCanonicalUrl($sub, 'cases') . '/' . rawurlencode($c['slug'] ?? '');
        $artSchema = [
            '@context'  => 'https://schema.org',
            '@type'     => 'Article',
            'headline'  => $c['title'] ?? '',
            'mainEntityOfPage' => $caseUrl,
            'author'    => [
                '@type' => 'Organization',
                'name'  => $client['brand_name'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => $client['brand_name'],
            ],
        ];
        if (!empty($c['description'])) {
            $artSchema['description'] = mb_strimwidth(strip_tags($c['description']), 0, 300, '…');
        }
        // image：after > before > client hero
        $imgPath = $c['after_image'] ?? ($c['before_image'] ?? null);
        if ($imgPath) {
            // 若 caseThumb 函式可用就用，否則直接拼
            $thumbed = function_exists('caseThumb') ? caseThumb($imgPath) : $imgPath;
            $artSchema['image'] = BASE_URL . '/' . $thumbed;
        } elseif (!empty($client['hero_image_path'])) {
            $artSchema['image'] = BASE_URL . '/' . $client['hero_image_path'];
        }
        // 出版/修改時間
        if (!empty($c['created_at'])) {
            $artSchema['datePublished'] = date('c', strtotime($c['created_at']));
            $artSchema['dateModified']  = date('c', strtotime($c['created_at']));
        }
        // logo 給 publisher（Google Article rich result 要 publisher.logo）
        if (!empty($client['logo_path'])) {
            $artSchema['publisher']['logo'] = [
                '@type' => 'ImageObject',
                'url'   => BASE_URL . '/' . $client['logo_path'],
            ];
        }
        // about：關聯的服務名稱
        if (!empty($c['svc_name'])) {
            $artSchema['about'] = $c['svc_name'];
        }
        $schemas[] = $artSchema;
    }

    // 2.7 BlogPosting schema — 專欄文章詳細頁（Phase 7）
    if ($pageKey === 'article_detail' && !empty($site['_current_article'])) {
        $a = $site['_current_article'];
        $articleUrl = (IS_LOCAL || IS_STAGING)
            ? BASE_URL . '/site/article_detail.php?sub=' . urlencode($sub) . '&slug=' . urlencode($a['slug'])
            : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column/' . rawurlencode($a['slug']);
        $blogSchema = [
            '@context'  => 'https://schema.org',
            '@type'     => 'BlogPosting',
            'headline'  => $a['title'] ?? '',
            'mainEntityOfPage' => $articleUrl,
            'author'    => ['@type' => 'Organization', 'name' => $client['brand_name']],
            'publisher' => ['@type' => 'Organization', 'name' => $client['brand_name']],
        ];
        if (!empty($a['summary'])) $blogSchema['description'] = $a['summary'];
        if (!empty($a['cover_image'])) $blogSchema['image'] = BASE_URL . '/' . $a['cover_image'];
        elseif (!empty($client['hero_image_path'])) $blogSchema['image'] = BASE_URL . '/' . $client['hero_image_path'];
        $publishDate = $a['published_at'] ?? $a['created_at'] ?? null;
        if ($publishDate) {
            $blogSchema['datePublished'] = date('c', strtotime($publishDate));
            $blogSchema['dateModified']  = date('c', strtotime($a['updated_at'] ?? $publishDate));
        }
        if (!empty($client['logo_path'])) {
            $blogSchema['publisher']['logo'] = [
                '@type' => 'ImageObject',
                'url'   => BASE_URL . '/' . $client['logo_path'],
            ];
        }
        if (!empty($a['category'])) {
            $blogSchema['articleSection'] = $a['category'];
        }
        // tags → keywords
        if (!empty($a['tags_json'])) {
            $tags = is_string($a['tags_json']) ? json_decode($a['tags_json'], true) : $a['tags_json'];
            if (is_array($tags) && $tags) {
                $blogSchema['keywords'] = implode(', ', $tags);
            }
        }
        $schemas[] = $blogSchema;
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

    // 5. 案例列表頁：ItemList（每案例一個 ListItem，含 url）
    if ($pageKey === 'cases' && !empty($site['cases'])) {
        $_caseBase = getCanonicalUrl($sub, 'cases');
        $_caseItems = [];
        $_i = 1;
        foreach ($site['cases'] as $c) {
            $_slug = $c['slug'] ?? '';
            if (!$_slug) continue;
            $_caseItems[] = [
                '@type'    => 'ListItem',
                'position' => $_i++,
                'url'      => $_caseBase . '/' . rawurlencode($_slug),
                'name'     => $c['title'] ?? '',
            ];
        }
        if ($_caseItems) {
            $schemas[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'ItemList',
                'name'            => $client['brand_name'] . '施工案例',
                'itemListElement' => $_caseItems,
            ];
        }
    }

    // 6. 專欄列表頁：ItemList（articles 表，try/catch 防表未建）
    if ($pageKey === 'articles') {
        try {
            $_artDb = getDB();
            $_artStmt = $_artDb->prepare("SELECT slug, title FROM articles WHERE client_id=? AND is_active=1 AND slug IS NOT NULL AND slug<>'' ORDER BY published_at DESC, id DESC LIMIT 50");
            $_artStmt->execute([(int)$client['id']]);
            $_artRows = $_artStmt->fetchAll(PDO::FETCH_ASSOC);
            $_artBase = getCanonicalUrl($sub, 'articles');
            $_artItems = [];
            $_i = 1;
            foreach ($_artRows as $a) {
                $_artItems[] = [
                    '@type'    => 'ListItem',
                    'position' => $_i++,
                    'url'      => $_artBase . '/' . rawurlencode($a['slug']),
                    'name'     => $a['title'] ?? '',
                ];
            }
            if ($_artItems) {
                $schemas[] = [
                    '@context'        => 'https://schema.org',
                    '@type'           => 'ItemList',
                    'name'            => $client['brand_name'] . '專欄文章',
                    'itemListElement' => $_artItems,
                ];
            }
        } catch (Exception $e) { /* articles 表未建或欄位不齊：跳過 */ }
    }

    // 輸出
    foreach ($schemas as $s) {
        echo "\n<script type=\"application/ld+json\">\n";
        echo json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}
