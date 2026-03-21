<?php
// auth/logout.php
require_once __DIR__ . '/../includes/helpers.php';
session_destroy();
header('Location: /tasktrack/auth/login.php');
exit;