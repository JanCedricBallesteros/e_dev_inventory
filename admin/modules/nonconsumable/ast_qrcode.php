<?php
// REMINDER, USE THE ALL FUNC CSM_INVENTORY TO REPLACE THIS FRONTEND TO UTILIZE THE PROCESSING IN ONE FILE
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
                        <div class="input-group" style="max-width:320px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="qrSearch" class="form-control" placeholder="Search property code or description">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check">
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

let qrItems = [];
let qrMsgTimeout = null;

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

// Render cards (latest first). Filter by property code or description.
function renderList(filter = '') {
    const list = $('#qrList');
    list.empty();
    const f = filter.trim().toLowerCase();
    const filtered = qrItems.filter(item => {
        const code = (item.property_code || '').toLowerCase();
        const desc = (item.item_description || '').toLowerCase();
        return f === '' || code.includes(f) || desc.includes(f);
    });

    if (filtered.length === 0) {
        $('#qrEmpty').show();
        return;
    }
    $('#qrEmpty').hide();

    const cards = filtered.map(item => {
        const code = item.property_code || '';
        const desc = item.item_description || '';
        const category = item.item_category_name || '';
        const qrUrl = item.qr_image_url || (QR_GENERATOR_URL + '?v=' + encodeURIComponent(code));
        return `
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="border rounded p-2 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check">
                            <input class="form-check-input qr-check" type="checkbox" data-code="${code}">
                        </div>
                        <span class="badge bg-light text-dark border">${code}</span>
                    </div>
                    <div class="text-center">
                        <img src="${qrUrl}" alt="QR ${code}" style="max-width:140px;width:100%;height:auto;border:1px solid #dee2e6;border-radius:8px;background:#fff;padding:6px;">
                    </div>
                    <div class="small mt-2 fw-semibold">${desc}</div>
                    <div class="small text-muted">${category}</div>
                </div>
            </div>
        `;
    });
    list.append(cards.join(''));
}

function updatePrintButton() {
    const selectedCount = $('.qr-check:checked').length;
    $('#btnPrintSelected').prop('disabled', selectedCount === 0);
    $('#btnSavePdf').prop('disabled', selectedCount === 0);
}

// Load latest items from server (already sorted by created_at DESC in the API)
function loadQrItems() {
    $.post(PROCESS_URL, { action: 'list_items', limit: 500 }, function(res) {
        if (res && res.success) {
            qrItems = res.data || [];
            renderList($('#qrSearch').val() || '');
            showQrMessage('');
        } else {
            qrItems = [];
            renderList('');
            showQrMessage(res && res.message ? res.message : 'Failed to load QR codes.');
        }
    }, 'json').fail(function() {
        qrItems = [];
        renderList('');
        showQrMessage('Server error while loading QR codes.');
    });
}

