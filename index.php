<?php
ob_start();

require_once __DIR__ . '/init/db.init.php';
require_once __DIR__ . '/init/init.php';
require_once __DIR__ . '/init/func/user.func.php';
require_once __DIR__ . '/init/func/task.func.php';
require_once __DIR__ . '/init/func/attendance.func.php';
require_once __DIR__ . '/init/func/leave.func.php';

$user    = loggedInUser();
$isAdmin = isAdmin();
$page    = $_GET['page'] ?? 'dashboard';

// ---- All available pages ----
$all_pages = [
    'login', 'logout',
    'dashboard',
    'task/list', 'task/create', 'task/show',
    'task/edit', 'task/delete', 'task/update_status',
    'attendance/index', 'attendance/history', 'attendance/admin',
    'leave/index', 'leave/manage', 'leave/action',
    'report/index',
    'user/list',
];

// ---- Pages that need login ----
$need_login = [
    'dashboard',
    'task/list', 'task/create', 'task/show',
    'task/edit', 'task/delete', 'task/update_status',
    'attendance/index', 'attendance/history', 'attendance/admin',
    'leave/index', 'leave/manage', 'leave/action',
    'report/index',
    'user/list',
];

// ---- Pages for guests only ----
$guest_only   = ['login'];

// ---- Pages for admin only ----
$admin_only   = ['user/list'];

// ---- Pages for manager and admin only ----
$manager_only = [
    'attendance/admin',
    'leave/manage', 'leave/action',
    'report/index',
];

// ---- Redirect logged in user away from login ----
if (in_array($page, $guest_only) && !empty($user)) {
    redirect('./?page=dashboard');
}

// ---- Redirect if not logged in ----
if (in_array($page, $need_login) && empty($user)) {
    redirect('./?page=login');
}

// ---- Block non admin ----
if (in_array($page, $admin_only) && !$isAdmin) {
    redirect('./?page=dashboard');
}

// ---- Block staff from manager pages ----
if (in_array($page, $manager_only) && !isManagerOrAdmin()) {
    redirect('./?page=dashboard');
}

// ---- Guest pages — centered layout ----
if (in_array($page, $guest_only)) {
    include __DIR__ . '/include/auth.inc.php';
    exit;
}

// ---- Logged in pages — sidebar layout ----
include __DIR__ . '/include/header.inc.php';
include __DIR__ . '/include/navbar.inc.php';

if (in_array($page, $all_pages)) {
    include __DIR__ . '/page/' . $page . '.php';
} else {
    redirect('./?page=dashboard');
}

include __DIR__ . '/include/footer.inc.php';