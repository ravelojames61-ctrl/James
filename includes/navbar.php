<?php
/**
 * Global Top Navbar Component — AdminLTE 4.10.1 Design
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$currentRole = getUserRole();
$userName = getUserName();
$facilityName = getSetting('facility_name', 'Aegis Corporate Center');
$stats = getVisitorStats();
$insideCount = $stats['currently_inside'] ?? 0;
?>
<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">
        <!-- Start Navbar Links -->
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list fs-4"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <span class="nav-link text-secondary fw-semibold">
                    <i class="bi bi-building me-1"></i> <?= htmlspecialchars($facilityName) ?>
                </span>
            </li>
            <li class="nav-item d-none d-lg-block">
                <div class="nav-link py-1 px-3 bg-body-secondary rounded-pill d-flex align-items-center gap-2">
                    <span class="live-dot"></span>
                    <span id="liveClock" class="font-monospace small fw-semibold text-secondary">Loading clock...</span>
                </div>
            </li>
        </ul>

        <!-- End Navbar Links -->
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Live Dynamic Sync Status Badge -->
            <li class="nav-item me-2 d-none d-sm-block">
                <span class="badge text-bg-success d-flex align-items-center gap-1 cursor-pointer" id="liveSyncBadge" title="Click to pause or resume real-time auto-sync">
                    <i class="bi bi-broadcast"></i> <span>SYNC ON</span>
                </span>
            </li>

            <!-- Active Visitors Pill -->
            <li class="nav-item me-2">
                <a href="current_visitors.php" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check"></i>
                    <span><strong id="navInsideBadge"><?= $insideCount ?></strong> Inside</span>
                </a>
            </li>

            <!-- Fullscreen Toggle -->
            <li class="nav-item d-none d-sm-block">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="Toggle fullscreen" title="Toggle Fullscreen">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                </a>
            </li>

            <!-- Color Mode Dropdown (AdminLTE 4.10.1 Standard) -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="bd-theme" aria-label="Toggle color scheme" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                    <i class="bi bi-moon-stars-fill d-none" data-lte-theme-icon="dark"></i>
                    <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
                            <i class="bi bi-sun-fill me-2 text-warning"></i> Light
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
                            <i class="bi bi-moon-stars-fill me-2 text-primary"></i> Dark
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto">
                            <i class="bi bi-circle-half me-2 text-secondary"></i> Auto
                        </button>
                    </li>
                </ul>
            </li>

            <!-- User Menu Dropdown -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; font-weight:700; font-size:0.85rem;">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline fw-semibold"><?= htmlspecialchars($userName) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                    <!-- User Header -->
                    <li class="user-header text-bg-primary p-4 text-center">
                        <div class="rounded-circle bg-white text-primary mx-auto mb-2 d-flex align-items-center justify-content-center shadow" style="width:64px; height:64px; font-size:1.5rem; font-weight:700;">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                        <p class="mb-1 fw-bold"><?= htmlspecialchars($userName) ?></p>
                        <span class="badge text-bg-light fw-bold text-uppercase px-2 py-1">
                            <?= htmlspecialchars(getRoleBadgeLabel($currentRole)) ?>
                        </span>
                    </li>
                    <!-- Menu Footer -->
                    <li class="user-footer d-flex justify-content-between p-3 bg-body-tertiary">
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
