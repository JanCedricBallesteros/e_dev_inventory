<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("PO", "AST", "CSM"));
if (!(
    role_has("SUPER_ADMIN") ||
    role_has("ADMIN") ||
    $staffAccess
)) {
    header("Location: " . BASE_URL);
    exit();
}

$type = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
if (!in_array($type, ['AST', 'CSM'], true)) {
    if (user_has_access('AST') && !user_has_access('CSM')) {
        $type = 'AST';
    } elseif (user_has_access('CSM') && !user_has_access('AST')) {
        $type = 'CSM';
    } else {
        $type = 'AST';
    }
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
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const REQ_TYPE = <?php echo json_encode($type); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/transactions/requisition_process.php';
let reqTable = null;

function showReqMessage(msg) {
    const el = $('#reqMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
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
            { title: 'ID', field: 'requisition_id', width: 80 },
            { title: 'Requester', field: 'requester_name', width: 200 },
            { title: 'Employment', field: 'employment_status', width: 140 },
            { title: 'Item Code', field: 'item_code', width: 140 },
            { title: 'Description', field: 'item_description', widthGrow: 2 },
            { title: 'Qty', field: 'qty_requested', width: 70, hozAlign: 'center' },
            { title: 'Status', field: 'status', width: 120 },
            { title: 'Date Modified', field: 'created_at', width: 140 },
            { title: 'Actions', field: 'requisition_id', width: 180, hozAlign: 'center', formatter: function(cell){
                const id = cell.getValue();
                return `
                    <button class="btn btn-sm btn-success me-1 btn-approve" data-id="${id}">Approve</button>
                    <button class="btn btn-sm btn-danger btn-disapprove" data-id="${id}">Disapprove</button>
                `;
            }}
        ]
    });

    $('#reqTable').on('click', '.btn-approve', function() {
        const id = $(this).data('id');
        if (!confirm('Approve this requisition?')) return;
        $.post(PROCESS_URL, { action: 'approve_requisition', requisition_id: id }, function(res) {
            if (res && res.success) {
                loadReqData();
            } else {
                alert(res.message || 'Approval failed.');
            }
        }, 'json').fail(function() {
            alert('Server error while approving.');
        });
    });

    $('#reqTable').on('click', '.btn-disapprove', function() {
        const id = $(this).data('id');
        const reason = prompt('Reason for disapproval:');
        if (reason === null) return;
        $.post(PROCESS_URL, { action: 'disapprove_requisition', requisition_id: id, reason: reason }, function(res) {
            if (res && res.success) {
                loadReqData();
            } else {
                alert(res.message || 'Disapproval failed.');
            }
        }, 'json').fail(function() {
            alert('Server error while disapproving.');
        });
    });
}

$(document).ready(function() {
    initReqTable();
    $('#reqRefresh').on('click', loadReqData);
    $('#reqSearch').on('keyup', loadReqData);
    $('#reqStatus').on('change', loadReqData);
});
</script>
</html>
