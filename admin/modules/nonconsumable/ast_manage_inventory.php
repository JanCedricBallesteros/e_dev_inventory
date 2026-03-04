<?php
// admin/modules/nonconsumable/ast_manage_inventory.php
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
        user_has_access(array("AST", "PO"))
    )
)) {
    header("Location: " . BASE_URL);
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
    <link href="<?= BASE_URL ?>assets/css/select2.min.css" rel="stylesheet"></style>
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .section-card .card-header { background: var(--bg-eclearance-rgb); color: #fff; font-weight: 600; }
        .badge-code { font-family: 'Courier New', monospace; }
        .form-section-title { font-size: 0.95rem; font-weight: 700; text-transform: uppercase; color: #6c757d; letter-spacing: .5px; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Select2 Bootstrap styling */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        /* Fix Select2 with button alignment */
        .select2-with-button {
            display: flex;
            gap: 0;
        }
        .select2-with-button .select2-container {
            flex: 1;
        }
        .select2-with-button .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: -1px;
        }
        .select2-with-button .select2-container .select2-selection {
/* SIX SEVEN SAIS SYETE ANIM PITO */
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        #inventoryTable { min-height: 220px; }
        .qr-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
        }
        .qr-preview-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            background: #fff;
        }
        .qr-preview-img {
            width: 100%;
            max-width: 100px;
            height: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px;
            background: #fff;
            margin: 0 auto;
            display: block;
        }
        .qr-preview-code {
            margin-top: 6px;
            font-size: 11px;
            word-break: break-word;
            color: #495057;
        }
        .review-table-wrap {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">Manage Non-Consumable Inventory</h1>
        <p class="text-muted small mb-0">Add items, bulk import/update, and control availability for requestors.</p>
    </div>

    <section class="section">
        <div id="pageMsg" class="alert alert-danger d-none mb-3"></div>
        <div class="row g-3">
            <!-- Add New Item -->
            <div class="col-12">
                <div class="card section-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-plus-circle"></i>&ensp;Add New Item</span>
                    </div>
                    <div class="card-body">
                        <form id="addItemForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_item">
                            <input type="hidden" name="property_series" id="propertySeriesInput" value="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Item Category</label>
                                    <select class="form-select" name="category_id" id="categorySelect" required>
                                        <option value="">Select category</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Property Number (base)</label>
                                    <input type="text" class="form-control" name="property_number" id="propertyNumberField" placeholder="Enter property no. (e.g., A12B)" required autocomplete="off" inputmode="text" pattern="[A-Za-z0-9]+">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Property Code (auto)</label>
                                    <input type="text" class="form-control" id="propertyCodeField" placeholder="AST-XXX-0001 ... AST-XXX-000N" readonly>
                                </div>

                                <input type="hidden" name="number_of_units" id="numberOfUnits" value="1">
                                <input type="hidden" name="serial_numbers_json" id="serialNumbersJson" value="[]">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Unit</label>
                                    <select class="form-select" name="unit" id="unitSelect" required>
                                        <option value="">Select or type unit</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Source of Fund (optional)</label>
                                    <input type="text" class="form-control" name="source_of_fund" id="sourceOfFund">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Cost Value (optional)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="cost_value" id="costValue" placeholder="0.00">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Item Description</label>
                                    <textarea class="form-control" name="item_description" id="itemDescription" rows="2" placeholder="Describe the item" required></textarea>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label fw-semibold mb-0">Unit Rows</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="addUnitRowBtn">
                                            <i class="bi bi-plus-lg"></i> Add Row
                                        </button>
                                    </div>
                                    <div id="unitRows" class="d-flex flex-column gap-2"></div>
                                    <div class="small text-muted mt-1">Each row = one item unit to be created.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">QR Code (auto-generated)</label>
                                    <div class="border rounded p-2 bg-white">
                                        <div id="qrLoading" class="text-muted small py-3" style="display: none;">
                                            <i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i>
                                            <br>Generating QR code...
                                        </div>
                                        <div id="qrPreviewList" class="qr-preview-grid"></div>
                                        <div id="qrPreviewMeta" class="small text-muted mt-2">Based on the property code</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Item
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="resetAddForm">Clear</button>
                            </div>
                            <div id="addItemMsg" class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Quantity (hidden temporarily) -->
            <div class="col-12 col-xl-4 d-none">
                <div class="card section-card h-100">
                    <div class="card-header"><i class="bi bi-plus-square"></i>&ensp;Add Quantity (Existing Item)</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Property Code</label>
                            <div class="select2-with-button">
                                <select class="form-select" id="searchPropertyCode">
                                    <option value="">Select property code</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" id="openAddQtyScanner" title="Scan QR">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
                            </div>
                            <div class="small text-muted mt-1">Last scanned: <span id="addQtyLastScanned">-</span></div>
                        </div>
                        <div id="itemInfo" class="mb-3 text-muted small">No item loaded.</div>
                        <form id="addQtyForm">
                            <input type="hidden" name="action" value="add_quantity">
                            <input type="hidden" name="property_code" id="addQtyPropertyCode">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Quantity to Add</label>
                                <input type="number" class="form-control" name="add_qty" id="addQtyValue" min="1" value="1">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Source of Fund</label>
                                    <input type="text" class="form-control" name="source_of_fund" id="addQtySource">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Cost Value</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="cost_value" id="addQtyCost">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success w-100"><i class="bi bi-arrow-up-circle"></i> Apply Quantity</button>
                            </div>
                            <div id="addQtyMsg" class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Inventory List -->
            <div class="col-12">
                <div class="card section-card h-100">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2">
                            <span><i class="bi bi-table"></i>&ensp;Recent Items</span>
                            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light btn-sm border" id="bulkSetAvailability">
                                        <i class="bi bi-sliders"></i> Bulk Set Rules
                                    </button>
                                    <button class="btn btn-light btn-sm border" id="clearSelection">
                                        Clear Selection
                                    </button>
                                </div>
                                <div class="input-group" style="max-width:360px;">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control form-control-sm" id="tableSearch" placeholder="Search property code or serial no.">
                                    <button class="btn btn-light btn-sm border" type="button" id="openSearchScanner" title="Scan QR">
                                        <i class="bi bi-qr-code-scan"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="inventoryTablePlaceholder" class="text-muted small mb-2">
                            Scroll to load recent items table.
                        </div>
                        <div id="inventoryTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<!-- SET AVAILABILITY MODAL -->
<div class="modal fade" id="availabilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-sliders"></i>&ensp;Set Available Item Rules</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="availabilityForm">
                    <input type="hidden" name="action" value="update_availability_settings">
                    <input type="hidden" name="property_code" id="availPropertyCode">
                    <input type="hidden" name="bulk_codes" id="availBulkCodes">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Property Code</label>
                        <input type="text" class="form-control" id="availPropertyCodeDisplay" readonly>
                        <div class="small text-muted" id="availBulkNote" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Available for Requisition (Qty)</label>
                        <input type="number" min="0" class="form-control" name="available_qty" id="availQty">
                        <div class="small text-muted">Must be between 0 and total quantity.</div>
                        <div class="small text-muted">Bulk set: quantity is disabled (only status is updated).</div>
                        <div class="small text-muted">Bulk set: leave blank to keep each item's current available qty.</div>
                        <div class="small text-muted">Total Qty: <span id="availTotalQty">0</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Allowed Employment Status</label>
                        <div class="small text-muted mb-2">Choose allowed status per position. "None" means no one can request.</div>
                        <div class="mb-2">
                            <div class="fw-semibold small text-muted">Teaching Personnel</div>
                            <select id="availTeachingStatus" class="form-select" multiple></select>
                        </div>
                        <div>
                            <div class="fw-semibold small text-muted">Non-Teaching Personnel</div>
                            <select id="availNonTeachingStatus" class="form-select" multiple></select>
                        </div>
                    </div>
                    <div id="availMsg" class="mt-2"></div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Rules</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SEARCH QR MODAL -->
<div class="modal fade" id="searchQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Search</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <select id="searchCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <button type="button" id="searchBtnStart" class="btn btn-success btn-sm">Start</button>
                    <button type="button" id="searchBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                </div>
                <div id="searchPreviewWrapper" style="width:100%;max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1;">
                    <div id="searchPreview" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
                    <div class="scanner-loading" id="searchScannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
                        <div>Initializing camera...</div>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="searchLastScanned" class="fw-semibold">�"</span>
                </div>
                <div id="searchScanError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ADD QTY QR MODAL -->
<div class="modal fade" id="addQtyQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR to Add Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <select id="addQtyCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                        <option value="">Loading cameras...</option>
                    </select>
                    <div class="d-flex gap-2">
                        <button type="button" id="addQtyBtnStart" class="btn btn-success btn-sm">Start</button>
                        <button type="button" id="addQtyBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div style="width:100%;max-width:420px;aspect-ratio:1;background:#000;border-radius:10px;overflow:hidden;position:relative;">
                        <div id="addQtyPreview" style="position:absolute;inset:0;"></div>
                        <div id="addQtyScannerLoading" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#fff;font-size:14px;">
                            Initializing camera...
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="text-muted">Last scanned:</span>
                        <span id="addQtyScanLast" class="fw-semibold">-</span>
                    </div>
                    <div id="addQtyScanError" class="text-danger small mt-1" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- REVIEW ADD ITEM MODAL -->
<div class="modal fade" id="reviewAddItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-check2-square"></i>&ensp;Review Item(s) Before Save</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="reviewAddItemError" class="alert alert-danger d-none mb-3"></div>
                <div id="reviewAddItemLoading" class="text-muted small py-2 d-none">
                    <i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i>
                    Preparing review...
                </div>
                <div id="reviewAddItemContent" style="display:none;">
                    <div class="row g-2 mb-3 small">
                        <div class="col-md-6"><span class="text-muted">Category:</span> <span class="fw-semibold" id="reviewCategory"></span></div>
                        <div class="col-md-6"><span class="text-muted">Property Number:</span> <span class="fw-semibold" id="reviewPropertyNumber"></span></div>
                        <div class="col-md-6"><span class="text-muted">Description:</span> <span class="fw-semibold" id="reviewDescription"></span></div>
                        <div class="col-md-6"><span class="text-muted">Unit:</span> <span class="fw-semibold" id="reviewUnit"></span></div>
                        <div class="col-md-6"><span class="text-muted">Source of Fund:</span> <span class="fw-semibold" id="reviewSource"></span></div>
                        <div class="col-md-6"><span class="text-muted">Cost:</span> <span class="fw-semibold" id="reviewCost"></span></div>
                        <div class="col-md-6"><span class="text-muted">Units to Create:</span> <span class="fw-semibold" id="reviewUnits"></span></div>
                        <div class="col-md-6"><span class="text-muted">Property Code Range:</span> <span class="fw-semibold" id="reviewRange"></span></div>
                    </div>
                    <div class="review-table-wrap">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:80px;">Unit #</th>
                                    <th style="width:260px;">Property Code</th>
                                    <th>Serial Number</th>
                                </tr>
                            </thead>
                            <tbody id="reviewUnitsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                <button type="button" class="btn btn-primary" id="confirmAddItemBtn">
                    <i class="bi bi-save"></i> Confirm Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';
const QR_GENERATOR_URL = BASE_URL + 'admin/modules/tools/qr_image.php';

let inventoryTable = null;
let categoriesCache = [];
let propertyCodesCache = [];
let propertyNumberTimeout = null;
let propertyCodeRequestSeq = 0;
let propertyCodeXhr = null;
let pendingAddItemDraft = null;
let isSavingAddItem = false;
let pageMsgTimeout = null;
let employmentStatuses = [];
let availTeachingSelect = null;
let availNonTeachingSelect = null;
const NONE_STATUS_VALUE = 'NONE';
let availStatusLock = false;
let isBulkAvailMode = false;

function setSelectizeValues(selectize, values) {
    if (!selectize) return;
    const arr = Array.isArray(values)
        ? values
        : (values ? String(values).split(',') : []);
    const filtered = arr
        .filter(v => v !== null && v !== undefined && v !== '')
        .map(v => String(v))
        .filter(v => Object.prototype.hasOwnProperty.call(selectize.options, v));
    selectize.clear(true);
    if (filtered.length) {
        selectize.setValue(filtered, true);
        return;
    }
    if (Object.prototype.hasOwnProperty.call(selectize.options, NONE_STATUS_VALUE)) {
        selectize.setValue([NONE_STATUS_VALUE], true);
    }
}

function updateAvailQtyState() {
    const $input = $('#availQty');
    if (!$input.length) return;
    if (isBulkAvailMode) {
        $input.val('');
        $input.prop('disabled', true);
        $input.removeAttr('max');
        return;
    }
    const teachRaw = availTeachingSelect ? availTeachingSelect.getValue() : [];
    const nonRaw = availNonTeachingSelect ? availNonTeachingSelect.getValue() : [];
    const teachArr = Array.isArray(teachRaw) ? teachRaw : (teachRaw ? String(teachRaw).split(',') : []);
    const nonArr = Array.isArray(nonRaw) ? nonRaw : (nonRaw ? String(nonRaw).split(',') : []);
    const teachHas = teachArr.filter(v => String(v) !== NONE_STATUS_VALUE).length > 0;
    const nonHas = nonArr.filter(v => String(v) !== NONE_STATUS_VALUE).length > 0;
    const allNone = !teachHas && !nonHas;
    if (allNone) {
        $input.val(0);
        $input.prop('disabled', true);
    } else {
        $input.prop('disabled', false);
    }
    const totalQty = parseInt($('#availTotalQty').text(), 10);
    if (!isNaN(totalQty) && totalQty >= 0) {
        $input.attr('max', totalQty);
    } else {
        $input.removeAttr('max');
    }
}


function showMessage(target, type, text) {
    $(target).html(`<div class="alert alert-${type} mb-2">${text}</div>`);
}

function showPageMessage(message) {
    const el = $('#pageMsg');
    if (!message) {
        el.addClass('d-none').text('');
        return;
    }
    el.removeClass('d-none').text(message);
    if (pageMsgTimeout) clearTimeout(pageMsgTimeout);
    pageMsgTimeout = setTimeout(() => {
        el.addClass('d-none').text('');
    }, 4000);
}


function loadCategories() {
    $.post(PROCESS_URL, { action: 'list_categories' }, function(res) {
        if (res.success) {
            categoriesCache = res.data || [];
            const options = ['<option value="">Select category</option>'];
            categoriesCache.forEach(cat => {
                options.push(`<option value="${cat.category_id}">${cat.item_category_name}</option>`);
            });
            $('#categorySelect').html(options.join(''));
            
            // Initialize Select2 for searchable dropdown
            $('#categorySelect').select2({
                placeholder: 'Select category',
                allowClear: true,
                width: '100%'
            });
            showPageMessage('');
        } else {
            $('#categorySelect').html('<option value="">Failed to load</option>');
            showPageMessage(res.message || 'Failed to load categories.');
        }
    }, 'json').fail(function() {
        $('#categorySelect').html('<option value="">Failed to load</option>');
        showPageMessage('Server error while loading categories.');
    });
}

function loadUnits() {
    $.post(PROCESS_URL, { action: 'list_units' }, function(res) {
        if (res.success) {
            const units = res.data || [];
            const options = ['<option value="">Select or type unit</option>'];
            units.forEach(unit => {
                options.push(`<option value="${unit}">${unit}</option>`);
            });
            $('#unitSelect').html(options.join(''));

            $('#unitSelect').select2({
                placeholder: 'Select or type unit',
                allowClear: true,
                tags: true,
                width: '100%'
            });
            showPageMessage('');
        } else {
            $('#unitSelect').html('<option value="">Failed to load</option>');
            showPageMessage(res.message || 'Failed to load units.');
        }
    }, 'json').fail(function() {
        $('#unitSelect').html('<option value="">Failed to load</option>');
        showPageMessage('Server error while loading units.');
    });
}

function loadPropertyCodes() {
    $.post(PROCESS_URL, { action: 'list_property_codes', limit: 1000 }, function(res) {
        if (res.success) {
            propertyCodesCache = res.data || [];
            const options = ['<option value="">Select property code</option>'];
            propertyCodesCache.forEach(item => {
                const category = item.item_category_name ? `[${item.item_category_name}] ` : '';
                const description = item.item_description ? ` - ${item.item_description}` : '';
                const label = `${item.property_code} ${category}${description}`;
                options.push(`<option value="${item.property_code}">${label}</option>`);
            });
            $('#searchPropertyCode').html(options.join(''));
            
            // Initialize Select2 for searchable dropdown
            $('#searchPropertyCode').select2({
                placeholder: 'Search property code',
                allowClear: true,
                width: '100%'
            }).on('select2:select', function(e) {
                const code = e.params.data.id;
                if (code) {
                    $('#addQtyLastScanned').text(code);
                    loadItemByCode(code);
                }
            }).on('select2:clear', function() {
                $('#itemInfo').html('<div class="text-muted small">No item loaded.</div>');
                $('#addQtyPropertyCode').val('');
                $('#addQtyLastScanned').text('-');
            });
            showPageMessage('');
        } else {
            $('#searchPropertyCode').html('<option value="">Failed to load</option>');
            showPageMessage(res.message || 'Failed to load property codes.');
        }
    }, 'json').fail(function() {
        $('#searchPropertyCode').html('<option value="">Failed to load</option>');
        showPageMessage('Server error while loading property codes.');
    });
}

function loadEmploymentStatuses() {
    return $.post(PROCESS_URL, { action: 'list_employment_status' }, function(res) {
        if (res && res.success) {
            employmentStatuses = res.data || [];
            initAvailabilitySelect();
        }
    }, 'json');
}

function initAvailabilitySelect() {
    if (!document.getElementById('availTeachingStatus')) return;
    if (availTeachingSelect) {
        availTeachingSelect.clear();
        availTeachingSelect.clearOptions();
        availTeachingSelect.destroy();
        availTeachingSelect = null;
    }
    if (availNonTeachingSelect) {
        availNonTeachingSelect.clear();
        availNonTeachingSelect.clearOptions();
        availNonTeachingSelect.destroy();
        availNonTeachingSelect = null;
    }
    const options = [{ employment_status_id: NONE_STATUS_VALUE, status_name: 'None', status_code: 'NONE' }]
        .concat(employmentStatuses || []);
    const baseConfig = {
        valueField: 'employment_status_id',
        labelField: 'status_name',
        searchField: ['status_name', 'status_code'],
        options: options,
        persist: false,
        maxItems: null,
        plugins: ['remove_button'],
        create: false,
        onChange: function() {
            if (availStatusLock) return;
            const vals = this.getValue();
            const arr = Array.isArray(vals) ? vals : (vals ? String(vals).split(',') : []);
            if (!arr.length) {
                availStatusLock = true;
                this.setValue([NONE_STATUS_VALUE], true);
                availStatusLock = false;
            }
            updateAvailQtyState();
        },
        onItemAdd: function(value) {
            if (availStatusLock) return;
            const vals = this.getValue();
            if (!Array.isArray(vals)) return;
            if (value === NONE_STATUS_VALUE) {
                availStatusLock = true;
                this.setValue([NONE_STATUS_VALUE], true);
                availStatusLock = false;
                return;
            }
            if (vals.includes(NONE_STATUS_VALUE) && vals.length > 1) {
                const cleaned = vals.filter(v => v !== NONE_STATUS_VALUE);
                availStatusLock = true;
                this.setValue(cleaned, true);
                availStatusLock = false;
            }
        }
    };
    availTeachingSelect = $('#availTeachingStatus').selectize(baseConfig)[0].selectize;
    availNonTeachingSelect = $('#availNonTeachingStatus').selectize(baseConfig)[0].selectize;
    availStatusLock = true;
    availTeachingSelect.setValue([NONE_STATUS_VALUE], true);
    availNonTeachingSelect.setValue([NONE_STATUS_VALUE], true);
    availStatusLock = false;
    updateAvailQtyState();
}


function openAvailabilityModal(code) {
    if (!code) return;
    isBulkAvailMode = false;
    $('#availMsg').html('');
    $('#availPropertyCode').val(code);
    $('#availPropertyCodeDisplay').val(code);
    $('#availBulkCodes').val('');
    $('#availBulkNote').hide().text('');
    $('#availQty').val('');
    $('#availTotalQty').text('0');
    if (availTeachingSelect) availTeachingSelect.clear();
    if (availNonTeachingSelect) availNonTeachingSelect.clear();
    updateAvailQtyState();
    $.post(PROCESS_URL, { action: 'get_availability_settings', property_code: code }, function(res) {
        if (res && res.success) {
            $('#availQty').val(res.data.available_qty);
            $('#availTotalQty').text(res.data.quantity);
            const allowed = res.data.allowed_employment_status || {};
            const teach = allowed.teaching || [];
            const non = allowed.non_teaching || [];
            const mode = allowed.mode || 'all';
            const allIds = (employmentStatuses || []).map(s => String(s.employment_status_id));
            if (availTeachingSelect) {
                let tvals = [];
                if (mode === 'none') {
                    tvals = [NONE_STATUS_VALUE];
                } else if (mode === 'all') {
                    tvals = allIds;
                } else {
                    tvals = (teach && teach.length) ? teach : [NONE_STATUS_VALUE];
                }
                availStatusLock = true;
                setSelectizeValues(availTeachingSelect, tvals);
                availStatusLock = false;
            }
            if (availNonTeachingSelect) {
                let nvals = [];
                if (mode === 'none') {
                    nvals = [NONE_STATUS_VALUE];
                } else if (mode === 'all') {
                    nvals = allIds;
                } else {
                    nvals = (non && non.length) ? non : [NONE_STATUS_VALUE];
                }
                availStatusLock = true;
                setSelectizeValues(availNonTeachingSelect, nvals);
                availStatusLock = false;
            }
            updateAvailQtyState();
        } else {
            $('#availMsg').html('<div class="alert alert-danger">Failed to load settings.</div>');
        }
    }, 'json').fail(function() {
        $('#availMsg').html('<div class="alert alert-danger">Server error while loading settings.</div>');
    });
    $('#availabilityModal').modal('show');
}

function openAvailabilityModalBulk(codes) {
    if (!codes || codes.length === 0) return;
    isBulkAvailMode = true;
    $('#availMsg').html('');
    $('#availPropertyCode').val('');
    $('#availPropertyCodeDisplay').val('Multiple items');
    $('#availBulkCodes').val(codes.join(','));
    $('#availBulkNote').show().text(codes.length + ' items selected');
    $('#availQty').val('');
    $('#availTotalQty').text('-');
    if (availTeachingSelect) availTeachingSelect.clear();
    if (availNonTeachingSelect) availNonTeachingSelect.clear();
    if (availTeachingSelect && availNonTeachingSelect) {
        availStatusLock = true;
        availTeachingSelect.setValue([NONE_STATUS_VALUE], true);
        availNonTeachingSelect.setValue([NONE_STATUS_VALUE], true);
        availStatusLock = false;
    }
    updateAvailQtyState();
    $('#availabilityModal').modal('show');
}

function countUnitRows() {
    const rows = $('#unitRows .unit-row').length;
    return rows > 0 ? rows : 1;
}

function addUnitRow(serialVal = '') {
    const idx = $('#unitRows .unit-row').length + 1;
    const rowHtml = `
        <div class="unit-row border rounded p-2">
            <div class="row g-2 align-items-center">
                <div class="col-sm-3 col-md-2">
                    <span class="badge bg-light text-dark border">Unit ${idx}</span>
                </div>
                <div class="col-sm-9 col-md-8">
                    <input type="text" class="form-control form-control-sm serial-row-input" maxlength="150" placeholder="Serial number (optional)" value="${serialVal}">
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-unit-row">Remove</button>
                </div>
            </div>
        </div>
    `;
    $('#unitRows').append(rowHtml);
    refreshUnitRowLabels();
}

function refreshUnitRowLabels() {
    $('#unitRows .unit-row').each(function(i) {
        $(this).find('.badge').text('Unit ' + (i + 1));
    });
}

function resetUnitRows() {
    $('#unitRows').html('');
    addUnitRow('');
}

function escapeHtml(str) {
    return String(str === null || str === undefined ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function buildPropertyCodeList(propertyNumber, startSeries, units) {
    const normalized = String(propertyNumber || '').toUpperCase();
    const start = parseInt(startSeries, 10) || 1;
    const list = [];
    for (let i = 0; i < units; i++) {
        list.push(`AST-${normalized}-${String(start + i).padStart(4, '0')}`);
    }
    return list;
}

function collectAddItemDraft() {
    const serialRows = [];
    $('#unitRows .serial-row-input').each(function() {
        serialRows.push(($(this).val() || '').trim());
    });
    const categoryLabelRaw = ($('#categorySelect option:selected').text() || '').trim();
    const unitLabelRaw = ($('#unitSelect option:selected').text() || '').trim();
    return {
        category_id: ($('#categorySelect').val() || '').trim(),
        category_label: categoryLabelRaw && categoryLabelRaw !== 'Select category' ? categoryLabelRaw : '',
        property_number: ($('#propertyNumberField').val() || '').trim().toUpperCase(),
        item_description: ($('#itemDescription').val() || '').trim(),
        unit: ($('#unitSelect').val() || '').trim(),
        unit_label: unitLabelRaw && unitLabelRaw !== 'Select or type unit' ? unitLabelRaw : '',
        source_of_fund: ($('#sourceOfFund').val() || '').trim(),
        cost_value: ($('#costValue').val() || '').trim(),
        serial_rows: serialRows,
        number_of_units: serialRows.length > 0 ? serialRows.length : 1
    };
}

function validateAddItemDraft(draft) {
    if (!draft.category_id) return 'Category is required.';
    if (!draft.property_number) return 'Property number is required.';
    if (!/^[A-Za-z0-9]+$/.test(draft.property_number)) return 'Property number must contain letters and numbers only.';
    if (!draft.item_description) return 'Item description is required.';
    if (!draft.unit) return 'Unit is required.';
    if (draft.number_of_units <= 0) return 'Please add at least one unit row.';
    if (draft.number_of_units > 200) return 'Maximum 200 units per add is allowed.';
    return '';
}

function setReviewModalState(opts) {
    const loading = !!(opts && opts.loading);
    const content = !!(opts && opts.content);
    const error = (opts && opts.error) ? String(opts.error) : '';
    if (loading) {
        $('#reviewAddItemLoading').removeClass('d-none');
    } else {
        $('#reviewAddItemLoading').addClass('d-none');
    }
    if (content) {
        $('#reviewAddItemContent').show();
    } else {
        $('#reviewAddItemContent').hide();
    }
    if (error) {
        $('#reviewAddItemError').removeClass('d-none').text(error);
    } else {
        $('#reviewAddItemError').addClass('d-none').text('');
    }
}

function populateReviewModal(draft) {
    const codes = Array.isArray(draft.property_codes) ? draft.property_codes : [];
    const firstCode = codes.length ? codes[0] : '-';
    const lastCode = codes.length ? codes[codes.length - 1] : '-';
    const rangeText = codes.length > 1 ? `${firstCode} ... ${lastCode}` : firstCode;

    $('#reviewCategory').text(draft.category_label || '-');
    $('#reviewPropertyNumber').text(draft.property_number || '-');
    $('#reviewDescription').text(draft.item_description || '-');
    $('#reviewUnit').text(draft.unit_label || draft.unit || '-');
    $('#reviewSource').text(draft.source_of_fund || '-');
    if (draft.cost_value !== '') {
        const parsed = parseFloat(draft.cost_value);
        $('#reviewCost').text(!isNaN(parsed) ? parsed.toFixed(2) : draft.cost_value);
    } else {
        $('#reviewCost').text('-');
    }
    $('#reviewUnits').text(String(draft.number_of_units || 0));
    $('#reviewRange').text(rangeText);

    const serials = Array.isArray(draft.serial_rows) ? draft.serial_rows : [];
    const rowsHtml = codes.map((code, idx) => {
        const serial = serials[idx] || '';
        const serialHtml = serial ? escapeHtml(serial) : '<span class="text-muted">-</span>';
        return `
            <tr>
                <td>${idx + 1}</td>
                <td><span class="badge bg-light text-dark border badge-code">${escapeHtml(code)}</span></td>
                <td>${serialHtml}</td>
            </tr>
        `;
    }).join('');
    $('#reviewUnitsBody').html(rowsHtml);
}

function buildAddItemFormData(draft) {
    const fd = new FormData();
    fd.set('action', 'add_item');
    fd.set('category_id', draft.category_id || '');
    fd.set('property_number', draft.property_number || '');
    fd.set('property_series', String(draft.property_series || ''));
    fd.set('item_description', draft.item_description || '');
    fd.set('number_of_units', String(draft.number_of_units || 1));
    fd.set('serial_numbers_json', JSON.stringify(Array.isArray(draft.serial_rows) ? draft.serial_rows : []));
    fd.set('unit', draft.unit || '');
    fd.set('source_of_fund', draft.source_of_fund || '');
    fd.set('cost_value', draft.cost_value || '');
    return fd;
}

function openReviewAddItemModal(draft) {
    pendingAddItemDraft = null;
    setReviewModalState({ loading: true, content: false, error: '' });
    $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    $('#reviewAddItemModal').modal('show');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: {
            action: 'get_next_property_code',
            property_number: draft.property_number,
            number_of_units: draft.number_of_units
        },
        dataType: 'json',
        global: false
    }).done(function(res) {
        if (!res || !res.success) {
            setReviewModalState({ loading: false, content: false, error: (res && res.message) ? res.message : 'Failed to prepare review.' });
            return;
        }
        const normalizedPropNum = String(res.property_number || draft.property_number).toUpperCase();
        const startSeries = parseInt(res.series, 10) || 1;
        const codeList = buildPropertyCodeList(normalizedPropNum, startSeries, draft.number_of_units);

        draft.property_number = normalizedPropNum;
        draft.property_series = startSeries;
        draft.property_codes = codeList;
        pendingAddItemDraft = draft;

        $('#propertySeriesInput').val(String(startSeries));
        $('#propertyCodeField').val(codeList.length > 1 ? `${codeList[0]} ... ${codeList[codeList.length - 1]}` : (codeList[0] || ''));
        updateQrPreview(codeList);

        populateReviewModal(draft);
        setReviewModalState({ loading: false, content: true, error: '' });
    }).fail(function() {
        setReviewModalState({ loading: false, content: false, error: 'Server error while preparing review.' });
    });
}

function submitAddItemFromReview() {
    if (!pendingAddItemDraft || isSavingAddItem) return;
    isSavingAddItem = true;
    $('#confirmAddItemBtn').prop('disabled', true).html('<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Saving...');
    const fd = buildAddItemFormData(pendingAddItemDraft);
    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function(res) {
        if (res && res.success) {
            $('#reviewAddItemModal').modal('hide');
            showMessage('#addItemMsg', 'success', res.message || 'Item saved.');
            $('#addItemForm')[0].reset();
            $('#categorySelect').val('').trigger('change');
            $('#unitSelect').val('').trigger('change');
            resetUnitRows();
            refreshPropertyCode();
            refreshTable();
            loadPropertyCodes();
            loadUnits();
            return;
        }
        setReviewModalState({ loading: false, content: true, error: (res && res.message) ? res.message : 'Save failed.' });
    }).fail(function() {
        setReviewModalState({ loading: false, content: true, error: 'Server error while saving.' });
    }).always(function() {
        isSavingAddItem = false;
        $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    });
}



function refreshPropertyCode() {
    const propNum = $('#propertyNumberField').val().trim();
    const units = countUnitRows();
    $('#numberOfUnits').val(units);
    propertyCodeRequestSeq += 1;
    const requestSeq = propertyCodeRequestSeq;
    if (propertyCodeXhr && typeof propertyCodeXhr.abort === 'function') {
        propertyCodeXhr.abort();
    }
    propertyCodeXhr = null;
    if (propNum === '') {
        $('#propertyCodeField').val('');
        $('#propertySeriesInput').val('');
        updateQrPreview([]);
        return;
    }
    
    // Show loading in QR area
    $('#qrLoading').show();
    
    propertyCodeXhr = $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: { action: 'get_next_property_code', property_number: propNum, number_of_units: units },
        dataType: 'json',
        global: false, // Disable global AJAX events to prevent full-screen loading
        success: function(res) {
            if (requestSeq !== propertyCodeRequestSeq) return;
            if (res.success) {
                const displayCode = (units > 1 && res.range_end_code)
                    ? `${res.property_code} ... ${res.range_end_code}`
                    : res.property_code;
                $('#propertyCodeField').val(displayCode);
                $('#propertySeriesInput').val(res.series);

                const normalizedPropNum = String(res.property_number || propNum).toUpperCase();
                const startSeries = parseInt(res.series, 10) || 1;
                const codeList = [];
                for (let i = 0; i < units; i++) {
                    const s = String(startSeries + i).padStart(4, '0');
                    codeList.push(`AST-${normalizedPropNum}-${s}`);
                }
                updateQrPreview(codeList);
            } else {
                console.error('Failed to get property code:', res.message);
                $('#propertyCodeField').val('');
                $('#propertySeriesInput').val('');
                updateQrPreview([]);
            }
        },
        error: function(xhr, status, error) {
            if (requestSeq !== propertyCodeRequestSeq) return;
            if (status === 'abort') return;
            // Session expired - redirect to login
            if (xhr.status === 401) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.redirect) { window.location.href = res.redirect; return; }
                } catch(e) {}
                window.location.reload();
                return;
            }
            console.error('AJAX error getting property code:', status, error);
            $('#propertyCodeField').val('');
            $('#propertySeriesInput').val('');
            updateQrPreview([]);
        }
    });
}

