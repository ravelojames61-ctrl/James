<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Operational Dashboard
 */
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/sidebar.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch.');
    } else {
        $visId = (int)($_POST['visitor_id'] ?? 0);
        $remarks = clean($_POST['remarks'] ?? '');
        if ($_POST['action'] === 'record_timein' && $visId > 0) {
            $stmt = $pdo->prepare("SELECT full_name, badge_number, remarks FROM visitor_logs WHERE id = ?");
            $stmt->execute([$visId]);
            $vis = $stmt->fetch();
            if ($vis) {
                $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Time-In: " . $remarks : "Time-In recorded"));
                $pdo->prepare("UPDATE visitor_logs SET status = 'Inside', time_in = NOW(), timed_in_confirmed = 1, time_out = NULL, registered_by = ?, remarks = ? WHERE id = ?")
                    ->execute([getUserId(), $newRemarks, $visId]);
                setFlash('success', "Time-In recorded for {$vis['full_name']} ({$vis['badge_number']}).");
            }
        } elseif ($_POST['action'] === 'record_timeout' && $visId > 0) {
            $stmt = $pdo->prepare("SELECT full_name, badge_number, remarks FROM visitor_logs WHERE id = ?");
            $stmt->execute([$visId]);
            $vis = $stmt->fetch();
            if ($vis) {
                $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Exit: " . $remarks : "Time-Out recorded"));
                $pdo->prepare("UPDATE visitor_logs SET status = 'Completed', time_out = NOW(), checked_out_by = ?, remarks = ? WHERE id = ?")
                    ->execute([getUserId(), $newRemarks, $visId]);
                setFlash('success', "Time-Out recorded for {$vis['full_name']} ({$vis['badge_number']}).");
            }
        }
    }
    header('Location: dashboard.php');
    exit;
}
$stats = getVisitorStats();
$facilityName = getSetting('facility_name', 'Aegis Corporate Center & Technology Park');
$maxCapacity = (int)getSetting('max_visitor_capacity', '250');

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

// Active inside visitors
$recentInsideStmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE status = 'Inside' ORDER BY time_in DESC LIMIT 6");
$recentInsideStmt->execute();
$recentInside = $recentInsideStmt->fetchAll();

// Purpose stats
$purposeStmt = $pdo->query("SELECT purpose_of_visit, COUNT(*) as count FROM visitor_logs GROUP BY purpose_of_visit ORDER BY count DESC LIMIT 5");
$purposeStats = $purposeStmt->fetchAll();

$purposeLabels = [];
$purposeCounts = [];
foreach ($purposeStats as $ps) {
    $purposeLabels[] = $ps['purpose_of_visit'];
    $purposeCounts[] = (int)$ps['count'];
}

// 7-Day trend stats
$trendStmt = $pdo->query("SELECT DATE(time_in) as log_date, COUNT(*) as count 
                          FROM visitor_logs 
                          WHERE time_in >= CURDATE() - INTERVAL 6 DAY 
                          GROUP BY DATE(time_in) 
                          ORDER BY log_date ASC");
$trendRows = $trendStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$chartDays = [];
$chartVolume = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartDays[] = date('D (M d)', strtotime($d));
    $chartVolume[] = isset($trendRows[$d]) ? (int)$trendRows[$d] : 0;
}
?>

<!-- App Content Header (Page header) -->
<div class="app-content-header py-3">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-bold">
                    <i class="bi bi-speedometer2 text-primary me-2"></i> Operations Dashboard
                </h3>
                <div class="text-muted small mt-1">
                    <?= htmlspecialchars($facilityName) ?> • Welcome, <strong><?= htmlspecialchars($userName) ?></strong> (<?= htmlspecialchars(getRoleBadgeLabel($currentRole)) ?>)
                </div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- App Main Content -->
