<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();

$flash = get_flash_message();
$foundationInfos = $pdo->query(
    'SELECT FoundationId, FoundationName, AboutFoundation, ContactNo1, ContactNo2, Logo, IsActive
     FROM FoundationInfo
     WHERE IsActive = 1
     ORDER BY FoundationId DESC'
)->fetchAll();

render_admin_header('Foundation Info Master', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'foundation-info', false);
?>
<style>
    #foundation-info-table td, #foundation-info-table th {
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
                        <h4 class="mb-1">Foundation Info Master</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="foundation-infos.php">Configuration</a></li>
                                <li class="breadcrumb-item active">Foundation Info Master</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="search-box">
                            <div class="position-relative">
                                <input type="search" id="custom-search" class="form-control rounded" placeholder="Search...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="foundation-info-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Foundation ID</th>
                                <th>Foundation Name</th>
                                <th>About Foundation</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($foundationInfos as $foundationInfo): ?>
                                <?php
                                $fullName = (string) ($foundationInfo['FoundationName'] ?? '');
                                $displayName = mb_strlen($fullName) > 25 ? mb_substr($fullName, 0, 25) . '...' : $fullName;
                                $fullAbout = trim((string) ($foundationInfo['AboutFoundation'] ?? ''));
                                $displayAbout = mb_strlen($fullAbout) > 40 ? mb_substr($fullAbout, 0, 40) . '...' : $fullAbout;
                                $isActive = (int) ($foundationInfo['IsActive'] ?? 0) === 1;
                                $btnOpacity = $isActive ? '' : ' opacity: 0.5; pointer-events: none;';
                                ?>
                                <tr>
                                    <td><?php echo (int) $foundationInfo['FoundationId']; ?></td>
                                    <td title="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullAbout, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayAbout, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="<?php echo $isActive ? 'foundation-info-form.php?id=' . (int) $foundationInfo['FoundationId'] : '#'; ?>" class="btn btn-sm<?php echo $isActive ? '' : ' disabled'; ?>" style="background-color: #002253; border-color: #002253; color: white;<?php echo $btnOpacity; ?>">
                                                <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                            </a>
                                        </div>
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
        const foundationInfoTable = $('#foundation-info-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[1, 'desc']]
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function() {
            foundationInfoTable.search(this.value).draw();
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
