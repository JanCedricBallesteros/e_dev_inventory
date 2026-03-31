<?php
// admin/modules/consumable/csm_available_items.php
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
        user_has_access(array("CSM", "PO"))
    )
)) {
    header("Location: " . BASE_URL);
    exit();
}

function getAllCategories() {
    $sql = "SELECT category_id, item_category_code, item_category_name
            FROM csm_inventory_category
            ORDER BY item_category_name ASC";
    $result = call_mysql_query($sql);

    $rows = [];
    if ($result) {
        while ($row = call_mysql_fetch_array($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function getUnitOptions() {
    $sql = "SELECT DISTINCT TRIM(unit) AS unit
            FROM csm_inventory
            WHERE unit IS NOT NULL
              AND TRIM(unit) <> ''
            ORDER BY unit ASC";
    $result = call_mysql_query($sql);

    $rows = [];
    if ($result) {
        while ($row = call_mysql_fetch_array($result)) {
            if (!empty($row['unit'])) {
                $rows[] = $row['unit'];
            }
        }
    }

    if (empty($rows)) {
        $rows = array('pcs', 'box', 'pack', 'ream', 'bottle', 'set', 'roll', 'pad', 'carton', 'unit');
    }

    return $rows;
}

$categories = getAllCategories();
$unitOptions = getUnitOptions();
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
        .section-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .section-card .card-header {
            background: var(--bg-eclearance-rgb);
            color: #fff;
            font-weight: 600;
        }
        .badge-code {
            font-family: 'Courier New', monospace;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
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
        #inventoryTable {
            min-height: 220px;
        }
        .qr-preview-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        .qr-preview-img {
            width: 100%;
            max-width: 180px;
            height: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px;
            background: #fff;
        }
        .review-table-wrap {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .inv-thumb-wrap{
            width:56px;
            height:56px;
            border:1px solid #dee2e6;
            border-radius:10px;
            background:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
        }
        .inv-thumb-wrap img{
            width:56px;
            height:56px;
            object-fit:cover;
            display:block;
        }
        .inv-thumb-fallback{
            font-size:24px;
            line-height:1;
            color:#6c757d;
        }
        .inv-thumb-click{
            cursor:pointer;
            display:inline-flex;
        }
        .view-img-wrap{
            width:100%;
            background:#f8f9fa;
            border:1px solid #e5e7eb;
            border-radius:14px;
            overflow:hidden;
        }
        .view-img-wrap img{
            width:100%;
            height:auto;
            display:block;
        }
        .card-header-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .card-header-tools .title-wrap {
            display: flex;
            align-items: center;
            gap: .35rem;
            min-width: 0;
        }
        .card-header-tools .header-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }
        @media (max-width: 576px) {
            .card-header-tools {
                flex-direction: column;
                align-items: stretch;
            }
            .card-header-tools .header-actions {
                justify-content: flex-start;
            }
        }

        /* ===== QR camera preview fixes ===== */
        .qr-camera-shell{
            width:100%;
            max-width:420px;
            margin:0 auto;
            aspect-ratio:1 / 1;
            position:relative;
            overflow:hidden;
            border-radius:12px;
            background:#111;
        }
        .qr-camera-shell > div{
            width:100%;
            height:100%;
        }
        .qr-camera-shell video,
        .qr-camera-shell canvas{
            width:100% !important;
            height:100% !important;
            object-fit:cover !important;
            display:block !important;
            border-radius:0 !important;
        }
        .qr-camera-shell #searchPreview,
        .qr-camera-shell #addQtyPreview{
            width:100%;
            height:100%;
            background:#111;
        }
        #searchPreview video,
        #searchPreview canvas,
        #addQtyPreview video,
        #addQtyPreview canvas{
            width:100% !important;
            height:100% !important;
            object-fit:cover !important;
            display:block !important;
            margin:0 !important;
            padding:0 !important;
            border:0 !important;
            background:#111 !important;
        }
        #searchPreview > div,
        #addQtyPreview > div{
            width:100% !important;
            height:100% !important;
        }
        #searchPreview img,
        #addQtyPreview img{
            max-width:none !important;
        }
        .floating-notice-stack {
            position: fixed;
            top: 86px;
            right: 18px;
            z-index: 1095;
            width: min(360px, calc(100vw - 24px));
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        .floating-notice {
            pointer-events: auto;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            margin: 0;
            position: relative;
            padding: .95rem 2.75rem .95rem 1rem;
            overflow: hidden;
            backdrop-filter: blur(8px);
            animation: floatingNoticeIn .22s ease-out;
        }
        .floating-notice::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: #64748b;
        }
        .floating-notice.alert-success {
            background: rgba(236, 253, 245, 0.97);
            color: #065f46;
        }
        .floating-notice.alert-success::before { background: #10b981; }
        .floating-notice.alert-danger {
            background: rgba(254, 242, 242, 0.97);
            color: #991b1b;
        }
        .floating-notice.alert-danger::before { background: #ef4444; }
        .floating-notice.alert-warning {
            background: rgba(255, 251, 235, 0.98);
            color: #92400e;
        }
        .floating-notice.alert-warning::before { background: #f59e0b; }
        .floating-notice.alert-primary,
        .floating-notice.alert-info {
            background: rgba(239, 246, 255, 0.97);
            color: #1d4ed8;
        }
        .floating-notice.alert-primary::before,
        .floating-notice.alert-info::before { background: #2563eb; }
        .floating-notice div {
            line-height: 1.4;
            font-size: .94rem;
        }
        .floating-notice .btn-close {
            position: absolute;
            top: 12px;
            right: 12px;
            transform: scale(.88);
            opacity: .62;
        }
        .floating-notice .btn-close:hover {
            opacity: 1;
        }
        @keyframes floatingNoticeIn {
            from {
                opacity: 0;
                transform: translate3d(0, -10px, 0) scale(.98);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }
        }
        @media (max-width: 576px) {
            .floating-notice-stack {
                top: 74px;
                right: 12px;
                left: 12px;
                width: auto;
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<div id="floatingNoticeStack" class="floating-notice-stack"></div>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">Manage Available Consumables</h1>
        <p class="text-muted small mb-0">Add records, review inventory, scan QR, and update available quantities.</p>
    </div>

    <section class="section">
        <div id="pageMsg" class="alert alert-danger d-none mb-3"></div>

        <div class="row g-3">
            <!-- Add New Item -->
            <div class="col-12 col-xl-8">
                <div class="card section-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-plus-circle"></i>&ensp;Add New Consumable Record</span>
                    </div>
                    <div class="card-body">
                        <form id="addItemForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Category</label>
                                    <select class="form-select" name="item_category_code" id="categorySelect" required>
                                        <option value="">Select category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['item_category_code']) ?>">
                                                <?= htmlspecialchars($cat['item_category_name']) ?> (<?= htmlspecialchars($cat['item_category_code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!--<div class="col-md-4">
                                    <label class="form-label fw-semibold">Item Code Number (optional)</label>
                                    <input type="text"
                                           class="form-control"
                                           name="inventory_system_item_code"
                                           id="itemCodeNumberField"
                                           placeholder="e.g. 1 or leave blank for auto"
                                           autocomplete="off"
                                           inputmode="numeric"
                                           pattern="[0-9]*">
                                    <div class="small text-muted mt-1">
                                        Numbers only. Full code becomes <span id="codePatternText">CSM-[CATEGORY]-0001</span>
                                    </div>
                                </div>-->

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Full Item Code Preview</label>
                                    <input type="text" class="form-control" id="fullItemCodeField" readonly placeholder="CSM-XXXX-0001">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Itemized Description</label>
                                    <textarea class="form-control"
                                              name="item_description"
                                              id="itemDescription"
                                              rows="2"
                                              placeholder="Enter itemized description"
                                              required></textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Actual Qty</label>
                                    <input type="number" min="0" class="form-control" name="quantity" id="unitQuantity" value="0" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Unit (measurement)</label>
                                    <select class="form-select" name="unit" id="unitSelect" required>
                                        <option value="">Select existing or type a new unit</option>
                                        <?php foreach ($unitOptions as $unit): ?>
                                            <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars($unit) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Critical Level</label>
                                    <input type="number" min="0" class="form-control" name="qty_crit_level" id="unitCritLevel" value="0" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Source of Funds</label>
                                    <input type="text" class="form-control" name="source_of_funds" id="sourceOfFunds" placeholder="e.g. General Fund">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Cost Value</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="cost_value" id="itemCost" value="0.00" required>
                                </div>

                                <!--<div class="col-md-4">
                                    <label class="form-label fw-semibold">Available to Issue</label>
                                    <input type="number" min="0" class="form-control" name="current_quantity" id="currentUnitQuantity" value="0" required>
                                </div>-->

                                <div class="col-12">
                                    <label class="form-label fw-semibold">QR Code Preview</label>
                                    <div class="border rounded p-3 bg-white">
                                        <div id="qrLoading" class="text-muted small py-3 text-center" style="display:none;">
                                            <i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i><br>
                                            Generating QR code...
                                        </div>
                                        <div id="qrPreviewWrap" class="qr-preview-wrap">
                                            <img id="qrPreviewImg" class="qr-preview-img d-none" src="" alt="QR Preview">
                                            <div id="qrPreviewEmpty" class="text-muted small">QR preview will appear here.</div>
                                        </div>
                                        <div id="qrPreviewMeta" class="small text-muted mt-2 text-center">Based on the full item code preview</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Record
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="resetAddForm">Clear</button>
                            </div>
                            <div id="addItemMsg" class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Quantity -->
            <div class="col-12 col-xl-4">
                <div class="card section-card h-100">
                    <div class="card-header">
                        <div class="card-header-tools">
                            <div class="title-wrap">
                                <i class="bi bi-plus-square"></i>
                                <span>Add Quantity</span>
                            </div>
                            <div class="header-actions">
                                <button class="btn btn-sm btn-light" type="button" id="openAddQtyScanner" title="Scan QR Code">
                                    <i class="bi bi-qr-code-scan"></i>&ensp;Scan QR Code
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Item Code</label>
                            <select class="form-select" id="searchItemCode">
                                <option value="">Select item code</option>
                            </select>
                            <div class="small text-muted mt-1">Last scanned: <span id="addQtyLastScanned">-</span></div>
                        </div>

                        <div id="itemInfo" class="mb-3 text-muted small">No item loaded.</div>

                        <form id="addQtyForm">
                            <input type="hidden" name="inventory_id" id="addQtyInventoryId">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Quantity to Add</label>
                                <input type="number" class="form-control" name="add_quantity" id="addQtyValue" min="1" value="1" required>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Source of Funds</label>
                                    <input type="text" class="form-control" name="source_of_funds" id="addQtySource">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Cost Value</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="cost_value" id="addQtyCost">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-arrow-up-circle"></i> Apply Quantity
                                </button>
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
                            <span><i class="bi bi-table"></i>&ensp;Recently Added</span>
                            <div class="input-group" style="max-width:360px;">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control form-control-sm" id="tableSearch" placeholder="Search item code, category, or description">
                                <button class="btn btn-light btn-sm border" type="button" id="openSearchScanner" title="Scan QR">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="inventoryTablePlaceholder" class="text-muted small mb-2">
                            Scroll to load recently added inventory table.
                        </div>
                        <div id="inventoryTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<!-- VIEW INVENTORY IMAGE MODAL -->
<div class="modal fade" id="viewInvImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-image"></i>&ensp;Inventory Image
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="small text-muted mb-2" id="viewInvImageTitle">—</div>
                <div id="viewInvImageBodyMsg" class="mb-2"></div>
                <div class="view-img-wrap">
                    <img id="viewInvImageImg" src="" alt="Inventory Image">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
                <div class="qr-camera-shell">
                    <div id="searchPreview"></div>
                    <div class="scanner-loading" id="searchScannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
                        <div>Initializing camera...</div>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-muted">Last scanned:</span>
                    <span id="searchLastScanned" class="fw-semibold">-</span>
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
                    <div class="qr-camera-shell">
                        <div id="addQtyPreview"></div>
                        <div id="addQtyScannerLoading" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#fff;font-size:14px;z-index:10;">
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
                <h5 class="modal-title fw-semibold"><i class="bi bi-check2-square"></i>&ensp;Review Record Before Save</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="reviewAddItemError" class="alert alert-danger d-none mb-3"></div>
                <div id="reviewAddItemContent">
                    <div class="row g-2 mb-3 small">
                        <div class="col-md-6"><span class="text-muted">Category:</span> <span class="fw-semibold" id="reviewCategory"></span></div>
                        <div class="col-md-6"><span class="text-muted">Item Code:</span> <span class="fw-semibold" id="reviewItemCode"></span></div>
                        <div class="col-md-6"><span class="text-muted">Description:</span> <span class="fw-semibold" id="reviewDescription"></span></div>
                        <div class="col-md-6"><span class="text-muted">Unit:</span> <span class="fw-semibold" id="reviewUnit"></span></div>
                        <div class="col-md-6"><span class="text-muted">Source of Funds:</span> <span class="fw-semibold" id="reviewSource"></span></div>
                        <div class="col-md-3"><span class="text-muted">Actual Qty:</span> <span class="fw-semibold" id="reviewUnitQty"></span></div>
                        <div class="col-md-3"><span class="text-muted">Available Qty:</span> <span class="fw-semibold" id="reviewCurrentQty"></span></div>
                        <div class="col-md-3"><span class="text-muted">Critical Level:</span> <span class="fw-semibold" id="reviewCritLevel"></span></div>
                        <div class="col-md-3"><span class="text-muted">Cost:</span> <span class="fw-semibold" id="reviewCost"></span></div>
                    </div>
                    <div class="review-table-wrap">
                        <table class="table table-sm table-striped mb-0">
                            <tbody>
                                <tr>
                                    <th style="width:220px;">Acquisition Date</th>
                                    <td>Automatically set to today by process file</td>
                                </tr>
                                <tr>
                                    <th>QR Target Value</th>
                                    <td id="reviewQrValue"></td>
                                </tr>
                                <tr>
                                    <th>Rule Check</th>
                                    <td id="reviewRuleCheck"></td>
                                </tr>
                            </tbody>
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

<!-- EDIT RECORD MODAL -->
<div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-pencil-square"></i>&ensp;Edit Inventory Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="editInventoryForm">
                    <input type="hidden" name="inventory_id" id="edit_inventory_id">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Item Code Number</label>
                            <input type="text"
                                   name="inventory_system_item_code"
                                   id="edit_inventory_system_item_code"
                                   class="form-control"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   placeholder="e.g. 25">
                            <div class="small text-muted mt-1">Full format is automatic.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="item_category_code" id="edit_item_category_code" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['item_category_code']) ?>">
                                        <?= htmlspecialchars($cat['item_category_name']) ?> (<?= htmlspecialchars($cat['item_category_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Full Item Code Preview</label>
                            <input type="text" class="form-control" id="edit_full_item_code" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Itemized Description</label>
                            <textarea name="item_description" id="edit_item_description" class="form-control" required></textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Unit (measurement)</label>
                            <select name="unit" id="edit_unit" class="form-select" required>
                                <option value="">Select existing or type a new unit</option>
                                <?php foreach ($unitOptions as $unit): ?>
                                    <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars($unit) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Source of Funds</label>
                            <input type="text" name="source_of_funds" id="edit_source_of_funds" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Actual Qty</label>
                            <input type="number" name="quantity" id="edit_unit_quantity" class="form-control" required min="0">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Critical Level</label>
                            <input type="number" name="qty_crit_level" id="edit_unit_crit_level" class="form-control" required min="0">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Cost Value</label>
                            <input type="number" step="0.01" name="cost_value" id="edit_item_cost" class="form-control" required min="0">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Available Qty</label>
                            <input type="text" id="edit_current_unit_quantity_display" class="form-control" readonly>
                            <div class="small text-muted mt-1">Editable through the separate Available action.</div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>

                <div id="editRecordMsg" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

<!-- AVAILABLE MODAL -->
<div class="modal fade" id="availableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-box-arrow-in-down"></i>&ensp;Update Available to Issue
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="availableForm">
                    <input type="hidden" name="inventory_id" id="avail_inventory_id">
                    <div class="mb-2">
                        <div class="small text-muted">Item</div>
                        <div class="fw-semibold" id="avail_item_label">—</div>
                    </div>

                    <label class="form-label">Available to Issue</label>
                    <input type="number" class="form-control" name="current_quantity" id="avail_current_unit_quantity" min="0" required>

                    <div class="form-text">Cannot be negative and cannot exceed Actual Qty.</div>

                    <div id="availableMsg" class="mt-2"></div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
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
const PROCESS_URL = BASE_URL + 'admin/modules/consumable/process/csm_inventory_process.php';
const QR_GENERATOR_URL = BASE_URL + 'admin/modules/tools/qr_image.php';

let inventoryTable = null;
let inventoryCache = [];
let pendingAddItemDraft = null;
let isSavingAddItem = false;
let pageMsgTimeout = null;

function showFloatingNotice(type, content, opts = {}) {
    const settings = Object.assign({ html: false, delay: 4000 }, opts || {});
    const id = 'notice-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
    const body = settings.html ? String(content || '') : escHtml(String(content || ''));
    const html = `
        <div class="alert alert-${type} floating-notice" data-notice-id="${id}">
            <button type="button" class="btn-close" aria-label="Close"></button>
            <div>${body}</div>
        </div>
    `;

    const $stack = $('#floatingNoticeStack');
    $stack.append(html);

    const closeNotice = () => {
        $stack.find(`[data-notice-id="${id}"]`).fadeOut(160, function() {
            $(this).remove();
        });
    };

    $stack.find(`[data-notice-id="${id}"] .btn-close`).on('click', closeNotice);
    window.setTimeout(closeNotice, settings.delay);
}

function observeFloatingTargets(selectors) {
    (selectors || []).forEach(selector => {
        document.querySelectorAll(selector).forEach(target => {
            const flushNotice = () => {
                if (target.dataset.noticeSync === '1') return;

                const rawHtml = target.innerHTML || '';
                const rawText = (target.textContent || '').trim();
                if (!rawHtml.trim() && !rawText) return;

                const $tmp = $('<div>').html(rawHtml);
                const $alert = $tmp.find('.alert').first();

                let type = 'info';
                let content = rawText;
                let asHtml = false;

                if ($alert.length) {
                    asHtml = true;
                    content = $alert.html();
                    if ($alert.hasClass('alert-danger')) type = 'danger';
                    else if ($alert.hasClass('alert-success')) type = 'success';
                    else if ($alert.hasClass('alert-warning')) type = 'warning';
                    else if ($alert.hasClass('alert-primary')) type = 'primary';
                    else if ($alert.hasClass('alert-info')) type = 'info';
                } else if (target.classList.contains('text-danger') || /error/i.test(target.id || '')) {
                    type = 'danger';
                }

                if (!String(content || '').trim()) return;

                target.dataset.noticeSync = '1';
                showFloatingNotice(type, content, { html: asHtml });
                $(target).html('').addClass('d-none');
                target.dataset.noticeSync = '0';
            };

            new MutationObserver(flushNotice).observe(target, {
                childList: true,
                subtree: true,
                characterData: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        });
    });
}

function showMessage(target, type, text) {
    showFloatingNotice(type, text);
}

function showPageMessage(message) {
    if (!message) {
        return;
    }
    showFloatingNotice('danger', message);
}

function escHtml(str) {
    return String(str === null || str === undefined ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function absUrl(path){
    const base = String(BASE_URL || '');
    const sep = base.endsWith('/') ? '' : '/';
    const rel = String(path || '').replace(/^\/+/, '');
    return base + sep + rel;
}

function normalizeCategoryCodeForDisplay(code){
    const raw = String(code || '').trim();
    if (!raw) return '';
    const m = raw.match(/^CSM-?(\d+)$/i);
    if (m) return `CSM-${m[1]}`;
    if (/^CSM-/i.test(raw)) return raw;
    return `CSM-${raw}`;
}

function normalizeCategoryCodeForItemCode(code){
    const raw = String(code || '').trim();
    if (!raw) return '';
    const m = raw.match(/^CSM-?(\d+)$/i);
    if (m) return m[1];
    return raw;
}

function groupLabel(code, name){
    const c = String(code || '').trim();
    const n = String(name || '').trim();
    if (!c && !n) return 'Uncategorized';
    const cDisp = c ? normalizeCategoryCodeForDisplay(c) : '';
    if (cDisp && n) return `${cDisp} — ${n}`;
    return cDisp || n;
}

function extractNumericSuffix(fullCode) {
    const s = String(fullCode || '').trim();
    const m = s.match(/-(\d{4})$/);
    return m ? String(parseInt(m[1], 10)) : '';
}

function getNextItemCodeNumber(categoryCode) {
    const cat = normalizeCategoryCodeForItemCode(categoryCode);
    if (!cat) return '1';

    let maxSuffix = 0;

    (inventoryCache || []).forEach(item => {
        const itemCategory = normalizeCategoryCodeForItemCode(item.item_category_code || '');
        if (itemCategory !== cat) return;

        const suffix = parseInt(extractNumericSuffix(item.inventory_system_item_code), 10);
        if (!isNaN(suffix) && suffix > maxSuffix) {
            maxSuffix = suffix;
        }
    });

    return String(maxSuffix + 1);
}

function buildItemCodePreview(categoryCode, numericInput) {
    const cat = normalizeCategoryCodeForItemCode(categoryCode);
    const rawNum = String(numericInput || '').trim();

    if (!cat) return '';

    const num = rawNum || getNextItemCodeNumber(categoryCode);
    if (!num) return `CSM-${cat}-0001`;

    const digits = num.replace(/\D/g, '');
    if (!digits) return `CSM-${cat}-0001`;

    const normalized = String(parseInt(digits, 10));
    if (!normalized || normalized === 'NaN' || normalized === '0') return `CSM-${cat}-0001`;

    return `CSM-${cat}-${normalized.padStart(4, '0')}`;
}

function updateCodePreview() {
    const categoryCode = $('#categorySelect').val() || '';
    const itemCodeNumber = ($('#itemCodeNumberField').val() || '').replace(/\D/g, '');
    $('#itemCodeNumberField').val(itemCodeNumber);

    const preview = buildItemCodePreview(categoryCode, itemCodeNumber);
    $('#fullItemCodeField').val(preview);

    $('#codePatternText').text(
        `CSM-${normalizeCategoryCodeForItemCode(categoryCode || 'CATEGORY')}-0001`
            .replace('CSM-CATEGORY-0001', 'CSM-[CATEGORY]-0001')
    );

    updateQrPreview(preview);
}

function updateEditCodePreview() {
    const categoryCode = $('#edit_item_category_code').val() || '';
    const itemCodeNumber = ($('#edit_inventory_system_item_code').val() || '').replace(/\D/g, '');
    $('#edit_inventory_system_item_code').val(itemCodeNumber);

    const preview = buildItemCodePreview(categoryCode, itemCodeNumber);
    $('#edit_full_item_code').val(preview);
}

function updateQrPreview(code) {
    const img = $('#qrPreviewImg');
    const empty = $('#qrPreviewEmpty');
    const loading = $('#qrLoading');
    const meta = $('#qrPreviewMeta');

    if (!code) {
        img.addClass('d-none').attr('src', '');
        empty.removeClass('d-none').text('QR preview will appear here.');
        loading.hide();
        meta.text('Based on the full item code preview');
        return;
    }

    loading.show();
    empty.addClass('d-none');

    const src = `${QR_GENERATOR_URL}?v=${encodeURIComponent(code)}&t=${Date.now()}`;
    img.off('load error')
        .on('load', function() {
            loading.hide();
            img.removeClass('d-none');
        })
        .on('error', function() {
            loading.hide();
            img.addClass('d-none').attr('src', '');
            empty.removeClass('d-none').text('Failed to load QR preview.');
        })
        .attr('src', src);

    meta.text(code);
}

function loadInventoryForDropdown() {
    $.post(PROCESS_URL, { action: 'list_inventory' }, function(res) {
        if (res && res.success) {
            inventoryCache = res.data || [];
            updateCodePreview();

            const options = ['<option value="">Select item code</option>'];
            inventoryCache.forEach(item => {
                const category = item.item_category_name ? `[${item.item_category_name}] ` : '';
                const desc = item.item_description ? ` - ${item.item_description}` : '';
                options.push(`<option value="${item.inventory_system_item_code}">${escHtml(item.inventory_system_item_code)} ${escHtml(category + desc)}</option>`);
            });

            $('#searchItemCode').html(options.join(''));

            if ($('#searchItemCode').data('select2')) {
                $('#searchItemCode').off('select2:select select2:clear').select2('destroy');
            }

            $('#searchItemCode').select2({
                placeholder: 'Search item code',
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
                $('#addQtyInventoryId').val('');
                $('#addQtyLastScanned').text('-');
            });

            showPageMessage('');
        } else {
            showPageMessage((res && res.message) || 'Failed to load inventory list.');
        }
    }, 'json').fail(function() {
        showPageMessage('Server error while loading inventory list.');
    });
}

function collectAddItemDraft() {
    const categoryCode = ($('#categorySelect').val() || '').trim();
    const categoryLabel = ($('#categorySelect option:selected').text() || '').trim();

    return {
        inventory_system_item_code: ($('#itemCodeNumberField').val() || '').trim(),
        full_item_code_preview: ($('#fullItemCodeField').val() || '').trim(),
        item_category_code: categoryCode,
        category_label: categoryLabel && categoryLabel !== 'Select category' ? categoryLabel : '',
        item_description: ($('#itemDescription').val() || '').trim(),
        unit: ($('#unitSelect').val() || '').trim(),
        unit_label: ($('#unitSelect option:selected').text() || '').trim(),
        source_of_funds: ($('#sourceOfFunds').val() || '').trim(),
        quantity: ($('#unitQuantity').val() || '').trim(),
        current_quantity: ($('#currentUnitQuantity').val() || '').trim(),
        qty_crit_level: ($('#unitCritLevel').val() || '').trim(),
        cost_value: ($('#itemCost').val() || '').trim()
    };
}

function validateAddItemDraft(draft) {
    if (!draft.item_category_code) return 'Category is required.';
    if (!draft.item_description) return 'Itemized description is required.';
    if (!draft.unit) return 'Unit is required.';
    //if (draft.inventory_system_item_code && !/^\d+$/.test(draft.inventory_system_item_code)) return 'Item code number must contain digits only.';
    if (draft.quantity === '' || parseInt(draft.quantity, 10) < 0) return 'Actual Qty must be 0 or higher.';
    // if (draft.current_quantity === '' || parseInt(draft.current_quantity, 10) < 0) return 'Available to Issue must be 0 or higher.';
    if (draft.qty_crit_level === '' || parseInt(draft.qty_crit_level, 10) < 0) return 'Critical Level must be 0 or higher.';
    if (parseInt(draft.quantity, 10) <= parseInt(draft.qty_crit_level, 10)) return 'Actual Qty must be greater than Critical Level.';
    // if (draft.cost_value === '' || parseFloat(draft.cost_value) < 0) return 'Cost Value must be 0 or higher.';
    // if (parseInt(draft.current_quantity, 10) > parseInt(draft.quantity, 10)) return 'Available to Issue cannot exceed Actual Qty.';
    return '';
}

function populateReviewModal(draft) {
    $('#reviewCategory').text(draft.category_label || draft.item_category_code || '-');
    $('#reviewItemCode').text(draft.full_item_code_preview || '(Auto-generated)');
    $('#reviewDescription').text(draft.item_description || '-');
    $('#reviewUnit').text(draft.unit_label || draft.unit || '-');
    $('#reviewSource').text(draft.source_of_funds || '-');
    $('#reviewUnitQty').text(draft.quantity);
    $('#reviewCurrentQty').text(draft.current_quantity);
    $('#reviewCritLevel').text(draft.qty_crit_level);
    $('#reviewCost').text(draft.cost_value !== '' ? parseFloat(draft.cost_value).toFixed(2) : '0.00');
    $('#reviewQrValue').text(draft.full_item_code_preview || '(Auto-generated after save)');
    $('#reviewRuleCheck').html(`
        Available (${escHtml(draft.current_quantity)}) is
        ${parseInt(draft.current_quantity, 10) >= parseInt(draft.qty_crit_level, 10) ? '<span class="text-success fw-semibold">not below</span>' : '<span class="text-danger fw-semibold">below</span>'}
        Critical Level (${escHtml(draft.qty_crit_level)}),
        and
        ${parseInt(draft.current_quantity, 10) <= parseInt(draft.quantity, 10) ? '<span class="text-success fw-semibold">not above</span>' : '<span class="text-danger fw-semibold">above</span>'}
        Actual Qty (${escHtml(draft.quantity)}).
    `);
}

function buildAddItemPayload(draft) {
    return {
        action: 'add_inventory',
        inventory_system_item_code: draft.inventory_system_item_code,
        item_description: draft.item_description,
        item_category_code: draft.item_category_code,
        unit: draft.unit,
        quantity: draft.quantity,
        current_quantity: draft.current_quantity,
        qty_crit_level: draft.qty_crit_level,
        cost_value: draft.cost_value,
        source_of_funds: draft.source_of_funds
    };
}

function openReviewAddItemModal(draft) {
    pendingAddItemDraft = draft;
    $('#reviewAddItemError').addClass('d-none').text('');
    populateReviewModal(draft);
    $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
    $('#reviewAddItemModal').modal('show');
}

function submitAddItemFromReview() {
    if (!pendingAddItemDraft || isSavingAddItem) return;

    isSavingAddItem = true;
    $('#confirmAddItemBtn').prop('disabled', true).html('<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Saving...');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: buildAddItemPayload(pendingAddItemDraft),
        success: function(response) {
            if ($.trim(response) === 'success') {
                $('#reviewAddItemModal').modal('hide');
                showMessage('#addItemMsg', 'success', 'Record added successfully.');
                $('#addItemForm')[0].reset();
                $('#categorySelect').val('').trigger('change');
                $('#unitSelect').val('').trigger('change');
                updateCodePreview();
                refreshTable();
                loadInventoryForDropdown();
                return;
            }
            $('#reviewAddItemError').removeClass('d-none').text(response || 'Save failed.');
        },
        error: function(xhr) {
            let msg = 'Server error while saving.';
            if (xhr.responseText) msg = xhr.responseText;
            $('#reviewAddItemError').removeClass('d-none').text(msg);
        },
        complete: function() {
            isSavingAddItem = false;
            $('#confirmAddItemBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Confirm Save');
        }
    });
}

function openInvImageModal(row){
    if (!row) return;

    const rel = row.display_image || '';
    if (!rel) return;

    const src = absUrl(rel);
    const title = `${row.inventory_system_item_code || ''} — ${String(row.item_description || '').slice(0, 60)}`;

    $('#viewInvImageTitle').text(title);
    $('#viewInvImageBodyMsg').html('');
    $('#viewInvImageImg')
        .off('error')
        .attr('src', src)
        .on('error', function(){
            $(this).attr('src','');
            $('#viewInvImageBodyMsg').html('<div class="alert alert-warning mb-0">Image not found.</div>');
        });

    $('#viewInvImageModal').modal('show');
}

function initTable() {
    inventoryTable = new Tabulator('#inventoryTable', {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_recent_added' },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        placeholder: 'No recently added items found',
        pagination: 'local',
        paginationSize: 20,
        paginationSizeSelector: [20, 100, 500, 1000, true],
        initialSort: [
            { column: "created_at", dir: "desc" }
        ],
        ajaxResponse: function(url, params, response) {
            const data = response && response.data ? response.data : [];
            inventoryCache = data;
            return data;
        },
        columns: [
            {
                title: "Image",
                field: "display_image",
                width: 88,
                hozAlign: "center",
                headerSort: false,
                formatter: function(cell) {
                    const d = cell.getRow().getData();
                    const rel = d.display_image || '';
                    const url = rel ? absUrl(rel) : '';

                    if (url) {
                        return `
                            <div class="inv-thumb-click" title="Click to view">
                                <div class="inv-thumb-wrap">
                                    <img src="${escHtml(url)}" alt="img"
                                         onerror="this.remove(); this.parentNode.innerHTML='<i class=&quot;bi bi-image inv-thumb-fallback&quot;></i>';">
                                </div>
                            </div>
                        `;
                    }
                    return `<div class="inv-thumb-wrap"><i class="bi bi-image inv-thumb-fallback"></i></div>`;
                },
                cellClick: function(e, cell) {
                    const d = cell.getRow().getData();
                    if (d && d.display_image) openInvImageModal(d);
                }
            },
            {
                title: 'QR',
                field: 'inventory_system_item_code',
                width: 92,
                hozAlign: 'center',
                headerSort: false,
                formatter: function(cell) {
                    const code = cell.getValue();
                    if (!code) return '';
                    const u = `${QR_GENERATOR_URL}?v=${encodeURIComponent(code)}`;
                    return `<img src="${u}" data-code="${escHtml(code)}" class="qr-thumb" style="height:56px;width:56px;object-fit:contain;border:1px solid #dee2e6;border-radius:8px;background:#fff;padding:4px;cursor:pointer;" alt="QR">`;
                }
            },
            {
                title: 'Item Code',
                field: 'inventory_system_item_code',
                width: 190,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...',
                formatter: function(cell){
                    const val = cell.getValue();
                    return `<span class="badge bg-light text-dark border badge-code">${escHtml(val)}</span>`;
                }
            },
            {
                title: 'Description',
                field: 'item_description',
                widthGrow: 2,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...'
            },
            {
                title: 'Category',
                field: 'item_category_code',
                width: 220,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...',
                formatter: function(cell){
                    const d = cell.getRow().getData();
                    return escHtml(groupLabel(d.item_category_code, d.item_category_name));
                }
            },
            { title: 'Unit', field: 'unit', width: 100, hozAlign: 'center', headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            { title: 'Actual Qty', field: 'quantity', width: 100, hozAlign: 'center' },
            { title: 'Available', field: 'current_quantity', width: 100, hozAlign: 'center' },
            { title: 'Critical', field: 'qty_crit_level', width: 95, hozAlign: 'center' },
            {
                title: 'Cost',
                field: 'cost_value',
                width: 110,
                hozAlign: 'right',
                formatter: function(cell){
                    const v = cell.getValue();
                    return v !== null && v !== '' ? parseFloat(v).toFixed(2) : '0.00';
                }
            },
            { title: 'Source', field: 'source_of_funds', width: 140, headerFilter: 'input', headerFilterPlaceholder: 'Filter...' },
            {
                title: 'Date Added',
                field: 'created_at',
                width: 170,
                sorter: 'datetime',
                sorterParams: { format: 'yyyy-MM-dd HH:mm:ss' },
                formatter: function(cell){
                    const value = cell.getValue();
                    if (!value) return '-';
                    const d = new Date(value.replace(' ', 'T'));
                    if (isNaN(d.getTime())) return value;
                    return d.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                }
            },
            {
                title: 'Acquired',
                field: 'acquisition_date',
                width: 120,
                formatter: function(cell){
                    return cell.getValue() || '-';
                }
            },
//            {
//                title: 'Actions',
//                field: 'inventory_id',
//                width: 200,
//                hozAlign: 'center',
//                headerSort: false,
//                formatter: function(cell){
//                    const id = cell.getValue();
//                    return `
//                        <button type="button" class="btn btn-sm btn-primary me-1 btn-edit" data-id="${id}">
//                            <i class="bi bi-pencil-square"></i>
//                        </button>
//                        <button type="button" class="btn btn-sm btn-outline-primary btn-available" data-id="${id}">
//                            <i class="bi bi-box-arrow-in-down"></i>
//                        </button>
//                    `;
//                }
//            }
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
    const search = ($('#tableSearch').val() || '').toLowerCase().trim();

    if (!inventoryTable) return;

    inventoryTable.setData(PROCESS_URL, { action: 'list_recent_added' }, 'POST').then(function() {
        if (!search) {
            inventoryTable.clearFilter(true);
            inventoryTable.setSort("created_at", "desc");
            return;
        }

        inventoryTable.setFilter(function(data){
            const hay = [
                data.inventory_system_item_code,
                data.item_description,
                data.item_category_code,
                data.item_category_name,
                data.unit,
                data.source_of_funds
            ].join(' ').toLowerCase();

            return hay.indexOf(search) !== -1;
        });
        inventoryTable.setSort("created_at", "desc");
    }).catch(function() {});
}

function loadItemByCode(code) {
    if (!code) return;

    $.post(PROCESS_URL, { action: 'find_item_by_code', inventory_system_item_code: code }, function(res) {
        if (res && res.success) {
            const d = res.data;
            $('#addQtyInventoryId').val(d.inventory_id);
            $('#addQtySource').val(d.source_of_funds || '');
            $('#addQtyCost').val(d.cost_value || '');

            $('#itemInfo').html(`
                <div class="d-flex flex-column gap-2">
                    <div>
                        <div class="small text-muted">Item Code</div>
                        <div class="fw-semibold">${escHtml(d.inventory_system_item_code || '-')}</div>
                    </div>
                    <div class="small text-muted">${escHtml(groupLabel(d.item_category_code, d.item_category_name))}</div>
                    <div>
                        <div class="small text-muted">Description</div>
                        <div>${escHtml(d.item_description || '-')}</div>
                    </div>
                    <div class="small text-muted">
                        Unit: <span class="fw-semibold">${escHtml(d.unit || '-')}</span>
                        &nbsp;|&nbsp;
                        Actual Qty: <span class="fw-semibold">${escHtml(d.quantity || '0')}</span>
                        &nbsp;|&nbsp;
                        Available: <span class="fw-semibold">${escHtml(d.current_quantity || '0')}</span>
                    </div>
                    <div class="small text-muted">
                        Critical: <span class="fw-semibold">${escHtml(d.qty_crit_level || '0')}</span>
                        ${d.source_of_funds ? ` | Source: ${escHtml(d.source_of_funds)}` : ''}
                        ${d.cost_value ? ` | Cost: ${escHtml(d.cost_value)}` : ''}
                    </div>
                </div>
            `);
        } else {
            $('#addQtyInventoryId').val('');
            $('#itemInfo').html('<span class="text-danger">' + escHtml((res && res.message) || 'Item not found.') + '</span>');
        }
    }, 'json').fail(function(xhr) {
        let msg = 'Item not found.';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        $('#addQtyInventoryId').val('');
        $('#itemInfo').html('<span class="text-danger">' + escHtml(msg) + '</span>');
    });
}

function openEditModal(id){
    $('#editRecordMsg').html('');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: { action: 'get_inventory', inventory_id: id },
        success: function(res){
            if(!res || !res.success){
                alert(res && res.message ? res.message : 'Record not found.');
                return;
            }

            const d = res.data;

            $('#edit_inventory_id').val(d.inventory_id);
            $('#edit_inventory_system_item_code').val(extractNumericSuffix(d.inventory_system_item_code));
            $('#edit_item_description').val(d.item_description || '');
            $('#edit_item_category_code').val(d.item_category_code || '').trigger('change');

            const editUnitVal = d.unit || '';
            if (editUnitVal && $('#edit_unit option').filter(function(){ return $(this).val() === editUnitVal; }).length === 0) {
                $('#edit_unit').append(new Option(editUnitVal, editUnitVal, false, false));
            }
            $('#edit_unit').val(editUnitVal).trigger('change');

            $('#edit_unit_quantity').val(d.quantity || 0);
            $('#edit_unit_crit_level').val(d.qty_crit_level || 0);
            $('#edit_item_cost').val(d.cost_value || 0);
            $('#edit_source_of_funds').val(d.source_of_funds || '');
            $('#edit_current_unit_quantity_display').val(d.current_quantity || 0);

            updateEditCodePreview();
            $('#editRecordModal').modal('show');
        },
        error: function(xhr){
            alert('Error loading record.');
            console.error(xhr.responseText);
        }
    });
}

function openAvailableModal(id){
    $('#availableMsg').html('');
    $('#avail_inventory_id').val(id);

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: { action: 'get_inventory', inventory_id: id },
        success: function(res){
            if (res && res.success) {
                const d = res.data;
                $('#avail_item_label').text(`${d.inventory_system_item_code || ''} — ${String(d.item_description || '').slice(0, 60)}`);
                $('#avail_current_unit_quantity').val(d.current_quantity || 0);
            } else {
                $('#avail_item_label').text(`ID #${id}`);
                $('#avail_current_unit_quantity').val(0);
            }
            $('#availableModal').modal('show');
        },
        error: function(){
            $('#avail_item_label').text(`ID #${id}`);
            $('#avail_current_unit_quantity').val(0);
            $('#availableModal').modal('show');
        }
    });
}

$(document).ready(function() {
    observeFloatingTargets([
        '#pageMsg',
        '#addItemMsg',
        '#addQtyMsg',
        '#viewInvImageBodyMsg',
        '#reviewAddItemError',
        '#editRecordMsg',
        '#availableMsg',
        '#searchScanError',
        '#addQtyScanError'
    ]);
    $('#categorySelect').select2({
        placeholder: 'Select category',
        allowClear: true,
        width: '100%'
    });

    $('#unitSelect').select2({
        placeholder: 'Select existing or type a new unit',
        allowClear: true,
        tags: true,
        width: '100%'
    });

    $('#edit_item_category_code').select2({
        dropdownParent: $('#editRecordModal'),
        placeholder: 'Select category',
        allowClear: true,
        width: '100%'
    });

    $('#edit_unit').select2({
        dropdownParent: $('#editRecordModal'),
        placeholder: 'Select existing or type a new unit',
        allowClear: true,
        tags: true,
        width: '100%'
    });

    loadInventoryForDropdown();
    initTableLazy();
    updateCodePreview();

    $('#categorySelect').on('change', updateCodePreview);
    $('#itemCodeNumberField').on('input', updateCodePreview);

    $('#edit_item_category_code').on('change', updateEditCodePreview);
    $('#edit_inventory_system_item_code').on('input', updateEditCodePreview);

    $('#unitQuantity, #currentUnitQuantity').on('input', function() {
        const unitQty = parseInt($('#unitQuantity').val() || '0', 10);
        const currentQty = parseInt($('#currentUnitQuantity').val() || '0', 10);
        if (currentQty > unitQty) {
            $('#currentUnitQuantity').val(unitQty);
        }
    });

    $('#confirmAddItemBtn').on('click', function() {
        submitAddItemFromReview();
    });

    $('#reviewAddItemModal').on('hidden.bs.modal', function() {
        pendingAddItemDraft = null;
        isSavingAddItem = false;
        $('#reviewAddItemError').addClass('d-none').text('');
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
        $('#categorySelect').val('').trigger('change');
        $('#unitSelect').val('').trigger('change');
        $('#addItemMsg').html('');
        updateCodePreview();
    });

    $('#addQtyForm').on('submit', function(e) {
        e.preventDefault();
        $('#addQtyMsg').html('');

        const inventoryId = ($('#addQtyInventoryId').val() || '').trim();
        if (!inventoryId) {
            showMessage('#addQtyMsg', 'danger', 'Please select or scan an item code first.');
            return;
        }

        $.ajax({
            url: PROCESS_URL,
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize() + '&action=add_quantity',
            success: function(res) {
                if (res && res.success) {
                    showMessage('#addQtyMsg', 'success', 'Quantity updated successfully.');
                    const codeVal = $('#searchItemCode').val();
                    if (codeVal) loadItemByCode(codeVal);
                    refreshTable();
                    loadInventoryForDropdown();
                } else {
                    showMessage('#addQtyMsg', 'danger', (res && res.message) || 'Update failed.');
                }
            },
            error: function(xhr) {
                let msg = 'Server error while updating quantity.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showMessage('#addQtyMsg', 'danger', msg);
            }
        });
    });

    $('#tableSearch').on('keyup', function() {
        refreshTable();
    });

    $('#inventoryTable')
        .off('click', '.btn-edit')
        .on('click', '.btn-edit', function(e){
            e.preventDefault();
            e.stopPropagation();
            openEditModal($(this).data('id'));
        });

    $('#inventoryTable')
        .off('click', '.btn-available')
        .on('click', '.btn-available', function(e){
            e.preventDefault();
            e.stopPropagation();
            openAvailableModal($(this).data('id'));
        });

    $('#inventoryTable')
        .off('click', '.qr-thumb')
        .on('click', '.qr-thumb', function(e){
            e.preventDefault();
            e.stopPropagation();
            const code = $(this).data('code');
            if (!code) return;
            updateQrPreview(code);
            $('html, body').animate({
                scrollTop: $('#qrPreviewWrap').offset().top - 120
            }, 250);
        });

    $('#editInventoryForm').on('submit', function(e){
        e.preventDefault();
        $('#editRecordMsg').html('');

        $.ajax({
            url: PROCESS_URL,
            type: 'POST',
            data: $(this).serialize() + '&action=update_inventory',
            success: function(response){
                if ($.trim(response) === 'success') {
                    $('#editRecordModal').modal('hide');
                    refreshTable();
                    loadInventoryForDropdown();
                } else {
                    $('#editRecordMsg').html('<div class="alert alert-danger">' + response + '</div>');
                }
            },
            error: function(xhr){
                $('#editRecordMsg').html('<div class="alert alert-danger">Error updating record.</div>');
                console.error(xhr.responseText);
            }
        });
    });

    $('#availableForm').on('submit', function(e){
        e.preventDefault();
        $('#availableMsg').html('');

        $.ajax({
            url: PROCESS_URL,
            type: 'POST',
            data: $(this).serialize() + '&action=update_available_qty',
            success: function(response){
                if ($.trim(response) === 'success') {
                    $('#availableModal').modal('hide');
                    refreshTable();
                    loadInventoryForDropdown();
                } else {
                    $('#availableMsg').html('<div class="alert alert-danger">' + response + '</div>');
                }
            },
            error: function(xhr){
                let msg = 'Error updating available quantity.';
                if (xhr.responseText) msg = xhr.responseText;
                $('#availableMsg').html('<div class="alert alert-danger">' + escHtml(msg) + '</div>');
            }
        });
    });

    if (typeof initQrSearch === 'function') {
        initQrSearch({
            modalId: '#searchQrModal',
            openButton: '#openSearchScanner',
            searchInput: '#tableSearch',
            cameraSelectId: '#searchCameraSelect',
            startBtnId: '#searchBtnStart',
            stopBtnId: '#searchBtnStop',
            previewId: '#searchPreview',
            lastScannedId: '#searchLastScanned',
            errorId: '#searchScanError',
            loadingId: '#searchScannerLoading',
            qrboxSize: 220,
            aspectRatio: 1,
            onSearch: function(decodedText) {
                if (!decodedText) return;
                $('#tableSearch').val(decodedText);
                refreshTable();
            }
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
            qrboxSize: 220,
            aspectRatio: 1,
            onSearch: function(decodedText) {
                if (!decodedText) return;
                $('#addQtyLastScanned').text(decodedText);
                if ($('#searchItemCode option[value="' + decodedText + '"]').length === 0) {
                    $('#searchItemCode').append(new Option(decodedText, decodedText, true, true));
                }
                $('#searchItemCode').val(decodedText).trigger('change');
                loadItemByCode(decodedText);
            }
        });
    }
});
</script>
</body>
</html>
