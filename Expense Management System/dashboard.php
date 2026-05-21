<?php

require_once 'includes/auth.php';
require_once 'includes/expenses.php';
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && verifyCsrf($_POST['csrf_token'] ?? '')) {
    deleteExpense((int)$_POST['delete_id'], $userId);
    header("Location: dashboard.php?" . http_build_query(array_filter([
        'date_from' => $_POST['filter_from'] ?? '',
        'date_to'   => $_POST['filter_to'] ?? '',
        'search'    => $_POST['filter_search'] ?? '',
        'month'     => $_POST['filter_month'] ?? '',
    ])));
    exit;
}

$dateFrom  = $_GET['date_from']  ?? '';
$dateTo    = $_GET['date_to']    ?? '';
$search    = $_GET['search']     ?? '';
$selMonth  = $_GET['month']      ?? date('Y-m');

if ($selMonth && !$dateFrom && !$dateTo) {
    [$yr, $mo] = explode('-', $selMonth);
    $dateFrom  = "$yr-$mo-01";
    $dateTo    = date("Y-m-t", mktime(0,0,0,(int)$mo,1,(int)$yr));
}

$expenses   = getExpenses($userId, $dateFrom ?: null, $dateTo ?: null, $search ?: null);
$catTotals  = getTotalByCategory($userId, $dateFrom ?: null, $dateTo ?: null);
$dailyData  = getDailyTotals($userId, $dateFrom ?: null, $dateTo ?: null);
$monthCard  = getMonthSummaryCard($userId, $selMonth);

