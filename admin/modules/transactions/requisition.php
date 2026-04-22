<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!role_has("ADMIN")) {
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
        .req-actions-stack {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            width: 100%;
        }
        .req-actions-stack .btn {
            width: 100%;
            white-space: nowrap;
        }
        .req-group-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            flex-wrap: wrap;
        }
        .req-group-meta {
            min-width: 240px;
            display: grid;
            gap: 0.2rem;
            line-height: 1.2;
        }
        .req-group-title {
            font-weight: 600;
        }
        .req-group-sub {
            color: #6c757d;
            font-size: 0.8rem;
        }
        .req-group-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }
        .req-group-actions .btn {
            white-space: nowrap;
        }
        #approveModal .modal-dialog {
            max-width: 96vw;
            margin: 0.75rem auto;
        }
        .approve-row-excluded td {
            background: #fff5f5 !important;
        }
        .approve-reason-input {
            min-width: 170px;
        }
        .approve-qty-input {
            width: 88px;
            margin: 0 auto;
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
                            <option value="not_claimed">Back to Storage</option>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approveReqIds">
                <p class="mb-2">Approve this requisition?</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;" class="text-center">Req ID</th>
                                <th style="width:70px;" class="text-center">Image</th>
                                <th style="width:120px;">Item Code</th>
                                <th>Description</th>
                                <th style="width:60px;" class="text-center">Qty</th>
                                <th style="width:110px;">Requester</th>
                                <th style="width:220px;">Not Approved Reason</th>
                                <th style="width:70px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="approvePreviewBody">
                            <tr>
                                <td colspan="8" class="text-muted small">No request selected.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                <input type="hidden" id="disapproveReqIds">
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
                <input type="hidden" id="claimReqIds">
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

<div class="modal fade" id="backStorageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-exclamation-triangle text-warning"></i>&ensp;Move Back To Storage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="backStorageReqIds">
                <p class="mb-2">Move this requisition back to storage?</p>
                <div class="alert alert-warning mb-0" id="backStorageInfo">This will update the requisition workflow status.</div>
                <div id="backStorageMsg" class="alert alert-danger d-none mt-2 mb-0"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-outline-secondary btn-sm" id="btnConfirmBackStorage">Confirm Move</button>
            </div>
        </div>
    </div>
</div>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const REQ_TYPE = <?php echo json_encode($type); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/transactions/requisition_process.php';
const DEFAULT_DISAPPROVE_REASON_FROM_APPROVE = 'Not approved during grouped approval.';
let reqTable = null;
let claimFacilities = [];
let claimUnits = [];
let reqSearchTimer = null;

function canonicalStatus(value) {
    let raw = String(value || '').trim().toLowerCase();
    if (!raw) return '';
    raw = raw
        .replace(/[^a-z0-9\s_-]/g, '')
        .replace(/[\s-]+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_+|_+$/g, '');

    // Flexible phrase matching for historical/manual status values.
    if ((raw.indexOf('back') !== -1 && raw.indexOf('storage') !== -1) ||
        (raw.indexOf('not') !== -1 && raw.indexOf('claim') !== -1)) {
        return 'not_claimed';
    }
    if (raw.indexOf('for') !== -1 && raw.indexOf('claim') !== -1) {
        return 'for_claiming';
    }
    if (raw.indexOf('disapprov') !== -1) {
        return 'disapproved';
    }

    if (raw === 'back_to_storage' || raw === 'back_storage' || raw === 'storage_back') return 'not_claimed';
    if (raw === 'notclaimed' || raw === 'not_claim') return 'not_claimed';
    if (raw === 'forclaiming' || raw === 'for_claim') return 'for_claiming';

    return raw;
}

function normalizeGroupStatus(statuses) {
    const list = Array.from(new Set((statuses || []).map(function(s){ return canonicalStatus(s); }).filter(Boolean)));
    if (!list.length) return '';
    if (list.length === 1) return list[0];
    if (list.indexOf('not_claimed') !== -1) return 'not_claimed';
    if (list.indexOf('claimed') !== -1) return 'claimed';
    const pendingLike = list.every(function(s){ return s === 'pending' || s === 'reviewed'; });
    if (pendingLike) return 'pending';
    const claimLike = list.every(function(s){ return s === 'for_claiming' || s === 'approved'; });
    if (claimLike) return 'for_claiming';
    return 'mixed';
}

function safeNumber(value, fallback) {
    const n = parseInt(value, 10);
    return Number.isFinite(n) ? n : (fallback || 0);
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
    const type = String(row.module_type || REQ_TYPE || '');
    return [type, requesterId, createdPart, facilityId, unitId].join('|');
}

function normalizeRowWorkflowStatus(row) {
    const src = row || {};
    let raw = canonicalStatus(src.workflow_status || src.status || '');

    const hasClaimAssignment = safeNumber(src.claim_assignment_id, 0) > 0;
    const hasClaimedAt = String(src.claimed_at || '').trim() !== '';
    if (hasClaimAssignment || hasClaimedAt) return 'claimed';

    if (raw === 'approved') return 'for_claiming';
    if (raw === 'for_claiming') return 'for_claiming';
    if (raw === 'not_claimed') return 'not_claimed';
    if (raw === 'claimed') return 'claimed';
    if (raw === 'pending' || raw === 'reviewed' || raw === 'disapproved') return raw;

    // Unknown values should not masquerade as pending.
    return raw || 'unknown';
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
        const k = String(row.batch_group_key || '');
        if (!grouped[k]) grouped[k] = [];
        grouped[k].push(row);
    });

    Object.keys(grouped).forEach(function(k) {
        const bucket = grouped[k] || [];
        const statuses = bucket.map(function(r){ return r.workflow_status; });
        const normalized = normalizeGroupStatus(statuses);
        const itemCount = bucket.length;
        const qtyTotal = bucket.reduce(function(sum, r){ return sum + safeNumber(r.qty_requested, 0); }, 0);
        const ids = bucket.map(function(r){ return safeNumber(r.requisition_id, 0); }).filter(function(id){ return id > 0; });
        const requesterNames = Array.from(new Set(bucket.map(function(r){ return String(r.requester_name || '').trim(); }).filter(Boolean)));

        bucket.forEach(function(r) {
            r.batch_item_count = itemCount;
            r.batch_qty_total = qtyTotal;
            r.batch_requisition_ids = ids;
            r.batch_workflow_status = normalized;
            r.batch_requester_name = requesterNames.length === 1 ? requesterNames[0] : 'Multiple Requesters';
            r.batch_has_multiple_requesters = requesterNames.length > 1;
            r.batch_created_at = bucket[0] && bucket[0].created_at ? bucket[0].created_at : (r.created_at || '');
        });
    });

    return mapped;
}

