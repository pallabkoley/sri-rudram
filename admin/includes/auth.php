<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

if (empty($_SESSION['admin_id'])) {
    flash_set('error', 'Please login to access admin panel.');
    redirect('admin/login.php');
}

if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}