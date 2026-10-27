<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public — Lecturer Registration Request
|--------------------------------------------------------------------------
| A visitor submits a lecturer registration request (name, username,
| department, qualification, employee id, password).
| The request is stored as PENDING in account_requests; an admin reviews and
| approves it on the Lecturers page, which then creates the real account.
| No account is created here. CSRF protected.
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Already logged in? Send them to their dashboard.
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        redirect('/lectures/admin/navigator.php');
    }
    redirect('/lectures/student/index.php');
}

// Ensure a login CSRF token exists (shared with the login page).
if (!isset($_SESSION['login_csrf']) || !is_string($_SESSION['login_csrf']) || $_SESSION['login_csrf'] === '') {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$oldFullName = '';
$oldUsername = '';
$oldDepartment = '';
$oldQualification = '';
$oldEmployeeId = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // CSRF
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals($_SESSION['login_csrf'] ?? '', $submitted)) {
        http_response_code(403);
        $error = 'Invalid request. Page refresh করে আবার চেষ্টা করো।';
    } else {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));
        $qualification = trim((string)($_POST['qualification'] ?? ''));
        $employeeId = trim((string)($_POST['employee_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $oldFullName = $fullName;
        $oldUsername = $username;
        $oldDepartment = $department;
        $oldQualification = $qualification;
        $oldEmployeeId = $employeeId;

        if ($error === '' && $fullName === '') {
            $error = 'তোমার পুরো নাম লিখো।';
        } elseif ($error === '' && strlen($fullName) > 120) {
            $error = 'নাম সর্বোচ্চ ১২০ অক্ষরের মধ্যে হতে হবে।';
        }

        if ($error === '' && !preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
            $error = 'Username ৪-৫০ অক্ষর হতে হবে (letters, numbers, dot, underscore, hyphen)।';
        }

        if ($error === '' && $department === '') {
            $error = 'Department নির্বাচন করো।';
        }

        if ($error === '' && $qualification === '') {
            $error = 'Qualification লিখো।';
        }

        if ($error === '' && $employeeId === '') {
            $error = 'Employee ID লিখো।';
        }

        if ($error === '' && $password === '') {
            $error = 'Password দিতে হবে।';
        } elseif ($error === '' && $password !== $confirm) {
            $error = 'Password দুটো মিলছে না।';
        }

        // Username must not already exist as a real account or pending request.
        if ($error === '') {
            $dupe = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $dupe->execute([$username]);
            if ((int)$dupe->fetchColumn() > 0) {
                $error = 'এই Username ইতিমধ্যে ব্যবহৃত হয়েছে। অন্য একটি বেছে নাও।';
            }
        }

        if ($error === '') {
            $dupeReq = $pdo->prepare('SELECT COUNT(*) FROM account_requests WHERE username = ? AND status = "pending"');
            $dupeReq->execute([$username]);
            if ((int)$dupeReq->fetchColumn() > 0) {
                $error = 'এই Username এর জন্য আগে থেকেই একটি Pending Request আছে।';
            }
        }

        // Insert pending lecturer registration request.
        if ($error === '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO account_requests
                    (type, full_name, username, department, qualification, employee_id, password_hash, status)
                 VALUES
                    ("register", :full_name, :username, :department, :qualification, :employee_id, :password_hash, "pending")'
            );
            $stmt->execute([
                'full_name' => $fullName,
                'username' => $username,
                'department' => $department,
                'qualification' => $qualification,
                'employee_id' => $employeeId,
                'password_hash' => $passwordHash,
            ]);

            $success = 'Registration Request পাঠানো হয়েছে। Admin অনুমোদন করলে আপনার Lecturer Account তৈরি হবে।';

            // Clear form values after success.
            $oldFullName = '';
            $oldUsername = '';
            $oldDepartment = '';
            $oldQualification = '';
            $oldEmployeeId = '';
        }
    }
}

?>

            <div class="login-heading">
                <span class="eyebrow">NEW LECTURER</span>
                <h1>Lecturer Registration</h1>
                <p>Registration Request পাঠাও। Admin অনুমোদন করলে Account তৈরি হবে।</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert">
                    <span class="error-icon" aria-hidden="true">!</span>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="login-success" role="alert">
                    <span class="success-icon" aria-hidden="true">&#10003;</span>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="/lectures/register-lecturer.php">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">

                <div class="form-group">
                    <label for="full_name">পুরো নাম</label>
                    <input id="full_name" name="full_name" type="text" value="<?= e($oldFullName) ?>" maxlength="120"
                        autocomplete="name" placeholder="তোমার পুরো নাম" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?= e($oldUsername) ?>" maxlength="50"
                        autocomplete="username" placeholder="letters, numbers, dot, underscore" required>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input id="department" name="department" type="text" value="<?= e($oldDepartment) ?>" maxlength="100"
                        placeholder="যেমন: Computer Science" required>
                </div>

                <div class="form-group">
                    <label for="qualification">Qualification</label>
                    <input id="qualification" name="qualification" type="text" value="<?= e($oldQualification) ?>" maxlength="200"
                        placeholder="যেমন: B.Sc in CSE" required>
                </div>

                <div class="form-group">
                    <label for="employee_id">Employee ID</label>
                    <input id="employee_id" name="employee_id" type="text" value="<?= e($oldEmployeeId) ?>" maxlength="50"
                        placeholder="ইমপ্লয়িয় আইডি" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" placeholder="তোমার Password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password"
                        placeholder="আবার Password লিখো" required>
                </div>

                <button type="submit" class="login-button">
                    <span>Registration Request পাঠাও</span>
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </button>
            </form>

            <div class="login-sub">
                Account আছে? <a href="/lectures/index.php">Login করো</a>
            </div>

            <div class="login-footer">
                <span class="footer-dot"></span>
                Secure Lecturer Learning Portal
            </div>
        </main>

    <p class="copyright">
        &copy; <?= date('Y') ?> Lectures. Learn. Practice. Succeed.
    </p>
  </div>
</body>
</html>