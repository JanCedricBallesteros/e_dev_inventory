<?php
// REMINDER, USE THE ALL FUNC CSM_INVENTORY TO REPLACE THIS FRONTEND TO UTILIZE THE PROCESSING IN ONE FILE
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!($g_user_role == "ADMIN" || "ADMINSTAFF")) {
    header("Location: " . BASE_URL);
    exit();
}

// Your page logic here
$placeholderImage = BASE_URL . 'assets/img/item-placeholder.png';
$qrGeneratorUrl = BASE_URL . 'admin/modules/tools/qr_image.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold">
                    <i class="bi bi-check-circle"></i>&ensp;Manage Consumable Inventory
                </div>
                <div class="card-body mt-3 bg-white">

                    <ul class="nav nav-tabs" id="myTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lists-tab" data-bs-toggle="tab" data-bs-target="#lists-pane" type="button" role="tab" aria-controls="lists-pane" aria-selected="false">Lists</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add_new_item-tab" data-bs-toggle="tab" data-bs-target="#add_new_item-pane" type="button" role="tab" aria-controls="add_new_item-pane" aria-selected="false">Add New Item</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add_quantity-tab" data-bs-toggle="tab" data-bs-target="#add_quantity-pane" type="button" role="tab" aria-controls="add_quantity-pane" aria-selected="false">Add Quantity</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-4" id="myTabContent">

                        <!-- LIST TAB-->
                        <div class="tab-pane fade" id="lists-pane" role="tabpanel" aria-labelledby="lists-tab">
                        <!-- Content for Lists tab -->
                            <!-- QR Scanner Toggle Button -->
                            <div class="mb-3 d-flex gap-2">
                                <button type="button" id="toggleQRScanner" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-qr-code"></i> Search by QR Code
                                </button>
                                <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm d-none">
                                    <i class="bi bi-x-circle"></i> Clear Filters
                                </button>
                            </div>
