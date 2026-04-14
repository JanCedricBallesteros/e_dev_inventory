<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("USER") || role_has("USERS"))) {
    header("Location: " . BASE_URL);
    exit();
}

$has_managing_facility_unit = user_has_managing_facility_unit($s_user_id ?? 0);
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
        .facility-header.personal { background: #4c1d95; }
        .facility-header.personal:hover { background: #5b21b6; }
        .facility-header.personal.active { background: #4c1d95; box-shadow: 0 2px 6px rgba(76,29,149,0.35); }
        .facility-header i { margin-right: 6px; }
        .system-note { display: block; font-size: 0.82rem; opacity: 0.95; margin-top: 2px; }
        .floor-group { margin-top: 8px; border-left: 3px solid #e2e8f0; padding-left: 8px; }
        .floor-header { font-weight: 600; font-size: 0.85rem; color: #334155; display: flex; align-items: center; gap: 6px; margin: 6px 0; cursor: pointer; user-select: none; }
        .floor-header .chevron { margin-left: auto; transition: transform 0.15s ease; }
        .floor-header.collapsed .chevron { transform: rotate(-90deg); }
        .floor-header.active { color: #0d6efd; }
        .unit-card { border: 1px solid #dbe2ea; border-radius: 8px; padding: 12px; background: #fff; cursor: pointer; transition: all 0.2s ease; margin-bottom: 8px; }
        .unit-card:hover { border-color: #a5b4fc; background: #f8faff; transform: translateX(4px); }
        .unit-card.active { border-color: #0d6efd; background: #eef5ff; box-shadow: 0 2px 6px rgba(13,110,253,0.2); }
        .small-muted { color: #6c757d; font-size: 0.9rem; }
        .status-badge { padding: 0.25rem 0.55rem; border-radius: 999px; font-size: 0.8rem; }
        .item-thumb { width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
        .item-badge { width: 50px; height: 50px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #1E3A8A; color: #fff; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; border: 1px solid rgba(0,0,0,0.06); cursor: default; }
        .thumb-wrap { display: flex; align-items: center; justify-content: center; }
        .img-preview { max-width: 100%; max-height: 70vh; border-radius: 8px; }
        .tabulator { font-size: 0.875rem; }
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
            <?php if (!$has_managing_facility_unit) { ?>
                <div class="alert alert-warning">You have no managing facility units.</div>
            <?php } ?>
            <?php if ($has_managing_facility_unit) { ?>
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
            <?php } ?>
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
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'users/modules/facility_records_process.php'); ?>;
const HAS_MANAGING_FACILITY_UNIT = <?php echo $has_managing_facility_unit ? 'true' : 'false'; ?>;
let managedUnits = [];
let selectedUnit = null;
let selectedFacility = null;
let selectedFloor = null;
let floorCollapseState = {};
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

function normalizeFloorKey(name) {
    return String(name || '').trim() || 'Unassigned';
}

function getRowFloorLabel(row) {
    return normalizeFloorKey(row ? (row.floor_label || '') : '');
}

function isPersonalFacility(facility) {
    if (!facility) return false;
    if (String(facility.is_personal || '0') === '1') return true;
    const code = String(facility.facility_code || '').trim().toUpperCase();
    if (code === 'PERSONAL') return true;
    const name = String(facility.facility_name || '').trim().toUpperCase();
    return name === 'FOR PERSONAL USE';
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
            { title: "Issued At", field: "issued_at", width: 130, formatter: function(cell){
                const v = String(cell.getValue() || '');
                if (!v) return '';
                // Try to split date and time if possible
                const parts = v.split(/\s+/);
                if (parts.length >= 2) {
                    return `<div style=\"line-height:1.2;white-space:normal;\">${escapeHtml(parts[0])}<br>${escapeHtml(parts.slice(1).join(' '))}</div>`;
                }
                return `<div style=\"line-height:1.2;white-space:normal;\">${escapeHtml(v)}</div>`;
            } },
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

    const facilities = Object.values(grouped).sort(function(a, b) {
        const aPersonal = isPersonalFacility(a) ? 0 : 1;
        const bPersonal = isPersonalFacility(b) ? 0 : 1;
        if (aPersonal !== bPersonal) {
            return aPersonal - bPersonal;
        }
        const aName = String(a.facility_name || '').toLowerCase();
        const bName = String(b.facility_name || '').toLowerCase();
        return aName.localeCompare(bName);
    });
    
    // Render each facility group
    facilities.forEach(function(fac) {
        const isFacSelected = selectedFacility && String(selectedFacility.facility_id) === String(fac.facility_id);
        const facActive = isFacSelected ? 'active' : '';
        const isPersonal = isPersonalFacility(fac);
        const totalItems = fac.units.reduce(function(sum, u) { return sum + (parseInt(u.active_item_count) || 0); }, 0);
        const safeFacName = escapeHtml(fac.facility_name || 'Unknown Facility');
        const safeFacCode = escapeHtml(fac.facility_code || '');
        list.append(`
            <div class="facility-group">
                <div class="facility-header ${facActive} ${isPersonal ? 'personal' : ''}" data-facility-id="${fac.facility_id}">
                    <i class="bi ${isPersonal ? 'bi-box-seam' : 'bi-building'}"></i>${safeFacName}
                    ${isPersonal ? '<span class="badge bg-warning text-dark ms-1">SYSTEM</span>' : ''}
                    <span class="small" style="opacity:0.9;">(${safeFacCode}) &middot; ${totalItems} item(s)</span>
                    ${isPersonal ? '<span class="system-note">System-managed location for personal-use assignments.</span>' : ''}
                </div>
                <div class="facility-units" data-facility-id="${fac.facility_id}">
                    ${isFacSelected ? '' : '<div class="small-muted">Select facility to view units.</div>'}
                </div>
            </div>
        `);

        const unitsContainer = list.find(`.facility-units[data-facility-id="${fac.facility_id}"]`);
        if (!isFacSelected) {
            return;
        }
        const floors = {};
        fac.units.forEach(function(u) {
            if (!u || !u.unit_id) {
                return;
            }
            const floorKey = normalizeFloorKey(u.floor_label);
            if (!floors[floorKey]) floors[floorKey] = [];
            floors[floorKey].push(u);
        });

        const floorKeys = Object.keys(floors).sort(function(a, b) {
            if (a === 'Unassigned') return 1;
            if (b === 'Unassigned') return -1;
            return a.localeCompare(b);
        });
        if (!floorKeys.length) {
            unitsContainer.append('<div class="small-muted">No facility units. Click the facility header to view assignments.</div>');
            return;
        }
        const singleFloor = floorKeys.length === 1;

        const collapseMap = floorCollapseState[String(fac.facility_id)] || {};
        floorKeys.forEach(function(floorKey) {
            const isActiveFloor = !selectedUnit
                && selectedFacility
                && String(selectedFacility.facility_id) === String(fac.facility_id)
                && normalizeFloorKey(selectedFloor) === floorKey;
            let isCollapsed = Object.prototype.hasOwnProperty.call(collapseMap, floorKey) ? !!collapseMap[floorKey] : true;
            if (isActiveFloor) isCollapsed = false;
            if (singleFloor) isCollapsed = false;

            const floorData = encodeURIComponent(floorKey);
            unitsContainer.append(`
                <div class="floor-group" data-floor-key="${escapeHtml(floorKey)}">
                    <div class="floor-header ${isCollapsed ? 'collapsed' : ''} ${isActiveFloor ? 'active' : ''}" data-facility-id="${fac.facility_id}" data-floor="${floorData}">
                        <i class="bi bi-layers"></i> ${escapeHtml(floorKey)}
                        <span class="chevron"><i class="bi bi-chevron-down"></i></span>
                    </div>
                    <div class="floor-body ${isCollapsed ? 'd-none' : ''}"></div>
                </div>
            `);

            const floorBody = unitsContainer.find(`.floor-header[data-facility-id="${fac.facility_id}"][data-floor="${floorData}"]`).siblings('.floor-body');
            floors[floorKey].forEach(function(u) {
                const active = selectedUnit && String(selectedUnit.unit_id) === String(u.unit_id) ? 'active' : '';
                const safeUnitCode = escapeHtml(u.unit_code || '');
                const safeUnitName = escapeHtml(u.unit_name || '');
                floorBody.append(`
                    <div class="unit-card ${active}" data-id="${u.unit_id}">
                        <div class="fw-semibold">${safeUnitCode} - ${safeUnitName}</div>
                        <div class="small-muted" style="margin-top:4px;">
                            <i class="bi bi-box-seam"></i> ${u.active_item_count || 0} assigned item(s)
                        </div>
                    </div>
                `);
            });
        });
    });
}

function renderAssignments() {
    if (!assignmentTable) return;
    const q = ($('#assignSearch').val() || '').toLowerCase().trim();
    let rows = assignments;
    if (!selectedUnit && selectedFacility && selectedFloor) {
        const floor = normalizeFloorKey(selectedFloor);
        rows = (rows || []).filter(function(r) {
            return getRowFloorLabel(r) === floor;
        });
    }
    assignmentTable.setData(rows);
    if (q) {
        assignmentTable.setFilter(function(r) {
            const hay = [r.module_type, r.item_code, r.item_description, r.status,
                         r.issued_to_name, r.accountable_name, r.managed_by_name,
                         r.unit_name, r.unit_code, r.floor_label].join(' ').toLowerCase();
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
        const floorText = selectedUnit.floor_label ? ` - ${selectedUnit.floor_label}` : '';
        infoText = `${selectedUnit.facility_code || ''} / ${selectedUnit.unit_code || ''} - ${selectedUnit.unit_name || ''}${floorText}`;
    } else {
        postData = { action: 'list_facility_assignments', facility_id: selectedFacility.facility_id };
        if (isPersonalFacility(selectedFacility)) {
            infoText = `${selectedFacility.facility_name || 'For Personal use'} - Personal Assignments`;
        } else {
            infoText = `${selectedFacility.facility_name || ''} (${selectedFacility.facility_code || ''}) - ${selectedFloor ? ('Floor: ' + selectedFloor) : 'All Units'}`;
        }
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
            $('#selectedManagedBy').text(isPersonalFacility(selectedFacility) ? 'Managed By: System' : 'Managed By: Multiple');
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
        if (selectedFacility) {
            const stillManaged = managedUnits.find(function(u) { return String(u.facility_id) === String(selectedFacility.facility_id); });
            if (!stillManaged) {
                selectedFacility = null;
                selectedFloor = null;
            }
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
    if (!HAS_MANAGING_FACILITY_UNIT) {
        return;
    }

    initAssignmentTable();
    loadManagedUnits();

    $('#unitList').on('click', '.facility-header', function() {
        const facId = $(this).data('facility-id');
        const facUnit = managedUnits.find(function(u) { return String(u.facility_id) === String(facId); });
        if (!facUnit) return;

        if (selectedFacility && String(selectedFacility.facility_id) === String(facId) && !selectedUnit) {
            selectedFacility = null;
            selectedUnit = null;
            selectedFloor = null;
            assignments = [];
            $('#selectedUnitInfo').text('Select a facility or unit to view assignments.');
            $('#selectedManagedBy').text('');
            renderUnits();
            renderAssignments();
            return;
        }

        selectedUnit = null;
        selectedFloor = null;
        selectedFacility = { facility_id: facUnit.facility_id, facility_code: facUnit.facility_code, facility_name: facUnit.facility_name };
        $('#selectedManagedBy').text(isPersonalFacility(selectedFacility) ? 'Managed By: System' : 'Managed By: Multiple');
        renderUnits();
        loadAssignments();
    });

    $('#unitList').on('click', '.floor-header', function() {
        const facId = $(this).data('facility-id');
        const floor = normalizeFloorKey(decodeURIComponent($(this).data('floor') || ''));
        const facUnit = managedUnits.find(function(u) { return String(u.facility_id) === String(facId); });
        if (!facUnit) return;

        const facKey = String(facId);
        if (!floorCollapseState[facKey]) {
            floorCollapseState[facKey] = {};
        }
        const currentCollapsed = Object.prototype.hasOwnProperty.call(floorCollapseState[facKey], floor)
            ? !!floorCollapseState[facKey][floor]
            : true;
        const nextCollapsed = !currentCollapsed;
        floorCollapseState[facKey][floor] = nextCollapsed;

        selectedUnit = null;
        selectedFacility = { facility_id: facUnit.facility_id, facility_code: facUnit.facility_code, facility_name: facUnit.facility_name };
        selectedFloor = nextCollapsed ? null : floor;
        $('#selectedManagedBy').text(isPersonalFacility(selectedFacility) ? 'Managed By: System' : 'Managed By: Multiple');
        renderUnits();
        loadAssignments();
    });

    $('#unitList').on('click', '.unit-card', function() {
        const id = $(this).data('id');
        selectedUnit = managedUnits.find(function(u) { return String(u.unit_id) === String(id); }) || null;
        selectedFloor = null;
        if (selectedUnit) {
            selectedFacility = {
                facility_id: selectedUnit.facility_id,
                facility_code: selectedUnit.facility_code,
                facility_name: selectedUnit.facility_name
            };
        }
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
                             r.issued_to_name, r.accountable_name, r.managed_by_name,
                             r.unit_name, r.unit_code, r.floor_label].join(' ').toLowerCase();
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


