<?php
// admin/modules/nonconsumable/ast_inventory.php
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
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .item-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
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
                            <input type="text" id="invSearch" class="form-control" placeholder="Property code, serial no., description, category">
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
                        <div class="summary-card">
                            <div class="summary-label">Total Issued</div>
                            <div class="summary-value" id="sumIssued">-</div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="d-flex justify-content-end gap-2 mb-2">
                    <button class="btn btn-outline-primary btn-sm" id="bulkSetAvailability">
                        <i class="bi bi-sliders"></i> Bulk Set Rules
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
                            <div class="fw-semibold small text-muted">Teaching Personnel</div>
                            <select id="availTeachingStatus" class="form-select" multiple></select>
                        </div>
                        <div>
                            <div class="fw-semibold small text-muted">Non-Teaching Personnel</div>
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

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';

let inventoryTable = null;
let rawRows = [];
let invMsgTimeout = null;
let employmentStatuses = [];
let availTeachingSelect = null;
let availNonTeachingSelect = null;
const NONE_STATUS_VALUE = 'NONE';
let availStatusLock = false;
let isBulkAvailMode = false;

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

// Update the summary cards based on the current filtered rows
function updateSummary(rows) {
    const totalItems = rows.length;
    const totalQty = rows.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
    // Issued totals are not available yet; keep placeholder for now
    $('#sumItems').text(totalItems);
    $('#sumQty').text(totalQty);
    $('#sumIssued').text('-');
}

// Apply search and date filters on the client side
function applyFilters() {
    const search = ($('#invSearch').val() || '').trim().toLowerCase();
    const range = parseDateRangeInput($('#dateRange').val());
    const fromDate = range.from;
    const toDate = range.to;
    if (toDate) {
        toDate.setHours(23, 59, 59, 999);
    }

    inventoryTable.clearFilter();
    inventoryTable.setFilter(function(data) {
        // Text search across key fields
        if (search) {
            const hay = `${data.property_code || ''} ${data.property_number || ''} ${data.serial_number || ''} ${data.item_description || ''} ${data.item_category_name || ''}`.toLowerCase();
            if (!hay.includes(search)) return false;
        }

        // Date range filter on created_at
        if (fromDate || toDate) {
            const d = parseDate(data.created_at);
            if (!d) return false;
            if (fromDate && d < fromDate) return false;
            if (toDate && d > toDate) return false;
        }
        return true;
    });
    // Reset to first page after filtering to avoid page/scroll mismatch.
    inventoryTable.setPage(1);

    // Update summary based on filtered rows
    const activeRows = inventoryTable.getData("active") || [];
    updateSummary(activeRows);
}

function scheduleInventoryRedraw() {
    if (!inventoryTable) return;
    // Allow layout to settle (e.g., sidebar animation) before redraw
    setTimeout(() => {
        if (inventoryTable) inventoryTable.redraw(true);
    }, 250);
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
        groupHeader: function(value, count, data) {
            const qty = data.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
            const name = value || "Uncategorized";
            return `${name} <span class="text-muted small">(${count} items, Qty ${qty})</span>`;
        },
        columns: [
            { formatter: "rowSelection", titleFormatter: "rowSelection", hozAlign: "center", headerSort: false, width: 40 },
            { title: "Image", field: "category_photo_thumb_url", width: 60, hozAlign: "center", formatter: function(cell){
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
            { title: "Property Code", field: "property_code", width: 170, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
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
                if (!v || v === 'None') return '<span class="text-muted small">None</span>';
                if (v === 'All') return '<span class="text-success small fw-semibold">All</span>';
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
                return `<span class="three-line-cell text-muted small" title="${escapeHtml(full)}">${escapeHtml(display)}</span>`;
            }},
            { title: "Source / Cost", field: "source_of_fund", width: 150, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const row = cell.getRow().getData();
                const src = row.source_of_fund ? escapeHtml(row.source_of_fund) : '';
                const costRaw = row.cost_value;
                const cost = (costRaw !== null && costRaw !== '' && !isNaN(costRaw)) ? parseFloat(costRaw).toFixed(2) : '';
                const parts = [];
                if (src) parts.push(src);
                if (cost) parts.push(`₱${cost}`);
                return parts.length ? `<span class="two-line-cell">${parts.join(' • ')}</span>` : '<span class="text-muted">-</span>';
            }},
            { title: "Issued To", field: "issued_to_name", width: 160, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const v = cell.getValue();
                return v ? twoLineText(v) : '<span class="text-muted">-</span>';
            }},
            { title: "Date", field: "created_at", width: 130 , formatter: function(cell){
                const d = parseDate(cell.getValue());
                return d ? d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
            }}
        ]
    });
}

function loadInventory() {
    $.post(PROCESS_URL, { action: 'list_items', limit: 1000 }, function(res) {
        if (res && res.success) {
            rawRows = res.data || [];
            inventoryTable.setData(rawRows);
            applyFilters();
            showInvMessage('');
        } else {
            rawRows = [];
            inventoryTable.setData([]);
            updateSummary([]);
            showInvMessage(res && res.message ? res.message : 'Failed to load inventory.');
        }
    }, 'json').fail(function() {
        rawRows = [];
        inventoryTable.setData([]);
        updateSummary([]);
        showInvMessage('Server error while loading inventory.');
    });
}

$(document).ready(function() {
    initTable();
    loadInventory();
    loadEmploymentStatuses();

    $('#invSearch').on('keyup', applyFilters);
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
            applyFilters();
        });

        $('#dateRange').on('cancel.daterangepicker', function() {
            $(this).val('');
            applyFilters();
        });
    }

    $('#bulkSetAvailability').on('click', function() {
        if (!inventoryTable) return;
        const rows = inventoryTable.getSelectedData() || [];
        const codes = rows.map(r => r.property_code).filter(Boolean);
        if (codes.length === 0) {
            showInvMessage('Please select at least one item from the table first.');
            return;
        }
        openAvailabilityModalBulk(codes);
    });

    $('#clearSelection').on('click', function() {
        if (!inventoryTable) return;
        inventoryTable.deselectRow();
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
            "Property Code": r.property_code || '',
            "Property No.": r.property_number || '',
            "Serial No.": r.serial_number || '',
            "Description": r.item_description || '',
            "Qty / Unit": `${r.quantity ?? ''}${r.unit ? ' ' + r.unit : ''}`,
            "Allowed Status": r.allowed_status_names || '',
            "Source / Cost": `${r.source_of_fund || ''}${(r.cost_value !== null && r.cost_value !== undefined && r.cost_value !== '') ? ' • ₱' + parseFloat(r.cost_value).toFixed(2) : ''}`,
            "Issued To": r.issued_to_name || '',
            "Date Modified": r.created_at || ''
        }));

        const ws = XLSX.utils.json_to_sheet(rows);
        ws['!cols'] = [
            { wch: 18 }, // Property Code
            { wch: 12 }, // Property No.
            { wch: 20 }, // Serial No.
            { wch: 40 }, // Description
            { wch: 12 }, // Qty / Unit
            { wch: 28 }, // Allowed Status
            { wch: 22 }, // Source / Cost
            { wch: 20 }, // Issued To
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
});
</script>
</html>
