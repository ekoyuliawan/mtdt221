<?php
/**
 * Plugin Name: AIdoforyou Stock Metadata Generator
 * Description: AI-powered stock metadata generator using Gemini Vision. Requires AIdoforyou Credit Manager. Use [aidofy_stock_metadata].
 * Version:      2.2.1
 * Author:       AIdoforyou
 * Text Domain:  aidoforyou-stock-metadata
 * Requires PHP: 8.1
 * License:      GPL-2.0+
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AIDOFY_META_VERSION', '2.2.1' );
define( 'AIDOFY_META_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIDOFY_META_URL', plugin_dir_url( __FILE__ ) );

/* ------------------------------------------------------------------------- *
 * PSR-4 NATIVE AUTOLOADER
 * ------------------------------------------------------------------------- */
spl_autoload_register( function ( string $class ) {
    $prefix   = 'AIdoforyou\\StockMetadata\\';
    $base_dir = AIDOFY_META_DIR . 'src/';
    $len      = strlen( $prefix );
    
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    
    if ( file_exists( $file ) ) {
        require $file;
    }
});

/* ------------------------------------------------------------------------- *
 * PLUGIN LIFECYCLE HOOKS
 * ------------------------------------------------------------------------- */
register_activation_hook( __FILE__, array( 'AIdoforyou\\StockMetadata\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AIdoforyou\\StockMetadata\\Core\\Deactivator', 'deactivate' ) );

/* ------------------------------------------------------------------------- *
 * BOOTSTRAP
 * ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', function() {
    if ( ! function_exists( 'aidoforyou' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>AIdoforyou Stock Metadata Generator</strong> requires the <strong>AIdoforyou Credit Manager (Core)</strong> plugin to be installed and activated.</p></div>';
        } );
        return;
    }

    $plugin = new \AIdoforyou\StockMetadata\Core\Plugin();
    $plugin->init();
});