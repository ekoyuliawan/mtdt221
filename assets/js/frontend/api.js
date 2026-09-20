window.Aidofy = window.Aidofy || {};

window.Aidofy.API = class {
    constructor(config) {
        this.config = config;
        this.coreRest = config.core_rest;
        this.metaRest = config.meta_rest;
        this.nonce = config.nonce;
        this.isLogged = config.is_logged_in;
        this.userId = config.user_id;
        
        this.guestToken = this.initGuestToken();
        this.accountId = this.isLogged ? this.userId : this.guestToken;
        
        this.controller = null;
    }

    initGuestToken() {
        try {
            let t = localStorage.getItem('afy_guest_token') || localStorage.getItem('aidofy_guest_token');
            if (!t || !/^[a-zA-Z0-9]{32}$/.test(t)) {
                t = crypto.randomUUID().replace(/-/g, '');
                localStorage.setItem('aidofy_guest_token', t);
            } else if (!localStorage.getItem('aidofy_guest_token')) {
                localStorage.setItem('aidofy_guest_token', t);
            }
            return t;
        } catch (e) {
            return crypto.randomUUID().replace(/-/g, '');
        }
    }

    getHeaders() {
        const headers = { 'X-WP-Nonce': this.nonce };
        if (!this.isLogged) headers['X-AIDOFORYOU-Token'] = this.guestToken;
        return headers;
    }

    async fetchCredits() {
        try {
            // Perfected endpoint strictly targeting legacy path
            const res = await fetch(`${this.coreRest}/credits`, { 
                headers: this.getHeaders() 
            });
            const data = await res.json();
            return data.credits || 0;
        } catch (e) {
            return 0;
        }
    }

    async _fetch(endpoint, bodyData, onProgress = null) {
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();

        bodyData.append('_wpnonce', this.nonce);

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.metaRest + endpoint, true);
            
            const headers = this.getHeaders();
            for (const key in headers) {
                xhr.setRequestHeader(key, headers[key]);
            }

            if (onProgress) {
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        onProgress(percent);
                    }
                };
            }

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try { resolve(JSON.parse(xhr.responseText)); } 
                    catch (err) { reject(new Error('Invalid JSON response from server.')); }
                } else {
                    try {
                        const errData = JSON.parse(xhr.responseText);
                        reject(new Error(errData.message || 'Request failed.'));
                    } catch (err) {
                        reject(new Error(`Server error: ${xhr.status}`));
                    }
                }
            };
            xhr.onerror = () => reject(new Error('Network error.'));
            
            this.controller.signal.addEventListener('abort', () => {
                xhr.abort();
                reject(new Error('Process cancelled.'));
            });

            xhr.send(bodyData);
        });
    }

    async analyze(fd, onProgress) {
        return await this._fetch('/extract/analyze', fd, onProgress);
    }

    async generate(fd) {
        return await this._fetch('/extract/generate', fd);
    }

    abort() {
        if (this.controller) this.controller.abort();
    }
};