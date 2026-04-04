<?php
$page_title = 'New Task';
$errors = [];
$users = getAllUsers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'todo';
    $priority = $_POST['priority'] ?? 'medium';
    $assignee = (int) ($_POST['assignee_id'] ?? 0) ?: null;
    $due_date = $_POST['due_date'] ?: null;

    if (!$title)
        $errors[] = 'Title is required.';

    if (!$errors) {
        createTask([
            'title' => $title,
            'description' => $desc,
            'status' => $status,
            'priority' => $priority,
            'assignee_id' => $assignee,
            'created_by' => loggedInUser()['id'],
            'due_date' => $due_date,
        ]);
        setFlash('success', 'Task created.');
        redirect('./?page=task/list');
    }
}
?>

<div class="max-w-2xl">
    <div class="mb-6">
        <a href="./?page=task/list" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2">New task</h1>
    </div>

    <?php if ($errors): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
            <?php foreach ($errors as $err): ?>
                <p>
                    <?= e($err) ?>
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="./?page=task/create" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <?= csrfField() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                             focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?= e($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['todo' => 'To Do', 'in_progress' => 'In Progress', 'in_review' => 'In Review', 'blocked' => 'Blocked', 'done' => 'Done'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= (($_POST['status'] ?? 'todo') === $v) ? 'selected' : '' ?>>
                            <?= $l ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['low', 'medium', 'high', 'urgent'] as $p): ?>
                        <option value="<?= $p ?>" <?= (($_POST['priority'] ?? 'medium') === $p) ? 'selected' : '' ?>>
                            <?= ucfirst($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                <select name="assignee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Unassigned</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (($_POST['assignee_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
                <input type="date" name="due_date" value="<?= e($_POST['due_date'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                Create task
            </button>
            <a href="./?page=task/list"
                class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
                Cancel
            </a>
        </div>
    </form>
</div>