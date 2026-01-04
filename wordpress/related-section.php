<?php


// استيراد الفئات المطلوبة بعد autoload
use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// NEW: Caching Configuration
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_LIFETIME', 3600); // 1 hour in seconds

// NEW: Function to get data from cache
function getFromCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile) && (filemtime($cacheFile) + CACHE_LIFETIME > time())) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    return null;
}

// NEW: Function to save data to cache
function saveToCache($key, $data) {
    global $log;

    if (!is_dir(CACHE_DIR)) {
        if (!mkdir(CACHE_DIR, 0775, true)) {
            $log->critical("Failed to create cache directory: " . CACHE_DIR);
            return false;
        }
    }

    $cacheFile = CACHE_DIR . md5($key) . '.json';
    $json_data = json_encode($data);

    if ($json_data === false) {
         $log->error("JSON Encode Error for key: " . $key . ". Error: " . json_last_error_msg());
        return false;
    }

    if (file_put_contents($cacheFile, $json_data) === false) {
       $log->error("Failed to write to cache file: " . $cacheFile);
        return false;
    }
    return true;
}

// NEW: Function to clear specific cache entry
function clearCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
}

// NEW: Function to clear all cache
function clearAllCache() {
    $files = glob(CACHE_DIR . '*.json');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// تحقق من وجود ملف keys.env
if (!file_exists(__DIR__ . '/apikeys.env')) {
    http_response_code(500);
    exit("A technical error occurred. Please try again later. (API keys not found)");
}

// تحميل ملف keys.env
try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
    $dotenv->load();
} catch (Exception $e) {
    http_response_code(500);
    exit("A technical error occurred. Please try again later. (Error loading API keys: " . $e->getMessage() . ")");
}

// إعداد Monolog
define('LOGS_DIR', __DIR__ . '/logs/');
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0775, true);
}

$log = new Logger('wc_store');
$log->pushHandler(new StreamHandler(LOGS_DIR . 'store_errors.log', Logger::WARNING));
$log->pushHandler(new StreamHandler(LOGS_DIR . 'visitors.log', Logger::INFO));

// تسجيل زيارة الصفحة
$log->info('Related Products Section Visit', [
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'device'  => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct Link'
]);

// WooCommerce API Credentials
$consumer_key = $_ENV['consumer_key'] ?? null;
$consumer_secret = $_ENV['secret_key'] ?? null;
$store_url =  $_ENV['wordpress_url'] ?? null;

if (!$consumer_key || !$consumer_secret || !$store_url) {
    http_response_code(500);
    exit("A technical error occurred. Essential WooCommerce API credentials are missing.");
}

// Initialize WooCommerce Client
$woocommerce = new Client(
    $store_url,
    $consumer_key,
    $consumer_secret,
    [
        'version' => 'wc/v3',
        'verify_ssl' => false,
        'timeout' => 30,
        'connect_timeout' => 10,
    ]
);

// ==========================================================
// 1. المنطق الجديد: خوارزمية المنتجات ذات الصلة (Related Products)
// ==========================================================

// الحصول على ID المنتج الحالي من الرابط
$current_product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$related_page1 = [];
$related_page2 = [];

