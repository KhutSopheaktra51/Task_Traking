<?php
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$me = loggedInUser();

if ($id && in_array($action, ['approved', 'rejected'])) {
    updateLeaveStatus($id, $action, $me['id']);
    setFlash('success', 'Leave request ' . $action . '.');
}

redirect('./?page=leave/manage');