<?php
// AYAKO NA PLS HUHUHUHU
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

// Your page logic here
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        /* ---- Tag preview cards (screen) ---- */
        .ast-preview-tag {
            border: 1.5px solid #000 !important;
            border-radius: 2px;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
        }
        .ast-preview-header {
            background: #fff;
            border-bottom: 1.5px solid #000;
            padding: 16px 12px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ast-header-brand {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ast-header-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            flex: 0 0 auto;
        }
        .ast-preview-body {
            display: flex;
        }
        .ast-preview-details {
            flex: 1;
            border-right: 1.5px solid #000;
            padding: 9px 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            overflow: hidden;
            min-width: 0;
        }
        .ast-preview-cat {
            font-size: 11px;
            font-style: italic;
            color: #555;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-preview-code {
            font-size: 16px;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-preview-prop {
            font-size: 11px;
            font-weight: 700;
            color: #222;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-preview-desc {
            font-size: 12px;
            font-weight: 600;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .ast-preview-meta {
            font-size: 11px;
            color: #444;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ast-preview-divider {
            border: none;
            border-top: 1px dashed #aaa;
            margin: 4px 0;
        }
        .ast-preview-qr {
            width: 170px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 8px 7px;
            gap: 5px;
        }
        .ast-preview-qr img {
            width: 152px;
            height: 152px;
            object-fit: contain;
            border: 1px solid #ccc;
            padding: 4px;
            background: #fff;
        }
        .ast-preview-qr-code {
            font-size: 9px;
            font-weight: 900;
            text-align: center;
            word-break: break-all;
            line-height: 1.2;
            max-width: 155px;
        }
        .ast-preview-body {
            padding-bottom: 6px;
        }
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
                    <i class="bi bi-qr-code"></i>&ensp;QR Codes
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                        <div class="d-flex flex-wrap gap-2 align-items-center" style="max-width:520px;">
                            <div class="input-group" style="width:320px;">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="qrSearch" class="form-control" placeholder="Search Property Tag or description">
                            </div>
                            <select id="qrCategoryFilter" class="form-select form-select-sm" style="min-width:180px;">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="btnDateBatch" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-calendar3"></i>&ensp;Select by Date
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" id="dateBatchMenu" style="max-height:300px;overflow-y:auto;min-width:220px;"></ul>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAllQr">
                                <label class="form-check-label" for="selectAllQr">Select All</label>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" id="btnPrintSelected" disabled>
                                <i class="bi bi-printer"></i>&ensp;Print Selected
                            </button>
                            <button class="btn btn-outline-success btn-sm" id="btnSavePdf" disabled>
                                <i class="bi bi-file-earmark-pdf"></i>&ensp;Save as PDF
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 gap-2">
                        <div id="qrPageInfo" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="mb-0 small text-muted">Per page:</label>
                            <select id="qrPageSize" class="form-select form-select-sm" style="width:auto;">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <div id="qrPagination"></div>
                        </div>
                    </div>
                    <div id="qrMsg" class="alert alert-danger d-none mb-2"></div>
                    <div id="qrList" class="row g-3"></div>
                    <div id="qrEmpty" class="text-muted small" style="display:none;">No QR codes found.</div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_inventory_process.php';
const QR_GENERATOR_URL = BASE_URL + 'admin/modules/tools/qr_image.php';
const LOGO_URL = BASE_URL + 'upload/img/ccc-logo.png';

let qrItems = [];
let qrMsgTimeout = null;
let currentPage = 1;
let pageSize = 20;
let lastFilter = '';
let selectedCodes = new Set(); // tracks selections across pages
let dateFilter = '';           // active date batch filter
let categoryFilter = '';       // active category filter

// Keep the full filtered list for Select All across pages
let filteredItems = [];

function showQrMessage(message) {
    const el = $('#qrMsg');
    if (!message) {
        el.addClass('d-none').text('');
        return;
    }
    el.removeClass('d-none').text(message);
    if (qrMsgTimeout) clearTimeout(qrMsgTimeout);
    qrMsgTimeout = setTimeout(() => {
        el.addClass('d-none').text('');
    }, 4000);
}

function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function formatPeso(value) {
    if (value === null || value === undefined || value === '') return '';
    const num = Number(value);
    if (!isFinite(num)) return '';
    return 'â‚±' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Render cards (latest first). Filter by Property Tag, serial, description, or category.
function renderList(filter = '', resetPage = false) {
    if (resetPage) currentPage = 1;
    lastFilter = filter;
    const list = $('#qrList');
    list.empty();
    const f = filter.trim().toLowerCase();
    filteredItems = qrItems.filter(item => {
        const code   = (item.property_code || '').toLowerCase();
        const desc   = (item.item_description || '').toLowerCase();
        const cat    = (item.item_category_name || '').toLowerCase();
        const serial = (item.serial_number || '').toLowerCase();
        const matchesSearch = f === '' || code.includes(f) || desc.includes(f) || cat.includes(f) || serial.includes(f);
        const matchesDate   = dateFilter === '' || (item.created_at && String(item.created_at).startsWith(dateFilter));
        const matchesCategory = categoryFilter === '' || cat === categoryFilter.toLowerCase();
        return matchesSearch && matchesDate && matchesCategory;
    });

    if (filteredItems.length === 0) {
        $('#qrEmpty').show();
        renderPagination(0, 0);
        updatePrintButton();
        return;
    }
    $('#qrEmpty').hide();

    const totalItems = filteredItems.length;
    const totalPages = Math.ceil(totalItems / pageSize);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * pageSize;
    const pageItems = filteredItems.slice(start, start + pageSize);

    const cards = pageItems.map(item => {
        const code        = item.property_code || '';
        const desc        = item.item_description || '';
        const cat         = item.item_category_name || '';
        const propNum     = item.property_number || '';
        const serial      = item.serial_number || '';
        const cost        = (item.cost_value != null && item.cost_value !== '') ? String(item.cost_value) : '';
        const acqDate     = item.acquisition_date || '';
        const issued      = item.issued_to_name || item.issued_to || '';
        const acctOfficer = item.accountable_officer || '';
        const qrUrl       = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);
        const shortDesc   = desc.length > 65 ? desc.slice(0, 63) + '\u2026' : desc;

        const metaLines = [
            serial      ? `S/N: ${escHtml(serial)}`     : '',
            acqDate     ? `Acq: ${escHtml(acqDate)}`    : '',
            cost        ? `Cost: ${escHtml(formatPeso(cost))}`       : '',
            issued      ? `Issued: ${escHtml(issued)}`  : '',
            acctOfficer ? `AO: ${escHtml(acctOfficer)}` : ''
        ].filter(Boolean);

        const metaHtml = metaLines.length
            ? `<hr class="ast-preview-divider">${metaLines.map(l => `<div class="ast-preview-meta">${l}</div>`).join('')}`
            : '';

        return `
            <div class="col-12 col-sm-6">
                <div class="ast-preview-tag">
                    <div class="ast-preview-header">
                        <div class="ast-header-brand">
                            <img class="ast-header-logo" src="${LOGO_URL}" alt="CCC logo">
                            <span>City College of Calamba</span>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input qr-check" type="checkbox" data-code="${escHtml(code)}" ${selectedCodes.has(code) ? 'checked' : ''}>
                        </div>
                    </div>
                    <div class="ast-preview-body">
                        <div class="ast-preview-details">
                            <div class="ast-preview-cat">${escHtml(cat)}</div>
                            <div class="ast-preview-code">${escHtml(code)}</div>
                            ${propNum ? `<div class="ast-preview-prop">Property No.: ${escHtml(propNum)}</div>` : ''}
                            <div class="ast-preview-desc">${escHtml(shortDesc)}</div>
                            ${metaHtml}
                        </div>
                        <div class="ast-preview-qr">
                            <img src="${qrUrl}" alt="QR ${escHtml(code)}">
                            <div class="ast-preview-qr-code">${escHtml(code)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    list.append(cards.join(''));
    const allSelected = filteredItems.length > 0 && filteredItems.every(i => selectedCodes.has(i.property_code));
    $('#selectAllQr').prop('checked', allSelected);
    renderPagination(totalItems, totalPages);
    updatePrintButton();
}

function renderPagination(total, totalPages) {
    const info = $('#qrPageInfo');
    const nav  = $('#qrPagination');
    if (total === 0) { info.html(''); nav.html(''); return; }

    const start = (currentPage - 1) * pageSize + 1;
    const end   = Math.min(currentPage * pageSize, total);
    const selCount = selectedCodes.size;
    info.html(`Showing ${start}&ndash;${end} of ${total}${selCount > 0 ? ` &bull; <strong>${selCount}</strong> selected` : ''}`);

    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);

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

function updatePrintButton() {
    const count = selectedCodes.size;
    $('#btnPrintSelected').prop('disabled', count === 0);
    $('#btnSavePdf').prop('disabled', count === 0);
}

/* ============================================================
   AST PROPERTY INVENTORY TAG â€” PRINT CSS + BUILDER
   Layout: 2 Ã— 5 grid per Letter page (10 tags), each tag ~50mm tall
   Left: details pane  |  Right: QR pane  |  Bottom: Remarks
   ============================================================ */
const AST_TAG_CSS = `
    @page { size: Letter; margin: 8mm; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        margin: 0;
    }

        /* Wrapper: 2 columns x 5 rows per page, dotted cut guides */
    .ast-print-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .ast-print-table tr {
        page-break-inside: avoid;
    }
    .ast-print-table td {
        border: 1px dotted #999;
        padding: 0.6mm;
        vertical-align: top;
        box-sizing: border-box;
    }
/* ---- Tag shell: fixed 50mm height to guarantee 5 rows/page ---- */
    .ast-tag {
        width: 97mm;
        height: 50mm;
        overflow: hidden;
        border: 1px solid #000;
        border-radius: 1mm;
        background: #fff;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        margin: 0 auto;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    /* ---- Header strip ---- */
    .ast-tag-header {
        background: #fff;
        color: #000;
        text-align: left;
        padding: 2.8mm 2mm;
        font-size: 8.6pt;
        font-weight: 800;
        letter-spacing: 0.3px;
        border-bottom: 1.5px solid #000;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px;
    }
    .ast-tag-header .ast-header-logo {
        width: 20px;
        height: 20px;
    }

    /* ---- Body: details left + QR right ---- */
    .ast-tag-body {
        display: grid;
        grid-template-columns: 1fr 32mm;
        flex: 1;
        min-height: 0;
    }

    /* ---- Details pane ---- */
    .ast-tag-details {
        border-right: 1.5px solid #000;
        padding: 1mm 1.5mm;
        display: flex;
        flex-direction: column;
        gap: 0.3mm;
        box-sizing: border-box;
        overflow: hidden;
    }

    .ast-tag-line {
        font-size: 5.8pt;
        font-weight: 600;
        line-height: 1.2;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .ast-tag-line.tl-code {
        font-size: 7pt;
        font-weight: 800;
    }
    .ast-tag-line.tl-prop {
        font-size: 6.4pt;
        font-weight: 700;
    }
    .ast-tag-line.tl-cat {
        font-size: 5.5pt;
        font-weight: 400;
        font-style: italic;
    }
    .ast-tag-line.tl-desc {
        font-size: 5.8pt;
    }
    .ast-tag-line.tl-meta {
        font-size: 5.3pt;
        font-weight: 400;
        color: #333;
    }
    .ast-tag-divider {
        border: none;
        border-top: 1px dashed #999;
        margin: 0.3mm 0;
    }

    /* ---- QR pane ---- */
    .ast-tag-qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5mm 1.5mm 1mm;
        gap: 0.8mm;
        box-sizing: border-box;
    }
    .ast-tag-qr img {
        width: 26mm;
        height: 26mm;
        object-fit: contain;
        border: 1px solid #ccc;
        padding: 0.5mm;
        background: #fff;
        box-sizing: border-box;
    }
    .ast-tag-qr-code {
        font-size: 4.5pt;
        font-weight: 900;
        text-align: center;
        word-break: break-word;
        line-height: 1.1;
    }


    @media print {
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
`;

function buildAstTag({ code, propNum, serial, category, desc, acqDate, cost, issued, acctOfficer, qrUrl }) {
    const shortDesc = desc.length > 65 ? desc.slice(0, 63) + '\u2026' : desc;
    // Build optional meta lines â€” only include if value exists
    const metaLines = [
        serial     ? `S/N: ${escHtml(serial)}`       : '',
        acqDate    ? `Acq: ${escHtml(acqDate)}`      : '',
        cost       ? `Cost: ${escHtml(formatPeso(cost))}`         : '',
        issued     ? `Issued: ${escHtml(issued)}`     : '',
        acctOfficer? `AO: ${escHtml(acctOfficer)}`   : ''
    ].filter(Boolean);

    return `
            <div class="ast-tag">
            <div class="ast-tag-header">
                <img class="ast-header-logo" src="${LOGO_URL}" alt="CCC logo">
                <span>City College of Calamba</span>
            </div>
            <div class="ast-tag-body">
                <div class="ast-tag-details">
                    <div class="ast-tag-line tl-cat">${escHtml(category)}</div>
                    <div class="ast-tag-line tl-code">${escHtml(code)}</div>
                    ${propNum ? `<div class="ast-tag-line tl-prop">Property No.: ${escHtml(propNum)}</div>` : ''}
                    <div class="ast-tag-line tl-desc">${escHtml(shortDesc)}</div>
                    ${metaLines.length ? '<hr class="ast-tag-divider">' + metaLines.map(l => `<div class="ast-tag-line tl-meta">${l}</div>`).join('') : ''}
                </div>
                <div class="ast-tag-qr">
                    <img src="${qrUrl}" alt="QR ${escHtml(code)}">
                    <div class="ast-tag-qr-code">${escHtml(code)}</div>
                </div>
            </div>
        </div>
    `;
}

function buildPrintTable(tagHtmlList) {
    const cols = 2;
    let rows = '';
    for (let i = 0; i < tagHtmlList.length; i += cols) {
        rows += '<tr>';
        for (let c = 0; c < cols; c++) {
            const idx = i + c;
            rows += `<td>${tagHtmlList[idx] || ''}</td>`;
        }
        rows += '</tr>';
    }
    return `<table class="ast-print-table"><tbody>${rows}</tbody></table>`;
}

function getTagData(code) {
    const byCode = {};
    qrItems.forEach(item => { if (item.property_code) byCode[item.property_code] = item; });
    const item = byCode[code] || {};
    return {
        code,
        propNum:     item.property_number || '',
        serial:      item.serial_number || '',
        category:    item.item_category_name || '',
        desc:        item.item_description || '',
        acqDate:     item.acquisition_date || '',
        cost:        (item.cost_value != null && item.cost_value !== '') ? String(item.cost_value) : '',
        issued:      item.issued_to_name || item.issued_to || '',
        acctOfficer: item.accountable_officer || '',
        qrUrl:       QR_GENERATOR_URL + '?v=' + encodeURIComponent(code)
    };
}

// Load latest items from server (already sorted by created_at DESC in the API)
function loadQrItems() {
    $.post(PROCESS_URL, { action: 'list_items', limit: 500 }, function(res) {
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
    }, 'json').fail(function() {
        qrItems = [];
        populateCategoryFilter();
        renderList('');
        showQrMessage('Server error while loading QR codes.');
    });
}

function populateCategoryFilter() {
    const select = $('#qrCategoryFilter');
    const map = new Map();
    qrItems.forEach(item => {
        const name = (item.item_category_name || '').trim();
        if (name) map.set(name.toLowerCase(), name);
    });
    const categories = Array.from(map.values()).sort((a, b) => a.localeCompare(b));
    const options = ['<option value="">All Categories</option>'];
    categories.forEach(cat => {
        options.push(`<option value="${escHtml(cat)}">${escHtml(cat)}</option>`);
    });
    select.html(options.join(''));
    if (categoryFilter) {
        select.val(categoryFilter);
    }
}

function printSelected() {
    if (selectedCodes.size === 0) return;

    const tags = [...selectedCodes].map(code => buildAstTag(getTagData(code)));
    const itemsHtml = buildPrintTable(tags);

    const printWin = window.open('', '_blank');
    printWin.document.write(`<!DOCTYPE html><html><head><title>AST Property Tags</title><style>${AST_TAG_CSS}</style></head><body>${itemsHtml}</body></html>`);
    printWin.document.close();
    printWin.focus();
    try {
        const imgs = printWin.document.images;
        const promises = [];
        for (let i = 0; i < imgs.length; i++) {
            if (imgs[i].complete) continue;
            promises.push(new Promise(resolve => {
                const done = () => { imgs[i].removeEventListener('load', done); imgs[i].removeEventListener('error', done); resolve(); };
                imgs[i].addEventListener('load', done);
                imgs[i].addEventListener('error', done);
            }));
        }
        Promise.race([Promise.all(promises), new Promise(r => setTimeout(r, 8000))]).then(() => {
            try { printWin.focus(); } catch(e){}
            printWin.print();
        });
    } catch(e) {
        try { printWin.focus(); } catch(er){}
        printWin.print();
    }
}

function saveToPdf() {
    if (selectedCodes.size === 0) return;

    const btn = $('#btnSavePdf');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generatingâ€¦');

    const codes = [...selectedCodes];
    const TAGS_PER_PAGE = 10; // 2 cols Ã— 5 rows on Letter

    const opt = {
        margin: [0.31, 0.31, 0.31, 0.31],
        filename: `ast-property-tags-${new Date().toISOString().split('T')[0]}.pdf`,
        image:       { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 1.5, useCORS: true, allowTaint: true },
        jsPDF:       { unit: 'in', format: 'letter', orientation: 'portrait' },
    };

    // Build a standalone DOM element for one page of tags
    function makePageEl(pageCodes) {
        const tags = pageCodes.map(code => buildAstTag(getTagData(code)));
        const tableHtml = buildPrintTable(tags);
        const el = document.createElement('div');
        el.innerHTML = `<style>${AST_TAG_CSS}</style>${tableHtml}`;
        return el;
    }

    // Slice selected codes into groups of TAGS_PER_PAGE
    const pageSlices = [];
    for (let i = 0; i < codes.length; i += TAGS_PER_PAGE) {
        pageSlices.push(codes.slice(i, i + TAGS_PER_PAGE));
    }

    // Render the first page â€” this creates the jsPDF document
    let worker = html2pdf().set(opt).from(makePageEl(pageSlices[0])).toPdf();

    // For every additional page: explicitly add a blank PDF page, then render
    // the next group into it. Each group is rasterised independently so
    // html2pdf never has to slice a single big canvas across page boundaries.
    for (let i = 1; i < pageSlices.length; i++) {
        const el = makePageEl(pageSlices[i]);
        worker = worker
            .get('pdf').then(pdf => { pdf.addPage('letter', 'portrait'); })
            .from(el).toContainer().toCanvas().toPdf();
    }

    worker.save().then(() => {
        btn.html('<i class="bi bi-file-earmark-pdf"></i>&ensp;Save as PDF');
        updatePrintButton();
    });
}

$(document).ready(function() {
    loadQrItems();

    $('#qrSearch').on('keyup', function() {
        renderList($(this).val(), true);
    });

    $('#qrCategoryFilter').on('change', function() {
        categoryFilter = ($(this).val() || '').trim();
        renderList($('#qrSearch').val() || '', true);
    });

    $(document).on('change', '.qr-check', function() {
        const code = $(this).data('code');
        if ($(this).is(':checked')) {
            selectedCodes.add(code);
        } else {
            selectedCodes.delete(code);
        }
        const allSelected = filteredItems.length > 0 && filteredItems.every(i => selectedCodes.has(i.property_code));
        $('#selectAllQr').prop('checked', allSelected);
        updatePrintButton();
        renderPagination(filteredItems.length, Math.ceil(filteredItems.length / pageSize));
    });

    // Select All: add/remove all filtered items across all pages
    $('#selectAllQr').on('change', function() {
        if ($(this).is(':checked')) {
            filteredItems.forEach(i => { if (i.property_code) selectedCodes.add(i.property_code); });
        } else {
            filteredItems.forEach(i => { if (i.property_code) selectedCodes.delete(i.property_code); });
        }
        renderList(lastFilter);
    });

    // Date batch: populate dropdown when opened
    $('#btnDateBatch').closest('.dropdown').on('show.bs.dropdown', function() {
        const menu = $('#dateBatchMenu');
        menu.empty();
        const dateMap = {};
        qrItems.forEach(item => {
            if (!item.created_at || !item.property_code) return;
            const d = String(item.created_at).split(' ')[0];
            if (!dateMap[d]) dateMap[d] = [];
            dateMap[d].push(item.property_code);
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
            const label = new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            menu.append(`<li><a class="dropdown-item small" href="#" data-date="${escHtml(d)}">${escHtml(label)} <span class="badge bg-secondary ms-1">${count}</span></a></li>`);
        });
    });

    // Date batch: select all items from a clicked date
    $(document).on('click', '#dateBatchMenu a', function(e) {
        e.preventDefault();
        const date = $(this).data('date');
        if (date === '__clear__') {
            selectedCodes.clear();
            dateFilter = '';
            $('#btnDateBatch').removeClass('btn-warning').addClass('btn-outline-secondary').html('<i class="bi bi-calendar3"></i>&ensp;Select by Date');
        } else {
            dateFilter = date;
            // auto-select all items of that date
            qrItems.forEach(item => {
                if (item.created_at && String(item.created_at).startsWith(date) && item.property_code) {
                    selectedCodes.add(item.property_code);
                }
            });
            const label = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            $('#btnDateBatch').removeClass('btn-outline-secondary').addClass('btn-warning').html(`<i class="bi bi-calendar-check"></i>&ensp;${label} <i class="bi bi-x ms-1" id="btnClearDate"></i>`);
        }
        renderList(lastFilter, true);
        updatePrintButton();
    });

    // Clear date filter via the Ã— on the button
    $(document).on('click', '#btnClearDate', function(e) {
        e.stopPropagation();
        dateFilter = '';
        selectedCodes.clear();
        $('#btnDateBatch').removeClass('btn-warning').addClass('btn-outline-secondary').html('<i class="bi bi-calendar3"></i>&ensp;Select by Date');
        renderList(lastFilter, true);
        updatePrintButton();
    });

    // Pagination buttons (Tabulator-style)
    $(document).on('click', '#qrPagination button[data-page]', function() {
        const page = parseInt($(this).data('page'));
        if (!page || page < 1) return;
        currentPage = page;
        renderList(lastFilter);
    });

    $('#qrPageSize').on('change', function() {
        pageSize = parseInt($(this).val());
        renderList(lastFilter, true);
    });

    $('#btnPrintSelected').on('click', printSelected);
    $('#btnSavePdf').on('click', saveToPdf);
});
</script>
</html>



