<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('purchase_order');
require_once 'includes/header_sidebar.php';
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
    .star-rating { color: #f5a623; font-size: .85rem; letter-spacing: 1px; }
    .star-rating .empty { color: var(--text-muted); }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Suppliers</li>
    </ol>
</nav>

<div class="page-header-section">
    <h2><i class="fas fa-truck"></i> Manage Suppliers</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
        <i class="fas fa-plus"></i> Add Supplier
    </button>
</div>

<div class="card">
    <div class="card-body">

        <div class="remove-messages"></div>

        <div class="table-responsive">
            <table class="table table-hover" id="manageSupplierTable">
                <thead>
                    <tr>
                        <th scope="col">Supplier Name</th>
                        <th scope="col">Contact Person</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Email</th>
                        <th scope="col">Rating</th>
                        <th scope="col">Lead Time</th>
                        <th scope="col">Status</th>
                        <th scope="col" style="width:140px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addSupplierForm" action="php_action/createSupplier.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSupplierModalLabel"><i class="fas fa-plus me-2"></i>Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add-supplier-messages"></div>

                    <div class="mb-3">
                        <label for="supplierName" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplierName" placeholder="Enter supplier name" name="supplierName" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="contactPerson" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contactPerson" placeholder="Enter contact person" name="contactPerson" autocomplete="off">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" placeholder="Enter phone number" name="phone" autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="Enter email address" name="email" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" placeholder="Enter address" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <select class="form-select" id="rating" name="rating">
                                <option value="0">-- No Rating --</option>
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5">5 Stars</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="leadTimeDays" class="form-label">Lead Time (days)</label>
                            <input type="number" class="form-control" id="leadTimeDays" name="leadTimeDays" value="7" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createSupplierBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editSupplierForm" action="php_action/editSupplier.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="supplierId" id="editSupplierId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel"><i class="fas fa-edit me-2"></i>Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-supplier-messages"></div>

                    <div class="modal-loading div-hide">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                            <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading supplier...</p>
                        </div>
                    </div>

                    <div class="edit-supplier-result">
                        <div class="mb-3">
                            <label for="editSupplierName" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editSupplierName" placeholder="Enter supplier name" name="editSupplierName" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="editContactPerson" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="editContactPerson" placeholder="Enter contact person" name="editContactPerson" autocomplete="off">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editPhone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="editPhone" placeholder="Enter phone number" name="editPhone" autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editEmail" placeholder="Enter email address" name="editEmail" autocomplete="off">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="editAddress" placeholder="Enter address" name="editAddress" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editRating" class="form-label">Rating</label>
                                <select class="form-select" id="editRating" name="editRating">
                                    <option value="0">-- No Rating --</option>
                                    <option value="1">1 Star</option>
                                    <option value="2">2 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="5">5 Stars</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editLeadTimeDays" class="form-label">Lead Time (days)</label>
                                <input type="number" class="form-control" id="editLeadTimeDays" name="editLeadTimeDays" value="7" min="1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer editSupplierFooter">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success" id="editSupplierBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Supplier Modal -->
<div class="modal fade" tabindex="-1" aria-hidden="true" id="removeSupplierModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeSupplierMessages"></div>
                <div class="text-center py-2">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem;color:var(--danger);margin-bottom:.5rem;display:block;"></i>
                    <p style="font-size:.9rem;color:var(--text);margin:0;">Are you sure you want to remove this supplier?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeSupplierFooter">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="removeSupplierBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Confirm Remove</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="custom/js/supplier.js?v=<?= time() ?>"></script>
