<?php
// filter_ajax.php
require_once 'logger_setup.php';
ini_set('display_errors', 0);
session_start();
// --- أضف هذا الجزء هنا ---
require_once 'db.php'; 
$user_wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
// ------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit(json_encode(['error' => 'Invalid Request']));

require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;

try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
    $dotenv->load();
} catch (Exception $e) { exit(); }

$woocommerce = new Client(
    $_ENV['wordpress_url'],
    $_ENV['consumer_key'],
    $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 40]
);

// ==========================================
// 1. خرائط الترجمة (Matching Maps)
// هنا نقوم بربط الـ Slug الإنجليزي بالاسم العربي في متجرك
// ==========================================

// خريطة الألوان: (القيمة من HTML => القيمة في المتجر)
$color_map = [
    'red'    => 'أحمر',
    'black'  => 'أسود',
    'white'  => 'أبيض',
    'blue'   => 'أزرق',
    'green'  => 'أخضر',
    'gold'   => 'ذهبي',
    'silver' => 'فضي',
    'beige'  => 'بيج',
    'brown'  => 'بني',
    'yellow' => 'أصفر',
    'grey'   => 'رمادي',
    'purple' => 'بنفسجي',
    'orange' => 'برتقالي',
    'navy'   => 'كحلي',
    'turquoise' => 'تركوازي'
];

// خريطة الأحجام (مل): (القيمة من HTML => القيمة في المتجر)
// ملاحظة: تأكد هل تكتبها "30 مل" أم "30ml" في المنتجات. وضعت الاحتمالين
$volume_map = [
    '30ml'  => ['30 مل', '30ml', '30ML'],
    '50ml'  => ['50 مل', '50ml', '50ML'],
    '75ml'  => ['75 مل', '75ml', '75ML'],
    '100ml' => ['100 مل', '100ml', '100ML'],
    '150ml' => ['150 مل', '150ml', '150ML'],
    '200ml' => ['200 مل', '200ml', '200ML'],
];

// ==========================================

$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
// --- التحديث المطلوب (Update) ---
// استبدل السطر القديم بهذا السطر الآمن الذي يعمل بدون وردبريس
$search = isset($_POST['search']) ? htmlspecialchars(strip_tags(trim($_POST['search'])), ENT_QUOTES, 'UTF-8') : '';
$per_page = 10; 

$params = [
    'status' => 'publish',
    'page' => $page,
    'per_page' => $per_page,
    'order' => 'desc',
    'orderby' => 'date'
];

// --- أضف هذا الجزء هنا ليفعل البحث الذكي ---
// --- منطق البحث الشامل والمصحح (تحديث) ---
if (!empty($search)) {
    try {
        // البحث عن الوسوم أولاً (لدعم كلمات مثل 3orod)
        $tags_found = $woocommerce->get('products/tags', ['search' => $search]);
        
        if (!empty($tags_found)) {
            $tag_ids = [];
            foreach($tags_found as $t) { $tag_ids[] = $t->id; }
            $params['tag'] = implode(',', $tag_ids);
        } else {
            // إذا لم يجد وسماً، يبحث في الاسم
            $params['search'] = $search;
        }
    } catch (Exception $e) {
        $params['search'] = $search;
    }
    // ملاحظة: عند البحث، نتجاهل التصنيفات المختارة لضمان ظهور النتائج من أي قسم
} else {
    // لا يتم تفعيل فلترة التصنيفات إلا إذا كان حقل البحث فارغاً
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        $cats = array_unique(array_filter($_POST['categories'], function($v) { return $v !== 'all'; }));
        if (!empty($cats)) {
            $params['category'] = implode(',', $cats);
        }
    }
}
if (isset($_POST['categories']) && is_array($_POST['categories'])) {
    $cats = array_unique(array_filter($_POST['categories'], function($v) { return $v !== 'all'; }));
    if (!empty($cats)) {
        $params['category'] = implode(',', $cats);
    }
}

if (isset($_POST['max_price']) && is_numeric($_POST['max_price'])) {
    $params['max_price'] = $_POST['max_price'];
    $params['min_price'] = '0'; 
}

$selected_sizes = isset($_POST['sizes']) ? array_unique($_POST['sizes']) : [];
$selected_colors = isset($_POST['colors']) ? array_unique($_POST['colors']) : [];
$selected_volumes = isset($_POST['volumes']) ? array_unique($_POST['volumes']) : [];

