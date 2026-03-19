<?php
// csm_category.php
// AST-style Add Category modal with Add Row
// CSM functions preserved

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

function getAllCSMCategoriesWithPrimary() {
    $sql = "
        SELECT
            c.category_id,
            c.item_category_name,
            c.item_category_code,
            c.created_at,
            c.updated_at,
            (
                SELECT CONCAT('upload/category/', i.file_name)
                FROM csm_inventory_category_images i
                WHERE i.category_id = c.category_id
                ORDER BY (CASE WHEN IFNULL(i.is_primary,0)=1 THEN 0 ELSE 1 END), i.image_id ASC
                LIMIT 1
            ) AS primary_image
        FROM csm_inventory_category c
        ORDER BY c.created_at DESC
    ";
    $res = call_mysql_query($sql);
    $rows = [];
    if ($res) {
        while ($r = call_mysql_fetch_array($res)) $rows[] = $r;
    }
    return $rows;
}

$categories = getAllCSMCategoriesWithPrimary();
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">

    <style>
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .summary-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; }
        .summary-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
        .summary-value { font-size: 18px; font-weight: 700; }

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
        .thumb-wrap { display: flex; align-items: center; justify-content: center; }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #e5e7eb;
        }

        .img-preview {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
        }

        .img-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; }
        .img-card { border:1px solid #e5e7eb;border-radius:12px;padding:8px;background:#fff; }
        .img-card img { width:100%;height:100px;object-fit:cover;border-radius:10px;border:1px solid #eee; }
        .img-card .btn { padding:.25rem .5rem; }

        .view-img-wrap {
            width:100%;
            background:#f8f9fa;
            border:1px solid #e5e7eb;
            border-radius:14px;
            overflow:hidden;
        }
        .view-img-wrap img { width:100%; height:auto; display:block; }

        .inv-assign-table td { vertical-align: middle; }
        .inv-assign-thumb{
            width:56px;height:56px;border:1px solid #dee2e6;border-radius:10px;background:#fff;
            display:flex;align-items:center;justify-content:center;overflow:hidden;
        }
        .inv-assign-thumb img{ width:56px;height:56px;object-fit:cover;display:block; }

        .multi-thumb-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        .multi-thumb-preview img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #f8f9fa;
        }

        .tabulator .tabulator-header .tabulator-col {
            background: #f8f9fa;
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
        <h1 class="h4 fw-semibold mb-1">Consumable Categories</h1>
        <p class="text-muted small mb-0">Manage item categories for consumable inventory.</p>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-tags-fill"></i>&ensp;Manage Consumable Categories</span>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle"></i> Add Category
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#bulkModal">
                        <i class="bi bi-file-earmark-arrow-up-fill"></i> Bulk CSV
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#logmodal">
                        <i class="bi bi-clock-history"></i> Category Log
                    </button>
                </div>
            </div>

            <div class="card-body mt-3 bg-white">
                <div id="categoryMsg" class="alert alert-danger d-none mb-3"></div>

                <div class="table-responsive">
                    <div id="categoryTable"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ADD CATEGORY MODAL (AST-STYLE ROW ADD) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i>&ensp;Add Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="addCategoryForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Tip:</strong> Add one or multiple categories at once. Duplicate names or codes will be skipped automatically.
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Rows</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addCategoryRowBtn">
                            <i class="bi bi-plus-circle"></i> Add Row
                        </button>
                    </div>

                    <div id="addCategoryRows" class="d-flex flex-column gap-2"></div>

                    <div id="addMsg" class="mt-3"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addCategorySubmitBtn">
                        <i class="bi bi-save2"></i> Save All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i>&ensp;Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="editCategoryForm">
                <input type="hidden" name="category_id" id="edit_category_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_category_name" id="edit_item_category_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Code</label>
                        <div class="input-group">
                            <span class="input-group-text fw-semibold">CSM-</span>
                            <input type="text" class="form-control" id="edit_item_category_code" readonly>
                        </div>
                        <small class="text-muted d-block mt-2">Locked.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Primary Image</label>
                        <div id="currentPhotoDisplay" class="mt-1 text-muted small">No image uploaded</div>
                    </div>

                    <div id="editMsg"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW IMAGE MODAL -->
<div class="modal fade" id="viewImageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-image"></i>&ensp;Category Image
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="small text-muted mb-2" id="viewImageTitle">—</div>
        <div id="viewImageBodyMsg" class="mb-2"></div>

        <div class="view-img-wrap">
          <img id="viewImageImg" src="" alt="Category Image">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- IMAGES MODAL -->
<div class="modal fade" id="categoryImagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-images"></i>&ensp;Category Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="img_category_id">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="small text-muted" id="imgCatTitle">—</div>

                    <form id="addImagesForm" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" class="form-control form-control-sm" name="images[]" id="moreImages" accept="image/*" multiple required>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                    </form>
                </div>

                <div id="imgMsg" class="mb-2"></div>
                <div id="imgGrid" class="img-grid"></div>

                <hr class="my-3">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="fw-semibold">
                        <i class="bi bi-box-seam"></i>&ensp;Assign Images to Inventory Items (same category)
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnReloadInvAssign">
                        <i class="bi bi-arrow-clockwise"></i> Reload
                    </button>
                </div>

                <div id="invAssignMsg" class="mb-2"></div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle inv-assign-table">
                        <thead>
                        <tr>
                            <th style="width:90px;">Current</th>
                            <th>Inventory</th>
                            <th style="width:260px;">Assign Image</th>
                            <th style="width:140px;">Action</th>
                        </tr>
                        </thead>
                        <tbody id="invAssignTbody">
                            <tr><td colspan="4" class="text-muted">Load a category first.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- BULK CSV MODAL -->
<div class="modal fade" id="bulkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-file-earmark-arrow-up-fill"></i>&ensp;Bulk Upload Categories
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="addBulkCategoryForm" enctype="multipart/form-data">
          <div class="row g-3">

            <div class="alert alert-info mb-3">
              <div class="fw-semibold mb-1"><i class="bi bi-info-circle"></i> CSV Upload Instructions</div>
              <ul class="mb-0 small">
                  <li>File type: <strong>.csv</strong></li>
                  <li>Columns (order): <strong>Category Name</strong>, <strong>Code</strong></li>
                  <li><strong>Code</strong> is optional. If blank, system auto-generates.</li>
                  <li>If provided, <strong>Code</strong> can be digits (e.g., <strong>1</strong>, <strong>0001</strong>, <strong>10000</strong>).</li>
                  <li>Display will show <strong>CSM-</strong> + digits.</li>
              </ul>
            </div>

            <div class="col-12 col-md-8">
              <label class="form-label fw-semibold">Upload CSV File</label>
              <input type="file" class="form-control" name="file"
                     id="bulkCategoryFile" accept=".csv" required>
              <small class="text-muted d-block mt-2">
                CSV format: Category Name, Code (optional digits).
              </small>
            </div>

            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload"></i> Upload
              </button>
              <button type="button" id="downloadTemplateBtn" class="btn btn-outline-secondary">
                <i class="bi bi-download"></i> Template
              </button>
            </div>

            <div class="col-12">
              <div id="bulkMsg"></div>
            </div>

          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-exclamation-triangle text-danger"></i>&ensp;Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-2">
                    Delete this category?
                    <div class="small text-muted mt-1" id="deleteCatInfo">—</div>
                </div>
                <div class="alert alert-warning mb-0">This action cannot be undone.</div>
                <div id="deleteMsg" class="mt-2"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteCat">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- INVENTORY LOG -->
<div class="modal fade" id="logmodal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-upc-scan"></i>&ensp;Inventory Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          WORK IN PROGRESS HERE
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once FOOTER_PATH; ?>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
var categoryData = <?php echo json_encode($categories); ?>;

const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = <?php echo json_encode(BASE_URL . 'admin/modules/consumable/process/csm_category_process.php'); ?>;
const BULK_PROCESS_URL = <?php echo json_encode(BASE_URL . 'admin/modules/consumable/process/csm_category_bulk_process.php'); ?>;

function absUrl(path){
  const base = String(BASE_URL || '');
  const sep = base.endsWith('/') ? '' : '/';
  path = String(path || '').replace(/^\/+/, '');
  return base + sep + path;
}
function escHtml(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function extractDigits(code){
  let s = String(code ?? '').trim();
  if(!s) return '';
  s = s.toUpperCase().replace(/[\s\-]/g,'');
  if(s.startsWith('CSM')) s = s.slice(3);
  s = s.replace(/\D/g,'');
  if(!s) return '';
  s = s.replace(/^0+/, '');
  return s ? s : '';
}
function padForDisplay(digits){
  let d = String(digits ?? '').trim().replace(/\D/g,'');
  if(!d) return '';
  d = d.replace(/^0+/, '');
  if(!d) return '';
  if(d.length < 4) d = d.padStart(4,'0');
  return d;
}
function digitsOnly(el){
  if(!el) return;
  let v = String(el.value ?? '');
  v = v.replace(/\D/g,'');
  el.value = v;
}
function displayCode(codeAny){
  const d = extractDigits(codeAny);
  const shown = padForDisplay(d);
  return shown ? ('CSM-' + shown) : '';
}
function getNextCodeDigitsFromData(arr){
    let maxNum = 0;
    (arr || []).forEach(r => {
        const d = extractDigits(r.item_category_code);
        const n = parseInt(d || '0', 10);
        if (!isNaN(n) && n > maxNum) maxNum = n;
    });
    const next = String(maxNum + 1);
    return padForDisplay(next);
}
function formatDate(val){
    if(!val) return '';
    const d = new Date(val);
    if (isNaN(d.getTime())) return escHtml(String(val));
    return d.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
}
function renderMainThumb(row){
    const thumb = row.primary_image ? absUrl(row.primary_image) : '';
    const full = thumb;
    const name = row.item_category_name || '';

    if (thumb) {
        return `<div class="thumb-wrap"><img src="${thumb}" data-full="${full}" class="item-thumb js-thumb-preview" alt="Category photo" loading="lazy"></div>`;
    }

    const initials = (String(name).trim().split(/\s+/).map(w => w.charAt(0)).filter(Boolean).slice(0,2).join('') || 'CT').toUpperCase();
    return `<div class="thumb-wrap"><div class="item-badge" title="${escHtml(name)}">${initials}</div></div>`;
}
function openViewModalFromRow(row){
    if(!row || !row.primary_image) return;
    const src = absUrl(row.primary_image);
    const title = `${displayCode(row.item_category_code)} — ${row.item_category_name}`;

    $('#viewImageTitle').text(title);
    $('#viewImageBodyMsg').html('');
    $('#viewImageImg')
        .off('error')
        .attr('src', src)
        .on('error', function(){
            $(this).attr('src','');
            $('#viewImageBodyMsg').html('<div class="alert alert-warning mb-0">Image not found.</div>');
        });

    $('#viewImageModal').modal('show');
}

let catTable = null;

function buildTabulator(data){
    catTable = new Tabulator("#categoryTable", {
        data: data || [],
        layout: "fitColumns",
        responsiveLayout: "collapse",
        placeholder: "No categories found",
        pagination: "local",
        paginationSize: 20,
        paginationSizeSelector: [20, 100, 500, 1000, true],
        paginationCounter: "rows",
        columns: [
            {
                title: "Image",
                field: "primary_image",
                width: 80,
                headerSort: false,
                formatter: function(cell) {
                    return renderMainThumb(cell.getRow().getData());
                }
            },
            {
                title: "Category Name",
                field: "item_category_name",
                minWidth: 180,
                widthGrow: 2,
                headerFilter: "input",
                formatter: function(cell) {
                    return `<strong>${escHtml(cell.getValue() || '')}</strong>`;
                }
            },
            {
                title: "Category Code",
                field: "item_category_code",
                width: 160,
                formatter: function(cell) {
                    return `<span class="badge bg-light text-dark border">${escHtml(displayCode(cell.getValue() || ''))}</span>`;
                }
            },
            {
                title: "Date Modified",
                field: "updated_at",
                width: 150,
                formatter: function(cell) {
                    return formatDate(cell.getValue() || cell.getRow().getData().created_at || '');
                }
            },
            {
                title: "Actions",
                field: "category_id",
                width: 260,
                headerSort: false,
                formatter: function(cell) {
                    const id = cell.getValue();
                    return `
                        <button class="btn btn-sm btn-info text-white me-1 btn-images-cat" data-id="${id}" title="Images">
                            <i class="bi bi-images"></i> Images
                        </button>
                        <button class="btn btn-sm btn-warning me-1 btn-edit-cat" data-id="${id}" title="Edit">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger btn-del-cat" data-id="${id}" title="Delete">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    `;
                }
            }
        ]
    });
}

function setTabulatorData(data){
    if(!catTable) buildTabulator(data);
    else catTable.replaceData(data || []);
}

function refreshCategoryList(){
    $('#categoryMsg').addClass('d-none').html('');
    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: { action: 'list_category' },
        success: function(res){
            if(res && res.success){
                categoryData = res.data || [];
                setTabulatorData(categoryData);
            } else {
                $('#categoryMsg').removeClass('d-none').html('Failed to load categories.');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            $('#categoryMsg').removeClass('d-none').html('Error loading categories.');
        }
    });
}

function previewSelectedImage(file, wrap){
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        wrap.html(`<img src="${e.target.result}" alt="preview">`);
    };
    reader.readAsDataURL(file);
}

/* AST-style add-row for Add Category */
function addCategoryRow(name = '', code = '') {
    const idx = $('#addCategoryRows .category-row').length;

    const row = `
        <div class="category-row border rounded p-2">
            <div class="row g-2 align-items-start">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold small mb-1">Category Name</label>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        name="bulk_names[]"
                        value="${escHtml(name)}"
                        required
                        placeholder="e.g., Office Supplies"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold small mb-1">Code (optional)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text fw-semibold">CSM-</span>
                        <input
                            type="text"
                            class="form-control add-code-input"
                            name="bulk_codes[]"
                            value="${escHtml(code)}"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Auto if blank"
                        >
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold small mb-1">Photo (optional)</label>
                    <input
                        type="file"
                        class="form-control form-control-sm add-photo-input"
                        name="bulk_photo_${idx}"
                        accept="image/*"
                    >
                    <div class="multi-thumb-preview row-photo-preview"></div>
                </div>

                <div class="col-12 col-md-1 d-grid">
                    <label class="form-label fw-semibold small mb-1">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-category-row" title="Remove row">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    $('#addCategoryRows').append(row);
}

$(function(){
    setTabulatorData(categoryData);
    addCategoryRow();
});

/* main image preview click */
$('#categoryTable').on('click', '.js-thumb-preview, .item-badge', function() {
    const full = $(this).data('full') || $(this).attr('src');
    if (!full) return;
    $('#viewImageTitle').text('Category Image');
    $('#viewImageBodyMsg').html('');
    $('#viewImageImg').attr('src', full);
    $('#viewImageModal').modal('show');
});

/* Add Category modal row controls */
$('#addCategoryRowBtn').on('click', function() {
    addCategoryRow();
});

$('#addCategoryRows').on('click', '.btn-remove-category-row', function() {
    $(this).closest('.category-row').remove();

    $('#addCategoryRows .category-row').each(function(i) {
        $(this).find('input[type="file"]').attr('name', 'bulk_photo_' + i);
    });

    if ($('#addCategoryRows .category-row').length === 0) {
        addCategoryRow();
    }
});

$('#addCategoryRows').on('input', '.add-code-input', function() {
    digitsOnly(this);
});

$('#addCategoryRows').on('change', '.add-photo-input', function() {
    const file = this.files && this.files[0] ? this.files[0] : null;
    const wrap = $(this).siblings('.row-photo-preview');
    wrap.html('');
    if (file) previewSelectedImage(file, wrap);
});

/* Add Category submit -> row-based bulk_add_categories */
$('#addCategoryForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    $('#addMsg').html('');

    $('#addCategoryRows .add-code-input').each(function() {
        digitsOnly(this);
    });

    const fd = new FormData(this);
    fd.append('action', 'bulk_add_categories');

    $('#addCategorySubmitBtn')
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res){
            $('#addCategoryRows .category-row').removeClass('border-danger');
            $('#addCategoryRows .duplicate-hint').remove();

            if(res && res.success){
                let msg = `<div class="alert alert-primary mt-2">
                    Inserted: ${res.inserted || 0}, Skipped: ${res.skipped || 0}
                </div>`;

                if(res.errors && res.errors.length > 0){
                    msg += '<div class="alert alert-warning"><strong>Errors:</strong><ul class="mb-0">';
                    res.errors.forEach(function(err){
                        msg += `<li>${escHtml(err)}</li>`;
                    });
                    msg += '</ul></div>';
                }

                $('#addMsg').html(msg);

                if (res.errors && res.errors.length > 0 && res.error_rows && res.error_rows.length > 0) {
                    $('#addCategoryRows').html('');

                    res.error_rows.forEach(function(er) {
                        addCategoryRow(er.name || '', er.code || '');

                        const rowEl = $('#addCategoryRows .category-row').last();
                        rowEl.addClass('border-danger');

                        const nameInput = rowEl.find('input[name="bulk_names[]"]').first();

                        let reasonText = 'Could not save this row';
                        if (er.reason === 'duplicate') reasonText = 'Duplicate category name';
                        if (er.reason === 'duplicate_code') reasonText = 'Duplicate category code';
                        if (er.reason === 'required') reasonText = 'Category name is required';
                        if (er.reason === 'invalid_code') reasonText = 'Invalid category code';

                        nameInput.after(`<div class="text-danger small duplicate-hint mt-1">${reasonText}</div>`);
                    });
                } else {
                    $('#addCategoryForm')[0].reset();
                    $('#addCategoryRows').html('');
                    addCategoryRow();
                }

                refreshCategoryList();
            } else {
                $('#addMsg').html('<div class="alert alert-danger">' + escHtml((res && res.message) ? res.message : 'Bulk add failed.') + '</div>');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Error adding categories.';
            $('#addMsg').html('<div class="alert alert-danger">' + escHtml(msg) + '</div>');
        }
    }).always(function() {
        $('#addCategorySubmitBtn')
            .prop('disabled', false)
            .html('<i class="bi bi-save2"></i> Save All');
    });
});

/* Edit category */
$(document)
  .off('click', '.btn-edit-cat')
  .on('click', '.btn-edit-cat', function(e){
      e.preventDefault();
      e.stopPropagation();
      $('#editMsg').html('');

      const id = $(this).data('id');

      $.ajax({
          url: PROCESS_URL,
          type: 'POST',
          dataType: 'json',
          data: { action: 'get_category', category_id: id },
          success: function(res){
              if(!res || !res.success){
                  alert(res && res.message ? res.message : 'Record not found.');
                  return;
              }

              const d = res.data;
              $('#edit_category_id').val(d.category_id);
              $('#edit_item_category_name').val(d.item_category_name || '');

              const digits = extractDigits(d.item_category_code || '');
              $('#edit_item_category_code').val(padForDisplay(digits));

              const row = (categoryData || []).find(x => String(x.category_id) === String(d.category_id));
              if (row && row.primary_image) {
                  $('#currentPhotoDisplay').html(`<img src="${absUrl(row.primary_image)}" class="preview-image" alt="Current image">`);
              } else {
                  $('#currentPhotoDisplay').html('<div class="text-muted small">No image uploaded</div>');
              }

              $('#editCategoryModal').modal('show');
          },
          error: function(xhr){
              console.error(xhr.responseText);
              alert('Error loading record.');
          }
      });
  });

$('#editCategoryForm').off('submit').on('submit', function(e){
    e.preventDefault();
    $('#editMsg').html('');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: $(this).serialize() + '&action=update_category',
        success: function(resp){
            if($.trim(resp) === 'success'){
                $('#editMsg').html('<div class="alert alert-success">Category updated!</div>');
                refreshCategoryList();
                setTimeout(() => $('#editCategoryModal').modal('hide'), 1000);
            } else {
                $('#editMsg').html('<div class="alert alert-danger">'+ escHtml(resp) +'</div>');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            $('#editMsg').html('<div class="alert alert-danger">Error updating category.</div>');
        }
    });
});

/* Delete category */
let pendingDeleteCatId = null;

$(document)
  .off('click', '.btn-del-cat')
  .on('click', '.btn-del-cat', function(e){
      e.preventDefault();
      e.stopPropagation();
      $('#deleteMsg').html('');

      pendingDeleteCatId = $(this).data('id');

      const row = (categoryData || []).find(x => String(x.category_id) === String(pendingDeleteCatId));
      const info = row ? `${displayCode(row.item_category_code)} — ${row.item_category_name}` : `ID #${pendingDeleteCatId}`;
      $('#deleteCatInfo').text(info);

      $('#deleteCategoryModal').modal('show');
  });

$('#btnConfirmDeleteCat').off('click').on('click', function(){
    if(!pendingDeleteCatId) return;

    $(this).prop('disabled', true);
    $('#deleteMsg').html('');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: { action: 'delete_category', category_id: pendingDeleteCatId },
        success: function(resp){
            if($.trim(resp) === 'success'){
                $('#deleteCategoryModal').modal('hide');
                refreshCategoryList();
            } else {
                $('#deleteMsg').html('<div class="alert alert-danger">'+ escHtml(resp) +'</div>');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            $('#deleteMsg').html('<div class="alert alert-danger">Error deleting category.</div>');
        },
        complete: function(){
            $('#btnConfirmDeleteCat').prop('disabled', false);
            pendingDeleteCatId = null;
        }
    });
});

