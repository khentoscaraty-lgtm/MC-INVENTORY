<?php
header("Location: product.php?tab=categories");
exit;
?>

<style>
    .page-header {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;
    }
    .page-header h2 {
        font-size: 1.15rem; font-weight: 700; color: var(--text); margin: 0;
        display: flex; align-items: center; gap: 8px;
    }
    .page-header h2 i { color: var(--primary); font-size: .95rem; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Categories</li>
    </ol>
</nav>

<div class="page-header-section">
    <h2><i class="fas fa-th-large"></i> Manage Categories</h2>
    <button class="btn btn-success" data-bs-toggle="modal" id="addCategoriesModalBtn" data-bs-target="#addCategoriesModal">
        <i class="fas fa-plus"></i> Add Category
    </button>
</div>

<div class="card">
    <div class="card-body">

        <div class="remove-messages"></div>

        <div class="table-responsive">
            <table class="table table-hover" id="manageCategoriesTable">
                <thead>
                    <tr>
                        <th scope="col">Category Name</th>
                        <th scope="col">Status</th>
                        <th scope="col" style="width:120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoriesModal" tabindex="-1" aria-labelledby="addCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="submitCategoriesForm" action="php_action/createCategories.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoriesModalLabel"><i class="fas fa-plus me-2"></i>Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add-categories-messages"></div>

                    <div class="mb-3">
                        <label for="categoriesName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoriesName" placeholder="Enter category name" name="categoriesName" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="categoriesStatus" class="form-label">Status</label>
                        <select class="form-select" id="categoriesStatus" name="categoriesStatus">
                            <option value="">-- Select Status --</option>
                            <option value="1">Available</option>
                            <option value="2">Not Available</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createCategoriesBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoriesModal" tabindex="-1" aria-labelledby="editCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoriesForm" action="php_action/editCategories.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoriesModalLabel"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-categories-messages"></div>

                    <div class="modal-loading div-hide" style="text-center py-4;">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                            <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading category...</p>
                        </div>
                    </div>

                    <div class="edit-categories-result">
                        <div class="mb-3">
                            <label for="editCategoriesName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="editCategoriesName" placeholder="Enter category name" name="editCategoriesName" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="editCategoriesStatus" class="form-label">Status</label>
                            <select class="form-select" id="editCategoriesStatus" name="editCategoriesStatus">
                                <option value="">-- Select Status --</option>
                                <option value="1">Available</option>
                                <option value="2">Not Available</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer editCategoriesFooter">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success" id="editCategoriesBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Category Modal -->
<div class="modal fade" tabindex="-1" aria-hidden="true" id="removeCategoriesModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeCategoriesMessages"></div>
                <div class="text-center py-2">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem;color:var(--danger);margin-bottom:.5rem;display:block;"></i>
                    <p style="font-size:.9rem;color:var(--text);margin:0;">Are you sure you want to remove this category?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeCategoriesFooter">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="removeCategoriesBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Confirm Remove</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="custom/js/categories.js?v=<?= time() ?>"></script>
