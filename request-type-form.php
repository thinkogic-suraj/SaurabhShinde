<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$requestTypeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $requestTypeId > 0;
$requestType = [
    'RequestTypeName' => '',
    'Description' => '',
    'IsActive' => 1,
];
$errors = [];

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT RequestTypeId, RequestTypeName, Description, IsActive
         FROM RequestTypeMaster
         WHERE RequestTypeId = :id'
    );
    $stmt->execute(['id' => $requestTypeId]);
    $existingRequestType = $stmt->fetch();

    if (!$existingRequestType) {
        set_flash_message('danger', 'Selected request type record was not found.');
        header('Location: request-types.php');
        exit;
    }

    $requestType = $existingRequestType;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestTypeId = (int) ($_POST['request_type_id'] ?? $requestTypeId);
    $isEditMode = $requestTypeId > 0;
    $requestType['RequestTypeName'] = trim($_POST['request_type_name'] ?? '');
    $requestType['Description'] = trim($_POST['description'] ?? '');
    $requestType['IsActive'] = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 0;

    if ($requestType['RequestTypeName'] === '') {
        $errors['RequestTypeName'] = 'Request type name is required.';
    } elseif (mb_strlen($requestType['RequestTypeName']) > 100) {
        $errors['RequestTypeName'] = 'Request type name must be 100 characters or fewer.';
    }

    if ($requestType['Description'] !== '' && mb_strlen($requestType['Description']) > 200) {
        $errors['Description'] = 'Description must be 200 characters or fewer.';
    }

    if ($requestType['IsActive'] !== 0 && $requestType['IsActive'] !== 1) {
        $errors['IsActive'] = 'Please select a valid status.';
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare(
                'UPDATE RequestTypeMaster
                 SET RequestTypeName = :request_type_name, Description = :description, IsActive = :is_active
                 WHERE RequestTypeId = :request_type_id'
            );
            $stmt->execute([
                'request_type_name' => $requestType['RequestTypeName'],
                'description' => $requestType['Description'] !== '' ? $requestType['Description'] : null,
                'is_active' => $requestType['IsActive'],
                'request_type_id' => $requestTypeId,
            ]);

            set_flash_message('success', 'Request Type updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO RequestTypeMaster (RequestTypeName, Description, IsActive)
                 VALUES (:request_type_name, :description, :is_active)'
            );
            $stmt->execute([
                'request_type_name' => $requestType['RequestTypeName'],
                'description' => $requestType['Description'] !== '' ? $requestType['Description'] : null,
                'is_active' => $requestType['IsActive'],
            ]);

            set_flash_message('success', 'Request Type added successfully.');
        }

        header('Location: request-types.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Request Type' : 'Add Request Type';

render_admin_header($pageTitle, [], 'request-type');
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                <p class="card-title-desc">
                    <?php echo $isEditMode ? 'Update the selected Request Type record.' : 'Create a new Request Type record.'; ?>
                </p>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="request_type_id" value="<?php echo (int) $requestTypeId; ?>">

                    <div class="mb-3">
                        <label for="request_type_name" class="form-label">Request Type Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['RequestTypeName']) ? ' is-invalid' : ''; ?>"
                            id="request_type_name"
                            name="request_type_name"
                            required
                            maxlength="100"
                            value="<?php echo htmlspecialchars((string) $requestType['RequestTypeName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter request type name"
                            data-parsley-required-message="Request type name is required."
                            data-parsley-maxlength="100"
                            data-parsley-maxlength-message="Request type name must be 100 characters or fewer."
                        >
                        <?php if (isset($errors['RequestTypeName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['RequestTypeName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea
                            class="form-control<?php echo isset($errors['Description']) ? ' is-invalid' : ''; ?>"
                            id="description"
                            name="description"
                            maxlength="200"
                            rows="4"
                            placeholder="Enter description"
                            data-parsley-maxlength="200"
                            data-parsley-maxlength-message="Description must be 200 characters or fewer."
                        ><?php echo htmlspecialchars((string) ($requestType['Description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <?php if (isset($errors['Description'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['Description'], ENT_QUOTES, 'UTF-8'); ?></div>
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
                            <option value="1" <?php echo (int) $requestType['IsActive'] === 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo (int) $requestType['IsActive'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php if (isset($errors['IsActive'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['IsActive'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="request-types.php" class="btn btn-secondary waves-effect">Cancel</a>
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
