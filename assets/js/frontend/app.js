document.addEventListener('DOMContentLoaded', () => {
    if (typeof AIDOFY_META_CONFIG === 'undefined' || !window.Aidofy || !window.Aidofy.API || !window.Aidofy.UI || !window.Aidofy.Utils) return; 

    const configOverrides = window.AIDOFY_META_CONFIG_OVERRIDES || {};
    const MAX_BULK = parseInt(configOverrides.max_bulk || 10);
    const PEAK_RPM = parseInt(configOverrides.peak_rpm || 5);
    const RPM_DELAY_MS = (60 / PEAK_RPM) * 1000;
    const RETRY_INTERVAL_MS = (parseInt(configOverrides.auto_retry_interval) || 1) * 60 * 1000;
    const RETRY_MAX = parseInt(configOverrides.auto_retry_max) || 3;

    const api = new window.Aidofy.API(AIDOFY_META_CONFIG);
    const ui  = new window.Aidofy.UI();
    
    let autoRescueTimer = null;

    const state = {
        credits: 0,
        cost: AIDOFY_META_CONFIG.cost || 2,
        maxMb: AIDOFY_META_CONFIG.max_mb || 5,
        models: AIDOFY_META_CONFIG.models || [],
        activeModelId: '',
        activeTab: 'media',
        files: [], 
        isBulk: false,
        bulkResults: [],
        regenCount: 0,
        currentBatchTimestamp: null
    };

    async function init() {
        const defaultModel = state.models.find(m => m.default && (!m.premium || api.isLogged));
        state.activeModelId = defaultModel ? defaultModel.id : (state.models.length ? state.models[0].id : '');
        ui.renderModels(state.models, state.activeModelId, api.isLogged, (id) => state.activeModelId = id);

        if (ui.els.accountIdText) {
            const actId = api.accountId || 'Guest';
            const displayId = api.isLogged ? actId : String(actId).substring(0, 8) + '...';
            ui.els.accountIdText.textContent = displayId;
            ui.els.accountIdText.parentElement.addEventListener('click', () => {
                const temp = document.createElement("input");
                temp.value = actId; 
                document.body.appendChild(temp); temp.select(); document.execCommand("copy"); document.body.removeChild(temp);
                const old = ui.els.accountIdText.textContent;
                ui.els.accountIdText.textContent = 'Copied!'; 
                ui.els.accountIdText.parentElement.style.color = '#10b981';
                setTimeout(() => { 
                    ui.els.accountIdText.textContent = old; 
                    ui.els.accountIdText.parentElement.style.color = 'var(--afy-text-muted)'; 
                }, 1500);
            });
        }

        // Initialize Cloudflare Turnstile if properly configured and enabled
        if (AIDOFY_META_CONFIG.turnstile_enabled && AIDOFY_META_CONFIG.turnstile_site_key && ui.els.turnstileWrap) {
            ui.els.turnstileWrap.style.display = 'flex';
            ui.els.turnstileWrap.innerHTML = `<div class="cf-turnstile" data-sitekey="${AIDOFY_META_CONFIG.turnstile_site_key}" data-theme="dark"></div>`;
        }

        state.credits = await api.fetchCredits();
        ui.updateCreditsDisplay(state.credits, state.cost, state.isBulk, state.files.length);
        bindEvents();
    }

    function resetTurnstile() {
        if (window.turnstile) {
            try { turnstile.reset(); } catch(e) {}
        }
    }

    function bindEvents() {
        ui.els.tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                ui.els.tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                state.activeTab = btn.getAttribute('data-tab');
                ui.els.areaMedia.style.display = state.activeTab === 'media' ? '' : 'none';
                ui.els.areaText.style.display = state.activeTab === 'text' ? '' : 'none';
                
                if (ui.els.userPromptWrap) {
                    ui.els.userPromptWrap.style.display = state.activeTab === 'text' ? 'none' : 'block';
                }
            });
        });

        if (ui.els.fileInput) ui.els.fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        if (ui.els.textSubmitBtn) ui.els.textSubmitBtn.addEventListener('click', handleText);

        if (ui.els.dz) {
            ui.els.dz.addEventListener('click', (e) => { 
                if (e.target !== ui.els.fileInput && e.target.tagName !== 'BUTTON') ui.els.fileInput.click(); 
            });
            ui.els.dz.addEventListener('dragover', (e) => { e.preventDefault(); ui.els.dz.classList.add('over'); });
            ui.els.dz.addEventListener('dragleave', () => ui.els.dz.classList.remove('over'));
            ui.els.dz.addEventListener('drop', (e) => {
                e.preventDefault(); ui.els.dz.classList.remove('over');
                if (e.dataTransfer && e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);
            });
        }

        document.addEventListener('click', (e) => {
            const cancelBtn = e.target.closest('.aidofy-meta-cancel-btn');
            if (!cancelBtn) return;
            
            if (ui.els.app.classList.contains('aidofy-state-result')) return;
            if (ui.els.app.classList.contains('aidofy-is-locked')) return;

            if (cancelBtn.closest('#aidofy-meta-preview-text-wrap') || cancelBtn.closest('#aidofy-meta-preview-media-wrap')) {
                resetApp();
            } else if (cancelBtn.closest('.aidofy-meta-thumb-wrap')) {
                const wrap = cancelBtn.closest('.aidofy-meta-thumb-wrap');
                if (wrap && wrap.id && wrap.id.startsWith('aidofy-thumb-')) {
                    const idx = parseInt(wrap.id.replace('aidofy-thumb-', ''));
                    state.files.splice(idx, 1);
                    
                    if (state.files.length === 0) {
                        resetApp();
                    } else if (state.files.length === 1) {
                        const remaining = state.files[0];
                        resetApp();
                        handleFiles([remaining]); 
                    } else {
                        wrap.style.opacity = '0';
                        setTimeout(() => {
                            wrap.remove(); 
                            document.querySelectorAll('#aidofy-meta-preview-list .aidofy-meta-thumb-wrap').forEach((el, newIdx) => {
                                el.id = 'aidofy-thumb-' + newIdx;
                                const badge = el.querySelector('.aidofy-meta-thumb-status');
                                if (badge) badge.id = 'aidofy-thumb-status-' + newIdx;
                            });
                            ui.els.fileName.textContent = `${state.files.length} Assets Queued`;
                            ui.updateCreditsDisplay(state.credits, state.cost, true, state.files.length);
                        }, 200);
                    }
                } else {
                    resetApp();
                }
            }
        });

        if (ui.els.resetBtn) ui.els.resetBtn.addEventListener('click', () => window.location.reload());
        if (ui.els.bulkResetBtn) ui.els.bulkResetBtn.addEventListener('click', () => window.location.reload());
        
        if (ui.els.singleDownloadBtn) ui.els.singleDownloadBtn.addEventListener('click', () => {
            const saved = sessionStorage.getItem('aidofy_meta_last_result');
            if (saved) window.Aidofy.Utils.exportSingleCSV(JSON.parse(saved).res, state.currentBatchTimestamp);
        });
        if (ui.els.bulkDownloadBtn) ui.els.bulkDownloadBtn.addEventListener('click', () => window.Aidofy.Utils.exportBulkCSV(state.bulkResults, state.currentBatchTimestamp));

        if (ui.els.procCancelBtn) ui.els.procCancelBtn.addEventListener('click', () => { 
            api.abort(); 
            ui.stopTimer();
            if (!state.isBulk) {
                ui.setState('settings');
                ui.updateThumbnailStatus(0, 'Canceled', 'canceled', true);
                if (ui.els.fileName) ui.els.fileName.textContent = 'Generation Canceled';
                if (ui.els.imageTip) ui.els.imageTip.textContent = 'Process was aborted by user.';
            }
        });

        if (ui.els.extractBtn) ui.els.extractBtn.addEventListener('click', () => {
            if (state.activeTab === 'media' && state.files.length === 0) return;
            
            // Client-Side Pre-Check for Turnstile to save network overhead
            if (AIDOFY_META_CONFIG.turnstile_enabled) {
                const turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');
                if (!turnstileResponse || !turnstileResponse.value) {
                    ui.showError("Please complete the security check.", 4000);
                    return;
                }
            }

            ui.hideError(); ui.setState('processing'); ui.startTimer();
            state.currentBatchTimestamp = new Date().getTime();
            if (state.isBulk) {
                runBatch(); 
            } else {
                ui.updateThumbnailStatus(0, 'Processing...', 'processing', true);
                executePipeline(buildFormData(0), 0, 0);
            }
        });

        if (ui.els.regenBtn) ui.els.regenBtn.addEventListener('click', () => {
            if (state.regenCount >= 3) { alert('Max regenerate limit reached.'); return; }
            ui.showRegenModal(state.models, state.activeModelId, api.isLogged, (selectedModelId) => {
                state.activeModelId = selectedModelId;
                ui.renderModels(state.models, state.activeModelId, api.isLogged, (id) => state.activeModelId = id);
                state.regenCount++;
                ui.hideError(); ui.setState('processing'); ui.startTimer();
                ui.updateThumbnailStatus(0, 'Processing...', 'processing', true);
                executePipeline(buildFormData(0), 0, 0);
            }, () => {});
        });

        if (ui.els.bulkRegenBtn) ui.els.bulkRegenBtn.addEventListener('click', () => {
            if (state.regenCount >= 3) { alert('Max regenerate limit reached.'); return; }
            ui.showRegenModal(state.models, state.activeModelId, api.isLogged, (selectedModelId) => {
                state.activeModelId = selectedModelId;
                ui.renderModels(state.models, state.activeModelId, api.isLogged, (id) => state.activeModelId = id);
                state.regenCount++;
                ui.hideError(); ui.setState('processing'); ui.startTimer();
                runBatch();
            }, () => {});
        });
    }

    function buildFormData(fileIndex) {
        const fd = new FormData();
        if (state.activeTab === 'media') fd.append('media_file', state.files[fileIndex]);
        else fd.append('text_input', ui.els.textInputRaw.value.trim());
        if (ui.els.userPrompt) fd.append('prompt', ui.els.userPrompt.value.trim());
        
        // Securely appends Turnstile Response Token to payload
        const turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');
        if (turnstileResponse) {
            fd.append('cf_turnstile_response', turnstileResponse.value);
        }

        return fd;
    }

    function handleFiles(fileList) {
        const files = Array.from(fileList);
        if (files.length > MAX_BULK || files.length === 0) return;
        state.files = files;
        state.isBulk = files.length > 1;
        ui.els.fileName.textContent = state.isBulk ? `${files.length} Assets Queued` : files[0].name;
        
        if (ui.els.prevTextWrap) ui.els.prevTextWrap.style.display = 'none';

        if (!state.isBulk) {
            if (ui.els.userPromptWrap) ui.els.userPromptWrap.style.display = 'block';
            if (ui.els.previewList) ui.els.previewList.style.display = 'none';
            if (ui.els.prevMediaWrap) ui.els.prevMediaWrap.style.display = '';
            
            const f = state.files[0];
            if (f.name.match(/\.(ai|eps)$/i)) {
                const vectorSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"><rect width="400" height="300" fill="%231E293B"/><svg x="176" y="100" width="48" height="48" fill="none" stroke="%233B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg><text x="200" y="180" font-family="system-ui, sans-serif" font-weight="700" font-size="16" fill="%2394A3B8" text-anchor="middle">Vector Format</text></svg>';
                if (ui.els.prevVid) ui.els.prevVid.style.display = 'none';
                if (ui.els.prevImg) {
                    ui.els.prevImg.style.display = '';
                    ui.els.prevImg.src = vectorSvg;
                }
            } else if (f.type.startsWith('video/')) {
                if (ui.els.prevImg) ui.els.prevImg.style.display = 'none';
                if (ui.els.prevVid) {
                    ui.els.prevVid.style.display = '';
                    ui.els.prevVid.src = URL.createObjectURL(f);
                }
            } else {
                if (ui.els.prevVid) ui.els.prevVid.style.display = 'none';
                if (ui.els.prevImg) {
                    ui.els.prevImg.style.display = '';
                    ui.els.prevImg.src = URL.createObjectURL(f);
                }
            }
        } else {
            if (ui.els.userPromptWrap) ui.els.userPromptWrap.style.display = 'none';
            if (ui.els.prevMediaWrap) ui.els.prevMediaWrap.style.display = 'none';
            ui.renderThumbnails(state.files);
        }

        if (ui.els.imageTip) ui.els.imageTip.textContent = 'Awaiting Generation';
        ui.updateCreditsDisplay(state.credits, state.cost, state.isBulk, state.files.length);
        ui.setState('settings');
    }

    function handleText() {
        const text = ui.els.textInputRaw.value.trim();
        if (!text) return;

        if (text.length < 15) {
            ui.showError("Concept is too short. Please provide a more detailed description (at least 15 characters).", 4000);
            return;
        }

        ui.hideError();
        state.isBulk = false; state.files = [];
        ui.els.fileName.textContent = 'Text Concept';
        if (ui.els.imageTip) ui.els.imageTip.textContent = 'Awaiting Generation';
        
        if (ui.els.prevMediaWrap) ui.els.prevMediaWrap.style.display = 'none';
        if (ui.els.previewList) ui.els.previewList.style.display = 'none';
        if (ui.els.prevTextWrap) {
            ui.els.prevTextWrap.style.display = 'block';
            ui.els.prevTextSnip.textContent = `"${text}"`;
        }

        if (ui.els.userPromptWrap) ui.els.userPromptWrap.style.display = 'none';
        ui.updateCreditsDisplay(state.credits, state.cost, false, 0);
        ui.setState('settings');
    }

    function resetApp() { 
        state.files = []; 
        state.isBulk = false; 
        if (ui.els.prevImg) ui.els.prevImg.src = '';
        if (ui.els.prevVid) ui.els.prevVid.src = '';
        const singleBadge = document.getElementById('aidofy-thumb-status-single');
        if (singleBadge) singleBadge.style.display = 'none';
        ui.setState('upload'); 
    }

    async function handleFallbackAwait(res, fd, isBulk, completedCount, retryAttempt) {
        return new Promise((resolve, reject) => {
            ui.stopTimer();
            let countdownInterval = null;
            const cleanup = () => { 
                if(countdownInterval) clearInterval(countdownInterval); 
                if(autoRescueTimer) clearTimeout(autoRescueTimer); 
            };

            ui.showFallbackModal(res.message, res.available_fallbacks,
                (newModel) => {
                    cleanup();
                    state.activeModelId = newModel;
                    ui.renderModels(state.models, state.activeModelId, api.isLogged, (id) => state.activeModelId = id);
                    ui.setState('processing');
                    ui.startTimer();
                    resolve(newModel);
                },
                (actionType) => {
                    cleanup();
                    reject(new Error(actionType === 'partial' ? 'Partial Download' : 'Process cancelled.'));
                },
                isBulk, completedCount, retryAttempt, RETRY_MAX
            );

            if (retryAttempt > 0 && retryAttempt <= RETRY_MAX) {
                const start = Date.now();
                countdownInterval = setInterval(() => {
                    const remain = RETRY_INTERVAL_MS - (Date.now() - start);
                    if (remain <= 0) { 
                        cleanup(); 
                        ui.els.modal.style.display = 'none';
                        ui.setState('processing'); 
                        ui.startTimer(); 
                        resolve(state.activeModelId); 
                    } else if (ui.els.fallbackTimeTxt) {
                        ui.els.fallbackTimeTxt.textContent = Math.ceil(remain / 1000);
                    }
                }, 500);
            } else if (isBulk && completedCount > 0) {
                autoRescueTimer = setTimeout(() => { 
                    cleanup(); 
                    ui.els.modal.style.display = 'none';
                    reject(new Error('Partial Download')); 
                }, 3 * 60 * 1000);
            }
        });
    }

    async function executePipeline(fd, indexInBatch, retryAttempt) {
        try {
            if (!fd.has('file_hash') && !fd.has('commercial_concept')) {
                ui.updateProcessingText('Analyzing Assets...', 'Extracting visual details and identifying concepts.');
                const aRes = await api.analyze(fd);
                if (aRes.code === 'switch_server') { fd.set('server_index', aRes.next_server_index); return executePipeline(fd, indexInBatch, retryAttempt); }
                fd.set('file_hash', aRes.file_hash); fd.set('mime_type', aRes.mime_type); fd.set('commercial_concept', aRes.commercial_concept);
                fd.set('active_modules', JSON.stringify(aRes.active_modules));
                if (fd.has('media_file')) fd.delete('media_file');
            }

            ui.updateProcessingText('Generating Metadata...', 'Synthesizing title and keyword architecture.');
            fd.set('model', state.activeModelId);
            const gRes = await api.generate(fd);
            if (gRes.code === 'switch_server') { fd.set('server_index', gRes.next_server_index); return executePipeline(fd, indexInBatch, retryAttempt); }
            if (gRes.code === 'fallback_required') {
                if (RETRY_MAX > 0 && retryAttempt >= RETRY_MAX) throw new Error(state.isBulk ? 'Partial Download' : `Failed after ${RETRY_MAX} retries.`);
                const origModel = state.activeModelId;
                const newModel = await handleFallbackAwait(gRes, fd, state.isBulk, state.bulkResults.length, retryAttempt + 1);
                return executePipeline(fd, indexInBatch, (newModel === origModel) ? retryAttempt + 1 : 0);
            }

            if (!state.isBulk) {
                ui.updateThumbnailStatus(0, 'Done', 'done', true);
                if (ui.els.fileName) ui.els.fileName.textContent = 'Processing Complete';
                if (ui.els.imageTip) ui.els.imageTip.textContent = '1 Successful';
                
                sessionStorage.setItem('aidofy_meta_last_result', JSON.stringify({res: gRes}));
                ui.renderResults(gRes);
            }
            return gRes;

        } catch (e) {
            resetTurnstile(); // Reset CAPTCHA upon rejection so they can try again smoothly
            
            if (e.message === 'Process cancelled.' || e.name === 'AbortError') { 
                if (!state.isBulk) {
                    ui.setState('settings');
                    ui.updateThumbnailStatus(0, 'Canceled', 'canceled', true);
                    if (ui.els.fileName) ui.els.fileName.textContent = 'Generation Canceled';
                    if (ui.els.imageTip) ui.els.imageTip.textContent = 'Process was aborted by user.';
                }
                return null; 
            }
            if (!state.isBulk) {
                ui.setState('settings');
                ui.updateThumbnailStatus(0, 'Failed', 'error', true);
                if (ui.els.fileName) ui.els.fileName.textContent = 'Processing Failed';
                if (ui.els.imageTip) ui.els.imageTip.textContent = 'An error occurred during extraction.';
                ui.showError(e.message);
            }
            if (e.message === 'Partial Download') throw e;
            throw e;
        }
    }

    async function runBatch() {
        state.bulkResults = [];
        let success = 0, failed = 0, canceled = 0;
        let aborted = false;

        for (let i = 0; i < state.files.length; i++) {
            if (aborted) {
                ui.updateThumbnailStatus(i, 'Canceled', 'canceled');
                canceled++;
                continue;
            }

            if (i > 0) await new Promise(r => setTimeout(r, RPM_DELAY_MS));
            ui.updateThumbnailStatus(i, 'Processing...', 'processing');
            
            try {
                const res = await executePipeline(buildFormData(i), i, 0);
                if (res) { 
                    res.filename = state.files[i].name; 
                    state.bulkResults.push(res); 
                    ui.updateThumbnailStatus(i, 'Done', 'done'); 
                    success++;
                } else {
                    ui.updateThumbnailStatus(i, 'Canceled', 'canceled');
                    canceled++;
                    aborted = true; 
                }
            } catch (e) {
                if (e.message === 'Partial Download') { 
                    ui.updateThumbnailStatus(i, 'Canceled', 'canceled'); 
                    canceled++;
                    aborted = true; 
                } else {
                    ui.updateThumbnailStatus(i, 'Failed', 'error');
                    failed++;
                }
            }
        }
        
        if (ui.els.fileName) ui.els.fileName.textContent = 'Processing Complete';
        
        const summaryArr = [];
        if (success > 0) summaryArr.push(`${success} Successful`);
        if (failed > 0) summaryArr.push(`${failed} Failed`);
        if (canceled > 0) summaryArr.push(`${canceled} Canceled`);
        if (ui.els.imageTip) ui.els.imageTip.textContent = summaryArr.join(', ');

        if (state.bulkResults.length > 0) {
            window.Aidofy.Utils.exportBulkCSV(state.bulkResults, state.currentBatchTimestamp);
            ui.renderBulkCompletion(state.bulkResults.length, state.bulkResults[0], success, failed, canceled);
        } else if (aborted && state.bulkResults.length === 0) {
            ui.setState('settings');
        }
        
        resetTurnstile(); // Reset CAPTCHA upon successful completion of batch
    }

    init();
});