/* Bulk CSV Upload */
$('#addBulkCategoryForm').off('submit').on('submit', function(e){
    e.preventDefault();
    $('#bulkMsg').html('');

    const fileInput = document.getElementById('bulkCategoryFile');
    if(!fileInput.files || !fileInput.files[0]){
        $('#bulkMsg').html('<div class="alert alert-warning">Please select a CSV file.</div>');
        return;
    }

    const fd = new FormData(this);
    fd.append('action', 'bulk_add_category');

    $.ajax({
        url: BULK_PROCESS_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res){
            if(res && res.success){
                let msg = `<div class="alert alert-success">
                    Bulk upload complete. Inserted: ${res.inserted || 0}, Skipped: ${res.skipped || 0}.
                </div>`;

                if(res.errors && res.errors.length){
                    msg += `<div class="alert alert-warning mt-2 mb-0">
                        <div class="fw-semibold mb-1">Warnings / Skipped reasons:</div>
                        <ul class="mb-0 small">${res.errors.map(e => `<li>${escHtml(e)}</li>`).join('')}</ul>
                    </div>`;
                }

                $('#bulkMsg').html(msg);
                $('#addBulkCategoryForm')[0].reset();
                refreshCategoryList();
            } else {
                $('#bulkMsg').html('<div class="alert alert-danger">'+ escHtml((res && res.message) ? res.message : 'Bulk upload failed.') +'</div>');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            $('#bulkMsg').html('<div class="alert alert-danger">Bulk upload error.</div>');
        }
    });
});

