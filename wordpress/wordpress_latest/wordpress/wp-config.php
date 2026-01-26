<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_store' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!T8UT3b$ZLS~u0@+=v2$qzr&Z Zeg@lk}4~sWBIE`:nHQ%3G4B_1?Q][BY?Y0Zaq' );
define( 'SECURE_AUTH_KEY',  'UH#7 %yXPJF,gz$JhzW+B%2}`g8Jqhy=zEO2He+WM/*lPKhzo~!XQv myM>r_TF}' );
define( 'LOGGED_IN_KEY',    '6=l(p9!D eY#krU(6y?#cG 0w^ .cJ;-@TUYa!B5QaDjQ7 < 1LVzU>(8@R1<__i' );
define( 'NONCE_KEY',        'pHG{F{+*+y]w_d. Pg8(<dRqSjfE[1 d7uzStdv</K^n?;hiin|YH&!~d>|LT4s>' );
define( 'AUTH_SALT',        'v|St72]Iz>L&w1s_88DLXf31e}qw#r:6;Gw`tzK5>uG9s4&h($3Ydzsh,<8n8#as' );
define( 'SECURE_AUTH_SALT', 'rIW/@.w6J>9[RPQ)q2u?nZy!Mlu,OjJu`|Zhn(kpB<~80a-)V/Wjw{A@u)S #_%/' );
define( 'LOGGED_IN_SALT',   '/;eo41+QR+Q.H.4r4T3,_LN7L&k|aJ&j$pYD@[1cA`YxNpB4|=S]C>K=.($IIS9,' );
define( 'NONCE_SALT',       'dHb+q^cv8BUme-M1lmeGe!-!W`rBBns/V(UbIK!OZm}CMQ8m,<1]&uKqp:^XL^{M' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
