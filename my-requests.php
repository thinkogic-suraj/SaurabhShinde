<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$flash = get_flash_message();
$pdo = app_pdo();
$requests = $pdo->query(
    'SELECT cr.CitizenRequestId, cr.RequestNo, cu.Name, cu.MobileNo, rtm.RequestTypeName,
            w.WardName, a.AreaName, rsm.StatusName, cr.RaisedDate, cr.IsActive
     FROM CitizenRequest cr
     LEFT JOIN CitizenUser cu ON cu.CitizenUserId = cr.CitizenUserId
     LEFT JOIN RequestTypeMaster rtm ON rtm.RequestTypeId = cr.RequestTypeId
     LEFT JOIN Ward w ON w.WardId = cr.WardId
     LEFT JOIN Area a ON a.AreaId = cr.AreaId
     LEFT JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
     ORDER BY cr.CitizenRequestId DESC'
)->fetchAll();

render_admin_header('My Requests', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'my-requests');
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
                        <h4 class="card-title mb-1">My Requests List</h4>
                        <p class="card-title-desc mb-0">View all citizen requests and update only their status from the edit page.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="my-requests-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Request No</th>
                                <th>Name</th>
                                <th>Mobile No</th>
                                <th>Request Type</th>
                                <th>Ward</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Raised Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['RequestNo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($request['Name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($request['MobileNo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($request['RequestTypeName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($request['WardName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($request['AreaName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php
                                        $statusName = (string) ($request['StatusName'] ?? '');
                                        $badgeClass = $statusName === '' ? 'bg-secondary' : 'bg-success';
                                        ?>
                                        <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($statusName !== '' ? $statusName : 'Not Set', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($request['RaisedDate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="my-request-form.php?id=<?php echo (int) $request['CitizenRequestId']; ?>" class="btn btn-sm btn-outline-primary">
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
        $('#my-requests-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[7, 'desc']]
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