$('#downloadTemplateBtn').off('click').on('click', function(){
    $.ajax({
        url: BULK_PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: { action: 'download_template' },
        success: function(res){
            if(!res || !res.success){
                alert('Failed to get template.');
                return;
            }
            const content = res.content || '';
            const filename = res.filename || 'csm_category_template.csv';

            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        },
        error: function(xhr){
            console.error(xhr.responseText);
            alert('Template download error.');
        }
    });
});

/* Images modal */
function renderImagesGrid(images){
    const $grid = $('#imgGrid');
    if(!images || images.length === 0){
        $grid.html('<div class="text-muted">No images uploaded yet.</div>');
        return;
    }

    let html = '';
    images.forEach(img => {
        const src = absUrl(img.file_url);
        html += `
          <div class="img-card">
            <img src="${escHtml(src)}" alt="img">
            <div class="d-flex justify-content-between align-items-center mt-2">
              <button type="button" class="btn btn-sm btn-outline-primary btn-set-primary" data-image-id="${img.image_id}">
                ${img.is_primary == 1 ? 'Primary' : 'Set Primary'}
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-del-image" data-image-id="${img.image_id}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        `;
    });
    $grid.html(html);
}

let _categoryImagesCache = [];

function buildCategoryImageOptions(images){
  const opts = [`<option value="">(None / Use category primary)</option>`];
  (images || []).forEach(img => {
    const label = (img.is_primary == 1) ? `#${img.image_id} (Primary)` : `#${img.image_id}`;
    opts.push(`<option value="${img.image_id}">${label}</option>`);
  });
  return opts.join('');
}

