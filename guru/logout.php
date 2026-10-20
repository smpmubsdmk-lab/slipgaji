<?php
require_once '../config/database.php';
unset($_SESSION['guru_id'], $_SESSION['guru_nama']);
header('Location: index.php');
exit;
?>