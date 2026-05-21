CREATE DATABASE IF NOT EXISTS watchmywallet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE watchmywallet;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    username VARCHAR(80) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    monthly_budget DECIMAL(12,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    icon VARCHAR(30) DEFAULT '💰',
    color VARCHAR(20) DEFAULT '#1f1f1f'
);

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255),
    expense_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    budget_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY unique_user_cat_month (user_id, category_id, month_year),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

INSERT INTO categories (name, icon, color) VALUES
('Food & Dining',    '🍽️',  '#e07b54'),
('Transport',        '🚗',  '#5b8dee'),
('Shopping',         '🛍️',  '#e05490'),
('Entertainment',    '🎬',  '#9b59b6'),
('Health & Medical', '🏥',  '#2ecc71'),
('Utilities',        '💡',  '#f1c40f'),
('Education',        '📚',  '#3498db'),
('Travel',           '✈️',  '#1abc9c'),
('Rent/Housing',     '🏠',  '#e74c3c'),
('Other',            '💼',  '#95a5a6');

INSERT INTO users (full_name, email, username, password, role, monthly_budget) VALUES
('Administrator', 'admin@watchmywallet.com', 'admin',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 50000.00);