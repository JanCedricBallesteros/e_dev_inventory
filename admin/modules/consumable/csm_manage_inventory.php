<?php
// csm_manage_inventory.php
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/call_func/phpqrcode/qrlib.php';
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

// -------------------- FUNCTIONS --------------------
function getAllCategories() {
    $sql = "SELECT item_category_code, item_category_name FROM csm_inventory_category ORDER BY item_category_name ASC";
    $result = call_mysql_query($sql);
    $categories = [];
    if ($result) {
        while ($row = call_mysql_fetch_array($result)) {
            $categories[] = $row;
        }
    }
    return $categories;
}

// -------------------- DATA --------------------
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
</head>

<style>
/* --- TABULATOR MOBILE FIX (NO RESPONSIVE COLLAPSE) --- */
#consumeable-table{
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
#consumeable-table .tabulator{
    min-width: 1220px;
}
@media (max-width: 576px){
    #consumeable-table .tabulator{
        min-width: 940px;
    }
    #consumeable-table .tabulator .tabulator-cell,
    #consumeable-table .tabulator .tabulator-col{
        padding: .35rem .4rem;
    }
}

/* --- HEADER BUTTONS MOBILE FIX (SCROLLABLE ACTIONS ROW) --- */
.header-actions{
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}
@media (max-width: 576px){
    .header-actions{
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
        padding-bottom: .25rem;
    }
    .header-actions .btn{
        white-space: nowrap;
        flex: 0 0 auto;
    }
}

/* QR preview */
#preview-wrapper {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    position: relative;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 4 / 3;
}

