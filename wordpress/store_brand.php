<?php

/**
 * Single source of truth for store branding.
 * Edit values here once to update branding across pages.
 *
 * QUICK EDIT MAP
 * 1) Store identity: STORE_NAME_* constants
 * 2) Media assets: hero, banners, section images
 * 3) Home categories: "HOME CATEGORIES (EDIT HERE)"
 * 4) Footer logo strip: STORE_SCROLL_BRAND_LOGO_* constants
 * 5) Advanced global replacements: store_brand_replacements()
 */

/**
 * ==============================
 * STORE IDENTITY (NAMES)
 * ==============================
 * Affects visible brand name text, SEO text replacements,
 * and any template using store_brand('name_*' / 'full_ar').
 */
if (!defined('STORE_NAME_AR')) {
    define('STORE_NAME_AR', 'اسم المتجر');
}

if (!defined('STORE_BUSINESS_AR')) {
    define('STORE_BUSINESS_AR', 'للملابس النسائية');
}

if (!defined('STORE_NAME_FULL_AR')) {
    define('STORE_NAME_FULL_AR', trim(STORE_NAME_AR . ' ' . STORE_BUSINESS_AR));
}

if (!defined('STORE_NAME_EN')) {
    define('STORE_NAME_EN', 'Store Name');
}

if (!defined('STORE_SITE_NAME_EN')) {
    define('STORE_SITE_NAME_EN', STORE_NAME_EN . ' Store');
}

/**
 * ==============================
 * CORE BRAND ASSETS (LOGO / ICON)
 * ==============================
 * Brand assets (single edit point):
 * - Change STORE_LOGO_MAIN_URL to update logo everywhere.
 * - Change STORE_FAVICON_URL to update favicon everywhere.
 */
if (!defined('STORE_LOGO_MAIN_URL')) {
    define('STORE_LOGO_MAIN_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png');
}

if (!defined('STORE_LOGO_ALT_URL')) {
    define('STORE_LOGO_ALT_URL', STORE_LOGO_MAIN_URL);
}

if (!defined('STORE_FAVICON_URL')) {
    define('STORE_FAVICON_URL', 'public/images/favicon.svg');
}

/**
 * ==============================
 * HOME / LANDING MEDIA ASSETS
 * ==============================
 * Affects homepage sections:
 * - Hero video
 * - Top/middle/bottom promo banners
 * - Before/after block
 * - Reviews images
 * - About/history image
 * - Breadcrumb backgrounds (inner pages)
 */
if (!defined('STORE_HERO_VIDEO_URL')) {
    define('STORE_HERO_VIDEO_URL', 'https://res.cloudinary.com/dhqavjbx6/video/upload/v1774107994/CLOTHING_BOUTIQUE_PROMO_VIDEO_BRISBANE_1080P_HD_sflshz.mp4');
}

if (!defined('STORE_ADS_BANNER_PRIMARY_URL')) {
    define('STORE_ADS_BANNER_PRIMARY_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774103970/AI_Eraser_image_2_z6p96t.png');
}

if (!defined('STORE_ADS_BANNER_SECONDARY_URL')) {
    define('STORE_ADS_BANNER_SECONDARY_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1766465668/Jewelry_Brand_-_Artboard_2_gfie26.jpg');
}

if (!defined('STORE_ADS_BANNER_TERTIARY_URL')) {
    define('STORE_ADS_BANNER_TERTIARY_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774102356/AI_Eraser_image_er1iah.png');
}

if (!defined('STORE_ONE_TOUCH_BEFORE_URL')) {
    define('STORE_ONE_TOUCH_BEFORE_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774105371/before_ykjmtw.png');
}

if (!defined('STORE_ONE_TOUCH_AFTER_URL')) {
    define('STORE_ONE_TOUCH_AFTER_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774105370/after_h1xjda.png');
}

if (!defined('STORE_REVIEWS_IMAGE_1_URL')) {
    define('STORE_REVIEWS_IMAGE_1_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774106370/Screenshot_2026-03-21_151903_o2tzsh.png');
}

