<?php
// reports/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
requireRole('admin', 'manager');

$month = $_GET['month'] ?? date('Y-m');
[$year, $mon] = explode('-', $month);

// CSV export
if (isset($_GET['export'])) {
    $users = dbRows('SELECT * FROM users ORDER BY name');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=report_' . $month . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Present', 'Late', 'Absent', 'Half Day']);
    foreach ($users as $u) {
        $records = dbRows(
            'SELECT status FROM attendance_records WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?',
            [$u['id'], $year, $mon]
        );
        $counts = array_count_values(array_column($records, 'status'));
        fputcsv($out, [
            $u['name'],
            $counts['present']  ?? 0,
            $counts['late']     ?? 0,
            $counts['absent']   ?? 0,
            $counts['half_day'] ?? 0,
        ]);
    }
    fclose($out);
    exit;
}

// Task status counts
$taskStatus = [];
foreach (['todo','in_progress','in_review','blocked','done'] as $s) {
    $row = dbRow('SELECT COUNT(*) as c FROM tasks WHERE status = ?', [$s]);
    $taskStatus[$s] = (int)$row['c'];
}

// Task priority counts
$taskPriority = [];
foreach (['low','medium','high','urgent'] as $p) {
    $row = dbRow('SELECT COUNT(*) as c FROM tasks WHERE priority = ?', [$p]);
    $taskPriority[$p] = (int)$row['c'];
}

// Attendance summary
$users = dbRows('SELECT * FROM users ORDER BY name');
$attendanceSummary = [];
foreach ($users as $u) {
    $records = dbRows(
        'SELECT status FROM attendance_records WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?',
        [$u['id'], $year, $mon]
    );
    $counts = array_count_values(array_column($records, 'status'));
    $attendanceSummary[] = [
        'name'     => $u['name'],
        'present'  => $counts['present']  ?? 0,
        'late'     => $counts['late']     ?? 0,
        'absent'   => $counts['absent']   ?? 0,
        'half_day' => $counts['half_day'] ?? 0,
    ];
}

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
    <a href="?month=<?= e($month) ?>&export=1"
       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
        Export CSV
    </a>
</div>

<form method="GET" class="mb-6 flex items-center gap-3 bg-white rounded-xl border border-gray-200 p-4">
    <label class="text-sm font-medium text-gray-700">Month</label>
    <input type="month" name="month" value="<?= e($month) ?>"
           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    <button type="submit"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
        Go
    </button>
</form>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Tasks by status</h2>
        <div style="height:240px">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Tasks by priority</h2>
        <div style="height:240px">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>
</div>

<!-- Attendance table -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">
            Attendance summary — <?= date('F Y', mktime(0,0,0,(int)$mon,1,(int)$year)) ?>
        </h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-green-600 uppercase">Present</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-yellow-600 uppercase">Late</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-red-500 uppercase">Absent</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-blue-600 uppercase">Half day</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach ($attendanceSummary as $row): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 font-medium text-gray-800"><?= e($row['name']) ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['present'] ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['late'] ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['absent'] ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['half_day'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($attendanceSummary)): ?>
                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No data found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['To Do','In Progress','In Review','Blocked','Done'],
        datasets: [{
            data: [
                <?= $taskStatus['todo'] ?>,
                <?= $taskStatus['in_progress'] ?>,
                <?= $taskStatus['in_review'] ?>,
                <?= $taskStatus['blocked'] ?>,
                <?= $taskStatus['done'] ?>
            ],
            backgroundColor: ['#e5e7eb','#bfdbfe','#fef3c7','#fee2e2','#d1fae5'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
    }
});

const priorityCtx = document.getElementById('priorityChart').getContext('2d');
new Chart(priorityCtx, {
    type: 'bar',
    data: {
        labels: ['Low','Medium','High','Urgent'],
        datasets: [{
            label: 'Tasks',
            data: [
                <?= $taskPriority['low'] ?>,
                <?= $taskPriority['medium'] ?>,
                <?= $taskPriority['high'] ?>,
                <?= $taskPriority['urgent'] ?>
            ],
            backgroundColor: ['#d1fae5','#bfdbfe','#fed7aa','#fecaca'],
            borderRadius: 6,
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>