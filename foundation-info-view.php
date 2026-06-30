<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$foundationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($foundationId <= 0) {
    set_flash_message('danger', 'Selected foundation info record was not found.');
    header('Location: foundation-infos.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT FoundationId, FoundationName, AboutFoundation, ContactNo1, ContactNo2, Logo, IsActive
     FROM FoundationInfo
     WHERE FoundationId = :id
     LIMIT 1'
);
$stmt->execute(['id' => $foundationId]);
$foundationInfo = $stmt->fetch();

if (!$foundationInfo || (int) ($foundationInfo['IsActive'] ?? 1) !== 1) {
    set_flash_message('danger', 'Selected foundation info record was not found.');
    header('Location: foundation-infos.php');
    exit;
}

render_admin_header('View Foundation Info', [], 'foundation-info', false);
?>
<style>
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .info-label {
        color: #64748b;
        font-size: 0.875rem;
        margin-bottom: 0.35rem;
    }
    .info-value {
        color: #0f172a;
        font-size: 1rem;
        margin-bottom: 1.5rem;
        word-break: break-word;
    }
    .view-logo {
        max-width: 220px;
        max-height: 220px;
        object-fit: contain;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem;
        background-color: #fff;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="page-title-box">
                        <h4 class="mb-1">View Foundation Info</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="foundation-infos.php">Configuration</a></li>
                                <li class="breadcrumb-item"><a href="foundation-infos.php">Foundation Info Master</a></li>
                                <li class="breadcrumb-item active">View Foundation Info</li>
                            </ol>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="foundation-info-form.php?id=<?php echo (int) $foundationInfo['FoundationId']; ?>" class="btn btn-primary waves-effect waves-light" style="background-color: #002253; border-color: #002253;">
                            <i class="ri-edit-2-line align-middle me-1"></i> Edit
                        </a>
                        <a href="foundation-infos.php" class="btn btn-secondary waves-effect">Back</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="info-label">Foundation Name</div>
                        <div class="info-value"><?php echo htmlspecialchars((string) ($foundationInfo['FoundationName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>

                        <div class="info-label">About Foundation</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars((string) ($foundationInfo['AboutFoundation'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-label">Contact No 1</div>
                                <div class="info-value"><?php echo htmlspecialchars((string) ($foundationInfo['ContactNo1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Contact No 2</div>
                                <div class="info-value"><?php echo htmlspecialchars((string) ($foundationInfo['ContactNo2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="info-label">Logo</div>
                        <?php if (trim((string) ($foundationInfo['Logo'] ?? '')) !== ''): ?>
                            <img src="<?php echo htmlspecialchars((string) $foundationInfo['Logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foundation Logo" class="view-logo">
                        <?php else: ?>
                            <div class="info-value">No logo uploaded.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
render_admin_footer();
