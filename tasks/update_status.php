<?php
// tasks/update_status.php  — AJAX endpoint
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['todo', 'in_progress', 'in_review', 'blocked', 'done'];

if ($id && in_array($status, $allowed)) {
    dbRun('UPDATE tasks SET status = ? WHERE id = ?', [$status, $id]);
    echo json_encode(['success' => true]);
} else {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid input']);
}