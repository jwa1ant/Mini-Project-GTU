<?php

$activePage = $activePage ?? '';
$user = currentUser();
$initial = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
?>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <a href="<?= APP_URL ?>/dashboard.php">Watch<span>My</span>Wallet</a><br>
    <span class="role-badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
  </div>

  <div class="sidebar-nav">
    <div class="nav-section-title">Main</div>
    <a href="<?= APP_URL ?>/dashboard.php"
       class="nav-link <?= $activePage==='dashboard'?'active':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <a href="<?= APP_URL ?>/add_expense.php"
       class="nav-link <?= $activePage==='add_expense'?'active':'' ?>">
      <span class="nav-icon">➕</span> Add Expense
    </a>
    <a href="<?= APP_URL ?>/reports.php"
       class="nav-link <?= $activePage==='reports'?'active':'' ?>">
      <span class="nav-icon">📈</span> Reports
    </a>
    <a href="<?= APP_URL ?>/budget.php"
       class="nav-link <?= $activePage==='budget'?'active':'' ?>">
      <span class="nav-icon">🎯</span> Budget
    </a>

    <?php if (isAdmin()): ?>
    <div class="nav-section-title" style="margin-top:.5rem;">Admin</div>
    <a href="<?= APP_URL ?>/admin/users.php"
       class="nav-link <?= $activePage==='admin_users'?'active':'' ?>">
      <span class="nav-icon">👥</span> Manage Users
    </a>
    <a href="<?= APP_URL ?>/admin/all_expenses.php"
       class="nav-link <?= $activePage==='admin_expenses'?'active':'' ?>">
      <span class="nav-icon">🗂️</span> All Expenses
    </a>
    <?php endif; ?>

    <div class="nav-section-title" style="margin-top:.5rem;">Account</div>
    <a href="<?= APP_URL ?>/profile.php"
       class="nav-link <?= $activePage==='profile'?'active':'' ?>">
      <span class="nav-icon">👤</span> Profile
    </a>
    <a href="<?= APP_URL ?>/logout.php" class="nav-link">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar-sm"><?= $initial ?></div>
      <div>
        <div style="font-weight:600;font-size:.85rem;color:var(--beige);">
          <?= htmlspecialchars($user['full_name']) ?>
        </div>
        <div style="font-size:.75rem;color:rgba(242,232,216,.45);">
          @<?= htmlspecialchars($user['username']) ?>
        </div>
      </div>
    </div>
  </div>
</nav>