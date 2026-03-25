<?php
// admin/modules/nonconsumable/ast_manage_inventory.php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(
    role_has("ADMIN") ||
    (
        (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) &&
        user_has_access(array("AST", "PO"))
    )
)) {
    header("Location: " . BASE_URL);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/select2.min.css" rel="stylesheet">
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .section-card .card-header { background: var(--bg-eclearance-rgb); color: #fff; font-weight: 600; }
        .badge-code { font-family: 'Courier New', monospace; }
        .form-section-title { font-size: 0.95rem; font-weight: 700; text-transform: uppercase; color: #6c757d; letter-spacing: .5px; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Select2 Bootstrap styling */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        /* Fix Select2 with button alignment */
        .select2-with-button {
            display: flex;
            gap: 0;
        }
        .select2-with-button .select2-container {
            flex: 1;
        }
        .select2-with-button .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: -1px;
        }
        .select2-with-button .select2-container .select2-selection {
/* SIX SEVEN SAIS SYETE ANIM PITO */
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        #inventoryTable { min-height: 220px; }
        .qr-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
        }
        .qr-preview-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            background: #fff;
        }
        .qr-preview-img {
            width: 100%;
            max-width: 100px;
            height: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px;
            background: #fff;
            margin: 0 auto;
            display: block;
        }
        .qr-preview-code {
            margin-top: 6px;
            font-size: 11px;
            word-break: break-word;
            color: #495057;
        }
        .review-table-wrap {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .three-line-cell {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
            max-height: 3.75em;
        }
        .two-line-cell {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
            max-height: 2.5em;
        }
        .tabulator {
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">Manage Non-Consumable Inventory</h1>
        <p class="text-muted small mb-0">Add items, bulk import/update, and control availability for requestors.</p>
    </div>

    <section class="section">
        <div id="pageMsg" class="alert alert-danger d-none mb-3"></div>
        <div class="row g-3">
            <!-- Add New Item -->
            <div class="col-12">
                <div class="card section-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-plus-circle"></i>&ensp;Add New Item</span>
                    </div>
                    <div class="card-body">
                        <form id="addItemForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_item">
                            <input type="hidden" name="property_series" id="propertySeriesInput" value="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Item Category</label>
                                    <select class="form-select" name="category_id" id="categorySelect" required>
                                        <option value="">Select category</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Property Number (base)</label>
                                    <input type="text" class="form-control" name="property_number" id="propertyNumberField" placeholder="Enter property no. (e.g., A12B)" required autocomplete="off" inputmode="text" pattern="[A-Za-z0-9]+">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Property Code (auto)</label>
                                    <input type="text" class="form-control" id="propertyCodeField" placeholder="AST-XXX-0001 ... AST-XXX-000N" readonly>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Item Description</label>
                                    <textarea class="form-control" name="item_description" id="itemDescription" rows="2" placeholder="Describe the item" required></textarea>
                                </div>

                                <input type="hidden" name="number_of_units" id="numberOfUnits" value="1">
                                <input type="hidden" name="serial_numbers_json" id="serialNumbersJson" value="[]">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Unit (measurement)
                                        <span class="text-muted ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Type a unit (e.g., pcs, box, set) and press Enter to add it if it is not listed.">
                                            <i class="bi bi-info-circle"></i>
                                        </span>
                                    </label>
                                    <select class="form-select" name="unit" id="unitSelect" required>
                                        <option value="">Select existing or type a new unit</option>
                                    </select>
                                    <div class="small text-muted mt-1">Tip: type a unit and press Enter to add it.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Source of Fund (optional)</label>
                                    <input type="text" class="form-control" name="source_of_fund" id="sourceOfFund">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Cost Value (optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" step="0.01" min="0" class="form-control" name="cost_value" id="costValue" placeholder="0.00" inputmode="decimal" oninput="this.value=this.value.replace(/[^0-9.]/g,'').replace(/(\\..*)\\./g,'$1');">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label fw-semibold mb-0">Quantity Rows</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="number" class="form-control form-control-sm" id="addUnitRowCount" min="1" max="50" value="1" style="width:90px;" aria-label="Quantity count">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addUnitRowBtn">
                                                <i class="bi bi-plus-lg"></i> Add Quantity
                                            </button>
                                        </div>
                                    </div>
                                    <div id="unitRows" class="d-flex flex-column gap-2"></div>
                                    <div class="small text-muted mt-1">Each row = one item quantity to be created.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">QR Code (auto-generated)</label>
                                    <div class="border rounded p-2 bg-white">
                                        <div id="qrLoading" class="text-muted small py-3" style="display: none;">
                                            <i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i>
                                            <br>Generating QR code...
                                        </div>
                                        <div id="qrPreviewList" class="qr-preview-grid"></div>
                                        <div id="qrPreviewMeta" class="small text-muted mt-2">Based on the property code</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Item
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="resetAddForm">Clear</button>
                            </div>
                            <div id="addItemMsg" class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Quantity (hidden temporarily) -->
            <div class="col-12 col-xl-4 d-none">
                <div class="card section-card h-100">
                    <div class="card-header"><i class="bi bi-plus-square"></i>&ensp;Add Quantity (Existing Item)</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Property Code</label>
                            <div class="select2-with-button">
                                <select class="form-select" id="searchPropertyCode">
                                    <option value="">Select property code</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" id="openAddQtyScanner" title="Scan QR">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
                            </div>
                            <div class="small text-muted mt-1">Last scanned: <span id="addQtyLastScanned">-</span></div>
                        </div>
                        <div id="itemInfo" class="mb-3 text-muted small">No item loaded.</div>
                        <form id="addQtyForm">
                            <input type="hidden" name="action" value="add_quantity">
                            <input type="hidden" name="property_code" id="addQtyPropertyCode">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Quantity to Add</label>
                                <input type="number" class="form-control" name="add_qty" id="addQtyValue" min="1" value="1">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Source of Fund</label>
                                    <input type="text" class="form-control" name="source_of_fund" id="addQtySource">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Cost Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" step="0.01" min="0" class="form-control" name="cost_value" id="addQtyCost" inputmode="decimal" oninput="this.value=this.value.replace(/[^0-9.]/g,'').replace(/(\\..*)\\./g,'$1');">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success w-100"><i class="bi bi-arrow-up-circle"></i> Apply Quantity</button>
                            </div>
                            <div id="addQtyMsg" class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Inventory List -->
            <div class="col-12">
                <div class="card section-card h-100">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2">
                            <span><i class="bi bi-table"></i>&ensp;Recent Items</span>
                            <div class="input-group" style="max-width:360px;">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control form-control-sm" id="tableSearch" placeholder="Search property code or serial no.">
                                <button class="btn btn-light btn-sm border" type="button" id="openSearchScanner" title="Scan QR">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="inventoryTablePlaceholder" class="text-muted small mb-2">
                            Scroll to load recent items table.
                        </div>
                        <div id="inventoryTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<!-- SEARCH QR MODAL -->
<div class="modal fade" id="searchQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Search</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="searchCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="searchBtnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="searchBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div id="searchPreviewWrapper" style="width:100%;max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1;">
                    <div id="searchPreview" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
                    <div class="scanner-loading" id="searchScannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
                        <div>Initializing camera...</div>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="searchLastScanned" class="fw-semibold">�"</span>
                </div>
                <div id="searchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ADD QTY QR MODAL -->
<div class="modal fade" id="addQtyQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Add Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <select id="addQtyCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <div class="d-flex gap-2">
                        <button type="button" id="addQtyBtnStart" class="btn btn-success btn-sm">Start</button>
                        <button type="button" id="addQtyBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div style="width:100%;max-width:420px;aspect-ratio:1;background:#000;border-radius:10px;overflow:hidden;position:relative;">
                        <div id="addQtyPreview" style="position:absolute;inset:0;"></div>
                        <div id="addQtyScannerLoading" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#fff;font-size:14px;">
                            Initializing camera...
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="text-muted">Last scanned:</span>
                        <span id="addQtyScanLast" class="fw-semibold">-</span>
                    </div>
                    <div id="addQtyScanError" class="text-danger small mt-1" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SERIAL BARCODE SCAN MODAL -->
<div class="modal fade" id="serialScanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-upc-scan"></i>&ensp;Scan Barcode for Serial No.</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="serialCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="serialBtnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="serialBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div style="width:100%;max-width:420px;aspect-ratio:1;background:#000;border-radius:10px;overflow:hidden;position:relative;margin:0 auto;">
                    <div id="serialPreview" style="position:absolute;inset:0;"></div>
                    <div id="serialScannerLoading" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#fff;font-size:14px;">
                        Initializing camera...
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="serialScanLast" class="fw-semibold">-</span>
                </div>
                <div id="serialScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- REVIEW ADD ITEM MODAL -->
<div class="modal fade" id="reviewAddItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-check2-square"></i>&ensp;Review Item(s) Before Save</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="reviewAddItemError" class="alert alert-danger d-none mb-3"></div>
                <div id="reviewAddItemLoading" class="text-muted small py-2 d-none">
                    <i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i>
                    Preparing review...
                </div>
                <div id="reviewAddItemContent" style="display:none;">
                    <div class="row g-2 mb-3 small">
                        <div class="col-md-6"><span class="text-muted">Category:</span> <span class="fw-semibold" id="reviewCategory"></span></div>
                        <div class="col-md-6"><span class="text-muted">Property Number:</span> <span class="fw-semibold" id="reviewPropertyNumber"></span></div>
                        <div class="col-md-6"><span class="text-muted">Description:</span> <span class="fw-semibold" id="reviewDescription"></span></div>
                        <div class="col-md-6"><span class="text-muted">Unit:</span> <span class="fw-semibold" id="reviewUnit"></span></div>
                        <div class="col-md-6"><span class="text-muted">Source of Fund:</span> <span class="fw-semibold" id="reviewSource"></span></div>
                        <div class="col-md-6"><span class="text-muted">Cost:</span> <span class="fw-semibold" id="reviewCost"></span></div>
                        <div class="col-md-6"><span class="text-muted">Quantities to Create:</span> <span class="fw-semibold" id="reviewUnits"></span></div>
                        <div class="col-md-6"><span class="text-muted">Property Code Range:</span> <span class="fw-semibold" id="reviewRange"></span></div>
                    </div>
                    <div class="review-table-wrap">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:90px;">Quantity #</th>
                                    <th style="width:260px;">Property Code</th>
                                    <th>Serial Number</th>
                                </tr>
                            </thead>
                            <tbody id="reviewUnitsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                <button type="button" class="btn btn-primary" id="confirmAddItemBtn">
                    <i class="bi bi-save"></i> Confirm Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';
const QR_GENERATOR_URL = BASE_URL + 'admin/modules/tools/qr_image.php';

let inventoryTable = null;
let categoriesCache = [];
let propertyCodesCache = [];
let propertyNumberTimeout = null;
let propertyCodeRequestSeq = 0;
let propertyCodeXhr = null;
let pendingAddItemDraft = null;
let isSavingAddItem = false;
let pageMsgTimeout = null;
let serialScanner = null;
let serialTargetInput = null;
let lastSerialScan = '';
let lastSerialScanAt = 0;
const ADD_ITEM_DRAFT_KEY = 'ast_add_item_draft_v1';
let pendingLocalDraft = null;
let suppressDraftSave = false;
let saveDraftTimer = null;

function playSerialBeep(kind) {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();
    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.value = (kind === 'error') ? 330 : 900;
    gain.gain.value = 5;
    oscillator.connect(gain);
    gain.connect(ctx.destination);
    oscillator.start();
    setTimeout(() => {
        oscillator.stop();
        ctx.close();
    }, kind === 'error' ? 220 : 120);
}


function showMessage(target, type, text) {
    $(target).html(`<div class="alert alert-${type} mb-2">${text}</div>`);
}

function showPageMessage(message) {
    const el = $('#pageMsg');
    if (!message) {
        el.addClass('d-none').text('');
        return;
    }
    el.removeClass('d-none').text(message);
    if (pageMsgTimeout) clearTimeout(pageMsgTimeout);
    pageMsgTimeout = setTimeout(() => {
        el.addClass('d-none').text('');
    }, 4000);
}

function saveAddItemDraft() {
    if (suppressDraftSave) return;
    const draft = collectAddItemDraft();
    const payload = { draft, saved_at: new Date().toISOString() };
    try {
        localStorage.setItem(ADD_ITEM_DRAFT_KEY, JSON.stringify(payload));
    } catch (e) {
        // Ignore storage errors (quota/private mode)
    }
}

function scheduleSaveAddItemDraft() {
    if (saveDraftTimer) clearTimeout(saveDraftTimer);
    saveDraftTimer = setTimeout(saveAddItemDraft, 250);
}

function clearAddItemDraft() {
    try { localStorage.removeItem(ADD_ITEM_DRAFT_KEY); } catch (e) {}
}

function loadAddItemDraft() {
    try {
        const raw = localStorage.getItem(ADD_ITEM_DRAFT_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        return parsed && parsed.draft ? parsed.draft : null;
    } catch (e) {
        return null;
    }
}

function applyAddItemDraft(draft) {
    if (!draft) return false;
    suppressDraftSave = true;

    $('#propertyNumberField').val(draft.property_number || '');
    $('#itemDescription').val(draft.item_description || '');
    $('#sourceOfFund').val(draft.source_of_fund || '');
    $('#costValue').val(draft.cost_value || '');

    if (draft.category_id) {
        if ($('#categorySelect option[value="' + draft.category_id + '"]').length === 0) {
            $('#categorySelect').append(new Option(draft.category_label || draft.category_id, draft.category_id, true, true));
        }
        $('#categorySelect').val(draft.category_id).trigger('change');
    }

    if (draft.unit) {
        if ($('#unitSelect option[value="' + draft.unit + '"]').length === 0) {
            $('#unitSelect').append(new Option(draft.unit_label || draft.unit, draft.unit, true, true));
        }
        $('#unitSelect').val(draft.unit).trigger('change');
    }

    $('#unitRows').html('');
    const serials = Array.isArray(draft.serial_rows) ? draft.serial_rows : [];
    if (serials.length === 0) {
        addUnitRow('');
    } else {
        serials.forEach(val => addUnitRow(val));
    }
    refreshPropertyCode();

    suppressDraftSave = false;
    return true;
}

function tryRestoreAddItemDraft() {
    if (!pendingLocalDraft) pendingLocalDraft = loadAddItemDraft();
    if (!pendingLocalDraft) return;

    const hasContent = (d) => {
        if (!d) return false;
        if (d.category_id) return true;
        if (d.property_number) return true;
        if (d.item_description) return true;
        if (d.unit) return true;
        if (d.source_of_fund) return true;
        if (d.cost_value) return true;
        if (Array.isArray(d.serial_rows) && d.serial_rows.some(v => (v || '').trim() !== '')) return true;
        return false;
    };
    if (!hasContent(pendingLocalDraft)) return;

    const categoryReady = $('#categorySelect option').length > 0;
    const unitReady = $('#unitSelect option').length > 0;
    if (!categoryReady || !unitReady) return;

    const applied = applyAddItemDraft(pendingLocalDraft);
    if (applied) {
        pendingLocalDraft = null;
        showMessage('#addItemMsg', 'info', 'Restored your saved draft.');
    }
}


function loadCategories() {
    $.post(PROCESS_URL, { action: 'list_categories' }, function(res) {
        if (res.success) {
            categoriesCache = res.data || [];
            const options = ['<option value="">Select category</option>'];
            categoriesCache.forEach(cat => {
                options.push(`<option value="${cat.category_id}">${cat.item_category_name}</option>`);
            });
            $('#categorySelect').html(options.join(''));
            
            // Initialize Select2 for searchable dropdown
            $('#categorySelect').select2({
                placeholder: 'Select category',
                allowClear: true,
                width: '100%'
            });
            tryRestoreAddItemDraft();
            showPageMessage('');
        } else {
            $('#categorySelect').html('<option value="">Failed to load</option>');
            showPageMessage(res.message || 'Failed to load categories.');
        }
    }, 'json').fail(function() {
        $('#categorySelect').html('<option value="">Failed to load</option>');
        showPageMessage('Server error while loading categories.');
    });
}

function loadUnits() {
    $.post(PROCESS_URL, { action: 'list_units' }, function(res) {
        if (res.success) {
            const units = res.data || [];
            const options = ['<option value="">Select existing or type a new unit</option>'];
            units.forEach(unit => {
                options.push(`<option value="${unit}">${unit}</option>`);
            });
            $('#unitSelect').html(options.join(''));

            $('#unitSelect').select2({
                placeholder: 'Select existing or type a new unit',
                allowClear: true,
                tags: true,
                width: '100%'
            });
            tryRestoreAddItemDraft();
            showPageMessage('');
        } else {
            $('#unitSelect').html('<option value="">Failed to load</option>');
            showPageMessage(res.message || 'Failed to load units.');
        }
    }, 'json').fail(function() {
        $('#unitSelect').html('<option value="">Failed to load</option>');
        showPageMessage('Server error while loading units.');
    });
}

function loadPropertyCodes() {
    $.post(PROCESS_URL, { action: 'list_property_codes', limit: 1000 }, function(res) {
        if (res.success) {
            propertyCodesCache = res.data || [];
            const options = ['<option value="">Select property code</option>'];
            propertyCodesCache.forEach(item => {
                const category = item.item_category_name ? `[${item.item_category_name}] ` : '';
                const description = item.item_description ? ` - ${item.item_description}` : '';
                const label = `${item.property_code} ${category}${description}`;
                options.push(`<option value="${item.property_code}">${label}</option>`);
            });
            $('#searchPropertyCode').html(options.join(''));
            
            // Initialize Select2 for searchable dropdown
            $('#searchPropertyCode').select2({
                placeholder: 'Search property code',
                allowClear: true,
                width: '100%'
            }).on('select2:select', function(e) {
                const code = e.params.data.id;
                if (code) {
                    $('#addQtyLastScanned').text(code);
                    loadItemByCode(code);
                }
            }).on('select2:clear', function() {
                $('#itemInfo').html('<div class="text-muted small">No item loaded.</div>');
                $('#addQtyPropertyCode').val('');
                $('#addQtyLastScanned').text('-');
            });
            showPageMessage('');
        } else {
            $('#searchPropertyCode').html('<option value="">Failed to load</option>');
            showPageMessage(res.message || 'Failed to load property codes.');
        }
    }, 'json').fail(function() {
        $('#searchPropertyCode').html('<option value="">Failed to load</option>');
        showPageMessage('Server error while loading property codes.');
    });
}


function countUnitRows() {
    const rows = $('#unitRows .unit-row').length;
    return rows > 0 ? rows : 1;
}

function addUnitRow(serialVal = '') {
    const idx = $('#unitRows .unit-row').length + 1;
    const rowHtml = `
        <div class="unit-row border rounded p-2">
            <div class="row g-2 align-items-center">
                <div class="col-sm-3 col-md-2">
                    <span class="badge bg-light text-dark border">Quantity ${idx}</span>
                </div>
                <div class="col-sm-9 col-md-8">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control serial-row-input" maxlength="150" placeholder="Serial number (optional)" value="${serialVal}">
                        <button class="btn btn-outline-secondary btn-scan-serial" type="button" title="Scan barcode for serial">
                            <i class="bi bi-upc-scan"></i> Scan
                        </button>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-unit-row">Remove</button>
                </div>
            </div>
        </div>
    `;
    $('#unitRows').append(rowHtml);
    refreshUnitRowLabels();
    scheduleSaveAddItemDraft();
}

function refreshUnitRowLabels() {
    $('#unitRows .unit-row').each(function(i) {
        $(this).find('.badge').text('Quantity ' + (i + 1));
    });
}

function resetUnitRows() {
    $('#unitRows').html('');
    addUnitRow('');
}

function escapeHtml(str) {
    return String(str === null || str === undefined ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatPeso(value) {
    if (value === null || value === undefined || value === '') return '';
    const num = Number(value);
    if (!isFinite(num)) return '';
    return '₱' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function threeLineText(value, fallback = '-') {
    const raw = (value === null || value === undefined || value === '') ? fallback : String(value);
    const safe = escapeHtml(raw);
    return `<span class="three-line-cell" title="${safe}">${safe}</span>`;
}

function twoLineText(value, fallback = '-') {
    const raw = (value === null || value === undefined || value === '') ? fallback : String(value);
    const safe = escapeHtml(raw);
    return `<span class="two-line-cell" title="${safe}">${safe}</span>`;
}

function buildPropertyCodeList(propertyNumber, startSeries, units) {
    const normalized = String(propertyNumber || '').toUpperCase();
    const start = parseInt(startSeries, 10) || 1;
    const list = [];
    for (let i = 0; i < units; i++) {
        list.push(`AST-${normalized}-${String(start + i).padStart(4, '0')}`);
    }
    return list;
}

function collectAddItemDraft() {
    const serialRows = [];
    $('#unitRows .serial-row-input').each(function() {
        serialRows.push(($(this).val() || '').trim());
    });
    const categoryLabelRaw = ($('#categorySelect option:selected').text() || '').trim();
    const unitLabelRaw = ($('#unitSelect option:selected').text() || '').trim();
    return {
        category_id: ($('#categorySelect').val() || '').trim(),
        category_label: categoryLabelRaw && categoryLabelRaw !== 'Select category' ? categoryLabelRaw : '',
        property_number: ($('#propertyNumberField').val() || '').trim().toUpperCase(),
        item_description: ($('#itemDescription').val() || '').trim(),
        unit: ($('#unitSelect').val() || '').trim(),
        unit_label: unitLabelRaw && unitLabelRaw !== 'Select existing or type a new unit' ? unitLabelRaw : '',
        source_of_fund: ($('#sourceOfFund').val() || '').trim(),
        cost_value: ($('#costValue').val() || '').trim(),
        serial_rows: serialRows,
        number_of_units: serialRows.length > 0 ? serialRows.length : 1
    };
}

function validateAddItemDraft(draft) {
    if (!draft.category_id) return 'Category is required.';
    if (!draft.property_number) return 'Property number is required.';
    if (!/^[A-Za-z0-9]+$/.test(draft.property_number)) return 'Property number must contain letters and numbers only.';
    if (!draft.item_description) return 'Item description is required.';
    if (!draft.unit) return 'Unit is required.';
    if (draft.number_of_units <= 0) return 'Please add at least one quantity row.';
    if (draft.number_of_units > 200) return 'Maximum 200 quantity rows per add is allowed.';
    return '';
}

function setReviewModalState(opts) {
    const loading = !!(opts && opts.loading);
    const content = !!(opts && opts.content);
    const error = (opts && opts.error) ? String(opts.error) : '';
    if (loading) {
        $('#reviewAddItemLoading').removeClass('d-none');
    } else {
        $('#reviewAddItemLoading').addClass('d-none');
    }
    if (content) {
        $('#reviewAddItemContent').show();
    } else {
        $('#reviewAddItemContent').hide();
    }
    if (error) {
        $('#reviewAddItemError').removeClass('d-none').text(error);
    } else {
        $('#reviewAddItemError').addClass('d-none').text('');
    }
}

function populateReviewModal(draft) {
    const codes = Array.isArray(draft.property_codes) ? draft.property_codes : [];
    const firstCode = codes.length ? codes[0] : '-';
    const lastCode = codes.length ? codes[codes.length - 1] : '-';
    const rangeText = codes.length > 1 ? `${firstCode} ... ${lastCode}` : firstCode;

    $('#reviewCategory').text(draft.category_label || '-');
    $('#reviewPropertyNumber').text(draft.property_number || '-');
    $('#reviewDescription').text(draft.item_description || '-');
    $('#reviewUnit').text(draft.unit_label || draft.unit || '-');
    $('#reviewSource').text(draft.source_of_fund || '-');
    if (draft.cost_value !== '') {
        const formatted = formatPeso(draft.cost_value);
        $('#reviewCost').text(formatted !== '' ? formatted : draft.cost_value);
    } else {
        $('#reviewCost').text('-');
    }
    $('#reviewUnits').text(String(draft.number_of_units || 0));
    $('#reviewRange').text(rangeText);

    const serials = Array.isArray(draft.serial_rows) ? draft.serial_rows : [];
    const rowsHtml = codes.map((code, idx) => {
        const serial = serials[idx] || '';
        const serialHtml = serial ? escapeHtml(serial) : '<span class="text-muted">-</span>';
        return `
            <tr>
                <td>${idx + 1}</td>
                <td><span class="badge bg-light text-dark border badge-code">${escapeHtml(code)}</span></td>
                <td>${serialHtml}</td>
            </tr>
        `;
    }).join('');
    $('#reviewUnitsBody').html(rowsHtml);
}

function buildAddItemFormData(draft) {
    const fd = new FormData();
    fd.set('action', 'add_item');
    fd.set('category_id', draft.category_id || '');
    fd.set('property_number', draft.property_number || '');
    fd.set('property_series', String(draft.property_series || ''));
    fd.set('item_description', draft.item_description || '');
    fd.set('number_of_units', String(draft.number_of_units || 1));
    fd.set('serial_numbers_json', JSON.stringify(Array.isArray(draft.serial_rows) ? draft.serial_rows : []));
    fd.set('unit', draft.unit || '');
    fd.set('source_of_fund', draft.source_of_fund || '');
    fd.set('cost_value', draft.cost_value || '');
    return fd;
}

function openReviewAddItemModal(draft) {
    pendingAddItemDraft = null;
    setReviewModalState({ loading: true, content: false, error: '' });
    $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    $('#reviewAddItemModal').modal('show');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: {
            action: 'get_next_property_code',
            property_number: draft.property_number,
            number_of_units: draft.number_of_units
        },
        dataType: 'json',
        global: false
    }).done(function(res) {
        if (!res || !res.success) {
            setReviewModalState({ loading: false, content: false, error: (res && res.message) ? res.message : 'Failed to prepare review.' });
            return;
        }
        const normalizedPropNum = String(res.property_number || draft.property_number).toUpperCase();
        const startSeries = parseInt(res.series, 10) || 1;
        const codeList = buildPropertyCodeList(normalizedPropNum, startSeries, draft.number_of_units);

        draft.property_number = normalizedPropNum;
        draft.property_series = startSeries;
        draft.property_codes = codeList;
        pendingAddItemDraft = draft;

        $('#propertySeriesInput').val(String(startSeries));
        $('#propertyCodeField').val(codeList.length > 1 ? `${codeList[0]} ... ${codeList[codeList.length - 1]}` : (codeList[0] || ''));
        updateQrPreview(codeList);

        populateReviewModal(draft);
        setReviewModalState({ loading: false, content: true, error: '' });
    }).fail(function() {
        setReviewModalState({ loading: false, content: false, error: 'Server error while preparing review.' });
    });
}

function submitAddItemFromReview() {
    if (!pendingAddItemDraft || isSavingAddItem) return;
    isSavingAddItem = true;
    $('#confirmAddItemBtn').prop('disabled', true).html('<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Saving...');
    const fd = buildAddItemFormData(pendingAddItemDraft);
    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        global: false
    }).done(function(res) {
        if (res && res.success) {
            $('#reviewAddItemModal').modal('hide');
            showMessage('#addItemMsg', 'success', res.message || 'Item saved.');
            $('#addItemForm')[0].reset();
            $('#categorySelect').val('').trigger('change');
            $('#unitSelect').val('').trigger('change');
            resetUnitRows();
            refreshPropertyCode();
            refreshTable();
            loadPropertyCodes();
            loadUnits();
            clearAddItemDraft();
            return;
        }
        setReviewModalState({ loading: false, content: true, error: (res && res.message) ? res.message : 'Save failed.' });
    }).fail(function(xhr) {
        let msg = 'Server error while saving.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        } else if (xhr.responseText) {
            try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch(e) {}
        }
        setReviewModalState({ loading: false, content: true, error: msg });
    }).always(function() {
        isSavingAddItem = false;
        $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    });
}



function refreshPropertyCode() {
    const propNum = $('#propertyNumberField').val().trim();
    const units = countUnitRows();
    $('#numberOfUnits').val(units);
    propertyCodeRequestSeq += 1;
    const requestSeq = propertyCodeRequestSeq;
    if (propertyCodeXhr && typeof propertyCodeXhr.abort === 'function') {
        propertyCodeXhr.abort();
    }
    propertyCodeXhr = null;
    if (propNum === '') {
        $('#propertyCodeField').val('');
        $('#propertySeriesInput').val('');
        updateQrPreview([]);
        return;
    }
    
    // Show loading in QR area
    $('#qrLoading').show();
    
    propertyCodeXhr = $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: { action: 'get_next_property_code', property_number: propNum, number_of_units: units },
        dataType: 'json',
        global: false, // Disable global AJAX events to prevent full-screen loading
        success: function(res) {
            if (requestSeq !== propertyCodeRequestSeq) return;
            if (res.success) {
                const displayCode = (units > 1 && res.range_end_code)
                    ? `${res.property_code} ... ${res.range_end_code}`
                    : res.property_code;
                $('#propertyCodeField').val(displayCode);
                $('#propertySeriesInput').val(res.series);

                const normalizedPropNum = String(res.property_number || propNum).toUpperCase();
                const startSeries = parseInt(res.series, 10) || 1;
                const codeList = [];
                for (let i = 0; i < units; i++) {
                    const s = String(startSeries + i).padStart(4, '0');
                    codeList.push(`AST-${normalizedPropNum}-${s}`);
                }
                updateQrPreview(codeList);
            } else {
                console.error('Failed to get property code:', res.message);
                $('#propertyCodeField').val('');
                $('#propertySeriesInput').val('');
                updateQrPreview([]);
            }
        },
        error: function(xhr, status, error) {
            if (requestSeq !== propertyCodeRequestSeq) return;
            if (status === 'abort') return;
            // Session expired - redirect to login
            if (xhr.status === 401) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.redirect) { window.location.href = res.redirect; return; }
                } catch(e) {}
                window.location.reload();
                return;
            }
            console.error('AJAX error getting property code:', status, error);
            $('#propertyCodeField').val('');
            $('#propertySeriesInput').val('');
            updateQrPreview([]);
        }
    });
}

