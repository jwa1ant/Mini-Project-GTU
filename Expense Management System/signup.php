<?php

require_once 'includes/auth.php';

if (isLoggedIn()) redirect(APP_URL . '/dashboard.php');

$error = '';
$success = false;
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch.';
    } elseif (strlen(trim($_POST['full_name'] ?? '')) < 2) {
        $error = 'Please enter your full name.';
    } elseif (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($_POST['username'] ?? '') < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $_POST['username'] ?? '')) {
        $error = 'Username may only contain letters, numbers, and underscores.';
    } elseif (strlen($_POST['password'] ?? '') < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser([
            'full_name' => $_POST['full_name'],
            'email'     => $_POST['email'],
            'username'  => $_POST['username'],
            'password'  => $_POST['password'],
            'budget'    => floatval($_POST['budget'] ?? 0),
        ]);
        if ($result['success']) {
            redirect(APP_URL . '/index.php?registered=1');
        } else {
            $error = $result['message'];
        }
    }
}
$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">

  <div class="auth-brand">
    <div class="brand-deco d1"></div>
    <div class="brand-deco d2"></div>
    <div class="brand-logo">Watch<span>My</span>Wallet</div>
    <p class="brand-tagline">Start tracking. Start saving.</p>
    <div style="margin-top:3rem;color:rgba(242,232,216,.5);font-size:.85rem;text-align:center;position:relative;z-index:1;line-height:2;">
      <div style="font-size:2.5rem;margin-bottom:.8rem;">💡</div>
      ✓ Pie & Line charts<br>
      ✓ Monthly summaries<br>
      ✓ Budget progress bars<br>
      ✓ Data Science insights
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Create account</h2>
      <p class="sub">Join WatchMyWallet — it's free</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $token ?>">

        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" placeholder="e.g. Priya Sharma"
                 value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="e.g. priya_s"
                   value="<?= htmlspecialchars($old['username'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@email.com"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min. 6 characters" required>
          </div>
          <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password" required>
          </div>
        </div>

        <div class="form-group">
          <label>Monthly Budget (₹) — optional</label>
          <input type="number" name="budget" placeholder="e.g. 15000" min="0" step="0.01"
                 value="<?= htmlspecialchars($old['budget'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:.4rem;">
          Create Account →
        </button>
      </form>

      <div class="divider"></div>
      <p class="text-center" style="font-size:.9rem;color:var(--ink-faint);">
        Already have an account?
        <a href="index.php" class="text-link">Sign in</a>
      </p>
    </div>
  </div>

</div>
</body>
</html>