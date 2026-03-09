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
    <link href="<?= BASE_URL ?>assets/css/select2.min.css" rel="stylesheet">
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .facility-card { border: 1px solid #dbe2ea; border-radius: 10px; padding: 12px; background: #fff; cursor: pointer; }
        .facility-card.active { border-color: #0d6efd; background: #eef5ff; }
        .facility-code { font-family: monospace; font-weight: 700; }
        .small-muted { color: #6c757d; font-size: 0.85rem; }
        .unit-pill { border: 1px solid #dee2e6; border-radius: 999px; padding: 4px 10px; background: #fff; cursor: pointer; font-size: 0.85rem; }
        .unit-pill.active { border-color: #0d6efd; color: #0d6efd; background: #eef5ff; }
        .select2-container { width: 100% !important; }
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
                        <span><i class="bi bi-building"></i>&ensp;Facilities</span>
                        <button class="btn btn-light btn-sm" id="btnAddFacility"><i class="bi bi-plus-lg"></i> Add</button>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div id="facilityList" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-door-open"></i>&ensp;Facility Units</span>
                        <button class="btn btn-light btn-sm" id="btnAddUnit" disabled><i class="bi bi-plus-lg"></i> Add Unit</button>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div id="selectedFacilityInfo" class="small-muted mb-2">Select a facility first.</div>
                        <div id="unitList" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <div class="card section-card mt-3">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i>&ensp;Unit Inventory Assignments</span>
                        <button class="btn btn-light btn-sm" id="btnAssignItem" disabled><i class="bi bi-plus-lg"></i> Assign Item</button>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <input type="text" class="form-control" id="invSearch" placeholder="Search code/description/status...">
                            <button class="btn btn-outline-secondary" id="btnRefreshAssignments">Refresh</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="assignmentTable">
                                <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Item Code</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Issued To</th>
                                    <th>Accountable</th>
                                    <th>Managed By</th>
                                    <th>Status</th>
                                    <th>Issued At</th>
                                    <th style="width:170px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
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
                        <input type="text" id="assignSearch" class="form-control" placeholder="Item code/description">
                    </div>
                </div>
                <div class="mt-3 table-responsive" style="max-height:260px;">
                    <table class="table table-sm table-bordered" id="assignItemTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Available</th>
                                <th>Unit</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Issued To</label>
                        <select id="assignIssuedToUserId" class="form-select"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Accountable</label>
                        <select id="assignAccountableUserId" class="form-select"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Managed By</label>
                        <select id="assignManagedByUserId" class="form-select"></select>
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

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script>
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'admin/modules/transactions/facility_inventory_records_process.php'); ?>;
let facilityList = [];
let unitList = [];
let assignmentList = [];
let selectedFacility = null;
let selectedUnit = null;
let assignItemsCache = [];
let userOptionsCache = [];
let unitManagerOptionsCache = [];

function showPageError(msg){
    const el = $('#pageMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
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
        const active = selectedFacility && String(selectedFacility.facility_id) === String(f.facility_id) ? 'active' : '';
        wrap.append(`
            <div class="facility-card ${active}" data-id="${f.facility_id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="facility-code">${f.facility_code}</div>
                    <button class="btn btn-outline-secondary btn-sm btn-edit-facility" data-id="${f.facility_id}"><i class="bi bi-pencil"></i></button>
                </div>
                <div class="fw-semibold">${f.facility_name}</div>
                <div class="small-muted">${f.unit_count} unit(s) | ${f.active_item_count} active item(s)</div>
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

function renderUserSelect($select, selectedValue, includeBlankLabel){
    const blankLabel = includeBlankLabel || 'Select user';
    $select.empty();
    $select.append(`<option value="">${blankLabel}</option>`);
    userOptionsCache.forEach(function(u){
        const role = u.role_name ? ` [${u.role_name}]` : '';
        const label = `${u.full_name || ''}${u.email ? ' - ' + u.email : ''}${role}`;
        $select.append(`<option value="${u.user_id}">${label}</option>`);
    });
    if (selectedValue) {
        $select.val(String(selectedValue));
    }
}

function renderUnitManagerSelect(selectedValue){
    const $select = $('#unitManagerUserId');
    $select.empty();
    $select.append('<option value="">Select facility unit manager</option>');
    unitManagerOptionsCache.forEach(function(u){
        const role = u.role_name ? ` [${u.role_name}]` : '';
        const label = `${u.full_name || ''}${u.email ? ' - ' + u.email : ''}${role}`;
        $select.append(`<option value="${u.user_id}">${label}</option>`);
    });
    if (selectedValue) {
        $select.val(String(selectedValue));
    }
    if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
        $select.trigger('change.select2');
    }
}

function loadUnitManagers(){
    return $.post(PROCESS_URL, { action: 'list_users', permanent_only: 1 }, function(res){
        if (!res.success) { return; }
        unitManagerOptionsCache = (res.data || []).map(function(u){
            const roleMap = { '1': 'SUPER ADMIN', '2': 'ADMIN', '3': 'ADMIN STAFF', '4': 'USER' };
            u.role_name = roleMap[String(u.role_id)] || '';
            return u;
        });
        renderUnitManagerSelect('');
    }, 'json');
}

function loadUsers(){
    return $.post(PROCESS_URL, { action: 'list_users' }, function(res){
        if (!res.success) { return; }
        userOptionsCache = (res.data || []).map(function(u){
            const roleMap = { '1': 'SUPER ADMIN', '2': 'ADMIN', '3': 'ADMIN STAFF', '4': 'USER' };
            u.role_name = roleMap[String(u.role_id)] || '';
            return u;
        });
        renderUserSelect($('#assignIssuedToUserId'), '', 'Optional');
        renderUserSelect($('#assignAccountableUserId'), '', 'Optional');
        renderUserSelect($('#assignManagedByUserId'), '', 'Optional');
    }, 'json');
}

function renderUnits(){
    $('#selectedFacilityInfo').text(selectedFacility ? `${selectedFacility.facility_code} - ${selectedFacility.facility_name}` : 'Select a facility first.');
    const wrap = $('#unitList');
    wrap.empty();
    if (!selectedFacility){
        wrap.html('<div class="small-muted">Select a facility.</div>');
        return;
    }
    if (!unitList.length){
        wrap.html('<div class="small-muted">No units yet for this facility.</div>');
        return;
    }
    unitList.forEach(function(u){
        const active = selectedUnit && String(selectedUnit.unit_id) === String(u.unit_id) ? 'active' : '';
        const officer = (u.unit_manager_name || '').trim();
        wrap.append(`
            <div class="d-flex align-items-center gap-1 mb-1">
                <div class="unit-pill ${active} flex-grow-1" data-id="${u.unit_id}" title="${u.unit_type}">
                    ${u.unit_code} - ${u.unit_name} (${u.active_item_count})
                </div>
                <button class="btn btn-outline-secondary btn-sm btn-edit-unit" data-id="${u.unit_id}" title="Edit Unit">
                    <i class="bi bi-pencil"></i>
                </button>
            </div>
        `);
        if (officer) {
            wrap.append(`<span class="small-muted ms-1">Manager: ${officer}</span>`);
        }
    });
}

function loadAssignments(){
    const tbody = $('#assignmentTable tbody');
    tbody.empty();
    if (!selectedUnit){
        tbody.html('<tr><td colspan="10" class="text-muted text-center">Select a unit first.</td></tr>');
        return;
    }
    $.post(PROCESS_URL, { action: 'list_unit_inventory', unit_id: selectedUnit.unit_id }, function(res){
        if (!res.success) { showPageError(res.message || 'Failed to load assignments.'); return; }
        assignmentList = res.data || [];
        renderAssignments();
    }, 'json');
}

function renderAssignments(){
    const q = ($('#invSearch').val() || '').toLowerCase().trim();
    const rows = assignmentList.filter(function(r){
        if (!q) return true;
        const hay = [r.module_type, r.item_code, r.item_description, r.status].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    });
    const tbody = $('#assignmentTable tbody');
    tbody.empty();
    if (!rows.length){
        tbody.html('<tr><td colspan="10" class="text-muted text-center">No assignments found.</td></tr>');
        return;
    }
    rows.forEach(function(r){
        const isReturned = String(r.status).toUpperCase() === 'RETURNED';
        tbody.append(`
            <tr>
                <td>${r.module_type || ''}</td>
                <td><span class="badge bg-light text-dark border">${r.item_code || ''}</span></td>
                <td>${r.item_description || ''}</td>
                <td class="text-center">${r.qty || ''}</td>
                <td>${r.issued_to_name || '-'}</td>
                <td>${r.accountable_name || '-'}</td>
                <td>${r.managed_by_name || '-'}</td>
                <td>${r.status || ''}</td>
                <td>${r.issued_at || ''}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-outline-warning btn-sm btn-report" data-id="${r.assignment_id}">Report</button>
                        <button class="btn btn-outline-success btn-sm btn-return" data-id="${r.assignment_id}" ${isReturned ? 'disabled' : ''}>Return</button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function loadAssignableItems(){
    const moduleType = $('#assignModule').val();
    const search = ($('#assignSearch').val() || '').trim();
    $.post(PROCESS_URL, { action: 'list_available_items', module_type: moduleType, search: search }, function(res){
        if (!res.success) {
            $('#assignMsg').html('<div class="alert alert-danger mb-0">' + (res.message || 'Failed to load items.') + '</div>');
            return;
        }
        assignItemsCache = res.data || [];
        const tbody = $('#assignItemTable tbody');
        tbody.empty();
        if (!assignItemsCache.length){
            tbody.html('<tr><td colspan="5" class="text-muted text-center">No available items.</td></tr>');
            return;
        }
        assignItemsCache.forEach(function(it){
            tbody.append(`
                <tr>
                    <td>${it.item_code || ''}</td>
                    <td>${it.item_description || ''}</td>
                    <td class="text-center">${it.available_qty || 0}</td>
                    <td>${it.unit || ''}</td>
                    <td><button class="btn btn-sm btn-primary btn-pick-item" data-id="${it.source_item_id}" data-code="${it.item_code}">Pick</button></td>
                </tr>
            `);
        });
    }, 'json');
}

function initSelect2Controls(){
    if (!$.fn.select2) return;
    $('#unitManagerUserId').select2({
        width: '100%',
        dropdownParent: $('#unitModal'),
        placeholder: 'Select facility unit manager',
        allowClear: true
    });
}

$(document).ready(function(){
    initSelect2Controls();
    loadUnitManagers();
    loadUsers();
    loadFacilities();

    $('#facilityList').on('click', '.facility-card', function(e){
        if ($(e.target).closest('.btn-edit-facility').length) return;
        const id = $(this).data('id');
        selectedFacility = facilityList.find(x => String(x.facility_id) === String(id)) || null;
        selectedUnit = null;
        $('#btnAddUnit').prop('disabled', !selectedFacility);
        $('#btnAssignItem').prop('disabled', true);
        renderFacilities();
        loadUnits();
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
            success_notif(res.message || 'Saved');
        }, 'json');
    });

    $('#btnAddUnit').on('click', function(){
        if (!selectedFacility) return;
        $('#unitId').val('');
        $('#unitType').val('ROOM');
        $('#unitCode').val('');
        $('#unitName').val('');
        $('#unitManagerUserId').val('').trigger('change');
        $('#unitMsg').html('');
        $('#unitModal').modal('show');
    });

    $('#unitList').on('click', '.unit-pill', function(e){
        if ($(e.target).closest('.btn-edit-unit').length) return;
        const id = $(this).data('id');
        selectedUnit = unitList.find(x => String(x.unit_id) === String(id)) || null;
        $('#btnAssignItem').prop('disabled', !selectedUnit);
        renderUnits();
        loadAssignments();
    });

    $('#unitList').on('click', '.btn-edit-unit', function(e){
        e.preventDefault();
        e.stopPropagation();
        const id = $(this).data('id');
        const u = unitList.find(x => String(x.unit_id) === String(id));
        if (!u) return;
        $('#unitId').val(u.unit_id || '');
        $('#unitType').val(u.unit_type || 'ROOM');
        $('#unitCode').val(u.unit_code || '');
        $('#unitName').val(u.unit_name || '');
        $('#unitManagerUserId').val(u.facility_unit_manager_user_id ? String(u.facility_unit_manager_user_id) : '').trigger('change');
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
            success_notif(res.message || 'Saved');
        }, 'json');
    });

    $('#invSearch').on('input', renderAssignments);
    $('#btnRefreshAssignments').on('click', loadAssignments);

    $('#btnAssignItem').on('click', function(){
        if (!selectedFacility || !selectedUnit) return;
        $('#assignSelected').val('');
        $('#assignSourceItemId').val('');
        $('#assignItemCode').val('');
        $('#assignQty').val(1);
        $('#assignRemarks').val('');
        $('#assignIssuedToUserId').val('');
        $('#assignAccountableUserId').val(selectedUnit && selectedUnit.facility_unit_manager_user_id ? String(selectedUnit.facility_unit_manager_user_id) : '');
        $('#assignManagedByUserId').val('');
        $('#assignMsg').html('');
        loadAssignableItems();
        $('#assignModal').modal('show');
    });

    $('#assignModule').on('change', function(){
        $('#assignSelected').val('');
        $('#assignSourceItemId').val('');
        $('#assignItemCode').val('');
        $('#assignQty').val(1);
        loadAssignableItems();
    });
    $('#assignSearch').on('input', loadAssignableItems);

    $('#assignItemTable').on('click', '.btn-pick-item', function(){
        const sourceId = $(this).data('id');
        const code = $(this).data('code');
        const item = assignItemsCache.find(x => String(x.source_item_id) === String(sourceId) && String(x.item_code) === String(code));
        if (!item) return;
        $('#assignSourceItemId').val(item.source_item_id);
        $('#assignItemCode').val(item.item_code);
        $('#assignSelected').val((item.item_code || '') + ' - ' + (item.item_description || ''));
        if ($('#assignModule').val() === 'AST') {
            $('#assignQty').val(1).prop('readonly', true);
        } else {
            $('#assignQty').prop('readonly', false);
        }
    });

    $('#confirmAssignBtn').on('click', function(){
        if (!selectedFacility || !selectedUnit){
            $('#assignMsg').html('<div class="alert alert-danger mb-0">Select facility and unit first.</div>');
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
            accountable_user_id: $('#assignAccountableUserId').val(),
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
            success_notif(res.message || 'Assigned');
        }, 'json');
    });

    $('#assignmentTable').on('click', '.btn-report', function(){
        const id = $(this).data('id');
        const remarks = prompt('Report remarks (optional):') || '';
        $.post(PROCESS_URL, { action: 'set_assignment_status', assignment_id: id, status: 'REPORTED', remarks: remarks }, function(res){
            if (!res.success){ error_notif(res.message || 'Failed to update status'); return; }
            loadAssignments();
            loadUnits();
            loadFacilities();
            success_notif(res.message || 'Updated');
        }, 'json');
    });

    $('#assignmentTable').on('click', '.btn-return', function(){
        const id = $(this).data('id');
        if (!confirm('Mark this assignment as returned?')) return;
        $.post(PROCESS_URL, { action: 'set_assignment_status', assignment_id: id, status: 'RETURNED' }, function(res){
            if (!res.success){ error_notif(res.message || 'Failed to update status'); return; }
            loadAssignments();
            loadUnits();
            loadFacilities();
            success_notif(res.message || 'Updated');
        }, 'json');
    });
});
</script>
</html>
