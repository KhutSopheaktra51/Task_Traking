<?php
$page_title = 'Tasks';
$me = loggedInUser();

// ---- Build filter conditions ----
$where  = '1=1';
$params = [];

if (!isManagerOrAdmin()) {
    $where .= ' AND t.assignee_id = ?';
    $params[] = $me['id'];
}

if (!empty($_GET['priority'])) {
    $where   .= ' AND t.priority = ?';
    $params[] = $_GET['priority'];
}

if (!empty($_GET['assignee_id'])) {
    $where   .= ' AND t.assignee_id = ?';
    $params[] = (int)$_GET['assignee_id'];
}

if (!empty($_GET['search'])) {
    $where   .= ' AND t.title LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}

// ---- Get filtered tasks ----
$tasks = getRows(
    "SELECT t.*, u.name AS assignee_name, p.name AS project_name
     FROM tasks t
     LEFT JOIN users u ON t.assignee_id = u.id
     LEFT JOIN projects p ON t.project_id = p.id
     WHERE $where
     ORDER BY t.created_at DESC",
    $params
);

// ---- Group tasks by status for Kanban ----
$columns = ['todo' => [], 'in_progress' => [], 'in_review' => [], 'done' => []];
foreach ($tasks as $task) {
    $s = $task['status'];
    if (isset($columns[$s])) {
        $columns[$s][] = $task;
    }
}

$users = getAllUsers();
?>

<!-- Heading -->
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Tasks</h1>
    <a href="./?page=task/create"
       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        + New task
    </a>
</div>

<!-- Filters -->
<form method="GET" action="./" class="flex flex-wrap gap-3 mb-6 bg-white rounded-xl border border-gray-200 p-4">
    <input type="hidden" name="page" value="task/list">

    <input type="text" name="search" value="<?= e($_GET['search'] ?? '') ?>"
           placeholder="Search tasks..."
           class="flex-1 min-w-40 px-3 py-2 text-sm border border-gray-300 rounded-lg
                  focus:outline-none focus:ring-2 focus:ring-indigo-500">

    <select name="priority"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                   focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <option value="">All priorities</option>
        <?php foreach (['low', 'medium', 'high', 'urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= (($_GET['priority'] ?? '') === $p) ? 'selected' : '' ?>>
                <?= ucfirst($p) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if (isManagerOrAdmin()): ?>
    <select name="assignee_id"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                   focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <option value="">All assignees</option>
        <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>"
                    <?= (($_GET['assignee_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                <?= e($u['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <button type="submit"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
        Filter
    </button>
    <a href="./?page=task/list" class="px-4 py-2 text-gray-500 text-sm">Clear</a>
</form>

<!-- Kanban Board -->
<div x-data="kanban()" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

    <?php
    $col_labels = [
        'todo'        => ['To Do',       'bg-gray-200 text-gray-700'],
        'in_progress' => ['In Progress', 'bg-blue-200 text-blue-800'],
        'in_review'   => ['In Review',   'bg-yellow-200 text-yellow-800'],
        'done'        => ['Done',        'bg-green-200 text-green-800'],
    ];
    foreach ($columns as $status => $tasks):
    [$label, $badge_class] = $col_labels[$status];
    ?>
    <div class="bg-gray-50 rounded-xl border border-gray-200 flex flex-col"
         x-on:dragover.prevent
         x-on:drop="drop($event, '<?= $status ?>')">

        <!-- Column header -->
        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $col_labels[$status][1] ?>">
                <?= $col_labels[$status][0] ?>
            </span>
            <span class="text-xs text-gray-400"><?= count($columns[$status]) ?></span>
        </div>

        <!-- Task cards -->
        <div class="flex-1 p-3 space-y-2 min-h-24">
            <?php if (empty($columns[$status])): ?>
                <div class="flex items-center justify-center h-16 text-xs text-gray-300
                            border-2 border-dashed border-gray-200 rounded-lg">
                    Drop here
                </div>
            <?php endif; ?>

            <?php foreach ($columns[$status] as $task): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-3 cursor-grab
                        hover:border-indigo-300 transition-colors"
                 draggable="true"
                 x-on:dragstart="dragStart($event, <?= $task['id'] ?>)">

                <a href="./?page=task/show&id=<?= $task['id'] ?>" class="block">
                    <p class="text-sm font-medium text-gray-800 leading-snug mb-2">
                        <?= e($task['title']) ?>
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs px-1.5 py-0.5 rounded <?= priorityBadge($task['priority']) ?>">
                            <?= ucfirst($task['priority']) ?>
                        </span>
                        <?php if ($task['due_date']): ?>
                            <?php $overdue = strtotime($task['due_date']) < time() && $task['status'] !== 'done'; ?>
                            <span class="text-xs <?= $overdue ? 'text-red-500 font-medium' : 'text-gray-400' ?>">
                                <?= date('M j', strtotime($task['due_date'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($task['assignee_name']): ?>
                    <div class="mt-2 flex justify-end">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center
                                    justify-center text-indigo-700 text-xs font-bold">
                            <?= e(strtoupper(substr($task['assignee_name'], 0, 1))) ?>
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
        dragging_id: null,

        dragStart(event, task_id) {
            this.dragging_id = task_id;
        },

        drop(event, new_status) {
            if (!this.dragging_id) return;

            fetch('./?page=task/update_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + this.dragging_id
                    + '&status=' + new_status
                    + '&csrf_token=<?= csrfToken() ?>',
            }).then(() => window.location.reload());
        },
    };
}
</script>