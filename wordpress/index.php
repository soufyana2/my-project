<?php
// ... بقية الكود الخاص بك تحته ...
ob_start(); // 1. هذا يجب أن يكون أول سطر


// 4. الآن نستدعي الملفات الأخرى
include("db.php");
require_once 'functions.php';
// require_once 'headers-policy.php';
require_once 'vendor/autoload.php';

require_once 'logger_setup.php'; // إذا كان لديك هذا الملف
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
// 2. إعدادات الكوكيز (مطابقة لملف register.php)
session_set_cookie_params([
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax' // استخدام Lax هو الأفضل للتنقل
]);


// 5. التحقق من "تذكرني"
check_remember_me($pdo);
// جلب قائمة بمعرفات المنتجات التي أضافها المستخدم للمفضلة حالياً
// في أعلى ملفك الرئيسي
$user_wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
// 7. إنشاء CSRF للزوار
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// ... بقية الكود (Autoload وما بعده) يبقى كما هو ...
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    // ...
    http_response_code(500);
    exit("A technical error occurred. Please try again later. (Autoload not found)");
}
require __DIR__ . '/vendor/autoload.php';

// استيراد الفئات المطلوبة بعد autoload
use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;
// أضف هذين السطرين
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// NEW: Caching Configuration
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_LIFETIME', 3600); // 1 hour in seconds (for production, consider 6-24 hours)

// NEW: Function to get data from cache
function getFromCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile) && (filemtime($cacheFile) + CACHE_LIFETIME > time())) {
        // Log for debugging: Cache hit
        // error_log("Cache HIT for key: " . $key);
        return json_decode(file_get_contents($cacheFile), true); // Return as associative array
    }
    // Log for debugging: Cache miss or expired
    // error_log("Cache MISS/EXPIRED for key: " . $key);
    return null;
}

// NEW: Function to save data to cache
function saveToCache($key, $data) {
        global $log; // استدعاء Monolog

    if (!is_dir(CACHE_DIR)) {
        // Attempt to create cache directory. Log error if fails.
        if (!mkdir(CACHE_DIR, 0775, true)) { // 0775 allows owner/group full access, others read/execute
           // تسجيل خطأ حرج: فشل إنشاء المجلد
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

    // Attempt to write to file. Log error if fails.
    if (file_put_contents($cacheFile, $json_data) === false) {
       $log->error("Failed to write to cache file: " . $cacheFile);
        return false;
    }
    // Log for debugging: Cache saved
    // error_log("Cache SAVED for key: " . $key);
    return true; // Indicate success
}

// NEW: Function to clear specific cache entry
function clearCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile)) {
        // Log for debugging
        // error_log("Cache CLEARED for key: " . $key);
        unlink($cacheFile);
    }
}

// NEW: Function to clear all cache (use with caution)
function clearAllCache() {
    $files = glob(CACHE_DIR . '*.json');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    // error_log("All cache cleared.");
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
// إعداد Monolog (ضعه قبل تعريف الدوال)
// تعريف مجلد السجلات
define('LOGS_DIR', __DIR__ . '/logs/');

// إنشاء المجلد إذا لم يكن موجوداً
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0775, true);
}

// إعداد Monolog
$log = new Logger('wc_store');

// 1. سجل الأخطاء (داخل مجلد logs)
$log->pushHandler(new StreamHandler(LOGS_DIR . 'store_errors.log', Logger::WARNING));

// 2. سجل الزوار (داخل مجلد logs)
$log->pushHandler(new StreamHandler(LOGS_DIR . 'visitors.log', Logger::INFO));

// تسجيل زيارة الصفحة
$log->info('Homepage Visit', [
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'device'  => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct Link'
]);
// WooCommerce API Credentials
$consumer_key = $_ENV['consumer_key'] ?? null;
$consumer_secret = $_ENV['secret_key'] ?? null;
$store_url =  $_ENV['wordpress_url'] ?? null;

// Validate essential environment variables
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
        'verify_ssl' => false, // Set to false for localhost (consider true for production with valid SSL)
        'timeout' => 30,       // زيادة المهلة إلى 30 ثانية
        'connect_timeout' => 10, // مهلة اتصال أولي إلى 10 ثوانٍ
    ]
);

