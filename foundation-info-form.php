<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$foundationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $foundationId > 0;

if (!$isEditMode && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Creating Foundation Info is not allowed.');
    header('Location: foundation-infos.php');
    exit;
}

$foundationInfo = [
    'FoundationName' => '',
    'AboutFoundation' => '',
    'ContactNo1' => '',
    'ContactNo2' => '',
    'Logo' => '',
];
$errors = [];

$redirectIfFoundationInfoInactive = static function (array|false $existingFoundationInfo): void {
    if (!$existingFoundationInfo) {
        set_flash_message('danger', 'Selected foundation info record was not found.');
        header('Location: foundation-infos.php');
        exit;
    }

    if ((int) ($existingFoundationInfo['IsActive'] ?? 1) !== 1) {
        set_flash_message('danger', 'This foundation info has been deleted and cannot be edited.');
        header('Location: foundation-infos.php');
        exit;
    }
};

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT FoundationId, FoundationName, AboutFoundation, ContactNo1, ContactNo2, Logo, IsActive
         FROM FoundationInfo
         WHERE FoundationId = :id'
    );
    $stmt->execute(['id' => $foundationId]);
    $existingFoundationInfo = $stmt->fetch();

    $redirectIfFoundationInfoInactive($existingFoundationInfo);

    $foundationInfo = [
        'FoundationName' => (string) ($existingFoundationInfo['FoundationName'] ?? ''),
        'AboutFoundation' => (string) ($existingFoundationInfo['AboutFoundation'] ?? ''),
        'ContactNo1' => (string) ($existingFoundationInfo['ContactNo1'] ?? ''),
        'ContactNo2' => (string) ($existingFoundationInfo['ContactNo2'] ?? ''),
        'Logo' => (string) ($existingFoundationInfo['Logo'] ?? ''),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foundationId = (int) ($_POST['foundation_id'] ?? $foundationId);
    $isEditMode = $foundationId > 0;

    if (!$isEditMode) {
        set_flash_message('danger', 'Creating Foundation Info is not allowed.');
        header('Location: foundation-infos.php');
        exit;
    }

    $foundationInfo['FoundationName'] = trim((string) ($_POST['foundation_name'] ?? ''));
    $foundationInfo['AboutFoundation'] = trim((string) ($_POST['about_foundation'] ?? ''));
    $foundationInfo['ContactNo1'] = trim((string) ($_POST['contact_no1'] ?? ''));
    $foundationInfo['ContactNo2'] = trim((string) ($_POST['contact_no2'] ?? ''));
    $foundationInfo['Logo'] = trim((string) ($_POST['existing_logo'] ?? $foundationInfo['Logo']));

    if ($foundationInfo['FoundationName'] === '') {
        $errors['FoundationName'] = 'Foundation name is required.';
    } elseif (mb_strlen($foundationInfo['FoundationName']) > 200) {
        $errors['FoundationName'] = 'Foundation name must be 200 characters or fewer.';
    }

    if ($foundationInfo['AboutFoundation'] !== '' && mb_strlen($foundationInfo['AboutFoundation']) > 5000) {
        $errors['AboutFoundation'] = 'About Foundation must be 5000 characters or fewer.';
    }

    $uploadedFile = $_FILES['logo'] ?? null;
    $hasUploadedFile = is_array($uploadedFile) && (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    $uploadedExtension = '';

    if ($hasUploadedFile) {
        if ((int) $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $errors['Logo'] = 'Logo could not be uploaded.';
        } else {
            $uploadedExtension = strtolower(pathinfo((string) $uploadedFile['name'], PATHINFO_EXTENSION));

            if (!in_array($uploadedExtension, $allowedExtensions, true)) {
                $errors['Logo'] = 'Please upload a valid image file (JPG, JPEG, PNG, WEBP, or SVG).';
            }
        }
    }

    if ($errors === []) {
        $existingFoundationInfoStmt = $pdo->prepare('SELECT FoundationId, IsActive FROM FoundationInfo WHERE FoundationId = :id');
        $existingFoundationInfoStmt->execute(['id' => $foundationId]);
        $redirectIfFoundationInfoInactive($existingFoundationInfoStmt->fetch());

        $stmt = $pdo->prepare(
            'UPDATE FoundationInfo
             SET FoundationName = :foundation_name,
                 AboutFoundation = :about_foundation,
                 ContactNo1 = :contact_no1,
                 ContactNo2 = :contact_no2
             WHERE FoundationId = :foundation_id'
        );
        $stmt->execute([
            'foundation_name' => $foundationInfo['FoundationName'],
            'about_foundation' => $foundationInfo['AboutFoundation'] !== '' ? $foundationInfo['AboutFoundation'] : null,
            'contact_no1' => $foundationInfo['ContactNo1'] !== '' ? $foundationInfo['ContactNo1'] : null,
            'contact_no2' => $foundationInfo['ContactNo2'] !== '' ? $foundationInfo['ContactNo2'] : null,
            'foundation_id' => $foundationId,
        ]);

        if ($hasUploadedFile) {
            $logoDirectory = __DIR__ . '/uploads/foundation-info';
            if (!is_dir($logoDirectory)) {
                mkdir($logoDirectory, 0777, true);
            }

            foreach (glob($logoDirectory . '/' . $foundationId . '.*') ?: [] as $existingLogoFile) {
                if (is_file($existingLogoFile)) {
                    unlink($existingLogoFile);
                }
            }

            $targetRelativePath = 'uploads/foundation-info/' . $foundationId . '.' . $uploadedExtension;
            $targetAbsolutePath = __DIR__ . '/' . $targetRelativePath;

            if (!move_uploaded_file((string) $uploadedFile['tmp_name'], $targetAbsolutePath)) {
                $errors['Logo'] = 'Logo could not be saved.';
            } else {
                $foundationInfo['Logo'] = $targetRelativePath;
                $updateLogoStmt = $pdo->prepare(
                    'UPDATE FoundationInfo
                     SET Logo = :logo
                     WHERE FoundationId = :foundation_id'
                );
                $updateLogoStmt->execute([
                    'logo' => $targetRelativePath,
                    'foundation_id' => $foundationId,
                ]);
            }
        }

        if ($errors === []) {
            set_flash_message('success', 'Foundation Info updated successfully.');

            header('Location: foundation-infos.php');
            exit;
        }
    }
}

$pageTitle = $isEditMode ? 'Edit Foundation Info' : 'Add Foundation Info';

render_admin_header($pageTitle, [], 'foundation-info', false);
?>
<style>
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .foundation-form-field,
    .foundation-form-textarea {
        width: 100%;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="page-title-box">
                        <h4 class="mb-1"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="foundation-infos.php">Configuration</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="foundation_id" value="<?php echo (int) $foundationId; ?>">
                    <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($foundationInfo['Logo'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="foundation-form-field">
                                <label for="foundation_name" class="form-label">Foundation Name</label>
                                <input
                                    type="text"
                                    class="form-control<?php echo isset($errors['FoundationName']) ? ' is-invalid' : ''; ?>"
                                    id="foundation_name"
                                    name="foundation_name"
                                    required
                                    maxlength="200"
                                    value="<?php echo htmlspecialchars($foundationInfo['FoundationName'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter foundation name"
                                    data-parsley-required-message="Foundation name is required."
                                    data-parsley-maxlength="200"
                                    data-parsley-maxlength-message="Foundation name must be 200 characters or fewer."
                                    data-parsley-whitespace="trim"
                                >
                                <?php if (isset($errors['FoundationName'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['FoundationName'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="foundation-form-field">
                                <label for="contact_no1" class="form-label">Contact No 1</label>
                                <input
                                    type="text"
                                    class="form-control<?php echo isset($errors['ContactNo1']) ? ' is-invalid' : ''; ?>"
                                    id="contact_no1"
                                    name="contact_no1"
                                    maxlength="15"
                                    value="<?php echo htmlspecialchars($foundationInfo['ContactNo1'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter contact number"
                                >
                                <?php if (isset($errors['ContactNo1'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['ContactNo1'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="foundation-form-field">
                                <label for="contact_no2" class="form-label">Contact No 2</label>
                                <input
                                    type="text"
                                    class="form-control<?php echo isset($errors['ContactNo2']) ? ' is-invalid' : ''; ?>"
                                    id="contact_no2"
                                    name="contact_no2"
                                    maxlength="15"
                                    value="<?php echo htmlspecialchars($foundationInfo['ContactNo2'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter alternate contact number"
                                >
                                <?php if (isset($errors['ContactNo2'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['ContactNo2'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="foundation-form-textarea">
                                <label for="about_foundation" class="form-label">About Foundation</label>
                                <textarea
                                    class="form-control<?php echo isset($errors['AboutFoundation']) ? ' is-invalid' : ''; ?>"
                                    id="about_foundation"
                                    name="about_foundation"
                                    rows="4"
                                    maxlength="5000"
                                    placeholder="Enter about foundation"
                                    data-parsley-maxlength="5000"
                                    data-parsley-maxlength-message="About Foundation must be 5000 characters or fewer."
                                ><?php echo htmlspecialchars($foundationInfo['AboutFoundation'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <?php if (isset($errors['AboutFoundation'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['AboutFoundation'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="foundation-infos.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    window.Parsley.addValidator('whitespace', {
        validateString: function (value) {
            return value.trim().length > 0;
        },
        messages: {
            en: 'Foundation name is required.'
        }
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
