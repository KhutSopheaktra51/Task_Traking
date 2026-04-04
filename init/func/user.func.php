<?php

// ---- Get logged in user ----
function loggedInUser()
{
    return $_SESSION['user'] ?? null;
}

// ---- Check if logged in ----
function isLoggedIn()
{
    return !empty($_SESSION['user']);
}

// ---- Check roles ----
function isAdmin()
{
    return (loggedInUser()['role'] ?? '') === 'admin';
}

function isManager()
{
    return (loggedInUser()['role'] ?? '') === 'manager';
}

function isManagerOrAdmin()
{
    return in_array(loggedInUser()['role'] ?? '', ['admin', 'manager']);
}

// ---- Get all users ----
function getAllUsers()
{
    return getRows('SELECT * FROM users ORDER BY name');
}

// ---- Get user by ID ----
function getUserById($id)
{
    return getRow('SELECT * FROM users WHERE id = ?', [$id]);
}

// ---- Create user ----
function createUser($name, $email, $password, $role)
{
    return runQuery(
        'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
        [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role]
    );
}

// ---- Update user ----
function updateUser($id, $name, $email, $role, $password = null)
{
    if ($password) {
        return runQuery(
            'UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?',
            [$name, $email, $role, password_hash($password, PASSWORD_BCRYPT), $id]
        );
    }
    return runQuery(
        'UPDATE users SET name=?, email=?, role=? WHERE id=?',
        [$name, $email, $role, $id]
    );
}

// ---- Delete user ----
function deleteUser($id)
{
    return runQuery('DELETE FROM users WHERE id = ?', [$id]);
}