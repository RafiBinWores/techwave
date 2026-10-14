(function (root) {
    root.qrDesigner = function (initial, wire, io = {}) {
        const fetchPreview = io.fetch || ((...args) => root.fetch(...args));
        const createUrl = io.createUrl || ((blob) => root.URL.createObjectURL(blob));
        const revokeUrl = io.revokeUrl || ((url) => root.URL.revokeObjectURL(url));
        const decode = io.decode || ((url) => new Promise((resolve, reject) => {
            const image = new root.Image();
            image.onload = resolve;
            image.onerror = reject;
            image.src = url;
        }));
        return {
            choices: { ...initial.choices },
            accepted: { ...initial.choices },
            revision: initial.revision || 0,
            busy: false,
            error: false,
            uploading: false,
            uploadProgress: 0,
            downloading: false,
            downloadError: null,
            displayUrl: null,
            _source: null,
            _request: null,
            _load: 0,
            _uploadRevision: 0,
            _unsubscribe: null,
            _destroyed: false,
            init() {
                if (root.Livewire?.hook) {
                    this._unsubscribe = root.Livewire.hook('commit', ({ component, commit, fail, succeed }) => {
                        if (component.id !== initial.id) return;
                        const revision = Number(commit?.updates?.uiRevision ?? this.revision);
                        fail(() => this.failure(revision));
                        succeed(({ effects }) => {
                            const dispatched = (effects.dispatches || []).some((event) => event.name === 'qr-preview-updated');
                            if (!dispatched && revision === this.revision) this.busy = false;
                        });
                    });
                }
                if (initial.url) this.receive({ ...initial, error: null });
            },
            begin() {
                this.revision++;
                this.busy = true;
                this.error = false;
                this._load++;
                this._request?.abort();
                wire.$set('uiRevision', this.revision, false);
                return this.revision;
            },
            beginInput(event) {
                if (event.target.type === 'file') return;
                if (![...event.target.attributes].some((attribute) => attribute.name.startsWith('wire:model'))) return;
                this.begin();
            },
            choose(property, value, action = null, argument = null) {
                const same = this.choices[property] === value;
                if (same && !this.error) return;
                this.choices[property] = value;
                const revision = this.begin();
                try {
                    const request = action ? (argument === null ? wire[action]() : wire[action](argument))
                        : (same ? wire.generate() : wire.$set(property, value, true));
                    Promise.resolve(request).catch(() => this.failure(revision));
                } catch (error) {
                    this.failure(revision);
                }
            },
            chooseLogo(value, action, argument = null) {
                if (this.uploading) {
                    wire.$cancelUpload?.('customLogo');
                    this.uploading = false;
                }
                this.choose('logo', value, action, argument);
            },
            uploadStart() {
                this._uploadRevision = this.begin();
                this.uploading = true;
                this.uploadProgress = 0;
                this.choices.logo = 'custom';
            },
            uploadError() {
                this.uploading = false;
                this.failure(this._uploadRevision);
            },
            failure(revision) {
                if (revision !== this.revision || this._destroyed) return;
                this._load++;
                this._request?.abort();
                this.choices = { ...this.accepted };
                this.busy = false;
                this.uploading = false;
                this.error = true;
            },
            async receive(detail) {
                if (detail.id !== initial.id || detail.revision < this.revision || this._destroyed) return;
                this.revision = detail.revision;
                this.accepted = { ...this.accepted, ...detail.choices };
                this.choices = { ...this.accepted };
                this.uploading = false;
                if (detail.error) {
                    this.failure(detail.revision);
                    return;
                }
                const load = ++this._load;
                this._request?.abort();
                if (!detail.url) {
                    if (this.displayUrl) revokeUrl(this.displayUrl);
                    this.displayUrl = null;
                    this._source = null;
                    this.busy = false;
                    this.error = false;
                    return;
                }
                if (this._source === detail.url && this.displayUrl) {
                    this.busy = false;
                    this.error = false;
                    return;
                }
                this.busy = true;
                this._request = new root.AbortController();
                let candidate = null;
                try {
                    const response = await fetchPreview(detail.url, { signal: this._request.signal, cache: 'no-store' });
                    if (!response.ok) throw new Error('Preview unavailable');
                    candidate = createUrl(await response.blob());
                    await decode(candidate);
                    if (load !== this._load || detail.revision !== this.revision || this._destroyed) {
                        revokeUrl(candidate);
                        return;
                    }
                    const previous = this.displayUrl;
                    this.displayUrl = candidate;
                    this._source = detail.url;
                    this.busy = false;
                    this.error = false;
                    if (previous) revokeUrl(previous);
                } catch (error) {
                    if (candidate) revokeUrl(candidate);
                    if (load === this._load) this.failure(detail.revision);
                }
            },
            async download(event) {
                if (this.busy || this.uploading || this.error || !this.displayUrl) return;
                this.downloading = true;
                this.downloadError = null;
                try { await root.downloadQr(event); }
                catch (error) { this.downloadError = error.message || 'Download failed. Please try again.'; }
                finally { this.downloading = false; }
            },
            destroy() {
                this._destroyed = true;
                this._load++;
                this._request?.abort();
                if (typeof this._unsubscribe === 'function') this._unsubscribe();
                if (this.displayUrl) revokeUrl(this.displayUrl);
            },
        };
    };

    root.qrPngBlob = async function (source, io = {}) {
        const image = io.createImage ? io.createImage() : new root.Image();
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = () => reject(new Error('The QR image could not be loaded.'));
            image.src = source;
        });
        // Rasterize the vector at export resolution, rather than enlarging a small PNG.
        const scale = 2048 / Math.max(image.naturalWidth, image.naturalHeight);
        const canvas = io.createCanvas ? io.createCanvas() : root.document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext('2d');
        if (!context) throw new Error('PNG export is unavailable.');
        // The SVG supplies the QR background and quiet zone; keep its outer corners transparent.
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        return await new Promise((resolve, reject) => canvas.toBlob(
            blob => blob ? resolve(blob) : reject(new Error('PNG export failed. Please try again.')),
            'image/png',
        ));
    };

    root.downloadQr = async function (event) {
        const button = event.currentTarget;
        const label = button.querySelector('.btn-text');
        const original = label.textContent;
        label.textContent = 'Downloading...';
        let url = null;
        let link = null;
        try {
            const image = button.closest('[data-qr-tool]').querySelector('[x-ref="qrImg"]');
            if (!image?.getAttribute('src')) throw new Error('Please generate a QR code before downloading.');
            const blob = await root.qrPngBlob(image.src);
            url = root.URL.createObjectURL(blob);
            link = root.document.createElement('a');
            link.download = 'techwave-qr-code.png';
            link.href = url;
            link.style.display = 'none';
            root.document.body.appendChild(link);
            link.click();
        } finally {
            link?.remove();
            // Allow the browser to consume the file before releasing its object URL.
            if (url) root.setTimeout(() => root.URL.revokeObjectURL(url), 10000);
            label.textContent = original;
        }
    };
})(globalThis);