function debouncedRefreshPropertyCode() {
    if (propertyNumberTimeout) {
        clearTimeout(propertyNumberTimeout);
    }
    propertyNumberTimeout = setTimeout(refreshPropertyCode, 500);
}

function updateQrPreview(codes) {
    const listEl = document.getElementById('qrPreviewList');
    const metaEl = document.getElementById('qrPreviewMeta');
    const loading = document.getElementById('qrLoading');
    if (!listEl || !loading) return;

    const codeList = Array.isArray(codes) ? codes : (codes ? [codes] : []);
    if (!codeList.length) {
        listEl.innerHTML = '';
        if (metaEl) metaEl.textContent = 'Based on the property code';
        loading.style.display = 'none';
        return;
    }

    const previewLimit = 24;
    const visibleCodes = codeList.slice(0, previewLimit);
    if (metaEl) {
        metaEl.textContent = codeList.length > previewLimit
            ? `Showing first ${previewLimit} of ${codeList.length} QR codes.`
            : 'Based on the property code';
    }

    loading.style.display = 'block';
    const stamp = Date.now();
    listEl.innerHTML = visibleCodes.map((code, idx) => {
        const safeCode = String(code);
        const src = `${QR_GENERATOR_URL}?v=${encodeURIComponent(safeCode)}&t=${stamp}-${idx}`;
        return `
            <div class="qr-preview-item">
                <img class="qr-preview-img" src="${src}" alt="QR ${idx + 1}" data-qr="1">
                <div class="qr-preview-code">${safeCode}</div>
            </div>
        `;
    }).join('');

    let pending = listEl.querySelectorAll('img[data-qr="1"]').length;
    if (pending === 0) {
        loading.style.display = 'none';
        return;
    }
    listEl.querySelectorAll('img[data-qr="1"]').forEach((img) => {
        const done = function() {
            pending -= 1;
            if (pending <= 0) {
                loading.style.display = 'none';
            }
        };
        img.addEventListener('load', done, { once: true });
        img.addEventListener('error', done, { once: true });
    });
}