try {
// تحديث: إضافة متغير $search لضمان عدم تداخل نتائج البحث في الكاش
$cacheKey = 'ajax_res_' . md5(json_encode($params) . $search . json_encode($selected_sizes) . json_encode($selected_colors) . json_encode($selected_volumes));    $cacheFile = __DIR__ . '/cache/' . $cacheKey . '.json';
    
    // --- منطق العدد الكلي (Total Store Count) ---
    $totalStoreFile = __DIR__ . '/cache/store_total_count.txt';
    $total_store_products = 0;

    if (file_exists($totalStoreFile) && (time() - filemtime($totalStoreFile) < 3600)) {
        $total_store_products = (int)file_get_contents($totalStoreFile);
    } else {
        // محاولة جلب العدد
        $woocommerce->get('products', ['status' => 'publish', 'per_page' => 1]);
        $headers = $woocommerce->http->getResponse()->getHeaders();
        
        // إصلاح مشكلة الصفر: التحقق من الهيدر بجميع حالات الأحرف
        if (isset($headers['x-wp-total'])) {
            $total_store_products = (int)$headers['x-wp-total'];
        } elseif (isset($headers['X-WP-Total'])) {
            $total_store_products = (int)$headers['X-WP-Total'];
        } else {
            // حل بديل أخير إذا السيرفر يحجب الهيدر: جلب تقرير بسيط
            // (هذه الخطوة قد تكون ثقيلة قليلاً لكنها تضمن الرقم)
            try {
                $reports = $woocommerce->get('reports/products/totals');
                foreach($reports as $rep) {
                    if($rep->slug == 'publish') $total_store_products = $rep->total;
                }
            } catch(Exception $ex) { $total_store_products = 0; }
        }

        if(!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);
        file_put_contents($totalStoreFile, $total_store_products);
    }

    $products = [];

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 600)) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        $products = $cachedData['products'];
    } else {
        $products = $woocommerce->get('products', $params);
        
        // ==========================================
        // منطق الفلترة المتقدم (مع الترجمة)
        // ==========================================
        if ((!empty($selected_sizes) || !empty($selected_colors) || !empty($selected_volumes)) && !empty($products)) {
            $products = array_filter($products, function($p) use ($selected_sizes, $selected_colors, $selected_volumes, $color_map, $volume_map) {
                
                $pass_size = empty($selected_sizes);
                $pass_color = empty($selected_colors);
                $pass_volume = empty($selected_volumes);

                // تجهيز قيم البحث المترجمة
                $search_colors = [];
                foreach($selected_colors as $sc) {
                    $search_colors[] = $sc; // القيمة الأصلية (مثلا red)
                    if(isset($color_map[$sc])) $search_colors[] = $color_map[$sc]; // القيمة العربية (أحمر)
                }

              $search_volumes = [];
foreach($selected_volumes as $sv) {
    if(isset($volume_map[$sv])) {
        // تنظيف القيم المترجمة (مثل 30ml و 30 مل)
        foreach($volume_map[$sv] as $v) {
            $search_volumes[] = str_replace(' ', '', strtolower($v));
        }
    } else {
        $search_volumes[] = str_replace(' ', '', strtolower($sv));
    }
}

                // التكرار داخل سمات المنتج
                foreach ($p->attributes as $attr) {
                    // توحيد الاسم والـ slug للمقارنة (إزالة الرموز)
                    $slug_raw = urldecode($attr->slug);
                    $name_raw = $attr->name;
                    
                    // 1. فحص الألوان
                    // نبحث عن أي سمة اسمها "اللون" أو "color" أو "pa_color"
                    if (!empty($selected_colors)) {
                        if ($slug_raw == 'اللون' || $slug_raw == 'pa_color' || $name_raw == 'اللون') {
                            if(!empty(array_intersect($search_colors, $attr->options))) {
                                $pass_color = true;
                            }
                        }
                    }

                    // 2. فحص المقاسات (Sizes)
                    // نبحث عن "المقاس" أو "size" أو "pa_size"
                    if (!empty($selected_sizes)) {
                        if ($slug_raw == 'المقاس' || $slug_raw == 'pa_size' || $name_raw == 'المقاس') {
                            // في المقاسات عادة لا نحتاج ترجمة (S, M, L, XL) تطابق ما في المتجر
                            // نحول القيمتين لـ UpperCase للتأكد (s => S)
                            $options_upper = array_map('strtoupper', $attr->options);
                            $selected_upper = array_map('strtoupper', $selected_sizes);
                            
                            if(!empty(array_intersect($selected_upper, $options_upper))) {
                                $pass_size = true;
                            }
                        }
                    }

                    // 3. فحص الأحجام (Volumes - ml)
                    // طلبت أن تكون داخل سمة "المقاس" أيضاً
                  // 3. فحص الأحجام (Volumes - ml)
if (!empty($selected_volumes)) {
    // نبحث عن أي سمة تحتوي على كلمة "المقاس" (لتشمل المقاسات) أو "الحجم"
    if (strpos($slug_raw, 'size') !== false || strpos($name_raw, 'المقاس') !== false || strpos($name_raw, 'الحجم') !== false) {
        
        // تنظيف الخيارات القادمة من المتجر (حذف المسافات وتحويلها لصغير) قبل المقارنة
        $cleaned_product_options = array_map(function($opt) {
            return str_replace(' ', '', strtolower($opt));
        }, $attr->options);

        // الآن نقارن القيم المنظفة ببعضها
        if(!empty(array_intersect($search_volumes, $cleaned_product_options))) {
            $pass_volume = true;
        }
    }
}
                }
                
                return $pass_size && $pass_color && $pass_volume;
            });
            $products = array_values($products);
        }
        
        if(!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);
        file_put_contents($cacheFile, json_encode(['products' => $products]));
    }

} catch (Exception $e) {
    $products = [];
    $total_store_products = 0;
}

