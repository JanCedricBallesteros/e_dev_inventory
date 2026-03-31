<?php
// admin/modules/nonconsumable/ast_inventory.php
// /*todo next time, not urgent:wire Tabulator to use AJAX pagination instead of loading all at once.*/
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
    <style>
        .summary-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; }
        .summary-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
        .summary-value { font-size: 18px; font-weight: 700; }
        .summary-card.is-clickable { cursor: pointer; }
        .summary-card.is-active { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,0.15); }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .item-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
        .qr-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: contain; border: 1px solid #e5e7eb; background: #fff; padding: 2px; }
        .item-badge {
            width: 46px;
            height: 46px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #1E3A8A;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            border: 1px solid rgba(0,0,0,0.06);
            cursor: default;
        }
        .thumb-wrap { display: flex; align-items: center; justify-content: center; }
        .img-preview { max-width: 100%; max-height: 70vh; border-radius: 8px; }
        .ast-sticker-tag {
            border: 1.5px solid #000;
            border-radius: 2px;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
        }
        #qrStickerPreviewModal .modal-dialog {
            max-width: 600px;
        }
        .ast-sticker-header {
            border-bottom: 1.5px solid #000;
            padding: 12px 10px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ast-sticker-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
            flex: 0 0 auto;
        }
        .ast-sticker-body {
            display: flex;
        }
        .ast-sticker-details {
            flex: 1;
            border-right: 1.5px solid #000;
            padding: 9px 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .ast-sticker-cat {
            font-size: 11px;
            font-style: italic;
            color: #555;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-sticker-code {
            font-size: 16px;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-sticker-prop {
            font-size: 11px;
            font-weight: 700;
            color: #222;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-sticker-desc {
            font-size: 12px;
            font-weight: 600;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .ast-sticker-meta {
            font-size: 11px;
            color: #444;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-sticker-divider {
            border: none;
            border-top: 1px dashed #aaa;
            margin: 4px 0;
        }
        .ast-sticker-qr {
            width: 170px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 8px 7px;
            gap: 5px;
        }
        .ast-sticker-qr img {
            width: 152px;
            height: 152px;
            object-fit: contain;
            border: 1px solid #ccc;
            padding: 4px;
            background: #fff;
        }
        .ast-sticker-qr-code {
            font-size: 9px;
            font-weight: 900;
            text-align: center;
            word-break: break-all;
            line-height: 1.2;
            max-width: 155px;
        }
        #ast-inventory-table .tabulator-header .tabulator-col .tabulator-col-content .tabulator-col-title {
            white-space: normal !important;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 2.4em;
        }
        #ast-inventory-table .tabulator-header .tabulator-col .tabulator-col-content {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        #ast-inventory-table .tabulator-header .tabulator-col .tabulator-header-filter {
            margin-top: 4px;
        }
        #ast-inventory-table .tabulator-header .tabulator-col .tabulator-header-filter input,
        #ast-inventory-table .tabulator-header .tabulator-col .tabulator-header-filter select {
            height: 32px;
            line-height: 1.2;
            padding: 4px 8px;
            box-sizing: border-box;
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
        .tabulator {
            font-size: 0.875rem;
        }
        .btn-match-input {
            height: 38px;
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
        <h1 class="h4 fw-semibold mb-1">Non-Consumable Inventory</h1>
        <p class="text-muted small mb-0">Grouped view with date filters and issued totals.</p>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold">
                <i class="bi bi-table"></i>&ensp;Inventory Overview
            </div>
            <div class="card-body mt-3 bg-white">
                <div id="invMsg" class="alert alert-danger d-none mb-3"></div>
                <!-- Filters -->
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <div class="filter-label">Search</div>
                        <div class="input-group">
                            <input type="text" id="invSearch" class="form-control" placeholder="Property Tag, serial no., description, category">
                            <button class="btn btn-outline-secondary" type="button" id="openSearchScanner" title="Scan QR">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-label">Date Range</div>
                        <input type="text" id="dateRange" class="form-control" placeholder="YYYY-MM-DD - YYYY-MM-DD">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100 btn-match-input" id="exportCsv">Export CSV</button>
                        <button class="btn btn-outline-primary w-100 btn-match-input" id="exportExcel">Export Excel</button>
                    </div>
                </div>

                <!-- Summary -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <div class="summary-card">
                            <div class="summary-label">Total Items</div>
                            <div class="summary-value" id="sumItems">0</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="summary-card">
                            <div class="summary-label">Total Quantity</div>
                            <div class="summary-value" id="sumQty">0</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="summary-card is-clickable" id="issuedSummaryCard" role="button">
                            <div class="summary-label">Total Issued</div>
                            <div class="summary-value" id="sumIssued">-</div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="d-flex justify-content-end gap-2 mb-2">
                    <button class="btn btn-outline-primary btn-sm" id="bulkSetAvailability">
                        <i class="bi bi-sliders"></i> Set Rules
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="clearSelection">Clear Selection</button>
                </div>
                <div id="ast-inventory-table"></div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<!-- SET AVAILABILITY MODAL -->
<div class="modal fade" id="availabilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-sliders"></i>&ensp;Set as Available Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="availabilityForm">
                    <input type="hidden" name="action" value="update_availability_settings">
                    <input type="hidden" name="property_code" id="availPropertyCode">
                    <input type="hidden" name="bulk_codes" id="availBulkCodes">
                    <div class="mb-2 small fw-semibold text-dark" id="availBulkNote" style="display:none;"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Allowed Employment Status</label>
                        <div class="small text-muted mb-2">Choose allowed status per position. "None" means no one can request.</div>
                        <div class="mb-2">
                            <div class="fw-semibold small text-muted">Academic Personnel</div>
                            <select id="availTeachingStatus" class="form-select" multiple></select>
                        </div>
                        <div>
                            <div class="fw-semibold small text-muted">Administrative</div>
                            <select id="availNonTeachingStatus" class="form-select" multiple></select>
                        </div>
                    </div>
                    <div id="availMsg" class="mt-2"></div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Rules</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
                    <span id="searchLastScanned" class="fw-semibold">-</span>
                </div>
                <div id="searchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- IMAGE PREVIEW MODAL -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-image"></i>&ensp;Item Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagePreviewImg" class="img-preview" src="" alt="Item image preview">
            </div>
        </div>
    </div>
</div>

<!-- QR STICKER PREVIEW MODAL -->
<div class="modal fade" id="qrStickerPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code"></i>&ensp;QR Sticker Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qrStickerPreviewWrap"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnDownloadQrStickerPdf" disabled>
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';
const FACILITY_RECORDS_URL = BASE_URL + 'admin/modules/transactions/facility_inventory_records.php';
const LOGO_URL = BASE_URL + 'upload/img/ccc-logo.png';

let inventoryTable = null;
let rawRows = [];
let invMsgTimeout = null;
let employmentStatuses = [];
let availTeachingSelect = null;
let availNonTeachingSelect = null;
const NONE_STATUS_VALUE = 'NONE';
let availStatusLock = false;
let isBulkAvailMode = false;
let showIssuedOnly = false;
let lastPageSize = null;
let loadRequestVersion = 0;
let loadXhr = null;
let loadDebounceTimer = null;
let inventoryTableReady = false;
let pendingInitialLoad = false;
let currentQrStickerCode = '';
const CHUNK_SIZE_ALL = 200;
const CHUNK_SIZE_PAGED = 300;
const GROUP_STATE_KEY = 'ast_inventory_group_state_v1';

const AST_TAG_CSS = `
    @page { size: Letter; margin: 8mm; }
    body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 0; }
    .ast-print-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .ast-print-table tr { page-break-inside: avoid; }
    .ast-print-table td { border: 1px dotted #999; padding: 0.6mm; vertical-align: top; box-sizing: border-box; }
    .ast-tag {
        width: 97mm;
        height: 50mm;
        overflow: hidden;
        border: 1px solid #000;
        border-radius: 1mm;
        background: #fff;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        margin: 0 auto;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .ast-tag-header {
        background: #fff;
        color: #000;
        text-align: left;
        padding: 2.8mm 2mm;
        font-size: 8.6pt;
        font-weight: 800;
        letter-spacing: 0.3px;
        border-bottom: 1.5px solid #000;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px;
    }
    .ast-tag-header .ast-header-logo { width: 20px; height: 20px; }
    .ast-tag-body { display: grid; grid-template-columns: 1fr 32mm; flex: 1; min-height: 0; }
    .ast-tag-details {
        border-right: 1.5px solid #000;
        padding: 1mm 1.5mm;
        display: flex;
        flex-direction: column;
        gap: 0.3mm;
        box-sizing: border-box;
        overflow: hidden;
    }
    .ast-tag-line {
        font-size: 5.8pt;
        font-weight: 600;
        line-height: 1.2;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .ast-tag-line.tl-code { font-size: 7pt; font-weight: 800; }
    .ast-tag-line.tl-prop { font-size: 6.4pt; font-weight: 700; }
    .ast-tag-line.tl-cat { font-size: 5.5pt; font-weight: 400; font-style: italic; }
    .ast-tag-line.tl-desc { font-size: 5.8pt; }
    .ast-tag-line.tl-meta { font-size: 5.3pt; font-weight: 400; color: #333; }
    .ast-tag-divider { border: none; border-top: 1px dashed #999; margin: 0.3mm 0; }
    .ast-tag-qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5mm 1.5mm 1mm;
        gap: 0.8mm;
        box-sizing: border-box;
    }
    .ast-tag-qr img {
        width: 26mm;
        height: 26mm;
        object-fit: contain;
        border: 1px solid #ccc;
        padding: 0.5mm;
        background: #fff;
        box-sizing: border-box;
    }
    .ast-tag-qr-code {
        font-size: 4.5pt;
        font-weight: 900;
        text-align: center;
        word-break: break-word;
        line-height: 1.1;
    }
    @media print {
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
`;

function loadGroupState() {
    try {
        const raw = localStorage.getItem(GROUP_STATE_KEY);
        const parsed = raw ? JSON.parse(raw) : {};
        return (parsed && typeof parsed === 'object') ? parsed : {};
    } catch (e) {
        return {};
    }
}

function saveGroupState(state) {
    try {
        localStorage.setItem(GROUP_STATE_KEY, JSON.stringify(state || {}));
    } catch (e) {}
}

function setGroupOpenState(key, isOpen) {
    const k = String(key || 'Uncategorized');
    const state = loadGroupState();
    state[k] = !!isOpen;
    saveGroupState(state);
}

function getGroupOpenState(key) {
    const k = String(key || 'Uncategorized');
    const state = loadGroupState();
    if (Object.prototype.hasOwnProperty.call(state, k)) return !!state[k];
    return true; // default open
}

function refreshGroupSelectStates() {
    if (!inventoryTable || !inventoryTableReady) return;
    const groups = inventoryTable.getGroups() || [];
    groups.forEach(function(g){
        const rows = g.getRows ? g.getRows() : [];
        const selected = rows.filter(r => r.isSelected && r.isSelected());
        const checkbox = $(g.getElement()).find('.js-group-select').get(0);
        if (!checkbox) return;
        if (!rows.length) {
            checkbox.checked = false;
            checkbox.indeterminate = false;
            return;
        }
        checkbox.indeterminate = selected.length > 0 && selected.length < rows.length;
        checkbox.checked = selected.length === rows.length;
    });
}

function showFloatingError(message) {
    if (!message) return;
    if (typeof error_notif === 'function') return error_notif(message);
    if ($.notify) return $.notify({ message: message }, { type: 'danger', delay: 3000, placement: { from: 'top', align: 'right' } });
    alert(message);
}

function setSelectizeValues(selectize, values) {
    if (!selectize) return;
    const arr = Array.isArray(values)
        ? values
        : (values ? String(values).split(',') : []);
    const filtered = arr
        .filter(v => v !== null && v !== undefined && v !== '')
        .map(v => String(v))
        .filter(v => Object.prototype.hasOwnProperty.call(selectize.options, v));
    selectize.clear(true);
    if (filtered.length) {
        selectize.setValue(filtered, true);
        return;
    }
    if (Object.prototype.hasOwnProperty.call(selectize.options, NONE_STATUS_VALUE)) {
        selectize.setValue([NONE_STATUS_VALUE], true);
    }
}

function updateAvailQtyState() {
    // Kept for compatibility with existing calls; AST no longer uses available_qty.
    return;
}

function loadEmploymentStatuses() {
    return $.post(PROCESS_URL, { action: 'list_employment_status' }, function(res) {
        if (res && res.success) {
            employmentStatuses = res.data || [];
            initAvailabilitySelect();
        }
    }, 'json');
}

function initAvailabilitySelect() {
    if (!document.getElementById('availTeachingStatus')) return;

    if (availTeachingSelect) {
        availTeachingSelect.clear();
        availTeachingSelect.clearOptions();
        availTeachingSelect.destroy();
        availTeachingSelect = null;
    }
    if (availNonTeachingSelect) {
        availNonTeachingSelect.clear();
        availNonTeachingSelect.clearOptions();
        availNonTeachingSelect.destroy();
        availNonTeachingSelect = null;
    }

    const options = [{ employment_status_id: NONE_STATUS_VALUE, status_name: 'None', status_code: 'NONE' }]
        .concat(employmentStatuses || []);
    const baseConfig = {
        valueField: 'employment_status_id',
        labelField: 'status_name',
        searchField: ['status_name', 'status_code'],
        options: options,
        persist: false,
        maxItems: null,
        plugins: ['remove_button'],
        create: false,
        onChange: function() {
            if (availStatusLock) return;
            const vals = this.getValue();
            const arr = Array.isArray(vals) ? vals : (vals ? String(vals).split(',') : []);
            if (!arr.length) {
                availStatusLock = true;
                this.setValue([NONE_STATUS_VALUE], true);
                availStatusLock = false;
            }
            updateAvailQtyState();
        },
        onItemAdd: function(value) {
            if (availStatusLock) return;
            const vals = this.getValue();
            if (!Array.isArray(vals)) return;
            if (value === NONE_STATUS_VALUE) {
                availStatusLock = true;
                this.setValue([NONE_STATUS_VALUE], true);
                availStatusLock = false;
                return;
            }
            if (vals.includes(NONE_STATUS_VALUE) && vals.length > 1) {
                const cleaned = vals.filter(v => v !== NONE_STATUS_VALUE);
                availStatusLock = true;
                this.setValue(cleaned, true);
                availStatusLock = false;
            }
        }
    };

    availTeachingSelect = $('#availTeachingStatus').selectize(baseConfig)[0].selectize;
    availNonTeachingSelect = $('#availNonTeachingStatus').selectize(baseConfig)[0].selectize;
    availStatusLock = true;
    availTeachingSelect.setValue([NONE_STATUS_VALUE], true);
    availNonTeachingSelect.setValue([NONE_STATUS_VALUE], true);
    availStatusLock = false;
    updateAvailQtyState();
}

function openAvailabilityModal(code) {
    if (!code) return;
    isBulkAvailMode = false;
    $('#availMsg').html('');
    $('#availPropertyCode').val(code);
    $('#availBulkCodes').val('');
    $('#availBulkNote').hide().text('');
    if (availTeachingSelect) availTeachingSelect.clear();
    if (availNonTeachingSelect) availNonTeachingSelect.clear();
    updateAvailQtyState();

    $.post(PROCESS_URL, { action: 'get_availability_settings', property_code: code }, function(res) {
        if (res && res.success) {
            const allowed = res.data.allowed_employment_status || {};
            const teach = allowed.teaching || [];
            const non = allowed.non_teaching || [];
            const mode = allowed.mode || 'all';
            const allIds = (employmentStatuses || []).map(s => String(s.employment_status_id));

            if (availTeachingSelect) {
                let tvals = [];
                if (mode === 'none') {
                    tvals = [NONE_STATUS_VALUE];
                } else if (mode === 'all') {
                    tvals = allIds;
                } else {
                    tvals = (teach && teach.length) ? teach : [NONE_STATUS_VALUE];
                }
                availStatusLock = true;
                setSelectizeValues(availTeachingSelect, tvals);
                availStatusLock = false;
            }

            if (availNonTeachingSelect) {
                let nvals = [];
                if (mode === 'none') {
                    nvals = [NONE_STATUS_VALUE];
                } else if (mode === 'all') {
                    nvals = allIds;
                } else {
                    nvals = (non && non.length) ? non : [NONE_STATUS_VALUE];
                }
                availStatusLock = true;
                setSelectizeValues(availNonTeachingSelect, nvals);
                availStatusLock = false;
            }

            updateAvailQtyState();
        } else {
            $('#availMsg').html('<div class="alert alert-danger">Failed to load settings.</div>');
        }
    }, 'json').fail(function() {
        $('#availMsg').html('<div class="alert alert-danger">Server error while loading settings.</div>');
    });

    $('#availabilityModal').modal('show');
}

function openAvailabilityModalBulk(codes) {
    if (!codes || codes.length === 0) return;
    isBulkAvailMode = true;
    $('#availMsg').html('');
    $('#availPropertyCode').val('');
    $('#availBulkCodes').val(codes.join(','));
    $('#availBulkNote').show().text(codes.length + ' items selected');

    if (availTeachingSelect) availTeachingSelect.clear();
    if (availNonTeachingSelect) availNonTeachingSelect.clear();
    if (availTeachingSelect && availNonTeachingSelect) {
        availStatusLock = true;
        availTeachingSelect.setValue([NONE_STATUS_VALUE], true);
        availNonTeachingSelect.setValue([NONE_STATUS_VALUE], true);
        availStatusLock = false;
    }

    updateAvailQtyState();
    $('#availabilityModal').modal('show');
}

function showInvMessage(message) {
    const el = $('#invMsg');
    if (!message) {
        el.addClass('d-none').text('');
        return;
    }
    el.removeClass('d-none').text(message);
    if (invMsgTimeout) clearTimeout(invMsgTimeout);
    invMsgTimeout = setTimeout(() => {
        el.addClass('d-none').text('');
    }, 4000);
}

// Parse a date string into a Date object (supports "YYYY-MM-DD" and "YYYY-MM-DD HH:MM:SS")
function parseDate(val) {
    if (!val) return null;
    const parts = String(val).split(' ');
    const datePart = parts[0];
    const date = new Date(datePart);
    return isNaN(date.getTime()) ? null : date;
}

function parseDateRangeInput(raw) {
    const text = (raw || '').trim();
    if (!text) return { from: null, to: null };
    const parts = text.split(/\s+-\s+|\s+–\s+/).filter(Boolean);
    if (parts.length === 1) {
        const d = parseDate(parts[0]);
        return { from: d, to: d };
    }
    const from = parseDate(parts[0]);
    const to = parseDate(parts[1]);
    return { from, to };
}

function escapeHtml(value) {
    return String(value)
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
    return String.fromCharCode(8369) + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function twoLineText(value, fallback = '-') {
    const raw = (value === null || value === undefined || value === '') ? fallback : String(value);
    const safe = escapeHtml(raw);
    return `<span class="two-line-cell" title="${safe}">${safe}</span>`;
}

function threeLineText(value, fallback = '-') {
    const raw = (value === null || value === undefined || value === '') ? fallback : String(value);
    const safe = escapeHtml(raw);
    return `<span class="three-line-cell" title="${safe}">${safe}</span>`;
}

function getItemByPropertyCode(code) {
    if (!code) return null;
    return (rawRows || []).find(function(row) {
        return String(row.property_code || '') === String(code);
    }) || null;
}

function buildQrStickerPreview(item) {
    if (!item) return '<div class="text-muted">No preview data.</div>';

    const code = item.property_code || '';
    const desc = item.item_description || '';
    const category = item.item_category_name || '';
    const propNum = item.property_number || '';
    const serial = item.serial_number || '';
    const cost = (item.cost_value !== null && item.cost_value !== undefined && item.cost_value !== '') ? formatPeso(item.cost_value) : '';
    const source = item.source_of_fund || '';
    const qrUrl = item.qr_image_url || (code ? (BASE_URL + 'admin/modules/tools/qr_image.php?v=' + encodeURIComponent(code)) : '');
    const shortDesc = String(desc).length > 65 ? String(desc).slice(0, 63) + '...' : String(desc);

    const metaLines = [];
    if (serial) metaLines.push(`S/N: ${escapeHtml(serial)}`);
    if (source) metaLines.push(`Source: ${escapeHtml(source)}`);
    if (cost) metaLines.push(`Cost: ${escapeHtml(cost)}`);

    const metaHtml = metaLines.length
        ? `<hr class="ast-sticker-divider">${metaLines.map(function(line){ return `<div class="ast-sticker-meta">${line}</div>`; }).join('')}`
        : '';

    return `
        <div class="ast-sticker-tag">
            <div class="ast-sticker-header">
                <img class="ast-sticker-logo" src="${LOGO_URL}" alt="CCC logo">
                <span>City College of Calamba</span>
            </div>
            <div class="ast-sticker-body">
                <div class="ast-sticker-details">
                    <div class="ast-sticker-cat">${escapeHtml(category)}</div>
                    <div class="ast-sticker-code">${escapeHtml(code)}</div>
                    ${propNum ? `<div class="ast-sticker-prop">Property No.: ${escapeHtml(propNum)}</div>` : ''}
                    <div class="ast-sticker-desc">${escapeHtml(shortDesc)}</div>
                    ${metaHtml}
                </div>
                <div class="ast-sticker-qr">
                    <img src="${escapeHtml(qrUrl)}" alt="QR ${escapeHtml(code)}">
                    <div class="ast-sticker-qr-code">${escapeHtml(code)}</div>
                </div>
            </div>
        </div>
    `;
}

function buildAstTagForPdf(item) {
    if (!item) return '';
    const code = item.property_code || '';
    const desc = item.item_description || '';
    const category = item.item_category_name || '';
    const propNum = item.property_number || '';
    const serial = item.serial_number || '';
    const source = item.source_of_fund || '';
    const cost = (item.cost_value !== null && item.cost_value !== undefined && item.cost_value !== '') ? formatPeso(item.cost_value) : '';
    const qrUrl = item.qr_image_url || (code ? (BASE_URL + 'admin/modules/tools/qr_image.php?v=' + encodeURIComponent(code)) : '');
    const shortDesc = String(desc).length > 65 ? String(desc).slice(0, 63) + '...' : String(desc);

    const metaLines = [];
    if (serial) metaLines.push(`S/N: ${escapeHtml(serial)}`);
    if (source) metaLines.push(`Source: ${escapeHtml(source)}`);
    if (cost) metaLines.push(`Cost: ${escapeHtml(cost)}`);

    return `
        <div class="ast-tag">
            <div class="ast-tag-header">
                <img class="ast-header-logo" src="${LOGO_URL}" alt="CCC logo">
                <span>City College of Calamba</span>
            </div>
            <div class="ast-tag-body">
                <div class="ast-tag-details">
                    <div class="ast-tag-line tl-cat">${escapeHtml(category)}</div>
                    <div class="ast-tag-line tl-code">${escapeHtml(code)}</div>
                    ${propNum ? `<div class="ast-tag-line tl-prop">Property No.: ${escapeHtml(propNum)}</div>` : ''}
                    <div class="ast-tag-line tl-desc">${escapeHtml(shortDesc)}</div>
                    ${metaLines.length ? '<hr class="ast-tag-divider">' + metaLines.map(function(l){ return `<div class="ast-tag-line tl-meta">${l}</div>`; }).join('') : ''}
                </div>
                <div class="ast-tag-qr">
                    <img src="${escapeHtml(qrUrl)}" alt="QR ${escapeHtml(code)}">
                    <div class="ast-tag-qr-code">${escapeHtml(code)}</div>
                </div>
            </div>
        </div>
    `;
}

function buildAstPrintTable(tagHtmlList) {
    const cols = 2;
    let rows = '';
    for (let i = 0; i < tagHtmlList.length; i += cols) {
        rows += '<tr>';
        for (let c = 0; c < cols; c++) {
            const idx = i + c;
            rows += `<td>${tagHtmlList[idx] || ''}</td>`;
        }
        rows += '</tr>';
    }
    return `<table class="ast-print-table"><tbody>${rows}</tbody></table>`;
}

function downloadCurrentQrStickerPdf() {
    if (!currentQrStickerCode) return;
    if (typeof html2pdf === 'undefined') {
        showInvMessage('PDF library is not available.');
        return;
    }

    const item = getItemByPropertyCode(currentQrStickerCode);
    if (!item) {
        showInvMessage('Unable to load sticker data for PDF download.');
        return;
    }

    const btn = $('#btnDownloadQrStickerPdf');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generating...');

    const tagHtml = buildAstTagForPdf(item);
    const pageHtml = buildAstPrintTable([tagHtml]);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `<style>${AST_TAG_CSS}</style>${pageHtml}`;

    const fileCode = String(item.property_code || 'sticker').replace(/[^A-Za-z0-9_-]/g, '_');
    const opt = {
        margin: [0.31, 0.31, 0.31, 0.31],
        filename: `${fileCode}-tag.pdf`,
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 1.5, useCORS: true, allowTaint: true },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(wrapper).save().then(function() {
        btn.prop('disabled', false).html('<i class="bi bi-file-earmark-pdf"></i> Download PDF');
    }).catch(function() {
        btn.prop('disabled', false).html('<i class="bi bi-file-earmark-pdf"></i> Download PDF');
        showInvMessage('Failed to generate PDF.');
    });
}

// Update the summary cards based on the current filtered rows
function updateSummary(rows) {
    const totalItems = rows.length;
    const totalQty = rows.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
    const totalIssued = rows.reduce((sum, r) => sum + ((parseInt(r.issued_count, 10) || 0) > 0 ? 1 : 0), 0);
    $('#sumItems').text(totalItems);
    $('#sumQty').text(totalQty);
    $('#sumIssued').text(totalIssued);
    $('#issuedSummaryCard').toggleClass('is-active', showIssuedOnly);
}

function isAllPageSize() {
    if (!inventoryTable || !inventoryTableReady || !inventoryTable.getPageSize) return false;
    return inventoryTable.getPageSize() === true;
}

function formatDateYmd(d) {
    if (!d) return '';
    const yy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
}

function getServerFilterParams() {
    const search = ($('#invSearch').val() || '').trim();
    const range = parseDateRangeInput($('#dateRange').val());
    return {
        search: search,
        date_from: range.from ? formatDateYmd(range.from) : '',
        date_to: range.to ? formatDateYmd(range.to) : '',
        issued_only: showIssuedOnly ? 1 : 0
    };
}

function showProgressMessage(loaded, total) {
    const el = $('#invMsg');
    el.removeClass('d-none').text(`Loading ${Number(loaded || 0).toLocaleString()} / ${Number(total || 0).toLocaleString()}...`);
}

function scheduleInventoryReload(delayMs) {
    if (loadDebounceTimer) clearTimeout(loadDebounceTimer);
    loadDebounceTimer = setTimeout(function() {
        loadInventory(true);
    }, typeof delayMs === 'number' ? delayMs : 250);
}

function applyFilters() {
    scheduleInventoryReload(250);
}

function fetchInventoryChunk(params) {
    const payload = {
        action: 'list_items',
        offset: params.offset,
        limit: params.limit,
        search: params.filters.search,
        date_from: params.filters.date_from,
        date_to: params.filters.date_to,
        issued_only: params.filters.issued_only
    };

    loadXhr = $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: payload,
        global: false
    }).done(function(res) {
        if (params.version !== loadRequestVersion) return;
        if (!(res && res.success)) {
            rawRows = [];
            inventoryTable.replaceData([]);
            updateSummary([]);
            showInvMessage((res && res.message) ? res.message : 'Failed to load inventory.');
            return;
        }

        const chunk = Array.isArray(res.data) ? res.data : [];
        const total = Number(res.total || 0);
        const hasMore = !!res.has_more;

        if (params.offset === 0) {
            rawRows = chunk.slice();
            inventoryTable.replaceData(rawRows);
        } else if (chunk.length > 0) {
            rawRows = rawRows.concat(chunk);
            inventoryTable.addData(chunk);
        }

        updateSummary(rawRows);
        refreshGroupSelectStates();

        if (params.allMode && hasMore && chunk.length > 0) {
            showProgressMessage(rawRows.length, total);
            fetchInventoryChunk({
                version: params.version,
                offset: params.offset + chunk.length,
                limit: params.limit,
                allMode: true,
                filters: params.filters
            });
            return;
        }

        loadXhr = null;
        if (!params.allMode && hasMore) {
            showInvMessage(`Showing first ${rawRows.length.toLocaleString()} of ${total.toLocaleString()} items. Select All to load all matching items.`);
        } else {
            showInvMessage('');
        }
        inventoryTable.setPage(1);
    }).fail(function(xhr, status) {
        if (status === 'abort') return;
        if (params.version !== loadRequestVersion) return;
        rawRows = [];
        inventoryTable.replaceData([]);
        updateSummary([]);
        showInvMessage('Server error while loading inventory.');
    });
}

function scheduleInventoryRedraw() {
    if (!inventoryTable) return;
    // Allow layout to settle (e.g., sidebar animation) before redraw
    setTimeout(() => {
        if (inventoryTable) inventoryTable.redraw(true);
    }, 250);
}

function toggleGroupSelection(groupKey, isChecked) {
    if (!inventoryTable) return;
    const groups = inventoryTable.getGroups() || [];
    const match = groups.find(g => String(g.getKey() || '') === String(groupKey || ''));
    if (!match) return;
    const rows = match.getRows ? match.getRows() : [];
    if (!rows.length) return;
    if (isChecked && rows[0]) {
        inventoryTable.scrollToRow(rows[0]);
    }
    if (isChecked) {
        inventoryTable.selectRow(rows);
    } else {
        rows.forEach(r => r.deselect && r.deselect());
    }
    refreshGroupSelectStates();
}

function initTable() {
    inventoryTable = new Tabulator('#ast-inventory-table', {
        layout: "fitColumns",
        // Keep rendering stable when pagination is set to "All"
        renderVertical: "basic",
        responsiveLayout: "collapse",
        resizableColumns: true,
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        paginationCounter: "rows",
        selectable: true,
        groupBy: "item_category_name",
        groupStartOpen: function(value){
            return getGroupOpenState(value || 'Uncategorized');
        },
        groupHeader: function(value, count, data) {
            const qty = data.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
            const name = value || "Uncategorized";
            const rawKey = value || '';
            const encodedKey = encodeURIComponent(rawKey);
            const safeName = escapeHtml(name);
            return `<div class="d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input js-group-select" data-group="${encodedKey}" aria-label="Select group ${safeName || 'Uncategorized'}">
                        <span>${safeName} <span class="text-muted small">(${count} items, Qty ${qty})</span></span>
                    </div>`;
        },
        columns: [
            { formatter: "rowSelection", titleFormatter: "rowSelection", hozAlign: "center", headerSort: false, width: 40 },
            { title: "Img", field: "category_photo_thumb_url", width: 80, hozAlign: "center", formatter: function(cell){
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || '';
                if (url) {
                    return `<div class="thumb-wrap">
                                <img class="item-thumb js-thumb-preview" src="${url}" data-full="${full || ''}" loading="lazy" alt="Item image">
                            </div>`;
                }

                // Render initials badge when no image
                const initials = (String(name).trim().split(/\s+/).map(w => w.charAt(0)).filter(Boolean).slice(0,2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge" title="${name}">${initials}</div></div>`;
            }},
            { title: "QR", field: "qr_image_url", width: 80, hozAlign: "center", headerSort: false, formatter: function(cell){
                const row = cell.getRow().getData();
                const qrUrl = cell.getValue() || (row.property_code ? (BASE_URL + 'admin/modules/tools/qr_image.php?v=' + encodeURIComponent(row.property_code)) : '');
                if (!qrUrl) {
                    return '<span class="text-muted small">-</span>';
                }
                const safeUrl = escapeHtml(qrUrl);
                const safeCode = escapeHtml(row.property_code || 'QR Code');
                return `<div class="thumb-wrap"><a href="#" class="js-qr-sticker-preview" data-code="${safeCode}" title="Preview QR sticker for ${safeCode}"><img class="qr-thumb" src="${safeUrl}" loading="lazy" alt="QR code"></a></div>`;
            }},
            { title: "Property Tag", field: "property_code", width: 170, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Property No.", field: "property_number", width: 120, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Serial No.", field: "serial_number", width: 140, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue(), '-');
            }},
            { title: "Description", field: "item_description", widthGrow: 4, minWidth: 260, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: "Qty / Unit", field: "quantity", width: 110, hozAlign: "center", headerFilter: "number", headerFilterPlaceholder: "<= qty", headerFilterFunc: "<=", formatter: function(cell){
                const row = cell.getRow().getData();
                const qty = row.quantity !== null && row.quantity !== undefined ? parseInt(row.quantity, 10) : '';
                const unit = row.unit ? String(row.unit) : '';
                return `<div class="text-center">${qty}${unit ? ' <span class="text-muted">' + escapeHtml(unit) + '</span>' : ''}</div>`;
            }},
            { title: "Allowed Status", field: "allowed_status_names", width: 180, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const v = cell.getValue();
                const row = cell.getRow().getData();
                const code = row.property_code || '';
                if (!v || v === 'None') {
                    return `<a href="#" class="text-primary small js-open-availability" data-code="${escapeHtml(code)}">None</a>`;
                }
                if (v === 'All') {
                    return `<a href="#" class="text-primary small fw-semibold js-open-availability" data-code="${escapeHtml(code)}">All</a>`;
                }
                // Format: "Teaching: A, B | Non-Teaching: None"
                // Strip sections where the value is "None"
                const parts = v.split('|').map(s => s.trim()).filter(s => {
                    const colon = s.indexOf(':');
                    if (colon === -1) return true;
                    const val = s.slice(colon + 1).trim();
                    return val.toLowerCase() !== 'none';
                });
                const display = parts.length ? parts.join(' | ') : 'None';
                const full = v; // keep original for tooltip
                const safeDisplay = escapeHtml(display);
                const safeFull = escapeHtml(full);
                return `<a href="#" class="three-line-cell text-primary small js-open-availability" data-code="${escapeHtml(code)}" title="${safeFull}">${safeDisplay}</a>`;
            }},
            { title: "Source / Cost Value", field: "source_of_fund", width: 150, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const row = cell.getRow().getData();
                const src = row.source_of_fund ? escapeHtml(row.source_of_fund) : '';
                const costRaw = row.cost_value;
                const cost = (costRaw !== null && costRaw !== '' && !isNaN(costRaw)) ? costRaw : '';
                const parts = [];
                if (src) parts.push(src);
                if (cost) parts.push(formatPeso(cost));
                return parts.length ? `<span class="two-line-cell">${parts.join(' &#8226; ')}</span>` : '<span class="text-muted">-</span>';
            }},
            { title: "Issued To", field: "issued_to_name", width: 160, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const v = cell.getValue();
                return v ? twoLineText(v) : '<span class="text-muted">-</span>';
            }},
            { title: "Location", field: "location_label", width: 200, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const v = cell.getValue();
                const row = cell.getRow().getData();
                const facId = row.location_facility_id;
                const unitId = row.location_unit_id;
                if (!v) return '<span class="text-muted">-</span>';
                if (!facId && !unitId) return threeLineText(v);
                const params = new URLSearchParams();
                if (facId) params.set('facility_id', facId);
                if (unitId) params.set('unit_id', unitId);
                const url = FACILITY_RECORDS_URL + '?' + params.toString();
                const safe = escapeHtml(v);
                return `<a href="${url}" class="three-line-cell" title="${safe}" target="_blank" rel="noopener">${safe}</a>`;
            }},
            { title: "Date", field: "created_at", width: 130 , formatter: function(cell){
                const d = parseDate(cell.getValue());
                return d ? d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
            }}
        ],
        rowSelectionChanged: function(){
            refreshGroupSelectStates();
        }
    });

    inventoryTable.on('groupOpened', function(group){
        setGroupOpenState(group.getKey(), true);
    });
    inventoryTable.on('groupClosed', function(group){
        setGroupOpenState(group.getKey(), false);
    });
    inventoryTable.on('tableBuilt', function(){
        inventoryTableReady = true;
        if (pendingInitialLoad) {
            pendingInitialLoad = false;
            loadInventory(true);
        }
    });
    inventoryTable.on('pageSizeChanged', function(){
        if (!inventoryTableReady) return;
        loadInventory(true);
    });
}

