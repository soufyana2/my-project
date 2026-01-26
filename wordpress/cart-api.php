<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);
$variation_id = intval($_POST['variation_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$size = $_POST['size'] ?? null;
$color = $_POST['color'] ?? null;

// مفتاح فريد للزوار لتمييز المنتجات ذات المقاسات المختلفة
$cart_key = $product_id . '_' . $variation_id . '_' . $size . '_' . $color;

if ($action == 'add') {
    if ($isLoggedIn) {
        // منطق المسجلين (Database)
        $stmt = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ? AND variation_id = ? AND selected_size = ? AND selected_color = ?");
        $stmt->execute([$user_id, $product_id, $variation_id, $size, $color]);
        $existing = $stmt->fetch();

        if ($existing) {
            $new_qty = $existing['quantity'] + $quantity;
            $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ?")->execute([$new_qty, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO user_cart (user_id, product_id, variation_id, quantity, selected_size, selected_color) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$user_id, $product_id, $variation_id, $quantity, $size, $color]);
        }
    } else {
        // منطق الزوار (Session)
        if (!isset($_SESSION['guest_cart'])) { $_SESSION['guest_cart'] = []; }
        if (isset($_SESSION['guest_cart'][$cart_key])) {
            $_SESSION['guest_cart'][$cart_key]['quantity'] += $quantity;
        } else {
            $_SESSION['guest_cart'][$cart_key] = [
                'id' => $cart_key,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'selected_size' => $size,
                'selected_color' => $color
            ];
        }
    }
    echo json_encode(['status' => 'success']);
} 
elseif ($action == 'remove') {
    $cart_id = $_POST['cart_id']; // في حالة الزائر هو cart_key
    if ($isLoggedIn) {
        $pdo->prepare("DELETE FROM user_cart WHERE id = ? AND user_id = ?")->execute([$cart_id, $user_id]);
    } else {
        unset($_SESSION['guest_cart'][$cart_id]);
    }
    echo json_encode(['status' => 'success']);
}
elseif ($action == 'update_qty') {
    $cart_id = $_POST['cart_id'];
    $new_qty = max(1, intval($_POST['new_qty']));
    if ($isLoggedIn) {
        $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$new_qty, $cart_id, $user_id]);
    } else {
        if(isset($_SESSION['guest_cart'][$cart_id])) {
            $_SESSION['guest_cart'][$cart_id]['quantity'] = $new_qty;
        }
    }
    echo json_encode(['status' => 'success']);
}