<?php
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
        .table-wrap { max-height: 320px; overflow: auto; border: 1px solid #dee2e6; border-radius: 8px; }
        #astIssueTable { min-height: 420px; }
        .item-thumb { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; }
        .qr-hit { box-shadow: inset 0 0 0 2px #0d6efd; }
        .select2-container { width: 100% !important; }
        .manager-box { min-height: 38px; background: #f8f9fa; }
        #selectedItemsBody td { vertical-align: middle; }
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
        <h1 class="h4 fw-semibold mb-1">AST Issuance</h1>
        <p class="text-muted small mb-0">Item-first issuance for non-consumable inventory with single or batch assignment.</p>
    </div>

    <section class="section">
        <div id="pageMsg" class="alert alert-danger d-none mb-3"></div>
        <div class="row g-3">
            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i>&ensp;Select AST Items</span>
                        <button type="button" class="btn btn-light btn-sm" id="refreshItemsBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-lg-7">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="astIssueSearch" placeholder="Search property tag/number/description/serial...">
                                    <button class="btn btn-outline-secondary" type="button" id="openIssueSearchScanner" title="Scan QR">
                                        <i class="bi bi-qr-code-scan"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-lg-5 d-flex align-items-center justify-content-lg-end">
                                <small id="selectedCountNote" class="text-muted">No item selected.</small>
                            </div>
                        </div>
                        <div id="astIssueTable"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-check"></i>&ensp;Selected Items Review</span>
                        <button type="button" class="btn btn-light btn-sm" id="clearSelectionBtn">Clear Selection</button>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="table-wrap">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 130px;">Property Tag</th>
                                        <th style="width: 100px;">Property No.</th>
                                        <th style="width: 120px;">Category</th>
                                        <th>Description</th>
                                        <th style="width: 120px;">Serial No.</th>
                                        <th style="width: 160px;">Current Location</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedItemsBody">
                                    <tr>
                                        <td colspan="7" class="text-muted">No selected items yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold">
                        <span><i class="bi bi-send-check"></i>&ensp;Batch Assignment</span>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <form id="issueBatchForm">
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label fw-semibold">Facility / Unit</label>
                                    <select class="form-select" id="issueFacilityUnit" required>
                                        <option value="">Select facility unit</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Unit Managers</label>
                                    <input type="text" id="unitManagersDisplay" class="form-control manager-box" value="-" readonly>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Issued To</label>
                                    <select class="form-select" id="issuedToUserId" required></select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Extra Managers (optional)</label>
                                    <select class="form-select" id="extraManagerUserIds" multiple></select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Remarks (optional)</label>
                                    <input type="text" class="form-control" id="issueRemarks" maxlength="255">
                                </div>
                            </div>
                            <div id="issueMsg" class="mt-3"></div>
                            <div class="mt-3 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="issueSubmitBtn"><i class="bi bi-check2-circle"></i> Issue Selected Items</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- QR SEARCH MODAL -->
<div class="modal fade" id="issueSearchQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Select AST Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="issueSearchCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="issueSearchBtnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="issueSearchBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div style="width:100%;max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1;">
                    <div id="issueSearchPreview" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
                    <div id="issueSearchScannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
                        <div>Initializing camera...</div>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="issueSearchLastScanned" class="fw-semibold">-</span>
                </div>
                <div id="issueSearchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<?php include_once FOOTER_PATH; ?>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script>
const PROCESS_URL = '<?= BASE_URL ?>admin/modules/nonconsumable/process/ast_issuance_process.php';
let astIssueTable = null;
let astIssueTableReady = false;
let pendingLoadAfterBuild = false;
let pendingSyncAfterBuild = false;
let pendingScanCode = '';
let availableRowsById = {};
let selectedUnitMeta = null;
let facilityLookup = {};

function flushTableReadyQueue() {
    if (!astIssueTableReady) return;
    if (pendingLoadAfterBuild) {
        pendingLoadAfterBuild = false;
        loadAvailableItems();
    }
    if (pendingSyncAfterBuild) {
        pendingSyncAfterBuild = false;
        syncSelectedItemsFromTable();
    }
    if (pendingScanCode) {
        const code = pendingScanCode;
        pendingScanCode = '';
        selectByItemCode(code);
    }
}

function escapeHtml(v){
    return String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showPageError(msg) {
    const $el = $('#pageMsg');
    if (!msg) {
        $el.addClass('d-none').text('');
        return;
    }
    $el.removeClass('d-none').text(msg);
}

function showIssueMsg(type, message) {
    $('#issueMsg').html('<div class="alert alert-' + type + ' mb-0">' + escapeHtml(message) + '</div>');
}

function renderThumb(row) {
    const thumb = row.category_photo_thumb_url || row.category_photo_url || '';
    if (!thumb) {
        return '<span class="text-muted">-</span>';
    }
    return '<img class="item-thumb" src="' + escapeHtml(thumb) + '" alt="Category">';
}

function indexRows(rows) {
    availableRowsById = {};
    (rows || []).forEach(function (r) {
        const id = Number(r.source_item_id || 0);
        if (id > 0) availableRowsById[id] = r;
    });
}

function initItemsTable() {
    astIssueTableReady = false;
    astIssueTable = new Tabulator('#astIssueTable', {
        index: 'source_item_id',
        height: 420,
        layout: 'fitColumns',
        selectable: true,
        placeholder: 'No available AST item found.',
        columns: [
            {
                formatter: 'rowSelection',
                titleFormatter: 'rowSelection',
                hozAlign: 'center',
                headerSort: false,
                width: 44,
                cellClick: function(e, cell) {
                    cell.getRow().toggleSelect();
                }
            },
            { title: 'Image', field: 'category_photo_thumb_url', hozAlign: 'center', width: 70, formatter: function(cell){ return renderThumb(cell.getRow().getData()); } },
            { title: 'Property Tag', field: 'item_code', width: 160 },
            { title: 'Property No.', field: 'property_number', width: 120 },
            { title: 'Category', field: 'item_category_name', width: 140 },
            { title: 'Description', field: 'item_description', formatter: function(cell){ return '<div class="two-line-cell">' + escapeHtml(cell.getValue() || '') + '</div>'; } },
            { title: 'Serial No.', field: 'serial_number', width: 140 },
            { title: 'Current Location', field: 'current_location', width: 170 }
        ],
        rowSelectionChanged: function(data) {
            renderSelectedItems(data || []);
        },
        rowSelected: function() {
            renderSelectedItems(astIssueTable ? astIssueTable.getSelectedData() : []);
        },
        rowDeselected: function() {
            renderSelectedItems(astIssueTable ? astIssueTable.getSelectedData() : []);
        }
    });

    // Primary readiness hook (works across Tabulator builds that expose event API).
    if (astIssueTable && typeof astIssueTable.on === 'function') {
        astIssueTable.on('tableBuilt', function() {
            astIssueTableReady = true;
            flushTableReadyQueue();
        });
    }
    // Fallback readiness in case the event is not emitted in this build.
    setTimeout(function() {
        if (!astIssueTableReady) {
            astIssueTableReady = true;
            flushTableReadyQueue();
        }
    }, 80);
}

function loadAvailableItems() {
    if (!astIssueTable || !astIssueTableReady) {
        pendingLoadAfterBuild = true;
        return;
    }
    showPageError('');
    const selectedIds = astIssueTable.getSelectedData().map(r => Number(r.source_item_id || 0));
    $.post(PROCESS_URL, { action: 'list_available_ast_items' }, function(res){
        if (!res || !res.success) {
            showPageError(res && res.message ? res.message : 'Failed to load available AST items.');
            return;
        }
        const rows = Array.isArray(res.data) ? res.data : [];
        indexRows(rows);
        astIssueTable.setData(rows).then(function(){
            const validIds = selectedIds.filter(id => !!availableRowsById[id]);
            if (validIds.length > 0) astIssueTable.selectRow(validIds);
            applyTableSearch($('#astIssueSearch').val() || '');
        });
    }, 'json').fail(function(){
        showPageError('Server error while loading available AST items.');
    });
}

function applyTableSearch(term) {
    if (!astIssueTable || !astIssueTableReady) return;
    const q = String(term || '').trim().toLowerCase();
    if (!q) {
        astIssueTable.clearFilter(true);
        return;
    }
    astIssueTable.setFilter(function(rowData){
        const hay = [
            rowData.item_code,
            rowData.property_number,
            rowData.item_description,
            rowData.serial_number,
            rowData.item_category_name,
            rowData.current_location
        ].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    });
}

function renderSelectedItems(items) {
    const rows = Array.isArray(items) ? items : [];
    const $body = $('#selectedItemsBody');
    if (rows.length === 0) {
        $('#selectedCountNote').text('No item selected.');
        $body.html('<tr><td colspan="7" class="text-muted">No selected items yet.</td></tr>');
        return;
    }

    $('#selectedCountNote').text(rows.length + ' item(s) selected.');
    const html = rows.map(function(r){
        return '<tr>' +
            '<td><span class="fw-semibold">' + escapeHtml(r.item_code || '-') + '</span></td>' +
            '<td>' + escapeHtml(r.property_number || '-') + '</td>' +
            '<td>' + escapeHtml(r.item_category_name || '-') + '</td>' +
            '<td><div class="two-line-cell">' + escapeHtml(r.item_description || '-') + '</div></td>' +
            '<td>' + escapeHtml(r.serial_number || '-') + '</td>' +
            '<td>' + escapeHtml(r.current_location || '-') + '</td>' +
            '<td><button type="button" class="btn btn-outline-danger btn-sm js-remove-selected" data-id="' + Number(r.source_item_id || 0) + '"><i class="bi bi-x-lg"></i></button></td>' +
            '</tr>';
    }).join('');
    $body.html(html);
}

function loadFacilityUnitOptions() {
    selectedUnitMeta = null;
    facilityLookup = {};
    $('#issueFacilityUnit').html('<option value="">Select facility unit</option>');
    $('#unitManagersDisplay').val('-');

    $.post(PROCESS_URL, { action: 'list_facilities' }, function(res){
        if (!res || !res.success) {
            showPageError(res && res.message ? res.message : 'Failed to load facilities.');
            return;
        }
        const facilities = Array.isArray(res.data) ? res.data : [];
        const regularFacilities = facilities.filter(function(f){
            return Number(f.is_stockroom || 0) !== 1;
        });
        if (regularFacilities.length === 0) {
            showIssueMsg('warning', 'No regular facilities available for AST issuance.');
            return;
        }
        const opts = ['<option value="">Select facility unit</option>'];
        let hasUnits = false;
        let idx = 0;

        function processNextFacility() {
            if (idx >= regularFacilities.length) {
                $('#issueFacilityUnit').html(opts.join(''));
                if (!hasUnits) {
                    showIssueMsg('warning', 'No active units found under regular facilities.');
                } else {
                    $('#issueMsg').html('');
                }
                return;
            }

            const facility = regularFacilities[idx++];
            const facilityId = Number(facility.facility_id || 0);
            const facilityName = String(facility.facility_name || '-');
            facilityLookup[facilityId] = facilityName;

            $.post(PROCESS_URL, { action: 'list_units', facility_id: facilityId }, function(unitRes){
                const units = (unitRes && unitRes.success && Array.isArray(unitRes.data)) ? unitRes.data : [];
                units.forEach(function(u){
                    hasUnits = true;
                    const unitId = Number(u.unit_id || 0);
                    const managedBy = Number(u.managed_by_user_id || 0);
                    const managerNames = String(u.manager_names || '').trim();
                    const unitName = String(u.unit_name || '-');
                    const value = facilityId + '::' + unitId;
                    const label = facilityName + ' - ' + unitName;
                    opts.push(
                        '<option value="' + escapeHtml(value) + '"' +
                        ' data-facility-id="' + facilityId + '"' +
                        ' data-unit-id="' + unitId + '"' +
                        ' data-managed-by="' + managedBy + '"' +
                        ' data-manager-names="' + escapeHtml(managerNames) + '">' +
                        escapeHtml(label) +
                        '</option>'
                    );
                });
                processNextFacility();
            }, 'json').fail(function(){
                processNextFacility();
            });
        }

        processNextFacility();
    }, 'json').fail(function(){
        showPageError('Server error while loading facilities.');
    });
}

function syncSelectedItemsFromTable() {
    if (!astIssueTable || !astIssueTableReady) {
        pendingSyncAfterBuild = true;
        return;
    }
    renderSelectedItems(astIssueTable.getSelectedData() || []);
}

function initUserSelects() {
    function buildUserSelect($el, multiple) {
        $el.select2({
            width: '100%',
            placeholder: multiple ? 'Search users...' : 'Select user',
            allowClear: !multiple,
            multiple: !!multiple,
            ajax: {
                url: PROCESS_URL,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'list_users',
                        search: params.term || '',
                        limit: 100
                    };
                },
                processResults: function(res) {
                    const data = (res && res.success && Array.isArray(res.data)) ? res.data : [];
                    return {
                        results: data.map(function(u){
                            const name = u.full_name || u.username || ('User #' + u.user_id);
                            const pos = u.position ? ' - ' + u.position : '';
                            return { id: String(u.user_id), text: name + pos };
                        })
                    };
                }
            }
        });
    }

    buildUserSelect($('#issuedToUserId'), false);
    buildUserSelect($('#extraManagerUserIds'), true);
}

function normalizeItemCode(raw) {
    return String(raw || '').trim().toUpperCase();
}

function highlightSelectedRow(rowId) {
    if (!astIssueTable || !astIssueTableReady) return;
    const row = astIssueTable.getRow(rowId);
    if (!row) return;
    const el = row.getElement();
    if (!el) return;
    el.classList.add('qr-hit');
    setTimeout(function(){ el.classList.remove('qr-hit'); }, 1400);
}

function selectByItemCode(itemCode) {
    const code = normalizeItemCode(itemCode);
    if (!code) return;
    if (!astIssueTable || !astIssueTableReady) {
        pendingScanCode = code;
        return;
    }

    $.post(PROCESS_URL, { action: 'search_ast_item_by_code', item_code: code }, function(res){
        if (!res || !res.success || !res.data) {
            showIssueMsg('warning', (res && res.message) ? res.message : 'No issuable item found for scanned code.');
            return;
        }

        const row = res.data;
        const id = Number(row.source_item_id || 0);
        if (id <= 0) {
            showIssueMsg('warning', 'Invalid item payload from server.');
            return;
        }

        if (!availableRowsById[id]) {
            availableRowsById[id] = row;
            astIssueTable.addData([row], true);
        }

        astIssueTable.selectRow(id);
        astIssueTable.scrollToRow(id, 'center', true).catch(function(){});
        highlightSelectedRow(id);
        showIssueMsg('success', 'Item selected: ' + (row.item_code || code));
    }, 'json').fail(function(){
        showIssueMsg('danger', 'Server error while searching scanned code.');
    });
}

function collectSelectedItemsPayload() {
    if (!astIssueTable || !astIssueTableReady) return [];
    const rows = astIssueTable.getSelectedData();
    return (rows || []).map(function(r){
        return {
            source_item_id: Number(r.source_item_id || 0),
            item_code: String(r.item_code || '').trim().toUpperCase()
        };
    }).filter(function(r){ return r.source_item_id > 0 && r.item_code !== ''; });
}

$(document).ready(function(){
    initItemsTable();
    initUserSelects();
    loadFacilityUnitOptions();
    loadAvailableItems();
    syncSelectedItemsFromTable();

    $('#refreshItemsBtn').on('click', function(){
        loadAvailableItems();
    });

    $('#clearSelectionBtn').on('click', function(){
        if (!astIssueTable) return;
        astIssueTable.deselectRow();
        syncSelectedItemsFromTable();
    });

    $('#astIssueSearch').on('input', function(){
        applyTableSearch($(this).val() || '');
    });

    $('#selectedItemsBody').on('click', '.js-remove-selected', function(){
        const rowId = Number($(this).data('id') || 0);
        if (rowId > 0 && astIssueTable) {
            astIssueTable.deselectRow(rowId);
            syncSelectedItemsFromTable();
        }
    });

    $('#issueFacilityUnit').on('change', function(){
        const $opt = $(this).find('option:selected');
        selectedUnitMeta = {
            facility_id: Number($opt.data('facility-id') || 0),
            unit_id: Number($opt.data('unit-id') || 0),
            managed_by_user_id: Number($opt.data('managed-by') || 0),
            manager_names: String($opt.attr('data-manager-names') || '').trim()
        };
        if ((selectedUnitMeta.facility_id || 0) <= 0 || (selectedUnitMeta.unit_id || 0) <= 0) {
            selectedUnitMeta = null;
            $('#unitManagersDisplay').val('-');
            return;
        }
        $('#unitManagersDisplay').val(selectedUnitMeta.manager_names || '-');
    });

    $('#issueBatchForm').on('submit', function(e){
        e.preventDefault();
        $('#issueMsg').html('');

        const items = collectSelectedItemsPayload();
        const facilityId = Number((selectedUnitMeta && selectedUnitMeta.facility_id) || 0);
        const unitId = Number((selectedUnitMeta && selectedUnitMeta.unit_id) || 0);
        const issuedTo = String($('#issuedToUserId').val() || '').trim();
        const extraManagers = $('#extraManagerUserIds').val() || [];
        const remarks = ($('#issueRemarks').val() || '').trim();

        if (items.length === 0) {
            showIssueMsg('danger', 'Select at least one item before issuing.');
            return;
        }
        if (facilityId <= 0 || unitId <= 0) {
            showIssueMsg('danger', 'Facility / Unit is required.');
            return;
        }
        if (!issuedTo) {
            showIssueMsg('danger', 'Issued To is required.');
            return;
        }

        const $btn = $('#issueSubmitBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

        $.post(PROCESS_URL, {
            action: 'issue_ast_items_batch',
            facility_id: facilityId,
            unit_id: unitId,
            issued_to_user_id: issuedTo,
            remarks: remarks,
            selected_items: JSON.stringify(items),
            extra_manager_user_ids: JSON.stringify(extraManagers)
        }, function(res){
            if (res && res.success) {
                showIssueMsg('success', res.message || 'Items issued successfully.');
                if (astIssueTable) astIssueTable.deselectRow();
                $('#issueBatchForm')[0].reset();
                $('#issueFacilityUnit').val('');
                $('#unitManagersDisplay').val('-');
                $('#issuedToUserId').val(null).trigger('change');
                $('#extraManagerUserIds').val(null).trigger('change');
                selectedUnitMeta = null;
                syncSelectedItemsFromTable();
                loadAvailableItems();
            } else {
                showIssueMsg('danger', (res && res.message) ? res.message : 'Batch issue failed.');
                loadAvailableItems();
            }
        }, 'json').fail(function(){
            showIssueMsg('danger', 'Server error while issuing items.');
        }).always(function(){
            $btn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Issue Selected Items');
        });
    });

    if (typeof initQrSearch === 'function') {
        initQrSearch({
            modalId: '#issueSearchQrModal',
            openButton: '#openIssueSearchScanner',
            searchInput: '#astIssueSearch',
            onSearch: function(decodedText) {
                const code = normalizeItemCode(decodedText);
                if (!code) return;
                $('#astIssueSearch').val(code);
                applyTableSearch(code);
                selectByItemCode(code);
            },
            cameraSelectId: '#issueSearchCameraSelect',
            startBtnId: '#issueSearchBtnStart',
            stopBtnId: '#issueSearchBtnStop',
            previewId: '#issueSearchPreview',
            lastScannedId: '#issueSearchLastScanned',
            errorId: '#issueSearchScanError',
            loadingId: '#issueSearchScannerLoading'
        });
    }
});
</script>
</html>
