<?php $me = loggedInUser(); ?>

<!-- Sidebar -->
<aside class="w-64 bg-white border-r border-gray-200 flex flex-col flex-shrink-0">

    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <span class="text-xl font-bold text-indigo-600">TaskTrack</span>
    </div>

    <!-- Nav links -->
    <nav class="flex-1 p-3 space-y-1">
        <?php
        $nav = [
            ['page' => 'dashboard', 'label' => 'Dashboard'],
            ['page' => 'task/list', 'label' => 'Tasks'],
            ['page' => 'attendance/index', 'label' => 'Attendance'],
            ['page' => 'leave/index', 'label' => 'Leave'],
        ];
        $current = $_GET['page'] ?? 'dashboard';
        foreach ($nav as $item):
            $active = ($current === $item['page']);
            ?>
            <a href="./?page=<?= $item['page'] ?>" class="block px-3 py-2 rounded-lg text-sm font-medium
                  <?= $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' ?>">
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>

        <!-- Manager and Admin only -->
        <?php if (isManagerOrAdmin()): ?>
            <div class="pt-4 pb-1 px-3 text-xs font-bold text-gray-400 uppercase">
                Management
            </div>
            <a href="./?page=attendance/admin" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                All Attendance
            </a>
            <a href="./?page=leave/manage" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                Leave Requests
            </a>
            <a href="./?page=report/index" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                Reports
            </a>
        <?php endif; ?>

        <!-- Admin only -->
        <?php if (isAdmin()): ?>
            <div class="pt-4 pb-1 px-3 text-xs font-bold text-gray-400 uppercase">
                Admin
            </div>
            <a href="./?page=user/list" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                Users
            </a>
        <?php endif; ?>
    </nav>

</aside>

<!-- Main content area -->
<div class="flex-1 flex flex-col min-w-0">

    <!-- Top bar with user profile and logout on right -->
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">

        <!-- Page title -->
        <h1 class="text-lg font-semibold text-gray-800">
            <?= e($page_title ?? 'TaskTrack') ?>
        </h1>

        <!-- Right side — user + logout -->
        <div class="flex items-center gap-4">

            <!-- Avatar + name + role -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center
                            text-indigo-700 text-xs font-bold">
                    <?= e(getInitials($me['name'])) ?>
                </div>
                <div class="hidden sm:block">
                    <p class="text-sm font-medium text-gray-800"><?= e($me['name']) ?></p>
                    <p class="text-xs text-gray-400"><?= ucfirst(e($me['role'])) ?></p>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-px h-6 bg-gray-200"></div>

            <!-- Logout -->
            <a href="./?page=logout" class="flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700
                      font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </a>
        </div>
    </header>

    <!-- Flash messages -->
    <?php if (getFlash('success')): ?>
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
            <?= e(getFlash('success')) ?>
        </div>
    <?php endif; ?>

    <?php if (getFlash('error')): ?>
        <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
            <?= e(getFlash('error')) ?>
        </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="flex-1 p-6">