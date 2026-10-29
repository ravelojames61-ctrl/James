<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Edit Visitor Record (Full Page Editor)
 */
$pageTitle = 'Edit Visitor Record';
$activeNav = 'history';

require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();
$errorMessage = '';
$visitorId = (int)($_GET['id'] ?? $_POST['visitor_id'] ?? 0);

if ($visitorId <= 0) {
    setFlash('error', 'Invalid visitor record requested.');
    header('Location: visitor_history.php');
    exit;
}

// Fetch visitor record
$stmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE id = ?");
$stmt->execute([$visitorId]);
$visitor = $stmt->fetch();

if (!$visitor) {
    setFlash('error', 'Visitor record not found.');
    header('Location: visitor_history.php');
    exit;
}

// Handle Form POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? 'update';

        // Delete action
        if ($action === 'delete') {
            $delStmt = $pdo->prepare("DELETE FROM visitor_logs WHERE id = ?");
            $delStmt->execute([$visitorId]);
            setFlash('success', "Visitor record for '{$visitor['full_name']}' ({$visitor['badge_number']}) was permanently deleted.");
            header('Location: visitor_history.php');
            exit;
        }

        // Update action
        $fullName          = clean($_POST['full_name'] ?? '');
        $contactNumber     = clean($_POST['contact_number'] ?? '');
        $email             = clean($_POST['email'] ?? '');
        $companyOrAddress  = clean($_POST['company_or_address'] ?? '');
        $idType            = clean($_POST['id_type'] ?? '');
        $idNumber          = clean($_POST['id_number'] ?? '');
        $purposeOfVisit    = clean($_POST['purpose_of_visit'] ?? '');
        $personToVisit     = clean($_POST['person_to_visit'] ?? '');
        $departmentToVisit = clean($_POST['department_to_visit'] ?? '');
        $status            = clean($_POST['status'] ?? 'Inside');
        $timeIn            = clean($_POST['time_in'] ?? '');
        $timeOut           = clean($_POST['time_out'] ?? '');
        $remarks           = clean($_POST['remarks'] ?? '');

        if (empty($fullName) || empty($contactNumber) || empty($purposeOfVisit) || empty($personToVisit)) {
            $errorMessage = 'Please complete all required fields (Full Name, Contact Number, Purpose of Visit, and Host).';
        } else {
            $formattedTimeIn = !empty($timeIn) ? date('Y-m-d H:i:s', strtotime($timeIn)) : date('Y-m-d H:i:s');
            $formattedTimeOut = (!empty($timeOut) && $status === 'Completed') ? date('Y-m-d H:i:s', strtotime($timeOut)) : null;
            if ($status === 'Inside') {
                $formattedTimeOut = null;
            }

            try {
                $upStmt = $pdo->prepare("
                    UPDATE visitor_logs 
                    SET full_name = ?,
                        contact_number = ?,
                        email = ?,
                        company_or_address = ?,
                        id_type = ?,
                        id_number = ?,
                        purpose_of_visit = ?,
                        person_to_visit = ?,
                        department_to_visit = ?,
                        time_in = ?,
                        time_out = ?,
                        status = ?,
                        remarks = ?
                    WHERE id = ?
                ");

                $upStmt->execute([
                    $fullName,
                    $contactNumber,
                    $email,
                    $companyOrAddress,
                    $idType,
                    $idNumber,
                    $purposeOfVisit,
                    $personToVisit,
                    $departmentToVisit,
                    $formattedTimeIn,
                    $formattedTimeOut,
                    $status,
                    $remarks,
                    $visitorId
                ]);

                setFlash('success', "Visitor '{$fullName}' ({$visitor['badge_number']}) was successfully updated.");
                header('Location: visitor_history.php');
                exit;
            } catch (Exception $e) {
                $errorMessage = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Format raw time for input datetime-local
$rawTimeIn = !empty($visitor['time_in']) ? date('Y-m-d\TH:i', strtotime($visitor['time_in'])) : '';
$rawTimeOut = !empty($visitor['time_out']) ? date('Y-m-d\TH:i', strtotime($visitor['time_out'])) : '';

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
                    <i class="bi bi-pencil-square text-warning me-2"></i> Edit Visitor Record
                </h3>
                <div class="text-muted small mt-1">
                    Modifying visit log for <strong class="text-body"><?= htmlspecialchars($visitor['full_name']) ?></strong> 
                    (<span class="badge text-bg-primary font-monospace"><?= htmlspecialchars($visitor['badge_number']) ?></span>)
                </div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="visitor_history.php" class="text-decoration-none">Visitor Records</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Record</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- App Content -->
<div class="app-content">
    <div class="container-fluid">
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card card-outline card-warning shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold mb-0">
                            <i class="bi bi-person-lines-fill text-warning me-2"></i> Visitor Information & Log Details
                        </h3>
                        <div class="card-tools d-flex gap-2">
                            <a href="visitor_pass.php?id=<?= $visitor['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-printer me-1"></i> Print Pass
                            </a>
                            <a href="visitor_history.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Back to Archive
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="edit_visitor.php?id=<?= $visitor['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="visitor_id" value="<?= $visitor['id'] ?>">

                        <div class="card-body p-4">
                            <!-- Badge & Status Banner -->
                            <div class="alert alert-secondary d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
                                <div>
                                    <span class="text-muted small text-uppercase fw-semibold d-block">Issued Badge Number</span>
                                    <span class="fs-5 font-monospace fw-bold text-primary"><?= htmlspecialchars($visitor['badge_number']) ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small text-uppercase fw-semibold d-block">Current Status</span>
                                    <?php if ($visitor['status'] === 'Inside'): ?>
                                        <span class="badge text-bg-success fs-6">
                                            <span class="live-dot" style="width:6px; height:6px;"></span> Inside
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary fs-6">Completed</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Personal Credentials -->
                                <div class="col-12">
                                    <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                                        <i class="bi bi-person-vcard me-2"></i> Personal & Contact Information
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="full_name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($visitor['full_name']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="contact_number">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= htmlspecialchars($visitor['contact_number']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="email">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($visitor['email'] ?? '') ?>" placeholder="visitor@example.com">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="company_or_address">Company / Organization / Address</label>
                                    <input type="text" name="company_or_address" id="company_or_address" class="form-control" value="<?= htmlspecialchars($visitor['company_or_address'] ?? '') ?>" placeholder="Company or residential address">
                                </div>

                                <!-- Identification Details -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                                        <i class="bi bi-card-heading me-2"></i> Identification Credentials
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="id_type">ID Type Presented</label>
                                    <select name="id_type" id="id_type" class="form-select">
                                        <option value="">-- None / Not Specified --</option>
                                        <?php foreach (getIdTypeOptions() as $t): ?>
                                            <option value="<?= htmlspecialchars($t) ?>" <?= ($visitor['id_type'] === $t) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="id_number">ID Number / Reference</label>
                                    <input type="text" name="id_number" id="id_number" class="form-control" value="<?= htmlspecialchars($visitor['id_number'] ?? '') ?>" placeholder="e.g. DL-987213">
                                </div>

                                <!-- Visit Purpose & Host -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                                        <i class="bi bi-building me-2"></i> Visit Details & Host Assignment
                                    </h6>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="purpose_of_visit">Purpose of Visit <span class="text-danger">*</span></label>
                                    <select name="purpose_of_visit" id="purpose_of_visit" class="form-select" required>
                                        <option value="">-- Select Purpose --</option>
                                        <?php foreach (getPurposeOptions() as $p): ?>
                                            <option value="<?= htmlspecialchars($p) ?>" <?= ($visitor['purpose_of_visit'] === $p) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="person_to_visit">Person to Visit (Host) <span class="text-danger">*</span></label>
                                    <input type="text" name="person_to_visit" id="person_to_visit" class="form-control" value="<?= htmlspecialchars($visitor['person_to_visit']) ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="department_to_visit">Department / Floor</label>
                                    <select name="department_to_visit" id="department_to_visit" class="form-select">
                                        <option value="">-- Select Department --</option>
                                        <?php foreach (getDepartmentOptions() as $d): ?>
                                            <option value="<?= htmlspecialchars($d) ?>" <?= ($visitor['department_to_visit'] === $d) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($d) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Status & Timestamps -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                                        <i class="bi bi-clock-history me-2"></i> Visit Timestamps & Status
                                    </h6>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="status">Visit Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select" onchange="toggleEditTimeOutField(this.value)">
                                        <option value="Inside" <?= ($visitor['status'] === 'Inside') ? 'selected' : '' ?>>Inside (Active)</option>
                                        <option value="Completed" <?= ($visitor['status'] === 'Completed') ? 'selected' : '' ?>>Completed (Checked Out)</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="time_in">Time-In (Arrival) <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="time_in" id="time_in" class="form-control" value="<?= $rawTimeIn ?>" required>
                                </div>

                                <div class="col-md-4" id="editTimeOutContainer">
                                    <label class="form-label small fw-semibold" for="editTimeOut">Time-Out (Departure)</label>
                                    <input type="datetime-local" name="time_out" id="editTimeOut" class="form-control" value="<?= $rawTimeOut ?>" <?= ($visitor['status'] === 'Inside') ? 'disabled' : '' ?>>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold" for="remarks">Remarks / Administrative Notes</label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter any notes, entry conditions, or departure observations"><?= htmlspecialchars($visitor['remarks'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-danger" onclick="triggerDynamicDelete(<?= $visitor['id'] ?>, '<?= addslashes(htmlspecialchars($visitor['full_name'])) ?>', '<?= htmlspecialchars($visitor['badge_number']) ?>')">
                                <i class="bi bi-trash me-1"></i> Delete Visitor Record
                            </button>

                            <div class="d-flex gap-2">
                                <a href="visitor_history.php" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-warning fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
