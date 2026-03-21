<?php
// leave/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $type = $_POST['type'] ?? '';
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (!in_array($type, ['sick', 'annual', 'unpaid']))
        $errors[] = 'Invalid leave type.';
    if (!$start || strtotime($start) < strtotime('today'))
        $errors[] = 'Start date must be today or later.';
    if (!$end || $end < $start)
        $errors[] = 'End date must be on or after start date.';
    if (!$reason)
        $errors[] = 'Reason is required.';

    if (!$errors) {
        dbRun(
            'INSERT INTO leave_requests (user_id, type, start_date, end_date, reason) VALUES (?,?,?,?,?)',
            [auth()['id'], $type, $start, $end, $reason]
        );
        flash('success', 'Leave request submitted.');
        redirect('/tasktrack/leave/index.php');
    }
}

$requests = dbRows(
    'SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC',
    [auth()['id']]
);

$pageTitle = 'Leave Requests';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Leave requests</h1>
    <?php if (isMgrOrAdmin()): ?>
        <a href="/tasktrack/leave/manage.php" class="text-sm text-indigo-600 hover:underline">Manage pending →</a>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
        <?php foreach ($errors as $err): ?>
            <p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Submit form -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">New request</h2>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="annual" <?= (($_POST['type'] ?? '') == 'annual') ? 'selected' : '' ?>>Annual leave</option>
                    <option value="sick" <?= (($_POST['type'] ?? '') == 'sick') ? 'selected' : '' ?>>Sick leave</option>
                    <option value="unpaid" <?= (($_POST['type'] ?? '') == 'unpaid') ? 'selected' : '' ?>>Unpaid leave</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="date" name="start_date" value="<?= e($_POST['start_date'] ?? '') ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea name="reason" rows="3" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?= e($_POST['reason'] ?? '') ?></textarea>
            </div>
            <button type="submit"
                class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                Submit request
            </button>
        </form>
    </div>

    <!-- My requests -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">My requests</h2>
            </div>
            <?php
            $statusBg = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
            ?>
            <div class="divide-y divide-gray-50">
                <?php if (empty($requests)): ?>
                    <p class="px-5 py-8 text-sm text-gray-400 text-center">No requests yet.</p>
                <?php endif; ?>
                <?php foreach ($requests as $r):
                    $days = (int) round((strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400) + 1;
                    ?>
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    <?= ucfirst($r['type']) ?> leave
                                    <span class="font-normal text-gray-500">· <?= $days ?>
                                        day<?= $days > 1 ? 's' : '' ?></span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <?= date('M j', strtotime($r['start_date'])) ?> –
                                    <?= date('M j, Y', strtotime($r['end_date'])) ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1"><?= e($r['reason']) ?></p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0 <?= $statusBg[$r['status']] ?? '' ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>