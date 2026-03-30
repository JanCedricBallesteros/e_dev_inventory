<?php
// csm_qrcode.php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// ACCESS CONTROL
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
    <style>
        .qr-topbar{
            align-items:flex-start;
            gap:1rem;
        }
        .qr-search-wrap{
            flex: 1 1 340px;
            min-width: 280px;
        }
        .qr-topbar .input-group{
            width:100%;
            max-width:none;
        }
        .qr-search-wrap .btn{
            min-width: 44px;
        }
        .qr-actions{
            flex: 1 1 540px;
            min-width: 320px;
            display:grid;
            grid-template-columns: repeat(3, minmax(160px, 1fr));
            gap:.75rem;
            align-items:start;
        }
        .qr-control{
            display:flex;
            flex-direction:column;
            gap:.35rem;
            min-width:0;
        }
        .qr-control-label{
            font-size:12px;
            color:#6c757d;
            line-height:1.2;
        }
        .qr-actions > *{
            min-width:0;
        }
        .qr-actions .btn,
        .qr-actions .form-select{
            min-height: 38px;
            width:100%;
        }
        .qr-actions .form-check{
            min-height: 38px;
            display:flex;
            align-items:center;
            padding: .5rem .75rem .5rem 1.9rem;
            margin: 0;
            border:1px solid #dee2e6;
            border-radius:.5rem;
            background:#fff;
        }
        .qr-filter-dropdown .dropdown-toggle{
            text-align:left;
            display:flex;
            align-items:center;
            justify-content:space-between;
        }
        .qr-filter-dropdown .dropdown-menu{
            width:100%;
            min-width:240px;
            max-height:280px;
            overflow-y:auto;
            padding:.5rem;
        }
        .qr-filter-option{
            display:flex;
            align-items:center;
            gap:.5rem;
            padding:.35rem .25rem;
            border-radius:.375rem;
        }
        .qr-filter-option:hover{
            background:#f8f9fa;
        }
        .qr-filter-option input{
            margin:0;
        }
        .qr-filter-summary{
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .qr-meta-bar{
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            justify-content:space-between;
            gap:.75rem;
        }
        .qr-meta-actions{
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            justify-content:flex-end;
            gap:.5rem;
        }
        .qr-meta-info{
            flex:1 1 240px;
        }
        .qr-meta-actions .form-select{
            min-width: 88px;
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

        .qr-preview-item{
            position:relative;
            border:1px solid #ddd;
            border-radius:12px;
            background:#fff;
            padding:12px;
            height:100%;
            overflow:hidden;
            cursor:pointer;
            transition:box-shadow .15s ease, border-color .15s ease;
        }
        .qr-preview-item:hover{
            border-color:#adb5bd;
            box-shadow:0 2px 8px rgba(0,0,0,.06);
        }
        .qr-preview-item.is-selected{
            border-color:#0d6efd;
            box-shadow:0 0 0 2px rgba(13,110,253,.10);
        }

        .qr-preview-check{
            position:absolute;
            top:10px;
            right:10px;
            z-index:30;
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:8px;
            padding:4px 7px;
            box-shadow:0 1px 2px rgba(0,0,0,.06);
            pointer-events:auto;
        }
        .qr-preview-check .form-check{
            margin:0;
            min-height:auto;
        }
        .qr-preview-check .form-check-input{
            margin:0;
            float:none;
            cursor:pointer;
        }

        .qr-preview-stage{
            width:100%;
            display:flex;
            justify-content:center;
            align-items:flex-start;
            overflow:auto;
            padding-top:28px;
            position:relative;
            z-index:1;
            pointer-events:none; /* preview itself won't block checkbox/card click */
        }

        .qr-preview-stage .simple-sticker,
        .qr-preview-stage .tag-sticker{
            flex:0 0 auto;
        }

        :root{
            --tag-border:#1d1d1d;
            --tag-grid:#2b2b2b;
            --tag-header:#b9d4ea;
            --tag-sub:#cfe3f5;
            --tag-text:#111;
            --detailed-border:#1E3A8A;
            --detailed-grid:#1E3A8A;
            --detailed-header:#1E3A8A;
            --detailed-sub:#FFFFFF;
            --detailed-text:#1E3A8A;

            --detailed-w: 100mm;
            --detailed-h: 48mm;
            --detailed-qr-size: 21mm;
            --simple-w: 70mm;
            --simple-h: 38mm;
            --simple-qr-column: 26mm;
            --simple-qr-size: 22mm;

            --gap: 6mm;
        }

        .tag-sticker,
        .simple-sticker{
            box-sizing:border-box;
            background:#fff;
        }

        .tag-sticker{
            width: var(--detailed-w);
            height: auto;
            min-height: var(--detailed-h);
            border: 2px solid var(--detailed-border);
            border-radius: 1mm;
            overflow: visible;
            display:flex;
            flex-direction:column;
        }

        .tag-top{
            display:grid;
            grid-template-columns: 18mm 1fr 14mm;
            min-height: 12mm;
            align-items:stretch;
            border-bottom: 2px solid var(--detailed-border);
            box-sizing:border-box;
        }

        .tag-ccc{
            background: var(--detailed-header);
            border-right: 2px solid var(--detailed-border);
            display:flex;
            align-items:center;
            justify-content:center;
            padding: 1mm;
        }
        .tag-ccc img{
            width: 13mm;
            height: 10mm;
            object-fit: contain;
            display:block;
        }

        .tag-agency{
            padding: 1.6mm 2mm;
            line-height: 1.05;
        }
        .tag-agency .agency{
            font-weight:700;
            font-size: 7.5pt;
            color:var(--detailed-text);
        }
        .tag-agency .subtitle{
            color:var(--detailed-text);
            font-weight:800;
            font-size: 7pt;
            margin-top: .6mm;
        }

        .tag-logo{
            border-left: 2px solid var(--detailed-border);
            display:flex;
            align-items:center;
            justify-content:center;
            padding: 1mm;
        }
        .tag-logo img{
            width: 10mm;
            height: 10mm;
            object-fit: contain;
            display:block;
        }

        .tag-body{
            display:grid;
            grid-template-columns: 1fr 27mm;
            height:auto;
        }

        .tag-form{
            border-right: 2px solid var(--detailed-border);
            height:auto;
            box-sizing:border-box;
            display:block;
        }

        .tag-row{
            display:grid;
            grid-template-columns: 20mm 1fr;
            border-bottom: 1px solid var(--detailed-grid);
            box-sizing:border-box;
            min-height: 5.2mm;
        }
        .tag-row:last-child{ border-bottom:none; }

        .tag-label{
            background:var(--detailed-sub);
            border-right:1px solid var(--detailed-grid);
            padding: 0.9mm 1.2mm;
            font-size: 6.6pt;
            font-style: italic;
            color:var(--detailed-text);
            display:flex;
            align-items:center;
            box-sizing:border-box;
        }

        .tag-value{
            padding: 0.9mm 1.2mm;
            font-size: 6.8pt;
            font-weight: 600;
            color:var(--detailed-text);
            display:block;
            box-sizing:border-box;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            overflow-wrap:anywhere;
            word-break:break-word;
            line-height: 1.15;
        }
        .tag-value.is-strong{ font-weight: 800; }

        .tag-qr{
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding: 2mm 1.5mm;
            box-sizing:border-box;
        }
        .tag-qr img{
            width: var(--detailed-qr-size);
            height: var(--detailed-qr-size);
            object-fit:contain;
            border:1px solid #c9c9c9;
            padding: 1mm;
            background:#fff;
            box-sizing:border-box;
        }

        .tag-remarks{
            min-height: 9mm;
            height:auto;
            border-top: 2px solid var(--detailed-border);
            display:grid;
            grid-template-columns: 32mm 1fr;
            box-sizing:border-box;
        }
        .tag-remarks .tag-label{
            background: var(--detailed-sub);
            border-right: 1px solid var(--detailed-border);
            font-size: 6.6pt;
        }
        .tag-remarks .tag-value{
            background: var(--detailed-sub);
            font-weight: 700;
            color:var(--detailed-text);
            font-size: 6.8pt;
            white-space: normal;
            overflow: visible;
        }

        .simple-sticker{
            width: var(--simple-w);
            height: var(--simple-h);
            border:2px solid var(--tag-border);
            border-radius: 1mm;
            padding: 2mm;
            display:grid;
            grid-template-columns: 1fr var(--simple-qr-column);
            gap: 2mm;
            align-items:center;
            overflow:hidden;
        }
        .simple-meta{
            display:flex;
            flex-direction:column;
            gap:1mm;
            min-width:0;
        }
        .simple-code{
            font-weight:900;
            font-size: 8.5pt;
        }
        .simple-name{
            font-size: 7pt;
            font-weight:800;
            white-space: normal;
            overflow:hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-height: 1.15;
            overflow-wrap:anywhere;
            word-break:break-word;
        }
        .simple-cat{
            font-size: 6.8pt;
            color:#555;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .simple-qr{
            text-align:center;
        }
        .simple-qr img{
            width: var(--simple-qr-size);
            height: var(--simple-qr-size);
            object-fit:contain;
            border:1px solid #c9c9c9;
            padding: 1mm;
            background:#fff;
            box-sizing:border-box;
        }

        @media (max-width: 1200px){
            .qr-preview-stage .tag-sticker{ transform:scale(.92); transform-origin: top center; margin-bottom:-14px; }
            .qr-preview-stage .simple-sticker{ transform:scale(.96); transform-origin: top center; margin-bottom:-6px; }
        }
        @media (max-width: 768px){
            .qr-search-wrap,
            .qr-actions,
            .qr-meta-bar > *,
            .qr-meta-actions{
                width:100%;
            }
            .qr-actions{
                grid-template-columns: 1fr;
                min-width: 0;
            }
            .qr-control{
                width:100%;
            }
            .qr-control-label{
                margin-bottom:0;
            }
            .qr-actions .form-check{
                width:100%;
            }
            .qr-meta-actions{
                justify-content:flex-start;
            }
            .qr-meta-actions label{
                width:100%;
            }
            .qr-meta-actions #qrPagination{
                width:100%;
            }
            .qr-preview-stage .tag-sticker{ transform:scale(.86); transform-origin: top center; margin-bottom:-26px; }
            .qr-preview-stage .simple-sticker{ transform:scale(.92); transform-origin: top center; margin-bottom:-10px; }
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
    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold">
                <i class="bi bi-qr-code"></i>&ensp;CSM QR Codes
            </div>

            <div class="card-body mt-3 bg-white">
                <div class="d-flex flex-wrap gap-2 justify-content-between mb-3 qr-topbar">
                    <div class="qr-search-wrap">
                        <div class="qr-control-label">Search</div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="qrSearch" class="form-control" placeholder="Search item code, description, category, source of funds, or QR verification">
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#scanQrModal" title="Scan QR">
                                <i class="bi bi-upc-scan"></i>
                            </button>
                        </div>
                    </div>

                    <div class="qr-actions">
                        <div class="qr-control">
                            <div class="qr-control-label">Category</div>
                            <div class="dropdown qr-filter-dropdown" data-bs-auto-close="outside">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="qrCategoryFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="qr-filter-summary">All Categories</span>
                                </button>
                                <div class="dropdown-menu" id="qrCategoryFilterMenu">
                                    <label class="qr-filter-option">
                                        <input type="checkbox" class="form-check-input" id="qrCategoryAll" checked>
                                        <span>All Categories</span>
                                    </label>
                                    <div class="dropdown-divider my-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="qr-control">
                            <div class="qr-control-label">Batch Select</div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="btnDateBatch" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-calendar3"></i>&ensp;Select by Date
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" id="dateBatchMenu" style="max-height:300px;overflow-y:auto;min-width:220px;"></ul>
                            </div>
                        </div>

                        <div class="qr-control">
                            <div class="qr-control-label">Selection</div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAllQr">
                                <label class="form-check-label" for="selectAllQr">Select All</label>
                            </div>
                        </div>

                        <div class="qr-control">
                            <div class="qr-control-label">Layout</div>
                            <select class="form-select form-select-sm" id="printLayout">
                                <option value="simple" selected>Print: Simple Sticker</option>
                                <option value="detailed">Print: Detailed Property Tag</option>
                            </select>
                        </div>

                        <div class="qr-control">
                            <div class="qr-control-label">Print</div>
                            <button class="btn btn-outline-primary btn-sm" id="btnPrintSelected" disabled>
                                <i class="bi bi-printer"></i>&ensp;Print Selected
                            </button>
                        </div>

                        <div class="qr-control">
                            <div class="qr-control-label">Export</div>
                            <button class="btn btn-outline-success btn-sm" id="btnSavePdf" disabled>
                                <i class="bi bi-file-earmark-pdf"></i>&ensp;Save as PDF
                            </button>
                        </div>
                    </div>
                </div>

                <div class="qr-meta-bar mb-2">
                    <div id="qrPageInfo" class="text-muted small qr-meta-info"></div>
                    <div class="qr-meta-actions">
                        <label class="mb-0 small text-muted">Per page:</label>
                        <select id="qrPageSize" class="form-select form-select-sm" style="width:auto;">
                            <option value="20">20</option>
                            <option value="100" selected>100</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                            <option value="all">All</option>
                        </select>
                        <div id="qrPagination"></div>
                    </div>
                </div>

                <div id="qrMsg" class="alert alert-danger d-none mb-2"></div>
                <div class="small text-muted mb-2">
                    Tip: Scan QR will auto-fill search and filter the list.
                </div>

                <div id="qrList" class="row g-3"></div>
                <div id="qrEmpty" class="text-muted small" style="display:none;">No QR codes found.</div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="scanQrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-upc-scan"></i>&ensp;Scan QR</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 mb-2">
          <select id="cameraSelect" class="form-select form-select-sm" style="max-width: 260px;">
            <option value="">Loading cameras...</option>
          </select>
          <button type="button" id="btnStart" class="btn btn-success btn-sm">Start</button>
          <button type="button" id="btnStop" class="btn btn-outline-danger btn-sm" disabled>Stop</button>
        </div>

        <div style="width:100%;max-width:520px;margin:0 auto;position:relative;background:#000;border-radius:12px;overflow:hidden;aspect-ratio:4/3;">
          <div id="preview"></div>
          <div id="scannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
            <div>Initializing camera...</div>
          </div>
        </div>

        <div class="mt-2 small">
          <span class="text-muted">Last scanned:</span>
          <span id="lastScanned" class="fw-semibold">-</span>
        </div>

        <div id="scanError" class="text-danger small mt-1" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<?php include_once FOOTER_PATH; ?>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/consumable/process/csm_inventory_process.php';
const QR_GENERATOR_URL = BASE_URL + 'admin/modules/tools/qr_image.php';
const LOGO_URL = BASE_URL + 'upload/img/ccc-display.png';
const INVENTORY_LOGO_URL = BASE_URL + 'upload/img/inventory-logo.png';

let qrItems = [];
let qrMsgTimeout = null;

let currentPage = 1;
let pageSize = 20;
let lastFilter = '';
let selectedCodes = new Set();
let dateFilter = '';
let categoryFilter = null;
let filteredItems = [];

function escapeHtml(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
  }[m]));
}

