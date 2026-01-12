<?php
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


require_once 'db.php';        // أولاً
require_once 'functions.php'; // ثانياً
// تأكد أن المسار صحيح. __DIR__ تعني المجلد الحالي الذي فيه الملف
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    die('Error: Composer autoload not found. Run "composer install" or "composer require monolog/monolog".');
}
$user_wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
/*
 * --------------------------------------------------------------------------
 * 2. MONOLOG SETUP
 * --------------------------------------------------------------------------
 */
// جلب قائمة بمعرفات المنتجات التي أضافها المستخدم للمفضلة حالياً



// تحميل ملف keys.env
try {
    if (!file_exists(__DIR__ . '/apikeys.env')) {
        throw new Exception("API keys file not found.");
    }
    $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
    $dotenv->load();
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}

// جلب رقم الواتساب من البيئة (أو وضع افتراضي إذا لم يوجد)
$whatsapp_number = $_ENV['whatsapp_number'] ?? '212000000000';

// التأكد من وجود مجلد للسجلات (Logs)
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// إعداد Monolog
$log = new Logger('shop_logger');
try {
    $log->pushHandler(new StreamHandler($log_dir . '/app.log', Logger::INFO));
} catch (Exception $e) {
    // في حالة وجود خطأ في الصلاحيات لا توقف الموقع، فقط تجاهل اللوج
}

/*
 * --------------------------------------------------------------------------
 * 3. GET ID & START LOGIC
 * --------------------------------------------------------------------------
 */

