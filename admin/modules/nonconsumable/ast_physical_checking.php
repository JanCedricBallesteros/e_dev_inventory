<?php
// admin/modules/nonconsumable/ast_physical_checking.php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(
    role_has("SUPER_ADMIN") ||
    role_has("ADMIN") ||
    (
        (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) &&
        user_has_access("AST")
    )
)) {
    header("Location: " . BASE_URL);
    exit();
}
$isAdmin = (role_has("SUPER_ADMIN") || role_has("ADMIN"));
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .section-card .card-header { background: var(--bg-eclearance-rgb); color: #fff; font-weight: 600; }
        .badge-code { font-family: 'Courier New', monospace; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .item-thumb { width: 120px; height: 120px; border-radius: 10px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
        .info-label { font-size: 12px; color: #6c757d; }
        .info-value { font-weight: 600; }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">Physical Checking (AST)</h1>
        <p class="text-muted small mb-0">Create audit sessions and record physical checks for non-consumable items.</p>
    </div>

    <section class="section">
        <div class="row g-3">
            <?php if ($isAdmin) { ?>
            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header"><i class="bi bi-calendar-check"></i>&ensp;Audit Sessions</div>
                    <div class="card-body mt-3 bg-white">
                        <form id="sessionForm" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <div class="filter-label">Audit Name (optional)</div>
                                <input type="text" class="form-control" name="audit_name" placeholder="e.g., March 2026">
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
                    <div class="card-header"><i class="bi bi-clipboard-check"></i>&ensp;Check Item</div>
                    <div class="card-body mt-3 bg-white">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <div class="filter-label">Active Session</div>
                                <select class="form-select" id="activeSession"></select>
                            </div>
                            <div class="col-md-5">
                                <div class="filter-label">Property Code</div>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="propertyCodeInput" placeholder="Scan or search property code">
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
                            <div class="col-md-4">
                                <div class="border rounded p-2 text-center bg-white">
                                    <img id="itemCategoryImg" src="" alt="Category" class="item-thumb d-none">
                                    <div id="itemNoImg" class="text-muted small">No image</div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="info-label">Property Code</div>
                                        <div class="info-value" id="infoPropertyCode">-</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Property Number</div>
                                        <div class="info-value" id="infoPropertyNumber">-</div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="info-label">Item Description</div>
                                        <div class="info-value" id="infoDescription">-</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Date Stock</div>
                                        <div class="info-value" id="infoDateStock">-</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Date Issued</div>
                                        <div class="info-value" id="infoDateIssued">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="filter-label">Serial Number</div>
                                <input type="text" class="form-control" id="serialNumber">
                            </div>
                            <div class="col-md-2">
                                <div class="filter-label">Quantity (fixed)</div>
                                <input type="number" min="1" class="form-control" id="qtyChecked" value="1" readonly>
                            </div>
                            <div class="col-md-2">
                                <div class="filter-label">Unit</div>
                                <input type="text" class="form-control" id="unitChecked" readonly>
                            </div>
                            <div class="col-md-2">
                                <div class="filter-label">Condition</div>
                                <select class="form-select" id="condition">
                                    <option value="">Select</option>
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Missing">Missing</option>
                                    <option value="Under Repair">Under Repair</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Status</div>
                                <input type="text" class="form-control" id="statusAtCheck" placeholder="e.g., Available">
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Facility</div>
                                <input type="text" class="form-control" id="facility">
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Accountable</div>
                                <input type="text" class="form-control" id="accountable">
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Issued To</div>
                                <input type="text" class="form-control" id="issuedTo">
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Managed By</div>
                                <input type="text" class="form-control" id="managedBy">
                            </div>
                            <div class="col-md-6">
                                <div class="filter-label">Remarks</div>
                                <input type="text" class="form-control" id="remarks" placeholder="Notes...">
                            </div>
                            <div class="col-md-3">
                                <div class="filter-label">Date Issued (if any)</div>
                                <input type="date" class="form-control" id="dateIssued">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button class="btn btn-success" id="saveCheck"><i class="bi bi-check2-circle"></i> Save Check</button>
                            </div>
                        </div>
                        <div id="checkMsg" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-table"></i>&ensp;Checked Items</span>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="filterSession"></select>
                            <input type="text" class="form-control form-control-sm" id="checkSearch" placeholder="Search property code or description" style="max-width:260px;">
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

<!-- SEARCH QR MODAL -->
<div class="modal fade" id="searchQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR</h5>
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
                    <span id="searchLastScanned" class="fw-semibold">—</span>
                </div>
                <div id="searchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<?php include_once FOOTER_PATH; ?>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_physical_checking_process.php';
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

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
        paginationSize: 5,
        paginationSizeSelector: [5, 10, 20, 50, true],
        columns: [
            { title: 'Series Code', field: 'series_code', width: 160, formatter: function(cell){
                return `<span class="badge bg-light text-dark border badge-code">${cell.getValue()}</span>`;
            }},
            { title: 'Audit Name', field: 'audit_name', widthGrow: 2 },
            { title: 'Start', field: 'start_date', width: 120 },
            { title: 'End', field: 'end_date', width: 120 },
            { title: 'Status', field: 'status', width: 100 },
            { title: 'Created By', field: 'created_by_name', width: 160 },
            { title: 'Actions', field: 'id', width: 160, formatter: function(cell){
                const id = cell.getValue();
                return `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-success btn-set-status" data-id="${id}" data-status="Active">Active</button>
                        <button class="btn btn-outline-secondary btn-set-status" data-id="${id}" data-status="Closed">Close</button>
                    </div>`;
            }}
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
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        ajaxResponse: function(url, params, response) {
            return response.data || [];
        },
        columns: [
            { title: 'Series', field: 'series_code', width: 140 },
            { title: 'Property Code', field: 'property_code', width: 150 },
            { title: 'Item Description', field: 'item_description', widthGrow: 2 },
            { title: 'Serial No.', field: 'serial_number', width: 130 },
            { title: 'Facility', field: 'facility', width: 140 },
            { title: 'Accountable', field: 'accountable', width: 140 },
            { title: 'Status', field: 'status_at_check', width: 120 },
            { title: 'Remarks', field: 'remarks', width: 160 },
            { title: 'Date Checked', field: 'checked_at', width: 150 },
            { title: 'Checked By', field: 'checked_by_name', width: 150 }
        ]
    });
}

function loadItem(code) {
    code = (code || '').trim();
    if (!code) {
        resetItemUI();
        return;
    }
    $.post(PROCESS_URL, { action: 'get_item_by_code', property_code: code }, function(res) {
        if (!res.success) {
            resetItemUI();
            showErrorToast(res.message || 'Item not found.');
            return;
        }
        currentItem = res.data;
        $('#infoPropertyCode').text(currentItem.property_code || '-');
        $('#infoPropertyNumber').text(currentItem.property_number || '-');
        $('#infoDescription').text(currentItem.item_description || '-');
        $('#infoDateStock').text(currentItem.created_at || '-');
        $('#infoDateIssued').text('-');
        $('#unitChecked').val(currentItem.unit || '');
        $('#serialNumber').val(currentItem.serial_number || '');

        if (currentItem.category_photo_thumb_url || currentItem.category_photo_url) {
            const src = currentItem.category_photo_thumb_url || currentItem.category_photo_url;
            $('#itemCategoryImg').attr('src', src).removeClass('d-none');
            $('#itemNoImg').hide();
        } else {
            $('#itemCategoryImg').addClass('d-none');
            $('#itemNoImg').show();
        }
    }, 'json');
}

function resetItemUI() {
    currentItem = null;
    $('#infoPropertyCode').text('-');
    $('#infoPropertyNumber').text('-');
    $('#infoDescription').text('-');
    $('#infoDateStock').text('-');
    $('#infoDateIssued').text('-');
    $('#unitChecked').val('');
    $('#serialNumber').val('');
    $('#qtyChecked').val(1);
    $('#statusAtCheck').val('');
    $('#facility').val('');
    $('#accountable').val('');
    $('#issuedTo').val('');
    $('#managedBy').val('');
    $('#remarks').val('');
    $('#dateIssued').val('');
    $('#condition').val('');
    $('#itemCategoryImg').addClass('d-none').attr('src', '');
    $('#itemNoImg').show();
}

$(document).ready(function() {
    initSessionsTable();
    initChecksTable();
    loadSessions();

    if (typeof initQrSearch === 'function') {
        initQrSearch({
            searchInput: '#propertyCodeInput',
            onSearch: function (decodedText) {
                if (!decodedText) return;
                const code = String(decodedText).trim();
                $('#propertyCodeInput').val(code);
                resetItemUI();
                loadItem(code);
                $('#searchQrModal').modal('hide');
            }
        });
    }

    // Auto-start camera when QR modal opens
    $('#searchQrModal').on('shown.bs.modal', function() {
        $('#searchBtnStart').trigger('click');
    });

    $('#btnLoadItem').on('click', function(e) {
        e.preventDefault();
        resetItemUI();
        loadItem($('#propertyCodeInput').val().trim());
    });
    $('#propertyCodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            resetItemUI();
            loadItem($('#propertyCodeInput').val().trim());
        }
    });
    $('#propertyCodeInput').on('input', function() {
        const val = $(this).val().trim();
        if (!val || (currentItem && currentItem.property_code !== val)) {
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
        $.post(PROCESS_URL, { action: 'update_session_status', session_id: id, status }, function(res) {
            if (res.success) {
                loadSessions();
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
        if (!currentItem || !currentItem.property_code) {
            showMessage('#checkMsg', 'danger', 'Load an item first.');
            return;
        }
        $('#qtyChecked').val(1);
        const payload = {
            action: 'create_check',
            session_id: sessionId,
            property_code: currentItem.property_code,
            serial_number: ($('#serialNumber').val().trim() || (currentItem.serial_number || '')),
            quantity_checked: 1,
            condition: $('#condition').val(),
            remarks: $('#remarks').val().trim(),
            status_at_check: $('#statusAtCheck').val().trim(),
            facility: $('#facility').val().trim(),
            accountable: $('#accountable').val().trim(),
            issued_to: $('#issuedTo').val().trim(),
            managed_by: $('#managedBy').val().trim(),
            date_issued: $('#dateIssued').val()
        };
        $.post(PROCESS_URL, payload, function(res) {
            if (res.success) {
                showMessage('#checkMsg', 'success', res.message || 'Check saved.');
                if (checksTable) {
                    checksTable.setData(PROCESS_URL, { action: 'list_checks', session_id: $('#filterSession').val() }, 'POST');
                }
            } else {
                showMessage('#checkMsg', 'danger', res.message || 'Failed to save check.');
            }
        }, 'json');
    });

    $('#filterSession').on('change', function() {
        const sessionId = $(this).val();
        checksTable.setData(PROCESS_URL, { action: 'list_checks', session_id: sessionId, search: $('#checkSearch').val().trim() }, 'POST');
    });
    $('#checkSearch').on('keyup', function() {
        const sessionId = $('#filterSession').val();
        checksTable.setData(PROCESS_URL, { action: 'list_checks', session_id: sessionId, search: $('#checkSearch').val().trim() }, 'POST');
    });
});
</script>
</body>
</html>