function showFloatingNotice(type, content, opts = {}) {
  const settings = Object.assign({ html: false, delay: 4000 }, opts || {});
  const id = 'notice-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
  const body = settings.html ? String(content || '') : escapeHtml(String(content || ''));
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

function showQrMessage(message) {
  if (!message) {
    return;
  }
  showFloatingNotice('danger', message);
}

function updateButtons() {
  const selectedCount = selectedCodes.size;
  $('#btnPrintSelected').prop('disabled', selectedCount === 0);
  $('#btnSavePdf').prop('disabled', selectedCount === 0);
}

function populateCategoryFilter() {
  const menu = $('#qrCategoryFilterMenu');
  const categories = new Set();

  qrItems.forEach(item => {
    const name = String(item.item_category_name || '').trim();
    if (name) categories.add(name);
  });

  const sorted = Array.from(categories).sort((a, b) => a.localeCompare(b));
  menu.find('.qr-filter-item').remove();

  sorted.forEach(name => {
    const checked = categoryFilter === null || categoryFilter.includes(name);
    menu.append(`
      <label class="qr-filter-option qr-filter-item">
        <input type="checkbox" class="form-check-input qr-category-option" value="${escapeHtml(name)}" ${checked ? 'checked' : ''}>
        <span>${escapeHtml(name)}</span>
      </label>
    `);
  });

  $('#qrCategoryAll').prop('checked', categoryFilter === null);
  updateCategoryFilterSummary();
}

function updateCategoryFilterSummary() {
  const summary = $('#qrCategoryFilterBtn .qr-filter-summary');
  if (categoryFilter === null) {
    summary.text('All Categories');
    return;
  }

  if (categoryFilter.length === 0) {
    summary.text('No Categories Selected');
    return;
  }

  if (categoryFilter.length === 1) {
    summary.text(categoryFilter[0]);
    return;
  }

  summary.text(`${categoryFilter.length} categories selected`);
}

function syncCategorySelectionFromMenu() {
  const checkedValues = $('.qr-category-option:checked').map(function() {
    return $(this).val();
  }).get();

  if (checkedValues.length === 0) {
    categoryFilter = [];
  } else {
    const allSelected = $('.qr-category-option').length === checkedValues.length;
    categoryFilter = allSelected ? null : checkedValues;
  }
}

function getSelectedCodes(){
  return Array.from(selectedCodes);
}

function buildSimpleSticker({code, desc, catLine, qrUrl}) {
  return `
    <div class="simple-sticker">
      <div class="simple-meta">
        <div class="simple-code">${escapeHtml(code)}</div>
        <div class="simple-name">${escapeHtml(desc || '')}</div>
        <div class="simple-cat">${escapeHtml(catLine || '')}</div>
      </div>
      <div class="simple-qr">
        <img src="${qrUrl}" alt="QR ${escapeHtml(code)}">
      </div>
    </div>
  `;
}

function buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl }) {
  return `
    <div class="tag-sticker">
      <div class="tag-top">
        <div class="tag-ccc">
          <img src="${LOGO_URL}" alt="CCC logo">
        </div>
        <div class="tag-agency">
          <div class="agency">CITY COLLEGE OF CALAMBA</div>
          <div class="subtitle">Property Inventory Tag</div>
        </div>
        <div class="tag-logo">
          <img src="${INVENTORY_LOGO_URL}" alt="Inventory logo">
        </div>
      </div>

      <div class="tag-body">
        <div class="tag-form">
          <div class="tag-row">
            <div class="tag-label">Property Tag</div>
            <div class="tag-value is-strong">${escapeHtml(code)}</div>
          </div>

          <div class="tag-row">
            <div class="tag-label">Item Description</div>
            <div class="tag-value">${escapeHtml(desc || '')}</div>
          </div>

          <div class="tag-row">
            <div class="tag-label">Category</div>
            <div class="tag-value">${escapeHtml(catLine || '')}</div>
          </div>

          <div class="tag-row">
            <div class="tag-label">Acquisition Date</div>
            <div class="tag-value">${escapeHtml(acq || '')}</div>
          </div>

          <div class="tag-row">
            <div class="tag-label">Cost</div>
            <div class="tag-value">${escapeHtml(cost || '')}</div>
          </div>
        </div>

        <div class="tag-qr">
          <img src="${qrUrl}" alt="QR ${escapeHtml(code)}">
        </div>
      </div>

      <div class="tag-remarks">
        <div class="tag-label">Notes</div>
        <div class="tag-value">${escapeHtml(notes || '')}</div>
      </div>
    </div>
  `;
}

