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

class AnalyzeController extends BaseController {

    public function handle_request( WP_REST_Request $request ) {
        // Inherited cleanly from BaseController
        $auth = $this->validate_prerequisites( $request, false );
        if ( is_wp_error( $auth ) ) return $auth;

        $files       = $request->get_file_params();
        $text_input  = sanitize_textarea_field( $request->get_param( 'text_input' ) ?: '' );
        $user_prompt = sanitize_textarea_field( $request->get_param( 'prompt' ) ?: '' );
        $file_hash   = sanitize_text_field( $request->get_param( 'file_hash' ) ?: '' );
        $mime_type   = sanitize_text_field( $request->get_param( 'mime_type' ) ?: '' );
        
        if ( empty( $files['media_file'] ) && empty( $text_input ) && empty( $file_hash ) ) {
            return new WP_Error( 'empty_input', __( 'Please provide a file or text input.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
        }

        $storage = new TempStorage();
        $temp_dir = $storage->init_directory();
        $base64_data = '';

        if ( ! empty( $file_hash ) ) {
            if ( ! preg_match( '/^[a-f0-9]{32}\.tmp$/', $file_hash ) ) return new WP_Error( 'invalid_hash', __( 'Invalid file hash.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
            $file_path = $temp_dir . $file_hash;
            if ( ! file_exists( $file_path ) ) return new WP_Error( 'file_expired', __( 'Session expired.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
            $base64_data = base64_encode( (string) file_get_contents( $file_path ) );
        } elseif ( ! empty( $files['media_file'] ) ) {
            $upload_err = $this->handle_upload( $files['media_file'], $temp_dir, $file_hash, $mime_type );
            if ( is_wp_error( $upload_err ) ) return $upload_err;
            $base64_data = base64_encode( (string) file_get_contents( $temp_dir . $file_hash ) );
        }

        $client       = new GeminiClient();
        $system_instr = PromptBuilder::build_analyze_instruction();
        $server_index = (int) $request->get_param( 'server_index' );
        $total_keys   = $client->get_keys_count();
        $start_time   = time();
        $ai_response  = null;

        for ( $i = $server_index; $i < $total_keys; $i++ ) {
            if ( get_transient( 'aidofy_meta_cooldown_' . $i ) ) continue;

            $ai_response = $client->analyze( 'gemini-3.5-flash-lite', $i, $system_instr, $text_input, $user_prompt, $base64_data, $mime_type );

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
                $this->log_api_error( "Analyze Phase: Node {$i} Failed. HTTP {$http_code}. Locked for {$cooldown}s." );
                
                if ( ( time() - $start_time ) > 60 && ( $i + 1 < $total_keys ) ) {
                    return rest_ensure_response( array( 'code' => 'switch_server', 'next_server_index' => $i + 1, 'file_hash' => $file_hash, 'mime_type' => $mime_type ) );
                }
                continue;
            }
            break;
        }

        if ( $ai_response === null ) {
            return new WP_Error( 'ai_error', 'API configuration missing or no models available.', array( 'status' => 500 ) );
        }

        if ( is_wp_error( $ai_response ) ) {
            return new WP_Error( 'ai_error', $ai_response->get_error_message(), array( 'status' => 400 ) );
        }

        $content = $ai_response['content'] ?? '{}';
        $parsed_json = json_decode( $content, true );
        
        if ( ! is_array( $parsed_json ) ) {
            if ( preg_match( '/```(?:json)?\s*(.*?)\s*```/is', $content, $matches ) ) {
                $parsed_json = json_decode( $matches[1], true );
            }
        }
        
        if ( ! is_array( $parsed_json ) ) {
            $parsed_json = array();
        }

        return rest_ensure_response( array(
            'code'               => 0,
            'commercial_concept' => $parsed_json['commercial_concept'] ?? 'General Asset',
            'active_modules'     => is_array( $parsed_json['active_modules'] ?? null ) ? $parsed_json['active_modules'] : array(),
            'file_hash'          => $file_hash,
            'mime_type'          => $mime_type,
            'next_server_index'  => $server_index
        ) );
    }

    private function handle_upload( array $file, string $temp_dir, string &$file_hash, string &$mime_type ): bool|WP_Error {
        if ( ! is_uploaded_file( $file['tmp_name'] ) ) return new WP_Error( 'upload_error', 'Upload failed.', array( 'status' => 400 ) );
        
        $max_bytes = (int) get_option( 'aidofy_meta_max_mb', 5 ) * 1024 * 1024;
        if ( (int) $file['size'] > $max_bytes ) return new WP_Error( 'file_too_large', 'Exceeds max size.', array( 'status' => 400 ) );
        
        if ( function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            if ( $finfo !== false ) { $mime_type = finfo_file( $finfo, $file['tmp_name'] ); finfo_close( $finfo ); }
        }

        $allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/postscript', 'application/pdf', 'application/illustrator', 'application/x-adobe-illustrator', 'video/mp4', 'video/quicktime', 'video/mpeg', 'video/x-msvideo', 'video/avi' );
        if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
            if ( file_exists( $file['tmp_name'] ) && is_writable( $file['tmp_name'] ) ) wp_delete_file( $file['tmp_name'] );
            return new WP_Error( 'invalid_file_type', __( 'Invalid format. Supported: JPG, PNG, WEBP, SVG, AI, EPS, MP4, MOV.', 'aidoforyou-stock-metadata' ), array( 'status' => 400 ) );
        }

        $hash_name = md5( uniqid( (string) wp_rand(), true ) ) . '.tmp';
        if ( ! move_uploaded_file( $file['tmp_name'], $temp_dir . $hash_name ) ) {
            return new WP_Error( 'write_error', 'Failed to write to temp storage.', array( 'status' => 500 ) );
        }
        
        $file_hash = $hash_name;
        return true;
    }

    private function log_api_error( string $message ): void {
        $logs = get_option( 'aidofy_meta_error_logs', array() );
        if ( ! is_array( $logs ) ) $logs = array();
        array_unshift( $logs, array( 'time' => current_time( 'Y-m-d H:i:s' ), 'message' => $message, 'code' => 'API' ) );
        update_option( 'aidofy_meta_error_logs', array_slice( $logs, 0, 20 ), false );
    }
}