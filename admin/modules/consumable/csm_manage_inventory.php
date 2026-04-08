<?php
// admin/modules/consumable/csm_inventory.php
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
        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fff;
        }
        .summary-card.is-clickable {
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        .summary-card.is-clickable:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 14px rgba(30, 58, 138, 0.08);
            transform: translateY(-1px);
        }
        .summary-card.is-active {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .summary-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .summary-value {
            font-size: 18px;
            font-weight: 700;
        }
        .filter-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .item-thumb {
            width: 46px;
            height: 46px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #f8f9fa;
            cursor: zoom-in;
        }
        .item-badge {
            width: 46px;
            height: 46px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #1E3A8A;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            border: 1px solid rgba(0,0,0,0.06);
            cursor: default;
        }
        .thumb-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .thumb-click {
            cursor: pointer;
            display: inline-flex;
        }
        .inv-thumb-wrap {
            width: 56px;
            height: 56px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .inv-thumb-wrap img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            display: block;
        }
        .inv-thumb-fallback {
            font-size: 24px;
            line-height: 1;
            color: #6c757d;
        }
        .inv-thumb-click {
            cursor: pointer;
            display: inline-flex;
        }
        .img-preview {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
        }
        #csm-inventory-table .tabulator-header .tabulator-col .tabulator-col-content .tabulator-col-title {
            white-space: normal !important;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 2.4em;
        }
        #csm-inventory-table .tabulator-header .tabulator-col .tabulator-col-content {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        #csm-inventory-table .tabulator-header .tabulator-col .tabulator-header-filter {
            margin-top: 4px;
        }
        #csm-inventory-table .tabulator-header .tabulator-col .tabulator-header-filter input,
        #csm-inventory-table .tabulator-header .tabulator-col .tabulator-header-filter select {
            height: 32px;
            line-height: 1.2;
            padding: 4px 8px;
            box-sizing: border-box;
        }
        #csm-inventory-table .tabulator-tableholder {
            max-height: 72vh;
            overflow-y: auto;
        }
        .two-line-cell {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
            max-height: 2.5em;
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
            max-height: 3.75em;
        }
        .tabulator {
            font-size: 0.875rem;
        }
        .btn-match-input {
            height: 38px;
        }
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-0 { background: #f3f4f6; color: #374151; }
        .status-1 { background: #dcfce7; color: #166534; }
        .status-2 { background: #fef3c7; color: #92400e; }
        .status-3 { background: #fee2e2; color: #991b1b; }
        .avail-metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .avail-metric-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px 12px;
        }
        .avail-metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .avail-metric-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
        }
        .avail-metric-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        .avail-bulk-mode {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-top: 8px;
        }
        .avail-bulk-mode .form-check {
            margin-bottom: 0;
            border: 1px solid #dbe3f0;
            border-radius: 14px;
            background: #fff;
            padding: 12px 14px;
            min-height: 100%;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .avail-bulk-mode .form-check:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 14px rgba(30, 58, 138, 0.08);
        }
        .avail-bulk-mode .form-check-input {
            margin-top: .28rem;
            flex-shrink: 0;
        }
        .avail-bulk-mode .form-check-label {
            flex: 1 1 auto;
            min-width: 0;
        }
        .avail-bulk-panel {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 14px;
            margin-top: 10px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .avail-bulk-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .avail-bulk-cap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .avail-bulk-help {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .avail-bulk-help-text {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }
        .avail-bulk-table {
            max-height: 260px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }
        .avail-bulk-table table {
            margin-bottom: 0;
            min-width: 640px;
        }
        .avail-bulk-table td,
        .avail-bulk-table th {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        .avail-bulk-item-meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        .avail-bulk-input {
            width: 100%;
            max-width: 100%;
        }
        .avail-bulk-mode .form-check-input:checked {
            background-color: #1E3A8A;
            border-color: #1E3A8A;
        }
        .avail-bulk-mode .form-check-input:checked ~ .form-check-label {
            color: #0f172a;
        }
        .avail-bulk-mode-title {
            display: block;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
            line-height: 1.3;
        }
        .avail-bulk-mode-sub {
            display: block;
            font-size: 12px;
            color: #64748b;
            line-height: 1.35;
            margin-top: 2px;
        }
        @media (max-width: 575.98px) {
            .avail-metric-grid {
                grid-template-columns: 1fr;
            }
        }
        #availabilityModal.is-bulk-mode .modal-dialog {
            max-width: min(1120px, calc(100vw - 2rem));
        }
        #availabilityModal .modal-dialog,
        #availabilityModal .modal-content,
        #availabilityModal .modal-body,
        #availabilityModal .row,
        #availabilityModal [class*="col-"] {
            min-width: 0;
        }
        #availabilityModal .modal-content {
            overflow: hidden;
        }
        #availabilityModal .modal-body {
            overflow-x: hidden;
        }
        #availabilityModal.is-bulk-mode #availQtyWrap,
        #availabilityModal.is-bulk-mode #availRulesWrap {
            align-self: start;
        }
        #availabilityModal.is-bulk-mode #availQtyWrap {
            flex-basis: 42%;
            max-width: 42%;
        }
        #availabilityModal.is-bulk-mode #availRulesWrap {
            flex-basis: 58%;
            max-width: 58%;
        }
        #availabilityModal.is-bulk-mode #availQtyHint {
            margin-top: 14px;
        }
        @media (max-width: 991.98px) {
            #availabilityModal.is-bulk-mode #availQtyWrap,
            #availabilityModal.is-bulk-mode #availRulesWrap {
                flex-basis: 100%;
                max-width: 100%;
            }
        }
        @media (max-width: 767.98px) {
            #availabilityModal .modal-dialog {
                width: calc(100vw - 1rem);
                max-width: calc(100vw - 1rem);
                margin: .5rem auto;
            }
            #availabilityModal .modal-header,
            #availabilityModal .modal-body,
            #availabilityModal .modal-footer {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            #availabilityModal .modal-title {
                font-size: 1.05rem;
                line-height: 1.3;
            }
            #availabilityModal .row.g-3 {
                --bs-gutter-y: .85rem;
            }
            .avail-bulk-panel {
                padding: 12px;
            }
            .avail-bulk-mode .form-check {
                padding: 10px 12px;
            }
            .avail-bulk-head {
                flex-direction: column;
                align-items: stretch;
            }
            .avail-bulk-cap {
                width: 100%;
                white-space: normal;
                justify-content: center;
            }
            .avail-bulk-help {
                flex-direction: column;
                align-items: stretch;
            }
            .avail-bulk-help-text,
            .avail-bulk-mode-sub {
                font-size: 11px;
            }
            .avail-bulk-mode-title,
            .avail-bulk-head .form-label {
                font-size: 1rem;
            }
            #availUseLowestQty {
                width: 100%;
            }
            #availBulkCurrentQty {
                width: 100%;
                min-width: 0;
            }
            .avail-bulk-table {
                max-height: 240px;
            }
            #availabilityModal.is-bulk-mode .modal-dialog {
                width: calc(100vw - 1rem);
                max-width: calc(100vw - 1rem);
            }
        }

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
        #searchPreview{
            width:100%;
            height:100%;
            background:#111;
        }
        #searchPreview video,
        #searchPreview canvas{
            width:100% !important;
            height:100% !important;
            object-fit:cover !important;
            display:block !important;
            margin:0 !important;
            padding:0 !important;
            border:0 !important;
            background:#111 !important;
        }
        #searchPreview > div{
            width:100% !important;
            height:100% !important;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 2px 6px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            margin-top: 4px;
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
        <h1 class="h4 fw-semibold mb-1">CSM Inventory</h1>
        <p class="text-muted small mb-0">Review inventory, filter by date, export data, and update availability rules.</p>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold">
                <i class="bi bi-table"></i>&ensp;Inventory Overview
            </div>
            <div class="card-body mt-3 bg-white">
                <div id="invMsg" class="alert alert-danger d-none mb-3"></div>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <div class="filter-label">Search</div>
                        <div class="input-group">
                            <input type="text" id="invSearch" class="form-control" placeholder="Item code, description, category, source">
                            <button class="btn btn-outline-secondary" type="button" id="openSearchScanner" title="Scan QR">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-label">Date Range</div>
                        <input type="text" id="dateRange" class="form-control" placeholder="YYYY-MM-DD - YYYY-MM-DD">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100 btn-match-input" id="exportCsv">Export CSV</button>
                        <button class="btn btn-outline-primary w-100 btn-match-input" id="exportExcel">Export Excel</button>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Total Items</div>
                            <div class="summary-value" id="sumItems">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Total Quantity</div>
                            <div class="summary-value" id="sumQty">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="summary-card">
                            <div class="summary-label">Available Quantity</div>
                            <div class="summary-value" id="sumAvailQty">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="summary-card is-clickable" id="criticalOutCard" title="Show only stock critical and out of stock items">
                            <div class="summary-label">Critical / Out</div>
                            <div class="summary-value" id="sumCritical">0 / 0</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-2">
                    <button class="btn btn-outline-primary btn-sm" id="bulkSetAvailability">
                        <i class="bi bi-sliders"></i> Bulk Set Rules
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="clearSelection">Clear Selection</button>
                </div>

                <div id="csm-inventory-table"></div>
            </div>
        </div>
    </section>
