<?php
// dashboard/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$user = auth();
$today = date('Y-m-d');

$tasksDueToday = (int) dbRow(
    'SELECT COUNT(*) as c FROM tasks WHERE due_date = ? AND status != "done"' .
    (isMgrOrAdmin() ? '' : ' AND assignee_id = ?'),
    isMgrOrAdmin() ? [$today] : [$today, $user['id']]
)['c'];

$myOpenTasks = (int) dbRow(
    'SELECT COUNT(*) as c FROM tasks WHERE status != "done"' .
    (isMgrOrAdmin() ? '' : ' AND assignee_id = ?'),
    isMgrOrAdmin() ? [] : [$user['id']]
)['c'];

$pendingLeaves = (int) dbRow(
    'SELECT COUNT(*) as c FROM leave_requests WHERE status = "pending"' .
    (isMgrOrAdmin() ? '' : ' AND user_id = ?'),
    isMgrOrAdmin() ? [] : [$user['id']]
)['c'];

$todayAttendance = dbRow(
    'SELECT * FROM attendance_records WHERE user_id = ? AND date = ?',
    [$user['id'], $today]
);

$recentTasks = dbRows(
    'SELECT t.*, u.name AS assignee_name, p.name AS project_name
     FROM tasks t
     LEFT JOIN users u ON t.assignee_id = u.id
     LEFT JOIN projects p ON t.project_id = p.id' .
    (isMgrOrAdmin() ? '' : ' WHERE t.assignee_id = ' . (int) $user['id']) .
    ' ORDER BY t.created_at DESC LIMIT 8'
);

$statusCounts = [];
foreach (['todo', 'in_progress', 'in_review', 'blocked', 'done'] as $s) {
    $row = dbRow(
        'SELECT COUNT(*) as c FROM tasks WHERE status = ?' .
        (isMgrOrAdmin() ? '' : ' AND assignee_id = ?'),
        isMgrOrAdmin() ? [$s] : [$s, $user['id']]
    );
    $statusCounts[$s] = (int) $row['c'];
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    <p class="text-sm text-gray-500 mt-1"><?= date('l, F j, Y') ?></p>
</div>

<!-- KPI cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $kpis = [
        ['Tasks due today', $tasksDueToday, 'text-gray-900'],
        ['My open tasks', $myOpenTasks, 'text-gray-900'],
        ['Pending leaves', $pendingLeaves, 'text-gray-900'],
    ];
    foreach ($kpis as [$label, $val, $cls]):
        ?>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider"><?= e($label) ?></p>
            <p class="text-3xl font-semibold <?= $cls ?> mt-1"><?= $val ?></p>
        </div>
    <?php endforeach; ?>

    <!-- Attendance card -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Today's attendance</p>
        <?php if ($todayAttendance): ?>
            <p class="text-2xl font-semibold text-green-600 mt-1">
                <?= $todayAttendance['check_out'] ? 'Completed' : 'Checked in' ?>
            </p>
            <p class="text-xs text-gray-400 mt-1">
                In: <?= e($todayAttendance['check_in'] ?? '—') ?>
                <?php if ($todayAttendance['check_out']): ?>
                    · Out: <?= e($todayAttendance['check_out']) ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="text-2xl font-semibold text-red-500 mt-1">Not checked in</p>
            <a href="/tasktrack/attendance/index.php" class="text-xs text-indigo-600 hover:underline mt-1 block">Check in
                →</a>
        <?php endif; ?>
    </div>
</div>

<!-- Status breakdown + Quick actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Tasks by status</h2>
        <div class="grid grid-cols-5 gap-2">
            <?php
            $statusColors = ['todo' => 'bg-gray-100 text-gray-700', 'in_progress' => 'bg-blue-50 text-blue-700', 'in_review' => 'bg-yellow-50 text-yellow-700', 'blocked' => 'bg-red-50 text-red-700', 'done' => 'bg-green-50 text-green-700'];
            foreach ($statusCounts as $s => $count):
                ?>
                <div class="rounded-lg <?= $statusColors[$s] ?> p-3 text-center">
                    <p class="text-2xl font-semibold"><?= $count ?></p>
                    <p class="text-xs mt-1 font-medium"><?= statusLabel($s) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Quick actions</h2>
        <div class="space-y-2">
            <a href="/tasktrack/tasks/create.php"
                class="flex items-center gap-2 w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                + New task
            </a>
            <a href="/tasktrack/attendance/index.php"
                class="flex items-center gap-2 w-full px-4 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                Attendance
            </a>
            <a href="/tasktrack/leave/index.php"
                class="flex items-center gap-2 w-full px-4 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                Request leave
            </a>
        </div>
    </div>
</div>

<!-- Recent tasks -->
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-700">Recent tasks</h2>
        <a href="/tasktrack/tasks/index.php" class="text-xs text-indigo-600 hover:underline">View all →</a>
    </div>
    <div class="divide-y divide-gray-50">
        <?php if (empty($recentTasks)): ?>
            <p class="px-6 py-8 text-sm text-gray-400 text-center">No tasks yet.</p>
        <?php endif; ?>
        <?php foreach ($recentTasks as $t): ?>
            <a href="/tasktrack/tasks/show.php?id=<?= $t['id'] ?>"
                class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= priorityClass($t['priority']) ?>">
                        <?= ucfirst(e($t['priority'])) ?>
                    </span>
                    <span class="text-sm text-gray-800 truncate"><?= e($t['title']) ?></span>
                    <?php if ($t['project_name']): ?>
                        <span class="text-xs text-gray-400 hidden sm:inline"><?= e($t['project_name']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= statusClass($t['status']) ?>">
                        <?= statusLabel($t['status']) ?>
                    </span>
                    <?php if ($t['due_date']): ?>
                        <span
                            class="text-xs <?= strtotime($t['due_date']) < time() && $t['status'] !== 'done' ? 'text-red-500 font-medium' : 'text-gray-400' ?>">
                            <?= date('M j', strtotime($t['due_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>