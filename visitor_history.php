<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Visitor History & Search Engine
 */
$pageTitle = 'Visitor Records & History';
$activeNav = 'history';

require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();

// Handle POST actions (Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security validation failed.');
    } else {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'record_timein') {
            $visId = (int)($_POST['visitor_id'] ?? 0);
            $remarks = clean($_POST['remarks'] ?? '');
            if ($visId > 0) {
                $stmt = $pdo->prepare("SELECT full_name, badge_number, status, remarks FROM visitor_logs WHERE id = ?");
                $stmt->execute([$visId]);
                $vis = $stmt->fetch();
                if ($vis) {
                    $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Time-In: " . $remarks : "Re-entry / Timed In"));
                    $up = $pdo->prepare("
                        UPDATE visitor_logs 
                        SET status = 'Inside', 
                            time_in = NOW(), 
                            timed_in_confirmed = 1, 
                            time_out = NULL, 
                            registered_by = ?,
                            remarks = ? 
                        WHERE id = ?
                    ");
                    $up->execute([getUserId(), $newRemarks, $visId]);
                    setFlash('success', "Time-In recorded for {$vis['full_name']} ({$vis['badge_number']}). Status updated to Inside.");
                }
            }
            header('Location: visitor_history.php');
            exit;
        }

        if ($postAction === 'record_timeout') {
            $visId = (int)($_POST['visitor_id'] ?? 0);
            $remarks = clean($_POST['remarks'] ?? '');
            if ($visId > 0) {
                $stmt = $pdo->prepare("SELECT full_name, badge_number, status, remarks FROM visitor_logs WHERE id = ?");
                $stmt->execute([$visId]);
                $vis = $stmt->fetch();
                if ($vis) {
                    $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Exit: " . $remarks : "Time-Out recorded"));
                    $up = $pdo->prepare("
                        UPDATE visitor_logs 
                        SET status = 'Completed', 
                            time_out = NOW(), 
                            checked_out_by = ?, 
                            remarks = ? 
                        WHERE id = ?
                    ");
                    $up->execute([getUserId(), $newRemarks, $visId]);
                    setFlash('success', "Time-Out recorded for {$vis['full_name']} ({$vis['badge_number']}). Status updated to Completed.");
                }
            }
            header('Location: visitor_history.php');
            exit;
        }

        if ($postAction === 'delete_visitor') {
            $delId = (int)($_POST['visitor_id'] ?? 0);
            if ($delId > 0) {
                $stmt = $pdo->prepare("SELECT full_name, badge_number FROM visitor_logs WHERE id = ?");
                $stmt->execute([$delId]);
                $vis = $stmt->fetch();
                if ($vis) {
                    $delStmt = $pdo->prepare("DELETE FROM visitor_logs WHERE id = ?");
                    $delStmt->execute([$delId]);
                    setFlash('success', "Visitor '{$vis['full_name']}' ({$vis['badge_number']}) was permanently deleted.");
                }
            }
            header('Location: visitor_history.php');
            exit;
        }

        if ($postAction === 'update_visitor') {
            $visId = (int)($_POST['visitor_id'] ?? 0);
            $fullName = clean($_POST['full_name'] ?? '');
            $contact = clean($_POST['contact_number'] ?? '');
            $email = clean($_POST['email'] ?? '');
            $company = clean($_POST['company_or_address'] ?? '');
            $idType = clean($_POST['id_type'] ?? '');
            $idNumber = clean($_POST['id_number'] ?? '');
            $purpose = clean($_POST['purpose_of_visit'] ?? '');
            $host = clean($_POST['person_to_visit'] ?? '');
            $dept = clean($_POST['department_to_visit'] ?? '');
            $status = clean($_POST['status'] ?? 'Inside');
            $timeIn = clean($_POST['time_in'] ?? '');
            $timeOut = clean($_POST['time_out'] ?? '');
            $remarks = clean($_POST['remarks'] ?? '');

            if ($visId > 0 && !empty($fullName) && !empty($contact) && !empty($purpose) && !empty($host)) {
                $formattedTimeIn = !empty($timeIn) ? date('Y-m-d H:i:s', strtotime($timeIn)) : date('Y-m-d H:i:s');
                $formattedTimeOut = (!empty($timeOut) && $status === 'Completed') ? date('Y-m-d H:i:s', strtotime($timeOut)) : null;
                if ($status === 'Inside') {
                    $formattedTimeOut = null;
                }

                $stmt = $pdo->prepare("
                    UPDATE visitor_logs 
                    SET full_name = ?, contact_number = ?, email = ?, company_or_address = ?,
                        id_type = ?, id_number = ?, purpose_of_visit = ?, person_to_visit = ?,
                        department_to_visit = ?, time_in = ?, time_out = ?, status = ?, remarks = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $fullName, $contact, $email, $company, $idType, $idNumber,
                    $purpose, $host, $dept, $formattedTimeIn, $formattedTimeOut, $status, $remarks, $visId
                ]);
                setFlash('success', "Visitor '{$fullName}' details updated successfully.");
            } else {
                setFlash('error', "Please fill in all required fields to update the visitor record.");
            }
            header('Location: visitor_history.php');
            exit;
        }
    }
}

