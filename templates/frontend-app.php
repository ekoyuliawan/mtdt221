<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$max_mb = (int) get_option('aidofy_meta_max_mb', 5);
$max_bulk = (int) get_option('aidofy_meta_max_bulk', 10);
$peak_rpm = (int) get_option('aidofy_meta_peak_rpm', 5);
$auto_retry_interval = (int) get_option('aidofy_meta_auto_retry_interval', 1);
$auto_retry_max      = (int) get_option('aidofy_meta_auto_retry_max', 3);
$cost   = (int) get_option('aidofy_meta_credit_cost', 2);
?>
<script>
    window.AIDOFY_META_CONFIG_OVERRIDES = {
        max_bulk: <?php echo $max_bulk; ?>,
        peak_rpm: <?php echo $peak_rpm; ?>,
        auto_retry_interval: <?php echo $auto_retry_interval; ?>,
        auto_retry_max: <?php echo $auto_retry_max; ?>
    };
</script>

<div id="aidofy-meta-app" class="aidofy-theme-prism">
    <div class="aidofy-meta-topbar">
        <div class="aidofy-meta-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            AIdoforyou Stock Metadata 
            <span class="aidofy-meta-version-badge">v<?php echo esc_html(AIDOFY_META_VERSION); ?></span>
        </div>
        <div class="aidofy-meta-topbar-right">
            <div class="aidofy-meta-account-id-pill" title="Click to copy Account ID">
                ID: <span id="aidofy-meta-account-id-text">Loading...</span>
            </div>
            <div class="aidofy-meta-credits-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <span id="aidofy-meta-credits-text"><?php esc_html_e( 'Checking credits...', 'aidoforyou-stock-metadata' ); ?></span>
            </div>
        </div>
    </div>

    <div id="aidofy-meta-alert-box" class="aidofy-meta-alert" style="display:none;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span id="aidofy-meta-alert-msg"></span>
    </div>

    <div id="aidofy-meta-state-upload" class="aidofy-meta-workspace aidofy-meta-fade-in">
        <div class="aidofy-upload-header">
            <h2><?php esc_html_e( 'Generate Stock Metadata', 'aidoforyou-stock-metadata' ); ?></h2>
            <p>Upload visual assets or provide a text concept to generate accurate metadata.</p>
        </div>
        
        <div class="aidofy-meta-tabs-wrap">
            <button type="button" class="aidofy-meta-tab-btn active" data-tab="media">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Media File
            </button>
            <button type="button" class="aidofy-meta-tab-btn" data-tab="text">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Text Concept
            </button>
        </div>

        <div id="aidofy-meta-area-media" class="aidofy-meta-tab-content active">
            <div id="aidofy-meta-dz" class="aidofy-meta-dropzone">
                <svg class="aidofy-meta-dz-icon" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                <p class="aidofy-meta-dz-title"><?php printf( esc_html__( 'Drop Media Files Here (Bulk max %d)', 'aidoforyou-stock-metadata' ), $max_bulk ); ?></p>
                <p class="aidofy-meta-dz-meta"><?php printf( esc_html__( 'JPG, PNG, WEBP, SVG, AI, EPS, MP4, MOV, MPG, AVI (Limit %dMB)', 'aidoforyou-stock-metadata' ), $max_mb ); ?></p>
                <input type="file" id="aidofy-meta-file-input" multiple accept="image/jpeg, image/png, image/webp, image/svg+xml, application/postscript, application/pdf, .ai, .eps, .svg, video/mp4, video/quicktime, video/mpeg, video/x-msvideo, video/avi" style="display:none;" />
                <button type="button" class="aidofy-meta-btn aidofy-btn-outline" style="margin-top:16px;" onclick="document.getElementById('aidofy-meta-file-input').click();">
                    Browse Files
                </button>
            </div>
        </div>

        <div id="aidofy-meta-area-text" class="aidofy-meta-tab-content" style="display:none;">
            <p class="aidofy-meta-hint" style="text-align:center;">Provide a concept or keyword idea to expand into metadata.</p>
            <textarea id="aidofy-meta-text-input" class="aidofy-meta-input" rows="6" placeholder="e.g. A futuristic cyberpunk city at night..."></textarea>
            <button type="button" id="aidofy-meta-text-submit-btn" class="aidofy-meta-btn aidofy-btn-cyan aidofy-meta-btn-full" style="margin-top:20px;">
                Proceed
            </button>
        </div>
    </div>

    <div id="aidofy-meta-state-workspace" style="display:none;">
        <div class="aidofy-meta-main-grid">
            
            <div class="aidofy-meta-persistent-image-col aidofy-meta-fade-in">
                <div id="aidofy-meta-preview-list" style="display:none;"></div>
                
                <div id="aidofy-meta-preview-media-wrap" class="aidofy-meta-thumb-wrap">
                    <div class="aidofy-meta-thumb-status" id="aidofy-thumb-status-single" style="display:none;"></div>
                    <img id="aidofy-meta-preview-img" src="" alt="Preview" style="display:none;" />
                    <video id="aidofy-meta-preview-vid" controls style="display:none;"></video>
                    <button type="button" class="aidofy-meta-thumb-remove aidofy-meta-cancel-btn" title="Remove Asset">
                        <svg style="width: 16px; height: 16px; stroke: #ffffff !important; fill: none !important; stroke-width: 2.5; stroke-linecap: round;" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                
                <div id="aidofy-meta-preview-text-wrap" class="aidofy-meta-thumb-wrap aidofy-text-preview" style="display:none;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <p id="aidofy-meta-preview-text-snippet"></p>
                    <button type="button" class="aidofy-meta-btn aidofy-btn-outline aidofy-meta-cancel-btn aidofy-meta-btn-full">Edit Text</button>
                </div>

                <div class="aidofy-meta-image-info">
                    <p id="aidofy-meta-file-name" class="aidofy-meta-filename-text"></p>
                    <p class="aidofy-meta-image-tip">Awaiting Generation</p>
                </div>
            </div>
            
            <div class="aidofy-meta-dynamic-panel-col">
                <div id="aidofy-meta-panel-settings" class="aidofy-meta-workspace aidofy-meta-fade-in">
                    <h3 class="aidofy-meta-section-heading"><?php esc_html_e( 'Generation Settings', 'aidoforyou-stock-metadata' ); ?></h3>
                    
                    <div class="aidofy-meta-field">
                        <label class="aidofy-meta-field-label">AI Model</label>
                        <div id="aidofy-meta-model-selection" class="aidofy-meta-model-group"></div>
                    </div>

                    <div class="aidofy-meta-field" id="aidofy-user-prompt-wrap" style="margin-top:24px;">
                        <label class="aidofy-meta-field-label">Additional Context (Optional)</label>
                        <p class="aidofy-meta-hint">Provide specific details to guide the AI's metadata generation.</p>
                        <textarea id="aidofy-meta-user-prompt" class="aidofy-meta-input" rows="4" placeholder="e.g. Emphasize that the object is specifically biodegradable and eco-friendly..."></textarea>
                    </div>

                    <!-- TURNSTILE RENDER TARGET -->
                    <div id="aidofy-turnstile-wrap" style="display:none; justify-content:center; margin-top: 24px;"></div>
                    
                    <div style="margin-top:24px;">
                        <button type="button" id="aidofy-meta-extract-btn" class="aidofy-meta-btn aidofy-btn-cyan aidofy-meta-btn-full aidofy-meta-btn-lg">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <span id="aidofy-meta-btn-text">Generate Metadata (<?php echo $cost; ?> Credits)</span>
                        </button>
                    </div>
                </div>

                <div id="aidofy-meta-panel-processing" class="aidofy-meta-workspace aidofy-meta-fade-in" style="display:none; text-align:center;">
                    <div class="aidofy-matrix-spinner">
                        <div class="aidofy-matrix-spinner-inner"></div>
                    </div>
                    <h3 id="aidofy-meta-proc-title" class="aidofy-meta-proc-title">Analyzing Assets...</h3>
                    <p id="aidofy-meta-proc-desc" class="aidofy-meta-proc-desc">Extracting visual details and creating concepts.</p>
                    <div id="aidofy-meta-proc-timer" class="aidofy-matrix-timer" style="display:none;">00:00:000</div>
                    <div style="margin-top:30px;">
                        <button type="button" id="aidofy-meta-proc-cancel-btn" class="aidofy-meta-btn aidofy-btn-outline">Cancel</button>
                    </div>
                </div>

                <div id="aidofy-meta-panel-bulk-result" class="aidofy-meta-workspace aidofy-meta-fade-in" style="display:none; padding: 0; background: transparent; border: none; box-shadow: none;">
                    <div class="aidofy-insight-board">
                        <div class="aidofy-insight-top">
                            <h3 class="aidofy-insight-title">Generation Summary</h3>
                            <div class="aidofy-insight-badges">
                                <span class="aidofy-badge aidofy-badge-violet" id="aidofy-meta-bulk-model-badge"></span>
                                <span class="aidofy-badge aidofy-badge-cyan" id="aidofy-meta-bulk-time-badge"></span>
                            </div>
                        </div>
                        <div class="aidofy-insight-body">
                            <div class="aidofy-insight-section">
                                <h4>
                                    <svg class="aidofy-concept-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    BATCH EXPORT COMPLETE
                                </h4>
                                <p id="aidofy-meta-bulk-desc" class="aidofy-monospace-text" style="color: #F1F5F9; font-size: 14.5px; line-height: 1.6; font-weight: 400; margin: 0; font-style: italic;">
                                    Metadata arrays have been successfully generated and downloaded. To re-export, please click the Export CSV button below.
                                </p>
                            </div>
                        </div>
                        <div class="aidofy-insight-actions">
                            <button type="button" id="aidofy-meta-bulk-download-btn" class="aidofy-meta-btn aidofy-btn-cyan">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export CSV
                            </button>
                            <button type="button" id="aidofy-meta-bulk-regen-btn" class="aidofy-meta-btn aidofy-btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.92-10.44l.43.43"></path></svg> Regenerate
                            </button>
                            <button type="button" id="aidofy-meta-bulk-reset-btn" class="aidofy-meta-btn aidofy-btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Start New Batch
                            </button>
                        </div>
                    </div>
                </div>

                <div id="aidofy-meta-panel-result" class="aidofy-meta-workspace aidofy-meta-fade-in" style="display:none; padding: 0; background: transparent; border: none; box-shadow: none;">
                    
                    <div class="aidofy-insight-board" style="margin-bottom: 24px;">
                        <div class="aidofy-insight-top">
                            <h3 class="aidofy-insight-title">Generation Summary</h3>
                            <div class="aidofy-insight-badges">
                                <span class="aidofy-badge aidofy-badge-violet" id="aidofy-meta-model-badge"></span>
                                <span class="aidofy-badge aidofy-badge-cyan" id="aidofy-meta-time-badge"></span>
                            </div>
                        </div>
                        <div class="aidofy-insight-body">
                            <div class="aidofy-insight-section">
                                <h4>
                                    <svg class="aidofy-concept-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                                    COMMERCIAL CONCEPT
                                </h4>
                                <p id="aidofy-meta-concept-text" class="aidofy-monospace-text" style="font-style: italic;"></p>
                            </div>
                            <div class="aidofy-insight-section" id="aidofy-meta-user-prompt-badge" style="display:none;">
                                <h4>
                                    <svg class="aidofy-context-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                    USER CONTEXT APPLIED
                                </h4>
                                <p id="aidofy-meta-user-prompt-text" class="aidofy-monospace-text" style="font-style:italic; color: var(--afy-text-muted); margin:0;"></p>
                            </div>
                        </div>
                        <div class="aidofy-insight-actions">
                            <button type="button" id="aidofy-meta-single-download-btn" class="aidofy-meta-btn aidofy-btn-cyan">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export CSV
                            </button>
                            <button type="button" id="aidofy-meta-regen-btn" class="aidofy-meta-btn aidofy-btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.92-10.44l.43.43"></path></svg> Regenerate
                            </button>
                            <button type="button" id="aidofy-meta-reset-btn" class="aidofy-meta-btn aidofy-btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Process Another
                            </button>
                        </div>
                    </div>

                    <div class="aidofy-matrix-blocks">
                        <div style="display:flex; gap:16px;">
                            <div class="aidofy-matrix-block" style="flex:1;">
                                <div class="matrix-block-header">
                                    <span>MEDIA TYPE</span>
                                    <button class="aidofy-meta-copy-icon-btn" data-target="aidofy-meta-res-media" title="Copy"></button>
                                </div>
                                <div class="matrix-block-body">
                                    <textarea id="aidofy-meta-res-media" class="aidofy-meta-result-textarea" readonly rows="1"></textarea>
                                </div>
                            </div>
                            <div class="aidofy-matrix-block" style="flex:1;">
                                <div class="matrix-block-header">
                                    <span>CATEGORY</span>
                                    <button class="aidofy-meta-copy-icon-btn" data-target="aidofy-meta-res-category" title="Copy"></button>
                                </div>
                                <div class="matrix-block-body">
                                    <textarea id="aidofy-meta-res-category" class="aidofy-meta-result-textarea" readonly rows="1"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="aidofy-matrix-block">
                            <div class="matrix-block-header">
                                <span id="aidofy-meta-title-label">TITLE</span>
                                <button class="aidofy-meta-copy-icon-btn" data-target="aidofy-meta-res-title" title="Copy"></button>
                            </div>
                            <div class="matrix-block-body">
                                <textarea id="aidofy-meta-res-title" class="aidofy-meta-result-textarea" readonly></textarea>
                            </div>
                        </div>

                        <div class="aidofy-matrix-block">
                            <div class="matrix-block-header">
                                <span id="aidofy-meta-keywords-label">KEYWORDS</span>
                                <button class="aidofy-meta-copy-icon-btn" data-target="aidofy-meta-res-keywords" title="Copy"></button>
                            </div>
                            <div class="matrix-block-body">
                                <textarea id="aidofy-meta-res-keywords" class="aidofy-meta-result-textarea" readonly></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="aidofy-meta-fallback-modal" class="aidofy-meta-modal-overlay" style="display:none;">
        <div class="aidofy-meta-modal-box">
            <div class="aidofy-meta-modal-content">
                <div class="aidofy-meta-modal-icon-wrap">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
                <h3 class="aidofy-meta-modal-title">Model Unavailable</h3>
                <p id="aidofy-meta-fallback-msg" class="aidofy-meta-modal-desc"></p>
                
                <div id="aidofy-meta-fallback-countdown" class="aidofy-matrix-countdown" style="display:none;">
                    Auto-retrying in <span id="aidofy-fallback-time">0</span>s... (Attempt <span id="aidofy-fallback-attempt">1</span>)
                </div>

                <div class="aidofy-meta-modal-offer">
                    <div class="aidofy-offer-text">Select Fallback Model:</div>
                    <select id="aidofy-meta-fallback-select" class="aidofy-meta-select"></select>
                </div>
                <div class="aidofy-meta-modal-actions">
                    <button id="aidofy-meta-fallback-no" class="aidofy-meta-btn aidofy-btn-outline">Cancel</button>
                    <button id="aidofy-meta-fallback-partial" class="aidofy-meta-btn aidofy-btn-outline" style="display:none; flex-basis: 100%;">Download Saved (0)</button>
                    <button id="aidofy-meta-fallback-yes" class="aidofy-meta-btn aidofy-btn-cyan">Switch & Retry</button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="aidofy-meta-regen-modal" class="aidofy-meta-modal-overlay" style="display:none;">
        <div class="aidofy-meta-modal-box">
            <div class="aidofy-meta-modal-content">
                <h3 class="aidofy-meta-modal-title">Regenerate Metadata</h3>
                <p class="aidofy-meta-modal-desc">Select a different AI model to process this asset.</p>
                <div class="aidofy-meta-modal-offer">
                    <select id="aidofy-meta-regen-select" class="aidofy-meta-select"></select>
                </div>
                <div class="aidofy-meta-modal-actions">
                    <button id="aidofy-meta-regen-no" class="aidofy-meta-btn aidofy-btn-outline">Cancel</button>
                    <button id="aidofy-meta-regen-yes" class="aidofy-meta-btn aidofy-btn-cyan">Regenerate</button>
                </div>
            </div>
        </div>
    </div>
</div>