<?php

require_once 'includes/auth.php';
require_once 'includes/expenses.php';
requireLogin();

$userId = $_SESSION['user_id'];
$cats   = getCategories();
$error  = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token error.';
    } elseif (floatval($_POST['amount'] ?? 0) <= 0) {
        $error = 'Amount must be greater than zero.';
    } elseif (empty($_POST['category_id'])) {
        $error = 'Please select a category.';
    } elseif (empty($_POST['expense_date'])) {
        $error = 'Please select a date.';
    } else {
        $ok = addExpense(
            $userId,
            (int)$_POST['category_id'],
            floatval($_POST['amount']),
            $_POST['description'] ?? '',
            $_POST['expense_date']
        );
        if ($ok) {
            $success = 'Expense added successfully!';
        } else {
            $error = 'Failed to add expense. Please try again.';
        }
    }
}
$activePage = 'add_expense';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Expense — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-title">
        <h1>Add Expense</h1>
        <p>Record a new expense entry</p>
      </div>
      <a href="dashboard.php" class="btn btn-outline btn-sm">← Back to Dashboard</a>
    </div>

    <div style="max-width:580px;">
      <div class="card">
        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($success) ?> &nbsp;
            <a href="add_expense.php" class="text-link">Add another</a> or
            <a href="dashboard.php" class="text-link">View dashboard</a>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="form-row">
            <div class="form-group">
              <label>Amount (₹) *</label>
              <input type="number" name="amount" placeholder="0.00" min="0.01" step="0.01"
                     value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Date *</label>
              <input type="date" name="expense_date"
                     value="<?= htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
              <option value="">— Select Category —</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>"
                  <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                  <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" placeholder="e.g. Lunch at Cafe Coffee Day"
                   value="<?= htmlspecialchars($_POST['description'] ?? '') ?>" maxlength="255">
          </div>

          <button type="submit" class="btn btn-primary">Save Expense</button>
        </form>
      </div>

      <div class="card" style="margin-top:1.2rem;background:var(--beige);">
        <div style="font-size:.85rem;color:var(--ink-faint);">
          💡 <strong>Data Science Tip:</strong> Consistent categorization helps generate more accurate
          insights in your pie charts and trend analysis. The more detailed your descriptions,
          the better your search filtering works!
        </div>
      </div>
    </div>

  </main>
</div>
</body>
</html>