<?php
define('DB_NAME', 'stores');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Keep admin URL generation stable for local Woo dashboard.
define('WP_HOME', 'http://localhost:8001');
define('WP_SITEURL', 'http://localhost:8001');
define('COOKIEPATH', '/');
define('SITECOOKIEPATH', '/');
define('ADMIN_COOKIE_PATH', '/');
define('PLUGINS_COOKIE_PATH', '/');

$table_prefix = 'wp_';

define('WP_DEBUG', false);

if (!defined('ABSPATH')) {
  define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';

