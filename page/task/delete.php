<?php
verifyCsrf();
$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    deleteTask($id);
    setFlash('success', 'Task deleted.');
}
redirect('./?page=task/list');