/* Inventory image */
.inv-thumb-wrap{
    width:56px;height:56px;
    border:1px solid #dee2e6;border-radius:10px;background:#fff;
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;
}
.inv-thumb-wrap img{ width:56px;height:56px;object-fit:cover;display:block; }
.inv-thumb-fallback{ font-size:24px; line-height:1; color:#6c757d; }
.inv-thumb-click{ cursor:pointer; display:inline-flex; }

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

/* ---------- AST-STYLE RULES MODAL ---------- */
.ast-rules-modal .modal-header{
    border-bottom: 1px solid #e9ecef;
}
.ast-rules-modal .modal-title{
    font-weight: 700;
    font-size: 1.05rem;
}
.ast-rules-modal .modal-body{
    padding: 1.1rem 1.1rem .9rem;
}
.ast-rule-group{
    margin-bottom: 1.2rem;
}
.ast-rule-label{
    font-weight: 700;
    color: #495057;
    margin-bottom: .45rem;
    display:block;
}
.ast-readonly{
    background:#f3f4f6 !important;
    color:#6c757d !important;
    border-color:#e5e7eb !important;
}
.ast-rule-help{
    margin-top:.45rem;
    color:#6c757d;
    font-size:.875rem;
    line-height:1.5;
}
.ast-rule-section-title{
    font-weight:700;
    color:#495057;
    margin-bottom:.2rem;
}
.ast-rule-section-subtitle{
    color:#6c757d;
    font-size:.9rem;
    margin-bottom:.7rem;
}
.ast-rule-inline-note{
    color:#6c757d;
    font-size:.875rem;
}
.ast-rule-status-wrap{
    margin-top:.5rem;
}
.ast-rule-status-wrap .badge{
    font-size:.85rem;
}
.ast-select-wrap{
    position:relative;
}
.select2-container{
    width:100% !important;
}
.select2-container--default .select2-selection--multiple{
    min-height:43px;
    border:1px solid #ced4da;
    border-radius:.375rem;
    padding:4px 32px 4px 6px;
    background:#fff;
}
.select2-container--default.select2-container--focus .select2-selection--multiple{
    border-color:#86b7fe;
    box-shadow:0 0 0 .2rem rgba(13,110,253,.15);
}
.select2-selection__choice{
    margin-top:4px !important;
}
.select2-dropdown{
    border:1px solid #ced4da;
}
.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field{
    margin-top:5px;
}
.ast-select-wrap:after{
    content:"";
    position:absolute;
    right:12px;
    top:50%;
    margin-top:-3px;
    border-left:5px solid transparent;
    border-right:5px solid transparent;
    border-top:6px solid #495057;
    pointer-events:none;
    z-index:2;
}
</style>

<body class="d-flex flex-column h-100">

<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1 class="h4 fw-semibold mb-1">Manage Consumable Inventory</h1>
    <p class="text-muted small mb-0">Add records, review inventory, scan QR, update available quantities and configure request rules.</p>
  </div>
  <section class="section">
    <div class="card">

      <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <i class="bi bi-box-seam"></i>&ensp;Manage Consumable Inventory
        </div>

        <div class="header-actions">
          <button class="btn btn-light btn-sm" id="btnAddRecordWarn">
            <i class="bi bi-plus-circle"></i>&ensp;Add Record
          </button>

          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#logmodal">
            <i class="bi bi-cloud-arrow-up-fill"></i>&ensp;Inventory Log
          </button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#scanQrModal">
            <i class="bi bi-upc-scan"></i>&ensp;Scan QR
          </button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#printModal">
            <i class="bi bi-printer"></i>&ensp;Print
          </button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-download"></i>&ensp;Export CSV
          </button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#bulkModal">
            <i class="bi bi-file-earmark-arrow-up-fill"></i>&ensp;Bulk
          </button>
        </div>
      </div>

      <div class="card-body mt-3 bg-white">
        <div id="consumeable-table" class="table table-bordered tabulator"></div>
      </div>

    </div>
  </section>
</main>

<!-- ===================== MODALS ===================== -->

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

<!-- ADD WARNING MODAL -->
<div class="modal fade" id="addWarningModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-exclamation-triangle text-warning"></i>&ensp;Before you add a record
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">Please double-check your input to avoid incorrect inventory records.</div>
        <ul class="small mb-0">
          <li><b>Item Code</b>: numbers only (or leave blank for auto).</li>
          <li><b>Itemized Description</b>: be specific (model, size, details, notes).</li>
          <li><b>Category</b>: choose the correct category.</li>
          <li><b>Unit Quantity</b> and <b>Critical Level</b>: verify correctness.</li>
          <li><b>Available to Issue</b>: set initial available quantity carefully.</li>
          <li><b>Acquisition Date</b> is auto-set to today when you add the record.</li>
          <li><b>Allowed Employment Status</b> can be configured later using the <b>Rules</b> button.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnProceedAdd">Proceed to Add</button>
      </div>
    </div>
  </div>
</div>

<!-- ADD RECORD -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
  <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle"></i>&ensp;Add Inventory Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="addInventoryForm">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Item Code (Number Only)</label>
              <input type="text" name="inventory_system_item_code" class="form-control"
                     inputmode="numeric" pattern="[0-9]*"
                     placeholder="e.g. 1 (auto becomes CSM-[Category]-0001)">
              <div class="form-text">
                Enter <b>numbers only</b>. Full format is automatic:
                <b>CSM-[Item Category Code]-0001</b>
                <br>
                Also accepts: <code>CSM0001</code>, <code>CSM-0001</code>, <code> csm 0001 </code>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="item_category_code" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['item_category_code']) ?>">
                    <?= htmlspecialchars($cat['item_category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Itemized Description</label>
              <textarea name="item_description" class="form-control" placeholder="Enter itemized description (include details, specs, notes)" required></textarea>
              <div class="form-text">
                This is the main item field (item_name is removed in DB). Put the complete details here.
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Unit Quantity</label>
              <input type="number" name="unit_quantity" class="form-control" placeholder="Enter Unit Quantity" required min="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Available to Issue</label>
              <input type="number" name="current_unit_quantity" class="form-control" placeholder="Enter Available to Issue" required min="0">
              <div class="form-text">This can be updated later using the separate “Available” button.</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Critical Level</label>
              <input type="number" name="unit_crit_level" class="form-control" placeholder="Enter Critical Level" required min="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Item Cost</label>
              <input type="number" step="0.01" name="item_cost" class="form-control" placeholder="Enter Item Cost" required min="0">
            </div>

            <div class="col-md-12">
              <label class="form-label">Source of Funds</label>
              <input type="text" name="source_of_funds" class="form-control" placeholder="e.g. General Fund, LGU Budget, Donation">
            </div>

            <div class="col-12">
              <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i>
                Acquisition Date is automatically set to <b>today</b> when you add this record.
              </div>
            </div>
          </div>

          <div class="mt-3">
            <button type="submit" class="btn btn-primary">Add Record</button>
          </div>
        </form>

        <div id="addRecordMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
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

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Item Code (Number Only)</label>
              <input type="text" name="inventory_system_item_code" id="edit_inventory_system_item_code"
                     class="form-control" inputmode="numeric" pattern="[0-9]*"
                     placeholder="e.g. 25 (auto becomes CSM-[Category]-0025)">
              <div class="form-text">
                Numbers only. Prefix and category are automatic: <b>CSM-[Category]-NNNN</b>
                <br>
                Also accepts: <code>CSM0025</code>, <code>CSM-0025</code>, <code> csm 0025 </code>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="item_category_code" id="edit_item_category_code" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['item_category_code']) ?>">
                    <?= htmlspecialchars($cat['item_category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Itemized Description</label>
              <textarea name="item_description" id="edit_item_description" class="form-control" required></textarea>
            </div>

            <div class="col-md-3">
              <label class="form-label">Unit Quantity</label>
              <input type="number" name="unit_quantity" id="edit_unit_quantity" class="form-control" required min="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Critical Level</label>
              <input type="number" name="unit_crit_level" id="edit_unit_crit_level" class="form-control" required min="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Item Cost</label>
              <input type="number" step="0.01" name="item_cost" id="edit_item_cost" class="form-control" required min="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Source of Funds</label>
              <input type="text" name="source_of_funds" id="edit_source_of_funds" class="form-control">
            </div>

            <div class="col-12">
              <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i>
                Acquisition Date is the original add date and is not edited here.
              </div>
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

<!-- UPDATE AVAILABLE MODAL -->
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
          <input type="number" class="form-control" name="current_unit_quantity" id="avail_current_unit_quantity" min="0" required>

          <div class="form-text">This updates only the “Available to Issue” value.</div>

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

<!-- AST-STYLE RULES MODAL -->
<div class="modal fade ast-rules-modal" id="availabilityRulesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-sliders"></i>&ensp;Set Available Item Rules
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="availabilityRulesForm">
          <input type="hidden" name="inventory_id" id="rules_inventory_id">

          <div class="ast-rule-group">
            <label class="ast-rule-label">Item Code</label>
            <input type="text" class="form-control ast-readonly" id="rules_item_code" readonly placeholder="Multiple items">
            <div class="small text-muted mt-1" id="rules_item_count">1 item selected</div>
          </div>

          <div class="ast-rule-group">
            <label class="ast-rule-label">Available for Requisition (Qty)</label>
            <input type="number" class="form-control" name="current_unit_quantity" id="rules_current_unit_quantity" min="0">
            <div class="ast-rule-help">
              <div>Must be between 0 and total quantity.</div>
              <div>Single set: quantity will update this item's current available qty.</div>
              <div>Single set: status is auto-computed from quantity and rules.</div>
              <div>Total Qty: <span id="rules_total_qty_text">-</span></div>
            </div>
          </div>

          <div class="ast-rule-group">
            <div class="ast-rule-section-title">Allowed Employment Status</div>
            <div class="ast-rule-section-subtitle">
              Choose allowed status per position. "None" means no one can request.
            </div>

            <div class="mb-3">
              <label class="ast-rule-label">Teaching Personnel</label>
              <div class="ast-select-wrap">
                <select id="rules_teaching_status" class="form-select" multiple></select>
              </div>
            </div>

            <div class="mb-3">
              <label class="ast-rule-label">Non-Teaching Personnel</label>
              <div class="ast-select-wrap">
                <select id="rules_non_teaching_status" class="form-select" multiple></select>
              </div>
            </div>

            <div class="ast-rule-status-wrap">
              <div class="ast-rule-inline-note">Current Status: <span id="rules_status_text">—</span></div>
              <div class="ast-rule-inline-note mt-1">Critical Level: <span id="rules_crit_level_text">0</span></div>
            </div>
          </div>

          <div id="rulesMsg" class="mt-2"></div>

          <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4" id="btnSaveRules">Save Rules</button>
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- QR PREVIEW MODAL -->
<div class="modal fade" id="qrPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-qr-code"></i>&ensp;QR Code Preview
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div class="small text-muted mb-2" id="qrPreviewCode">—</div>
        <img id="qrPreviewImg" src="" alt="QR" style="max-width:320px;width:100%;height:auto;border:1px solid #dee2e6;border-radius:12px;background:#fff;padding:10px;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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

<!-- PRINT MODAL -->
<div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-printer"></i>&ensp;Print</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info mb-0">Print module placeholder.</div>
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
        <div id="preview-wrapper">
          <div id="preview"></div>
          <div class="scanner-loading" id="scannerLoading" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:14px;z-index:10;text-align:center;">
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

<!-- EXPORT CSV MODAL -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-download"></i>&ensp;Export CSV
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info">
          <div class="fw-semibold mb-1">Choose export type</div>
          <div class="small">
            <b>Current View</b> exports what you see in the table (filters/sorts included).<br>
            <b>Server Export</b> exports from database (ignores pagination; can filter by category only).
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2">
                <i class="bi bi-table"></i>&ensp;Export Current View (Tabulator)
              </div>
              <div class="small text-muted mb-3">
                Exports the table as currently filtered/sorted.
              </div>
              <button type="button" id="btnExportCurrentView" class="btn btn-primary w-100">
                <i class="bi bi-download"></i> Download Current View CSV
              </button>
            </div>
          </div>

          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-semibold mb-2">
                <i class="bi bi-database"></i>&ensp;Export Full Database (Server)
              </div>

              <div class="row g-2">
                <div class="col-12">
                  <label class="form-label">Filter: Category (optional)</label>
                  <select id="exportCategory" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                      <option value="<?= htmlspecialchars($cat['item_category_code']) ?>">
                        <?= htmlspecialchars($cat['item_category_name']) ?> (<?= htmlspecialchars($cat['item_category_code']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-12">
                  <button type="button" id="btnExportServer" class="btn btn-outline-primary w-100">
                    <i class="bi bi-download"></i> Download Server CSV
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div id="exportMsg" class="mt-3"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- BULK MODAL -->
<div class="modal fade" id="bulkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-file-earmark-arrow-up-fill"></i>&ensp;Bulk Import (CSV)
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
              Upload a CSV file to bulk insert/update inventory items.
              <div class="small mt-1">
                Item Code is <b>numbers only</b>. Full format is automatic:
                <b>CSM-[Category]-0001</b>
              </div>
              <div class="small mt-1">
                Also accepts: <code>0001</code>, <code>CSM0001</code>, <code>CSM-0001</code>, <code> csm 0001 </code>
              </div>
              <div class="small mt-1">
                Acquisition Date is auto-set to <b>today</b> on insert (no acquisition_date column needed).
              </div>
            </div>
            <a class="btn btn-sm btn-outline-primary"
               href="process/csm_inventory_bulk_process.php?action=download_template">
              <i class="bi bi-download"></i>&ensp;Download CSV Template
            </a>
          </div>
        </div>

        <form id="bulkUploadForm" enctype="multipart/form-data">
          <div class="row g-2">
            <div class="col-md-8">
              <label class="form-label">CSV File</label>
              <input type="file" name="csv_file" id="bulkCsvFile" class="form-control" accept=".csv,text/csv" required>
              <div class="form-text">
                Save as <b>CSV UTF-8</b>. Supported delimiters: comma/semicolon/tab.
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Import Mode</label>
              <select class="form-select" name="mode" id="bulkMode">
                <option value="upsert" selected>Upsert (Update if full Item Code exists)</option>
                <option value="insert_only">Insert Only (Skip duplicates)</option>
              </select>
              <div class="form-text">
                If you leave item code blank, it auto-increments per category.
              </div>
            </div>

            <div class="col-12 mt-2">
              <button type="submit" class="btn btn-primary" id="btnBulkUpload">
                <i class="bi bi-upload"></i> Upload & Process
              </button>
              <button type="button" class="btn btn-outline-secondary ms-2" data-bs-dismiss="modal">
                Close
              </button>
            </div>
          </div>
        </form>

        <div id="bulkUploadMsg" class="mt-3"></div>

        <div class="mt-3">
          <details>
            <summary class="small text-muted">Show expected CSV columns</summary>
            <pre class="small mb-0 mt-2">inventory_system_item_code,item_description,item_category_code,unit_quantity,current_unit_quantity,unit_crit_level,item_cost,source_of_funds,status,allowed_employment_status</pre>
          </details>
          <div class="small text-muted mt-2">
            <b>inventory_system_item_code</b>: numbers only (or blank). Example: <code>1</code> becomes <code>CSM-0002-0001</code> (depending on category).
          </div>
          <div class="small text-muted mt-1">
            <b>allowed_employment_status</b>: optional. Examples: <code>ALL</code>, <code>NONE</code>, <code>{"teaching":[1],"non_teaching":[2]}</code>.
          </div>
          <div class="small text-muted mt-1">
            <b>status</b>: numeric. Use <code>1</code> for Available and <code>0</code> for Unavailable.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once FOOTER_PATH; ?>

<script src="https://unpkg.com/tabulator-tables@4.7.2/dist/js/tabulator.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
var consumableData = [];
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = 'process/csm_inventory_process.php';
const BULK_PROCESS_URL = 'process/csm_inventory_bulk_process.php';

let employmentStatusCache = [];
let currentRulesRow = null;

function escHtml(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function absUrl(path){
  const base = String(BASE_URL || '');
  const sep = base.endsWith('/') ? '' : '/';
  path = String(path || '').replace(/^\/+/, '');
  return base + sep + path;
}
function normalizeCategoryDisplay(code){
  const raw = String(code ?? '').trim();
  if(!raw) return '';
  const m1 = raw.match(/^CSM-?(\d+)$/i);
  if(m1) return `CSM-${m1[1]}`;
  if(/^CSM-/i.test(raw)) return raw;
  return `CSM-${raw}`;
}
function groupLabel(code, name){
  const c = String(code ?? '').trim();
  const n = String(name ?? '').trim();
  if(!c && !n) return 'Uncategorized';
  const cDisp = c ? normalizeCategoryDisplay(c) : '';
  if(cDisp && n) return `${cDisp} — ${n}`;
  return cDisp || n;
}
function badgeStatusHtml(status){
  const v = parseInt(status, 10) || 0;
  if(v === 1) return '<span class="badge bg-success">Available</span>';
  return '<span class="badge bg-secondary">Unavailable</span>';
}
function applyItemCodeSmartFilter(input){
  const raw = String(input ?? '').trim();
  if(!raw){
    table.clearFilter(true);
    return;
  }

  const compact = raw.replace(/\s+/g, '');

  const full = compact.match(/^CSM-([A-Za-z0-9\-_]+)-(\d{4})$/i);
  if(full){
    const canon = `CSM-${full[1]}-${full[2]}`;
    table.setFilter("inventory_system_item_code", "=", canon);
    return;
  }

  const m = compact.match(/(\d{1,4})$/);
  if(m){
    const padded = m[1].padStart(4, '0');
    table.setFilter(function(data){
      const code = String(data.inventory_system_item_code ?? '');
      const mm = code.match(/-(\d{4})$/);
      return mm ? mm[1] === padded : false;
    });
    return;
  }

  const normIn = compact.toLowerCase().replace(/[^a-z0-9]/g, '');
  table.setFilter(function(data){
    const codeNorm = String(data.inventory_system_item_code ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');
    return codeNorm === normIn;
  });
}
function openInvImageModal(row){
  if(!row) return;
  const rel = row.display_image || '';
  if(!rel) return;

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

/* ---------- RULES SELECT HELPERS ---------- */
function makeSelect2Data() {
  const data = [{ id: 'NONE', text: 'None' }];
  employmentStatusCache.forEach(s => {
    data.push({
      id: String(s.employment_status_id),
      text: s.status_name || s.status_code || ('Status #' + s.employment_status_id)
    });
  });
  return data;
}
function normalizeNoneSelection($el) {
  let vals = ($el.val() || []).map(String);
  if (vals.includes('NONE') && vals.length > 1) {
    vals = vals.filter(v => v !== 'NONE');
    $el.val(vals).trigger('change.select2');
  }
}
function initSingleRulesSelect2(selector, placeholderText) {
  const $el = $(selector);

  if ($el.hasClass('select2-hidden-accessible')) {
    $el.select2('destroy');
  }

  $el.empty();

  const data = makeSelect2Data();
  data.forEach(function(item) {
    const opt = new Option(item.text, item.id, false, false);
    $el.append(opt);
  });

  $el.select2({
    placeholder: placeholderText,
    width: '100%',
    closeOnSelect: false,
    dropdownParent: $('#availabilityRulesModal'),
    allowClear: false
  });

  $el.off('change.ast').on('change.ast', function() {
    normalizeNoneSelection($(this));
  });
}
function initRulesSelect2() {
  initSingleRulesSelect2('#rules_teaching_status', 'Select teaching status');
  initSingleRulesSelect2('#rules_non_teaching_status', 'Select non-teaching status');
}
function getSelectValues($el) {
  return ($el.val() || []).map(String);
}
function setRulesSelectValues(mode, teachingIds, nonTeachingIds){
  if (mode === 'none') {
    $('#rules_teaching_status').val(['NONE']).trigger('change');
    $('#rules_non_teaching_status').val(['NONE']).trigger('change');
    return;
  }
  if (mode === 'all') {
    $('#rules_teaching_status').val([]).trigger('change');
    $('#rules_non_teaching_status').val([]).trigger('change');
    return;
  }
  $('#rules_teaching_status').val((teachingIds || []).map(String)).trigger('change');
  $('#rules_non_teaching_status').val((nonTeachingIds || []).map(String)).trigger('change');
}
function getAllowedStatusPayloadFromModal(){
  const teachingVals = getSelectValues($('#rules_teaching_status'));
  const nonTeachingVals = getSelectValues($('#rules_non_teaching_status'));

  const teachingNone = teachingVals.includes('NONE');
  const nonTeachingNone = nonTeachingVals.includes('NONE');

  const teachingIds = teachingVals.filter(v => v !== 'NONE').map(v => parseInt(v, 10)).filter(v => v > 0);
  const nonTeachingIds = nonTeachingVals.filter(v => v !== 'NONE').map(v => parseInt(v, 10)).filter(v => v > 0);

  if (teachingVals.length === 0 && nonTeachingVals.length === 0) {
    return 'ALL';
  }

  if (teachingNone && nonTeachingNone) {
    return 'NONE';
  }

  return JSON.stringify({
    teaching: teachingNone ? [] : teachingIds,
    non_teaching: nonTeachingNone ? [] : nonTeachingIds
  });
}
function resetRulesModalState(){
  currentRulesRow = null;
  $('#rulesMsg').html('');
  $('#rules_inventory_id').val('');
  $('#rules_item_code').val('');
  $('#rules_item_count').text('1 item selected');
  $('#rules_current_unit_quantity').val('');
  $('#rules_total_qty_text').text('-');
  $('#rules_crit_level_text').text('0');
  $('#rules_status_text').html('—');
  if ($('#rules_teaching_status').hasClass('select2-hidden-accessible')) {
    $('#rules_teaching_status').val([]).trigger('change');
  }
  if ($('#rules_non_teaching_status').hasClass('select2-hidden-accessible')) {
    $('#rules_non_teaching_status').val([]).trigger('change');
  }
}
function openAvailabilityRulesModal(id){
  resetRulesModalState();
  $('#rules_inventory_id').val(id);
  $('#rules_item_code').val('Loading...');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    dataType: 'json',
    data: { action: 'get_availability_settings', inventory_id: id },
    success: function(res){
      if(!res || !res.success){
        $('#rulesMsg').html('<div class="alert alert-danger">'+ escHtml((res && res.message) ? res.message : 'Record not found.') +'</div>');
        return;
      }

      const d = res.data || {};
      currentRulesRow = d;

      $('#rules_inventory_id').val(d.inventory_id || id);
      $('#rules_item_code').val(d.inventory_system_item_code || '');
      $('#rules_item_count').text('1 item selected');
      $('#rules_current_unit_quantity').val(d.current_unit_quantity ?? 0);
      $('#rules_total_qty_text').text(d.unit_quantity ?? '-');
      $('#rules_crit_level_text').text(d.unit_crit_level ?? 0);
      $('#rules_status_text').html(badgeStatusHtml(d.status));

      const mode = d.allowed_employment_status?.mode || 'all';
      const teaching = Array.isArray(d.allowed_employment_status?.teaching) ? d.allowed_employment_status.teaching : [];
      const nonTeaching = Array.isArray(d.allowed_employment_status?.non_teaching) ? d.allowed_employment_status.non_teaching : [];

      $('#availabilityRulesModal')
        .off('shown.bs.modal.setvals')
        .on('shown.bs.modal.setvals', function () {
          initRulesSelect2();
          setRulesSelectValues(mode, teaching, nonTeaching);
          $('#availabilityRulesModal').off('shown.bs.modal.setvals');
        });

      $('#availabilityRulesModal').modal('show');
    },
    error: function(xhr){
      $('#rulesMsg').html('<div class="alert alert-danger">Error loading availability settings.</div>');
      console.error(xhr.responseText);
    }
  });
}
function loadEmploymentStatuses(){
  return $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    dataType: 'json',
    data: { action: 'list_employment_status' }
  }).done(function(res){
    employmentStatusCache = (res && res.success && Array.isArray(res.data)) ? res.data : [];
  }).fail(function(){
    employmentStatusCache = [];
  });
}

/* ---------- TABULATOR ---------- */
var columns = [
  {
    title:"Image",
    field:"display_image",
    width: 90,
    hozAlign:"center",
    headerSort:false,
    formatter:function(cell){
      const d = cell.getRow().getData();
      const rel = d.display_image || '';
      const url = rel ? absUrl(rel) : '';

      if(url){
        return `
          <div class="inv-thumb-click" title="Click to view">
            <div class="inv-thumb-wrap">
              <img src="${escHtml(url)}" alt="img"
                onerror="this.remove();
                this.parentNode.innerHTML='<i class=&quot;bi bi-image inv-thumb-fallback&quot;></i>';"/>
            </div>
          </div>
        `;
      }
      return `<div class="inv-thumb-wrap"><i class="bi bi-image inv-thumb-fallback"></i></div>`;
    },
    cellClick:function(e, cell){
      const d = cell.getRow().getData();
      if(d && d.display_image) openInvImageModal(d);
    }
  },

  {title:"Item Code", field:"inventory_system_item_code", minWidth:170},
  {title:"Itemized Description", field:"item_description", minWidth:320, formatter:"textarea"},

  {
    title:"Category",
    field:"item_category_code",
    minWidth:240,
    formatter:function(cell){
      const d = cell.getRow().getData();
      return escHtml(groupLabel(d.item_category_code || '', d.item_category_name || ''));
    }
  },

  {title:"Actual Qty", field:"unit_quantity", align:"right", minWidth:90},
  {title:"Available to Issue", field:"current_unit_quantity", align:"right", minWidth:150},

  {
    title:"Status",
    field:"status",
    minWidth:120,
    hozAlign:"center",
    formatter:function(cell){
      return badgeStatusHtml(cell.getValue());
    }
  },

  {title:"Critical Level", field:"unit_crit_level", align:"right", minWidth:130},

  {
    title:"Allowed Status",
    field:"allowed_status_names",
    minWidth:220,
    formatter:function(cell){
      const v = String(cell.getValue() || '');
      return escHtml(v || 'All').replace(/\s\|\s/g, '<br>');
    }
  },

  {title:"Cost", field:"item_cost", align:"right", formatter:"money", formatterParams:{precision:2}, minWidth:110},
  {title:"Source of Funds", field:"source_of_funds", minWidth:160},
  {title:"Acquisition Date", field:"acquisition_date", minWidth:130},
  {title:"Created At", field:"created_at", minWidth:160},
  {title:"Last Updated", field:"last_updated", minWidth:130},

  {
    title: "QR Code",
    field: "inventory_system_item_code",
    minWidth: 140,
    headerSort: false,
    formatter: function(cell){
      var code = cell.getValue();
      if(!code) return "";
      var u = "../tools/qr_image.php?v=" + encodeURIComponent(code);
      return `
        <div class="d-flex align-items-center gap-2">
          <img src="${u}" class="qr-thumb" data-code="${escHtml(code)}"
               style="height:72px;width:72px;object-fit:contain;border:1px solid #dee2e6;border-radius:8px;background:#fff;padding:4px;cursor:pointer;"
               alt="QR">
        </div>
      `;
    }
  },

  {
    title: "Actions",
    field: "inventory_id",
    headerSort: false,
    hozAlign: "center",
    minWidth: 380,
    formatter: function(cell){
      const id = cell.getValue();
      return `
        <button type="button" class="btn btn-sm btn-primary me-1 btn-edit" data-id="${id}">
          <i class="bi bi-pencil-square"></i> Edit
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-available" data-id="${id}">
          <i class="bi bi-box-arrow-in-down"></i> Available
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-rules" data-id="${id}">
          <i class="bi bi-sliders"></i> Rules
        </button>
      `;
    }
  }
];

var table = new Tabulator("#consumeable-table", {
  data: consumableData,
  layout: "fitColumns",
  columns: columns,
  pagination: "local",
  paginationSize: 20,
  paginationSizeSelector: [20,100,500,1000,true],
  movableColumns: true,
  height: "500px",
  groupBy: function(data){
    return groupLabel(data.item_category_code, data.item_category_name);
  },
  groupHeader: function(value, count, data){
    const qty = data.reduce((sum, r) => sum + (parseInt(r.unit_quantity, 10) || 0), 0);
    return `${escHtml(value)} <span class="text-muted small">(${count} items, Qty ${qty})</span>`;
  },
  groupStartOpen: true
});

window.table = table;

function refreshTableData(){
  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    dataType: 'json',
    data: { action: 'list_inventory' },
    success: function(res){
      if(res && res.success){
        table.setData(res.data || []);
      }
    },
    error: function(xhr){
      console.error('list_inventory error:', xhr.responseText);
    }
  });
}

/* ---------- ADD WARNING FLOW ---------- */
$('#btnAddRecordWarn').off('click').on('click', function(){
  $('#addWarningModal').modal('show');
});
$('#btnProceedAdd').off('click').on('click', function(){
  $('#addWarningModal').modal('hide');
  $('#addRecordMsg').html('');
  $('#addRecordModal').modal('show');
});

/* ---------- EXPORT CSV ---------- */
$('#btnExportCurrentView').off('click').on('click', function(){
  try{
    if(!window.table){
      $('#exportMsg').html('<div class="alert alert-danger">Table is not ready.</div>');
      return;
    }
    window.table.download("csv", "csm_inventory_current_view.csv", { delimiter: ",", bom: true });
    $('#exportMsg').html('<div class="alert alert-success">Downloading current view CSV...</div>');
  } catch (e){
    console.error(e);
    $('#exportMsg').html('<div class="alert alert-danger">Export failed.</div>');
  }
});

$('#btnExportServer').off('click').on('click', function(){
  const cat = ($('#exportCategory').val() || '').trim();
  const url = new URL(BULK_PROCESS_URL, window.location.href);
  url.searchParams.set('action', 'export_csv');
  if(cat) url.searchParams.set('item_category_code', cat);
  window.location.href = url.toString();
  $('#exportMsg').html('<div class="alert alert-success">Downloading server CSV...</div>');
});

$('#exportModal').on('shown.bs.modal', function(){
  $('#exportMsg').html('');
});

/* ---------- ADD ---------- */
$('#addInventoryForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $('#addRecordMsg').html('');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    data: $(this).serialize() + '&action=add_inventory',
    success: function(response){
      if($.trim(response) === 'success'){
        $('#addRecordMsg').html('<div class="alert alert-success">Record added!</div>');
        $('#addInventoryForm')[0].reset();
        $('#addRecordModal').modal('hide');
        refreshTableData();
      } else {
        $('#addRecordMsg').html('<div class="alert alert-danger">'+ response +'</div>');
      }
    },
    error: function(xhr){
      $('#addRecordMsg').html('<div class="alert alert-danger">Error adding record.</div>');
      console.error(xhr.responseText);
    }
  });
});

