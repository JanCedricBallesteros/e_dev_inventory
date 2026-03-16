<?php
// admin/modules/consumable/csm_physical_checking.php
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
        user_has_access(array("CSM", "PO"))
    )
)) {
    header("Location: " . BASE_URL);
    exit();
}

$isAdmin = role_has("ADMIN");
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
        .section-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .section-card .card-header {
            background: var(--bg-eclearance-rgb);
            color: #fff;
            font-weight: 600;
        }
        .badge-code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .filter-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .item-thumb {
            width: 140px;
            height: 140px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #f8f9fa;
            cursor: zoom-in;
        }
        .info-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .info-value {
            font-weight: 600;
            word-break: break-word;
        }
        .mini-stat {
            border: 1px solid #edf0f3;
            border-radius: 10px;
            padding: 10px 12px;
            background: #fff;
        }
        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .pill-available { background: #d1fae5; color: #065f46; }
        .pill-critical { background: #fef3c7; color: #92400e; }
        .pill-out { background: #fee2e2; color: #991b1b; }
        .pill-unavailable { background: #e5e7eb; color: #374151; }

        /* Camera preview copied from csm_manage_inventory.php */
        #preview-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            position: relative;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 4 / 3;
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
        <h1 class="h4 fw-semibold mb-1">Physical Checking (CSM)</h1>
        <p class="text-muted small mb-0">Create stock-check sessions and record physical counts for consumable inventory items.</p>
    </div>

    <section class="section">
        <div class="row g-3">

            <?php if ($isAdmin) { ?>
            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header">
                        <i class="bi bi-calendar-check"></i>&ensp;Audit Sessions
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <form id="sessionForm" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <div class="filter-label">Audit Name (optional)</div>
                                <input type="text" class="form-control" name="audit_name" placeholder="e.g., March 2026 stock check">
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Start Date</div>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">End Date</div>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                            <div class="col-md-2">
                                <div class="filter-label">Status</div>
                                <select class="form-select" name="status">
                                    <option value="Pending">Pending</option>
                                    <option value="Active">Active</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>
                        </form>
                        <div id="sessionMsg" class="mt-2"></div>
                        <div class="mt-3" id="sessionsTable"></div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header">
                        <i class="bi bi-clipboard-check"></i>&ensp;Check Inventory Item
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <div class="filter-label">Active Session</div>
                                <select class="form-select" id="activeSession"></select>
                            </div>
                            <div class="col-md-5">
                                <div class="filter-label">Inventory Item Code / QR Verification</div>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="itemCodeInput" placeholder="Scan or search item code">
                                    <button class="btn btn-outline-secondary" type="button" id="openSearchScanner" title="Scan QR">
                                        <i class="bi bi-qr-code-scan"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button class="btn btn-outline-secondary" id="btnLoadItem">Load Item</button>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-lg-3">
                                <div class="border rounded p-3 text-center bg-white h-100">
                                    <img id="itemCategoryImg" src="" alt="Item Image" class="item-thumb d-none">
                                    <div id="itemNoImg" class="text-muted small">No image available</div>
                                </div>
                            </div>

                            <div class="col-lg-9">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="mini-stat">
                                            <div class="info-label">Inventory Code</div>
                                            <div class="info-value" id="infoItemCode">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mini-stat">
                                            <div class="info-label">Category Code</div>
                                            <div class="info-value" id="infoCategoryCode">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mini-stat">
                                            <div class="info-label">Category Name</div>
                                            <div class="info-value" id="infoCategoryName">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mini-stat">
                                            <div class="info-label">Item Description</div>
                                            <div class="info-value" id="infoDescription">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Acquisition Date</div>
                                            <div class="info-value" id="infoAcquisitionDate">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Last Updated</div>
                                            <div class="info-value" id="infoLastUpdated">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Item Cost</div>
                                            <div class="info-value" id="infoItemCost">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Source of Funds</div>
                                            <div class="info-value" id="infoSourceOfFunds">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Original Qty</div>
                                            <div class="info-value" id="infoUnitQty">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Current Qty</div>
                                            <div class="info-value" id="infoCurrentQty">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">Critical Level</div>
                                            <div class="info-value" id="infoCritLevel">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-stat">
                                            <div class="info-label">System Status</div>
                                            <div class="info-value" id="infoStockStatus">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="filter-label">Counted Quantity</div>
                                <input type="number" min="0" class="form-control" id="countedQuantity" value="0">
                            </div>

                            <div class="col-md-3">
                                <div class="filter-label">Condition</div>
                                <select class="form-select" id="condition">
                                    <option value="">Select</option>
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Missing">Missing</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <div class="filter-label">Status at Check</div>
                                <input type="text" class="form-control" id="statusAtCheck" placeholder="e.g., Available / Critical Stock">
                            </div>

                            <div class="col-md-3">
                                <div class="filter-label">Storage Location</div>
                                <input type="text" class="form-control" id="storageLocation" placeholder="e.g., Supply Room A">
                            </div>

                            <div class="col-md-9">
                                <div class="filter-label">Remarks</div>
                                <input type="text" class="form-control" id="remarks" placeholder="Notes, discrepancy details, observations...">
                            </div>

                            <div class="col-md-3 d-grid">
                                <button class="btn btn-success" id="saveCheck">
                                    <i class="bi bi-check2-circle"></i> Save Check
                                </button>
                            </div>
                        </div>

                        <div id="checkMsg" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span><i class="bi bi-table"></i>&ensp;Checked Items</span>
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="form-select form-select-sm" id="filterSession" style="min-width: 180px;"></select>
                            <input type="text" class="form-control form-control-sm" id="checkSearch" placeholder="Search code, description, category, location" style="max-width:280px;">
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="checksTable"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- Scan QR -->
<div class="modal fade" id="scanQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-upc-scan"></i>&ensp;Scan QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="cameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="btnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="btnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div id="preview-wrapper">
                    <div id="preview"></div>
                    <div class="scanner-loading" id="scannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
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
    </div>
</div>

<?php include_once FOOTER_PATH; ?>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const BASE_URL = <?= json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/consumable/process/csm_physical_checking_process.php';
const isAdmin = <?= $isAdmin ? 'true' : 'false'; ?>;

let sessionsTable = null;
let checksTable = null;
let currentItem = null;

function showMessage(target, type, text) {
    $(target).html(`<div class="alert alert-${type} mb-2">${text}</div>`);
}

function showErrorToast(msg) {
    if (typeof error_notif === 'function') {
        error_notif(msg);
    } else {
        showMessage('#checkMsg', 'danger', msg);
    }
}

function peso(value) {
    const num = Number(value || 0);
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(num);
}

function stockBadge(label) {
    const val = String(label || '').toLowerCase();
    if (val.includes('critical')) return `<span class="status-pill pill-critical">${label}</span>`;
    if (val.includes('out')) return `<span class="status-pill pill-out">${label}</span>`;
    if (val.includes('unavailable')) return `<span class="status-pill pill-unavailable">${label}</span>`;
    return `<span class="status-pill pill-available">${label || 'Available'}</span>`;
}

function varianceBadge(value) {
    const n = Number(value || 0);
    let cls = 'bg-secondary';
    if (n > 0) cls = 'bg-primary';
    if (n < 0) cls = 'bg-danger';
    if (n === 0) cls = 'bg-success';
    return `<span class="badge ${cls}">${n}</span>`;
}

function loadSessions() {
    $.post(PROCESS_URL, { action: 'list_sessions' }, function(res) {
        if (!res.success) return;

        if (sessionsTable) {
            sessionsTable.setData(res.data || []);
        }

        const active = (res.data || []).filter(r => r.status === 'Active');
        const options = ['<option value="">Select Active Session</option>'];

        active.forEach(s => {
            const label = `${s.series_code} (${s.start_date} to ${s.end_date})`;
            options.push(`<option value="${s.id}">${label}</option>`);
        });

        $('#activeSession').html(options.join(''));

        const filterOpts = ['<option value="">All Sessions</option>'];
        (res.data || []).forEach(s => {
            filterOpts.push(`<option value="${s.id}">${s.series_code}</option>`);
        });
        $('#filterSession').html(filterOpts.join(''));
    }, 'json');
}

function initSessionsTable() {
    if (!isAdmin) return;

    sessionsTable = new Tabulator('#sessionsTable', {
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No sessions found',
        pagination: 'local',
        paginationSize: 20,
        paginationSizeSelector: [20, 100, 500, 1000, true],
        columns: [
            {
                title: 'Series Code',
                field: 'series_code',
                width: 170,
                formatter: function(cell) {
                    return `<span class="badge bg-light text-dark border badge-code">${cell.getValue()}</span>`;
                }
            },
            { title: 'Audit Name', field: 'audit_name', widthGrow: 2 },
            { title: 'Start', field: 'start_date', width: 120 },
            { title: 'End', field: 'end_date', width: 120 },
            {
                title: 'Status',
                field: 'status',
                width: 110,
                formatter: function(cell) {
                    const status = cell.getValue() || '';
                    if (status === 'Active') return '<span class="badge bg-success">Active</span>';
                    if (status === 'Closed') return '<span class="badge bg-secondary">Closed</span>';
                    return '<span class="badge bg-warning text-dark">Pending</span>';
                }
            },
            { title: 'Created By', field: 'created_by_name', width: 170 },
            {
                title: 'Actions',
                field: 'id',
                width: 220,
                formatter: function(cell) {
                    const id = cell.getValue();
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-warning btn-set-status" data-id="${id}" data-status="Pending">Pending</button>
                            <button class="btn btn-outline-success btn-set-status" data-id="${id}" data-status="Active">Active</button>
                            <button class="btn btn-outline-secondary btn-set-status" data-id="${id}" data-status="Closed">Close</button>
                        </div>
                    `;
                }
            }
        ]
    });
}

function initChecksTable() {
    checksTable = new Tabulator('#checksTable', {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_checks' },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No checked items found',
        pagination: 'local',
        paginationSize: 20,
        paginationSizeSelector: [20, 100, 500, 1000, true],
        ajaxResponse: function(url, params, response) {
            return response.data || [];
        },
        columns: [
            { title: 'Series', field: 'series_code', width: 145 },
            { title: 'Item Code', field: 'inventory_system_item_code', width: 165 },
            { title: 'Category Code', field: 'item_category_code', width: 130 },
            { title: 'Item Description', field: 'item_description', widthGrow: 2 },
            { title: 'System Qty', field: 'system_current_quantity', width: 110, hozAlign: 'center' },
            { title: 'Counted Qty', field: 'counted_quantity', width: 110, hozAlign: 'center' },
            {
                title: 'Variance',
                field: 'variance_quantity',
                width: 100,
                hozAlign: 'center',
                formatter: function(cell) {
                    return varianceBadge(cell.getValue());
                }
            },
            {
                title: 'Status',
                field: 'status_at_check',
                width: 130,
                formatter: function(cell) {
                    return stockBadge(cell.getValue());
                }
            },
            { title: 'Condition', field: 'condition', width: 110 },
            { title: 'Location', field: 'storage_location', width: 150 },
            { title: 'Checked At', field: 'checked_at', width: 160 },
            { title: 'Checked By', field: 'checked_by_name', width: 150 },
            { title: 'Remarks', field: 'remarks', width: 200 }
        ]
    });
}

function loadItem(code) {
    code = (code || '').trim();

    if (!code) {
        resetItemUI();
        return;
    }

    $.post(PROCESS_URL, { action: 'get_item_by_code', item_code: code }, function(res) {
        if (!res.success) {
            resetItemUI();
            showErrorToast(res.message || 'Item not found.');
            return;
        }

        currentItem = res.data;

        $('#infoItemCode').text(currentItem.inventory_system_item_code || '-');
        $('#infoCategoryCode').text(currentItem.item_category_code || '-');
        $('#infoCategoryName').text(currentItem.item_category_name || '-');
        $('#infoDescription').text(currentItem.item_description || '-');
        $('#infoAcquisitionDate').text(currentItem.acquisition_date || '-');
        $('#infoLastUpdated').text(currentItem.last_updated || currentItem.updated_at || '-');
        $('#infoItemCost').text(typeof currentItem.item_cost !== 'undefined' ? peso(currentItem.item_cost) : '-');
        $('#infoSourceOfFunds').text(currentItem.source_of_funds || '-');
        $('#infoUnitQty').text(currentItem.unit_quantity ?? '-');
        $('#infoCurrentQty').text(currentItem.current_unit_quantity ?? '-');
        $('#infoCritLevel').text(currentItem.unit_crit_level ?? '-');
        $('#infoStockStatus').html(stockBadge(currentItem.system_stock_label || 'Available'));

        $('#countedQuantity').val(Number(currentItem.current_unit_quantity || 0));
        $('#statusAtCheck').val(currentItem.system_stock_label || '');
        $('#condition').val('');
        $('#storageLocation').val('');
        $('#remarks').val('');

        if (currentItem.item_image_thumb_url || currentItem.item_image_url) {
            const src = currentItem.item_image_thumb_url || currentItem.item_image_url;
            $('#itemCategoryImg').attr('src', src).removeClass('d-none');
            $('#itemNoImg').hide();
        } else {
            $('#itemCategoryImg').addClass('d-none').attr('src', '');
            $('#itemNoImg').show();
        }
    }, 'json');
}

function resetItemUI() {
    currentItem = null;

    $('#infoItemCode').text('-');
    $('#infoCategoryCode').text('-');
    $('#infoCategoryName').text('-');
    $('#infoDescription').text('-');
    $('#infoAcquisitionDate').text('-');
    $('#infoLastUpdated').text('-');
    $('#infoItemCost').text('-');
    $('#infoSourceOfFunds').text('-');
    $('#infoUnitQty').text('-');
    $('#infoCurrentQty').text('-');
    $('#infoCritLevel').text('-');
    $('#infoStockStatus').text('-');

    $('#countedQuantity').val(0);
    $('#condition').val('');
    $('#statusAtCheck').val('');
    $('#storageLocation').val('');
    $('#remarks').val('');

    $('#itemCategoryImg').addClass('d-none').attr('src', '');
    $('#itemNoImg').show();
}

$(document).ready(function() {
    initSessionsTable();
    initChecksTable();
    loadSessions();

    $('#openSearchScanner').on('click', function() {
        $('#scanQrModal').modal('show');
    });

    $('#btnLoadItem').on('click', function(e) {
        e.preventDefault();
        resetItemUI();
        loadItem($('#itemCodeInput').val().trim());
    });

    $('#itemCodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            resetItemUI();
            loadItem($('#itemCodeInput').val().trim());
        }
    });

    $('#itemCodeInput').on('input', function() {
        const val = $(this).val().trim();
        if (!val || (currentItem && currentItem.inventory_system_item_code !== val && currentItem.qr_verification !== val)) {
            resetItemUI();
        }
    });

    $('#sessionForm').on('submit', function(e) {
        e.preventDefault();
        if (!isAdmin) return;

        const data = $(this).serialize() + '&action=create_session';
        $.post(PROCESS_URL, data, function(res) {
            if (res.success) {
                showMessage('#sessionMsg', 'success', res.message || 'Session created.');
                $('#sessionForm')[0].reset();
                loadSessions();
            } else {
                showMessage('#sessionMsg', 'danger', res.message || 'Failed to create session.');
            }
        }, 'json');
    });

    $('#sessionsTable').on('click', '.btn-set-status', function() {
        const id = $(this).data('id');
        const status = $(this).data('status');

        $.post(PROCESS_URL, { action: 'update_session_status', session_id: id, status: status }, function(res) {
            if (res.success) {
                loadSessions();
            } else {
                showMessage('#sessionMsg', 'danger', res.message || 'Failed to update status.');
            }
        }, 'json');
    });

    $('#saveCheck').on('click', function() {
        $('#checkMsg').html('');

        const sessionId = $('#activeSession').val();
        if (!sessionId) {
            showMessage('#checkMsg', 'danger', 'Select an active session first.');
            return;
        }

        if (!currentItem || !currentItem.inventory_system_item_code) {
            showMessage('#checkMsg', 'danger', 'Load an item first.');
            return;
        }

        const countedQty = Number($('#countedQuantity').val() || 0);
        if (countedQty < 0) {
            showMessage('#checkMsg', 'danger', 'Counted quantity cannot be negative.');
            return;
        }

        const payload = {
            action: 'create_check',
            session_id: sessionId,
            item_code: currentItem.inventory_system_item_code,
            counted_quantity: countedQty,
            condition: $('#condition').val(),
            remarks: $('#remarks').val().trim(),
            status_at_check: $('#statusAtCheck').val().trim(),
            storage_location: $('#storageLocation').val().trim()
        };

        $.post(PROCESS_URL, payload, function(res) {
            if (res.success) {
                showMessage('#checkMsg', 'success', res.message || 'Check saved.');
                if (checksTable) {
                    checksTable.setData(PROCESS_URL, {
                        action: 'list_checks',
                        session_id: $('#filterSession').val(),
                        search: $('#checkSearch').val().trim()
                    }, 'POST');
                }
            } else {
                showMessage('#checkMsg', 'danger', res.message || 'Failed to save check.');
            }
        }, 'json');
    });

    $('#filterSession').on('change', function() {
        checksTable.setData(PROCESS_URL, {
            action: 'list_checks',
            session_id: $('#filterSession').val(),
            search: $('#checkSearch').val().trim()
        }, 'POST');
    });

    $('#checkSearch').on('keyup', function() {
        checksTable.setData(PROCESS_URL, {
            action: 'list_checks',
            session_id: $('#filterSession').val(),
            search: $('#checkSearch').val().trim()
        }, 'POST');
    });
});

/* Camera function copied from csm_manage_inventory.php and adapted for this page */

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

let html5QrcodeScanner = null;
let isScanning = false;

function showScanError(msg) {
    const errEl = document.getElementById('scanError');
    const loadingEl = document.getElementById('scannerLoading');
    errEl.textContent = msg;
    errEl.style.display = msg ? 'block' : 'none';
    loadingEl.style.display = 'none';
    if (msg) console.error('QR Error:', msg);
}

function setRunning(running) {
    document.getElementById('btnStart').disabled = running;
    document.getElementById('btnStop').disabled = !running;
    document.getElementById('cameraSelect').disabled = running;
    document.getElementById('scannerLoading').style.display = running ? 'flex' : 'none';
    isScanning = running;
}

async function loadCameras() {
    showScanError('');
    const cameraSelect = document.getElementById('cameraSelect');
    cameraSelect.innerHTML = `<option value="">Loading cameras...</option>`;

    try {
        const cameras = await Html5Qrcode.getCameras();
        if (!cameras || cameras.length === 0) {
            cameraSelect.innerHTML = `<option value="">No cameras found</option>`;
            showScanError('No cameras found. Ensure:\n• Camera is connected\n• Browser has permission to access camera\n• HTTPS is enabled (or localhost)\n• No other app is using the camera');
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
        const errMsg = e && e.message ? e.message : String(e);
        showScanError(`Cannot access cameras: ${errMsg}`);
    }
}

async function startScanner() {
    showScanError('');
    setRunning(true);

    const cameraSelect = document.getElementById('cameraSelect');
    const selectedCamId = cameraSelect.value;
    if (!selectedCamId) {
        showScanError('Please select a camera first.');
        setRunning(false);
        return;
    }

    if (html5QrcodeScanner) {
        try { await html5QrcodeScanner.stop(); } catch (e) {}
    }

    html5QrcodeScanner = new Html5Qrcode('preview');

    const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase());
    let config;
    if (isMobile) {
        config = {
            fps: 15,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            disableFlip: false,
            showTorchButtonIfSupported: true,
            supportedScanTypes: []
        };
    } else {
        config = {
            fps: 10,
            qrbox: { width: 200, height: 200 },
            disableFlip: false,
            showTorchButtonIfSupported: false,
            supportedScanTypes: []
        };
    }

    try {
        await html5QrcodeScanner.start(
            selectedCamId,
            config,
            (decodedText) => {
                playBeep();
                document.getElementById('lastScanned').textContent = decodedText;
                $('#itemCodeInput').val(decodedText);
                resetItemUI();
                loadItem(decodedText);
                $('#scanQrModal').modal('hide');
            },
            (errorMessage) => {}
        );
        document.getElementById('scannerLoading').style.display = 'none';
    } catch (e) {
        setRunning(false);
        showScanError('Failed to start camera: ' + (e && e.message ? e.message : String(e)));
    }
}

async function stopScanner() {
    showScanError('');
    if (!html5QrcodeScanner || !isScanning) return;

    try {
        await html5QrcodeScanner.stop();
        setRunning(false);
    } catch (e) {
        setRunning(false);
    }
}

$('#scanQrModal').on('shown.bs.modal', function () {
    loadCameras();
    setRunning(false);
    document.getElementById('lastScanned').textContent = '—';
    document.getElementById('preview').innerHTML = '';
});

$('#btnStart').on('click', startScanner);
$('#btnStop').on('click', stopScanner);

$('#cameraSelect').on('change', function () {
    if (isScanning) {
        stopScanner().then(() => startScanner());
    }
});

$('#scanQrModal').on('hidden.bs.modal', function () {
    stopScanner();
    document.getElementById('preview').innerHTML = '';
});
</script>
</body>
</html>