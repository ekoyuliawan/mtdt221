<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Core;

use AIdoforyou\StockMetadata\Services\File\TempStorage;
use AIdoforyou\StockMetadata\Admin\Settings;
use AIdoforyou\StockMetadata\Admin\Menu;
use AIdoforyou\StockMetadata\Api\Router;
use AIdoforyou\StockMetadata\Frontend\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    public function init(): void {
        $this->define_cron_schedules();
        $this->init_services();

        if ( is_admin() ) {
            $settings = new Settings();
            add_action( 'admin_init', array( $settings, 'register_settings' ) );
            add_action( 'added_option', array( $settings, 'enforce_no_autoload' ), 10, 1 );
            
            new Menu();
        }

        // REST API
        $router = new Router();
        add_action( 'rest_api_init', array( $router, 'register_routes' ) );

        // Frontend Shortcode
        add_action( 'init', function() {
            new Shortcode();
        } );
    }

    private function define_cron_schedules(): void {
        add_filter( 'cron_schedules', function( array $schedules ) {
            $schedules['aidofy_meta_15min'] = array(
                'interval' => 900,
                'display'  => __( 'Every 15 Minutes', 'aidoforyou-stock-metadata' ),
            );
            return $schedules;
        });
    }

    private function init_services(): void {
        $temp_storage = new TempStorage();
        add_action( 'aidofy_meta_temp_cleanup', array( $temp_storage, 'garbage_collection' ) );
    }
}