function debouncedRefreshPropertyCode() {
    if (propertyNumberTimeout) {
        clearTimeout(propertyNumberTimeout);
    }
    propertyNumberTimeout = setTimeout(refreshPropertyCode, 500);
}

function updateQrPreview(codes) {
    const listEl = document.getElementById('qrPreviewList');
    const metaEl = document.getElementById('qrPreviewMeta');
    const loading = document.getElementById('qrLoading');
    if (!listEl || !loading) return;

    const codeList = Array.isArray(codes) ? codes : (codes ? [codes] : []);
    if (!codeList.length) {
        listEl.innerHTML = '';
        if (metaEl) metaEl.textContent = 'Based on the property code';
        loading.style.display = 'none';
        return;
    }

    const previewLimit = 24;
    const visibleCodes = codeList.slice(0, previewLimit);
    if (metaEl) {
        metaEl.textContent = codeList.length > previewLimit
            ? `Showing first ${previewLimit} of ${codeList.length} QR codes.`
            : 'Based on the property code';
    }

    loading.style.display = 'block';
    const stamp = Date.now();
    listEl.innerHTML = visibleCodes.map((code, idx) => {
        const safeCode = String(code);
        const src = `${QR_GENERATOR_URL}?v=${encodeURIComponent(safeCode)}&t=${stamp}-${idx}`;
        return `
            <div class="qr-preview-item">
                <img class="qr-preview-img" src="${src}" alt="QR ${idx + 1}" data-qr="1">
                <div class="qr-preview-code">${safeCode}</div>
            </div>
        `;
    }).join('');

    let pending = listEl.querySelectorAll('img[data-qr="1"]').length;
    if (pending === 0) {
        loading.style.display = 'none';
        return;
    }
    listEl.querySelectorAll('img[data-qr="1"]').forEach((img) => {
        const done = function() {
            pending -= 1;
            if (pending <= 0) {
                loading.style.display = 'none';
            }
        };
        img.addEventListener('load', done, { once: true });
        img.addEventListener('error', done, { once: true });
    });
}

