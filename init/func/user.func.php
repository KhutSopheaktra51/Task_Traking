<?php

// ---- Get logged in user from session ----
function loggedInUser()
{
    return $_SESSION['user'] ?? null;
}

// ---- Check if user is logged in ----
function isLoggedIn()
{
    return !empty($_SESSION['user']);
}

// ---- Check if user is admin ----
function isAdmin()
{
    $user = loggedInUser();
    return ($user['role'] ?? '') === 'admin';
}

// ---- Check if user is manager ----
function isManager()
{
    $user = loggedInUser();
    return ($user['role'] ?? '') === 'manager';
}

// ---- Check if user is manager or admin ----
function isManagerOrAdmin()
{
    $user = loggedInUser();
    return in_array($user['role'] ?? '', ['admin', 'manager']);
}

// ---- Get all users ----
function getAllUsers()
{
    return getRows(
        'SELECT u.*, t.name AS team_name
         FROM users u
         LEFT JOIN teams t ON u.team_id = t.id
         ORDER BY u.name'
    );
}

// ---- Get user by ID ----
function getUserById($id)
{
    return getRow('SELECT * FROM users WHERE id = ?', [$id]);
}

// ---- Create new user ----
function createUser($name, $email, $password, $role, $team_id)
{
    return runQuery(
        'INSERT INTO users (name, email, password, role, team_id)
         VALUES (?, ?, ?, ?, ?)',
        [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $team_id]
    );
}

// ---- Update user ----
function updateUser($id, $name, $email, $role, $team_id, $password = null)
{
    if ($password) {
        return runQuery(
            'UPDATE users SET name=?, email=?, role=?, team_id=?, password=? WHERE id=?',
            [$name, $email, $role, $team_id, password_hash($password, PASSWORD_BCRYPT), $id]
        );
    }
    return runQuery(
        'UPDATE users SET name=?, email=?, role=?, team_id=? WHERE id=?',
        [$name, $email, $role, $team_id, $id]
    );
}

// ---- Delete user ----
function deleteUser($id)
{
    return runQuery('DELETE FROM users WHERE id = ?', [$id]);
}

// ---- Get all teams ----
function getAllTeams()
{
    return getRows('SELECT * FROM teams ORDER BY name');
}