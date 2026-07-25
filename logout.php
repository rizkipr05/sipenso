<?php
require_once __DIR__ . '/config/helpers.php';

if (isset($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'Logout', 'Berhasil keluar dari sistem.');
}

session_unset();
session_destroy();
session_start();

set_flash('success', 'Anda telah berhasil keluar dari akun.');
header('Location: ' . base_url('login.php'));
exit;
