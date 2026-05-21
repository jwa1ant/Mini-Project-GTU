<?php

require_once '../includes/auth.php';
require_once '../includes/expenses.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $db->prepare("DELETE FROM expenses WHERE id=?")->execute([(int)$_POST['delete_id']]);
    header("Location: all_expenses.php");
    exit;
}

$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';
$search   = $_GET['search']    ?? '';
$userId   = (int)($_GET['user_id'] ?? 0);

$params = [];
$sql = "SELECT e.*, c.name AS cat_name, c.icon AS cat_icon, c.color AS cat_color,
               u.full_name AS user_name, u.username
        FROM expenses e
        JOIN categories c ON e.category_id=c.id
        JOIN users u ON e.user_id=u.id
        WHERE 1=1";

if ($dateFrom) { $sql .= " AND e.expense_date >= ?"; $params[] = $dateFrom; }
if ($dateTo)   { $sql .= " AND e.expense_date <= ?"; $params[] = $dateTo; }
if ($search)   { $sql .= " AND e.description LIKE ?"; $params[] = "%$search%"; }
if ($userId)   { $sql .= " AND e.user_id = ?"; $params[] = $userId; }

$sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$users = $db->query("SELECT id, full_name, username FROM users ORDER BY full_name")->fetchAll();

$activePage = 'admin_expenses';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Expenses — WatchMyWallet Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-title">
        <h1>All Expenses</h1>
        <p><?= count($expenses) ?> records across all users</p>
      </div>
      <button onclick="window.print()" class="btn btn-outline btn-sm no-print">🖨️ Print</button>
    </div>

    <div class="print-header">
      <h2>WatchMyWallet — All Expenses Report</h2>
      <p>Generated: <?= date('d M Y, H:i') ?></p>
    </div>

    <div class="filter-bar no-print">
      <form method="GET" style="display:contents;">
        <div class="form-group">
          <label>User</label>
          <select name="user_id">
            <option value="">All Users</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>" <?= $userId==$u['id']?'selected':'' ?>>
                <?= htmlspecialchars($u['full_name']) ?> (@<?= $u['username'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>From</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="form-group">
          <label>To</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div class="form-group" style="flex:2;min-width:180px;">
          <label>🔍 Search</label>
          <input type="text" name="search" placeholder="Search description…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div style="display:flex;gap:.5rem;align-items:flex-end;">
          <button type="submit" class="btn btn-primary btn-sm">Apply</button>
          <a href="all_expenses.php" class="btn btn-outline btn-sm">Reset</a>
        </div>
      </form>
    </div>

    <div class="card">
      <?php if (empty($expenses)): ?>
        <div class="empty-state"><div class="es-icon">🗂️</div><p>No expenses found.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>#</th><th>Date</th><th>User</th><th>Description</th><th>Category</th><th>Amount</th><th class="no-print">Del</th></tr>
            </thead>
            <tbody>
              <?php $total = 0; foreach ($expenses as $i => $e): $total += $e['amount']; ?>
              <tr>
                <td style="color:var(--ink-faint);"><?= $i+1 ?></td>
                <td><?= date('d M Y', strtotime($e['expense_date'])) ?></td>
                <td>
                  <div style="font-size:.85rem;">
                    <?= htmlspecialchars($e['user_name']) ?><br>
                    <span style="color:var(--ink-faint);font-size:.78rem;">@<?= htmlspecialchars($e['username']) ?></span>
                  </div>
                </td>
                <td><?= htmlspecialchars($e['description'] ?: '—') ?></td>
                <td>
                  <span class="cat-badge" style="color:<?= htmlspecialchars($e['cat_color']) ?>">
                    <?= $e['cat_icon'] ?> <?= htmlspecialchars($e['cat_name']) ?>
                  </span>
                </td>
                <td class="amount-cell">₹<?= number_format($e['amount'],2) ?></td>
                <td class="no-print">
                  <form method="POST" style="display:inline;"
                        onsubmit="return confirm('Delete this expense?')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:var(--beige);">
                <td colspan="5" style="font-weight:700;padding:.65rem 1rem;text-align:right;">Total</td>
                <td class="amount-cell">₹<?= number_format($total,2) ?></td>
                <td class="no-print"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>
</body>
</html>
