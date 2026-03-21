<?php
// attendance/admin.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
requireRole('admin', 'manager');

// CSV export
if (isset($_GET['export'])) {
    $rows = dbRows('SELECT ar.*, u.name AS user_name FROM attendance_records ar JOIN users u ON ar.user_id = u.id ORDER BY ar.date DESC');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=attendance_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Date', 'Check In', 'Check Out', 'Status']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['user_name'], $r['date'], $r['check_in'] ?? '—', $r['check_out'] ?? '—', $r['status']]);
    }
    fclose($out);
    exit;
}

$where = '1=1';
$params = [];
if (!empty($_GET['date'])) {
    $where .= ' AND ar.date = ?';
    $params[] = $_GET['date'];
}
if (!empty($_GET['user_id'])) {
    $where .= ' AND ar.user_id = ?';
    $params[] = (int) $_GET['user_id'];
}

$result = paginate(
    "SELECT ar.*, u.name AS user_name FROM attendance_records ar JOIN users u ON ar.user_id = u.id WHERE $where ORDER BY ar.date DESC",
    $params,
    30
);

$users = dbRows('SELECT id, name FROM users ORDER BY name');
$pageTitle = 'All Attendance';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">All attendance</h1>
    <a href="?export=1&<?= http_build_query(array_filter(['date' => $_GET['date'] ?? '', 'user_id' => $_GET['user_id'] ?? ''])) ?>"
        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
        Export CSV
    </a>
</div>

<form method="GET" class="flex flex-wrap gap-3 mb-5 bg-white rounded-xl border border-gray-200 p-4">
    <input type="date" name="date" value="<?= e($_GET['date'] ?? '') ?>"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
    <select name="user_id"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <option value="">All users</option>
        <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= (($_GET['user_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                <?= e($u['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit"
        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Filter</button>
    <a href="/tasktrack/attendance/admin.php" class="px-4 py-2 text-gray-500 text-sm">Clear</a>
</form>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">In</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Out</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php
            $statusBg = ['present' => 'bg-green-100 text-green-700', 'late' => 'bg-yellow-100 text-yellow-700', 'absent' => 'bg-red-100 text-red-700', 'half_day' => 'bg-blue-100 text-blue-700'];
            foreach ($result['rows'] as $r):
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800"><?= e($r['user_name']) ?></td>
                    <td class="px-5 py-3 text-gray-600"><?= date('M j, Y', strtotime($r['date'])) ?></td>
                    <td class="px-5 py-3 text-gray-600"><?= $r['check_in'] ? substr($r['check_in'], 0, 5) : '—' ?></td>
                    <td class="px-5 py-3 text-gray-600"><?= $r['check_out'] ? substr($r['check_out'], 0, 5) : '—' ?></td>
                    <td class="px-5 py-3">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBg[$r['status']] ?? '' ?>">
                            <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['rows'])): ?>
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-400">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= paginationLinks($result) ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>