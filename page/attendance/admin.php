<?php
$page_title = 'All Attendance';
$filter_date = $_GET['date'] ?? null;
$filter_user = $_GET['user_id'] ?? null;
$records = getAllAttendance($filter_date, $filter_user);
$users = getAllUsers();

// ---- CSV export ----
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=attendance_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Date', 'Check In', 'Check Out', 'Status']);
    foreach ($records as $r) {
        fputcsv($out, [
            $r['user_name'],
            $r['date'],
            $r['check_in'] ? substr($r['check_in'], 0, 5) : '—',
            $r['check_out'] ? substr($r['check_out'], 0, 5) : '—',
            $r['status'],
        ]);
    }
    fclose($out);
    exit;
}
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">All attendance</h1>
    <a href="./?page=attendance/admin&export=1&<?= http_build_query(array_filter(['date' => $filter_date, 'user_id' => $filter_user])) ?>"
        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
        Export CSV
    </a>
</div>

<form method="GET" action="./" class="flex flex-wrap gap-3 mb-5 bg-white rounded-xl border border-gray-200 p-4">
    <input type="hidden" name="page" value="attendance/admin">
    <input type="date" name="date" value="<?= e($filter_date ?? '') ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
    <select name="user_id" class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                   focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <option value="">All users</option>
        <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= ($filter_user == $u['id']) ? 'selected' : '' ?>>
                <?= e($u['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
        Filter
    </button>
    <a href="./?page=attendance/admin" class="px-4 py-2 text-gray-500 text-sm">Clear</a>
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
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-400">No records found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($records as $r): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">
                        <?= e($r['user_name']) ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <?= date('M j, Y', strtotime($r['date'])) ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <?= $r['check_in'] ? substr($r['check_in'], 0, 5) : '—' ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <?= $r['check_out'] ? substr($r['check_out'], 0, 5) : '—' ?>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                 <?= attendanceBadge($r['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>