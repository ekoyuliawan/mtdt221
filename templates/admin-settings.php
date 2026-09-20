<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Stock Metadata Generator Settings', 'aidoforyou-stock-metadata' ); ?></h1>
    <hr class="wp-header-end">
    <?php settings_errors(); ?>
    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px; margin-top: 20px;">
        <div class="card" style="max-width: 100%; margin-top: 0; padding: 10px 20px 20px;">
            <form method="post" action="options.php">
                <?php settings_fields( 'aidofy_stock_metadata_opts' ); do_settings_sections( 'aidoforyou-stock-metadata' ); submit_button(); ?>
            </form>
        </div>

        <div>
            <div class="card" style="margin-top: 0; padding: 20px; background: #fff; margin-bottom: 20px;">
                <h2><?php esc_html_e( 'AI API Tester', 'aidoforyou-stock-metadata' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Test the API connection. AI will automatically use the Default Model and follow your Master System Prompt above.', 'aidoforyou-stock-metadata' ); ?></p>
                <div style="margin-top: 15px;"><textarea id="aidofy-test-prompt" rows="3" style="width:100%;" placeholder="e.g. Please explain what microstock is in one sentence."></textarea></div>
                <div style="margin-top: 15px;">
                    <button type="button" id="aidofy-btn-test-api" class="button button-secondary" style="width:100%;">
                        <span class="dashicons dashicons-format-chat" style="margin-top:4px;"></span> <?php esc_html_e( 'Send Ping to Gemini', 'aidoforyou-stock-metadata' ); ?>
                    </button>
                </div>
                <div id="aidofy-test-result" style="margin-top:20px; padding:12px; background:#f6f7f7; border-left:4px solid #cbd5e1; display:none; white-space: pre-wrap; font-family: monospace; font-size:13px; max-height:300px; overflow-y:auto;"></div>
            </div>

            <div class="card" style="margin-top: 0; padding: 20px; background: #fff;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;"><?php esc_html_e( 'API Error Logs', 'aidoforyou-stock-metadata' ); ?></h2>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'aidofy_clear_logs_action' ); ?>
                        <input type="hidden" name="aidofy_clear_logs" value="1">
                        <button type="submit" class="button button-small" onclick="return confirm('Clear all error logs?');"><?php esc_html_e( 'Clear Logs', 'aidoforyou-stock-metadata' ); ?></button>
                    </form>
                </div>
                <p class="description" style="margin-top: 5px;"><?php esc_html_e( 'Silently logging Gemini connection failures (storing up to the last 20 reports).', 'aidoforyou-stock-metadata' ); ?></p>
                
                <div style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 4px; background: #f8fafc;">
                    <?php
                    $logs = get_option( 'aidofy_meta_error_logs', array() );
                    if ( empty( $logs ) ) {
                        echo '<p style="padding: 15px; margin: 0; color: #10b981; font-weight: bold;">✅ System running flawlessly. No errors recorded.</p>';
                    } else {
                        foreach ( $logs as $log ) {
                            $code = esc_html( (string) ($log['code'] ?? 'N/A') );
                            $time = esc_html( (string) ($log['time'] ?? '') );
                            $msg  = esc_html( (string) ($log['message'] ?? '') );
                            echo '<div style="padding: 10px; border-bottom: 1px solid #e2e8f0;">';
                            echo '<div style="font-size: 11px; color: #64748b; margin-bottom: 4px;">' . $time . ' | HTTP: ' . $code . '</div>';
                            echo '<div style="font-size: 13px; color: #d63638;">' . $msg . '</div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. API Keys Builder
    (function() {
        const wrap = document.getElementById('aidofy-apikeys-builder-wrap');
        if(!wrap) return;
        const rowsContainer = document.getElementById('aidofy-apikeys-rows');
        const btnAdd = document.getElementById('aidofy-btn-add-apikey');
        const hiddenInput = document.getElementById('aidofy_meta_api_keys');
        let keysData = [];
        try { keysData = JSON.parse(hiddenInput.value); } catch(e) { keysData = []; }
        
        function renderKeys() {
            rowsContainer.innerHTML = '';
            keysData.forEach((k, idx) => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex; gap:10px; align-items:center;';
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'regular-text';
                input.value = k;
                input.placeholder = 'AIzaSy...';
                input.oninput = (e) => { keysData[idx] = e.target.value; hiddenInput.value = JSON.stringify(keysData); };
                
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'button button-link-delete';
                btnRemove.textContent = 'Remove';
                btnRemove.onclick = () => { keysData.splice(idx, 1); renderKeys(); hiddenInput.value = JSON.stringify(keysData); };
                
                row.appendChild(input);
                row.appendChild(btnRemove);
                rowsContainer.appendChild(row);
            });
        }
        btnAdd.onclick = () => { keysData.push(''); renderKeys(); hiddenInput.value = JSON.stringify(keysData); };
        renderKeys();
    })();

    // 2. Models Config Builder
    (function() {
        const wrap = document.getElementById('aidofy-model-builder-wrap');
        if(!wrap) return;
        const rowsContainer = document.getElementById('aidofy-model-rows');
        const btnAdd = document.getElementById('aidofy-btn-add-model');
        const textarea = document.getElementById('aidofy_meta_models_config');
        let data = [];
        try { data = JSON.parse(textarea.value); } catch(e) { data = []; }

        function render() {
            rowsContainer.innerHTML = '';
            data.forEach((item, index) => {
                const row = document.createElement('div');
                row.style.cssText = 'background:#fff; padding:15px; border:1px solid #e2e8f0; border-radius:6px; display:flex; flex-direction:column; gap:10px;';
                
                const topRow = document.createElement('div');
                topRow.style.cssText = 'display:flex; gap:10px; align-items:center;';
                
                const idInput = document.createElement('input');
                idInput.type = 'text'; idInput.placeholder = 'Model ID (gemini-...)'; idInput.value = item.id || ''; idInput.style.flex = '2';
                idInput.oninput = (e) => { data[index].id = e.target.value; update(); };
                
                const labelInput = document.createElement('input');
                labelInput.type = 'text'; labelInput.placeholder = 'Label'; labelInput.value = item.label || ''; labelInput.style.flex = '1';
                labelInput.oninput = (e) => { data[index].label = e.target.value; update(); };

                const thinkInput = document.createElement('select');
                thinkInput.innerHTML = '<option value="">No Thinking</option><option value="low">Low Thinking</option><option value="high">High Thinking</option>';
                thinkInput.value = item.thinking || '';
                thinkInput.onchange = (e) => { data[index].thinking = e.target.value; update(); };
                
                const premiumLabel = document.createElement('label');
                premiumLabel.innerHTML = '<input type="checkbox" ' + (item.premium ? 'checked' : '') + '> Premium';
                premiumLabel.querySelector('input').onchange = (e) => { data[index].premium = e.target.checked; update(); };
                
                const defLabel = document.createElement('label');
                defLabel.innerHTML = '<input type="radio" name="aidofy_default_model" ' + (item.default ? 'checked' : '') + '> Default';
                defLabel.querySelector('input').onchange = () => { data.forEach(d => d.default = false); data[index].default = true; update(); };
                
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button'; btnRemove.className = 'button button-link-delete'; btnRemove.textContent = 'Remove';
                btnRemove.onclick = () => { data.splice(index, 1); render(); update(); };
                
                topRow.append(idInput, labelInput, thinkInput, premiumLabel, defLabel, btnRemove);
                row.appendChild(topRow);
                rowsContainer.appendChild(row);
            });
        }
        function update() { textarea.value = JSON.stringify(data); }
        btnAdd.onclick = () => { data.push({ id: '', label: '', premium: false, default: false, thinking: '' }); render(); update(); };
        render();
    })();

    // 3. Modular Rules Builder
    (function() {
        const wrap = document.getElementById('aidofy-modules-builder-wrap');
        if(!wrap) return;
        const rowsContainer = document.getElementById('aidofy-modules-rows');
        const btnAdd = document.getElementById('aidofy-btn-add-module');
        const textarea = document.getElementById('aidofy_meta_modular_rules');
        let data = [];
        try { data = JSON.parse(textarea.value); } catch(e) { data = []; }

        function render() {
            rowsContainer.innerHTML = '';
            data.forEach((item, index) => {
                const row = document.createElement('div');
                row.style.cssText = 'background:#fff; padding:15px; border:1px solid #e2e8f0; border-radius:6px; display:flex; flex-direction:column; gap:10px;';
                
                const topRow = document.createElement('div');
                topRow.style.cssText = 'display:flex; gap:10px; align-items:center;';
                
                const idInput = document.createElement('input');
                idInput.type = 'text'; idInput.placeholder = 'ID'; idInput.value = item.id || ''; idInput.style.width = '150px';
                idInput.oninput = (e) => { data[index].id = e.target.value; update(); };
                
                const titleInput = document.createElement('input');
                titleInput.type = 'text'; titleInput.placeholder = 'Title'; titleInput.value = item.title || ''; titleInput.style.flex = '1';
                titleInput.oninput = (e) => { data[index].title = e.target.value; update(); };
                
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button'; btnRemove.className = 'button button-link-delete'; btnRemove.textContent = 'Remove';
                btnRemove.onclick = () => { data.splice(index, 1); render(); update(); };
                
                topRow.append(idInput, titleInput, btnRemove);
                
                const contentInput = document.createElement('textarea');
                contentInput.rows = 4; contentInput.value = item.content || ''; contentInput.style.width = '100%';
                contentInput.oninput = (e) => { data[index].content = e.target.value; update(); };
                
                row.append(topRow, contentInput);
                rowsContainer.appendChild(row);
            });
        }
        function update() { textarea.value = JSON.stringify(data); }
        btnAdd.onclick = () => { data.push({ id: '', title: '', content: '' }); render(); update(); };
        render();
    })();
});
</script>