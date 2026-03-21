<?php
// tasks/delete.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
verifyCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id) dbRun('DELETE FROM tasks WHERE id = ?', [$id]);
flash('success', 'Task deleted.');
redirect('/tasktrack/tasks/index.php');