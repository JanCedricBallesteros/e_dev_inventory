<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("PO", "AST", "CSM"));
if (!(role_has("ADMIN") || $staffAccess)) {
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
        .facility-group { margin-bottom: 1rem; }
        .facility-header { background: #0d6efd; color: white; padding: 10px 14px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: background 0.2s ease; display: flex; gap: 10px; align-items: flex-start; }
        .facility-header:hover { background: #0b5ed7; }
        .facility-header.active { background: #0a58ca; box-shadow: 0 2px 6px rgba(13,110,253,0.3); }
        .facility-header i { margin-right: 6px; }
        .facility-header .facility-meta { flex: 1 1 auto; min-width: 0; }
        .facility-header .facility-meta .title-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .facility-header .facility-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .facility-code { font-family: monospace; font-weight: 700; }
        .small-muted { color: #6c757d; font-size: 0.85rem; }
        .unit-card { border: 1px solid #dbe2ea; border-radius: 8px; padding: 10px 12px; background: #fff; cursor: pointer; transition: all 0.2s ease; margin-bottom: 8px; }
        .unit-card:hover { border-color: #a5b4fc; background: #f8faff; transform: translateX(4px); }
        .unit-card.active { border-color: #0d6efd; background: #eef5ff; box-shadow: 0 2px 6px rgba(13,110,253,0.2); }
        .select2-container { width: 100% !important; }
        .assign-search-wrap { display: flex; align-items: stretch; gap: .5rem; }
        .assign-search-wrap .search-select-holder { flex: 1 1 auto; min-width: 0; }
        .assign-search-wrap .btn { flex: 0 0 auto; }
        .facility-items { border: 1px dashed #d0d7de; border-radius: 8px; padding: 10px 12px; background: #f8fafc; }
        .facility-item-row { display: grid; grid-template-columns: auto 1fr; gap: 4px 8px; padding: 6px 0; border-bottom: 1px solid #e5e7eb; }
        .facility-item-row:last-child { border-bottom: none; }
        .facility-item-main { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .facility-item-desc { grid-column: 1 / -1; color: #6c757d; font-size: 0.9rem; }
        .facility-item-meta { display: flex; flex-wrap: wrap; gap: 6px; color: #6c757d; font-size: 0.85rem; }
        .item-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
        .item-badge { width: 46px; height: 46px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #1E3A8A; color: #fff; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; border: 1px solid rgba(0,0,0,0.06); cursor: default; }
        .thumb-wrap { display: flex; align-items: center; justify-content: center; }
        .img-preview { max-width: 100%; max-height: 70vh; border-radius: 8px; }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">Facility Inventory Records</h1>
        <p class="text-muted small mb-0">Manage facilities, units, and assigned consumable/non-consumable inventory.</p>
    </div>

    <section class="section">
        <div id="pageMsg" class="alert alert-danger d-none"></div>
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="card section-card h-100">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-building"></i>&ensp;Facilities & Units</span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" id="btnAddFacility"><i class="bi bi-plus-lg"></i> Add Facility</button>
                        </div>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div id="facilityList"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i>&ensp;Unit Inventory Assignments</span>
                        <button class="btn btn-light btn-sm" id="btnAssignItem" disabled><i class="bi bi-plus-lg"></i> Assign Item</button>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <input type="text" class="form-control" id="invSearch" placeholder="Search code/description/status...">
                            <button class="btn btn-outline-secondary" id="btnRefreshAssignments">Refresh</button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div id="selectedUnitInfo" class="small text-muted">Select a facility or unit to view items.</div>
                            <div id="selectedManagedBy" class="small text-muted"></div>
                        </div>
                        <div id="assignmentTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<!-- Facility Modal -->
<div class="modal fade" id="facilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="facilityId">
                <div class="mb-2">
                    <label class="form-label fw-semibold">Facility Code</label>
                    <input type="text" id="facilityCode" class="form-control" placeholder="e.g., SCI-BLDG">
                </div>
                <div>
                    <label class="form-label fw-semibold">Facility Name</label>
                    <input type="text" id="facilityName" class="form-control" placeholder="e.g., Science Building">
                </div>
                <div id="facilityMsg" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary btn-sm" id="saveFacilityBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Unit Modal -->
<div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Facility Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="unitId">
                <div class="mb-2">
                    <label class="form-label fw-semibold">Unit Type</label>
                    <select id="unitType" class="form-select">
                        <option value="ROOM">ROOM</option>
                        <option value="OFFICE">OFFICE</option>
                        <option value="LABORATORY">LABORATORY</option>
                        <option value="OTHER">OTHER</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Unit Code</label>
                    <input type="text" id="unitCode" class="form-control" placeholder="e.g., RM-101">
                </div>
                <div>
                    <label class="form-label fw-semibold">Unit Name</label>
                    <input type="text" id="unitName" class="form-control" placeholder="e.g., Room 101">
                </div>
                <div class="mt-2">
                    <label class="form-label fw-semibold">Facility Unit Manager</label>
                    <select id="unitManagerUserId" class="form-select"></select>
                </div>
                <div id="unitMsg" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary btn-sm" id="saveUnitBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Item to Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Module</label>
                        <select id="assignModule" class="form-select">
                            <option value="AST">AST</option>
                            <option value="CSM">CSM</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Search</label>
                        <div class="assign-search-wrap">
                            <div class="search-select-holder">
                                <select id="assignSearchSelect" class="form-select"></select>
                            </div>
                            <button class="btn btn-outline-secondary" type="button" id="openAssignSearchScanner" title="Scan QR">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                        </div>
                        <input type="hidden" id="assignSearch">
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Selected Item</label>
                        <input type="text" id="assignSelected" class="form-control" readonly>
                        <input type="hidden" id="assignSourceItemId">
                        <input type="hidden" id="assignItemCode">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Qty</label>
                        <input type="number" id="assignQty" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" id="assignRemarks" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Issued To</label>
                        <select id="assignIssuedToUserId" class="form-select"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Managed By (Facility Unit Manager)</label>
                        <input type="text" id="assignManagedByName" class="form-control" readonly>
                        <input type="hidden" id="assignManagedByUserId">
                    </div>
                </div>
                <div id="assignMsg" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary btn-sm" id="confirmAssignBtn">Assign</button>
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

<!-- SEARCH QR MODAL -->
<div class="modal fade" id="assignSearchQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Search Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="assignSearchCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="assignSearchBtnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="assignSearchBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div style="width:100%;max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1;">
                    <div id="assignSearchPreview" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
                    <div id="assignSearchScannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
                        <div>Initializing camera...</div>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="assignSearchLastScanned" class="fw-semibold">-</span>
                </div>
                <div id="assignSearchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script>
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'admin/modules/transactions/facility_inventory_records_process.php'); ?>;
let facilityList = [];
let unitList = [];
let assignmentList = [];
let facilityItems = [];
let selectedFacility = null;
let selectedUnit = null;
let assignmentTable = null;
let assignItemsCache = [];
let assignItemSearchMap = {};

function showPageError(msg){
    const el = $('#pageMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function notifySuccess(msg){
    if (typeof success_notif === 'function') return success_notif(msg);
    if ($.notify) return $.notify({ message: msg }, { type: 'success', delay: 2500, placement: { from: 'top', align: 'right' } });
    alert(msg);
}

function notifyError(msg){
    if (typeof error_notif === 'function') return error_notif(msg);
    if ($.notify) return $.notify({ message: msg }, { type: 'danger', delay: 3000, placement: { from: 'top', align: 'right' } });
    alert(msg);
}

function escapeHtml(value){
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeScannedCode(raw){
    let text = String(raw || '').trim();
    if (!text) return '';

    try {
        const parsed = JSON.parse(text);
        if (parsed && typeof parsed === 'object') {
            if (parsed.item_code) return String(parsed.item_code).trim();
            if (parsed.property_code) return String(parsed.property_code).trim();
            if (parsed.code) return String(parsed.code).trim();
        }
    } catch (e) {}

    // If QR contains URL, try common query params.
    if (/^https?:\/\//i.test(text)) {
        try {
            const u = new URL(text);
            const qp = u.searchParams.get('item_code') || u.searchParams.get('property_code') || u.searchParams.get('code') || '';
            if (qp) return qp.trim();
            const seg = u.pathname.split('/').filter(Boolean).pop();
            if (seg) return decodeURIComponent(seg).trim();
        } catch (e) {}
    }

    return text.replace(/\s+/g, ' ').trim();
}

function loadFacilities(){
    $.post(PROCESS_URL, { action: 'list_facilities' }, function(res){
        if (!res.success) { showPageError(res.message || 'Failed to load facilities.'); return; }
        facilityList = res.data || [];
        renderFacilities();
    }, 'json').fail(function(xhr){
        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Server error loading facilities.';
        showPageError(msg);
    });
}

function renderFacilities(){
    const wrap = $('#facilityList');
    wrap.empty();
    if (!facilityList.length){
        wrap.html('<div class="small-muted">No facilities yet.</div>');
        return;
    }
    facilityList.forEach(function(f){
        const isActive = selectedFacility && String(selectedFacility.facility_id) === String(f.facility_id);
        const facilityCode = escapeHtml(f.facility_code || '');
        const facilityName = escapeHtml(f.facility_name || '');
        const totalItems = parseInt(f.active_item_count || 0, 10) || 0;
        const totalUnits = parseInt(f.unit_count || 0, 10) || 0;
        wrap.append(`
            <div class="facility-group">
                <div class="facility-header ${isActive ? 'active' : ''}" data-id="${f.facility_id}">
                    <div class="facility-meta">
                        <div class="title-row">
                            <i class="bi bi-building"></i>
                            <span>${facilityName}</span>
                            <span class="badge bg-light text-dark facility-code">${facilityCode}</span>
                        </div>
                        <div class="small" style="opacity:0.9;">${totalUnits} unit(s) &middot; ${totalItems} item(s)</div>
                    </div>
                    <div class="facility-actions">
                        <button class="btn btn-light btn-sm btn-add-unit" data-id="${f.facility_id}"><i class="bi bi-plus-lg"></i> Add Unit</button>
                        <button class="btn btn-outline-light btn-sm btn-edit-facility" data-id="${f.facility_id}"><i class="bi bi-pencil"></i></button>
                    </div>
                </div>
                <div class="facility-units mt-2" data-facility-id="${f.facility_id}">
                    ${isActive ? '<div class="small-muted">Loading units...</div>' : '<div class="small-muted">Select to view units.</div>'}
                </div>
            </div>
        `);
    });
}

function loadUnits(){
    if (!selectedFacility) return;
    $.post(PROCESS_URL, { action: 'list_units', facility_id: selectedFacility.facility_id }, function(res){
        if (!res.success) { showPageError(res.message || 'Failed to load units.'); return; }
        unitList = res.data || [];
        renderUnits();
    }, 'json');
}

function formatUserOption(u){
    const roleMap = { '1': 'SUPER ADMIN', '2': 'ADMIN', '3': 'ADMIN STAFF', '4': 'USER' };
    const roleId = u.role_id != null ? String(u.role_id) : '';
    const role = roleMap[roleId] ? ` [${roleMap[roleId]}]` : '';
    const email = u.email ? ` - ${u.email}` : '';
    return `${u.full_name || ''}${email}${role}`;
}

function initUserSelect2(){
    if (!$.fn.select2) return;

    const commonAjax = function(permanentOnly){
        return {
            url: PROCESS_URL,
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function(params){
                return {
                    action: 'list_users',
                    permanent_only: permanentOnly ? 1 : 0,
                    search: params.term || ''
                };
            },
            processResults: function(res){
                if (!(res && res.success)) {
                    return { results: [] };
                }
                const items = (res.data || []).map(function(u){
                    return { id: String(u.user_id), text: formatUserOption(u) };
                });
                return { results: items };
            }
        };
    };

    if ($('#unitManagerUserId').hasClass('select2-hidden-accessible')) {
        $('#unitManagerUserId').select2('destroy');
    }
    $('#unitManagerUserId').select2({
        placeholder: 'Select facility unit manager',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#unitModal'),
        ajax: commonAjax(true)
    });

    if ($('#assignIssuedToUserId').hasClass('select2-hidden-accessible')) {
        $('#assignIssuedToUserId').select2('destroy');
    }
    $('#assignIssuedToUserId').select2({
        placeholder: 'Search user',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#assignModal'),
        ajax: commonAjax(false)
    });
}

function renderUnits(){
    if (!selectedFacility) return;
    const container = $(`.facility-units[data-facility-id="${selectedFacility.facility_id}"]`);
    if (!container.length) return;
    container.empty();
    if (!unitList.length){
        container.html('<div class="small-muted">No units yet for this facility.</div>');
        return;
    }
    unitList.forEach(function(u){
        const active = selectedUnit && String(selectedUnit.unit_id) === String(u.unit_id) ? 'active' : '';
        const officer = escapeHtml((u.unit_manager_name || '').trim());
        const unitCode = escapeHtml(u.unit_code || '');
        const unitName = escapeHtml(u.unit_name || '');
        container.append(`
            <div class="unit-card ${active}" data-id="${u.unit_id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">${unitCode} - ${unitName} (${u.active_item_count || 0})</div>
                    <button class="btn btn-outline-secondary btn-sm btn-edit-unit" data-id="${u.unit_id}" title="Edit Unit">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
                ${officer ? `<div class="small-muted mt-1">Manager: ${officer}</div>` : ''}
            </div>
        `);
    });
}

function loadFacilityItems(){
    if (!selectedFacility){
        facilityItems = [];
        if (assignmentTable) assignmentTable.setData([]);
        return;
    }
    facilityItems = [];
    if (assignmentTable) assignmentTable.setData([]);
    $.post(PROCESS_URL, { action: 'list_facility_inventory', facility_id: selectedFacility.facility_id }, function(res){
        if (!res.success) {
            showPageError(res.message || 'Failed to load items.');
            return;
        }
        facilityItems = res.data || [];
        renderAssignments();
    }, 'json').fail(function(){
        showPageError('Server error loading items.');
    });
}

function loadAssignments(){
    if (!selectedUnit){
        if (!selectedFacility && assignmentTable) assignmentTable.setData([]);
        return;
    }
    if (assignmentTable) assignmentTable.setData([]);
    $.post(PROCESS_URL, { action: 'list_unit_inventory', unit_id: selectedUnit.unit_id }, function(res){
        if (!res.success) { showPageError(res.message || 'Failed to load assignments.'); return; }
        assignmentList = res.data || [];
        renderAssignments();
    }, 'json');
}

function renderAssignments(){
    if (!assignmentTable) return;
    const sourceRows = selectedUnit ? assignmentList : (selectedFacility ? facilityItems : []);
    const q = ($('#invSearch').val() || '').toLowerCase().trim();
    assignmentTable.setData(sourceRows);
    if (q) {
        assignmentTable.setFilter(function(r){
            const hay = [r.module_type, r.item_code, r.item_description, r.status, r.issued_to_name, r.accountable_name, r.managed_by_name, r.unit_name, r.unit_code].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    } else {
        assignmentTable.clearFilter();
    }
}

function loadAssignableItems(){
    const search = ($('#assignSearch').val() || '').trim();
    if (!search) return;
    pickAssignableItemByCode(search);
}

function applyPickedItem(item){
    if (!item) return;
    $('#assignSourceItemId').val(item.source_item_id || '');
    $('#assignItemCode').val(item.item_code || '');
    $('#assignSelected').val((item.item_code || '') + ' - ' + (item.item_description || ''));
    if ($('#assignModule').val() === 'AST') {
        $('#assignQty').val(1).prop('readonly', true);
    } else {
        $('#assignQty').prop('readonly', false);
    }
    $('#assignMsg').html('');
}

function initAssignableItemSelect2(){
    if (!$.fn.select2) return;
    const $sel = $('#assignSearchSelect');
    if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
    }
    assignItemSearchMap = {};
    $sel.empty();
    $sel.select2({
        placeholder: 'Search item code/description',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#assignModal'),
        ajax: {
            url: PROCESS_URL,
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function(params){
                return {
                    action: 'list_available_items',
                    module_type: $('#assignModule').val(),
                    search: params.term || ''
                };
            },
            processResults: function(res){
                if (!(res && res.success)) return { results: [] };
                const results = (res.data || []).map(function(it){
                    const key = String(it.source_item_id) + '::' + String(it.item_code || '');
                    assignItemSearchMap[key] = it;
                    return { id: key, text: `${it.item_code || ''} - ${it.item_description || ''}` };
                });
                return { results: results };
            }
        }
    });

    $sel.off('select2:select').on('select2:select', function(e){
        const id = e.params && e.params.data ? e.params.data.id : '';
        const item = assignItemSearchMap[id] || null;
        applyPickedItem(item);
    });
    $sel.off('select2:clear').on('select2:clear', function(){
        $('#assignSourceItemId').val('');
        $('#assignItemCode').val('');
        $('#assignSelected').val('');
    });
}

function pickAssignableItemByCode(code){
    const q = normalizeScannedCode(code);
    if (!q) return;
    const selectedModule = $('#assignModule').val();
    let moduleType = selectedModule;
    const qUpper = q.toUpperCase();
    let forcedByPrefix = false;
    if (qUpper.startsWith('AST-')) { moduleType = 'AST'; forcedByPrefix = true; }
    if (qUpper.startsWith('CSM-')) { moduleType = 'CSM'; forcedByPrefix = true; }
    if ($('#assignModule').val() !== moduleType) {
        $('#assignModule').val(moduleType);
        initAssignableItemSelect2();
    }

    const runSearch = function(mod){
        $.post(PROCESS_URL, {
            action: 'list_available_items',
            module_type: mod,
            search: q
        }, function(res){
            if (!(res && res.success)) {
                $('#assignMsg').html('<div class="alert alert-danger mb-0">' + ((res && res.message) || 'Failed to search item.') + '</div>');
                return;
            }
            const rows = res.data || [];
            if (!rows.length) {
                return $.post(PROCESS_URL, {
                    action: 'diagnose_item_search',
                    module_type: mod,
                    search: q
                }, function(diag){
                    const msg = (diag && diag.message) ? diag.message : 'No matching item found for the selected module.';
                    $('#assignMsg').html('<div class="alert alert-warning mb-0">' + msg + '</div>');
                }, 'json').fail(function(){
                    $('#assignMsg').html('<div class="alert alert-warning mb-0">No matching item found for the selected module.</div>');
                });
            }
            const exact = rows.find(function(it){
                return String(it.item_code || '').toLowerCase() === q.toLowerCase();
            });
            const picked = exact || rows[0];
            const key = String(picked.source_item_id) + '::' + String(picked.item_code || '');
            assignItemSearchMap[key] = picked;
            const opt = new Option(`${picked.item_code || ''} - ${picked.item_description || ''}`, key, true, true);
            $('#assignSearchSelect').append(opt).trigger('change');
            applyPickedItem(picked);
            if (!exact && rows.length > 1) {
                $('#assignMsg').html('<div class="alert alert-warning mb-0">Multiple matches found. Picked first match. Refine search if needed.</div>');
            }
        }, 'json').fail(function(){
            $('#assignMsg').html('<div class="alert alert-danger mb-0">Server error while searching item.</div>');
        });
    };

    // Keep diagnostics aligned with the user's chosen module.
    // Only prefix-based module override is allowed to prevent misleading messages.
    if (!forcedByPrefix && selectedModule !== moduleType) {
        moduleType = selectedModule;
    }
    runSearch(moduleType);
}

function initAssignmentTable(){
    assignmentTable = new Tabulator('#assignmentTable', {
        layout: "fitColumns",
        renderVertical: "basic",
        responsiveLayout: "collapse",
        pagination: "local",
        paginationSize: 5,
        paginationSizeSelector: [5, 10, 20, 50, true],
        placeholder: "Select a facility or unit to view items.",
        columns: [
            { title: "Image", field: "category_photo_thumb_url", width: 60, hozAlign: "center", headerSort: false, formatter: function(cell){
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || cell.getRow().getData().module_type || '';
                if (url) {
                    return `<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="${url}" data-full="${full || url}" loading="lazy" alt="Item image"></div>`;
                }
                const initials = (String(name).trim().split(/\s+/).map(function(w){ return w.charAt(0); }).filter(Boolean).slice(0,2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge js-badge-preview" data-full="${full || ''}" title="${escapeHtml(name)}">${escapeHtml(initials)}</div></div>`;
            }},
            { title: "Item Code", field: "item_code", width: 130, formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? '<span class="badge bg-light text-dark border">' + v + '</span>' : '';
            }},
            { title: "Description", field: "item_description", widthGrow: 2, minWidth: 160, formatter: function(cell){
                const row = cell.getRow().getData();
                const desc = escapeHtml(row.item_description || '');
                const unitLabel = !selectedUnit && row.unit_name
                    ? '<div class="small text-muted">Unit: ' + escapeHtml(row.unit_name || '-') + (row.unit_code ? ' (' + escapeHtml(row.unit_code) + ')' : '') + '</div>'
                    : '';
                return desc + unitLabel;
            }},
            { title: "Qty", field: "qty", width: 60, hozAlign: "center" },
            { title: "Issued To", field: "issued_to_name", width: 150, formatter: function(cell){
                return escapeHtml(cell.getValue() || '-');
            }},
            { title: "Status", field: "status", width: 100 },
            { title: "Issued At", field: "issued_at", width: 130 },
            { title: "Actions", field: "assignment_id", width: 175, headerSort: false, formatter: function(cell){
                const id = cell.getValue();
                const row = cell.getRow().getData();
                const isReturned = String(row.status || '').toUpperCase() === 'RETURNED';
                return '<div class="d-flex gap-1"><button class="btn btn-outline-warning btn-sm btn-report" data-id="' + id + '">Report</button><button class="btn btn-outline-success btn-sm btn-return" data-id="' + id + '"' + (isReturned ? ' disabled' : '') + '>Return</button></div>';
            }}
        ]
    });
}

$(document).ready(function(){
    initAssignmentTable();
    initUserSelect2();
    initAssignableItemSelect2();
    loadFacilities();
    renderAssignments();

    $('#facilityList').on('click', '.facility-header', function(e){
        if ($(e.target).closest('.btn-edit-facility, .btn-add-unit').length) return;
        const id = $(this).data('id');
        if (selectedFacility && String(selectedFacility.facility_id) === String(id)) {
            selectedFacility = null;
            selectedUnit = null;
            facilityItems = [];
            assignmentList = [];
            unitList = [];
            currentPage = 1;
            $('#btnAssignItem').prop('disabled', true);
            $('#selectedUnitInfo').text('Select a facility or unit to view items.');
            $('#selectedManagedBy').text('');
            renderFacilities();
            renderAssignments();
            return;
        }
        selectedFacility = facilityList.find(x => String(x.facility_id) === String(id)) || null;
        facilityItems = [];
        assignmentList = [];
        selectedUnit = null;
        currentPage = 1;
        $('#btnAssignItem').prop('disabled', true);
        $('#selectedUnitInfo').text(selectedFacility ? (selectedFacility.facility_name || '') + ' (' + (selectedFacility.facility_code || '') + ') — All Units' : '');
        $('#selectedManagedBy').text('Managed By: Multiple');
        renderFacilities();
        loadUnits();
        loadFacilityItems();
        loadAssignments();
    });

    $('#facilityList').on('click', '.btn-edit-facility', function(){
        const id = $(this).data('id');
        const f = facilityList.find(x => String(x.facility_id) === String(id));
        if (!f) return;
        $('#facilityId').val(f.facility_id);
        $('#facilityCode').val(f.facility_code);
        $('#facilityName').val(f.facility_name);
        $('#facilityMsg').html('');
        $('#facilityModal').modal('show');
    });

    $('#btnAddFacility').on('click', function(){
        $('#facilityId').val('');
        $('#facilityCode').val('');
        $('#facilityName').val('');
        $('#facilityMsg').html('');
        $('#facilityModal').modal('show');
    });

    $('#saveFacilityBtn').on('click', function(){
        const payload = {
            action: 'save_facility',
            facility_id: $('#facilityId').val(),
            facility_code: $('#facilityCode').val().trim(),
            facility_name: $('#facilityName').val().trim()
        };
        $.post(PROCESS_URL, payload, function(res){
            if (!res.success){
                $('#facilityMsg').html('<div class="alert alert-danger mb-0">' + (res.message || 'Save failed.') + '</div>');
                return;
            }
            $('#facilityModal').modal('hide');
            loadFacilities();
            notifySuccess(res.message || 'Saved');
        }, 'json');
    });

    $('#facilityList').on('click', '.btn-add-unit', function(e){
        e.preventDefault();
        e.stopPropagation();
        const id = $(this).data('id');
        selectedFacility = facilityList.find(x => String(x.facility_id) === String(id)) || null;
        facilityItems = [];
        selectedUnit = null;
        renderFacilities();
        loadUnits();
        loadFacilityItems();
        $('#btnAssignItem').prop('disabled', true);
        if (!selectedFacility) return;
        $('#unitId').val('');
        $('#unitType').val('ROOM');
        $('#unitCode').val('');
        $('#unitName').val('');
        $('#unitManagerUserId').val(null).trigger('change');
        $('#unitMsg').html('');
        $('#unitModal').modal('show');
    });

    $('#facilityList').on('click', '.unit-card', function(e){
        if ($(e.target).closest('.btn-edit-unit').length) return;
        const id = $(this).data('id');
        selectedUnit = unitList.find(x => String(x.unit_id) === String(id)) || null;
        currentPage = 1;
        $('#btnAssignItem').prop('disabled', !selectedUnit);
        if (selectedUnit) {
            const _facCode = selectedFacility ? (selectedFacility.facility_code || '') : '';
            $('#selectedUnitInfo').text((_facCode ? _facCode + ' / ' : '') + (selectedUnit.unit_code || '') + ' — ' + (selectedUnit.unit_name || ''));
            $('#selectedManagedBy').text(selectedUnit.unit_manager_name ? 'Managed By: ' + selectedUnit.unit_manager_name : '');
        }
        renderUnits();
        loadAssignments();
    });

    $('#facilityList').on('click', '.btn-edit-unit', function(e){
        e.preventDefault();
        e.stopPropagation();
        const id = $(this).data('id');
        const u = unitList.find(x => String(x.unit_id) === String(id));
        if (!u) return;
        $('#unitId').val(u.unit_id || '');
        $('#unitType').val(u.unit_type || 'ROOM');
        $('#unitCode').val(u.unit_code || '');
        $('#unitName').val(u.unit_name || '');
        const managerId = u.facility_unit_manager_user_id ? String(u.facility_unit_manager_user_id) : '';
        const managerName = u.unit_manager_name ? String(u.unit_manager_name) : '';
        if (managerId) {
            const opt = new Option(managerName || ('User #' + managerId), managerId, true, true);
            $('#unitManagerUserId').append(opt).trigger('change');
        } else {
            $('#unitManagerUserId').val(null).trigger('change');
        }
        $('#unitMsg').html('');
        $('#unitModal').modal('show');
    });

    $('#saveUnitBtn').on('click', function(){
        if (!selectedFacility){
            $('#unitMsg').html('<div class="alert alert-danger mb-0">Select facility first.</div>');
            return;
        }
        const payload = {
            action: 'save_unit',
            unit_id: $('#unitId').val(),
            facility_id: selectedFacility.facility_id,
            unit_type: $('#unitType').val(),
            unit_code: $('#unitCode').val().trim(),
            unit_name: $('#unitName').val().trim(),
            facility_unit_manager_user_id: $('#unitManagerUserId').val()
        };
        $.post(PROCESS_URL, payload, function(res){
            if (!res.success){
                $('#unitMsg').html('<div class="alert alert-danger mb-0">' + (res.message || 'Save failed.') + '</div>');
                return;
            }
            $('#unitModal').modal('hide');
            loadUnits();
            notifySuccess(res.message || 'Saved');
        }, 'json');
    });

    $('#invSearch').on('input', function(){
        if (!assignmentTable) return;
        const q = ($(this).val() || '').toLowerCase().trim();
        if (q) {
            assignmentTable.setFilter(function(r){
                const hay = [r.module_type, r.item_code, r.item_description, r.status, r.issued_to_name, r.accountable_name, r.managed_by_name, r.unit_name, r.unit_code].join(' ').toLowerCase();
                return hay.indexOf(q) !== -1;
            });
        } else {
            assignmentTable.clearFilter();
        }
        assignmentTable.setPage(1);
    });
    $('#btnRefreshAssignments').on('click', function(){
        if (selectedFacility && !selectedUnit) loadFacilityItems();
        else loadAssignments();
    });

    $('#btnAssignItem').on('click', function(){
        if (!selectedFacility || !selectedUnit) return;
        $('#assignSelected').val('');
        $('#assignSourceItemId').val('');
        $('#assignItemCode').val('');
        $('#assignSearch').val('');
        $('#assignSearchSelect').val(null).trigger('change');
        $('#assignQty').val(1);
        $('#assignRemarks').val('');
        $('#assignIssuedToUserId').val(null).trigger('change');
        $('#assignManagedByUserId').val(selectedUnit && selectedUnit.facility_unit_manager_user_id ? String(selectedUnit.facility_unit_manager_user_id) : '');
        $('#assignManagedByName').val(selectedUnit && selectedUnit.unit_manager_name ? String(selectedUnit.unit_manager_name) : '');
        $('#assignMsg').html('<div class="alert alert-info mb-0">Search item code/description or scan QR.</div>');
        $('#assignModal').modal('show');
    });

    $('#assignModule').on('change', function(){
        $('#assignSelected').val('');
        $('#assignSourceItemId').val('');
        $('#assignItemCode').val('');
        $('#assignSearch').val('');
        $('#assignSearchSelect').val(null).trigger('change');
        $('#assignQty').val(1);
        initAssignableItemSelect2();
    });
    $('#confirmAssignBtn').on('click', function(){
        if (!selectedFacility || !selectedUnit){
            $('#assignMsg').html('<div class="alert alert-danger mb-0">Select facility and unit first.</div>');
            return;
        }
        if (!$('#assignSourceItemId').val() || !$('#assignItemCode').val()) {
            $('#assignMsg').html('<div class="alert alert-danger mb-0">No item selected. Search or scan QR first.</div>');
            return;
        }
        const payload = {
            action: 'assign_item',
            facility_id: selectedFacility.facility_id,
            unit_id: selectedUnit.unit_id,
            module_type: $('#assignModule').val(),
            source_item_id: $('#assignSourceItemId').val(),
            item_code: $('#assignItemCode').val(),
            qty: $('#assignQty').val(),
            remarks: $('#assignRemarks').val().trim(),
            issued_to_user_id: $('#assignIssuedToUserId').val(),
            accountable_user_id: $('#assignIssuedToUserId').val(),
            managed_by_user_id: $('#assignManagedByUserId').val()
        };
        $.post(PROCESS_URL, payload, function(res){
            if (!res.success){
                $('#assignMsg').html('<div class="alert alert-danger mb-0">' + (res.message || 'Assign failed.') + '</div>');
                return;
            }
            $('#assignModal').modal('hide');
            loadAssignments();
            loadUnits();
            loadFacilities();
            notifySuccess(res.message || 'Assigned');
        }, 'json');
    });

    $('#assignmentTable').on('click', '.js-thumb-preview', function(){
        const full = $(this).data('full') || $(this).attr('src') || '';
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });

    $('#assignmentTable').on('click', '.btn-report', function(){
        const id = $(this).data('id');
        const remarks = prompt('Report remarks (optional):') || '';
        $.post(PROCESS_URL, { action: 'set_assignment_status', assignment_id: id, status: 'REPORTED', remarks: remarks }, function(res){
            if (!res.success){ notifyError(res.message || 'Failed to update status'); return; }
            loadAssignments();
            loadUnits();
            loadFacilities();
            notifySuccess(res.message || 'Updated');
        }, 'json');
    });

    $('#assignmentTable').on('click', '.btn-return', function(){
        const id = $(this).data('id');
        if (!confirm('Mark this assignment as returned?')) return;
        $.post(PROCESS_URL, { action: 'set_assignment_status', assignment_id: id, status: 'RETURNED' }, function(res){
            if (!res.success){ notifyError(res.message || 'Failed to update status'); return; }
            loadAssignments();
            loadUnits();
            loadFacilities();
            notifySuccess(res.message || 'Updated');
        }, 'json');
    });

    if (typeof initQrSearch === 'function') {
        initQrSearch({
            modalId: '#assignSearchQrModal',
            openButton: '#openAssignSearchScanner',
            searchInput: '#assignSearch',
            onSearch: function () {
                pickAssignableItemByCode($('#assignSearch').val() || '');
            },
            cameraSelectId: '#assignSearchCameraSelect',
            startBtnId: '#assignSearchBtnStart',
            stopBtnId: '#assignSearchBtnStop',
            previewId: '#assignSearchPreview',
            lastScannedId: '#assignSearchLastScanned',
            errorId: '#assignSearchScanError',
            loadingId: '#assignSearchScannerLoading'
        });
    }
});
</script>
</html>
