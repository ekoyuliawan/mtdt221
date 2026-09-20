<?php
declare(strict_types=1);

namespace AIdoforyou\StockMetadata\Services\AI;

use AIdoforyou\StockMetadata\Config\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PromptBuilder {

    public static function build_analyze_instruction(): string {
        $modular_rules_json = get_option( 'aidofy_meta_modular_rules', Defaults::get_modular_rules_json() );
        $modular_rules      = json_decode( (string) $modular_rules_json, true ) ?: array();
        
        $modules_desc = "";
        foreach ( $modular_rules as $mod ) {
            $modules_desc .= "- ID: " . $mod['id'] . " (" . $mod['title'] . ")\n";
        }

        return "You are an expert Adobe Stock analyst.\n\nAnalyze this asset to determine the primary 'commercial_concept'. This must be a highly factual description blending the commercial intent with the literal visual facts. Scan for and neutralize any third-party IP, brands, or logos. If 'Additional Context' is provided by the user, you MUST explicitly fuse its specific intent and themes into this commercial concept. Do not hallucinate details not visible in the image. This concept will be used as the absolute ground-truth for downstream metadata generation.\n\nAlso, select which of the following Specific Rule Modules apply to this asset:\n" . $modules_desc;
    }

    public static function build_generate_instruction( string $commercial_concept, array $active_modules ): string {
        $base_rules_str = trim( (string) get_option( 'aidofy_meta_base_prompt', Defaults::get_base_prompt() ) );
        $modular_json   = get_option( 'aidofy_meta_modular_rules', Defaults::get_modular_rules_json() );
        $modular_rules  = json_decode( (string) $modular_json, true ) ?: array();

        $prompt  = "# SYSTEM ROLE & UNIVERSAL RULES\n" . $base_rules_str . "\n\n";
        $prompt .= "## ACTIVE SPECIFIC MODULES\n";
        
        $applied_count = 0;
        foreach ( $modular_rules as $mod ) {
            if ( in_array( $mod['id'], $active_modules, true ) ) {
                $prompt .= "### MODULE: " . strtoupper( (string) $mod['title'] ) . "\n" . $mod['content'] . "\n\n";
                $applied_count++;
            }
        }
        
        if ( $applied_count === 0 ) {
            $prompt .= "(No specific module triggered. Follow standard Adobe Stock universal rules.)\n\n";
        }

        $prompt .= "## SESSION CONTEXT\n";
        $prompt .= "- Identified Commercial Concept: {$commercial_concept}\n";
        $prompt .= "- Instruction: Base all metadata strictly on this Commercial Concept and the Active Modules provided above. Do not hallucinate outside this context.\n";

        return (string) apply_filters( 'aidofy_meta_system_prompt', $prompt, $commercial_concept, $active_modules );
    }
}