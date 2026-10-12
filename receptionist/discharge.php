<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('receptionist');

$page_title = 'Discharge Patient';
$page_sub   = 'Calculate room charges and generate the final combined bill';
$active     = 'discharge';
$flash = '';
$flashType = 'success';

// ---- Confirm discharge (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_discharge'])) {
    $admissionId  = (int)$_POST['admission_id'];
    $dischargeDate = $_POST['discharge_date'];

    $stmt = $pdo->prepare("SELECT ad.*, r.per_day_rate, r.id AS room_id FROM admissions ad JOIN rooms r ON r.id = ad.room_id WHERE ad.id = ? AND ad.status = 'admitted'");
    $stmt->execute([$admissionId]);
    $admission = $stmt->fetch();

    if (!$admission) {
        $flash = 'This admission was not found or has already been discharged.';
        $flashType = 'danger';
    } else {
        $days = (new DateTime($admission['admission_date']))->diff(new DateTime($dischargeDate))->days;
        $days = max(1, $days);
        $roomCharges = $days * (float)$admission['per_day_rate'];

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE admissions SET discharge_date = ?, status = 'discharged' WHERE id = ?")
            ->execute([$dischargeDate, $admissionId]);

        $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$admission['room_id']]);

        $bill = $pdo->prepare("SELECT * FROM bills WHERE admission_id = ?");
        $bill->execute([$admissionId]);
        $bill = $bill->fetch();

        if ($bill) {
            $total = (float)$bill['checkup_fee'] + (float)$bill['operation_charges'] + $roomCharges;
            $pdo->prepare("UPDATE bills SET room_charges = ?, total_amount = ? WHERE id = ?")
                ->execute([$roomCharges, $total, $bill['id']]);
        } else {
            $total = $roomCharges;
            $pdo->prepare("INSERT INTO bills (patient_id, admission_id, room_charges, total_amount, payment_status) VALUES (?, ?, ?, ?, 'unpaid')")
                ->execute([$admission['patient_id'], $admissionId, $roomCharges, $total]);
        }

        $pdo->commit();
        $flash = "Patient discharged successfully. Room charges of " . money($roomCharges) . " for $days day(s) added to the final bill.";
    }
}

$viewId = $_GET['admission_id'] ?? null;
$previewDate = $_GET['discharge_date'] ?? date('Y-m-d');
$detail = null;

if ($viewId) {
    $stmt = $pdo->prepare("
        SELECT ad.*, p.name AS patient_name, p.mrn, r.room_no, r.per_day_rate, u.name AS doctor_name
        FROM admissions ad
        JOIN patients p ON p.id = ad.patient_id
        JOIN rooms r ON r.id = ad.room_id
        JOIN doctors d ON d.id = ad.doctor_id JOIN users u ON u.id = d.user_id
        WHERE ad.id = ? AND ad.status = 'admitted'
    ");
    $stmt->execute([$viewId]);
    $detail = $stmt->fetch();

    if ($detail) {
        $billStmt = $pdo->prepare("SELECT * FROM bills WHERE admission_id = ?");
        $billStmt->execute([$viewId]);
        $bill = $billStmt->fetch() ?: ['checkup_fee' => 0, 'operation_charges' => 0];

        $days = (new DateTime($detail['admission_date']))->diff(new DateTime($previewDate))->days;
        $days = max(1, $days);
        $roomCharges = $days * (float)$detail['per_day_rate'];
        $total = (float)$bill['checkup_fee'] + (float)$bill['operation_charges'] + $roomCharges;
    }
}

$admittedList = $pdo->query("
    SELECT ad.id, p.name AS patient_name, p.mrn, r.room_no, ad.admission_date
    FROM admissions ad JOIN patients p ON p.id = ad.patient_id JOIN rooms r ON r.id = ad.room_id
    WHERE ad.status = 'admitted' ORDER BY ad.id DESC
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><i class="bi bi-check-circle me-1"></i><?= e($flash) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-header"><h5><i class="bi bi-door-open me-2"></i>Admitted Patients</h5></div>
      <div class="panel-body p-0">
        <div class="table-responsive">
          <table class="table table-hms mb-0">
            <thead><tr><th>Patient</th><th>Room</th><th>Since</th><th></th></tr></thead>
            <tbody>
              <?php if (!$admittedList): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No admitted patients right now.</td></tr>
              <?php endif; ?>
              <?php foreach ($admittedList as $a): ?>
              <tr class="<?= (int)$viewId === (int)$a['id'] ? 'table-active' : '' ?>">
                <td><?= e($a['patient_name']) ?> <span class="text-muted">(<?= e($a['mrn']) ?>)</span></td>
                <td><?= e($a['room_no']) ?></td>
                <td><?= e(date('d M', strtotime($a['admission_date']))) ?></td>
                <td><a href="?admission_id=<?= $a['id'] ?>" class="btn btn-outline-hms btn-sm">Discharge</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <?php if (!$detail): ?>
      <div class="panel">
        <div class="panel-body text-center text-muted py-5">
          <i class="bi bi-arrow-left-circle" style="font-size:1.8rem;"></i>
          <p class="mt-2 mb-0">Select an admitted patient from the list to begin discharge.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="panel mb-3">
        <div class="panel-header"><h5><i class="bi bi-person me-2"></i><?= e($detail['patient_name']) ?> — <?= e($detail['mrn']) ?></h5></div>
        <div class="panel-body">
          <p class="mb-1"><strong>Room:</strong> <?= e($detail['room_no']) ?> (Rs. <?= number_format($detail['per_day_rate'],2) ?>/day)</p>
          <p class="mb-1"><strong>Doctor:</strong> Dr. <?= e($detail['doctor_name']) ?></p>
          <p class="mb-3"><strong>Admission Date:</strong> <?= e($detail['admission_date']) ?></p>

          <form method="GET" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="admission_id" value="<?= $detail['id'] ?>">
            <div class="col-8">
              <label class="form-label">Discharge Date</label>
              <input type="date" name="discharge_date" class="form-control" value="<?= e($previewDate) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-4">
              <button class="btn btn-outline-hms w-100">Recalculate</button>
            </div>
          </form>

          <div class="panel" style="background:var(--primary-light); border:none;">
            <div class="panel-body">
              <div class="bill-line"><span>Checkup Fee</span><span><?= money($bill['checkup_fee']) ?></span></div>
              <div class="bill-line"><span>Operation Charges</span><span><?= money($bill['operation_charges']) ?></span></div>
              <div class="bill-line"><span>Room Charges (<?= $days ?> day<?= $days > 1 ? 's' : '' ?> × Rs. <?= number_format($detail['per_day_rate'],2) ?>)</span><span><?= money($roomCharges) ?></span></div>
              <div class="bill-line total"><span>Total Bill</span><span><?= money($total) ?></span></div>
            </div>
          </div>

          <form method="POST" class="mt-3">
            <input type="hidden" name="admission_id" value="<?= $detail['id'] ?>">
            <input type="hidden" name="discharge_date" value="<?= e($previewDate) ?>">
            <button type="submit" name="confirm_discharge" class="btn btn-accent w-100">
              <i class="bi bi-check2-circle me-1"></i> Confirm Discharge &amp; Generate Final Bill
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