</main>

<?php include_once FOOTER_PATH; ?>

<!-- AVAILABILITY MODAL -->
<div class="modal fade" id="availabilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-sliders"></i>&ensp;Set Availability Rules</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="availabilityForm">
                    <input type="hidden" name="action" value="update_availability_settings">
                    <input type="hidden" name="inventory_id" id="availInventoryId">
                    <input type="hidden" id="availBulkIds">

                    <div class="small fw-semibold text-dark mb-2 d-none" id="availBulkNote"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="small text-muted">Item</div>
                            <div class="fw-semibold" id="availItemLabel">-</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Status</div>
                            <div id="availStatusLabel">-</div>
                        </div>

                        <div class="col-12 col-lg-5" id="availQtyWrap">
                            <label class="form-label fw-semibold">Available Quantity</label>
                            <input type="number" min="0" name="current_quantity" id="availCurrentQty" class="form-control" placeholder="Enter available quantity">
                            <div id="availBulkControls" class="d-none">
                                <div class="avail-bulk-mode">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="avail_bulk_qty_mode" id="availBulkQtyModeAll" value="same_for_all" checked>
                                        <label class="form-check-label" for="availBulkQtyModeAll">
                                            <span class="avail-bulk-mode-title">Set all selected items</span>
                                            <span class="avail-bulk-mode-sub">Apply one shared available qty to every selected row.</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="avail_bulk_qty_mode" id="availBulkQtyModeIndividual" value="individual">
                                        <label class="form-check-label" for="availBulkQtyModeIndividual">
                                            <span class="avail-bulk-mode-title">Set individually</span>
                                            <span class="avail-bulk-mode-sub">Use a different available qty for each selected row.</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="avail-bulk-panel" id="availBulkSameWrap">
                                    <div class="avail-bulk-head">
                                        <label class="form-label fw-semibold mb-0">Available Qty for All Selected Items</label>
                                        <div class="avail-bulk-cap" id="availBulkLowestCap">
                                            <i class="bi bi-arrow-down-circle"></i>
                                            Lowest Actual Qty: <span id="availBulkLowestQty">-</span>
                                        </div>
                                    </div>
                                    <input type="number" min="0" id="availBulkCurrentQty" class="form-control avail-bulk-input" placeholder="Enter one value for all selected items">
                                    <div class="avail-bulk-help">
                                        <p class="avail-bulk-help-text" id="availBulkSameHint">This shared value cannot be higher than the lowest actual qty among the selected items.</p>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="availUseLowestQty">Use Lowest Actual Qty</button>
                                    </div>
                                </div>

                                <div class="avail-bulk-panel d-none" id="availBulkIndividualWrap">
                                    <div class="fw-semibold mb-2">Available Qty Per Selected Item</div>
                                    <div class="avail-bulk-table">
                                        <table class="table table-sm align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Item</th>
                                                    <th style="width:120px;">Set Avail</th>
                                                    <th style="width:88px;">Actual</th>
                                                    <th style="width:88px;">Current</th>
                                                    <th style="width:88px;">Critical</th>
                                                </tr>
                                            </thead>
                                            <tbody id="availBulkQtyTbody">
                                                <tr><td colspan="5" class="text-muted">Select items first.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="avail-metric-grid" id="availQtyHint">
                                <div class="avail-metric-card">
                                    <div class="avail-metric-label">Total Quantity</div>
                                    <div class="avail-metric-value" id="availTotalQtyValue">-</div>
                                    <div class="avail-metric-sub" id="availTotalQtyUnit">Unit: -</div>
                                </div>
                                <div class="avail-metric-card">
                                    <div class="avail-metric-label">Critical Level</div>
                                    <div class="avail-metric-value" id="availCriticalLevelValue">-</div>
                                    <div class="avail-metric-sub">Threshold before stock critical</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-7" id="availRulesWrap">
                            <div class="row g-3">
                                <div class="col-12" id="availTeachWrap">
                                    <label class="form-label fw-semibold">Academic Personnel</label>
                                    <select id="availTeachingStatus" class="form-select" multiple></select>
                                    <div class="small text-muted mt-1">Choose allowed teaching status. Select None to block all.</div>
                                </div>

                                <div class="col-12" id="availNonTeachWrap">
                                    <label class="form-label fw-semibold">Administrative</label>
                                    <select id="availNonTeachingStatus" class="form-select" multiple></select>
                                    <div class="small text-muted mt-1">Choose allowed non-teaching status. Select None to block all.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="availMsg" class="mt-3"></div>

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