$budget     = getUserBudget($userId);
$monthSpend = getCurrentMonthSpend($userId);
$budgetPct  = $budget > 0 ? min(round($monthSpend / $budget * 100, 1), 999) : 0;
$totalFiltered = array_sum(array_column($expenses, 'amount'));

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">

  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content" id="mainContent">

    <div class="print-header">
      <h2>WatchMyWallet — Expense Report</h2>
      <p>Generated: <?= date('d M Y, H:i') ?> &nbsp;|&nbsp; User: <?= htmlspecialchars($_SESSION['full_name']) ?></p>
      <?php if ($dateFrom || $dateTo): ?>
        <p>Period: <?= $dateFrom ?: '—' ?> to <?= $dateTo ?: '—' ?></p>
      <?php endif; ?>
    </div>

    <div class="topbar no-print">
      <div class="topbar-title">
        <h1>Dashboard</h1>
        <p>Hello, <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</p>
      </div>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <a href="add_expense.php" class="btn btn-primary btn-sm">+ Add Expense</a>
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print Report</button>
      </div>
    </div>

    <div class="filter-bar no-print">
      <form method="GET" style="display:contents;">
        <div class="form-group">
          <label>Month Preset</label>
          <select name="month" onchange="this.form.submit()">
            <?php
            for ($i = 0; $i < 12; $i++) {
                $d  = date('Y-m', strtotime("-$i months"));
                $lbl = date('M Y', strtotime("-$i months"));
                $sel = $selMonth === $d ? 'selected' : '';
                echo "<option value=\"$d\" $sel>$lbl</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label>From Date</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="form-group">
          <label>To Date</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div class="form-group" style="flex:2;min-width:200px;">
          <label>🔍 Search Description</label>
          <input type="text" name="search" placeholder="Search expenses..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div style="display:flex;gap:.5rem;align-items:flex-end;">
          <button type="submit" class="btn btn-primary btn-sm">Apply</button>
          <a href="dashboard.php" class="btn btn-outline btn-sm">Reset</a>
        </div>
      </form>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">💸</div>
        <div class="stat-label">Total Spent (Filtered)</div>
        <div class="stat-value">₹<?= number_format($totalFiltered, 2) ?></div>
        <div class="stat-sub"><?= count($expenses) ?> transactions</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🗓️</div>
        <div class="stat-label">This Month</div>
        <div class="stat-value">₹<?= number_format($monthSpend, 2) ?></div>
        <div class="stat-sub"><?= date('F Y') ?></div>
        <?php if ($budget > 0): ?>
        <div class="budget-bar-wrap">
          <div class="budget-bar-track">
            <div class="budget-bar-fill <?= $budgetPct>=100?'over':($budgetPct>=80?'warn':'') ?>"
                 style="width:<?= min($budgetPct,100) ?>%"></div>
          </div>
          <div class="budget-pct" style="color:<?= $budgetPct>=100?'var(--danger)':($budgetPct>=80?'var(--warn)':'var(--success)') ?>">
            <?= $budgetPct ?>% of ₹<?= number_format($budget,0) ?> budget
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🏆</div>
        <div class="stat-label">Top Category</div>
        <?php if (!empty($catTotals)): ?>
          <div class="stat-value" style="font-size:1.1rem;">
            <?= $catTotals[0]['icon'] ?> <?= htmlspecialchars($catTotals[0]['name']) ?>
          </div>
          <div class="stat-sub">₹<?= number_format($catTotals[0]['total'], 2) ?> spent</div>
        <?php else: ?>
          <div class="stat-sub">No data yet</div>
        <?php endif; ?>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-label">Monthly Summary</div>
        <?php $ms = $monthCard['summary']; $tc = $monthCard['top_category']; ?>
        <div class="stat-value" style="font-size:1rem;">
          <?= $ms['txn_count'] ?? 0 ?> transactions
        </div>
        <div class="stat-sub">
          Avg: ₹<?= number_format($ms['avg_amount'] ?? 0, 2) ?> per expense
          <?php if ($tc): ?><br>📌 <?= $tc['icon'] ?> <?= htmlspecialchars($tc['name']) ?><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="dash-grid">

      <div class="card">
        <div class="card-header">
          <span class="card-title">🥧 Spending by Category</span>
          <span style="font-size:.78rem;color:var(--ink-faint);">Pie Chart</span>
        </div>
        <?php if (!empty($catTotals)): ?>
          <canvas id="pieChart" height="220"></canvas>
        <?php else: ?>
          <div class="empty-state"><div class="es-icon">📊</div><p>No data for selected period.</p></div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">📈 Daily Spending Trend</span>
          <span style="font-size:.78rem;color:var(--ink-faint);">Line Chart</span>
        </div>
        <?php if (!empty($dailyData)): ?>
          <canvas id="lineChart" height="220"></canvas>
        <?php else: ?>
          <div class="empty-state"><div class="es-icon">📈</div><p>No data for selected period.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header no-print">
        <span class="card-title">📋 Expense Records</span>
        <span style="font-size:.82rem;color:var(--ink-faint);"><?= count($expenses) ?> records</span>
      </div>

      <?php if (empty($expenses)): ?>
        <div class="empty-state">
          <div class="es-icon">🧾</div>
          <p>No expenses found for the selected filters.</p>
          <a href="add_expense.php" class="btn btn-primary btn-sm" style="margin-top:.8rem;">Add First Expense</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Amount</th>
                <th class="no-print">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($expenses as $i => $e): ?>
              <tr>
                <td style="color:var(--ink-faint);"><?= $i+1 ?></td>
                <td><?= date('d M Y', strtotime($e['expense_date'])) ?></td>
                <td><?= htmlspecialchars($e['description'] ?: '—') ?></td>
                <td>
                  <span class="cat-badge" style="color:<?= htmlspecialchars($e['category_color']) ?>">
                    <?= $e['category_icon'] ?> <?= htmlspecialchars($e['category_name']) ?>
                  </span>
                </td>
                <td class="amount-cell">₹<?= number_format($e['amount'], 2) ?></td>
                <td class="no-print">
                  <form method="POST" style="display:inline;"
                        onsubmit="return confirm('Delete this expense?')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                    <input type="hidden" name="filter_from" value="<?= htmlspecialchars($dateFrom) ?>">
                    <input type="hidden" name="filter_to" value="<?= htmlspecialchars($dateTo) ?>">
                    <input type="hidden" name="filter_search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="filter_month" value="<?= htmlspecialchars($selMonth) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:var(--beige);">
                <td colspan="4" style="font-weight:700;padding:.65rem 1rem;text-align:right;">Total</td>
                <td class="amount-cell" style="color:var(--ink);">₹<?= number_format($totalFiltered,2) ?></td>
                <td class="no-print"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<script>
<?php if (!empty($catTotals)): ?>

const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($catTotals, 'name')) ?>,
    datasets: [{
      data: <?= json_encode(array_map(fn($r) => round($r['total'],2), $catTotals)) ?>,
      backgroundColor: <?= json_encode(array_column($catTotals, 'color')) ?>,
      borderColor: '#fff',
      borderWidth: 3,
      hoverOffset: 8
    }]
  },
  options: {
    responsive: true,
    cutout: '60%',
    plugins: {
      legend: { position: 'right', labels: { font: { family: 'DM Sans', size: 12 }, padding: 14 } },
      tooltip: { callbacks: { label: ctx => ` ₹${ctx.parsed.toLocaleString('en-IN', {minimumFractionDigits:2})}` } }
    }
  }
});
<?php endif; ?>

<?php if (!empty($dailyData)): ?>

const lineCtx = document.getElementById('lineChart').getContext('2d');
const lineLabels = <?= json_encode(array_column($dailyData, 'expense_date')) ?>;
const lineValues = <?= json_encode(array_map(fn($r) => round($r['total'],2), $dailyData)) ?>;

new Chart(lineCtx, {
  type: 'line',
  data: {
    labels: lineLabels.map(d => { const p=d.split('-'); return p[2]+'/'+p[1]; }),
    datasets: [{
      label: 'Daily Spending (₹)',
      data: lineValues,
      borderColor: '#7c5c3e',
      backgroundColor: 'rgba(124,92,62,.08)',
      tension: 0.45,
      fill: true,
      pointBackgroundColor: '#7c5c3e',
      pointRadius: 4,
      pointHoverRadius: 7
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ₹${ctx.parsed.y.toLocaleString('en-IN',{minimumFractionDigits:2})}` } }
    },
    scales: {
      y: { beginAtZero: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') }, grid: { color: 'rgba(31,31,31,.05)' } },
      x: { grid: { display: false } }
    }
  }
});
<?php endif; ?>
</script>
</body>
</html>