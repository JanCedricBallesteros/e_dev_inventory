// assets/js/qr_search.js
// Global helper to wire a QR scanner to any search input.
(function (global) {
    function playBeep() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 1000;
        gain.gain.value = 5;
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        setTimeout(() => {
            oscillator.stop();
            ctx.close();
        }, 120);
    }

    function isSecureContextOk() {
        return window.isSecureContext || location.hostname === 'localhost';
    }

    // opts: selectors + callback
    function initQrSearch(opts) {
        const cfg = Object.assign({
            modalId: '#searchQrModal',
            openButton: '#openSearchScanner',
            searchInput: '#tableSearch',
            onSearch: function () {},
            cameraSelectId: '#searchCameraSelect',
            startBtnId: '#searchBtnStart',
            stopBtnId: '#searchBtnStop',
            previewId: '#searchPreview',
            lastScannedId: '#searchLastScanned',
            errorId: '#searchScanError',
            loadingId: '#searchScannerLoading',
            beepCooldownMs: 1500,
            autoStart: true,
            autoCloseOnScan: true,
            autoCloseDelayMs: 150
        }, opts || {});

        if (!window.jQuery) return;
        if (!document.querySelector(cfg.modalId)) return;

        let searchQrScanner = null;
        let searchScanning = false;
        let lastSearchScanText = '';
        let lastSearchScanAt = 0;
        let closingOnScan = false;

        function showError(msg) {
            const errEl = document.querySelector(cfg.errorId);
            const loadingEl = document.querySelector(cfg.loadingId);
            if (errEl) {
                errEl.textContent = msg;
                errEl.style.display = msg ? 'block' : 'none';
            }
            if (loadingEl) loadingEl.style.display = 'none';
            if (msg) console.error('QR Search Error:', msg);
        }

        function setRunning(running) {
            const startBtn = document.querySelector(cfg.startBtnId);
            const stopBtn = document.querySelector(cfg.stopBtnId);
            const camSelect = document.querySelector(cfg.cameraSelectId);
            const loadingEl = document.querySelector(cfg.loadingId);
            if (startBtn) startBtn.disabled = running;
            if (stopBtn) stopBtn.disabled = !running;
            if (camSelect) camSelect.disabled = running;
            if (loadingEl) loadingEl.style.display = running ? 'flex' : 'none';
            searchScanning = running;
        }

        async function loadCameras() {
            showError('');
            const cameraSelect = document.querySelector(cfg.cameraSelectId);
            if (!cameraSelect) return;
            if (!isSecureContextOk()) {
                cameraSelect.innerHTML = '<option value="">HTTPS or localhost required</option>';
                showError('Camera access is only supported in a secure context (HTTPS) or localhost.');
                return;
            }
            cameraSelect.innerHTML = '<option value="">Loading cameras...</option>';
            try {
                if (typeof Html5Qrcode === 'undefined') {
                    showError('QR library failed to load.');
                    return;
                }
                const cameras = await Html5Qrcode.getCameras();
                if (!cameras || cameras.length === 0) {
                    cameraSelect.innerHTML = '<option value="">No cameras found</option>';
                    showError('No cameras found. Ensure camera access is granted.');
                    return;
                }
                cameraSelect.innerHTML = '';
                cameras.forEach((cam, idx) => {
                    const opt = document.createElement('option');
                    opt.value = cam.id;
                    opt.textContent = cam.label || `Camera ${idx + 1}`;
                    cameraSelect.appendChild(opt);
                });
                const backCam = cameras.find(c => /back|rear|environment/i.test(c.label || ''));
                cameraSelect.value = backCam ? backCam.id : cameras[0].id;
            } catch (e) {
                cameraSelect.innerHTML = '<option value="">Tap Start to request permission</option>';
                const errMsg = e && e.message ? e.message : String(e);
                showError(`Cannot access cameras: ${errMsg}`);
            }
        }

        async function startScanner() {
            showError('');
            if (!isSecureContextOk()) {
                showError('Camera access requires HTTPS or localhost. Open this page via https:// or http://localhost.');
                return;
            }
            if (closingOnScan) return;
            setRunning(true);
            const cameraSelect = document.querySelector(cfg.cameraSelectId);
            let selectedCamId = cameraSelect ? cameraSelect.value : '';
            if (!selectedCamId) {
                await loadCameras();
                const freshId = cameraSelect ? cameraSelect.value : '';
                if (!freshId) {
                    showError('Please select a camera first.');
                    setRunning(false);
                    return;
                }
                selectedCamId = freshId;
            }
            if (searchQrScanner) {
                try { await searchQrScanner.stop(); } catch (e) {}
            }

            const previewId = cfg.previewId.replace(/^#/, '');
            searchQrScanner = new Html5Qrcode(previewId);
            const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase());
            const config = isMobile
                ? { fps: 15, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0, disableFlip: false, showTorchButtonIfSupported: true, supportedScanTypes: [] }
                : { fps: 10, qrbox: { width: 200, height: 200 }, disableFlip: false, showTorchButtonIfSupported: false, supportedScanTypes: [] };

            try {
                await searchQrScanner.start(
                    selectedCamId,
                    config,
                    (decodedText) => {
                        if (closingOnScan) return;
                        const now = Date.now();
                        if (decodedText !== lastSearchScanText || (now - lastSearchScanAt) > cfg.beepCooldownMs) {
                            playBeep();
                            lastSearchScanText = decodedText;
                            lastSearchScanAt = now;
                        }
                        const lastEl = document.querySelector(cfg.lastScannedId);
                        if (lastEl) lastEl.textContent = decodedText;
                        if (cfg.searchInput) {
                            $(cfg.searchInput).val(decodedText);
                        }
                        if (typeof cfg.onSearch === 'function') cfg.onSearch(decodedText);
                        if (cfg.autoCloseOnScan && decodedText) {
                            closingOnScan = true;
                            stopScanner().then(() => {
                                setTimeout(() => {
                                    $(cfg.modalId).modal('hide');
                                }, cfg.autoCloseDelayMs || 0);
                            });
                        }
                    },
                    () => {}
                );
                const loadingEl = document.querySelector(cfg.loadingId);
                if (loadingEl) loadingEl.style.display = 'none';
            } catch (e) {
                setRunning(false);
                showError('Failed to start camera: ' + (e && e.message ? e.message : String(e)));
            }
        }

        async function stopScanner() {
            showError('');
            if (!searchQrScanner || !searchScanning) return;
            try {
                await searchQrScanner.stop();
                setRunning(false);
            } catch (e) {
                setRunning(false);
            }
        }

        // Wire UI events
        if (document.querySelector(cfg.openButton)) {
            $(cfg.openButton).on('click', function () {
                $(cfg.modalId).modal('show');
            });
        }

        $(cfg.modalId).on('shown.bs.modal', function () {
            loadCameras();
            setRunning(false);
            closingOnScan = false;
            const lastEl = document.querySelector(cfg.lastScannedId);
            const previewEl = document.querySelector(cfg.previewId);
            if (lastEl) lastEl.textContent = '-';
            if (previewEl) previewEl.innerHTML = '';
            if (cfg.autoStart) {
                startScanner();
            }
        });

        $(cfg.modalId).on('hidden.bs.modal', function () {
            stopScanner();
            const previewEl = document.querySelector(cfg.previewId);
            if (previewEl) previewEl.innerHTML = '';
            closingOnScan = false;
        });

        $(cfg.startBtnId).on('click', startScanner);
        $(cfg.stopBtnId).on('click', stopScanner);
        $(cfg.cameraSelectId).on('change', function () {
            if (searchScanning) {
                stopScanner().then(() => startScanner());
            }
        });
    }

    global.initQrSearch = initQrSearch;
})(window);
