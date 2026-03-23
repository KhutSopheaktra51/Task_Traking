<?php
$page_title = 'Dashboard';
$me = loggedInUser();
$today = date('Y-m-d');

// ---- KPI counts ----
$tasks_due_today = (int) getRow(
    'SELECT COUNT(*) as total FROM tasks
     WHERE due_date = ? AND status != "done"' .
    (isManagerOrAdmin() ? '' : ' AND assignee_id = ' . (int) $me['id']),
    [$today]
)['total'];

$open_tasks = (int) getRow(
    'SELECT COUNT(*) as total FROM tasks WHERE status != "done"' .
    (isManagerOrAdmin() ? '' : ' AND assignee_id = ' . (int) $me['id'])
)['total'];

$pending_leaves = (int) getRow(
    'SELECT COUNT(*) as total FROM leave_requests WHERE status = "pending"' .
    (isManagerOrAdmin() ? '' : ' AND user_id = ' . (int) $me['id'])
)['total'];

// ---- Today attendance ----
$today_record = getTodayRecord($me['id']);

// ---- Status counts ----
$statuses = ['todo', 'in_progress', 'in_review', 'blocked', 'done'];
$status_counts = [];
foreach ($statuses as $s) {
    $row = getRow(
        'SELECT COUNT(*) as total FROM tasks WHERE status = ?' .
        (isManagerOrAdmin() ? '' : ' AND assignee_id = ' . (int) $me['id']),
        [$s]
    );
    $status_counts[$s] = (int) $row['total'];
}

// ---- Recent tasks ----
$sql = 'SELECT t.*, u.name AS assignee_name, p.name AS project_name
        FROM tasks t
        LEFT JOIN users u ON t.assignee_id = u.id
        LEFT JOIN projects p ON t.project_id = p.id';

if (!isManagerOrAdmin()) {
    $sql .= ' WHERE t.assignee_id = ' . (int) $me['id'];
}
$sql .= ' ORDER BY t.created_at DESC LIMIT 8';
$recent_tasks = getRows($sql);
?>

<!-- Heading -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    <p class="text-sm text-gray-500 mt-1">
        <?= date('l, F j, Y') ?>
    </p>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase font-medium">Tasks due today</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
            <?= $tasks_due_today ?>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase font-medium">Open tasks</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
            <?= $open_tasks ?>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase font-medium">Pending leaves</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">
            <?= $pending_leaves ?>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase font-medium">Today attendance</p>
        <?php if ($today_record): ?>
            <p class="text-2xl font-bold text-green-600 mt-1">
                <?= $today_record['check_out'] ? 'Completed' : 'Checked in' ?>
            </p>
            <p class="text-xs text-gray-400 mt-1">
                In:
                <?= e(substr($today_record['check_in'] ?? '', 0, 5)) ?>
                <?php if ($today_record['check_out']): ?>
                    · Out:
                    <?= e(substr($today_record['check_out'], 0, 5)) ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="text-2xl font-bold text-red-500 mt-1">Not checked in</p>
            <a href="./?page=attendance/index" class="text-xs text-indigo-600 hover:underline">
                Check in →
            </a>
        <?php endif; ?>
    </div>

</div>

<!-- Status breakdown -->
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-8">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Tasks by status</h2>
    <div class="grid grid-cols-5 gap-3">
        <?php
        $colors = [
            'todo' => 'bg-gray-100 text-gray-700',
            'in_progress' => 'bg-blue-50 text-blue-700',
            'in_review' => 'bg-yellow-50 text-yellow-700',
            'blocked' => 'bg-red-50 text-red-700',
            'done' => 'bg-green-50 text-green-700',
        ];
        foreach ($status_counts as $status => $count):
            ?>
            <div class="rounded-lg <?= $colors[$status] ?> p-3 text-center">
                <p class="text-2xl font-bold">
                    <?= $count ?>
                </p>
                <p class="text-xs mt-1">
                    <?= statusLabel($status) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Quick actions -->
<div class="flex gap-3 mb-8">
    <a href="./?page=task/create"
        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        + New task
    </a>
    <a href="./?page=attendance/index"
        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg">
        Attendance
    </a>
    <a href="./?page=leave/index"
        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg">
        Request leave
    </a>
</div>

<!-- Recent tasks -->
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-sm font-semibold text-gray-700">Recent tasks</h2>
        <a href="./?page=task/list" class="text-xs text-indigo-600 hover:underline">View all →</a>
    </div>
    <div class="divide-y divide-gray-50">
        <?php if (empty($recent_tasks)): ?>
            <p class="px-6 py-8 text-sm text-gray-400 text-center">No tasks yet.</p>
        <?php endif; ?>
        <?php foreach ($recent_tasks as $task): ?>
            <a href="./?page=task/show&id=<?= $task['id'] ?>"
                class="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-xs font-medium px-2 py-0.5 rounded <?= priorityBadge($task['priority']) ?>">
                        <?= ucfirst($task['priority']) ?>
                    </span>
                    <span class="text-sm text-gray-800 truncate">
                        <?= e($task['title']) ?>
                    </span>
                </div>
                <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                    <span class="text-xs px-2 py-0.5 rounded-full <?= statusBadge($task['status']) ?>">
                        <?= statusLabel($task['status']) ?>
                    </span>
                    <?php if ($task['due_date']): ?>
                        <?php $overdue = strtotime($task['due_date']) < time() && $task['status'] !== 'done'; ?>
                        <span class="text-xs <?= $overdue ? 'text-red-500 font-medium' : 'text-gray-400' ?>">
                            <?= date('M j', strtotime($task['due_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div> 