function renderCardHTML($product) {
        global $user_wishlist_ids; // لجلب المصفوفة من الخارج

    if(is_object($product)) $product = json_decode(json_encode($product), true);
    
    $image_src = !empty($product['images'][0]['src']) ? $product['images'][0]['src'] : "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=";
    $hover_image_src = (isset($product['images'][1]) && !empty($product['images'][1]['src'])) ? $product['images'][1]['src'] : $image_src;
    $category_name = !empty($product['categories'][0]['name']) ? $product['categories'][0]['name'] : "غير مصنف";
    $product_title = $product['name'];
    $price = $product['price'] ? number_format($product['price'], 2) . ' د.م' : '';
    $regular_price = $product['regular_price'] && ($product['regular_price'] > $product['price']) ? number_format($product['regular_price'], 2) . ' د.م' : '';
    $product_link = 'product.php?id=' . $product['id'];
    $is_active = in_array($product['id'], $user_wishlist_ids) ? 'active' : '';

    ob_start();
    ?>
    <div class="product-card cursor-pointer group card-load-animation fade-in-up" style="--card-bg-color: var(--card-one-bg);">
        <div class="relative flex-grow flex flex-col">
            <a href="<?php echo $product_link; ?>" class="block">
                <div class="image-container skeleton-pending" 
                     data-main-image-src="<?php echo $image_src; ?>" 
                     data-hover-image-src="<?php echo $hover_image_src; ?>">
                    <img loading="lazy" src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product_title); ?>" class="main-product-image">
                    <img loading="lazy" src="<?php echo $hover_image_src; ?>" alt="<?php echo htmlspecialchars($product_title); ?> Hover" class="hover-product-image">
                    
                      <!-- تحديث الزر هنا: أضفنا data-product-id والكلاس النشط -->
                    <button class="wishlist-icon <?php echo $is_active; ?>" 
                            data-product-id="<?php echo $product['id']; ?>" 
                            onclick="toggleWishlist(this, event)">
                        <i class="fa-regular fa-heart icon-empty"></i>
                        <i class="fa-solid fa-heart icon-filled"></i>
                    </button>
                </div>
            </a>
            <div class="mt-auto w-full info-part flex flex-col items-start text-right">
                <p class="text-xs font-bold underline category-gold-beige arabic-font"><?php echo htmlspecialchars($category_name); ?></p>
                <h3 class="product-title text-sm font-bold mt-1 arabic-font text-gray-800"><?php echo htmlspecialchars($product_title); ?></h3>
                <div class="product-price-small flex gap-1 items-center">
                    <p class="text-sm font-bold arabic-font" style="color: black !important;"><?php echo $price; ?></p>
                    <?php if ($regular_price): ?>
                        <p class="text-xs text-gray-400 line-through arabic-font"><?php echo $regular_price; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

$html = '';
if (!empty($products)) {
    foreach ($products as $product) {
        $html .= renderCardHTML($product);
    }
}

$has_more = (count($products) >= $per_page); 

echo json_encode([
    'html' => $html, 
    'has_more' => $has_more,
    'total_store_products' => $total_store_products 
]);
?>