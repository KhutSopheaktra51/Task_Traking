<?php
ob_start();

require_once __DIR__ . '/init/db.init.php';
require_once __DIR__ . '/init/init.php';
require_once __DIR__ . '/init/func/user.func.php';
require_once __DIR__ . '/init/func/task.func.php';
require_once __DIR__ . '/init/func/attendance.func.php';
require_once __DIR__ . '/init/func/leave.func.php';

$user = loggedInUser();
$isAdmin = isAdmin();

$all_pages = [
    'login',
    'register',
    'logout',
    'dashboard',
    'task/list',
    'task/create',
    'task/show',
    'task/edit',
    'task/delete',
    'task/update_status',
    'attendance/index',
    'attendance/history',
    'attendance/admin',
    'leave/index',
    'leave/manage',
    'leave/action',
    'report/index',
    'user/list',
];

$need_login = [
    'dashboard',
    'task/list',
    'task/create',
    'task/show',
    'task/edit',
    'task/delete',
    'task/update_status',
    'attendance/index',
    'attendance/history',
    'attendance/admin',
    'leave/index',
    'leave/manage',
    'leave/action',
    'report/index',
    'user/list',
];

$guest_only = ['login', 'register'];
$admin_only = ['user/list'];
$manager_only = ['attendance/admin', 'leave/manage', 'leave/action', 'report/index'];

$page = $_GET['page'] ?? 'dashboard';

// ---- Redirect logged in user away from login/register ----
if (in_array($page, $guest_only) && !empty($user)) {
    redirect('./?page=dashboard');
}

// ---- Redirect if not logged in ----
if (in_array($page, $need_login) && empty($user)) {
    redirect('./?page=login');
}

// ---- Block non-admin ----
if (in_array($page, $admin_only) && !$isAdmin) {
    redirect('./?page=dashboard');
}

// ---- Block staff from manager pages ----
if (in_array($page, $manager_only) && !isManagerOrAdmin()) {
    redirect('./?page=dashboard');
}
if (in_array($page, $guest_only)) {
    include __DIR__ . '/include/auth.inc.php';
    exit;
}

// ====================================================
// LOGGED IN PAGES — with sidebar layout
// ====================================================
include __DIR__ . '/include/header.inc.php';
include __DIR__ . '/include/navbar.inc.php';

if (in_array($page, $all_pages)) {
    include __DIR__ . '/page/' . $page . '.php';
} else {
    redirect('./?page=dashboard');
}

include __DIR__ . '/include/footer.inc.php';