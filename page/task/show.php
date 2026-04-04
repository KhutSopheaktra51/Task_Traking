<?php
$page_title = 'Task Detail';
$id = (int) ($_GET['id'] ?? 0);
$task = getTaskById($id);
$me = loggedInUser();

if (!$task)
    redirect('./?page=task/list');

$page_title = e($task['title']);
$is_overdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] !== 'done';
?>

<div class="max-w-2xl">
    <div class="mb-6 flex items-center justify-between">
        <a href="./?page=task/list" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        <div class="flex items-center gap-2">
            <a href="./?page=task/edit&id=<?= $id ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white
                      border border-gray-300 rounded-lg hover:bg-gray-50">
                Edit
            </a>
            <form method="POST" action="./?page=task/delete" onsubmit="return confirm('Delete this task?')">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 bg-white
                               border border-red-200 rounded-lg hover:bg-red-50">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <h1 class="text-xl font-semibold text-gray-900">
                <?= e($task['title']) ?>
            </h1>
            <div x-data="{ open: false }" class="relative flex-shrink-0">
                <button @click="open = !open" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full
                               text-sm font-medium cursor-pointer <?= statusBadge($task['status']) ?>">
                    <?= statusLabel($task['status']) ?> ▾
                </button>
                <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-1 w-40 bg-white rounded-xl border
                            border-gray-200 shadow-lg py-1 z-10">
                    <?php foreach (['todo' => 'To Do', 'in_progress' => 'In Progress', 'in_review' => 'In Review', 'blocked' => 'Blocked', 'done' => 'Done'] as $sv => $sl): ?>
                        <button onclick="changeStatus('<?= $sv ?>')" @click="open = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <?= $sl ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mb-5">
            <div>
                <p class="text-xs text-gray-400 mb-1">Priority</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                             <?= priorityBadge($task['priority']) ?>">
                    <?= ucfirst($task['priority']) ?>
                </span>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Assignee</p>
                <p class="text-gray-800 font-medium">
                    <?= e($task['assignee_name'] ?? '—') ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Due date</p>
                <p class="font-medium <?= $is_overdue ? 'text-red-500' : 'text-gray-800' ?>">
                    <?= $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : '—' ?>
                </p>
            </div>
        </div>

        <?php if ($task['description']): ?>
            <div class="text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                <?= nl2br(e($task['description'])) ?>
            </div>
        <?php endif; ?>

        <p class="text-xs text-gray-400 mt-4">
            Created by
            <?= e($task['creator_name']) ?>
            ·
            <?= date('M j, Y', strtotime($task['created_at'])) ?>
        </p>
    </div>
</div>

<script>
    function changeStatus(status) {
        fetch('./?page=task/update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=<?= $id ?>&status=' + status + '&csrf_token=<?= csrfToken() ?>',
        }).then(() => window.location.reload());
    }
</script>