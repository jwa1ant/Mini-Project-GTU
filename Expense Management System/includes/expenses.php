<?php

require_once __DIR__ . '/config.php';


function addExpense(int $userId, int $catId, float $amount, string $desc, string $date): bool {
    $db = getDB();
    $stmt = $db->prepare(
        "INSERT INTO expenses (user_id, category_id, amount, description, expense_date)
         VALUES (?, ?, ?, ?, ?)"
    );
    return $stmt->execute([$userId, $catId, $amount, trim($desc), $date]);
}

function deleteExpense(int $expenseId, int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    return $stmt->execute([$expenseId, $userId]);
}

function getCategories(): array {
    $db = getDB();
    return $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

function getExpenses(int $userId, ?string $dateFrom = null, ?string $dateTo = null, ?string $search = null): array {
    $db     = getDB();
    $params = [$userId];
    $sql    = "SELECT e.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
               FROM expenses e
               JOIN categories c ON e.category_id = c.id
               WHERE e.user_id = ?";

    if ($dateFrom) { $sql .= " AND e.expense_date >= ?"; $params[] = $dateFrom; }
    if ($dateTo)   { $sql .= " AND e.expense_date <= ?"; $params[] = $dateTo; }
    if ($search)   { $sql .= " AND e.description LIKE ?"; $params[] = '%' . $search . '%'; }

    $sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


function getTotalByCategory(int $userId, ?string $dateFrom = null, ?string $dateTo = null): array {
    $db     = getDB();
    $params = [$userId];
    $sql    = "SELECT c.name, c.icon, c.color, SUM(e.amount) AS total
               FROM expenses e JOIN categories c ON e.category_id = c.id
               WHERE e.user_id = ?";
    if ($dateFrom) { $sql .= " AND e.expense_date >= ?"; $params[] = $dateFrom; }
    if ($dateTo)   { $sql .= " AND e.expense_date <= ?"; $params[] = $dateTo; }
    $sql .= " GROUP BY c.id ORDER BY total DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getDailyTotals(int $userId, ?string $dateFrom = null, ?string $dateTo = null): array {
    $db     = getDB();
    $params = [$userId];
    $sql    = "SELECT expense_date, SUM(amount) AS total
               FROM expenses WHERE user_id = ?";
    if ($dateFrom) { $sql .= " AND expense_date >= ?"; $params[] = $dateFrom; }
    if ($dateTo)   { $sql .= " AND expense_date <= ?"; $params[] = $dateTo; }
    $sql .= " GROUP BY expense_date ORDER BY expense_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getMonthlySummary(int $userId, int $year): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT MONTH(expense_date) AS month, SUM(amount) AS total
         FROM expenses WHERE user_id = ? AND YEAR(expense_date) = ?
         GROUP BY MONTH(expense_date) ORDER BY month ASC"
    );
    $stmt->execute([$userId, $year]);
    $rows = $stmt->fetchAll();
    $result = array_fill(1, 12, 0);
    foreach ($rows as $r) { $result[(int)$r['month']] = (float)$r['total']; }
    return $result;
}

function getMonthSummaryCard(int $userId, string $monthYear): array {
    $db   = getDB();
    [$yr, $mo] = explode('-', $monthYear);

    $stmt = $db->prepare(
        "SELECT COUNT(*) AS txn_count, SUM(amount) AS total, AVG(amount) AS avg_amount
         FROM expenses WHERE user_id = ? AND YEAR(expense_date)=? AND MONTH(expense_date)=?"
    );
    $stmt->execute([$userId, $yr, $mo]);
    $summary = $stmt->fetch();

    $stmt2 = $db->prepare(
        "SELECT c.name, c.icon, SUM(e.amount) AS total
         FROM expenses e JOIN categories c ON e.category_id=c.id
         WHERE e.user_id=? AND YEAR(e.expense_date)=? AND MONTH(e.expense_date)=?
         GROUP BY c.id ORDER BY total DESC LIMIT 1"
    );
    $stmt2->execute([$userId, $yr, $mo]);
    $topCat = $stmt2->fetch();

    return ['summary' => $summary, 'top_category' => $topCat];
}


function getUserBudget(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare("SELECT monthly_budget FROM users WHERE id=?");
    $stmt->execute([$userId]);
    return (float)($stmt->fetchColumn() ?? 0);
}

function updateUserBudget(int $userId, float $budget): void {
    $db = getDB();
    $db->prepare("UPDATE users SET monthly_budget=? WHERE id=?")->execute([$budget, $userId]);
}

function getCurrentMonthSpend(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(amount),0) FROM expenses
         WHERE user_id=? AND YEAR(expense_date)=YEAR(NOW()) AND MONTH(expense_date)=MONTH(NOW())"
    );
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}
?>