function initTable() {
    inventoryTable = new Tabulator('#inventoryTable', {
        height: "auto",
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_items', limit: 200 },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No items found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        paginationCounter: 'rows',
        ajaxResponse: function(url, params, response) {
            return response.data || [];
        },
        columns: [
            { title: 'Property Code', field: 'property_code', width: 200, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: 'Property No.', field: 'property_number', width: 140, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: 'Serial No.', field: 'serial_number', width: 150, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const v = cell.getValue();
                return v && String(v).trim() !== '' ? v : '-';
            }},
            { title: 'Category', field: 'item_category_name', width: 170, headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            { title: 'Description', field: 'item_description', widthGrow: 2, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: 'Qty / Unit', field: 'quantity', width: 110, hozAlign: 'center', headerFilter: 'number', headerFilterPlaceholder: '<= qty', headerFilterFunc: '<=', formatter: function(cell){
                const row = cell.getRow().getData();
                const qty = row.quantity !== null && row.quantity !== undefined ? parseInt(row.quantity, 10) : '';
                const unit = row.unit ? String(row.unit) : '';
                return `<div class="text-center">${qty}${unit ? ' <span class="text-muted">' + escapeHtml(unit) + '</span>' : ''}</div>`;
            }},
            { title: 'Allowed Status', field: 'allowed_status_names', width: 180, visible: false, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const v = cell.getValue();
                if (!v || v === 'None') return '<span class="text-muted small">None</span>';
                if (v === 'All') return '<span class="text-success small fw-semibold">All</span>';
                const parts = v.split('|').map(s => s.trim()).filter(s => {
                    const colon = s.indexOf(':');
                    if (colon === -1) return true;
                    const val = s.slice(colon + 1).trim();
                    return val.toLowerCase() !== 'none';
                });
                const display = parts.length ? parts.join(' | ') : 'None';
                return threeLineText(display);
            }},
            { title: 'Source / Cost', field: 'source_of_fund', width: 150, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const row = cell.getRow().getData();
                const src = row.source_of_fund ? escapeHtml(row.source_of_fund) : '';
                const costRaw = row.cost_value;
                const cost = (costRaw !== null && costRaw !== '' && !isNaN(costRaw)) ? parseFloat(costRaw).toFixed(2) : '';
                const parts = [];
                if (src) parts.push(src);
                if (cost) parts.push(formatPeso(cost));
                return parts.length ? `<span class="two-line-cell">${parts.join(' • ')}</span>` : '<span class="text-muted">-</span>';
            }},
            { title: 'Date Modified', field: 'created_at', width: 160, formatter: function(cell){
                const d = new Date(cell.getValue());
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }}
        ]
    });
}