$searchQuery = trim($_GET['q'] ?? '');
$filterDateFrom = trim($_GET['date_from'] ?? '');
$filterDateTo = trim($_GET['date_to'] ?? '');
$filterPurpose = trim($_GET['purpose'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM visitor_logs WHERE 1=1";
$params = [];

if (!empty($searchQuery)) {
    $sql .= " AND (full_name LIKE ? OR badge_number LIKE ? OR company_or_address LIKE ? OR person_to_visit LIKE ? OR contact_number LIKE ?)";
    $like = "%{$searchQuery}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($filterDateFrom)) {
    $sql .= " AND DATE(time_in) >= ?";
    $params[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $sql .= " AND DATE(time_in) <= ?";
    $params[] = $filterDateTo;
}

if (!empty($filterPurpose)) {
    $sql .= " AND purpose_of_visit = ?";
    $params[] = $filterPurpose;
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY time_in DESC";

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->prepare($sql);
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=visitor_records_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['ID', 'Badge Number', 'Full Name', 'Contact', 'Email', 'Company/Address', 'ID Type', 'ID Number', 'Purpose', 'Person to Visit', 'Department', 'Time-In', 'Time-Out', 'Status', 'Remarks']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id'],
            $r['badge_number'],
            $r['full_name'],
            $r['contact_number'],
            $r['email'],
            $r['company_or_address'],
            $r['id_type'],
            $r['id_number'],
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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();
$totalFound = count($records);

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
                    <i class="bi bi-journal-text text-primary me-2"></i> Visitor Records & History
                </h3>
                <div class="text-muted small mt-1">Search, filter, and export comprehensive facility visitor archives.</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Visitor Records</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- App Content -->
<div class="app-content">
    <div class="container-fluid">
        <?php if ($flashSuccess = getFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                <?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError = getFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                <?= htmlspecialchars($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-semibold">
                    <i class="bi bi-funnel-fill text-secondary me-2"></i> Filter & Search Records
                </h3>
                <?php if (!empty($searchQuery) || !empty($filterDateFrom) || !empty($filterDateTo) || !empty($filterPurpose) || !empty($filterStatus)): ?>
                    <a href="visitor_history.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Reset Filters
                    </a>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <form method="GET" action="visitor_history.php" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold" for="q">Keyword Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" id="q" class="form-control" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Name, badge, host...">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold" for="date_from">Arrival Date (From)</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filterDateFrom) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold" for="date_to">Arrival Date (To)</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filterDateTo) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold" for="purpose">Purpose of Visit</label>
                        <select name="purpose" id="purpose" class="form-select">
                            <option value="">-- All Purposes --</option>
                            <?php foreach (getPurposeOptions() as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= ($filterPurpose === $p) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold" for="status">Visit Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">-- All Statuses --</option>
                            <option value="Inside" <?= ($filterStatus === 'Inside') ? 'selected' : '' ?>>Inside</option>
                            <option value="Completed" <?= ($filterStatus === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Records Table Card -->
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title fw-bold mb-0">
                    <i class="bi bi-list-check text-primary me-2"></i> Visitor Archive (<?= $totalFound ?> records found)
                </h3>
                <div class="card-tools d-flex gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <input type="text" id="tableSearchInput" class="form-control" placeholder="Quick filter page...">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                    </div>
                    <?php
                    $exportParams = $_GET;
                    $exportParams['export'] = 'csv';
                    $csvUrl = 'visitor_history.php?' . http_build_query($exportParams);
                    ?>
                    <a href="<?= htmlspecialchars($csvUrl) ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                    </a>
                    <a href="register_visitor.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Register Visitor
                    </a>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped align-middle data-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Badge #</th>
                            <th>Status</th>
                            <th>Visitor Name & Company</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Person Visited (Host)</th>
                            <th>Time-In</th>
                            <th>Time-Out</th>
                            <th>Duration</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                    <h5>No Visitor Records Found</h5>
                                    <p class="small mb-0">Try clearing or adjusting your search filters.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $v): ?>
                                <tr data-visitor-id="<?= $v['id'] ?>">
                                    <td>
                                        <span class="badge text-bg-primary font-monospace"><?= htmlspecialchars($v['badge_number']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($v['status'] === 'Inside'): ?>
                                            <span class="badge text-bg-success">
                                                 <span class="live-dot" style="width:6px; height:6px;"></span> Inside
                                            </span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-body"><?= htmlspecialchars($v['full_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($v['company_or_address'] ?: 'Individual / Private') ?></small>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold text-body"><?= htmlspecialchars($v['contact_number']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-info text-white"><?= htmlspecialchars($v['purpose_of_visit']) ?></span>
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
                                        <?php if ($v['time_out']): ?>
                                            <span class="font-monospace fw-bold text-secondary"><?= formatTimeOnly($v['time_out']) ?></span>
                                            <div class="small text-muted"><?= formatDateOnly($v['time_out']) ?></div>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border"><?= calculateDuration($v['time_in'], $v['time_out']) ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary" onclick="viewDynamicVisitorDetails(<?= $v['id'] ?>)" title="View Details">
                                                View
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
                                            <?php if ($v['status'] === 'Inside' && empty($v['time_out'])): ?>
                                                <?php if (empty($v['timed_in_confirmed'])): ?>
                                                    <button type="button" class="btn btn-success" onclick="triggerDynamicTimeIn(<?= (int)$v['id'] ?>, <?= htmlspecialchars(json_encode($v['full_name']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($v['badge_number']), ENT_QUOTES, 'UTF-8') ?>)" title="Time-In">
                                                        Time-In
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-danger" onclick="triggerDynamicTimeOut(<?= (int)$v['id'] ?>, <?= htmlspecialchars(json_encode($v['full_name']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($v['badge_number']), ENT_QUOTES, 'UTF-8') ?>)" title="Time-Out">
                                                    Time-Out
                                                </button>
                                            <?php endif; ?>
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
