<?php

require_once '../includes/auth.php';
require_once '../includes/expenses.php';
requireAdmin();

$db      = getDB();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($uid === $_SESSION['user_id'] && in_array($action,['delete','toggle'])) {
        $error = 'You cannot modify your own account.';
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$uid]);
        $success = 'User deleted.';
    } elseif ($action === 'toggle') {
        $db->prepare("UPDATE users SET is_active = 1-is_active WHERE id=?")->execute([$uid]);
        $success = 'User status updated.';
    } elseif ($action === 'add') {
        $result = registerUser([
            'full_name' => $_POST['full_name'] ?? '',
            'email'     => $_POST['email'] ?? '',
            'username'  => $_POST['username'] ?? '',
            'password'  => $_POST['password'] ?? '',
            'budget'    => floatval($_POST['budget'] ?? 0),
        ]);
        if ($result['success']) {

            if (!empty($_POST['is_admin'])) {
                $newId = $db->lastInsertId();
                $db->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$newId]);
            }
            $success = 'User added successfully.';
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'edit') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $budget   = floatval($_POST['budget'] ?? 0);
        $db->prepare("UPDATE users SET full_name=?, email=?, monthly_budget=? WHERE id=?")
           ->execute([$fullName, $email, $budget, $uid]);
        $success = 'User updated.';
    }
}

$search = $_GET['search'] ?? '';
$where  = $search ? "WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?" : "";
$params = $search ? ["%$search%","%$search%","%$search%"] : [];
$stmt   = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM expenses e WHERE e.user_id=u.id) AS expense_count FROM users u $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$activePage = 'admin_users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users — WatchMyWallet Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <?php

  define('APP_URL_ADJ', APP_URL);
  include '../includes/sidebar.php';
  ?>
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-title">
        <h1>Manage Users</h1>
        <p><?= count($users) ?> registered users</p>
      </div>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('open')">
        + Add User
      </button>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="filter-bar" style="margin-bottom:1.2rem;">
      <form method="GET" style="display:flex;gap:.7rem;flex:1;align-items:flex-end;">
        <div class="form-group" style="flex:1;margin:0;">
          <label>🔍 Search Users</label>
          <input type="text" name="search" placeholder="Name, email or username…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search): ?><a href="users.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Expenses</th>
              <th>Budget</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem;">
                  <div class="user-avatar"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
                  <?= htmlspecialchars($u['full_name']) ?>
                </div>
              </td>
              <td style="color:var(--ink-faint);">@<?= htmlspecialchars($u['username']) ?></td>
              <td style="font-size:.85rem;"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="chip chip-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td>
                <span class="chip <?= $u['is_active']?'chip-active':'chip-off' ?>">
                  <span class="status-dot <?= $u['is_active']?'active':'inactive' ?>"></span>
                  <?= $u['is_active']?'Active':'Inactive' ?>
                </span>
              </td>
              <td><?= $u['expense_count'] ?></td>
              <td>₹<?= number_format($u['monthly_budget'],0) ?></td>
              <td style="font-size:.82rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div style="display:flex;gap:.4rem;">
                
                  <button class="btn btn-outline btn-sm"
                    onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)">✏️</button>

                
                  <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-warn btn-sm"
                            title="<?= $u['is_active']?'Deactivate':'Activate' ?>">
                      <?= $u['is_active']?'⏸':'▶' ?>
                    </button>
                  </form>

                
                  <?php if ($u['role'] !== 'admin'): ?>
                  <form method="POST" style="display:inline;"
                        onsubmit="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>? This removes all their expenses too.')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                  </form>
                  <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Add New User</h3>
      <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
        <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-row">
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Monthly Budget (₹)</label><input type="number" name="budget" min="0" step="0.01" value="0"></div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:.5rem;text-transform:none;letter-spacing:0;font-size:.9rem;font-weight:500;">
          <input type="checkbox" name="is_admin" value="1" style="width:auto;">
          Make Admin
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add User</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit User</h3>
      <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="uid" id="edit_uid">
      <div class="form-group"><label>Full Name</label><input type="text" name="full_name" id="edit_fullname" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" required></div>
      <div class="form-group"><label>Monthly Budget (₹)</label><input type="number" name="budget" id="edit_budget" min="0" step="0.01"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(user) {
  document.getElementById('edit_uid').value     = user.id;
  document.getElementById('edit_fullname').value = user.full_name;
  document.getElementById('edit_email').value   = user.email;
  document.getElementById('edit_budget').value  = user.monthly_budget;
  document.getElementById('editModal').classList.add('open');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
</script>
</body>
</html>
