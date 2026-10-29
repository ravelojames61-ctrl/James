<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Current Visitor Monitoring & Departure Recording
 */
$pageTitle = 'Current Visitors';
$activeNav = 'current';

require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();

// Handle Arrival / Time-In Recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_timein') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch. Please try again.');
    } else {
        $visitorId = (int)($_POST['visitor_id'] ?? 0);
        $remarks   = clean($_POST['remarks'] ?? '');

        if ($visitorId > 0) {
            try {
                $checkStmt = $pdo->prepare("SELECT full_name, badge_number, status, remarks FROM visitor_logs WHERE id = ?");
                $checkStmt->execute([$visitorId]);
                $vis = $checkStmt->fetch();

                if ($vis) {
                    $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Time-In: " . $remarks : "Time-In recorded"));
                    $updateStmt = $pdo->prepare("
                        UPDATE visitor_logs 
                        SET status = 'Inside', 
                            time_in = NOW(), 
                            timed_in_confirmed = 1, 
                            time_out = NULL, 
                            registered_by = ?, 
                            remarks = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([getUserId(), $newRemarks, $visitorId]);
                    setFlash('success', "Time-In recorded for {$vis['full_name']} ({$vis['badge_number']}). Status updated to Inside.");
                } else {
                    setFlash('error', 'Visitor record not found.');
                }
            } catch (Exception $e) {
                setFlash('error', 'Error updating visitor status: ' . $e->getMessage());
            }
        }
    }
    header('Location: current_visitors.php');
    exit;
}

// Handle Departure / Time-Out Recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_timeout') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch. Please try again.');
    } else {
        $visitorId = (int)($_POST['visitor_id'] ?? 0);
        $remarks   = clean($_POST['remarks'] ?? '');

        if ($visitorId > 0) {
            try {
                $checkStmt = $pdo->prepare("SELECT full_name, badge_number, status, remarks FROM visitor_logs WHERE id = ?");
                $checkStmt->execute([$visitorId]);
                $vis = $checkStmt->fetch();

                if ($vis && $vis['status'] === 'Inside') {
                    $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Exit: " . $remarks : ""));
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE visitor_logs 
                        SET status = 'Completed', 
                            time_out = NOW(), 
                            checked_out_by = ?, 
                            remarks = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([getUserId(), $newRemarks, $visitorId]);

                    setFlash('success', "Time-Out recorded for {$vis['full_name']} ({$vis['badge_number']}). Status updated to Completed.");
                } else {
                    setFlash('error', 'Visitor record is already marked as completed or does not exist.');
                }
            } catch (Exception $e) {
                setFlash('error', 'Error updating visitor status: ' . $e->getMessage());
            }
        }
    }
    header('Location: current_visitors.php');
    exit;
}

// Handle Quick Badge Scan / Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scan_checkout') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch.');
    } else {
        $badgeQuery = trim($_POST['scan_badge'] ?? '');
        if (!empty($badgeQuery)) {
            $findStmt = $pdo->prepare("SELECT id, full_name, badge_number, status FROM visitor_logs WHERE (badge_number = ? OR badge_number LIKE ?) AND status = 'Inside' LIMIT 1");
            $findStmt->execute([$badgeQuery, "%$badgeQuery%"]);
            $found = $findStmt->fetch();

            if ($found) {
                $upStmt = $pdo->prepare("UPDATE visitor_logs SET status = 'Completed', time_out = NOW(), checked_out_by = ? WHERE id = ?");
                $upStmt->execute([getUserId(), $found['id']]);
                setFlash('success', "Instant checkout successful! Recorded departure for {$found['full_name']} ({$found['badge_number']}).");
            } else {
                setFlash('error', "No currently inside visitor found matching badge '{$badgeQuery}'.");
            }
        }
    }
    header('Location: current_visitors.php');
    exit;
}

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

$insideStmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE status = 'Inside' ORDER BY time_in DESC");
$insideStmt->execute();
$currentVisitors = $insideStmt->fetchAll();
$totalInside = count($currentVisitors);

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
                    <i class="bi bi-clock-history text-success me-2"></i> Current Visitor Monitoring
                </h3>
                <div class="text-muted small mt-1">Live register of visitors active inside the facility.</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Current Visitors</li>
                </ol>
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

        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($flashError) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Scan Card -->
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="POST" action="current_visitors.php" id="dynamicQuickScanForm" class="row g-2 align-items-center">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="scan_checkout">
                    
                    <div class="col-auto">
                        <span class="badge text-bg-success p-2">
                            <i class="bi bi-upc-scan fs-5"></i>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold small text-body">Instant Badge Checkout</div>
                        <div class="small text-muted">Scan or enter badge number to check out</div>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" name="scan_badge" class="form-control" placeholder="Scan or enter badge e.g. VIS-2026-001..." required autofocus>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right me-1"></i> Record Departure
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="register_visitor.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-plus-fill me-1"></i> Register New Visitor
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Active Visitors Table Card -->
        <div class="card card-success card-outline shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title fw-bold mb-0">
                    <span class="live-dot me-2"></span> Active Visitors Inside (<?= $totalInside ?>)
                </h3>
                <div class="card-tools d-flex gap-2">
                    <div class="input-group input-group-sm" style="width: 240px;">
                        <input type="text" id="tableSearchInput" class="form-control" placeholder="Search active visitors...">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                    </div>
                    <button type="button" class="btn btn-tool" onclick="window.location.reload();" title="Refresh">
                        <i class="bi bi-arrow-clockwise fs-5"></i>
                    </button>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped align-middle data-table dynamic-active-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Badge #</th>
                            <th>Visitor Details</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Host / Floor</th>
                            <th>Arrival Time (Time-In)</th>
                            <th>Duration Inside</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($currentVisitors)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                                    <h5>No Visitors Inside Right Now</h5>
                                    <p class="small mb-0">All visitors have checked out, or none have entered yet today.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($currentVisitors as $v): ?>
                                <tr data-visitor-id="<?= $v['id'] ?>">
                                    <td>
                                        <span class="badge text-bg-success d-inline-flex align-items-center gap-1">
                                            <span class="live-dot" style="width:6px; height:6px;"></span> Inside
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-primary font-monospace"><?= htmlspecialchars($v['badge_number']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-body"><?= htmlspecialchars($v['full_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($v['company_or_address'] ?: 'Individual / Private') ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-body"><?= htmlspecialchars($v['contact_number']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($v['email'] ?: 'No email') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?= htmlspecialchars($v['purpose_of_visit']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-body"><?= htmlspecialchars($v['person_to_visit']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($v['department_to_visit'] ?: 'Front Desk') ?></small>
                                    </td>
                                    <td>
                                        <span class="text-success fw-bold font-monospace"><?= formatTimeOnly($v['time_in']) ?></span>
                                        <div class="small text-muted"><?= formatDateOnly($v['time_in']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border fw-bold visitor-duration"><?= calculateDuration($v['time_in']) ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary" onclick="viewDynamicVisitorDetails(<?= $v['id'] ?>)" title="View Details">
                                                Details
                                            </button>
                                            <a href="visitor_pass.php?id=<?= $v['id'] ?>" target="_blank" class="btn btn-outline-info" title="Pass">
                                                Pass
                                            </a>
                                            <button type="button" class="btn btn-outline-warning" onclick="openEditVisitorModal(<?= $v['id'] ?>)" title="Edit Record">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="triggerDynamicDelete(<?= $v['id'] ?>, '<?= addslashes(htmlspecialchars($v['full_name'])) ?>', '<?= htmlspecialchars($v['badge_number']) ?>')" title="Delete Record">
                                                Delete
                                            </button>
                                            <?php if (empty($v['timed_in_confirmed'])): ?>
                                                <button type="button" class="btn btn-success" onclick="triggerDynamicTimeIn(<?= (int)$v['id'] ?>, <?= htmlspecialchars(json_encode($v['full_name']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($v['badge_number']), ENT_QUOTES, 'UTF-8') ?>)" title="Time-In">
                                                    Time-In
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger" onclick="triggerDynamicTimeOut(<?= (int)$v['id'] ?>, <?= htmlspecialchars(json_encode($v['full_name']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($v['badge_number']), ENT_QUOTES, 'UTF-8') ?>)" title="Time-Out">
                                                Time-Out
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
