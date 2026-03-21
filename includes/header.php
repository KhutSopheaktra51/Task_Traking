<?php
// includes/header.php
// Usage: include with $pageTitle set before including
$pageTitle = $pageTitle ?? 'TaskTrack';
$user = auth();
$flashOk = getFlash('success');
$flashErr = getFlash('error');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — TaskTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="flex h-full min-h-screen">

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col
                  transform transition-transform duration-200 lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex h-16 items-center px-6 border-b border-gray-200 flex-shrink-0">
                <span class="text-xl font-semibold text-indigo-600">TaskTrack</span>
            </div>
            <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
                <?php
                $navItems = [
                    ['/tasktrack/dashboard/index.php', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['/tasktrack/tasks/index.php', 'Tasks', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ['/tasktrack/attendance/index.php', 'Attendance', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['/tasktrack/leave/index.php', 'Leave', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
                $current = $_SERVER['PHP_SELF'];
                foreach ($navItems as [$href, $label, $icon]):
                    $active = str_contains($current, explode('/', $href)[3]);
                    ?>
                    <a href="<?= e($href) ?>"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      <?= $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= e($icon) ?>" />
                        </svg>
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>

                <?php if (isMgrOrAdmin()): ?>
                    <div class="pt-4 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management
                    </div>
                    <a href="/tasktrack/attendance/admin.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        All Attendance
                    </a>
                    <a href="/tasktrack/leave/manage.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Leave Requests
                    </a>
                    <a href="/tasktrack/reports/index.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Reports
                    </a>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <a href="/tasktrack/users/index.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Users
                    </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex-1"></div>
                <div class="flex items-center gap-4">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 text-sm text-gray-700">
                            <div
                                class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs">
                                <?= e(initials($user['name'])) ?>
                            </div>
                            <span class="hidden sm:block"><?= e($user['name']) ?></span>
                        </button>
                        <div x-show="open" @click.outside="open = false"
                            class="absolute right-0 mt-2 w-44 bg-white rounded-xl border border-gray-200 shadow-lg py-1 z-50">
                            <div class="px-4 py-2 text-xs text-gray-400 border-b"><?= e($user['email']) ?></div>
                            <a href="/tasktrack/auth/logout.php"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Sign out</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash messages -->
            <div class="px-6 pt-4 space-y-2">
                <?php if ($flashOk): ?>
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                        class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                        <?= e($flashOk) ?>
                    </div>
                <?php endif; ?>
                <?php if ($flashErr): ?>
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                        <?= e($flashErr) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Page content -->
            <main class="flex-1 overflow-auto p-6">