<!-- IMAGE PREVIEW MODAL -->
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

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/consumable/process/csm_inventory_process.php';
const BULK_PROCESS_URL = BASE_URL + 'admin/modules/consumable/process/csm_inventory_bulk_process.php';

let inventoryTable = null;
let inventoryCache = [];
let invMsgTimeout = null;
let employmentStatuses = [];
const NONE_STATUS_VALUE = 'NONE';
let availStatusLock = false;
let isBulkAvailMode = false;
let statusSummaryFilter = '';
let bulkAvailabilityRows = [];
let bulkLowestActualQty = 0;

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

function showInvMessage(message) {
    if (!message) {
        return;
    }
    showFloatingNotice('danger', message);
}

function showAvailMessage(type, text) {
    showFloatingNotice(type, text);
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

function parseDate(val) {
    if (!val) return null;
    const datePart = String(val).split(' ')[0];
    const date = new Date(datePart);
    return isNaN(date.getTime()) ? null : date;
}

function parseDateRangeInput(raw) {
    const text = (raw || '').trim();
    if (!text) return { from: null, to: null };
    const parts = text.split(/\s+-\s+|\s+\u2013\s+/).filter(Boolean);
    if (parts.length === 1) {
        const d = parseDate(parts[0]);
        return { from: d, to: d };
    }
    const from = parseDate(parts[0]);
    const to = parseDate(parts[1]);
    return { from, to };
}

function twoLineText(value, fallback = '-') {
    const raw = (value === null || value === undefined || value === '') ? fallback : String(value);
    const safe = escHtml(raw);
    return `<span class="two-line-cell" title="${safe}">${safe}</span>`;
}

function threeLineText(value, fallback = '-') {
    const raw = (value === null || value === undefined || value === '') ? fallback : String(value);
    const safe = escHtml(raw);
    return `<span class="three-line-cell" title="${safe}">${safe}</span>`;
}

function formatMetricNumber(value) {
    const n = parseInt(value, 10);
    return Number.isFinite(n) ? n.toLocaleString('en-US') : '0';
}

function clampQtyInputToMax(input) {
    if (!input) return;

    const rawValue = String(input.value || '').trim();
    const rawMax = String(input.getAttribute('max') || '').trim();
    if (rawValue === '' || rawMax === '') return;

    const value = parseInt(rawValue, 10);
    const max = parseInt(rawMax, 10);
    if (isNaN(value) || isNaN(max)) return;

    if (value > max) {
        input.value = max;
    }
}

function getBulkQtyMode() {
    return $('input[name="avail_bulk_qty_mode"]:checked').val() || 'same_for_all';
}

function syncBulkQtyModeUI() {
    const mode = getBulkQtyMode();
    $('#availBulkSameWrap').toggleClass('d-none', mode !== 'same_for_all');
    $('#availBulkIndividualWrap').toggleClass('d-none', mode !== 'individual');
}

function updateBulkQtySharedState(rows) {
    const list = Array.isArray(rows) ? rows : [];

    if (!list.length) {
        bulkLowestActualQty = 0;
        $('#availBulkLowestQty').text('-');
        $('#availBulkCurrentQty').attr('max', '').val('');
        $('#availBulkSameHint').text('This shared value cannot be higher than the lowest actual qty among the selected items.');
        return;
    }

    bulkLowestActualQty = Math.min.apply(null, list.map(function(row) {
        return parseInt(row.quantity, 10) || 0;
    }));

    $('#availBulkLowestQty').text(formatMetricNumber(bulkLowestActualQty));
    $('#availBulkCurrentQty').attr('max', String(bulkLowestActualQty));
    $('#availBulkSameHint').text(`Use exactly ${formatMetricNumber(bulkLowestActualQty)} so the shared value matches the lowest actual qty in the selected rows.`);
}

function renderBulkAvailabilityRows(rows) {
    const $tbody = $('#availBulkQtyTbody');

    if (!rows || !rows.length) {
        $tbody.html('<tr><td colspan="5" class="text-muted">Select items first.</td></tr>');
        return;
    }

    let html = '';
    rows.forEach(function(row) {
        const code = escHtml(row.inventory_system_item_code || '-');
        const desc = escHtml(String(row.item_description || '').slice(0, 60));
        html += `
            <tr>
                <td>
                    <div class="fw-semibold">${code}</div>
                    <div class="avail-bulk-item-meta">${desc || '-'}</div>
                </td>
                <td>
                    <input
                        type="number"
                        min="0"
                        class="form-control form-control-sm avail-bulk-item-qty"
                        data-inventory-id="${row.inventory_id}"
                        max="${parseInt(row.quantity, 10) || 0}"
                        value="${parseInt(row.current_quantity, 10) || 0}"
                    >
                </td>
                <td class="text-center">${formatMetricNumber(row.quantity || 0)}</td>
                <td class="text-center">${formatMetricNumber(row.current_quantity || 0)}</td>
                <td class="text-center">${formatMetricNumber(row.qty_crit_level || 0)}</td>
            </tr>
        `;
    });

    $tbody.html(html);
}

function getBulkCurrentQtyPayload() {
    const mode = getBulkQtyMode();

    if (mode === 'same_for_all') {
        const qtyRaw = ($('#availBulkCurrentQty').val() || '').trim();
        const qty = parseInt(qtyRaw, 10);
        if (qtyRaw === '' || isNaN(qty) || qty < 0) {
            return { ok: false, message: 'Enter a valid available quantity for all selected items.' };
        }
        if (qty !== bulkLowestActualQty) {
            return {
                ok: false,
                message: `For "Set all selected items", the available quantity must match the lowest actual qty (${formatMetricNumber(bulkLowestActualQty)}).`
            };
        }
        return { ok: true, mode: mode, current_quantity: qty };
    }

    const map = {};
    let hasError = false;

    $('#availBulkQtyTbody .avail-bulk-item-qty').each(function() {
        const inventoryId = String($(this).data('inventory-id') || '').trim();
        const qtyRaw = ($(this).val() || '').trim();
        const qty = parseInt(qtyRaw, 10);

        if (!inventoryId || qtyRaw === '' || isNaN(qty) || qty < 0) {
            hasError = true;
            return false;
        }

        const row = bulkAvailabilityRows.find(function(item) {
            return String(item.inventory_id) === inventoryId;
        });
        const actualQty = row ? (parseInt(row.quantity, 10) || 0) : 0;
        if (qty > actualQty) {
            hasError = true;
            return false;
        }

        map[inventoryId] = qty;
    });

    if (hasError) {
        return { ok: false, message: 'Each individual available quantity must be valid and cannot exceed that item\'s actual qty.' };
    }

    return { ok: true, mode: mode, current_quantities_json: JSON.stringify(map) };
}

function statusLabel(status) {
    const s = parseInt(status, 10) || 0;
    switch (s) {
        case 1: return '<span class="status-pill status-1">Available</span>';
        case 2: return '<span class="status-pill status-2">Stock Critical</span>';
        case 3: return '<span class="status-pill status-3">Out of Stock</span>';
        default: return '<span class="status-pill status-0">Unavailable</span>';
    }
}

function updateSummary(rows) {
    const totalItems = rows.length;
    const totalQty = rows.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
    const totalAvailQty = rows.reduce((sum, r) => sum + (parseInt(r.current_quantity, 10) || 0), 0);
    const totalCritical = rows.filter(r => parseInt(r.status, 10) === 2).length;
    const totalOut = rows.filter(r => parseInt(r.status, 10) === 3).length;

    $('#sumItems').text(totalItems);
    $('#sumQty').text(totalQty);
    $('#sumAvailQty').text(totalAvailQty);
    $('#sumCritical').text(totalCritical + ' / ' + totalOut);
    $('#criticalOutCard').toggleClass('is-active', statusSummaryFilter === 'critical_out');
}

function applyInventoryFilters() {
    if (!inventoryTable) return;

    const search = ($('#invSearch').val() || '').toLowerCase().trim();
    const range = parseDateRangeInput($('#dateRange').val());
    const fromDate = range.from;
    const toDate = range.to;
    if (toDate) toDate.setHours(23, 59, 59, 999);

    const hasStatusFilter = statusSummaryFilter === 'critical_out';

    if (!search && !fromDate && !toDate && !hasStatusFilter) {
        inventoryTable.deselectRow();
        inventoryTable.clearFilter(true);
        inventoryTable.setSort('created_at', 'desc');
        updateSummary(inventoryTable.getData() || []);
        return;
    }

    inventoryTable.deselectRow();
    inventoryTable.setFilter(function(data) {
        if (hasStatusFilter) {
            const status = parseInt(data.status, 10) || 0;
            if (status !== 2 && status !== 3) return false;
        }

        if (search) {
            const hay = [
                data.inventory_system_item_code,
                data.item_description,
                data.item_category_code,
                data.item_category_name,
                data.unit,
                data.source_of_funds
            ].join(' ').toLowerCase();

            if (hay.indexOf(search) === -1) return false;
        }

        if (fromDate || toDate) {
            const basisDate = data.created_at || data.last_updated || data.acquisition_date || '';
            const d = parseDate(basisDate);
            if (!d) return false;
            if (fromDate && d < fromDate) return false;
            if (toDate && d > toDate) return false;
        }

        return true;
    });

    inventoryTable.setSort('created_at', 'desc');
    updateSummary(inventoryTable.getData('active') || []);
}

function initAvailabilitySelect() {
    const options = [{ id: NONE_STATUS_VALUE, text: 'None' }].concat(
        (employmentStatuses || []).map(row => ({
            id: String(row.employment_status_id),
            text: row.status_name
        }))
    );

    $('#availTeachingStatus').empty();
    $('#availNonTeachingStatus').empty();

    options.forEach(opt => {
        $('#availTeachingStatus').append(new Option(opt.text, opt.id, false, false));
        $('#availNonTeachingStatus').append(new Option(opt.text, opt.id, false, false));
    });

    if ($('#availTeachingStatus').data('select2')) {
        $('#availTeachingStatus').off('change').select2('destroy');
    }
    if ($('#availNonTeachingStatus').data('select2')) {
        $('#availNonTeachingStatus').off('change').select2('destroy');
    }

    $('#availTeachingStatus').select2({
        dropdownParent: $('#availabilityModal'),
        placeholder: 'Select allowed teaching status',
        allowClear: false,
        width: '100%'
    });

    $('#availNonTeachingStatus').select2({
        dropdownParent: $('#availabilityModal'),
        placeholder: 'Select allowed non-teaching status',
        allowClear: false,
        width: '100%'
    });

    $('#availTeachingStatus').on('change', function() {
        if (availStatusLock) return;
        let vals = $(this).val() || [];
        if (!Array.isArray(vals)) vals = [vals];

        if (vals.includes(NONE_STATUS_VALUE) && vals.length > 1) {
            availStatusLock = true;
            $(this).val(vals.filter(v => v !== NONE_STATUS_VALUE)).trigger('change.select2');
            availStatusLock = false;
            return;
        }

        if (vals.length === 0) {
            availStatusLock = true;
            $(this).val([NONE_STATUS_VALUE]).trigger('change.select2');
            availStatusLock = false;
        }
    });

    $('#availNonTeachingStatus').on('change', function() {
        if (availStatusLock) return;
        let vals = $(this).val() || [];
        if (!Array.isArray(vals)) vals = [vals];

        if (vals.includes(NONE_STATUS_VALUE) && vals.length > 1) {
            availStatusLock = true;
            $(this).val(vals.filter(v => v !== NONE_STATUS_VALUE)).trigger('change.select2');
            availStatusLock = false;
            return;
        }

        if (vals.length === 0) {
            availStatusLock = true;
            $(this).val([NONE_STATUS_VALUE]).trigger('change.select2');
            availStatusLock = false;
        }
    });

    availStatusLock = true;
    $('#availTeachingStatus').val([NONE_STATUS_VALUE]).trigger('change.select2');
    $('#availNonTeachingStatus').val([NONE_STATUS_VALUE]).trigger('change.select2');
    availStatusLock = false;
}

function loadEmploymentStatuses() {
    $.post(PROCESS_URL, { action: 'list_employment_status' }, function(res) {
        if (res && res.success) {
            employmentStatuses = res.data || [];
            initAvailabilitySelect();
        }
    }, 'json');
}

function setAvailabilitySelectValues(teachVals, nonVals, mode) {
    const allIds = (employmentStatuses || []).map(row => String(row.employment_status_id));

    let teaching = [];
    let nonTeaching = [];

    if (mode === 'none') {
        teaching = [NONE_STATUS_VALUE];
        nonTeaching = [NONE_STATUS_VALUE];
    } else if (mode === 'all') {
        teaching = allIds;
        nonTeaching = allIds;
    } else {
        teaching = Array.isArray(teachVals) && teachVals.length ? teachVals.map(String) : [NONE_STATUS_VALUE];
        nonTeaching = Array.isArray(nonVals) && nonVals.length ? nonVals.map(String) : [NONE_STATUS_VALUE];
    }

    availStatusLock = true;
    $('#availTeachingStatus').val(teaching).trigger('change.select2');
    $('#availNonTeachingStatus').val(nonTeaching).trigger('change.select2');
    availStatusLock = false;
}

function normalizeStatusIdList(list) {
    return (Array.isArray(list) ? list : [])
        .map(v => parseInt(v, 10))
        .filter(v => !isNaN(v) && v > 0)
        .sort((a, b) => a - b);
}

function haveSameRuleSet(a, b) {
    const modeA = String((a && a.mode) || 'all');
    const modeB = String((b && b.mode) || 'all');
    if (modeA !== modeB) return false;

    const teachA = normalizeStatusIdList(a && a.teaching);
    const teachB = normalizeStatusIdList(b && b.teaching);
    const nonA = normalizeStatusIdList(a && a.non_teaching);
    const nonB = normalizeStatusIdList(b && b.non_teaching);

    return JSON.stringify(teachA) === JSON.stringify(teachB)
        && JSON.stringify(nonA) === JSON.stringify(nonB);
}

function getBulkRulePreview(rows) {
    const list = Array.isArray(rows) ? rows : [];
    if (!list.length) {
        return { mode: 'none', teaching: [], non_teaching: [] };
    }

    const first = list[0] && list[0].allowed_employment_status
        ? list[0].allowed_employment_status
        : { mode: 'all', teaching: [], non_teaching: [] };

    const allMatch = list.every(function(row) {
        const current = row && row.allowed_employment_status
            ? row.allowed_employment_status
            : { mode: 'all', teaching: [], non_teaching: [] };
        return haveSameRuleSet(first, current);
    });

    if (!allMatch) {
        return { mode: 'none', teaching: [], non_teaching: [] };
    }

    return {
        mode: first.mode || 'all',
        teaching: normalizeStatusIdList(first.teaching),
        non_teaching: normalizeStatusIdList(first.non_teaching)
    };
}

function openAvailabilityModal(inventoryId) {
    isBulkAvailMode = false;
    bulkAvailabilityRows = [];
    $('#availabilityModal').removeClass('is-bulk-mode');
    $('#availMsg').html('');
    $('#availInventoryId').val(inventoryId);
    $('#availBulkIds').val('');
    $('#availBulkNote').addClass('d-none').text('');
    $('#availQtyWrap').show();
    $('#availQtyWrap').removeClass('d-none').addClass('col-12 col-lg-5');
    $('#availRulesWrap').removeClass('col-12').addClass('col-12 col-lg-7');
    $('#availTeachWrap').removeClass('col-md-6 col-md-8 offset-md-4').addClass('col-12');
    $('#availNonTeachWrap').removeClass('col-md-6 col-md-8 offset-md-4 offset-md-0').addClass('col-12');

    $('#availItemLabel').text('Loading...');
    $('#availStatusLabel').html('-');
    $('#availCurrentQty').val('');
    $('#availCurrentQty').attr('max', '');
    $('#availBulkCurrentQty').val('');
    $('#availBulkControls').addClass('d-none');
    $('#availCurrentQty').removeClass('d-none');
    $('#availQtyHint').removeClass('d-none');
    $('#availBulkQtyModeAll').prop('checked', true);
    syncBulkQtyModeUI();
    renderBulkAvailabilityRows([]);
    updateBulkQtySharedState([]);
    $('#availTotalQtyValue').text('-');
    $('#availTotalQtyUnit').text('Unit: -');
    $('#availCriticalLevelValue').text('-');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: { action: 'get_availability_settings', inventory_id: inventoryId },
        success: function(res) {
            if (!res || !res.success) {
                showAvailMessage('danger', (res && res.message) || 'Failed to load settings.');
                return;
            }

            const d = res.data || {};
            const rules = d.allowed_employment_status || {};
            const label = `${d.inventory_system_item_code || ''} - ${String(d.item_description || '').slice(0, 100)}`;

            $('#availItemLabel').text(label);
            $('#availStatusLabel').html(statusLabel(d.status || 0));
            $('#availCurrentQty').val(d.current_quantity || 0);
            $('#availCurrentQty').attr('max', parseInt(d.quantity, 10) || 0);
            $('#availTotalQtyValue').text(formatMetricNumber(d.quantity || 0));
            $('#availTotalQtyUnit').text(`Unit: ${d.unit || '-'}`);
            $('#availCriticalLevelValue').text(formatMetricNumber(d.qty_crit_level || 0));

            setAvailabilitySelectValues(
                rules.teaching || [],
                rules.non_teaching || [],
                rules.mode || 'all'
            );

            $('#availabilityModal').modal('show');
        },
        error: function(xhr) {
            let msg = 'Server error while loading settings.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            showAvailMessage('danger', msg);
            $('#availabilityModal').modal('show');
        }
    });
}