function getBatchRowsByKey(batchKey) {
    if (!reqTable || !batchKey) return [];
    const rows = reqTable.getRows() || [];
    return rows
        .map(function(r){ return r.getData(); })
        .filter(function(d){ return String(d.batch_group_key || '') === String(batchKey); })
        .sort(function(a, b){ return safeNumber(a.requisition_id, 0) - safeNumber(b.requisition_id, 0); });
}

function getBatchReqIds(batchRows) {
    return (batchRows || []).map(function(r){ return safeNumber(r.requisition_id, 0); }).filter(function(id){ return id > 0; });
}

function getStoredIds(selector) {
    try {
        const raw = $(selector).val() || '[]';
        const arr = JSON.parse(raw);
        return Array.isArray(arr) ? arr.map(function(id){ return safeNumber(id, 0); }).filter(function(id){ return id > 0; }) : [];
    } catch (e) {
        return [];
    }
}

function setStoredIds(selector, ids) {
    $(selector).val(JSON.stringify(Array.isArray(ids) ? ids : []));
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
    let s = canonicalStatus(raw);
    if (!s) s = 'unknown';
    const map = {
        'pending': 'bg-warning text-dark',
        'reviewed': 'bg-info text-dark',
        'approved': 'bg-primary text-white',
        'for_claiming': 'bg-primary text-white',
        'claimed': 'bg-success text-white',
        'mixed': 'bg-dark text-white',
        'back_to_storage': 'bg-secondary text-white',
        'not_claimed': 'bg-secondary text-white',
        'disapproved': 'bg-danger text-white',
        'unknown': 'bg-light text-dark border'
    };
    const labelMap = {
        'back_to_storage': 'Back to Storage',
        'not_claimed': 'Back to Storage',
        'unknown': 'Unknown'
    };
    const cls = map[s] || 'bg-light text-dark border';
    const label = labelMap[s] || s.replace('_', ' ');
    return `<span class="status-badge ${cls}">${escapeHtml(label)}</span>`;
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

function renderApprovePreview(row) {
    const $body = $('#approvePreviewBody');
    const rows = Array.isArray(row) ? row : (row ? [row] : []);
    if (!rows.length) {
        $body.html('<tr><td colspan="8" class="text-muted small">No request selected.</td></tr>');
        return;
    }
    const html = rows.map(function(entry) {
        const reqId = safeNumber(entry.requisition_id, 0);
        const code = escapeHtml(entry.item_code || '-');
        const desc = threeLineText(entry.item_description || '-', '-');
        const moduleType = String(entry.module_type || REQ_TYPE || '').toUpperCase();
        const csmMaxQty = safeNumber(entry.csm_available_qty, 0);
        let qty = safeNumber(entry.qty_requested, 1);
        if (moduleType === 'AST') {
            qty = 1;
        } else if (csmMaxQty > 0 && qty > csmMaxQty) {
            qty = csmMaxQty;
        }
        if (qty <= 0) qty = 1;
        const requester = twoLineText(entry.requester_name || '-', '-');
        const thumbUrl = entry.category_photo_thumb_url || '';
        const fullUrl = entry.category_photo_url || thumbUrl;
        const displayName = String(entry.item_category_name || entry.item_description || 'Item');
        const initials = (displayName.trim().split(/\s+/).map(function(w){ return w.charAt(0); }).filter(Boolean).slice(0, 2).join('') || 'IT').toUpperCase();
        const imageHtml = thumbUrl
            ? '<div class="thumb-wrap"><img class="item-thumb js-thumb-preview" src="' + escapeHtml(thumbUrl) + '" data-full="' + escapeHtml(fullUrl) + '" loading="lazy" alt="Item image"></div>'
            : '<div class="thumb-wrap"><div class="item-badge" title="' + escapeHtml(displayName) + '">' + escapeHtml(initials) + '</div></div>';
        const qtyInputHtml = moduleType === 'AST'
            ? '<input type="number" class="form-control form-control-sm text-center approve-qty-input js-approve-qty" min="1" max="1" step="1" value="1" readonly data-module="AST" data-max="1">'
            : '<div>' +
                '<input type="number" class="form-control form-control-sm text-center approve-qty-input js-approve-qty" min="1"' + (csmMaxQty > 0 ? (' max="' + escapeHtml(String(csmMaxQty)) + '"') : '') + ' step="1" value="' + escapeHtml(String(qty)) + '" data-module="CSM" data-max="' + escapeHtml(String(csmMaxQty)) + '">' +
                '<div class="text-muted small text-center">max ' + escapeHtml(String(Math.max(csmMaxQty, 0))) + '</div>' +
            '</div>';
        return '<tr data-req-id="' + escapeHtml(reqId ? String(reqId) : '') + '" data-module="' + escapeHtml(moduleType) + '" data-max-qty="' + escapeHtml(String(csmMaxQty)) + '">' +
            '<td class="text-center">' + escapeHtml(reqId ? String(reqId) : '-') + '</td>' +
            '<td class="text-center">' + imageHtml + '</td>' +
            '<td><span class="badge bg-light text-dark border">' + code + '</span></td>' +
            '<td>' + desc + '</td>' +
            '<td class="text-center">' + qtyInputHtml + '</td>' +
            '<td>' + requester + '</td>' +
            '<td><textarea class="form-control form-control-sm approve-reason-input js-approve-reason" rows="1" placeholder="Optional reason" disabled></textarea></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-approve-row-remove" title="Exclude from approve"><i class="bi bi-x-lg"></i></button></td>' +
        '</tr>';
    }).join('');
    $body.html(html);
}

function setApproveRowExcluded($row, excluded) {
    if (!$row || !$row.length) return;
    const $reason = $row.find('.js-approve-reason');
    const $qty = $row.find('.js-approve-qty');
    const $btn = $row.find('.js-approve-row-remove');
    $row.toggleClass('approve-row-excluded', !!excluded);
    $reason.prop('disabled', !excluded);
    $qty.prop('disabled', !!excluded);
    if (!excluded) {
        $reason.val('');
    }
    $btn.toggleClass('btn-outline-danger', !excluded);
    $btn.toggleClass('btn-danger', !!excluded);
    $btn.attr('title', excluded ? 'Re-include item for approve' : 'Exclude from approve');
}

function collectApproveDecisions() {
    const decisions = {
        approveItems: [],
        rejectItems: []
    };
    $('#approvePreviewBody tr[data-req-id]').each(function() {
        const $row = $(this);
        const id = safeNumber($row.attr('data-req-id'), 0);
        if (!id) return;
        const excluded = $row.hasClass('approve-row-excluded');
        const reason = ($row.find('.js-approve-reason').val() || '').trim();
        const moduleType = String($row.attr('data-module') || '').toUpperCase();
        const maxQty = safeNumber($row.attr('data-max-qty'), 0);
        const qty = safeNumber($row.find('.js-approve-qty').val(), 0);
        if (excluded) {
            decisions.rejectItems.push({ id: id, reason: reason });
        } else {
            decisions.approveItems.push({ id: id, qty: qty, moduleType: moduleType, maxQty: maxQty });
        }
    });
    return decisions;
}

function buildApproveItemsFromIds(ids) {
    const items = [];
    const rows = reqTable ? (reqTable.getData() || []) : [];
    const rowMap = {};
    rows.forEach(function(r) {
        rowMap[String(r.requisition_id)] = r;
    });
    (ids || []).forEach(function(id) {
        const reqId = safeNumber(id, 0);
        if (!reqId) return;
        const row = rowMap[String(reqId)] || {};
        const moduleType = String(row.module_type || REQ_TYPE || '').toUpperCase();
        const maxQty = safeNumber(row.csm_available_qty, 0);
        let qty = safeNumber(row.qty_requested, 1);
        if (moduleType === 'AST') qty = 1;
        if (qty <= 0) qty = 1;
        items.push({ id: reqId, qty: qty, moduleType: moduleType, maxQty: maxQty });
    });
    return items;
}

function validateApproveItems(approveItems) {
    const list = Array.isArray(approveItems) ? approveItems : [];
    for (let i = 0; i < list.length; i += 1) {
        const item = list[i];
        const id = safeNumber(item.id, 0);
        const qty = safeNumber(item.qty, 0);
        const moduleType = String(item.moduleType || '').toUpperCase();
        const maxQty = safeNumber(item.maxQty, 0);
        if (!id) {
            return { ok: false, message: 'Invalid requisition entry.' };
        }
        if (qty <= 0) {
            return { ok: false, message: 'Qty must be at least 1 for requisition #' + id + '.' };
        }
        if (moduleType === 'AST' && qty !== 1) {
            return { ok: false, message: 'AST requisition #' + id + ' must have Qty 1.' };
        }
        if (moduleType === 'CSM') {
            if (maxQty <= 0) {
                return { ok: false, message: 'No available quantity for CSM requisition #' + id + '.' };
            }
            if (qty > maxQty) {
                return { ok: false, message: 'Qty for requisition #' + id + ' exceeds available quantity (' + maxQty + ').' };
            }
        }
    }
    return { ok: true, message: '' };
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
        groupBy: 'batch_group_key',
        groupStartOpen: true,
        groupHeader: function(value, count, data) {
            const rows = Array.isArray(data) ? data : [];
            const first = rows[0] || {};
            const requester = escapeHtml(first.batch_requester_name || first.requester_name || '-');
            const employment = escapeHtml(first.employment_status || '-');
            const created = escapeHtml(String(first.batch_created_at || first.created_at || '-'));
            const totalQty = rows.reduce(function(sum, r){ return sum + safeNumber(r.qty_requested, 0); }, 0);
            const statuses = rows.map(function(r){ return r.workflow_status; });
            const groupStatus = normalizeGroupStatus(statuses);
            const statusHtml = statusBadge(groupStatus);
            const encodedKey = encodeURIComponent(String(value || ''));

            let actionsHtml = '';
            if (groupStatus === 'pending' || groupStatus === 'reviewed') {
                actionsHtml = '' +
                    '<button class="btn btn-sm btn-success btn-batch-approve" data-batch="' + encodedKey + '">Approve Group</button>' +
                    '<button class="btn btn-sm btn-danger btn-batch-disapprove" data-batch="' + encodedKey + '">Disapprove Group</button>';
            } else if (groupStatus === 'for_claiming' || groupStatus === 'approved') {
                actionsHtml = '' +
                    '<button class="btn btn-sm btn-outline-secondary btn-batch-back-storage" data-batch="' + encodedKey + '">Back Group to Storage</button>';
            }

            return '' +
                '<div class="req-group-header">' +
                    '<div class="req-group-meta">' +
                        '<div class="req-group-title">' + requester + ' <span class="text-muted">(' + count + ' item' + (count > 1 ? 's' : '') + ', Qty ' + totalQty + ')</span></div>' +
                        '<div class="req-group-sub">Employment: ' + employment + '</div>' +
                        '<div class="req-group-sub">Created: ' + created + ' &nbsp; ' + statusHtml + '</div>' +
                    '</div>' +
                    '<div class="req-group-actions">' + actionsHtml + '</div>' +
                '</div>';
        },
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
            return enrichRowsWithBatch(response.data || []);
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
            { title: 'Req ID', field: 'requisition_id', width: 70, hozAlign: 'center' },
            { title: 'Item Code', field: 'item_code', width: 125, formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? `<span class="badge bg-light text-dark border">${v}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: 'Qty', field: 'qty_requested', width: 55, hozAlign: 'center' },
            { title: 'Workflow', field: 'workflow_status', width: 120, formatter: function(cell){
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
            }}
        ]
    });

    $('#reqTable').on('click', '.btn-batch-approve', function() {
        const encoded = String($(this).data('batch') || '');
        const batchKey = decodeURIComponent(encoded);
        const rows = getBatchRowsByKey(batchKey);
        const ids = getBatchReqIds(rows);
        setStoredIds('#approveReqIds', ids);
        renderApprovePreview(rows);
        $('#approveMsg').addClass('d-none').text('');
        $('#approveModal').modal('show');
    });

    $('#approvePreviewBody').on('click', '.js-approve-row-remove', function() {
        const $row = $(this).closest('tr[data-req-id]');
        const nextExcluded = !$row.hasClass('approve-row-excluded');
        setApproveRowExcluded($row, nextExcluded);
    });

    $('#approvePreviewBody').on('input change', '.js-approve-qty', function() {
        const $input = $(this);
        const moduleType = String($input.data('module') || '').toUpperCase();
        const maxQty = safeNumber($input.data('max'), 0);
        let qty = safeNumber($input.val(), 0);
        if (qty <= 0) qty = 1;
        if (moduleType === 'AST') {
            qty = 1;
        } else if (maxQty > 0 && qty > maxQty) {
            qty = maxQty;
        }
        $input.val(String(qty));
    });

    $('#btnConfirmApprove').on('click', function() {
        const decisions = collectApproveDecisions();
        let approveItems = decisions.approveItems;
        const rejectItems = decisions.rejectItems;
        if (!approveItems.length && !rejectItems.length) {
            approveItems = buildApproveItemsFromIds(getStoredIds('#approveReqIds'));
        }
        if (!approveItems.length && !rejectItems.length) {
            $('#approveMsg').removeClass('d-none').text('No requisition selected.');
            return;
        }
        const validation = validateApproveItems(approveItems);
        if (!validation.ok) {
            $('#approveMsg').removeClass('d-none').text(validation.message || 'Invalid quantity values.');
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');

        runBatchAction(approveItems, function(item, done) {
            $.post(PROCESS_URL, { action: 'approve_requisition', requisition_id: item.id, qty_requested: item.qty }, function(res) {
                done(!!(res && res.success), (res && res.message) ? res.message : 'Approval failed.');
            }, 'json').fail(function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while approving.';
                done(false, msg);
            });
        }, function(approveResult) {
            const rejectIds = rejectItems.map(function(item){ return item.id; });
            const rejectReasonById = {};
            rejectItems.forEach(function(item) {
                rejectReasonById[item.id] = item.reason || DEFAULT_DISAPPROVE_REASON_FROM_APPROVE;
            });

            runBatchAction(rejectIds, function(id, done) {
                $.post(PROCESS_URL, {
                    action: 'disapprove_requisition',
                    requisition_id: id,
                    reason: rejectReasonById[id] || DEFAULT_DISAPPROVE_REASON_FROM_APPROVE
                }, function(res) {
                    done(!!(res && res.success), (res && res.message) ? res.message : 'Disapproval failed.');
                }, 'json').fail(function(xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while disapproving.';
                    done(false, msg);
                });
            }, function(rejectResult) {
                $btn.prop('disabled', false).text('Confirm Approve');

                const approvedCount = approveResult.successCount || 0;
                const disapprovedCount = rejectResult.successCount || 0;
                const failed = (approveResult.failed || []).concat(rejectResult.failed || []);

                if (approvedCount > 0 || disapprovedCount > 0) {
                    $('#approveModal').modal('hide');
                    loadReqData();
                    const parts = [];
                    if (approvedCount > 0) parts.push(approvedCount + ' approved');
                    if (disapprovedCount > 0) parts.push(disapprovedCount + ' disapproved');
                    const summary = parts.join(', ');
                    if (failed.length) {
                        error_notif(summary + '. ' + failed.length + ' failed.');
                    } else {
                        success_notif(summary + '.');
                    }
                } else {
                    const firstErr = failed[0] ? failed[0].message : 'Approval failed.';
                    $('#approveMsg').removeClass('d-none').text(firstErr);
                }
            });
        });
    });

    $('#reqTable').on('click', '.btn-batch-disapprove', function() {
        const encoded = String($(this).data('batch') || '');
        const batchKey = decodeURIComponent(encoded);
        const rows = getBatchRowsByKey(batchKey);
        const ids = getBatchReqIds(rows);
        setStoredIds('#disapproveReqIds', ids);
        $('#disapproveReason').val('');
        $('#disapproveMsg').addClass('d-none').text('');
        $('#disapproveModal').modal('show');
    });

    $('#btnConfirmDisapprove').on('click', function() {
        const ids = getStoredIds('#disapproveReqIds');
        const reason = ($('#disapproveReason').val() || '').trim();
        if (!ids.length) {
            $('#disapproveMsg').removeClass('d-none').text('No requisition selected.');
            return;
        }
        if (!reason) {
            $('#disapproveMsg').removeClass('d-none').text('Reason is required.');
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');
        runBatchAction(ids, function(id, done) {
            $.post(PROCESS_URL, { action: 'disapprove_requisition', requisition_id: id, reason: reason }, function(res) {
                done(!!(res && res.success), (res && res.message) ? res.message : 'Disapproval failed.');
            }, 'json').fail(function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while disapproving.';
                done(false, msg);
            });
        }, function(result) {
            $btn.prop('disabled', false).text('Confirm Disapprove');
            if (result.successCount > 0) {
                $('#disapproveModal').modal('hide');
                loadReqData();
                if (result.failed.length) {
                    error_notif(result.successCount + ' request(s) disapproved. ' + result.failed.length + ' failed.');
                } else {
                    success_notif(result.successCount + ' request(s) disapproved.');
                }
                return;
            }
            const firstErr = result.failed[0] ? result.failed[0].message : 'Disapproval failed.';
            $('#disapproveMsg').removeClass('d-none').text(firstErr);
        });
    });

    $('#reqTable').on('click', '.btn-batch-claim', function() {
        const encoded = String($(this).data('batch') || '');
        const batchKey = decodeURIComponent(encoded);
        const rows = getBatchRowsByKey(batchKey);
        const ids = getBatchReqIds(rows);
        const data = rows[0] || null;
        if (!data || !ids.length) {
            error_notif('Requisition row not found.');
            return;
        }
        const requesterIds = Array.from(new Set(rows.map(function(r){ return String(r.requester_user_id || '').trim(); }).filter(Boolean)));
        setStoredIds('#claimReqIds', ids);
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
        $('#claimIssuedToUserId').val(requesterIds.length === 1 ? String(data.requester_user_id || '') : '');
        $('#claimIssuedToName').val(requesterIds.length === 1 ? (data.requester_name || '') : ('Multiple (' + requesterIds.length + ')'));
        $('#claimManagedByUserId').val('');
        $('#claimManagedByName').val('');
        $('#claimMsg').html('');
        $('#claimModal').modal('show');
    });

    $('#reqTable').on('click', '.btn-batch-back-storage', function() {
        const encoded = String($(this).data('batch') || '');
        const batchKey = decodeURIComponent(encoded);
        const rows = getBatchRowsByKey(batchKey);
        const ids = getBatchReqIds(rows);
        if (!ids.length) return;
        setStoredIds('#backStorageReqIds', ids);
        $('#backStorageInfo').text('This will update ' + ids.length + ' requisition item(s) and move them back to storage.');
        $('#backStorageMsg').addClass('d-none').text('');
        $('#backStorageModal').modal('show');
    });

    $('#btnConfirmBackStorage').on('click', function(){
        const ids = getStoredIds('#backStorageReqIds');
        if (!ids.length) {
            $('#backStorageMsg').removeClass('d-none').text('Invalid requisition id.');
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');
        runBatchAction(ids, function(id, done) {
            $.post(PROCESS_URL, { action: 'decline_requisition_claim', requisition_id: id }, function(res){
                done(!!(res && res.success), (res && res.message) ? res.message : 'Unable to move requisition back to storage.');
            }, 'json').fail(function(xhr){
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while updating requisition.';
                done(false, msg);
            });
        }, function(result) {
            $btn.prop('disabled', false).text('Confirm Move');
            if (result.successCount > 0) {
                $('#backStorageModal').modal('hide');
                loadReqData();
                if (result.failed.length) {
                    error_notif(result.successCount + ' request(s) moved back to storage. ' + result.failed.length + ' failed.');
                } else {
                    success_notif(result.successCount + ' request(s) moved back to storage.');
                }
                return;
            }
            const firstErr = result.failed[0] ? result.failed[0].message : 'Unable to move requisitions back to storage.';
            $('#backStorageMsg').removeClass('d-none').text(firstErr);
        });
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
        const ids = getStoredIds('#claimReqIds');
        if (!ids.length) {
            $('#claimMsg').html('<div class="alert alert-danger mb-0">No requisition selected.</div>');
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');
        const payload = {
            action: 'claim_requisition',
            facility_id: $('#claimFacilityId').val(),
            unit_id: $('#claimUnitId').val(),
            issued_to_user_id: $('#claimIssuedToUserId').val(),
            managed_by_user_id: $('#claimManagedByUserId').val(),
            remarks: ($('#claimRemarks').val() || '').trim()
        };
        runBatchAction(ids, function(id, done) {
            const requestPayload = Object.assign({}, payload, { requisition_id: id });
            $.post(PROCESS_URL, requestPayload, function(res){
                done(!!(res && res.success), (res && res.message) ? res.message : 'Claim failed.');
            }, 'json').fail(function(xhr){
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while claiming.';
                done(false, msg);
            });
        }, function(result) {
            $btn.prop('disabled', false).text('Confirm Claim');
            if (result.successCount > 0) {
                $('#claimModal').modal('hide');
                loadReqData();
                if (result.failed.length) {
                    error_notif(result.successCount + ' request(s) claimed. ' + result.failed.length + ' failed.');
                } else {
                    success_notif(result.successCount + ' request(s) claimed.');
                }
                return;
            }
            const firstErr = result.failed[0] ? result.failed[0].message : 'Claim failed.';
            $('#claimMsg').html('<div class="alert alert-danger mb-0">' + firstErr + '</div>');
        });
    });
}

$(document).ready(function() {
    // Keep image preview and other secondary modals above the currently open modal.
    $(document).on('show.bs.modal', '.modal', function () {
        const zIndex = 1050 + (10 * $('.modal.show').length);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack').first().css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal.show').length > 0) {
            $('body').addClass('modal-open');
        }
    });

    loadClaimLookups();
    initReqTable();
    $('#reqRefresh').on('click', loadReqData);
    $('#reqSearch').on('keyup', function(){
        clearTimeout(reqSearchTimer);
        reqSearchTimer = setTimeout(loadReqData, 300);
    });
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
