<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("AST", "CSM"));
if (!(
    role_has("ADMIN") ||
    $staffAccess
)) {
    header("Location: " . BASE_URL);
    exit();
}

$type = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
$canAST = role_has("ADMIN") || user_has_access('AST');
$canCSM = role_has("ADMIN") || user_has_access('CSM');

if (!in_array($type, ['AST', 'CSM'], true)) {
    if ($canAST && !$canCSM) {
        $type = 'AST';
    } elseif ($canCSM && !$canAST) {
        $type = 'CSM';
    } elseif ($canAST) {
        $type = 'AST';
    } elseif ($canCSM) {
        $type = 'CSM';
    } else {
        header("Location: " . BASE_URL);
        exit();
    }
}

if (($type === 'AST' && !$canAST) || ($type === 'CSM' && !$canCSM)) {
    if ($canAST) {
        header("Location: " . BASE_URL . "admin/modules/transactions/requisition.php?type=AST");
    } elseif ($canCSM) {
        header("Location: " . BASE_URL . "admin/modules/transactions/requisition.php?type=CSM");
    } else {
        header("Location: " . BASE_URL);
    }
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
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .tab-pill { border-radius: 999px; }
        .muted { color: #6c757d; }
        .status-badge { padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.8rem; }
        .item-thumb { width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
        .item-badge { width: 50px; height: 50px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #1E3A8A; color: #fff; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; border: 1px solid rgba(0,0,0,0.06); cursor: default; }
        .thumb-wrap { display: flex; align-items: center; justify-content: center; }
        .img-preview { max-width: 100%; max-height: 70vh; border-radius: 8px; }
        .two-line-cell {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
        }
        .three-line-cell {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
        }
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
            <h1 class="h4 fw-semibold mb-1">Requisition Approval</h1>
            <p class="text-muted small mb-0">Review, approve, or disapprove requests. Mode: <?php echo htmlspecialchars($type); ?></p>
        </div>

        <section class="section">
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-clipboard-check"></i>&ensp;Requisition Queue</span>
                    <div class="btn-group" role="group" aria-label="Module tabs">
                        <a class="btn btn-light btn-sm tab-pill <?php echo $type === 'AST' ? 'active' : ''; ?>"
                           href="<?php echo BASE_URL; ?>admin/modules/transactions/requisition.php?type=AST">
                            AST
                        </a>
                        <a class="btn btn-light btn-sm tab-pill <?php echo $type === 'CSM' ? 'active' : ''; ?>"
                           href="<?php echo BASE_URL; ?>admin/modules/transactions/requisition.php?type=CSM">
                            CSM
                        </a>
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div id="reqMsg" class="alert alert-danger d-none mb-3"></div>
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <input type="text" id="reqSearch" class="form-control" placeholder="Search requests..." style="max-width:280px;">
                        <select id="reqStatus" class="form-select" style="max-width:180px;">
                            <option value="">Status: All</option>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="approved">Approved</option>
                            <option value="for_claiming">For Claiming</option>
                            <option value="claimed">Claimed</option>
                            <option value="not_claimed">Not Claimed</option>
                            <option value="disapproved">Disapproved</option>
                        </select>
                        <button class="btn btn-outline-secondary" id="reqRefresh">Refresh</button>
                    </div>
                    <div id="reqTable"></div>
                </div>
            </div>
        </section>
    </main>

<?php include_once FOOTER_PATH; ?>

<!-- Image Preview Modal -->
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

<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approveReqId">
                <p class="mb-0">Approve this requisition?</p>
                <div id="approveMsg" class="alert alert-danger d-none mt-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm" id="btnConfirmApprove">Confirm Approve</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="disapproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Disapprove Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="disapproveReqId">
                <label class="form-label fw-semibold">Reason</label>
                <textarea id="disapproveReason" class="form-control" rows="3" placeholder="Provide reason..."></textarea>
                <div id="disapproveMsg" class="alert alert-danger d-none mt-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" id="btnConfirmDisapprove">Confirm Disapprove</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Claim Requisition To Facility Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="claimReqId">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Facility</label>
                        <select id="claimFacilityId" class="form-select"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Unit</label>
                        <select id="claimUnitId" class="form-select"></select>
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Issued To</label>
                        <input type="text" id="claimIssuedToName" class="form-control" readonly>
                        <input type="hidden" id="claimIssuedToUserId">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Facility Unit Manager</label>
                        <input type="text" id="claimManagedByName" class="form-control" readonly>
                        <input type="hidden" id="claimManagedByUserId">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input type="text" id="claimRemarks" class="form-control" placeholder="Optional">
                </div>
                <div id="claimMsg" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary btn-sm" id="btnConfirmClaim">Confirm Claim</button>
            </div>
        </div>
    </div>
</div>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const REQ_TYPE = <?php echo json_encode($type); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/transactions/requisition_process.php';
let reqTable = null;
let claimFacilities = [];
let claimUnits = [];

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function twoLineText(value, fallback) {
    const raw = (value === null || value === undefined || value === '') ? (fallback || '-') : String(value);
    const safe = escapeHtml(raw);
    return `<span class="two-line-cell" title="${safe}">${safe}</span>`;
}

function threeLineText(value, fallback) {
    const raw = (value === null || value === undefined || value === '') ? (fallback || '-') : String(value);
    const safe = escapeHtml(raw);
    return `<span class="three-line-cell" title="${safe}">${safe}</span>`;
}

function statusBadge(raw) {
    const s = String(raw || '').toLowerCase();
    const map = {
        'pending': 'bg-warning text-dark',
        'reviewed': 'bg-info text-dark',
        'approved': 'bg-primary text-white',
        'for_claiming': 'bg-primary text-white',
        'claimed': 'bg-success text-white',
        'not_claimed': 'bg-secondary text-white',
        'disapproved': 'bg-danger text-white'
    };
    const cls = map[s] || 'bg-light text-dark border';
    return `<span class="status-badge ${cls}">${escapeHtml(s.replace('_', ' '))}</span>`;
}

function showReqMessage(msg) {
    const el = $('#reqMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function notifyMsg(type, msg) {
    const el = $('#reqMsg');
    if (!msg) {
        el.addClass('d-none').text('');
        return;
    }
    el.removeClass('d-none alert-danger alert-success alert-warning alert-info');
    const cls = type ? ('alert-' + type) : 'alert-info';
    el.addClass(cls).text(msg);
}

if (typeof window.success_notif !== 'function') {
    window.success_notif = function(msg) { notifyMsg('success', msg); };
}
if (typeof window.error_notif !== 'function') {
    window.error_notif = function(msg) { notifyMsg('danger', msg); };
}

function loadClaimLookups() {
    $.post(PROCESS_URL, { action: 'list_facilities' }, function(res){
        if (res && res.success) {
            claimFacilities = res.data || [];
            const $f = $('#claimFacilityId');
            $f.empty().append('<option value="">Select facility</option>');
            claimFacilities.forEach(function(f){
                $f.append(`<option value="${f.facility_id}">${f.facility_code} - ${f.facility_name}</option>`);
            });
        }
    }, 'json');
}

function loadClaimUnits(facilityId, selectedUnitId) {
    const $u = $('#claimUnitId');
    claimUnits = [];
    $u.empty().append('<option value="">Select unit</option>');
    if (!facilityId) return;
    $.post(PROCESS_URL, { action: 'list_units', facility_id: facilityId }, function(res){
        if (!(res && res.success)) return;
        claimUnits = res.data || [];
        claimUnits.forEach(function(u){
            $u.append(`<option value="${u.unit_id}">${u.unit_code} - ${u.unit_name}</option>`);
        });
        if (selectedUnitId) {
            $u.val(String(selectedUnitId));
        }
        const picked = claimUnits.find(function(u){ return String(u.unit_id) === String($u.val() || ''); });
        if (picked && picked.facility_unit_manager_user_id) {
            $('#claimManagedByUserId').val(String(picked.facility_unit_manager_user_id));
            $('#claimManagedByName').val(picked.unit_manager_name || '');
        } else {
            $('#claimManagedByUserId').val('');
            $('#claimManagedByName').val('');
        }
    }, 'json');
}

function loadReqData() {
    const search = ($('#reqSearch').val() || '').trim();
    const status = $('#reqStatus').val() || '';
    if (!reqTable) return;
    reqTable.setData(PROCESS_URL, { action: 'list_requisitions', type: REQ_TYPE, search, status }, 'POST');
}

function initReqTable() {
    reqTable = new Tabulator('#reqTable', {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_requisitions', type: REQ_TYPE },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No requisitions found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        ajaxResponse: function(url, params, response) {
            if (response && response.success === false) {
                showReqMessage(response.message || 'Failed to load requisitions.');
                return [];
            }
            showReqMessage('');
            return response.data || [];
        },
        columns: [
            { title: 'Image', field: 'category_photo_thumb_url', width: 62, hozAlign: 'center', headerSort: false, formatter: function(cell){
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || cell.getRow().getData().item_description || '';
                if (url) {
                    return `<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="${url}" data-full="${escapeHtml(full || url)}" loading="lazy" alt="Item image"></div>`;
                }
                const initials = (String(name).trim().split(/\s+/).map(function(w){ return w.charAt(0); }).filter(Boolean).slice(0,2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge" title="${escapeHtml(name)}">${escapeHtml(initials)}</div></div>`;
            }},
            { title: 'ID', field: 'requisition_id', width: 60, hozAlign: 'center' },
            { title: 'Requester', field: 'requester_name', widthGrow: 1, minWidth: 150, headerFilter: 'input', formatter: function(cell){
                return twoLineText(cell.getValue());
            }},
            { title: 'Employment', field: 'employment_status', width: 155, headerFilter: 'select', headerFilterParams: { values: { '': 'All', 'teaching': 'Teaching', 'non_teaching': 'Non-Teaching' } }, headerFilterFunc: function(headerValue, rowValue, rowData) { if (!headerValue) return true; return String(rowData.position_category || '').toLowerCase() === headerValue; }, formatter: function(cell){
                const row = cell.getRow().getData();
                const status = escapeHtml(cell.getValue() || '-');
                const cat = String(row.position_category || '').toLowerCase();
                let label = '';
                if (cat === 'teaching') {
                    label = 'Teaching';
                } else if (cat === 'non_teaching') {
                    label = 'Non-Teaching';
                }
                const sub = label ? `<div class="text-muted small">${label}</div>` : '';
                return `<div style="line-height:1.3;white-space:normal;">${status}${sub}</div>`;
            }},
            { title: 'Item Code', field: 'item_code', width: 125, headerFilter: 'input', formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? `<span class="badge bg-light text-dark border">${v}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, headerFilter: 'input', formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: 'Qty', field: 'qty_requested', width: 55, hozAlign: 'center' },
            { title: 'Workflow', field: 'workflow_status', width: 120, headerFilter: 'select', headerFilterParams: { values: { '': 'All', 'pending': 'Pending', 'reviewed': 'Reviewed', 'approved': 'Approved', 'for_claiming': 'For Claiming', 'claimed': 'Claimed', 'not_claimed': 'Not Claimed', 'disapproved': 'Disapproved' } }, formatter: function(cell){
                return statusBadge(cell.getValue());
            }},
            { title: 'Updated', field: 'updated_at', width: 130, formatter: function(cell){
                const v = String(cell.getValue() || '');
                if (!v) return '-';
                const parts = v.split(/\s+/);
                if (parts.length >= 2) {
                    return `<div style="line-height:1.2;white-space:normal;">${escapeHtml(parts[0])}<br><span class="text-muted">${escapeHtml(parts.slice(1).join(' '))}</span></div>`;
                }
                return escapeHtml(v);
            }},
            { title: 'Remarks', field: 'remarks', widthGrow: 1, minWidth: 130, formatter: function(cell){
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Actions', field: 'requisition_id', width: 210, hozAlign: 'center', formatter: function(cell){
                const id = cell.getValue();
                const data = cell.getRow().getData();
                const wf = String(data.workflow_status || '').toLowerCase();
                if (wf === 'claimed') {
                    return `<span class="badge bg-success">Claimed</span>`;
                }
                if (wf === 'disapproved') {
                    return `<span class="badge bg-danger">Disapproved</span>`;
                }
                if (wf === 'not_claimed') {
                    return `<span class="badge bg-secondary">Not Claimed</span>`;
                }
                const canApprove = wf === 'pending' || wf === 'reviewed';
                const canClaim = wf === 'for_claiming' || wf === 'approved';
                return `
                    <button class="btn btn-sm btn-success me-1 btn-approve" data-id="${id}" ${canApprove ? '' : 'disabled'}>Approve</button>
                    <button class="btn btn-sm btn-danger btn-disapprove" data-id="${id}" ${canApprove ? '' : 'disabled'}>Disapprove</button>
                    ${canClaim ? `<button class="btn btn-sm btn-primary mt-1 btn-claim" data-id="${id}">Claimed</button>` : ''}
                `;
            }}
        ]
    });

    $('#reqTable').on('click', '.btn-approve', function() {
        const id = $(this).data('id');
        $('#approveReqId').val(id || '');
        $('#approveMsg').addClass('d-none').text('');
        $('#approveModal').modal('show');
    });

    $('#btnConfirmApprove').on('click', function() {
        const id = $('#approveReqId').val();
        $.post(PROCESS_URL, { action: 'approve_requisition', requisition_id: id }, function(res) {
            if (res && res.success) {
                $('#approveModal').modal('hide');
                loadReqData();
                success_notif(res.message || 'Requisition approved.');
            } else {
                $('#approveMsg').removeClass('d-none').text(res.message || 'Approval failed.');
            }
        }, 'json').fail(function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while approving.';
            $('#approveMsg').removeClass('d-none').text(msg);
        });
    });

    $('#reqTable').on('click', '.btn-disapprove', function() {
        const id = $(this).data('id');
        $('#disapproveReqId').val(id || '');
        $('#disapproveReason').val('');
        $('#disapproveMsg').addClass('d-none').text('');
        $('#disapproveModal').modal('show');
    });

    $('#btnConfirmDisapprove').on('click', function() {
        const id = $('#disapproveReqId').val();
        const reason = ($('#disapproveReason').val() || '').trim();
        if (!reason) {
            $('#disapproveMsg').removeClass('d-none').text('Reason is required.');
            return;
        }
        $.post(PROCESS_URL, { action: 'disapprove_requisition', requisition_id: id, reason: reason }, function(res) {
            if (res && res.success) {
                $('#disapproveModal').modal('hide');
                loadReqData();
                success_notif(res.message || 'Requisition disapproved.');
            } else {
                $('#disapproveMsg').removeClass('d-none').text(res.message || 'Disapproval failed.');
            }
        }, 'json').fail(function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while disapproving.';
            $('#disapproveMsg').removeClass('d-none').text(msg);
        });
    });

    $('#reqTable').on('click', '.btn-claim', function() {
        const id = $(this).data('id');
        const row = reqTable.getRows().find(r => String(r.getData().requisition_id) === String(id));
        const data = row ? row.getData() : null;
        if (!data) {
            error_notif('Requisition row not found.');
            return;
        }
        $('#claimReqId').val(data.requisition_id || '');
        const reqFacilityId = data.claim_facility_id || '';
        const reqUnitId = data.claim_unit_id || '';
        const hasPresetUnit = String(reqFacilityId) !== '' && String(reqUnitId) !== '';
        $('#claimFacilityId').val(reqFacilityId);
        $('#claimUnitId').empty().append('<option value="">Select unit</option>');
        $('#claimFacilityId').prop('disabled', hasPresetUnit);
        $('#claimUnitId').prop('disabled', hasPresetUnit);
        if (reqFacilityId) {
            loadClaimUnits(reqFacilityId, reqUnitId);
        }
        $('#claimRemarks').val('');
        $('#claimIssuedToUserId').val(String(data.requester_user_id || ''));
        $('#claimIssuedToName').val(data.requester_name || '');
        $('#claimManagedByUserId').val('');
        $('#claimManagedByName').val('');
        $('#claimMsg').html('');
        $('#claimModal').modal('show');
    });

    $('#claimFacilityId').on('change', function(){
        const facilityId = $(this).val();
        loadClaimUnits(facilityId);
    });

    $('#claimUnitId').on('change', function(){
        const unitId = $(this).val();
        const picked = claimUnits.find(function(u){ return String(u.unit_id) === String(unitId || ''); });
        if (picked && picked.facility_unit_manager_user_id) {
            $('#claimManagedByUserId').val(String(picked.facility_unit_manager_user_id));
            $('#claimManagedByName').val(picked.unit_manager_name || '');
        } else {
            $('#claimManagedByUserId').val('');
            $('#claimManagedByName').val('');
        }
    });

    $('#btnConfirmClaim').on('click', function(){
        const payload = {
            action: 'claim_requisition',
            requisition_id: $('#claimReqId').val(),
            facility_id: $('#claimFacilityId').val(),
            unit_id: $('#claimUnitId').val(),
            issued_to_user_id: $('#claimIssuedToUserId').val(),
            managed_by_user_id: $('#claimManagedByUserId').val(),
            remarks: ($('#claimRemarks').val() || '').trim()
        };
        $.post(PROCESS_URL, payload, function(res){
            if (!(res && res.success)) {
                $('#claimMsg').html('<div class="alert alert-danger mb-0">' + ((res && res.message) || 'Claim failed.') + '</div>');
                return;
            }
            $('#claimModal').modal('hide');
            loadReqData();
            success_notif(res.message || 'Requisition claimed.');
        }, 'json').fail(function(xhr){
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while claiming.';
            $('#claimMsg').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
        });
    });
}

$(document).ready(function() {
    loadClaimLookups();
    initReqTable();
    $('#reqRefresh').on('click', loadReqData);
    $('#reqSearch').on('keyup', loadReqData);
    $('#reqStatus').on('change', loadReqData);

    $('body').on('click', '.js-thumb-preview', function() {
        const full = $(this).data('full') || $(this).attr('data-full');
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });
});
</script>
</body>
</html>
