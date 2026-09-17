<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('products');
require_once 'includes/header_sidebar.php';
?>

<div class="row my-4">
		<div class="card">
			<div class="card-header">
				<i class="fas fa-file-import"></i>	Import Brand
			</div>
			<!-- /card-header -->
			<div class="card-body">

				<form class="form-horizontal" id="submitImportForm" action="php_action/createBrandImport.php" method="POST" enctype="multipart/form-data">
				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
				<div id="add-product-messages"></div>
				<div class="form-group">
	        	<label for="brandfile" class="col-sm-3 control-label">Import Brand FIle: </label>
	        	<label class="col-sm-1 control-label">: </label>
				    <div class="col-sm-8">
					    <!-- the avatar markup -->
							<div id="kv-avatar-errors-1" class="center-block" style="display:none;"></div>
					    <div class="kv-avatar center-block">
					        <input type="file" class="form-control" id="brandfile" placeholder="Import Brand FIle" name="brandfile" class="file-loading" style="width:auto;"/>

					    </div>
						<a href="assests/import/brand.xlsx" download>Sample file</a>

				    </div>
	        	</div> <!-- /form-group-->

				  <div class="form-group">
				    <div class="col-sm-offset-2 col-sm-10">
				      <button type="submit" class="btn btn-success" id="importBrandBtn"> <i class="fas fa-check-circle"></i> Import</button>
				    </div>
				  </div>
				</form>

			</div>
			<!-- /card-body -->
		</div>
	</div>
	<!-- /col-dm-12 -->
</div>
<!-- /row -->
</div>
<!-- /row -->
<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="custom/js/import.js"></script>