/* ---------- EDIT ---------- */
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

      const d = res.data || {};
      $('#edit_inventory_id').val(d.inventory_id);

      let numericPart = '';
      try{
        const s = String(d.inventory_system_item_code || '');
        const m = s.match(/-(\d{4})$/);
        numericPart = m ? String(parseInt(m[1], 10)) : '';
      }catch(e){ numericPart = ''; }

      $('#edit_inventory_system_item_code').val(numericPart);
      $('#edit_item_description').val(d.item_description || '');
      $('#edit_item_category_code').val(d.item_category_code || '');
      $('#edit_unit_quantity').val(d.unit_quantity ?? 0);
      $('#edit_unit_crit_level').val(d.unit_crit_level ?? 0);
      $('#edit_item_cost').val(d.item_cost ?? 0);
      $('#edit_source_of_funds').val(d.source_of_funds || '');

      $('#editRecordModal').modal('show');
    },
    error: function(xhr){
      alert('Error loading record.');
      console.error(xhr.responseText);
    }
  });
}

$('#editInventoryForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $('#editRecordMsg').html('');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    data: $(this).serialize() + '&action=update_inventory',
    success: function(response){
      if($.trim(response) === 'success'){
        $('#editRecordModal').modal('hide');
        refreshTableData();
      } else {
        $('#editRecordMsg').html('<div class="alert alert-danger">'+ response +'</div>');
      }
    },
    error: function(xhr){
      $('#editRecordMsg').html('<div class="alert alert-danger">Error updating record.</div>');
      console.error(xhr.responseText);
    }
  });
});

