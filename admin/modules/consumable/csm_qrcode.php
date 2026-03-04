<?php
// csm_qrcode.php (QR-focused: list + search + select + print + PDF + scan QR)
// - NO add/edit/delete
// - Prints ONLY QR + tag details (NO status/qty/critical anywhere)
// - Print layout option: Simple Sticker / Detailed Property Inventory Tag
// - Data source: csm_inventory_process.php (action=list_inventory)
//
// UPDATED TO MATCH SQL (csm_inventory):
// - item_name REMOVED (not in SQL) -> uses item_description
// - acquisition_date, item_cost, source_of_funds supported (shown in list; not printed in tag unless you add it)
// - qr_verification supported (searchable; not printed by default)
// - category name supported IF list_inventory joins csm_inventory_category (item_category_name)
//
// FLEXIBLE PRINT (TRADE-OFF):
// - Detailed tag grows based on content (no clipping, cost won't disappear)
// - Grid alignment is NOT guaranteed because heights vary

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
        user_has_access("AST")
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
        /* Page UI (not print) */
        .qr-card { border:1px solid #ddd; border-radius:12px; padding:10px; background:#fff; height:100%; }
        .qr-img { max-width:150px; width:100%; height:auto; border:1px solid #dee2e6; border-radius:10px; background:#fff; padding:8px; }
        .qr-badge { font-size:12px; }
        .qr-desc { font-size:12px; font-weight:700; margin-top:8px; }
        .qr-meta { font-size:12px; color:#6c757d; }
        .qr-topbar .input-group { max-width:380px; }
        .qr-meta .meta-line { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold">
                <i class="bi bi-qr-code"></i>&ensp;CSM QR Codes (Consumable Inventory)
            </div>

            <div class="card-body mt-3 bg-white">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3 qr-topbar">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="qrSearch" class="form-control" placeholder="Search item code, description, category, or source of funds">
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllQr">
                            <label class="form-check-label" for="selectAllQr">Select All</label>
                        </div>

                        <select class="form-select form-select-sm" id="printLayout" style="max-width:280px;">
                            <option value="simple" selected>Print: Simple Sticker</option>
                            <option value="detailed">Print: Detailed Property Tag</option>
                        </select>

                        <button class="btn btn-outline-primary btn-sm" id="btnPrintSelected" disabled>
                            <i class="bi bi-printer"></i>&ensp;Print Selected
                        </button>

                        <button class="btn btn-outline-success btn-sm" id="btnSavePdf" disabled>
                            <i class="bi bi-file-earmark-pdf"></i>&ensp;Save as PDF
                        </button>

                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#scanQrModal">
                            <i class="bi bi-upc-scan"></i>&ensp;Scan QR
                        </button>
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

<!-- SCAN QR MODAL -->
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
          <span id="lastScanned" class="fw-semibold">—</span>
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

// IMPORTANT: Adjust to your real module path if different.
const PROCESS_URL = BASE_URL + 'admin/modules/consumable/process/csm_inventory_process.php';
const QR_GENERATOR_URL = BASE_URL + 'admin/modules/tools/qr_image.php';

let qrItems = [];
let qrMsgTimeout = null;

function escapeHtml(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function showQrMessage(message) {
  const el = $('#qrMsg');
  if (!message) {
    el.addClass('d-none').text('');
    return;
  }
  el.removeClass('d-none').text(message);
  if (qrMsgTimeout) clearTimeout(qrMsgTimeout);
  qrMsgTimeout = setTimeout(() => el.addClass('d-none').text(''), 4000);
}

function renderList(filter = '') {
  const list = $('#qrList');
  list.empty();

  const f = (filter || '').trim().toLowerCase();

  const filtered = qrItems.filter(item => {
    const code = (item.inventory_system_item_code || '').toLowerCase();
    const desc = (item.item_description || '').toLowerCase();
    const catCode = (item.item_category_code || '').toLowerCase();
    const catName = (item.item_category_name || '').toLowerCase(); // if joined
    const sof  = (item.source_of_funds || '').toLowerCase();
    const qrver = (item.qr_verification || '').toLowerCase();
    return f === '' ||
      code.includes(f) ||
      desc.includes(f) ||
      catCode.includes(f) ||
      catName.includes(f) ||
      sof.includes(f) ||
      qrver.includes(f);
  });

  if (filtered.length === 0) {
    $('#qrEmpty').show();
    return;
  }
  $('#qrEmpty').hide();

  const cards = filtered.map(item => {
    const code = item.inventory_system_item_code || '';
    const desc = item.item_description || '';
    const catCode = item.item_category_code || '';
    const catName = item.item_category_name || '';
    const sof = item.source_of_funds || '';
    const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);

    const categoryLine = (catName && catCode)
      ? `${catName} (${catCode})`
      : (catName || catCode);

    const metaLines = [
      categoryLine ? `<span class="meta-line"><b>Category:</b> ${escapeHtml(categoryLine)}</span>` : '',
      sof ? `<span class="meta-line"><b>Source:</b> ${escapeHtml(sof)}</span>` : ''
    ].filter(Boolean).join('');

    return `
      <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        <div class="qr-card">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="form-check">
              <input class="form-check-input qr-check" type="checkbox" data-code="${escapeHtml(code)}">
            </div>
            <span class="badge bg-light text-dark border qr-badge">${escapeHtml(code)}</span>
          </div>

          <div class="text-center">
            <img src="${qrUrl}" alt="QR ${escapeHtml(code)}" class="qr-img">
          </div>

          <div class="qr-desc">${escapeHtml(desc || code)}</div>
          <div class="qr-meta">${metaLines || `<span class="meta-line">${escapeHtml(categoryLine || '')}</span>`}</div>
        </div>
      </div>
    `;
  });

  list.append(cards.join(''));
}

function updateButtons() {
  const selectedCount = $('.qr-check:checked').length;
  $('#btnPrintSelected').prop('disabled', selectedCount === 0);
  $('#btnSavePdf').prop('disabled', selectedCount === 0);
}

function loadQrItems() {
  $.post(PROCESS_URL, { action: 'list_inventory' }, function(res){
    if (res && res.success) {
      qrItems = res.data || [];
      renderList($('#qrSearch').val() || '');
      showQrMessage('');
    } else {
      qrItems = [];
      renderList('');
      showQrMessage(res && res.message ? res.message : 'Failed to load QR codes.');
    }
  }, 'json').fail(function(){
    qrItems = [];
    renderList('');
    showQrMessage('Server error while loading QR codes.');
  });
}

function getSelectedCodes(){
  return $('.qr-check:checked').map(function(){ return $(this).data('code'); }).get();
}

/* =========================
   PRINT/PDF STYLES + TEMPLATES
   DETAILED = FLEXIBLE HEIGHT (NO CLIPPING)
========================= */
const TAG_PRINT_CSS = `
  :root{
    --tag-border:#1d1d1d;
    --tag-grid:#2b2b2b;
    --tag-header:#b9d4ea;
    --tag-sub:#cfe3f5;
    --tag-text:#111;

    --detailed-w: 100mm;
    --detailed-h: 48mm; /* baseline min height */
    --simple-w: 70mm;
    --simple-h: 38mm;

    --gap: 6mm;
  }

  @page { size: Letter; margin: 8mm; }

  body{
    font-family: Arial, Helvetica, sans-serif;
    color: var(--tag-text);
    margin:0;
  }

  /* ===== SIMPLE still uses grid paging ===== */
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

  /* ===== DETAILED uses flex-wrap (variable heights) ===== */
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
  }

  /* ===================== DETAILED STICKER (FLEXIBLE) ===================== */
  .tag-sticker{
    width: var(--detailed-w);
    height: auto;               /* flexible */
    min-height: var(--detailed-h); /* baseline */
    border: 2px solid var(--tag-border);
    border-radius: 1mm;
    background:#fff;
    overflow: visible;          /* do not clip content */
    box-sizing:border-box;
    display:flex;
    flex-direction:column;
  }

  .tag-top{
    display:grid;
    grid-template-columns: 18mm 1fr 14mm;
    min-height: 12mm;
    align-items:stretch;
    border-bottom: 2px solid var(--tag-border);
    box-sizing:border-box;
  }

  .tag-ccc{
    background: var(--tag-header);
    border-right: 2px solid var(--tag-border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    letter-spacing:.6px;
    font-size: 10pt;
  }

  .tag-agency{
    padding: 1.6mm 2mm;
    line-height: 1.05;
  }
  .tag-agency .agency{
    font-weight:700;
    font-size: 7.5pt;
  }
  .tag-agency .subtitle{
    color:#b02a37;
    font-weight:800;
    font-size: 7pt;
    margin-top: .6mm;
  }

  .tag-logo{
    border-left: 2px solid var(--tag-border);
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .tag-logo .logo-circle{
    width: 9mm; height: 9mm;
    border-radius: 50%;
    border: 2px solid #b02a37;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size: 7pt;
    font-weight:800;
    color:#b02a37;
  }

  .tag-body{
    display:grid;
    grid-template-columns: 1fr 24mm;
    height:auto;                /* ✅ flexible */
  }

  .tag-form{
    border-right: 2px solid var(--tag-border);
    height:auto;                /* flexible */
    box-sizing:border-box;
    display:block;              /* allow rows to expand */
  }

  .tag-row{
    display:grid;
    grid-template-columns: 32mm 1fr;
    border-bottom: 1px solid var(--tag-grid);
    box-sizing:border-box;
    min-height: 5.2mm;          /* baseline */
  }
  .tag-row:last-child{ border-bottom:none; }

  .tag-label{
    background:#fafafa;
    border-right:1px solid var(--tag-grid);
    padding: 0.9mm 1.2mm;
    font-size: 6.6pt;
    font-style: italic;
    color:#333;
    display:flex;
    align-items:center;
    box-sizing:border-box;
  }

  .tag-value{
    padding: 0.9mm 1.2mm;
    font-size: 6.8pt;
    font-weight: 600;
    display:block;
    box-sizing:border-box;

    white-space: normal;        /* wrap */
    overflow: visible;          /* no clipping */
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
    justify-content:flex-start;
    padding: 2mm 1.5mm 1.5mm;
    box-sizing:border-box;
    gap: 1mm;
  }
  .tag-qr img{
    width: 18mm;
    height: 18mm;
    object-fit:contain;
    border:1px solid #c9c9c9;
    padding: 1mm;
    background:#fff;
    box-sizing:border-box;
  }
  .tag-code{
    font-size: 6.2pt;
    font-weight:900;
    text-align:center;
    line-height:1.05;
    word-break:break-word;
  }

  .tag-remarks{
    min-height: 9mm;            /* baseline */
    height:auto;                /* flexible */
    border-top: 2px solid var(--tag-border);
    display:grid;
    grid-template-columns: 32mm 1fr;
    box-sizing:border-box;
  }
  .tag-remarks .tag-label{
    background: var(--tag-sub);
    border-right: 1px solid var(--tag-border);
    font-size: 6.6pt;
  }
  .tag-remarks .tag-value{
    background: var(--tag-sub);
    font-weight: 700;
    color:#111;
    font-size: 6.8pt;
    white-space: normal;
    overflow: visible;
  }

  /* ===================== SIMPLE STICKER (unchanged fixed) ===================== */
  .simple-sticker{
    width: var(--simple-w);
    height: var(--simple-h);
    border:2px solid var(--tag-border);
    border-radius: 1mm;
    background:#fff;
    padding: 2mm;
    display:grid;
    grid-template-columns: 1fr 22mm;
    gap: 2mm;
    align-items:center;
    box-sizing:border-box;
    overflow:hidden;
  }
  .simple-meta{ display:flex; flex-direction:column; gap:1mm; }
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
    width: 18mm; height: 18mm;
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

/**
 * Detailed tag EXACTLY like your screenshot:
 * Property Tag, Item Description, Category, Acquisition Date, Cost, Notes
 * (Flexible wrapping; no clipping)
 */
function buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl }) {
  return `
    <div class="tag-sticker">
      <div class="tag-top">
        <div class="tag-ccc">CCC</div>
        <div class="tag-agency">
          <div class="agency">City Government of Calamba</div>
          <div class="subtitle">Property Inventory Tag</div>
        </div>
        <div class="tag-logo">
          <div class="logo-circle">CG</div>
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
          <div class="tag-code">${escapeHtml(code)}</div>
        </div>
      </div>

      <div class="tag-remarks">
        <div class="tag-label">Notes</div>
        <div class="tag-value">${escapeHtml(notes || '')}</div>
      </div>
    </div>
  `;
}

/**
 * Paging:
 * - SIMPLE: keep fixed pages for clean alignment
 * - DETAILED: no forced per-page; let browser paginate naturally (variable heights)
 */
function buildPagesHtml(layout, cards) {
  if (layout === 'detailed') {
    return `<div class="print-page detailed is-last">${cards.join('')}</div>`;
  }

  // Simple sticker paging
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

/* -------- PRINT -------- */
function printSelected() {
  const layout = ($('#printLayout').val() || 'simple');

  const byCode = {};
  qrItems.forEach(item => {
    if (item.inventory_system_item_code) byCode[item.inventory_system_item_code] = item;
  });

  const selectedCodes = getSelectedCodes();
  if (!selectedCodes.length) return;

  const cards = selectedCodes.map(code => {
    const item = byCode[code] || {};

    const desc = item.item_description || '';
    const acq  = item.acquisition_date || '';
    const cost = (item.item_cost != null && item.item_cost !== '') ? item.item_cost : '';

    const catCode = item.item_category_code || '';
    const catName = item.item_category_name || '';
    const catLine = (catName && catCode) ? `${catName} (${catCode})` : (catName || catCode);

    // Optional notes field if your process returns it; otherwise blank.
    const notes = item.notes || item.note || item.remarks || '';

    const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);

    if (layout === 'simple') {
      return buildSimpleSticker({ code, desc, catLine, qrUrl });
    }
    return buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl });
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

  // Wait for images to load before printing
  try {
    const imgs = printWin.document.images;
    const promises = [];
    for (let i = 0; i < imgs.length; i++) {
      const img = imgs[i];
      if (img.complete) continue;
      promises.push(new Promise(resolve => {
        const done = () => { img.removeEventListener('load', done); img.removeEventListener('error', done); resolve(); };
        img.addEventListener('load', done);
        img.addEventListener('error', done);
      }));
    }
    const timeoutMs = 8000;
    Promise.race([Promise.all(promises), new Promise(r => setTimeout(r, timeoutMs))]).then(() => {
      try { printWin.focus(); } catch(e){}
      printWin.print();
    });
  } catch (e) {
    try { printWin.focus(); } catch(er){}
    printWin.print();
  }
}

/* -------- PDF -------- */
function saveToPdf() {
  const layout = ($('#printLayout').val() || 'simple');

  const byCode = {};
  qrItems.forEach(item => {
    if (item.inventory_system_item_code) byCode[item.inventory_system_item_code] = item;
  });

  const selectedCodes = getSelectedCodes();
  if (!selectedCodes.length) return;

  const cards = selectedCodes.map(code => {
    const item = byCode[code] || {};

    const desc = item.item_description || '';
    const acq  = item.acquisition_date || '';
    const cost = (item.item_cost != null && item.item_cost !== '') ? item.item_cost : '';

    const catCode = item.item_category_code || '';
    const catName = item.item_category_name || '';
    const catLine = (catName && catCode) ? `${catName} (${catCode})` : (catName || catCode);

    const notes = item.notes || item.note || item.remarks || '';

    const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);

    if (layout === 'simple') {
      return buildSimpleSticker({ code, desc, catLine, qrUrl });
    }
    return buildDetailedTag({ code, desc, catLine, acq, cost, notes, qrUrl });
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

/* =========================
   SCANNER (decode -> fill search)
========================= */
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
  setTimeout(() => { oscillator.stop(); ctx.close(); }, 120);
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
    try{ await html5QrcodeScanner.stop(); } catch(e){}
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
        renderList(decodedText);

        $('#selectAllQr').prop('checked', false);
        updateButtons();
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
  try{ await html5QrcodeScanner.stop(); } catch(e){}
  setRunning(false);
}

/* =========================
   INIT
========================= */
$(document).ready(function(){
  loadQrItems();

  $('#qrSearch').on('keyup', function(){
    renderList($(this).val());
    $('#selectAllQr').prop('checked', false);
    updateButtons();
  });

  $(document).on('change', '.qr-check', function(){
    const total = $('.qr-check').length;
    const checked = $('.qr-check:checked').length;
    $('#selectAllQr').prop('checked', total > 0 && total === checked);
    updateButtons();
  });

  $('#selectAllQr').on('change', function(){
    $('.qr-check').prop('checked', $(this).is(':checked'));
    updateButtons();
  });

  $('#btnPrintSelected').on('click', printSelected);
  $('#btnSavePdf').on('click', saveToPdf);

  $('#scanQrModal').on('shown.bs.modal', function(){
    loadCameras();
    setRunning(false);
    document.getElementById('lastScanned').textContent = '—';
    document.getElementById('preview').innerHTML = '';
  });

  $('#btnStart').on('click', startScanner);
  $('#btnStop').on('click', stopScanner);

  $('#cameraSelect').on('change', function(){
    if(isScanning){ stopScanner().then(() => startScanner()); }
  });

  $('#scanQrModal').on('hidden.bs.modal', function(){
    stopScanner();
    document.getElementById('preview').innerHTML = '';
  });
});
</script>
</body>
</html>