if (!defined('STORE_REVIEWS_IMAGE_2_URL')) {
    define('STORE_REVIEWS_IMAGE_2_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774106415/1768534942096d28b4354ebaa87b43868ab8d32ba9_thumbnail_999x999_qp3a7a.jpg');
}

if (!defined('STORE_HISTORY_SECTION_IMAGE_URL')) {
    define('STORE_HISTORY_SECTION_IMAGE_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774104290/AI_Eraser_image_3_mvhpg4.png');
}

if (!defined('STORE_BREADCRUMB_BG_URL')) {
    define('STORE_BREADCRUMB_BG_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774108097/da03ca5e2169685ac4867c812a4f0d4c_g8wpob_s2jnsr.webp');
}

/**
 * ==============================
 * HOME CATEGORIES (EDIT HERE)
 * ==============================
 * Add/remove category cards from this list only.
 * Each item supports:
 * - category_url: target URL
 * - label: card title
 * - image_url: card image
 */
if (!defined('STORE_HOME_CATEGORIES_TITLE')) {
    define('STORE_HOME_CATEGORIES_TITLE', 'التصنيفات');
}

if (!function_exists('store_brand_home_categories')) {
    /**
     * Home category card data source.
     * Add/remove items to control category cards on homepage.
     */
    function store_brand_home_categories(): array
    {
        return [
            [
                'category_url' => 'filter.php?category=%D8%AC%D8%A7%D9%83%D9%8A%D8%AA',
                'label' => 'جاكيت',
                'image_url' => 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774107248/l_20261-s6kt66z8-cvl-80-62-91-176_5_xcil6i.webp',
            ],
            [
                'category_url' => 'filter.php?category=%D9%85%D9%84%D8%A7%D8%A8%D8%B3%20%D8%B5%D9%8A%D9%81%D9%8A%D8%A9',
                'label' => 'ملابس صيفية',
                'image_url' => 'https://images.pexels.com/photos/4458519/pexels-photo-4458519.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
            ],
            [
                'category_url' => 'filter.php?category=%D8%B3%D8%B1%D8%A7%D9%88%D9%8A%D9%84',
                'label' => 'سراويل',
                'image_url' => 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774107182/f70a007d538ded96cf932b8f73d0d6ea.jpg_960x960q80.jpg__qpn0kh.webp',
            ],
            [
                'category_url' => 'filter.php?category=%D8%A3%D8%AD%D8%AF%D9%8A%D8%A9',
                'label' => 'أحذية',
                'image_url' => 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774107077/e2d8eedd-8ee3-46ed-b8b3-13a557527fb2_tm7qqp.webp',
            ],
            [
                'category_url' => 'filter.php?category=%D8%A7%D9%83%D8%B3%D8%B3%D9%88%D8%A7%D8%B1%D8%A7%D8%AA%20%D9%86%D8%B3%D8%A7%D8%A6%D9%8A%D8%A9',
                'label' => 'اكسسوارات نسائية',
                'image_url' => 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774106986/Velvetine_White_Rosegod_mesh_3414fc8c-7eb3-4815-8f32-a4cf2e5987ef_1024x1024_vwblov.webp',
            ],
            [
                'category_url' => 'filter.php?category=%D8%A8%D8%A7%D9%82%D8%A7%D8%AA%20%D9%88%D8%B9%D8%B1%D9%88%D8%B6',
                'label' => 'عروض',
                'image_url' => 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1765043616/Black_Friday_Poster_with_Shiny_Balloons_and_Confetti_on_White_Background_vector_illustration_smeagorl_8584397___Stockfresh_y0v5up.jpg',
            ],
        ];
    }
}

