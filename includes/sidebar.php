<?php
/**
 * Global Sidebar Navigation Component — AdminLTE 4.10.1 Design
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$currentRole = getUserRole();
$userName = getUserName();
$stats = getVisitorStats();
$insideCount = $stats['currently_inside'] ?? 0;
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Sidebar Brand -->
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
            <img src="assets/adminlte/assets/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image opacity-75 shadow rounded">
            <span class="brand-text fw-light">AdminLTE <strong>VLMS</strong></span>
        </a>
    </div>

    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <!-- Sidebar User Card -->
        <div class="user-panel py-3 px-3 d-flex align-items-center border-bottom border-secondary border-opacity-25">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm me-2" style="width:36px; height:36px; font-weight:700;">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <div class="info">
                <a href="#" class="d-block text-decoration-none text-light fw-semibold small text-truncate" style="max-width:160px;">
                    <?= htmlspecialchars($userName) ?>
                </a>
                <span class="badge text-bg-secondary text-uppercase" style="font-size:0.65rem;">
                    <?= htmlspecialchars(getRoleBadgeLabel($currentRole)) ?>
                </span>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="mt-2" aria-label="Main navigation">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation">
                <!-- Section Header -->
                <li class="nav-header text-uppercase small text-muted px-3 mt-2 mb-1">Operations</li>

                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= ($activeNav === 'dashboard') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="register_visitor.php" class="nav-link <?= ($activeNav === 'register') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-person-plus-fill"></i>
                        <p>Register Visitor</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="current_visitors.php" class="nav-link <?= ($activeNav === 'current') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-clock-history"></i>
                        <p>
                            Current Visitors
                            <span id="sidebarInsideBadge" class="badge text-bg-success float-end ms-2" <?= ($insideCount > 0) ? '' : 'style="display:none;"' ?>><?= $insideCount ?></span>
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="visitor_history.php" class="nav-link <?= ($activeNav === 'history') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-journal-text"></i>
                        <p>Visitor Records</p>
                    </a>
                </li>

                <?php if (can('view_reports')): ?>
                <!-- Section Header -->
                <li class="nav-header text-uppercase small text-muted px-3 mt-3 mb-1">Analytics</li>

                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?= ($activeNav === 'reports') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-bar-chart-line-fill"></i>
                        <p>Visitor Reports</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($currentRole === 'admin'): ?>
                <!-- Section Header -->
                <li class="nav-header text-uppercase small text-muted px-3 mt-3 mb-1">Administration</li>

                <li class="nav-item">
                    <a href="users.php" class="nav-link <?= ($activeNav === 'users') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-people-fill"></i>
                        <p>User Management</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?= ($activeNav === 'settings') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-gear-fill"></i>
                        <p>System Settings</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-header text-uppercase small text-muted px-3 mt-3 mb-1">Session</li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="nav-icon bi bi-box-arrow-right"></i>
                        <p>Sign Out</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
<main class="app-main">