function initTableLazy() {
    const target = document.getElementById('inventoryTable');
    if (!target) return;
    if (!('IntersectionObserver' in window)) {
        initTable();
        const ph = document.getElementById('inventoryTablePlaceholder');
        if (ph) ph.remove();
        return;
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initTable();
                const ph = document.getElementById('inventoryTablePlaceholder');
                if (ph) ph.remove();
                observer.disconnect();
            }
        });
    }, { root: null, threshold: 0.1 });
    observer.observe(target);
}

function refreshTable() {
    const search = $('#tableSearch').val();
    if (inventoryTable) {
        inventoryTable.setData(PROCESS_URL, { action: 'list_items', search: search, limit: 200 }, 'POST');
    }
}

function loadItemByCode(code) {
    if (!code) return;
    $.post(PROCESS_URL, { action: 'get_item_by_code', property_code: code }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#addQtyPropertyCode').val(d.property_code);
            $('#addQtySource').val(d.source_of_fund || '');
            $('#addQtyCost').val(d.cost_value || '');
            $('#itemInfo').html(`
                <div class="d-flex flex-column gap-2">
                    <div>
                        <div class="small text-muted">Property Code</div>
                        <div class="fw-semibold">${d.property_code}</div>
                    </div>
                    <div class="small text-muted">${d.item_category_name || 'Uncategorized'}</div>
                    <div>
                        <div class="small text-muted">Description</div>
                        <div>${d.item_description || '-'}</div>
                    </div>
                    <div class="small text-muted">
                        Serial No.: <span class="fw-semibold">${d.serial_number || '-'}</span>
                    </div>
                    <div class="small text-muted">
                        Current Qty: <span class="fw-semibold">${d.quantity}</span> ${d.unit || ''}
                        ${d.source_of_fund ? ` | Source: ${d.source_of_fund}` : ''}
                        ${d.cost_value ? ` | Cost: ${formatPeso(d.cost_value)}` : ''}
                    </div>
                </div>
            `);
        } else {
            $('#itemInfo').html('<span class="text-danger">' + (res.message || 'Item not found.') + '</span>');
        }
    }, 'json');
}

