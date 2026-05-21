<?php

require_once 'includes/auth.php';
require_once 'includes/expenses.php';
requireLogin();

$userId = $_SESSION['user_id'];
$year   = (int)($_GET['year'] ?? date('Y'));
$monthly = getMonthlySummary($userId, $year);
$catTotals = getTotalByCategory($userId, "$year-01-01", "$year-12-31");

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$activePage = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports — WatchMyWallet</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-title">
        <h1>Reports</h1>
        <p>Annual spending analysis for <?= $year ?></p>
      </div>
      <div style="display:flex;gap:.6rem;align-items:center;">
        <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
          <select name="year" onchange="this.form.submit()" class="form-group" style="padding:.5rem .8rem;border:1.5px solid var(--beige-dark);border-radius:8px;background:var(--white);font-family:var(--ff-body);font-size:.9rem;">
            <?php for ($y = date('Y'); $y >= date('Y')-4; $y--): ?>
              <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </form>
        <button onclick="window.print()" class="btn btn-outline btn-sm no-print">🖨️ Print</button>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-header">
        <span class="card-title">📊 Monthly Spending — <?= $year ?></span>
      </div>
      <canvas id="barChart" height="120"></canvas>
    </div>

    <div class="dash-grid">

      <div class="card">
        <div class="card-header">
          <span class="card-title">🥧 Category Breakdown — <?= $year ?></span>
        </div>
        <?php if (!empty($catTotals)): ?>
          <canvas id="pieChart" height="220"></canvas>
        <?php else: ?>
          <div class="empty-state"><div class="es-icon">📊</div><p>No data for <?= $year ?>.</p></div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">📋 Monthly Totals — <?= $year ?></span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Month</th><th>Amount Spent</th><th>Share</th></tr>
            </thead>
            <tbody>
              <?php
              $yearTotal = array_sum($monthly);
              foreach ($months as $idx => $mName):
                $amt = $monthly[$idx+1];
                $share = $yearTotal > 0 ? round($amt/$yearTotal*100,1) : 0;
              ?>
              <tr>
                <td><?= $mName ?></td>
                <td class="amount-cell">₹<?= number_format($amt, 2) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:.5rem;">
                    <div class="budget-bar-track" style="width:80px;">
                      <div class="budget-bar-fill" style="width:<?= $share ?>%;background:var(--accent-soft);"></div>
                    </div>
                    <span style="font-size:.8rem;"><?= $share ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:var(--beige);">
                <td style="font-weight:700;padding:.65rem 1rem;">Total</td>
                <td class="amount-cell">₹<?= number_format($yearTotal, 2) ?></td>
                <td>100%</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <?php if (!empty($catTotals)): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">🏷️ Category-wise Summary — <?= $year ?></span>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Rank</th><th>Category</th><th>Total Spent</th><th>Share</th></tr></thead>
          <tbody>
            <?php
            $catTotal = array_sum(array_column($catTotals,'total'));
            foreach ($catTotals as $rk => $row):
              $pct = $catTotal>0 ? round($row['total']/$catTotal*100,1) : 0;
            ?>
            <tr>
              <td style="font-weight:700;color:var(--ink-faint);">#<?= $rk+1 ?></td>
              <td><span class="cat-badge" style="color:<?= htmlspecialchars($row['color']) ?>"><?= $row['icon'] ?> <?= htmlspecialchars($row['name']) ?></span></td>
              <td class="amount-cell">₹<?= number_format($row['total'],2) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:.5rem;">
                  <div class="budget-bar-track" style="width:100px;">
                    <div class="budget-bar-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($row['color']) ?>;"></div>
                  </div>
                  <span style="font-size:.8rem;"><?= $pct ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>
<script>

const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Monthly Spend (₹)',
      data: <?= json_encode(array_values($monthly)) ?>,
      backgroundColor: 'rgba(124,92,62,.75)',
      borderColor: '#7c5c3e',
      borderWidth: 1,
      borderRadius: 6,
      borderSkipped: false
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ₹${ctx.parsed.y.toLocaleString('en-IN',{minimumFractionDigits:2})}` } } },
    scales: {
      y: { beginAtZero: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') }, grid: { color: 'rgba(31,31,31,.05)' } },
      x: { grid: { display: false } }
    }
  }
});

<?php if (!empty($catTotals)): ?>
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($catTotals,'name')) ?>,
    datasets: [{
      data: <?= json_encode(array_map(fn($r)=>round($r['total'],2), $catTotals)) ?>,
      backgroundColor: <?= json_encode(array_column($catTotals,'color')) ?>,
      borderColor: '#fff', borderWidth: 3, hoverOffset: 8
    }]
  },
  options: {
    responsive: true, cutout: '55%',
    plugins: {
      legend: { position: 'right', labels: { font:{family:'DM Sans',size:12}, padding:12 } },
      tooltip: { callbacks: { label: ctx => ` ₹${ctx.parsed.toLocaleString('en-IN',{minimumFractionDigits:2})}` } }
    }
  }
});
<?php endif; ?>
</script>
</body>
</html>