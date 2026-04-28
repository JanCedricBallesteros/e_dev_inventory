<?php
// admin/modules/nonconsumable/ast_summary_report.php
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
        .summary-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; background: #fff; }
        .summary-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
        .summary-value { font-size: 18px; font-weight: 700; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .report-box { border: 1px solid #dee2e6; border-radius: 10px; overflow: hidden; background: #fff; }
        .report-box .box-head { background: #f8f9fa; padding: .65rem .9rem; font-weight: 600; border-bottom: 1px solid #dee2e6; }
        .report-box .box-body { max-height: 280px; overflow: auto; }
        .table thead th { white-space: nowrap; }
        .tabulator { font-size: 0.875rem; }
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
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">AST Summary Report</h1>
        <p class="text-muted small mb-0">Dedicated summary view for non-consumable inventory, location, and issuance data.</p>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold">
                <i class="bi bi-graph-up"></i>&ensp;AST Summary Report
            </div>
            <div class="card-body mt-3 bg-white">
                <div id="msgBox" class="alert alert-danger d-none mb-3"></div>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <div class="filter-label">Search</div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Property Tag, Property No., description, category, location">
                    </div>
                    <div class="col-md-4">
                        <div class="filter-label">Date Range</div>
                        <input type="text" id="dateRange" class="form-control" placeholder="YYYY-MM-DD - YYYY-MM-DD">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100" id="btnCsv">Export CSV</button>
                        <button class="btn btn-outline-primary w-100" id="btnExcel">Export Excel</button>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Total Items</div>
                            <div class="summary-value" id="sumItems">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Total Quantity</div>
                            <div class="summary-value" id="sumQty">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Assigned Items</div>
                            <div class="summary-value" id="sumAssigned">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Active Issued Qty</div>
                            <div class="summary-value" id="sumIssuedQty">0</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-4">
                        <div class="report-box h-100">
                            <div class="box-head">Category Breakdown</div>
                            <div class="box-body">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="categoryBody">
                                        <tr><td colspan="3" class="text-muted small">No data.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="report-box h-100">
                            <div class="box-head">Location Breakdown</div>
                            <div class="box-body">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Location</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="locationBody">
                                        <tr><td colspan="3" class="text-muted small">No data.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="report-box h-100">
                            <div class="box-head">Top Issued To</div>
                            <div class="box-body">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="issuedBody">
                                        <tr><td colspan="3" class="text-muted small">No data.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-semibold">Detailed Snapshot</div>
                    <div class="small text-muted" id="scopeLabel">Current filter scope</div>
                </div>
                <div id="summaryTable"></div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';

let table = null;
let tableReady = false;
let rowsCache = [];
let loadToken = 0;
let pendingLoad = false;
let loadXhr = null;

function escapeHtml(v) {
    return String(v === null || v === undefined ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function parseDate(val) {
    if (!val) return null;
    const d = new Date(String(val).split(' ')[0]);
    return isNaN(d.getTime()) ? null : d;
}

function parseDateRangeInput(raw) {
    const text = (raw || '').trim();
    if (!text) return { from: null, to: null };
    const parts = text.split(/\s+-\s+/).filter(Boolean);
    if (parts.length === 1) {
        const d = parseDate(parts[0]);
        return { from: d, to: d };
    }
    return { from: parseDate(parts[0]), to: parseDate(parts[1]) };
}

function formatDateYmd(d) {
    if (!d) return '';
    const yy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
}

function showMessage(text) {
    if (!text) {
        $('#msgBox').addClass('d-none').text('');
        return;
    }
    $('#msgBox').removeClass('d-none').text(text);
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

function getFilters() {
    const search = ($('#searchInput').val() || '').trim();
    const range = parseDateRangeInput($('#dateRange').val());
    return {
        search: search,
        date_from: range.from ? formatDateYmd(range.from) : '',
        date_to: range.to ? formatDateYmd(range.to) : ''
    };
}

function summarize(rows) {
    const list = Array.isArray(rows) ? rows : [];
    const totalItems = list.length;
    const totalQty = list.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
    const assignedItems = list.reduce((sum, r) => sum + ((parseInt(r.issued_count, 10) || 0) > 0 ? 1 : 0), 0);
    const issuedQty = list.reduce((sum, r) => sum + (parseInt(r.issued_count, 10) || 0), 0);
    $('#sumItems').text(totalItems);
    $('#sumQty').text(totalQty);
    $('#sumAssigned').text(assignedItems);
    $('#sumIssuedQty').text(issuedQty);

    const categoryMap = {};
    const locationMap = {};
    const issuedMap = {};
    list.forEach(function(r) {
        const qty = parseInt(r.quantity, 10) || 0;
        const issued = parseInt(r.issued_count, 10) || 0;
        const category = String(r.item_category_name || 'Uncategorized').trim() || 'Uncategorized';
        const location = String(r.location_label || 'Unassigned').trim() || 'Unassigned';
        const issuedTo = String(r.issued_to_name || 'Unassigned').trim() || 'Unassigned';

        categoryMap[category] = categoryMap[category] || { items: 0, qty: 0 };
        categoryMap[category].items += 1;
        categoryMap[category].qty += qty;

        locationMap[location] = locationMap[location] || { items: 0, qty: 0 };
        locationMap[location].items += 1;
        locationMap[location].qty += qty;

        issuedMap[issuedTo] = issuedMap[issuedTo] || { items: 0, qty: 0 };
        issuedMap[issuedTo].items += issued > 0 ? 1 : 0;
        issuedMap[issuedTo].qty += issued;
    });

    const buildRows = function(map) {
        const keys = Object.keys(map).sort(function(a, b) {
            return map[b].qty - map[a].qty || a.localeCompare(b);
        }).slice(0, 8);
        if (!keys.length) return '<tr><td colspan="3" class="text-muted small">No data.</td></tr>';
        return keys.map(function(key) {
            const row = map[key];
            return `<tr><td>${escapeHtml(key)}</td><td class="text-end">${row.items}</td><td class="text-end">${row.qty}</td></tr>`;
        }).join('');
    };

    $('#categoryBody').html(buildRows(categoryMap));
    $('#locationBody').html(buildRows(locationMap));
    $('#issuedBody').html(buildRows(issuedMap));

    const scope = [];
    if (getFilters().search) scope.push('Search: ' + getFilters().search);
    if ($('#dateRange').val().trim()) scope.push('Dates: ' + $('#dateRange').val().trim());
    $('#scopeLabel').text(scope.length ? scope.join(' | ') : 'Current filter scope');
}

function loadData() {
    if (!tableReady) {
        pendingLoad = true;
        return;
    }
    if (loadXhr && typeof loadXhr.abort === 'function') {
        loadXhr.abort();
        loadXhr = null;
    }
    const token = ++loadToken;
    const filters = getFilters();
    showMessage('');
    table.replaceData([]);
    rowsCache = [];

    loadXhr = $.post(PROCESS_URL, {
        action: 'list_items',
        offset: 0,
        limit: 1000,
        search: filters.search,
        date_from: filters.date_from,
        date_to: filters.date_to,
        issued_only: 0
    }, function(res) {
        if (token !== loadToken) return;
        if (!res || !res.success) {
            showMessage((res && res.message) ? res.message : 'Failed to load report.');
            return;
        }
        rowsCache = Array.isArray(res.data) ? res.data : [];
        table.setData(rowsCache);
        summarize(rowsCache);
    }, 'json').fail(function() {
        if (token !== loadToken) return;
        showMessage('Server error while loading report.');
    });
}

function initTable() {
    table = new Tabulator('#summaryTable', {
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No items found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        columns: [
            { title: 'Img', field: 'category_photo_thumb_url', width: 78, hozAlign: 'center', formatter: function(cell){
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                if (url) return `<img src="${escapeHtml(url)}" alt="Item image" style="width:42px;height:42px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;" data-full="${escapeHtml(full || '')}">`;
                return '<span class="text-muted small">-</span>';
            }},
            { title: 'Property Tag', field: 'property_code', width: 170, formatter: function(cell){ return twoLineText(cell.getValue()); }},
            { title: 'Property No.', field: 'property_number', width: 120, formatter: function(cell){ return twoLineText(cell.getValue()); }},
            { title: 'Serial No.', field: 'serial_number', width: 140, formatter: function(cell){ return twoLineText(cell.getValue(), '-'); }},
            { title: 'Description', field: 'item_description', widthGrow: 4, minWidth: 220, formatter: function(cell){ return threeLineText(cell.getValue()); }},
            { title: 'Qty / Unit', field: 'quantity', width: 110, hozAlign: 'center', formatter: function(cell){
                const row = cell.getRow().getData();
                const qty = row.quantity !== null && row.quantity !== undefined ? parseInt(row.quantity, 10) : '';
                const unit = row.unit ? String(row.unit) : '';
                return `<div class="text-center">${qty}${unit ? ' <span class="text-muted">' + escapeHtml(unit) + '</span>' : ''}</div>`;
            }},
            { title: 'Issued To', field: 'issued_to_name', width: 170, formatter: function(cell){ return cell.getValue() ? twoLineText(cell.getValue()) : '<span class="text-muted">-</span>'; }},
            { title: 'Location', field: 'location_label', width: 220, formatter: function(cell){ return cell.getValue() ? threeLineText(cell.getValue()) : '<span class="text-muted">-</span>'; }},
            { title: 'Date Added', field: 'created_at', width: 135, formatter: function(cell){
                const d = parseDate(cell.getValue());
                return d ? d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
            }}
        ]
    });
    table.on('tableBuilt', function() {
        tableReady = true;
        if (pendingLoad) {
            pendingLoad = false;
            loadData();
        }
    });
}

$(document).ready(function() {
    initTable();

    $('#searchInput').on('input', function() {
        clearTimeout(window.__astSummaryTimer);
        window.__astSummaryTimer = setTimeout(loadData, 250);
    });
    $('#dateRange').on('change keyup', loadData);
    if (typeof $.fn.daterangepicker === 'function') {
        $('#dateRange').daterangepicker({
            autoUpdateInput: false,
            locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
            opens: 'left'
        });
        $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            loadData();
        });
        $('#dateRange').on('cancel.daterangepicker', function() {
            $(this).val('');
            loadData();
        });
    }

    $('#btnCsv').on('click', function() {
        if (table) table.download('csv', 'ast_summary_report.csv');
    });
    $('#btnExcel').on('click', function() {
        if (table) table.download('xlsx', 'ast_summary_report.xlsx', { sheetName: 'AST Summary' });
    });

    if (typeof initQrSearch === 'function') {
        initQrSearch({
            searchInput: '#searchInput',
            onSearch: loadData
        });
    }

    loadData();
});
</script>
</body>
</html>
