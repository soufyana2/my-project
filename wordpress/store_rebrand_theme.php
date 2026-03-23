<?php

/**
 * Global visual rebranding (colors, hovers, backgrounds).
 * Edit this file only to update the website palette everywhere.
 *
 * QUICK EDIT MAP
 * 1) Palette constants: STORE_THEME_* values
 * 2) Key lookup helper: store_theme_palette()
 * 3) Email palette constants: STORE_THEME_EMAIL_* values
 * 4) Auto replacement map: store_theme_replacements()
 * 5) Output buffering hook: store_theme_enable_auto_replace()
 */

/**
 * ==============================
 * THEME PALETTE CONSTANTS
 * ==============================
 * Change these values to update colors globally.
 * They are consumed by token replacement and legacy color mapping.
 */
if (!defined('STORE_THEME_ACCENT')) {
    // Primary brand accent for highlights/CTAs.
    define('STORE_THEME_ACCENT', '#C8A95A');
}

if (!defined('STORE_THEME_ACCENT_SOFT')) {
    // Secondary accent for softer highlighted areas.
    define('STORE_THEME_ACCENT_SOFT', '#D4B882');
}

if (!defined('STORE_THEME_ACCENT_ALT')) {
    // Alternative accent for badges/section accents.
    define('STORE_THEME_ACCENT_ALT', '#43A047');
}

if (!defined('STORE_THEME_ACCENT_STRONG')) {
    // Strong accent used for active states/buttons.
    define('STORE_THEME_ACCENT_STRONG', '#000000');
}

if (!defined('STORE_THEME_TEXT_PRIMARY')) {
    // Main heading/high-contrast text color.
    define('STORE_THEME_TEXT_PRIMARY', '#000000');
}

if (!defined('STORE_THEME_TEXT_SECONDARY')) {
    // Body and secondary text color.
    define('STORE_THEME_TEXT_SECONDARY', '#475569');
}

if (!defined('STORE_THEME_BG_BASE')) {
    // Base page background.
    define('STORE_THEME_BG_BASE', '#FFFFFF');
}

if (!defined('STORE_THEME_BG_SOFT')) {
    // Soft backgrounds for sections/cards.
    define('STORE_THEME_BG_SOFT', '#F8FAFC');
}

if (!defined('STORE_THEME_BORDER')) {
    // Border/divider color.
    define('STORE_THEME_BORDER', '#E2E8F0');
}

/**
 * ==============================
 * EMAIL THEME CONSTANTS
 * ==============================
 * These values style OTP/reset and user-facing auth emails.
 * Edit here once to update all email templates.
 * CONTROL SECTION:
 * - sendmail.php (OTP email)
 * - forgot_password.php (password reset email)
 * Change only these constants to restyle all auth emails globally.
 */
if (!defined('STORE_THEME_EMAIL_BG')) {
    define('STORE_THEME_EMAIL_BG', STORE_THEME_BG_SOFT);
}

if (!defined('STORE_THEME_EMAIL_CARD_BG')) {
    define('STORE_THEME_EMAIL_CARD_BG', '#FFFFFF');
}

if (!defined('STORE_THEME_EMAIL_HEADER_BG')) {
    define('STORE_THEME_EMAIL_HEADER_BG', STORE_THEME_ACCENT_STRONG);
}

if (!defined('STORE_THEME_EMAIL_HEADER_TEXT')) {
    define('STORE_THEME_EMAIL_HEADER_TEXT', '#FFFFFF');
}

if (!defined('STORE_THEME_EMAIL_HEADER_SUBTEXT')) {
    define('STORE_THEME_EMAIL_HEADER_SUBTEXT', STORE_THEME_ACCENT);
}

if (!defined('STORE_THEME_EMAIL_TITLE')) {
    define('STORE_THEME_EMAIL_TITLE', STORE_THEME_TEXT_PRIMARY);
}

if (!defined('STORE_THEME_EMAIL_TEXT')) {
    define('STORE_THEME_EMAIL_TEXT', STORE_THEME_TEXT_SECONDARY);
}

if (!defined('STORE_THEME_EMAIL_MUTED')) {
    define('STORE_THEME_EMAIL_MUTED', '#71717A');
}

