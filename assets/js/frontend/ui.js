window.Aidofy = window.Aidofy || {};

window.Aidofy.UI = class {
    constructor() {
        this.els = this.cacheDOM();
        this.iconCopy = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
        this.iconCheck = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        this.timerInterval = null;
        this.errorTimer = null;
    }

    cacheDOM() {
        const dom = {
            app: document.getElementById('aidofy-meta-app'),
            accountIdText: document.getElementById('aidofy-meta-account-id-text'),
            creditsText: document.getElementById('aidofy-meta-credits-text'),
            alertBox: document.getElementById('aidofy-meta-alert-box'),
            alertMsg: document.getElementById('aidofy-meta-alert-msg'),
            
            sUpload: document.getElementById('aidofy-meta-state-upload'),
            sWorkspace: document.getElementById('aidofy-meta-state-workspace'), 
            pSettings: document.getElementById('aidofy-meta-panel-settings'),
            pProcessing: document.getElementById('aidofy-meta-panel-processing'),
            pResult: document.getElementById('aidofy-meta-panel-result'),
            pBulkResult: document.getElementById('aidofy-meta-panel-bulk-result'),
            
            tabBtns: document.querySelectorAll('.aidofy-meta-tab-btn'),
            areaMedia: document.getElementById('aidofy-meta-area-media'),
            areaText: document.getElementById('aidofy-meta-area-text'),
            textInputRaw: document.getElementById('aidofy-meta-text-input'),
            textSubmitBtn: document.getElementById('aidofy-meta-text-submit-btn'),

            dz: document.getElementById('aidofy-meta-dz'),
            fileInput: document.getElementById('aidofy-meta-file-input'),
            
            previewList: document.getElementById('aidofy-meta-preview-list'),
            prevMediaWrap: document.getElementById('aidofy-meta-preview-media-wrap'),
            prevImg: document.getElementById('aidofy-meta-preview-img'),
            prevVid: document.getElementById('aidofy-meta-preview-vid'),
            prevTextWrap: document.getElementById('aidofy-meta-preview-text-wrap'),
            prevTextSnip: document.getElementById('aidofy-meta-preview-text-snippet'),
            fileName: document.getElementById('aidofy-meta-file-name'),
            imageTip: document.querySelector('.aidofy-meta-image-tip'),
            
            userPromptWrap: document.getElementById('aidofy-user-prompt-wrap'),
            userPrompt: document.getElementById('aidofy-meta-user-prompt'),
            modelGroup: document.getElementById('aidofy-meta-model-selection'),
            
            turnstileWrap: document.getElementById('aidofy-turnstile-wrap'),

            extractBtn: document.getElementById('aidofy-meta-extract-btn'),
            resetBtn: document.getElementById('aidofy-meta-reset-btn'),
            regenBtn: document.getElementById('aidofy-meta-regen-btn'),
            singleDownloadBtn: document.getElementById('aidofy-meta-single-download-btn'),

            bulkDownloadBtn: document.getElementById('aidofy-meta-bulk-download-btn'),
            bulkResetBtn: document.getElementById('aidofy-meta-bulk-reset-btn'),
            bulkRegenBtn: document.getElementById('aidofy-meta-bulk-regen-btn'),
            bulkDesc: document.getElementById('aidofy-meta-bulk-desc'),
            bulkModelBadge: document.getElementById('aidofy-meta-bulk-model-badge'),
            bulkTimeBadge: document.getElementById('aidofy-meta-bulk-time-badge'),
            
            resMedia: document.getElementById('aidofy-meta-res-media'),
            resCategory: document.getElementById('aidofy-meta-res-category'),
            resTitle: document.getElementById('aidofy-meta-res-title'),
            titleLabel: document.getElementById('aidofy-meta-title-label'),
            resKeywords: document.getElementById('aidofy-meta-res-keywords'),
            keywordsLabel: document.getElementById('aidofy-meta-keywords-label'),
            
            modelBadge: document.getElementById('aidofy-meta-model-badge'),
            timeBadge: document.getElementById('aidofy-meta-time-badge'),
            conceptText: document.getElementById('aidofy-meta-concept-text'),
            userPromptBadge: document.getElementById('aidofy-meta-user-prompt-badge'),
            userPromptTxt: document.getElementById('aidofy-meta-user-prompt-text'),
            
            modal: document.getElementById('aidofy-meta-fallback-modal'),
            modalMsg: document.getElementById('aidofy-meta-fallback-msg'),
            modalSelect: document.getElementById('aidofy-meta-fallback-select'),
            btnYes: document.getElementById('aidofy-meta-fallback-yes'),
            btnNo: document.getElementById('aidofy-meta-fallback-no'),
            btnPartial: document.getElementById('aidofy-meta-fallback-partial'),
            fallbackCountdown: document.getElementById('aidofy-meta-fallback-countdown'),
            fallbackTimeTxt: document.getElementById('aidofy-fallback-time'),
            fallbackAttemptTxt: document.getElementById('aidofy-fallback-attempt'),

            regenModal: document.getElementById('aidofy-meta-regen-modal'),
            regenSelect: document.getElementById('aidofy-meta-regen-select'),
            regenBtnYes: document.getElementById('aidofy-meta-regen-yes'),
            regenBtnNo: document.getElementById('aidofy-meta-regen-no'),

            procTitle: document.getElementById('aidofy-meta-proc-title'),
            procDesc: document.getElementById('aidofy-meta-proc-desc'),
            timerEl: document.getElementById('aidofy-meta-proc-timer')
        };

        if (dom.pProcessing && !document.getElementById('aidofy-meta-proc-cancel-btn')) {
            dom.procCancelBtn = document.createElement('button');
            dom.procCancelBtn.id = 'aidofy-meta-proc-cancel-btn';
            dom.procCancelBtn.className = 'aidofy-meta-btn aidofy-btn-outline';
            dom.procCancelBtn.innerHTML = 'Cancel';
            dom.pProcessing.appendChild(dom.procCancelBtn);
        } else {
            dom.procCancelBtn = document.getElementById('aidofy-meta-proc-cancel-btn');
        }

        return dom;
    }

    setState(name) {
        if (this.els.app) {
            if (name === 'processing') {
                this.els.app.classList.add('aidofy-is-locked');
                this.els.app.classList.remove('aidofy-state-result');
            } else if (name === 'result' || name === 'bulk-result') {
                this.els.app.classList.remove('aidofy-is-locked');
                this.els.app.classList.add('aidofy-state-result'); 
            } else {
                this.els.app.classList.remove('aidofy-is-locked');
                this.els.app.classList.remove('aidofy-state-result');
            }
        }

        if (name === 'upload') {
            if(this.els.sUpload) { this.els.sUpload.style.display = ''; }
            if(this.els.sWorkspace) this.els.sWorkspace.style.display = 'none';
        } else {
            if(this.els.sUpload) this.els.sUpload.style.display = 'none';
            if(this.els.sWorkspace) this.els.sWorkspace.style.display = 'flex';
            
            [this.els.pSettings, this.els.pProcessing, this.els.pResult, this.els.pBulkResult].forEach(el => { if(el) el.style.display = 'none'; });
            
            const activePanel = name === 'settings' ? this.els.pSettings : (name === 'processing' ? this.els.pProcessing : (name === 'bulk-result' ? this.els.pBulkResult : this.els.pResult));
            if (activePanel) activePanel.style.display = '';
        }
    }

    showError(msg, timeout = 5000) {
        if(this.els.alertMsg) this.els.alertMsg.textContent = msg;
        if(this.els.alertBox) {
            this.els.alertBox.style.display = 'flex';
            if (this.errorTimer) clearTimeout(this.errorTimer);
            if (timeout > 0) {
                this.errorTimer = setTimeout(() => {
                    this.hideError();
                }, timeout);
            }
        }
    }

    hideError() {
        if(this.els.alertBox) this.els.alertBox.style.display = 'none';
        if (this.errorTimer) clearTimeout(this.errorTimer);
    }

    updateProcessingText(title, desc) {
        if (this.els.procTitle) this.els.procTitle.textContent = title;
        if (this.els.procDesc) this.els.procDesc.textContent = desc;
    }

    updateCreditsDisplay(credits, cost, isBulk, fileCount) {
        if(this.els.creditsText) this.els.creditsText.textContent = `${credits} Credit${credits === 1 ? '' : 's'} Remaining`;
        if(this.els.extractBtn) {
            const totalCost = isBulk ? (cost * fileCount) : cost;
            this.els.extractBtn.disabled = (credits < totalCost);
            const span = this.els.extractBtn.querySelector('span');
            if (span) span.textContent = isBulk ? `Generate Bulk (${totalCost} Credits)` : `Generate Metadata (${totalCost} Credits)`;
        }
    }

    renderModels(models, activeModelId, isLogged, onSelect) {
        if (!this.els.modelGroup) return;
        this.els.modelGroup.innerHTML = '';
        models.forEach(m => {
            const isLocked = m.premium && !isLogged;
            const labelEl = document.createElement('label');
            labelEl.className = `aidofy-meta-model-card ${isLocked ? 'locked' : ''} ${activeModelId === m.id ? 'active' : ''}`;
            labelEl.innerHTML = `
                <input type="radio" name="ai_model" value="${m.id}" ${isLocked ? 'disabled' : ''} ${activeModelId === m.id ? 'checked' : ''}>
                <span class="aidofy-meta-model-name">${m.label}</span>
                <div class="aidofy-model-check">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
            `;
            if (!isLocked) {
                labelEl.addEventListener('click', () => {
                    this.els.modelGroup.querySelectorAll('.aidofy-meta-model-card').forEach(c => c.classList.remove('active'));
                    labelEl.classList.add('active');
                    onSelect(m.id);
                });
            }
            this.els.modelGroup.appendChild(labelEl);
        });
    }

    showRegenModal(models, activeModelId, isLogged, onConfirm, onCancel) {
        if (!this.els.regenModal) return;
        this.els.regenSelect.innerHTML = '';
        
        models.forEach(m => {
            const isLocked = m.premium && !isLogged;
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.label + (isLocked ? ' (Premium)' : '');
            if (isLocked) opt.disabled = true;
            if (m.id === activeModelId && !isLocked) opt.selected = true;
            this.els.regenSelect.appendChild(opt);
        });

        const newBtnYes = this.els.regenBtnYes.cloneNode(true);
        const newBtnNo = this.els.regenBtnNo.cloneNode(true);
        this.els.regenBtnYes.replaceWith(newBtnYes);
        this.els.regenBtnNo.replaceWith(newBtnNo);
        this.els.regenBtnYes = newBtnYes;
        this.els.regenBtnNo = newBtnNo;

        this.els.regenBtnYes.addEventListener('click', () => {
            this.els.regenModal.style.display = 'none';
            if (onConfirm) onConfirm(this.els.regenSelect.value);
        });
        this.els.regenBtnNo.addEventListener('click', () => {
            this.els.regenModal.style.display = 'none';
            if (onCancel) onCancel();
        });

        this.els.regenModal.style.display = 'flex';
    }

    showFallbackModal(message, fallbacks, onConfirm, onCancel, isBulk, completedCount, retryAttempt, maxRetries) {
        if (!this.els.modal) return;
        this.els.modalMsg.textContent = message;

        this.els.modalSelect.innerHTML = '';
        fallbacks.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.label;
            this.els.modalSelect.appendChild(opt);
        });

        const newBtnYes = this.els.btnYes.cloneNode(true);
        const newBtnNo = this.els.btnNo.cloneNode(true);
        const newBtnPartial = this.els.btnPartial.cloneNode(true);

        this.els.btnYes.replaceWith(newBtnYes);
        this.els.btnNo.replaceWith(newBtnNo);
        this.els.btnPartial.replaceWith(newBtnPartial);

        this.els.btnYes = newBtnYes;
        this.els.btnNo = newBtnNo;
        this.els.btnPartial = newBtnPartial;

        if (isBulk && completedCount > 0) {
            this.els.btnPartial.style.display = 'block';
            this.els.btnPartial.textContent = `Download Saved (${completedCount})`;
        } else {
            this.els.btnPartial.style.display = 'none';
        }

        if (retryAttempt > 0 && retryAttempt <= maxRetries && this.els.fallbackCountdown) {
            this.els.fallbackCountdown.style.display = 'block';
            if (this.els.fallbackAttemptTxt) this.els.fallbackAttemptTxt.textContent = retryAttempt;
        } else if (this.els.fallbackCountdown) {
            this.els.fallbackCountdown.style.display = 'none';
        }

        this.els.btnYes.addEventListener('click', () => {
            this.els.modal.style.display = 'none';
            if (onConfirm) onConfirm(this.els.modalSelect.value);
        });

        this.els.btnNo.addEventListener('click', () => {
            this.els.modal.style.display = 'none';
            if (onCancel) onCancel('cancel');
        });

        this.els.btnPartial.addEventListener('click', () => {
            this.els.modal.style.display = 'none';
            if (onCancel) onCancel('partial');
        });

        this.els.modal.style.display = 'flex';
    }

    startTimer() {
        if (this.timerInterval) clearInterval(this.timerInterval);
        const startTime = Date.now();
        if (this.els.timerEl) {
            this.els.timerEl.style.display = 'block';
            this.timerInterval = setInterval(() => {
                const diff = Date.now() - startTime;
                const m = String(Math.floor(diff / 60000)).padStart(2, '0');
                const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                const ms = String(diff % 1000).padStart(3, '0');
                this.els.timerEl.textContent = `${m}:${s}:${ms}`;
            }, 47);
        }
    }

    stopTimer() {
        if (this.timerInterval) clearInterval(this.timerInterval);
    }

    renderThumbnails(files) {
        if (!this.els.previewList) return;
        this.els.previewList.innerHTML = '';
        files.forEach((f, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'aidofy-meta-thumb-wrap';
            wrap.id = 'aidofy-thumb-' + idx;
            wrap.innerHTML = `<div class="aidofy-meta-thumb-status" id="aidofy-thumb-status-${idx}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Queued...</div><button type="button" class="aidofy-meta-thumb-remove aidofy-meta-cancel-btn" title="Remove Asset"><svg style="width: 16px; height: 16px; stroke: #ffffff !important; fill: none !important; stroke-width: 2.5; stroke-linecap: round;" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>`;
            
            if (f.name.match(/\.(ai|eps)$/i)) {
                const vectorSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"><rect width="400" height="300" fill="%231E293B"/><svg x="176" y="100" width="48" height="48" fill="none" stroke="%233B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg><text x="200" y="180" font-family="system-ui, sans-serif" font-weight="700" font-size="16" fill="%2394A3B8" text-anchor="middle">Vector Format</text></svg>';
                wrap.innerHTML += `<img src="${vectorSvg}" style="border-radius: 12px; width: 100%; height: auto;">`;
            } else if (f.type.startsWith('video/')) {
                wrap.innerHTML += `<video src="${URL.createObjectURL(f)}" controls style="border-radius: 12px; width: 100%; max-height: 200px; object-fit: contain; background: #000;"></video>`;
            } else {
                wrap.innerHTML += `<img src="${URL.createObjectURL(f)}" style="border-radius: 12px; width: 100%; height: auto;">`;
            }
            this.els.previewList.appendChild(wrap);
        });
        this.els.previewList.style.display = 'flex';
    }

    updateThumbnailStatus(idx, text, type, isSingle = false) {
        const badgeId = isSingle ? 'aidofy-thumb-status-single' : `aidofy-thumb-status-${idx}`;
        const badge = document.getElementById(badgeId);
        if (!badge) return;

        badge.style.display = 'flex';
        badge.className = 'aidofy-meta-thumb-status'; 

        let icon = '';
        if (type === 'done') {
            badge.classList.add('aidofy-status-done');
            icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        } else if (type === 'error') {
            badge.classList.add('aidofy-status-error');
            icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        } else if (type === 'canceled') {
            badge.classList.add('aidofy-status-canceled');
            icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
        } else if (type === 'processing') {
            badge.classList.add('aidofy-status-processing');
            icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"></path><polyline points="21 3 21 8 16 8"></polyline></svg>';
        } else {
            icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
        }
        badge.innerHTML = icon + ' ' + text;
    }

    renderResults(data) {
        this.stopTimer();
        this.setState('result');

        if (this.els.modelBadge) this.els.modelBadge.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg> ${data.model_label.toUpperCase()} • ${data.server_label.toUpperCase()}`;
        if (this.els.timeBadge) this.els.timeBadge.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> ${data.generated_at}`;
        if (this.els.conceptText) this.els.conceptText.textContent = data.commercial_concept || 'Factual ground-truth successfully extracted.';

        if (this.els.userPromptBadge && data.user_prompt) {
            this.els.userPromptTxt.textContent = `"${data.user_prompt}"`;
            this.els.userPromptBadge.style.display = 'block';
        } else if (this.els.userPromptBadge) {
            this.els.userPromptBadge.style.display = 'none';
        }

        let parsed;
        try { parsed = window.Aidofy.Utils.parseRobustJSON(data.metadata || '{}'); } 
        catch(e) { this.showError("Invalid JSON from AI."); return; }

        if(this.els.resMedia) this.els.resMedia.value = parsed.media_type || 'N/A';
        if(this.els.resCategory) this.els.resCategory.value = parsed.category || 'N/A';
        if(this.els.resTitle) {
            this.els.resTitle.value = parsed.title || 'N/A';
            if(this.els.titleLabel) this.els.titleLabel.textContent = `TITLE (${(parsed.title||'').length})`;
        }
        if(this.els.resKeywords) {
            const kwStr = Array.isArray(parsed.keywords) ? parsed.keywords.join(', ') : (parsed.keywords || 'N/A');
            this.els.resKeywords.value = kwStr;
            if(this.els.keywordsLabel) this.els.keywordsLabel.textContent = `KEYWORDS (${kwStr.split(',').filter(Boolean).length})`;
        }

        [this.els.resMedia, this.els.resCategory, this.els.resTitle, this.els.resKeywords].forEach(el => {
            if(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }
        });
        
        document.querySelectorAll('.aidofy-meta-copy-icon-btn').forEach(btn => {
            btn.innerHTML = this.iconCopy;
            btn.onclick = () => {
                document.getElementById(btn.dataset.target).select();
                document.execCommand('copy');
                btn.innerHTML = this.iconCheck;
                setTimeout(() => btn.innerHTML = this.iconCopy, 2000);
            };
        });
    }

    renderBulkCompletion(count, firstResult, success, failed, canceled) {
        this.stopTimer();
        this.setState('bulk-result');

        if (this.els.bulkModelBadge && firstResult) {
            this.els.bulkModelBadge.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg> ${firstResult.model_label.toUpperCase()} • ${firstResult.server_label.toUpperCase()}`;
        }
        if (this.els.bulkTimeBadge && firstResult) {
            this.els.bulkTimeBadge.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> ${firstResult.generated_at}`;
        }
    }
};