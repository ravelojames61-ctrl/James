<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 Visitor Registration & Automatic Time-In
 */
$pageTitle = 'Register Visitor';
$activeNav = 'register';

require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();
$errorMessage = '';
$successRecord = null;
$facilityName = getSetting('facility_name', 'Aegis Corporate Center');

$nextBadge = generateNextBadgeNumber();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed. Please refresh and try again.';
    } else {
        $fullName         = clean($_POST['full_name'] ?? '');
        $contactNumber    = clean($_POST['contact_number'] ?? '');
        $email            = clean($_POST['email'] ?? '');
        $companyOrAddress = clean($_POST['company_or_address'] ?? '');
        $idType           = clean($_POST['id_type'] ?? '');
        $idNumber         = clean($_POST['id_number'] ?? '');
        $purposeOfVisit   = clean($_POST['purpose_of_visit'] ?? '');
        $personToVisit    = clean($_POST['person_to_visit'] ?? '');
        $deptToVisit      = clean($_POST['department_to_visit'] ?? '');
        $remarks          = clean($_POST['remarks'] ?? '');

        if (empty($fullName) || empty($contactNumber) || empty($purposeOfVisit) || empty($personToVisit)) {
            $errorMessage = 'Please complete all required fields (Full Name, Contact Number, Purpose of Visit, and Person to Visit).';
        } else {
            try {
                $badgeToUse = generateNextBadgeNumber();

                $stmt = $pdo->prepare("
                    INSERT INTO visitor_logs 
                    (badge_number, full_name, contact_number, email, company_or_address, id_type, id_number, purpose_of_visit, person_to_visit, department_to_visit, time_in, status, remarks, registered_by)
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Inside', ?, ?)
                ");
                
                $stmt->execute([
                    $badgeToUse,
                    $fullName,
                    $contactNumber,
                    $email,
                    $companyOrAddress,
                    $idType,
                    $idNumber,
                    $purposeOfVisit,
                    $personToVisit,
                    $deptToVisit,
                    $remarks,
                    getUserId()
                ]);

                $newId = $pdo->lastInsertId();

                $fetchStmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE id = ?");
                $fetchStmt->execute([$newId]);
                $successRecord = $fetchStmt->fetch();

                setFlash('success', "Visitor {$fullName} successfully registered! Badge #{$badgeToUse} issued. Time-in automatically recorded.");
            } catch (Exception $e) {
                $errorMessage = 'Error saving visitor record: ' . $e->getMessage();
            }
        }
    }
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
                    <i class="bi bi-person-plus-fill text-primary me-2"></i> Visitor Registration
                </h3>
                <div class="text-muted small mt-1">Record visitor credentials, generate badge pass, and record arrival time.</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Register Visitor</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- App Content -->
<div class="app-content">
    <div class="container-fluid">
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($errorMessage) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($successRecord): ?>
            <!-- Success Confirmation Card -->
            <div class="card card-success card-outline shadow-sm mb-4">
                <div class="card-header bg-success-subtle">
                    <h3 class="card-title text-success fw-bold">
                        <i class="bi bi-check-circle-fill me-2"></i> Visitor Registered & Time-In Recorded!
                    </h3>
                    <div class="card-tools">
                        <a href="visitor_pass.php?id=<?= $successRecord['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="bi bi-printer me-1"></i> Print Visitor Badge
                        </a>
                        <a href="register_visitor.php" class="btn btn-outline-secondary btn-sm">Register Another</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-7">
                            <h4 class="mb-1 fw-bold"><?= htmlspecialchars($successRecord['full_name']) ?></h4>
                            <p class="text-muted mb-0">
                                Host: <strong><?= htmlspecialchars($successRecord['person_to_visit']) ?></strong> (<?= htmlspecialchars($successRecord['department_to_visit'] ?: 'General') ?>)
                                • Purpose: <span class="badge text-bg-secondary"><?= htmlspecialchars($successRecord['purpose_of_visit']) ?></span>
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <span class="badge text-bg-primary fs-5 font-monospace px-3 py-2 me-2">
                                <?= htmlspecialchars($successRecord['badge_number']) ?>
                            </span>
                            <div class="small text-success fw-bold mt-1">
                                <i class="bi bi-clock me-1"></i> Time-In: <?= formatDateTime($successRecord['time_in']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2-Column Responsive Layout: Entry Form + Live Dynamic Pass Preview -->
        <div class="row g-4">
            <!-- Left Column: Registration Form -->
            <div class="col-lg-8">
                <div class="card card-primary card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-semibold">
                            <i class="bi bi-card-text text-primary me-2"></i> New Visitor Entry Form
                        </h3>
                        <div class="card-tools">
                            <span class="badge text-bg-primary font-monospace fs-6">
                                Next Badge: <?= htmlspecialchars($nextBadge) ?>
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="register_visitor.php" id="visitorRegForm">
                        <?= csrf_field() ?>
                        <div class="card-body p-4">
                            <!-- Section 1 -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 fw-bold small text-uppercase">
                                <i class="bi bi-person-lines-fill me-1"></i> 1. Personal Information
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="full_name">
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. Jessica Parker" required autofocus>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="contact_number">
                                        Contact Phone Number <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="tel" name="contact_number" id="contact_number" class="form-control" placeholder="e.g. +1 (555) 456-7890" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="email">Email Address (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" id="email" class="form-control" placeholder="e.g. jessica.p@example.com">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="company_or_address">Company / Organization / Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                                        <input type="text" name="company_or_address" id="company_or_address" class="form-control" placeholder="e.g. Apex Financial Corp">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2 -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 fw-bold small text-uppercase">
                                <i class="bi bi-shield-check me-1"></i> 2. Identification Details
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="id_type">ID Type Presented</label>
                                    <select name="id_type" id="id_type" class="form-select">
                                        <option value="">-- Select Presented ID --</option>
                                        <?php foreach (getIdTypeOptions() as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="id_number">ID / License Number</label>
                                    <input type="text" name="id_number" id="id_number" class="form-control" placeholder="e.g. DL-9921482">
                                </div>
                            </div>

                            <!-- Section 3 -->
                            <h5 class="text-primary border-bottom pb-2 mb-3 fw-bold small text-uppercase">
                                <i class="bi bi-geo-alt-fill me-1"></i> 3. Visit Purpose & Destination
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="purpose_of_visit">
                                        Purpose of Visit <span class="text-danger">*</span>
                                    </label>
                                    <select name="purpose_of_visit" id="purpose_of_visit" class="form-select" required>
                                        <option value="">-- Select Purpose --</option>
                                        <?php foreach (getPurposeOptions() as $p): ?>
                                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="person_to_visit">
                                        Person to Visit (Host) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                        <input type="text" name="person_to_visit" id="person_to_visit" class="form-control" placeholder="e.g. Sarah Jenkins" autocomplete="off" required>
                                    </div>
                                    <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-magic me-1"></i>Type for live staff suggestions</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="department_to_visit">Department / Floor</label>
                                    <select name="department_to_visit" id="department_to_visit" class="form-select">
                                        <option value="">-- Select Department --</option>
                                        <?php foreach (getDepartmentOptions() as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold" for="remarks">Special Instructions / Remarks (Optional)</label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="e.g. Escort required, visitor parking stall #12, bringing equipment"></textarea>
                                </div>
                            </div>

                            <!-- Auto Time-In Alert -->
                            <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0 py-2">
                                <span class="live-dot"></span>
                                <span class="small text-muted">
                                    Arrival time (Time-In) will automatically be stamped at submission: <strong><?= date('M d, Y h:i A') ?></strong>
                                </span>
                            </div>
                        </div>

                        <div class="card-footer bg-body-tertiary d-flex justify-content-end gap-2 p-3">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i> Register Visitor & Record Time-In
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Real-Time Live Badge Pass Preview -->
            <div class="col-lg-4">
                <div class="card card-outline card-success shadow-sm sticky-top" style="top: 1rem;">
                    <div class="card-header bg-success-subtle py-2 d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-success text-uppercase">
                            <i class="bi bi-upc-scan me-1"></i> Live Pass Preview
                        </span>
                        <span class="badge text-bg-success d-flex align-items-center gap-1">
                            <span class="live-dot" style="width:6px; height:6px;"></span> Real-Time
                        </span>
                    </div>

                    <div class="card-body p-3">
                        <div class="border rounded-3 p-3 bg-body shadow-sm text-center">
                            <div class="text-uppercase small fw-bold text-muted mb-1" style="font-size:0.75rem; letter-spacing:0.05em;">
                                <?= htmlspecialchars($facilityName) ?>
                            </div>
                            <div class="badge text-bg-primary fs-6 font-monospace mb-2" id="previewBadge">
                                <?= htmlspecialchars($nextBadge) ?>
                            </div>
                            <h4 class="fw-bold mb-1 text-primary" id="previewName">Visitor Name</h4>
                            <div class="text-secondary small mb-3 text-truncate" id="previewCompany">Company / Organization</div>

                            <div class="border-top border-bottom py-2 my-2 text-start small">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size:0.7rem;">HOST:</span>
                                        <strong id="previewHost" class="text-truncate d-block">—</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size:0.7rem;">DEPARTMENT:</span>
                                        <strong id="previewDept" class="text-truncate d-block">—</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size:0.7rem;">PURPOSE:</span>
                                        <span class="badge text-bg-secondary" id="previewPurpose">General</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size:0.7rem;">ARRIVAL:</span>
                                        <span class="text-success fw-bold font-monospace"><?= date('h:i A') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Barcode Representation -->
                            <div class="bg-body-tertiary p-2 rounded border mt-3 font-monospace small">
                                <div style="letter-spacing: 3px; font-weight: 900; font-size: 1.1rem; color: var(--bs-body-color);">
                                    ||| | |||| | ||| || ||| | ||
                                </div>
                                <div class="small fw-bold text-muted"><?= htmlspecialchars($nextBadge) ?></div>
                            </div>
                        </div>

                        <div class="mt-3 text-center small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Preview updates dynamically as credentials are typed.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nameIn = document.getElementById('full_name');
            const compIn = document.getElementById('company_or_address');
            const hostIn = document.getElementById('person_to_visit');
            const deptIn = document.getElementById('department_to_visit');
            const purpIn = document.getElementById('purpose_of_visit');

            const prevName = document.getElementById('previewName');
            const prevComp = document.getElementById('previewCompany');
            const prevHost = document.getElementById('previewHost');
            const prevDept = document.getElementById('previewDept');
            const prevPurp = document.getElementById('previewPurpose');

            function syncPreview() {
                if (prevName) prevName.textContent = nameIn.value.trim() || 'Visitor Name';
                if (prevComp) prevComp.textContent = compIn.value.trim() || 'Company / Organization';
                if (prevHost) prevHost.textContent = hostIn.value.trim() || '—';
                if (prevDept) prevDept.textContent = deptIn.value ? deptIn.options[deptIn.selectedIndex].text : '—';
                if (prevPurp) prevPurp.textContent = purpIn.value || 'General';
            }

            [nameIn, compIn, hostIn].forEach(el => {
                if (el) el.addEventListener('input', syncPreview);
            });
            [deptIn, purpIn].forEach(el => {
                if (el) el.addEventListener('change', syncPreview);
            });
        });
        </script>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