function printSelected() {
    // Build a lookup so we can attach description/category to the printed card
    const byCode = {};
    qrItems.forEach(item => {
        if (item.property_code) byCode[item.property_code] = item;
    });

    const selectedCodes = $('.qr-check:checked').map(function() {
        return $(this).data('code');
    }).get();
    if (selectedCodes.length === 0) return;

    const cards = selectedCodes.map(code => {
        const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);
        const item = byCode[code] || {};
        const desc = item.item_description || '';
        const category = item.item_category_name || '';
        return `
            <div class="print-card">
                <div class="print-code">${code}</div>
                <div class="print-desc">${desc}</div>
                <div class="print-cat">${category}</div>
                <img src="${qrUrl}" alt="QR ${code}">
            </div>
        `;
    });

    // Group into pages of 9 (3x3) for printing
    const pages = [];
    for (let i = 0; i < cards.length; i += 9) {
        const pageCards = cards.slice(i, i + 9).join('');
        const isLast = (i + 9) >= cards.length;
        pages.push(`<div class="print-page${isLast ? ' is-last' : ''}">${pageCards}</div>`);
    }
    const itemsHtml = pages.join('');

    const printWin = window.open('', '_blank');
    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Codes</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .print-page { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
                .print-page.is-last { margin-bottom: 0; }
                .print-card { border: 1px solid #ddd; border-radius: 8px; padding: 10px; text-align: center; }
                .print-card img { width: 160px; height: auto; }
                .print-code { font-size: 12px; margin-bottom: 8px; font-weight: 600; }
                .print-desc { font-size: 11px; margin-bottom: 4px; }
                .print-cat { font-size: 11px; color: #555; margin-bottom: 8px; }
                @media print {
                    body { margin: 0; }
                    .print-page { page-break-after: always; break-after: page; }
                    .print-page.is-last { page-break-after: auto; break-after: auto; }
                }
            </style>
        </head>
        <body>
            ${itemsHtml}
        </body>
        </html>
    `);
    printWin.document.close();
    printWin.focus();
    // Wait for images to load in the print window before printing to ensure they appear
    try {
        const imgs = printWin.document.images;
        const loadPromises = [];

        for (let i = 0; i < imgs.length; i++) {
            const img = imgs[i];
            if (img.complete) continue;
            loadPromises.push(new Promise((resolve) => {
                const onFinish = () => { img.removeEventListener('load', onFinish); img.removeEventListener('error', onFinish); resolve(); };
                img.addEventListener('load', onFinish);
                img.addEventListener('error', onFinish);
            }));
        }

        if (loadPromises.length === 0) {
            printWin.print();
        } else {
            // Safety timeout in case some images never load
            const timeoutMs = 5000;
            Promise.race([
                Promise.all(loadPromises),
                new Promise((resolve) => setTimeout(resolve, timeoutMs))
            ]).then(() => {
                try { printWin.focus(); } catch (e) {}
                printWin.print();
            });
        }
    } catch (e) {
        // If anything goes wrong, fall back to immediate print
        try { printWin.focus(); } catch (er) {}
        printWin.print();
    }
}

function saveToPdf() {
    // Build a lookup so we can attach description/category to the PDF
    const byCode = {};
    qrItems.forEach(item => {
        if (item.property_code) byCode[item.property_code] = item;
    });

    const selectedCodes = $('.qr-check:checked').map(function() {
        return $(this).data('code');
    }).get();
    if (selectedCodes.length === 0) return;

    const cards = selectedCodes.map(code => {
        const qrUrl = QR_GENERATOR_URL + '?v=' + encodeURIComponent(code);
        const item = byCode[code] || {};
        const desc = item.item_description || '';
        const category = item.item_category_name || '';
        return `
            <div class="pdf-card">
                <div class="pdf-code">${code}</div>
                <div class="pdf-desc">${desc}</div>
                <div class="pdf-cat">${category}</div>
                <img src="${qrUrl}" alt="QR ${code}">
            </div>
        `;
    });

    // Group into pages of 9 (3x3) for PDF
    const pages = [];
    for (let i = 0; i < cards.length; i += 9) {
        const pageCards = cards.slice(i, i + 9).join('');
        const isLast = (i + 9) >= cards.length;
        pages.push(`<div class="pdf-page ${isLast ? 'pdf-last-page' : ''}">${pageCards}</div>`);
    }
    const itemsHtml = pages.join('');

    // Create temporary element for PDF generation
    const element = document.createElement('div');
    element.innerHTML = `
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .pdf-page { 
                display: grid; 
                grid-template-columns: repeat(3, 1fr); 
                gap: 16px; 
                margin-bottom: 20px;
                min-height: 100vh;
                page-break-after: always;
            }
            .pdf-last-page { 
                page-break-after: avoid;
                min-height: auto;
            }
            .pdf-card { 
                border: 1px solid #ddd; 
                border-radius: 8px; 
                padding: 10px; 
                text-align: center; 
                height: fit-content;
            }
            .pdf-card img { 
                width: 150px; 
                height: 150px; 
                object-fit: contain;
            }
            .pdf-code { font-size: 14px; margin-bottom: 8px; font-weight: 600; }
            .pdf-desc { font-size: 12px; margin-bottom: 4px; }
            .pdf-cat { font-size: 12px; color: #555; margin-bottom: 8px; }
        </style>
        ${itemsHtml}
    `;

    const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: `qr-codes-${new Date().toISOString().split('T')[0]}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2,
            useCORS: true,
            allowTaint: true
        },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };

    html2pdf().set(opt).from(element).save();
}

$(document).ready(function() {
    loadQrItems();

    $('#qrSearch').on('keyup', function() {
        renderList($(this).val());
        $('#selectAllQr').prop('checked', false);
        updatePrintButton();
    });

    $(document).on('change', '.qr-check', function() {
        const total = $('.qr-check').length;
        const checked = $('.qr-check:checked').length;
        $('#selectAllQr').prop('checked', total > 0 && total === checked);
        updatePrintButton();
    });

    $('#selectAllQr').on('change', function() {
        $('.qr-check').prop('checked', $(this).is(':checked'));
        updatePrintButton();
    });

    $('#btnPrintSelected').on('click', printSelected);
    $('#btnSavePdf').on('click', saveToPdf);
});
</script>
</html>
