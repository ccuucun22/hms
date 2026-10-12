<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$page_title = 'Doctors';
$page_sub   = 'Manage doctor profiles and consultation fees';
$active     = 'doctors';
$flash      = '';
$flashType  = 'success';

// ---- Handle: Add / Update doctor (same form serves both) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_doctor'])) {
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $pass     = $_POST['password'];
    $spec     = trim($_POST['specialization']);
    $fee      = (float)$_POST['consultation_fee'];
    $surgeon  = isset($_POST['is_surgeon']) ? 1 : 0;

    // Email must stay unique, excluding the doctor's own current account when editing.
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != (SELECT user_id FROM doctors WHERE id = ?)");
    $check->execute([$email, $doctorId]);

    if ($check->fetch()) {
        $flash = 'A user with this email already exists.';
        $flashType = 'danger';
    } elseif ($doctorId > 0) {
        // ---- Update existing doctor ----
        $userIdStmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $userIdStmt->execute([$doctorId]);
        $userId = $userIdStmt->fetchColumn();

        if ($userId) {
            if ($pass !== '') {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?")
                    ->execute([$name, $email, $hash, $userId]);
            } else {
                $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")
                    ->execute([$name, $email, $userId]);
            }
            $pdo->prepare("UPDATE doctors SET specialization = ?, consultation_fee = ?, is_surgeon = ? WHERE id = ?")
                ->execute([$spec, $fee, $surgeon, $doctorId]);
            $flash = 'Doctor updated successfully.';
        }
    } else {
        // ---- Add new doctor ----
        $pdo->beginTransaction();
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'doctor')")
            ->execute([$name, $email, $hash]);
        $userId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO doctors (user_id, specialization, consultation_fee, is_surgeon) VALUES (?, ?, ?, ?)")
            ->execute([$userId, $spec, $fee, $surgeon]);
        $pdo->commit();
        $flash = 'Doctor added successfully.';
    }

    if ($flashType !== 'danger') {
        header('Location: doctors.php?saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) {
    $flash = 'Saved successfully.';
}

// ---- Handle: Toggle doctor status ----
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE doctors d JOIN users u ON u.id = d.user_id
                   SET u.status = IF(u.status='active','inactive','active') WHERE d.id = ?")->execute([$id]);
    header('Location: doctors.php');
    exit;
}

// ---- Handle: Delete doctor ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $apptCheck = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
    $apptCheck->execute([$id]);
    $admCheck = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE doctor_id = ?");
    $admCheck->execute([$id]);

    if ($apptCheck->fetchColumn() > 0 || $admCheck->fetchColumn() > 0) {
        header('Location: doctors.php?err=' . urlencode('Cannot delete — this doctor has appointment or admission records. Use Toggle to deactivate instead.'));
        exit;
    }

    $userIdStmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $userIdStmt->execute([$id]);
    $userId = $userIdStmt->fetchColumn();
    if ($userId) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]); // cascades to doctors row
    }
    header('Location: doctors.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) { $flash = 'Doctor deleted successfully.'; }
if (isset($_GET['err'])) { $flash = $_GET['err']; $flashType = 'danger'; }

// ---- Load doctor for editing, if requested ----
$editDoctor = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("
        SELECT d.id, u.name, u.email, d.specialization, d.consultation_fee, d.is_surgeon
        FROM doctors d JOIN users u ON u.id = d.user_id WHERE d.id = ?
    ");
    $stmt->execute([(int)$_GET['edit']]);
    $editDoctor = $stmt->fetch();
}

$doctors = $pdo->query("
    SELECT d.id, u.name, u.email, u.status, d.specialization, d.consultation_fee, d.is_surgeon
    FROM doctors d JOIN users u ON u.id = d.user_id
    ORDER BY d.id DESC
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><?= e($flash) ?></div>
<?php endif; ?>

<!-- ===== Add / Edit Doctor (top) ===== -->
<div class="panel mb-4">
  <div class="panel-header">
    <h5>
      <i class="bi bi-<?= $editDoctor ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
      <?= $editDoctor ? 'Edit Doctor — Dr. ' . e($editDoctor['name']) : 'Add Doctor' ?>
    </h5>
    <?php if ($editDoctor): ?>
      <a href="doctors.php" class="btn btn-outline-hms btn-sm">Cancel Edit</a>
    <?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="POST">
      <input type="hidden" name="doctor_id" value="<?= $editDoctor ? (int)$editDoctor['id'] : '' ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Full Name <span class="text-muted" style="font-weight:400;">(without "Dr." — it's added automatically everywhere)</span></label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Ahmed Khan" value="<?= e($editDoctor['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Login Email</label>
          <input type="email" name="email" class="form-control" value="<?= e($editDoctor['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">
            <?= $editDoctor ? 'New Password' : 'Password' ?>
            <?php if ($editDoctor): ?><span class="text-muted" style="font-weight:400;">(leave blank to keep current)</span><?php endif; ?>
          </label>
          <input type="password" name="password" class="form-control" <?= $editDoctor ? '' : 'required' ?>>
        </div>
        <div class="col-md-4">
          <label class="form-label">Specialization</label>
          <input type="text" name="specialization" class="form-control" placeholder="e.g. General Physician" value="<?= e($editDoctor['specialization'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Consultation Fee (Rs.)</label>
          <input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?= $editDoctor ? e((string)$editDoctor['consultation_fee']) : '' ?>" required>
        </div>
        <div class="col-md-4 d-flex align-items-center">
          <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" id="is_surgeon" name="is_surgeon" <?= !empty($editDoctor['is_surgeon']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_surgeon">Can perform operations / surgeries</label>
          </div>
        </div>
      </div>
      <button type="submit" name="save_doctor" class="btn btn-hms mt-3">
        <?= $editDoctor ? 'Update Doctor' : 'Add Doctor' ?>
      </button>
    </form>
  </div>
</div>

<!-- ===== All Doctors (below) ===== -->
<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-person-badge me-2"></i>All Doctors</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Name</th><th>Specialization</th><th>Fee</th><th>Type</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$doctors): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No doctors added yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($doctors as $d): ?>
          <tr class="<?= (int)($editDoctor['id'] ?? 0) === (int)$d['id'] ? 'table-active' : '' ?>">
            <td>Dr. <?= e($d['name']) ?><br><span class="text-muted" style="font-size:0.78rem;"><?= e($d['email']) ?></span></td>
            <td><?= e($d['specialization']) ?></td>
            <td><?= money($d['consultation_fee']) ?></td>
            <td><?= $d['is_surgeon'] ? '<span class="badge-soft occupied">Surgeon</span>' : '<span class="badge-soft available">Physician</span>' ?></td>
            <td><span class="badge-soft <?= $d['status'] === 'active' ? 'active' : 'cancelled' ?>"><?= e(ucfirst($d['status'])) ?></span></td>
            <td class="d-flex gap-1">
              <a href="?edit=<?= $d['id'] ?>" class="btn btn-outline-hms btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
              <a href="?toggle=<?= $d['id'] ?>" class="btn btn-outline-hms btn-sm">Toggle</a>
              <a href="?delete=<?= $d['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete Dr. <?= e(addslashes($d['name'])) ?>? This cannot be undone.');"><i class="bi bi-trash3"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
