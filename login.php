<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Login Page
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errorMessage = '';
$infoMessage = '';

if (isset($_GET['logged_out'])) {
    $infoMessage = 'You have been successfully signed out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errorMessage = 'Please enter both username and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name, email, role, status FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Supports both bcrypt hash and plain text password matching
        if ($user && (password_verify($password, $user['password_hash']) || $password === $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $errorMessage = 'Your account has been deactivated. Please contact an Administrator.';
            } else {
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                setFlash('success', 'Welcome back, ' . clean($user['full_name']) . '! Logged in as ' . getRoleBadgeLabel($user['role']) . '.');
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errorMessage = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>AdminLTE 4 | Sign In — VLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">

    <!-- Fonts (Source Sans 3) -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.3.0/index.css"
      crossorigin="anonymous"
    />
    <!-- Bootstrap Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!-- AdminLTE 4.10.1 CSS -->
    <link rel="stylesheet" href="assets/adminlte/css/adminlte.min.css" />
</head>
<body class="login-page bg-body-secondary d-flex align-items-center justify-content-center min-vh-100">
    <main class="login-box" style="width: 420px; max-width: 95%;">
        <div class="login-logo text-center mb-4">
            <a href="login.php" class="text-decoration-none d-flex align-items-center justify-content-center gap-2">
                <img src="assets/adminlte/assets/img/AdminLTELogo.png" alt="AdminLTE Logo" style="height:44px;" class="shadow-sm rounded">
                <span class="fs-2 fw-light text-body"><b>Admin</b>LTE <span class="fw-bold text-primary">VLMS</span></span>
            </a>
            <div class="text-muted small">Visitor Log Management System</div>
        </div>

        <div class="card card-outline card-primary shadow">
            <div class="card-body login-card-body p-4">
                <p class="login-box-msg text-center text-muted mb-4">Sign in to start your session</p>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <span><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($infoMessage)): ?>
                    <div class="alert alert-success py-2 small d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span><?= htmlspecialchars($infoMessage) ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <label class="form-label small fw-semibold" for="username">Username</label>
                    <div class="input-group mb-3">
                        <input id="username" name="username" type="text" class="form-control" placeholder="Username" required autofocus autocomplete="username" />
                        <div class="input-group-text">
                            <span class="bi bi-person-fill"></span>
                        </div>
                    </div>

                    <label class="form-label small fw-semibold" for="password">Password</label>
                    <div class="input-group mb-4">
                        <input id="password" name="password" type="password" class="form-control" placeholder="Password" required autocomplete="current-password" />
                        <div class="input-group-text">
                            <span class="bi bi-lock-fill"></span>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html>
