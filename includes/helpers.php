<?php
// includes/helpers.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// ── Auth helpers ────────────────────────────────────────────

function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!auth()) {
        redirect('/auth/login.php');
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array(auth()['role'], $roles, true)) {
        http_response_code(403);
        die(renderError('403 — You do not have permission to view this page.'));
    }
}

function isAdmin(): bool
{
    return (auth()['role'] ?? '') === 'admin';
}
function isManager(): bool
{
    return (auth()['role'] ?? '') === 'manager';
}
function isMgrOrAdmin(): bool
{
    return in_array(auth()['role'] ?? '', ['admin', 'manager']);
}

// ── Redirect ────────────────────────────────────────────────

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ── Flash messages ───────────────────────────────────────────

function flash(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string
{
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ── CSRF ────────────────────────────────────────────────────

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        die('CSRF token mismatch. <a href="javascript:history.back()">Go back</a>');
    }
}

// ── HTML escaping ────────────────────────────────────────────

function e(mixed $val): string
{
    return htmlspecialchars((string) ($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Database shortcuts ───────────────────────────────────────

function dbRow(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function dbRows(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbRun(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) db()->lastInsertId() ?: $stmt->rowCount();
}

// ── Pagination ───────────────────────────────────────────────

function paginate(string $sql, array $params, int $perPage = 20): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS _count';
    $total = (int) db()->prepare($countSql)->execute($params) ? db()->prepare($countSql) : 0;
    $stmtC = db()->prepare($countSql);
    $stmtC->execute($params);
    $total = (int) $stmtC->fetchColumn();

    $stmt = db()->prepare($sql . " LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'pages' => (int) ceil($total / $perPage),
    ];
}

function paginationLinks(array $p, string $baseUrl = ''): string
{
    if ($p['pages'] <= 1)
        return '';
    $url = $baseUrl ?: strtok($_SERVER['REQUEST_URI'], '?');
    $q = $_GET;
    $html = '<div class="flex items-center gap-1 mt-4">';

    for ($i = 1; $i <= $p['pages']; $i++) {
        $q['page'] = $i;
        $active = $i === $p['page'];
        $html .= '<a href="' . e($url . '?' . http_build_query($q)) . '"
            class="px-3 py-1.5 text-sm rounded-lg ' .
            ($active ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50') .
            '">' . $i . '</a>';
    }

    return $html . '</div>';
}

// ── Misc ─────────────────────────────────────────────────────

function statusLabel(string $status): string
{
    return [
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'in_review' => 'In Review',
        'blocked' => 'Blocked',
        'done' => 'Done',
    ][$status] ?? ucfirst($status);
}

function priorityClass(string $priority): string
{
    return [
        'low' => 'text-green-700 bg-green-50',
        'medium' => 'text-blue-700 bg-blue-50',
        'high' => 'text-orange-700 bg-orange-50',
        'urgent' => 'text-red-700 bg-red-50',
    ][$priority] ?? 'text-gray-700 bg-gray-50';
}

function statusClass(string $status): string
{
    return [
        'todo' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'in_review' => 'bg-yellow-100 text-yellow-700',
        'blocked' => 'bg-red-100 text-red-700',
        'done' => 'bg-green-100 text-green-700',
    ][$status] ?? 'bg-gray-100 text-gray-600';
}

function renderError(string $msg): string
{
    return '<!DOCTYPE html><html><head><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="text-center"><p class="text-2xl font-semibold text-gray-700">' . e($msg) . '</p>
    <a href="/tasktrack/dashboard/index.php" class="mt-4 inline-block text-indigo-600 hover:underline text-sm">← Dashboard</a>
    </div></body></html>';
}

function initials(string $name): string
{
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)
        return 'just now';
    if ($diff < 3600)
        return floor($diff / 60) . 'm ago';
    if ($diff < 86400)
        return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}