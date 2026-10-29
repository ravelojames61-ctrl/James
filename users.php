<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 User & Staff Management (Admin Only)
 */
$pageTitle = 'User Management';
$activeNav = 'users';

require_once __DIR__ . '/config/auth.php';
requireRole('admin');
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();
$errorMessage = '';

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed.';
    } else {
        $fullName = clean($_POST['full_name'] ?? '');
        $username = clean($_POST['username'] ?? '');
        $email    = clean($_POST['email'] ?? '');
        $phone    = clean($_POST['phone'] ?? '');
        $role     = clean($_POST['role'] ?? 'receptionist');
        $password = $_POST['password'] ?? '';

        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            $errorMessage = 'Please provide full name, username, email, and password.';
        } elseif (!in_array($role, ['admin', 'security', 'receptionist'], true)) {
            $errorMessage = 'Invalid role selected.';
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $check->execute([$username]);
            if ($check->fetch()) {
                $errorMessage = 'Username is already taken by another account.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert = $pdo->prepare("
                    INSERT INTO users (username, password_hash, full_name, email, phone, role, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                $insert->execute([$username, $hash, $fullName, $email, $phone, $role]);
                setFlash('success', "User account '{$fullName}' ({$username}) successfully created.");
                header('Location: users.php');
                exit;
            }
        }
    }
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed.';
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId === getUserId()) {
            setFlash('error', 'You cannot deactivate your own administrative account.');
        } else {
            $stmt = $pdo->prepare("SELECT status, full_name FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $u = $stmt->fetch();
            if ($u) {
                $newStatus = ($u['status'] === 'active') ? 'inactive' : 'active';
                $up = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $up->execute([$newStatus, $targetId]);
                setFlash('success', "User '{$u['full_name']}' status changed to " . ucfirst($newStatus) . ".");
            }
        }
    }
    header('Location: users.php');
    exit;
}

// Handle Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed.';
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $newPass  = $_POST['new_password'] ?? '';
        if (strlen($newPass) < 4) {
            setFlash('error', 'Password must be at least 4 characters long.');
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $up->execute([$hash, $targetId]);
            setFlash('success', 'Password successfully reset.');
        }
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, full_name ASC")->fetchAll();

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- App Content Header -->
<div class="app-content-header py-3">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-bold">
                    <i class="bi bi-people-fill text-primary me-2"></i> User & Staff Management
                </h3>
                <div class="text-muted small mt-1">Administer user credentials, assigned roles, and access controls.</div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Add New User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- App Content -->
<div class="app-content">
    <div class="container-fluid">
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div><?= htmlspecialchars($flashSuccess) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError || $errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($flashError ?: $errorMessage) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Role Distribution Small Boxes -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="small-box text-bg-danger shadow-sm">
                    <div class="inner">
                        <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></h3>
                        <p>Administrators (Full Access)</p>
                    </div>
                    <i class="small-box-icon bi bi-shield-lock-fill"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="small-box text-bg-primary shadow-sm">
                    <div class="inner">
                        <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'security')) ?></h3>
                        <p>Security Personnel (Gate Operations)</p>
                    </div>
                    <i class="small-box-icon bi bi-shield-shaded"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="small-box text-bg-success shadow-sm">
                    <div class="inner">
                        <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'receptionist')) ?></h3>
                        <p>Receptionists (Front Desk & Reports)</p>
                    </div>
                    <i class="small-box-icon bi bi-person-workspace"></i>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i> System User Accounts (<?= count($users) ?>)
                </h3>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email & Phone</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width:32px; height:32px; font-weight:700; font-size:0.8rem;">
                                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <small class="text-muted">Registered <?= formatDateOnly($u['created_at']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge text-bg-dark font-monospace"><?= htmlspecialchars($u['username']) ?></span>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge text-bg-danger text-uppercase">Admin</span>
                                    <?php elseif ($u['role'] === 'security'): ?>
                                        <span class="badge text-bg-primary text-uppercase">Security</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success text-uppercase">Reception</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-body"><?= htmlspecialchars($u['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['phone'] ?: 'No phone') ?></small>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= formatDateTime($u['last_login']) ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= addslashes(htmlspecialchars($u['username'])) ?>')">
                                            Reset Password
                                        </button>
                                        <?php if ($u['id'] !== getUserId()): ?>
                                            <form method="POST" action="users.php" style="display:inline;" onsubmit="return confirm('Toggle status for <?= addslashes(htmlspecialchars($u['full_name'])) ?>?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn <?= ($u['status'] === 'active') ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                    <?= ($u['status'] === 'active') ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="btn btn-sm btn-light disabled text-muted">(You)</span>
                                        <?php endif; ?>
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

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="users.php" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i> Add New System User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="new_full_name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="new_full_name" class="form-control" placeholder="e.g. Officer John Davis" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="new_username">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="new_username" class="form-control" placeholder="e.g. jdavis" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="new_role">Assigned Role <span class="text-danger">*</span></label>
                        <select name="role" id="new_role" class="form-select" required>
                            <option value="receptionist">Receptionist</option>
                            <option value="security">Security Personnel</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="new_email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="new_email" class="form-control" placeholder="e.g. jdavis@aegiscenter.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="new_phone">Contact Phone</label>
                        <input type="tel" name="phone" id="new_phone" class="form-control" placeholder="e.g. +1 (555) 019-9922">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="new_password">Initial Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="new_password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>

            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPassModal" tabindex="-1" aria-labelledby="resetPassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="users.php" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId" value="">

            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="resetPassModalLabel">
                    <i class="bi bi-key-fill me-1"></i> Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">
                <p class="small text-muted mb-2">
                    Set a new password for <strong id="resetUsernameDisplay">username</strong>:
                </p>
                <input type="password" name="new_password" class="form-control" placeholder="New Password" required minlength="4">
            </div>

            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning btn-sm">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetPasswordModal(id, username) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUsernameDisplay').textContent = username;
    const modal = new bootstrap.Modal(document.getElementById('resetPassModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