<!-- 67 RAHHHHHHHHH -->

                            <!-- QR Scanner Section (Hidden Initially) -->
                            <div class="border rounded p-3 mb-4 d-none" id="qrScannerSection">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div class="fw-semibold">
                                        <i class="bi bi-qr-code"></i> Search Item by QR Code
                                    </div>
                                    <div class="d-flex gap-2">
                                        <select id="cameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                                            <option value="">Loading cameras...</option>
                                        </select>
                                        <button type="button" id="btnStart" class="btn btn-success btn-sm">Start</button>
                                        <button type="button" id="btnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                                    </div>
                                </div>

                                <div class="d-flex flex-column align-items-center">
                                    <div id="preview-wrapper" style="width: 100%; max-width: 420px; margin: 0 auto; position: relative; background: #000; border-radius: 10px; overflow: hidden; aspect-ratio: 1;">
                                        <div id="preview"></div>
                                        <div class="scanner-loading" id="scannerLoading" style="display:none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 14px; z-index: 10; text-align: center;">
                                            <div>Initializing camera...</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 small">
                                        <span class="text-muted">Last scanned:</span>
                                        <span id="lastScanned" class="fw-semibold">—</span>
                                    </div>
                                    <div id="scanError" class="text-danger small mt-1" style="display:none;"></div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div id="csm-list-table"></div>       
                        </div>
                        
                        <!-- ADD NEW ITEM TAB-->
                        <div class="tab-pane fade" id="add_new_item-pane" role="tabpanel" aria-labelledby="add_new_item-tab">
                        <!-- Content for Add New Item tab -->
                        <div class="row g-4">

                                <!-- LEFT: FORM -->
                                <div class="col-lg-8">
                                    <form id="addItemForm">

                                        <div class="row g-3">

                                            <!-- Item Category -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Item Category</label>
                                                <select class="form-select" id="itemCategory">
                                                    <option value="">Select category</option>
                                                    <!-- Categories will be dynamically loaded from the categories table -->
                                                </select>
                                                <small class="text-muted d-block mt-2">
                                                    Categories are managed in the <strong>Manage Consumables Categories</strong> page
                                                </small>
                                            </div>

                                            <!-- Item Code -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Item Code</label>
                                                <input type="text" id="itemCodeInput" class="form-control" placeholder="Auto-generated" readonly>
                                                <small class="text-muted d-block mt-2">
                                                    Automatically generated based on category
                                                </small>
                                            </div>

                                            <!-- Item Description -->
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Item Description</label>
                                                <textarea class="form-control" rows="2"></textarea>
                                            </div>

                                            <!-- Quantity -->
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Quantity</label>
                                                <input type="number" class="form-control" min="0">
                                            </div>

                                            <!-- Units -->
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Units</label>
                                                <select class="form-select">
                                                    <option value="">Select unit</option>
                                                    <option>pcs</option>
                                                    <option>box</option>
                                                    <option>ream</option>
                                                    <option>set</option>
                                                </select>
                                            </div>

                                            <!-- Critical Level -->
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Critical Level</label>
                                                <input type="number" class="form-control" min="0">
                                            </div>

                                            <!-- Source of Fund (optional) -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    Source of Fund <span class="text-muted">(optional)</span>
                                                </label>
                                                <input type="text" class="form-control">
                                            </div>

                                            <!-- Cost (optional) -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    Cost <span class="text-muted">(optional)</span>
                                                </label>
                                                <input type="number" class="form-control" min="0" step="0.01">
                                            </div>

                                        </div>

                                        <!-- MOCK ACTION -->
                                        <div class="mt-4 text-end">
                                            <button type="button" class="btn btn-eclearance">
                                                <i class="bi bi-plus-circle"></i> Add Item
                                            </button>
                                        </div>

                                    </form>
                                </div>

                                <!-- RIGHT: ITEM PHOTO & QR CODE PREVIEW -->
                                <div class="col-lg-4">
                                    <div class="row g-3">
                                        <!-- Item Photo -->
                                        <div class="col-12">
                                            <div class="border rounded p-3 text-center">
                                                <label class="fw-semibold mb-2 d-block">Item Photo</label>
                                                <img id="itemPhotoPreview"
                                                    src="<?php echo $placeholderImage; ?>"
                                                    class="img-fluid rounded"
                                                    style="max-height: 180px; object-fit: contain;">
                                                <div class="small text-muted mt-2">
                                                    Auto changes based on item category
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Item QR Code -->
                                        <div class="col-12">
                                            <div class="border rounded p-3 text-center">
                                                <label class="fw-semibold mb-2 d-block">Item QR Code</label>
                                                <div class="border border-dashed rounded p-2 bg-light" style="min-height: 180px; display: flex; align-items: center; justify-content: center;">
                                                    <img id="itemQRPreview"
                                                        src="<?php echo $placeholderImage; ?>"
                                                        class="img-fluid rounded"
                                                        style="max-height: 160px; object-fit: contain;">
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    QR code updates as you enter item code
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                        
                        <!-- ADD QUANTITY TAB-->
                        <div class="tab-pane fade" id="add_quantity-pane" role="tabpanel" aria-labelledby="add_quantity-tab">
                        <!-- Content for Add Quantity tab -->
                        <div class="row g-4">
                            
                            <!-- ITEMS LIST -->
                            <div class="col-lg-9">
                                <div class="mb-3 d-flex gap-2">
                                    <button type="button" id="toggleQRScannerAddQty" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-qr-code"></i> Search by QR Code
                                    </button>
                                    <button type="button" id="clearFiltersAddQtyBtn" class="btn btn-outline-secondary btn-sm d-none">
                                        <i class="bi bi-x-circle"></i> Clear Filters
                                    </button>
                                </div>

                                <!-- QR Scanner Section (Hidden Initially) -->
                                <div class="border rounded p-3 mb-4 d-none" id="qrScannerSectionAddQty">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <div class="fw-semibold">
                                            <i class="bi bi-qr-code"></i> Search Item by QR Code
                                        </div>
                                        <div class="d-flex gap-2">
                                            <select id="cameraSelectAddQty" class="form-select form-select-sm" style="max-width: 260px;">
                                                <option value="">Loading cameras...</option>
                                            </select>
                                            <button type="button" id="btnStartAddQty" class="btn btn-success btn-sm">Start</button>
                                            <button type="button" id="btnStopAddQty" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column align-items-center">
                                        <div id="preview-wrapperAddQty" style="width: 100%; max-width: 420px; margin: 0 auto; position: relative; background: #000; border-radius: 10px; overflow: hidden; aspect-ratio: 1;">
                                            <div id="previewAddQty"></div>
                                            <div class="scanner-loading" id="scannerLoadingAddQty" style="display:none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 14px; z-index: 10; text-align: center;">
                                                <div>Initializing camera...</div>
                                            </div>
                                        </div>
                                        <div class="mt-2 small">
                                            <span class="text-muted">Last scanned:</span>
                                            <span id="lastScannedAddQty" class="fw-semibold">—</span>
                                        </div>
                                        <div id="scanErrorAddQty" class="text-danger small mt-1" style="display:none;"></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Item to Add Quantity</label>
                                    <div id="addQty-list-table"></div>
                                </div>
                            </div>

                            <!-- ADD QUANTITY FORM -->
                            <div class="col-lg-3">
                                <div class="border rounded p-4" id="addQtyFormContainer" style="opacity: 0.5; pointer-events: none;">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-plus-lg"></i> Add Quantity
                                    </h6>

                                    <div id="selectedItemInfo" class="alert alert-light border mb-4 d-none">
                                        <div class="mb-2">
                                            <img id="selectedItemPhoto" src="<?php echo $placeholderImage; ?>" class="img-fluid rounded" style="max-height: 100px; width: 100%; object-fit: cover;">
                                        </div>
                                        <small class="text-muted">Item Code:</small>
                                        <div id="selectedItemCode" class="fw-semibold mb-2">-</div>
                                        <small class="text-muted">Item Name:</small>
                                        <div id="selectedItemName" class="fw-semibold mb-2">-</div>
                                        <small class="text-muted">Current Qty:</small>
                                        <div id="selectedItemCurrentQty" class="text-primary fw-semibold">-</div>
                                    </div>

                                    <form id="addQuantityForm">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Quantity to Add</label>
                                            <input type="number" id="addQtyInput" class="form-control" min="1" placeholder="Enter quantity" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">
                                                Source of Fund <span class="text-muted">(optional)</span>
                                            </label>
                                            <input type="text" id="addQtySoF" class="form-control" placeholder="e.g., Budget 2025">
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">
                                                Total Price <span class="text-muted">(optional)</span>
                                            </label>
                                            <input type="number" id="addQtyTotalPrice" class="form-control" min="0" step="0.01" placeholder="0.00">
                                        </div>

                                        <div class="text-end">
                                            <button type="button" class="btn btn-secondary btn-sm me-2" id="addQtyResetBtn">
                                                <i class="bi bi-arrow-counterclockwise"></i> Clear
                                            </button>
                                            <button type="button" class="btn btn-eclearance btn-sm" id="addQtySubmitBtn" disabled>
                                                <i class="bi bi-save"></i> Save
                                            </button>
                                        </div>

                                    </form>
                                </div>
                            </div>

                        </div>
                        
                        </div>

                    </div>



                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

