<?php
/**
 * Abdolwahab Accessories & Parfums - Cart API v2.1
 * Fixes: Speed optimization, Product Links for WhatsApp, CSRF Consistency
 */

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'db.php';
require_once 'functions.php'; 
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;

check_csrf();

// حماية Rate Limit (تم تخفيفها قليلاً لضمان عدم حظر المستخدم السريع)
if (!isset($_SESSION['cart_api_rate'])) {
    $_SESSION['cart_api_rate'] = ['count' => 0, 'time' => time()];
}
if (time() - $_SESSION['cart_api_rate']['time'] < 20) {
    if ($_SESSION['cart_api_rate']['count'] > 40) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى الانتظار قليلاً.']);
        exit;
    }
    $_SESSION['cart_api_rate']['count']++;
} else {
    $_SESSION['cart_api_rate'] = ['count' => 1, 'time' => time()];
}

try {
    if (file_exists(__DIR__ . '/apikeys.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
        $dotenv->load();
    }
} catch (Exception $e) {}

$woocommerce = new Client(
    $_ENV['wordpress_url'], 
    $_ENV['consumer_key'], 
    $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 30]
);

$action = $_POST['action'] ?? 'fetch';
$user_id = $_SESSION['user_id'] ?? null;

try {
    if (in_array($action, ['add', 'update', 'remove'])) {
        $p_id = intval($_POST['product_id']);
        $v_id = intval($_POST['variation_id'] ?? 0);
        $qty  = intval($_POST['quantity'] ?? 1);
        $attr = trim($_POST['attributes'] ?? '');

        if ($action === 'add' || $action === 'update') {
            if ($user_id) {
                $stmt = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ? AND variation_id = ? AND attributes = ?");
                $stmt->execute([$user_id, $p_id, $v_id, $attr]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $new_qty = ($action === 'add') ? ($existing['quantity'] + $qty) : $qty;
                    if ($new_qty <= 0) {
                        $pdo->prepare("DELETE FROM user_cart WHERE id = ?")->execute([$existing['id']]);
                    } else {
                        $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ?")->execute([$new_qty, $existing['id']]);
                    }
                } else {
                    if ($qty > 0) {
                        $pdo->prepare("INSERT INTO user_cart (user_id, product_id, variation_id, quantity, attributes) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$user_id, $p_id, $v_id, $qty, $attr]);
                    }
                }
            } else {
                if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
                $found = false;
                foreach ($_SESSION['guest_cart'] as &$item) {
                    if ($item['product_id'] == $p_id && $item['variation_id'] == $v_id && $item['attributes'] === $attr) {
                        $item['quantity'] = ($action === 'add') ? ($item['quantity'] + $qty) : $qty;
                        $found = true; break;
                    }
                }
                if (!$found && $qty > 0) {
                    $_SESSION['guest_cart'][] = ['product_id' => $p_id, 'variation_id' => $v_id, 'quantity' => $qty, 'attributes' => $attr];
                }
                $_SESSION['guest_cart'] = array_values(array_filter($_SESSION['guest_cart'], fn($i) => $i['quantity'] > 0));
            }
        } elseif ($action === 'remove') {
            if ($user_id) {
                $pdo->prepare("DELETE FROM user_cart WHERE user_id = ? AND product_id = ? AND variation_id = ? AND attributes = ?")
                    ->execute([$user_id, $p_id, $v_id, $attr]);
            } else {
                $_SESSION['guest_cart'] = array_values(array_filter($_SESSION['guest_cart'] ?? [], 
                    fn($i) => !($i['product_id'] == $p_id && $i['variation_id'] == $v_id && $i['attributes'] === $attr)
                ));
            }
        }
    }

    $items = [];
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $items = $_SESSION['guest_cart'] ?? [];
    }

    $totals = ['subtotal' => 0, 'count' => 0, 'tax' => 0, 'shipping' => 0, 'total' => 0, 'items_list' => []];
    $product_ids = array_unique(array_column($items, 'product_id'));

    if (!empty($product_ids)) {
        $raw_products = $woocommerce->get('products', ['include' => $product_ids, 'per_page' => 100]);
        $p_map = [];
        foreach ($raw_products as $rp) {
            $p_map[$rp->id] = $rp;
        }

        foreach ($items as $item) {
            $p = $p_map[$item['product_id']] ?? null;
            if (!$p) continue;

            $price = floatval($p->price);
            $qty = intval($item['quantity']);
            
            $totals['subtotal'] += ($price * $qty);
            $totals['count'] += $qty;
            
            $totals['items_list'][] = [
                'name' => $p->name,
                'qty' => $qty,
                'price' => $price,
                'attr' => $item['attributes'],
                'link' => $p->permalink // إضافة رابط المنتج للواتساب
            ];
        }
    }

    $totals['total'] = number_format($totals['subtotal'], 2, '.', '');
    $totals['tax'] = "0.00";
    $totals['shipping'] = "0.00";

    $_SESSION['cart_cache'] = $totals;

    echo json_encode([
        'status' => 'success', 
        'data' => $totals, 
        'new_csrf' => $_SESSION['csrf_token']
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في معالجة السلة']);
}

ob_end_flush();