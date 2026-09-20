<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Admin;

use AIdoforyou\StockMetadata\Config\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {

    public function register_settings(): void {
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_api_keys', array( 'default' => '[]', 'sanitize_callback' => array( $this, 'sanitize_api_keys' ) ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_gemini_api_base', array( 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_models_config', array( 'default' => Defaults::get_models_config_json() ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_media_resolution', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'MEDIA_RESOLUTION_HIGH' ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_google_search', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'yes' ) );
        
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_api_timeout', array( 'sanitize_callback' => 'absint', 'default' => 85 ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_peak_rpm', array( 'sanitize_callback' => 'absint', 'default' => 5 ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_max_bulk', array( 'sanitize_callback' => 'absint', 'default' => 10 ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_auto_retry_interval', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_auto_retry_max', array( 'sanitize_callback' => 'absint', 'default' => 3 ) );
        
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_credit_cost', array( 'sanitize_callback' => 'absint', 'default' => 2 ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_max_mb', array( 'sanitize_callback' => 'absint', 'default' => 5 ) );
        
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_base_prompt', array( 'default' => Defaults::get_base_prompt(), 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_modular_rules', array( 'default' => Defaults::get_modular_rules_json() ) ); 

        // Turnstile Security Registrations
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_turnstile_enabled', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'no' ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_turnstile_site_key', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
        register_setting( 'aidofy_stock_metadata_opts', 'aidofy_meta_turnstile_secret_key', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );

        add_settings_section( 'aidofy_meta_api_section', __( 'AI API Configuration', 'aidoforyou-stock-metadata' ), null, 'aidoforyou-stock-metadata' );
        add_settings_field( 'aidofy_meta_api_keys', __( 'API Keys Manager', 'aidoforyou-stock-metadata' ), array( $this, 'render_api_keys_builder' ), 'aidoforyou-stock-metadata', 'aidofy_meta_api_section' );
        add_settings_field( 'aidofy_meta_gemini_api_base', __( 'API Base URL', 'aidoforyou-stock-metadata' ), array( $this, 'render_text_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_api_section', array( 'id' => 'aidofy_meta_gemini_api_base', 'default' => '', 'help' => 'Optional proxy.' ) );
        add_settings_field( 'aidofy_meta_media_resolution', __( 'Media Resolution', 'aidoforyou-stock-metadata' ), array( $this, 'render_resolution_dropdown' ), 'aidoforyou-stock-metadata', 'aidofy_meta_api_section' );
        add_settings_field( 'aidofy_meta_google_search', __( 'Google Search', 'aidoforyou-stock-metadata' ), array( $this, 'render_google_search_toggle' ), 'aidoforyou-stock-metadata', 'aidofy_meta_api_section' );
        add_settings_field( 'aidofy_meta_api_timeout', __( 'API Timeout', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_api_section', array( 'id' => 'aidofy_meta_api_timeout', 'default' => 85, 'max' => 95, 'suffix' => 'Sec (Max 95)' ) );
        add_settings_field( 'aidofy_meta_models_config', __( 'AI Models', 'aidoforyou-stock-metadata' ), array( $this, 'render_models_builder' ), 'aidoforyou-stock-metadata', 'aidofy_meta_api_section' );

        add_settings_section( 'aidofy_meta_bulk_section', __( 'Bulk Processing & Retries', 'aidoforyou-stock-metadata' ), null, 'aidoforyou-stock-metadata' );
        add_settings_field( 'aidofy_meta_peak_rpm', __( 'Peak RPM (Rate Limit)', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_bulk_section', array( 'id' => 'aidofy_meta_peak_rpm', 'default' => 5, 'suffix' => 'Req/Minute' ) );
        add_settings_field( 'aidofy_meta_max_bulk', __( 'Max Bulk Upload', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_bulk_section', array( 'id' => 'aidofy_meta_max_bulk', 'default' => 10, 'suffix' => 'Files' ) );
        add_settings_field( 'aidofy_meta_auto_retry_interval', __( 'Auto-Retry Interval', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_bulk_section', array( 'id' => 'aidofy_meta_auto_retry_interval', 'default' => 1, 'suffix' => 'Minute(s)' ) );
        add_settings_field( 'aidofy_meta_auto_retry_max', __( 'Max Auto-Retries', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_bulk_section', array( 'id' => 'aidofy_meta_auto_retry_max', 'default' => 3, 'suffix' => 'Attempts' ) );

        add_settings_section( 'aidofy_meta_rules_section', __( 'Pricing & Prompt Routing', 'aidoforyou-stock-metadata' ), null, 'aidoforyou-stock-metadata' );
        add_settings_field( 'aidofy_meta_credit_cost', __( 'Credit Cost', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_rules_section', array( 'id' => 'aidofy_meta_credit_cost', 'default' => 2, 'suffix' => 'Credit(s)' ) );
        add_settings_field( 'aidofy_meta_max_mb', __( 'Max Upload MB', 'aidoforyou-stock-metadata' ), array( $this, 'render_number_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_rules_section', array( 'id' => 'aidofy_meta_max_mb', 'default' => 5, 'suffix' => 'MB' ) );
        add_settings_field( 'aidofy_meta_base_prompt', __( 'Base Rules (Universal)', 'aidoforyou-stock-metadata' ), array( $this, 'render_base_prompt_textarea' ), 'aidoforyou-stock-metadata', 'aidofy_meta_rules_section' );
        add_settings_field( 'aidofy_meta_modular_rules', __( 'Specific Rules (Modular)', 'aidoforyou-stock-metadata' ), array( $this, 'render_modular_rules_builder' ), 'aidoforyou-stock-metadata', 'aidofy_meta_rules_section' );

        // Render Security Section
        add_settings_section( 'aidofy_meta_security_section', __( 'Security & CAPTCHA', 'aidoforyou-stock-metadata' ), null, 'aidoforyou-stock-metadata' );
        add_settings_field( 'aidofy_meta_turnstile_enabled', __( 'Enable Turnstile', 'aidoforyou-stock-metadata' ), array( $this, 'render_turnstile_toggle' ), 'aidoforyou-stock-metadata', 'aidofy_meta_security_section' );
        add_settings_field( 'aidofy_meta_turnstile_site_key', __( 'Turnstile Site Key', 'aidoforyou-stock-metadata' ), array( $this, 'render_text_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_security_section', array( 'id' => 'aidofy_meta_turnstile_site_key', 'default' => '', 'help' => 'Cloudflare Turnstile Site Key to protect the frontend generation form.' ) );
        add_settings_field( 'aidofy_meta_turnstile_secret_key', __( 'Turnstile Secret Key', 'aidoforyou-stock-metadata' ), array( $this, 'render_text_field' ), 'aidoforyou-stock-metadata', 'aidofy_meta_security_section', array( 'id' => 'aidofy_meta_turnstile_secret_key', 'default' => '', 'type' => 'password', 'help' => 'Server-side Secret Key (Masked for security).' ) );
    }

    public function enforce_no_autoload( mixed $option ): void {
        $heavy_options = array( 'aidofy_meta_api_keys', 'aidofy_meta_models_config', 'aidofy_meta_modular_rules', 'aidofy_meta_error_logs', 'aidofy_meta_base_prompt' );
        
        if ( is_string( $option ) && in_array( $option, $heavy_options, true ) ) {
            global $wpdb;
            $wpdb->update( $wpdb->options, array( 'autoload' => 'no' ), array( 'option_name' => $option ) );
            wp_cache_delete( $option, 'options' );
            wp_cache_delete( 'alloptions', 'options' );
        }
    }

    public function sanitize_api_keys( mixed $input ): string {
        $old_keys_json = get_option( 'aidofy_meta_api_keys', '[]' );
        $old_keys = json_decode( (string) $old_keys_json, true );
        if ( ! is_array( $old_keys ) ) $old_keys = array();

        $new_keys = json_decode( wp_unslash( (string) $input ), true );
        if ( ! is_array( $new_keys ) ) return '[]';

        $sanitized = array();
        foreach ( $new_keys as $index => $key ) {
            $key = sanitize_text_field( $key );
            if ( strpos( $key, '***' ) !== false ) {
                $sanitized[] = isset( $old_keys[ $index ] ) ? $old_keys[ $index ] : '';
            } else {
                $sanitized[] = $key;
            }
        }
        return wp_json_encode( array_filter( $sanitized ) );
    }

    public function render_text_field( array $args ): void {
        $val  = get_option( $args['id'], $args['default'] ?? '' );
        $type = $args['type'] ?? 'text';
        printf( '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />', esc_attr( $type ), esc_attr( $args['id'] ), esc_attr( $args['id'] ), esc_attr( (string) $val ) );
        if ( ! empty( $args['help'] ) ) echo '<p class="description">' . esc_html( $args['help'] ) . '</p>';
    }

    public function render_number_field( array $args ): void {
        $val = get_option( $args['id'], $args['default'] );
        $max_attr = isset( $args['max'] ) ? 'max="' . esc_attr( (string) $args['max'] ) . '"' : '';
        printf( '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="1" step="1" %s /> %s', esc_attr( $args['id'] ), esc_attr( $args['id'] ), esc_attr( (string) $val ), $max_attr, esc_html( $args['suffix'] ) );
    }

    public function render_turnstile_toggle(): void {
        $val = get_option( 'aidofy_meta_turnstile_enabled', 'no' );
        echo '<label><input type="radio" name="aidofy_meta_turnstile_enabled" value="yes" ' . checked($val, 'yes', false) . '> Enable</label><br>';
        echo '<label><input type="radio" name="aidofy_meta_turnstile_enabled" value="no" ' . checked($val, 'no', false) . '> Disable</label>';
        echo '<p class="description">Enable Cloudflare Turnstile to securely block automated bots and API spam.</p>';
    }

    public function render_resolution_dropdown(): void {
        $val = get_option( 'aidofy_meta_media_resolution', 'MEDIA_RESOLUTION_HIGH' );
        $options = array(
            'default'                 => 'Default (AI decides)',
            'MEDIA_RESOLUTION_LOW'    => 'Low Resolution (Faster, cheaper)',
            'MEDIA_RESOLUTION_MEDIUM' => 'Medium Resolution',
            'MEDIA_RESOLUTION_HIGH'   => 'High Resolution (Best for small details)'
        );
        echo '<select id="aidofy_meta_media_resolution" name="aidofy_meta_media_resolution">';
        foreach ( $options as $key => $label ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $val, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
    }

    public function render_google_search_toggle(): void {
        $val = get_option( 'aidofy_meta_google_search', 'yes' );
        echo '<label><input type="radio" name="aidofy_meta_google_search" value="yes" ' . checked($val, 'yes', false) . '> Enable</label><br>';
        echo '<label><input type="radio" name="aidofy_meta_google_search" value="no" ' . checked($val, 'no', false) . '> Disable</label>';
        echo '<p class="description">If enabled, the AI can search the internet in real-time to verify facts in your images.</p>';
    }

    public function render_base_prompt_textarea(): void {
        $val = get_option( 'aidofy_meta_base_prompt', Defaults::get_base_prompt() );
        printf( '<textarea id="aidofy_meta_base_prompt" name="aidofy_meta_base_prompt" rows="12" style="width:100%%; font-family:monospace; font-size:13px;" placeholder="General rules (JSON Structure)...">%s</textarea>', esc_textarea( (string) $val ) );
        echo '<p class="description">Universal rules that are ALWAYS sent to the AI (e.g., JSON schema, content filtering rules, character limits, etc.).</p>';
    }

    public function render_modular_rules_builder(): void {
        $json_val = get_option( 'aidofy_meta_modular_rules', Defaults::get_modular_rules_json() );
        ?>
        <div id="aidofy-modules-builder-wrap" style="background:#f8fafc; padding:15px; border:1px solid #cbd5e1; border-radius:8px; max-width: 820px;">
            <div id="aidofy-modules-rows" style="display:flex; flex-direction:column; gap:15px; margin-bottom:15px;"></div>
            <button type="button" id="aidofy-btn-add-module" class="button button-secondary">+ Add Specific Rule Module</button>
            <textarea id="aidofy_meta_modular_rules" name="aidofy_meta_modular_rules" style="display:none;"><?php echo esc_textarea((string)$json_val); ?></textarea>
            <p class="description" style="margin-top:10px;">This specific rule will ONLY be injected into the main prompt if the AI detects that category within the image (Prompt Routing).</p>
        </div>
        <?php
    }

    public function render_api_keys_builder(): void {
        $json_val = get_option( 'aidofy_meta_api_keys', '[]' );
        $keys = json_decode( (string) $json_val, true );
        if ( ! is_array( $keys ) ) $keys = array();

        $masked_keys = array();
        foreach ( $keys as $k ) {
            if ( strlen( $k ) > 10 ) {
                $masked_keys[] = substr( $k, 0, 4 ) . '****************' . substr( $k, -4 );
            } else {
                $masked_keys[] = '***';
            }
        }
        $masked_json = wp_json_encode( $masked_keys );
        ?>
        <div id="aidofy-apikeys-builder-wrap" style="background:#f8fafc; padding:15px; border:1px solid #cbd5e1; border-radius:8px; max-width: 600px;">
            <div id="aidofy-apikeys-rows" style="display:flex; flex-direction:column; gap:8px; margin-bottom:15px;"></div>
            <button type="button" id="aidofy-btn-add-apikey" class="button button-secondary">+ Add API Key</button>
            <input type="hidden" id="aidofy_meta_api_keys" name="aidofy_meta_api_keys" value="<?php echo esc_attr($masked_json); ?>">
            <p class="description" style="margin-top:10px;">Provide multiple API Keys to enable <b>Auto Rotation</b>. Keys are masked for security.</p>
        </div>
        <?php
    }

    public function render_models_builder(): void {
        $json_val = get_option( 'aidofy_meta_models_config', Defaults::get_models_config_json() );
        ?>
        <div id="aidofy-model-builder-wrap" style="background:#f8fafc; padding:15px; border:1px solid #cbd5e1; border-radius:8px; max-width: 820px;">
            <div id="aidofy-model-rows" style="display:flex; flex-direction:column; gap:10px; margin-bottom:15px;"></div>
            <button type="button" id="aidofy-btn-add-model" class="button button-secondary">+ Add New Model</button>
            <input type="hidden" id="aidofy_meta_models_config" name="aidofy_meta_models_config" value="<?php echo esc_attr((string)$json_val); ?>">
            <p class="description" style="margin-top:10px;">Configure the models available to your users.</p>
        </div>
        <?php
    }
}