function openAvailabilityModalBulk(ids) {
    if (!ids || !ids.length) return;

    isBulkAvailMode = true;
    $('#availabilityModal').addClass('is-bulk-mode');
    bulkAvailabilityRows = (inventoryCache || []).filter(function(row) {
        return ids.includes(parseInt(row.inventory_id, 10));
    });
    $('#availMsg').html('');
    $('#availInventoryId').val('');
    $('#availBulkIds').val(ids.join(','));
    $('#availBulkNote').removeClass('d-none').text(`${ids.length} item(s) selected`);
    $('#availQtyWrap').show();
    $('#availQtyWrap').removeClass('d-none').addClass('col-12 col-lg-5');
    $('#availRulesWrap').removeClass('col-12').addClass('col-12 col-lg-7');
    $('#availTeachWrap').removeClass('col-md-6 col-md-8 offset-md-4').addClass('col-12');
    $('#availNonTeachWrap').removeClass('col-md-6 col-md-8 offset-md-4 offset-md-0').addClass('col-12');

    $('#availItemLabel').text('Bulk rule update');
    $('#availStatusLabel').html('<span class="status-pill status-0">Multiple Items</span>');
    $('#availCurrentQty').val('');
    $('#availCurrentQty').attr('max', '');
    $('#availBulkCurrentQty').val('');
    $('#availCurrentQty').addClass('d-none');
    $('#availQtyHint').removeClass('d-none');
    $('#availBulkControls').removeClass('d-none');
    $('#availBulkQtyModeAll').prop('checked', true);
    syncBulkQtyModeUI();
    $('#availTotalQtyValue').text('-');
    $('#availTotalQtyUnit').text('Unit: -');
    $('#availCriticalLevelValue').text('-');
    renderBulkAvailabilityRows(bulkAvailabilityRows);
    updateBulkQtySharedState(bulkAvailabilityRows);

    if (bulkAvailabilityRows.length) {
        const totalQty = bulkAvailabilityRows.reduce((sum, row) => sum + (parseInt(row.quantity, 10) || 0), 0);
        const totalCrit = bulkAvailabilityRows.reduce((sum, row) => sum + (parseInt(row.qty_crit_level, 10) || 0), 0);
        const units = Array.from(new Set(bulkAvailabilityRows.map(row => String(row.unit || '-').trim()).filter(Boolean)));
        $('#availTotalQtyValue').text(formatMetricNumber(totalQty));
        $('#availTotalQtyUnit').text(`Units: ${units.join(', ') || '-'}`);
        $('#availCriticalLevelValue').text(formatMetricNumber(totalCrit));
    }

    const bulkRulePreview = getBulkRulePreview(bulkAvailabilityRows);
    setAvailabilitySelectValues(
        bulkRulePreview.teaching,
        bulkRulePreview.non_teaching,
        bulkRulePreview.mode
    );

    $('#availabilityModal').modal('show');
}

