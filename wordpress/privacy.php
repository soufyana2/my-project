<?php
require_once __DIR__ . '/store_brand.php';
require_once __DIR__ . '/store_rebrand_theme.php';
store_theme_enable_auto_replace();
store_brand_enable_auto_replace();

include __DIR__ . '/privacy.html';
