<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access("AST");
if (!(
    role_has("ADMIN") ||
    $staffAccess
)) {
    header("Location: " . BASE_URL);
    exit();
}

// Your page logic here
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold">
                    <i class="bi bi-journal-check"></i>&ensp;Manage Returns
                </div>
                <div class="card-body mt-3 bg-white">
                    <div id="pageMsg" class="alert alert-danger d-none"></div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <select id="returnFilter" class="form-select form-select-sm" style="max-width: 240px;">
                            <option value="PENDING">Pending Return Requests</option>
                            <option value="PROCESSED">Processed Returns</option>
                            <option value="ALL">All</option>
                        </select>
                        <button class="btn btn-outline-secondary btn-sm" id="btnRefresh">Refresh</button>
                    </div>
                    <div id="returnsTable"></div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script>
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'admin/modules/transactions/facility_inventory_records_process.php'); ?>;
let returnsTable = null;
let decisionTargetId = 0;

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

function statusBadge(status){
    const s = String(status || '').toUpperCase();
    const cls = s === 'RETURN_REQUESTED' ? 'bg-warning text-dark' : (s === 'RETURNED' ? 'bg-success' : 'bg-secondary');
    return '<span class="badge ' + cls + '">' + s + '</span>';
}

function escapeHtml(value){
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function parseReasonImagePath(remarks){
    const txt = String(remarks || '');
    const m = txt.match(/\[reason_image:([^\]]+)\]/i);
    return m ? m[1].trim() : '';
}

function cleanRemarksText(remarks){
    return String(remarks || '')
        .replace(/\[reason_image:[^\]]+\]/gi, '')
        .replace(/\[admin_decision:[^\]]+\]/gi, '')
        .trim();
}

function initTable(){
    returnsTable = new Tabulator('#returnsTable', {
        data: [],
        layout: 'fitColumns',
        placeholder: 'No return requests found.',
        pagination: true,
        paginationSize: 10,
        columns: [
            { title: 'ID', field: 'assignment_id', width: 80, hozAlign: 'right' },
            { title: 'Item', field: 'item_code', minWidth: 170 },
            { title: 'Description', field: 'item_description', minWidth: 220 },
            { title: 'Module', field: 'module_type', width: 90 },
            { title: 'Qty', field: 'qty', width: 80, hozAlign: 'right' },
            { title: 'Facility / Unit', field: 'facility_name', minWidth: 180, formatter: function(cell){
                const d = cell.getRow().getData();
                return (d.facility_name || '-') + '<br><small class="text-muted">' + (d.unit_name || '-') + '</small>';
            }},
            { title: 'Requested By', field: 'requested_by_name', minWidth: 150 },
            { title: 'Reason', field: 'remarks', minWidth: 220, formatter: function(cell){
                const d = cell.getRow().getData();
                const reasonText = cleanRemarksText(d.remarks);
                const imgPath = parseReasonImagePath(d.remarks);
                const reason = reasonText ? escapeHtml(reasonText) : '<span class="text-muted">No remarks</span>';
                const imgBtn = imgPath ? '<button class="btn btn-outline-secondary btn-sm ms-1 js-view-reason-image">View Image</button>' : '';
                return '<div>' + reason + '</div><div class="mt-1">' + imgBtn + '</div>';
            }},
            { title: 'Status', field: 'status', width: 160, formatter: function(cell){ return statusBadge(cell.getValue()); } },
            { title: 'Actions', field: 'actions', width: 220, hozAlign: 'center', formatter: function(cell){
                const d = cell.getRow().getData();
                if (String(d.status).toUpperCase() !== 'RETURN_REQUESTED') return '-';
                return '<div class="d-flex gap-1 justify-content-center">' +
                    '<button class="btn btn-success btn-sm js-approve">Approve</button>' +
                    '<button class="btn btn-outline-danger btn-sm js-reject">Reject</button>' +
                    '</div>';
            }, cellClick: function(e, cell){
                const d = cell.getRow().getData();
                if ($(e.target).hasClass('js-view-reason-image')) {
                    const imgPath = parseReasonImagePath(d.remarks);
                    if (imgPath) {
                        $('#reasonImagePreview').attr('src', <?= json_encode(BASE_URL) ?> + imgPath);
                        $('#reasonImageModal').modal('show');
                    }
                    return;
                }
                if ($(e.target).hasClass('js-approve')) {
                    openDecisionModal(d.assignment_id, 'APPROVE');
                } else if ($(e.target).hasClass('js-reject')) {
                    openDecisionModal(d.assignment_id, 'REJECT');
                }
            }}
        ]
    });
}

function loadReturns(){
    const status = $('#returnFilter').val() || 'PENDING';
    $.post(PROCESS_URL, { action: 'list_return_requests', status: status }, function(resp){
        if (!resp || !resp.success) {
            notifyError((resp && resp.message) ? resp.message : 'Failed to load return requests.');
            return;
        }
        returnsTable.setData(resp.data || []);
    }, 'json').fail(function(){
        notifyError('Failed to load return requests.');
    });
}

function openDecisionModal(assignmentId, decision){
    decisionTargetId = assignmentId;
    $('#decisionType').val(decision);
    $('#decisionRemarks').val('');
    $('#decisionTitle').text(decision === 'APPROVE' ? 'Approve Return Request' : 'Reject Return Request');
    $('#decisionModal').modal('show');
}

function processRequest(){
    const assignmentId = decisionTargetId;
    const decision = ($('#decisionType').val() || '').toUpperCase();
    if (!assignmentId || !decision) return;
    $.post(PROCESS_URL, {
        action: 'process_return_request',
        assignment_id: assignmentId,
        decision: decision,
        remarks: ($('#decisionRemarks').val() || '').trim()
    }, function(resp){
        if (!resp || !resp.success) {
            notifyError((resp && resp.message) ? resp.message : 'Failed to process request.');
            return;
        }
        $('#decisionModal').modal('hide');
        notifySuccess(resp.message || 'Request processed.');
        loadReturns();
    }, 'json').fail(function(){
        notifyError('Failed to process request.');
    });
}

$(function(){
    initTable();
    loadReturns();
    $('#btnRefresh').on('click', loadReturns);
    $('#returnFilter').on('change', loadReturns);
    $('#confirmDecisionBtn').on('click', processRequest);
});
</script>
<div class="modal fade" id="decisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="decisionTitle">Process Return Request</h5>
                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="decisionType" value="">
                <label class="form-label fw-semibold">Decision Remarks (optional)</label>
                <textarea id="decisionRemarks" class="form-control" rows="3" placeholder="Add processing notes..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm" id="confirmDecisionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="reasonImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reason Image</h5>
                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="reasonImagePreview" src="" alt="Reason image" style="max-width:100%;max-height:70vh;border-radius:8px;">
            </div>
        </div>
    </div>
</div>
</html>
