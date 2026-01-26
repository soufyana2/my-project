<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

try {
    // التصحيح هنا: نخرج مستوى واحد فقط من wordpress إلى my-project
    $root = dirname(__DIR__); 

    // التأكد من اسم الملف، إذا كان اسمه .env نستخدم الحالة الأولى
    if (file_exists($root . '/.env')) {
        $dotenv = Dotenv::createImmutable($root);
        $dotenv->load();
    } 
    // إذا كان اسمه apikeys.env نستخدم هذه الحالة
    elseif (file_exists($root . '/apikeys.env')) {
        $dotenv = Dotenv::createImmutable($root, 'apikeys.env');
        $dotenv->load();
    }
} catch (Exception $e) {
    // خطأ في تحميل ملف البيئة
}
$woocommerce = new Automattic\WooCommerce\Client(
    $_ENV['wordpress_url'], $_ENV['consumer_key'], $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 30]
);

$isLoggedIn = isset($_SESSION['user_id']);
$cart_items = [];

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    $cart_items = isset($_SESSION['guest_cart']) ? $_SESSION['guest_cart'] : [];
}
    // حساب العدد الإجمالي للمنتجات من قاعدة البيانات مباشرة
$total_db_qty = 0;
foreach ($cart_items as $ci) { $total_db_qty += $ci['quantity']; }
// إرسال العدد في حقل مخفي ليقرأه الجافا سكريبت
echo '<input type="hidden" id="db-cart-total-count" value="' . $total_db_qty . '">';
// --- حالة السلة فارغة ---
if (empty($cart_items)) {
    echo '<div class="text-center py-20">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/v1768252470/empty_cart_abbrh8.png" class="w-20 mx-auto mb-4">
            <h3 class="text-gray-500 font-bold" style="font-family:\'Cairo\';">سلتك فارغة حالياً</h3>
          </div>';
    echo '<input type="hidden" id="hidden-cart-total" value="0.00 د.م">';
    exit;
}

$product_ids = array_column($cart_items, 'product_id');
try {
    $all_products = $woocommerce->get('products', ['include' => array_unique($product_ids), 'per_page' => 100]);
    $products_map = [];
    foreach($all_products as $p) { $products_map[$p->id] = $p; }

    $total_price = 0;
    echo '<div class="space-y-4">';
    foreach ($cart_items as $item) {
        $p = $products_map[$item['product_id']] ?? null;
        if (!$p) continue;

        $price = floatval($p->price);
        $subtotal = $price * $item['quantity'];
        $total_price += $subtotal;
        $img = !empty($p->images) ? $p->images[0]->src : '';
        // معرف السطر (cart_id للمسجل أو cart_key للزائر)
        $row_id = $isLoggedIn ? $item['id'] : $item['id']; 
        ?>
        <div class="cart-item-row product-card-professional p-4 flex flex-row items-start gap-4 bg-white border border-gray-100 rounded-lg relative" data-p-id="<?php echo $item['product_id']; ?>"data-qty="<?php echo $item['quantity']; ?>">
            <div class="w-24 h-32 flex-shrink-0 overflow-hidden relative rounded-lg border border-gray-100">
                <img src="<?php echo $img; ?>" class="w-full h-full object-cover">
                <button onclick="removeFromCart('<?php echo $row_id; ?>', this)" class="remove-product-icon active absolute top-1.5 left-1.5 w-7 h-7 bg-white/90 shadow-sm text-black rounded-full flex items-center justify-center">
                    <i class="ph ph-trash text-base"></i>
                </button>
            </div>
            <div class="flex flex-col flex-grow text-right">
                <h4 class="font-bold text-sm text-gray-900 leading-tight mb-1"><?php echo $p->name; ?></h4>
                <p class="category-text text-xs font-semibold mb-2" style="color: #C8A95A !important;">
                    <?php if(!empty($item['selected_size'])) echo 'المقاس: '.$item['selected_size']; ?>
                    <?php if(!empty($item['selected_color'])) echo ' | اللون: '.$item['selected_color']; ?>
                </p>
                <div class="flex items-center justify-between mt-auto">
             <div class="flex items-center border border-gray-200 px-2 py-1" style="border-radius: 50px !important;">
    <!-- زر الزيادة: نرسل رقم 1 -->
    <button onclick="updateCartQty('<?php echo $row_id; ?>', 1, this)" class="ph ph-plus text-xs px-1"></button>
    
    <span class="px-2 text-sm font-bold"><?php echo $item['quantity']; ?></span>
    
    <!-- زر النقصان: نرسل رقم -1 -->
    <button onclick="updateCartQty('<?php echo $row_id; ?>', -1, this)" class="ph ph-minus text-xs px-1"></button>
</div>
                    <span class="font-bold text-base text-gray-900"><?php echo number_format($price, 2); ?> د.م</span>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    echo '<input type="hidden" id="hidden-cart-total" value="' . number_format($total_price, 2) . ' د.م">';
} catch (Exception $e) { echo 'خطأ في جلب البيانات'; }