<?php
// tasks/edit.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$id   = (int)($_GET['id'] ?? 0);
$task = dbRow('SELECT * FROM tasks WHERE id = ?', [$id]);
if (!$task) die(renderError('Task not found.'));

$errors   = [];
$users    = dbRows('SELECT id, name FROM users ORDER BY name');
$projects = dbRows('SELECT id, name FROM projects ORDER BY name');
$tags     = dbRows('SELECT * FROM tags ORDER BY name');
$taskTags = array_column(dbRows('SELECT tag_id FROM task_tag WHERE task_id = ?', [$id]), 'tag_id');

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

    if (!$errors) {
        dbRun('UPDATE tasks SET title=?, description=?, status=?, priority=?, assignee_id=?, project_id=?, due_date=? WHERE id=?',
              [$title, $desc, $status, $priority, $assignee, $project, $due, $id]);

        dbRun('DELETE FROM task_tag WHERE task_id = ?', [$id]);
        foreach ($_POST['tags'] ?? [] as $tagId) {
            dbRun('INSERT IGNORE INTO task_tag (task_id, tag_id) VALUES (?, ?)', [$id, (int)$tagId]);
        }

        flash('success', 'Task updated.');
        redirect('/tasktrack/tasks/show.php?id=' . $id);
    }
    $task = array_merge($task, $_POST);
}

$pageTitle = 'Edit Task';
include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="/tasktrack/tasks/show.php?id=<?= $id ?>" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2">Edit task</h1>
    </div>
    <?php if ($errors): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
            <?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <?= csrfField() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" value="<?= e($task['title']) ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?= e($task['description']) ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','blocked'=>'Blocked','done'=>'Done'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $task['status']===$v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['low','medium','high','urgent'] as $p): ?>
                        <option value="<?= $p ?>" <?= $task['priority']===$p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
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
                        <option value="<?= $u['id'] ?>" <?= $task['assignee_id']==$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                <select name="project_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">No project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $task['project_id']==$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
            <input type="date" name="due_date" value="<?= e($task['due_date']) ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($tags as $tag): ?>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>"
                           <?= in_array($tag['id'], $taskTags) ? 'checked' : '' ?>
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
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Save changes</button>
            <a href="/tasktrack/tasks/show.php?id=<?= $id ?>" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>