<?php

require_once 'includes/auth.php';
require_once 'includes/expenses.php';
requireLogin();

$userId = $_SESSION['user_id'];
$db     = getDB();
$user   = currentUser();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        if (strlen($fullName) < 2) {
            $error = 'Full name too short.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email.';
        } else {

            $stmt = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error = 'Email already in use by another account.';
            } else {
                $db->prepare("UPDATE users SET full_name=?, email=? WHERE id=?")->execute([$fullName, $email, $userId]);
                $_SESSION['full_name'] = $fullName;
                $success = 'Profile updated!';
                $user = currentUser();
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $userId]);
            $success = 'Password changed successfully!';
        }
    }
}
$activePage = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-title"><h1>My Profile</h1><p>Manage your account settings</p></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="max-width:600px;display:grid;gap:1.5rem;">

      <div class="card">
        <div class="card-header"><span class="card-title">👤 Personal Information</span></div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
              <label>Username</label>
              <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.6;">
            </div>
          </div>
          <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?= ucfirst($user['role']) ?>" disabled style="opacity:.6;">
          </div>
          <div class="form-group">
            <label>Member Since</label>
            <input type="text" value="<?= date('d M Y', strtotime($user['created_at'])) ?>" disabled style="opacity:.6;">
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">🔒 Change Password</span></div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
      </div>

    </div>
  </main>
</div>
</body>
</html>