/* ---------- AVAILABLE ---------- */
function openAvailableModal(id){
  $('#availableMsg').html('');
  $('#avail_inventory_id').val(id);
  $('#avail_item_label').text('Loading...');
  $('#avail_current_unit_quantity').val('');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    dataType: 'json',
    data: { action: 'get_inventory', inventory_id: id },
    success: function(res){
      if(res && res.success){
        const d = res.data || {};
        $('#avail_item_label').text(`${d.inventory_system_item_code || ''} — ${String(d.item_description || '').slice(0, 60)}`);
        $('#avail_current_unit_quantity').val(d.current_unit_quantity ?? 0);
      }else{
        $('#avail_item_label').text(`ID #${id}`);
        $('#avail_current_unit_quantity').val(0);
      }
      $('#availableModal').modal('show');
    },
    error: function(xhr){
      $('#avail_item_label').text(`ID #${id}`);
      $('#avail_current_unit_quantity').val(0);
      $('#availableModal').modal('show');
      console.error(xhr.responseText);
    }
  });
}

$('#availableForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $('#availableMsg').html('');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    data: $(this).serialize() + '&action=update_available_qty',
    success: function(response){
      if($.trim(response) === 'success'){
        $('#availableModal').modal('hide');
        refreshTableData();
      }else{
        $('#availableMsg').html('<div class="alert alert-danger">'+ response +'</div>');
      }
    },
    error: function(xhr){
      $('#availableMsg').html('<div class="alert alert-danger">Error updating available quantity.</div>');
      console.error(xhr.responseText);
    }
  });
});

