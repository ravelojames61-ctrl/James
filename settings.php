<?php
/**
 * Visitor Log Management System (VLMS)
 * AdminLTE 4.10.1 System Settings & Diagnostics (Admin Only)
 */
$pageTitle = 'System Settings';
$activeNav = 'settings';

require_once __DIR__ . '/config/auth.php';
requireRole('admin');
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed.';
    } else {
        $facilityName    = clean($_POST['facility_name'] ?? '');
        $facilityAddress = clean($_POST['facility_address'] ?? '');
        $contactPhone    = clean($_POST['contact_phone'] ?? '');
        $contactEmail    = clean($_POST['contact_email'] ?? '');
        $badgePrefix     = strtoupper(clean($_POST['badge_prefix'] ?? 'VIS'));
        $maxCapacity     = clean($_POST['max_visitor_capacity'] ?? '250');
        $operatingHours  = clean($_POST['operating_hours'] ?? '07:00 AM - 09:00 PM');
        $requireId       = isset($_POST['require_id']) ? '1' : '0';

        updateSetting('facility_name', $facilityName);
        updateSetting('facility_address', $facilityAddress);
        updateSetting('contact_phone', $contactPhone);
        updateSetting('contact_email', $contactEmail);
        updateSetting('badge_prefix', $badgePrefix);
        updateSetting('max_visitor_capacity', $maxCapacity);
        updateSetting('operating_hours', $operatingHours);
        updateSetting('require_id', $requireId);

        setFlash('success', 'System settings successfully updated.');
        header('Location: settings.php');
        exit;
    }
}

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

$facilityName    = getSetting('facility_name', 'Aegis Corporate Center & Technology Park');
$facilityAddress = getSetting('facility_address', 'Tower 4, Silicon Boulevard, Innovation District, CA 94025');
$contactPhone    = getSetting('contact_phone', '+1 (555) 019-2834');
$contactEmail    = getSetting('contact_email', 'security@aegiscenter.com');
$badgePrefix     = getSetting('badge_prefix', 'VIS');
$maxCapacity     = getSetting('max_visitor_capacity', '250');
$operatingHours  = getSetting('operating_hours', '07:00 AM - 09:00 PM');
$requireId       = getSetting('require_id', '1');

$dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
$dbSize = $pdo->query("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.TABLES WHERE table_schema = 'vlms_db'")->fetchColumn();

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
                    <i class="bi bi-gear-fill text-primary me-2"></i> System Settings & Configuration
                </h3>
                <div class="text-muted small mt-1">Configure facility metadata, badge generation prefixes, and capacity parameters.</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">System Settings</li>
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

        <?php if ($flashError || $errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($flashError ?: $errorMessage) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Settings Form Column -->
            <div class="col-lg-8">
                <div class="card card-primary card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">
                            <i class="bi bi-sliders text-primary me-2"></i> Facility Configuration Parameters
                        </h3>
                    </div>

                    <form method="POST" action="settings.php">
                        <?= csrf_field() ?>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold" for="facility_name">Facility / Organization Name <span class="text-danger">*</span></label>
                                    <input type="text" name="facility_name" id="facility_name" class="form-control" value="<?= htmlspecialchars($facilityName) ?>" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold" for="facility_address">Facility Address</label>
                                    <input type="text" name="facility_address" id="facility_address" class="form-control" value="<?= htmlspecialchars($facilityAddress) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="contact_phone">Front Desk / Security Contact Phone</label>
                                    <input type="text" name="contact_phone" id="contact_phone" class="form-control" value="<?= htmlspecialchars($contactPhone) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="contact_email">Security Inquiries Email</label>
                                    <input type="email" name="contact_email" id="contact_email" class="form-control" value="<?= htmlspecialchars($contactEmail) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="badge_prefix">
                                        Badge Prefix <span class="text-muted">(e.g. VIS, PASS)</span>
                                    </label>
                                    <input type="text" name="badge_prefix" id="badge_prefix" class="form-control" value="<?= htmlspecialchars($badgePrefix) ?>" maxlength="8" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="max_visitor_capacity">
                                        Max Concurrently Inside Visitors
                                    </label>
                                    <input type="number" name="max_visitor_capacity" id="max_visitor_capacity" class="form-control" value="<?= htmlspecialchars($maxCapacity) ?>" min="10" max="10000" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold" for="operating_hours">Operating Hours</label>
                                    <input type="text" name="operating_hours" id="operating_hours" class="form-control" value="<?= htmlspecialchars($operatingHours) ?>">
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="require_id" id="require_id" value="1" <?= ($requireId === '1') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="require_id">
                                            Mandate official government ID verification during visitor registration
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-body-tertiary d-flex justify-content-end p-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Diagnostics Column -->
            <div class="col-lg-4">
                <div class="card card-info card-outline shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">
                            <i class="bi bi-cpu text-info me-2"></i> Environment Diagnostics
                        </h3>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Template System</span>
                                <span class="badge text-bg-primary">AdminLTE 4.10.1</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">PHP Engine</span>
                                <span class="font-monospace text-body"><?= PHP_VERSION ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Database Engine</span>
                                <span class="font-monospace text-body"><?= htmlspecialchars(substr($dbVersion, 0, 15)) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Database Name</span>
                                <span class="font-monospace text-primary fw-bold">vlms_db</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Database Size</span>
                                <span class="text-body fw-semibold"><?= round((float)$dbSize, 2) ?> MB</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Server Time</span>
                                <span class="font-monospace text-success fw-bold"><?= date('H:i:s T') ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card card-outline card-secondary shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-2">Visitor Log Management System</h5>
                        <p class="small text-muted mb-0">
                            Fully compliant with <strong>Visitor_Log_Management_System.md</strong> specifications, styled in accordance with <strong>AdminLTE 4.10.1</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
