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
        .small-muted { color: #6c757d; font-size: 0.9rem; }
        .status-badge { padding: 0.25rem 0.55rem; border-radius: 999px; font-size: 0.8rem; }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 class="h4 fw-semibold mb-1">Personal Inventory Records</h1>
            <p class="text-muted small mb-0">Items issued to you or where you are accountable.</p>
        </div>

        <section class="section">
            <div id="pageMsg" class="alert alert-danger d-none"></div>
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <span><i class="bi bi-person-lines-fill"></i>&ensp;My Assignments</span>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select id="statusFilter" class="form-select form-select-sm" style="width: 180px;">
                            <option value="">All Statuses</option>
                            <option value="ACTIVE">Active</option>
                            <option value="REPORTED">Reported</option>
                            <option value="RETURN_REQUESTED">Return Requested</option>
                            <option value="RETURNED">Returned</option>
                        </select>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search code, description, status..." style="width: 260px;">
                        <button class="btn btn-light btn-sm" id="refreshBtn">Refresh</button>
                    </div>
                </div>
                <div class="card-body bg-white mt-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" id="personalTable">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Item Code</th>
                                    <th>Description</th>
                                    <th class="text-center" style="width:90px;">Qty</th>
                                    <th>Location</th>
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
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

    <div class="modal fade" id="statusActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusActionTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" id="statusModalCloseX" data-dismiss="modal" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="statusActionText"></p>
                    <label for="statusActionRemarks" class="form-label fw-semibold mb-1">Remarks (optional)</label>
                    <textarea id="statusActionRemarks" class="form-control" rows="3" placeholder="Add context for this request..."></textarea>
                    <label for="statusActionImage" class="form-label fw-semibold mb-1 mt-3">Reason Image (optional)</label>
                    <input type="file" id="statusActionImage" class="form-control" accept="image/*">
                    <div class="form-text">This will be visible to admins handling your request.</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" id="statusModalCancelBtn" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary btn-sm" id="confirmStatusActionBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'users/modules/facility_records_process.php'); ?>;
let myAssignments = [];
let pendingAction = null;
let statusActionModal = null;

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

function renderTable() {
    const tbody = $('#personalTable tbody');
    tbody.empty();
    const q = ($('#searchInput').val() || '').toLowerCase().trim();
    const filtered = myAssignments.filter(function(a) {
        if (!q) return true;
        const hay = [a.module_type, a.item_code, a.item_description, a.status, a.facility_code, a.unit_code].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    });
    if (!filtered.length) {
        tbody.html('<tr><td colspan="8" class="text-muted text-center">No records found.</td></tr>');
        return;
    }
    filtered.forEach(function(a) {
        const currentStatus = String(a.status || '').toUpperCase();
        const lockReturn = currentStatus === 'RETURNED' || currentStatus === 'RETURN_REQUESTED';
        const lockReport = currentStatus === 'RETURNED';
        const loc = [a.facility_code || '', a.unit_code || ''].filter(Boolean).join(' / ');
        tbody.append(`
            <tr>
                <td>${a.module_type || ''}</td>
                <td><span class="badge bg-light text-dark border">${a.item_code || ''}</span></td>
                <td>${a.item_description || ''}</td>
                <td class="text-center">${a.qty || ''}</td>
                <td>${loc || '-'}</td>
                <td>${statusBadge(a.status)}</td>
                <td>${a.issued_at || ''}</td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-outline-warning btn-sm btn-report" data-id="${a.assignment_id}" ${lockReport ? 'disabled' : ''}>Report</button>
                        <button class="btn btn-outline-primary btn-sm btn-return" data-id="${a.assignment_id}" ${lockReturn ? 'disabled' : ''}>Return Request</button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function loadAssignments() {
    const status = $('#statusFilter').val() || '';
    $.post(PROCESS_URL, { action: 'list_my_assignments', status: status }, function(res) {
        if (!res || res.success !== true) {
            togglePageMsg((res && res.message) || 'Failed to load records.');
            myAssignments = [];
            renderTable();
            return;
        }
        togglePageMsg('');
        myAssignments = res.data || [];
        renderTable();
    }, 'json').fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while loading records.';
        togglePageMsg(msg);
        myAssignments = [];
        renderTable();
    });
}

function submitStatusAction() {
    if (!pendingAction || !pendingAction.assignmentId || !pendingAction.status) return;
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('assignment_id', pendingAction.assignmentId);
    fd.append('status', pendingAction.status);
    fd.append('remarks', ($('#statusActionRemarks').val() || '').trim());
    const imgInput = document.getElementById('statusActionImage');
    if (imgInput && imgInput.files && imgInput.files[0]) {
        fd.append('reason_image', imgInput.files[0]);
    }

    $.ajax({
        url: PROCESS_URL,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function(res) {
        if (!res || res.success !== true) {
            togglePageMsg((res && res.message) || 'Failed to update status.');
            return;
        }
        if (statusActionModal) { $('#statusActionModal').modal('hide'); }
        togglePageMsg('');
        loadAssignments();
    }).fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while updating status.';
        togglePageMsg(msg);
    });
}

function openStatusActionModal(assignmentId, status, label) {
    if (!assignmentId) return;
    pendingAction = { assignmentId: assignmentId, status: status, label: label };
    $('#statusActionTitle').text('Confirm ' + label);
    $('#statusActionText').text('Are you sure you want to set this item as ' + label.toLowerCase() + '?');
    $('#statusActionRemarks').val('');
    $('#statusActionImage').val('');
    $('#statusActionModal').modal('show');
}

$(document).ready(function() {
    statusActionModal = $('#statusActionModal');
    loadAssignments();

    $('#refreshBtn').on('click', loadAssignments);
    $('#statusFilter').on('change', loadAssignments);
    $('#searchInput').on('input', renderTable);
    $('#confirmStatusActionBtn').on('click', submitStatusAction);
    $('#statusModalCloseX, #statusModalCancelBtn').on('click', function(){
        $('#statusActionModal').modal('hide');
    });

    $('#personalTable').on('click', '.btn-report', function() {
        const id = $(this).data('id');
        openStatusActionModal(id, 'REPORTED', 'Report');
    });

    $('#personalTable').on('click', '.btn-return', function() {
        const id = $(this).data('id');
        openStatusActionModal(id, 'RETURN_REQUESTED', 'Return Request');
    });
});
</script>
</html>


