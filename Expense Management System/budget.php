<?php

require_once 'includes/auth.php';
require_once 'includes/expenses.php';
requireLogin();

$userId  = $_SESSION['user_id'];
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $newBudget = floatval($_POST['monthly_budget'] ?? 0);
    if ($newBudget < 0) {
        $error = 'Budget cannot be negative.';
    } else {
        updateUserBudget($userId, $newBudget);
        $success = 'Monthly budget updated!';
    }
}

$budget     = getUserBudget($userId);
$monthSpend = getCurrentMonthSpend($userId);
$remaining  = $budget - $monthSpend;
$budgetPct  = $budget > 0 ? min(round($monthSpend / $budget * 100, 1), 999) : 0;
$catTotals  = getTotalByCategory($userId, date('Y-m-01'), date('Y-m-t'));
$activePage = 'budget';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budget — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-title">
        <h1>Budget</h1>
        <p>Set and track your monthly budget — <?= date('F Y') ?></p>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="max-width:720px;">

      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
          <span class="card-title">🎯 Set Monthly Budget</span>
        </div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="form-row">
            <div class="form-group">
              <label>Monthly Budget Amount (₹)</label>
              <input type="number" name="monthly_budget" min="0" step="0.01"
                     placeholder="e.g. 20000"
                     value="<?= $budget > 0 ? number_format($budget, 2, '.', '') : '' ?>">
            </div>
            <div style="display:flex;align-items:flex-end;">
              <button type="submit" class="btn btn-primary">Update Budget</button>
            </div>
          </div>
        </form>
      </div>

      <?php if ($budget > 0): ?>
      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card">
          <div class="stat-icon">🎯</div>
          <div class="stat-label">Monthly Budget</div>
          <div class="stat-value">₹<?= number_format($budget,2) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💸</div>
          <div class="stat-label">Spent This Month</div>
          <div class="stat-value">₹<?= number_format($monthSpend,2) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><?= $remaining >= 0 ? '✅' : '🚨' ?></div>
          <div class="stat-label"><?= $remaining >= 0 ? 'Remaining' : 'Over Budget' ?></div>
          <div class="stat-value" style="color:<?= $remaining>=0?'var(--success)':'var(--danger)' ?>">
            ₹<?= number_format(abs($remaining),2) ?>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
          <span class="card-title">📊 Budget Progress — <?= date('F Y') ?></span>
          <span style="font-weight:700;color:<?= $budgetPct>=100?'var(--danger)':($budgetPct>=80?'var(--warn)':'var(--success)') ?>">
            <?= $budgetPct ?>%
          </span>
        </div>
        <div class="budget-bar-track" style="height:20px;border-radius:99px;">
          <div class="budget-bar-fill <?= $budgetPct>=100?'over':($budgetPct>=80?'warn':'') ?>"
               style="width:<?= min($budgetPct,100) ?>%;height:100%;border-radius:99px;">
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:.6rem;font-size:.82rem;color:var(--ink-faint);">
          <span>₹0</span>
          <span style="font-weight:600;">Spent: ₹<?= number_format($monthSpend,2) ?></span>
          <span>Budget: ₹<?= number_format($budget,2) ?></span>
        </div>

        <?php if ($budgetPct >= 100): ?>
          <div class="alert alert-error" style="margin-top:1rem;">
            🚨 You've exceeded your monthly budget by ₹<?= number_format(abs($remaining),2) ?>!
          </div>
        <?php elseif ($budgetPct >= 80): ?>
          <div class="alert alert-warn" style="margin-top:1rem;">
            ⚠️ You've used <?= $budgetPct ?>% of your budget. Only ₹<?= number_format($remaining,2) ?> left.
          </div>
        <?php else: ?>
          <div class="alert alert-success" style="margin-top:1rem;">
            ✅ You're on track! ₹<?= number_format($remaining,2) ?> remaining this month.
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($catTotals)): ?>
      <div class="card">
        <div class="card-header">
          <span class="card-title">🏷️ Category Spending — <?= date('F Y') ?></span>
        </div>
        <?php foreach ($catTotals as $row): ?>
          <?php $pct = $budget>0?min(round($row['total']/$budget*100,1),100):0; ?>
          <div style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
              <span class="cat-badge" style="color:<?= htmlspecialchars($row['color']) ?>">
                <?= $row['icon'] ?> <?= htmlspecialchars($row['name']) ?>
              </span>
              <span style="font-weight:700;font-family:var(--ff-display);font-size:.95rem;">
                ₹<?= number_format($row['total'],2) ?> <span style="font-weight:400;font-size:.8rem;color:var(--ink-faint);">(<?= $pct ?>% of budget)</span>
              </span>
            </div>
            <div class="budget-bar-track">
              <div class="budget-bar-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($row['color']) ?>;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
        <div class="card">
          <div class="empty-state">
            <div class="es-icon">🎯</div>
            <p>No budget set yet. Enter an amount above to start tracking!</p>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>
</body>
</html>