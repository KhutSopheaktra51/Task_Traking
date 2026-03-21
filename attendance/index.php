<?php
// attendance/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$user = auth();
$today = date('Y-m-d');

// Handle check-in
if (isset($_POST['action']) && $_POST['action'] === 'checkin') {
    verifyCsrf();
    $existing = dbRow('SELECT id FROM attendance_records WHERE user_id=? AND date=?', [$user['id'], $today]);
    if (!$existing) {
        $time = date('H:i:s');
        $status = (date('H:i') > '09:00') ? 'late' : 'present';
        dbRun(
            'INSERT INTO attendance_records (user_id, date, check_in, status) VALUES (?,?,?,?)',
            [$user['id'], $today, $time, $status]
        );
        flash('success', 'Checked in at ' . date('H:i'));
    } else {
        flash('error', 'Already checked in today.');
    }
    redirect('/tasktrack/attendance/index.php');
}

// Handle check-out
if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
    verifyCsrf();
    $record = dbRow('SELECT * FROM attendance_records WHERE user_id=? AND date=?', [$user['id'], $today]);
    if ($record && !$record['check_out']) {
        dbRun('UPDATE attendance_records SET check_out=? WHERE id=?', [date('H:i:s'), $record['id']]);
        flash('success', 'Checked out at ' . date('H:i'));
    } else {
        flash('error', $record ? 'Already checked out.' : 'No check-in found.');
    }
    redirect('/tasktrack/attendance/index.php');
}

$todayRecord = dbRow('SELECT * FROM attendance_records WHERE user_id=? AND date=?', [$user['id'], $today]);

// Build this week's days
$weekStart = strtotime('monday this week');
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', $weekStart + $i * 86400);
    $weekDays[] = [
        'date' => $date,
        'label' => date('D', $weekStart + $i * 86400),
        'day' => date('j', $weekStart + $i * 86400),
        'record' => dbRow('SELECT * FROM attendance_records WHERE user_id=? AND date=?', [$user['id'], $date]),
    ];
}

$pageTitle = 'Attendance';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Attendance</h1>
    <a href="/tasktrack/attendance/history.php" class="text-sm text-indigo-600 hover:underline">View history →</a>
</div>

<!-- Today's card -->
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Today — <?= date('l, F j, Y') ?></h2>
    <?php if ($todayRecord): ?>
        <div class="flex items-center gap-8 mb-4">
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-1">Checked in</p>
                <p class="text-2xl font-semibold text-gray-900"><?= e(substr($todayRecord['check_in'] ?? '—', 0, 5)) ?></p>
            </div>
            <div class="text-gray-300 text-xl">→</div>
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-1">Checked out</p>
                <p class="text-2xl font-semibold <?= $todayRecord['check_out'] ? 'text-gray-900' : 'text-gray-300' ?>">
                    <?= $todayRecord['check_out'] ? e(substr($todayRecord['check_out'], 0, 5)) : '—' ?>
                </p>
            </div>
            <?php if ($todayRecord['check_in'] && $todayRecord['check_out']): ?>
                <?php
                $mins = (strtotime($todayRecord['check_out']) - strtotime($todayRecord['check_in'])) / 60;
                $hours = round($mins / 60, 2);
                ?>
                <div class="text-center ml-4">
                    <p class="text-xs text-gray-400 mb-1">Hours worked</p>
                    <p class="text-2xl font-semibold text-green-600"><?= $hours ?>h</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $statusBg = ['present' => 'bg-green-100 text-green-700', 'late' => 'bg-yellow-100 text-yellow-700', 'absent' => 'bg-red-100 text-red-700', 'half_day' => 'bg-blue-100 text-blue-700'];
        ?>
        <span
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusBg[$todayRecord['status']] ?? '' ?>">
            <?= ucfirst(str_replace('_', ' ', $todayRecord['status'])) ?>
        </span>
        <?php if (!$todayRecord['check_out']): ?>
            <form method="POST" class="mt-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="checkout">
                <button type="submit"
                    class="px-5 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg">
                    Check out now
                </button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-sm text-gray-500 mb-4">You haven't checked in yet today.</p>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="checkin">
            <button type="submit"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                Check in now — <?= date('H:i') ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Weekly grid -->
<div class="bg-white rounded-xl border border-gray-200 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">This week</h2>
    <div class="grid grid-cols-7 gap-2">
        <?php
        $weekBg = ['present' => 'bg-green-50 border-green-200 text-green-700', 'late' => 'bg-yellow-50 border-yellow-200 text-yellow-700', 'absent' => 'bg-red-50 border-red-200 text-red-700', 'half_day' => 'bg-blue-50 border-blue-200 text-blue-700'];
        foreach ($weekDays as $day):
            $isToday = $day['date'] === $today;
            ?>
            <div
                class="rounded-lg border p-2 text-center <?= $isToday ? 'border-indigo-300 bg-indigo-50' : 'border-gray-100' ?>">
                <p class="text-xs font-medium text-gray-500"><?= $day['label'] ?></p>
                <p class="text-sm font-semibold text-gray-800 my-1"><?= $day['day'] ?></p>
                <?php if ($day['record']): ?>
                    <span
                        class="inline-block px-1 py-0.5 rounded text-xs font-medium border <?= $weekBg[$day['record']['status']] ?? 'bg-gray-50 border-gray-200 text-gray-500' ?>">
                        <?= ucfirst(str_replace('_', ' ', $day['record']['status'])) ?>
                    </span>
                <?php elseif ($day['date'] < $today): ?>
                    <span class="text-xs text-gray-300">—</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>