function renderInvAssignRows(items, images){
  const $tb = $('#invAssignTbody');

  if(!items || items.length === 0){
    $tb.html(`<tr><td colspan="4" class="text-muted">No inventory items found for this category.</td></tr>`);
    return;
  }

  const optionsHtml = buildCategoryImageOptions(images);

  let html = '';
  items.forEach(it => {
    const currentUrl = it.assigned_image_url ? absUrl(it.assigned_image_url) : '';
    const invCode = escHtml(it.inventory_code || '');
    const invName = escHtml(it.item_name || '');
    const invLabel = (invCode && invName) ? `${invCode} — ${invName}` : (invCode || invName || `Inventory #${escHtml(it.inventory_id)}`);

    html += `
      <tr>
        <td>
          <div class="inv-assign-thumb">
            ${currentUrl ? `
              <img src="${escHtml(currentUrl)}" alt="assigned"
                onerror="this.remove(); this.parentNode.innerHTML='<i class=&quot;bi bi-image text-muted&quot;></i>';">
            ` : `<i class="bi bi-image text-muted"></i>`}
          </div>
        </td>

        <td>
          <div class="fw-semibold">${invLabel}</div>
          <div class="small text-muted">Category: ${escHtml(displayCode(it.item_category_code || ''))}</div>
          <div class="small text-muted">Current image_id: ${it.category_image_id ? escHtml(it.category_image_id) : '—'}</div>
        </td>

        <td>
          <select class="form-select form-select-sm inv-image-select" data-inventory-id="${it.inventory_id}">
            ${optionsHtml}
          </select>
        </td>

        <td>
          <button class="btn btn-sm btn-primary btn-save-inv-image" data-inventory-id="${it.inventory_id}">
            Save
          </button>
        </td>
      </tr>
    `;
  });

  $tb.html(html);

  items.forEach(it => {
    $tb.find(`.inv-image-select[data-inventory-id="${it.inventory_id}"]`).val(it.category_image_id || '');
  });
}