if ($current_product_id > 0) {
    // محاولة جلب بيانات المنتج الحالي من الكاش أو الـ API
    $cacheKeyCurrent = 'product_details_' . $current_product_id;
    $current_product_data = getFromCache($cacheKeyCurrent);

    if (!$current_product_data) {
        try {
            $current_product_data = $woocommerce->get('products/' . $current_product_id);
            $current_product_data = json_decode(json_encode($current_product_data), true);
            saveToCache($cacheKeyCurrent, $current_product_data);
        } catch (Exception $e) {
            $log->error("Failed to fetch current product: " . $e->getMessage());
            $current_product_data = null;
        }
    }

    // إذا وجدنا المنتج، نبحث عن منتجات ذات صلة بذكاء
    if ($current_product_data && !empty($current_product_data['categories'])) {
        $cat_id = $current_product_data['categories'][0]['id'];
        $current_price = (float)($current_product_data['price'] ?? 0);
        $current_attributes = $current_product_data['attributes'] ?? [];

        $cacheKeyRelated = 'related_smart_' . $current_product_id;
        $related_final = getFromCache($cacheKeyRelated);

        if ($related_final === null) {
            try {
                // جلب مرشحين (20 منتج من نفس التصنيف)
                $params = [
                    'category' => $cat_id,
                    'per_page' => 20,
                    'status' => 'publish',
                    'exclude' => [$current_product_id]
                ];
                $candidates = $woocommerce->get('products', $params);
                $candidates = json_decode(json_encode($candidates), true);

                if (!empty($candidates)) {
                    // 1. خلط عشوائي
                    shuffle($candidates);

                    // 2. حساب النقاط
                    foreach ($candidates as $key => $prod) {
                        $score = 0;
                        $p_price = (float)($prod['price'] ?? 0);

                        // نقاط السعر
                        if ($current_price > 0 && $p_price > 0) {
                            $ratio = $p_price / $current_price;
                            if ($ratio >= 0.8 && $ratio <= 1.2) $score += 30;
                            elseif ($ratio >= 0.5 && $ratio <= 1.5) $score += 10;
                        }

                        // نقاط السمات (Attributes)
                        if (!empty($current_attributes) && !empty($prod['attributes'])) {
                            foreach ($current_attributes as $ca) {
                                foreach ($prod['attributes'] as $pa) {
                                    if ($ca['name'] == $pa['name'] && !empty(array_intersect($ca['options'], $pa['options']))) {
                                        $score += 20;
                                    }
                                }
                            }
                        }
                        $candidates[$key]['_score'] = $score;
                    }

                    // 3. الترتيب حسب النقاط
                    usort($candidates, function($a, $b) {
                        return $b['_score'] <=> $a['_score'];
                    });

                    // 4. أخذ أفضل 8
                    $related_final = array_slice($candidates, 0, 8);
                    saveToCache($cacheKeyRelated, $related_final);
                } else {
                    $related_final = [];
                }
            } catch (Exception $e) {
                $log->error("Error fetching related products: " . $e->getMessage());
                $related_final = [];
            }
        }
        
        // تقسيم النتائج لصفحتين
        if (!empty($related_final)) {
            $related_page1 = array_slice($related_final, 0, 4);
            $related_page2 = array_slice($related_final, 4, 4);
        }
    }
}



function renderSkeletonCards($count) {
    for ($i = 0; $i < $count; $i++) {
        renderProductCard(null, true);
    }
}

?>

    

  

    <style>
            * {
        -webkit-overflow-scrolling: touch;
    }
       
        h1, h2, h3, h4, h5, h6 { /* جعل Playfair Display هو الخط الأساسي للعناوين */
            font-family: 'Playfair Display', serif;
        }

        /* Changed to use the new professional font from tailwind config */
        .category-text {
            font-family: 'Lato', sans-serif; /* Professional font for category */
        }
        /* 1. توحيد الخط ليكون مثل الصفحة الرئيسية (Cairo) */
.arabic-font {
    font-family: 'Cairo', sans-serif !important;
}

/* 2. تغيير لون السعر للون الفحمي الداكن (مثل Index) */
.product-card .info-part .product-price-small .price-value {
    color: #3A3A3A !important; /* هذا هو لون الـ accent في الصفحة الرئيسية */
    font-weight: 800 !important;
    font-family: 'Cairo', sans-serif !important;
}

/* 3. تنسيق العنوان ليتطابق مع شكل Index */
.product-card .info-part .product-title {
    font-family: 'Cairo', sans-serif !important;
    font-weight: 700 !important;
    color: #212121 !important; /* لون النص الداكن في Index */
}

