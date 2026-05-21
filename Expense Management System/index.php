<?php

require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $result = loginUser(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            redirect($result['role'] === 'admin' ? APP_URL . '/admin/users.php' : APP_URL . '/dashboard.php');
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
  <title>Login — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">

  <div class="auth-brand">
    <div class="brand-deco d1"></div>
    <div class="brand-deco d2"></div>
    <div class="brand-logo">Watch<span>My</span>Wallet</div>
    <p class="brand-tagline">Smart expense tracking for data-driven minds</p>
    <div style="margin-top:3rem;color:rgba(242,232,216,.5);font-size:.85rem;text-align:center;position:relative;z-index:1;">
      <div style="font-size:2.5rem;margin-bottom:1rem;">📊</div>
      Visualize spending · Track budgets<br>Make better financial decisions
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Welcome back</h2>
      <p class="sub">Sign in to your WatchMyWallet account</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">Account created successfully! Please sign in.</div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $token ?>">

        <div class="form-group">
          <label>Username or Email</label>
          <input type="text" name="username" placeholder="Enter your username or email"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:.4rem;">
          Sign In →
        </button>
      </form>

      <div class="divider"></div>
      <p class="text-center" style="font-size:.9rem;color:var(--ink-faint);">
        New here?
        <a href="signup.php" class="text-link">Create an account</a>
      </p>

      <div class="divider"></div>
      <p style="font-size:.78rem;color:var(--ink-faint);text-align:center;">
        Default admin → username: <strong>admin</strong> &nbsp;|&nbsp; password: <strong>admin@1234</strong>
      </p>
    </div>
  </div>

</div>
</body>
</html>