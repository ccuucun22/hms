<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('receptionist');

$page_title = 'Appointments';
$page_sub   = 'Book a checkup — fee is generated automatically';
$active     = 'appointments';
$flash = '';
$flashType = 'success';

$doctors = $pdo->query("
    SELECT d.id, u.name, d.specialization, d.consultation_fee FROM doctors d
    JOIN users u ON u.id = d.user_id WHERE d.status = 'active' ORDER BY u.name
")->fetchAll();

// ---- Handle: Book / Update appointment (same form serves both) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_appointment'])) {
    $apptId    = (int)($_POST['appointment_id'] ?? 0);
    $patientId = (int)$_POST['patient_id'];
    $doctorId  = (int)$_POST['doctor_id'];
    $date      = $_POST['appointment_date'];
    $time      = $_POST['appointment_time'];

    $feeStmt = $pdo->prepare("SELECT consultation_fee FROM doctors WHERE id = ?");
    $feeStmt->execute([$doctorId]);
    $fee = (float)$feeStmt->fetchColumn();

    if ($apptId > 0) {
        $pdo->prepare("UPDATE appointments SET patient_id=?, doctor_id=?, appointment_date=?, appointment_time=?, checkup_fee=? WHERE id = ? AND status = 'scheduled'")
            ->execute([$patientId, $doctorId, $date, $time, $fee, $apptId]);

        $bill = $pdo->prepare("SELECT * FROM bills WHERE appointment_id = ?");
        $bill->execute([$apptId]);
        $bill = $bill->fetch();
        if ($bill) {
            $total = $fee + (float)$bill['operation_charges'] + (float)$bill['room_charges'];
            $pdo->prepare("UPDATE bills SET patient_id=?, checkup_fee=?, total_amount=? WHERE id=?")
                ->execute([$patientId, $fee, $total, $bill['id']]);
        }
        $flash = 'Appointment updated and fee recalculated.';
    } else {
        $pdo->beginTransaction();
        $ins = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, checkup_fee) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$patientId, $doctorId, $date, $time, $fee]);
        $newApptId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO bills (patient_id, appointment_id, checkup_fee, total_amount, payment_status) VALUES (?, ?, ?, ?, 'unpaid')")
            ->execute([$patientId, $newApptId, $fee, $fee]);
        $pdo->commit();
        $flash = "Appointment booked. Checkup fee of " . money($fee) . " has been generated for this patient.";
    }
    header('Location: appointments.php?saved=' . urlencode($flash));
    exit;
}
if (isset($_GET['saved'])) { $flash = $_GET['saved']; }

// ---- Handle: Delete appointment (only if not yet tied to an admission) ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $linked = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE appointment_id = ?");
    $linked->execute([$id]);

    if ($linked->fetchColumn() > 0) {
        header('Location: appointments.php?err=' . urlencode('Cannot delete — this appointment is linked to a patient admission.'));
        exit;
    }
    $pdo->prepare("DELETE FROM bills WHERE appointment_id = ? AND admission_id IS NULL")->execute([$id]);
    $pdo->prepare("DELETE FROM appointments WHERE id = ? AND status = 'scheduled'")->execute([$id]);
    header('Location: appointments.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) { $flash = 'Appointment deleted successfully.'; }
if (isset($_GET['err'])) { $flash = $_GET['err']; $flashType = 'danger'; }

// ---- Load appointment for editing, if requested ----
$editAppt = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND status = 'scheduled'");
    $stmt->execute([(int)$_GET['edit']]);
    $editAppt = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE name LIKE ? OR mrn LIKE ? ORDER BY name");
    $like = "%$search%";
    $stmt->execute([$like, $like]);
    $patients = $stmt->fetchAll();
} else {
    $patients = $pdo->query("SELECT * FROM patients ORDER BY name LIMIT 50")->fetchAll();
}