function loadInvAssign(categoryId){
  $('#invAssignMsg').html('');
  $('#invAssignTbody').html('<tr><td colspan="4" class="text-muted">Loading…</td></tr>');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    dataType: 'json',
    data: { action: 'list_inventory_by_category', category_id: categoryId },
    success: function(res){
      if(res && res.success){
        renderInvAssignRows(res.data || [], _categoryImagesCache || []);
      } else {
        $('#invAssignMsg').html('<div class="alert alert-danger">Failed to load inventory items.</div>');
        $('#invAssignTbody').html('<tr><td colspan="4" class="text-muted">No data.</td></tr>');
      }
    },
    error: function(xhr){
      console.error(xhr.responseText);
      $('#invAssignMsg').html(
        '<div class="alert alert-danger mb-0">' +
        '<div class="fw-semibold">Error loading inventory items.</div>' +
        '<pre class="mb-0 small" style="white-space:pre-wrap;">' + escHtml(xhr.responseText || '') + '</pre>' +
        '</div>'
      );
      $('#invAssignTbody').html('<tr><td colspan="4" class="text-muted">Failed to load.</td></tr>');
    }
  });
}

function loadCategoryImages(categoryId){
    $('#imgMsg').html('');
    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        dataType: 'json',
        data: { action: 'list_category_images', category_id: categoryId },
        success: function(res){
            if(res && res.success){
                _categoryImagesCache = res.data || [];
                renderImagesGrid(_categoryImagesCache);
                refreshCategoryList();
                loadInvAssign(categoryId);
            } else {
                $('#imgMsg').html('<div class="alert alert-danger">Failed to load images.</div>');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            $('#imgMsg').html('<div class="alert alert-danger">Error loading images.</div>');
        }
    });
}