function getSelectedRulesPayload() {
    let teaching = $('#availTeachingStatus').val() || [];
    let nonTeaching = $('#availNonTeachingStatus').val() || [];

    if (!Array.isArray(teaching)) teaching = [teaching];
    if (!Array.isArray(nonTeaching)) nonTeaching = [nonTeaching];

    teaching = teaching.filter(v => String(v) !== NONE_STATUS_VALUE).map(v => parseInt(v, 10)).filter(v => !isNaN(v) && v > 0);
    nonTeaching = nonTeaching.filter(v => String(v) !== NONE_STATUS_VALUE).map(v => parseInt(v, 10)).filter(v => !isNaN(v) && v > 0);

    if (teaching.length === 0 && nonTeaching.length === 0) {
        return { none: true };
    }

    return {
        teaching: teaching,
        non_teaching: nonTeaching
    };
}

function initTable() {
    inventoryTable = new Tabulator('#csm-inventory-table', {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: 'list_recent_added' },
        ajaxConfig: 'POST',
        layout: 'fitColumns',
        renderVertical: 'basic',
        responsiveLayout: 'collapse',
        resizableColumns: true,
        pagination: 'local',
        paginationSize: 20,
        paginationSizeSelector: [20, 100, 500, 1000, true],
        paginationCounter: 'rows',
        selectable: true,
        placeholder: 'No inventory records found',
        initialSort: [
            { column: 'created_at', dir: 'desc' }
        ],
        ajaxResponse: function(url, params, response) {
            const data = response && response.data ? response.data : [];
            inventoryCache = data;
            updateSummary(data);
            showInvMessage('');
            return data;
        },
        ajaxError: function(xhr, textStatus, errorThrown) {
            showInvMessage('Server error while loading inventory.');
            console.error(xhr && xhr.responseText ? xhr.responseText : errorThrown);
        },
        groupBy: 'item_category_name',
        groupHeader: function(value, count, data) {
            const qty = data.reduce((sum, r) => sum + (parseInt(r.quantity, 10) || 0), 0);
            const avail = data.reduce((sum, r) => sum + (parseInt(r.current_quantity, 10) || 0), 0);
            const name = value || 'Uncategorized';
            return `${escHtml(name)} <span class="text-muted small">(${count} items, Qty ${qty}, Available ${avail})</span>`;
        },
        columns: [
            {
                formatter: 'rowSelection',
                titleFormatter: 'rowSelection',
                titleFormatterParams: {
                    rowRange: 'active'
                },
                hozAlign: 'center',
                headerSort: false,
                width: 40
            },
            {
                title: 'Image',
                field: 'display_image',
                width: 70,
                hozAlign: 'center',
                headerSort: false,
                formatter: function(cell) {
                    const d = cell.getRow().getData();
                    const rel = d.display_image || '';
                    const url = rel ? absUrl(rel) : '';

                    if (url) {
                        return `
                            <div class="inv-thumb-click js-thumb-preview" data-full="${escHtml(url)}" title="Click to view">
                                <div class="inv-thumb-wrap">
                                    <img src="${escHtml(url)}" alt="img"
                                         onerror="this.remove(); this.parentNode.innerHTML='<i class=&quot;bi bi-image inv-thumb-fallback&quot;></i>';">
                                </div>
                            </div>
                        `;
                    }

                    return `<div class="inv-thumb-wrap"><i class="bi bi-image inv-thumb-fallback"></i></div>`;
                }
            },
            {
                title: 'Item Code',
                field: 'inventory_system_item_code',
                width: 190,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...',
                formatter: function(cell) {
                    return twoLineText(cell.getValue());
                }
            },
            {
                title: 'Description',
                field: 'item_description',
                widthGrow: 2,
                minWidth: 240,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...',
                formatter: function(cell) {
                    return threeLineText(cell.getValue());
                }
            },
            {
                title: 'Category',
                field: 'item_category_name',
                width: 220,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...',
                formatter: function(cell) {
                    const row = cell.getRow().getData();
                    const code = row.item_category_code || '';
                    const name = row.item_category_name || '';
                    return twoLineText((code ? code + ' - ' : '') + name);
                }
            },
            {
                title: 'Qty / Available / Unit',
                field: 'quantity',
                width: 160,
                hozAlign: 'center',
                formatter: function(cell) {
                    const row = cell.getRow().getData();
                    const qty = parseInt(row.quantity, 10) || 0;
                    const avail = parseInt(row.current_quantity, 10) || 0;
                    const unit = row.unit ? String(row.unit) : '';
                    return `<div class="text-center">
                        <div><strong>${qty}</strong> / ${avail}</div>
                        <div class="small text-muted">${escHtml(unit || '-')}</div>
                    </div>`;
                }
            },
            {
                title: 'Critical',
                field: 'qty_crit_level',
                width: 95,
                hozAlign: 'center'
            },
            {
                title: 'Cost',
                field: 'cost_value',
                width: 110,
                hozAlign: 'right',
                formatter: function(cell) {
                    const v = cell.getValue();
                    return v !== null && v !== '' ? parseFloat(v).toFixed(2) : '0.00';
                }
            },
            {
                title: 'Source',
                field: 'source_of_funds',
                width: 140,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...'
            },
            {
                title: 'Allowed Status',
                field: 'allowed_status_names',
                width: 220,
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filter...',
                formatter: function(cell) {
                    const v = cell.getValue();
                    if (!v || v === 'None') return '<span class="text-muted small">None</span>';
                    if (v === 'All') return '<span class="text-success small fw-semibold">All</span>';
                    return `<span class="three-line-cell text-muted small" title="${escHtml(v)}">${escHtml(v)}</span>`;
                }
            },
            {
                title: 'Status',
                field: 'status',
                width: 130,
                hozAlign: 'center',
                formatter: function(cell) {
                    return statusLabel(cell.getValue());
                }
            },
            {
                title: 'Date Added',
                field: 'created_at',
                width: 170,
                sorter: 'datetime',
                sorterParams: { format: 'yyyy-MM-dd HH:mm:ss' },
                formatter: function(cell) {
                    const value = cell.getValue();
                    if (!value) return '-';
                    const d = new Date(String(value).replace(' ', 'T'));
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
                title: 'Actions',
                field: 'inventory_id',
                width: 120,
                hozAlign: 'center',
                headerSort: false,
                formatter: function(cell) {
                    const id = cell.getValue();
                    return `<button type="button" class="btn btn-outline-primary btn-sm js-set-availability" data-id="${id}">
                        Rules
                    </button>`;
                }
            }
        ]
    });
}