/* ---------- RULES SAVE ---------- */
$('#availabilityRulesForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $('#rulesMsg').html('');

  const btn = $('#btnSaveRules');
  const oldHtml = btn.html();
  btn.prop('disabled', true).text('Saving...');

  $.ajax({
    url: PROCESS_URL,
    type: 'POST',
    dataType: 'json',
    data: {
      action: 'update_availability_settings',
      inventory_id: $('#rules_inventory_id').val(),
      current_unit_quantity: $('#rules_current_unit_quantity').val(),
      allowed_status: getAllowedStatusPayloadFromModal()
    },
    success: function(res){
      if(res && res.success){
        $('#rulesMsg').html('<div class="alert alert-success mb-0">'+ escHtml(res.message || 'Rules updated.') +'</div>');
        refreshTableData();
        setTimeout(function(){
          $('#availabilityRulesModal').modal('hide');
        }, 450);
      } else {
        $('#rulesMsg').html('<div class="alert alert-danger">'+ escHtml((res && res.message) ? res.message : 'Update failed.') +'</div>');
      }
    },
    error: function(xhr){
      let msg = 'Error updating rules.';
      try {
        const r = JSON.parse(xhr.responseText);
        if(r && r.message) msg = r.message;
      } catch(e){}
      $('#rulesMsg').html('<div class="alert alert-danger">'+ escHtml(msg) +'</div>');
    },
    complete: function(){
      btn.prop('disabled', false).html(oldHtml);
    }
  });
});

