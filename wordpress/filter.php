<?php
ob_start();
require_once 'logger_setup.php'; 
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
include("db.php");
require_once 'functions.php';

require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;

try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
    $dotenv->load();
} catch (Exception $e) { exit("Error loading API keys"); }

$woocommerce = new Client(
    $_ENV['wordpress_url'],
    $_ENV['consumer_key'],
    $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 40]
);

// --- 1. تعريف الدالة المفقودة (هذا سبب الخطأ 500) ---
if (!function_exists('getCachedData')) {
    function getCachedData($wc, $endpoint, $key_suffix) {
        $cacheFile = __DIR__ . '/cache/data_' . $key_suffix . '.json';
        if(!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            return json_decode(file_get_contents($cacheFile), true);
        }
        try {
            $data = $wc->get($endpoint, ['per_page' => 100, 'hide_empty' => true]);
            file_put_contents($cacheFile, json_encode($data));
            return json_decode(json_encode($data), true);
        } catch (Exception $e) { return []; }
    }
}

// --- 2. جلب التصنيفات أولاً ---
$categories = getCachedData($woocommerce, 'products/categories', 'cats');

// --- 3. معالجة الرابط (دعم كلا المسميين category و categurie لضمان عمل الهيدر والئيسية) ---
$raw_url = $_GET['category'] ?? $_GET['categurie'] ?? ''; 
$target_name = trim(urldecode($raw_url)); 

$selected_id = null;
$found_in_list = false;

if (!empty($target_name) && is_array($categories)) {
    foreach ($categories as $cat) {
        // المقارنة بالاسم العربي أو الـ slug (حساس جداً للعربي)
        if ($cat['name'] == $target_name || $cat['slug'] == $target_name) {
            $selected_id = $cat['id'];
            $found_in_list = true;
            break;
        }
    }
}