/* 4. تنسيق السعر القديم (الخصم) */
.product-card .info-part .product-price-small .old-price {
    color: #9CA3AF !important; /* رمادي فاتح */
    text-decoration: line-through !important;
    font-size: 0.75rem !important;
}
/* هذا يضمن أن تأثير اللمس يعمل فقط على الكمبيوتر والتابلت الكبير */
@media (min-width: 768px) {
    .product-card .wishlist-icon:hover {
        color: #ff4b4b !important;
        border-color: #ff4b4b !important;
    }

    .product-card .wishlist-icon:hover i {
        color: #ff4b4b !important;
    }
}
        /* Arabic font for titles and categories */
        .arabic-font {
            font-family: 'Cairo', sans-serif;
        }


        /* Responsive Hero Height for small and medium screens */


        /* Default card styles (for small/medium screens first, then override for large) */
        .product-card {
            /* background-color: transparent !important; <-- إزالة هذه التجاوزات للسماح بالستايلات في config */
            background-color: transparent; /* استخدام لون من الـ config */
            box-shadow: none !important; /* No shadow by default for smaller screens */
            margin-bottom: 1rem;
            border-radius:0;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Ensure content within the card respects boundaries */
            height: auto; /* مهم: اسمح للبطاقة بتحديد ارتفاعها تلقائيًا */
            min-height: 280px; /* **جديد:** حد أدنى لارتفاع البطاقة لضمان بعض الاتساق على الموبايل */
        }

        /* Image container and image styles */
        .product-card .image-container {
            width: 100%;
            /* height: 200px;  <-- تم إزالة هذا السطر بالكامل */
            padding-bottom: 120%; /* نسبة 5:6 (ارتفاع 120% من العرض) - أفضل لصور المنتجات الطولية قليلاً */
            height: 0; /* ضروري لجعل padding-bottom يعمل كارتفاع */
            position: relative;
            overflow: hidden;
            flex-shrink: 0; /* **جديد:** يمنع حاوية الصورة من الانكماش إذا كان هناك ضغط مساحة */
             border-radius:0;
           
        }
        /* Changed to target img inside image-container for general styles */
        .product-card .image-container img {
             object-fit: cover; /* Ensure image covers the container */
             width: 100%;
             height: 100%;
             position: absolute;
             inset: 0;
             transition: transform 0.3s ease-in-out; /* Add transition */
        }
        
        /* Info part styles for small screens */
        .product-card .info-part {
            /* background-color: transparent !important; <-- إزالة هذه التجاوزات للسماح بالستايلات في config */
            background-color: transparent; /* استخدام لون من الـ config */
            padding: 0.75rem 0.5rem; /* Adjusted padding for smaller screens */
            text-align: right; /* Changed to right for RTL */
            display: flex; /* Make info-part a flex container for its children */
            flex-direction: column; /* Stack its children vertically */
            align-items: flex-start; /* Changed to flex-end for RTL */
            flex-grow: 1; /* **جديد:** اسمح لجزء المعلومات بالنمو لملء أي مساحة متاحة */
            justify-content: flex-start; /* **جديد:** يدفع المحتوى إلى الأسفل داخل info-part لتقليل الفراغ العلوي */
        }
        .product-card .info-part .product-price-small { /* Specific class for prices on small screens */
            display: flex; /* Make price block a flex container */
            flex-direction: row-reverse; /* Changed to row-reverse for RTL (180 د.م ثم 220 د.م) */
            gap: 0.5rem; /* Small gap between prices */
            margin-top: 0.25rem; /* Small margin above prices */
            align-items: baseline; /* Align prices nicely */
            justify-content: flex-end; /* **جديد:** لمحاذاة السعر إلى اليمين داخل حاويته */
        }

        /* Styles specific to large screens (lg and up) - overriding defaults */
        @media (min-width: 1024px) {
            .product-card {
                width: 16rem; /* Significantly reduced width for big cards to make it medium */
                height: auto; /* تأكد من أن الارتفاع auto هنا أيضًا */
                margin-bottom: 1.5rem;
                border-radius: 0px;
                background-color: transparent; /* Use CSS variable for dynamic background */
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Reintroduce shadow for large screens */
                
            }
            .product-card .info-part {
                background-color: transparent; /* على الشاشات الكبيرة، يمكن أن تكون الخلفية شفافة لجزء المعلومات */
                border-radius: 0;
                margin: 0;
                padding: 0.75rem 1.25rem;
                display: flex; /* Make info-part a flex container */
                flex-direction: column; /* Stack contents vertically */
                align-items: flex-start; /* Right align all content for RTL */
            }
            .product-card .image-container {
                height: 288px; /* Original height for large screen images - يمكن الإبقاء عليه إذا كان يعمل جيدًا */
                padding-bottom: 0; /* إلغاء padding-bottom عندما يكون هناك height ثابت */
            }

            .card-load-animation {
                opacity: 0;
                animation: fadeIn 0.8s forwards ease-out;
            }
            .card-load-animation:nth-child(1) { animation-delay: 0.2s; }
            .card-load-animation:nth-child(2) { animation-delay: 0.4s; }
            .card-load-animation:nth-child(3) { animation-delay: 0.6s; }
            .card-load-animation:nth-child(4) { animation-delay: 0.8s; } /* Added delay for the fourth card */

            .product-card .info-part .title-and-price {
                display: flex;
                flex-direction: column; /* Stack title and price vertically */
                align-items: flex-end; /* Right align them */
                width: 100%;
            }
            .product-card .info-part .product-title {
                width: 100%; /* Ensure title takes full width to prevent excessive wrapping */
            }
            .product-card .info-part .product-price-small {
                width: auto; /* Allow width to be determined by content */
                white-space: nowrap; /* Prevent price from wrapping */
                flex-direction: row-reverse; /* For 180 د.م then 220 د.م */
                justify-content: flex-end; /* Align to the right */
                gap: 0.25rem; /* Reduced gap between price and old price */
                margin-top: 0.5rem; /* Space below title */
            }

        }

        /* NEW: Styles for the skeleton effect */
        .image-container.skeleton-active { /* Only apply animation when this class is present */
            background: linear-gradient(-90deg, #e2e8f0 0%, #cbd5e1 50%, #e2e8f0 100%);
            background-size: 400% 400%;
            animation: skeleton-pulse 1.5s ease-in-out infinite;
        }
        
        /* Ensure skeleton images are hidden by default */
        .skeleton-image {
            opacity: 0;
            transition: opacity 0.7s ease-in-out; /* Increased transition duration */
        }
        /* Only show images when they have the 'loaded' class */
        .skeleton-image.loaded {
            opacity: 1;
        }

        /* تعريف متغير CSS للون الأساسي لسهولة الوصول إليه في الستايلات المخصصة */
        :root {
            --primary-color: #f5f5f4; /* stone-200 */
            --secondary-color: #E0E0E0; /* رمادي فاتاتح لجزء المعلومات */
            --card-one-bg: #F5F5F5;
            --card-two-bg: #E0E0E0;
            --card-three-bg: #D3D3D3;
        }
        /* Custom color for category text, not via config */
        .category-gold-beige {
            color: #C8A95A;
        }

        @keyframes pulse {
            0% {
                background-color: #e2e8f0;
            }
            50% {
                background-color: #cbd5e1;
            }
            100% {
                background-color: #e2e8f0;
            }
        }
        @keyframes skeleton-pulse { /* Specific animation for gradient */
            0% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- نظام الصفحات بالـ CSS فقط --- */
        /* Pagination dots visibility for all screens */
        .pagination-dots {
            display: flex; /* Always display for small screens as well now */
            justify-content: center;
            align-items: center;
            gap: 0.75rem; /* Equivalent to gap-3 */
            margin-top: 2rem; /* Equivalent to mt-8 */
        }

        .page-radio { display: none; }
        .page {
            grid-column: 1; grid-row: 1; opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }
        /* Page 1 opacity is 1 by default, no need to define it explicitly here for the default state */
        #page-2 { opacity: 0; }

        #page-radio-2:checked ~ .card-pages-wrapper #page-1 { opacity: 0; }
        #page-radio-2:checked ~ .card-pages-wrapper #page-2 { opacity: 1; }
        #page-radio-1:checked ~ .card-pages-wrapper #page-2 { opacity: 0; }
        #page-radio-1:checked ~ .card-pages-wrapper #page-1 { opacity: 1; }

      .pagination-dot {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 9999px !important; /* or 50% */
    background-color: #D1D5DB;
    cursor: pointer;
    transition: background-color 0.3s ease;
    
    /* ADD THESE TWO LINES */
    flex-shrink: 0;  /* Prevents the dot from being squished into an oval */
    display: block;  /* Ensures width and height are respected */
}
        #page-radio-1:checked ~ .pagination-dots label[for="page-radio-1"],
        #page-radio-2:checked ~ .pagination-dots label[for="page-radio-2"] {
            background-color: #3A3A3A; /* accent color */
        }

        /* Custom styles for your request */
        .product-card .info-part .product-title {
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis; /* Add ellipsis if text exceeds 2 lines */
            height: 3.2em; /* Approximately 2 lines height for default line-height */
            line-height: 1.6; /* Adjust line-height as needed for precise 2-line control */
            white-space: normal; /* Override truncate to allow multiple lines */
            text-align: right; /* Right align for Arabic titles */
        }

        /* Categories and prices should also be right-aligned for Arabic */
        .product-card .info-part p,
        .product-card .info-part .product-price-small {
            text-align: right;
        }


     /* === Mobile: أصغر بطاقات + صور أصغر === */
@media (max-width: 767px) {
    .card-pages-wrapper .page {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem; /* أقل فراغ بين البطاقات */
    }
    .product-card {
        min-height: 220px; /* أصغر من 280px */
        margin-bottom: 0.5rem;
    }
    .product-card .image-container {
        padding-bottom: 110%; /* صورة أطول قليلاً لكن أصغر حجمًا */
    }
    .product-card .info-part {
        padding: 0.5rem 0.35rem;
    }
    .product-card .info-part .product-title {
        font-size: 0.75rem;
        line-height: 1.4;
        height: 2.8em;
    }
    .product-card .info-part .category-text {
        font-size: 0.65rem;
    }
    .product-card .info-part .product-price-small p {
        font-size: 0.8rem;
    }
     .force-center {
        text-align: center !important;
        width: 100% !important;
        display: block !important;
    }
}

/* === Tablet: أصغر بطاقات لكن أنيقة === */
/* === Tablet: 4 منتجات في الصف (مثل Desktop) === */
@media (min-width: 768px) and (max-width: 1023px) {
    .card-pages-wrapper .page {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
    }

    .product-card {
        flex: 0 0 calc(25% - 1rem); /* 4 بطاقات في الصف */
        max-width: calc(25% - 1rem);
        min-height: 300px;
        margin: 0;
    }

    .product-card .image-container {
        padding-bottom: 95%; /* نسبة جيدة */
        height: 0;
    }

    .product-card .info-part {
        padding: 0.6rem 0.5rem;
    }

    .product-card .info-part .product-title {
        font-size: 0.875rem;
        -webkit-line-clamp: 2;
        height: 3em;
    }

    .product-card .info-part .category-text {
        font-size: 0.75rem;
    }

    .product-card .info-part .product-price-small p {
        font-size: 0.9rem;
    }
}

        /* Wishlist icon specific styles */
        .wishlist-icon {
            position: absolute;
            top: 0.5rem; /* 5px from top */
            right: 0.5rem; /* 5px from right */
            z-index: 10;
            background-color: #F5F5F5; /* Silver/Grey with transparency */
            border-radius: 50% !important;
            width: 2rem; /* Small size */
            height: 2rem; /* Small size */
            display: flex;
            justify-content: center;
            align-items: center;
            color: black; /* Heart icon color */
            font-size: 0.875rem; /* Small icon size */
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, opacity 0.3s ease-in-out, visibility 0.3s ease-in-out; /* Added opacity and visibility for smooth hide/show */
            opacity: 1; /* Default visible */
            visibility: visible;
        }
        /* NEW: Hidden wishlist icon */
        .wishlist-icon.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none; /* Disable clicks when hidden */
        }

        /* Wishlist icon hover only for large screens */
        @media (min-width: 1024px) {
            .wishlist-icon:hover {
                background-color: #D3D3D3; /* Slightly darker on hover */
                color: darkmagenta;
            }
        }


        /* Ensure interactivity for pages based on radio selection */
        #page-1 {
            pointer-events: auto; /* Ensure interactivity */
        }
        #page-2 {
            pointer-events: none; /* Disable interaction when hidden */
        }
        #page-radio-1:checked ~ .card-pages-wrapper #page-1 {
            opacity: 1;
            pointer-events: auto;
        }
        #page-radio-1:checked ~ .card-pages-wrapper #page-2 {
            opacity: 0;
            pointer-events: none;
        }
        #page-radio-2:checked ~ .card-pages-wrapper #page-1 {
            opacity: 0;
            pointer-events: none;
        }
        #page-radio-2:checked ~ .card-pages-wrapper #page-2 {
            opacity: 1;
            pointer-events: auto;
        }

        /* Hover image specific styles for large screens */
          /* تأثير انزلاق احترافي (Slide) عند الـ hover - بدون opacity أو 3D */
       /* === Desktop: بطاقات أصغر + تأثير Hover مفعّل 100% === */
