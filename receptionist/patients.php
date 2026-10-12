<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('receptionist');

$page_title = 'Patients';
$page_sub   = 'Register, edit and search patients';
$active     = 'patients';
$flash = '';
$flashType = 'success';

// ---- Handle: Add / Update patient (same form serves both) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_patient'])) {
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $data = [
        trim($_POST['name']),
        trim($_POST['cnic']),
        (int)$_POST['age'],
        $_POST['gender'],
        trim($_POST['contact']),
        trim($_POST['address']),
        trim($_POST['blood_group']),
        trim($_POST['emergency_contact']),
    ];

    if ($patientId > 0) {
        $pdo->prepare("UPDATE patients SET name=?, cnic=?, age=?, gender=?, contact=?, address=?, blood_group=?, emergency_contact=? WHERE id=?")
            ->execute(array_merge($data, [$patientId]));
        $flash = 'Patient details updated successfully.';
    } else {
        $mrn = generate_mrn($pdo);
        $pdo->prepare("INSERT INTO patients (mrn, name, cnic, age, gender, contact, address, blood_group, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(array_merge([$mrn], $data));
        $flash = "Patient registered successfully. MRN: $mrn";
    }
    header('Location: patients.php?saved=' . urlencode($flash));
    exit;
}
if (isset($_GET['saved'])) { $flash = $_GET['saved']; }

// ---- Handle: Delete patient ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $activeCheck = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE patient_id = ? AND status = 'admitted'");
    $activeCheck->execute([$id]);

    if ($activeCheck->fetchColumn() > 0) {
        header('Location: patients.php?err=' . urlencode('Cannot delete — this patient is currently admitted. Discharge first.'));
        exit;
    }
    $pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
    header('Location: patients.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) { $flash = 'Patient and all related records deleted.'; }
if (isset($_GET['err'])) { $flash = $_GET['err']; $flashType = 'danger'; }

// ---- Load patient for editing, if requested ----
$editPatient = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editPatient = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE name LIKE ? OR mrn LIKE ? OR contact LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY id DESC LIMIT 25");
}
$patients = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><i class="bi bi-check-circle me-1"></i><?= e($flash) ?></div><?php endif; ?>

<!-- ===== Register / Edit Patient (top) ===== -->
<div class="panel mb-4">
  <div class="panel-header">
    <h5><i class="bi bi-<?= $editPatient ? 'pencil-square' : 'person-plus' ?> me-2"></i><?= $editPatient ? 'Edit Patient — ' . e($editPatient['name']) : 'Register New Patient' ?></h5>
    <?php if ($editPatient): ?><a href="patients.php" class="btn btn-outline-hms btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="POST">
      <input type="hidden" name="patient_id" value="<?= $editPatient ? (int)$editPatient['id'] : '' ?>">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= e($editPatient['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Age</label>
          <input type="number" name="age" class="form-control" value="<?= e($editPatient['age'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-select" required>
            <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($editPatient['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Blood Group</label>
          <input type="text" name="blood_group" class="form-control" placeholder="O+" value="<?= e($editPatient['blood_group'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">CNIC</label>
          <input type="text" name="cnic" class="form-control" placeholder="35202-1234567-1" value="<?= e($editPatient['cnic'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact" class="form-control" value="<?= e($editPatient['contact'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Emergency Contact</label>
          <input type="text" name="emergency_contact" class="form-control" value="<?= e($editPatient['emergency_contact'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" value="<?= e($editPatient['address'] ?? '') ?>">
        </div>
      </div>
      <button type="submit" name="save_patient" class="btn btn-hms mt-3"><?= $editPatient ? 'Update Patient' : 'Register Patient' ?></button>
    </form>
  </div>
</div>

<!-- ===== Search + Patient List (below) ===== -->
<div class="panel mb-3">
  <div class="panel-body">
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="q" class="form-control" placeholder="Search by name, MRN or phone..." value="<?= e($search) ?>">
      <button class="btn btn-hms">Search</button>
      <?php if ($search): ?><a href="patients.php" class="btn btn-outline-hms">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-people me-2"></i>Patients</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>MRN</th><th>Name</th><th>Age/Gender</th><th>Contact</th><th>Blood</th><th></th></tr></thead>
        <tbody>
          <?php if (!$patients): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No patients found.</td></tr>
          <?php endif; ?>
          <?php foreach ($patients as $p): ?>
          <tr class="<?= (int)($editPatient['id'] ?? 0) === (int)$p['id'] ? 'table-active' : '' ?>">
            <td><?= e($p['mrn']) ?></td>
            <td><?= e($p['name']) ?></td>
            <td><?= e($p['age']) ?> / <?= e($p['gender']) ?></td>
            <td><?= e($p['contact']) ?></td>
            <td><?= e($p['blood_group']) ?></td>
            <td class="d-flex gap-1">
              <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline-hms btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
              <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete <?= e(addslashes($p['name'])) ?>? This will also delete their appointments, admissions and bills. This cannot be undone.');"><i class="bi bi-trash3"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
