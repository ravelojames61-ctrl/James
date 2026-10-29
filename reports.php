<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Reports & Analytics
 */
$pageTitle = 'Visitor Reports';
$activeNav = 'reports';

require_once __DIR__ . '/config/auth.php';
requireRole(['admin', 'receptionist']);
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();

$reportType = $_GET['type'] ?? 'daily';
if (!in_array($reportType, ['daily', 'weekly', 'monthly'], true)) {
    $reportType = 'daily';
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedWeek = $_GET['week'] ?? date('Y-\WW');

$reportTitle = '';
$periodLabel = '';
$summaryStats = [
    'total_visitors' => 0,
    'completed'      => 0,
    'inside'         => 0,
    'top_purpose'    => '—',
];
$reportVisitors = [];
$chartLabels = [];
$chartValues = [];

if ($reportType === 'daily') {
    $reportTitle = 'Daily Visitor Summary Report';
    $periodLabel = date('l, F j, Y', strtotime($selectedDate));

    $statStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Inside' THEN 1 ELSE 0 END) as inside
        FROM visitor_logs 
        WHERE DATE(time_in) = ?
    ");
    $statStmt->execute([$selectedDate]);
    $counts = $statStmt->fetch();
    $summaryStats['total_visitors'] = (int)($counts['total'] ?? 0);
    $summaryStats['completed'] = (int)($counts['completed'] ?? 0);
    $summaryStats['inside'] = (int)($counts['inside'] ?? 0);

    $hourStmt = $pdo->prepare("
        SELECT HOUR(time_in) as hr, COUNT(*) as cnt 
        FROM visitor_logs 
        WHERE DATE(time_in) = ? 
        GROUP BY HOUR(time_in)
    ");
    $hourStmt->execute([$selectedDate]);
    $hourlyCounts = $hourStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    for ($h = 7; $h <= 19; $h++) {
        $chartLabels[] = date('g A', strtotime("$h:00"));
        $chartValues[] = isset($hourlyCounts[$h]) ? (int)$hourlyCounts[$h] : 0;
    }

    $purpStmt = $pdo->prepare("SELECT purpose_of_visit, COUNT(*) as c FROM visitor_logs WHERE DATE(time_in) = ? GROUP BY purpose_of_visit ORDER BY c DESC LIMIT 1");
    $purpStmt->execute([$selectedDate]);
    $topP = $purpStmt->fetch();
    if ($topP) $summaryStats['top_purpose'] = $topP['purpose_of_visit'] . " ({$topP['c']})";

    $listStmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE DATE(time_in) = ? ORDER BY time_in ASC");
    $listStmt->execute([$selectedDate]);
    $reportVisitors = $listStmt->fetchAll();

} elseif ($reportType === 'weekly') {
    $reportTitle = 'Weekly Visitor Summary Report';
    
    $dto = new DateTime();
    $weekParts = explode('-W', $selectedWeek);
    $year = $weekParts[0] ?? date('Y');
    $wNum = $weekParts[1] ?? date('W');
    $dto->setISODate((int)$year, (int)$wNum);
    $weekStart = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $weekEnd = $dto->format('Y-m-d');

    $periodLabel = "Week " . (int)$wNum . " (" . date('M j', strtotime($weekStart)) . " – " . date('M j, Y', strtotime($weekEnd)) . ")";

    $statStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Inside' THEN 1 ELSE 0 END) as inside
        FROM visitor_logs 
        WHERE DATE(time_in) BETWEEN ? AND ?
    ");
    $statStmt->execute([$weekStart, $weekEnd]);
    $counts = $statStmt->fetch();
    $summaryStats['total_visitors'] = (int)($counts['total'] ?? 0);
    $summaryStats['completed'] = (int)($counts['completed'] ?? 0);
    $summaryStats['inside'] = (int)($counts['inside'] ?? 0);

    $dayStmt = $pdo->prepare("
        SELECT DATE(time_in) as log_date, COUNT(*) as cnt 
        FROM visitor_logs 
        WHERE DATE(time_in) BETWEEN ? AND ? 
        GROUP BY DATE(time_in)
    ");
    $dayStmt->execute([$weekStart, $weekEnd]);
    $dailyCounts = $dayStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    for ($i = 0; $i < 7; $i++) {
        $curD = date('Y-m-d', strtotime("$weekStart +$i days"));
        $chartLabels[] = date('D (M d)', strtotime($curD));
        $chartValues[] = isset($dailyCounts[$curD]) ? (int)$dailyCounts[$curD] : 0;
    }

    $purpStmt = $pdo->prepare("SELECT purpose_of_visit, COUNT(*) as c FROM visitor_logs WHERE DATE(time_in) BETWEEN ? AND ? GROUP BY purpose_of_visit ORDER BY c DESC LIMIT 1");
    $purpStmt->execute([$weekStart, $weekEnd]);
    $topP = $purpStmt->fetch();
    if ($topP) $summaryStats['top_purpose'] = $topP['purpose_of_visit'] . " ({$topP['c']})";

    $listStmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE DATE(time_in) BETWEEN ? AND ? ORDER BY time_in DESC");
    $listStmt->execute([$weekStart, $weekEnd]);
    $reportVisitors = $listStmt->fetchAll();

} elseif ($reportType === 'monthly') {
    $reportTitle = 'Monthly Visitor Summary Report';
    $monthStart = $selectedMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $periodLabel = date('F Y', strtotime($monthStart));

    $statStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Inside' THEN 1 ELSE 0 END) as inside
        FROM visitor_logs 
        WHERE DATE(time_in) BETWEEN ? AND ?
    ");
    $statStmt->execute([$monthStart, $monthEnd]);
    $counts = $statStmt->fetch();
    $summaryStats['total_visitors'] = (int)($counts['total'] ?? 0);
    $summaryStats['completed'] = (int)($counts['completed'] ?? 0);
    $summaryStats['inside'] = (int)($counts['inside'] ?? 0);

    $purpStmt = $pdo->prepare("
        SELECT purpose_of_visit, COUNT(*) as cnt 
        FROM visitor_logs 
        WHERE DATE(time_in) BETWEEN ? AND ? 
        GROUP BY purpose_of_visit 
        ORDER BY cnt DESC
    ");
    $purpStmt->execute([$monthStart, $monthEnd]);
    $purpStats = $purpStmt->fetchAll();

    foreach ($purpStats as $ps) {
        $chartLabels[] = $ps['purpose_of_visit'];
        $chartValues[] = (int)$ps['cnt'];
    }
    if (!empty($purpStats)) {
        $summaryStats['top_purpose'] = $purpStats[0]['purpose_of_visit'] . " ({$purpStats[0]['cnt']})";
    }

    $listStmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE DATE(time_in) BETWEEN ? AND ? ORDER BY time_in DESC");
    $listStmt->execute([$monthStart, $monthEnd]);
    $reportVisitors = $listStmt->fetchAll();
}

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $reportType . '_visitor_report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');

    fputcsv($output, ["REPORT: $reportTitle", "PERIOD: $periodLabel", "GENERATED: " . date('Y-m-d H:i:s')]);
    fputcsv($output, ["Total Visitors: {$summaryStats['total_visitors']}", "Completed: {$summaryStats['completed']}", "Currently Inside: {$summaryStats['inside']}", "Top Purpose: {$summaryStats['top_purpose']}"]);
    fputcsv($output, []);
    fputcsv($output, ['Badge #', 'Full Name', 'Company/Address', 'Contact', 'Purpose', 'Person to Visit', 'Department', 'Time-In', 'Time-Out', 'Status', 'Remarks']);

    foreach ($reportVisitors as $r) {
        fputcsv($output, [
            $r['badge_number'],
            $r['full_name'],
            $r['company_or_address'],
            $r['contact_number'],
            $r['purpose_of_visit'],
            $r['person_to_visit'],
            $r['department_to_visit'],
            $r['time_in'],
            $r['time_out'],
            $r['status'],
            $r['remarks']
        ]);
    }
    fclose($output);
    exit;
}

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
                    <i class="bi bi-bar-chart-line-fill text-primary me-2"></i> Visitor Reports & Summaries
                </h3>
                <div class="text-muted small mt-1">Generate aggregated daily, weekly, and monthly visitor traffic summaries.</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Visitor Reports</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- App Content -->
<div class="app-content">
    <div class="container-fluid">
        <!-- Report Navigator Card -->
        <div class="card card-outline card-secondary shadow-sm mb-4 no-print">
            <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- Type Tabs -->
                <div class="btn-group" role="group">
                    <a href="reports.php?type=daily" class="btn btn-sm <?= ($reportType === 'daily') ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-calendar-day me-1"></i> Daily Summary
                    </a>
                    <a href="reports.php?type=weekly" class="btn btn-sm <?= ($reportType === 'weekly') ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-calendar-week me-1"></i> Weekly Summary
                    </a>
                    <a href="reports.php?type=monthly" class="btn btn-sm <?= ($reportType === 'monthly') ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-calendar-month me-1"></i> Monthly Summary
                    </a>
                </div>

                <!-- Date / Period Controls -->
                <form method="GET" action="reports.php" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($reportType) ?>">

                    <?php if ($reportType === 'daily'): ?>
                        <span class="small fw-semibold text-secondary">Date:</span>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
                    <?php elseif ($reportType === 'weekly'): ?>
                        <span class="small fw-semibold text-secondary">Week:</span>
                        <input type="week" name="week" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedWeek) ?>" onchange="this.form.submit()">
                    <?php elseif ($reportType === 'monthly'): ?>
                        <span class="small fw-semibold text-secondary">Month:</span>
                        <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
                    <?php endif; ?>

                    <button type="submit" class="btn btn-secondary btn-sm">Load</button>
                </form>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <?php
                    $exportParams = $_GET;
                    $exportParams['export'] = 'csv';
                    $reportCsvUrl = 'reports.php?' . http_build_query($exportParams);
                    ?>
                    <a href="<?= htmlspecialchars($reportCsvUrl) ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Report Header Section -->
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($reportTitle) ?></h4>
                <div class="text-primary fw-semibold fs-5"><?= htmlspecialchars($periodLabel) ?></div>
            </div>
            <div class="text-end small text-muted">
                Facility: <strong><?= htmlspecialchars(getSetting('facility_name', 'Aegis Corporate Center')) ?></strong><br>
                Generated: <?= date('M d, Y h:i A') ?>
            </div>
        </div>

        <!-- Small Boxes Row for Report Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-primary shadow-sm">
                    <div class="inner">
                        <h3><?= number_format($summaryStats['total_visitors']) ?></h3>
                        <p>Total Visits In Period</p>
                    </div>
                    <i class="small-box-icon bi bi-people-fill"></i>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-success shadow-sm">
                    <div class="inner">
                        <h3><?= number_format($summaryStats['completed']) ?></h3>
                        <p>Completed Visits</p>
                    </div>
                    <i class="small-box-icon bi bi-door-closed-fill"></i>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-warning shadow-sm">
                    <div class="inner">
                        <h3><?= number_format($summaryStats['inside']) ?></h3>
                        <p>Currently Inside</p>
                    </div>
                    <i class="small-box-icon bi bi-person-check-fill"></i>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-info shadow-sm">
                    <div class="inner">
                        <h4 class="text-truncate fw-bold mb-1" style="max-width: 90%;"><?= htmlspecialchars($summaryStats['top_purpose']) ?></h4>
                        <p>Top Visit Purpose</p>
                    </div>
                    <i class="small-box-icon bi bi-pie-chart-fill"></i>
                </div>
            </div>
        </div>

        <!-- Chart Card (Hidden on Print) -->
        <div class="card card-primary card-outline shadow-sm mb-4 no-print">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                    <?= ($reportType === 'daily') ? 'Hourly Traffic Flow Distribution' : (($reportType === 'weekly') ? 'Daily Traffic Volume Breakdown' : 'Monthly Visit Purpose Breakdown') ?>
                </h3>
            </div>
            <div class="card-body">
                <div style="height: 260px; position: relative;">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detail Table Card -->
        <div class="card card-secondary card-outline shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-table text-secondary me-2"></i> Detailed Visit Records for <?= htmlspecialchars($periodLabel) ?> (<?= count($reportVisitors) ?> visits)
                </h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Badge #</th>
                            <th>Visitor Name</th>
                            <th>Company / Address</th>
                            <th>Purpose</th>
                            <th>Host / Floor</th>
                            <th>Arrival (Time-In)</th>
                            <th>Departure (Time-Out)</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportVisitors)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No visitor records found for <?= htmlspecialchars($periodLabel) ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportVisitors as $rv): ?>
                                <tr>
                                    <td>
                                        <span class="badge text-bg-primary font-monospace"><?= htmlspecialchars($rv['badge_number']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-body"><?= htmlspecialchars($rv['full_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($rv['contact_number']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($rv['company_or_address'] ?: 'Individual / Private') ?></td>
                                    <td>
                                        <span class="badge text-bg-info text-white"><?= htmlspecialchars($rv['purpose_of_visit']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-body"><?= htmlspecialchars($rv['person_to_visit']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($rv['department_to_visit'] ?: 'Front Desk') ?></small>
                                    </td>
                                    <td>
                                        <span class="text-success fw-bold font-monospace"><?= formatTimeOnly($rv['time_in']) ?></span>
                                        <div class="small text-muted"><?= formatDateOnly($rv['time_in']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($rv['time_out']): ?>
                                            <span class="font-monospace fw-bold text-secondary"><?= formatTimeOnly($rv['time_out']) ?></span>
                                            <div class="small text-muted"><?= formatDateOnly($rv['time_out']) ?></div>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Active Inside</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= calculateDuration($rv['time_in'], $rv['time_out']) ?></td>
                                    <td>
                                        <?php if ($rv['status'] === 'Inside'): ?>
                                            <span class="badge text-bg-success">Inside</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Completed</span>
                                        <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const reportCtx = document.getElementById('reportChart');
    if (reportCtx) {
        new Chart(reportCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Visitor Volume',
                    data: <?= json_encode($chartValues) ?>,
                    backgroundColor: '#0d6efd',
                    borderRadius: 4
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
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
