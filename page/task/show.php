<?php
$page_title = 'Task Detail';
$id = (int) ($_GET['id'] ?? 0);
$task = getTaskById($id);

if (!$task) {
    redirect('./?page=task/list');
}

$page_title = e($task['title']);
$me = loggedInUser();
$comments = getTaskComments($id);
$task_tags = getTaskTags($id);
$is_overdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] !== 'done';

// ---- Add comment ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    verifyCsrf();
    $body = trim($_POST['comment']);
    if ($body) {
        runQuery(
            'INSERT INTO comments (task_id, user_id, body) VALUES (?, ?, ?)',
            [$id, $me['id'], $body]
        );
    }
    redirect('./?page=task/show&id=' . $id);
}

// ---- Delete comment ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    verifyCsrf();
    $cid = (int) $_POST['delete_comment_id'];
    $comment = getRow('SELECT * FROM comments WHERE id = ?', [$cid]);
    if ($comment && ($comment['user_id'] == $me['id'] || isAdmin())) {
        runQuery('DELETE FROM comments WHERE id = ?', [$cid]);
    }
    redirect('./?page=task/show&id=' . $id);
}
?>

<div class="max-w-3xl">

    <!-- Header -->
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

    <!-- Task info -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
        <div class="flex items-start justify-between gap-4 mb-4">
            <h1 class="text-xl font-semibold text-gray-900 leading-snug">
                <?= e($task['title']) ?>
            </h1>

            <!-- Status dropdown -->
            <div x-data="{ open: false }" class="relative flex-shrink-0">
                <button @click="open = !open" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full
                               text-sm font-medium cursor-pointer <?= statusBadge($task['status']) ?>">
                    <?= statusLabel($task['status']) ?> ▾
                </button>
                <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-1 w-40 bg-white rounded-xl border
                            border-gray-200 shadow-lg py-1 z-10">
                    <?php foreach (['todo' => 'To Do', 'in_progress' => 'In Progress', 'in_review' => 'In Review', 'blocked' => 'Blocked', 'done' => 'Done'] as $sv => $sl): ?>
                        <button onclick="changeStatus('<?= $sv ?>')" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50
                                   <?= $task['status'] === $sv ? 'font-medium text-indigo-600' : '' ?>">
                            <?= $sl ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Task meta -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-5">
            <div>
                <p class="text-xs text-gray-400 mb-1">Priority</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                             <?= priorityBadge($task['priority']) ?>">
                    <?= ucfirst($task['priority']) ?>
                </span>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Assignee</p>
                <p class="text-gray-800 font-medium"><?= e($task['assignee_name'] ?? '—') ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Project</p>
                <p class="text-gray-800 font-medium"><?= e($task['project_name'] ?? '—') ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Due date</p>
                <p class="font-medium <?= $is_overdue ? 'text-red-500' : 'text-gray-800' ?>">
                    <?= $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : '—' ?>
                </p>
            </div>
        </div>

        <!-- Description -->
        <?php if ($task['description']): ?>
            <div class="text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                <?= nl2br(e($task['description'])) ?>
            </div>
        <?php endif; ?>

        <!-- Tags -->
        <?php if ($task_tags): ?>
            <div class="flex flex-wrap gap-1.5 mt-4">
                <?php foreach ($task_tags as $tag): ?>
                    <span class="px-2 py-0.5 rounded text-xs font-medium"
                        style="background-color:<?= e($tag['color']) ?>22;color:<?= e($tag['color']) ?>">
                        <?= e($tag['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="text-xs text-gray-400 mt-4">
            Created by <?= e($task['creator_name']) ?>
            · <?= date('M j, Y H:i', strtotime($task['created_at'])) ?>
        </p>
    </div>

    <!-- Comments -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">
            Comments (<?= count($comments) ?>)
        </h2>

        <!-- Comment list -->
        <div class="space-y-4 mb-5">
            <?php if (empty($comments)): ?>
                <p class="text-sm text-gray-400">No comments yet.</p>
            <?php endif; ?>

            <?php foreach ($comments as $c): ?>
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center
                            text-indigo-700 text-xs font-bold flex-shrink-0">
                        <?= e(getInitials($c['user_name'])) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-medium text-gray-800"><?= e($c['user_name']) ?></span>
                            <span class="text-xs text-gray-400"><?= timeAgo($c['created_at']) ?></span>
                        </div>
                        <p class="text-sm text-gray-600"><?= nl2br(e($c['body'])) ?></p>

                        <?php if ($c['user_id'] == $me['id'] || isAdmin()): ?>
                            <form method="POST" action="./?page=task/show&id=<?= $id ?>" class="mt-1">
                                <?= csrfField() ?>
                                <input type="hidden" name="delete_comment_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">
                                    Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add comment -->
        <form method="POST" action="./?page=task/show&id=<?= $id ?>" class="flex gap-3">
            <?= csrfField() ?>
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center
                        text-indigo-700 text-xs font-bold flex-shrink-0">
                <?= e(getInitials($me['name'])) ?>
            </div>
            <div class="flex-1 flex gap-2">
                <input type="text" name="comment" placeholder="Add a comment..." required class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               text-sm font-medium rounded-lg">
                    Post
                </button>
            </div>
        </form>
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