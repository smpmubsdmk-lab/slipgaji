<?php
require_once 'config/database.php';
logAktivitas($pdo, 'LOGOUT', 'Logout');
session_destroy();
header('Location: ' . BASE_URL . 'login.php');
exit;
?>