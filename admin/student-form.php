<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h(string|null $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = ($id && $id > 0) ? $id : null;

$student = [
    'full_name' => '',
    'username' => '',
    'class_id' => '',
    'is_active' => 1,
];

if ($id !== null) {
    $stmt = $pdo->prepare(
        "SELECT id, full_name, username, class_id, is_active
         FROM users
         WHERE id = :id AND role = 'student'
         LIMIT 1"
    );
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();

    if (!$found) {
        http_response_code(404);
        exit('Student not found.');
    }

    $student = $found;
}

$classes = $pdo->query(
    "SELECT id, name FROM classes
     WHERE is_active = 1
     ORDER BY name"
)->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Invalid request.');
    }

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $classInput = $_POST['class_id'] ?? '';

    $classId = null;

    if ($classInput !== '') {
        $validatedClassId = filter_var(
            $classInput,
            FILTER_VALIDATE_INT
        );

        if (!$validatedClassId || $validatedClassId < 1) {
            $error = 'Please select a valid class.';
        } else {
            $classId = (int) $validatedClassId;

            $checkClass = $pdo->prepare(
                "SELECT id FROM classes
                 WHERE id = :id AND is_active = 1"
            );
            $checkClass->execute(['id' => $classId]);

            if (!$checkClass->fetch()) {
                $error = 'Selected class is unavailable.';
            }
        }
    }

    if ($error === '' && ($fullName === '' || strlen($fullName) > 120)) {
        $error = 'Enter a valid full name (maximum 120 characters).';
    } elseif (
        $error === '' &&
        !preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)
    ) {
        $error = 'Username must be 4-50 characters: letters, numbers, dot, underscore or hyphen.';
    } elseif ($error === '' && $id === null && strlen($password) < 12) {
        $error = 'New student password must be at least 12 characters.';
    } elseif ($error === '' && $password !== '' && strlen($password) < 12) {
        $error = 'Password must be at least 12 characters.';
    }

    if ($error === '') {
        try {
            if ($id === null) {
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                        (username, password_hash, full_name, role, class_id, is_active)
                     VALUES
                        (:username, :password_hash, :full_name, 'student', :class_id, 1)"
                );

                $stmt->execute([
                    'username' => $username,
                    'password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    'full_name' => $fullName,
                    'class_id' => $classId,
                ]);
            } else {
                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET username = :username,
                             full_name = :full_name,
                             class_id = :class_id,
                             password_hash = :password_hash
                         WHERE id = :id AND role = 'student'"
                    );

                    $stmt->execute([
                        'username' => $username,
                        'full_name' => $fullName,
                        'class_id' => $classId,
                        'password_hash' => password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        ),
                        'id' => $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET username = :username,
                             full_name = :full_name,
                             class_id = :class_id
                         WHERE id = :id AND role = 'student'"
                    );

                    $stmt->execute([
                        'username' => $username,
                        'full_name' => $fullName,
                        'class_id' => $classId,
                        'id' => $id,
                    ]);
                }
            }

            header('Location: /lectures/admin/students.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());

            if ($e->getCode() === '23000') {
                $error = 'Username already exists, or the selected class is invalid.';
            } else {
                $error = 'Could not save the student. Check the server error log.';
            }
        }
    }

    // Keep entered values visible after a validation error.
    $student['full_name'] = $fullName;
    $student['username'] = $username;
    $student['class_id'] = $classId ?? '';
}
?>

$pageTitle = $id === null ? 'Add Student' : 'Edit Student';
$activePage = 'students';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1 class="page-title"><?= $id === null ? 'Add Student' : 'Edit Student' ?></h1>
        <p class="page-header-desc">
            <?= $id === null
                ? 'Create a new student account and assign a class.'
                : 'Update this student account. Leave the password blank to keep it unchanged.' ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a class="btn btn-outline-secondary" href="/lectures/admin/students.php">
            <i class="bi bi-arrow-left me-1"></i> Back to Students
        </a>
    </div>
</div>

<div class="panel" style="max-width:640px;">
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token"
            value="<?= h($_SESSION['csrf_token']) ?>">

        <div class="mb-3">
            <label class="form-label" for="full_name">Student Full Name</label>
            <input class="form-control" id="full_name" name="full_name" type="text"
                maxlength="120" required
                value="<?= h((string) $student['full_name']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" type="text"
                minlength="4" maxlength="50"
                pattern="[a-zA-Z0-9_.-]+" required
                value="<?= h((string) $student['username']) ?>">
            <div class="form-text text-muted">
                Use 4-50 letters, numbers, dots, underscores or hyphens.
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="class_id">Class</label>
            <select class="form-select" id="class_id" name="class_id">
                <option value="">Not assigned yet</option>
                <?php foreach ($classes as $class): ?>
                    <option
                        value="<?= (int) $class['id'] ?>"
                        <?= (string) ($student['class_id'] ?? '') ===
                            (string) $class['id'] ? 'selected' : '' ?>>
                        <?= h($class['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">
                <?= $id === null ? 'Initial Password' : 'New Password (optional)' ?>
            </label>
            <input class="form-control" id="password" name="password" type="password"
                <?= $id === null ? 'required' : '' ?>
                minlength="12" autocomplete="new-password">
            <div class="form-text text-muted">
                At least 12 characters.
                <?= $id === null
                    ? 'This password is required for a new account.'
                    : 'Leave blank to keep the existing password.' ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i>
                <?= $id === null ? 'Create Student' : 'Save Changes' ?>
            </button>
            <a class="btn btn-outline-secondary" href="/lectures/admin/students.php">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
