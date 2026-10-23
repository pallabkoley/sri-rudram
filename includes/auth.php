<?php
// ============================================================
// DIVYA AASTHA - Authentication Helpers
// User login state checks
// ============================================================

if (!defined('DIVYA_AASTHA')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Check if a user is currently logged in.
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

/**
 * Require login — redirect if not logged in.
 * Use this at the top of any page that needs authentication.
 */
if (!function_exists('require_login')) {
    function require_login(string $redirect = 'login.php'): void
    {
        if (!is_logged_in()) {
            flash_set('error', 'Please login to continue.');
            redirect($redirect);
        }
    }
}

/**
 * Get current user ID (or null).
 */
if (!function_exists('current_user_id')) {
    function current_user_id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }
}

/**
 * Get current user's name from session.
 */
if (!function_exists('current_user_name')) {
    function current_user_name(): string
    {
        return $_SESSION['user_name'] ?? 'Devotee';
    }
}