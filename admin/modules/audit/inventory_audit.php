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
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        .audit-hero {
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.16), transparent 36%),
                radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.16), transparent 30%),
                linear-gradient(135deg, #eff6ff 0%, #f8fafc 55%, #ecfeff 100%);
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 20px;
            padding: 1.25rem;
        }
        .audit-panel {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .audit-panel .card-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f766e 100%);
            color: #fff;
            border-bottom: 0;
            padding: 1rem 1.25rem;
            font-weight: 700;
        }
        .audit-helper-links {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .audit-helper-links a {
            text-decoration: none;
        }
        .audit-item-thumb-shell {
            min-height: 250px;
            border-radius: 18px;
            border: 1px dashed #cbd5e1;
            background: linear-gradient(180deg, rgba(241, 245, 249, 0.95), rgba(248, 250, 252, 0.95));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
        }
        .audit-item-thumb-shell img {
            max-width: 100%;
            max-height: 240px;
            object-fit: contain;
            border-radius: 14px;
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.14);
        }
        .audit-empty-thumb {
            color: #64748b;
            font-size: .95rem;
            line-height: 1.5;
            max-width: 260px;
        }
        .audit-info-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: .9rem 1rem;
            background: #fff;
            height: 100%;
        }
        .audit-label {
            font-size: .74rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .35rem;
            font-weight: 700;
        }
        .audit-info-value {
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }
        .audit-module-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .4rem .8rem;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 700;
            font-size: .85rem;
        }
        .audit-status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .78rem;
            font-weight: 700;
        }
        .audit-status-available {
            background: #dcfce7;
            color: #166534;
        }
        .audit-status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .audit-status-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .audit-status-neutral {
            background: #e2e8f0;
            color: #334155;
        }
        .qr-modal-preview {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            position: relative;
            background: #000;
            border-radius: 14px;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            min-height: 320px;
        }
        .qr-modal-preview > div {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        .qr-modal-preview video,
        .qr-modal-preview canvas,
        #auditPreview video,
        #auditPreview canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            background: #000 !important;
        }
        #auditPreview > div {
            width: 100% !important;
            height: 100% !important;
        }
        .scanner-loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            z-index: 10;
            font-size: .9rem;
            text-align: center;
        }
        @media (max-width: 576px) {
            .qr-modal-preview {
                max-width: 260px;
                min-height: 260px;
            }
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
            <h1 class="h4 fw-semibold mb-1">Inventory Audit</h1>
            <p class="text-muted small mb-0">Scan a QR code and check if a specific CSM or AST item exists.</p>
        </div>

        <section class="section">
            <div class="audit-hero mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-8">
                        <div class="audit-label">Auto Detect</div>
                        <h2 class="h4 fw-bold mb-2 text-dark">QR checker for specific inventory items</h2>
                        <p class="text-muted mb-0">Scan the QR code and the page will automatically identify whether the item belongs to CSM or AST, then show the matching details.</p>
                    </div>
                    <div class="col-lg-4">
                        <span class="audit-module-chip" id="activeModuleChip"><i class="bi bi-upc-scan"></i> Waiting for QR scan</span>
                        <div class="audit-helper-links mt-3">
                            <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>admin/modules/consumable/csm_qrcode.php">
                                <i class="bi bi-qr-code"></i> CSM QR labels
                            </a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>admin/modules/nonconsumable/ast_qrcode.php">
                                <i class="bi bi-qr-code"></i> AST QR labels
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card audit-panel">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <span><i class="bi bi-qr-code-scan"></i>&ensp;Scan Specific Item</span>
                        <span class="small">Use QR first, or type the code manually if needed.</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label class="form-label fw-semibold" id="codeInputLabel">QR Code / Item Code / Property Tag</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="auditCodeInput" placeholder="Scan or enter the code">
                                <button type="button" class="btn btn-outline-secondary" id="openAuditScanner" title="Scan QR Code">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-2 d-grid">
                            <button type="button" class="btn btn-outline-primary" id="loadItemBtn">
                                <i class="bi bi-search"></i> Check
                            </button>
                        </div>
                        <div class="col-lg-2 d-grid">
                            <button type="button" class="btn btn-outline-secondary" id="resetAuditFormBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div id="auditMsg" class="mt-3"></div>

                    <div class="row g-4 mt-1">
                        <div class="col-lg-4">
                            <div class="audit-item-thumb-shell">
                                <img id="itemPreviewImage" src="" alt="Item Preview" class="d-none">
                                <div id="itemPreviewEmpty" class="audit-empty-thumb">
                                    Scan a QR code to check whether the item exists and view its details here.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div id="detailsCsm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Inventory Code</div>
                                            <div class="audit-info-value" id="csmInfoCode">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Category Code</div>
                                            <div class="audit-info-value" id="csmInfoCategoryCode">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Category Name</div>
                                            <div class="audit-info-value" id="csmInfoCategoryName">-</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Item Description</div>
                                            <div class="audit-info-value" id="csmInfoDescription">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Acquisition Date</div>
                                            <div class="audit-info-value" id="csmInfoAcquired">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Current Qty</div>
                                            <div class="audit-info-value" id="csmInfoCurrentQty">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Critical Level</div>
                                            <div class="audit-info-value" id="csmInfoCritical">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">System Status</div>
                                            <div class="audit-info-value" id="csmInfoStatus">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Source of Funds</div>
                                            <div class="audit-info-value" id="csmInfoSource">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Cost Value</div>
                                            <div class="audit-info-value" id="csmInfoCost">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="detailsAst" class="d-none">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Property Tag</div>
                                            <div class="audit-info-value" id="astInfoCode">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Property Number</div>
                                            <div class="audit-info-value" id="astInfoNumber">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Unit</div>
                                            <div class="audit-info-value" id="astInfoUnit">-</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Item Description</div>
                                            <div class="audit-info-value" id="astInfoDescription">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Serial Number</div>
                                            <div class="audit-info-value" id="astInfoSerial">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Date Stock</div>
                                            <div class="audit-info-value" id="astInfoStockDate">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Date Issued</div>
                                            <div class="audit-info-value" id="astInfoIssuedDate">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Assignment Status</div>
                                            <div class="audit-info-value" id="astInfoStatus">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Facility</div>
                                            <div class="audit-info-value" id="astInfoFacility">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Assignment Source</div>
                                            <div class="audit-info-value" id="astInfoAssignmentSource">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Accountable</div>
                                            <div class="audit-info-value" id="astInfoAccountable">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Issued To</div>
                                            <div class="audit-info-value" id="astInfoIssuedTo">-</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="audit-info-card">
                                            <div class="audit-label">Managed By</div>
                                            <div class="audit-info-value" id="astInfoManagedBy">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="auditQrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code-scan"></i>&ensp;Scan QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <select id="auditCameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
                            <option value="">Loading cameras...</option>
                        </select>
                        <button type="button" id="auditBtnStart" class="btn btn-success btn-sm">Start</button>
                        <button type="button" id="auditBtnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
                    </div>
                    <div class="qr-modal-preview">
                        <div id="auditPreview"></div>
                        <div class="scanner-loading" id="auditScannerLoading">
                            <div>Initializing camera...</div>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <span class="text-muted">Last scanned:</span>
                        <span id="auditLastScanned" class="fw-semibold">-</span>
                    </div>
                    <div id="auditScanError" class="text-danger small mt-2" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once FOOTER_PATH; ?>
    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="<?= BASE_URL ?>assets/js/qr_search.js"></script>
    <script>
        const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
        const MODULES = {
            CSM: {
                label: 'Consumable (CSM)',
                chipText: 'Detected: CSM item',
                processUrl: BASE_URL + 'admin/modules/consumable/process/csm_physical_checking_process.php',
                codeParam: 'item_code',
                codeKey: 'inventory_system_item_code'
            },
            AST: {
                label: 'Non-Consumable (AST)',
                chipText: 'Detected: AST item',
                processUrl: BASE_URL + 'admin/modules/nonconsumable/process/ast_physical_checking_process.php',
                codeParam: 'property_code',
                codeKey: 'property_code'
            }
        };

        let currentItem = null;
        let currentModule = '';

        function escapeHtml(value) {
            return String(value === null || value === undefined ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function showMessage(target, type, text) {
            $(target).html('<div class="alert alert-' + type + ' mb-0">' + escapeHtml(text) + '</div>');
        }

        function clearMessage(target) {
            $(target).html('');
        }

        function money(value) {
            const num = Number(value || 0);
            if (!isFinite(num)) {
                return '-';
            }
            return 'PHP ' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function statusBadge(text) {
            const raw = String(text || '').trim();
            if (!raw) {
                return '<span class="audit-status-badge audit-status-neutral">-</span>';
            }
            const lower = raw.toLowerCase();
            let cls = 'audit-status-neutral';
            if (lower.indexOf('available') !== -1 || lower.indexOf('good') !== -1 || lower.indexOf('active') !== -1 || lower.indexOf('in stock') !== -1 || lower.indexOf('in_stock') !== -1) {
                cls = 'audit-status-available';
            } else if (lower.indexOf('critical') !== -1 || lower.indexOf('pending') !== -1 || lower.indexOf('repair') !== -1) {
                cls = 'audit-status-warning';
            } else if (lower.indexOf('out') !== -1 || lower.indexOf('missing') !== -1 || lower.indexOf('damaged') !== -1 || lower.indexOf('expired') !== -1 || lower.indexOf('unassigned') !== -1) {
                cls = 'audit-status-danger';
            }
            return '<span class="audit-status-badge ' + cls + '">' + escapeHtml(raw) + '</span>';
        }

        function setItemPreview(src) {
            if (src) {
                $('#itemPreviewImage').attr('src', src).removeClass('d-none');
                $('#itemPreviewEmpty').addClass('d-none');
                return;
            }
            $('#itemPreviewImage').attr('src', '').addClass('d-none');
            $('#itemPreviewEmpty').removeClass('d-none');
        }

        function resetCsmDetails() {
            $('#csmInfoCode, #csmInfoCategoryCode, #csmInfoCategoryName, #csmInfoDescription, #csmInfoAcquired, #csmInfoCurrentQty, #csmInfoCritical, #csmInfoSource, #csmInfoCost').text('-');
            $('#csmInfoStatus').html('-');
        }

        function resetAstDetails() {
            $('#astInfoCode, #astInfoNumber, #astInfoUnit, #astInfoDescription, #astInfoSerial, #astInfoStockDate, #astInfoIssuedDate, #astInfoFacility, #astInfoAssignmentSource, #astInfoAccountable, #astInfoIssuedTo, #astInfoManagedBy').text('-');
            $('#astInfoStatus').html('-');
        }

        function resetLoadedItem() {
            currentItem = null;
            currentModule = '';
            setItemPreview('');
            resetCsmDetails();
            resetAstDetails();
            $('#detailsCsm').removeClass('d-none');
            $('#detailsAst').addClass('d-none');
            $('#activeModuleChip').html('<i class="bi bi-upc-scan"></i> Waiting for QR scan');
        }

        function detectModuleFromCode(code) {
            const clean = String(code || '').trim().toUpperCase();
            if (/^CSM[-_]/.test(clean)) {
                return 'CSM';
            }
            if (/^AST[-_]/.test(clean)) {
                return 'AST';
            }
            return '';
        }

        function showDetectedModule(moduleKey) {
            currentModule = moduleKey;
            const cfg = MODULES[moduleKey];
            $('#activeModuleChip').html('<i class="bi bi-upc-scan"></i> ' + escapeHtml(cfg.chipText));
            $('#detailsCsm').toggleClass('d-none', moduleKey !== 'CSM');
            $('#detailsAst').toggleClass('d-none', moduleKey !== 'AST');
        }

        function requestItem(moduleKey, code) {
            const cfg = MODULES[moduleKey];
            const payload = { action: 'get_item_by_code' };
            payload[cfg.codeParam] = code;

            return new Promise(function(resolve, reject) {
                $.post(cfg.processUrl, payload, function(res) {
                    if (res && res.success && res.data) {
                        resolve({ moduleKey: moduleKey, data: res.data });
                        return;
                    }
                    reject(new Error((res && res.message) || 'Item not found.'));
                }, 'json').fail(function(xhr) {
                    const msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Item not found.';
                    reject(new Error(msg));
                });
            });
        }

        function renderCsmItem(item) {
            $('#csmInfoCode').text(item.inventory_system_item_code || '-');
            $('#csmInfoCategoryCode').text(item.item_category_code || '-');
            $('#csmInfoCategoryName').text(item.item_category_name || '-');
            $('#csmInfoDescription').text(item.item_description || '-');
            $('#csmInfoAcquired').text(item.acquisition_date || '-');
            $('#csmInfoCurrentQty').text(item.current_quantity ?? '-');
            $('#csmInfoCritical').text(item.qty_crit_level ?? '-');
            $('#csmInfoSource').text(item.source_of_funds || '-');
            $('#csmInfoCost').text(item.cost_value !== undefined && item.cost_value !== null && item.cost_value !== '' ? money(item.cost_value) : '-');
            $('#csmInfoStatus').html(statusBadge(item.system_stock_label || 'Available'));
            setItemPreview(item.item_image_url || item.item_image_thumb_url || '');
        }

        function renderAstItem(item) {
            const issuedAt = String(item.assignment_issued_at || '');
            const issuedDate = issuedAt ? issuedAt.substring(0, 10) : '';
            $('#astInfoCode').text(item.property_code || '-');
            $('#astInfoNumber').text(item.property_number || '-');
            $('#astInfoUnit').text(item.unit || '-');
            $('#astInfoDescription').text(item.item_description || '-');
            $('#astInfoSerial').text(item.serial_number || '-');
            $('#astInfoStockDate').text(item.created_at || '-');
            $('#astInfoIssuedDate').text(issuedDate || '-');
            $('#astInfoStatus').html(statusBadge(item.status_at_check || 'UNASSIGNED'));
            $('#astInfoFacility').text(item.facility || '-');
            $('#astInfoAssignmentSource').text(item.assignment_source || '-');
            $('#astInfoAccountable').text(item.accountable || '-');
            $('#astInfoIssuedTo').text(item.issued_to || '-');
            $('#astInfoManagedBy').text(item.managed_by || '-');
            setItemPreview(item.category_photo_url || item.category_photo_thumb_url || '');
        }

        async function loadItem() {
            const code = ($('#auditCodeInput').val() || '').trim();
            clearMessage('#auditMsg');

            if (!code) {
                resetLoadedItem();
                showMessage('#auditMsg', 'danger', 'Enter or scan a valid code first.');
                return;
            }

            resetLoadedItem();
            const detectedModule = detectModuleFromCode(code);
            const moduleOrder = detectedModule
                ? [detectedModule].concat(Object.keys(MODULES).filter(key => key !== detectedModule))
                : ['CSM', 'AST'];

            let found = null;
            let lastError = 'Item not found.';

            for (const moduleKey of moduleOrder) {
                try {
                    found = await requestItem(moduleKey, code);
                    break;
                } catch (error) {
                    lastError = error && error.message ? error.message : 'Item not found.';
                }
            }

            if (!found) {
                showMessage('#auditMsg', 'danger', lastError);
                return;
            }

            currentItem = found.data;
            showDetectedModule(found.moduleKey);

            if (found.moduleKey === 'CSM') {
                renderCsmItem(currentItem);
            } else {
                renderAstItem(currentItem);
            }

            showMessage('#auditMsg', 'success', MODULES[found.moduleKey].label + ' item found.');
        }

        $(document).ready(function() {
            $('#loadItemBtn').on('click', function() {
                loadItem();
            });

            $('#auditCodeInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    loadItem();
                }
            });

            $('#auditCodeInput').on('input', function() {
                const typed = ($(this).val() || '').trim();
                if (!typed) {
                    resetLoadedItem();
                    clearMessage('#auditMsg');
                    return;
                }
                if (currentItem) {
                    const cfg = currentModule && MODULES[currentModule] ? MODULES[currentModule] : null;
                    const currentCode = cfg ? String(currentItem[cfg.codeKey] || '').trim() : '';
                    if (currentCode !== typed) {
                        resetLoadedItem();
                    }
                }
            });

            $('#resetAuditFormBtn').on('click', function() {
                $('#auditCodeInput').val('');
                resetLoadedItem();
                clearMessage('#auditMsg');
            });

            if (typeof initQrSearch === 'function') {
                initQrSearch({
                    modalId: '#auditQrModal',
                    openButton: '#openAuditScanner',
                    searchInput: '#auditCodeInput',
                    cameraSelectId: '#auditCameraSelect',
                    startBtnId: '#auditBtnStart',
                    stopBtnId: '#auditBtnStop',
                    previewId: '#auditPreview',
                    lastScannedId: '#auditLastScanned',
                    errorId: '#auditScanError',
                    loadingId: '#auditScannerLoading',
                    onSearch: function(decodedText) {
                        if (!decodedText) {
                            return;
                        }
                        $('#auditCodeInput').val(decodedText);
                        loadItem();
                    }
                });
            }

            resetLoadedItem();
        });
    </script>
</body>

</html>