$(document)
  .off('click', '.btn-images-cat')
  .on('click', '.btn-images-cat', function(e){
      e.preventDefault();
      e.stopPropagation();

      const id = $(this).data('id');
      $('#img_category_id').val(id);

      const row = (categoryData || []).find(x => String(x.category_id) === String(id));
      $('#imgCatTitle').text(row ? `${displayCode(row.item_category_code)} — ${row.item_category_name}` : `Category #${id}`);

      $('#moreImages').val('');
      $('#imgGrid').html('');
      $('#invAssignMsg').html('');
      $('#invAssignTbody').html('<tr><td colspan="4" class="text-muted">Loading…</td></tr>');

      $('#categoryImagesModal').modal('show');
      loadCategoryImages(id);
  });

$('#addImagesForm').off('submit').on('submit', function(e){
    e.preventDefault();
    $('#imgMsg').html('');

    const categoryId = $('#img_category_id').val();
    if(!categoryId){
        $('#imgMsg').html('<div class="alert alert-warning">Missing category id.</div>');
        return;
    }

    const input = document.getElementById('moreImages');
    if(!input.files || input.files.length === 0){
        $('#imgMsg').html('<div class="alert alert-warning">Select images to upload.</div>');
        return;
    }

    const fd = new FormData(this);
    fd.append('action', 'upload_category_images');
    fd.append('category_id', categoryId);

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res){
            if(res && res.success){
                $('#imgMsg').html('<div class="alert alert-success">Uploaded.</div>');
                $('#addImagesForm')[0].reset();
                loadCategoryImages(categoryId);
            } else {
                $('#imgMsg').html('<div class="alert alert-danger">'+ escHtml(res && res.message ? res.message : 'Upload failed') +'</div>');
            }
        },
        error: function(xhr){
            console.error(xhr.responseText);
            $('#imgMsg').html('<div class="alert alert-danger">Upload error.</div>');
        }
    });
});

