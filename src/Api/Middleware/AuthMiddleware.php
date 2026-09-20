<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Api\Middleware;

use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AuthMiddleware {

    public static function validate( WP_REST_Request $request, bool $check_model = true ): array|WP_Error {
        if ( ! function_exists( 'aidoforyou' ) ) {
            return new WP_Error( 'core_missing', __( 'AIdoforyou Core is not active.', 'aidoforyou-stock-metadata' ), array( 'status' => 500 ) );
        }

        // Accurately mirrors the legacy behavior: Extracts the session from the custom header or falls back to WP User ID.
        $session_id = $request->get_header( 'x_aidoforyou_token' );
        if ( empty( $session_id ) ) {
            $session_id = (string) get_current_user_id();
        }

        if ( empty( $session_id ) || $session_id === '0' ) {
            return new WP_Error( 'bad_token', __( 'Invalid session token.', 'aidoforyou-stock-metadata' ), array( 'status' => 401 ) );
        }

        $api_keys = json_decode( (string) get_option( 'aidofy_meta_api_keys', '[]' ), true );
        if ( empty( $api_keys ) ) {
            return new WP_Error( 'no_config', __( 'Metadata service is not configured.', 'aidoforyou-stock-metadata' ), array( 'status' => 503 ) );
        }

        $credit_cost = (int) get_option( 'aidofy_meta_credit_cost', 2 );
        $balance     = aidoforyou()->credits->get( $session_id );
        
        if ( $balance < $credit_cost ) {
            return new WP_Error( 'insufficient_credits', sprintf( __( 'You need at least %d credit(s).', 'aidoforyou-stock-metadata' ), $credit_cost ), array( 'status' => 403 ) );
        }

        $response = array(
            'identifier' => $session_id,
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