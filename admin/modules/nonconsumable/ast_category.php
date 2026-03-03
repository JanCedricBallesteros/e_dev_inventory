<?php
// admin/modules/nonconsumable/ast_category.php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(
    role_has("SUPER_ADMIN") ||
    role_has("ADMIN") ||
    (
        (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) &&
        user_has_access("
        
        
        ")
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
    <style>
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .summary-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; }
        .summary-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
        .summary-value { font-size: 18px; font-weight: 700; }
        .item-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; background: #f8f9fa; cursor: zoom-in; }
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
        }
        .img-preview { max-width: 100%; max-height: 70vh; border-radius: 8px; }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">Non-Consumable Categories</h1>
        <p class="text-muted small mb-0">Manage item categories for non-consumable inventory.</p>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header bg-eclearance text-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tags-fill"></i>&ensp;Manage Non-Consumable Categories</span>
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#bulkAddModal">
                    <i class="bi bi-plus-circle"></i> Add Category
                </button>
            </div>

            <div class="card-body mt-3 bg-white">
                <div id="catMsg" class="alert alert-danger d-none mb-3"></div>

                <!-- Category Table -->
                <div class="table-responsive">
                    <div id="categoryTable"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ADD CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i>&ensp;Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCategoryForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_category_name" required placeholder="e.g., Furniture, Equipment">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Photo</label>
                        <input type="file" class="form-control" name="category_photo" accept="image/*" id="addPhotoInput">
                        <small class="text-muted">JPG, PNG, WEBP, GIF (optional)</small>
                        <div id="addPhotoPreview"></div>
                    </div>

                    <div id="addMsg"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Add Category
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
            <form id="editCategoryForm" enctype="multipart/form-data">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_category_name" id="edit_category_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Photo</label>
                        <div id="currentPhotoDisplay" class="mb-2"></div>
                        <input type="file" class="form-control" name="category_photo" accept="image/*" id="editPhotoInput">
                        <small class="text-muted">Upload new image to replace current photo</small>
                        <div id="editPhotoPreview"></div>
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

    <!-- BULK ADD MODAL -->
    <div class="modal fade" id="bulkAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload"></i>&ensp;Bulk Add Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkAddForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Tip:</strong> Add multiple categories at once. Codes will be auto-generated.
                    </div>
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Rows</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkAddRow">
                            <i class="bi bi-plus-circle"></i> Add Row
                        </button>
                    </div>
                    <div id="bulkRows" class="d-flex flex-column gap-2"></div>

                    <div id="bulkMsg"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bulkSubmitBtn">
                        <i class="bi bi-save2"></i> Save All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i>&ensp;Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this category?</p>
                <div class="alert alert-warning mb-0">
                    <strong id="deleteCategoryInfo"></strong>
                    <p class="mb-0 mt-2 small">This action cannot be undone.</p>
                </div>
                <div id="deleteMsg" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash"></i> Delete Category
                </button>
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
                <img id="imagePreviewImg" class="img-preview" src="" alt="Category image preview">
            </div>
        </div>
    </div>
</div>

<?php include_once FOOTER_PATH; ?>

<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/tabulator.min.js"></script>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
const PROCESS_URL = BASE_URL + 'admin/modules/nonconsumable/process/ast_category_process.php';

let table;
let categoryToDelete = null;

