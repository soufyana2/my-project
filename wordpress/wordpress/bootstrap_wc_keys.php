<?php
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-admin/includes/plugin.php';
if (!is_blog_installed()) {
  require_once __DIR__ . '/wp-admin/includes/upgrade.php';
  $admin_email = 'admin@example.com';
  $admin_user = 'admin';
  $admin_pass = 'Admin123!';
  wp_install('My Store', $admin_user, $admin_email, true, '', $admin_pass);
}
$user = get_user_by('login', 'admin');
if (!$user) {
  $uid = wp_create_user('admin', 'Admin123!', 'admin@example.com');
  $user = get_user_by('id', $uid);
  $user->set_role('administrator');
}
if (!is_plugin_active('woocommerce/woocommerce.php')) {
  activate_plugin('woocommerce/woocommerce.php');
}
if (!function_exists('wc_api_hash')) {
  if (file_exists(__DIR__ . '/wp-content/plugins/woocommerce/includes/wc-api-functions.php')) {
    require_once __DIR__ . '/wp-content/plugins/woocommerce/includes/wc-api-functions.php';
  }
}
$ck = 'ck_' . wp_generate_password(38, false, false);
$cs = 'cs_' . wp_generate_password(38, false, false);
$hash = function_exists('wc_api_hash') ? wc_api_hash($ck) : hash('sha256', $ck);
global $wpdb;
$table = $wpdb->prefix . 'woocommerce_api_keys';
$wpdb->insert($table, [
  'user_id' => $user->ID,
  'description' => 'Auto-generated',
  'permissions' => 'read_write',
  'consumer_key' => $hash,
  'consumer_secret' => $cs,
  'truncated_key' => substr($ck, -7),
]);
$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'apikeys.env';
$contents = "consumer_key={$ck}\nsecret_key={$cs}\nwordpress_url=http://localhost:8001\n";
file_put_contents($envPath, $contents);
echo "CONSUMER_KEY={$ck}\n";
echo "CONSUMER_SECRET={$cs}\n";
echo "UPDATED_FILE={$envPath}\n";

