<?php
/**
 * Visitor Log Management System (VLMS)
 * Authentication, Session & Role-Based Access Control (RBAC)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently authenticated
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user array
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'guest',
    ];
}

function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole(): string {
    return $_SESSION['role'] ?? '';
}

function getUserName(): string {
    return $_SESSION['full_name'] ?? 'User';
}

/**
 * Enforce authentication: redirects to login if unauthenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: login.php');
        exit;
    }
}

/**
 * Enforce role access
 * @param string|array $allowedRoles Array of allowed role strings
 */
function requireRole($allowedRoles): void {
    requireLogin();
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    $currentRole = getUserRole();
    if (!in_array($currentRole, $allowedRoles, true)) {
        $_SESSION['flash_error'] = 'Access denied: Your role (' . htmlspecialchars(getRoleBadgeLabel($currentRole)) . ') does not have permission to view that resource.';
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Check if the current user can perform an action
 */
function can(string $action): bool {
    if (!isLoggedIn()) {
        return false;
    }
    $role = getUserRole();
    switch ($action) {
        case 'manage_users':
        case 'manage_settings':
            return $role === 'admin';
        case 'generate_reports':
        case 'view_reports':
            return in_array($role, ['admin', 'receptionist'], true);
        case 'register_visitor':
            return in_array($role, ['admin', 'security', 'receptionist'], true);
        case 'checkout_visitor':
            return in_array($role, ['admin', 'security', 'receptionist'], true);
        case 'monitor_current':
            return in_array($role, ['admin', 'security', 'receptionist'], true);
        case 'search_history':
            return in_array($role, ['admin', 'security', 'receptionist'], true);
        case 'view_dashboard':
            return in_array($role, ['admin', 'security', 'receptionist'], true);
        default:
            return false;
    }
}

/**
 * Get readable badge label for roles
 */
function getRoleBadgeLabel(string $role): string {
    switch ($role) {
        case 'admin':
            return 'Administrator';
        case 'security':
            return 'Security Personnel';
        case 'receptionist':
            return 'Receptionist';
        default:
            return ucfirst($role);
    }
}

/**
 * CSRF Protection Helpers
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
