<?php
/**
 * Visitor Log Management System (VLMS)
 * Core Helper Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize input data
 */
function clean(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash messaging system
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * System Settings Getter/Setter
 */
function getSetting(string $key, string $default = ''): string {
    static $settingsCache = null;
    $pdo = getDB();
    if ($settingsCache === null) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    return $settingsCache[$key] ?? $default;
}

function updateSetting(string $key, string $value): bool {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate Next Unique Badge Number
 * Format: [PREFIX]-[YEAR]-[3-digit-padded-number] e.g. VIS-2026-018
 */
function generateNextBadgeNumber(): string {
    $pdo = getDB();
    $prefix = getSetting('badge_prefix', 'VIS');
    $year = date('Y');
    
    // Find highest badge sequence for this year
    $pattern = $prefix . '-' . $year . '-%';
    $stmt = $pdo->prepare("SELECT badge_number FROM visitor_logs WHERE badge_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$pattern]);
    $lastBadge = $stmt->fetchColumn();

    $seq = 1;
    if ($lastBadge) {
        $parts = explode('-', $lastBadge);
        if (isset($parts[2]) && is_numeric($parts[2])) {
            $seq = intval($parts[2]) + 1;
        }
    }
    return sprintf('%s-%s-%03d', $prefix, $year, $seq);
}

/**
 * Format datetime nicely
 */
function formatDateTime(?string $date, string $format = 'M d, Y h:i A'): string {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '—';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return clean($date);
    }
}

function formatDateOnly(?string $date): string {
    return formatDateTime($date, 'M d, Y');
}

function formatTimeOnly(?string $date): string {
    return formatDateTime($date, 'h:i A');
}

/**
 * Calculate visit duration in human readable format
 */
function calculateDuration(?string $timeIn, ?string $timeOut = null): string {
    if (empty($timeIn)) return '—';
    try {
        $start = new DateTime($timeIn);
        $end = $timeOut ? new DateTime($timeOut) : new DateTime();
        $diff = $start->diff($end);
        
        $parts = [];
        if ($diff->days > 0) {
            $parts[] = $diff->days . 'd';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . 'h';
        }
        $parts[] = $diff->i . 'm';
        
        return implode(' ', $parts);
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Get comprehensive visitor statistics
 */
function getVisitorStats(): array {
    $pdo = getDB();
    $stats = [
        'total_all_time'  => 0,
        'currently_inside'=> 0,
        'completed_today' => 0,
        'total_today'     => 0,
        'total_this_week' => 0,
        'total_this_month'=> 0,
        'active_capacity_percent' => 0,
    ];

    try {
        // Total All-Time
        $stats['total_all_time'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_logs")->fetchColumn();

        // Currently Inside
        $stats['currently_inside'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_logs WHERE status = 'Inside'")->fetchColumn();

        // Total registered today
        $stats['total_today'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_logs WHERE DATE(time_in) = CURDATE()")->fetchColumn();

        // Completed Today
        $stats['completed_today'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_logs WHERE status = 'Completed' AND DATE(time_out) = CURDATE()")->fetchColumn();

        // Total this week
        $stats['total_this_week'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_logs WHERE YEARWEEK(time_in, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();

        // Total this month
        $stats['total_this_month'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_logs WHERE YEAR(time_in) = YEAR(CURDATE()) AND MONTH(time_in) = MONTH(CURDATE())")->fetchColumn();

        // Capacity calculation
        $maxCap = (int)getSetting('max_visitor_capacity', '250');
        if ($maxCap > 0) {
            $stats['active_capacity_percent'] = min(100, round(($stats['currently_inside'] / $maxCap) * 100));
        }
    } catch (Exception $e) {
        // Fallback zeros
    }

    return $stats;
}

/**
 * Standard Purpose options
 */
function getPurposeOptions(): array {
    return [
        'Official Business',
        'Meeting',
        'Interview',
        'Contractor / Vendor',
        'Delivery / Courier',
        'Maintenance / Repair',
        'Personal Visit',
        'Media / Press',
        'Audit / Inspection',
        'Other'
    ];
}

/**
 * Standard ID Types
 */
function getIdTypeOptions(): array {
    return [
        'Driver\'s License',
        'Passport',
        'National ID',
        'Employee ID',
        'Student ID',
        'State Bar ID',
        'Military ID',
        'Other'
    ];
}

/**
 * Standard Departments
 */
function getDepartmentOptions(): array {
    return [
        'Executive Board',
        'Human Resources',
        'IT & Infrastructure',
        'Facilities & Operations',
        'Finance & Accounting',
        'Legal Department',
        'Corporate Communications',
        'Research & Development',
        'Sales & Marketing',
        'Security & Safety',
        'Front Desk / Receiving'
    ];
}
