<?php
/**
 * Dynamic Visitor Actions API Endpoint
 * Handles AJAX checkout, quick scan, visitor details, and host autocomplete
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/auth.php';
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}
require_once __DIR__ . '/../config/helpers.php';

$pdo = getDB();
$action = $_REQUEST['action'] ?? '';

// 1. Record Time-Out via AJAX
if ($action === 'record_timeout') {
    $visitorId = (int)($_POST['visitor_id'] ?? 0);
    $remarks   = clean($_POST['remarks'] ?? '');

    if ($visitorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid visitor ID provided.']);
        exit;
    }

    $checkStmt = $pdo->prepare("SELECT full_name, badge_number, status, remarks, time_in FROM visitor_logs WHERE id = ?");
    $checkStmt->execute([$visitorId]);
    $vis = $checkStmt->fetch();

    if (!$vis) {
        echo json_encode(['success' => false, 'message' => 'Visitor record not found.']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Exit: " . $remarks : "Time-Out recorded"));
    $updateStmt = $pdo->prepare("
        UPDATE visitor_logs 
        SET status = 'Completed', 
            time_out = ?, 
            checked_out_by = ?, 
            remarks = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$now, getUserId(), $newRemarks, $visitorId]);

    $stats = getVisitorStats();
    echo json_encode([
        'success'       => true,
        'message'       => "Departure recorded for {$vis['full_name']} ({$vis['badge_number']}).",
        'visitor_id'    => $visitorId,
        'badge_number'  => $vis['badge_number'],
        'full_name'     => $vis['full_name'],
        'status'        => 'Completed',
        'time_out'      => date('h:i A', strtotime($now)),
        'time_out_time' => formatTimeOnly($now),
        'time_out_date' => formatDateOnly($now),
        'duration'      => calculateDuration($vis['time_in'] ?? null, $now),
        'new_stats'     => [
            'currently_inside' => $stats['currently_inside'],
            'completed_today'  => $stats['completed_today'],
            'total_all_time'   => $stats['total_all_time'],
        ]
    ]);
    exit;
}

// 1b. Record Time-In via AJAX
if ($action === 'record_timein') {
    $visitorId = (int)($_POST['visitor_id'] ?? 0);
    $remarks   = clean($_POST['remarks'] ?? '');

    if ($visitorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid visitor ID provided.']);
        exit;
    }

    $checkStmt = $pdo->prepare("SELECT full_name, badge_number, status, remarks FROM visitor_logs WHERE id = ?");
    $checkStmt->execute([$visitorId]);
    $vis = $checkStmt->fetch();

    if (!$vis) {
        echo json_encode(['success' => false, 'message' => 'Visitor record not found.']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $newRemarks = trim(($vis['remarks'] ? $vis['remarks'] . " | " : "") . ($remarks ? "Time-In: " . $remarks : "Time-In recorded"));
    $updateStmt = $pdo->prepare("
        UPDATE visitor_logs 
        SET status = 'Inside', 
            time_in = ?, 
            timed_in_confirmed = 1, 
            time_out = NULL, 
            registered_by = ?, 
            remarks = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$now, getUserId(), $newRemarks, $visitorId]);

    $stats = getVisitorStats();
    echo json_encode([
        'success'            => true,
        'message'            => "Time-In recorded for {$vis['full_name']} ({$vis['badge_number']}).",
        'visitor_id'         => $visitorId,
        'badge_number'       => $vis['badge_number'],
        'full_name'          => $vis['full_name'],
        'status'             => 'Inside',
        'timed_in_confirmed' => 1,
        'time_in'            => date('h:i A', strtotime($now)),
        'time_in_time'       => formatTimeOnly($now),
        'time_in_date'       => formatDateOnly($now),
        'duration'      => 'Just now',
        'new_stats'     => [
            'currently_inside' => $stats['currently_inside'],
            'completed_today'  => $stats['completed_today'],
            'total_all_time'   => $stats['total_all_time'],
        ]
    ]);
    exit;
}

// 2. Quick Scan Checkout via AJAX
if ($action === 'quick_checkout') {
    $badgeQuery = trim($_POST['scan_badge'] ?? '');

    if (empty($badgeQuery)) {
        echo json_encode(['success' => false, 'message' => 'Please enter or scan a badge number.']);
        exit;
    }

    $findStmt = $pdo->prepare("SELECT id, full_name, badge_number, status, remarks FROM visitor_logs WHERE (badge_number = ? OR badge_number LIKE ?) AND status = 'Inside' LIMIT 1");
    $findStmt->execute([$badgeQuery, "%$badgeQuery%"]);
    $found = $findStmt->fetch();

    if (!$found) {
        echo json_encode(['success' => false, 'message' => "No active visitor found inside matching badge '{$badgeQuery}'."]);
        exit;
    }

    $newRemarks = trim(($found['remarks'] ? $found['remarks'] . " | " : "") . "Exit via Quick Scan");
    $upStmt = $pdo->prepare("UPDATE visitor_logs SET status = 'Completed', time_out = NOW(), checked_out_by = ?, remarks = ? WHERE id = ?");
    $upStmt->execute([getUserId(), $newRemarks, $found['id']]);

    $stats = getVisitorStats();
    echo json_encode([
        'success'      => true,
        'message'      => "Instant checkout completed for {$found['full_name']} ({$found['badge_number']}).",
        'visitor_id'   => (int)$found['id'],
        'badge_number' => $found['badge_number'],
        'full_name'    => $found['full_name'],
        'time_out'     => date('h:i A'),
        'new_stats'    => [
            'currently_inside' => $stats['currently_inside'],
            'completed_today'  => $stats['completed_today'],
            'total_all_time'   => $stats['total_all_time'],
        ]
    ]);
    exit;
}

// 3. Get Visitor Details
if ($action === 'get_visitor') {
    $visitorId = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE id = ?");
    $stmt->execute([$visitorId]);
    $v = $stmt->fetch();

    if (!$v) {
        echo json_encode(['success' => false, 'message' => 'Visitor not found.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'visitor' => [
            'id'                  => (int)$v['id'],
            'badge_number'        => $v['badge_number'],
            'full_name'           => $v['full_name'],
            'contact_number'      => $v['contact_number'],
            'email'               => $v['email'] ?? '',
            'company_or_address'  => $v['company_or_address'] ?? '',
            'id_type'             => $v['id_type'] ?? '',
            'id_number'           => $v['id_number'] ?? '',
            'purpose_of_visit'    => $v['purpose_of_visit'],
            'person_to_visit'     => $v['person_to_visit'],
            'department_to_visit' => $v['department_to_visit'] ?? '',
            'time_in'             => $v['time_in'] ?? '',
            'time_out'            => $v['time_out'] ?? '',
            'raw_time_in'         => !empty($v['time_in']) ? date('Y-m-d\TH:i', strtotime($v['time_in'])) : '',
            'raw_time_out'        => !empty($v['time_out']) ? date('Y-m-d\TH:i', strtotime($v['time_out'])) : '',
            'formatted_time_in'   => formatDateTime($v['time_in']),
            'formatted_time_out'  => formatDateTime($v['time_out']),
            'duration'            => calculateDuration($v['time_in'], $v['time_out']),
            'status'              => $v['status'],
            'remarks'             => $v['remarks'] ?? '',
        ]
    ]);
    exit;
}

// 4. Autocomplete Host Suggestions
if ($action === 'autocomplete_hosts') {
    $term = trim($_GET['term'] ?? '');
    $results = [];

    // Search existing hosts from visitor logs and user staff
    $stmt = $pdo->prepare("
        SELECT DISTINCT person_to_visit AS host_name, department_to_visit AS dept 
        FROM visitor_logs 
        WHERE person_to_visit LIKE ? 
        LIMIT 8
    ");
    $stmt->execute(["%$term%"]);
    $hosts = $stmt->fetchAll();

    foreach ($hosts as $h) {
        $results[] = [
            'host' => $h['host_name'],
            'dept' => $h['dept'] ?? 'General',
        ];
    }

    echo json_encode(['success' => true, 'suggestions' => $results]);
    exit;
}

// 5. Update Visitor Record via AJAX
if ($action === 'update_visitor') {
    $visitorId = (int)($_POST['visitor_id'] ?? 0);
    if ($visitorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid visitor ID provided.']);
        exit;
    }

    $fullName         = clean($_POST['full_name'] ?? '');
    $contactNumber    = clean($_POST['contact_number'] ?? '');
    $email            = clean($_POST['email'] ?? '');
    $companyOrAddress = clean($_POST['company_or_address'] ?? '');
    $idType           = clean($_POST['id_type'] ?? '');
    $idNumber         = clean($_POST['id_number'] ?? '');
    $purposeOfVisit   = clean($_POST['purpose_of_visit'] ?? '');
    $personToVisit    = clean($_POST['person_to_visit'] ?? '');
    $departmentToVisit= clean($_POST['department_to_visit'] ?? '');
    $status           = clean($_POST['status'] ?? 'Inside');
    $timeIn           = clean($_POST['time_in'] ?? '');
    $timeOut          = clean($_POST['time_out'] ?? '');
    $remarks          = clean($_POST['remarks'] ?? '');

    if (empty($fullName) || empty($contactNumber) || empty($purposeOfVisit) || empty($personToVisit)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (Name, Contact, Purpose, Host).']);
        exit;
    }

    $checkStmt = $pdo->prepare("SELECT id, badge_number FROM visitor_logs WHERE id = ?");
    $checkStmt->execute([$visitorId]);
    $existing = $checkStmt->fetch();
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Visitor record not found.']);
        exit;
    }

    $formattedTimeIn = !empty($timeIn) ? date('Y-m-d H:i:s', strtotime($timeIn)) : date('Y-m-d H:i:s');
    $formattedTimeOut = (!empty($timeOut) && $status === 'Completed') ? date('Y-m-d H:i:s', strtotime($timeOut)) : null;
    if ($status === 'Inside') {
        $formattedTimeOut = null;
    }

    try {
        $updateStmt = $pdo->prepare("
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

        $updateStmt->execute([
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

        $stats = getVisitorStats();

        echo json_encode([
            'success'      => true,
            'message'      => "Visitor {$fullName} ({$existing['badge_number']}) updated successfully.",
            'visitor'      => [
                'id'                  => $visitorId,
                'badge_number'        => $existing['badge_number'],
                'full_name'           => $fullName,
                'contact_number'      => $contactNumber,
                'email'               => $email,
                'company_or_address'  => $companyOrAddress,
                'id_type'             => $idType,
                'id_number'           => $idNumber,
                'purpose_of_visit'    => $purposeOfVisit,
                'person_to_visit'     => $personToVisit,
                'department_to_visit' => $departmentToVisit,
                'time_in'             => $formattedTimeIn,
                'time_out'            => $formattedTimeOut,
                'status'              => $status,
                'remarks'             => $remarks,
                'formatted_time_in'   => formatDateTime($formattedTimeIn),
                'formatted_time_out'  => formatDateTime($formattedTimeOut),
                'time_in_time'        => formatTimeOnly($formattedTimeIn),
                'time_in_date'        => formatDateOnly($formattedTimeIn),
                'time_out_time'       => formatTimeOnly($formattedTimeOut),
                'time_out_date'       => formatDateOnly($formattedTimeOut),
                'duration'            => calculateDuration($formattedTimeIn, $formattedTimeOut),
            ],
            'new_stats'    => [
                'currently_inside' => $stats['currently_inside'],
                'completed_today'  => $stats['completed_today'],
                'total_all_time'   => $stats['total_all_time'],
            ]
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// 6. Delete Visitor Record via AJAX
if ($action === 'delete_visitor') {
    $visitorId = (int)($_POST['visitor_id'] ?? 0);
    if ($visitorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid visitor ID provided.']);
        exit;
    }

    $checkStmt = $pdo->prepare("SELECT id, full_name, badge_number FROM visitor_logs WHERE id = ?");
    $checkStmt->execute([$visitorId]);
    $vis = $checkStmt->fetch();

    if (!$vis) {
        echo json_encode(['success' => false, 'message' => 'Visitor record not found or already deleted.']);
        exit;
    }

    try {
        $delStmt = $pdo->prepare("DELETE FROM visitor_logs WHERE id = ?");
        $delStmt->execute([$visitorId]);

        $stats = getVisitorStats();
        echo json_encode([
            'success'      => true,
            'message'      => "Visitor {$vis['full_name']} ({$vis['badge_number']}) was permanently deleted.",
            'visitor_id'   => $visitorId,
            'badge_number' => $vis['badge_number'],
            'full_name'    => $vis['full_name'],
            'new_stats'    => [
                'currently_inside' => $stats['currently_inside'],
                'completed_today'  => $stats['completed_today'],
                'total_all_time'   => $stats['total_all_time'],
            ]
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete visitor: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
exit;