if (!function_exists('store_brand_home_categories_html')) {
    /**
     * Converts category array into card HTML injected by
     * __STORE_HOME_CATEGORIES_ITEMS__ token in categurie.html.
     */
    function store_brand_home_categories_html(): string
    {
        $html = [];
        foreach (store_brand_home_categories() as $item) {
            $href = htmlspecialchars((string)($item['category_url'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $image = htmlspecialchars((string)($item['image_url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $style = "background-image: url('{$image}');";

            $html[] = '<a href="' . $href . '" class="cat-card-hidden group flex flex-col items-center cursor-pointer">';
            $html[] = '    <div class="w-16 h-16 md:w-28 md:h-28 rounded-full border border-gray-300 bg-white p-1 overflow-hidden relative shadow-sm">';
            $html[] = '        <div class="w-full h-full rounded-full bg-cover bg-center bg-no-repeat transition-transform duration-500 ease-in-out lg:group-hover:scale-110" style="' . $style . '"></div>';
            $html[] = '    </div>';
            $html[] = '    <span class="mt-2 text-gray-800 font-bold text-[11px] md:text-sm text-center">' . $label . '</span>';
            $html[] = '</a>';
        }

        return implode("\n", $html);
    }
}

/**
 * Scrolling brand logos above footer (home page).
 * Keep these separated here for easy updates.
 */
if (!defined('STORE_SCROLL_BRAND_LOGO_1_URL')) {
    define('STORE_SCROLL_BRAND_LOGO_1_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973897/logo2_tvjddo.png');
}

if (!defined('STORE_SCROLL_BRAND_LOGO_2_URL')) {
    define('STORE_SCROLL_BRAND_LOGO_2_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973897/logo3_gj2ztk.png');
}

if (!defined('STORE_SCROLL_BRAND_LOGO_3_URL')) {
    define('STORE_SCROLL_BRAND_LOGO_3_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973897/logo4_jayicd.png');
}

if (!defined('STORE_SCROLL_BRAND_LOGO_4_URL')) {
    define('STORE_SCROLL_BRAND_LOGO_4_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973898/logo5_lkwwil.png');
}

if (!defined('STORE_SCROLL_BRAND_LOGO_5_URL')) {
    define('STORE_SCROLL_BRAND_LOGO_5_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973898/logo6_ysrebx.png');
}

if (!defined('STORE_SCROLL_BRAND_LOGO_6_URL')) {
    define('STORE_SCROLL_BRAND_LOGO_6_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973898/logo7_payslx.png');
}

if (!function_exists('store_brand')) {
    /**
     * Name resolver helper for templates.
     * Use this when you need consistent brand name variants.
     */
    function store_brand(string $key = 'full_ar'): string
    {
        switch ($key) {
            case 'name_ar':
                return STORE_NAME_AR;
            case 'business_ar':
                return STORE_BUSINESS_AR;
            case 'full_ar':
                return STORE_NAME_FULL_AR;
            case 'name_en':
                return STORE_NAME_EN;
            case 'site_en':
                return STORE_SITE_NAME_EN;
            default:
                return STORE_NAME_FULL_AR;
        }
    }
}

if (!function_exists('store_brand_asset')) {
    /**
     * Asset resolver helper for templates.
     * Maps short keys to asset constants above.
     */
    function store_brand_asset(string $key = 'logo_main'): string
    {
        switch ($key) {
            case 'logo_main':
                return STORE_LOGO_MAIN_URL;
            case 'logo_alt':
                return STORE_LOGO_ALT_URL;
            case 'favicon':
                return STORE_FAVICON_URL;
            case 'hero_video':
                return STORE_HERO_VIDEO_URL;
            case 'ads_banner_primary':
                return STORE_ADS_BANNER_PRIMARY_URL;
            case 'ads_banner_secondary':
                return STORE_ADS_BANNER_SECONDARY_URL;
            case 'ads_banner_tertiary':
                return STORE_ADS_BANNER_TERTIARY_URL;
            case 'one_touch_before':
                return STORE_ONE_TOUCH_BEFORE_URL;
            case 'one_touch_after':
                return STORE_ONE_TOUCH_AFTER_URL;
            case 'reviews_image_1':
                return STORE_REVIEWS_IMAGE_1_URL;
            case 'reviews_image_2':
                return STORE_REVIEWS_IMAGE_2_URL;
            case 'history_image':
                return STORE_HISTORY_SECTION_IMAGE_URL;
            case 'breadcrumb_bg':
                return STORE_BREADCRUMB_BG_URL;
            case 'scroll_brand_logo_1':
                return STORE_SCROLL_BRAND_LOGO_1_URL;
            case 'scroll_brand_logo_2':
                return STORE_SCROLL_BRAND_LOGO_2_URL;
            case 'scroll_brand_logo_3':
                return STORE_SCROLL_BRAND_LOGO_3_URL;
            case 'scroll_brand_logo_4':
                return STORE_SCROLL_BRAND_LOGO_4_URL;
            case 'scroll_brand_logo_5':
                return STORE_SCROLL_BRAND_LOGO_5_URL;
            case 'scroll_brand_logo_6':
                return STORE_SCROLL_BRAND_LOGO_6_URL;
            default:
                return STORE_LOGO_MAIN_URL;
        }
    }
}

if (!function_exists('store_brand_replacements')) {
    /**
     * Global text/token replacement table.
     * Advanced section: used by output buffering to replace
     * old hardcoded strings with current brand constants.
     */
    function store_brand_replacements(): array
    {
        return [
            // Store asset variants
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png' => STORE_LOGO_MAIN_URL,
            'https://your-domain.com/images/lgicon.png' => STORE_LOGO_MAIN_URL,
            'https://www.your-domain.com/images/lgicon.png' => STORE_LOGO_MAIN_URL,
            'public/images/favicon.svg' => STORE_FAVICON_URL,
            '/public/images/favicon.svg' => STORE_FAVICON_URL,
            '__STORE_HERO_VIDEO_URL__' => STORE_HERO_VIDEO_URL,
            '__STORE_ADS_BANNER_PRIMARY_URL__' => STORE_ADS_BANNER_PRIMARY_URL,
            '__STORE_ADS_BANNER_SECONDARY_URL__' => STORE_ADS_BANNER_SECONDARY_URL,
            '__STORE_ADS_BANNER_TERTIARY_URL__' => STORE_ADS_BANNER_TERTIARY_URL,
            '__STORE_ONE_TOUCH_BEFORE_URL__' => STORE_ONE_TOUCH_BEFORE_URL,
            '__STORE_ONE_TOUCH_AFTER_URL__' => STORE_ONE_TOUCH_AFTER_URL,
            '__STORE_REVIEWS_IMAGE_1_URL__' => STORE_REVIEWS_IMAGE_1_URL,
            '__STORE_REVIEWS_IMAGE_2_URL__' => STORE_REVIEWS_IMAGE_2_URL,
            '__STORE_HISTORY_SECTION_IMAGE_URL__' => STORE_HISTORY_SECTION_IMAGE_URL,
            '__STORE_BREADCRUMB_BG_URL__' => STORE_BREADCRUMB_BG_URL,
            '__STORE_SCROLL_BRAND_LOGO_1_URL__' => STORE_SCROLL_BRAND_LOGO_1_URL,
            '__STORE_SCROLL_BRAND_LOGO_2_URL__' => STORE_SCROLL_BRAND_LOGO_2_URL,
            '__STORE_SCROLL_BRAND_LOGO_3_URL__' => STORE_SCROLL_BRAND_LOGO_3_URL,
            '__STORE_SCROLL_BRAND_LOGO_4_URL__' => STORE_SCROLL_BRAND_LOGO_4_URL,
            '__STORE_SCROLL_BRAND_LOGO_5_URL__' => STORE_SCROLL_BRAND_LOGO_5_URL,
            '__STORE_SCROLL_BRAND_LOGO_6_URL__' => STORE_SCROLL_BRAND_LOGO_6_URL,
            '__STORE_HOME_CATEGORIES_TITLE__' => STORE_HOME_CATEGORIES_TITLE,
            '__STORE_HOME_CATEGORIES_ITEMS__' => store_brand_home_categories_html(),
            'https://soufyana2.github.io/my-videos/hero2.mp4' => STORE_HERO_VIDEO_URL,
            'https://res.cloudinary.com/dhqavjbx6/video/upload/v1774091195/videoplayback_fgtabz.mp4' => STORE_HERO_VIDEO_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1766467634/Jewellery_Store_1_wqegkl.jpg' => STORE_ADS_BANNER_PRIMARY_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774083316/Men_s_fashion_in_moody_tones_ttkayj.png' => STORE_ADS_BANNER_PRIMARY_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1766465668/Jewelry_Brand_-_Artboard_2_gfie26.jpg' => STORE_ADS_BANNER_SECONDARY_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1765151066/Jewelry_Etsy_Banner_Deisgn_1_ognhzj.jpg' => STORE_ADS_BANNER_TERTIARY_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089617/Gemini_Generated_Image_xk69gbxk69gbxk69_qzkljn.png' => STORE_ADS_BANNER_TERTIARY_URL,
            'https://i.pinimg.com/736x/4f/a9/f0/4fa9f07328cfcbd48931f27ec2ea7410.jpg' => STORE_ONE_TOUCH_BEFORE_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774090586/before_v2krgy.png' => STORE_ONE_TOUCH_BEFORE_URL,
            'https://i.pinimg.com/736x/43/68/c8/4368c81e65ece6ec205fed82189e53a9.jpg' => STORE_ONE_TOUCH_AFTER_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774090575/after_e2ocpv.png' => STORE_ONE_TOUCH_AFTER_URL,
            'https://rewimg-eu.kwcdn.com/review-image/2066d90972/76f164cc-89ff-4587-8994-87f0c8a4920d.jpeg?imageMogr2/auto-orient%7CimageView2/2/w/816/q/70/format/avif' => STORE_REVIEWS_IMAGE_1_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089983/177003399091d3962133208342c41600b834134d63_thumbnail_999x999_fbpnjr.jpg' => STORE_REVIEWS_IMAGE_1_URL,
            'https://rewimg-eu.kwcdn.com/review-image/20237f7514/69861fcb-c2d3-4023-ac3c-1c064893c661.jpeg?imageMogr2/auto-orient%7CimageView2/2/w/816/q/70/format/avif0' => STORE_REVIEWS_IMAGE_2_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089983/1754551116f5554e6c8486a463b6152d9a51e6eb04_thumbnail_999x999_ofkugy.jpg' => STORE_REVIEWS_IMAGE_2_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/v1765688538/Jewellry_Social_Proof_Ad_1_m3sh1z.jpg' => STORE_HISTORY_SECTION_IMAGE_URL,
            'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089047/wmremove-transformed_gpxaaf.png' => STORE_HISTORY_SECTION_IMAGE_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764797982/da03ca5e2169685ac4867c812a4f0d4c_g8wpob.jpg' => STORE_BREADCRUMB_BG_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973897/logo2_tvjddo.png' => STORE_SCROLL_BRAND_LOGO_1_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973897/logo3_gj2ztk.png' => STORE_SCROLL_BRAND_LOGO_2_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973897/logo4_jayicd.png' => STORE_SCROLL_BRAND_LOGO_3_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973898/logo5_lkwwil.png' => STORE_SCROLL_BRAND_LOGO_4_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973898/logo6_ysrebx.png' => STORE_SCROLL_BRAND_LOGO_5_URL,
            'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764973898/logo7_payslx.png' => STORE_SCROLL_BRAND_LOGO_6_URL,

            // Arabic variants
            'عبدالوهاب للملابس الرجالية' => STORE_NAME_FULL_AR,
            'عبد الوهاب للملابس الرجالية' => STORE_NAME_FULL_AR,
            'عبدالوهاب للملابس النسائية' => STORE_NAME_FULL_AR,
            'عبد الوهاب للملابس النسائية' => STORE_NAME_FULL_AR,
            'عبدالوهاب' => STORE_NAME_AR,
            'عبد الوهاب' => STORE_NAME_AR,

            // Arabic brand/content normalization for women's store
            'عبدالوهاب للملابس الرجالية' => STORE_NAME_FULL_AR,
            'عبد الوهاب للملابس الرجالية' => STORE_NAME_FULL_AR,
            'عبدالوهاب للملابس النسائية' => STORE_NAME_FULL_AR,
            'عبد الوهاب للملابس النسائية' => STORE_NAME_FULL_AR,
            'للملابس الرجالية' => STORE_BUSINESS_AR,
            'ملابس رجالية' => 'ملابس نسائية',
            'الملابس الرجالية' => 'الملابس النسائية',
            'الأزياء الرجالية' => 'الأزياء النسائية',
            'ازياء رجالية' => 'ازياء نسائية',
            'رجالية' => 'نسائية',
            'رجالي' => 'نسائي',
            'قمصان' => 'ملابس صيفية',
            'قميص' => 'قطعة صيفية',
            'البناطيل' => 'السراويل',
            'بناطيل' => 'سراويل',
            'أحذية' => 'أحدية',
            'احذية' => 'احدية',
            'اكسسوارات رجالية' => 'اكسسوارات نسائية',
            'إكسسوارات رجالية' => 'اكسسوارات نسائية',
            'تصاميم رجالية' => 'تصاميم نسائية',
            'أسلوبك الرجالي' => 'أسلوبك النسائي',

            // Arabic feminine tone normalization (CTA and copy voice)
            'تسوق الآن' => 'تسوقي الآن',
            'تسوق أونلاين' => 'تسوقي أونلاين',
            'تسوق أفضل' => 'تسوقي أفضل',
            'تصفية وتسوق' => 'تصفية وتسوقي',
            'استكشفوا' => 'استكشفي',
            'اكتشفوا' => 'اكتشفي',
            'غوصوا' => 'غوصي',
            'تصفح الآن' => 'تصفحي الآن',
            'انضم إلينا' => 'انضمي إلينا',
            'سجل دخولك الآن' => 'سجلي دخولك الآن',
            'تعرف علينا الآن' => 'تعرفي علينا الآن',
            'اتصل الآن' => 'اتصلي الآن',
            'تحقق من حسابك' => 'تحققي من حسابك',
            'تحقق الآن' => 'تحققي الآن',
            'لم تستلم الرمز؟' => 'لم تستلمي الرمز؟',
            'ليس لديك حساب؟' => 'ليس لديكِ حساب؟',
            'لديك حساب بالفعل؟' => 'لديكِ حساب بالفعل؟',
            'حصولك على هذا الخصم قبل انتهائه، اضغط على الزر أدناه واطلبه الآن!' => 'حصولكِ على هذا الخصم قبل انتهائه، اضغطي على الزر أدناه واطلبيه الآن!',
            'ثقتكم هي أساس نجاحنا، ونسعى دائمًا لتقديم الأفضل.' => 'ثقتكِ هي أساس نجاحنا، ونسعى دائمًا لتقديم الأفضل لكِ.',
            'مرحباً بك،' => 'مرحباً بكِ،',
            'بحسابك' => 'بحسابكِ',
            'عميل سعيد' => 'عميلة سعيدة',
            'عميله' => 'عميلة',

            // English/legacy variants
            'Abdolwahab' => STORE_NAME_EN,
            'Abodlwahab' => STORE_NAME_EN,
            'Bdolwahab Store' => STORE_SITE_NAME_EN,
            'Abdolwahab Logo' => STORE_NAME_EN . ' Logo',
            'PARFUMS & ACCESSORIES' => 'WOMEN CLOTHING',
            'Parfums & Accessories' => 'Women Clothing',
            'Accessories' => 'Women Clothing',
            'accessories' => 'women clothing',
            'mens accessories' => 'womens accessories',
            'Mens Accessories' => 'Womens Accessories',
            'Men Accessories' => 'Women Accessories',
            "Men's Accessories" => "Women's Accessories",
        ];
    }
}

if (!function_exists('store_brand_replace')) {
    /**
     * Applies full brand replacement map to HTML output.
     */
    function store_brand_replace(string $text): string
    {
        $text = strtr($text, store_brand_replacements());

        if (function_exists('store_brand_feminine_regex_replace')) {
            $text = store_brand_feminine_regex_replace($text);
        }

        // Normalize accidental duplicated suffixes from legacy replacements.
        $text = strtr($text, [
            'استكشفيي' => 'استكشفي',
            'اكتشفيي' => 'اكتشفي',
            'كزائرةة' => 'كزائرة',
            'عميلةة' => 'عميلة',
            'بحسابكِِ' => 'بحسابكِ',
        ]);

        return $text;
    }
}

if (!function_exists('store_brand_feminine_regex_replace')) {
    function store_brand_feminine_regex_replace(string $text): string
    {
        $patterns = [
            '/(?<!\p{L})تسوق(?!ي)(?!\p{L})/u' => 'تسوقي',
            '/(?<!\p{L})اكتشف(?!ي)(?!\p{L})/u' => 'اكتشفي',
            '/(?<!\p{L})استكشف(?!ي)(?!\p{L})/u' => 'استكشفي',
            '/(?<!\p{L})اشترك(?!ي)(?!\p{L})/u' => 'اشتركي',
            '/(?<!\p{L})احصل(?!ي)(?!\p{L})/u' => 'احصلي',
            '/(?<!\p{L})أدخل(?!ي)(?!\p{L})/u' => 'أدخلي',
            '/(?<!\p{L})ادخل(?!ي)(?!\p{L})/u' => 'ادخلي',
            '/(?<!\p{L})اتصل(?!ي)(?!\p{L})/u' => 'اتصلي',
            '/(?<!\p{L})اضغط(?!ي)(?!\p{L})/u' => 'اضغطي',
            '/(?<!\p{L})اطلبه(?!ي)(?!\p{L})/u' => 'اطلبيه',
            '/(?<!\p{L})تحقق(?!ي)(?!\p{L})/u' => 'تحققي',
            '/(?<!\p{L})سجل(?!ي)(?!\p{L})/u' => 'سجلي',
            '/(?<!\p{L})كزائر(?!ة)(?!\p{L})/u' => 'كزائرة',
            '/(?<!\p{L})عميل(?!ة)(?!\p{L})/u' => 'عميلة',
            '/(?<!\p{L})عصري(?!ة)(?!\p{L})/u' => 'عصرية',
            '/(?<!\p{L})أنيق(?!ة)(?!\p{L})/u' => 'أنيقة',
            '/(?<!\p{L})انيق(?!ة)(?!\p{L})/u' => 'انيقة',
            '/(?<!\p{L})مميز(?!ة)(?!\p{L})/u' => 'مميزة',
            '/(?<!\p{L})متميز(?!ة)(?!\p{L})/u' => 'متميزة',
        ];

        $result = preg_replace(array_keys($patterns), array_values($patterns), $text);
        return $result ?? $text;
    }
}

if (!function_exists('store_brand_buffer_callback')) {
    /**
     * Output-buffer callback:
     * runs branding replacements on HTML responses only.
     */
    function store_brand_buffer_callback(string $buffer): string
    {
        // Only touch HTML responses, leave JSON/text responses unchanged.
        if (stripos($buffer, '<html') === false && stripos($buffer, '<body') === false) {
            return $buffer;
        }

        return store_brand_replace($buffer);
    }
}

if (!function_exists('store_brand_enable_auto_replace')) {
    /**
     * Enables automatic branding replacement globally.
     * Called early from db.php.
     */
    function store_brand_enable_auto_replace(): void
    {
        static $enabled = false;

        if ($enabled || PHP_SAPI === 'cli') {
            return;
        }

        $enabled = true;
        ob_start('store_brand_buffer_callback');
    }
}

