<?php
$page_title = 'My Attendance History';
$records = getUserAttendance(loggedInUser()['id']);
?>

<div class="mb-6">
    <a href="./?page=attendance/index" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-semibold text-gray-900 mt-1">My attendance history</h1>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Check in</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Check out</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="4" class="px-5 py-8 text-center text-gray-400">No records found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($records as $r): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">
                        <?= date('D, M j, Y', strtotime($r['date'])) ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <?= $r['check_in'] ? e(substr($r['check_in'], 0, 5)) : '—' ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <?= $r['check_out'] ? e(substr($r['check_out'], 0, 5)) : '—' ?>
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