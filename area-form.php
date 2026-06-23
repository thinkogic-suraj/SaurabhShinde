<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$areaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $areaId > 0;
$area = [
    'WardId' => 0,
    'AreaName' => '',
    'IsActive' => 1,
];
$errors = [];

$wards = $pdo->query('SELECT WardId, WardName FROM Ward ORDER BY WardName ASC')->fetchAll();

if ($isEditMode) {
    $stmt = $pdo->prepare('SELECT AreaId, WardId, AreaName, IsActive FROM Area WHERE AreaId = :id');
    $stmt->execute(['id' => $areaId]);
    $existingArea = $stmt->fetch();

    if (!$existingArea) {
        set_flash_message('danger', 'Selected area record was not found.');
        header('Location: areas.php');
        exit;
    }

    $area = $existingArea;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $areaId = (int) ($_POST['area_id'] ?? $areaId);
    $isEditMode = $areaId > 0;
    $area['WardId'] = (int) ($_POST['ward_id'] ?? 0);
    $area['AreaName'] = trim($_POST['area_name'] ?? '');
    $area['IsActive'] = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 0;

    $validWardIds = array_map(static fn(array $ward): int => (int) $ward['WardId'], $wards);

    if ($area['WardId'] <= 0 || !in_array($area['WardId'], $validWardIds, true)) {
        $errors['WardId'] = 'Please select a valid Ward.';
    }

    if ($area['AreaName'] === '') {
        $errors['AreaName'] = 'Area name is required.';
    } elseif (mb_strlen($area['AreaName']) > 200) {
        $errors['AreaName'] = 'Area name must be 200 characters or fewer.';
    }

    if ($area['IsActive'] !== 0 && $area['IsActive'] !== 1) {
        $errors['IsActive'] = 'Please select a valid status.';
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare(
                'UPDATE Area
                 SET WardId = :ward_id, AreaName = :area_name, IsActive = :is_active
                 WHERE AreaId = :area_id'
            );
            $stmt->execute([
                'ward_id' => $area['WardId'],
                'area_name' => $area['AreaName'],
                'is_active' => $area['IsActive'],
                'area_id' => $areaId,
            ]);

            set_flash_message('success', 'Area updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO Area (WardId, AreaName, IsActive)
                 VALUES (:ward_id, :area_name, :is_active)'
            );
            $stmt->execute([
                'ward_id' => $area['WardId'],
                'area_name' => $area['AreaName'],
                'is_active' => $area['IsActive'],
            ]);

            set_flash_message('success', 'Area added successfully.');
        }

        header('Location: areas.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Area' : 'Add Area';

render_admin_header($pageTitle, [], 'area');
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                <p class="card-title-desc">
                    <?php echo $isEditMode ? 'Update the selected Area record.' : 'Create a new Area record.'; ?>
                </p>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="area_id" value="<?php echo (int) $areaId; ?>">

                    <div class="mb-3">
                        <label for="ward_id" class="form-label">Ward</label>
                        <select
                            class="form-select<?php echo isset($errors['WardId']) ? ' is-invalid' : ''; ?>"
                            id="ward_id"
                            name="ward_id"
                            required
                            data-parsley-required-message="Ward is required."
                        >
                            <option value="">Select Ward</option>
                            <?php foreach ($wards as $ward): ?>
                                <option
                                    value="<?php echo (int) $ward['WardId']; ?>"
                                    <?php echo (int) $area['WardId'] === (int) $ward['WardId'] ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($ward['WardName'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['WardId'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['WardId'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="area_name" class="form-label">Area Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['AreaName']) ? ' is-invalid' : ''; ?>"
                            id="area_name"
                            name="area_name"
                            required
                            maxlength="200"
                            value="<?php echo htmlspecialchars((string) $area['AreaName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter area name"
                            data-parsley-required-message="Area name is required."
                            data-parsley-maxlength="200"
                            data-parsley-maxlength-message="Area name must be 200 characters or fewer."
                        >
                        <?php if (isset($errors['AreaName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['AreaName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="is_active" class="form-label">Status</label>
                        <select
                            class="form-select<?php echo isset($errors['IsActive']) ? ' is-invalid' : ''; ?>"
                            id="is_active"
                            name="is_active"
                            required
                            data-parsley-required-message="Status is required."
                        >
                            <option value="1" <?php echo (int) $area['IsActive'] === 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo (int) $area['IsActive'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php if (isset($errors['IsActive'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['IsActive'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="areas.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
