<?php

// ---- Database settings ----
$db_host = '127.0.0.1';
$db_name = 'tasktrack';
$db_user = 'root';
$db_pass = '';

// ---- Connect to database (only connects once) ----
function db()
{
    global $db_host, $db_name, $db_user, $db_pass;
    static $conn = null;

    if ($conn === null) {
        $conn = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    return $conn;
}

// ---- Get one row ----
function getRow($sql, $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

// ---- Get multiple rows ----
function getRows($sql, $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ---- Run insert / update / delete ----
function runQuery($sql, $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $last_id = (int) db()->lastInsertId();
    return $last_id > 0 ? $last_id : $stmt->rowCount();
}