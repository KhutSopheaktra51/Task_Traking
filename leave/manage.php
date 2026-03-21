<?php
// leave/manage.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
requireRole('admin', 'manager');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id && in_array($action, ['approved', 'rejected'])) {
        dbRun(
            'UPDATE leave_requests SET status=?, approved_by=? WHERE id=?',
            [$action, auth()['id'], $id]
        );
        flash('success', 'Leave request ' . $action . '.');
    }
    redirect('/tasktrack/leave/manage.php');
}

$requests = dbRows(
    'SELECT lr.*, u.name AS user_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id
     WHERE lr.status = "pending" ORDER BY lr.start_date ASC'
);

$pageTitle = 'Manage Leave';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Pending leave requests</h1>
    <p class="text-sm text-gray-500 mt-1"><?= count($requests) ?> pending</p>
</div>

<?php if (empty($requests)): ?>
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
        <p class="text-gray-400 text-sm">All caught up — no pending requests.</p>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($requests as $r):
            $days = (int) round((strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400) + 1;
            ?>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-sm flex-shrink-0">
                            <?= e(initials($r['user_name'])) ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800"><?= e($r['user_name']) ?></p>
                            <p class="text-sm text-gray-600 mt-0.5">
                                <span class="font-medium"><?= ucfirst($r['type']) ?> leave</span>
                                — <?= date('M j', strtotime($r['start_date'])) ?> to
                                <?= date('M j, Y', strtotime($r['end_date'])) ?>
                                <span class="text-gray-400">(<?= $days ?> day<?= $days > 1 ? 's' : '' ?>)</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1"><?= e($r['reason']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="approved">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                                Approve
                            </button>
                        </form>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="rejected">
                            <button type="submit"
                                class="px-4 py-2 bg-white hover:bg-gray-50 border border-red-200 text-red-600 text-sm font-medium rounded-lg">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>