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
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
            max-height: 2.5em;
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
                    <div class="col-md-3">
                        <div class="filter-label">Date From</div>
                        <input type="date" id="dateFrom" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <div class="filter-label">Date To</div>
                        <input type="date" id="dateTo" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" id="clearFilters">Clear</button>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100" id="exportCsv">Export CSV</button>
                        <button class="btn btn-outline-primary w-100" id="exportExcel">Export Excel</button>
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
                <div id="ast-inventory-table"></div>
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
<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';

let inventoryTable = null;
let rawRows = [];
let invMsgTimeout = null;

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
    const fromVal = $('#dateFrom').val();
    const toVal = $('#dateTo').val();
    const fromDate = fromVal ? new Date(fromVal) : null;
    const toDate = toVal ? new Date(toVal) : null;
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

function initTable() {
    inventoryTable = new Tabulator('#ast-inventory-table', {
        layout: "fitColumns",
        // Keep rendering stable when pagination is set to "All"
        renderVertical: "basic",
        responsiveLayout: "collapse",
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        groupBy: "item_category_name",
        groupHeader: function(value, count, data) {
            const qty = data.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
            const name = value || "Uncategorized";
            return `${name} <span class="text-muted small">(${count} items, Qty ${qty})</span>`;
        },
        columns: [
            { title: "Image", field: "category_photo_thumb_url", width: 70, hozAlign: "center", formatter: function(cell){
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
            { title: "Property Code", field: "property_code", width: 190, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Property No.", field: "property_number", width: 130, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Serial No.", field: "serial_number", width: 150, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue(), '-');
            }},
            { title: "Description", field: "item_description", widthGrow: 2, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Qty", field: "quantity", width: 70, hozAlign: "center", headerFilter: "number", headerFilterPlaceholder: "<= qty", headerFilterFunc: "<=" },
            { title: "Available Qty", field: "available_qty", width: 110, hozAlign: "center", headerFilter: "number", headerFilterPlaceholder: "<= qty", headerFilterFunc: "<=", formatter: function(cell){
                const v = cell.getValue();
                return v !== null && v !== '' ? parseInt(v, 10) : '-';
            }},
            { title: "Allowed Status", field: "allowed_status_names", width: 180, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                const v = cell.getValue();
                const text = v || '-';
                return `<span class="two-line-cell text-muted small" title="${escapeHtml(text)}">${escapeHtml(text)}</span>`;
            }},
            { title: "Unit", field: "unit", width: 80, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Source", field: "source_of_fund", width: 120, headerFilter: "input", headerFilterPlaceholder: "Filter...", formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: "Cost", field: "cost_value", width: 100, headerFilter: "number", headerFilterPlaceholder: "<= cost", headerFilterFunc: "<=", formatter: function(cell){
                const v = cell.getValue();
                return v !== null && v !== '' ? parseFloat(v).toFixed(2) : '-';
            }},
            { title: "Issued Qty", field: "issued_total", width: 95, hozAlign: "center", formatter: function(){
                return "-";
            }},
            { title: "Issuance Details", field: "issued_details", width: 140, formatter: function(){
                return '<button class="btn btn-sm btn-outline-secondary" disabled>View</button>';
            }},
            { title: "Date Modified", field: "created_at", width: 120, headerFilter: "input", headerFilterPlaceholder: "YYYY-MM-DD", formatter: function(cell){
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

    $('#invSearch').on('keyup', applyFilters);
    $('#dateFrom, #dateTo').on('change', applyFilters);
    $('#clearFilters').on('click', function() {
        $('#invSearch').val('');
        $('#dateFrom').val('');
        $('#dateTo').val('');
        applyFilters();
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
            "Qty": r.quantity ?? '',
            "Available Qty": r.available_qty ?? '',
            "Allowed Status": r.allowed_status_names || '',
            "Unit": r.unit || '',
            "Source": r.source_of_fund || '',
            "Cost": r.cost_value ?? '',
            "Date Modified": r.created_at || ''
        }));

        const ws = XLSX.utils.json_to_sheet(rows);
        ws['!cols'] = [
            { wch: 18 }, // Property Code
            { wch: 12 }, // Property No.
            { wch: 20 }, // Serial No.
            { wch: 40 }, // Description
            { wch: 6 },  // Qty
            { wch: 14 }, // Available Qty
            { wch: 28 }, // Allowed Status
            { wch: 8 },  // Unit
            { wch: 18 }, // Source
            { wch: 10 }, // Cost
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