if (!defined('STORE_THEME_EMAIL_BUTTON_BG')) {
    define('STORE_THEME_EMAIL_BUTTON_BG', STORE_THEME_ACCENT_STRONG);
}

if (!defined('STORE_THEME_EMAIL_BUTTON_TEXT')) {
    define('STORE_THEME_EMAIL_BUTTON_TEXT', '#FFFFFF');
}

if (!defined('STORE_THEME_EMAIL_BUTTON_BORDER')) {
    define('STORE_THEME_EMAIL_BUTTON_BORDER', STORE_THEME_ACCENT_STRONG);
}

if (!defined('STORE_THEME_EMAIL_LINK')) {
    define('STORE_THEME_EMAIL_LINK', STORE_THEME_ACCENT_STRONG);
}

if (!defined('STORE_THEME_EMAIL_SURFACE_SOFT')) {
    define('STORE_THEME_EMAIL_SURFACE_SOFT', '#F7F0F4');
}

if (!defined('STORE_THEME_EMAIL_BORDER')) {
    define('STORE_THEME_EMAIL_BORDER', STORE_THEME_BORDER);
}

if (!function_exists('store_theme_email_replacements')) {
    /**
     * Token replacements used by email HTML templates.
     * Keep template tokens readable and editable from this file only.
     * CONTROL SECTION:
     * Maps __STORE_EMAIL_* tokens used inside auth email templates.
     */
    function store_theme_email_replacements(): array
    {
        return [
            '__STORE_EMAIL_BG__' => STORE_THEME_EMAIL_BG,
            '__STORE_EMAIL_CARD_BG__' => STORE_THEME_EMAIL_CARD_BG,
            '__STORE_EMAIL_HEADER_BG__' => STORE_THEME_EMAIL_HEADER_BG,
            '__STORE_EMAIL_HEADER_TEXT__' => STORE_THEME_EMAIL_HEADER_TEXT,
            '__STORE_EMAIL_HEADER_SUBTEXT__' => STORE_THEME_EMAIL_HEADER_SUBTEXT,
            '__STORE_EMAIL_TITLE__' => STORE_THEME_EMAIL_TITLE,
            '__STORE_EMAIL_TEXT__' => STORE_THEME_EMAIL_TEXT,
            '__STORE_EMAIL_MUTED__' => STORE_THEME_EMAIL_MUTED,
            '__STORE_EMAIL_BUTTON_BG__' => STORE_THEME_EMAIL_BUTTON_BG,
            '__STORE_EMAIL_BUTTON_TEXT__' => STORE_THEME_EMAIL_BUTTON_TEXT,
            '__STORE_EMAIL_BUTTON_BORDER__' => STORE_THEME_EMAIL_BUTTON_BORDER,
            '__STORE_EMAIL_LINK__' => STORE_THEME_EMAIL_LINK,
            '__STORE_EMAIL_SURFACE_SOFT__' => STORE_THEME_EMAIL_SURFACE_SOFT,
            '__STORE_EMAIL_BORDER__' => STORE_THEME_EMAIL_BORDER,
        ];
    }
}

