<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['status' => 'need_login']); exit; }

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $row = $stmt->fetch();

        if ($row) {
            $pdo->prepare("DELETE FROM user_wishlist WHERE id = ?")->execute([$row['id']]);
            $action = 'removed';
        } else {
            // نترك الـ id فارغ لكي تأخذه القاعدة تلقائياً AUTO_INCREMENT
            $pdo->prepare("INSERT INTO user_wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
            $action = 'added';
        }

        unset($_SESSION['wishlist_cache']); 
        $count = $pdo->prepare("SELECT COUNT(*) FROM user_wishlist WHERE user_id = ?");
        $count->execute([$user_id]);
        
        echo json_encode(['status' => 'success', 'action' => $action, 'count' => $count->fetchColumn()]);
    } catch (Exception $e) { echo json_encode(['status' => 'error']); }
}