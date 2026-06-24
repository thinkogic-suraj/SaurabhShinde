<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$flash = get_flash_message();

render_admin_header('Dashboard', [], 'dashboard');
?>
<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                            <i class="ri-map-pin-2-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Ward Module</h5>
                        <p class="text-muted mb-3">Add, edit, and manage Ward master records.</p>
                        <a href="wards.php" class="btn btn-primary btn-sm">Open Ward Master</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                            <i class="ri-road-map-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Area Module</h5>
                        <p class="text-muted mb-3">Add, edit, and manage Area master records.</p>
                        <a href="areas.php" class="btn btn-primary btn-sm">Open Area Master</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                            <i class="ri-user-star-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Age Category Module</h5>
                        <p class="text-muted mb-3">Add, edit, and manage Age Category records.</p>
                        <a href="age-categories.php" class="btn btn-primary btn-sm">Open Age Category</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                            <i class="ri-file-list-3-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Request Type Module</h5>
                        <p class="text-muted mb-3">Add, edit, and manage Request Type records.</p>
                        <a href="request-types.php" class="btn btn-primary btn-sm">Open Request Type</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                            <i class="ri-inbox-archive-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">My Requests Module</h5>
                        <p class="text-muted mb-3">Review citizen requests and update only their status.</p>
                        <a href="my-requests.php" class="btn btn-primary btn-sm">Open My Requests</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
render_admin_footer();
