<?php
// csm_available_items.php
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

/* ---------- Helpers ---------- */
function getAllCategories() {
    $sql = "SELECT category_id, item_category_code, item_category_name
            FROM csm_inventory_category
            ORDER BY item_category_name ASC";
    $result = call_mysql_query($sql);
    $rows = [];
    if ($result) {
        while ($r = call_mysql_fetch_array($result)) $rows[] = $r;
    }
    return $rows;
}

$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="https://unpkg.com/tabulator-tables@4.7.2/dist/css/tabulator.min.css" rel="stylesheet">
    <style>
        .header-actions{
            display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
        }
        @media (max-width:576px){
            .header-actions{ flex-wrap:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; padding-bottom:.25rem; }
            .header-actions .btn{ white-space:nowrap; flex:0 0 auto; }
        }

        .inv-thumb-wrap{
            width:56px;height:56px;border:1px solid #dee2e6;border-radius:10px;background:#fff;
            display:flex;align-items:center;justify-content:center;overflow:hidden;
        }
        .inv-thumb-wrap img{ width:56px;height:56px;object-fit:cover;display:block; }
        .inv-thumb-fallback{ font-size:24px; line-height:1; color:#6c757d; }
        .inv-thumb-click{ cursor:pointer; display:inline-flex; }

        .view-img-wrap{
            width:100%; background:#f8f9fa; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden;
        }
        .view-img-wrap img{ width:100%; height:auto; display:block; }

        .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

        /* QR thumb */
        .qr-thumb{
            height:56px;width:56px;object-fit:contain;
            border:1px solid #dee2e6;border-radius:10px;background:#fff;padding:4px;
            cursor:pointer;
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

            <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <i class="bi bi-check-circle"></i>&ensp;Consumable Inventory Controls
                </div>

                <div class="header-actions">
                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#scanQrModal">
                        <i class="bi bi-upc-scan"></i>&ensp;Scan QR
                    </button>
                    <button class="btn btn-light btn-sm" id="btnRefreshAll">
                        <i class="bi bi-arrow-clockwise"></i>&ensp;Refresh
                    </button>
                </div>
            </div>

            <div class="card-body mt-3 bg-white">

                <!-- TABS -->
                <ul class="nav nav-tabs" id="invTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-additem" data-bs-toggle="tab" data-bs-target="#pane-additem" type="button" role="tab">
                            <i class="bi bi-plus-circle"></i>&ensp;Add New Item
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-addqty" data-bs-toggle="tab" data-bs-target="#pane-addqty" type="button" role="tab">
                            <i class="bi bi-box-arrow-in-down"></i>&ensp;Add Quantity
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-setavail" data-bs-toggle="tab" data-bs-target="#pane-setavail" type="button" role="tab">
                            <i class="bi bi-sliders"></i>&ensp;Set Available Item
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <!-- ===================== ADD NEW ITEM ===================== -->
                    <div class="tab-pane fade show active" id="pane-additem" role="tabpanel">
                        <div class="row g-3">

                            <div class="col-lg-5">
                                <div class="border rounded p-3">
                                    <div class="fw-semibold mb-2"><i class="bi bi-plus-circle"></i>&ensp;Add New Item</div>

                                    <form id="formAddNewItem">
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold">Item Category</label>
                                            <select class="form-select" name="item_category_code" required>
                                                <option value="">Select Category</option>
                                                <?php foreach($categories as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat['item_category_code']) ?>">
                                                        <?= htmlspecialchars($cat['item_category_name']) ?> (<?= htmlspecialchars($cat['item_category_code']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-semibold">Item Code (Auto-generated)</label>
                                            <input type="text" class="form-control mono" value="CSM-[CATEGORY]-0001" disabled>
                                            <div class="form-text">
                                                Actual will be generated as: <b>CSM-[Item Category Code]-0001</b>, next numbers auto increment per category.
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-semibold">Item Description</label>
                                            <textarea class="form-control" name="item_description" required placeholder="Enter full item description (model/size/details)"></textarea>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Quantity</label>
                                                <input type="number" class="form-control" name="unit_quantity" min="0" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Critical Level</label>
                                                <input type="number" class="form-control" name="unit_crit_level" min="0" required>
                                            </div>
                                        </div>

                                        <div class="row g-2 mt-1">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Unit</label>
                                                <input type="text" class="form-control" name="unit" placeholder="pcs / box / ream / set">
                                                <div class="form-text">Saved only if your DB has <code>csm_inventory.unit</code>.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Cost Value (optional)</label>
                                                <input type="number" class="form-control" name="item_cost" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <label class="form-label fw-semibold">Source of Fund (optional)</label>
                                            <input type="text" class="form-control" name="source_of_funds" placeholder="General Fund / Donation / LGU / etc">
                                        </div>

                                        <div class="alert alert-info mt-2 mb-2">
                                            <i class="bi bi-info-circle"></i>
                                            <b>Available quantity</b> will start equal to Quantity.
                                            Acquisition Date and Last Updated are set to <b>today</b>.
                                        </div>

                                        <button class="btn btn-primary w-100" type="submit" id="btnAddNewItem">
                                            <i class="bi bi-save"></i> Add Item
                                        </button>
                                        <div id="addNewItemMsg" class="mt-2"></div>
                                    </form>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="border rounded p-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="fw-semibold"><i class="bi bi-clock-history"></i>&ensp;Recently Added Items</div>
                                        <button class="btn btn-outline-primary btn-sm" id="btnReloadRecent">
                                            <i class="bi bi-arrow-clockwise"></i> Reload
                                        </button>
                                    </div>
                                    <div class="small text-muted mb-2">Shows the most recent inventory records.</div>
                                    <div id="tableRecentAdded"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================== ADD QUANTITY ===================== -->
                    <div class="tab-pane fade" id="pane-addqty" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="border rounded p-3">
                                    <div class="fw-semibold mb-2"><i class="bi bi-box-arrow-in-down"></i>&ensp;Add Quantity to Existing Item</div>

                                    <div class="mb-2">
                                        <label class="form-label fw-semibold">Scan QR / Search Code</label>
                                        <div class="input-group">
                                            <input type="text" id="addQtySearchCode" class="form-control mono" placeholder="Paste/scan item code here">
                                            <button class="btn btn-outline-primary" type="button" id="btnFindItem">
                                                <i class="bi bi-search"></i> Find
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Accepts full code like <code>CSM-0002-0001</code>.
                                        </div>
                                    </div>

                                    <div id="foundItemCard" class="border rounded p-2 mb-2" style="display:none;">
                                        <div class="d-flex gap-2">
                                            <div class="inv-thumb-wrap" id="foundItemImgWrap">
                                                <i class="bi bi-image inv-thumb-fallback"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mono" id="foundItemCode">—</div>
                                                <div class="small" id="foundItemDesc">—</div>
                                                <div class="small text-muted" id="foundItemMeta">—</div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="formAddQty" style="display:none;">
                                        <input type="hidden" name="inventory_id" id="addQtyInventoryId">

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Quantity (add)</label>
                                                <input type="number" class="form-control" name="add_quantity" min="1" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Unit</label>
                                                <input type="text" class="form-control" name="unit" placeholder="pcs / box / ream / set">
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <label class="form-label fw-semibold">Source of Fund (optional)</label>
                                            <input type="text" class="form-control" name="source_of_funds" id="addQtySourceFunds" placeholder="Defaults to existing if blank">
                                        </div>

                                        <div class="mt-2">
                                            <label class="form-label fw-semibold">Cost Value (optional)</label>
                                            <input type="number" class="form-control" name="item_cost" id="addQtyCost" step="0.01" min="0" placeholder="Defaults to existing if blank">
                                        </div>

                                        <button class="btn btn-primary w-100 mt-3" type="submit" id="btnSubmitAddQty">
                                            <i class="bi bi-save"></i> Save Quantity
                                        </button>

                                        <div id="addQtyMsg" class="mt-2"></div>
                                    </form>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="border rounded p-3">
                                    <div class="fw-semibold mb-2"><i class="bi bi-list-ul"></i>&ensp;Quick Search (All Items)</div>
                                    <div class="small text-muted mb-2">Search and click an item to load it into “Add Quantity”.</div>
                                    <div id="tableAllItems"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================== SET AVAILABLE ITEM ===================== -->
                    <div class="tab-pane fade" id="pane-setavail" role="tabpanel">
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="fw-semibold"><i class="bi bi-sliders"></i>&ensp;Set Available Item (Requestor Display)</div>
                                <button class="btn btn-outline-primary btn-sm" id="btnReloadAvail">
                                    <i class="bi bi-arrow-clockwise"></i> Reload
                                </button>
                            </div>

                            <div class="alert alert-warning mt-2 mb-2">
                                <i class="bi bi-shield-exclamation"></i>
                                Restriction: <b>Remaining quantity must not be less than the critical level</b>.
                                This page prevents setting available below critical.
                            </div>

                            <div id="tableSetAvailable"></div>
                            <div id="setAvailMsg" class="mt-2"></div>
                        </div>
                    </div>

                </div><!-- tab-content -->

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
                <h5 class="modal-title fw-semibold"><i class="bi bi-image"></i>&ensp;Category Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2" id="viewInvImageTitle">—</div>
                <div id="viewInvImageBodyMsg" class="mb-2"></div>
                <div class="view-img-wrap">
                    <img id="viewInvImageImg" src="" alt="Image">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- QR PREVIEW MODAL -->
<div class="modal fade" id="qrPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code"></i>&ensp;QR Code Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div class="small text-muted mb-2" id="qrPreviewCode">—</div>
        <img id="qrPreviewImg" src="" alt="QR"
             style="max-width:320px;width:100%;height:auto;border:1px solid #dee2e6;border-radius:12px;background:#fff;padding:10px;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- SCAN QR -->
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
                <div id="preview-wrapper" style="width:100%;max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:4/3;">
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

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/tabulator-tables@4.7.2/dist/js/tabulator.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
const PROCESS_URL = 'process/csm_available_items_process.php';
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;

function escHtml(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

/**
 * IMPORTANT:
 * This matches your working page:
 * - Backend returns display_image like "upload/category/xxx.jpg"
 * - We simply prefix BASE_URL
 */
function absUrl(path){
    const base = String(BASE_URL || '');
    const sep = base.endsWith('/') ? '' : '/';
    path = String(path || '').replace(/^\/+/, '');
    return base + sep + path;
}

function toastMsg(el, html){ $(el).html(html); }

function openImgModal(row){
    const rel = row.display_image || '';
    if(!rel) return;
    const src = absUrl(rel);
    $('#viewInvImageTitle').text((row.inventory_system_item_code || '') + ' — ' + String(row.item_description || '').slice(0,80));
    $('#viewInvImageBodyMsg').html('');
    $('#viewInvImageImg').off('error').attr('src', src).on('error', function(){
        $(this).attr('src','');
        $('#viewInvImageBodyMsg').html('<div class="alert alert-warning mb-0">Image not found.</div>');
    });
    $('#viewInvImageModal').modal('show');
}

function openQrModal(code){
    if(!code) return;
    const imgUrl = "../tools/qr_image.php?v=" + encodeURIComponent(code);
    $('#qrPreviewCode').text(code);
    $('#qrPreviewImg').attr('src', imgUrl);
    $('#qrPreviewModal').modal('show');
}

/* ========== Tabulator Tables (pagination like manage_inventory) ========== */
const PAGINATION_SIZE_SELECTOR = [20,100,500,1000,true];

let tableRecent = new Tabulator("#tableRecentAdded", {
    layout:"fitColumns",
    height:"520px",
    pagination:"local",
    paginationSize:20,
    paginationSizeSelector:PAGINATION_SIZE_SELECTOR,
    movableColumns:true,
    columns:[
        {title:"Image", field:"display_image", width:80, hozAlign:"center", headerSort:false,
            formatter:(cell)=>{
                const d = cell.getRow().getData();
                const rel = d.display_image || '';
                const url = rel ? absUrl(rel) : '';
                if(!url) return `<div class="inv-thumb-wrap"><i class="bi bi-image inv-thumb-fallback"></i></div>`;
                return `<div class="inv-thumb-click"><div class="inv-thumb-wrap"><img src="${escHtml(url)}" onerror="this.remove();this.parentNode.innerHTML='<i class=&quot;bi bi-image inv-thumb-fallback&quot;></i>';"></div></div>`;
            },
            cellClick:(e, cell)=> openImgModal(cell.getRow().getData())
        },
        {title:"Item Code", field:"inventory_system_item_code", minWidth:170},
        {title:"Category", field:"item_category_name", minWidth:220},
        {title:"Description", field:"item_description", minWidth:250, formatter:"textarea"},
        {title:"Qty", field:"current_unit_quantity", hozAlign:"right", width:90},
        {title:"Crit", field:"unit_crit_level", hozAlign:"right", width:90},
        {title:"QR", field:"inventory_system_item_code", width:90, hozAlign:"center", headerSort:false,
          formatter:(cell)=>{
            const code = cell.getValue();
            if(!code) return '';
            const u = "../tools/qr_image.php?v=" + encodeURIComponent(code);
            return `<img class="qr-thumb" src="${u}" data-code="${escHtml(code)}" alt="QR">`;
          },
          cellClick:(e, cell)=> openQrModal(cell.getRow().getData().inventory_system_item_code)
        },
        {title:"Created", field:"created_at", minWidth:160},
    ]
});

let tableAll = new Tabulator("#tableAllItems", {
    layout:"fitColumns",
    height:"520px",
    pagination:"local",
    paginationSize:20,
    paginationSizeSelector:PAGINATION_SIZE_SELECTOR,
    movableColumns:true,
    columns:[
        {title:"Item Code", field:"inventory_system_item_code", minWidth:170, headerFilter:"input"},
        {title:"Category", field:"item_category_name", minWidth:220, headerFilter:"input"},
        {title:"Description", field:"item_description", minWidth:250, headerFilter:"input", formatter:"textarea"},
        {title:"Current Qty", field:"current_unit_quantity", hozAlign:"right", width:120, headerFilter:"number"},
        {title:"Crit", field:"unit_crit_level", hozAlign:"right", width:90},
        {title:"QR", field:"inventory_system_item_code", width:90, hozAlign:"center", headerSort:false,
          formatter:(cell)=>{
            const code = cell.getValue();
            if(!code) return '';
            const u = "../tools/qr_image.php?v=" + encodeURIComponent(code);
            return `<img class="qr-thumb" src="${u}" data-code="${escHtml(code)}" alt="QR">`;
          },
          cellClick:(e, cell)=> openQrModal(cell.getRow().getData().inventory_system_item_code)
        },
        {title:"Action", headerSort:false, width:110, hozAlign:"center",
            formatter:()=> `<button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-right-circle"></i> Select</button>`,
            cellClick:(e, cell)=>{
                const d = cell.getRow().getData();
                loadFoundItem(d);
                document.getElementById("addQtySearchCode").value = d.inventory_system_item_code || '';
                $('#tab-addqty').trigger('click');
            }
        }
    ]
});

let tableAvail = new Tabulator("#tableSetAvailable", {
    layout:"fitColumns",
    height:"600px",
    pagination:"local",
    paginationSize:20,
    paginationSizeSelector:PAGINATION_SIZE_SELECTOR,
    movableColumns:true,
    columns:[
        {title:"Image", field:"display_image", width:80, hozAlign:"center", headerSort:false,
            formatter:(cell)=>{
                const d = cell.getRow().getData();
                const rel = d.display_image || '';
                const url = rel ? absUrl(rel) : '';
                if(!url) return `<div class="inv-thumb-wrap"><i class="bi bi-image inv-thumb-fallback"></i></div>`;
                return `<div class="inv-thumb-click"><div class="inv-thumb-wrap"><img src="${escHtml(url)}" onerror="this.remove();this.parentNode.innerHTML='<i class=&quot;bi bi-image inv-thumb-fallback&quot;></i>';"></div></div>`;
            },
            cellClick:(e, cell)=> openImgModal(cell.getRow().getData())
        },
        {title:"Item Code", field:"inventory_system_item_code", minWidth:170, headerFilter:"input"},
        {title:"Category", field:"item_category_name", minWidth:220, headerFilter:"input"},
        {title:"Description", field:"item_description", minWidth:280, headerFilter:"input", formatter:"textarea"},
        {title:"Remaining", field:"current_unit_quantity", hozAlign:"right", width:110},
        {title:"Critical", field:"unit_crit_level", hozAlign:"right", width:110},
        {title:"Status", field:"status", width:140, headerFilter:"input"},
        {title:"QR", field:"inventory_system_item_code", width:90, hozAlign:"center", headerSort:false,
          formatter:(cell)=>{
            const code = cell.getValue();
            if(!code) return '';
            const u = "../tools/qr_image.php?v=" + encodeURIComponent(code);
            return `<img class="qr-thumb" src="${u}" data-code="${escHtml(code)}" alt="QR">`;
          },
          cellClick:(e, cell)=> openQrModal(cell.getRow().getData().inventory_system_item_code)
        },
        {
            title:"Set Available Qty",
            field:"current_unit_quantity",
            width:170,
            hozAlign:"center",
            headerSort:false,
            formatter:(cell)=>{
                const d = cell.getRow().getData();
                const remain = parseInt(d.current_unit_quantity||0,10);
                const crit = parseInt(d.unit_crit_level||0,10);
                const disabled = (remain < crit) ? 'disabled' : '';
                return `
                  <div class="d-flex gap-1 justify-content-center">
                    <input class="form-control form-control-sm avail-input" type="number" min="${crit}" value="${remain}" style="max-width:90px" ${disabled}>
                    <button class="btn btn-sm btn-primary avail-save" ${disabled}><i class="bi bi-save"></i></button>
                  </div>
                `;
            },
            cellClick:(e, cell)=>{
                const target = e.target;
                const row = cell.getRow();
                const d = row.getData();

                if(!target.classList.contains('avail-save')) return;

                const wrap = target.closest('div');
                const input = wrap.querySelector('.avail-input');
                const newVal = parseInt(input.value || '0', 10);

                $.ajax({
                    url: PROCESS_URL,
                    type: 'POST',
                    dataType:'json',
                    data: { action:'set_available_qty', inventory_id: d.inventory_id, current_unit_quantity: newVal },
                    success: function(res){
                        if(res && res.success){
                            toastMsg('#setAvailMsg','<div class="alert alert-success mb-0">Saved.</div>');
                            reloadAllTables();
                        }else{
                            toastMsg('#setAvailMsg','<div class="alert alert-danger mb-0">'+escHtml(res.message || 'Failed')+'</div>');
                        }
                    },
                    error: function(xhr){
                        toastMsg('#setAvailMsg','<div class="alert alert-danger mb-0">Server error.</div>');
                        console.error(xhr.responseText);
                    }
                });
            }
        }
    ]
});

/* ========== Loaders ========== */
function reloadAllTables(){
    loadRecent();
    loadAllItems();
    loadAvail();
}
function loadRecent(){
    $.ajax({
        url: PROCESS_URL,
        type:'POST',
        dataType:'json',
        data:{ action:'list_recent_added' },
        success:(res)=>{ if(res && res.success) tableRecent.setData(res.data); }
    });
}
function loadAllItems(){
    $.ajax({
        url: PROCESS_URL,
        type:'POST',
        dataType:'json',
        data:{ action:'list_all_items' },
        success:(res)=>{ if(res && res.success) tableAll.setData(res.data); }
    });
}
function loadAvail(){
    $.ajax({
        url: PROCESS_URL,
        type:'POST',
        dataType:'json',
        data:{ action:'list_set_available' },
        success:(res)=>{ if(res && res.success) tableAvail.setData(res.data); }
    });
}

/* ========== Add New Item ========== */
$('#formAddNewItem').on('submit', function(e){
    e.preventDefault();
    $('#addNewItemMsg').html('');
    $('#btnAddNewItem').prop('disabled', true).text('Saving...');

    $.ajax({
        url: PROCESS_URL,
        type:'POST',
        dataType:'json',
        data: $(this).serialize() + '&action=add_new_item',
        success: function(res){
            if(res && res.success){
                $('#addNewItemMsg').html('<div class="alert alert-success">Added! New Code: <span class="mono">'+escHtml(res.inventory_system_item_code)+'</span></div>');
                $('#formAddNewItem')[0].reset();
                reloadAllTables();
            }else{
                $('#addNewItemMsg').html('<div class="alert alert-danger">'+escHtml(res.message || 'Failed')+'</div>');
            }
        },
        error:function(xhr){
            $('#addNewItemMsg').html('<div class="alert alert-danger">Server error.</div>');
            console.error(xhr.responseText);
        },
        complete:function(){
            $('#btnAddNewItem').prop('disabled', false).html('<i class="bi bi-save"></i> Add Item');
        }
    });
});

/* ========== Add Qty (Find) ========== */
function loadFoundItem(d){
    if(!d){ return; }

    $('#foundItemCard').show();
    $('#formAddQty').show();

    $('#addQtyInventoryId').val(d.inventory_id);
    $('#foundItemCode').text(d.inventory_system_item_code || '—');
    $('#foundItemDesc').text(d.item_description || '—');

    const remain = parseInt(d.current_unit_quantity||0,10);
    const crit = parseInt(d.unit_crit_level||0,10);
    $('#foundItemMeta').html(`Current: <b>${remain}</b> | Critical: <b>${crit}</b>`);

    $('#addQtySourceFunds').val(d.source_of_funds || '');
    $('#addQtyCost').val(d.item_cost || '');

    const rel = d.display_image || '';
    const url = rel ? absUrl(rel) : '';
    if(url){
        $('#foundItemImgWrap').html(`<img src="${escHtml(url)}" onerror="this.remove();this.parentNode.innerHTML='<i class=&quot;bi bi-image inv-thumb-fallback&quot;></i>';">`);
    }else{
        $('#foundItemImgWrap').html(`<i class="bi bi-image inv-thumb-fallback"></i>`);
    }
}

$('#btnFindItem').on('click', function(){
    const code = ($('#addQtySearchCode').val() || '').trim();
    if(!code){
        $('#addQtyMsg').html('<div class="alert alert-warning">Enter or scan an item code.</div>');
        return;
    }

    $('#addQtyMsg').html('');

    $.ajax({
        url: PROCESS_URL,
        type:'POST',
        dataType:'json',
        data:{ action:'find_item_by_code', inventory_system_item_code: code },
        success: function(res){
            if(res && res.success){
                loadFoundItem(res.data);
                $('#addQtyMsg').html('<div class="alert alert-success">Item loaded.</div>');
            }else{
                $('#foundItemCard').hide();
                $('#formAddQty').hide();
                $('#addQtyMsg').html('<div class="alert alert-danger">'+escHtml(res.message || 'Not found')+'</div>');
            }
        },
        error:function(xhr){
            $('#addQtyMsg').html('<div class="alert alert-danger">Server error.</div>');
            console.error(xhr.responseText);
        }
    });
});

$('#formAddQty').on('submit', function(e){
    e.preventDefault();
    $('#addQtyMsg').html('');
    $('#btnSubmitAddQty').prop('disabled', true).text('Saving...');

    $.ajax({
        url: PROCESS_URL,
        type:'POST',
        dataType:'json',
        data: $(this).serialize() + '&action=add_quantity',
        success: function(res){
            if(res && res.success){
                $('#addQtyMsg').html('<div class="alert alert-success">Quantity added successfully.</div>');
                $('#formAddQty')[0].reset();
                $('#foundItemCard').hide();
                $('#formAddQty').hide();
                reloadAllTables();
            }else{
                $('#addQtyMsg').html('<div class="alert alert-danger">'+escHtml(res.message || 'Failed')+'</div>');
            }
        },
        error:function(xhr){
            $('#addQtyMsg').html('<div class="alert alert-danger">Server error.</div>');
            console.error(xhr.responseText);
        },
        complete:function(){
            $('#btnSubmitAddQty').prop('disabled', false).html('<i class="bi bi-save"></i> Save Quantity');
        }
    });
});

/* ========== Buttons ========== */
$('#btnReloadRecent').on('click', loadRecent);
$('#btnReloadAvail').on('click', loadAvail);
$('#btnRefreshAll').on('click', reloadAllTables);

document.addEventListener("DOMContentLoaded", function(){
    reloadAllTables();
});

/* ===================== QR Scanner (fills code box) ===================== */
function showError(msg) {
    const errEl = document.getElementById('scanError');
    const loadingEl = document.getElementById('scannerLoading');
    errEl.textContent = msg;
    errEl.style.display = msg ? 'block' : 'none';
    loadingEl.style.display = 'none';
}
function setRunning(running) {
    document.getElementById('btnStart').disabled = running;
    document.getElementById('btnStop').disabled = !running;
    document.getElementById('cameraSelect').disabled = running;
    document.getElementById('scannerLoading').style.display = running ? 'block' : 'none';
}
let html5QrcodeScanner = null;
let isScanning = false;

async function loadCameras() {
    showError('');
    const cameraSelect = document.getElementById('cameraSelect');
    cameraSelect.innerHTML = `<option value="">Loading cameras...</option>`;

    try {
        const cameras = await Html5Qrcode.getCameras();
        if (!cameras || cameras.length === 0) {
            cameraSelect.innerHTML = `<option value="">No cameras found</option>`;
            showError('No cameras found.');
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
        cameraSelect.value = backCam ? backCam.id : cameras[0].id;
    } catch (e) {
        cameraSelect.innerHTML = `<option value="">Camera permission denied</option>`;
        showError('Cannot access cameras: ' + (e && e.message ? e.message : String(e)));
    }
}

async function startScanner() {
    showError('');
    setRunning(true);
    isScanning = true;

    const selectedCamId = document.getElementById('cameraSelect').value;
    if (!selectedCamId) {
        showError('Select a camera first.');
        setRunning(false);
        isScanning = false;
        return;
    }

    if (html5QrcodeScanner) {
        try { await html5QrcodeScanner.stop(); } catch (e) {}
    }

    html5QrcodeScanner = new Html5Qrcode('preview');
    const config = { fps: 10, qrbox: { width: 220, height: 220 } };

    try {
        await html5QrcodeScanner.start(
            selectedCamId,
            config,
            (decodedText) => {
                document.getElementById('lastScanned').textContent = decodedText;

                document.getElementById('addQtySearchCode').value = decodedText;
                $('#tab-addqty').trigger('click');
                $('#scanQrModal').modal('hide');
                $('#btnFindItem').trigger('click');
            },
            () => {}
        );
        document.getElementById('scannerLoading').style.display = 'none';
    } catch (e) {
        showError('Failed to start camera: ' + (e && e.message ? e.message : String(e)));
        setRunning(false);
        isScanning = false;
    }
}

async function stopScanner() {
    showError('');
    if (!html5QrcodeScanner || !isScanning) return;
    try { await html5QrcodeScanner.stop(); } catch (e) {}
    setRunning(false);
    isScanning = false;
}

$('#scanQrModal').on('shown.bs.modal', function () {
    loadCameras();
    setRunning(false);
    document.getElementById('lastScanned').textContent = '—';
    document.getElementById('preview').innerHTML = '';
});
$('#scanQrModal').on('hidden.bs.modal', function () {
    stopScanner();
    document.getElementById('preview').innerHTML = '';
});

$('#btnStart').on('click', startScanner);
$('#btnStop').on('click', stopScanner);
</script>

</body>
</html>