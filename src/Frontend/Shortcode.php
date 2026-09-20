<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Frontend;

use AIdoforyou\StockMetadata\Config\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode {

    public function __construct() {
        add_shortcode( 'aidofy_stock_metadata', array( $this, 'render_shortcode' ) );
        add_shortcode( 'aidoforyou_metadata', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode(): string {
        $this->enqueue_assets();

        ob_start();
        require AIDOFY_META_DIR . 'templates/frontend-app.php';
        return ob_get_clean() ?: '';
    }

    private function enqueue_assets(): void {
        wp_enqueue_style(
            'aidofy-meta-frontend',
            AIDOFY_META_URL . 'assets/css/frontend.css',
            array(),
            AIDOFY_META_VERSION
        );

        $turnstile_enabled  = get_option( 'aidofy_meta_turnstile_enabled', 'no' ) === 'yes';
        $turnstile_site_key = get_option( 'aidofy_meta_turnstile_site_key', '' );
        
        if ( $turnstile_enabled && ! empty( $turnstile_site_key ) ) {
            wp_enqueue_script( 'cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
        }

        wp_enqueue_script( 'aidofy-meta-utils', AIDOFY_META_URL . 'assets/js/frontend/utils.js', array(), AIDOFY_META_VERSION, true );
        wp_enqueue_script( 'aidofy-meta-api', AIDOFY_META_URL . 'assets/js/frontend/api.js', array( 'aidofy-meta-utils' ), AIDOFY_META_VERSION, true );
        wp_enqueue_script( 'aidofy-meta-ui', AIDOFY_META_URL . 'assets/js/frontend/ui.js', array( 'aidofy-meta-utils' ), AIDOFY_META_VERSION, true );
        wp_enqueue_script( 'aidofy-meta-app', AIDOFY_META_URL . 'assets/js/frontend/app.js', array( 'aidofy-meta-utils', 'aidofy-meta-api', 'aidofy-meta-ui' ), AIDOFY_META_VERSION, true );

        $models_json = (string) get_option( 'aidofy_meta_models_config', Defaults::get_models_config_json() );
        $models_arr  = json_decode( $models_json, true );

        $config = array(
            'core_rest'          => esc_url_raw( rest_url( 'aidoforyou/v1' ) ),
            'meta_rest'          => esc_url_raw( rest_url( 'aidofy-metadata/v1' ) ),
            'nonce'              => wp_create_nonce( 'wp_rest' ),
            'max_mb'             => (int) get_option( 'aidofy_meta_max_mb', 5 ),
            'cost'               => (int) get_option( 'aidofy_meta_credit_cost', 2 ),
            'models'             => is_array( $models_arr ) ? $models_arr : array(),
            'is_logged_in'       => is_user_logged_in(),
            'user_id'            => get_current_user_id(),
            'turnstile_enabled'  => $turnstile_enabled,
            'turnstile_site_key' => $turnstile_enabled ? $turnstile_site_key : ''
        );

        wp_localize_script( 'aidofy-meta-app', 'AIDOFY_META_CONFIG', $config );
    }
}