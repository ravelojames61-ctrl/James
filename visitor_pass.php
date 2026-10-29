<?php
/**
 * Visitor Log Management System (VLMS)
 * Printable Visitor Pass & Security Badge
 */
require_once __DIR__ . '/config/auth.php';
requireLogin();
require_once __DIR__ . '/config/helpers.php';

$pdo = getDB();
$visitorId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE id = ?");
$stmt->execute([$visitorId]);
$visitor = $stmt->fetch();

if (!$visitor) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Visitor pass record not found.</h2><a href='dashboard.php'>Return to Dashboard</a></div>");
}

$facilityName = getSetting('facility_name', 'Aegis Corporate Center');
$facilityAddress = getSetting('facility_address', 'Innovation District');
$autoPrint = isset($_GET['auto_print']) && $_GET['auto_print'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Pass — <?= htmlspecialchars($visitor['badge_number']) ?> (<?= htmlspecialchars($visitor['full_name']) ?>)</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/print.css" media="print">
    <style>
        body {
            background: #0b0f19;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }
        .pass-controls {
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
        }
        .printable-pass-container {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            color: #0f172a;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 25px rgba(6, 182, 212, 0.3);
            border: 2px solid #06b6d4;
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
            position: relative;
        }
        .pass-header {
            background: linear-gradient(135deg, #0a0d14 0%, #161d2f 100%);
            color: #ffffff;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 4px solid #06b6d4;
        }
        .pass-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pass-brand img {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(6, 182, 212, 0.4);
        }
        .pass-badge-type {
            font-family: 'JetBrains Mono', monospace;
            background: #06b6d4;
            color: #000;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }
        .pass-body {
            padding: 24px;
        }
        .pass-visitor-title {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .pass-visitor-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .pass-visitor-company {
            font-size: 0.95rem;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }
        .pass-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .pass-info-item label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .pass-info-item span {
            font-weight: 600;
            color: #0f172a;
        }
        .pass-footer {
            background: #f8fafc;
            border-top: 2px dashed #cbd5e1;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pass-barcode-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .barcode-lines {
            height: 32px;
            width: 160px;
            background: repeating-linear-gradient(
                90deg,
                #000,
                #000 2px,
                #fff 2px,
                #fff 4px,
                #000 4px,
                #000 7px,
                #fff 7px,
                #fff 8px,
                #000 8px,
                #000 12px,
                #fff 12px,
                #fff 14px
            );
        }
        .pass-security-notes {
            font-size: 0.7rem;
            color: #64748b;
            line-height: 1.3;
            max-width: 200px;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="pass-controls no-print">
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Print Visitor Pass</span>
    </button>
    <a href="current_visitors.php" class="btn btn-secondary">Current Visitors</a>
    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
</div>

<div class="printable-pass-container">
    <div class="pass-header">
        <div class="pass-brand">
            <img src="assets/images/logo.jpg" alt="Aegis VLMS">
            <div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.1rem; font-weight:700; line-height:1.2;"><?= htmlspecialchars($facilityName) ?></div>
                <div style="font-size:0.7rem; opacity:0.8;"><?= htmlspecialchars($facilityAddress) ?></div>
            </div>
        </div>
        <div class="pass-badge-type">
            VISITOR
        </div>
    </div>

    <div class="pass-body">
        <div class="pass-visitor-title">
            <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:#06b6d4; font-weight:800; margin-bottom:4px;">Official Visitor Pass</div>
            <div class="pass-visitor-name"><?= htmlspecialchars($visitor['full_name']) ?></div>
            <div class="pass-visitor-company"><?= htmlspecialchars($visitor['company_or_address'] ?: 'Individual / Private Visitor') ?></div>
        </div>

        <div class="pass-info-grid">
            <div class="pass-info-item">
                <label>Badge Number</label>
                <span style="font-family:'JetBrains Mono',monospace; color:#06b6d4; font-size:1.05rem;"><?= htmlspecialchars($visitor['badge_number']) ?></span>
            </div>

            <div class="pass-info-item">
                <label>Arrival Time (Time-In)</label>
                <span style="color:#059669;"><?= formatTimeOnly($visitor['time_in']) ?> (<?= formatDateOnly($visitor['time_in']) ?>)</span>
            </div>

            <div class="pass-info-item">
                <label>Person to Visit (Host)</label>
                <span><?= htmlspecialchars($visitor['person_to_visit']) ?></span>
            </div>

            <div class="pass-info-item">
                <label>Department / Floor</label>
                <span><?= htmlspecialchars($visitor['department_to_visit'] ?: 'Front Desk') ?></span>
            </div>

            <div class="pass-info-item">
                <label>Purpose of Visit</label>
                <span><?= htmlspecialchars($visitor['purpose_of_visit']) ?></span>
            </div>

            <div class="pass-info-item">
                <label>Contact Phone</label>
                <span><?= htmlspecialchars($visitor['contact_number']) ?></span>
            </div>
        </div>
    </div>

    <div class="pass-footer">
        <div class="pass-barcode-box">
            <div class="barcode-lines"></div>
            <span style="font-family:'JetBrains Mono',monospace; font-size:0.75rem; font-weight:700; letter-spacing:0.1em;">
                *<?= htmlspecialchars($visitor['badge_number']) ?>*
            </span>
        </div>
        <div class="pass-security-notes">
            <strong>SECURITY NOTICE:</strong><br>
            Badge must be visibly displayed at all times. Return badge to Front Desk upon facility exit.
        </div>
    </div>
</div>

<?php if ($autoPrint): ?>
<script>
    window.addEventListener('load', () => {
        window.print();
    });
</script>
<?php endif; ?>

</body>
</html>
