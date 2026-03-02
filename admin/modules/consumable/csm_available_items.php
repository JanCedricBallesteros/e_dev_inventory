<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!($g_user_role == "ADMIN" || "ADMINSTAFF")) {
    header("Location: " . BASE_URL);
    exit();
}

// Your page logic here
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
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
                    <i class="bi bi-check-circle"></i>&ensp;Available Consumable Items
                </div>
                <div class="card-body mt-3 bg-white">
                    <!-- Your content here dito mo lagay ha, LABYU -->

                      <!-- Content for List tab -->
                    <div id="available-items-table"></div>

                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

</body>

<!-- UPDATE AVAILABLE ITEM MODAL -->
<div class="modal fade" id="updateItemModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-eclearance text-white">
        <h5 class="modal-title">
          <i class="bi bi-pencil-square"></i> Update Available Item
        </h5>
        <button type="button" class="btn-close btn-close-white" aria-label="Close" id="modalCloseX"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="editRowIndex">

        <div class="mb-3">
          <label class="form-label fw-semibold">Available Quantity</label>
          <input type="number" id="editAvailableQty" class="form-control" min="0">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">User Type</label>
          <select id="editUserType" class="form-select">
            <option value="ADMIN">ADMIN</option>
            <option value="ADMINSTAFF">ADMINSTAFF</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="modalCancelBtn">Cancel</button>
        <button class="btn btn-eclearance" id="saveUpdateBtn">
          <i class="bi bi-save"></i> Save Changes
        </button>
      </div>

    </div>
  </div>
</div>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    var modalEl = document.getElementById("updateItemModal");
    var updateModal = new bootstrap.Modal(modalEl);

    document.getElementById("modalCloseX").addEventListener("click", function () {
        updateModal.hide();
    });

    document.getElementById("modalCancelBtn").addEventListener("click", function () {
        updateModal.hide();
    });

    var availableItemsData = [
        {
            item_code: "CON-001",
            item_name: "Bond Paper",
            description: "A4 White Paper",
            available_qty: 120,
            unit: "ream",
            current_qty: 150,
            crit_lvl: 30,
            user_type: "ADMIN"
        },
        {
            item_code: "CON-002",
            item_name: "Ink Cartridge",
            description: "Black Ink",
            available_qty: 5,
            unit: "pcs",
            current_qty: 20,
            crit_lvl: 10,
            user_type: "ADMINSTAFF"
        }
    ];

    var table = new Tabulator("#available-items-table", {
        data: availableItemsData,
        layout: "fitColumns",
        pagination: "local",
        paginationSize: 10,
        height: "500px",
        responsiveLayout: false,

        columns: [
            { title: "Item Name", field: "item_name", minWidth: 90, frozen: true, headerFilter: "input", widthGrow: 2 },
            { title: "Item Code", field: "item_code", minWidth: 70, headerFilter: "input", widthGrow: 1 },
            { title: "Description", field: "description", minWidth: 120, headerFilter: "input", widthGrow: 2 },
            { title: "Available Qty", field: "available_qty", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ qty", headerFilterFunc: "<=" },
            { title: "Unit", field: "unit", minWidth: 120, headerFilter: "input" },
            { title: "Current Qty", field: "current_qty", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ qty", headerFilterFunc: "<=" },
            { title: "Crit Lvl", field: "crit_lvl", minWidth: 120, hozAlign: "right", headerFilter: "number", headerFilterPlaceholder: "≤ qty", headerFilterFunc: "<=" },
            { title: "User Type", field: "user_type", minWidth: 120, headerFilter: "input", widthGrow: 1 },
            {
                title: "Action",
                formatter: () =>
                    `<button class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Update
                    </button>`,
                minWidth: 90, widthGrow: 1, frozen: true,
                hozAlign: "center",
                cellClick: function (e, cell) {
                    const row = cell.getRow();
                    const data = row.getData();

                    document.getElementById("editAvailableQty").value = data.available_qty;
                    document.getElementById("editUserType").value = data.user_type;
                    document.getElementById("editRowIndex").value = row.getIndex();

                    updateModal.show();
                }
            }
        ]
    });

document.getElementById("saveUpdateBtn").addEventListener("click", function () {

        const row = table.getRow(
            document.getElementById("editRowIndex").value
        );

        row.update({
            available_qty: parseInt(
                document.getElementById("editAvailableQty").value
            ),
            user_type: document.getElementById("editUserType").value
        });

        updateModal.hide();
    });

});


</script>

</html>