function buildScreenPreviewCard(item, layout) {
  const code = item.inventory_system_item_code || '';
  const desc = item.item_description || '';
  const catCode = item.item_category_code || '';
  const catName = item.item_category_name || '';
  const acq = item.acquisition_date || '';
  const cost = (item.cost_value != null && item.cost_value !== '') ? String(item.cost_value) : '';
  const notes = item.notes || item.note || item.remarks || '';
  const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);

  const catLine = (catName && catCode) ? `${catName} (${catCode})` : (catName || catCode);

  let previewHtml = '';
  if (layout === 'detailed') {
    previewHtml = buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl });
  } else {
    previewHtml = buildSimpleSticker({ code, desc, catLine, qrUrl });
  }

  const selectedClass = selectedCodes.has(code) ? ' is-selected' : '';

  return `
    <div class="col-12 col-sm-6 col-lg-6">
      <div class="qr-preview-item${selectedClass}" data-code="${escapeHtml(code)}">
        <div class="qr-preview-check">
          <div class="form-check">
            <input class="form-check-input qr-check" type="checkbox" data-code="${escapeHtml(code)}" ${selectedCodes.has(code) ? 'checked' : ''}>
          </div>
        </div>
        <div class="qr-preview-stage ${layout === 'detailed' ? 'preview-detailed' : 'preview-simple'}">
          ${previewHtml}
        </div>
      </div>
    </div>
  `;
}

