USE tasktrack;

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','staff') DEFAULT 'staff',
    team_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    team_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#6366f1'
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('todo','in_progress','in_review','blocked','done') DEFAULT 'todo',
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    assignee_id INT,
    created_by INT NOT NULL,
    project_id INT,
    due_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS task_tag (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('present','late','absent','half_day') DEFAULT 'present',
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('sick','annual','unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO teams (name, description) VALUES
('Engineering', 'Frontend and backend developers'),
('Design', 'UI/UX designers'),
('Marketing', 'Marketing and growth team'),
('Operations', 'Operations and HR');

INSERT INTO users (name, email, password, role, team_id) VALUES
('Admin User', 'admin@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 4),
('Emma Manager', 'emma@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1),
('James Manager', 'james@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 2),
('Alice Smith', 'alice@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1),
('Bob Johnson', 'bob@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1),
('Carol White', 'carol@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 2),
('David Brown', 'david@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 3),
('Eve Williams', 'eve@tasktrack.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 3);

INSERT INTO projects (name, description, team_id) VALUES
('Website Redesign', 'Full redesign of the company website', 2),
('Mobile App v2', 'Second version of the mobile application', 1),
('API Integration', 'Third-party API integrations', 1),
('Q4 Campaign', 'End-of-year marketing push', 3);

INSERT INTO tags (name, color) VALUES
('bug', '#ef4444'),
('feature', '#6366f1'),
('design', '#ec4899'),
('backend', '#f59e0b'),
('frontend', '#10b981'),
('urgent', '#dc2626'),
('review', '#8b5cf6');

INSERT INTO tasks (title, description, status, priority, assignee_id, created_by, project_id, due_date) VALUES
('Set up CI/CD pipeline', 'Automate deployments', 'done', 'high', 4, 1, 3, DATE_SUB(CURDATE(),INTERVAL 5 DAY)),
('Design landing page mockups', 'New homepage design', 'in_review', 'high', 6, 1, 1, DATE_ADD(CURDATE(),INTERVAL 2 DAY)),
('Fix login timeout bug', 'Session expires early', 'in_progress', 'urgent', 4, 1, 3, CURDATE()),
('Write API documentation', 'Document all endpoints', 'todo', 'medium', 5, 1, 3, DATE_ADD(CURDATE(),INTERVAL 7 DAY)),
('Implement push notifications', 'Mobile push support', 'todo', 'medium', 4, 1, 2, DATE_ADD(CURDATE(),INTERVAL 10 DAY)),
('Refactor database queries', 'Optimize slow queries', 'in_progress', 'medium', 5, 1, 3, DATE_ADD(CURDATE(),INTERVAL 3 DAY)),
('Update dependencies', 'Upgrade all packages', 'done', 'low', 4, 1, 2, DATE_SUB(CURDATE(),INTERVAL 2 DAY)),
('Create onboarding flow', 'New user onboarding', 'todo', 'high', 6, 1, 1, DATE_ADD(CURDATE(),INTERVAL 14 DAY)),
('Mobile navigation redesign', 'Redesign mobile nav', 'in_review', 'medium', 6, 1, 2, DATE_ADD(CURDATE(),INTERVAL 5 DAY)),
('Add dark mode support', 'System-wide dark mode', 'blocked', 'low', 5, 1, 1, DATE_ADD(CURDATE(),INTERVAL 20 DAY)),
('Fix payment gateway', 'Payment 500 errors', 'in_progress', 'urgent', 4, 1, 3, DATE_ADD(CURDATE(),INTERVAL 1 DAY)),
('Write unit tests auth', 'Cover auth module', 'todo', 'medium', 5, 1, 3, DATE_ADD(CURDATE(),INTERVAL 8 DAY)),
('Optimize image compression', 'Reduce image sizes', 'todo', 'low', 6, 1, 1, DATE_ADD(CURDATE(),INTERVAL 12 DAY)),
('Email template redesign', 'New email templates', 'in_review', 'medium', 6, 1, 1, DATE_ADD(CURDATE(),INTERVAL 4 DAY)),
('Setup staging environment', 'Staging server setup', 'done', 'high', 4, 1, 3, DATE_SUB(CURDATE(),INTERVAL 10 DAY)),
('Implement search', 'Global search feature', 'todo', 'medium', 5, 1, 2, DATE_ADD(CURDATE(),INTERVAL 15 DAY)),
('A/B test homepage CTA', 'Test button variants', 'in_progress', 'medium', 7, 1, 4, DATE_ADD(CURDATE(),INTERVAL 6 DAY)),
('GDPR compliance audit', 'Audit data handling', 'blocked', 'urgent', 4, 1, 4, DATE_ADD(CURDATE(),INTERVAL 3 DAY)),
('Export reports feature', 'CSV/PDF export', 'todo', 'medium', 5, 1, 2, DATE_ADD(CURDATE(),INTERVAL 9 DAY)),
('Migrate to new hosting', 'Move to cloud hosting', 'todo', 'high', 4, 1, 3, DATE_ADD(CURDATE(),INTERVAL 30 DAY));

INSERT INTO task_tag (task_id, tag_id) VALUES
(1,4),(1,2),(2,3),(2,5),(3,1),(3,6),(4,4),(4,2),(5,2),(5,5),
(6,4),(7,2),(8,2),(8,5),(9,3),(10,5),(10,2),(11,1),(11,6),(12,4),
(13,5),(14,3),(15,4),(16,2),(17,5),(18,6),(19,2),(20,4);