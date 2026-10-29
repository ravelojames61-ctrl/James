<?php
/**
 * Dynamic Stats & Live Visitor API Endpoint
 * Returns real-time KPI metrics and active visitor list in JSON format
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../config/helpers.php';

$pdo = getDB();
$stats = getVisitorStats();
$maxCapacity = (int)getSetting('max_visitor_capacity', '250');

// Fetch currently active inside visitors
$stmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE status = 'Inside' ORDER BY time_in DESC");
$stmt->execute();
$rawVisitors = $stmt->fetchAll();

$activeVisitors = [];
foreach ($rawVisitors as $v) {
    $activeVisitors[] = [
        'id'                  => (int)$v['id'],
        'badge_number'        => $v['badge_number'],
        'full_name'           => $v['full_name'],
        'contact_number'      => $v['contact_number'],
        'email'               => $v['email'] ?? '',
        'company_or_address'  => $v['company_or_address'] ?? 'Individual / Private',
        'id_type'             => $v['id_type'] ?? '',
        'id_number'           => $v['id_number'] ?? '',
        'purpose_of_visit'    => $v['purpose_of_visit'],
        'person_to_visit'     => $v['person_to_visit'],
        'department_to_visit' => $v['department_to_visit'] ?? 'Front Desk',
        'time_in_formatted'   => formatTimeOnly($v['time_in']),
        'date_in_formatted'   => formatDateOnly($v['time_in']),
        'time_in_full'        => formatDateTime($v['time_in']),
        'duration'            => calculateDuration($v['time_in']),
        'timed_in_confirmed'  => (int)($v['timed_in_confirmed'] ?? 0),
        'remarks'             => $v['remarks'] ?? '',
    ];
}

echo json_encode([
    'success'         => true,
    'stats'           => [
        'currently_inside'        => $stats['currently_inside'],
        'completed_today'         => $stats['completed_today'],
        'total_all_time'          => $stats['total_all_time'],
        'total_today'             => $stats['total_today'],
        'active_capacity_percent' => $stats['active_capacity_percent'],
        'max_capacity'            => $maxCapacity,
    ],
    'active_visitors' => $activeVisitors,
    'server_time'     => date('M d, Y h:i:s A'),
], JSON_UNESCAPED_UNICODE);
exit;
