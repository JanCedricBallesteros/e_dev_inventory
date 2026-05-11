<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(
    role_has("ADMIN") ||
    (
        (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) &&
        user_has_access("CSM")
    )
)) {
    header("Location: " . BASE_URL);
    exit();
}

$preselectedRequestGroup = isset($_GET['request_group']) ? trim((string)$_GET['request_group']) : '';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/select2.min.css" rel="stylesheet">
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .item-thumb { width: 38px; height: 38px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; }
        .item-badge { width: 38px; height: 38px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #1E3A8A; color: #fff; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; border: 1px solid rgba(0,0,0,0.06); }
        .thumb-wrap { display: flex; align-items: center; justify-content: center; }
        .img-preview { max-width: 100%; max-height: 70vh; border-radius: 8px; }
        .status-badge { padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.8rem; }
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
        .request-group-meta {
            display: grid;
            gap: 0.2rem;
            line-height: 1.2;
            min-width: 260px;
        }
        .request-group-title { font-weight: 600; }
        .request-group-sub { color: #6c757d; font-size: 0.82rem; }
        .request-group-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            flex-wrap: wrap;
        }
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
            color: #212529;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .manager-box { min-height: 38px; background: #f8f9fa; }
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
        <h1 class="h4 fw-semibold mb-1">CSM Issuance</h1>
        <p class="text-muted small mb-0">Search or scan approved consumable requests, issue them to the requested facility unit, and review the latest issued items.</p>
    </div>

    <section class="section">
        <div id="pageMsg" class="alert alert-danger d-none mb-3"></div>
        <div class="row g-3">
            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard-check"></i>&ensp;Approved CSM Requests</span>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-lg-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="requestSearch" placeholder="Search item code, description, requester, or facility...">
                                    <button class="btn btn-outline-secondary" type="button" id="openRequestSearchScanner" title="Scan QR">
                                        <i class="bi bi-qr-code-scan"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 d-flex align-items-center justify-content-lg-end gap-2">
                                <small id="selectedRequestNote" class="text-muted">Select a request group to continue.</small>
                                <button class="btn btn-outline-secondary btn-sm" id="refreshRequestGroups">Refresh</button>
                            </div>
                        </div>
                        <div id="requestGroupTable"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-check"></i>&ensp;Items To Be Issued</span>
                        <div id="selectedBatchSummary" class="small">No request selected.</div>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div id="selectedItemsTable"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold">
                        <span><i class="bi bi-send-check"></i>&ensp;Issue Request</span>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <form id="issueForm">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Facility</label>
                                    <select class="form-select" id="issueFacilityId" required>
                                        <option value="">Select facility</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Unit</label>
                                    <select class="form-select" id="issueUnitId">
                                        <option value="">Select unit</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Requested By</label>
                                    <input type="text" id="issueRequestedBy" class="form-control manager-box" value="-" readonly>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Unit Manager</label>
                                    <input type="text" id="issueUnitManager" class="form-control manager-box" value="-" readonly>
                                    <input type="hidden" id="issueUnitManagerId">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">Batch Status</label>
                                    <input type="text" id="issueBatchStatus" class="form-control manager-box" value="Awaiting selection" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Remarks</label>
                                    <input type="text" class="form-control" id="issueRemarks" placeholder="Optional issuance remarks">
                                </div>
                            </div>
                            <div id="issueMsg" class="mt-3"></div>
                            <div class="mt-3 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="issueSubmitBtn"><i class="bi bi-check2-circle"></i> Issue Selected Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold">
                        <span><i class="bi bi-box-seam"></i>&ensp;Issued CSM Items</span>
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <input type="text" class="form-control" id="issuedSearch" placeholder="Search issued item code, description, facility, or requester...">
                            <button class="btn btn-outline-secondary" id="refreshIssuedItems">Refresh</button>
                        </div>
                        <div id="issuedItemsTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

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

<div class="modal fade" id="requestSearchQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Search Approved Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="requestSearchCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="requestSearchBtnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="requestSearchBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div style="width:100%;max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1;">
                    <div id="requestSearchPreview" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
                    <div id="requestSearchScannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
                        <div>Initializing camera...</div>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="requestSearchLastScanned" class="fw-semibold">-</span>
                </div>
                <div id="requestSearchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<?php include_once FOOTER_PATH; ?>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script>
const BASE_URL = <?= json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/transactions/requisition_process.php';
const PRESELECT_REQUEST_GROUP = <?= json_encode($preselectedRequestGroup); ?>;

let requestGroupTable = null;
let selectedItemsTable = null;
let issuedItemsTable = null;
let requestSearchTimer = null;
let issuedSearchTimer = null;
let requestRows = [];
let selectedBatchKey = '';
let selectedBatchRows = [];
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

function safeNumber(value, fallback) {
    const n = parseInt(value, 10);
    return Number.isFinite(n) ? n : (fallback || 0);
}

function togglePageMsg(msg) {
    const el = $('#pageMsg');
    if (!msg) { el.addClass('d-none').text(''); return; }
    el.removeClass('d-none').text(msg);
}

function twoLineText(value, fallback) {
    const raw = (value === null || value === undefined || value === '') ? (fallback || '-') : String(value);
    const safe = escapeHtml(raw);
    return `<span class="two-line-cell" title="${safe}">${safe}</span>`;
}

function statusBadge(raw) {
    const normalized = String(raw || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
    const map = {
        pending: 'bg-warning text-dark',
        reviewed: 'bg-info text-dark',
        approved: 'bg-primary text-white',
        for_claiming: 'bg-primary text-white',
        claimed: 'bg-success text-white',
        not_claimed: 'bg-secondary text-white'
    };
    const cls = map[normalized] || 'bg-light text-dark border';
    const label = normalized ? normalized.replace(/_/g, ' ') : 'Unknown';
    return `<span class="status-badge ${cls}">${escapeHtml(label)}</span>`;
}

function canonicalStatus(value) {
    let raw = String(value || '').trim().toLowerCase();
    if (!raw) return '';
    raw = raw.replace(/[^a-z0-9\s_-]/g, '').replace(/[\s-]+/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
    if ((raw.indexOf('back') !== -1 && raw.indexOf('storage') !== -1) || (raw.indexOf('not') !== -1 && raw.indexOf('claim') !== -1)) return 'not_claimed';
    if (raw.indexOf('for') !== -1 && raw.indexOf('claim') !== -1) return 'for_claiming';
    return raw;
}

function toIsoSecond(value) {
    const v = String(value || '').trim();
    if (!v) return '';
    return v.slice(0, 19);
}

function buildBatchGroupKey(row) {
    const requesterId = safeNumber(row.requester_user_id, 0);
    const createdPart = toIsoSecond(row.created_at || row.updated_at || '');
    const facilityId = String(row.claim_facility_id || '0');
    const unitId = String(row.claim_unit_id || '0');
    const type = String(row.module_type || 'CSM');
    return [type, requesterId, createdPart, facilityId, unitId].join('|');
}

function normalizeRowWorkflowStatus(row) {
    const raw = canonicalStatus(row.workflow_status || row.status || '');
    if (raw === 'approved') return 'for_claiming';
    return raw || 'unknown';
}

function normalizeGroupStatus(statuses) {
    const list = Array.from(new Set((statuses || []).map(function(s){ return canonicalStatus(s); }).filter(Boolean)));
    if (!list.length) return '';
    if (list.length === 1) return list[0];
    if (list.indexOf('claimed') !== -1) return 'claimed';
    if (list.indexOf('not_claimed') !== -1) return 'not_claimed';
    if (list.every(function(s){ return s === 'approved' || s === 'for_claiming'; })) return 'for_claiming';
    return list[0];
}

function enrichRowsWithBatch(rows) {
    const mapped = (rows || []).map(function(row) {
        const next = Object.assign({}, row);
        next.workflow_status = normalizeRowWorkflowStatus(next);
        next.batch_group_key = buildBatchGroupKey(next);
        return next;
    });
    const grouped = {};
    mapped.forEach(function(row) {
        const key = String(row.batch_group_key || '');
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(row);
    });
    Object.keys(grouped).forEach(function(key) {
        const bucket = grouped[key] || [];
        const statuses = bucket.map(function(r){ return r.workflow_status; });
        const normalized = normalizeGroupStatus(statuses);
        const requesterNames = Array.from(new Set(bucket.map(function(r){ return String(r.requester_name || '').trim(); }).filter(Boolean)));
        bucket.forEach(function(row) {
            row.batch_workflow_status = normalized;
            row.batch_item_count = bucket.length;
            row.batch_qty_total = bucket.reduce(function(sum, r){ return sum + safeNumber(r.qty_requested, 0); }, 0);
            row.batch_requester_name = requesterNames.length === 1 ? requesterNames[0] : 'Multiple Requesters';
            row.batch_created_at = bucket[0] ? (bucket[0].created_at || bucket[0].updated_at || '') : '';
        });
    });
    return mapped;
}

function getBatchRowsByKey(batchKey) {
    return requestRows
        .filter(function(row) { return String(row.batch_group_key || '') === String(batchKey || ''); })
        .sort(function(a, b) { return safeNumber(a.requisition_id, 0) - safeNumber(b.requisition_id, 0); });
}

function getBatchReqIds(rows) {
    return (rows || []).map(function(row) { return safeNumber(row.requisition_id, 0); }).filter(function(id) { return id > 0; });
}

function initSelect2() {
    $('#issueFacilityId').select2({ width: '100%' });
    $('#issueUnitId').select2({ width: '100%' });
}

function loadClaimLookups() {
    $.post(PROCESS_URL, { action: 'list_facilities' }, function(res) {
        if (!(res && res.success)) return;
        claimFacilities = res.data || [];
        const $facility = $('#issueFacilityId');
        $facility.empty().append('<option value="">Select facility</option>');
        claimFacilities.forEach(function(f) {
            $facility.append(`<option value="${f.facility_id}">${escapeHtml(f.facility_code || '')} - ${escapeHtml(f.facility_name || '')}</option>`);
        });
        $facility.trigger('change.select2');
    }, 'json');
}

function loadClaimUnits(facilityId, selectedUnitId) {
    const $unit = $('#issueUnitId');
    claimUnits = [];
    $unit.empty().append('<option value="">Select unit</option>');
    $('#issueUnitManager').val('-');
    $('#issueUnitManagerId').val('');
    if (!facilityId) {
        $unit.trigger('change.select2');
        return;
    }
    $.post(PROCESS_URL, { action: 'list_units', facility_id: facilityId }, function(res) {
        if (!(res && res.success)) return;
        claimUnits = res.data || [];
        claimUnits.forEach(function(unit) {
            $unit.append(`<option value="${unit.unit_id}">${escapeHtml(unit.unit_code || '')} - ${escapeHtml(unit.unit_name || '')}</option>`);
        });
        if (selectedUnitId !== undefined && selectedUnitId !== null && selectedUnitId !== '') {
            $unit.val(String(selectedUnitId));
        }
        $unit.trigger('change.select2');
        syncSelectedUnitManager();
    }, 'json');
}

function syncSelectedUnitManager() {
    const selectedUnitId = String($('#issueUnitId').val() || '');
    const picked = claimUnits.find(function(unit) { return String(unit.unit_id || '') === selectedUnitId; });
    if (picked && picked.facility_unit_manager_user_id) {
        $('#issueUnitManager').val(picked.unit_manager_name || '-');
        $('#issueUnitManagerId').val(String(picked.facility_unit_manager_user_id));
    } else {
        $('#issueUnitManager').val('-');
        $('#issueUnitManagerId').val('');
    }
}

function renderSelectedBatch() {
    const rows = selectedBatchRows || [];
    const requesterNames = Array.from(new Set(rows.map(function(row) { return String(row.requester_name || '').trim(); }).filter(Boolean)));
    const requestedBy = requesterNames.length === 1 ? requesterNames[0] : (requesterNames.length ? ('Multiple (' + requesterNames.length + ')') : '-');
    $('#issueRequestedBy').val(requestedBy);
    $('#selectedRequestNote').text(rows.length ? (rows.length + ' item(s) ready for issuance.') : 'Select a request group to continue.');
    $('#selectedBatchSummary').html(rows.length
        ? ('<span class="text-light">' + escapeHtml(requestedBy) + ' | Qty ' + rows.reduce(function(sum, row){ return sum + safeNumber(row.qty_requested, 0); }, 0) + '</span>')
        : 'No request selected.');

    const first = rows[0] || {};
    $('#issueBatchStatus').val(rows.length ? String(first.batch_workflow_status || first.workflow_status || '').replace(/_/g, ' ') : 'Awaiting selection');

    if (rows.length) {
        const facilityId = String(first.claim_facility_id || '');
        const unitId = String(first.claim_unit_id || '');
        if (facilityId) {
            $('#issueFacilityId').val(facilityId).trigger('change.select2');
            loadClaimUnits(facilityId, unitId);
        } else {
            $('#issueFacilityId').val('').trigger('change.select2');
            loadClaimUnits('', '');
        }
    } else {
        $('#issueFacilityId').val('').trigger('change.select2');
        loadClaimUnits('', '');
    }

    if (selectedItemsTable) {
        selectedItemsTable.setData(rows);
    }
}

function selectBatch(batchKey) {
    selectedBatchKey = String(batchKey || '');
    selectedBatchRows = getBatchRowsByKey(selectedBatchKey);
    renderSelectedBatch();
}

function buildRequestGroupHeader(value, count, data) {
    const rows = Array.isArray(data) ? data : [];
    const first = rows[0] || {};
    const requester = escapeHtml(first.batch_requester_name || first.requester_name || '-');
    const created = escapeHtml(String(first.batch_created_at || first.created_at || '-'));
    const totalQty = rows.reduce(function(sum, row){ return sum + safeNumber(row.qty_requested, 0); }, 0);
    const statusHtml = statusBadge(first.batch_workflow_status || first.workflow_status || 'for_claiming');
    const facilityName = escapeHtml(first.facility_name || '-');
    const unitName = escapeHtml(first.unit_name || '-');
    const encodedKey = encodeURIComponent(String(value || ''));

    return '<div class="request-group-header">'
        + '<div class="request-group-meta">'
        + '<div class="request-group-title">' + requester + ' <span class="text-muted">(' + count + ' item' + (count > 1 ? 's' : '') + ', Qty ' + totalQty + ')</span></div>'
        + '<div class="request-group-sub">Created: ' + created + ' &nbsp; ' + statusHtml + '</div>'
        + '<div class="request-group-sub">Facility / Unit: ' + facilityName + ' / ' + unitName + '</div>'
        + '</div>'
        + '<div><button type="button" class="btn btn-sm btn-primary btn-select-request-group" data-batch="' + encodedKey + '">Select</button></div>'
        + '</div>';
}

function initRequestGroupTable() {
    requestGroupTable = new Tabulator('#requestGroupTable', {
        ajaxURL: PROCESS_URL,
        ajaxConfig: 'POST',
        ajaxParams: { action: 'list_requisitions', type: 'CSM', status: 'for_claiming' },
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No approved CSM requests are waiting for issuance.',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        groupBy: 'batch_group_key',
        groupStartOpen: true,
        groupHeader: buildRequestGroupHeader,
        ajaxResponse: function(url, params, response) {
            if (!(response && response.success)) {
                togglePageMsg((response && response.message) || 'Failed to load approved requests.');
                requestRows = [];
                return [];
            }
            togglePageMsg('');
            requestRows = enrichRowsWithBatch(response.data || []);
            if (PRESELECT_REQUEST_GROUP && requestRows.some(function(row) { return String(row.batch_group_key || '') === String(PRESELECT_REQUEST_GROUP); })) {
                window.setTimeout(function() {
                    selectBatch(PRESELECT_REQUEST_GROUP);
                }, 50);
            } else if (selectedBatchKey && requestRows.some(function(row) { return String(row.batch_group_key || '') === String(selectedBatchKey); })) {
                window.setTimeout(function() {
                    selectBatch(selectedBatchKey);
                }, 50);
            } else if (!requestRows.length) {
                selectedBatchKey = '';
                selectedBatchRows = [];
                renderSelectedBatch();
            }
            return requestRows;
        },
        columns: [
            { title: 'Image', field: 'category_photo_thumb_url', width: 58, hozAlign: 'center', headerSort: false, formatter: function(cell) {
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || cell.getRow().getData().item_description || '';
                if (url) {
                    return `<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="${url}" data-full="${escapeHtml(full || url)}" loading="lazy" alt="Item image"></div>`;
                }
                const initials = (String(name).trim().split(/\s+/).map(function(word){ return word.charAt(0); }).filter(Boolean).slice(0, 2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge" title="${escapeHtml(name)}">${escapeHtml(initials)}</div></div>`;
            }},
            { title: 'Req ID', field: 'requisition_id', width: 70, hozAlign: 'center' },
            { title: 'Item Code', field: 'item_code', width: 140, formatter: function(cell) {
                const value = escapeHtml(cell.getValue() || '');
                return value ? `<span class="badge bg-light text-dark border">${value}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Qty', field: 'qty_requested', width: 60, hozAlign: 'center' },
            { title: 'Workflow', field: 'workflow_status', width: 120, formatter: function(cell) {
                return statusBadge(cell.getValue());
            }},
            { title: 'Facility / Unit', field: 'facility_name', widthGrow: 1, minWidth: 170, formatter: function(cell) {
                const row = cell.getRow().getData();
                const facilityName = escapeHtml(row.facility_name || '');
                const unitName = escapeHtml(row.unit_name || '');
                return `<div>${facilityName || '-'}<div class="text-muted small">${unitName || '-'}</div></div>`;
            }},
            { title: 'Requested By', field: 'requester_name', width: 170, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }}
        ]
    });
}

function initSelectedItemsTable() {
    selectedItemsTable = new Tabulator('#selectedItemsTable', {
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'Select a request group to view the items to be issued.',
        columns: [
            { title: 'Image', field: 'category_photo_thumb_url', width: 58, hozAlign: 'center', headerSort: false, formatter: function(cell) {
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || cell.getRow().getData().item_description || '';
                if (url) {
                    return `<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="${url}" data-full="${escapeHtml(full || url)}" loading="lazy" alt="Item image"></div>`;
                }
                const initials = (String(name).trim().split(/\s+/).map(function(word){ return word.charAt(0); }).filter(Boolean).slice(0, 2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge" title="${escapeHtml(name)}">${escapeHtml(initials)}</div></div>`;
            }},
            { title: 'Req ID', field: 'requisition_id', width: 70, hozAlign: 'center' },
            { title: 'Item Code', field: 'item_code', width: 140, formatter: function(cell) {
                const value = escapeHtml(cell.getValue() || '');
                return value ? `<span class="badge bg-light text-dark border">${value}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Qty', field: 'qty_requested', width: 60, hozAlign: 'center' },
            { title: 'Purpose', field: 'reason', widthGrow: 1, minWidth: 160, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }}
        ]
    });
}

function initIssuedItemsTable() {
    issuedItemsTable = new Tabulator('#issuedItemsTable', {
        ajaxURL: PROCESS_URL,
        ajaxConfig: 'POST',
        ajaxParams: { action: 'list_issued_items', type: 'CSM' },
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No issued CSM items found.',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        ajaxResponse: function(url, params, response) {
            if (!(response && response.success)) {
                togglePageMsg((response && response.message) || 'Failed to load issued items.');
                return [];
            }
            togglePageMsg('');
            return response.data || [];
        },
        columns: [
            { title: 'Image', field: 'category_photo_thumb_url', width: 58, hozAlign: 'center', headerSort: false, formatter: function(cell) {
                const url = cell.getValue();
                const full = cell.getRow().getData().category_photo_url;
                const name = cell.getRow().getData().item_category_name || cell.getRow().getData().item_description || '';
                if (url) {
                    return `<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="${url}" data-full="${escapeHtml(full || url)}" loading="lazy" alt="Item image"></div>`;
                }
                const initials = (String(name).trim().split(/\s+/).map(function(word){ return word.charAt(0); }).filter(Boolean).slice(0, 2).join('') || 'IT').toUpperCase();
                return `<div class="thumb-wrap"><div class="item-badge" title="${escapeHtml(name)}">${escapeHtml(initials)}</div></div>`;
            }},
            { title: 'Item Code', field: 'item_code', width: 140, formatter: function(cell) {
                const value = escapeHtml(cell.getValue() || '');
                return value ? `<span class="badge bg-light text-dark border">${value}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Qty / Unit', field: 'unit', width: 110, hozAlign: 'center', formatter: function(cell) {
                const row = cell.getRow().getData();
                const qty = safeNumber(row.qty, 0);
                const unit = escapeHtml(row.unit || '');
                return unit ? `${qty} / ${unit}` : String(qty);
            }},
            { title: 'Issued To', field: 'issued_to_name', width: 160, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Facility / Unit', field: 'facility_name', widthGrow: 1, minWidth: 170, formatter: function(cell) {
                const row = cell.getRow().getData();
                const facilityName = escapeHtml(row.facility_name || '');
                const unitName = escapeHtml(row.unit_name || '');
                return `<div>${facilityName || '-'}<div class="text-muted small">${unitName || '-'}</div></div>`;
            }},
            { title: 'Issued At', field: 'issued_at', width: 140, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }},
            { title: 'Remarks', field: 'remarks', widthGrow: 1, minWidth: 150, formatter: function(cell) {
                return twoLineText(cell.getValue() || '', '-');
            }}
        ]
    });
}

function loadRequestGroups() {
    const search = ($('#requestSearch').val() || '').trim();
    if (!requestGroupTable) return;
    requestGroupTable.setData(PROCESS_URL, { action: 'list_requisitions', type: 'CSM', status: 'for_claiming', search: search }, 'POST');
}

function loadIssuedItems() {
    const search = ($('#issuedSearch').val() || '').trim();
    if (!issuedItemsTable) return;
    issuedItemsTable.setData(PROCESS_URL, { action: 'list_issued_items', type: 'CSM', search: search }, 'POST');
}

function runBatchAction(ids, runOne, onDone) {
    const queue = Array.isArray(ids) ? ids.slice() : [];
    const failed = [];
    let okCount = 0;

    function next() {
        if (!queue.length) {
            onDone({ successCount: okCount, failed: failed });
            return;
        }
        const id = queue.shift();
        runOne(id, function(success, message) {
            if (success) {
                okCount += 1;
            } else {
                failed.push({ id: id, message: message || 'Request failed.' });
            }
            next();
        });
    }

    next();
}

$(document).ready(function() {
    initSelect2();
    loadClaimLookups();
    initSelectedItemsTable();
    initRequestGroupTable();
    initIssuedItemsTable();

    $('#refreshRequestGroups').on('click', loadRequestGroups);
    $('#refreshIssuedItems').on('click', loadIssuedItems);

    $('#requestSearch').on('keyup', function() {
        clearTimeout(requestSearchTimer);
        requestSearchTimer = setTimeout(loadRequestGroups, 250);
    });

    $('#issuedSearch').on('keyup', function() {
        clearTimeout(issuedSearchTimer);
        issuedSearchTimer = setTimeout(loadIssuedItems, 250);
    });

    $('#issueFacilityId').on('change', function() {
        loadClaimUnits($(this).val(), '');
    });

    $('#issueUnitId').on('change', function() {
        syncSelectedUnitManager();
    });

    $('#requestGroupTable').on('click', '.btn-select-request-group', function() {
        const encoded = String($(this).data('batch') || '');
        const batchKey = decodeURIComponent(encoded);
        if (!batchKey) return;
        selectBatch(batchKey);
    });

    $('#issueForm').on('submit', function(e) {
        e.preventDefault();
        const ids = getBatchReqIds(selectedBatchRows);
        if (!ids.length) {
            $('#issueMsg').html('<div class="alert alert-danger mb-0">Select an approved request group first.</div>');
            return;
        }

        const facilityId = String($('#issueFacilityId').val() || '').trim();
        const unitId = String($('#issueUnitId').val() || '').trim();
        if (!facilityId) {
            $('#issueMsg').html('<div class="alert alert-danger mb-0">Facility is required.</div>');
            return;
        }

        const $btn = $('#issueSubmitBtn');
        $btn.prop('disabled', true).text('Issuing...');
        $('#issueMsg').html('');

        const payload = {
            action: 'claim_requisition',
            facility_id: facilityId,
            unit_id: unitId,
            remarks: ($('#issueRemarks').val() || '').trim()
        };

        runBatchAction(ids, function(id, done) {
            $.post(PROCESS_URL, Object.assign({}, payload, { requisition_id: id }), function(res) {
                done(!!(res && res.success), (res && res.message) ? res.message : 'Issuance failed.');
            }, 'json').fail(function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while issuing items.';
                done(false, msg);
            });
        }, function(result) {
            $btn.prop('disabled', false).text('Issue Selected Request');
            if (result.successCount > 0) {
                $('#issueMsg').html('<div class="alert alert-success mb-0">' + result.successCount + ' request item(s) issued successfully.</div>');
                $('#issueRemarks').val('');
                selectedBatchKey = '';
                selectedBatchRows = [];
                renderSelectedBatch();
                loadRequestGroups();
                loadIssuedItems();
                if (result.failed.length) {
                    togglePageMsg(result.failed.length + ' issuance action(s) still failed. Check the latest activity log if needed.');
                } else {
                    togglePageMsg('');
                }
                return;
            }
            const firstErr = result.failed[0] ? result.failed[0].message : 'Issuance failed.';
            $('#issueMsg').html('<div class="alert alert-danger mb-0">' + escapeHtml(firstErr) + '</div>');
        });
    });

    $('body').on('click', '.js-thumb-preview', function() {
        const full = $(this).data('full') || $(this).attr('data-full');
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });

    initQrSearch({
        modalSelector: '#requestSearchQrModal',
        previewSelector: '#requestSearchPreview',
        cameraSelectSelector: '#requestSearchCameraSelect',
        startButtonSelector: '#requestSearchBtnStart',
        stopButtonSelector: '#requestSearchBtnStop',
        loadingSelector: '#requestSearchScannerLoading',
        errorSelector: '#requestSearchScanError',
        lastScannedSelector: '#requestSearchLastScanned',
        openButtonSelector: '#openRequestSearchScanner',
        onDetected: function(code) {
            $('#requestSearch').val(code);
            loadRequestGroups();
            $('#requestSearchQrModal').modal('hide');
        }
    });
});
</script>
</body>
</html>
