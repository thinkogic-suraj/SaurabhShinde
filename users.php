<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/rbac.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$currentAdmin = current_admin_context($pdo);
$roleIds = app_role_ids($pdo);

if ($currentAdmin === null || !can_access_user_management($currentAdmin, $roleIds)) {
    set_flash_message('danger', 'You are not authorized to access user management.');
    header('Location: dashboard.php');
    exit;
}

$isSuperAdmin = is_super_admin_user($currentAdmin, $roleIds);
$adminRoleId = $roleIds['admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $userIdToDelete = (int) ($_POST['user_id'] ?? 0);

    if ($userIdToDelete > 0) {
        $targetStmt = $pdo->prepare(
            'SELECT EmployeeId, UserName, RoleId, CreatedBy, IsActive
             FROM Employee
             WHERE EmployeeId = :employee_id
             LIMIT 1'
        );
        $targetStmt->execute([
            'employee_id' => $userIdToDelete,
        ]);
        $targetUser = $targetStmt->fetch();

        if (!$targetUser || !can_soft_delete_managed_user($currentAdmin, $targetUser, $roleIds)) {
            set_flash_message('danger', 'You are not authorized to delete this user.');
        } elseif ((int) ($targetUser['IsActive'] ?? 0) !== 1) {
            set_flash_message('danger', 'This user is already inactive.');
        } else {
            $deleteStmt = $pdo->prepare(
                'UPDATE Employee
                 SET IsActive = 0
                 WHERE EmployeeId = :employee_id'
            );
            $deleteStmt->execute([
                'employee_id' => $userIdToDelete,
            ]);

            if ($deleteStmt->rowCount() > 0) {
                set_flash_message('success', 'User deactivated successfully.');
            } else {
                set_flash_message('danger', 'Selected user record was not found.');
            }
        }
    }

    header('Location: users.php');
    exit;
}

if ($isSuperAdmin) {
    $userStmt = $pdo->prepare(
        'SELECT e.EmployeeId,
                e.UserName,
                e.MobileNo,
                e.Email,
                e.RoleId,
                e.CreatedBy,
                e.IsMobileVerified,
                e.IsActive
         FROM Employee e
         WHERE e.RoleId = :admin_role_id
            OR e.EmployeeId = :current_admin_id
         ORDER BY CASE WHEN e.EmployeeId = :current_admin_id THEN 0 ELSE 1 END,
                  e.EmployeeId DESC'
    );
    $userStmt->execute([
        'admin_role_id' => $adminRoleId,
        'current_admin_id' => (int) $currentAdmin['EmployeeId'],
    ]);
} else {
    $userStmt = $pdo->prepare(
        'SELECT e.EmployeeId,
                e.UserName,
                e.MobileNo,
                e.Email,
                e.RoleId,
                e.CreatedBy,
                e.IsMobileVerified,
                e.IsActive
         FROM Employee e
         WHERE e.RoleId = :admin_role_id
         ORDER BY e.EmployeeId DESC'
    );
    $userStmt->execute([
        'admin_role_id' => $adminRoleId,
    ]);
}

$users = $userStmt->fetchAll();
$flash = get_flash_message();

render_admin_header('User Management', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'user', false);
?>
<style>
    #user-table td, #user-table th {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .dataTables_paginate .page-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }
    .action-muted {
        color: #94a3b8;
        font-size: 0.875rem;
    }
</style>
<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function () {
                    var alertNode = document.querySelector('.alert');
                    if (alertNode) {
                        var alert = new bootstrap.Alert(alertNode);
                        alert.close();
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="page-title-box">
                        <h4 class="mb-1">User Management</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">User Management</li>
                            </ol>
                        </div>
                    </div>
                    <a href="user-form.php" class="btn btn-primary waves-effect waves-light" style="background-color: #002253; border-color: #002253;">
                        <i class="ri-add-line align-middle me-1"></i> Add User
                    </a>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label" for="filter-status">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" selected>Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="search-box">
                            <label class="form-label" for="custom-search">&nbsp;</label>
                            <div class="position-relative">
                                <input type="search" id="custom-search" class="form-control rounded" placeholder="Search...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="user-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Username</th>
                                <th>Mobile Number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th style="width: 160px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                if (!can_view_managed_user($currentAdmin, $user, $roleIds)) {
                                    continue;
                                }

                                $fullUserName = (string) $user['UserName'];
                                $fullEmail = (string) ($user['Email'] ?? '');
                                $displayUserName = mb_strlen($fullUserName) > 20 ? mb_substr($fullUserName, 0, 20) . '...' : $fullUserName;
                                $displayEmail = mb_strlen($fullEmail) > 25 ? mb_substr($fullEmail, 0, 25) . '...' : $fullEmail;
                                $isActive = (int) $user['IsActive'] === 1;
                                $canEdit = can_edit_managed_user($currentAdmin, $user, $roleIds)
                                    && ($isActive || can_change_managed_user_status($currentAdmin, $user, $roleIds));
                                $canDelete = $isActive && can_soft_delete_managed_user($currentAdmin, $user, $roleIds);
                                ?>
                                <tr>
                                    <td title="<?php echo htmlspecialchars($fullUserName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayUserName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $user['MobileNo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td title="<?php echo htmlspecialchars($fullEmail, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayEmail, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge rounded-pill bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canEdit || $canDelete): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($canEdit): ?>
                                                    <a href="user-form.php?id=<?php echo (int) $user['EmployeeId']; ?>" class="btn btn-sm" style="background-color: #002253; border-color: #002253; color: white;">
                                                        <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($canDelete): ?>
                                                    <form method="POST" action="" class="m-0 delete-user-form">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['EmployeeId']; ?>">
                                                        <button type="submit" class="btn btn-sm" style="background-color: #dc3545; border-color: #dc3545; color: white;">
                                                            <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="action-muted">No actions</span>
                                        <?php endif; ?>
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
        const userTable = $('#user-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[0, 'asc']]
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function () {
            userTable.search(this.value).draw();
        });

        $('#filter-status').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            userTable.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#filter-status').trigger('change');

        document.querySelectorAll('.delete-user-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Are you sure you want to delete this user?')) {
                    event.preventDefault();
                }
            });
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
