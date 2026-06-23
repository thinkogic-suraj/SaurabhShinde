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
], 'my-requests', false);
?>
<style>
    #my-requests-table td, #my-requests-table th {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .dataTables_paginate .page-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
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
                setTimeout(function() {
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
                        <h4 class="mb-1">My Requests</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">My Requests</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="my-requests-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Request No</th>
                                <th>Name</th>
                                <th>Mobile No</th>
                                <th>Request Type</th>
                                <th>Ward</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Raised Date</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                $fullName = (string) ($request['Name'] ?? '');
                                $displayName = mb_strlen($fullName) > 20 ? mb_substr($fullName, 0, 20) . '...' : $fullName;
                                $fullRequestType = (string) ($request['RequestTypeName'] ?? '');
                                $displayRequestType = mb_strlen($fullRequestType) > 20 ? mb_substr($fullRequestType, 0, 20) . '...' : $fullRequestType;
                                $fullWard = (string) ($request['WardName'] ?? '');
                                $displayWard = mb_strlen($fullWard) > 20 ? mb_substr($fullWard, 0, 20) . '...' : $fullWard;
                                $fullArea = (string) ($request['AreaName'] ?? '');
                                $displayArea = mb_strlen($fullArea) > 20 ? mb_substr($fullArea, 0, 20) . '...' : $fullArea;
                                $statusName = (string) ($request['StatusName'] ?? '');
                                $normalizedStatus = strtolower(trim($statusName));
                                if ($normalizedStatus === '' || $normalizedStatus === 'not set') {
                                    $badgeClass = 'bg-secondary';
                                    $badgeStyle = '';
                                } elseif ($normalizedStatus === 'raised') {
                                    $badgeClass = '';
                                    $badgeStyle = 'background-color: #F8D7DA; color: #842029;';
                                } elseif (in_array($normalizedStatus, ['declined', 'rejected', 'decline'], true)) {
                                    $badgeClass = 'bg-danger';
                                    $badgeStyle = '';
                                } elseif (in_array($normalizedStatus, ['pending', 'in progress', 'processing'], true)) {
                                    $badgeClass = 'bg-warning';
                                    $badgeStyle = '';
                                } else {
                                    $badgeClass = 'bg-success';
                                    $badgeStyle = '';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['RequestNo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td title="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($request['MobileNo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td title="<?php echo htmlspecialchars($fullRequestType, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayRequestType, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullWard, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayWard, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullArea, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayArea, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $badgeClass; ?>"<?php echo $badgeStyle !== '' ? ' style="' . htmlspecialchars($badgeStyle, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                                            <?php echo htmlspecialchars($statusName !== '' ? $statusName : 'Not Set', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($request['RaisedDate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="my-request-form.php?id=<?php echo (int) $request['CitizenRequestId']; ?>" class="btn btn-sm" style="background-color: #002253; border-color: #002253; color: white;">
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
        const myRequestsTable = $('#my-requests-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[7, 'desc']]
        });

        $('.dataTables_filter').css('text-align', 'left').appendTo($('#my-requests-table_wrapper .row:first-child > div:first-child'));
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/datatables.net/js/jquery.dataTables.min.js'),
    app_asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js'),
    app_asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js'),
    app_asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js'),
]);
