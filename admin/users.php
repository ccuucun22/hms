<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$page_title = 'Staff Accounts';
$page_sub   = 'Manage receptionist, accountant and admin logins';
$active     = 'users';
$flash = '';
$flashType = 'success';

// ---- Handle: Add / Update staff account (same form serves both) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $pass   = $_POST['password'];
    $role   = $_POST['role'];

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $userId]);

    if ($check->fetch()) {
        $flash = 'A user with this email already exists.';
        $flashType = 'danger';
    } elseif ($userId > 0) {
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ? AND role != 'doctor'")
                ->execute([$name, $email, $role, $hash, $userId]);
        } else {
            $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ? AND role != 'doctor'")
                ->execute([$name, $email, $role, $userId]);
        }
        $flash = 'Staff account updated successfully.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
            ->execute([$name, $email, $hash, $role]);
        $flash = 'Staff account created successfully.';
    }

    if ($flashType !== 'danger') {
        header('Location: users.php?saved=1');
        exit;
    }
}
if (isset($_GET['saved'])) { $flash = 'Saved successfully.'; }

// ---- Handle: Toggle status ----
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ? AND role != 'doctor'")->execute([$id]);
    }
    header('Location: users.php');
    exit;
}

// ---- Handle: Delete staff account ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === (int)$_SESSION['user_id']) {
        header('Location: users.php?err=' . urlencode('You cannot delete your own account.'));
        exit;
    }
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'doctor'")->execute([$id]);
    header('Location: users.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) { $flash = 'Staff account deleted successfully.'; }
if (isset($_GET['err'])) { $flash = $_GET['err']; $flashType = 'danger'; }

// ---- Load user for editing, if requested ----
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'doctor'");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch();
}

$staff = $pdo->query("SELECT * FROM users WHERE role != 'doctor' ORDER BY id DESC")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><?= e($flash) ?></div><?php endif; ?>

<!-- ===== Add / Edit Staff Account (top) ===== -->
<div class="panel mb-4">
  <div class="panel-header">
    <h5><i class="bi bi-<?= $editUser ? 'pencil-square' : 'person-plus' ?> me-2"></i><?= $editUser ? 'Edit Staff Account — ' . e($editUser['name']) : 'Add Staff Account' ?></h5>
    <?php if ($editUser): ?><a href="users.php" class="btn btn-outline-hms btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="POST">
      <input type="hidden" name="user_id" value="<?= $editUser ? (int)$editUser['id'] : '' ?>">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= e($editUser['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Login Email</label>
          <input type="email" name="email" class="form-control" value="<?= e($editUser['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <?= $editUser ? 'New Password' : 'Password' ?>
            <?php if ($editUser): ?><span class="text-muted" style="font-weight:400;">(optional)</span><?php endif; ?>
          </label>
          <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
        </div>
        <div class="col-md-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <?php foreach (['receptionist' => 'Receptionist', 'accountant' => 'Accountant', 'admin' => 'Admin'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($editUser['role'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" name="save_user" class="btn btn-hms mt-3"><?= $editUser ? 'Update Account' : 'Create Account' ?></button>
    </form>
  </div>
</div>

<!-- ===== All Staff Accounts (below) ===== -->
<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-people me-2"></i>Staff Accounts</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$staff): ?><tr><td colspan="5" class="text-center text-muted py-4">No staff accounts yet.</td></tr><?php endif; ?>
          <?php foreach ($staff as $s): ?>
          <tr class="<?= (int)($editUser['id'] ?? 0) === (int)$s['id'] ? 'table-active' : '' ?>">
            <td><?= e($s['name']) ?></td>
            <td><?= e($s['email']) ?></td>
            <td><span class="badge-soft scheduled"><?= e(ucfirst($s['role'])) ?></span></td>
            <td><span class="badge-soft <?= $s['status'] === 'active' ? 'active' : 'cancelled' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
            <td class="d-flex gap-1">
              <?php if ((int)$s['id'] !== (int)$_SESSION['user_id']): ?>
                <a href="?edit=<?= $s['id'] ?>" class="btn btn-outline-hms btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                <a href="?toggle=<?= $s['id'] ?>" class="btn btn-outline-hms btn-sm">Toggle</a>
                <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete this staff account?');"><i class="bi bi-trash3"></i></a>
              <?php else: ?>
                <span class="text-muted" style="font-size:0.78rem;">(You)</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
