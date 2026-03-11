<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("USER") || role_has("USERS"))) {
    header("Location: " . BASE_URL);
    exit();
}
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
        .section-card .card-header { min-height: 50px; display: flex; align-items: center; }
        .facility-group { margin-bottom: 1.5rem; }
        .facility-header { background: #0d6efd; color: white; padding: 10px 14px; border-radius: 8px; font-weight: 600; margin-bottom: 10px; font-size: 0.95rem; cursor: pointer; transition: background 0.2s ease; }
        .facility-header:hover { background: #0b5ed7; }
        .facility-header.active { background: #0a58ca; box-shadow: 0 2px 6px rgba(13,110,253,0.3); }
        .facility-header i { margin-right: 6px; }
        .unit-card { border: 1px solid #dbe2ea; border-radius: 8px; padding: 12px; background: #fff; cursor: pointer; transition: all 0.2s ease; margin-bottom: 8px; }
        .unit-card:hover { border-color: #a5b4fc; background: #f8faff; transform: translateX(4px); }
        .unit-card.active { border-color: #0d6efd; background: #eef5ff; box-shadow: 0 2px 6px rgba(13,110,253,0.2); }
        .small-muted { color: #6c757d; font-size: 0.9rem; }
        .status-badge { padding: 0.25rem 0.55rem; border-radius: 999px; font-size: 0.8rem; }
        .item-thumb { width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
        .item-badge { width: 50px; height: 50px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #1E3A8A; color: #fff; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; border: 1px solid rgba(0,0,0,0.06); cursor: default; }
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
            <p class="text-muted small mb-0">View items inside units you manage.</p>
        </div>

        <section class="section">
            <div id="pageMsg" class="alert alert-danger d-none"></div>
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <div class="card section-card h-100">
                        <div class="card-header bg-eclearance text-white fw-semibold">
                            <i class="bi bi-door-open"></i>&ensp;Units I Manage
                        </div>
                        <div class="card-body bg-white mt-3" style="max-height: 600px; overflow-y: auto;">
                            <div id="unitsEmpty" class="alert alert-info d-none">No assigned units yet.</div>
                            <div id="unitList"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card section-card">
                        <div class="card-header bg-eclearance text-white fw-semibold">
                            <i class="bi bi-box-seam"></i>&ensp;Unit Inventory
                        </div>
                        <div class="card-body bg-white mt-3">
                            <div class="d-flex gap-2 mb-3">
                                <input type="text" class="form-control" id="assignSearch" placeholder="Search code, description, status...">
                                <button class="btn btn-outline-secondary" id="refreshAssignments">Refresh</button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <div id="selectedUnitInfo" class="small-muted">Select a facility or unit to view assignments.</div>
                                <div id="selectedManagedBy" class="small-muted"></div>
                            </div>
                            <div id="assignmentTable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

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
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script>
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'app/facility_records_process.php'); ?>;
let managedUnits = [];
let selectedUnit = null;
let selectedFacility = null;
let assignments = [];
let assignmentTable = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function togglePageMsg(msg) {
    const el = $('#pageMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function statusBadge(status) {
    const s = escapeHtml(String(status || '').toUpperCase());
    const map = {
        'ACTIVE': 'bg-success text-white',
        'REPORTED': 'bg-warning text-dark',
        'RETURN_REQUESTED': 'bg-info text-dark',
        'RETURNED': 'bg-secondary text-white'
    };
    const cls = map[s] || 'bg-light text-dark border';
    return `<span class="status-badge ${cls}">${s || ''}</span>`;
}

function initAssignmentTable(){
    assignmentTable = new Tabulator('#assignmentTable', {
        layout: "fitColumns",
        renderVertical: "basic",
        responsiveLayout: "collapse",
        pagination: "local",
        paginationSize: 5,
        paginationSizeSelector: [5, 10, 20, 50, true],
        placeholder: "Select a facility or unit to view assignments.",
        columns: [
            { title: "Image", field: "category_photo_thumb_url", width: 60, hozAlign: "center", headerSort: false, formatter: function(cell){
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || cell.getRow().getData().item_description || '';
                if (url) {
                    return `<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="${url}" data-full="${full || url}" loading="lazy" alt="Item image"></div>`;
                }
                const initials = (String(name).trim().split(/\s+/).map(function(w){ return w.charAt(0); }).filter(Boolean).slice(0,2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge" title="${escapeHtml(name)}">${escapeHtml(initials)}</div></div>`;
            }},
            { title: "Item Code", field: "item_code", width: 130, formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? '<span class="badge bg-light text-dark border">' + v + '</span>' : '';
            }},
            { title: "Description", field: "item_description", widthGrow: 2, minWidth: 160, formatter: function(cell){
                return escapeHtml(cell.getValue() || '');
            }},
            { title: "Qty", field: "qty", width: 60, hozAlign: "center" },
            { title: "Issued To", field: "issued_to_name", width: 150, formatter: function(cell){
                return escapeHtml(cell.getValue() || '-');
            }},
            { title: "Status", field: "status", width: 120, formatter: function(cell){
                return statusBadge(cell.getValue());
            }},
            { title: "Issued At", field: "issued_at", width: 130 },
            { title: "Actions", field: "assignment_id", width: 185, headerSort: false, formatter: function(cell){
                const id = cell.getValue();
                const row = cell.getRow().getData();
                const s = String(row.status || '').toUpperCase();
                const isReturned = s === 'RETURNED' || s === 'RETURN_REQUESTED';
                return '<div class="d-flex gap-1"><button class="btn btn-outline-warning btn-sm btn-report" data-id="' + id + '">Report</button><button class="btn btn-outline-primary btn-sm btn-return" data-id="' + id + '"' + (isReturned ? ' disabled' : '') + '>Return Request</button></div>';
            }}
        ]
    });
}

function renderUnits() {
    const list = $('#unitList');
    list.empty();
    if (!managedUnits.length) {
        $('#unitsEmpty').removeClass('d-none');
        return;
    }
    $('#unitsEmpty').addClass('d-none');
    
    // Group units by facility
    const grouped = {};
    managedUnits.forEach(function(u) {
        const facId = u.facility_id || 'unknown';
        if (!grouped[facId]) {
            grouped[facId] = {
                facility_code: u.facility_code,
                facility_name: u.facility_name,
                facility_id: u.facility_id,
                units: []
            };
        }
        grouped[facId].units.push(u);
    });
    
    // Render each facility group
    Object.values(grouped).forEach(function(fac) {
        const facActive = !selectedUnit && selectedFacility && String(selectedFacility.facility_id) === String(fac.facility_id) ? 'active' : '';
        const totalItems = fac.units.reduce(function(sum, u) { return sum + (parseInt(u.active_item_count) || 0); }, 0);
        const safeFacName = escapeHtml(fac.facility_name || 'Unknown Facility');
        const safeFacCode = escapeHtml(fac.facility_code || '');
        list.append(`
            <div class="facility-group">
                <div class="facility-header ${facActive}" data-facility-id="${fac.facility_id}">
                    <i class="bi bi-building"></i>${safeFacName}
                    <span class="small" style="opacity:0.9;">(${safeFacCode}) &middot; ${totalItems} item(s)</span>
                </div>
                <div class="facility-units" data-facility-id="${fac.facility_id}">
                </div>
            </div>
        `);
        
        const unitsContainer = list.find(`.facility-units[data-facility-id="${fac.facility_id}"]`);
        fac.units.forEach(function(u) {
            const active = selectedUnit && String(selectedUnit.unit_id) === String(u.unit_id) ? 'active' : '';
            const safeUnitCode = escapeHtml(u.unit_code || '');
            const safeUnitName = escapeHtml(u.unit_name || '');
            unitsContainer.append(`
                <div class="unit-card ${active}" data-id="${u.unit_id}">
                    <div class="fw-semibold">${safeUnitCode} - ${safeUnitName}</div>
                    <div class="small-muted" style="margin-top:4px;">
                        <i class="bi bi-box-seam"></i> ${u.active_item_count || 0} assigned item(s)
                    </div>
                </div>
            `);
        });
    });
}

function renderAssignments() {
    if (!assignmentTable) return;
    const q = ($('#assignSearch').val() || '').toLowerCase().trim();
    assignmentTable.setData(assignments);
    if (q) {
        assignmentTable.setFilter(function(r) {
            const hay = [r.module_type, r.item_code, r.item_description, r.status,
                         r.issued_to_name, r.accountable_name, r.managed_by_name].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    } else {
        assignmentTable.clearFilter();
    }
}

function loadAssignments() {
    if (!selectedUnit && !selectedFacility) { renderAssignments(); return; }
    
    let postData, infoText;
    if (selectedUnit) {
        postData = { action: 'list_unit_assignments', unit_id: selectedUnit.unit_id };
        infoText = `${selectedUnit.facility_code || ''} / ${selectedUnit.unit_code || ''} - ${selectedUnit.unit_name || ''}`;
    } else {
        postData = { action: 'list_facility_assignments', facility_id: selectedFacility.facility_id };
        infoText = `${selectedFacility.facility_name || ''} (${selectedFacility.facility_code || ''}) - All Units`;
    }
    
    $.post(PROCESS_URL, postData, function(res) {
        if (!res || res.success !== true) {
            togglePageMsg((res && res.message) || 'Failed to load assignments.');
            assignments = [];
            renderAssignments();
            return;
        }
        togglePageMsg('');
        assignments = res.data || [];
        $('#selectedUnitInfo').text(infoText);
        if (selectedUnit && selectedUnit.unit_manager_name) {
            $('#selectedManagedBy').text('Managed By: ' + selectedUnit.unit_manager_name);
        } else if (selectedFacility) {
            $('#selectedManagedBy').text('Managed By: Multiple');
        } else {
            $('#selectedManagedBy').text('');
        }
        renderAssignments();
    }, 'json').fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while loading assignments.';
        togglePageMsg(msg);
        assignments = [];
        renderAssignments();
    });
}

function loadManagedUnits() {
    $.post(PROCESS_URL, { action: 'list_managed_units' }, function(res) {
        if (!res || res.success !== true) {
            togglePageMsg((res && res.message) || 'Failed to load units.');
            return;
        }
        togglePageMsg('');
        managedUnits = res.data || [];
        if (selectedUnit) {
            const stillExists = managedUnits.find(function(u) { return String(u.unit_id) === String(selectedUnit.unit_id); });
            if (!stillExists) selectedUnit = null;
        }
        renderUnits();
        loadAssignments();
    }, 'json').fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while loading units.';
        togglePageMsg(msg);
    });
}

function updateStatus(assignmentId, status, label) {
    if (!assignmentId) return;
    const ok = confirm(`Confirm ${label.toLowerCase()}?`);
    if (!ok) return;
    $.post(PROCESS_URL, { action: 'update_status', assignment_id: assignmentId, status: status }, function(res) {
        if (!res || res.success !== true) {
            togglePageMsg((res && res.message) || 'Failed to update status.');
            return;
        }
        togglePageMsg('');
        loadAssignments();
        loadManagedUnits();
    }, 'json').fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while updating status.';
        togglePageMsg(msg);
    });
}

$(document).ready(function() {
    initAssignmentTable();
    loadManagedUnits();

    $('#unitList').on('click', '.facility-header', function() {
        const facId = $(this).data('facility-id');
        const facUnit = managedUnits.find(function(u) { return String(u.facility_id) === String(facId); });
        if (!facUnit) return;
        selectedUnit = null;
        selectedFacility = { facility_id: facUnit.facility_id, facility_code: facUnit.facility_code, facility_name: facUnit.facility_name };
        $('#selectedManagedBy').text('Managed By: Multiple');
        renderUnits();
        loadAssignments();
    });

    $('#unitList').on('click', '.unit-card', function() {
        const id = $(this).data('id');
        selectedUnit = managedUnits.find(function(u) { return String(u.unit_id) === String(id); }) || null;
        selectedFacility = null;
        if (selectedUnit && selectedUnit.unit_manager_name) {
            $('#selectedManagedBy').text('Managed By: ' + selectedUnit.unit_manager_name);
        } else {
            $('#selectedManagedBy').text('');
        }
        renderUnits();
        loadAssignments();
    });

    $('#assignSearch').on('input', function(){
        if (!assignmentTable) return;
        const q = ($(this).val() || '').toLowerCase().trim();
        if (q) {
            assignmentTable.setFilter(function(r){
                const hay = [r.module_type, r.item_code, r.item_description, r.status,
                             r.issued_to_name, r.accountable_name, r.managed_by_name].join(' ').toLowerCase();
                return hay.indexOf(q) !== -1;
            });
        } else {
            assignmentTable.clearFilter();
        }
        assignmentTable.setPage(1);
    });
    $('#refreshAssignments').on('click', loadAssignments);

    $('#assignmentTable').on('click', '.btn-report', function() {
        const id = $(this).data('id');
        updateStatus(id, 'REPORTED', 'report');
    });

    $('#assignmentTable').on('click', '.btn-return', function() {
        const id = $(this).data('id');
        updateStatus(id, 'RETURN_REQUESTED', 'return request');
    });

    // Image expand functionality
    $('#assignmentTable').on('click', '.js-thumb-preview', function() {
        const full = $(this).data('full') || $(this).attr('data-full');
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });
});
</script>
</html>
