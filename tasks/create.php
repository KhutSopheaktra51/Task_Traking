<?php
// tasks/create.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$errors   = [];
$users    = dbRows('SELECT id, name FROM users ORDER BY name');
$projects = dbRows('SELECT id, name FROM projects ORDER BY name');
$tags     = dbRows('SELECT * FROM tags ORDER BY name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $status   = $_POST['status'] ?? 'todo';
    $priority = $_POST['priority'] ?? 'medium';
    $assignee = (int)($_POST['assignee_id'] ?? 0) ?: null;
    $project  = (int)($_POST['project_id'] ?? 0) ?: null;
    $due      = $_POST['due_date'] ?: null;

    if (!$title) $errors[] = 'Title is required.';
    if (!in_array($status, ['todo','in_progress','in_review','blocked','done'])) $errors[] = 'Invalid status.';
    if (!in_array($priority, ['low','medium','high','urgent'])) $errors[] = 'Invalid priority.';

    if (!$errors) {
        $taskId = dbRun(
            'INSERT INTO tasks (title, description, status, priority, assignee_id, created_by, project_id, due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$title, $desc, $status, $priority, $assignee, auth()['id'], $project, $due]
        );

        // Tags
        if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
            foreach ($_POST['tags'] as $tagId) {
                dbRun('INSERT IGNORE INTO task_tag (task_id, tag_id) VALUES (?, ?)', [$taskId, (int)$tagId]);
            }
        }

        flash('success', 'Task created successfully.');
        redirect('/tasktrack/tasks/show.php?id=' . $taskId);
    }
}

$pageTitle = 'New Task';
include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="/tasktrack/tasks/index.php" class="text-sm text-gray-500 hover:text-gray-700">← Back to tasks</a>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2">New task</h1>
    </div>
    <?php if ($errors): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
            <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <?= csrfField() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?= e($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','blocked'=>'Blocked','done'=>'Done'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= (($_POST['status'] ?? 'todo') === $v) ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['low','medium','high','urgent'] as $p): ?>
                        <option value="<?= $p ?>" <?= (($_POST['priority'] ?? 'medium') === $p) ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                <select name="assignee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Unassigned</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (($_POST['assignee_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">No project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (($_POST['project_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
            <input type="date" name="due_date" value="<?= e($_POST['due_date'] ?? '') ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($tags as $tag): ?>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>"
                           <?= in_array($tag['id'], $_POST['tags'] ?? []) ? 'checked' : '' ?>
                           class="rounded border-gray-300 text-indigo-600">
                    <span class="text-sm px-2 py-0.5 rounded"
                          style="background-color:<?= e($tag['color']) ?>22;color:<?= e($tag['color']) ?>">
                        <?= e($tag['name']) ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                Create task
            </button>
            <a href="/tasktrack/tasks/index.php"
               class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>