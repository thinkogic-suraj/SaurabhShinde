<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$ageCategoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $ageCategoryId > 0;
$ageCategory = [
    'CategoryName' => '',
    'IsActive' => 1,
];
$errors = [];

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT AgeCategoryId, CategoryName, IsActive
         FROM AgeCategoryMaster
         WHERE AgeCategoryId = :id'
    );
    $stmt->execute(['id' => $ageCategoryId]);
    $existingAgeCategory = $stmt->fetch();

    if (!$existingAgeCategory) {
        set_flash_message('danger', 'Selected age category record was not found.');
        header('Location: age-categories.php');
        exit;
    }

    $ageCategory = $existingAgeCategory;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ageCategoryId = (int) ($_POST['age_category_id'] ?? $ageCategoryId);
    $isEditMode = $ageCategoryId > 0;
    $ageCategory['CategoryName'] = trim($_POST['category_name'] ?? '');
    $ageCategory['IsActive'] = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 0;

    if ($ageCategory['CategoryName'] === '') {
        $errors['CategoryName'] = 'Category name is required.';
    } elseif (mb_strlen($ageCategory['CategoryName']) > 50) {
        $errors['CategoryName'] = 'Category name must be 50 characters or fewer.';
    }

    if ($ageCategory['IsActive'] !== 0 && $ageCategory['IsActive'] !== 1) {
        $errors['IsActive'] = 'Please select a valid status.';
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare(
                'UPDATE AgeCategoryMaster
                 SET CategoryName = :category_name, IsActive = :is_active
                 WHERE AgeCategoryId = :age_category_id'
            );
            $stmt->execute([
                'category_name' => $ageCategory['CategoryName'],
                'is_active' => $ageCategory['IsActive'],
                'age_category_id' => $ageCategoryId,
            ]);

            set_flash_message('success', 'Age Category updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO AgeCategoryMaster (CategoryName, IsActive)
                 VALUES (:category_name, :is_active)'
            );
            $stmt->execute([
                'category_name' => $ageCategory['CategoryName'],
                'is_active' => $ageCategory['IsActive'],
            ]);

            set_flash_message('success', 'Age Category added successfully.');
        }

        header('Location: age-categories.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Age Category' : 'Add Age Category';

render_admin_header($pageTitle, [], 'age-category');
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                <p class="card-title-desc">
                    <?php echo $isEditMode ? 'Update the selected Age Category record.' : 'Create a new Age Category record.'; ?>
                </p>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="age_category_id" value="<?php echo (int) $ageCategoryId; ?>">

                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['CategoryName']) ? ' is-invalid' : ''; ?>"
                            id="category_name"
                            name="category_name"
                            required
                            maxlength="50"
                            value="<?php echo htmlspecialchars((string) $ageCategory['CategoryName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter category name"
                            data-parsley-required-message="Category name is required."
                            data-parsley-maxlength="50"
                            data-parsley-maxlength-message="Category name must be 50 characters or fewer."
                        >
                        <?php if (isset($errors['CategoryName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['CategoryName'], ENT_QUOTES, 'UTF-8'); ?></div>
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
                            <option value="1" <?php echo (int) $ageCategory['IsActive'] === 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo (int) $ageCategory['IsActive'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php if (isset($errors['IsActive'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['IsActive'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="age-categories.php" class="btn btn-secondary waves-effect">Cancel</a>
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