<div class="app-content">
    <div class="container-fluid">
        <!-- Flash Messages -->
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

        <!-- AdminLTE 4 Small Boxes Row (Requirement #8: Display total visitors, current visitors, and completed visits) -->
        <div class="row g-3 mb-4">
            <!-- Currently Inside -->
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-success shadow-sm">
                    <div class="inner">
                        <h3 id="kpiCurrentlyInside"><?= number_format($stats['currently_inside']) ?></h3>
                        <p>Currently Inside</p>
                    </div>
                    <i class="small-box-icon bi bi-person-check-fill"></i>
                    <a href="current_visitors.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        Live Registry <i class="bi bi-arrow-right-circle ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Completed Today -->
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-info shadow-sm">
                    <div class="inner">
                        <h3 id="kpiCompletedToday"><?= number_format($stats['completed_today']) ?></h3>
                        <p>Completed Visits Today</p>
                    </div>
                    <i class="small-box-icon bi bi-door-closed-fill"></i>
                    <a href="visitor_history.php?status=Completed" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        View Departures <i class="bi bi-arrow-right-circle ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Total Registered -->
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-primary shadow-sm">
                    <div class="inner">
                        <h3 id="kpiTotalRegistered"><?= number_format($stats['total_all_time']) ?></h3>
                        <p>Total Registered Visitors</p>
                    </div>
                    <i class="small-box-icon bi bi-people-fill"></i>
                    <a href="visitor_history.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        Browse Records <i class="bi bi-arrow-right-circle ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Occupancy Capacity -->
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-warning shadow-sm">
                    <div class="inner">
                        <h3 id="kpiOccupancyRate"><?= $stats['active_capacity_percent'] ?><sup class="fs-5">%</sup></h3>
                        <p>Facility Occupancy (<span id="kpiOccupancySub"><?= $stats['currently_inside'] ?> / <?= $maxCapacity ?></span>)</p>
                    </div>
                    <i class="small-box-icon bi bi-pie-chart-fill"></i>
                    <a href="settings.php" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                        Capacity Settings <i class="bi bi-arrow-right-circle ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Operational Actions -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card card-outline card-secondary shadow-sm">
                    <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold small text-muted text-uppercase">Quick Actions:</span>
                            <a href="register_visitor.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-person-plus-fill me-1"></i> Register New Visitor
                            </a>
                            <a href="current_visitors.php" class="btn btn-success btn-sm">
                                <i class="bi bi-clock-history me-1"></i> Monitor Inside (<?= $stats['currently_inside'] ?>)
                            </a>
                            <a href="visitor_history.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-search me-1"></i> Search Records
                            </a>
                        </div>
                        <?php if (can('view_reports')): ?>
                            <a href="reports.php" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-bar-chart-line-fill me-1"></i> View Reports
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card card-primary card-outline shadow-sm h-100">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title fw-semibold">
                                <i class="bi bi-graph-up text-primary me-2"></i> 7-Day Visitor Volume Trend
                            </h3>
                            <span class="badge text-bg-primary">Past 7 Days</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 280px; position: relative;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-info card-outline shadow-sm h-100">
                    <div class="card-header border-0">
                        <h3 class="card-title fw-semibold">
                            <i class="bi bi-pie-chart text-info me-2"></i> Visit Purposes
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="height: 280px; position: relative;">
                            <canvas id="purposeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Active Visitors Table (Requirement #6 & #8) -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card card-success card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-semibold">
                            <i class="bi bi-clock-history text-success me-2"></i> Visitors Currently Inside Facility (<?= count($recentInside) ?>)
                        </h3>
                        <div class="card-tools">
                            <a href="current_visitors.php" class="btn btn-tool" title="View Full List">
                                <i class="bi bi-arrow-right-circle fs-5"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped align-middle data-table dynamic-active-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Badge #</th>
                                    <th>Visitor Name</th>
                                    <th>Host / Department</th>
                                    <th>Purpose</th>
                                    <th>Arrival Time (Time-In)</th>
                                    <th>Duration Inside</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentInside)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-check2-circle fs-2 text-success d-block mb-2"></i>
                                            No visitors currently logged inside the facility.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentInside as $v): ?>
                                        <tr data-visitor-id="<?= $v['id'] ?>">
                                            <td>
                                                <span class="badge text-bg-primary font-monospace"><?= htmlspecialchars($v['badge_number']) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-body"><?= htmlspecialchars($v['full_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($v['company_or_address'] ?: 'Individual / Private') ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($v['person_to_visit']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($v['department_to_visit'] ?: 'Front Desk') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge text-bg-secondary"><?= htmlspecialchars($v['purpose_of_visit']) ?></span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold font-monospace"><?= formatTimeOnly($v['time_in']) ?></span>
                                                <div class="small text-muted"><?= formatDateOnly($v['time_in']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge text-bg-light border visitor-duration"><?= calculateDuration($v['time_in']) ?></span>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Trend Chart
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartDays) ?>,
                datasets: [{
                    label: 'Visitor Check-Ins',
                    data: <?= json_encode($chartVolume) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0d6efd',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Purpose Chart
    const purposeCtx = document.getElementById('purposeChart');
    if (purposeCtx) {
        new Chart(purposeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($purposeLabels) ?>,
                datasets: [{
                    data: <?= json_encode($purposeCounts) ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#d63384'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
