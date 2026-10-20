<?php
require_once 'config/database.php';
header('Location: ' . BASE_URL . (isLogin() ? 'admin/index.php' : 'login.php'));
exit;
?>