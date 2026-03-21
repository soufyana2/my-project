<?php

/**
 * Global visual rebranding (colors, hovers, backgrounds).
 * Edit this file only to update the website palette everywhere.
 */

if (!defined('STORE_THEME_ACCENT')) {
    define('STORE_THEME_ACCENT', '#D9978F');
}

if (!defined('STORE_THEME_ACCENT_SOFT')) {
    define('STORE_THEME_ACCENT_SOFT', '#B8A38E');
}

if (!defined('STORE_THEME_TEXT_PRIMARY')) {
    define('STORE_THEME_TEXT_PRIMARY', '#1A1A1A');
}

if (!defined('STORE_THEME_TEXT_SECONDARY')) {
    define('STORE_THEME_TEXT_SECONDARY', '#6B6B6B');
}

if (!defined('STORE_THEME_BG_BASE')) {
    define('STORE_THEME_BG_BASE', '#FDFDFD');
}

if (!defined('STORE_THEME_BG_SOFT')) {
    define('STORE_THEME_BG_SOFT', '#F8F8F8');
}

if (!defined('STORE_THEME_BORDER')) {
    define('STORE_THEME_BORDER', '#E5E4E2');
}

if (!function_exists('store_theme_palette')) {
    function store_theme_palette(string $key = 'accent'): string
    {
        switch ($key) {
            case 'accent':
                return STORE_THEME_ACCENT;
            case 'accent_soft':
                return STORE_THEME_ACCENT_SOFT;
            case 'text_primary':
                return STORE_THEME_TEXT_PRIMARY;
            case 'text_secondary':
                return STORE_THEME_TEXT_SECONDARY;
            case 'bg_base':
                return STORE_THEME_BG_BASE;
            case 'bg_soft':
                return STORE_THEME_BG_SOFT;
            case 'border':
                return STORE_THEME_BORDER;
            default:
                return STORE_THEME_ACCENT;
        }
    }
}

if (!function_exists('store_theme_replacements')) {
    function store_theme_replacements(): array
    {
        return [
            // Optional tokens
            '__STORE_THEME_ACCENT__' => STORE_THEME_ACCENT,
            '__STORE_THEME_ACCENT_SOFT__' => STORE_THEME_ACCENT_SOFT,
            '__STORE_THEME_TEXT_PRIMARY__' => STORE_THEME_TEXT_PRIMARY,
            '__STORE_THEME_TEXT_SECONDARY__' => STORE_THEME_TEXT_SECONDARY,
            '__STORE_THEME_BG_BASE__' => STORE_THEME_BG_BASE,
            '__STORE_THEME_BG_SOFT__' => STORE_THEME_BG_SOFT,
            '__STORE_THEME_BORDER__' => STORE_THEME_BORDER,

            // Brand/accent colors
            '#C8A95A' => STORE_THEME_ACCENT,
            '#c8a95a' => strtolower(STORE_THEME_ACCENT),
            '#d4af37' => strtolower(STORE_THEME_ACCENT),
            '#D4AF37' => STORE_THEME_ACCENT,
            '#d4b882' => STORE_THEME_ACCENT_SOFT,
            '#b8965a' => STORE_THEME_ACCENT_SOFT,
            '#FFD700' => STORE_THEME_ACCENT,
            '#ffd700' => strtolower(STORE_THEME_ACCENT),

            // Base and soft backgrounds
            '#ffffff' => STORE_THEME_BG_BASE,
            '#FFFFFF' => STORE_THEME_BG_BASE,
            '#f5f5f4' => STORE_THEME_BG_BASE,
            '#F5F5F5' => STORE_THEME_BG_SOFT,
            '#f8fafc' => STORE_THEME_BG_SOFT,
            '#f9f9f9' => STORE_THEME_BG_SOFT,
            '#f1f5f9' => STORE_THEME_BG_SOFT,
            '#f0f0f0' => STORE_THEME_BG_SOFT,
            '#FAFAFA' => STORE_THEME_BG_SOFT,

            // Border and divider colors
            '#e2e8f0' => STORE_THEME_BORDER,
            '#E2E8F0' => STORE_THEME_BORDER,
            '#cbd5e1' => STORE_THEME_BORDER,
            '#e0e0e0' => STORE_THEME_BORDER,
            '#E0E0E0' => STORE_THEME_BORDER,
            '#E5E7EB' => STORE_THEME_BORDER,
            '#e5e5e5' => STORE_THEME_BORDER,
            '#d1d5db' => STORE_THEME_BORDER,
            '#D3D3D3' => STORE_THEME_BORDER,

            // Main text/dark tones
            '#0f172a' => STORE_THEME_TEXT_PRIMARY,
            '#111827' => STORE_THEME_TEXT_PRIMARY,
            '#212121' => STORE_THEME_TEXT_PRIMARY,
            '#1a1a1a' => STORE_THEME_TEXT_PRIMARY,
            '#3A3A3A' => STORE_THEME_TEXT_PRIMARY,
            '#333333' => STORE_THEME_TEXT_PRIMARY,
            '#5A5A5A' => STORE_THEME_TEXT_PRIMARY,
            '#000000' => STORE_THEME_TEXT_PRIMARY,

            // Gold-ish rgba hover overlays to Nouar accent
            'rgba(200, 169, 90,' => 'rgba(217, 151, 143,',
            'rgba(200,169,90,' => 'rgba(217,151,143,',
        ];
    }
}

if (!function_exists('store_theme_replace')) {
    function store_theme_replace(string $text): string
    {
        return strtr($text, store_theme_replacements());
    }
}

if (!function_exists('store_theme_buffer_callback')) {
    function store_theme_buffer_callback(string $buffer): string
    {
        // Only touch HTML responses.
        if (stripos($buffer, '<html') === false && stripos($buffer, '<body') === false) {
            return $buffer;
        }

        return store_theme_replace($buffer);
    }
}

if (!function_exists('store_theme_enable_auto_replace')) {
    function store_theme_enable_auto_replace(): void
    {
        static $enabled = false;

        if ($enabled || PHP_SAPI === 'cli') {
            return;
        }

        $enabled = true;
        ob_start('store_theme_buffer_callback');
    }
}