@media (min-width: 1024px) {
    .card-pages-wrapper .page {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1.25rem;
    }

    .product-card {
        flex: 0 0 14rem;
        max-width: 14rem;
        min-width: 0;
        height: auto;
        margin: 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 0px;
        overflow: hidden;
        position: relative; /* ضروري للـ hover */
    }

    .product-card .image-container {
        height: 240px;
        padding-bottom: 0;
        position: relative;
        overflow: hidden;
    }

    /* === تأثير الـ Hover (تبديل الصور) === */
    .product-card .main-product-image,
    .product-card .hover-product-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
        transform: translateX(0);
        backface-visibility: hidden;
    }

    .product-card .hover-product-image {
        transform: translateX(100%);
    }

    .product-card:hover .main-product-image {
        transform: translateX(-100%);
    }

    .product-card:hover .hover-product-image {
        transform: translateX(0);
    }

    /* إظهار الصور فقط بعد التحميل */
    .product-card .main-product-image:not(.loaded),
    .product-card .hover-product-image:not(.loaded) {
        visibility: hidden;
    }

    .product-card .main-product-image.loaded,
    .product-card .hover-product-image.loaded {
        visibility: visible;
    }

    /* باقي التنسيقات */
    .product-card .info-part {
        padding: 0.75rem 1rem;
    }

    .product-card .info-part .product-title {
        font-size: 0.875rem;
        -webkit-line-clamp: 2;
        height: 3em;
        line-height: 1.5;
    }

    .product-card .info-part .category-text {
        font-size: 0.75rem;
    }

    .product-card .info-part .product-price-small p {
        font-size: 0.925rem;
    }

    /* أنيميشن الدخول */
    .card-load-animation {
        opacity: 0;
        animation: fadeIn 0.7s forwards ease-out;
    }
    .card-load-animation:nth-child(1) { animation-delay: 0.15s; }
    .card-load-animation:nth-child(2) { animation-delay: 0.3s; }
    .card-load-animation:nth-child(3) { animation-delay: 0.45s; }
    .card-load-animation:nth-child(4) { animation-delay: 0.6s; }
}
        /* Disable hover for small/medium screens */
        @media (max-width: 1023px) {
            .product-card .main-product-image.loaded {
                opacity: 1 !important; /* Always show main image when loaded */
            }
            .product-card .hover-product-image.loaded {
                opacity: 0 !important; /* Always hide hover image when loaded */
            }
        }

        /* RTL for card containers on large screens */
      /* RTL for card containers on large screens - فقط لقسم أفضل المنتجات */