// تحديد حالة "الكل"
$all_checked_status = ($found_in_list) ? '' : 'checked';
// --- القوائم الثابتة ---
$static_colors = [
    ['name' => 'أحمر', 'hex' => '#E53935', 'slug' => 'red'],
    ['name' => 'أسود', 'hex' => '#000000', 'slug' => 'black'],
    ['name' => 'أبيض', 'hex' => '#FFFFFF', 'slug' => 'white'],
    ['name' => 'أزرق', 'hex' => '#1E88E5', 'slug' => 'blue'],
    ['name' => 'أخضر', 'hex' => '#43A047', 'slug' => 'green'],
    ['name' => 'ذهبي', 'hex' => '#FFD700', 'slug' => 'gold'],
    ['name' => 'فضي', 'hex' => '#C0C0C0', 'silver'],
    ['name' => 'بيج', 'hex' => '#F5F5DC', 'slug' => 'beige'],
    ['name' => 'بني', 'hex' => '#795548', 'slug' => 'brown'],
];
$static_sizes = [
    ['name' => 'XS', 'slug' => 'xs'], ['name' => 'S', 'slug' => 's'],
    ['name' => 'M', 'slug' => 'm'], ['name' => 'L', 'slug' => 'l'],
    ['name' => 'XL', 'slug' => 'xl'], ['name' => 'XXL', 'slug' => 'xxl'],
    ['name' => 'Free Size', 'slug' => 'free-size'],
];
$static_volumes = [
    ['name' => '30 مل', 'slug' => '30ml'], ['name' => '50 مل', 'slug' => '50ml'],
    ['name' => '75 مل', 'slug' => '75ml'], ['name' => '100 مل', 'slug' => '100ml'],
    ['name' => '150 مل', 'slug' => '150ml'], ['name' => '200 مل', 'slug' => '200ml'],
];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <!-- ... (نفس الـ HEAD تماماً) ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المنتجات | تصفية وتسوق</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700&family=Playfair+Display:wght@700&family=Roboto:wght@400;500&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
       tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#f5f5f4', 'accent': '#3A3A3A',
                        'gold-main': '#C8A95A', 'text-dark': '#212121'
                    },
                    fontFamily: {
                        sans: ['Roboto', 'sans-serif'],
                        arabic: ['Cairo', 'sans-serif'],
                        body: ['Tajawal', 'sans-serif']
                    }
                }
            }
        }
    </script>

  <style>
    /* ... (جميع الستايلات السابقة كما هي) ... */
    * { -webkit-overflow-scrolling: touch; box-sizing: border-box; }
    body { font-family: 'Cairo', sans-serif; background-color: #ffffff; margin: 0; overflow-x: hidden; }
    .arabic-font { font-family: 'Cairo', sans-serif; }
    .category-gold-beige { color: #C8A95A !important; }

    .breadcrumb-header {
        width: 100%;
        background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764797982/da03ca5e2169685ac4867c812a4f0d4c_g8wpob.jpg') center/cover no-repeat;
        padding: 40px 0; color: #fff;
        margin-bottom: 20px;
        margin-top: 100px;
    }
    .breadcrumb-inner {
        max-width: 1200px; margin: 0 auto; padding: 0 20px;
        display: flex; align-items: center; justify-content: flex-start;
        font-family: 'Tajawal', sans-serif; font-weight: 700;
    }
    .breadcrumb-links a { color: #ddd; text-decoration: none; margin-left: 10px; }
    .breadcrumb-current { color: #C8A95A; }
    
    @media (min-width: 1024px) {
        .wishlist-icon:hover {
            color: #ff4b4b !important;
            border-color: #ff4b4b !important;
            background-color: #F5F5F5;
        }
    }
    @media (min-width: 764px) and (max-width: 1023px) {
        .breadcrumb-header { margin-top: 80px; padding: 30px 0; }
        .wishlist-icon:hover { color: #ff4b4b !important; border-color: #ff4b4b !important; }
    }
    @media (max-width: 763px) {
        .breadcrumb-header { margin-top: 60px; padding: 20px 0; } 
    }

    .product-card {
        background-color: transparent; box-shadow: none !important;
        margin-bottom: 1rem; border-radius: 0;
        display: flex; flex-direction: column; overflow: hidden; 
        height: auto; min-height: 250px;
    }

    /* === ANIMATION START === */
    /* هذا هو الأنيميشن الجديد لظهور المنتجات */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-up {
        animation: fadeUp 0.6s ease-out forwards;
    }
    /* === ANIMATION END === */

   /* --- تأثير Skeleton الموحد من الصفحة الرئيسية --- */
.image-container.skeleton-active {
    background: linear-gradient(-90deg, #e2e8f0 0%, #cbd5e1 50%, #e2e8f0 100%);
    background-size: 400% 400%;
    animation: skeleton-pulse 1.5s ease-in-out infinite;
    position: relative;
    overflow: hidden;
}

@keyframes skeleton-pulse {
    0% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* إخفاء الصور حتى يتم تحميلها بالكامل */
.skeleton-image {
    opacity: 0;
    transition: opacity 0.7s ease-in-out;
}


    .skeleton-image.loaded {
    opacity: 1 !important;
}

    .product-card .main-product-image, .product-card .hover-product-image {
        opacity: 0; transition: opacity 0.5s ease-in-out; z-index: 10;
    }
    .product-card .main-product-image.loaded, .product-card .hover-product-image.loaded { opacity: 1; }

    .product-card .image-container {
        width: 100%; padding-bottom: 120%; height: 0;
        position: relative; overflow: hidden !important; flex-shrink: 0; border-radius: 0;
    }
    .product-card .image-container img {
        object-fit: cover; width: 100%; height: 100%; position: absolute; inset: 0;
        transition: transform 0.3s ease-in-out; z-index: 1;
    }
    .product-card .hover-product-image { transform: translateX(105%); }

    .product-card .info-part {
        background-color: transparent; padding: 0.5rem 0.25rem;
        text-align: right; display: flex; flex-direction: column;
        align-items: flex-start; flex-grow: 1; justify-content: flex-start;
    }
    .product-card .info-part .product-title { color: #212121; }
    
    .wishlist-icon {
        position: absolute; top: 5px; right: 5px; z-index: 20 !important; 
        background-color: #fff; border-radius: 50%; width: 32px; height: 32px;
        display: flex !important; justify-content: center; align-items: center;
        color: black; font-size: 0.9rem; cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1); opacity: 1 !important; visibility: visible !important;
    }
    .image-container.skeleton-pending .wishlist-icon, .image-container.skeleton-active .wishlist-icon { display: none !important; }
    .wishlist-icon.active .icon-filled { display: block; color: #ff4b4b !important; animation: heartBeat 0.3s ease-in-out; }
    .wishlist-icon .icon-filled { display: none; }
    .wishlist-icon.active .icon-empty { display: none; }

    @media (min-width: 1024px) {
        #result-count { margin-top: 40px; margin-right: 20px !important; }
        #products-grid { display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 1rem; position: relative; min-height: 400px; transition: min-height 0.3s ease; }
        .product-card { width: calc(25% - 0.8rem); margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .product-card .image-container { height: 200px; padding-bottom: 0; }
        .product-card .main-product-image { position: absolute; top: 0; left: 0; transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1); }
        .product-card:hover .main-product-image { transform: translateX(-100%); }
        .product-card:hover .hover-product-image { transform: translateX(0); }
    }
    @media (min-width: 764px) and (max-width: 1023px) {
        #products-grid { display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 1rem; }
        .product-card { width: calc(33.33% - 0.7rem); margin-bottom: 1rem; }
        .product-card .image-container { height: 180px; padding-bottom: 0; }
    }
    @media (max-width: 763px) {
        #products-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .product-card { min-height: 220px; margin-bottom: 0.5rem; width: 100%; }
        .product-card .image-container { padding-bottom: 100%; }
        .product-card .hover-product-image { display: none !important; }
        .product-card:hover .main-product-image { transform: none !important; }
    }

    .sidebar-container { width: 25%; flex-shrink: 0; }
    @media (max-width: 763px) { .sidebar-container { display: none; } }
    .sticky-sidebar { position: sticky; top: 120px; padding-left: 20px; border-left: 1px solid #eee; }

    .filter-group { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f3f3; }
    .filter-title { font-weight: 700; margin-bottom: 0.8rem; display: block; color: #3A3A3A; }
    
    .custom-checkbox { display: flex; align-items: center; margin-bottom: 0.5rem; cursor: pointer; color: #555; }
    .custom-checkbox input { margin-left: 10px; width: 16px; height: 16px; accent-color: #C8A95A; }

    .price-slider-container { width: 100%; padding: 10px 0; }
    .slider-wrapper { position: relative; width: 100%; height: 6px; background: #e0e0e0; border-radius: 3px; }
    .slider-track-fill { position: absolute; top: 0; height: 100%; right: 0; left: auto; background: #C8A95A; border-radius: 3px; width: 50%; pointer-events: none; }
    .single-range-input { position: absolute; top: -7px; left: 0; width: 100%; height: 20px; -webkit-appearance: none; background: transparent; pointer-events: auto; cursor: pointer; }
    .single-range-input::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%; background: #FFFFFF; border: 2px solid #C8A95A; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 0.2s; }
    .price-labels { display: flex; justify-content: space-between; margin-top: 10px; font-size: 0.85rem; color: #333; font-weight: 600; font-family: 'Roboto', sans-serif; }

    .color-circle { width: 24px; height: 24px; border-radius: 50%; border: 1px solid #000; display: block; }
    .color-option-label input:checked + .color-circle { transform: scale(1.2); box-shadow: 0 0 0 2px #fff, 0 0 0 3px #000; }
    .color-option-label { margin: 5px; cursor: pointer; position: relative; }
    .color-option-label input { position: absolute; opacity: 0; width: 0; height: 0; }

    /* === SIZE & VOLUME STYLING UNIFIED (تم التعديل ليتطابقا تماماً) === */
.size-box,
.volume-box {
    min-width: 35px;
    height: 35px;
    box-sizing: border-box; /* 🔒 prevents shaking */
    
    border: 1px solid #ddd !important;
    border-radius: 0px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-family: 'Roboto', sans-serif;
    font-size: 0.85rem;
    font-weight: 400; /* 🔒 fixed weight */

    padding: 0 10px;
    background-color: #fff;

    transition: 
        color 0.2s ease,
        background-color 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;

    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Hover & Checked */
.size-option-label:hover .size-box,
.size-option-label input:checked + .size-box,
.volume-option-label:hover .volume-box,
.volume-option-label input:checked + .volume-box {
    border-color: #C8A95A;
    color: #C8A95A;
    background-color: rgba(200, 169, 90, 0.05);

    /* professional highlight instead of bold */
}


    .size-option-label, .volume-option-label { margin: 3px; cursor: pointer; position: relative; }
    .size-option-label input, .volume-option-label input { position: absolute; opacity: 0; width: 0; height: 0; }
    
    /* Hover and Checked states for BOTH Size and Volume */
    .size-option-label:hover .size-box, 
    .size-option-label input:checked + .size-box,
    .volume-option-label:hover .volume-box, 
    .volume-option-label input:checked + .volume-box { 
        border-color: #C8A95A; 
        color: #C8A95A; 
        font-weight: bold; 
        border-width: 2px;
        background-color: transparent; /* لضمان نفس الخلفية */
    }
    /* === END SIZE & VOLUME STYLING === */

    .mobile-filter-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; }
    .mobile-filter-menu { position: fixed; top: 0; right: 0; bottom: 0; width: 100%; background: #fff; z-index: 9999; transform: translateX(100%); transition: transform 0.3s ease-in-out; padding: 20px; overflow-y: auto; z-index: 10001 !important; }
    .mobile-filter-menu.active { transform: translateX(0); }
    .mobile-filter-overlay.active { display: block; }
    
    .filter-group .custom-checkbox .arabic-font { color: #000000 !important; font-weight: 600; }
    
    .mobile-filter-toggle { display: none; position: fixed; bottom: 30px; left: 0; z-index: 9990; background: #212121; color: #C8A95A; border: 1px solid #C8A95A; padding: 12px 25px; border-radius: 0 30px 30px 0; box-shadow: 0 4px 15px rgba(200, 169, 90, 0.2); font-family: 'Cairo', sans-serif; transform: translateX(-100%); transition: transform 0.4s ease-out; }
    .mobile-filter-toggle.slide-in { transform: translateX(0); }
    @media (max-width: 763px) { .mobile-filter-toggle { display: flex; align-items: center; gap: 8px; } }

    .infinite-loader { text-align: center; padding: 20px; display: none; justify-content: center; }
    .infinite-loader p { display: none !important; }

    .fixed-loader { position: static !important; margin: 50px auto !important; transform: none !important; display: flex !important; flex-direction: column !important; justify-content: center !important; align-items: center !important; background: transparent !important; border: none !important; box-shadow: none !important; z-index: 50 !important; }
    .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #3A3A3A; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    
    .particle { position: fixed; width: 6px; height: 6px; border-radius: 50%; pointer-events: none; z-index: 9999; opacity: 0; }
    @keyframes heartBeat { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }
    .hidden-loader { display: none !important; }
</style>
</head>
<body dir="rtl">

<?php include 'header.php'; ?>

<header class="breadcrumb-header">
    <div class="breadcrumb-inner">
        <div class="breadcrumb-links">
            <a href="index.php">الرئيسية</a>
            <i class="fa-solid fa-chevron-left text-gray-400 text-sm mx-2"></i>
            <span class="breadcrumb-current">المنتجات</span>
        </div>
    </div>
</header>

<button class="mobile-filter-toggle" id="mobileFilterBtn" onclick="toggleMobileMenu()">
    <i class="fa-solid fa-filter"></i> تصفية
</button>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex gap-8 relative">
        
        <!-- SIDEBAR -->
        <aside class="sidebar-container">
            <div class="sticky-sidebar">
                <form id="filterFormDesktop">
              <div class="filter-group">
    <span class="filter-title">التصنيف</span>
    <div>
        <!-- All Checkbox -->
        <label class="custom-checkbox">
            <input type="checkbox" name="categories[]" value="all" <?php echo $all_checked_status; ?> onchange="handleFilterChange(true)">
            <span class="arabic-font">الكل</span>
        </label>

   <?php foreach($categories as $cat): 
    $is_checked = ($selected_id == $cat['id']) ? 'checked' : '';
?>
    <label class="custom-checkbox flex justify-between items-center w-full">
        <div class="flex items-center">
            <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" <?php echo $is_checked; ?> onchange="handleFilterChange(true)">
            <span class="arabic-font"><?php echo $cat['name']; ?></span>
        </div>
        <!-- إعادة إظهار عدد المنتجات -->
        <span class="text-xs text-gray-400 font-sans">(<?php echo $cat['count']; ?>)</span>
    </label>
<?php endforeach; ?>
    </div>
</div>
                    <div class="filter-group">
                         <span class="filter-title">السعر</span>
                         <div class="price-slider-container">
                             <div class="slider-wrapper">
                                 <div class="slider-track-fill" id="track-fill-desktop"></div>
                                 <input type="range" class="single-range-input" id="price-range-desktop" name="max_price" min="0" max="2000" value="0" step="10" oninput="updateSingleSlider('desktop')" onchange="handleFilterChange(true)">
                             </div>
                             <div class="price-labels">
                                 <span>0 د.م</span>
                                 <span id="price-val-desktop">0 د.م</span>
                             </div>
                         </div>
                    </div>

                    <div class="filter-group">
                        <span class="filter-title">المقاسات</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($static_sizes as $size): ?>
                                <label class="size-option-label">
                                    <input type="checkbox" name="sizes[]" value="<?php echo $size['slug']; ?>" onchange="handleFilterChange(true)">
                                    <span class="size-box"><?php echo $size['name']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="filter-group">
                        <span class="filter-title">الحجم (مل)</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($static_volumes as $vol): ?>
                                <label class="volume-option-label">
                                    <input type="checkbox" name="volumes[]" value="<?php echo $vol['slug']; ?>" onchange="handleFilterChange(true)">
                                    <span class="volume-box"><?php echo $vol['name']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="filter-group">
                        <span class="filter-title">الألوان</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($static_colors as $color): ?>
                                <label class="color-option-label" title="<?php echo $color['name']; ?>">
                                    <input type="checkbox" name="colors[]" value="<?php echo $color['slug']; ?>" onchange="handleFilterChange(true)">
                                    <span class="color-circle" style="background-color: <?php echo $color['hex']; ?>;"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </form>
            </div>
        </aside>

        <!-- MOBILE MENU -->
        <div class="mobile-filter-overlay" id="mobileOverlay" onclick="toggleMobileMenu()"></div>
        <div class="mobile-filter-menu" id="mobileMenu">
            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                <h3 class="font-bold text-xl arabic-font">خيارات الفلترة</h3>
                <button onclick="toggleMobileMenu()" class="text-2xl text-gray-500">&times;</button>
            </div>
            <form id="filterFormMobile">
          <div class="filter-group">
    <span class="filter-title">التصنيف</span>
    <div>
        <!-- All Checkbox -->
        <label class="custom-checkbox">
            <input type="checkbox" name="categories[]" value="all" <?php echo $all_checked_status; ?> onchange="handleFilterChange(true)">
            <span class="arabic-font">الكل</span>
        </label>

       <?php foreach($categories as $cat): 
    $is_checked = ($selected_id == $cat['id']) ? 'checked' : '';
?>
    <label class="custom-checkbox flex justify-between items-center w-full">
        <div class="flex items-center">
            <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" <?php echo $is_checked; ?> onchange="handleFilterChange(true)">
            <span class="arabic-font"><?php echo $cat['name']; ?></span>
        </div>
        <!-- إعادة إظهار عدد المنتجات -->
        <span class="text-xs text-gray-400 font-sans">(<?php echo $cat['count']; ?>)</span>
    </label>
<?php endforeach; ?>
    </div>
</div>

               <div class="filter-group">
                     <span class="filter-title">السعر</span>
                     <div class="price-slider-container">
                         <div class="slider-wrapper">
                             <div class="slider-track-fill" id="track-fill-mobile"></div>
                             <input type="range" class="single-range-input" id="price-range-mobile" name="max_price" min="0" max="2000" value="0" step="10" oninput="updateSingleSlider('mobile')" onchange="handleFilterChange(true)">
                         </div>
                         <div class="price-labels">
                             <span>0 د.م</span>
                             <span id="price-val-mobile">0 د.م</span>
                         </div>
                     </div>
                </div>

                <div class="filter-group">
                    <span class="filter-title">المقاسات</span>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($static_sizes as $size): ?>
                            <label class="size-option-label">
                                <input type="checkbox" name="sizes[]" value="<?php echo $size['slug']; ?>" onchange="handleFilterChange(true)">
                                <span class="size-box"><?php echo $size['name']; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                 <div class="filter-group">
                        <span class="filter-title">الحجم (مل)</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($static_volumes as $vol): ?>
                                <label class="volume-option-label">
                                    <input type="checkbox" name="volumes[]" value="<?php echo $vol['slug']; ?>" onchange="handleFilterChange(true)">
                                    <span class="volume-box"><?php echo $vol['name']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <div class="filter-group">
                    <span class="filter-title">الألوان</span>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($static_colors as $color): ?>
                            <label class="color-option-label">
                                <input type="checkbox" name="colors[]" value="<?php echo $color['slug']; ?>" onchange="handleFilterChange(true)">
                                <span class="color-circle" style="background-color: <?php echo $color['hex']; ?>;"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- PRODUCTS -->
        <div class="w-full md:w-3/4">
            <div id="result-count" class="w-full text-right mb-4 text-sm font-bold text-gray-500 arabic-font bg-transparent">
            </div>

            <div id="products-grid"></div>

            <div id="loader" class="infinite-loader">
                <div class="spinner"></div>
                <p class="text-xs text-gray-500 mt-2 arabic-font"></p>
            </div>
            
            <div id="end-message" class="text-center py-8 hidden">
                <p class="text-gray-400 arabic-font text-sm">تم عرض جميع المنتجات.</p>
            </div>
        </div>

    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    let page = 1;
    let loading = false;
    let hasMore = true;
    let currentlyShown = 0;
    let debounceTimer; 

    function updateSingleSlider(type) {
        const input = document.getElementById(`price-range-${type}`);
        const fill = document.getElementById(`track-fill-${type}`);
        const valDisp = document.getElementById(`price-val-${type}`);
        
        const val = input.value;
        const max = input.max;
        const percentage = (val / max) * 100;
        
        fill.style.width = percentage + "%";
        valDisp.textContent = val + " د.م";
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateSingleSlider('desktop');
        updateSingleSlider('mobile');
        fetchProducts();
    });

    function toggleMobileMenu() {
        document.getElementById('mobileMenu').classList.toggle('active');
        document.getElementById('mobileOverlay').classList.toggle('active');
    }

    function toggleFilters(disable) {
        const inputs = document.querySelectorAll('#filterFormDesktop input, #filterFormMobile input');
        inputs.forEach(input => {
            input.disabled = disable;
        });
    }

    function handleFilterChange(reset = false) {
        if (loading) return;

        const isMobile = window.innerWidth < 764; 
        const formId = isMobile ? 'filterFormMobile' : 'filterFormDesktop';
        
        const container = document.getElementById(formId);
        const catCheckboxes = container.querySelectorAll('input[name="categories[]"]');
        const anyChecked = container.querySelector('input[name="categories[]"]:checked');

        const allCb = container.querySelector('input[value="all"]');
        const changedInput = event ? event.target : null;

        if (changedInput && changedInput.name === 'categories[]') {
            if (changedInput !== allCb && changedInput.checked) {
                if(allCb) allCb.checked = false;
            } else if (changedInput === allCb && allCb.checked) {
                catCheckboxes.forEach(cb => { if(cb !== allCb) cb.checked = false; });
            }
        }

        const currentChecked = container.querySelectorAll('input[name="categories[]"]:checked');
        if (currentChecked.length === 0 && allCb) {
            allCb.checked = true;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            executeFilterChange(reset);
        }, 400); 
    }

  function executeFilterChange(reset) {
    if(reset) {
        page = 1;
        hasMore = true;
        currentlyShown = 0;
        
        const grid = document.getElementById('products-grid');
        grid.innerHTML = ''; 
        
        const loader = document.getElementById('loader');
        loader.classList.add('fixed-loader'); 
        loader.style.display = 'flex';
        
        document.getElementById('end-message').classList.add('hidden');
    }
    fetchProducts();
}

 function fetchProducts() {
    if (loading || (!hasMore && page > 1)) return;
    
    loading = true;
    toggleFilters(true);

    document.getElementById('loader').style.display = 'flex'; 

    const params = new URLSearchParams();
    params.append('page', page);
    params.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

 // --- التحديث المطلوب (Update) ---
    // قراءة البحث من الرابط وتعبئته في الحقول إذا وجد
    const urlParams = new URLSearchParams(window.location.search);
    const urlSearch = urlParams.get('search');
    
    if (urlSearch) {
        params.append('search', urlSearch);
        // إضافة: عرض نص للمستخدم ليظهر له أنه يبحث عن كلمة معينة
        const countEl = document.getElementById('result-count');
        if(page === 1) countEl.innerHTML = `<span class="text-accent">نتائج البحث عن: "${urlSearch}"</span>`;
         // تحديث: إذا كان هناك بحث، اجعل العميل يرى أنه يبحث في "الكل" 
    document.querySelectorAll('input[name="categories[]"]').forEach(cb => {
        cb.checked = (cb.value === 'all');
    });

    }
    // ---------------------------------
    const isMobile = window.innerWidth < 764; 
    const formId = isMobile ? 'filterFormMobile' : 'filterFormDesktop';
    
    document.querySelectorAll(`#${formId} input:checked`).forEach(input => {
        if(input.name === 'categories[]') params.append('categories[]', input.value);
        if(input.name === 'sizes[]') params.append('sizes[]', input.value);
        if(input.name === 'colors[]') params.append('colors[]', input.value);
        if(input.name === 'volumes[]') params.append('volumes[]', input.value);
    });

    const priceInput = document.getElementById(`price-range-${isMobile?'mobile':'desktop'}`);
    if(priceInput) params.append('max_price', priceInput.value);

    fetch('filter_ajax.php', {
        method: 'POST',
        body: params
    })
    .then(response => response.json())
    .then(data => {
        const grid = document.getElementById('products-grid');
        const countEl = document.getElementById('result-count');
        const loader = document.getElementById('loader');

        if(page === 1) grid.style.minHeight = 'auto';

        if (data.html) {
            grid.insertAdjacentHTML('beforeend', data.html);
            revealSkeletonsWithDelay();
        } else if (page === 1) {
            grid.innerHTML = '<div class="col-span-full w-full text-center py-10 text-gray-500 arabic-font">لا توجد منتجات تطابق اختيارك.</div>';
        }

        const newCount = (data.html.match(/product-card/g) || []).length;
        currentlyShown += newCount;
        
        // هنا يتم عرض العدد الكلي لمنتجات المتجر القادم من data.total_store_products
        // هذا الرقم ثابت للمتجر كله كما طلبت
        countEl.innerText = `تم عرض ${currentlyShown} منتجات من أصل ${data.total_store_products} منتج`;
        
        hasMore = data.has_more;
        if (!hasMore && page > 1) {
            document.getElementById('end-message').classList.remove('hidden');
        }

        loading = false;
        toggleFilters(false);
        
        loader.classList.remove('fixed-loader');
        loader.style.display = 'none';
    })
    .catch(err => {
        console.error(err);
        loading = false;
        toggleFilters(false); 
        document.getElementById('loader').style.display = 'none';
    });
}
    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
            if(hasMore && !loading) {
                page++;
                fetchProducts();
            }
        }
         const btn = document.getElementById('mobileFilterBtn');
        const footer = document.querySelector('footer'); 
        if (btn && window.innerWidth < 764) {
            const footerRect = footer ? footer.getBoundingClientRect() : { top: 99999 };
            if (footerRect.top > window.innerHeight) {
                btn.classList.add('slide-in');
            } else {
                btn.classList.remove('slide-in');
            }
        }
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
    function createParticles(x, y) {
        const colors = ['#ff4b4b', '#C8A95A', '#FFD700', '#ffb6b6'];
        for (let i = 0; i < 10; i++) {
            const p = document.createElement('div');
            p.classList.add('particle');
            document.body.appendChild(p);
            p.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            p.style.left = x + 'px'; p.style.top = y + 'px'; p.style.opacity = '1';
            const dx = (Math.random() - 0.5) * 80; const dy = (Math.random() - 0.5) * 80;
            const anim = p.animate([{transform:`translate(0,0)`,opacity:1},{transform:`translate(${dx}px,${dy}px)`,opacity:0}], {duration:600,fill:'forwards'});
            anim.onfinish = () => p.remove();
        }
    }
 function revealSkeletonsWithDelay() {
    const containers = document.querySelectorAll('.image-container.skeleton-pending, .image-container.skeleton-active');
    const MIN_SKELETON_TIME = 500; 

    containers.forEach(container => {
        container.classList.remove('skeleton-pending');
        container.classList.add('skeleton-active');

        const mainImg = container.querySelector('.main-product-image');
        const hoverImg = container.querySelector('.hover-product-image'); // جلب صورة الهوفر
        const wishlistIcon = container.querySelector('.wishlist-icon');
        
        const mainSrc = container.getAttribute('data-main-image-src');
        const hoverSrc = container.getAttribute('data-hover-image-src'); // جلب رابط صورة الهوفر من الـ attribute

        if (!mainSrc) return;

        let isDone = false;
        const finishLoading = () => {
            if (isDone) return;
            isDone = true;
            
            container.classList.remove('skeleton-active');

            // تفعيل الصورة الأساسية
            if (mainImg) {
                mainImg.src = mainSrc;
                mainImg.classList.add('loaded');
            }

            // تفعيل صورة الهوفر (هذا هو الجزء المفقود لديك)
            if (hoverImg && hoverSrc) {
                hoverImg.src = hoverSrc;
                hoverImg.classList.add('loaded'); // لكي تصبح opacity: 1
            }

            if (wishlistIcon) {
                wishlistIcon.style.opacity = "1";
                wishlistIcon.style.visibility = "visible";
                wishlistIcon.style.display = "flex";
            }
        };

        const imgCheck = new Image();
        imgCheck.src = mainSrc;

        if (imgCheck.complete) {
            setTimeout(finishLoading, MIN_SKELETON_TIME);
        } else {
            imgCheck.onload = () => setTimeout(finishLoading, MIN_SKELETON_TIME);
            imgCheck.onerror = finishLoading; 
            setTimeout(finishLoading, 3000); 
        }
    });
}
</script>
</body>
</html>