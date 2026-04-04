CREATE DATABASE IF NOT EXISTS tasktrack
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tasktrack;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','manager','staff') DEFAULT 'staff',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    status      ENUM('todo','in_progress','in_review','blocked','done') DEFAULT 'todo',
    priority    ENUM('low','medium','high','urgent') DEFAULT 'medium',
    assignee_id INT,
    created_by  INT NOT NULL,
    due_date    DATE,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance_records (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    date       DATE NOT NULL,
    check_in   TIME,
    check_out  TIME,
    status     ENUM('present','late','absent','half_day') DEFAULT 'present',
    note       TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS leave_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('sick','annual','unpaid') NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    reason      TEXT NOT NULL,
    status      ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Demo users (password = "password")
INSERT INTO users (name, email, password, role) VALUES
('Admin User',  'admin@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Emma Manager','emma@tasktrack.test',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Alice Smith', 'alice@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Bob Johnson', 'bob@tasktrack.test',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Carol White', 'carol@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Demo tasks
INSERT INTO tasks (title, description, status, priority, assignee_id, created_by, due_date) VALUES
('Setup project',        'Initial setup',         'done',        'high',   3, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
('Design login page',    'Create login UI',        'done',        'medium', 3, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
('Fix login bug',        'Session issue',          'in_progress', 'urgent', 4, 1, CURDATE()),
('Write API docs',       'Document endpoints',     'todo',        'medium', 4, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
('Build dashboard',      'KPI cards and charts',   'in_review',   'high',   3, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY)),
('Database optimize',    'Optimize slow queries',  'todo',        'medium', 5, 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
('Mobile responsive',    'Fix mobile layout',      'in_progress', 'medium', 5, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY)),
('User testing',         'Test all features',      'todo',        'low',    3, 1, DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
('Security audit',       'Check vulnerabilities',  'blocked',     'urgent', 4, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
('Deploy production',    'Move to live server',    'todo',        'high',   5, 1, DATE_ADD(CURDATE(), INTERVAL 14 DAY));