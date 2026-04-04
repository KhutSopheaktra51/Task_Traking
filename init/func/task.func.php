<?php

// ---- Get all tasks ----
function getAllTasks($user_id = null)
{
    if ($user_id) {
        return getRows(
            'SELECT t.*, u.name AS assignee_name
             FROM tasks t
             LEFT JOIN users u ON t.assignee_id = u.id
             WHERE t.assignee_id = ?
             ORDER BY t.created_at DESC',
            [$user_id]
        );
    }
    return getRows(
        'SELECT t.*, u.name AS assignee_name
         FROM tasks t
         LEFT JOIN users u ON t.assignee_id = u.id
         ORDER BY t.created_at DESC'
    );
}

// ---- Get one task ----
function getTaskById($id)
{
    return getRow(
        'SELECT t.*, u.name AS assignee_name, c.name AS creator_name
         FROM tasks t
         LEFT JOIN users u ON t.assignee_id = u.id
         LEFT JOIN users c ON t.created_by = c.id
         WHERE t.id = ?',
        [$id]
    );
}

// ---- Create task ----
function createTask($data)
{
    return runQuery(
        'INSERT INTO tasks (title, description, status, priority, assignee_id, created_by, due_date)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $data['title'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['assignee_id'],
            $data['created_by'],
            $data['due_date'],
        ]
    );
}

// ---- Update task ----
function updateTask($id, $data)
{
    return runQuery(
        'UPDATE tasks SET title=?, description=?, status=?, priority=?, assignee_id=?, due_date=?
         WHERE id=?',
        [
            $data['title'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['assignee_id'],
            $data['due_date'],
            $id,
        ]
    );
}

// ---- Update status only ----
function updateTaskStatus($id, $status)
{
    return runQuery('UPDATE tasks SET status=? WHERE id=?', [$status, $id]);
}

// ---- Delete task ----
function deleteTask($id)
{
    return runQuery('DELETE FROM tasks WHERE id=?', [$id]);
}