if (!function_exists('store_theme_palette')) {
    /**
     * Palette lookup helper for templates and scripts.
     */
    function store_theme_palette(string $key = 'accent'): string
    {
        switch ($key) {
            case 'accent':
                return STORE_THEME_ACCENT;
            case 'accent_soft':
                return STORE_THEME_ACCENT_SOFT;
            case 'accent_alt':
                return STORE_THEME_ACCENT_ALT;
            case 'accent_strong':
                return STORE_THEME_ACCENT_STRONG;
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
    /**
     * Master color replacement map.
     * Replaces old hardcoded colors with current theme constants.
     * Also supports __STORE_THEME_* tokens in templates.
     */
    function store_theme_replacements(): array
    {
        return array_merge([
            // Optional tokens
            '__STORE_THEME_ACCENT__' => STORE_THEME_ACCENT,
            '__STORE_THEME_ACCENT_SOFT__' => STORE_THEME_ACCENT_SOFT,
            '__STORE_THEME_ACCENT_ALT__' => STORE_THEME_ACCENT_ALT,
            '__STORE_THEME_ACCENT_STRONG__' => STORE_THEME_ACCENT_STRONG,
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
            '#D9978F' => STORE_THEME_ACCENT,
            '#d9978f' => strtolower(STORE_THEME_ACCENT),
            '#B8A38E' => STORE_THEME_ACCENT_SOFT,
            '#b8a38e' => strtolower(STORE_THEME_ACCENT_SOFT),
            '#3A3A3A' => STORE_THEME_ACCENT_ALT,
            '#3a3a3a' => strtolower(STORE_THEME_ACCENT_ALT),
            '#43A047' => STORE_THEME_ACCENT_ALT,
            '#43a047' => strtolower(STORE_THEME_ACCENT_ALT),
            '#FF6B9D' => STORE_THEME_ACCENT,
            '#ff6b9d' => strtolower(STORE_THEME_ACCENT),
            '#AC2A5D' => STORE_THEME_ACCENT_SOFT,
            '#ac2a5d' => strtolower(STORE_THEME_ACCENT_SOFT),
            '#A3B18A' => STORE_THEME_ACCENT_ALT,
            '#a3b18a' => strtolower(STORE_THEME_ACCENT_ALT),
            '#006A65' => STORE_THEME_ACCENT_STRONG,
            '#006a65' => strtolower(STORE_THEME_ACCENT_STRONG),
            '#18181B' => STORE_THEME_TEXT_PRIMARY,
            '#18181b' => strtolower(STORE_THEME_TEXT_PRIMARY),
            '#52525B' => STORE_THEME_TEXT_SECONDARY,
            '#52525b' => strtolower(STORE_THEME_TEXT_SECONDARY),
            '#D8DECC' => STORE_THEME_BORDER,
            '#d8decc' => strtolower(STORE_THEME_BORDER),

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
            '#FDFDFD' => STORE_THEME_BG_BASE,
            '#fdfdfd' => STORE_THEME_BG_BASE,
            '#F8F8F8' => STORE_THEME_BG_SOFT,
            '#f8f8f8' => STORE_THEME_BG_SOFT,

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
            '#E5E4E2' => STORE_THEME_BORDER,
            '#e5e4e2' => STORE_THEME_BORDER,

            // Main text/dark tones
            '#0f172a' => STORE_THEME_TEXT_PRIMARY,
            '#111827' => STORE_THEME_TEXT_PRIMARY,
            '#212121' => STORE_THEME_TEXT_PRIMARY,
            '#1a1a1a' => STORE_THEME_TEXT_PRIMARY,
            '#333333' => STORE_THEME_TEXT_PRIMARY,
            '#020617' => STORE_THEME_TEXT_PRIMARY,
            '#000000' => STORE_THEME_TEXT_PRIMARY,
            '#5A5A5A' => STORE_THEME_TEXT_SECONDARY,
            '#5a5a5a' => STORE_THEME_TEXT_SECONDARY,
            '#475569' => STORE_THEME_TEXT_SECONDARY,
            '#64748b' => STORE_THEME_TEXT_SECONDARY,
            '#94a3b8' => STORE_THEME_TEXT_SECONDARY,
            '#6b7280' => STORE_THEME_TEXT_SECONDARY,
            '#555' => STORE_THEME_TEXT_SECONDARY,
            '#6B6B6B' => STORE_THEME_TEXT_SECONDARY,
            '#6b6b6b' => STORE_THEME_TEXT_SECONDARY,

            // Hover overlay normalization to the active brand accent
            'rgba(200, 169, 90,' => 'rgba(183, 110, 121,',
            'rgba(200,169,90,' => 'rgba(183,110,121,',
            'rgba(217, 151, 143,' => 'rgba(183, 110, 121,',
            'rgba(217,151,143,' => 'rgba(183,110,121,',
            'rgba(255, 107, 157,' => 'rgba(183, 110, 121,',
            'rgba(255,107,157,' => 'rgba(183,110,121,',
        ], store_theme_email_replacements());
    }
}

if (!function_exists('store_theme_replace')) {
    /**
     * Applies theme replacement map to a given HTML string.
     */
    function store_theme_replace(string $text): string
    {
        return strtr($text, store_theme_replacements());
    }
}

if (!function_exists('store_theme_buffer_callback')) {
    /**
     * Output-buffer callback:
     * applies color replacements on HTML responses only.
     */
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
    /**
     * Enables automatic theme replacements globally.
     * Called early from db.php.
     */
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

