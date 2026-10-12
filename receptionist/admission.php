<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('receptionist');

$page_title = 'Admit Patient';
$page_sub   = 'Allocate a room and record operation charges if required';
$active     = 'admission';
$flash = '';
$flashType = 'success';

$patients = $pdo->query("SELECT id, name, mrn FROM patients ORDER BY name")->fetchAll();
$doctors  = $pdo->query("
    SELECT d.id, u.name, d.specialization, d.is_surgeon FROM doctors d
    JOIN users u ON u.id = d.user_id WHERE d.status='active' ORDER BY u.name
")->fetchAll();

// ---- Handle: New admission OR update existing admission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admission'])) {
    $admissionId = (int)($_POST['admission_id'] ?? 0);
    $patientId   = (int)$_POST['patient_id'];
    $doctorId    = (int)$_POST['doctor_id'];
    $roomId      = (int)$_POST['room_id'];
    $apptId      = $_POST['appointment_id'] !== '' ? (int)$_POST['appointment_id'] : null;
    $admDate     = $_POST['admission_date'];
    $opRequired  = isset($_POST['operation_required']) ? 1 : 0;
    $opType      = trim($_POST['operation_type'] ?? '');
    $opCharges   = (float)($_POST['operation_charges'] ?? 0);
    $opDate      = $_POST['operation_date'] ?? $admDate;

    if ($admissionId > 0) {
        // ---- Update existing (active) admission ----
        $current = $pdo->prepare("SELECT * FROM admissions WHERE id = ? AND status = 'admitted'");
        $current->execute([$admissionId]);
        $current = $current->fetch();

        if (!$current) {
            $flash = 'This admission could not be found or is already discharged.';
            $flashType = 'danger';
        } else {
            $pdo->beginTransaction();

            // Room change?
            if ($roomId !== (int)$current['room_id']) {
                $roomCheck = $pdo->prepare("SELECT status FROM rooms WHERE id = ?");
                $roomCheck->execute([$roomId]);
                if ($roomCheck->fetchColumn() !== 'available') {
                    $pdo->rollBack();
                    header('Location: admission.php?err=' . urlencode('That room is not available. Please choose another.'));
                    exit;
                }
                $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$current['room_id']]);
                $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$roomId]);
            }

            $pdo->prepare("UPDATE admissions SET patient_id=?, doctor_id=?, room_id=?, operation_required=?, admission_date=? WHERE id=?")
                ->execute([$patientId, $doctorId, $roomId, $opRequired, $admDate, $admissionId]);

            $existingOp = $pdo->prepare("SELECT * FROM operations WHERE admission_id = ?");
            $existingOp->execute([$admissionId]);
            $existingOp = $existingOp->fetch();
            $oldCharges = $existingOp ? (float)$existingOp['charges'] : 0;

            if ($opRequired) {
                if ($existingOp) {
                    $pdo->prepare("UPDATE operations SET doctor_id=?, operation_type=?, charges=?, operation_date=? WHERE id=?")
                        ->execute([$doctorId, $opType, $opCharges, $opDate, $existingOp['id']]);
                } else {
                    $pdo->prepare("INSERT INTO operations (admission_id, doctor_id, operation_type, charges, operation_date) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$admissionId, $doctorId, $opType, $opCharges, $opDate]);
                }
                $delta = $opCharges - $oldCharges;
            } else {
                if ($existingOp) {
                    $pdo->prepare("DELETE FROM operations WHERE id = ?")->execute([$existingOp['id']]);
                }
                $delta = -$oldCharges;
            }

            if ($delta != 0) {
                $pdo->prepare("UPDATE bills SET operation_charges = operation_charges + ?, total_amount = total_amount + ? WHERE admission_id = ?")
                    ->execute([$delta, $delta, $admissionId]);
            }

            $pdo->commit();
            $flash = 'Admission updated successfully.';
        }
    } else {
        // ---- New admission ----
        $roomCheck = $pdo->prepare("SELECT status FROM rooms WHERE id = ?");
        $roomCheck->execute([$roomId]);

        if ($roomCheck->fetchColumn() !== 'available') {
            $flash = 'That room was just taken by another admission. Please pick a different room.';
            $flashType = 'danger';
        } else {
            $pdo->beginTransaction();

            $ins = $pdo->prepare("INSERT INTO admissions (patient_id, doctor_id, room_id, appointment_id, operation_required, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, 'admitted')");
            $ins->execute([$patientId, $doctorId, $roomId, $apptId, $opRequired, $admDate]);
            $newAdmissionId = $pdo->lastInsertId();

            $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$roomId]);

            if ($apptId) {
                $pdo->prepare("UPDATE bills SET admission_id = ? WHERE appointment_id = ?")->execute([$newAdmissionId, $apptId]);
            } else {
                $pdo->prepare("INSERT INTO bills (patient_id, admission_id, checkup_fee, total_amount, payment_status) VALUES (?, ?, 0, 0, 'unpaid')")
                    ->execute([$patientId, $newAdmissionId]);
            }

            if ($opRequired) {
                $pdo->prepare("INSERT INTO operations (admission_id, doctor_id, operation_type, charges, operation_date) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$newAdmissionId, $doctorId, $opType, $opCharges, $opDate]);
                $pdo->prepare("UPDATE bills SET operation_charges = operation_charges + ?, total_amount = total_amount + ? WHERE admission_id = ?")
                    ->execute([$opCharges, $opCharges, $newAdmissionId]);
            }

            $pdo->commit();
            $flash = 'Patient admitted and room allocated successfully.' . ($opRequired ? ' Operation charges recorded.' : '');
        }
    }

    if ($flashType !== 'danger') {
        header('Location: admission.php?saved=' . urlencode($flash));
        exit;
    }
}
if (isset($_GET['saved'])) { $flash = $_GET['saved']; }
if (isset($_GET['err'])) { $flash = $_GET['err']; $flashType = 'danger'; }