@media (min-width: 1024px) {
    #best-products-section .card-pages-wrapper .page {
        flex-direction: row-reverse;
        display: flex;
        justify-content: center;
        gap: 1.5rem; /* نفس gap-6 */
    }

    /* قسم المنتجات المميزة: نفس التخطيط لكن بدون تأثير قديم */
    #featured-products-section .card-pages-wrapper .page {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 2rem; /* أو 1.5rem حسب رغبتك - لكن متسق */
        flex-direction: row; /* من اليسار لليمين (طبيعي) أو row-reverse حسب التصميم */
    }
}

        /* Styles for section lazy loading (unchanged) */
        .lazy-load-section {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .lazy-load-section.is-visible {
            opacity: 1;
            transform: translateY(0);
        }



/* تنسيق حالة التفعيل للقلب */
.wishlist-icon .icon-filled {
    display: none; /* مخفي افتراضياً */
}

.wishlist-icon.active .icon-empty {
    display: none; /* إخفاء الفارغ عند التفعيل */
}

.wishlist-icon.active .icon-filled {
    display: block; /* إظهار الممتلئ */
    color: #ff4b4b !important;
    animation: heartBeat 0.3s ease-in-out;
}

/* حركة نبض القلب */
@keyframes heartBeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* تنسيق الفتات المتطاير (Particles) */
.particle {
    position: fixed; /* لتظهر فوق كل شيء */
    width: 6px;
    height: 6px;
    border-radius: 50%;
    pointer-events: none; /* لكي لا تمنع الضغط */
    z-index: 9999;
    opacity: 0;
}
        
    </style>

<!-- Existing "أفضل المنتجات" section -->
<!-- ID remains best-products-section to match the exact CSS provided -->
<section id="best-products-section" class="relative overflow-hidden bg-section-bg-transparent py-8 lazy-load-section" data-first-load="true">
    <div class="relative max-w-7xl mx-auto mt-8 px-4 sm:px-8 z-20">
        <div class="flex justify-center items-center mb-10 mx-auto">
          <h2 class="text-4xl font-bold text-text-dark">منتجات ذات صلة</h2>
        </div>

        <input type="radio" name="page" id="page-radio-1" class="page-radio" checked>
        <input type="radio" name="page" id="page-radio-2" class="page-radio">

        <div class="card-pages-wrapper grid grid-cols-1 grid-rows-1">
            <div id="page-1" class="page grid grid-cols-2 lg:flex justify-center gap-4 lg:gap-6">
                <?php
                if (!empty($related_page1)) {
                    foreach ($related_page1 as $product) {
                        renderProductCard($product);
                    }
                } else {
   echo '
           <div class="col-span-full w-full flex justify-center items-center py-10">
    <p class="text-center text-xl lg:text-2xl font-bold text-gray-400 w-full">
        نأسف لعدم توفر أي منتجات ذات صلة حالياً.
    </p>
</div>';
                        }
                ?>
            </div>

            <div id="page-2" class="page grid grid-cols-2 lg:flex justify-center gap-4 lg:gap-6">
                <?php
                if (!empty($related_page2)) {
                    foreach ($related_page2 as $product) {
                        renderProductCard($product);
                    }
                } else {
                     // Empty state for page 2 can be empty or message, typically empty if page 1 has items
                }
                ?>
            </div>
        </div>
        
        <?php if (!empty($related_page2)): ?>
        <div class="pagination-dots flex justify-center items-center gap-3 mt-8">
            <label for="page-radio-1" class="pagination-dot"></label>
            <label for="page-radio-2" class="pagination-dot"></label>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const MIN_SKELETON_DISPLAY_TIME = 800; // وقت أدنى لعرض الـ Skeleton (حتى لو حملت الصور بسرعة)

        // === دالة تحميل الصور مع Skeleton + أنيميشن (مرة واحدة فقط) ===
        function activateSectionPermanently(section) {
            // إذا تم التحميل من قبل → لا نفعل شيئًا (حتى لو رجعت)
            if (section.classList.contains('permanently-loaded')) {
                return;
            }

            section.classList.add('permanently-loaded', 'is-visible');

            const imageContainers = section.querySelectorAll('.image-container');
            const wishlistIcons = section.querySelectorAll('.wishlist-icon');
            const cards = section.querySelectorAll('.card-load-animation');

            let imagesLoaded = 0;
            const totalImages = imageContainers.length * 2; // main + hover
            let minTimePassed = false;

            // تفعيل Skeleton
            imageContainers.forEach(container => {
                container.classList.remove('skeleton-pending');
                container.classList.add('skeleton-active');
            });

            // إخفاء أيقونة القلب أولاً
            wishlistIcons.forEach(icon => icon.classList.add('is-hidden'));

            // وظيفة عند تحميل أي صورة
            const imageLoaded = () => {
                imagesLoaded++;
                if (imagesLoaded >= totalImages && minTimePassed) {
                    finishLoading();
                }
            };

            // إنهاء التحميل
            const finishLoading = () => {
                imageContainers.forEach(container => {
                    container.classList.remove('skeleton-active');
                    const mainImg = container.querySelector('.main-product-image');
                    const hoverImg = container.querySelector('.hover-product-image');
                    mainImg.classList.add('loaded');
                    hoverImg.classList.add('loaded');
                });
                wishlistIcons.forEach(icon => icon.classList.remove('is-hidden'));

                // أنيميشن البطاقات (مرة واحدة)
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 200);
                });
            };

            // تحميل الصور
            imageContainers.forEach(container => {
                const mainSrc = container.getAttribute('data-main-image-src');
                const hoverSrc = container.getAttribute('data-hover-image-src');
                const mainImg = container.querySelector('.main-product-image');
                const hoverImg = container.querySelector('.hover-product-image');

                const loadImg = (src, img) => {
                    const temp = new Image();
                    temp.onload = temp.onerror = () => {
                        img.src = src;
                        imageLoaded();
                    };
                    temp.src = src;
                };

                loadImg(mainSrc, mainImg);
                loadImg(hoverSrc, hoverImg);
            });

            // ضمان عرض Skeleton لمدة لا تقل عن 800ms
            setTimeout(() => {
                minTimePassed = true;
                if (imagesLoaded >= totalImages) {
                    finishLoading();
                }
            }, MIN_SKELETON_DISPLAY_TIME);
        }

        // === تبديل الصفحات تلقائيًا ===
        let currentPage = 1;
        const totalPages = 2;
        // التحقق من وجود الصفحة الثانية قبل تشغيل التبديل التلقائي
        const page2 = document.getElementById('page-2');
        if (page2 && page2.children.length > 0 && page2.innerHTML.trim() !== '') {
            function autoChangePage() {
                currentPage = (currentPage % totalPages) + 1;
                const radio = document.getElementById(`page-radio-${currentPage}`);
                if(radio) radio.checked = true;
            }
            setInterval(autoChangePage, 12000);
        }

        // === Intersection Observer: تحميل عند أول ظهور فقط ===
        const lazyLoadSections = document.querySelectorAll('.lazy-load-section');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    activateSectionPermanently(entry.target);
                    observer.unobserve(entry.target); // لا نراقب بعدها أبدًا
                }
            });
        }, {
            rootMargin: '0px 0px -10% 0px',
            threshold: 0.1
        });

        lazyLoadSections.forEach(section => {
            observer.observe(section);
        });
    });


   function toggleWishlist(btn, e) {
    e.preventDefault();
    e.stopPropagation();

    const productId = btn.getAttribute('data-product-id');

    fetch('wishlist-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'need_login') {
            window.location.href = 'register.php';
            return;
        }
        
        if (data.status === 'success') {
            // 1. تحديث شكل القلب
            if (data.action === 'added') {
                btn.classList.add('active');
                if(typeof createParticles === 'function') createParticles(e.clientX, e.clientY);
                openWishlist();
            } else {
                btn.classList.remove('active');
            }

            // 2. تحديث الرقم في الهيدر (Badge)
            const badge = document.querySelector('.wishlist-badge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            }

            // 3. تحديث محتوى السايدبار (إعادة تحميل الجزء فقط)
            // يفضل عمل دالة هنا تجلب HTML الكروت وتضعها في container
            refreshWishlistSidebar(); 
        }
    });
}

