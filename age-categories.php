<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$flash = get_flash_message();
$pdo = app_pdo();
$ageCategories = $pdo->query(
    'SELECT AgeCategoryId, CategoryName, IsActive
     FROM AgeCategoryMaster
     ORDER BY AgeCategoryId DESC'
)->fetchAll();

render_admin_header('Age Category Master', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'age-category');
?>
<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title mb-1">Age Category List</h4>
                        <p class="card-title-desc mb-0">Manage Age Category records with search, sorting, pagination, and edit actions.</p>
                    </div>
                    <a href="age-category-form.php" class="btn btn-primary waves-effect waves-light">
                        <i class="ri-add-line align-middle me-1"></i> Add New Age Category
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="age-category-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Age Category ID</th>
                                <th>Category Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ageCategories as $ageCategory): ?>
                                <tr>
                                    <td><?php echo (int) $ageCategory['AgeCategoryId']; ?></td>
                                    <td><?php echo htmlspecialchars($ageCategory['CategoryName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ((int) $ageCategory['IsActive'] === 1): ?>
                                            <span class="badge rounded-pill bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="age-category-form.php?id=<?php echo (int) $ageCategory['AgeCategoryId']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#age-category-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/datatables.net/js/jquery.dataTables.min.js'),
    app_asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js'),
    app_asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js'),
    app_asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js'),
]);
