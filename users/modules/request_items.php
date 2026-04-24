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
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/select2.min.css" rel="stylesheet">
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .tab-pill { border-radius: 999px; }
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
        .batch-qty-wrap { display: flex; justify-content: center; }
        .batch-qty-group { width: 120px; }
        .batch-qty-input {
            width: 58px;
            min-width: 58px;
            height: 28px;
            padding: 0.1rem 0.35rem;
            text-align: center;
        }
        .batch-qty-unit { min-width: 52px; justify-content: center; }
        .req-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            width: 100%;
            flex-wrap: wrap;
        }
        .req-group-meta { min-width: 220px; }
        .req-group-title { font-weight: 600; line-height: 1.25; }
        .req-group-sub { font-size: 0.82rem; color: #6b7280; line-height: 1.25; margin-top: 2px; }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 class="h4 fw-semibold mb-1">Request Items</h1>
            <p class="text-muted small mb-0">Pick AST for non-consumable items and CSM for consumable items.</p>
        </div>

        <section class="section">
            <div class="card section-card">
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
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <span class="small text-muted" id="selectedItemsCount">No item selected.</span>
                            <button class="btn btn-primary" id="requestSelectedBtn" disabled>Batch Request (AST only)</button>
                            <button class="btn btn-outline-secondary" id="clearSelectedItems">Clear Selected</button>
                        </div>
                    </div>
                    <div id="itemsTable"></div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-clipboard-check"></i>&ensp;My Requests</span>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div id="myReqMsg" class="alert alert-danger d-none mb-3"></div>
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <input type="text" id="myReqSearch" class="form-control" placeholder="Search requests..." style="max-width:280px;">
                        <select id="myReqStatus" class="form-select" style="max-width:180px;">
                            <option value="">Status: All</option>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="approved">Approved</option>
                            <option value="for_claiming">For Claiming</option>
                            <option value="claimed">Claimed</option>
                            <option value="not_claimed">Not Claimed</option>
                            <option value="disapproved">Disapproved</option>
                        </select>
                        <button class="btn btn-outline-secondary" id="myReqRefresh">Refresh</button>
                    </div>
                    <div id="myReqTable"></div>
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

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="reqModalTitle"><i class="bi bi-bag-plus"></i>&ensp;Request Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reqSingleInfo">
                        <div class="mb-2">
                            <div class="small text-muted">Property Tag</div>
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
                    </div>
                    <div id="reqBatchInfo" class="d-none mb-2">
                        <div class="small text-muted">Selected AST Items</div>
                        <div class="fw-semibold mb-2"><span id="reqBatchCount">0</span> item(s)</div>
                        <div id="reqBatchItemsTable" class="border rounded"></div>
                        <div class="small text-muted mt-2">Set quantity per item in the table.</div>
                    </div>
                    <div class="mb-3" id="reqQtyGroup">
                        <label class="form-label fw-semibold" id="reqQtyLabel">Quantity to request</label>
                        <input type="number" class="form-control" id="reqQtyInput" min="1" value="1">
                        <div class="small text-muted mt-1" id="reqQtyHelp">Must be exactly 1 for AST requests.</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Facility / Unit</label>
                            <select id="reqFacilityUnit" class="form-select"></select>
                        </div>
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
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'users/modules/request_items_process.php';
let currentType = 'AST';
let table = null;
let reqSelected = null;
let myReqTable = null;
let itemSearchTimer = null;
let myReqSearchTimer = null;
let myReqLoading = false;
let myReqPending = false;
let reqMode = 'single';
let reqBatchItems = [];
let reqBatchTable = null;
let reqBatchTableReady = false;
let pendingReqBatchRows = [];
let suppressSelectionSync = false;
let selectedItemCodesByType = { AST: {}, CSM: {} };
let itemCacheByType = { AST: {}, CSM: {} };

function setReqBatchTableData(rows) {
    const safeRows = Array.isArray(rows) ? rows : [];
    pendingReqBatchRows = safeRows;
    if (!reqBatchTable || !reqBatchTableReady) {
        return;
    }
    reqBatchTable.setData(safeRows).then(function() {
        pendingReqBatchRows = [];
    }).catch(function() {
        // Keep pending rows for retry after table/modal becomes ready.
    });
}

function getBatchItemMaxQty(item) {
    if (currentType === 'AST') return 1;
    const avail = parseInt(item && item.available_qty, 10);
    return Number.isFinite(avail) && avail > 0 ? avail : 1;
}

function normalizeBatchQty(item) {
    const maxQty = getBatchItemMaxQty(item);
    const rawQty = parseInt(item && item.qty_requested, 10);
    let qty = Number.isFinite(rawQty) ? rawQty : 1;
    if (qty < 1) qty = 1;
    if (qty > maxQty) qty = maxQty;
    return qty;
}

function getItemKey(row) {
    return String((row && row.item_code) || '').trim();
}

function cacheItems(type, rows) {
    const t = String(type || '').toUpperCase() === 'CSM' ? 'CSM' : 'AST';
    if (!itemCacheByType[t]) itemCacheByType[t] = {};
    (rows || []).forEach(function(row) {
        const key = getItemKey(row);
        if (!key) return;
        itemCacheByType[t][key] = row;
    });
}

function getSelectedCodes(type) {
    const map = selectedItemCodesByType[type] || {};
    return Object.keys(map);
}

function getSelectedItemsByType(type) {
    const cache = itemCacheByType[type] || {};
    return getSelectedCodes(type).map(function(code) {
        return cache[code] || { item_code: code, item_description: '', unit: '', available_qty: 0 };
    });
}

function updateBatchButtonState() {
    const selectedCount = getSelectedCodes(currentType).length;
    const $btn = $('#requestSelectedBtn');
    if (currentType !== 'AST') {
        $btn.prop('disabled', true).text('Batch Request (AST only)');
        return;
    }
    $btn.prop('disabled', selectedCount === 0).text('Request Selected (' + selectedCount + ')');
}

function renderSelectedItemsReview() {
    const selectedCount = getSelectedCodes(currentType).length;
    $('#selectedItemsCount').text(selectedCount > 0 ? (selectedCount + ' item(s) selected in ' + currentType) : 'No item selected.');
    updateBatchButtonState();
}

function restoreSelectionForCurrentType() {
    if (!table) return;
    const targetCodes = getSelectedCodes(currentType);
    suppressSelectionSync = true;
    table.deselectRow();
    if (targetCodes.length > 0) {
        const rowsToSelect = table.getRows().filter(function(row) {
            const key = getItemKey(row.getData());
            return !!selectedItemCodesByType[currentType][key];
        });
        rowsToSelect.forEach(function(row) {
            row.select();
        });
    }
    suppressSelectionSync = false;
}

function getSelectedRowsDataSafe() {
    if (!table) return [];
    let rows = [];
    if (typeof table.getSelectedRows === 'function') {
        rows = table.getSelectedRows() || [];
    }
    if ((!rows || rows.length === 0) && typeof table.getRows === 'function') {
        const allRows = table.getRows() || [];
        rows = allRows.filter(function(r) {
            return r && typeof r.isSelected === 'function' && r.isSelected();
        });
    }
    return (rows || []).map(function(r) {
        return r && typeof r.getData === 'function' ? (r.getData() || {}) : {};
    });
}

function syncCurrentTabSelectionFromTable() {
    if (!table || suppressSelectionSync) return;

    const selectedRows = getSelectedRowsDataSafe();
    const selectedMap = {};
    selectedRows.forEach(function(row) {
        const key = getItemKey(row);
        if (!key) return;
        selectedMap[key] = true;
        cacheItems(currentType, [row]);
    });

    const nextMap = Object.assign({}, selectedItemCodesByType[currentType] || {});
    const visibleRows = (typeof table.getRows === 'function' ? (table.getRows() || []) : []).map(function(r) {
        return r.getData ? (r.getData() || {}) : {};
    });
    visibleRows.forEach(function(row) {
        const key = getItemKey(row);
        if (!key) return;
        if (selectedMap[key]) {
            nextMap[key] = true;
        } else {
            delete nextMap[key];
        }
    });

    selectedItemCodesByType[currentType] = nextMap;
    renderSelectedItemsReview();
}

function toReqBatchRows(items) {
    return (Array.isArray(items) ? items : []).map(function(item) {
        const unit = String(item.unit || '').trim();
        const maxQty = getBatchItemMaxQty(item);
        return {
            item_code: item.item_code || '',
            item_category_name: item.item_category_name || '-',
            item_description: item.item_description || '-',
            unit: unit,
            qty_requested: normalizeBatchQty(item),
            max_qty: maxQty
        };
    });
}

function removeBatchItemByCode(code) {
    const key = String(code || '').trim();
    if (!key) return;

    reqBatchItems = reqBatchItems.filter(function(item) {
        return String(item.item_code || '').trim() !== key;
    });

    delete selectedItemCodesByType.AST[key];

    const row = table ? table.getRows().find(function(r) { return getItemKey(r.getData()) === key; }) : null;
    if (row && row.isSelected()) {
        suppressSelectionSync = true;
        row.deselect();
        suppressSelectionSync = false;
    }

    $('#reqBatchCount').text(reqBatchItems.length);
    setReqBatchTableData(toReqBatchRows(reqBatchItems));
    renderSelectedItemsReview();

    if (reqBatchItems.length === 0) {
        $('#btnSubmitRequest').prop('disabled', true);
        $('#reqModalMsg').removeClass('d-none').text('No item left in batch. Select at least one AST item.');
        return;
    }

    $('#btnSubmitRequest').prop('disabled', false);
    $('#reqModalMsg').addClass('d-none').text('');
}

function setRequestModalMode(mode, batchItems) {
    reqMode = mode === 'batch' ? 'batch' : 'single';
    if (reqMode === 'batch') {
        reqSelected = null;
        reqBatchItems = (Array.isArray(batchItems) ? batchItems : []).map(function(item) {
            const cloned = Object.assign({}, item);
            cloned.qty_requested = normalizeBatchQty(cloned);
            return cloned;
        });
        const batchRows = toReqBatchRows(reqBatchItems);
        $('#reqModalTitle').html('<i class="bi bi-bag-plus"></i>&ensp;Request items');
        $('#reqSingleInfo').addClass('d-none');
        $('#reqBatchInfo').removeClass('d-none');
        $('#reqQtyGroup').addClass('d-none');
        $('#reqBatchCount').text(reqBatchItems.length);
        if (!reqBatchTable) {
            reqBatchTableReady = false;
            reqBatchTable = new Tabulator('#reqBatchItemsTable', {
                data: batchRows,
                height: 320,
                layout: 'fitColumns',
                responsiveLayout: 'collapse',
                placeholder: 'No selected item.',
                columns: [
                    { title: 'Property Tag', field: 'item_code', width: 135, formatter: function(cell){
                        const v = escapeHtml(cell.getValue() || '');
                        return v ? '<span class="badge bg-light text-dark border">' + v + '</span>' : '-';
                    }},
                    { title: 'Category', field: 'item_category_name', width: 130, formatter: function(cell){
                        return twoLineText(cell.getValue() || '-', '-');
                    }},
                    { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, formatter: function(cell){
                        return twoLineText(cell.getValue() || '-', '-');
                    }},
                    { title: 'Qty/Unit', field: 'qty_requested', width: 150, hozAlign: 'center', formatter: function(cell){
                        const row = cell.getRow().getData() || {};
                        const maxQty = Number(row.max_qty || 1);
                        const val = Number(cell.getValue() || 1);
                        const unit = String(row.unit || '').trim();
                        const unitLabel = unit ? escapeHtml(unit) : 'Unit';
                        const safeVal = (Number.isFinite(val) && val > 0) ? Math.min(val, maxQty) : 1;
                        return '<div class="batch-qty-wrap">'
                            + '<div class="input-group input-group-sm batch-qty-group">'
                            + '<input type="number" class="form-control js-batch-qty-input batch-qty-input" '
                            + 'min="1" max="' + maxQty + '" step="1" inputmode="numeric" '
                            + 'data-code="' + escapeHtml(row.item_code || '') + '" value="' + safeVal + '">'
                            + '<span class="input-group-text batch-qty-unit">' + unitLabel + '</span>'
                            + '</div>'
                            + '</div>';
                    }},
                    {
                        title: 'Action',
                        field: 'item_code',
                        width: 84,
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function() {
                            return '<button type="button" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg"></i></button>';
                        },
                        cellClick: function(e, cell) {
                            const row = cell.getRow().getData() || {};
                            removeBatchItemByCode(row.item_code || '');
                        }
                    }
                ]
            });
            if (reqBatchTable && typeof reqBatchTable.on === 'function') {
                reqBatchTable.on('tableBuilt', function() {
                    reqBatchTableReady = true;
                    if (pendingReqBatchRows.length > 0) {
                        setReqBatchTableData(pendingReqBatchRows);
                    }
                });
            }
            setTimeout(function() {
                if (!reqBatchTableReady) {
                    reqBatchTableReady = true;
                    if (pendingReqBatchRows.length > 0) {
                        setReqBatchTableData(pendingReqBatchRows);
                    }
                }
            }, 80);
            pendingReqBatchRows = batchRows;
        } else {
            setReqBatchTableData(batchRows);
        }
        $('#btnSubmitRequest').prop('disabled', reqBatchItems.length === 0).text('Submit Batch Request');
        return;
    }
    reqBatchItems = [];
    $('#reqModalTitle').html('<i class="bi bi-bag-plus"></i>&ensp;Request Item');
    $('#reqSingleInfo').removeClass('d-none');
    $('#reqBatchInfo').addClass('d-none');
    $('#reqQtyGroup').removeClass('d-none');
    $('#btnSubmitRequest').prop('disabled', false).text('Submit Request');
    syncRequestQtyUI();
}

function openBatchRequestModal() {
    if (currentType !== 'AST') {
        showReqMessage('Batch request is currently available for AST only.');
        return;
    }
    const items = getSelectedItemsByType('AST');
    if (items.length === 0) {
        showReqMessage('Select at least one AST item for batch request.');
        return;
    }
    setRequestModalMode('batch', items);
    $('#reqModalMsg').addClass('d-none').text('');
    $('#reqFacilityUnit').val(null).trigger('change');
    loadRequestFacilityUnits();
    $('#requestModal').modal('show');
}

function showReqMessage(msg) {
    const el = $('#reqMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function showMyReqMessage(msg) {
    const el = $('#myReqMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

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

function canonicalReqStatus(raw) {
    const v = String(raw || '').trim().toLowerCase();
    if (!v) return '';
    if (v === 'approved') return 'for_claiming';
    if (v === 'forclaiming' || v === 'for_claim') return 'for_claiming';
    if (v === 'notclaimed' || v === 'not_claim') return 'not_claimed';
    return v;
}

function normalizeGroupReqStatus(statuses) {
    const list = Array.from(new Set((statuses || []).map(function(s){ return canonicalReqStatus(s); }).filter(Boolean)));
    if (!list.length) return '';
    if (list.length === 1) return list[0];
    if (list.indexOf('not_claimed') !== -1) return 'not_claimed';
    if (list.indexOf('claimed') !== -1) return 'claimed';
    const pendingLike = list.every(function(s){ return s === 'pending' || s === 'reviewed'; });
    if (pendingLike) return 'pending';
    const claimLike = list.every(function(s){ return s === 'for_claiming'; });
    if (claimLike) return 'for_claiming';
    return 'mixed';
}

function safeReqNumber(value, fallback) {
    const n = parseInt(value, 10);
    return Number.isFinite(n) ? n : (fallback || 0);
}

function reqIsoSecond(value) {
    const v = String(value || '').trim();
    if (!v) return '';
    return v.slice(0, 19);
}

function buildMyReqBatchGroupKey(row) {
    const requesterId = safeReqNumber(row && row.requester_user_id, 0);
    const createdPart = reqIsoSecond((row && (row.created_at || row.updated_at)) || '');
    const facilityId = String((row && row.claim_facility_id) || '0');
    const unitId = String((row && row.claim_unit_id) || '0');
    const type = String((row && row.module_type) || currentType || '');
    return [type, requesterId, createdPart, facilityId, unitId].join('|');
}

function normalizeMyReqWorkflowStatus(row) {
    const src = row || {};
    let raw = canonicalReqStatus(src.workflow_status || src.status || '');
    const hasClaimAssignment = safeReqNumber(src.claim_assignment_id, 0) > 0;
    const hasClaimedAt = String(src.claimed_at || '').trim() !== '';
    if (hasClaimAssignment || hasClaimedAt) return 'claimed';
    if (raw === 'for_claiming' || raw === 'not_claimed' || raw === 'claimed' || raw === 'pending' || raw === 'reviewed' || raw === 'disapproved') {
        return raw;
    }
    return raw || 'unknown';
}

function enrichMyReqRowsWithBatch(rows) {
    const mapped = (rows || []).map(function(row) {
        const next = Object.assign({}, row);
        next.workflow_status = normalizeMyReqWorkflowStatus(next);
        next.batch_group_key = buildMyReqBatchGroupKey(next);
        return next;
    });

    const grouped = {};
    mapped.forEach(function(row) {
        const k = String(row.batch_group_key || '');
        if (!grouped[k]) grouped[k] = [];
        grouped[k].push(row);
    });

    Object.keys(grouped).forEach(function(k) {
        const bucket = grouped[k] || [];
        const statuses = bucket.map(function(r){ return r.workflow_status; });
        const normalized = normalizeGroupReqStatus(statuses);
        const itemCount = bucket.length;
        const qtyTotal = bucket.reduce(function(sum, r){ return sum + safeReqNumber(r.qty_requested, 0); }, 0);
        bucket.forEach(function(r) {
            r.batch_item_count = itemCount;
            r.batch_qty_total = qtyTotal;
            r.batch_workflow_status = normalized;
            r.batch_created_at = bucket[0] && bucket[0].created_at ? bucket[0].created_at : (r.created_at || '');
        });
    });

    return mapped;
}

function loadItems() {
    const search = ($('#itemSearch').val() || '').trim();
    if (!table) return;
    suppressSelectionSync = true;
    table.setData(PROCESS_URL, { action: 'list_available_items', type: currentType, search }, 'POST').catch(function() {
        suppressSelectionSync = false;
    });
}

function initReqFacilityUnitSelect() {
    if (!$.fn.select2) return;
    const $sel = $('#reqFacilityUnit');
    if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
    }
    $sel.select2({
        width: '100%',
        placeholder: 'Select facility unit',
        allowClear: true,
        dropdownParent: $('#requestModal')
    });
}

function loadRequestFacilityUnits() {
    const $sel = $('#reqFacilityUnit');
    $sel.empty().append('<option value="">Select facility unit</option>');
    $.post(PROCESS_URL, { action: 'list_facilities' }, function(res) {
        if (!(res && res.success)) {
            $('#reqModalMsg').removeClass('d-none').text((res && res.message) || 'Failed to load facilities.');
            return;
        }
        const facilities = Array.isArray(res.data) ? res.data : [];
        const regularFacilities = facilities.filter(function(f){
            const code = String(f.facility_code || '').toUpperCase().trim();
            return code !== 'STOCKROOM';
        }).sort(function(a, b) {
            const aPersonal = (
                String(a.is_personal || '0') === '1' ||
                String(a.facility_code || '').toUpperCase().trim() === 'PERSONAL' ||
                String(a.facility_name || '').toUpperCase().trim() === 'FOR PERSONAL USE'
            ) ? 0 : 1;
            const bPersonal = (
                String(b.is_personal || '0') === '1' ||
                String(b.facility_code || '').toUpperCase().trim() === 'PERSONAL' ||
                String(b.facility_name || '').toUpperCase().trim() === 'FOR PERSONAL USE'
            ) ? 0 : 1;
            if (aPersonal !== bPersonal) {
                return aPersonal - bPersonal;
            }
            const aName = String(a.facility_name || '').toLowerCase();
            const bName = String(b.facility_name || '').toLowerCase();
            return aName.localeCompare(bName);
        });
        if (regularFacilities.length === 0) {
            $('#reqModalMsg').removeClass('d-none').text('No available facility units for request.');
            return;
        }

        const opts = ['<option value="">Select facility unit</option>'];
        let idx = 0;

        function processNext() {
            if (idx >= regularFacilities.length) {
                $sel.html(opts.join(''));
                $sel.val(null).trigger('change');
                return;
            }
            const f = regularFacilities[idx++];
            const facilityId = Number(f.facility_id || 0);
            const facilityName = String(f.facility_name || '-');

            $.post(PROCESS_URL, { action: 'list_units', facility_id: facilityId }, function(unitRes){
                const units = (unitRes && unitRes.success && Array.isArray(unitRes.data)) ? unitRes.data : [];
                if (units.length > 0) {
                    const group = [];
                    units.forEach(function(u){
                        const unitId = Number(u.unit_id || 0);
                        const unitName = String(u.unit_name || '-');
                        const value = facilityId + '::' + unitId;
                        group.push(
                            '<option value="' + escapeHtml(value) + '"' +
                            ' data-facility-id="' + facilityId + '"' +
                            ' data-unit-id="' + unitId + '">' +
                            escapeHtml(unitName) +
                            '</option>'
                        );
                    });
                    opts.push('<optgroup label="' + escapeHtml(facilityName) + '">' + group.join('') + '</optgroup>');
                }
                processNext();
            }, 'json').fail(function(){
                processNext();
            });
        }

        processNext();
    }, 'json');
}

function syncRequestQtyUI() {
    if (currentType === 'AST') {
        $('#reqAvailLabel').text('Availability');
        $('#reqQtyLabel').text('Quantity to request (fixed)');
        $('#reqQtyHelp').text('AST uses one Property Tag per unit. Quantity is always 1.');
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
        height: 420,
        selectable: true,
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No available items found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        dataLoaded: function() {
            restoreSelectionForCurrentType();
            syncCurrentTabSelectionFromTable();
            suppressSelectionSync = false;
        },
        dataLoadError: function() {
            suppressSelectionSync = false;
        },
        rowSelectionChanged: function() {
            syncCurrentTabSelectionFromTable();
        },
        rowSelected: function(row) {
            syncCurrentTabSelectionFromTable();
        },
        rowDeselected: function(row) {
            syncCurrentTabSelectionFromTable();
        },
        ajaxResponse: function(url, params, response) {
            if (response && response.success === false) {
                showReqMessage(response.message || 'Failed to load items.');
                return [];
            }
            showReqMessage('');
            const rows = response.data || [];
            const t = (params && params.type) ? String(params.type).toUpperCase() : currentType;
            cacheItems(t, rows);
            return rows;
        },
        columns: [
            { formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerSort: false, width: 44 },
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
            { title: 'Property Tag', field: 'item_code', width: 125, headerFilter: 'input', formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? `<span class="badge bg-light text-dark border">${v}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, headerFilter: 'input', formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: 'Qty / Unit', field: 'unit', width: 120, hozAlign: 'center', formatter: function(cell){
                const row = cell.getRow().getData();
                const unit = String(row.unit || '').trim();
                if (currentType === 'AST') {
                    return unit ? ('1 / ' + escapeHtml(unit)) : '1';
                }
                const qty = parseInt(row.available_qty, 10);
                const safeQty = Number.isFinite(qty) ? qty : 0;
                return unit ? (safeQty + ' / ' + escapeHtml(unit)) : String(safeQty);
            }}
        ]
    });

}

function loadMyReqs() {
    const search = ($('#myReqSearch').val() || '').trim();
    const status = $('#myReqStatus').val() || '';
    if (!myReqTable) return;
    if (myReqLoading) {
        myReqPending = true;
        return;
    }
    myReqTable.setData(PROCESS_URL, { action: 'list_my_requisitions', search, status }, 'POST');
}

function initMyReqTable() {
    myReqTable = new Tabulator('#myReqTable', {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_my_requisitions' },
        height: 420,
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        groupBy: 'batch_group_key',
        groupStartOpen: true,
        groupHeader: function(value, count, data) {
            const first = (data && data[0]) ? data[0] : {};
            const totalQty = (data || []).reduce(function(sum, row){ return sum + safeReqNumber(row.qty_requested, 0); }, 0);
            const groupStatus = normalizeGroupReqStatus((data || []).map(function(r){ return r.batch_workflow_status || r.workflow_status; }));
            const statusHtml = statusBadge(groupStatus);
            const created = escapeHtml((first.batch_created_at || first.created_at || '-'));
            const fac = String(first.facility_name || '').trim();
            const unit = String(first.unit_name || '').trim();
            const place = fac || unit ? escapeHtml((fac ? fac : '-') + (unit ? (' / ' + unit) : '')) : '-';

            return '<div class="req-group-header">'
                + '<div class="req-group-meta">'
                + '<div class="req-group-title">Request Group <span class="text-muted">(' + count + ' item' + (count > 1 ? 's' : '') + ', Qty ' + totalQty + ')</span></div>'
                + '<div class="req-group-sub">Created: ' + created + '</div>'
                + '<div class="req-group-sub">Facility / Unit: ' + place + '</div>'
                + '</div>'
                + '<div>' + statusHtml + '</div>'
                + '</div>';
        },
        responsiveLayout: 'collapse',
        placeholder: 'No requests found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        dataLoading: function(){
            myReqLoading = true;
        },
        dataLoaded: function(){
            myReqLoading = false;
            if (myReqPending) {
                myReqPending = false;
                loadMyReqs();
            }
        },
        dataLoadError: function(){
            myReqLoading = false;
            myReqPending = false;
        },
        ajaxResponse: function(url, params, response) {
            if (response && response.success === false) {
                showMyReqMessage(response.message || 'Failed to load requests.');
                return [];
            }
            showMyReqMessage('');
            return enrichMyReqRowsWithBatch(response.data || []);
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
            { title: 'Item Code', field: 'item_code', width: 125, headerFilter: 'input', formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? '<span class="badge bg-light text-dark border">' + v + '</span>' : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, headerFilter: 'input', formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: 'Qty', field: 'qty_requested', width: 55, hozAlign: 'center' },
            { title: 'Workflow', field: 'workflow_status', width: 120, formatter: function(cell){
                return statusBadge(cell.getValue());
            }},
            { title: 'Claimed', field: 'claimed_at', width: 130, formatter: function(cell){
                const v = String(cell.getValue() || '');
                if (!v) return '-';
                const parts = v.split(/\s+/);
                if (parts.length >= 2) {
                    return `<div style="line-height:1.2;white-space:normal;">${escapeHtml(parts[0])}<br><span class="text-muted">${escapeHtml(parts.slice(1).join(' '))}</span></div>`;
                }
                return escapeHtml(v);
            }},
            { title: 'Facility / Unit', field: 'facility_name', widthGrow: 1, minWidth: 150, headerFilter: 'input', formatter: function(cell){
                const row = cell.getRow().getData();
                const fac = escapeHtml(row.facility_name || '');
                const unit = escapeHtml(row.unit_name || '');
                if (!fac && !unit) return '-';
                const facLine = fac ? `<div class="two-line-cell" title="${fac}">${fac}</div>` : '';
                const unitLine = unit ? `<div class="text-muted small two-line-cell" title="${unit}">${unit}</div>` : '';
                return `<div style="line-height:1.3;white-space:normal;">${facLine}${unitLine}</div>`;
            }},
            { title: 'Reason', field: 'reason', widthGrow: 1, minWidth: 130, formatter: function(cell){
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Remarks', field: 'remarks', widthGrow: 1, minWidth: 130, formatter: function(cell){
                return twoLineText(cell.getValue() || '', '-');
            }}
        ]
    });
}

$(document).ready(function() {
    initReqFacilityUnitSelect();
    initTable();
    try {
        initMyReqTable();
    } catch (e) {
        console.error('My Requests table init failed:', e);
        showMyReqMessage('My Requests table failed to initialize. You can still browse and request items.');
    }
    $('#itemSearch').on('keyup', function() {
        clearTimeout(itemSearchTimer);
        itemSearchTimer = setTimeout(loadItems, 300);
    });
    $('#refreshItems').on('click', loadItems);
    $('#myReqSearch').on('keyup', function() {
        clearTimeout(myReqSearchTimer);
        myReqSearchTimer = setTimeout(loadMyReqs, 300);
    });
    $('#myReqStatus').on('change', loadMyReqs);
    $('#myReqRefresh').on('click', loadMyReqs);
    $('.tab-pill').on('click', function() {
        $('.tab-pill').removeClass('active');
        $(this).addClass('active');
        currentType = $(this).data('type');
        syncRequestQtyUI();
        loadItems();
        renderSelectedItemsReview();
    });

    $('#requestSelectedBtn').on('click', function() {
        openBatchRequestModal();
    });

    $('#clearSelectedItems').on('click', function() {
        selectedItemCodesByType[currentType] = {};
        suppressSelectionSync = true;
        if (table) table.deselectRow();
        suppressSelectionSync = false;
        renderSelectedItemsReview();
    });

    $('body').on('click', '.js-thumb-preview', function() {
        const full = $(this).data('full') || $(this).attr('data-full');
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });

    $('#requestModal').on('shown.bs.modal', function() {
        if (reqMode === 'batch' && reqBatchTable && typeof reqBatchTable.redraw === 'function') {
            reqBatchTable.redraw(true);
            if (pendingReqBatchRows.length > 0) {
                setReqBatchTableData(pendingReqBatchRows);
            }
        }
    });

    $('#reqBatchItemsTable').on('input change', '.js-batch-qty-input', function() {
        const code = String($(this).data('code') || '').trim();
        if (!code) return;
        const idx = reqBatchItems.findIndex(function(item) {
            return String(item.item_code || '').trim() === code;
        });
        if (idx < 0) return;

        const item = reqBatchItems[idx];
        const maxQty = getBatchItemMaxQty(item);
        let qty = parseInt($(this).val(), 10);
        if (!Number.isFinite(qty)) qty = 1;
        if (qty < 1) qty = 1;
        if (qty > maxQty) qty = maxQty;
        item.qty_requested = qty;
        $(this).val(qty);
    });

    $('#itemsTable').on('click change', 'input[type="checkbox"], .tabulator-row, .tabulator-cell', function() {
        setTimeout(function() {
            syncCurrentTabSelectionFromTable();
        }, 0);
    });

    $('#btnSubmitRequest').on('click', function() {
        const $picked = $('#reqFacilityUnit option:selected');
        const facilityRaw = $picked.attr('data-facility-id');
        const unitRaw = $picked.attr('data-unit-id');
        const facilityId = facilityRaw != null ? String(facilityRaw) : '';
        const unitId = unitRaw != null ? String(unitRaw) : '';
        if (!facilityId || !unitId) {
            $('#reqModalMsg').removeClass('d-none').text('Facility / Unit is required.');
            return;
        }
        const qtyVal = parseInt($('#reqQtyInput').val(), 10);
        if (isNaN(qtyVal) || qtyVal <= 0) {
            $('#reqModalMsg').removeClass('d-none').text('Invalid quantity.');
            return;
        }

        if (reqMode === 'batch') {
            if (currentType !== 'AST') {
                $('#reqModalMsg').removeClass('d-none').text('Batch request is currently available for AST only.');
                return;
            }
            const payloadItems = reqBatchItems.map(function(item) {
                const qty = normalizeBatchQty(item);
                return {
                    item_code: String(item.item_code || '').trim(),
                    qty_requested: qty
                };
            }).filter(function(item) {
                return item.item_code !== '';
            });
            if (payloadItems.length === 0) {
                $('#reqModalMsg').removeClass('d-none').text('No selected AST item found for batch request.');
                return;
            }
            $.post(PROCESS_URL, {
                action: 'create_batch_request',
                type: 'AST',
                facility_id: facilityId,
                unit_id: unitId,
                items: JSON.stringify(payloadItems)
            }, function(res) {
                if (res && res.success) {
                    $('#requestModal').modal('hide');
                    selectedItemCodesByType.AST = {};
                    showReqMessage(res.message || 'Batch request submitted.');
                    loadItems();
                    setTimeout(loadMyReqs, 400);
                    renderSelectedItemsReview();
                } else {
                    $('#reqModalMsg').removeClass('d-none').text((res && res.message) || 'Batch request failed.');
                }
            }, 'json').fail(function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while submitting batch request.';
                $('#reqModalMsg').removeClass('d-none').text(msg);
            });
            return;
        }

        if (!reqSelected) {
            $('#reqModalMsg').removeClass('d-none').text('No item selected.');
            return;
        }
        const maxQty = currentType === 'CSM' ? (parseInt(reqSelected.available_qty, 10) || 0) : 1;
        if (currentType === 'AST' && qtyVal !== 1) {
            $('#reqModalMsg').removeClass('d-none').text('AST requests must be exactly 1.');
            return;
        }
        if (currentType === 'CSM' && maxQty > 0 && qtyVal > maxQty) {
            $('#reqModalMsg').removeClass('d-none').text('Quantity exceeds available.');
            return;
        }
        $.post(PROCESS_URL, { action: 'create_request', type: currentType, item_code: reqSelected.item_code, qty_requested: qtyVal, facility_id: facilityId, unit_id: unitId }, function(res) {
            if (res && res.success) {
                $('#requestModal').modal('hide');
                showReqMessage('');
                loadItems();
                setTimeout(loadMyReqs, 400);
            } else {
                $('#reqModalMsg').removeClass('d-none').text(res.message || 'Request failed.');
            }
        }, 'json').fail(function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while submitting request.';
            $('#reqModalMsg').removeClass('d-none').text(msg);
        });
    });
    syncRequestQtyUI();
    renderSelectedItemsReview();
});
</script>
</html>

