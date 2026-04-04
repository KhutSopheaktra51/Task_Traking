<?php

// ---- Get my leave requests ----
function getMyLeaves($user_id)
{
    return getRows(
        'SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC',
        [$user_id]
    );
}

// ---- Get all pending leaves ----
function getPendingLeaves()
{
    return getRows(
        'SELECT lr.*, u.name AS user_name
         FROM leave_requests lr
         JOIN users u ON lr.user_id = u.id
         WHERE lr.status = "pending"
         ORDER BY lr.start_date ASC'
    );
}

// ---- Submit leave ----
function createLeave($user_id, $type, $start, $end, $reason)
{
    return runQuery(
        'INSERT INTO leave_requests (user_id, type, start_date, end_date, reason)
         VALUES (?, ?, ?, ?, ?)',
        [$user_id, $type, $start, $end, $reason]
    );
}

// ---- Approve or reject leave ----
function updateLeaveStatus($id, $status, $approved_by)
{
    return runQuery(
        'UPDATE leave_requests SET status = ?, approved_by = ? WHERE id = ?',
        [$status, $approved_by, $id]
    );
}