$('#imgGrid')
  .off('click', '.btn-set-primary')
  .on('click', '.btn-set-primary', function(e){
      e.preventDefault();
      e.stopPropagation();
      const categoryId = $('#img_category_id').val();
      const imageId = $(this).data('image-id');
      $('#imgMsg').html('');

      $.ajax({
          url: PROCESS_URL,
          type: 'POST',
          dataType: 'json',
          data: { action:'set_primary_image', category_id: categoryId, image_id: imageId },
          success: function(res){
              if(res && res.success){
                  loadCategoryImages(categoryId);
              } else {
                  $('#imgMsg').html('<div class="alert alert-danger">Failed to set primary.</div>');
              }
          },
          error: function(xhr){
              console.error(xhr.responseText);
              $('#imgMsg').html('<div class="alert alert-danger">Error setting primary.</div>');
          }
      });
  });

$('#imgGrid')
  .off('click', '.btn-del-image')
  .on('click', '.btn-del-image', function(e){
      e.preventDefault();
      e.stopPropagation();
      const categoryId = $('#img_category_id').val();
      const imageId = $(this).data('image-id');
      $('#imgMsg').html('');

      $.ajax({
          url: PROCESS_URL,
          type: 'POST',
          dataType: 'json',
          data: { action:'delete_category_image', category_id: categoryId, image_id: imageId },
          success: function(res){
              if(res && res.success){
                  loadCategoryImages(categoryId);
              } else {
                  $('#imgMsg').html('<div class="alert alert-danger">Failed to delete image.</div>');
              }
          },
          error: function(xhr){
              console.error(xhr.responseText);
              $('#imgMsg').html('<div class="alert alert-danger">Error deleting image.</div>');
          }
      });
  });

