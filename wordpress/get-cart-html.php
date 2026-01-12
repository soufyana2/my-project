<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Dotenv\Dotenv;
try {
    if (file_exists(__DIR__ . '/apikeys.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
        $dotenv->load();
    }
} catch (Exception $e) {}

$woocommerce = new Automattic\WooCommerce\Client(
    $_ENV['wordpress_url'], 
    $_ENV['consumer_key'], 
    $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 30]
);

$user_id = $_SESSION['user_id'] ?? null;
$items = [];

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $items = $_SESSION['guest_cart'] ?? [];
}

if (empty($items)) {
    echo '<div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/empty_cart_abbrh8.png" class="empty-cart-image mx-auto opacity-80 mb-4 animate-pulse empty-cart-icon-fix empty-menu-icon">
            <h3 style="font-family: \'Cairo\'; font-weight: 700;" class="text-xl mb-2">سلة التسوق فارغة</h3>
          </div>';
    exit;
}

$product_ids = array_unique(array_column($items, 'product_id'));
$p_map = [];

if (!empty($product_ids)) {
    try {
        $products_data = $woocommerce->get('products', ['include' => $product_ids, 'per_page' => 100]);
        foreach ($products_data as $pr) {
            $p_map[$pr->id] = $pr;
        }
    } catch (Exception $e) {}
}

foreach ($items as $item): 
    $p = $p_map[$item['product_id']] ?? null;
    if (!$p) continue; 
    
    $product_name = $p->name; 
    $img = $p->images[0]->src ?? '';

    $attr_display = "";
    if (!empty($item['attributes'])) {
        $attrs = explode(' ', $item['attributes']);
        foreach ($attrs as $a) {
            if (trim($a) !== "") {
                $attr_display .= '<span class="px-2 py-0.5 bg-gray-100 border border-gray-200 text-[10px] font-bold text-gray-600 rounded-md">' . htmlspecialchars($a) . '</span>';
            }
        }
    }
?>
    <div class="product-card-professional group p-4 flex flex-row items-start gap-4 bg-white border-b border-gray-50" style="direction: rtl;">
        <div class="w-24 h-32 flex-shrink-0 overflow-hidden relative rounded-lg border border-gray-100">
            <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?= $img ?>');"></div>
            <button onclick="updateCart(<?= $item['product_id'] ?>, 'remove', 0, <?= $item['variation_id'] ?>, '<?= addslashes($item['attributes']) ?>')" class="remove-product-icon active absolute top-1.5 left-1.5 w-7 h-7 bg-white shadow-md text-red-500 rounded-full flex items-center justify-center">
                <i class="ph ph-trash text-base"></i>
            </button>
        </div>

        <div class="flex flex-col flex-grow py-1 text-right">
            <h4 class="font-bold text-sm text-gray-900 leading-tight mb-1" style="font-family: 'Cairo';"><?= htmlspecialchars($product_name) ?></h4>
            <div class="flex flex-wrap gap-1.5 my-2"><?= $attr_display ?></div>
            <div class="flex justify-between items-end mt-auto">
                <span class="font-bold text-base text-accent" style="color: #C8A95A;"><?= $p->price ?> د.م</span>
                <div class="flex items-center bg-gray-50 rounded-full p-1" style="direction: ltr; gap: 8px; border: 1px solid #f1f5f9;">
                    <button onclick="updateCart(<?= $item['product_id'] ?>, 'update', <?= $item['quantity'] + 1 ?>, <?= $item['variation_id'] ?>, '<?= addslashes($item['attributes']) ?>')" class="w-7 h-7 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-600">+</button>
                    <span class="text-xs font-bold text-gray-800"><?= $item['quantity'] ?></span>
                    <button onclick="updateCart(<?= $item['product_id'] ?>, 'update', <?= $item['quantity'] - 1 ?>, <?= $item['variation_id'] ?>, '<?= addslashes($item['attributes']) ?>')" class="w-7 h-7 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-600">-</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
