<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Menu {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_submenu' ), 40 );
        // We enqueue inline JS below to avoid external dependencies for settings logic
    }

    public function add_submenu(): void {
        add_submenu_page(
            'aidoforyou', 
            __( 'Stock Metadata', 'aidoforyou-stock-metadata' ),
            __( 'Stock Metadata', 'aidoforyou-stock-metadata' ),
            'manage_options',
            'aidoforyou-stock-metadata',
            array( $this, 'render_page' )
        );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( isset( $_POST['aidofy_clear_logs'] ) && check_admin_referer( 'aidofy_clear_logs_action' ) ) {
            delete_option( 'aidofy_meta_error_logs' );
            echo '<div class="notice notice-success is-dismissible"><p>Error logs cleared successfully.</p></div>';
        }

        require_once AIDOFY_META_DIR . 'templates/admin-settings.php';
    }
}