function setupSerialScannerModal() {
    if (typeof Html5Qrcode === 'undefined') return;

    function showSerialMessage(message) {
        if (!message) return;
        $('#serialScanError').text(message).show();
        playSerialBeep('error');
    }

    function isDuplicateInBatch(serial, currentInput) {
        const serialNorm = String(serial || '').trim().toLowerCase();
        if (!serialNorm) return false;
        let duplicate = false;
        $('#unitRows .serial-row-input').each(function() {
            if (currentInput && this === currentInput[0]) return;
            const other = String($(this).val() || '').trim().toLowerCase();
            if (other && other === serialNorm) {
                duplicate = true;
                return false;
            }
        });
        return duplicate;
    }

    function checkSerialExistsInDb(serial, currentInput, onSuccess) {
        if (!serial) return;
        const now = Date.now();
        if (serial === lastSerialScan && (now - lastSerialScanAt) < 1500) {
            return;
        }
        lastSerialScan = serial;
        lastSerialScanAt = now;
        $('#serialScanError').hide();
        if (isDuplicateInBatch(serial, currentInput)) {
            showSerialMessage('Duplicate serial number detected in this batch.');
            return;
        }
        $.post(PROCESS_URL, { action: 'check_serial_exists', serial_number: serial }, function(res) {
            if (res && res.success && res.exists) {
                showSerialMessage('Serial number already exists in inventory.');
                return;
            }
            if (typeof onSuccess === 'function') {
                playSerialBeep('success');
                onSuccess();
            }
        }, 'json').fail(function() {
            showSerialMessage('Unable to validate serial number. Try again.');
        });
    }

    function pickPreferredCamera(cameras) {
        if (!Array.isArray(cameras) || cameras.length === 0) return '';
        const preferred = cameras.find(c => /back|rear|environment/i.test(c.label || ''));
        return (preferred ? preferred.id : cameras[0].id) || '';
    }

    function startSerialScanner(cameraId) {
        if (serialScanner) return;
        $('#serialScanError').hide();
        $('#serialScannerLoading').show();
        $('#serialBtnStart').prop('disabled', true);
        $('#serialBtnStop').prop('disabled', false);

        serialScanner = new Html5Qrcode('serialPreview');
        const formats = [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.CODE_93,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.ITF,
            Html5QrcodeSupportedFormats.CODABAR
        ];
        const config = { fps: 10, qrbox: 250, formatsToSupport: formats };
        const cameraConfig = cameraId ? { deviceId: { exact: cameraId } } : { facingMode: "environment" };
        serialScanner.start(
            cameraConfig,
            config,
            function(decodedText, decodedResult) {
                const formatName = decodedResult && decodedResult.result && decodedResult.result.format
                    ? decodedResult.result.format.formatName
                    : '';
                if (String(formatName).toUpperCase() === 'QR_CODE') {
                    return;
                }
                $('#serialScanLast').text(decodedText || '-');
                if (!serialTargetInput) {
                    return;
                }
                const raw = decodedText || '';
                checkSerialExistsInDb(raw, serialTargetInput, function() {
                    serialTargetInput.val(raw).trigger('input');
                    stopSerialScanner();
                    $('#serialScanModal').modal('hide');
                });
            },
            function() {}
        ).then(function() {
            $('#serialScannerLoading').hide();
        }).catch(function() {
            $('#serialScanError').text('Unable to start scanner.').show();
            $('#serialScannerLoading').hide();
            stopSerialScanner();
        });
    }

    function stopSerialScanner() {
        if (serialScanner) {
            serialScanner.stop().then(function() {
                serialScanner.clear();
                serialScanner = null;
            }).catch(function() {
                serialScanner = null;
            });
        }
        $('#serialBtnStart').prop('disabled', false);
        $('#serialBtnStop').prop('disabled', true);
        $('#serialScannerLoading').hide();
    }

    $('#serialBtnStart').on('click', function() {
        const cameraId = $('#serialCameraSelect').val();
        if (!cameraId) {
            $('#serialScanError').text('Select a camera first.').show();
            return;
        }
        startSerialScanner(cameraId);
    });

    $('#serialBtnStop').on('click', function() {
        stopSerialScanner();
    });

    $('#serialScanModal').on('hidden.bs.modal', function() {
        stopSerialScanner();
        serialTargetInput = null;
    }).on('shown.bs.modal', function() {
        $('#serialScanLast').text('-');
        $('#serialScanError').hide();
        $('#serialCameraSelect').html('<option value="">Loading cameras...</option>');
        Html5Qrcode.getCameras().then(function(cameras) {
            if (!cameras || !cameras.length) {
                $('#serialCameraSelect').html('<option value="">No camera found</option>');
                return;
            }
            const options = cameras.map(function(cam) {
                return `<option value="${cam.id}">${cam.label || cam.id}</option>`;
            });
            $('#serialCameraSelect').html(options.join(''));
            const preferredId = pickPreferredCamera(cameras);
            if (preferredId) {
                $('#serialCameraSelect').val(preferredId);
            }
            startSerialScanner(preferredId);
        }).catch(function() {
            $('#serialCameraSelect').html('<option value="">Unable to access cameras</option>');
        });
    });

    $('#unitRows').on('click', '.btn-scan-serial', function() {
        serialTargetInput = $(this).closest('.input-group').find('.serial-row-input');
        $('#serialScanModal').modal('show');
    });
}

    $(document).ready(function() {
        loadCategories();
        loadUnits();
        loadPropertyCodes();
        initTableLazy();
        resetUnitRows();
        refreshPropertyCode();
        setupSerialScannerModal();

        // Enable Bootstrap tooltips (for unit helper, etc.)
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

    $('#propertyNumberField').on('keypress', function(e) {
        // Only allow letters and numbers
        const char = String.fromCharCode(e.which || e.keyCode);
        if (e.which > 31 && !/[a-zA-Z0-9]/.test(char)) {
            e.preventDefault();
            return false;
        }
    }).on('input', function() {
        // Keep alphanumeric only and normalize to uppercase
        let val = ($(this).val() || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        $(this).val(val);
        debouncedRefreshPropertyCode();
        scheduleSaveAddItemDraft();
    }).on('change', function() {
        debouncedRefreshPropertyCode();
        scheduleSaveAddItemDraft();
    });

    $('#addUnitRowBtn').on('click', function() {
        const raw = parseInt($('#addUnitRowCount').val(), 10);
        const count = isFinite(raw) ? Math.max(1, Math.min(raw, 50)) : 1;
        for (let i = 0; i < count; i++) {
            addUnitRow('');
        }
        refreshPropertyCode();
        scheduleSaveAddItemDraft();
    });

    $('#unitRows').on('click', '.btn-remove-unit-row', function() {
        const rows = $('#unitRows .unit-row').length;
        if (rows <= 1) {
            $(this).closest('.unit-row').find('.serial-row-input').val('');
        } else {
            $(this).closest('.unit-row').remove();
            refreshUnitRowLabels();
        }
        refreshPropertyCode();
        scheduleSaveAddItemDraft();
    });

    $('#confirmAddItemBtn').on('click', function() {
        submitAddItemFromReview();
    });

    $('#reviewAddItemModal').on('hidden.bs.modal', function() {
        pendingAddItemDraft = null;
        isSavingAddItem = false;
        setReviewModalState({ loading: false, content: false, error: '' });
        $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    });

    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        $('#addItemMsg').html('');
        const draft = collectAddItemDraft();
        const validationError = validateAddItemDraft(draft);
        if (validationError) {
            showMessage('#addItemMsg', 'danger', validationError);
            return;
        }
        openReviewAddItemModal(draft);
    });

    $('#resetAddForm').on('click', function() {
        $('#addItemForm')[0].reset();
        $('#categorySelect').val('').trigger('change'); // Reset Select2 dropdown
        $('#unitSelect').val('').trigger('change'); // Reset Select2 dropdown
        resetUnitRows();
        refreshPropertyCode();
        $('#addItemMsg').html('');
        clearAddItemDraft();
    });

    $('#addItemForm').on('input change', 'input, textarea, select', function() {
        scheduleSaveAddItemDraft();
    });

    $('#unitRows').on('input', '.serial-row-input', function() {
        scheduleSaveAddItemDraft();
    });

    $('#addQtyForm').on('submit', function(e) {
        e.preventDefault();
        $('#addQtyMsg').html('');
        const codeVal = ($('#addQtyPropertyCode').val() || '').trim();
        if (!codeVal) {
            showMessage('#addQtyMsg', 'danger', 'Please select a property code first.');
            return;
        }
        const data = $(this).serialize();
        $.post(PROCESS_URL, data, function(res) {
            if (res.success) {
                showMessage('#addQtyMsg', 'success', res.message || 'Quantity updated.');
                loadItemByCode($('#addQtyPropertyCode').val());
                refreshTable();
            } else {
                showMessage('#addQtyMsg', 'danger', res.message || 'Update failed.');
            }
        }, 'json').fail(function() {
            showMessage('#addQtyMsg', 'danger', 'Server error while updating quantity.');
        });
    });

    $('#tableSearch').on('keyup', function() {
        refreshTable();
    });

    // Global QR search helper (shared across pages)
    if (typeof initQrSearch === 'function') {
        initQrSearch({
            searchInput: '#tableSearch',
            onSearch: refreshTable
        });
        initQrSearch({
            modalId: '#addQtyQrModal',
            openButton: '#openAddQtyScanner',
            searchInput: '',
            cameraSelectId: '#addQtyCameraSelect',
            startBtnId: '#addQtyBtnStart',
            stopBtnId: '#addQtyBtnStop',
            previewId: '#addQtyPreview',
            lastScannedId: '#addQtyScanLast',
            errorId: '#addQtyScanError',
            loadingId: '#addQtyScannerLoading',
            onSearch: function (decodedText) {
                if (!decodedText) return;
                $('#addQtyLastScanned').text(decodedText);
                // Ensure option exists for Select2, then select it
                if ($('#searchPropertyCode option[value="' + decodedText + '"]').length === 0) {
                    $('#searchPropertyCode').append(new Option(decodedText, decodedText, true, true));
                }
                $('#searchPropertyCode').val(decodedText).trigger('change');
                $('#addQtyPropertyCode').val(decodedText);
                loadItemByCode(decodedText);
            }
        });
    }

    tryRestoreAddItemDraft();
});
</script>
</body>
</html>
