<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Admin — Lecturer Requests Management
|--------------------------------------------------------------------------
| Lists pending lecturer registration requests. Admin can approve or reject.
| Approval creates a real users account (role = lecturer) and a lecturer_profiles row.
| Requires admin role. CSRF protected.
*/

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

// Ensure a login CSRF token exists for approve/reject forms.
if (!isset($_SESSION['login_csrf']) || !is_string($_SESSION['login_csrf']) || $_SESSION['login_csrf'] === '') {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Lecturer Requests';
$activePage = 'lecturers';

$error = '';
$success = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Handle Approve / Reject actions
|--------------------------------------------------------------------------
*/
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $submitted = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['login_csrf'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals($sessionToken, $submitted)) {
        http_response_code(403);
        $error = 'Invalid request. Page refresh করে আবার চেষ্টা করো।';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $requestId = (int)($_POST['request_id'] ?? 0);
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));

        if ($requestId <= 0) {
            $error = 'Invalid request ID.';
            $messageType = 'error';
        } elseif (!in_array($action, ['approve', 'reject'], true)) {
            $error = 'Invalid action.';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM account_requests WHERE id = ? AND type = "register" AND status = "pending" LIMIT 1'
            );
            $stmt->execute([$requestId]);
            $req = $stmt->fetch();

            if (!$req) {
                $error = 'Request not found or already processed.';
                $messageType = 'error';
            } elseif ($action === 'approve') {
                $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $dup->execute([$req['username']]);
                if ((int)$dup->fetchColumn() > 0) {
                    $error = 'Username already exists in users table.';
                    $messageType = 'error';
                } else {
                    $pdo->beginTransaction();
                    try {
                        $insUser = $pdo->prepare(
                            'INSERT INTO users (username, password_hash, full_name, role, class_id, is_active)
                             VALUES (?, ?, ?, "lecturer", NULL, 1)'
                        );
                        $insUser->execute([
                            $req['username'],
                            $req['password_hash'],
                            $req['full_name'],
                        ]);
                        $userId = (int)$pdo->lastInsertId();

                        $insProfile = $pdo->prepare(
                            'INSERT INTO lecturer_profiles
                                (user_id, employee_id, department, qualification, is_approved, approved_at, approved_by)
                             VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP, ?)'
                        );
                        $insProfile->execute([
                            $userId,
                            $req['employee_id'] ?? null,
                            $req['department'] ?? null,
                            $req['qualification'] ?? null,
                            (int)$_SESSION['user_id'],
                        ]);

                        $updReq = $pdo->prepare(
                            'UPDATE account_requests SET status = "approved", reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ?, admin_note = ? WHERE id = ?'
                        );
                        $updReq->execute([(int)$_SESSION['user_id'], $adminNote, $requestId]);

                        $pdo->commit();

                        $success = 'Lecturer account approved and created successfully.';
                        $messageType = 'success';
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $error = 'Failed to approve lecturer: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
            } elseif ($action === 'reject') {
                $updReq = $pdo->prepare(
                    'UPDATE account_requests SET status = "rejected", reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ?, admin_note = ? WHERE id = ?'
                );
                $updReq->execute([(int)$_SESSION['user_id'], $adminNote, $requestId]);

                $success = 'Lecturer request rejected.';
                $messageType = 'success';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Load pending requests
|--------------------------------------------------------------------------
*/
$stmtPending = $pdo->prepare(
    'SELECT * FROM account_requests WHERE type = "register" AND status = "pending" ORDER BY created_at DESC'
);
$stmtPending->execute();
$pendingRequests = $stmtPending->fetchAll();

/*
|--------------------------------------------------------------------------
| Load recent history (approved/rejected)
|--------------------------------------------------------------------------
*/
$stmtHistory = $pdo->prepare(
    'SELECT * FROM account_requests WHERE type = "register" AND status IN ("approved","rejected") ORDER BY reviewed_at DESC LIMIT 20'
);
$stmtHistory->execute();
$historyRequests = $stmtHistory->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
            <!-- Page Header -->
            <section class="page-header">
                <div class="page-header-text">
                    <h1 class="page-heading">Lecturer Requests</h1>
                    <p class="page-header-desc">Review and approve/reject lecturer registration requests</p>
                </div>
            </section>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div class="alert alert-success" role="alert"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Pending Requests -->
            <section class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Pending Lecturer Requests</h2>
                    <p class="section-subtitle">These requests need admin approval before accounts are created</p>
                </div>

                <?php if ($pendingRequests): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Department</th>
                                    <th>Qualification</th>
                                    <th>Employee ID</th>
                                    <th>Requested</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $i => $req): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= e($req['full_name']) ?></td>
                                        <td><?= e($req['username']) ?></td>
                                        <td><?= e($req['department'] ?? '-') ?></td>
                                        <td><?= e($req['qualification'] ?? '-') ?></td>
                                        <td><?= e($req['employee_id'] ?? '-') ?></td>
                                        <td><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></td>
                                        <td>
                                            <form method="post" action="" class="d-inline" onsubmit="return confirm('Approve lecturer account for <?= e($req['full_name']) ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                                <input type="text" name="admin_note" placeholder="Note (optional)" class="form-control form-control-sm d-inline-block w-auto" style="max-width:150px;">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form method="post" action="" class="d-inline ms-2" onsubmit="return confirm('Reject lecturer request for <?= e($req['full_name']) ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                                <input type="text" name="admin_note" placeholder="Reason" class="form-control form-control-sm d-inline-block w-auto" style="max-width:150px;">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center py-4">
                        <i class="bi bi-check-circle"></i>
                        <h3>No Pending Requests</h3>
                        <p>All lecturer registration requests have been processed.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recent History -->
            <section class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Recent History</h2>
                    <p class="section-subtitle">Recently approved and rejected requests</p>
                </div>

                <?php if ($historyRequests): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Reviewed</th>
                                    <th>Admin Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyRequests as $i => $req): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= e($req['full_name']) ?></td>
                                        <td><?= e($req['username']) ?></td>
                                        <td>
                                            <span class="badge <?= $req['status'] === 'approved' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst(e($req['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y, h:i A', strtotime($req['reviewed_at'])) ?></td>
                                        <td><?= e($req['admin_note'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center py-4">
                        <i class="bi bi-clock-history"></i>
                        <h3>No History Yet</h3>
                        <p>Approved and rejected requests will appear here.</p>
                    </div>
                <?php endif; ?>
            </section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>