$recent = $pdo->query("
    SELECT a.*, p.name AS patient_name, p.mrn, u.name AS doctor_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id
    ORDER BY a.id DESC LIMIT 15
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><i class="bi bi-check-circle me-1"></i><?= e($flash) ?></div><?php endif; ?>

<!-- ===== Book / Edit Appointment (top) ===== -->
<div class="panel mb-4">
  <div class="panel-header">
    <h5><i class="bi bi-<?= $editAppt ? 'pencil-square' : 'calendar-plus' ?> me-2"></i><?= $editAppt ? 'Edit Appointment #' . $editAppt['id'] : 'Book Appointment' ?></h5>
    <?php if ($editAppt): ?><a href="appointments.php" class="btn btn-outline-hms btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="POST">
      <input type="hidden" name="appointment_id" value="<?= $editAppt ? (int)$editAppt['id'] : '' ?>">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Patient</label>
          <select name="patient_id" class="form-select" required>
            <option value="">Select patient...</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($editAppt['patient_id'] ?? null) == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> — <?= e($p['mrn']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Not listed? <a href="patients.php">Register them first.</a></div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Doctor</label>
          <select name="doctor_id" id="doctor_id" class="form-select" required onchange="document.getElementById('feePreview').textContent = this.options[this.selectedIndex].dataset.fee ? 'Rs. ' + this.options[this.selectedIndex].dataset.fee : '—';">
            <option value="">Select doctor...</option>
            <?php foreach ($doctors as $d): ?>
              <option value="<?= $d['id'] ?>" data-fee="<?= number_format($d['consultation_fee'], 2) ?>" <?= ($editAppt['doctor_id'] ?? null) == $d['id'] ? 'selected' : '' ?>>Dr. <?= e($d['name']) ?> — <?= e($d['specialization']) ?> (Rs. <?= number_format($d['consultation_fee'],2) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Date</label>
          <input type="date" name="appointment_date" class="form-control" value="<?= e($editAppt['appointment_date'] ?? '') ?>" <?= $editAppt ? '' : 'min="' . date('Y-m-d') . '"' ?> required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Time</label>
          <input type="time" name="appointment_time" class="form-control" value="<?= $editAppt ? substr($editAppt['appointment_time'],0,5) : '' ?>" required>
        </div>

        <div class="col-md-2">
          <label class="form-label">Checkup Fee</label>
          <div class="panel" style="background:var(--primary-soft); border:none;">
            <div class="panel-body py-2 px-3">
              <strong id="feePreview" style="color:var(--primary-dark); font-size:1rem;"><?= $editAppt ? money($editAppt['checkup_fee']) : '—' ?></strong>
            </div>
          </div>
        </div>
      </div>

      <button type="submit" name="save_appointment" class="btn btn-hms mt-3">
        <?= $editAppt ? 'Update Appointment' : 'Book Appointment & Generate Fee' ?>
      </button>
    </form>
  </div>
</div>

<!-- ===== Recent Appointments (below) ===== -->
<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-clock-history me-2"></i>Recent Appointments</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Fee</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$recent): ?><tr><td colspan="6" class="text-center text-muted py-4">No appointments yet.</td></tr><?php endif; ?>
          <?php foreach ($recent as $a): ?>
          <tr class="<?= (int)($editAppt['id'] ?? 0) === (int)$a['id'] ? 'table-active' : '' ?>">
            <td><?= e($a['patient_name']) ?> <span class="text-muted">(<?= e($a['mrn']) ?>)</span></td>
            <td>Dr. <?= e($a['doctor_name']) ?></td>
            <td><?= e(date('d M, h:i A', strtotime($a['appointment_date'].' '.$a['appointment_time']))) ?></td>
            <td><?= money($a['checkup_fee']) ?></td>
            <td><span class="badge-soft <?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
            <td>
              <?php if ($a['status'] === 'scheduled'): ?>
                <a href="?edit=<?= $a['id'] ?>" class="btn btn-outline-hms btn-sm"><i class="bi bi-pencil-square"></i></a>
                <a href="?delete=<?= $a['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete this appointment?');"><i class="bi bi-trash3"></i></a>
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
