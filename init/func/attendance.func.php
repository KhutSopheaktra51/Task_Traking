<?php

// ---- Get today's attendance for a user ----
function getTodayRecord($user_id)
{
    return getRow(
        'SELECT * FROM attendance_records
         WHERE user_id = ? AND date = ?',
        [$user_id, date('Y-m-d')]
    );
}

// ---- Check in ----
function doCheckIn($user_id)
{
    $today = date('Y-m-d');
    $time = date('H:i:s');
    $status = (date('H:i') > '09:00') ? 'late' : 'present';

    return runQuery(
        'INSERT INTO attendance_records (user_id, date, check_in, status)
         VALUES (?, ?, ?, ?)',
        [$user_id, $today, $time, $status]
    );
}

// ---- Check out ----
function doCheckOut($record_id)
{
    return runQuery(
        'UPDATE attendance_records SET check_out = ? WHERE id = ?',
        [date('H:i:s'), $record_id]
    );
}

// ---- Get attendance history for a user ----
function getUserAttendance($user_id)
{
    return getRows(
        'SELECT * FROM attendance_records
         WHERE user_id = ?
         ORDER BY date DESC',
        [$user_id]
    );
}

// ---- Get all attendance (for admin) ----
function getAllAttendance($filter_date = null, $filter_user = null)
{
    $where = '1=1';
    $params = [];

    if ($filter_date) {
        $where .= ' AND ar.date = ?';
        $params[] = $filter_date;
    }

    if ($filter_user) {
        $where .= ' AND ar.user_id = ?';
        $params[] = $filter_user;
    }

    return getRows(
        "SELECT ar.*, u.name AS user_name
         FROM attendance_records ar
         JOIN users u ON ar.user_id = u.id
         WHERE $where
         ORDER BY ar.date DESC",
        $params
    );
}

// ---- Get this week's days with attendance records ----
function getWeekDays($user_id)
{
    $week_start = strtotime('monday this week');
    $days = [];

    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', $week_start + $i * 86400);
        $days[] = [
            'date' => $date,
            'label' => date('D', $week_start + $i * 86400),
            'day' => date('j', $week_start + $i * 86400),
            'record' => getRow(
                'SELECT * FROM attendance_records
                 WHERE user_id = ? AND date = ?',
                [$user_id, $date]
            ),
        ];
    }

    return $days;
}