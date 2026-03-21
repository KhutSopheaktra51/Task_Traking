<?php
// tasks/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$user = auth();
$where = isMgrOrAdmin() ? '1=1' : 't.assignee_id = ' . (int) $user['id'];
$params = [];

if (!empty($_GET['priority'])) {
    $where .= ' AND t.priority = ?';
    $params[] = $_GET['priority'];
}
if (!empty($_GET['assignee_id'])) {
    $where .= ' AND t.assignee_id = ?';
    $params[] = (int) $_GET['assignee_id'];
}
if (!empty($_GET['search'])) {
    $where .= ' AND t.title LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}

$tasks = dbRows(
    "SELECT t.*, u.name AS assignee_name, p.name AS project_name
     FROM tasks t
     LEFT JOIN users u ON t.assignee_id = u.id
     LEFT JOIN projects p ON t.project_id = p.id
     WHERE $where ORDER BY t.created_at DESC",
    $params
);

$grouped = [];
foreach (['todo', 'in_progress', 'in_review', 'done'] as $s)
    $grouped[$s] = [];
foreach ($tasks as $t) {
    if (isset($grouped[$t['status']]))
        $grouped[$t['status']][] = $t;
    else
        $grouped['todo'][] = $t;
}

$users = dbRows('SELECT id, name FROM users ORDER BY name');
$pageTitle = 'Tasks';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Tasks</h1>
    <a href="/tasktrack/tasks/create.php"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        + New task
    </a>
</div>

<!-- Filters -->
<form method="GET" class="flex flex-wrap gap-3 mb-6 bg-white rounded-xl border border-gray-200 p-4">
    <input type="text" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search tasks…"
        class="flex-1 min-w-40 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
    <select name="priority"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <option value="">All priorities</option>
        <?php foreach (['low', 'medium', 'high', 'urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= (($_GET['priority'] ?? '') === $p) ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (isMgrOrAdmin()): ?>
        <select name="assignee_id"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All assignees</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (($_GET['assignee_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                    <?= e($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button type="submit"
        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Filter</button>
    <a href="/tasktrack/tasks/index.php" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm">Clear</a>
</form>

<!-- Kanban board -->
<div x-data="kanban()" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <?php
    $columns = [
        'todo' => ['To Do', 'bg-gray-200 text-gray-700'],
        'in_progress' => ['In Progress', 'bg-blue-200 text-blue-800'],
        'in_review' => ['In Review', 'bg-yellow-200 text-yellow-800'],
        'done' => ['Done', 'bg-green-200 text-green-800'],
    ];
    foreach ($columns as $status => [$label, $badgeClass]):
        ?>
        <div class="bg-gray-50 rounded-xl border border-gray-200 flex flex-col" x-on:dragover.prevent
            x-on:drop="drop($event, '<?= $status ?>')">

            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $badgeClass ?>">
                        <?= $label ?>
                    </span>
                    <span class="text-xs text-gray-400 font-medium"><?= count($grouped[$status]) ?></span>
                </div>
            </div>

            <div class="flex-1 p-3 space-y-2 min-h-24">
                <?php if (empty($grouped[$status])): ?>
                    <div
                        class="flex items-center justify-center h-16 text-xs text-gray-300 border-2 border-dashed border-gray-200 rounded-lg">
                        Drop here</div>
                <?php endif; ?>
                <?php foreach ($grouped[$status] as $t): ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-3 cursor-grab hover:border-indigo-300 transition-colors"
                        draggable="true" x-on:dragstart="dragStart($event, <?= $t['id'] ?>)">
                        <a href="/tasktrack/tasks/show.php?id=<?= $t['id'] ?>" class="block">
                            <p class="text-sm font-medium text-gray-800 leading-snug mb-2"><?= e($t['title']) ?></p>
                            <div class="flex items-center justify-between gap-2">
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= priorityClass($t['priority']) ?>">
                                    <?= ucfirst($t['priority']) ?>
                                </span>
                                <?php if ($t['due_date']): ?>
                                    <span
                                        class="text-xs <?= (strtotime($t['due_date']) < time() && $t['status'] !== 'done') ? 'text-red-500 font-medium' : 'text-gray-400' ?>">
                                        <?= date('M j', strtotime($t['due_date'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($t['assignee_name']): ?>
                                <div class="mt-2 flex justify-end">
                                    <div
                                        class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-semibold">
                                        <?= e(strtoupper(substr($t['assignee_name'], 0, 1))) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    function kanban() {
        return {
            draggingId: null,
            dragStart(event, taskId) { this.draggingId = taskId; },
            drop(event, newStatus) {
                if (!this.draggingId) return;
                fetch('/tasktrack/tasks/update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + this.draggingId + '&status=' + newStatus + '&csrf_token=<?= csrfToken() ?>',
                }).then(() => window.location.reload());
            },
        };
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>