function initTable() {
    inventoryTable = new Tabulator('#inventoryTable', {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_items', limit: 200 },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No items found',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        selectable: true,
        ajaxResponse: function(url, params, response) {
            return response.data || [];
        },
        columns: [
            { formatter: "rowSelection", titleFormatter: "rowSelection", hozAlign: "center", headerSort: false, width: 40 },
            { title: 'Property Code', field: 'property_code', width: 200, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const val = cell.getValue();
                return `<span class="badge bg-light text-dark border badge-code">${val}</span>`;
            }},
            { title: 'Category', field: 'item_category_name', width: 170, headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            { title: 'Description', field: 'item_description', widthGrow: 2, headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            { title: 'Serial No.', field: 'serial_number', width: 150, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const v = cell.getValue();
                return v && String(v).trim() !== '' ? v : '-';
            }},
            { title: 'Qty', field: 'quantity', width: 80, hozAlign: 'center' },
            { title: 'Available Qty', field: 'available_qty', width: 110, hozAlign: 'center', formatter: function(cell){
                const v = cell.getValue();
                return v !== null && v !== '' ? parseInt(v, 10) : '-';
            }},
            { title: 'Allowed Status', field: 'allowed_status_names', width: 180, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const v = cell.getValue();
                if (!v) return '-';
                const parts = v.split(' | ');
                const html = parts.map(p => `<div class="text-muted small" style="line-height:1.3;">${p}</div>`).join('');
                return html;
            }},
            { title: 'Unit', field: 'unit', width: 90, headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            { title: 'Source', field: 'source_of_fund', width: 130, headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            { title: 'Cost', field: 'cost_value', width: 110, headerFilter: 'input', headerFilterPlaceholder: 'Filter...', formatter: function(cell){
                const v = cell.getValue();
                return v !== null && v !== '' ? parseFloat(v).toFixed(2) : '-';
            }},
            { title: 'Set', field: 'property_code', width: 90, hozAlign: 'center', formatter: function(cell){
                const code = cell.getValue();
                return `<button class="btn btn-sm btn-outline-primary btn-set-availability" data-code="${code}">Set</button>`;
            }},
            { title: 'Date Modified', field: 'created_at', width: 160, formatter: function(cell){
                const d = new Date(cell.getValue());
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }}
        ]
    });
}

function initTableLazy() {
    const target = document.getElementById('inventoryTable');
    if (!target) return;
    if (!('IntersectionObserver' in window)) {
        initTable();
        const ph = document.getElementById('inventoryTablePlaceholder');
        if (ph) ph.remove();
        return;
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initTable();
                const ph = document.getElementById('inventoryTablePlaceholder');
                if (ph) ph.remove();
                observer.disconnect();
            }
        });
    }, { root: null, threshold: 0.1 });
    observer.observe(target);
}