// ---- Handle: Cancel / delete an active admission ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $adm = $pdo->prepare("SELECT * FROM admissions WHERE id = ? AND status = 'admitted'");
    $adm->execute([$id]);
    $adm = $adm->fetch();

    if ($adm) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$adm['room_id']]);
        $pdo->prepare("DELETE FROM bills WHERE admission_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM admissions WHERE id = ?")->execute([$id]); // operations cascade automatically
        $pdo->commit();
        header('Location: admission.php?saved=' . urlencode('Admission cancelled and room freed.'));
        exit;
    }
    header('Location: admission.php?err=' . urlencode('Admission not found or already discharged.'));
    exit;
}

// ---- Load admission for editing, if requested ----
$editAdmission = null;
$editOperation = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ? AND status = 'admitted'");
    $stmt->execute([(int)$_GET['edit']]);
    $editAdmission = $stmt->fetch();
    if ($editAdmission) {
        $opStmt = $pdo->prepare("SELECT * FROM operations WHERE admission_id = ?");
        $opStmt->execute([$editAdmission['id']]);
        $editOperation = $opStmt->fetch();
    }
}

// Rooms available now, PLUS the patient's current room when editing (so it still shows as selectable)
$rooms = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY room_type, room_no")->fetchAll();
if ($editAdmission) {
    $curRoom = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $curRoom->execute([$editAdmission['room_id']]);
    $curRoom = $curRoom->fetch();
    if ($curRoom) array_unshift($rooms, $curRoom);
}

