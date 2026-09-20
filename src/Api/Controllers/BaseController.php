<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Api\Controllers;

use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class BaseController extends \AIDOFORYOU_Core_Endpoint_Base {

    public function __construct() {
        parent::__construct( aidoforyou()->credits );
    }

    protected function validate_prerequisites( WP_REST_Request $request, bool $check_model = true ): array|WP_Error {
        if ( ! function_exists( 'aidoforyou' ) ) {
            return new WP_Error( 'core_missing', __( 'AIdoforyou Core is not active.', 'aidoforyou-stock-metadata' ), array( 'status' => 500 ) );
        }

        $identifier = $this->get_identifier( $request );
        if ( ! $identifier ) {
            return new WP_Error( 'bad_token', __( 'Invalid session token.', 'aidoforyou-stock-metadata' ), array( 'status' => 401 ) );
        }

        $rate_err = $this->check_rate_limit( $identifier );
        if ( is_wp_error( $rate_err ) ) {
            return $rate_err;
        }

        // --- Turnstile CAPTCHA Verification ---
        $turnstile_enabled = get_option( 'aidofy_meta_turnstile_enabled', 'no' ) === 'yes';
        
        if ( $turnstile_enabled ) {
            $secret_key = get_option( 'aidofy_meta_turnstile_secret_key', '' );
            $site_key   = get_option( 'aidofy_meta_turnstile_site_key', '' );
            
            // FAIL-CLOSED SECURITY: If enabled but keys missing, instantly block access.
            if ( empty( $secret_key ) || empty( $site_key ) ) {
                return new WP_Error( 'turnstile_misconfigured', __( 'Security misconfiguration: CAPTCHA keys are missing. Access forbidden.', 'aidoforyou-stock-metadata' ), array( 'status' => 403 ) );
            }

            $transient_key = 'aidofy_ts_' . md5( $identifier . ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
            $is_verified   = get_transient( $transient_key );

            if ( ! $is_verified ) {
                $token = sanitize_text_field( $request->get_param( 'cf_turnstile_response' ) ?: '' );
                
                if ( empty( $token ) ) {
                    return new WP_Error( 'missing_captcha', __( 'Please complete the security check.', 'aidoforyou-stock-metadata' ), array( 'status' => 403 ) );
                }

                $verify_response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
                    'body' => array(
                        'secret'   => $secret_key,
                        'response' => $token,
                        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
                    )
                ) );

                if ( is_wp_error( $verify_response ) ) {
                    return new WP_Error( 'captcha_failed', __( 'Security check connection failed.', 'aidoforyou-stock-metadata' ), array( 'status' => 500 ) );
                }

                $verify_body = json_decode( wp_remote_retrieve_body( $verify_response ), true );
                if ( empty( $verify_body['success'] ) ) {
                    return new WP_Error( 'invalid_captcha', __( 'Security check failed. Please refresh the page and try again.', 'aidoforyou-stock-metadata' ), array( 'status' => 403 ) );
                }

                // Grants the user a 15-minute clearance window to allow seamless execution of large bulk batches
                set_transient( $transient_key, true, 15 * MINUTE_IN_SECONDS );
            }
        }

        $api_keys = json_decode( (string) get_option( 'aidofy_meta_api_keys', '[]' ), true );
        if ( empty( $api_keys ) ) {
            return new WP_Error( 'no_config', __( 'Metadata service is not configured.', 'aidoforyou-stock-metadata' ), array( 'status' => 503 ) );
        }

        $credit_cost = (int) get_option( 'aidofy_meta_credit_cost', 2 );
        $balance     = $this->credits->get( $identifier );
        
        if ( $balance < $credit_cost ) {
            return new WP_Error( 'insufficient_credits', sprintf( __( 'You need at least %d credit(s).', 'aidoforyou-stock-metadata' ), $credit_cost ), array( 'status' => 403 ) );
        }

        $response = array(
            'identifier' => $identifier,
            'cost'       => $credit_cost,
        );

        if ( $check_model ) {
            $requested_model = sanitize_text_field( $request->get_param( 'model' ) );
            $models_config   = json_decode( (string) get_option( 'aidofy_meta_models_config', '[]' ), true );
            $selected_model  = null;
            
            if ( is_array( $models_config ) ) {
                foreach ( $models_config as $m ) {
                    if ( $m['id'] === $requested_model ) { $selected_model = $m; break; }
                }
            }

            if ( ! $selected_model ) {
                return new WP_Error( 'invalid_model', __( 'The selected AI model is invalid.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
            }
            if ( ! empty( $selected_model['premium'] ) && get_current_user_id() === 0 ) {
                return new WP_Error( 'premium_locked', __( 'You must log in to use Premium models.', 'aidoforyou-stock-metadata' ), array( 'status' => 403 ) );
            }

            $response['model_data'] = $selected_model;
            $response['models']     = $models_config;
        }

        return $response;
    }
}