function refreshTable() {
    if (!inventoryTable) return;

    inventoryTable.setData(PROCESS_URL, { action: 'list_recent_added' }, 'POST').then(function() {
        applyInventoryFilters();
    }).catch(function(err) {
        showInvMessage('Server error while loading inventory.');
        console.error(err);
    });
}

$(document).ready(function() {
    observeFloatingTargets(['#invMsg', '#availMsg', '#searchScanError']);
    initTable();
    loadEmploymentStatuses();

    $('#invSearch').on('keyup', function() {
        refreshTable();
    });

    $('#dateRange').on('change keyup', function() {
        refreshTable();
    });

    $('#criticalOutCard').on('click', function() {
        statusSummaryFilter = (statusSummaryFilter === 'critical_out') ? '' : 'critical_out';
        applyInventoryFilters();
    });

    if (typeof $.fn.daterangepicker === 'function') {
        $('#dateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear'
            },
            opens: 'left'
        });

        $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
            const value = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
            $(this).val(value);
            refreshTable();
        });

        $('#dateRange').on('cancel.daterangepicker', function() {
            $(this).val('');
            refreshTable();
        });
    }

    $('#bulkSetAvailability').on('click', function() {
        if (!inventoryTable) return;

        const rows = inventoryTable.getSelectedData() || [];
        const ids = rows
            .map(r => parseInt(r.inventory_id, 10))
            .filter(v => !isNaN(v) && v > 0);

        if (!ids.length) {
            showInvMessage('Please select at least one item first.');
            return;
        }

        openAvailabilityModalBulk(ids);
    });

    $('#clearSelection').on('click', function() {
        if (!inventoryTable) return;
        inventoryTable.deselectRow();
    });

    $('#availabilityForm').on('submit', function(e) {
        e.preventDefault();
        $('#availMsg').html('');

        const rulesPayload = getSelectedRulesPayload();
        const bulkIds = ($('#availBulkIds').val() || '').trim();

        if (bulkIds) {
            const bulkQtyPayload = getBulkCurrentQtyPayload();
            if (!bulkQtyPayload.ok) {
                showAvailMessage('danger', bulkQtyPayload.message || 'Invalid bulk quantity setup.');
                return;
            }

            const requestData = {
                action: 'update_availability_settings_bulk',
                inventory_ids: bulkIds,
                allowed_status: JSON.stringify(rulesPayload),
                bulk_qty_mode: bulkQtyPayload.mode
            };

            if (bulkQtyPayload.mode === 'same_for_all') {
                requestData.current_quantity = bulkQtyPayload.current_quantity;
            } else {
                requestData.current_quantities_json = bulkQtyPayload.current_quantities_json;
            }

            $.ajax({
                url: PROCESS_URL,
                type: 'POST',
                dataType: 'json',
                data: requestData,
                success: function(res) {
                    if (res && res.success) {
                        showAvailMessage('success', res.message || 'Bulk rules updated.');
                        refreshTable();
                        if (inventoryTable) inventoryTable.deselectRow();
                        setTimeout(function() {
                            $('#availabilityModal').modal('hide');
                        }, 700);
                    } else {
                        showAvailMessage('danger', (res && res.message) || 'Bulk save failed.');
                    }
                },
                error: function(xhr) {
                    let msg = 'Server error while saving bulk rules.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.responseText) msg = xhr.responseText;
                    showAvailMessage('danger', msg);
                }
            });
            return;
        }

        const inventoryId = ($('#availInventoryId').val() || '').trim();
        const currentQty = parseInt($('#availCurrentQty').val() || '0', 10);

        if (!inventoryId) {
            showAvailMessage('danger', 'Invalid inventory record.');
            return;
        }

        if (isNaN(currentQty) || currentQty < 0) {
            showAvailMessage('danger', 'Available quantity cannot be negative.');
            return;
        }

        $.ajax({
            url: PROCESS_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_availability_settings',
                inventory_id: inventoryId,
                current_quantity: currentQty,
                allowed_status: JSON.stringify(rulesPayload)
            },
            success: function(res) {
                if (res && res.success) {
                    showAvailMessage('success', res.message || 'Availability rules updated.');
                    refreshTable();
                    setTimeout(function() {
                        $('#availabilityModal').modal('hide');
                    }, 600);
                } else {
                    showAvailMessage('danger', (res && res.message) || 'Save failed.');
                }
            },
            error: function(xhr) {
                let msg = 'Server error while saving.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                else if (xhr.responseText) msg = xhr.responseText;
                showAvailMessage('danger', msg);
            }
        });
    });

    $('input[name="avail_bulk_qty_mode"]').on('change', function() {
        syncBulkQtyModeUI();
    });

    $('#availCurrentQty, #availBulkCurrentQty').on('input change', function() {
        clampQtyInputToMax(this);
    });

    $('#availabilityModal').on('input change', '.avail-bulk-item-qty', function() {
        clampQtyInputToMax(this);
    });

    $('#availUseLowestQty').on('click', function() {
        $('#availBulkCurrentQty').val(bulkLowestActualQty > 0 ? bulkLowestActualQty : 0);
    });

    $('#availabilityModal').on('hidden.bs.modal', function() {
        isBulkAvailMode = false;
        bulkAvailabilityRows = [];
        bulkLowestActualQty = 0;
        $('#availabilityModal').removeClass('is-bulk-mode');
        $('#availBulkIds').val('');
        $('#availBulkNote').addClass('d-none').text('');
        $('#availCurrentQty').removeClass('d-none').val('');
        $('#availBulkCurrentQty').val('');
        $('#availBulkControls').addClass('d-none');
        $('#availQtyHint').removeClass('d-none');
        $('#availBulkQtyModeAll').prop('checked', true);
        syncBulkQtyModeUI();
        renderBulkAvailabilityRows([]);
        updateBulkQtySharedState([]);
    });

    $('#exportCsv').on('click', function() {
        window.location.href = BULK_PROCESS_URL + '?action=export_csv';
    });

    $('#exportExcel').on('click', function() {
        if (!inventoryTable) {
            showInvMessage('Inventory table is not ready.');
            return;
        }

        const data = inventoryTable.getData();
        if (!data || data.length === 0) {
            showInvMessage('No data to export.');
            return;
        }

        if (typeof XLSX === 'undefined') {
            showInvMessage('Excel export library not available.');
            return;
        }

        const rows = data.map(r => ({
            "Item Code": r.inventory_system_item_code || '',
            "Description": r.item_description || '',
            "Category Code": r.item_category_code || '',
            "Category Name": r.item_category_name || '',
            "Unit": r.unit || '',
            "Quantity": r.quantity ?? '',
            "Available Quantity": r.current_quantity ?? '',
            "Critical Level": r.qty_crit_level ?? '',
            "Cost Value": (r.cost_value !== null && r.cost_value !== undefined && r.cost_value !== '') ? parseFloat(r.cost_value).toFixed(2) : '',
            "Allowed Status": r.allowed_status_names || '',
            "Source of Funds": r.source_of_funds || '',
            "Status": r.status ?? '',
            "Acquisition Date": r.acquisition_date || '',
            "Last Updated": r.last_updated || '',
            "Date Added": r.created_at || ''
        }));

        const ws = XLSX.utils.json_to_sheet(rows);
        ws['!cols'] = [
            { wch: 20 },
            { wch: 40 },
            { wch: 14 },
            { wch: 24 },
            { wch: 10 },
            { wch: 10 },
            { wch: 16 },
            { wch: 14 },
            { wch: 12 },
            { wch: 30 },
            { wch: 20 },
            { wch: 10 },
            { wch: 14 },
            { wch: 14 },
            { wch: 18 }
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'CSM Inventory');
        XLSX.writeFile(wb, 'csm_inventory.xlsx');
    });

    $('#csm-inventory-table').on('click', '.js-set-availability', function() {
        const id = $(this).data('id');
        openAvailabilityModal(id);
    });

    $('#csm-inventory-table').on('click', '.js-thumb-preview', function() {
        const full = $(this).data('full') || '';
        if (!full) return;
        $('#imagePreviewImg').attr('src', full);
        $('#imagePreviewModal').modal('show');
    });

    if (typeof initQrSearch === 'function') {
        initQrSearch({
            modalId: '#searchQrModal',
            openButton: '#openSearchScanner',
            searchInput: '#invSearch',
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
                $('#invSearch').val(decodedText);
                refreshTable();
            }
        });
    }
});
</script>
</body>
</html>

