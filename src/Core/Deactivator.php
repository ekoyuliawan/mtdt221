<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Deactivator {

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'aidofy_meta_temp_cleanup' );
    }
}