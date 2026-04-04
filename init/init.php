<?php

// ---- Timezone ----
date_default_timezone_set('Asia/Phnom_Penh');

// ---- Start session ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Clean output to prevent XSS ----
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ---- Redirect ----
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// ---- Save flash message ----
function setFlash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

// ---- Get and delete flash message ----
function getFlash($type)
{
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}

// ---- CSRF token ----
function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ---- CSRF hidden field ----
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

// ---- Check CSRF ----
function verifyCsrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        die('Security check failed.');
    }
}

// ---- Get initials from name ----
function getInitials($name)
{
    $parts = explode(' ', trim($name));
    $first = strtoupper(substr($parts[0], 0, 1));
    $last = strtoupper(substr(end($parts), 0, 1));
    return $first . $last;
}

// ---- Time ago ----
function timeAgo($datetime)
{
    $seconds = time() - strtotime($datetime);
    if ($seconds < 60)
        return 'just now';
    if ($seconds < 3600)
        return floor($seconds / 60) . 'm ago';
    if ($seconds < 86400)
        return floor($seconds / 3600) . 'h ago';
    return floor($seconds / 86400) . 'd ago';
}

// ---- Status label ----
function statusLabel($status)
{
    $labels = [
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'in_review' => 'In Review',
        'blocked' => 'Blocked',
        'done' => 'Done',
    ];
    return $labels[$status] ?? ucfirst($status);
}

// ---- Priority badge CSS ----
function priorityBadge($priority)
{
    $colors = [
        'low' => 'bg-green-100 text-green-700',
        'medium' => 'bg-blue-100 text-blue-700',
        'high' => 'bg-orange-100 text-orange-700',
        'urgent' => 'bg-red-100 text-red-700',
    ];
    return $colors[$priority] ?? 'bg-gray-100 text-gray-700';
}

// ---- Status badge CSS ----
function statusBadge($status)
{
    $colors = [
        'todo' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'in_review' => 'bg-yellow-100 text-yellow-700',
        'blocked' => 'bg-red-100 text-red-700',
        'done' => 'bg-green-100 text-green-700',
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-600';
}

// ---- Attendance badge CSS ----
function attendanceBadge($status)
{
    $colors = [
        'present' => 'bg-green-100 text-green-700',
        'late' => 'bg-yellow-100 text-yellow-700',
        'absent' => 'bg-red-100 text-red-700',
        'half_day' => 'bg-blue-100 text-blue-700',
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-600';
}

// ---- Leave badge CSS ----
function leaveBadge($status)
{
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-600';
}