// استيراد الفئات المطلوبة بعد autoload
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// تسجيل زيارة (تجربة)
$log->info('Product Page Visited', ['product_id' => $product_id, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown']);

if ($product_id === 0) {
    $log->warning('Product ID missing');
    die('<div style="text-align:center; padding:50px;">Product ID not specified.</div>');
}

// ... أكمل باقي الكود الخاص بالكاش والـ WooCommerce كما هو ...
// ملاحظة: احذف سطر require __DIR__ . '/vendor/autoload.php'; القديم الموجود في الأسفل داخل if (!$data)
// لأننا قمنا باستدعائه في الأعلى بالفعل.


$cache_dir = __DIR__ . '/cache';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$cache_file = $cache_dir . '/product_' . $product_id . '.json';
$cache_time = 3600; // 1 Hour
$data = null;

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    $json_content = file_get_contents($cache_file);
    $data = json_decode($json_content, true);
}

/*
 * --------------------------------------------------------------------------
 * SETUP: CONNECT TO WOOCOMMERCE
 * --------------------------------------------------------------------------
 */
/*
 * --------------------------------------------------------------------------
 * SETUP: CONNECT TO WOOCOMMERCE
 * --------------------------------------------------------------------------
 */
if (!$data) {
    // لا نحتاج لـ require autoload هنا لأننا استدعيناه في الأعلى
    
    // استخدام المتغيرات من .env
    $store_url = $_ENV['wordpress_url'] ?? null;
    $consumer_key = $_ENV['consumer_key'] ?? null;
    $consumer_secret = $_ENV['secret_key'] ?? null;

    if (!$store_url || !$consumer_key || !$consumer_secret) {
        die("Technical Error: API config missing in .env file.");
    }

    $woocommerce = new Automattic\WooCommerce\Client(
        $store_url,
        $consumer_key,
        $consumer_secret,
        ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 20]
    );
    function get_attr_value_fn($attributes, $target_name) {
        foreach ($attributes as $attr) {
            if (strcasecmp($attr->name, $target_name) == 0 || strpos($attr->name, $target_name) !== false) {
                return is_array($attr->options) ? implode(', ', $attr->options) : $attr->options;
            }
        }
        return null;
    }

    function get_attr_options_fn($attributes, $target_names) {
        foreach ($attributes as $attr) {
            foreach($target_names as $name) {
                if (strcasecmp($attr->name, $name) == 0 || strpos($attr->name, $name) !== false) {
                    return is_array($attr->options) ? $attr->options : [$attr->options];
                }
            }
        }
        return [];
    }

    function get_meta_value_fn($meta_data, $key) {
        foreach ($meta_data as $meta) {
            if ($meta->key === $key) return $meta->value;
        }
        return null;
    }

    try {
        try {
            $product = $woocommerce->get('products/' . $product_id);
            
        } catch (Exception $e) {
            $log->error("Product not found via API: ID $product_id - " . $e->getMessage());
            die('<div style="text-align:center; padding:50px;">Product Not Found.</div>');
        }

        $extracted = [];
        $extracted['id'] = $product->id;
        $extracted['name'] = $product->name;
        $extracted['sku'] = $product->sku ?: 'N/A';
        $extracted['short_desc'] = strip_tags($product->short_description) ?: '';
        $extracted['full_desc'] = $product->description ?: $extracted['short_desc'];
        $extracted['weight'] = $product->weight ? $product->weight . ' كجم' : 'غير متوفر';
        
        // Stock Logic
        $stock_qty = 2; // Default fallback
        if (isset($product->stock_quantity) && is_numeric($product->stock_quantity) && $product->stock_quantity > 0) {
            $stock_qty = $product->stock_quantity;
        }
        $extracted['stock_qty'] = $stock_qty;

        if (!empty($product->dimensions) && $product->dimensions->length) {
            $d = $product->dimensions;
            $extracted['dims'] = "$d->length × $d->width × $d->height سم";
        } else {
            $extracted['dims'] = 'غير متوفر';
        }

        $extracted['attr_material'] = get_attr_value_fn($product->attributes, 'Material') ?: (get_attr_value_fn($product->attributes, 'الخامات') ?: 'غير متوفر');
        $extracted['attr_country'] = get_attr_value_fn($product->attributes, 'Country') ?: (get_attr_value_fn($product->attributes, 'بلد الصنع') ?: 'غير متوفر');
        
        $brand_name = 'غير محدد';
        if (isset($product->brands) && is_array($product->brands) && !empty($product->brands)) {
            $brand_name = $product->brands[0]->name;
        } elseif (isset($product->brand) && is_string($product->brand)) {
            $brand_name = $product->brand;
        }
        $extracted['brand_name'] = $brand_name;

        $extracted['categories'] = [];
        foreach($product->categories as $cat) {
            $extracted['categories'][] = $cat->name;
        }
        $extracted['cat_string'] = !empty($extracted['categories']) ? implode('، ', $extracted['categories']) : 'غير متوفر';

        $cats_hide_sizes = ['ساعات', 'Watches', 'تكنولوجيا', 'Technology', 'اكسسوارات', 'Accessories', 'مكياج', 'Makeup', 'ديكورات', 'Decor'];
        $cats_hide_colors = ['ديكورات', 'Decor'];
$cats_ml_sizes = ['عطور', 'Parfums', 'Perfumes']; // يمكنك إضافة أي تصنيف جديد هنا مستقبلاً
$is_ml_category = false;
        $show_sizes = true;
        $show_colors = true;

        foreach($extracted['categories'] as $cat_name) {
             if(in_array($cat_name, $cats_ml_sizes)) {
        $is_ml_category = true;
    }
            if(in_array($cat_name, $cats_hide_sizes)) {
                $show_sizes = false;
            }
            if(in_array($cat_name, $cats_hide_colors)) {
                $show_colors = false;
            }
        }
        $extracted['show_sizes'] = $show_sizes;
        $extracted['show_colors'] = $show_colors;

        $extracted['available_colors'] = get_attr_options_fn($product->attributes, ['Color', 'اللون', 'الألوان']);

        $extracted['tags'] = [];
        if (!empty($product->tags)) {
            foreach($product->tags as $t) $extracted['tags'][] = $t->name;
        }

        $variations_map = [];
        $available_sizes_list = [];
if ($is_ml_category) {
    // هذه هي المقاسات التي ستظهر إذا كان التصنيف عطور
    $standard_sizes_list = ['30ml', '50ml', '100ml', '150ml', '200ml'];
} else {
    // المقاسات الافتراضية للملابس وغيرها
    $standard_sizes_list = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
}        
        $active_price = $product->price . ' د.م';
        $active_regular_price = '';
        $active_discount = 0;
        $active_image = isset($product->images[0]) ? $product->images[0]->src : 'https://via.placeholder.com/600x600';
        $selected_size = null;
        $selected_color = null;

        if ($product->type === 'variable') {
            $variations = $woocommerce->get("products/{$product_id}/variations", ['per_page' => 50]);
            
            foreach ($variations as $variation) {
                $key = null;
                $type = 'none';

                foreach ($variation->attributes as $attr) {
                    if (strcasecmp($attr->name, 'Size') == 0 || strpos($attr->name, 'المقاس') !== false) {
// إذا كان المقاس يحتوي على "ml" نتركه كما هو، وإذا كان حروف (S, M) نحوله لكبير
// حذف المسافات وتحويل الكل لحروف صغيرة لضمان المطابقة
// إذا كان عطر يحوله لصغير، وإذا ملابس يحوله لكبير.. مع حذف المسافات في الحالتين
$clean_option = str_replace(' ', '', $attr->option);
$key = $is_ml_category ? strtolower($clean_option) : strtoupper($clean_option);                     $type = 'size';
                        break;
                    }
                }

                if (!$key) {
                    foreach ($variation->attributes as $attr) {
                        if (strcasecmp($attr->name, 'Color') == 0 || strcasecmp($attr->name, 'اللون') == 0 || strcasecmp($attr->name, 'الألوان') == 0) {
                            $key = $attr->option;
                            $type = 'color';
                            break;
                        }
                    }
                }

                if ($key) {
                    if ($type === 'size') $available_sizes_list[] = $key;
                    
                    $v_price = $variation->price;
                    $v_reg = $variation->regular_price;
                    $v_disc = 0;
                    $v_reg_display = '';
                    if($v_reg && $v_price && $v_reg > $v_price) {
                        $v_disc = round((($v_reg - $v_price) / $v_reg) * 100);
                        $v_reg_display = $v_reg . ' د.م';
                    }
                    $v_img = isset($variation->image) ? $variation->image->src : null;
                    
                    $variations_map[$key] = [
                        'id' => $variation->id, // أضف هذا السطر ضروري
                        'price' => $v_price . ' د.م',
                        'regular_price' => $v_reg_display,
                        'discount' => $v_disc,
                        'image' => $v_img,
                        'type' => $type
                    ];
                }
            }
            
            if ($show_sizes && !empty($available_sizes_list)) {
                foreach ($standard_sizes_list as $std) {
                    if (in_array($std, $available_sizes_list)) {
                        $selected_size = $std;
                        $v_data = $variations_map[$std];
                        $active_price = $v_data['price'];
                        $active_regular_price = $v_data['regular_price'];
                        $active_discount = $v_data['discount'];
                        if ($v_data['image']) $active_image = $v_data['image'];
                        break;
                    }
                }
            } elseif ($show_colors && !empty($extracted['available_colors'])) {
                $first_color = $extracted['available_colors'][0];
                if (isset($variations_map[$first_color])) {
                    $selected_color = $first_color;
                    $v_data = $variations_map[$first_color];
                    $active_price = $v_data['price'];
                    $active_regular_price = $v_data['regular_price'];
                    $active_discount = $v_data['discount'];
                    if ($v_data['image']) $active_image = $v_data['image'];
                }
            }
        } else {
            if (!empty($product->regular_price) && !empty($product->price) && $product->regular_price > $product->price) {
                $active_discount = round((($product->regular_price - $product->price) / $product->regular_price) * 100);
                $active_regular_price = $product->regular_price . ' د.م';
            }
            foreach ($product->attributes as $attr) {
              if (strcasecmp($attr->name, 'Size') == 0 || strpos($attr->name, 'المقاس') !== false) {
                     $opts = is_array($attr->options) ? $attr->options : [$attr->options];
foreach($opts as $o) {
    $clean_opt = str_replace(' ', '', $o);
    $available_sizes_list[] = $is_ml_category ? strtolower($clean_opt) : strtoupper($clean_opt);
}                }
            }
            foreach ($standard_sizes_list as $std) {
                if (in_array($std, $available_sizes_list)) { $selected_size = $std; break; }
            }
        }

        $extracted['variations_map'] = $variations_map;
        $extracted['available_sizes'] = $available_sizes_list;
        $extracted['standard_sizes'] = $standard_sizes_list;
        $extracted['selected_size'] = $selected_size;
        $extracted['selected_color'] = $selected_color;
        $extracted['active_price'] = $active_price;
        $extracted['active_regular_price'] = $active_regular_price;
        $extracted['active_discount'] = $active_discount;
        $extracted['active_image'] = $active_image;

    $final_gallery = [];
        // 1. إضافة الصورة الرئيسية أولاً
        $final_gallery[] = ['src' => $active_image, 'type' => 'image'];

        // ==========================================
        // إعدادات البوستر الموحد (Fake Poster)
        // ضع هنا رابط الصورة التي تريدها أن تظهر لأي فيديو ليس له صورة خاصة
        // ==========================================
        $default_global_poster = 'https://i.pinimg.com/736x/64/f9/cb/64f9cbca265b726e0e339b7c2bd02638.jpg'; 

        // 2. التحقق من الفيديو الأول
        $v1_url = get_meta_value_fn($product->meta_data, 'product_video_url_1');
        $v1_poster = get_meta_value_fn($product->meta_data, 'product_video_poster_1');
        
        if ($v1_url) {
            // اللوجيك: إذا كان للفيديو بوستر خاص استخدمه، وإلا استخدم الموحد
            $poster = $v1_poster ? $v1_poster : $default_global_poster;
            $final_gallery[] = ['src' => $v1_url, 'poster' => $poster, 'type' => 'video'];
        }

        // 3. إضافة باقي صور المنتج
        if (isset($product->images)) {
            foreach($product->images as $img) {
                if ($img->src !== $active_image) {
                    $final_gallery[] = ['src' => $img->src, 'type' => 'image'];
                }
            }
        }

        // 4. التحقق من باقي الفيديوهات (من 2 إلى 10)
        for ($v = 2; $v <= 10; $v++) {
            $v_url = get_meta_value_fn($product->meta_data, 'product_video_url_' . $v);
            $v_poster = get_meta_value_fn($product->meta_data, 'product_video_poster_' . $v);
            
            if ($v_url) {
                // نفس اللوجيك: الخاص أو الموحد
                $poster = $v_poster ? $v_poster : $default_global_poster;
                $final_gallery[] = ['src' => $v_url, 'poster' => $poster, 'type' => 'video'];
            }
        }
        
        // حفظ النتيجة النهائية
        $extracted['gallery'] = $final_gallery;

        $product_faqs = [];
        for ($k = 1; $k <= 10; $k++) { 
            $q = get_meta_value_fn($product->meta_data, 'faq_question_' . $k);
            $a = get_meta_value_fn($product->meta_data, 'faq_answer_' . $k);
            if ($q && $a) $product_faqs[] = ['q' => $q, 'a' => $a];
        }
        $extracted['faqs'] = $product_faqs;

        $data = $extracted;
        file_put_contents($cache_file, json_encode($data));

    } catch (Exception $e) { 
        $log->critical(message: "API Error in Product Page: " . $e->getMessage());
        die("System Error. Please try again later."); 
    }
}

