<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Services\File;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TempStorage {

    private string $temp_dir;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = trailingslashit( $upload_dir['basedir'] ) . 'aidofy_meta_temp/';
    }

    public function init_directory(): string {
        if ( ! file_exists( $this->temp_dir ) ) {
            wp_mkdir_p( $this->temp_dir );
        }
        
        $htaccess = $this->temp_dir . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Deny from all\n<Files *>\nOrder Allow,Deny\nDeny from all\n</Files>\nOptions -Indexes\n" );
        }
        
        $index = $this->temp_dir . 'index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php\n// Silence is golden." );
        }
        
        return $this->temp_dir;
    }

    public function garbage_collection(): void {
        if ( ! is_dir( $this->temp_dir ) ) return;
        
        $files = glob( $this->temp_dir . '*.tmp' );
        if ( ! empty( $files ) && is_array( $files ) ) {
            $now = time();
            foreach ( $files as $file ) {
                if ( is_file( $file ) && ( $now - filemtime( $file ) >= 900 ) ) {
                    if ( is_writable( $file ) ) {
                        wp_delete_file( $file );
                    } else {
                        $this->log_filesystem_error( $file );
                    }
                }
            }
        }
    }

    private function log_filesystem_error( string $file ): void {
        $logs = get_option( 'aidofy_meta_error_logs', array() );
        if ( ! is_array( $logs ) ) $logs = array();
        
        array_unshift( $logs, array(
            'time'    => current_time( 'Y-m-d H:i:s' ),
            'message' => 'Filesystem Error: Could not delete orphaned temp file: ' . basename( $file ),
            'code'    => 'fs_error'
        ) );
        
        $logs = array_slice( $logs, 0, 20 );
        update_option( 'aidofy_meta_error_logs', $logs, false );
    }
}