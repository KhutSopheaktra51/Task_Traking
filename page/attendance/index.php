<?php
$page_title = 'Attendance';
$me = loggedInUser();
$today = date('Y-m-d');

// ---- Check in ----
if (isset($_POST['action']) && $_POST['action'] === 'checkin') {
    verifyCsrf();
    if (!getTodayRecord($me['id'])) {
        doCheckIn($me['id']);
        setFlash('success', 'Checked in at ' . date('H:i'));
    } else {
        setFlash('error', 'Already checked in today.');
    }
    redirect('./?page=attendance/index');
}

// ---- Check out ----
if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
    verifyCsrf();
    $record = getTodayRecord($me['id']);
    if ($record && !$record['check_out']) {
        doCheckOut($record['id']);
        setFlash('success', 'Checked out at ' . date('H:i'));
    } else {
        setFlash('error', $record ? 'Already checked out.' : 'No check-in found.');
    }
    redirect('./?page=attendance/index');
}

$today_record = getTodayRecord($me['id']);
$week_days = getWeekDays($me['id']);
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Attendance</h1>
    <a href="./?page=attendance/history" class="text-sm text-indigo-600 hover:underline">
        View history →
    </a>
</div>

<!-- Today card -->
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Today —
        <?= date('l, F j, Y') ?>
    </h2>

    <?php if ($today_record): ?>
        <div class="flex items-center gap-8 mb-4">
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-1">Checked in</p>
                <p class="text-2xl font-bold text-gray-900">
                    <?= e(substr($today_record['check_in'] ?? '', 0, 5)) ?>
                </p>
            </div>
            <div class="text-gray-300 text-xl">→</div>
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-1">Checked out</p>
                <p class="text-2xl font-bold <?= $today_record['check_out'] ? 'text-gray-900' : 'text-gray-300' ?>">
                    <?= $today_record['check_out'] ? e(substr($today_record['check_out'], 0, 5)) : '—' ?>
                </p>
            </div>
        </div>

        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                     <?= attendanceBadge($today_record['status']) ?>">
            <?= ucfirst(str_replace('_', ' ', $today_record['status'])) ?>
        </span>

        <?php if (!$today_record['check_out']): ?>
            <form method="POST" action="./?page=attendance/index" class="mt-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="checkout">
                <button type="submit"
                    class="px-5 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg">
                    Check out now
                </button>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <p class="text-sm text-gray-500 mb-4">You have not checked in yet.</p>
        <form method="POST" action="./?page=attendance/index">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="checkin">
            <button type="submit"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                Check in now —
                <?= date('H:i') ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Weekly grid -->
<div class="bg-white rounded-xl border border-gray-200 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">This week</h2>
    <div class="grid grid-cols-7 gap-2">
        <?php foreach ($week_days as $day):
            $is_today = $day['date'] === $today;
            ?>
            <div class="rounded-lg border p-2 text-center
                    <?= $is_today ? 'border-indigo-300 bg-indigo-50' : 'border-gray-100' ?>">
                <p class="text-xs font-medium text-gray-500">
                    <?= $day['label'] ?>
                </p>
                <p class="text-sm font-bold text-gray-800 my-1">
                    <?= $day['day'] ?>
                </p>
                <?php if ($day['record']): ?>
                    <span class="inline-block px-1 py-0.5 rounded text-xs font-medium
                             <?= attendanceBadge($day['record']['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $day['record']['status'])) ?>
                    </span>
                <?php elseif ($day['date'] < $today): ?>
                    <span class="text-xs text-gray-300">—</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>