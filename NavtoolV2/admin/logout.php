<?php
require_once __DIR__ . '/../includes/common.php';
$_SESSION=[]; session_destroy(); header('Location:/admin/login.php');