// دالة لتحديث محتوى السايدبار بدون ريفريش كامل
function refreshWishlistSidebar() {
    // يمكنك هنا عمل fetch لملف يعيد فقط HTML المنتجات المضافة
    // حالياً لإبقاء الكود نظيفاً، سيظهر التحديث عند أول ريفريش أو يمكنك إضافة fetch بسيط هنا.
}
// دالة إنشاء الفتات (كما هي في كودك المميز)
function createParticles(x, y) {
    const colors = ['#ff4b4b', '#C8A95A', '#FFD700', '#ffb6b6'];
    const particleCount = 12;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        document.body.appendChild(particle);

        const color = colors[Math.floor(Math.random() * colors.length)];
        particle.style.backgroundColor = color;
        
        particle.style.left = x + 'px';
        particle.style.top = y + 'px';
        particle.style.opacity = '1';

        const destinationX = (Math.random() - 0.5) * 100;
        const destinationY = (Math.random() - 0.5) * 100;

        const animation = particle.animate([
            { transform: `translate(0, 0)`, opacity: 1 },
            { transform: `translate(${destinationX}px, ${destinationY}px)`, opacity: 0 }
        ], {
            duration: 600 + Math.random() * 200,
            easing: 'cubic-bezier(0, .9, .57, 1)',
            fill: 'forwards'
        });

        animation.onfinish = () => {
            particle.remove();
        };
    }
}

</script>
