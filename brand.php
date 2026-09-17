<?php
header("Location: product.php?tab=brands");
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
        <li class="breadcrumb-item active">Brands</li>
    </ol>
</nav>

<div class="page-header-section">
    <h2><i class="fas fa-tags"></i> Manage Brands</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBrandModel">
        <i class="fas fa-plus"></i> Add Brand
    </button>
</div>

<div class="card">
    <div class="card-body">

        <div class="remove-messages"></div>

        <div class="table-responsive">
            <table class="table table-hover" id="manageBrandTable">
                <thead>
                    <tr>
                        <th scope="col">Brand Name</th>
                        <th scope="col">Status</th>
                        <th scope="col" style="width:120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModel" tabindex="-1" aria-labelledby="addBrandModelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="submitBrandForm" action="php_action/createBrand.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandModelLabel"><i class="fas fa-plus me-2"></i>Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add-brand-messages"></div>

                    <div class="mb-3">
                        <label for="brandName" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="brandName" placeholder="Enter brand name" name="brandName" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="brandStatus" class="form-label">Status</label>
                        <select class="form-select" id="brandStatus" name="brandStatus">
                            <option value="">-- Select Status --</option>
                            <option value="1">Available</option>
                            <option value="2">Not Available</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createBrandBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModel" tabindex="-1" aria-labelledby="editBrandModelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editBrandForm" action="php_action/editBrand.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBrandModelLabel"><i class="fas fa-edit me-2"></i>Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-brand-messages"></div>

                    <div class="modal-loading div-hide">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                            <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading brand...</p>
                        </div>
                    </div>

                    <div class="edit-brand-result">
                        <div class="mb-3">
                            <label for="editBrandName" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="editBrandName" placeholder="Enter brand name" name="editBrandName" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="editBrandStatus" class="form-label">Status</label>
                            <select class="form-select" id="editBrandStatus" name="editBrandStatus">
                                <option value="">-- Select Status --</option>
                                <option value="1">Available</option>
                                <option value="2">Not Available</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer editBrandFooter">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success" id="editBrandBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Brand Modal -->
<div class="modal fade" tabindex="-1" aria-hidden="true" id="removeBrandModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeBrandMessages"></div>
                <div class="text-center py-2">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem;color:var(--danger);margin-bottom:.5rem;display:block;"></i>
                    <p style="font-size:.9rem;color:var(--text);margin:0;">Are you sure you want to remove this brand?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeBrandFooter">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="removeBrandBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Confirm Remove</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="custom/js/brand.js?v=<?= time() ?>"></script>
