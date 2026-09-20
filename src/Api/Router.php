<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Api;

use AIdoforyou\StockMetadata\Api\Controllers\AnalyzeController;
use AIdoforyou\StockMetadata\Api\Controllers\GenerateController;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Router {

    private const NAMESPACE = 'aidofy-metadata/v1';

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/extract/analyze', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( new AnalyzeController(), 'handle_request' ),
            'permission_callback' => '__return_true'
        ) );

        register_rest_route( self::NAMESPACE, '/extract/generate', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( new GenerateController(), 'handle_request' ),
            'permission_callback' => '__return_true'
        ) );
    }
}