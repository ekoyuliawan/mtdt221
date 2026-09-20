<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Api\Controllers;

use AIdoforyou\StockMetadata\Services\File\TempStorage;
use AIdoforyou\StockMetadata\Services\AI\GeminiClient;
use AIdoforyou\StockMetadata\Services\AI\PromptBuilder;
use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GenerateController extends BaseController {

    public function handle_request( WP_REST_Request $request ) {
        $auth = $this->validate_prerequisites( $request, true );
        if ( is_wp_error( $auth ) ) return $auth;

        $server_index       = (int) $request->get_param( 'server_index' );
        $failed_models      = json_decode( wp_unslash( $request->get_param( 'failed_models' ) ?: '[]' ), true ) ?: array();
        $commercial_concept = sanitize_text_field( $request->get_param( 'commercial_concept' ) ?: 'General Concept' );
        $active_modules     = json_decode( wp_unslash( $request->get_param( 'active_modules' ) ?: '[]' ), true ) ?: array();
        
        $file_hash   = sanitize_text_field( $request->get_param( 'file_hash' ) ?: '' );
        $mime_type   = sanitize_text_field( $request->get_param( 'mime_type' ) ?: '' );
        $text_input  = sanitize_textarea_field( $request->get_param( 'text_input' ) ?: '' );
        $user_prompt = sanitize_textarea_field( $request->get_param( 'prompt' ) ?: '' );
        $think_level = ! empty( $auth['model_data']['thinking'] ) ? $auth['model_data']['thinking'] : '';

        $base64_data = '';
        if ( ! empty( $file_hash ) ) {
            $storage = new TempStorage();
            $temp_dir = $storage->init_directory();
            $file_path = $temp_dir . $file_hash;
            
            if ( ! file_exists( $file_path ) ) return new WP_Error( 'file_expired', __( 'Image session expired.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
            $base64_data = base64_encode( (string) file_get_contents( $file_path ) );
        }

        $client       = new GeminiClient();
        $system_instr = PromptBuilder::build_generate_instruction( $commercial_concept, $active_modules );
        $total_keys   = $client->get_keys_count();
        $start_time   = time();
        $ai_response  = null;

        for ( $i = $server_index; $i < $total_keys; $i++ ) {
            if ( get_transient( 'aidofy_meta_cooldown_' . $i ) ) continue;

            $ai_response = $client->generate( $auth['model_data']['id'], $i, $system_instr, $text_input, $user_prompt, $base64_data, $mime_type, $think_level );

            if ( ! is_wp_error( $ai_response ) ) {
                $server_index = $i;
                break;
            }

            $err_data   = $ai_response->get_error_data();
            $http_code  = $err_data['http_code'] ?? 0;
            $err_msg    = strtolower( (string) $ai_response->get_error_message() );
            $is_timeout = ( $ai_response->get_error_code() === 'http_request_failed' && ( strpos( $err_msg, 'curl error 28' ) !== false || strpos( $err_msg, 'timed out' ) !== false ) );
            $is_busy    = ( $http_code === 503 || $http_code === 429 || strpos( $err_msg, 'high demand' ) !== false || strpos( $err_msg, 'quota' ) !== false );

            if ( $is_busy || $is_timeout ) {
                $cooldown = ( $http_code === 429 ) ? 120 : 30;
                set_transient( 'aidofy_meta_cooldown_' . $i, 'locked', $cooldown );
                $this->log_api_error( "Generate Phase: Server {$i} Failed. HTTP {$http_code}. Locked for {$cooldown}s." );
                
                if ( ( time() - $start_time ) > 60 && ( $i + 1 < $total_keys ) ) {
                    return rest_ensure_response( array(
                        'code'               => 'switch_server',
                        'next_server_index'  => $i + 1,
                        'file_hash'          => $file_hash,
                        'mime_type'          => $mime_type,
                        'commercial_concept' => $commercial_concept,
                        'active_modules'     => wp_json_encode( $active_modules )
                    ) );
                }
                continue;
            }
            break;
        }

        if ( $ai_response === null ) {
            return new WP_Error( 'ai_error', 'API configuration missing or all models exhausted.', array( 'status' => 500 ) );
        }

        if ( is_wp_error( $ai_response ) ) {
            return $this->handle_fallback_state( $ai_response, $auth['model_data'], $failed_models, $auth['models'] );
        }

        aidoforyou()->credits->deduct( $auth['identifier'], $auth['cost'] );

        return rest_ensure_response( array(
            'code'               => 0,
            'credits'            => aidoforyou()->credits->get( $auth['identifier'] ),
            'metadata'           => $ai_response['content'],
            'file_hash'          => $file_hash,
            'mime_type'          => $mime_type,
            'commercial_concept' => $commercial_concept,
            'model_label'        => $auth['model_data']['label'],
            'server_label'       => 'Server ' . ($server_index + 1), // Replaced "Node"
            'generated_at'       => current_time( 'Y-m-d H:i:s' ),
            'user_prompt'        => $user_prompt
        ) );
    }

    private function handle_fallback_state( WP_Error $error, array $selected_model_data, array $failed_models, array $models ) {
        $err_data  = $error->get_error_data();
        $http_code = $err_data['http_code'] ?? 0;
        $err_msg   = strtolower( (string) $error->get_error_message() );
        
        $is_timeout = ( $error->get_error_code() === 'http_request_failed' && ( strpos( $err_msg, 'curl error 28' ) !== false || strpos( $err_msg, 'timed out' ) !== false ) );
        $is_busy    = ( $http_code === 503 || $http_code === 429 || strpos( $err_msg, 'high demand' ) !== false || strpos( $err_msg, 'quota' ) !== false );

        if ( $is_busy || $is_timeout ) {
            $failed_models[] = $selected_model_data['id'];
            $available_fallbacks = array();
            
            foreach ( $models as $m ) {
                if ( in_array( $m['id'], $failed_models ) ) continue;
                if ( ! empty( $m['premium'] ) && get_current_user_id() === 0 ) continue;
                $available_fallbacks[] = array( 'id' => $m['id'], 'label' => $m['label'] );
            }

            if ( count( $available_fallbacks ) > 0 ) {
                $reason = ( $http_code === 429 ) ? 'has exceeded its quota limit' : 'is currently experiencing high demand';
                return rest_ensure_response( array(
                    'code'                => 'fallback_required',
                    'message'             => sprintf( __( 'The AI Model (%s) %s.', 'aidoforyou-stock-metadata' ), $selected_model_data['label'], $reason ),
                    'available_fallbacks' => $available_fallbacks,
                    'failed_models'       => $failed_models
                ) );
            } else {
                $this->log_api_error( "All models exhausted. Last error: " . $err_msg );
                return new WP_Error( 'ai_exhausted', __( 'All available models are currently overloaded. Please try again later.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
            }
        }
        $this->log_api_error( "Direct AI Error: " . $error->get_error_message() );
        return new WP_Error( 'ai_error', $error->get_error_message(), array( 'status' => 400 ) );
    }
    
    private function log_api_error( string $message ): void {
        $logs = get_option( 'aidofy_meta_error_logs', array() );
        if ( ! is_array( $logs ) ) $logs = array();
        array_unshift( $logs, array( 'time' => current_time( 'Y-m-d H:i:s' ), 'message' => $message, 'code' => 'API' ) );
        update_option( 'aidofy_meta_error_logs', array_slice( $logs, 0, 20 ), false );
    }
}