</body>

<!-- UPDATE AVAILABLE ITEM MODAL -->
<div class="modal fade" id="updateItemModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-eclearance text-white">
        <h5 class="modal-title">
          <i class="bi bi-pencil-square"></i> Update Available Item
        </h5>
        <button type="button" class="btn-close btn-close-white" aria-label="Close" id="modalCloseX"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="editRowIndex">

        <div class="mb-3">
          <label class="form-label fw-semibold">Available Quantity</label>
          <input type="number" id="editAvailableQty" class="form-control" min="0">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">User Type</label>
          <select id="editUserType" class="form-select">
            <option value="ADMIN">ADMIN</option>
            <option value="ADMINSTAFF">ADMINSTAFF</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="modalCancelBtn">Cancel</button>
        <button class="btn btn-eclearance" id="saveUpdateBtn">
          <i class="bi bi-save"></i> Save Changes
        </button>
      </div>

    </div>
  </div>
</div>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<!-- html5-qrcode library (mobile-friendly) -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // ===== TAB PERSISTENCE =====
    const tabsEl = document.getElementById('myTabs');
    const tabLinks = tabsEl.querySelectorAll('[role="tab"]');
    const TAB_STORAGE_KEY = 'csm_inventory_selected_tab';

    // Initialize tabs - show default or saved tab
    function initializeTabs() {
        const savedTab = localStorage.getItem(TAB_STORAGE_KEY);
        let tabToShow = 'lists-tab'; // default tab
        
        // Check if saved tab exists and is valid
        if (savedTab && document.getElementById(savedTab)) {
            tabToShow = savedTab;
        }
        
        // Hide all tab panes first
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        // Remove active from all tab buttons
        tabLinks.forEach(link => {
            link.classList.remove('active');
            link.setAttribute('aria-selected', 'false');
        });
        
        // Show the correct tab
        const tabButton = document.getElementById(tabToShow);
        const tabPane = document.querySelector(tabButton.getAttribute('data-bs-target'));
        
        tabButton.classList.add('active');
        tabButton.setAttribute('aria-selected', 'true');
        tabPane.classList.add('show', 'active');
    }

    // Initialize tabs when DOM is ready
    initializeTabs();

    // Save selected tab to localStorage
    tabLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function() {
            localStorage.setItem(TAB_STORAGE_KEY, this.id);
        });
    });

    // ===== CLEAR FILTERS BUTTONS =====
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const clearFiltersAddQtyBtn = document.getElementById('clearFiltersAddQtyBtn');

    function resetTableFilters(tableInstance) {
        tableInstance.clearFilter();
        clearFiltersBtn.classList.add('d-none');
        lastEl.textContent = '—';
    }

    function resetTableFiltersAddQty(tableInstance) {
        tableInstance.clearFilter();
        clearFiltersAddQtyBtn.classList.add('d-none');
        lastElAddQty.textContent = '—';
    }

    clearFiltersBtn.addEventListener('click', function() {
        resetTableFilters(tableInstance);
    });

    clearFiltersAddQtyBtn.addEventListener('click', function() {
        resetTableFiltersAddQty(addQtyTableInstance);
    });
    const toggleBtn = document.getElementById('toggleQRScanner');
    const qrScannerSection = document.getElementById('qrScannerSection');

    toggleBtn.addEventListener('click', function() {
        qrScannerSection.classList.toggle('d-none');
        if (!qrScannerSection.classList.contains('d-none')) {
            toggleBtn.innerHTML = '<i class="bi bi-qr-code"></i> Hide QR Scanner';
            toggleBtn.classList.add('btn-primary');
            toggleBtn.classList.remove('btn-outline-primary');
            // Load cameras when scanner is shown
            loadCameras();
        } else {
            toggleBtn.innerHTML = '<i class="bi bi-qr-code"></i> Search by QR Code';
            toggleBtn.classList.remove('btn-primary');
            toggleBtn.classList.add('btn-outline-primary');
            // Stop scanner when hidden
            if (html5QrcodeScanner && isScanning) {
                btnStop.click();
            }
        }
    });

    // ===== QR SCANNER TOGGLE FOR ADD QUANTITY =====
    const toggleBtnAddQty = document.getElementById('toggleQRScannerAddQty');
    const qrScannerSectionAddQty = document.getElementById('qrScannerSectionAddQty');

    toggleBtnAddQty.addEventListener('click', function() {
        qrScannerSectionAddQty.classList.toggle('d-none');
        if (!qrScannerSectionAddQty.classList.contains('d-none')) {
            toggleBtnAddQty.innerHTML = '<i class="bi bi-qr-code"></i> Hide QR Scanner';
            toggleBtnAddQty.classList.add('btn-primary');
            toggleBtnAddQty.classList.remove('btn-outline-primary');
            // Load cameras when scanner is shown
            loadCameras();
        } else {
            toggleBtnAddQty.innerHTML = '<i class="bi bi-qr-code"></i> Search by QR Code';
            toggleBtnAddQty.classList.remove('btn-primary');
            toggleBtnAddQty.classList.add('btn-outline-primary');
            // Stop scanner when hidden
            if (html5QrcodeScannerAddQty && isScanningAddQty) {
                btnStopAddQty.click();
            }
        }
    });

    // ===== QR SCANNER SETUP =====
    const cameraSelect = document.getElementById('cameraSelect');
    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    const lastEl = document.getElementById('lastScanned');
    const errEl = document.getElementById('scanError');
    const loadingEl = document.getElementById('scannerLoading');

    // Add Quantity QR Scanner Elements
    const cameraSelectAddQty = document.getElementById('cameraSelectAddQty');
    const btnStartAddQty = document.getElementById('btnStartAddQty');
    const btnStopAddQty = document.getElementById('btnStopAddQty');
    const lastElAddQty = document.getElementById('lastScannedAddQty');
    const errElAddQty = document.getElementById('scanErrorAddQty');
    const loadingElAddQty = document.getElementById('scannerLoadingAddQty');

    let html5QrcodeScanner = null;
    let html5QrcodeScannerAddQty = null;
    let cameras = [];
    let isScanning = false;
    let isScanningAddQty = false;
    let tableInstance = null;
    let addQtyTableInstance = null;

    function showError(msg) {
        errEl.textContent = msg;
        errEl.style.display = msg ? 'block' : 'none';
        loadingEl.style.display = 'none';
        if (msg) console.error('QR Error:', msg);
    }

    function showErrorAddQty(msg) {
        errElAddQty.textContent = msg;
        errElAddQty.style.display = msg ? 'block' : 'none';
        loadingElAddQty.style.display = 'none';
        if (msg) console.error('QR Error:', msg);
    }

    function setRunning(running) {
        btnStart.disabled = running;
        btnStop.disabled = !running;
        cameraSelect.disabled = running;
        loadingEl.style.display = running ? 'flex' : 'none';
        isScanning = running;
    }

    function setRunningAddQty(running) {
        btnStartAddQty.disabled = running;
        btnStopAddQty.disabled = !running;
        cameraSelectAddQty.disabled = running;
        loadingElAddQty.style.display = running ? 'flex' : 'none';
        isScanningAddQty = running;
    }

    async function loadCameras() {
        showError('');
        cameraSelect.innerHTML = `<option value="">Loading cameras...</option>`;

        try {
            cameras = await Html5Qrcode.getCameras();
            if (!cameras || cameras.length === 0) {
                cameraSelect.innerHTML = `<option value="">No cameras found</option>`;
                showError('❌ No cameras found. Ensure camera is connected and browser has permission.');
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
            const defaultCam = backCam ? backCam.id : cameras[0].id;
            cameraSelect.value = defaultCam;

        } catch (e) {
            cameraSelect.innerHTML = `<option value="">Camera permission denied</option>`;
            showError(`❌ Cannot access cameras: ${e?.message || String(e)}`);
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        lastEl.textContent = decodedText;
        
        // Search for the scanned item code in the table
        if (tableInstance) {
            tableInstance.setFilter('item_code', '=', decodedText);
            clearFiltersBtn.classList.remove('d-none');
        }
        
        // Play beep sound
        playBeep();
    }

    function onScanSuccessAddQty(decodedText, decodedResult) {
        lastElAddQty.textContent = decodedText;
        
        // Search for the scanned item code in the Add Quantity table
        if (addQtyTableInstance) {
            addQtyTableInstance.setFilter('item_code', '=', decodedText);
            clearFiltersAddQtyBtn.classList.remove('d-none');
        }
        
        // Play beep sound
        playBeep();
    }

    function playBeep() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 1000;
        gain.gain.value = 0.15;
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        setTimeout(() => {
            oscillator.stop();
            ctx.close();
        }, 120);
    }

    btnStart.addEventListener('click', async () => {
        if (!cameraSelect.value) {
            showError('Please select a camera first.');
            return;
        }

        setRunning(true);
        showError('');

        try {
            html5QrcodeScanner = new Html5Qrcode(document.getElementById('preview').id, {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                rememberLastUsedCamera: true
            });

            await html5QrcodeScanner.start(
                cameraSelect.value,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                undefined
            );
        } catch (e) {
            setRunning(false);
            showError(`Failed to start scanner: ${e?.message || String(e)}`);
        }
    });

    btnStop.addEventListener('click', async () => {
        if (html5QrcodeScanner) {
            try {
                await html5QrcodeScanner.stop();
                html5QrcodeScanner = null;
            } catch (e) {
                showError(`Error stopping scanner: ${e?.message || String(e)}`);
            }
        }
        setRunning(false);
    });

    btnStartAddQty.addEventListener('click', async () => {
        if (!cameraSelectAddQty.value) {
            showErrorAddQty('Please select a camera first.');
            return;
        }

        setRunningAddQty(true);
        showErrorAddQty('');

        try {
            html5QrcodeScannerAddQty = new Html5Qrcode(document.getElementById('previewAddQty').id, {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                rememberLastUsedCamera: true
            });

            await html5QrcodeScannerAddQty.start(
                cameraSelectAddQty.value,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccessAddQty,
                undefined
            );
        } catch (e) {
            setRunningAddQty(false);
            showErrorAddQty(`Failed to start scanner: ${e?.message || String(e)}`);
        }
    });

    btnStopAddQty.addEventListener('click', async () => {
        if (html5QrcodeScannerAddQty) {
            try {
                await html5QrcodeScannerAddQty.stop();
                html5QrcodeScannerAddQty = null;
            } catch (e) {
                showErrorAddQty(`Error stopping scanner: ${e?.message || String(e)}`);
            }
        }
        setRunningAddQty(false);
    });

    // Load cameras on page load
    loadCameras();

    // ===== ITEMS TABLE SETUP =====
    var itemCodeInput = document.getElementById("itemCodeInput");
    var itemQRPreview = document.getElementById("itemQRPreview");
    var defaultPlaceholder = "<?php echo $placeholderImage; ?>";
    var itemCategorySelect = document.getElementById("itemCategory");

    // Load categories from the categories table dynamically
    function loadCategoriesFromTable() {
        const tableRows = document.querySelectorAll('#csm-list-table tbody tr');
        const categoryMap = {}; // To avoid duplicates

        tableRows.forEach(row => {
            const codeCell = row.cells[1]?.textContent.trim(); // Item Code column
            if (codeCell) {
                // Extract category prefix (first 3 letters)
                const categoryPrefix = codeCell.substring(0, 3);
                const categoryName = row.cells[2]?.textContent.trim() || categoryPrefix;
                
                // Store unique categories
                if (!categoryMap[categoryPrefix]) {
                    categoryMap[categoryPrefix] = categoryName;
                }
            }
        });

        // Clear existing options (keep placeholder)
        itemCategorySelect.innerHTML = '<option value="">Select category</option>';

        // Add categories to dropdown
        Object.entries(categoryMap).forEach(([prefix, name]) => {
            const option = document.createElement('option');
            option.value = prefix;
            option.textContent = name;
            itemCategorySelect.appendChild(option);
        });
    }

    // Auto-generate item code based on selected category
    function getNextItemCode(categoryPrefix) {
        if (!categoryPrefix) return '';
        
        // Get all rows from the table
        const tableRows = document.querySelectorAll('#csm-list-table tbody tr');
        let maxCode = 0;

        tableRows.forEach(row => {
            const codeCell = row.cells[1]?.textContent.trim(); // Item Code column
            if (codeCell && codeCell.startsWith(categoryPrefix)) {
                const numPart = parseInt(codeCell.substring(categoryPrefix.length));
                if (!isNaN(numPart) && numPart > maxCode) {
                    maxCode = numPart;
                }
            }
        });

        const nextCode = String(maxCode + 1).padStart(4, '0');
        return categoryPrefix + nextCode;
    }

    // Load categories on page load
    loadCategoriesFromTable();

    // Update item code when category changes
    itemCategorySelect.addEventListener('change', function() {
        if (this.value) {
            itemCodeInput.value = getNextItemCode(this.value);
        } else {
            itemCodeInput.value = '';
        }
        // Update QR code if item code changed
        updateQRCode();
    });

    function updateQRCode() {
        var itemCode = itemCodeInput.value.trim();
        
        if (itemCode === "") {
            itemQRPreview.src = defaultPlaceholder;
        } else {
            // Generate QR code using the shared tools endpoint
            itemQRPreview.src = "<?php echo $qrGeneratorUrl; ?>?v=" + encodeURIComponent(itemCode) + "&t=" + new Date().getTime();
        }
    }

    var modalEl = document.getElementById("updateItemModal");
    var updateModal = new bootstrap.Modal(modalEl);

    document.getElementById("modalCloseX").addEventListener("click", function () {
        updateModal.hide();
    });

    document.getElementById("modalCancelBtn").addEventListener("click", function () {
        updateModal.hide();
    });

    var availableItemsData = [
        {
            item_code: "CON-001",
            item_name: "Bond Paper",
            description: "A4 White Paper",
            available_qty: 120,
            unit: "ream",
            current_qty: 150,
            crit_lvl: 30,
            user_type: "ADMIN",
            photo: "<?php echo $placeholderImage; ?>",
            source_of_fund: "Budget 2025",
            total_price: 1200.00
        },
        {
            item_code: "CON-002",
            item_name: "Ink Cartridge",
            description: "Black Ink",
            available_qty: 5,
            unit: "pcs",
            current_qty: 20,
            crit_lvl: 10,
            user_type: "ADMINSTAFF",
            photo: "<?php echo $placeholderImage; ?>",
            source_of_fund: "Office Supplies",
            total_price: 500.00
        }
    ];

    var table = new Tabulator("#csm-list-table", {
        data: availableItemsData,
        layout: "fitColumns",
        pagination: "local",
        paginationSize: 10,
        height: "500px",
        responsiveLayout: false,
        placeholder: "No items found. Try adjusting your filters.",

        columns: [
            { title: "ID", field: "id", minWidth: 90, frozen: true, headerFilter: "input" },
            { title: "Item Code", field: "item_code", minWidth: 125, headerFilter: "input", widthGrow: 1 },
            { title: "Name", field: "item_name", minWidth: 120, headerFilter: "input", widthGrow: 2 },
            { title: "Description", field: "description", minWidth: 120, headerFilter: "input", widthGrow: 2 },
            { title: "Category", field: "category", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ qty", headerFilterFunc: "<=" },
            { title: "Status", field: "status", minWidth: 120, headerFilter: "input" },
            { 
                title: "Stock Level", 
                field: "current_qty", 
                minWidth: 110, 
                hozAlign: "center",
                formatter: function(cell) {
                    var current = cell.getRow().getData().current_qty;
                    var crit = cell.getRow().getData().crit_lvl;
                    var badge = "";
                    
                    if (current <= crit) {
                        badge = '<span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Critical</span>';
                    } else if (current <= crit * 1.5) {
                        badge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Low</span>';
                    } else {
                        badge = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Adequate</span>';
                    }
                    return badge;
                }
            },
            { title: "Qty", field: "current_qty", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ qty", headerFilterFunc: "<=" },
            { title: "Crit Lvl", field: "crit_lvl", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ qty", headerFilterFunc: "<=" },
            { title: "Officer", field: "officer", minWidth: 120, headerFilter: "input", widthGrow: 1 },
            { title: "Validator", field: "validated_by", minWidth: 120, headerFilter: "input", widthGrow: 1 },
            { title: "Acquired", field: "acquired", minWidth: 120, headerFilter: "input", widthGrow: 1 },
            { title: "cost", field: "cost", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ cost", headerFilterFunc: "<=" },
        ]
    });

    // Store table instance for QR scanner to use
    tableInstance = table;

    // Add Quantity Table Setup
    var addQtyTable = new Tabulator("#addQty-list-table", {
        data: availableItemsData,
        layout: "fitColumns",
        pagination: "local",
        paginationSize: 10,
        height: "400px",
        responsiveLayout: false,
        selectable: 1,
        placeholder: "No items found. Try adjusting your filters.",

        columns: [
            {
                title: "Photo",
                field: "photo",
                minWidth: 60,
                hozAlign: "center",
                formatter: "image",
                formatterParams: { height: "50px", width: "50px" }
            },
            { title: "Item Code", field: "item_code", minWidth: 90, headerFilter: "input", widthGrow: 1 },
            { title: "Item Name", field: "item_name", minWidth: 120, headerFilter: "input", widthGrow: 1.5 },
            { title: "Description", field: "description", minWidth: 120, headerFilter: "input", widthGrow: 1.5 },
            { title: "Current Qty", field: "current_qty", minWidth: 100, hozAlign: "right", widthGrow: 1.25 },
            { 
                title: "Stock Level", 
                field: "current_qty", 
                minWidth: 110, 
                hozAlign: "center",
                formatter: function(cell) {
                    var current = cell.getRow().getData().current_qty;
                    var crit = cell.getRow().getData().crit_lvl;
                    var badge = "";
                    
                    if (current <= crit) {
                        badge = '<span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Critical</span>';
                    } else if (current <= crit * 1.5) {
                        badge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Low</span>';
                    } else {
                        badge = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Adequate</span>';
                    }
                    return badge;
                }
            },
            { title: "Crit Lvl", field: "crit_lvl", minWidth: 80, hozAlign: "right"},
            { title: "Qty", field: "available_qty", minWidth: 80, hozAlign: "right" },
            { title: "Unit", field: "unit", minWidth: 70 },
            { title: "Source of Fund", field: "source_of_fund", minWidth: 130, widthGrow: 1 },
            { title: "Total Price", field: "total_price", minWidth: 110, hozAlign: "right", formatter: "money", formatterParams: { symbol: "₱" } }
        ],

        rowClick: function(e, row) {
            var data = row.getData();
            populateItemForm(data);
        }
    });

    // Store table instance for QR scanner to use
    addQtyTableInstance = addQtyTable;

    // Populate Item Form when row is selected
    function populateItemForm(itemData) {
        document.getElementById("selectedItemInfo").classList.remove("d-none");
        document.getElementById("selectedItemPhoto").src = itemData.photo;
        document.getElementById("selectedItemCode").textContent = itemData.item_code;
        document.getElementById("selectedItemName").textContent = itemData.item_name;
        document.getElementById("selectedItemCurrentQty").textContent = itemData.current_qty + " " + itemData.unit;

        // Store selected item ID for submission (to be used later)
        document.getElementById("addQuantityForm").setAttribute("data-item-code", itemData.item_code);

        // Enable form container
        var formContainer = document.getElementById("addQtyFormContainer");
        formContainer.style.opacity = "1";
        formContainer.style.pointerEvents = "auto";

        // Enable submit button when item is selected and quantity is entered
        checkFormValidity();
    }

    // Form Validation
    function checkFormValidity() {
        var qtyInput = document.getElementById("addQtyInput");
        var submitBtn = document.getElementById("addQtySubmitBtn");

        if (qtyInput.value && qtyInput.value > 0 && document.getElementById("addQuantityForm").getAttribute("data-item-code")) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    // Event Listeners for Add Quantity Form
    document.getElementById("addQtyInput").addEventListener("input", checkFormValidity);

    document.getElementById("addQtyResetBtn").addEventListener("click", function () {
        document.getElementById("addQuantityForm").reset();
        document.getElementById("selectedItemInfo").classList.add("d-none");
        document.getElementById("addQuantityForm").removeAttribute("data-item-code");
        document.getElementById("addQtySubmitBtn").disabled = true;
        addQtyTable.deselectRow();
    });

    document.getElementById("addQtySubmitBtn").addEventListener("click", function () {
        var itemCode = document.getElementById("addQuantityForm").getAttribute("data-item-code");
        var qty = document.getElementById("addQtyInput").value;
        var soF = document.getElementById("addQtySoF").value;
        var totalPrice = document.getElementById("addQtyTotalPrice").value;

        // TODO: Connect to backend API
        console.log({
            item_code: itemCode,
            quantity: qty,
            source_of_fund: soF,
            total_price: totalPrice
        });

        alert("Quantity added successfully! (Backend pending)");
        document.getElementById("addQtyResetBtn").click();
    });

    document.getElementById("saveUpdateBtn").addEventListener("click", function () {

        const row = table.getRow(
            document.getElementById("editRowIndex").value
        );

        row.update({
            available_qty: parseInt(
                document.getElementById("editAvailableQty").value
            ),
            user_type: document.getElementById("editUserType").value
        });

        updateModal.hide();
    });

});


</script>

</html>