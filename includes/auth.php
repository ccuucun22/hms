<?php
/**
 * CarePoint HMS - Auth & Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/** Is a user currently logged in? */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/** Send the logged-in user to their correct dashboard. */
function redirect_by_role(string $role): void {
    $map = [
        'admin'        => '/admin/dashboard.php',
        'doctor'       => '/doctor/dashboard.php',
        'receptionist' => '/receptionist/dashboard.php',
        'accountant'   => '/accountant/dashboard.php',
    ];
    $base = base_url();
    header('Location: ' . $base . ($map[$role] ?? '/index.php'));
    exit;
}

/**
 * Guard a page so only the given role(s) can view it.
 * Call at the very top of every protected page.
 */
function require_role($allowed_roles): void {
    if (!is_logged_in()) {
        header('Location: ' . base_url() . '/index.php?expired=1');
        exit;
    }
    $allowed_roles = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        redirect_by_role($_SESSION['role']);
    }
}

/** Base URL helper so links work regardless of sub-folder install. */
function base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Strip the current sub-folder (admin/doctor/etc.) to get the app root.
    $parts = explode('/', trim($script, '/'));
    array_pop($parts); // remove filename
    if (!empty($parts) && in_array(end($parts), ['admin', 'doctor', 'receptionist', 'accountant'])) {
        array_pop($parts);
    }
    $root = '/' . implode('/', $parts);
    return $root === '/' ? '' : rtrim($root, '/');
}

/** Generate a unique Medical Record Number for a new patient. */
function generate_mrn(PDO $pdo): string {
    $stmt = $pdo->query("SELECT id FROM patients ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $next = $last ? ((int)$last + 1001) : 1001;
    return 'MRN-' . $next;
}

/** Escape output safely. */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Format currency for display (PKR). */
function money($amount): string {
    return 'Rs. ' . number_format((float)$amount, 2);
}

/** Initials for avatar chip. */
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $first = $parts[0][0] ?? '';
    $second = $parts[1][0] ?? '';
    return strtoupper($first . $second);
}
