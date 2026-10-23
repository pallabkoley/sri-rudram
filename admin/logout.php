<?php
require_once dirname(__DIR__) . '/config/config.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_regenerate_id(true);
flash_set('success', 'Logged out successfully.');
redirect('admin/login.php');