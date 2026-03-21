<?php
// index.php
require_once __DIR__ . '/includes/helpers.php';

if (auth()) {
    redirect('/tasktrack/dashboard/index.php');
} else {
    redirect('/tasktrack/auth/login.php');
}

// http://localhost/tasktrack/auth/login.php