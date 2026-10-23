<?php
// admin/includes/auth.php
// Include at the top of EVERY admin page.

require_once dirname(__DIR__, 2) . '/config/config.php';

if (empty($_SESSION['admin_id'])) {
    flash_set('error', 'Please login to access admin.');
    redirect('admin/login.php');
}

if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}