$currentlyAdmitted = $pdo->query("
    SELECT ad.id, p.name AS patient_name, p.mrn, r.room_no, u.name AS doctor_name, ad.admission_date, ad.operation_required
    FROM admissions ad
    JOIN patients p ON p.id = ad.patient_id
    JOIN rooms r ON r.id = ad.room_id
    JOIN doctors d ON d.id = ad.doctor_id JOIN users u ON u.id = d.user_id
    WHERE ad.status = 'admitted'
    ORDER BY ad.id DESC
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><i class="bi bi-check-circle me-1"></i><?= e($flash) ?></div><?php endif; ?>

<!-- ===== New / Edit Admission (top) ===== -->
<div class="panel mb-4">
  <div class="panel-header">
    <h5><i class="bi bi-<?= $editAdmission ? 'pencil-square' : 'hospital' ?> me-2"></i><?= $editAdmission ? 'Edit Admission #' . $editAdmission['id'] : 'New Admission' ?></h5>
    <?php if ($editAdmission): ?><a href="admission.php" class="btn btn-outline-hms btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="POST">
      <input type="hidden" name="admission_id" value="<?= $editAdmission ? (int)$editAdmission['id'] : '' ?>">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Patient</label>
          <select name="patient_id" class="form-select" required>
            <option value="">Select patient...</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($editAdmission['patient_id'] ?? null) == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> — <?= e($p['mrn']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Attending Doctor</label>
          <select name="doctor_id" class="form-select" required>
            <option value="">Select doctor...</option>
            <?php foreach ($doctors as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($editAdmission['doctor_id'] ?? null) == $d['id'] ? 'selected' : '' ?>>Dr. <?= e($d['name']) ?> — <?= e($d['specialization']) ?><?= $d['is_surgeon'] ? ' (Surgeon)' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Room</label>
          <select name="room_id" class="form-select" required>
            <option value="">Select room...</option>
            <?php foreach ($rooms as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ($editAdmission['room_id'] ?? null) == $r['id'] ? 'selected' : '' ?>><?= e($r['room_no']) ?> — <?= e($r['room_type']) ?> (Rs. <?= number_format($r['per_day_rate'],2) ?>/day)</option>
            <?php endforeach; ?>
            <?php if (!$rooms): ?><option value="" disabled>No rooms currently available</option><?php endif; ?>
          </select>
        </div>

        <?php if (!$editAdmission): ?>
        <div class="col-md-3">
          <label class="form-label">Link to Appointment ID <span class="text-muted">(optional)</span></label>
          <input type="number" name="appointment_id" class="form-control" placeholder="e.g. 12">
        </div>
        <?php endif; ?>

        <div class="col-md-3">
          <label class="form-label">Admission Date</label>
          <input type="date" name="admission_date" class="form-control" value="<?= e($editAdmission['admission_date'] ?? date('Y-m-d')) ?>" required>
        </div>
      </div>

      <div class="form-check mt-3 mb-2">
        <input type="checkbox" class="form-check-input" id="operation_required" name="operation_required" <?= !empty($editAdmission['operation_required']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="operation_required">Operation / Surgery required for this patient</label>
      </div>

      <div id="operation_section" class="panel mb-3" style="background:var(--primary-soft); border:none; display:<?= !empty($editAdmission['operation_required']) ? 'block' : 'none' ?>;">
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Operation Type</label>
              <input type="text" name="operation_type" class="form-control" placeholder="e.g. Appendectomy" value="<?= e($editOperation['operation_type'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Operation Charges (Rs.)</label>
              <input type="number" step="0.01" name="operation_charges" class="form-control" value="<?= e($editOperation['charges'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Operation Date</label>
              <input type="date" name="operation_date" class="form-control" value="<?= e($editOperation['operation_date'] ?? date('Y-m-d')) ?>">
            </div>
          </div>
          <p class="text-muted mb-0 mt-2" style="font-size:0.8rem;"><i class="bi bi-info-circle me-1"></i>Operation charges are recorded separately from the checkup fee and added to the final bill at discharge.</p>
        </div>
      </div>

      <button type="submit" name="save_admission" class="btn btn-hms"><?= $editAdmission ? 'Update Admission' : 'Admit Patient & Allocate Room' ?></button>
    </form>
  </div>
</div>

<!-- ===== Currently Admitted Patients (below) ===== -->
<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-list-check me-2"></i>Currently Admitted Patients</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Patient</th><th>Room</th><th>Doctor</th><th>Since</th><th>Op?</th><th></th></tr></thead>
        <tbody>
          <?php if (!$currentlyAdmitted): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No patients currently admitted.</td></tr>
          <?php endif; ?>
          <?php foreach ($currentlyAdmitted as $a): ?>
          <tr class="<?= (int)($editAdmission['id'] ?? 0) === (int)$a['id'] ? 'table-active' : '' ?>">
            <td><?= e($a['patient_name']) ?> <span class="text-muted">(<?= e($a['mrn']) ?>)</span></td>
            <td><?= e($a['room_no']) ?></td>
            <td>Dr. <?= e($a['doctor_name']) ?></td>
            <td><?= e(date('d M', strtotime($a['admission_date']))) ?></td>
            <td><?= $a['operation_required'] ? '<span class="badge-soft occupied">Yes</span>' : '<span class="text-muted">—</span>' ?></td>
            <td class="d-flex gap-1">
              <a href="?edit=<?= $a['id'] ?>" class="btn btn-outline-hms btn-sm"><i class="bi bi-pencil-square"></i></a>
              <a href="?delete=<?= $a['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Cancel this admission and free the room? This deletes the related bill too.');"><i class="bi bi-trash3"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