function loadInventory(restart) {
    if (!inventoryTable) return;
    if (!inventoryTableReady) {
        pendingInitialLoad = true;
        return;
    }
    if (restart !== false) {
        loadRequestVersion += 1;
    }
    const version = loadRequestVersion;
    if (loadXhr && typeof loadXhr.abort === 'function') {
        loadXhr.abort();
        loadXhr = null;
    }

    rawRows = [];
    inventoryTable.replaceData([]);
    updateSummary([]);
    refreshGroupSelectStates();

    const allMode = isAllPageSize();
    const limit = allMode ? CHUNK_SIZE_ALL : CHUNK_SIZE_PAGED;
    const filters = getServerFilterParams();

    fetchInventoryChunk({
        version: version,
        offset: 0,
        limit: limit,
        allMode: allMode,
        filters: filters
    });
}

$(document).ready(function() {
    initTable();
    pendingInitialLoad = true;
    loadEmploymentStatuses();

    $('#invSearch').on('input', applyFilters);
    $('#dateRange').on('change keyup', applyFilters);

    if (typeof $.fn.daterangepicker === 'function') {
        $('#dateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear'
            },
            opens: 'left'
        });

        $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
            const value = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
            $(this).val(value);
            loadInventory(true);
        });

        $('#dateRange').on('cancel.daterangepicker', function() {
            $(this).val('');
            loadInventory(true);
        });
    }

    $('#bulkSetAvailability').on('click', function() {
        if (!inventoryTable) return;
        const rows = inventoryTable.getSelectedData() || [];
        const codes = rows.map(r => r.property_code).filter(Boolean);
        if (codes.length === 0) {
            showFloatingError('Please select at least one item from the table first.');
            return;
        }
        openAvailabilityModalBulk(codes);
    });

    $('#clearSelection').on('click', function() {
        if (!inventoryTable) return;
        inventoryTable.deselectRow();
    });

    $('#issuedSummaryCard').on('click', function() {
        showIssuedOnly = !showIssuedOnly;
        loadInventory(true);
    });

    // Keep columns fitted when sidebar toggles or window resizes
    $(document).on('click', '.toggle-sidebar', scheduleInventoryRedraw);
    $(window).on('resize', scheduleInventoryRedraw);

    $('#availabilityForm').on('submit', function(e) {
        e.preventDefault();
        $('#availMsg').html('');

        const teachRaw = availTeachingSelect ? availTeachingSelect.getValue() : [];
        const nonRaw = availNonTeachingSelect ? availNonTeachingSelect.getValue() : [];
        const teachArr = Array.isArray(teachRaw) ? teachRaw : (teachRaw ? String(teachRaw).split(',') : []);
        const nonArr = Array.isArray(nonRaw) ? nonRaw : (nonRaw ? String(nonRaw).split(',') : []);
        const teachAllowed = teachArr.filter(v => String(v) !== NONE_STATUS_VALUE);
        const nonAllowed = nonArr.filter(v => String(v) !== NONE_STATUS_VALUE);
        const allNone = teachAllowed.length === 0 && nonAllowed.length === 0;
        const bulkCodes = ($('#availBulkCodes').val() || '').trim();

        const code = $('#availPropertyCode').val();
        const allowedPayload = allNone
            ? { none: true }
            : { teaching: teachAllowed, non_teaching: nonAllowed };
        const payload = {
            allowed_status: JSON.stringify(allowedPayload)
        };

        if (bulkCodes) {
            payload.action = 'update_availability_settings_bulk';
            payload.bulk_codes = bulkCodes;
        } else {
            payload.action = 'update_availability_settings';
            payload.property_code = code;
        }

        $.post(PROCESS_URL, payload, function(res) {
            if (res && res.success) {
                $('#availMsg').html('<div class="alert alert-success">Saved.</div>');
                loadInventory();
                if (inventoryTable && bulkCodes) inventoryTable.deselectRow();
                setTimeout(() => $('#availabilityModal').modal('hide'), 600);
            } else {
                $('#availMsg').html('<div class="alert alert-danger">' + (res.message || 'Save failed.') + '</div>');
            }
        }, 'json').fail(function() {
            $('#availMsg').html('<div class="alert alert-danger">Server error while saving.</div>');
        });
    });

    $('#exportCsv').on('click', function() {
        if (!inventoryTable) {
            showInvMessage('Inventory table is not ready.');
            return;
        }
        const data = inventoryTable.getData();
        if (!data || data.length === 0) {
            showInvMessage('No data to export.');
            return;
        }
        inventoryTable.download("csv", "ast_inventory.csv");
    });
    $('#exportExcel').on('click', function() {
        if (!inventoryTable) {
            showInvMessage('Inventory table is not ready.');
            return;
        }
        const data = inventoryTable.getData();
        if (!data || data.length === 0) {
            showInvMessage('No data to export.');
            return;
        }
        if (typeof XLSX === 'undefined') {
            showInvMessage('Excel export library not available.');
            return;
        }

        const rows = data.map(r => ({
            "Property Tag": r.property_code || '',
            "Property No.": r.property_number || '',
            "Serial No.": r.serial_number || '',
            "Description": r.item_description || '',
            "Qty / Unit": `${r.quantity ?? ''}${r.unit ? ' ' + r.unit : ''}`,
            "Allowed Status": r.allowed_status_names || '',
            "Source / Cost": `${r.source_of_fund || ''}${(r.cost_value !== null && r.cost_value !== undefined && r.cost_value !== '') ? ' | ' + formatPeso(r.cost_value) : ''}`,
            "Issued To": r.issued_to_name || '',
            "Location": r.location_label || '',
            "Date Modified": r.created_at || ''
        }));

        const ws = XLSX.utils.json_to_sheet(rows);
        ws['!cols'] = [
            { wch: 18 }, // Property Tag
            { wch: 12 }, // Property No.
            { wch: 20 }, // Serial No.
            { wch: 40 }, // Description
            { wch: 12 }, // Qty / Unit
            { wch: 28 }, // Allowed Status
            { wch: 22 }, // Source / Cost
            { wch: 20 }, // Issued To
            { wch: 24 }, // Location
            { wch: 18 }  // Date Modified
        ];
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'AST Inventory');
        XLSX.writeFile(wb, 'ast_inventory.xlsx');
    });

    // Global QR search helper (shared across pages)
    if (typeof initQrSearch === 'function') {
        initQrSearch({
            searchInput: '#invSearch',
            onSearch: applyFilters
        });
    }

    $('#ast-inventory-table').on('click', '.js-thumb-preview, .item-badge', function() {
        const full = $(this).data('full') || $(this).attr('data-full');
        if (!full) return; // badge without full image does nothing
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });

    $('#ast-inventory-table').on('click', '.js-open-availability', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const code = $(this).data('code');
        if (!code) return;
        openAvailabilityModal(code);
    });

    $('#ast-inventory-table').on('change', '.js-group-select', function() {
        const encoded = $(this).data('group');
        const key = decodeURIComponent(String(encoded || ''));
        toggleGroupSelection(key, this.checked);
    });

    $('#ast-inventory-table').on('click', '.js-qr-sticker-preview', function(e) {
        e.preventDefault();
        const code = $(this).data('code');
        const item = getItemByPropertyCode(code);
        currentQrStickerCode = String(code || '');
        $('#btnDownloadQrStickerPdf').prop('disabled', !item);
        $('#qrStickerPreviewWrap').html(buildQrStickerPreview(item));
        $('#qrStickerPreviewModal').modal('show');
    });

    $('#btnDownloadQrStickerPdf').on('click', function() {
        downloadCurrentQrStickerPdf();
    });

    $('#qrStickerPreviewModal').on('hidden.bs.modal', function() {
        currentQrStickerCode = '';
        $('#btnDownloadQrStickerPdf').prop('disabled', true).html('<i class="bi bi-file-earmark-pdf"></i> Download PDF');
    });
});
</script>
</html>


