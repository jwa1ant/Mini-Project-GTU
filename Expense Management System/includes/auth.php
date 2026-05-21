<?php

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/index.php');
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect(APP_URL . '/dashboard.php');
    }
}

function currentUser(): array {
    if (!isLoggedIn()) return [];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: [];
}

function loginUser(string $username, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    session_regenerate_id(true);

    return ['success' => true, 'role' => $user['role']];
}

function logoutUser(): void {
    session_unset();
    session_destroy();
    redirect(APP_URL . '/index.php');
}

function registerUser(array $data): array {
    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$data['email'], $data['username']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email or username already exists.'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, username, password, monthly_budget) VALUES (?,?,?,?,?)");
    $stmt->execute([
        trim($data['full_name']),
        strtolower(trim($data['email'])),
        strtolower(trim($data['username'])),
        $hash,
        floatval($data['budget'] ?? 0)
    ]);

    return ['success' => true];
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