// Function to fetch products with specific parameters
function fetchProducts($woocommerce, $category, $per_page, $offset = 0) {
    $cacheKey = 'products_' . md5($category . '_' . $per_page . '_' . $offset);
    $cachedData = getFromCache($cacheKey);

    if ($cachedData !== null) {
        return $cachedData; // Return from cache (as associative array)
    }

    try {
        $category_id = getCategoryId($woocommerce, $category); // This function now also uses cache
        if (!$category_id) {
            error_log('Category not found: ' . $category);
            return [];
        }

        $params = [
            'category' => $category_id,
            'per_page' => $per_page,
            'offset' => $offset,
            'status' => 'publish',
            'order' => 'desc',
            'orderby' => 'date'
        ];
        $products = $woocommerce->get('products', $params);
        // Convert objects to associative arrays before saving to cache for consistency
        $products_as_array = json_decode(json_encode($products), true);
        saveToCache($cacheKey, $products_as_array);
        return $products_as_array; // Return as associative array
    } catch (HttpClientException $e) {
          global $log; // استدعاء Monolog
        // تسجيل خطأ الاتصال بالـ API مع ذكر الفئة والرسالة
        $log->error('WooCommerce API Error (HTTP) fetching ' . $category . ': ' . $e->getMessage());
        return [];
    } catch (Exception $e) {
         global $log; // استدعاء Monolog
        // تسجيل الأخطاء العامة
        $log->critical('General Error fetching ' . $category . ': ' . $e->getMessage());
        return [];
    }
}

// Function to get category ID by name
function getCategoryId($woocommerce, $category_name) {
    $cacheKey = 'category_id_' . md5($category_name);
    $cachedData = getFromCache($cacheKey);

    if ($cachedData !== null) {
        return $cachedData['id']; // Return from cache (as associative array)
    }

    try {
        $categories = $woocommerce->get('products/categories', ['search' => $category_name, 'per_page' => 1]);
        if (!empty($categories) && isset($categories[0]->id)) {
            $categoryId = $categories[0]->id;
            saveToCache($cacheKey, ['id' => $categoryId]); // Save to cache (as associative array)
            return $categoryId;
        }
        return null;
    } catch (HttpClientException $e) {
        global $log;
        $log->warning('Could not retrieve ID for category: ' . $category_name . '. Error: ' . $e->getMessage());
        return null;
    } catch (Exception $e) {
         global $log;
        $log->warning('Could not retrieve ID for category: ' . $category_name . '. Error: ' . $e->getMessage());
        return null;
    }
}

// Function to fetch featured products
function fetchFeaturedProducts($woocommerce, $per_page) {
    $cacheKey = 'featured_products_' . md5($per_page);
    $cachedData = getFromCache($cacheKey);

    if ($cachedData !== null) {
        return $cachedData; // Return from cache (as associative array)
    }

    try {
        $params = [
            'featured' => true,
            'per_page' => $per_page,
            'status' => 'publish',
            'order' => 'desc',
            'orderby' => 'date'
        ];
        $products = $woocommerce->get('products', $params);
        // Convert objects to associative arrays before saving to cache for consistency
        $products_as_array = json_decode(json_encode($products), true);
        saveToCache($cacheKey, $products_as_array);
        return $products_as_array; // Return as associative array
    } catch (HttpClientException $e) {
         global $log; // استدعاء Monolog
        // تسجيل خطأ الاتصال بالـ API مع ذكر الفئة والرسالة
$log->error('WooCommerce API Error (HTTP) fetching Featured Products: ' . $e->getMessage());
        return [];
    } catch (Exception $e) {
          global $log; // استدعاء Monolog
        // تسجيل الأخطاء العامة
        $log->critical('General Error fetching '  . ': ' . $e->getMessage());
        return [];
    }
}

// Fetch products for "أفضل المنتجات" section - Page 1 (Parfums)
$parfums_products = fetchProducts($woocommerce, 'عطور', 4);

// Fetch products for "أفضل المنتجات" section - Page 2 (Watches)
$watches_products = fetchProducts($woocommerce, 'ساعات', 4);