$('#btnReloadInvAssign').off('click').on('click', function(){
  const categoryId = $('#img_category_id').val();
  if(categoryId) loadInvAssign(categoryId);
});

$(document)
  .off('click', '.btn-save-inv-image')
  .on('click', '.btn-save-inv-image', function(e){
      e.preventDefault();
      e.stopPropagation();
      $('#invAssignMsg').html('');

      const inventoryId = $(this).data('inventory-id');
      const $sel = $(`.inv-image-select[data-inventory-id="${inventoryId}"]`);
      const imageId = $sel.val();

      $.ajax({
          url: PROCESS_URL,
          type: 'POST',
          dataType: 'json',
          data: { action: 'assign_inventory_image', inventory_id: inventoryId, image_id: imageId },
          success: function(res){
              if(res && res.success){
                  $('#invAssignMsg').html('<div class="alert alert-success">Saved.</div>');
                  const categoryId = $('#img_category_id').val();
                  if(categoryId) loadInvAssign(categoryId);
              } else {
                  $('#invAssignMsg').html('<div class="alert alert-danger">'+ escHtml(res && res.message ? res.message : 'Failed to save.') +'</div>');
              }
          },
          error: function(xhr){
              console.error(xhr.responseText);
              $('#invAssignMsg').html(
                '<div class="alert alert-danger mb-0">' +
                '<div class="fw-semibold">Error saving assignment.</div>' +
                '<pre class="mb-0 small" style="white-space:pre-wrap;">' + escHtml(xhr.responseText || '') + '</pre>' +
                '</div>'
              );
          }
      });
  });

/* Reset modals */
$('#addCategoryModal').on('hidden.bs.modal', function() {
    $('#addCategoryForm')[0].reset();
    $('#addMsg').html('');
    $('#addCategoryRows').html('');
    addCategoryRow();
});

$('#editCategoryModal').on('hidden.bs.modal', function() {
    $('#editCategoryForm')[0].reset();
    $('#currentPhotoDisplay').html('No image uploaded');
    $('#editMsg').html('');
});

$('#bulkModal').on('hidden.bs.modal', function() {
    $('#addBulkCategoryForm')[0].reset();
    $('#bulkMsg').html('');
});
</script>
</body>
</html>