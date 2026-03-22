<?php
/**
 * Ensure admin links always resolve under /wp-admin/.
 * This protects Woo dashboard links when a plugin/theme builds malformed admin URLs.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tt_fix_admin_url_path')) {
    function tt_fix_admin_url_path($url)
    {
        $parts = wp_parse_url((string) $url);
        if (!is_array($parts) || empty($parts['path'])) {
            return $url;
        }

        $currentPath = (string) $parts['path'];
        $lowerPath = strtolower($currentPath);

        // Already correct.
        if (strpos($lowerPath, '/wp-admin/') === 0) {
            return $url;
        }

        // Rewrite only admin-like PHP targets.
        $filename = strtolower((string) basename($lowerPath));
        if (!preg_match('/^[a-z0-9\\-]+\\.php$/', $filename)) {
            return $url;
        }

        $adminPath = '/wp-admin/' . ltrim($currentPath, '/');
        $rebuilt = '';

        if (!empty($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }
        if (!empty($parts['host'])) {
            $rebuilt .= $parts['host'];
        }
        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $adminPath;

        if (!empty($parts['query'])) {
            $rebuilt .= '?' . $parts['query'];
        }
        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }
}

// Fix all core admin URL generators.
add_filter('admin_url', 'tt_fix_admin_url_path', 999, 1);
add_filter('self_admin_url', 'tt_fix_admin_url_path', 999, 1);
add_filter('network_admin_url', 'tt_fix_admin_url_path', 999, 1);
add_filter('user_admin_url', 'tt_fix_admin_url_path', 999, 1);

// Canonical redirect if request reaches "/edit.php" (or similar) at site root.
add_action('init', function () {
    if (is_admin()) {
        return;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($uri === '') {
        return;
    }

    $path = parse_url($uri, PHP_URL_PATH);
    $path = is_string($path) ? $path : '';

    if (!preg_match('#^/([a-z0-9\\-]+\\.php)$#i', $path, $m)) {
        return;
    }

    $filename = $m[1];
    $adminFile = ABSPATH . 'wp-admin/' . $filename;
    if (!is_file($adminFile)) {
        return;
    }

    $target = admin_url($filename);
    $query = parse_url($uri, PHP_URL_QUERY);
    if (is_string($query) && $query !== '') {
        $target .= '?' . $query;
    }

    wp_safe_redirect($target, 302);
    exit;
}, 1);
