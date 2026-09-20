<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {
    
    public static function activate(): void {
        // 1. Purge legacy monolithic cron jobs from v1.49.2 to prevent silent filesystem crashes
        wp_clear_scheduled_hook( 'afy_meta_hourly_cleanup' );
        wp_clear_scheduled_hook( 'afy_meta_periodic_cleanup' );

        // 2. Schedule the modern garbage collection hook used by Deactivator & TempStorage
        if ( ! wp_next_scheduled( 'aidofy_meta_temp_cleanup' ) ) {
            // Using WP's native 'hourly' schedule to safely clear temp files without overloading the server
            wp_schedule_event( time(), 'hourly', 'aidofy_meta_temp_cleanup' );
        }

        // 3. Flush rewrite rules to ensure secure API routing
        flush_rewrite_rules();
    }
    
}