// Initialize Tabulator
function initTable() {
    table = new Tabulator("#categoryTable", {
        ajaxURL: PROCESS_URL,
        ajaxParams: { action: "list_categories" },
        ajaxConfig: "POST",
        layout: "fitColumns",
        height: "600px",
        responsiveLayout: "collapse",
        placeholder: "No categories found",
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        columns: [
            {
                title: "Photo",
                field: "category_photo_thumb_url",
                width: 80,
                headerSort: false,
                formatter: function(cell) {
                    const thumb = cell.getValue();
                    const full = cell.getRow().getData().category_photo_url;
                    const name = cell.getRow().getData().item_category_name || '';
                    if (thumb) {
                        return `<div class="thumb-wrap"><img src="${thumb}" data-full="${full || thumb}" class="item-thumb js-thumb-preview" alt="Category photo" loading="lazy"></div>`;
                    }
                    const initials = (String(name).trim().split(/\s+/).map(w => w.charAt(0)).filter(Boolean).slice(0,2).join('') || 'CT').toUpperCase();
                    return `<div class="thumb-wrap"><div class="item-badge" title="${name}">${initials}</div></div>`;
                }
            },
            {
                title: "Category Name",
                field: "item_category_name",
                widthGrow: 2,
                minWidth: 150,
                headerFilter: "input",
                formatter: function(cell) {
                    return `<strong>${cell.getValue()}</strong>`;
                }
            },
            {
                title: "Date Modified",
                field: "created_at",
                width: 150,
                formatter: function(cell) {
                    const date = new Date(cell.getValue());
                    return date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                }
            },
            {
                title: "Actions",
                field: "category_id",
                width: 160,
                headerSort: false,
                formatter: function(cell) {
                    const id = cell.getValue();
                    return `
                        <button class="btn btn-sm btn-warning me-1 btn-edit" data-id="${id}" title="Edit">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete" data-id="${id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        ajaxResponse: function(url, params, response) {
            return response.data || [];
        },
        rowClick: function(e, row) {
            // Handle edit/delete button clicks
            const target = e.target;
            if (target.classList.contains('btn-edit') || target.closest('.btn-edit')) {
                const btn = target.classList.contains('btn-edit') ? target : target.closest('.btn-edit');
                editCategory(btn.getAttribute('data-id'));
            } else if (target.classList.contains('btn-delete') || target.closest('.btn-delete')) {
                const btn = target.classList.contains('btn-delete') ? target : target.closest('.btn-delete');
                deleteCategory(btn.getAttribute('data-id'));
            }
        }
    });
}

// Add category
$('#addCategoryForm').on('submit', function(e) {
    e.preventDefault();
    $('#addMsg').html('');

    const formData = new FormData(this);
    formData.append('action', 'add_category');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#addMsg').html('<div class="alert alert-success">' + res.message + '</div>');
                $('#addCategoryForm')[0].reset();
                $('#addPhotoPreview').html('');
                table.replaceData();
                setTimeout(() => $('#addCategoryModal').modal('hide'), 1500);
            } else {
                $('#addMsg').html('<div class="alert alert-danger">' + res.message + '</div>');
            }
        },
        error: function() {
            $('#addMsg').html('<div class="alert alert-danger">Error adding category.</div>');
        }
    });
});

// Photo preview for add form
$('#addPhotoInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#addPhotoPreview').html(`<img src="${e.target.result}" class="preview-image">`);
        };
        reader.readAsDataURL(file);
    } else {
        $('#addPhotoPreview').html('');
    }
});

// Edit category
function editCategory(id) {
    $('#editMsg').html('');
    $('#editPhotoPreview').html('');

    $.post(PROCESS_URL, { action: 'get_category', category_id: id }, function(res) {
        if (res.success) {
            const data = res.data;
            $('#edit_category_id').val(data.category_id);
            $('#edit_category_name').val(data.item_category_name);
            
            if (data.category_photo_url) {
                $('#currentPhotoDisplay').html(`
                    <div class="text-muted small mb-1">Current photo:</div>
                    <img src="${data.category_photo_url}" class="preview-image">
                `);
            } else {
                $('#currentPhotoDisplay').html('<div class="text-muted small">No photo uploaded</div>');
            }
            
            $('#editCategoryModal').modal('show');
        } else {
            alert(res.message || 'Failed to load category data.');
        }
    }, 'json');
}

// Update category
$('#editCategoryForm').on('submit', function(e) {
    e.preventDefault();
    $('#editMsg').html('');

    const formData = new FormData(this);
    formData.append('action', 'update_category');

    $.ajax({
        url: PROCESS_URL,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#editMsg').html('<div class="alert alert-success">' + res.message + '</div>');
                table.replaceData();
                setTimeout(() => $('#editCategoryModal').modal('hide'), 1500);
            } else {
                $('#editMsg').html('<div class="alert alert-danger">' + res.message + '</div>');
            }
        },
        error: function() {
            $('#editMsg').html('<div class="alert alert-danger">Error updating category.</div>');
        }
    });
});

// Photo preview for edit form
$('#editPhotoInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#editPhotoPreview').html(`
                <div class="text-muted small mt-2 mb-1">New photo preview:</div>
                <img src="${e.target.result}" class="preview-image">
            `);
        };
        reader.readAsDataURL(file);
    } else {
        $('#editPhotoPreview').html('');
    }
});

// Delete category
function deleteCategory(id) {
    categoryToDelete = id;
    $('#deleteMsg').html('');

    // Get category info
    const rowData = table.getData().find(row => row.category_id == id);
    if (rowData) {
        $('#deleteCategoryInfo').text(`${rowData.item_category_name}`);
    }

    $('#deleteModal').modal('show');
}

// Enlarge photo on click — matches ast_inventory.php pattern
$('#categoryTable').on('click', '.js-thumb-preview, .item-badge', function() {
    const full = $(this).data('full') || $(this).attr('src');
    if (!full) return;
    $('#imagePreviewImg').attr('src', full);
    $('#imagePreviewModal').modal('show');
});

$('#confirmDeleteBtn').on('click', function() {
    if (!categoryToDelete) return;

    $(this).prop('disabled', true);
    $('#deleteMsg').html('');

    $.post(PROCESS_URL, {
        action: 'delete_category',
        category_id: categoryToDelete
    }, function(res) {
        if (res.success) {
            $('#deleteMsg').html('<div class="alert alert-success">' + res.message + '</div>');
            table.replaceData();
            setTimeout(() => {
                $('#deleteModal').modal('hide');
                categoryToDelete = null;
            }, 1500);
        } else {
            $('#deleteMsg').html('<div class="alert alert-danger">' + res.message + '</div>');
        }
    }, 'json').fail(function() {
        $('#deleteMsg').html('<div class="alert alert-danger">Error deleting category.</div>');
    }).always(function() {
        $('#confirmDeleteBtn').prop('disabled', false);
    });
});

    function addBulkRow(name = '') {
        const idx = $('#bulkRows .bulk-row').length;
        const row = `
            <div class="bulk-row border rounded p-2">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small mb-1">Category Name</label>
                        <input type="text" class="form-control form-control-sm" name="bulk_names[]" value="${name}" required placeholder="e.g., Furniture">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label fw-semibold small mb-1">Photo (optional)</label>
                        <input type="file" class="form-control form-control-sm" name="bulk_photo_${idx}" accept="image/*">
                    </div>
                    <div class="col-12 col-md-1 d-grid">
                        <label class="form-label fw-semibold small mb-1">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-bulk-remove" title="Remove row">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#bulkRows').append(row);
    }

    $('#bulkAddRow').on('click', function() {
        addBulkRow();
    });

    $('#bulkRows').on('click', '.btn-bulk-remove', function() {
        $(this).closest('.bulk-row').remove();
        // Re-index file inputs to keep backend mapping consistent
        $('#bulkRows .bulk-row').each(function(i) {
            $(this).find('input[type="file"]').attr('name', 'bulk_photo_' + i);
        });
    });

    $('#bulkAddForm').on('submit', function(e) {
        e.preventDefault();
        $('#bulkMsg').html('');

        const formData = new FormData(this);
        formData.append('action', 'bulk_add_categories');
        $('#bulkSubmitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

        $.ajax({
            url: PROCESS_URL,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    let msg = `<div class="alert alert-success">
                        <strong>Success!</strong> Inserted: ${res.inserted}, Skipped: ${res.skipped}
                    </div>`;
                
                if (res.errors && res.errors.length > 0) {
                    msg += '<div class="alert alert-warning"><strong>Errors:</strong><ul class="mb-0">';
                    res.errors.forEach(err => {
                        msg += `<li>${err}</li>`;
                    });
                    msg += '</ul></div>';
                }
                
                    $('#bulkMsg').html(msg);
                    $('#bulkAddForm')[0].reset();
                    $('#bulkRows').html('');
                    addBulkRow();
                    table.replaceData();
                    
                } else {
                    $('#bulkMsg').html('<div class="alert alert-danger">' + res.message + '</div>');
                }
            },
            error: function() {
                $('#bulkMsg').html('<div class="alert alert-danger">Error processing CSV file.</div>');
            }
        }).always(function() {
            $('#bulkSubmitBtn').prop('disabled', false).html('<i class="bi bi-upload"></i> Upload & Process');
        });
    });

    // Initialize on document ready
    $(document).ready(function() {
        initTable();
        addBulkRow();
    });

// Reset forms when modals close
$('#addCategoryModal').on('hidden.bs.modal', function() {
    $('#addCategoryForm')[0].reset();
    $('#addPhotoPreview').html('');
    $('#addMsg').html('');
});

$('#editCategoryModal').on('hidden.bs.modal', function() {
    $('#editCategoryForm')[0].reset();
    $('#currentPhotoDisplay').html('');
    $('#editPhotoPreview').html('');
    $('#editMsg').html('');
});

    $('#bulkAddModal').on('hidden.bs.modal', function() {
        $('#bulkAddForm')[0].reset();
        $('#bulkMsg').html('');
        $('#bulkRows').html('');
        addBulkRow();
    });

$('#deleteModal').on('hidden.bs.modal', function() {
    categoryToDelete = null;
    $('#deleteMsg').html('');
});
</script>
</body>
</html>
