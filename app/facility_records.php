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
                        <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-box-seam"></i>&ensp;Unit Inventory</span>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" id="assignSearch" class="form-control form-control-sm" placeholder="Search code, description, status..." style="max-width: 240px;">
                                <button class="btn btn-light btn-sm" id="refreshAssignments">Refresh</button>
                            </div>
                        </div>
                        <div class="card-body bg-white mt-3">
                            <div id="selectedUnitInfo" class="small-muted mb-2">Select a facility or unit to view assignments.</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" id="assignmentTable">
                                    <thead>
                                        <tr>
                                            <th style="width:70px;" class="text-center">Image</th>
                                            <th>Item Code</th>
                                            <th>Description</th>
                                            <th class="text-center" style="width:90px;">Qty</th>
                                            <th>Issued To</th>
                                            <th>Status</th>
                                            <th>Issued At</th>
                                            <th style="width:150px;">Actions</th>
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
<script>
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'app/facility_records_process.php'); ?>;
let managedUnits = [];
let selectedUnit = null;
let selectedFacility = null;
let assignments = [];

function togglePageMsg(msg) {
    const el = $('#pageMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function statusBadge(status) {
    const s = String(status || '').toUpperCase();
    const map = {
        'ACTIVE': 'bg-success text-white',
        'REPORTED': 'bg-warning text-dark',
        'RETURN_REQUESTED': 'bg-info text-dark',
        'RETURNED': 'bg-secondary text-white'
    };
    const cls = map[s] || 'bg-light text-dark border';
    return `<span class="status-badge ${cls}">${s || ''}</span>`;
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
                units: []
            };
        }
        grouped[facId].units.push(u);
    });
    
    // Render each facility group
    Object.values(grouped).forEach(function(fac) {
        const facActive = !selectedUnit && selectedFacility && String(selectedFacility.facility_id) === String(fac.units[0].facility_id) ? 'active' : '';
        const totalItems = fac.units.reduce(function(sum, u) { return sum + (parseInt(u.active_item_count) || 0); }, 0);
        list.append(`
            <div class="facility-group">
                <div class="facility-header ${facActive}" data-facility-id="${fac.units[0].facility_id}">
                    <i class="bi bi-building"></i>${fac.facility_name || 'Unknown Facility'}
                    <span class="small" style="opacity:0.9;">(${fac.facility_code || ''}) &middot; ${totalItems} item(s)</span>
                </div>
                <div class="facility-units" data-facility="${fac.facility_code || ''}">
                </div>
            </div>
        `);
        
        const unitsContainer = list.find(`.facility-units[data-facility="${fac.facility_code || ''}"]`);
        fac.units.forEach(function(u) {
            const active = selectedUnit && String(selectedUnit.unit_id) === String(u.unit_id) ? 'active' : '';
            unitsContainer.append(`
                <div class="unit-card ${active}" data-id="${u.unit_id}">
                    <div class="fw-semibold">${u.unit_code || ''} - ${u.unit_name || ''}</div>
                    <div class="small-muted" style="margin-top:4px;">
                        <i class="bi bi-box-seam"></i> ${u.active_item_count || 0} assigned item(s)
                    </div>
                </div>
            `);
        });
    });
}

function renderAssignments() {
    const tbody = $('#assignmentTable tbody');
    tbody.empty();
    if (!selectedUnit && !selectedFacility) {
        tbody.html('<tr><td colspan="8" class="text-muted text-center">Select a facility or unit first.</td></tr>');
        return;
    }
    const q = ($('#assignSearch').val() || '').toLowerCase().trim();
    const filtered = assignments.filter(function(a) {
        if (!q) return true;
        const hay = [a.module_type, a.item_code, a.item_description, a.status].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    });
    if (!filtered.length) {
        tbody.html('<tr><td colspan="8" class="text-muted text-center">No assignments found.</td></tr>');
        return;
    }
    filtered.forEach(function(a) {
        // Prepare image HTML - URLs are built server-side
        let imageHtml = '';
        if (a.category_photo_thumb_url) {
            imageHtml = `<div class="thumb-wrap">
                            <img class="item-thumb js-thumb-preview" src="${a.category_photo_thumb_url}" data-full="${a.category_photo_url || a.category_photo_thumb_url}" loading="lazy" alt="Item image">
                        </div>`;
        } else {
            const name = String(a.item_category_name || a.item_description || 'IT').trim();
            const initials = name.split(/\s+/).map(w => w.charAt(0)).filter(Boolean).slice(0,2).join('').toUpperCase();
            imageHtml = `<div class="thumb-wrap"><div class="item-badge" title="${name}">${initials}</div></div>`;
        }
        
        tbody.append(`
            <tr>
                <td class="text-center">${imageHtml}</td>
                <td><span class="badge bg-light text-dark border">${a.item_code || ''}</span></td>
                <td>${a.item_description || ''}</td>
                <td class="text-center">${a.qty || ''}</td>
                <td>${a.issued_to_name || '-'}</td>
                <td>${statusBadge(a.status)}</td>
                <td>${a.issued_at || ''}</td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-outline-warning btn-sm btn-report" data-id="${a.assignment_id}">Report</button>
                        <button class="btn btn-outline-primary btn-sm btn-return" data-id="${a.assignment_id}">Return Request</button>
                    </div>
                </td>
            </tr>
        `);
    });
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
    loadManagedUnits();

    $('#unitList').on('click', '.facility-header', function() {
        const facId = $(this).data('facility-id');
        const facUnit = managedUnits.find(function(u) { return String(u.facility_id) === String(facId); });
        if (!facUnit) return;
        selectedUnit = null;
        selectedFacility = { facility_id: facUnit.facility_id, facility_code: facUnit.facility_code, facility_name: facUnit.facility_name };
        renderUnits();
        loadAssignments();
    });

    $('#unitList').on('click', '.unit-card', function() {
        const id = $(this).data('id');
        selectedUnit = managedUnits.find(function(u) { return String(u.unit_id) === String(id); }) || null;
        selectedFacility = null;
        renderUnits();
        loadAssignments();
    });

    $('#assignSearch').on('input', renderAssignments);
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
