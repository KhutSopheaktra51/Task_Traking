<?php

// ---- Get all tasks (admin/manager see all, staff see own) ----
function getAllTasks($user_id = null)
{
    if ($user_id) {
        return getRows(
            'SELECT t.*, u.name AS assignee_name, p.name AS project_name
             FROM tasks t
             LEFT JOIN users u ON t.assignee_id = u.id
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.assignee_id = ?
             ORDER BY t.created_at DESC',
            [$user_id]
        );
    }
    return getRows(
        'SELECT t.*, u.name AS assignee_name, p.name AS project_name
         FROM tasks t
         LEFT JOIN users u ON t.assignee_id = u.id
         LEFT JOIN projects p ON t.project_id = p.id
         ORDER BY t.created_at DESC'
    );
}

// ---- Get one task by ID ----
function getTaskById($id)
{
    return getRow(
        'SELECT t.*,
                u.name AS assignee_name,
                c.name AS creator_name,
                p.name AS project_name
         FROM tasks t
         LEFT JOIN users u ON t.assignee_id = u.id
         LEFT JOIN users c ON t.created_by = c.id
         LEFT JOIN projects p ON t.project_id = p.id
         WHERE t.id = ?',
        [$id]
    );
}

// ---- Create new task ----
function createTask($data)
{
    return runQuery(
        'INSERT INTO tasks
         (title, description, status, priority, assignee_id, created_by, project_id, due_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $data['title'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['assignee_id'],
            $data['created_by'],
            $data['project_id'],
            $data['due_date'],
        ]
    );
}

// ---- Update task ----
function updateTask($id, $data)
{
    return runQuery(
        'UPDATE tasks
         SET title=?, description=?, status=?, priority=?,
             assignee_id=?, project_id=?, due_date=?
         WHERE id=?',
        [
            $data['title'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['assignee_id'],
            $data['project_id'],
            $data['due_date'],
            $id,
        ]
    );
}

// ---- Update task status only ----
function updateTaskStatus($id, $status)
{
    return runQuery(
        'UPDATE tasks SET status=? WHERE id=?',
        [$status, $id]
    );
}

// ---- Delete task ----
function deleteTask($id)
{
    return runQuery('DELETE FROM tasks WHERE id=?', [$id]);
}

// ---- Get comments for a task ----
function getTaskComments($task_id)
{
    return getRows(
        'SELECT cm.*, u.name AS user_name
         FROM comments cm
         JOIN users u ON cm.user_id = u.id
         WHERE cm.task_id = ?
         ORDER BY cm.created_at ASC',
        [$task_id]
    );
}

// ---- Get tags for a task ----
function getTaskTags($task_id)
{
    return getRows(
        'SELECT tg.*
         FROM tags tg
         JOIN task_tag tt ON tg.id = tt.tag_id
         WHERE tt.task_id = ?',
        [$task_id]
    );
}

// ---- Save tags for a task ----
function saveTaskTags($task_id, $tag_ids)
{
    runQuery('DELETE FROM task_tag WHERE task_id = ?', [$task_id]);
    foreach ($tag_ids as $tag_id) {
        runQuery(
            'INSERT IGNORE INTO task_tag (task_id, tag_id) VALUES (?, ?)',
            [$task_id, (int) $tag_id]
        );
    }
}

// ---- Get all projects ----
function getAllProjects()
{
    return getRows('SELECT * FROM projects ORDER BY name');
}

// ---- Get all tags ----
function getAllTags()
{
    return getRows('SELECT * FROM tags ORDER BY name');
}