function renderPagination(total, totalPages) {
  const info = $('#qrPageInfo');
  const nav  = $('#qrPagination');

  if (total === 0) {
    info.html('');
    nav.html('');
    return;
  }

  const start = (currentPage - 1) * pageSize + 1;
  const end   = Math.min(currentPage * pageSize, total);
  const selCount = selectedCodes.size;

  info.html(`Showing ${start}&ndash;${end} of ${total}${selCount > 0 ? ` &bull; <strong>${selCount}</strong> selected` : ''}`);

  const maxVisible = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
  let endPage   = Math.min(totalPages, startPage + maxVisible - 1);

  if (endPage - startPage < maxVisible - 1) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }

  let btns = '';
  btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-page="1" ${currentPage === 1 ? 'disabled' : ''}>First</button>`;
  btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>`;

  if (startPage > 1) btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1" disabled>&hellip;</button>`;

  for (let p = startPage; p <= endPage; p++) {
    btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1${p === currentPage ? ' active' : ''}" data-page="${p}">${p}</button>`;
  }

  if (endPage < totalPages) btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1" disabled>&hellip;</button>`;

  btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>`;
  btns += `<button type="button" class="btn btn-sm btn-outline-secondary" data-page="${totalPages}" ${currentPage === totalPages ? 'disabled' : ''}>Last</button>`;

  nav.html(btns);
}

function renderList(filter = '', resetPage = false) {
  if (resetPage) currentPage = 1;
  lastFilter = filter;

  const list = $('#qrList');
  list.empty();

  const f = (filter || '').trim().toLowerCase();
  const layout = ($('#printLayout').val() || 'simple');

  filteredItems = qrItems.filter(item => {
    const code    = (item.inventory_system_item_code || '').toLowerCase();
    const desc    = (item.item_description || '').toLowerCase();
    const catCode = (item.item_category_code || '').toLowerCase();
    const catName = (item.item_category_name || '').toLowerCase();
    const sof     = (item.source_of_funds || '').toLowerCase();
    const qrver   = (item.qr_verification || '').toLowerCase();

    const matchesSearch = (
      f === '' ||
      code.includes(f) ||
      desc.includes(f) ||
      catCode.includes(f) ||
      catName.includes(f) ||
      sof.includes(f) ||
      qrver.includes(f)
    );

    const matchesDate = (
      dateFilter === '' ||
      (item.created_at && String(item.created_at).startsWith(dateFilter))
    );

    const matchesCategory = (
      categoryFilter === null ||
      categoryFilter.some(name => name.toLowerCase() === catName)
    );

    return matchesSearch && matchesDate && matchesCategory;
  });

  if (filteredItems.length === 0) {
    $('#qrEmpty').show();
    $('#selectAllQr').prop('checked', false);
    renderPagination(0, 0);
    updateButtons();
    return;
  }

  $('#qrEmpty').hide();

  const totalItems = filteredItems.length;
  const totalPages = Math.ceil(totalItems / pageSize);

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  const start = (currentPage - 1) * pageSize;
  const pageItems = filteredItems.slice(start, start + pageSize);

  const cards = pageItems.map(item => buildScreenPreviewCard(item, layout));
  list.append(cards.join(''));

  const allSelected = filteredItems.length > 0 && filteredItems.every(i => selectedCodes.has(i.inventory_system_item_code));
  $('#selectAllQr').prop('checked', allSelected);

  renderPagination(totalItems, totalPages);
  updateButtons();
}

function loadQrItems() {
  $.post(PROCESS_URL, { action: 'list_inventory' }, function(res){
    if (res && res.success) {
      qrItems = res.data || [];
      populateCategoryFilter();
      renderList($('#qrSearch').val() || '');
      showQrMessage('');
    } else {
      qrItems = [];
      populateCategoryFilter();
      renderList('');
      showQrMessage(res && res.message ? res.message : 'Failed to load QR codes.');
    }
  }, 'json').fail(function(){
    qrItems = [];
    populateCategoryFilter();
    renderList('');
    showQrMessage('Server error while loading QR codes.');
  });
}

const TAG_PRINT_CSS = `
  :root{
    --tag-border:#1d1d1d;
    --tag-grid:#2b2b2b;
    --tag-header:#b9d4ea;
    --tag-sub:#cfe3f5;
    --tag-text:#111;
    --detailed-border:#1E3A8A;
    --detailed-grid:#1E3A8A;
    --detailed-header:#1E3A8A;
    --detailed-sub:#FFFFFF;
    --detailed-text:#1E3A8A;

    --detailed-w: 100mm;
    --detailed-h: 48mm;
    --detailed-qr-size: 21mm;
    --simple-w: 70mm;
    --simple-h: 38mm;
    --simple-qr-column: 26mm;
    --simple-qr-size: 22mm;

  }

  @page { size: Letter; margin: 8mm; }

  body{
    font-family: Arial, Helvetica, sans-serif;
    color: var(--tag-text);
    margin:0;
  }

  .print-page.simple{
    display:grid;
    gap: var(--gap);
    justify-content:start;
    align-content:start;
    page-break-after: always;
    break-after: page;
  }
  .print-page.simple.is-last{
    page-break-after:auto;
    break-after:auto;
  }

  .print-page.detailed{
    display:flex;
    flex-wrap:wrap;
    gap: var(--gap);
    align-content:flex-start;
    page-break-after:auto;
    break-after:auto;
  }

  .tag-sticker,
  .simple-sticker{
    break-inside: avoid;
    page-break-inside: avoid;
    box-sizing:border-box;
    background:#fff;
  }

  .tag-sticker{
    width: var(--detailed-w);
    height: auto;
    min-height: var(--detailed-h);
    border: 2px solid var(--detailed-border);
    border-radius: 1mm;
    overflow: visible;
    display:flex;
    flex-direction:column;
  }

  .tag-top{
    display:grid;
    grid-template-columns: 18mm 1fr 14mm;
    min-height: 12mm;
    align-items:stretch;
    border-bottom: 2px solid var(--detailed-border);
    box-sizing:border-box;
  }

  .tag-ccc{
    background: var(--detailed-header);
    border-right: 2px solid var(--detailed-border);
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 1mm;
  }
  .tag-ccc img{
    width: 13mm;
    height: 10mm;
    object-fit: contain;
    display:block;
  }

  .tag-agency{
    padding: 1.6mm 2mm;
    line-height: 1.05;
  }
  .tag-agency .agency{
    font-weight:700;
    font-size: 7.5pt;
    color:var(--detailed-text);
  }
  .tag-agency .subtitle{
    color:var(--detailed-text);
    font-weight:800;
    font-size: 7pt;
    margin-top: .6mm;
  }

  .tag-logo{
    border-left: 2px solid var(--detailed-border);
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 1mm;
  }
  .tag-logo img{
    width: 10mm;
    height: 10mm;
    object-fit: contain;
    display:block;
  }

  .tag-body{
    display:grid;
    grid-template-columns: 1fr 27mm;
    height:auto;
  }

  .tag-form{
    border-right: 2px solid var(--detailed-border);
    height:auto;
    box-sizing:border-box;
    display:block;
  }

  .tag-row{
    display:grid;
    grid-template-columns: 20mm 1fr;
    border-bottom: 1px solid var(--detailed-grid);
    box-sizing:border-box;
    min-height: 5.2mm;
  }
  .tag-row:last-child{ border-bottom:none; }

  .tag-label{
    background:var(--detailed-sub);
    border-right:1px solid var(--detailed-grid);
    padding: 0.9mm 1.2mm;
    font-size: 6.6pt;
    font-style: italic;
    color:var(--detailed-text);
    display:flex;
    align-items:center;
    box-sizing:border-box;
  }

  .tag-value{
    padding: 0.9mm 1.2mm;
    font-size: 6.8pt;
    font-weight: 600;
    color:var(--detailed-text);
    display:block;
    box-sizing:border-box;
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
    overflow-wrap:anywhere;
    word-break:break-word;
    line-height: 1.15;
  }
  .tag-value.is-strong{ font-weight: 800; }

  .tag-qr{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding: 2mm 1.5mm;
    box-sizing:border-box;
  }
  .tag-qr img{
    width: var(--detailed-qr-size);
    height: var(--detailed-qr-size);
    object-fit:contain;
    border:1px solid #c9c9c9;
    padding: 1mm;
    background:#fff;
    box-sizing:border-box;
  }

  .tag-remarks{
    min-height: 9mm;
    height:auto;
    border-top: 2px solid var(--detailed-border);
    display:grid;
    grid-template-columns: 32mm 1fr;
    box-sizing:border-box;
  }
  .tag-remarks .tag-label{
    background: var(--detailed-sub);
    border-right: 1px solid var(--detailed-border);
    font-size: 6.6pt;
  }
  .tag-remarks .tag-value{
    background: var(--detailed-sub);
    font-weight: 700;
    color:var(--detailed-text);
    font-size: 6.8pt;
    white-space: normal;
    overflow: visible;
  }

  .simple-sticker{
    width: var(--simple-w);
    height: var(--simple-h);
    border:2px solid var(--tag-border);
    border-radius: 1mm;
    padding: 2mm;
    display:grid;
    grid-template-columns: 1fr var(--simple-qr-column);
    gap: 2mm;
    align-items:center;
    overflow:hidden;
  }
  .simple-meta{ display:flex; flex-direction:column; gap:1mm; min-width:0; }
  .simple-code{ font-weight:900; font-size: 8.5pt; }
  .simple-name{
    font-size: 7pt;
    font-weight:800;
    white-space: normal;
    overflow:hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-height: 1.15;
    overflow-wrap:anywhere;
    word-break:break-word;
  }
  .simple-cat{
    font-size: 6.8pt;
    color:#555;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .simple-qr{ text-align:center; }
  .simple-qr img{
    width: var(--simple-qr-size);
    height: var(--simple-qr-size);
    object-fit:contain;
    border:1px solid #c9c9c9;
    padding: 1mm;
    background:#fff;
    box-sizing:border-box;
  }

  @media print{
    body{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
`;

function buildPagesHtml(layout, cards) {
  if (layout === 'detailed') {
    return `<div class="print-page detailed is-last">${cards.join('')}</div>`;
  }

  const perPage = 12;
  const gridCols = 'repeat(3, var(--simple-w))';

  const pages = [];
  for (let i = 0; i < cards.length; i += perPage) {
    const pageCards = cards.slice(i, i + perPage).join('');
    const isLast = (i + perPage) >= cards.length;
    pages.push(`<div class="print-page simple${isLast ? ' is-last' : ''}" style="grid-template-columns:${gridCols}">${pageCards}</div>`);
  }
  return pages.join('');
}

function printSelected() {
  const layout = ($('#printLayout').val() || 'simple');

  const byCode = {};
  qrItems.forEach(item => {
    if (item.inventory_system_item_code) {
      byCode[item.inventory_system_item_code] = item;
    }
  });

  const codes = getSelectedCodes();
  if (!codes.length) return;

  const cards = codes.map(code => {
    const item = byCode[code] || {};
    const desc = item.item_description || '';
    const acq  = item.acquisition_date || '';
    const cost = (item.cost_value != null && item.cost_value !== '') ? item.cost_value : '';
    const catCode = item.item_category_code || '';
    const catName = item.item_category_name || '';
    const catLine = (catName && catCode) ? `${catName} (${catCode})` : (catName || catCode);
    const notes = item.notes || item.note || item.remarks || '';
    const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);

    return layout === 'simple'
      ? buildSimpleSticker({ code, desc, catLine, qrUrl })
      : buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl });
  });

  const pagesHtml = buildPagesHtml(layout, cards);

  const printWin = window.open('', '_blank');
  printWin.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>CSM QR Codes</title>
      <style>${TAG_PRINT_CSS}</style>
    </head>
    <body>${pagesHtml}</body>
    </html>
  `);
  printWin.document.close();
  printWin.focus();

  try {
    const imgs = printWin.document.images;
    const promises = [];
    for (let i = 0; i < imgs.length; i++) {
      const img = imgs[i];
      if (img.complete) continue;
      promises.push(new Promise(resolve => {
        const done = () => {
          img.removeEventListener('load', done);
          img.removeEventListener('error', done);
          resolve();
        };
        img.addEventListener('load', done);
        img.addEventListener('error', done);
      }));
    }

    Promise.race([
      Promise.all(promises),
      new Promise(r => setTimeout(r, 8000))
    ]).then(() => {
      try { printWin.focus(); } catch(e){}
      printWin.print();
    });
  } catch (e) {
    try { printWin.focus(); } catch(er){}
    printWin.print();
  }
}

function saveToPdf() {
  const layout = ($('#printLayout').val() || 'simple');

  const byCode = {};
  qrItems.forEach(item => {
    if (item.inventory_system_item_code) {
      byCode[item.inventory_system_item_code] = item;
    }
  });

  const codes = getSelectedCodes();
  if (!codes.length) return;

  const cards = codes.map(code => {
    const item = byCode[code] || {};
    const desc = item.item_description || '';
    const acq  = item.acquisition_date || '';
    const cost = (item.cost_value != null && item.cost_value !== '') ? item.cost_value : '';
    const catCode = item.item_category_code || '';
    const catName = item.item_category_name || '';
    const catLine = (catName && catCode) ? `${catName} (${catCode})` : (catName || catCode);
    const notes = item.notes || item.note || item.remarks || '';
    const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);

    return layout === 'simple'
      ? buildSimpleSticker({ code, desc, catLine, qrUrl })
      : buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl });
  });

  const pagesHtml = buildPagesHtml(layout, cards);

  const element = document.createElement('div');
  element.innerHTML = `<style>${TAG_PRINT_CSS}</style>${pagesHtml}`;

  const opt = {
    margin: [0.2, 0.2, 0.2, 0.2],
    filename: `csm-qr-codes-${new Date().toISOString().split('T')[0]}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, allowTaint: true },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
    pagebreak: { mode: ['css', 'legacy'] }
  };

  html2pdf().set(opt).from(element).save();
}

function playBeep() {
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  const ctx = new AudioContext();
  const oscillator = ctx.createOscillator();
  const gain = ctx.createGain();
  oscillator.type = 'sine';
  oscillator.frequency.value = 1000;
  gain.gain.value = 0.15;
  oscillator.connect(gain);
  gain.connect(ctx.destination);
  oscillator.start();
  setTimeout(() => {
    oscillator.stop();
    ctx.close();
  }, 120);
}

let html5QrcodeScanner = null;
let isScanning = false;

function showScanError(msg){
  const errEl = document.getElementById('scanError');
  errEl.textContent = msg;
  errEl.style.display = msg ? 'block' : 'none';
  document.getElementById('scannerLoading').style.display = 'none';
}

function setRunning(running){
  document.getElementById('btnStart').disabled = running;
  document.getElementById('btnStop').disabled = !running;
  document.getElementById('cameraSelect').disabled = running;
  document.getElementById('scannerLoading').style.display = running ? 'flex' : 'none';
  isScanning = running;
}

async function loadCameras(){
  showScanError('');
  const cameraSelect = document.getElementById('cameraSelect');
  cameraSelect.innerHTML = `<option value="">Loading cameras...</option>`;

  try{
    const cameras = await Html5Qrcode.getCameras();
    if(!cameras || cameras.length === 0){
      cameraSelect.innerHTML = `<option value="">No cameras found</option>`;
      showScanError('No cameras found / permission issue.');
      return;
    }

    cameraSelect.innerHTML = '';
    cameras.forEach((cam, idx) => {
      const opt = document.createElement('option');
      opt.value = cam.id;
      opt.textContent = cam.label || `Camera ${idx + 1}`;
      cameraSelect.appendChild(opt);
    });

    const backCam = cameras.find(c => /back|rear|environment/i.test(c.label || ''));
    cameraSelect.value = (backCam ? backCam.id : cameras[0].id);

  } catch(e){
    cameraSelect.innerHTML = `<option value="">Camera permission denied</option>`;
    showScanError('Cannot access cameras: ' + (e && e.message ? e.message : String(e)));
  }
}

async function startScanner(){
  showScanError('');
  setRunning(true);

  const camId = document.getElementById('cameraSelect').value;
  if(!camId){
    showScanError('Please select a camera first.');
    setRunning(false);
    return;
  }

  if(html5QrcodeScanner){
    try { await html5QrcodeScanner.stop(); } catch(e){}
  }

  html5QrcodeScanner = new Html5Qrcode('preview');

  const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase());
  const config = isMobile
    ? { fps: 15, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0, disableFlip: false, showTorchButtonIfSupported: true }
    : { fps: 10, qrbox: { width: 220, height: 220 }, disableFlip: false };

  try{
    await html5QrcodeScanner.start(
      camId,
      config,
      (decodedText) => {
        playBeep();
        document.getElementById('lastScanned').textContent = decodedText;
        $('#qrSearch').val(decodedText);
        renderList(decodedText, true);
      },
      () => {}
    );
    document.getElementById('scannerLoading').style.display = 'none';
  } catch(e){
    setRunning(false);
    showScanError('Failed to start camera: ' + (e && e.message ? e.message : String(e)));
  }
}

async function stopScanner(){
  showScanError('');
  if(!html5QrcodeScanner || !isScanning) return;
  try { await html5QrcodeScanner.stop(); } catch(e){}
  setRunning(false);
}

function rebuildDateBatchMenu() {
  const menu = $('#dateBatchMenu');
  menu.empty();

  const dateMap = {};
  qrItems.forEach(item => {
    const code = item.inventory_system_item_code || '';
    if (!item.created_at || !code) return;

    const d = String(item.created_at).split(' ')[0];
    if (!dateMap[d]) dateMap[d] = [];
    dateMap[d].push(code);
  });

  const dates = Object.keys(dateMap).sort().reverse();

  if (dates.length === 0) {
    menu.append('<li><span class="dropdown-item-text text-muted small">No dates available</span></li>');
    return;
  }

  menu.append('<li><a class="dropdown-item small text-danger" href="#" data-date="__clear__"><i class="bi bi-x-circle me-1"></i>Clear Selection</a></li>');
  menu.append('<li><hr class="dropdown-divider my-1"></li>');

  dates.forEach(d => {
    const count = dateMap[d].length;
    const label = new Date(d + 'T00:00:00').toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
    menu.append(`<li><a class="dropdown-item small" href="#" data-date="${escapeHtml(d)}">${escapeHtml(label)} <span class="badge bg-secondary ms-1">${count}</span></a></li>`);
  });
}

$(document).ready(function(){
  observeFloatingTargets(['#qrMsg', '#scanError']);
  loadQrItems();

  $('#qrSearch').on('keyup', function(){
    renderList($(this).val(), true);
  });

  $(document).on('change', '#qrCategoryAll', function() {
    if ($(this).is(':checked')) {
      categoryFilter = null;
      $('.qr-category-option').prop('checked', true);
    } else {
      categoryFilter = [];
      $('.qr-category-option').prop('checked', false);
    }
    populateCategoryFilter();
    renderList($('#qrSearch').val() || '', true);
  });

  $(document).on('change', '.qr-category-option', function() {
    syncCategorySelectionFromMenu();
    populateCategoryFilter();
    renderList($('#qrSearch').val() || '', true);
  });

  $(document).on('click mousedown', '#qrCategoryFilterMenu', function(e) {
    e.stopPropagation();
  });

  $('#printLayout').on('change', function(){
    renderList(lastFilter);
  });

  // Checkbox direct change
  $(document).on('change', '.qr-check', function(e){
    const code = $(this).data('code');
    if ($(this).is(':checked')) {
      selectedCodes.add(code);
    } else {
      selectedCodes.delete(code);
    }

    const card = $(this).closest('.qr-preview-item');
    card.toggleClass('is-selected', $(this).is(':checked'));

    const allSelected = filteredItems.length > 0 && filteredItems.every(i => selectedCodes.has(i.inventory_system_item_code));
    $('#selectAllQr').prop('checked', allSelected);

    updateButtons();
    renderPagination(filteredItems.length, Math.ceil(filteredItems.length / pageSize));
    e.stopPropagation();
  });

  // Clicking the card toggles the checkbox
  $(document).on('click', '.qr-preview-item', function(e){
    if ($(e.target).closest('.qr-preview-check').length) return;

    const checkbox = $(this).find('.qr-check').first();
    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
  });

  $('#selectAllQr').on('change', function(){
    if ($(this).is(':checked')) {
      filteredItems.forEach(i => {
        if (i.inventory_system_item_code) selectedCodes.add(i.inventory_system_item_code);
      });
    } else {
      filteredItems.forEach(i => {
        if (i.inventory_system_item_code) selectedCodes.delete(i.inventory_system_item_code);
      });
    }
    renderList(lastFilter);
  });

  $('#btnDateBatch').closest('.dropdown').on('show.bs.dropdown', function() {
    rebuildDateBatchMenu();
  });

  $(document).on('click', '#dateBatchMenu a', function(e) {
    e.preventDefault();
    const date = $(this).data('date');

    if (date === '__clear__') {
      selectedCodes.clear();
      dateFilter = '';
      $('#btnDateBatch')
        .removeClass('btn-warning')
        .addClass('btn-outline-secondary')
        .html('<i class="bi bi-calendar3"></i>&ensp;Select by Date');
    } else {
      dateFilter = date;

      qrItems.forEach(item => {
        if (item.created_at && String(item.created_at).startsWith(date) && item.inventory_system_item_code) {
          selectedCodes.add(item.inventory_system_item_code);
        }
      });

      const label = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });

      $('#btnDateBatch')
        .removeClass('btn-outline-secondary')
        .addClass('btn-warning')
        .html(`<i class="bi bi-calendar-check"></i>&ensp;${label} <i class="bi bi-x ms-1" id="btnClearDate"></i>`);
    }

    renderList(lastFilter, true);
    updateButtons();
  });

  $(document).on('click', '#btnClearDate', function(e) {
    e.stopPropagation();
    dateFilter = '';
    selectedCodes.clear();
    $('#btnDateBatch')
      .removeClass('btn-warning')
      .addClass('btn-outline-secondary')
      .html('<i class="bi bi-calendar3"></i>&ensp;Select by Date');
    renderList(lastFilter, true);
    updateButtons();
  });

  $(document).on('click', '#qrPagination button[data-page]', function() {
    const page = parseInt($(this).data('page'), 10);
    if (!page || page < 1) return;
    currentPage = page;
    renderList(lastFilter);
  });

  $('#qrPageSize').on('change', function() {
    pageSize = parseInt($(this).val(), 10) || 20;
    renderList(lastFilter, true);
  });

  $('#btnPrintSelected').on('click', printSelected);
  $('#btnSavePdf').on('click', saveToPdf);

  $('#scanQrModal').on('shown.bs.modal', function(){
    loadCameras();
    setRunning(false);
    document.getElementById('lastScanned').textContent = '-';
    document.getElementById('preview').innerHTML = '';
  });

  $('#btnStart').on('click', startScanner);
  $('#btnStop').on('click', stopScanner);

  $('#cameraSelect').on('change', function(){
    if(isScanning){
      stopScanner().then(() => startScanner());
    }
  });

  $('#scanQrModal').on('hidden.bs.modal', function(){
    stopScanner();
    document.getElementById('preview').innerHTML = '';
  });
});
</script>
</body>
</html>
