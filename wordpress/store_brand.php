<?php

/**
 * Single source of truth for store branding.
 * Edit values here once to update branding across pages.
 */
if (!defined('STORE_NAME_AR')) {
    define('STORE_NAME_AR', 'اسم المتجر');
}

if (!defined('STORE_BUSINESS_AR')) {
    define('STORE_BUSINESS_AR', 'للملابس الرجالية');
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

// Home/landing media assets
if (!defined('STORE_HERO_VIDEO_URL')) {
    define('STORE_HERO_VIDEO_URL', 'https://res.cloudinary.com/dhqavjbx6/video/upload/v1774091195/videoplayback_fgtabz.mp4');
}

if (!defined('STORE_ADS_BANNER_PRIMARY_URL')) {
    define('STORE_ADS_BANNER_PRIMARY_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774083316/Men_s_fashion_in_moody_tones_ttkayj.png');
}

if (!defined('STORE_ADS_BANNER_SECONDARY_URL')) {
    define('STORE_ADS_BANNER_SECONDARY_URL', 'https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1766465668/Jewelry_Brand_-_Artboard_2_gfie26.jpg');
}

if (!defined('STORE_ADS_BANNER_TERTIARY_URL')) {
    define('STORE_ADS_BANNER_TERTIARY_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089617/Gemini_Generated_Image_xk69gbxk69gbxk69_qzkljn.png');
}

if (!defined('STORE_ONE_TOUCH_BEFORE_URL')) {
    define('STORE_ONE_TOUCH_BEFORE_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774090586/before_v2krgy.png');
}

if (!defined('STORE_ONE_TOUCH_AFTER_URL')) {
    define('STORE_ONE_TOUCH_AFTER_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774090575/after_e2ocpv.png');
}

if (!defined('STORE_REVIEWS_IMAGE_1_URL')) {
    define('STORE_REVIEWS_IMAGE_1_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089983/177003399091d3962133208342c41600b834134d63_thumbnail_999x999_fbpnjr.jpg');
}

if (!defined('STORE_REVIEWS_IMAGE_2_URL')) {
    define('STORE_REVIEWS_IMAGE_2_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089983/1754551116f5554e6c8486a463b6152d9a51e6eb04_thumbnail_999x999_ofkugy.jpg');
}

if (!defined('STORE_HISTORY_SECTION_IMAGE_URL')) {
    define('STORE_HISTORY_SECTION_IMAGE_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774089047/wmremove-transformed_gpxaaf.png');
}

if (!defined('STORE_BREADCRUMB_BG_URL')) {
    define('STORE_BREADCRUMB_BG_URL', 'https://res.cloudinary.com/dhqavjbx6/image/upload/v1774093558/Gemini_Generated_Image_1b4ir11b4ir11b4i_fbcjrr.png');
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
            'عبدالوهاب' => STORE_NAME_AR,
            'عبد الوهاب' => STORE_NAME_AR,

            // English/legacy variants
            'Abdolwahab' => STORE_NAME_EN,
            'Abodlwahab' => STORE_NAME_EN,
            'Bdolwahab Store' => STORE_SITE_NAME_EN,
            'Abdolwahab Logo' => STORE_NAME_EN . ' Logo',
        ];
    }
}

if (!function_exists('store_brand_replace')) {
    function store_brand_replace(string $text): string
    {
        return strtr($text, store_brand_replacements());
    }
}

if (!function_exists('store_brand_buffer_callback')) {
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