// Fetch products for "منتجات مميزة" section (8 mixed featured products)
$featured_products = fetchFeaturedProducts($woocommerce, 8);


// Helper function to render a product card

// Function to generate skeleton cards
function renderSkeletonCards($count) {
    for ($i = 0; $i < $count; $i++) {
        renderProductCard(null, true); // Pass true to indicate it's a skeleton
    }
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. العنوان المحسن: يحتوي على اسم المتجر + الكلمات المفتاحية الرئيسية -->
    <title>عبد الوهاب للعطور والإكسسوارات | تسوق أفضل العطور والساعات في المغرب</title>
    
    <!-- 2. وصف دقيق وجذاب يحتوي كلمات بحثية -->
    <meta name="description" content="تسوق أونلاين من عبد الوهاب للإكسسوارات والعطور. اكتشف تشكيلة واسعة من العطور الفاخرة، الساعات الأنيقة، والهدايا المميزة بأفضل الأسعار في المغرب. توصيل سريع ودفع عند الاستلام.">
    
    <!-- 3. الكلمات المفتاحية (اختياري لكن مفيد لمحركات البحث الأخرى) -->
    <meta name="keywords" content="عطور, ساعات, إكسسوارات, تسوق أونلاين, المغرب, هدايا, عبد الوهاب">
    
    <!-- 4. Canonical URL (مهم جداً لمنع تكرار المحتوى) -->
    <!-- استبدل الرابط أدناه برابط موقعك الحقيقي -->
    <link rel="canonical" href="https://your-domain.com/index.php" />

    <!-- 5. Favicon -->
    <link rel="icon" type="image/png" href="images/lgicon.png">

    <!-- 6. Open Graph (للظهور باحترافية على فيسبوك، واتساب، وتويتر) -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="عبد الوهاب للعطور والإكسسوارات - فخامة وأناقة" />
    <meta property="og:description" content="اكتشف مجموعتنا الحصرية من العطور والساعات. جودة عالية وأسعار تنافسية." />
    <meta property="og:url" content="https://your-domain.com/" />
    <meta property="og:site_name" content="Bdolwahab Store" />
    <meta property="og:image" content="https://your-domain.com/images/lgicon.png" /> <!-- ضع رابط صورة شعار المتجر أو صورة دعائية -->

    <!-- 7. Schema Markup (البيانات المنظمة - سر الاحترافية) -->
    <!-- هذا الكود يخبر جوجل أن هذا "متجر" وليس مجرد مدونة -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Store",
      "name": "عبد الوهاب للإكسسوارات والعطور",
      "url": "https://your-domain.com/",
      "logo": "https://your-domain.com/images/lgicon.png",
      "description": "متجر متخصص في بيع العطور والساعات والإكسسوارات الفاخرة في المغرب.",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "MA"
      },
      "priceRange": "$$"
    }
    </script>

    <!-- Preconnect & Fonts (كما هي في كودك الأصلي - ممتازة للأداء) -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;500&family=Montserrat:wght@400;700&family=Lato:wght@400;700&family=Cairo:wght@400;600;700&family=Tajawal:wght@300;400;500;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;500&family=Montserrat:wght@400;700&family=Lato:wght@400;700&family=Cairo:wght@400;600;700&family=Tajawal:wght@300;400;500;700;800&display=swap">
    </noscript>

    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"></noscript>
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- هنا تضع السكربت والستايل الخاص بك كما هو موجود سابقاً -->
<?php
$activeCategory = 'index'; // عرفنا الصفحة
include 'header.php';
?>    
    

  <script>

   tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#f5f5f4',      // لون خلفية البطاقات (stone-200)
                        'secondary': '#E0E0E0',    // لون خلفية جزء المعلومات (رمادي فاتاتح)
                        'accent': '#3A3A3A',        // لون فحمي داكن وراقي للسعر
                        'text-dark': '#212121',     // أسود ناعم للنصوص الرئيسية
                        'text-light': '#5A5A5A',    // رمادي معتدل للنصوص الثانوية
                        // Changed big card background colors to more professional tones
                        'card-one-bg': '#F5F5F5',   // Light Grayish White
                        'card-two-bg': '#E0E0E0',   // Light Gray
                        'card-three-bg': '#D3D3D3', // Medium Light Gray
                        'section-bg-light-grey': '#F9F9F9', // Light grey for the card section background
                    },
                     fontFamily: {
                        sans: ['Roboto', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                        mono: ['Montserrat', 'sans-serif'],
                        professional: ['Lato', 'sans-serif'], // Added a professional font for categories
                        arabic: ['Cairo', 'sans-serif'], // Added Cairo for Arabic text
                    }
                }
            }
        }
    </script>
        <script src="https://cdn.tailwindcss.com"></script>

    <style>
            * {
        -webkit-overflow-scrolling: touch;
    }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #ffffff; 
            margin: 0;
            overflow-x: none;
            scroll-behavior: smooth;
        }
     
        h1, h2, h3, h4, h5, h6 { /* جعل Playfair Display هو الخط الأساسي للعناوين */
            font-family: 'Playfair Display', serif;
        }

        /* Changed to use the new professional font from tailwind config */
        .category-text {
            font-family: 'Lato', sans-serif; /* Professional font for category */
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
/* توحيد ستايل السعر ليطابق المنتجات ذات الصلة */
.product-card .info-part .product-price-small .price-value {
    color: #3A3A3A !important;      /* لون فحمي داكن احترافي */
    font-weight: 800 !important;     /* خط عريض جداً */
    font-family: 'Cairo', sans-serif !important;
    font-size: 0.9rem !important;    /* حجم متناسق */
}

/* ستايل السعر القديم (قبل الخصم) ليكون متناسقاً أيضاً */
.product-card .info-part .product-price-small .old-price {
    color: #9CA3AF !important;      /* رمادي فاتح */
    text-decoration: line-through !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    margin-right: 5px !important;    /* مسافة بسيطة بين السعرين */
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
            width: 0.75rem; height: 0.75rem; border-radius: 9999px;
            background-color: #D1D5DB; cursor: pointer; transition: background-color 0.3s ease;
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
   
    .force-center {
        text-align: center !important;
        width: 100% !important;
        display: block !important;
        margin-left: auto !important;  /* يضمن التوسيط الأفقي */
        margin-right: auto !important; /* يضمن التوسيط الأفقي */
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
            border-radius: 50%;
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
</head>
<body dir="rtl"> <!-- Added dir="rtl" for overall right-to-left flow -->
 <?php include 'hero.html';?>
 <?php include 'categurie.html';?>
  <?php include 'ads1.html';?>

<main>
<!-- Existing "أفضل المنتجات" section -->
<section id="best-products-section" class="relative overflow-hidden bg-section-bg-transparent py-8 lazy-load-section" data-first-load="true">
    <div class="relative max-w-7xl mx-auto mt-8 px-4 sm:px-8 z-20">
        <div class="flex justify-center items-center mb-10 mx-auto">
          <h2 class="text-4xl font-bold text-text-dark">أفضل المنتجات</h2>
        </div>

        <input type="radio" name="page" id="page-radio-1" class="page-radio" checked>
        <input type="radio" name="page" id="page-radio-2" class="page-radio">

        <div class="card-pages-wrapper grid grid-cols-1 grid-rows-1">
            <div id="page-1" class="page grid grid-cols-2 lg:flex justify-center gap-4 lg:gap-6">
                <?php
                if (!empty($parfums_products)) {
                    foreach ($parfums_products as $product) {
                        renderProductCard($product);
                    }
                } else {
echo '
    <!-- أضفنا col-span-2 هنا -->
    <div class="w-full col-span-2 flex justify-center items-center py-10">
        <p class="force-center text-center text-xl lg:text-2xl font-semibold text-gray-600 leading-relaxed">
            نأسف لعدم توفر أي منتجات حالياً في هذا القسم.  
        </p>
    </div>';
                        }
                ?>
            </div>

            <div id="page-2" class="page grid grid-cols-2 lg:flex justify-center gap-4 lg:gap-6">
                <?php
                if (!empty($watches_products)) {
                    foreach ($watches_products as $product) {
                        renderProductCard($product);
                    }
                } else {
echo '
    <!-- أضفنا col-span-2 هنا -->
    <div class="w-full col-span-2 flex justify-center items-center py-10">
        <p class="force-center text-center text-xl lg:text-2xl font-semibold text-gray-600 leading-relaxed">
            نأسف لعدم توفر أي منتجات حالياً في هذا القسم.  
        </p>
    </div>';
                        }
                ?>
            </div>
        </div>
        <div class="pagination-dots flex justify-center items-center gap-3 mt-8">
            <label for="page-radio-1" class="pagination-dot"></label>
            <label for="page-radio-2" class="pagination-dot"></label>
        </div>
    </div>
</section>
  <?php include 'ads2.html';?>

 <?php include 'history.html';?>
<!-- NEW "منتجات مميزة" (Featured Products) section -->
<section id="featured-products-section" class="relative overflow-hidden bg-transparent py-8 lazy-load-section" data-first-load="true">
<div class="relative max-w-7xl mx-auto mt-8 px-4 sm:px-8 z-20">
            <div class="flex justify-center items-center mb-10 mx-auto">
          <h2 class="text-4xl font-bold text-text-dark">منتجات مميزة</h2>
        </div>

<!-- بهذا (نفس منطق Top Products) -->
<div class="card-pages-wrapper grid grid-cols-1 grid-rows-1">
    <div class="page grid grid-cols-2 lg:flex justify-center gap-4 lg:gap-6">
                   <?php
             if (!empty($featured_products)) {
                 foreach ($featured_products as $product) {
                     renderProductCard($product);
                 }
             } else {
echo '
    <!-- أضفنا col-span-2 هنا -->
    <div class="w-full col-span-2 flex justify-center items-center py-10">
        <p class="force-center text-center text-xl lg:text-2xl font-semibold text-gray-600 leading-relaxed">
            نأسف لعدم توفر أي منتجات حالياً في هذا القسم.  
        </p>
    </div>';
                     }
             ?>
    </div>
</div>    </div>
</section>
 <?php include 'subscribe.html';?>
    <?php include 'ads3.html';?>

   <?php include 'reviews.html';?>

  <?php include 'abou-us.html';?>
    <?php include 'footer.php';?>
</main>
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
        function autoChangePage() {
            currentPage = (currentPage % totalPages) + 1;
            document.getElementById(`page-radio-${currentPage}`).checked = true;
        }
        setInterval(autoChangePage, 12000);

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


  

window.toggleWishlist = function(btn, e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }

    // فحص تسجيل الدخول فوراً قبل عمل أي شيء
    // المتغير $isLoggedIn معرف مسبقاً في أعلى ملف header.php
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    if (!isLoggedIn) {
        // إذا كان المستخدم غير مسجل، نأخذه فوراً لصفحة التسجيل
        window.location.href = 'register.php';
        return; // نتوقف هنا ولا نفتح السايدبار
    }

    const productId = btn.getAttribute('data-product-id');
    if (!productId) return;

    // بما أنه وصل هنا فهو مسجل دخول.. الآن نفتح القائمة الجانبية
    if (typeof openWishlist === "function") openWishlist();

    // تحديث الحالة البصرية للقلب فوراً (أنيميشن)
    const isCurrentlyActive = btn.classList.contains('active');
    const allHearts = document.querySelectorAll(`.wishlist-icon[data-product-id="${productId}"], .main-product-wishlist[data-product-id="${productId}"]`);    
    allHearts.forEach(heart => {
        if (isCurrentlyActive) {
            heart.classList.remove('active');
        } else {
            heart.classList.add('active');
            // إذا كنت في صفحة المنتج، شغل الفتات
            if (typeof createParticles === 'function' && e) createParticles(e.clientX, e.clientY);
        }
    });

    // إرسال البيانات للسيرفر (URLSearchParams يبقى هنا لأنه ضروري للإرسال)
    const params = new URLSearchParams();
    params.append('product_id', productId);

    fetch('wishlist-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.status === 'added' || data.status === 'removed') {
            // تحديث العداد في الهيدر
            document.querySelectorAll('.wishlist-badge').forEach(badge => {
                badge.innerText = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            });

            // تحديث محتوى القائمة الجانبية
            if (typeof updateWishlistSidebar === "function") {
                updateWishlistSidebar();
            }
        }
    })
    .catch(err => console.error('Error:', err));
};
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
</body>
</html>