function refreshTable() {
    const search = $('#tableSearch').val();
    if (inventoryTable) {
        inventoryTable.setData(PROCESS_URL, { action: 'list_items', search: search, limit: 200 }, 'POST');
    }
}

function loadItemByCode(code) {
    if (!code) return;
    $.post(PROCESS_URL, { action: 'get_item_by_code', property_code: code }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#addQtyPropertyCode').val(d.property_code);
            $('#addQtySource').val(d.source_of_fund || '');
            $('#addQtyCost').val(d.cost_value || '');
            $('#itemInfo').html(`
                <div class="d-flex flex-column gap-2">
                    <div>
                        <div class="small text-muted">Property Code</div>
                        <div class="fw-semibold">${d.property_code}</div>
                    </div>
                    <div class="small text-muted">${d.item_category_name || 'Uncategorized'}</div>
                    <div>
                        <div class="small text-muted">Description</div>
                        <div>${d.item_description || '-'}</div>
                    </div>
                    <div class="small text-muted">
                        Serial No.: <span class="fw-semibold">${d.serial_number || '-'}</span>
                    </div>
                    <div class="small text-muted">
                        Current Qty: <span class="fw-semibold">${d.quantity}</span> ${d.unit || ''}
                        ${d.source_of_fund ? ` | Source: ${d.source_of_fund}` : ''}
                        ${d.cost_value ? ` | Cost: ${d.cost_value}` : ''}
                    </div>
                </div>
            `);
        } else {
            $('#itemInfo').html('<span class="text-danger">' + (res.message || 'Item not found.') + '</span>');
        }
    }, 'json');
}

    $(document).ready(function() {
        loadCategories();
        loadUnits();
        loadPropertyCodes();
        loadEmploymentStatuses();
        initTableLazy();
        resetUnitRows();
        refreshPropertyCode();

    $('#propertyNumberField').on('keypress', function(e) {
        // Only allow letters and numbers
        const char = String.fromCharCode(e.which || e.keyCode);
        if (e.which > 31 && !/[a-zA-Z0-9]/.test(char)) {
            e.preventDefault();
            return false;
        }
    }).on('input', function() {
        // Keep alphanumeric only and normalize to uppercase
        let val = ($(this).val() || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        $(this).val(val);
        debouncedRefreshPropertyCode();
    }).on('change', debouncedRefreshPropertyCode);

    $('#addUnitRowBtn').on('click', function() {
        addUnitRow('');
        refreshPropertyCode();
    });

    $('#unitRows').on('click', '.btn-remove-unit-row', function() {
        const rows = $('#unitRows .unit-row').length;
        if (rows <= 1) {
            $(this).closest('.unit-row').find('.serial-row-input').val('');
        } else {
            $(this).closest('.unit-row').remove();
            refreshUnitRowLabels();
        }
        refreshPropertyCode();
    });

    $('#confirmAddItemBtn').on('click', function() {
        submitAddItemFromReview();
    });

    $('#reviewAddItemModal').on('hidden.bs.modal', function() {
        pendingAddItemDraft = null;
        isSavingAddItem = false;
        setReviewModalState({ loading: false, content: false, error: '' });
        $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    });

    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        $('#addItemMsg').html('');
        const draft = collectAddItemDraft();
        const validationError = validateAddItemDraft(draft);
        if (validationError) {
            showMessage('#addItemMsg', 'danger', validationError);
            return;
        }
        openReviewAddItemModal(draft);
    });

    $('#resetAddForm').on('click', function() {
        $('#addItemForm')[0].reset();
        $('#categorySelect').val('').trigger('change'); // Reset Select2 dropdown
        $('#unitSelect').val('').trigger('change'); // Reset Select2 dropdown
        resetUnitRows();
        refreshPropertyCode();
        $('#addItemMsg').html('');
    });

    $('#addQtyForm').on('submit', function(e) {
        e.preventDefault();
        $('#addQtyMsg').html('');
        const codeVal = ($('#addQtyPropertyCode').val() || '').trim();
        if (!codeVal) {
            showMessage('#addQtyMsg', 'danger', 'Please select a property code first.');
            return;
        }
        const data = $(this).serialize();
        $.post(PROCESS_URL, data, function(res) {
            if (res.success) {
                showMessage('#addQtyMsg', 'success', res.message || 'Quantity updated.');
                loadItemByCode($('#addQtyPropertyCode').val());
                refreshTable();
            } else {
                showMessage('#addQtyMsg', 'danger', res.message || 'Update failed.');
            }
        }, 'json').fail(function() {
            showMessage('#addQtyMsg', 'danger', 'Server error while updating quantity.');
        });
    });

    $('#tableSearch').on('keyup', function() {
        refreshTable();
    });

    // Global QR search helper (shared across pages)
    if (typeof initQrSearch === 'function') {
        initQrSearch({
            searchInput: '#tableSearch',
            onSearch: refreshTable
        });
        initQrSearch({
            modalId: '#addQtyQrModal',
            openButton: '#openAddQtyScanner',
            searchInput: '',
            cameraSelectId: '#addQtyCameraSelect',
            startBtnId: '#addQtyBtnStart',
            stopBtnId: '#addQtyBtnStop',
            previewId: '#addQtyPreview',
            lastScannedId: '#addQtyScanLast',
            errorId: '#addQtyScanError',
            loadingId: '#addQtyScannerLoading',
            onSearch: function (decodedText) {
                if (!decodedText) return;
                $('#addQtyLastScanned').text(decodedText);
                // Ensure option exists for Select2, then select it
                if ($('#searchPropertyCode option[value="' + decodedText + '"]').length === 0) {
                    $('#searchPropertyCode').append(new Option(decodedText, decodedText, true, true));
                }
                $('#searchPropertyCode').val(decodedText).trigger('change');
                $('#addQtyPropertyCode').val(decodedText);
                loadItemByCode(decodedText);
            }
        });
    }
    $('#inventoryTable').on('click', '.btn-set-availability', function() {
        const code = $(this).data('code');
        openAvailabilityModal(code);
    });

    $('#bulkSetAvailability').on('click', function() {
        console.log('Bulk Set Availability clicked');
        if (!inventoryTable) {
            console.error('inventoryTable not initialized');
            return;
        }
        const rows = inventoryTable.getSelectedData() || [];
        console.log('Selected rows:', rows.length);
        const codes = rows.map(r => r.property_code).filter(Boolean);
        console.log('Property codes:', codes);
        if (codes.length === 0) {
            error_notif('Please select at least one item from the table first.');
            return;
        }
        openAvailabilityModalBulk(codes);
    });

    $('#clearSelection').on('click', function() {
        if (!inventoryTable) return;
        inventoryTable.deselectRow();
    });

    $('#availabilityForm').on('submit', function(e) {
        e.preventDefault();
        $('#availMsg').html('');
        const teachRaw = availTeachingSelect ? availTeachingSelect.getValue() : [];
        const nonRaw = availNonTeachingSelect ? availNonTeachingSelect.getValue() : [];
        const teachArr = Array.isArray(teachRaw) ? teachRaw : (teachRaw ? String(teachRaw).split(',') : []);
        const nonArr = Array.isArray(nonRaw) ? nonRaw : (nonRaw ? String(nonRaw).split(',') : []);
        const teachAllowed = teachArr.filter(v => String(v) !== NONE_STATUS_VALUE);
        const nonAllowed = nonArr.filter(v => String(v) !== NONE_STATUS_VALUE);
        const allNone = teachAllowed.length === 0 && nonAllowed.length === 0;
        const bulkCodes = ($('#availBulkCodes').val() || '').trim();
        const qtyRaw = $('#availQty').val();
        const qtyProvided = qtyRaw !== '' && qtyRaw !== null;
        const qty = parseInt(qtyRaw, 10);
        const totalQty = parseInt($('#availTotalQty').text(), 10);

        if (!bulkCodes && !allNone) {
            if (!qtyProvided) {
                $('#availMsg').html('<div class="alert alert-danger">Available quantity is required.</div>');
                return;
            }
            if (qtyProvided && (isNaN(qty) || qty < 0)) {
                $('#availMsg').html('<div class="alert alert-danger">Available quantity must be 0 or more.</div>');
                return;
            }
            if (qtyProvided && !isNaN(totalQty) && qty > totalQty) {
                $('#availMsg').html('<div class="alert alert-danger">Available quantity cannot exceed total quantity.</div>');
                return;
            }
        }

        const code = $('#availPropertyCode').val();
        const allowedPayload = allNone
            ? { none: true }
            : { teaching: teachAllowed, non_teaching: nonAllowed };
        const payload = {
            allowed_status: JSON.stringify(allowedPayload)
        };
        if (bulkCodes) {
            payload.action = 'update_availability_settings_bulk';
            payload.bulk_codes = bulkCodes;
        } else {
            payload.action = 'update_availability_settings';
            payload.property_code = code;
            if (!allNone) {
                payload.available_qty = qty;
            }
        }
        $.post(PROCESS_URL, payload, function(res) {
            if (res && res.success) {
                $('#availMsg').html('<div class="alert alert-success">Saved.</div>');
                refreshTable();
                if (inventoryTable && bulkCodes) inventoryTable.deselectRow();
                setTimeout(() => $('#availabilityModal').modal('hide'), 600);
            } else {
                $('#availMsg').html('<div class="alert alert-danger">' + (res.message || 'Save failed.') + '</div>');
            }
        }, 'json').fail(function() {
            $('#availMsg').html('<div class="alert alert-danger">Server error while saving.</div>');
        });
    });
    $('#availQty').on('input change', function() {
        const maxQty = parseInt($('#availTotalQty').text(), 10);
        const val = parseInt($(this).val(), 10);
        if (!isNaN(maxQty) && !isNaN(val) && val > maxQty) {
            $(this).val(maxQty);
        }
    });

});
</script>
</body>
</html>