function get_color_hex($color_name) {
   $map = [

    // Red
    'red' => '#E53935', 'أحمر' => '#E53935', 'احمر' => '#E53935',

    // Blue
    'blue' => '#1E88E5', 'أزرق' => '#1E88E5', 'ازرق' => '#1E88E5',

    // Navy
    'navy' => '#0D47A1', 'كحلي' => '#0D47A1',

    // Green
    'green' => '#43A047', 'أخضر' => '#43A047', 'اخضر' => '#43A047',

    // Black
    'black' => '#000000', 'أسود' => '#000000', 'اسود' => '#000000',

    // White
    'white' => '#FFFFFF', 'أبيض' => '#FFFFFF', 'ابيض' => '#FFFFFF',

    // Yellow
    'yellow' => '#FBC02D', 'أصفر' => '#FBC02D', 'اصفر' => '#FBC02D',

    // Orange
    'orange' => '#FB8C00', 'برتقالي' => '#FB8C00',

    // Purple
    'purple' => '#8E24AA', 'بنفسجي' => '#8E24AA',

    // Brown
    'brown'  => '#6D4C41', 'بني' => '#6D4C41',

    // Grey
    'grey' => '#757575', 'gray' => '#757575',
    'رمادي' => '#757575',

    // Pink
    'pink' => '#EC407A', 'وردي' => '#EC407A', 'بمبي' => '#EC407A',

    // Beige
    'beige' => '#F5F5DC', 'بيج' => '#F5F5DC',

    // Gold
    'gold' => '#FFD700', 'ذهبي' => '#FFD700',

    // Silver
    'silver' => '#C0C0C0', 'فضي' => '#C0C0C0',
];

    $key = mb_strtolower(trim($color_name), 'UTF-8');
    return isset($map[$key]) ? $map[$key] : '#cccccc'; 
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $data['short_desc']; ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    
    <title> <?php echo htmlspecialchars($data['name']);?> - Abodlwahab Accssories & Parfums </title>
        <link rel="icon" type="image/png" href="images/lgicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'gold-main': '#C8A95A', 'text-dark': '#111111', 'bg-cream': '#FCFAF7' },
                    fontFamily: { arabic: ['Cairo', 'sans-serif'], body: ['Tajawal', 'sans-serif'] }
                }
            }
        }
    </script>

   <!-- Font Awesome CDN -->
   <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"></noscript>
    <!-- إعدادات Tailwind المخصصة -->
     <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { box-sizing: border-box; border-radius: 0 !important; }
        body { font-family: 'Cairo', sans-serif;background-color: #ffffff !important;  color: #333; margin: 0; overflow-x: hidden;padding-top: 100px; }
        
        /* Skeleton */
        .skeleton-box {
            background: linear-gradient(-90deg, #e2e8f0 0%, #cbd5e1 50%, #e2e8f0 100%);
            background-size: 200% 100%;
            animation: skeleton-pulse 1.5s ease-in-out infinite;
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 20;
            transition: opacity 0.5s ease;
            opacity: 1;
        }
        .skeleton-box.hidden-skeleton { opacity: 0; pointer-events: none; }
        @keyframes skeleton-pulse { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }

        /* BREADCRUMB */
        .breadcrumb-header {
            width: 100%;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764797982/da03ca5e2169685ac4867c812a4f0d4c_g8wpob.jpg') center/cover no-repeat;
            padding: 40px 0;
            color: #fff;
            margin-bottom: 20px;
        }
        .breadcrumb-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            font-family: 'Tajawal', sans-serif;
            font-weight: 700;
        }
        .breadcrumb-links {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .breadcrumb-links a { color: #ddd; text-decoration: none; transition: color 0.2s; white-space: nowrap; }
        @media (min-width: 1024px) {
            .breadcrumb-links a:hover { color: #fff; }
        }
        .breadcrumb-separator {  font-size: 1rem; color: #aaa; flex-shrink: 0; }
        
        .breadcrumb-current { 
            color: #C8A95A;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            max-width: 150px;
        }
        @media (min-width: 768px) {
            .breadcrumb-current { max-width: 500px; }
        }

        /* Layout */
        .product-layout { max-width: 1200px; margin: 0 auto; padding: 40px 20px; display: flex; flex-wrap: wrap; gap: 30px; align-items: flex-start; background: transparent; justify-content: center; }
        .images-col { flex: 1 1 45%; min-width: 300px; position: relative; align-self: flex-start; }
        
       .details-col { 
    flex: 1 1 45%; 
    min-width: 300px; 
    opacity: 1; /* غيرها من 0 إلى 1 لضمان الظهور الفوري */
    transform: none; /* ألغِ الحركة إذا كانت تسبب مشاكل في الظهور */
}
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* Main Image */
        .main-image { 
            width: 100%; height: 0; padding-bottom: 90%; 
            margin-bottom: 15px; border: 1px solid #eee; 
            position: relative; background-color: #fff; 
            overflow: hidden;
            cursor: crosshair;
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover; 
            transition: background-size 0.3s ease; 
        }
        @media (max-width: 1024px) { .main-image { pointer-events: none; } }
        .main-image-bg { display: none; }
        .main-image-video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; display: none; z-index: 15; }

        @media (min-width: 1024px) {
            .product-layout { gap: 40px; }
            .images-col { position: sticky; top: 20px; flex: 0 0 42%; max-width: 42%; }
            .details-col { flex: 0 0 50%; max-width: 50%; display: flex; flex-direction: column; align-items: center; }
            .details-content-wrapper { width: 100%; max-width: 480px; }
            .main-image { padding-bottom: 105%; }
            .breadcrumb-inner { padding-right: calc((100% - 1200px) / 2 + 20px); }
        }
        
        .details-content-wrapper { width: 100%; }

        /* Wishlist Inline */
        .wishlist-inline-btn { background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; color: #ccc; transition: color 0.2s; }
        @media (hover: hover) and (min-width: 1024px) { .wishlist-inline-btn:hover { color: #ff4b4b; } }
      /* أضف هذا لضمان ظهور الأيقونة الفارغة */
/* الحالة الافتراضية (عندما لا يكون المنتج في المفضلة) */
.wishlist-inline-btn .icon-empty { 
    display: block !important; 
}
.wishlist-inline-btn .icon-filled { 
    display: none !important; 
}

/* حالة التفعيل (عندما يكون المنتج في المفضلة - وجود كلاس active) */
.wishlist-inline-btn.active .icon-empty { 
    display: none !important; 
}
.wishlist-inline-btn.active .icon-filled { 
    display: block !important; 
    color: #ff4b4b; 
    animation: heartBeat 0.3s ease-in-out; 
}
        .gallery-wrapper-relative { position: relative; width: 100%; display: flex; align-items: center; }
        .gallery-row { display: flex; gap: 10px; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth; justify-content: flex-start; padding-bottom: 5px; scrollbar-width: none; width: 100%; margin: 0 30px; }
        .gallery-row::-webkit-scrollbar { display: none; }
        .gallery-item { flex: 0 0 auto; width: 82px; height: 82px; background-size: cover; background-position: center; cursor: pointer; border: 2px solid #eee; transition: 0.2s; background-color: #fff; position: relative; overflow: hidden; }
        @media (hover: hover) and (min-width: 1024px) { .gallery-item:hover { border-color: #000; } }
        .gallery-item.active { border-color: #C8A95A; border-width: 2px; }
        .scroll-arrow { position: absolute; background: none; border: none; color: #000; cursor: pointer; z-index: 5; padding: 0; height: 100%; display: flex; align-items: center; justify-content: center; width: 25px; transition: opacity 0.3s, transform 0.2s; }
        .scroll-arrow.disabled { opacity: 0.2; pointer-events: none; filter: grayscale(100%); }
        .scroll-arrow.left { left: -5px; } .scroll-arrow.right { right: -5px; } 
        .video-badge { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; pointer-events: none; z-index: 25; transition: opacity 0.3s; }
        .gallery-item .skeleton-box:not(.hidden-skeleton) ~ .video-badge { opacity: 0; }

        /* Typography */
        .product-title { font-size: 1.5rem;font-family: 'Arial', sans-serif !important;
    font-size: 1.6rem; /* تكبير بسيط */
     letter-spacing: 0.5px; /* تباعد بسيط بين الحروف */
 font-weight: 800; margin-bottom: 10px; line-height: 1.4; color: #000; }
        .price-container { display: flex; align-items: center; flex-wrap: nowrap; gap: 20px; margin-bottom: 15px; }
        .product-price { font-size: 1.3rem; font-weight: 800; color: #000; margin: 0; } 
        #regularPriceContainer { display: inline-flex; align-items: center; gap: 15px; }
        .regular-price-text { font-size: 1rem; color: #555; position: relative; display: inline-block; text-decoration: none !important; font-weight: 600; }
        .regular-price-text::after { content: ''; position: absolute; left: 0; width: 100%; height: 1px; background-color: #444; top: 50%; transform: translateY(-50%); opacity: 0.8; }
        .discount-badge { display: inline-flex; align-items: center; gap: 4px; background-color: transparent; color: #dc2626; font-weight: 800; font-size: 0.9rem; padding: 0; border: none; }
        @keyframes vibrate { 0% { transform: translateX(0); } 25% { transform: translateX(-2px) rotate(-5deg); } 50% { transform: translateX(0) rotate(0deg); } 75% { transform: translateX(2px) rotate(5deg); } 100% { transform: translateX(0); } }
        .fire-icon { animation: vibrate 0.4s infinite ease-in-out; display: inline-block; color: #dc2626; vertical-align: middle; font-size: 18px; }

        /* Category & Stock Row (Updated) */
        .cat-stock-row { display: flex; align-items: center; justify-content: flex-start; width: 100%; margin-bottom: 10px; font-size: 0.9rem; font-family: 'Tajawal', sans-serif; font-weight: 700; color: #555; border-bottom: 1px solid #eee; padding-bottom: 10px; gap: 15px; flex-wrap: wrap; }
        .cat-text { color: black; display: flex; align-items: center; gap: 5px; }
        .cat-value { color: #C8A95A; font-weight: 800; }
        .stock-text { color: #dc2626; display: flex; align-items: center; gap: 5px; }
        
        .product-bio { 
            font-family: 'Tajawal', sans-serif; font-size: 0.9rem; color: #222; 
            line-height: 1.7; margin-bottom: 30px; font-weight: 700; 
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; 
            overflow: hidden; text-overflow: ellipsis; 
        }
        
        @media (min-width: 1024px) {
            .product-bio { max-width: 500px; }
        }

        .options-container { display: flex; flex-direction: column; gap: 20px; margin-bottom: 20px; align-items: flex-start; width: 100%; }
        .options-label { display: block; font-weight: 700; margin-bottom: 10px; font-size: 0.95rem; color: #000; }
        .size-options-container { display: flex; gap: 10px; flex-wrap: wrap; }
        .size-box { min-width: 45px; padding: 9px 15px; border: 2px solid #ccc; text-align: center; cursor: pointer; font-weight: 700; font-size: 0.95rem; user-select: none; background: #fff; color: #000; transition: all 0.2s; }
        .size-box.selected { background-color: #C8A95A; color: #fff !important; border-color: #C8A95A; }
        .size-box.disabled { border-color: #e5e5e5; background-color: #f9f9f9; color: #aaa; cursor: not-allowed; }
        
        .qty-color-row { display: flex; gap: 15px; width: 100%; align-items: flex-end; flex-wrap: nowrap; justify-content: flex-start; }
        .qty-wrapper { flex: 0 0 130px; width: 130px; }
        .color-wrapper { flex: 1; max-width: 250px; min-width: 150px; position: relative; }

        .custom-select-trigger { display: flex; align-items: center; justify-content: space-between; width: 100%; height: 45px; background: #fff; border: 1px solid #ddd; padding: 0 15px; cursor: pointer; font-family: 'Tajawal', sans-serif; font-weight: 700; font-size: 0.95rem; transition: all 0.2s; user-select: none; }
        @media (min-width: 1024px) {
            .custom-select-trigger:hover { border-color: #999; }
        }
        .custom-select-options { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-top: none; z-index: 50; display: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-height: 250px; overflow-y: auto; }
        .custom-select-options.open { display: block; }
        .custom-option { display: flex; align-items: center; gap: 10px; padding: 12px 15px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f5f5f5; }
        .custom-option:last-child { border-bottom: none; }
        @media (min-width: 1024px) {
            .custom-option:hover { background-color: #f9f9f9; }
        }
        .custom-option.selected { background-color: #FCFAF7; color: #C8A95A; }
        .dropdown-color-dot { width: 20px; height: 20px; border-radius: 50% !important; border: 1px solid rgba(0,0,0,0.1); flex-shrink: 0; }

        .qty-box { display: flex; border: 1px solid #ccc; height: 45px; width: 100%; }
        .qty-btn { background: #f5f5f5; border: none; width: 40px; cursor: pointer; font-size: 1.2rem; color: #000; flex-shrink: 0; }
        .qty-input { flex: 1; text-align: center; border: none; border-left: 1px solid #ccc; border-right: 1px solid #ccc; outline: none; font-weight: 700; font-size: 1.1rem; width: 100%; }

        .meta-data-row { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; font-size: 0.75rem; flex-wrap: wrap; }
        .meta-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag-item { background-color: #f3f4f6; color: #222; padding: 3px 8px; font-weight: 600; border: 1px solid #eee; }

        .actions-row { display: flex; flex-direction: row; gap: 10px; width: 100%; margin-bottom: 12px; }
        .btn-cart { background-color: #000; color: white; width: 100%; padding: 16px; border: 2px solid #000; font-weight: 700; font-size: 1.05rem; cursor: pointer; transition: all 0.3s ease; display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 0; flex: 1; }
        .btn-whatsapp { background-color: #25D366; color: white; width: 100%; padding: 15px; border: none; font-weight: 700; font-size: 1.05rem; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px; flex: 1; }
        
        @media (min-width: 1024px) { .actions-row { max-width: 480px; } }
        @media (hover: hover) and (min-width: 1024px) { .btn-cart:hover { color: #C8A95A; } .btn-whatsapp:hover { background-color: #128C7E; } }

      /* Trust Badges Row (has background) */
.trust-badges-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    width: 100%;
    flex-wrap: wrap;

    background: transparent;              /* الخلفية هنا فقط */
    padding: 12px 15px;           /* مسافة داخلية حول كل البادجات */
    border-radius: 5px !important;          /* حواف ناعمة للصف بالكامل */
    margin: 10px 0;

}


/* Each Trust Item (no background) */
.trust-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding: 5px 8px;             /* مسافة بسيطة داخلية */
    gap: 4px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #333;
    text-align: center;
    white-space: nowrap;
    font-family: 'Tajawal', sans-serif;

    background: transparent;       /* مهم — بدون خلفية */
    border: none;                  /* بدون حدود */
}


/* Icon images */
.trust-item img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    display: block;
}
.comments-section {
    display: flex;
    justify-content: center;
    gap: 10px;
    width: 100%;
    height: 100px;
    background: transparent;              /* الخلفية هنا فقط */
    padding: 12px 15px;           /* مسافة داخلية حول كل البادجات */
    border-radius: 12px;          /* حواف ناعمة للصف بالكامل */
    margin: 10px 0;

    border: 2px dotted black;       /* حدود خفيفة أنيقة */
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}

        /* TABS */
        .tabs-section-full { background-color: transparent; padding: 50px 0; width: 100%; border-top: 1px solid #eee; }
        .tabs-wrapper { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        .tabs-nav { display: flex; justify-content: flex-start; gap: 15px; margin-bottom: 35px; border-bottom: 1px solid #e0e0e0; padding-bottom: 0; overflow-x: auto; scrollbar-width: none; }
        .tabs-nav::-webkit-scrollbar { display: none; }
        .tab-btn { padding: 12px 20px; background: transparent; border: none; font-family: 'Cairo', sans-serif; font-size: 1rem; font-weight: 700; color: #888; cursor: pointer; white-space: nowrap; margin-bottom: -1px; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab-btn.active { color: #C8A95A; border-bottom: 3px solid #C8A95A; }
        .tab-content-container { min-height: 220px; background: transparent; position: relative; overflow: hidden; }
        .tab-content { display: none; opacity: 0; transform: translateY(20px); transition: opacity 0.8s ease-out, transform 0.8s ease-out; }
        .tab-content.display-visible { display: block; }
        .tab-content.fade-in { opacity: 1; transform: translateY(0); }
        
        .tab-text { font-family: 'Tajawal', sans-serif; color: #222; font-size: 0.9rem; line-height: 1.8; font-weight: 700; }
        .info-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0 !important; overflow: hidden; box-shadow: none; }
        .info-table td { padding: 16px 20px; color: #333; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; font-weight: 500; background-color: #fff; font-family: 'Tajawal', sans-serif; }
        .info-table tr:last-child td { border-bottom: none; }
        .info-table td:first-child { font-weight: 700; width: 25%; background-color: #f9fafb; color: #111; border-left: 1px solid #e5e7eb; }

        .faq-item { background: #fff; border: 1px solid #eee; margin-bottom: 10px; padding: 0 20px; }
        .faq-question { width: 100%; text-align: right; background: none; border: none; padding: 20px 0; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-family: 'Tajawal', sans-serif; color: #000; }
        .faq-question span { margin-left: 15px; display: block; line-height: 1.4; }
        .faq-answer { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease; color: #333; padding-bottom: 0; font-weight: 500; font-family: 'Tajawal', sans-serif; font-size: 0.9rem; }
        .faq-item.open .faq-answer { max-height: 300px; opacity: 1; padding-bottom: 20px; }
        .faq-item i { transition: 0.3s; flex-shrink: 0; }
        .faq-item.open i { transform: rotate(180deg); color: #C8A95A; }

        /* MOBILE ADJUSTMENTS */
        @media (max-width: 768px) {
              body { padding-top: 95px; }  /* تم التعديل هنا لزيادة المسافة */
            .breadcrumb-header { padding: 20px 0; margin-bottom: 15px; }
            .breadcrumb-inner { font-size: 0.9rem; padding: 0 15px; }
            .product-layout { flex-direction: column; padding: 20px 15px; gap: 25px; }
            .images-col, .details-col { flex: 1 1 100%; width: 100%; min-width: 100%; max-width: 100%; }
            .images-col { position: static; }
            .product-title { font-size: 1.3rem; }
            .size-box { min-width: 35px; padding: 8px 10px; font-size: 0.85rem; }
            .info-table td { padding: 10px 8px; font-size: 0.85rem; }
            .faq-question { font-size: 0.8rem !important; padding: 15px 0; }
            .tabs-nav { width: 100%; justify-content: space-between; }
            .tab-btn { flex: 1; text-align: center; padding: 15px 5px; }
            #desc .tab-text h3 { margin-right: 12px; }
            #desc .tab-text p { margin-left: 12px; margin-right: 12px; }
            .actions-row { flex-direction: column; }
            
            .qty-color-row { gap: 10px; flex-direction: row; }
            .qty-wrapper { flex: 0 0 50%; max-width: 50%; }
            .color-wrapper { flex: 1; width: 50%; max-width: none; }
            .qty-box { width: 100%; }
            .custom-select-trigger { width: 100%; }
             .trust-item {
        font-size: 0.65rem;
        padding: 5px;
    }
      .tabs-wrapper {
        padding: 0 !important; /* إلغاء الحواف الجانبية */
        width: 100% !important;
        max-width: 100% !important;
    }
    .trust-item img {
        width: 55px;
        height: 55px;
    }
            .cat-stock-row { gap: 10px; font-size: 0.8rem; }
        }

  @media (max-width: 500px) {
            body { padding-top: 60px !important; } /* هنا نعيد المسافة طبيعية للموبايل */
              /* إخفاء آخر عنصر في شريط الثقة (Trust Badges) على الموبايل فقط */
            .trust-badges-row .trust-item:last-child {
                display: none;
            }
        }

.gallery-item.active:has(.skeleton-box:not(.hidden-skeleton)) {
    border-color: #eee !important; 
    border-width: 2px;
}
        /* تأثيرات الانتقال للعناصر المتغيرة */
.transition-element {
    transition: opacity 0.3s ease-in-out, transform 0.3s ease;
    opacity: 1;
    transform: translateY(0);
}

.fade-out-active {
    opacity: 0.4; /* تخفيف الشفافية بدلاً من إخفائها تماماً للحفاظ على المكان */
    transform: translateY(-2px); /* حركة بسيطة للأعلى لإعطاء طابع حيوي */
    pointer-events: none; /* منع النقر أثناء التحميل */
}

/* تطبيق الانتقال على السعر والصورة الرئيسية */
.product-price, .regular-price-text, .discount-badge {
    transition: opacity 0.3s ease;
}
.main-image {
    transition: background-image 0.3s ease-in-out, opacity 0.3s ease;
}




/* تنسيق حالة التفعيل */
.wishlist-inline-btn.active .icon-empty { display: none !important; }
.wishlist-inline-btn.active .icon-filled { display: block !important; color: #ff4b4b; animation: heartBeat 0.3s ease-in-out; }
/* هذا السطر سيجعل اللون الأحمر يظهر عند التفعيل للكلاسين معاً */
.wishlist-inline-btn.active .icon-filled{ 
    display: block !important; 
    color: #ff4b4b; 
    animation: heartBeat 0.3s ease-in-out; 
}
/* تنسيق زر المفضلة في صفحة المنتج */
.main-product-wishlist.active .icon-empty { display: none !important; }
.main-product-wishlist.active .icon-filled { 
    display: block !important; 
    color: #ff4b4b !important; 
    animation: heartBeat 0.3s ease-in-out; 
}
/* تنسيق الزر ليكون شفافاً وبدون حدود */
.main-product-wishlist {
    background: transparent !important;
    border: none !important;
    cursor: pointer;
    padding: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.wishlist-inline-btn.active .icon-empty { 
    display: none !important; 
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* تنسيق الفتات (Particles) */
.particle {
    position: fixed;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    opacity: 0;
}
    </style>
    <noscript>
        <style>.product-layout { display: block; } .skeleton-box { display: none; } .main-image { opacity: 1; height: auto; }</style>
    </noscript>



    <!-- SEO: Structured Data for Google Rich Snippets -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?php echo json_encode($data['name']); ?>,
  "image": [
    "<?php echo $data['active_image']; ?>"
    <?php 
    foreach($data['gallery'] as $img) {
        if($img['type'] == 'image' && $img['src'] != $data['active_image']) {
            echo ', "' . $img['src'] . '"';
        }
    }
    ?>
   ],
  "description": <?php echo json_encode(strip_tags($data['short_desc'])); ?>,
  "sku": <?php echo json_encode($data['sku']); ?>,
  "brand": {
    "@type": "Brand",
    "name": <?php echo json_encode($data['brand_name']); ?>
  },
  "offers": {
    "@type": "Offer",
    "url": "<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>",
    "priceCurrency": "MAD",
    "price": "<?php echo preg_replace('/[^0-9.]/', '', $data['active_price']); ?>",
    "availability": "<?php echo ($data['stock_qty'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'; ?>",
    "itemCondition": "https://schema.org/NewCondition"
  }
}
</script>

<!-- SEO: Breadcrumb Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [{
    "@type": "ListItem",
    "position": 1,
    "name": "الرئيسية",
    "item": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/"
  },{
    "@type": "ListItem",
    "position": 2,
    "name": "المنتجات",
    "item": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/products"
  },{
    "@type": "ListItem",
    "position": 3,
    "name": "<?php echo addslashes($data['name']); ?>"
  }]
}
</script>
<!-- Social Media Meta Tags -->
<meta property="og:title" content="<?php echo $data['name']; ?> | أفضل سعر في المغرب" />
<meta property="og:description" content="<?php echo $data['short_desc']; ?>" />
<meta property="og:image" content="<?php echo $data['active_image']; ?>" />
<meta property="og:url" content="<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" />
<meta property="og:type" content="product" />
<meta property="og:site_name" content="Abodlwahab Accessories" />
<meta property="og:locale" content="ar_MA" />

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $data['name']; ?>">
<meta name="twitter:description" content="<?php echo $data['short_desc']; ?>">
<meta name="twitter:image" content="<?php echo $data['active_image']; ?>">
<link rel="canonical" href="<?php echo "https://$_SERVER[HTTP_HOST]" . strtok($_SERVER["REQUEST_URI"], '?') . "?id=" . $product_id; ?>" />
</head>
<body>

    <!-- Breadcrumb Header -->
    <header class="breadcrumb-header">
        <div class="breadcrumb-inner">
            <div class="breadcrumb-links">
                <a href="index.php">الرئيسية</a>
 <i class="breadcrumb-separator fa-solid fa-chevron-left"></i>
                <a href="filter.php">المنتجات</a>
<i class="breadcrumb-separator fa-solid fa-chevron-left"></i>
                <span class="breadcrumb-current"><?php echo $data['name']; ?></span>
            </div>
        </div>
    </header>
        <?php include 'header.php';?>
    <?php include 'timer.php';?>

    <div class="product-layout">
        <div class="images-col">
            <div class="main-image" id="mainImgContainer" style="background-image: url('<?php echo $data['active_image']; ?>');">
                <div class="skeleton-box" id="mainSkeleton"></div>
                <!-- Wishlist removed from here -->
                <video id="mainVideoPlayer" class="main-image-video" controls controlsList="nodownload">Your browser does not support the video tag.</video>
            </div>
            
            <div class="gallery-wrapper-relative">
                <button class="scroll-arrow right" id="btnRight" onclick="scrollGallery('right')"><i class="fa-solid fa-chevron-right"></i></button>
                <div class="gallery-row" id="galleryRow">
                    <?php $index = 0; foreach($data['gallery'] as $item) { 
                        $isActive = ($index === 0) ? 'active' : '';
                        $isVideo = ($item['type'] === 'video');
                        $displaySrc = $isVideo ? $item['poster'] : $item['src'];
                        $mediaType = $isVideo ? 'video' : 'image';
                    ?>
                      <div class="gallery-item <?php echo $isActive; ?>" 
     role="img" 
     aria-label="<?php echo htmlspecialchars($data['name']) . ' - صورة ' . ($index + 1); ?>" 
     title="<?php echo htmlspecialchars($data['name']); ?>"
     style="background-image: url('<?php echo $displaySrc; ?>');"
     data-type="<?php echo $mediaType; ?>"
     data-src="<?php echo $item['src']; ?>"
     onclick="swapMedia(this)">
     <div class="skeleton-box"></div>
     <?php if($isVideo): ?><div class="video-badge"><i class="fa-solid fa-circle-play" style="font-size:32px;"></i></div><?php endif; ?>
</div>
                    <?php $index++; } ?>
                </div>
                <button class="scroll-arrow left" id="btnLeft" onclick="scrollGallery('left')"><i class="fa-solid fa-chevron-left"></i></button>
            </div>
        </div>

        <div class="details-col">
            <div class="details-content-wrapper" >
                <h1 class="product-title" title="<?php echo $data['name']; ?>"><?php echo $data['name']; ?></h1>
                <div class="price-container">
                    <div class="product-price" id="displayPrice"><?php echo $data['active_price']; ?></div>
                    <div id="regularPriceContainer" style="<?php echo $data['active_regular_price'] ? '' : 'display:none;'; ?>">
                        <span class="regular-price-text" id="displayRegPrice"><?php echo $data['active_regular_price']; ?></span>
                        <span class="discount-badge" id="displayDiscount"><i class="fa-solid fa-fire fire-icon"></i> <span id="discVal"><?php echo $data['active_discount']; ?></span>% خصم</span>
                    </div>
                </div>

                <!-- UPDATED: Category, Wishlist, Stock Row -->
                <div class="cat-stock-row">
                    <!-- 1. Wishlist -->
<?php $main_is_active = in_array($product_id, $user_wishlist_ids) ? 'active' : ''; ?>
<!-- أضفنا wishlist-icon هنا -->
<!-- زر المفضلة المحدث ليطابق نظام الهيدر -->
<button id="wishlistBtn" 
        class="main-product-wishlist wishlist-inline-btn <?php echo in_array($product_id, $user_wishlist_ids) ? 'active' : ''; ?>" 
        data-product-id="<?php echo $product_id; ?>" 
        onclick="toggleWishlist(this, event)">
     
    <!-- أيقونة القلب الفارغ (تظهر عندما لا يكون مضافاً) -->
    <i class="ph ph-heart icon-empty" style="font-size:26px; color:black;"></i> 
    
    <!-- أيقونة القلب الممتلئ (تظهر عند الإضافة) -->
    <i class="ph-fill ph-heart icon-filled" style="font-size:26px; color:#ff4b4b;"></i>
</button>

                    <!-- 2. Category -->
                    <div class="cat-text">
                        <span>التصنيف:</span> 
                        <span class="cat-value"><?php echo $data['cat_string']; ?></span>
                    </div>

                    <!-- 3. Stock -->
                    <div class="stock-text">
                        | تبقى <?php echo $data['stock_qty']; ?> من هاد المنتج
                    </div>
                </div>

                <div class="product-bio"><?php echo $data['short_desc']; ?></div>
                
                <div class="options-container">
                    <?php if($data['show_sizes']): ?>
                    <div style="width: 100%;">
                        <label class="options-label">المقاسات:</label>
                        <div class="size-options-container" id="sizesContainer">
                            <?php 
                            foreach($data['standard_sizes'] as $std_size): 
                                $is_available = in_array($std_size, $data['available_sizes']);
                                $size_class = $is_available ? 'size-box' : 'size-box disabled';
                                if ($std_size === $data['selected_size']) {
                                    $size_class .= ' selected';
                                }
                            ?>
                                <div class="<?php echo $size_class; ?>" 
                                     data-size="<?php echo $std_size; ?>"
                                     onclick="selectSize(this)"><?php echo $std_size; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="qty-color-row">
                        <!-- QUANTITY -->
                        <div class="qty-wrapper">
                            <label class="options-label">الكمية:</label>
                            <div class="qty-box">
                                <button class="qty-btn" onclick="upd(-1)">-</button>
                                <input type="text" value="1" id="qInput" class="qty-input" readonly>
                                <button class="qty-btn" onclick="upd(1)">+</button>
                            </div>
                        </div>

                        <!-- COLORS (Show Check) -->
                        <?php if($data['show_colors'] && !empty($data['available_colors'])): ?>
                        <div class="color-wrapper">
                            <label class="options-label">الألوان:</label>
                            <!-- Custom Dropdown Wrapper -->
                            <div class="custom-select-wrapper" onclick="toggleColorDropdown(event)">
                                <div class="custom-select-trigger" id="colorTrigger">
                                    <?php 
                                        $initColor = $data['selected_color'] ?: 'اختر اللون';
                                        $initHex = '';
                                        if($data['selected_color']) {
                                            $initHex = get_color_hex($data['selected_color']);
                                        }
                                    ?>
                                    <?php if($initHex): ?>
                                        <span style="display:flex; align-items:center; gap:10px;">
                                            <div class="dropdown-color-dot" style="background-color: <?php echo $initHex; ?>;"></div>
                                            <?php echo $initColor; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="display:flex; align-items:center; gap:10px;"><?php echo $initColor; ?></span>
                                    <?php endif; ?>
                                    <i class="fa-solid fa-chevron-down" style="font-size:18px; color:#888;"></i>
                                </div>
                                <div class="custom-select-options" id="colorOptions">
                                    <?php foreach($data['available_colors'] as $color_name): 
                                        $hex = get_color_hex($color_name);
                                        $isSelected = ($color_name === $data['selected_color']) ? 'selected' : '';
                                    ?>
                                        <div class="custom-option <?php echo $isSelected; ?>" 
                                             data-value="<?php echo $color_name; ?>" 
                                             data-hex="<?php echo $hex; ?>"
                                             onclick="selectColorOption(this, event)">
                                            <div class="dropdown-color-dot" style="background-color: <?php echo $hex; ?>;"></div>
                                            <span><?php echo $color_name; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <input type="hidden" id="selectedColorInput" name="color" value="<?php echo $data['selected_color']; ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="meta-data-row">
                    <div class="meta-tags">
                        <?php if(!empty($data['tags'])): foreach($data['tags'] as $tag): ?>
                            <span class="tag-item"><?php echo $tag; ?></span>
                        <?php endforeach; else: ?><span class="tag-item"></span><?php endif; ?>
                    </div>
                </div>

                <div class="actions-row">
                    <a href="#" class="btn-cart" onclick="return false;"><i class="fa-solid fa-cart-shopping"></i>إضافة للسلة</a>
                   <button class="btn-whatsapp" onclick="buyViaWhatsapp()">
    <i class="fa-brands fa-whatsapp" style="font-size: 1.2rem;"></i>
    شراء عبر واتساب 
</button>
                </div>

             <!-- Trust Badges Row -->
<div class="trust-badges-row">
    <div class="trust-item">
        <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764784692/shipping_tfebzm.png" alt="شحن مجاني">
        <span>شحن مجاني</span>
    </div>

    <div class="trust-item">
        <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764784633/premiume_av64ez.png" alt=" جودة عالية ">
        <span>جودة عالية  </span>
    </div>

    <div class="trust-item">
        <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764784673/free_r80y9m.png" alt="إرجاع مجاني ">
        <span>إرجاع مجاني </span>
    </div>
    <div class="trust-item">
        <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764784634/scheckout_ev04p8.png" alt="دفع آمن ">
        <span> دفع آمن</span>
    </div>
     <div class="trust-item ">
        <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/v1764897325/Satisfaction-3_0.5x_vyqdun.png" alt="ضمان الرضا  ">
        <span>  ضمان الرضا</span>
    </div>
</div>

            <!-- Trust Badges Row 

            its comments part
<div class="comments-section">
    

-->
   

    

   
</div>

            </div>
     
        </div>
    </div>

    <!-- TABS -->
    <div class="tabs-section-full">
        <div class="tabs-wrapper">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="openTab(event, 'desc')">وصف المنتج</button>
                <button class="tab-btn" onclick="openTab(event, 'info')">معلومات إضافية</button>
                <button class="tab-btn" onclick="openTab(event, 'faq')">الأسئلة الشائعة</button>
            </div>
            <div class="tab-content-container">
                <div id="desc" class="tab-content display-visible fade-in">
                    <div class="tab-text">
                        <h3 class="font-bold text-xl mb-4 text-black font-arabic">وصف المنتج</h3>
                        <?php echo $data['full_desc']; ?>
                    </div>
                </div>
                <div id="info" class="tab-content">
                    <table class="info-table">
                        <tbody>
                            <tr><td>رمز المنتج</td><td><?php echo $data['sku']; ?></td></tr>
                            <tr>
                                <td>العلامة التجارية</td>
                                <td><?php echo $data['brand_name']; ?></td>
                            </tr>
                            <tr><td>الوزن</td><td><?php echo $data['weight']; ?></td></tr>
                            <tr><td>الأبعاد</td><td><?php echo $data['dims']; ?></td></tr>
                            <tr><td>الخامات (Material)</td><td><?php echo $data['attr_material']; ?></td></tr>
                            <tr><td>بلد الصنع (Country)</td><td><?php echo $data['attr_country']; ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="faq" class="tab-content">
                    <?php if(!empty($data['faqs'])): ?>
                        <?php foreach($data['faqs'] as $faq): ?>
                            <div class="faq-item">
                                <button class="faq-question" onclick="toggleFaq(this)"><span><?php echo $faq['q']; ?></span><i class="fa-solid fa-chevron-down"></i></button>
                                <div class="faq-answer"><?php echo $faq['a']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 30px; text-align: center; color: #666; font-weight: 500; font-family: 'Tajawal', sans-serif;">
                            لا توجد أسئلة شائعة لهذا المنتج حالياً.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
        <?php include 'related-section.php';?>
    <?php include 'deatail-reels.html';?>
    <?php include 'footer.php';?>

    <script>
        const variationsData = <?php echo json_encode($data['variations_map']); ?>;

       // 1. تشغيل فوري بمجرد تحميل هيكل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        
        // إظهار الصور فوراً (تقليل التأخير من 1000ms إلى 50ms فقط)
        setTimeout(function() {
            document.querySelectorAll('.skeleton-box').forEach(el => {
                el.classList.add('hidden-skeleton');
                setTimeout(() => { el.style.display = 'none'; }, 500);
            });
            // تفعيل التايمر فوراً
            document.getElementById('timerBox')?.classList.add('loaded');
        }, 50);

        // تحديث أزرار الجاليري
        if(typeof updateScrollButtons === 'function') updateScrollButtons();

        // منطق الـ ZOOM (بدون انتظار تحميل كامل الصفحة)
        const mainContainer = document.getElementById('mainImgContainer');
        if (mainContainer) {
            mainContainer.addEventListener('mousemove', function(e) {
                if (window.innerWidth < 1024 || this.classList.contains('has-video')) return;
                const rect = this.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                this.style.backgroundPosition = x + "% " + y + "%";
                this.style.backgroundSize = "250%";
            });
            mainContainer.addEventListener('mouseleave', function() {
                this.style.backgroundPosition = "center center";
                this.style.backgroundSize = "cover";
            });
        }

        // إغلاق قائمة الألوان عند الضغط خارجها
        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.custom-select-wrapper');
            const options = document.getElementById('colorOptions');
            if (wrapper && !wrapper.contains(e.target) && options) {
                options.classList.remove('open');
            }
        });
    });

        function swapMedia(el) {
            const type = el.getAttribute('data-type');
            const src = el.getAttribute('data-src');
            const bgDiv = document.getElementById('mainImgBg');
            const videoEl = document.getElementById('mainVideoPlayer');
            const mainContainer = document.getElementById('mainImgContainer');
            
            document.querySelectorAll('.gallery-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            
         if(type === 'video') {
    videoEl.style.display = 'block';
    videoEl.src = src;
    
    // تشغيل آمن للفيديو لمنع انهيار باقي الأزرار
    let playPromise = videoEl.play();
    if (playPromise !== undefined) {
        playPromise.then(_ => {
            // بدأ التشغيل بنجاح
        }).catch(error => {
            console.warn("فشل تشغيل الفيديو، تم منع انهيار الصفحة.");
        });
    }
    
    mainContainer.classList.add('has-video');
    mainContainer.style.backgroundImage = 'none'; 
} else {
                videoEl.pause();
                videoEl.style.display = 'none';
                mainContainer.style.backgroundImage = "url('" + src + "')";
                mainContainer.classList.remove('has-video');
            }
        }

        const galleryRow = document.getElementById('galleryRow');
        const btnRight = document.getElementById('btnRight');
        const btnLeft = document.getElementById('btnLeft');
        function scrollGallery(direction) {
            if (direction === 'right') galleryRow.scrollBy({ left: 120, behavior: 'smooth' });
            else galleryRow.scrollBy({ left: -120, behavior: 'smooth' });
        }
        function updateScrollButtons() {
            const scrollLeftVal = Math.abs(galleryRow.scrollLeft);
            const maxScrollLeft = galleryRow.scrollWidth - galleryRow.clientWidth;
            if (scrollLeftVal < 5) btnRight.classList.add('disabled'); else btnRight.classList.remove('disabled');
            if (scrollLeftVal >= (maxScrollLeft - 5)) btnLeft.classList.add('disabled'); else btnLeft.classList.remove('disabled');
        }
        galleryRow.addEventListener('scroll', updateScrollButtons);

        function upd(v) {
            let i = document.getElementById('qInput');
            let n = parseInt(i.value) + v;
            if(n < 1) n = 1;
            i.value = n;
        }

        function selectSize(el) {
            if (el.classList.contains('disabled')) return;
            if (el.classList.contains('selected')) return;

            document.querySelectorAll('.size-box').forEach(box => box.classList.remove('selected'));
            el.classList.add('selected');
            
            const size = el.getAttribute('data-size');
            if (variationsData[size]) {
                const v = variationsData[size];
                const imgToUse = v.image ? v.image : null; 
                updateProductDisplay(v.price, v.regular_price, v.discount, imgToUse);
            }
        }

        function toggleColorDropdown(e) {
            const options = document.getElementById('colorOptions');
            options.classList.toggle('open');
        }

        function selectColorOption(el, e) {
            e.stopPropagation(); 
            const value = el.getAttribute('data-value');
            const hex = el.getAttribute('data-hex');
            
            document.getElementById('selectedColorInput').value = value;
            
            const trigger = document.getElementById('colorTrigger');
            trigger.innerHTML = `
                <span style="display:flex; align-items:center; gap:10px;">
                    <div class="dropdown-color-dot" style="background-color: ${hex};"></div>
                    ${value}
                </span>
                <i class="fa-solid fa-chevron-down" style="font-size:18px; color:#888;"></i>
            `;
            
            document.querySelectorAll('.custom-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('colorOptions').classList.remove('open');

            if (variationsData[value]) {
                const v = variationsData[value];
                const imgToUse = v.image ? v.image : null;
                updateProductDisplay(v.price, v.regular_price, v.discount, imgToUse);
            }
        }

      function updateProductDisplay(price, regPrice, discount, image) {
    // 1. تحديد العناصر التي ستتغير
    const priceEl = document.getElementById('displayPrice');
    const regContainer = document.getElementById('regularPriceContainer');
    const regText = document.getElementById('displayRegPrice');
    const discBadge = document.getElementById('displayDiscount');
    const discVal = document.getElementById('discVal');
    const mainContainer = document.getElementById('mainImgContainer');
    
    // 2. تفعيل تأثير الاختفاء (Fade Out)
    priceEl.style.opacity = '0';
    if(regContainer) regContainer.style.opacity = '0';
    if(image) mainContainer.style.opacity = '0.6'; // جعل الصورة شبه شفافة

    // 3. الانتظار قليلاً (300 ملي ثانية) ثم تغيير المحتوى
    setTimeout(function() {
        // --- تحديث الأسعار ---
        priceEl.innerHTML = price;
        
        if (regPrice) {
            regContainer.style.display = 'inline-flex';
            regText.innerHTML = regPrice;
            if (discount > 0) {
                discVal.innerHTML = discount;
                discBadge.style.display = 'inline-flex';
            } else {
                discBadge.style.display = 'none';
            }
        } else {
            regContainer.style.display = 'none';
        }

        // --- تحديث الصورة (إذا وجدت) ---
        if (image) {
            const videoEl = document.getElementById('mainVideoPlayer');
            
            videoEl.pause();
            videoEl.style.display = 'none';
            mainContainer.style.backgroundImage = "url('" + image + "')";
            mainContainer.classList.remove('has-video');
            
            // تحديث تحديد الصورة في المعرض بالأسفل
            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-src') === image) {
                    item.classList.add('active');
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            });
        }

        // 4. إعادة الظهور (Fade In)
        priceEl.style.opacity = '1';
        if(regContainer) regContainer.style.opacity = '1';
        if(image) mainContainer.style.opacity = '1';
        
    }, 300); // هذا الرقم يجب أن يطابق مدة الـ transition في الـ CSS
}

        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove('fade-in');
                tabcontent[i].classList.remove('display-visible');
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            var selectedTab = document.getElementById(tabName);
            selectedTab.classList.add('display-visible');
            setTimeout(function() {
                selectedTab.classList.add('fade-in');
            }, 50); 
            evt.currentTarget.className += " active";
        }
        function toggleFaq(btn) {
            const item = btn.parentElement;
            const isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(f => f.classList.remove('open'));
            if (!isOpen) item.classList.add('open');
        }
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
    const particleCount = 12; // عدد الفتات

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        document.body.appendChild(particle);

        // لون عشوائي
        const color = colors[Math.floor(Math.random() * colors.length)];
        particle.style.backgroundColor = color;
        
        // موقع البدء (مكان الضغطة)
        particle.style.left = x + 'px';
        particle.style.top = y + 'px';
        particle.style.opacity = '1';

        // حركة عشوائية
        const destinationX = (Math.random() - 0.5) * 100;
        const destinationY = (Math.random() - 0.5) * 100;

        // استخدام Web Animations API لأداء أفضل
        const animation = particle.animate([
            { transform: `translate(0, 0)`, opacity: 1 },
            { transform: `translate(${destinationX}px, ${destinationY}px)`, opacity: 0 }
        ], {
            duration: 600 + Math.random() * 200,
            easing: 'cubic-bezier(0, .9, .57, 1)',
            fill: 'forwards'
        });

        // حذف العنصر من الصفحة بعد انتهاء الحركة لتخفيف الضغط
        animation.onfinish = () => {
            particle.remove();
        };
    }
}


function buyViaWhatsapp() {
    // 1. جمع البيانات
    // اسم المتجر (يمكنك تغييره هنا أو جلبه من PHP)
    const storeName = "Abodlwahab Accessories"; 
    
    // رابط الصفحة الحالي
    const productUrl = window.location.href;
    
    // اسم المنتج (من عنوان الصفحة أو عنصر H1)
    const productName = document.querySelector('.product-title').innerText.trim();
    
    // السعر الحالي الظاهر
    const price = document.querySelector('#displayPrice').innerText.trim();
    
    // المقاس المختار
    let size = "غير محدد";
    const selectedSizeEl = document.querySelector('.size-box.selected');
    if(selectedSizeEl) {
        size = selectedSizeEl.getAttribute('data-size');
    }

    // اللون المختار (نأخذه من الـ input المخفي الذي قمت بعمله في الكود السابق)
    let color = document.getElementById('selectedColorInput').value;
    if(!color) color = "الافتراضي";

    // 2. تجهيز الرسالة
    // ملاحظة: \n تعني سطر جديد
    const message = `مرحبا ${storeName} انا مهتم بهاد المنتج :
رابط المنتج: ${productUrl}
اسم المنتج: ${productName}
سعر المنتج: ${price}
مقاس المنتج: ${size}
لون المنتج: ${color}`;

    // 3. تشفير الرسالة لتناسب الرابط
    const encodedMessage = encodeURIComponent(message);

    // 4. رقم الهاتف (ضعه بمفتاح الدولة بدون + أو 00)
    // مثال: 212xxxxxxxxx
const phoneNumber = "<?php echo $whatsapp_number; ?>";
    // 5. فتح الواتساب
    const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
    window.open(whatsappUrl, '_blank');
}
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-cart')) {
        e.preventDefault();
        
        const pId = new URLSearchParams(window.location.search).get('id');
        const qty = document.getElementById('qInput')?.value || 1;
        
        // جلب اللون والمقاس المختارين
        const selectedSize = document.querySelector('.size-box.selected')?.getAttribute('data-size') || '';
        const selectedColor = document.getElementById('selectedColorInput')?.value || '';
        
        let vId = 0;
        let attrString = "";

        // البحث عن الـ Variation ID بناءً على الاختيار
        // ملاحظة: variationsData هو الكائن الذي قمت بتعريفه أنت في PHP
        if (typeof variationsData !== 'undefined') {
            // نحاول إيجاد الـ Variation بالمقاس أو اللون
            const variation = variationsData[selectedSize] || variationsData[selectedColor];
            if (variation) {
                // ملاحظة: يجب التأكد أن PHP يرسل الـ ID داخل مصفوفة variations_map
                // إذا لم يكن موجوداً، سنرسل المنتج كمنتج بسيط
                vId = variation.id || 0; 
            }
        }

        if (selectedSize) attrString += "المقاس: " + selectedSize + " ";
        if (selectedColor) attrString += "اللون: " + selectedColor;

        updateCart(pId, 'add', qty, vId, attrString);
        if(typeof openCart === 'function') openCart();
    }
});
    </script>
</body>
</html>
