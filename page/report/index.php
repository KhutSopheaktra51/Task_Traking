<?php
$page_title = 'Reports';
$month      = $_GET['month'] ?? date('Y-m');
[$year, $mon] = explode('-', $month);

// ---- CSV export ----
if (isset($_GET['export'])) {
    while (ob_get_level()) ob_end_clean();
    $all_users = getAllUsers();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=report_' . $month . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Present', 'Late', 'Absent', 'Half Day']);
    foreach ($all_users as $u) {
        $records = getRows(
            'SELECT status FROM attendance_records
             WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?',
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

// ---- Task counts ----
$task_status = [];
foreach (['todo', 'in_progress', 'in_review', 'blocked', 'done'] as $s) {
    $row = getRow('SELECT COUNT(*) as total FROM tasks WHERE status = ?', [$s]);
    $task_status[$s] = (int)$row['total'];
}

$task_priority = [];
foreach (['low', 'medium', 'high', 'urgent'] as $p) {
    $row = getRow('SELECT COUNT(*) as total FROM tasks WHERE priority = ?', [$p]);
    $task_priority[$p] = (int)$row['total'];
}

// ---- Attendance summary ----
$all_users          = getAllUsers();
$attendance_summary = [];
foreach ($all_users as $u) {
    $records = getRows(
        'SELECT status FROM attendance_records
         WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?',
        [$u['id'], $year, $mon]
    );
    $counts = array_count_values(array_column($records, 'status'));
    $attendance_summary[] = [
        'name'     => $u['name'],
        'present'  => $counts['present']  ?? 0,
        'late'     => $counts['late']     ?? 0,
        'absent'   => $counts['absent']   ?? 0,
        'half_day' => $counts['half_day'] ?? 0,
    ];
}
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
    <a href="./?page=report/index&month=<?= e($month) ?>&export=1"
       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
        Export CSV
    </a>
</div>

<form method="GET" action="./"
      class="mb-6 flex items-center gap-3 bg-white rounded-xl border border-gray-200 p-4">
    <input type="hidden" name="page" value="report/index">
    <label class="text-sm font-medium text-gray-700">Month</label>
    <input type="month" name="month" value="<?= e($month) ?>"
           class="px-3 py-2 border border-gray-300 rounded-lg text-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
    <button type="submit"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
        Go
    </button>
</form>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Tasks by status</h2>
        <div style="height:240px"><canvas id="status_chart"></canvas></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Tasks by priority</h2>
        <div style="height:240px"><canvas id="priority_chart"></canvas></div>
    </div>
</div>

<!-- Attendance table -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">
            Attendance — <?= date('F Y', mktime(0, 0, 0, (int)$mon, 1, (int)$year)) ?>
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
            <?php if (empty($attendance_summary)): ?>
                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No data.</td></tr>
            <?php endif; ?>
            <?php foreach ($attendance_summary as $row): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 font-medium text-gray-800"><?= e($row['name']) ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['present'] ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['late'] ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['absent'] ?></td>
                <td class="px-4 py-3 text-center text-gray-700"><?= $row['half_day'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
new Chart(document.getElementById('status_chart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['To Do', 'In Progress', 'In Review', 'Blocked', 'Done'],
        datasets: [{
            data: [<?= $task_status['todo'] ?>,<?= $task_status['in_progress'] ?>,<?= $task_status['in_review'] ?>,<?= $task_status['blocked'] ?>,<?= $task_status['done'] ?>],
            backgroundColor: ['#e5e7eb','#bfdbfe','#fef3c7','#fee2e2','#d1fae5'],
            borderWidth: 0,
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
});

new Chart(document.getElementById('priority_chart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Low', 'Medium', 'High', 'Urgent'],
        datasets: [{
            data: [<?= $task_priority['low'] ?>,<?= $task_priority['medium'] ?>,<?= $task_priority['high'] ?>,<?= $task_priority['urgent'] ?>],
            backgroundColor: ['#d1fae5','#bfdbfe','#fed7aa','#fecaca'],
            borderRadius: 6, borderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } }
    }
});
</script>