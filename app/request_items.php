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
</head>

<body class="d-flex flex-column h-100">
    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 class="h4 fw-semibold mb-1">Request Items</h1>
            <p class="text-muted small mb-0">Browse available items and submit a requisition.</p>
        </div>

        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-bag-plus"></i>&ensp;Available Items</span>
                    <div class="btn-group" role="group" aria-label="Module tabs">
                        <button class="btn btn-light btn-sm tab-pill active" data-type="AST">AST</button>
                        <button class="btn btn-light btn-sm tab-pill" data-type="CSM">CSM</button>
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div id="reqMsg" class="alert alert-danger d-none mb-3"></div>
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <input type="text" id="itemSearch" class="form-control" placeholder="Search item code or description..." style="max-width:280px;">
                        <button class="btn btn-outline-secondary" id="refreshItems">Refresh</button>
                    </div>
                    <div id="itemsTable"></div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-bag-plus"></i>&ensp;Request Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <div class="small text-muted">Item Code</div>
                        <div class="fw-semibold" id="reqItemCode">-</div>
                    </div>
                    <div class="mb-2">
                        <div class="small text-muted">Description</div>
                        <div id="reqItemDesc">-</div>
                    </div>
                    <div class="mb-2">
                        <div class="small text-muted" id="reqAvailLabel">Availability</div>
                        <div><span id="reqItemAvail">0</span> <span id="reqItemUnit"></span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="reqQtyLabel">Quantity to request</label>
                        <input type="number" class="form-control" id="reqQtyInput" min="1" value="1">
                        <div class="small text-muted mt-1" id="reqQtyHelp">Must be exactly 1 for AST requests.</div>
                    </div>
                    <div id="reqModalMsg" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitRequest">Submit Request</button>
                </div>
            </div>
        </div>
    </div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'app/request_items_process.php';
let currentType = 'AST';
let table = null;
let reqSelected = null;

function showReqMessage(msg) {
    const el = $('#reqMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function loadItems() {
    const search = ($('#itemSearch').val() || '').trim();
    if (!table) return;
    table.setData(PROCESS_URL, { action: 'list_available_items', type: currentType, search }, 'POST');
}

function syncRequestQtyUI() {
    if (currentType === 'AST') {
        $('#reqAvailLabel').text('Availability');
        $('#reqQtyLabel').text('Quantity to request (fixed)');
        $('#reqQtyHelp').text('AST uses one property code per unit. Quantity is always 1.');
        $('#reqQtyInput').val(1).prop('readonly', true);
        return;
    }
    $('#reqAvailLabel').text('Available Qty');
    $('#reqQtyLabel').text('Quantity to request');
    $('#reqQtyHelp').text('Must not exceed available quantity.');
    $('#reqQtyInput').prop('readonly', false);
}

    function initTable() {
        table = new Tabulator('#itemsTable', {
            ajaxURL: PROCESS_URL,
            ajaxParams: { action: 'list_available_items', type: currentType },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No available items found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        ajaxResponse: function(url, params, response) {
            if (response && response.success === false) {
                showReqMessage(response.message || 'Failed to load items.');
                return [];
            }
            showReqMessage('');
            return response.data || [];
        },
        columns: [
            { title: 'Item Code', field: 'item_code', width: 160 },
            { title: 'Description', field: 'item_description', widthGrow: 2 },
            { title: 'Stock', field: 'available_qty', width: 110, hozAlign: 'center', formatter: function(cell){
                if (currentType === 'AST') return 'Available';
                const v = parseInt(cell.getValue(), 10);
                return Number.isFinite(v) ? v : 0;
            }},
            { title: 'Unit', field: 'unit', width: 90 },
            { title: 'Allowed Status', field: 'allowed_status_names', width: 180, formatter: function(cell){
                const v = cell.getValue();
                return v ? `<span class="text-muted small">${v}</span>` : 'All';
            }},
            { title: 'Action', field: 'item_code', width: 120, hozAlign: 'center', formatter: function(cell){
                const code = cell.getValue();
                return `<button class="btn btn-sm btn-primary btn-request" data-code="${code}">Request</button>`;
            }}
        ]
    });

    $('#itemsTable').on('click', '.btn-request', function() {
        const code = $(this).data('code');
        const row = table.getRows().find(r => r.getData().item_code === code);
        const data = row ? row.getData() : null;
        if (!data) {
            showReqMessage('Item not found in table.');
            return;
        }
        reqSelected = data;
        $('#reqItemCode').text(data.item_code || '-');
        $('#reqItemDesc').text(data.item_description || '-');
        if (currentType === 'AST') {
            $('#reqItemAvail').text('Available');
            $('#reqItemUnit').text('');
        } else {
            $('#reqItemAvail').text(data.available_qty || 0);
            $('#reqItemUnit').text(data.unit || '');
        }
        $('#reqQtyInput').val(1);
        syncRequestQtyUI();
        $('#reqModalMsg').addClass('d-none').text('');
        $('#requestModal').modal('show');
    });
}

$(document).ready(function() {
    initTable();
    loadItems();
    $('#itemSearch').on('keyup', loadItems);
    $('#refreshItems').on('click', loadItems);
    $('.tab-pill').on('click', function() {
        $('.tab-pill').removeClass('active');
        $(this).addClass('active');
        currentType = $(this).data('type');
        syncRequestQtyUI();
        if (table) {
            table.setData(PROCESS_URL, { action: 'list_available_items', type: currentType }, 'POST');
        }
    });

    $('#btnSubmitRequest').on('click', function() {
        if (!reqSelected) {
            $('#reqModalMsg').removeClass('d-none').text('No item selected.');
            return;
        }
        const qtyVal = parseInt($('#reqQtyInput').val(), 10);
        const maxQty = currentType === 'CSM' ? (parseInt(reqSelected.available_qty, 10) || 0) : 1;
        if (isNaN(qtyVal) || qtyVal <= 0) {
            $('#reqModalMsg').removeClass('d-none').text('Invalid quantity.');
            return;
        }
        if (currentType === 'AST' && qtyVal !== 1) {
            $('#reqModalMsg').removeClass('d-none').text('AST requests must be exactly 1.');
            return;
        }
        if (currentType === 'CSM' && maxQty > 0 && qtyVal > maxQty) {
            $('#reqModalMsg').removeClass('d-none').text('Quantity exceeds available.');
            return;
        }
        $.post(PROCESS_URL, { action: 'create_request', type: currentType, item_code: reqSelected.item_code, qty_requested: qtyVal }, function(res) {
            if (res && res.success) {
                $('#requestModal').modal('hide');
                showReqMessage('');
                loadItems();
            } else {
                $('#reqModalMsg').removeClass('d-none').text(res.message || 'Request failed.');
            }
        }, 'json').fail(function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while submitting request.';
            $('#reqModalMsg').removeClass('d-none').text(msg);
        });
    });
    syncRequestQtyUI();
});
</script>
</html>