$('#availabilityRulesModal').on('hidden.bs.modal', function(){
  resetRulesModalState();
});

/* ---------- TABLE BUTTONS ---------- */
$('#consumeable-table')
  .off('click', '.btn-edit')
  .on('click', '.btn-edit', function(e){
    e.preventDefault();
    e.stopPropagation();
    openEditModal($(this).data('id'));
  });

$('#consumeable-table')
  .off('click', '.btn-available')
  .on('click', '.btn-available', function(e){
    e.preventDefault();
    e.stopPropagation();
    openAvailableModal($(this).data('id'));
  });

$('#consumeable-table')
  .off('click', '.btn-rules')
  .on('click', '.btn-rules', function(e){
    e.preventDefault();
    e.stopPropagation();
    openAvailabilityRulesModal($(this).data('id'));
  });

/* ---------- QR PREVIEW ---------- */
$('#consumeable-table')
  .off('click', '.qr-thumb')
  .on('click', '.qr-thumb', function(e){
    e.preventDefault();
    e.stopPropagation();
    const code = $(this).data('code');
    if(!code) return;

    const imgUrl = "../tools/qr_image.php?v=" + encodeURIComponent(code);
    $('#qrPreviewCode').text(code);
    $('#qrPreviewImg').attr('src', imgUrl);
    $('#qrPreviewModal').modal('show');
  });

