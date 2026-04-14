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

function loadItems() {
    const search = ($('#itemSearch').val() || '').trim();
    if (!table) return;
    table.setData(PROCESS_URL, { action: 'list_available_items', type: currentType, search }, 'POST');
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
            { title: 'Item Code', field: 'item_code', width: 125, headerFilter: 'input', formatter: function(cell){
                const v = escapeHtml(cell.getValue() || '');
                return v ? `<span class="badge bg-light text-dark border">${v}</span>` : '-';
            }},
            { title: 'Description', field: 'item_description', widthGrow: 2, minWidth: 180, headerFilter: 'input', formatter: function(cell){
                return threeLineText(cell.getValue());
            }},
            { title: 'Stock', field: 'available_qty', width: 85, hozAlign: 'center', formatter: function(cell){
                if (currentType === 'AST') return 'Available';
                const v = parseInt(cell.getValue(), 10);
                return Number.isFinite(v) ? v : 0;
            }},
            { title: 'Unit', field: 'unit', width: 80 },
            { title: 'Allowed Status', field: 'allowed_status_names', widthGrow: 1, minWidth: 140, formatter: function(cell){
                const v = cell.getValue();
                return v ? `<span class="text-muted small">${escapeHtml(v)}</span>` : 'All';
            }},
            { title: 'Action', field: 'item_code', width: 110, hozAlign: 'center', formatter: function(cell){
                const code = escapeHtml(cell.getValue() || '');
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
        $('#reqFacilityUnit').val(null).trigger('change');
        loadRequestFacilityUnits();
        $('#requestModal').modal('show');
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
        ajaxConfig: 'POST',
        layout: 'fitColumns',
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
            { title: 'Updated', field: 'updated_at', width: 130, formatter: function(cell){
                const v = String(cell.getValue() || '');
                if (!v) return '-';
                const parts = v.split(/\s+/);
                if (parts.length >= 2) {
                    return `<div style="line-height:1.2;white-space:normal;">${escapeHtml(parts[0])}<br><span class="text-muted">${escapeHtml(parts.slice(1).join(' '))}</span></div>`;
                }
                return escapeHtml(v);
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
        if (table) {
            table.setData(PROCESS_URL, { action: 'list_available_items', type: currentType }, 'POST');
        }
    });

    $('body').on('click', '.js-thumb-preview', function() {
        const full = $(this).data('full') || $(this).attr('data-full');
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });

    $('#btnSubmitRequest').on('click', function() {
        if (!reqSelected) {
            $('#reqModalMsg').removeClass('d-none').text('No item selected.');
            return;
        }
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
});
</script>
</html>