/* ---------- BULK UPLOAD ---------- */
$('#bulkUploadForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $('#bulkUploadMsg').html('');

  const fileInput = document.getElementById('bulkCsvFile');
  if(!fileInput.files || !fileInput.files[0]){
    $('#bulkUploadMsg').html('<div class="alert alert-danger">Please choose a CSV file.</div>');
    return;
  }

  const fd = new FormData();
  fd.append('action', 'bulk_import');
  fd.append('mode', $('#bulkMode').val());
  fd.append('csv_file', fileInput.files[0]);

  $('#btnBulkUpload').prop('disabled', true).text('Processing...');

  $.ajax({
    url: BULK_PROCESS_URL,
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(res){
      if(!res || !res.success){
        const msg = (res && res.message) ? res.message : 'Bulk import failed.';
        $('#bulkUploadMsg').html('<div class="alert alert-danger">'+ escHtml(msg) +'</div>');
        return;
      }

      let html = `
        <div class="alert alert-success">
          <div class="fw-semibold mb-1">Bulk import completed.</div>
          <div class="small">
            Inserted: <b>${res.inserted}</b> |
            Updated: <b>${res.updated}</b> |
            Skipped: <b>${res.skipped}</b> |
            Errors: <b>${res.errors_count}</b>
          </div>
        </div>
      `;

      if(res.errors && res.errors.length){
        html += `<div class="alert alert-warning">
          <div class="fw-semibold mb-2">Some rows had issues (showing up to 20):</div>
          <ul class="mb-0 small">${res.errors.map(e => `<li>${escHtml(e)}</li>`).join('')}</ul>
        </div>`;
      }

      $('#bulkUploadMsg').html(html);
      refreshTableData();
    },
    error: function(xhr){
      console.error(xhr.responseText);
      $('#bulkUploadMsg').html('<div class="alert alert-danger">Server error during bulk import.</div>');
    },
    complete: function(){
      $('#btnBulkUpload').prop('disabled', false).html('<i class="bi bi-upload"></i> Upload & Process');
    }
  });
});

$('#bulkModal').on('shown.bs.modal', function(){
  $('#bulkUploadMsg').html('');
  $('#bulkUploadForm')[0].reset();
});

/* ---------- INITIAL LOAD ---------- */
$(function(){
  loadEmploymentStatuses();
  refreshTableData();
});
</script>

</body>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<!-- html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
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

function showError(msg) {
  const errEl = document.getElementById('scanError');
  const loadingEl = document.getElementById('scannerLoading');
  errEl.textContent = msg;
  errEl.style.display = msg ? 'block' : 'none';
  loadingEl.style.display = 'none';
  if (msg) console.error('QR Error:', msg);
}

function setRunning(running) {
  document.getElementById('btnStart').disabled = running;
  document.getElementById('btnStop').disabled = !running;
  document.getElementById('cameraSelect').disabled = running;
  document.getElementById('scannerLoading').style.display = running ? 'flex' : 'none';
  isScanning = running;
}

async function loadCameras() {
  showError('');
  const cameraSelect = document.getElementById('cameraSelect');
  cameraSelect.innerHTML = `<option value="">Loading cameras...</option>`;

  try {
    const cameras = await Html5Qrcode.getCameras();
    if (!cameras || cameras.length === 0) {
      cameraSelect.innerHTML = `<option value="">No cameras found</option>`;
      showError('No cameras found. Ensure:\n• Camera is connected\n• Browser has permission to access camera\n• HTTPS is enabled (or localhost)\n• No other app is using the camera');
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
    const defaultCam = backCam ? backCam.id : cameras[0].id;
    cameraSelect.value = defaultCam;

  } catch (e) {
    cameraSelect.innerHTML = `<option value="">Camera permission denied</option>`;
    const errMsg = e && e.message ? e.message : String(e);
    showError(`Cannot access cameras: ${errMsg}`);
  }
}

async function startScanner() {
  showError('');
  setRunning(true);

  const cameraSelect = document.getElementById('cameraSelect');
  const selectedCamId = cameraSelect.value;
  if (!selectedCamId) {
    showError('Please select a camera first.');
    setRunning(false);
    return;
  }

  if (html5QrcodeScanner) {
    try { await html5QrcodeScanner.stop(); } catch (e) {}
  }

  html5QrcodeScanner = new Html5Qrcode('preview');

  const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase());
  let config;
  if (isMobile) {
    config = { fps: 15, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0, disableFlip: false, showTorchButtonIfSupported: true, supportedScanTypes: [] };
  } else {
    config = { fps: 10, qrbox: { width: 200, height: 200 }, disableFlip: false, showTorchButtonIfSupported: false, supportedScanTypes: [] };
  }

  try {
    await html5QrcodeScanner.start(
      selectedCamId,
      config,
      (decodedText) => {
        playBeep();
        document.getElementById('lastScanned').textContent = decodedText;
        if (window.table) applyItemCodeSmartFilter(decodedText);
      },
      () => {}
    );
    document.getElementById('scannerLoading').style.display = 'none';
  } catch (e) {
    setRunning(false);
    showError('Failed to start camera: ' + (e && e.message ? e.message : String(e)));
  }
}

async function stopScanner() {
  showError('');
  if (!html5QrcodeScanner || !isScanning) return;

  try {
    await html5QrcodeScanner.stop();
    setRunning(false);
  } catch (e) {
    setRunning(false);
  }
}

$('#scanQrModal').on('shown.bs.modal', function () {
  loadCameras();
  setRunning(false);
  document.getElementById('lastScanned').textContent = '—';
  document.getElementById('preview').innerHTML = '';
});

$('#btnStart').on('click', startScanner);
$('#btnStop').on('click', stopScanner);

$('#cameraSelect').on('change', function () {
  if (isScanning) {
    stopScanner().then(() => startScanner());
  }
});

$('#scanQrModal').on('hidden.bs.modal', function () {
  stopScanner();
  document.getElementById('preview').innerHTML = '';
});
</script>
</html>