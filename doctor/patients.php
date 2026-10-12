<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('doctor');

$page_title = 'My Patients';
$page_sub   = 'Patients you have treated';
$active     = 'patients';

$doc = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
$doc->execute([$_SESSION['user_id']]);
$doctor = $doc->fetch();
$doctorId = $doctor['id'] ?? 0;

$viewId = $_GET['view'] ?? null;

if ($viewId) {
    $p = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $p->execute([$viewId]);
    $patient = $p->fetch();

    $appts = $pdo->prepare("
        SELECT a.*, u.name AS doctor_name FROM appointments a
        JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id
        WHERE a.patient_id = ? ORDER BY a.appointment_date DESC
    ");
    $appts->execute([$viewId]);
    $appts = $appts->fetchAll();

    $admissions = $pdo->prepare("
        SELECT ad.*, r.room_no, u.name AS doctor_name FROM admissions ad
        JOIN rooms r ON r.id = ad.room_id
        JOIN doctors d ON d.id = ad.doctor_id JOIN users u ON u.id = d.user_id
        WHERE ad.patient_id = ? ORDER BY ad.admission_date DESC
    ");
    $admissions->execute([$viewId]);
    $admissions = $admissions->fetchAll();
}

$patients = $pdo->prepare("
    SELECT DISTINCT p.* FROM patients p
    JOIN appointments a ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    ORDER BY p.name
");
$patients->execute([$doctorId]);
$patients = $patients->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($viewId && $patient): ?>

  <a href="patients.php" class="btn btn-outline-hms btn-sm mb-3"><i class="bi bi-arrow-left"></i> Back to Patient List</a>

  <div class="panel mb-4">
    <div class="panel-body d-flex flex-wrap justify-content-between gap-3">
      <div>
        <h5 class="mb-1"><?= e($patient['name']) ?></h5>
        <p class="text-muted mb-0"><?= e($patient['mrn']) ?> · <?= e($patient['age']) ?> yrs · <?= e($patient['gender']) ?> · <?= e($patient['blood_group']) ?></p>
      </div>
      <div class="text-end">
        <div class="text-muted" style="font-size:0.85rem;">Contact</div>
        <div><?= e($patient['contact']) ?></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="panel">
        <div class="panel-header"><h5><i class="bi bi-calendar2-check me-2"></i>Appointment History</h5></div>
        <div class="panel-body p-0">
          <table class="table table-hms mb-0">
            <thead><tr><th>Date</th><th>Doctor</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$appts): ?><tr><td colspan="3" class="text-center text-muted py-3">No records.</td></tr><?php endif; ?>
              <?php foreach ($appts as $a): ?>
              <tr>
                <td><?= e(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                <td>Dr. <?= e($a['doctor_name']) ?></td>
                <td><span class="badge-soft <?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="panel">
        <div class="panel-header"><h5><i class="bi bi-hospital me-2"></i>Admission History</h5></div>
        <div class="panel-body p-0">
          <table class="table table-hms mb-0">
            <thead><tr><th>Room</th><th>Admitted</th><th>Discharged</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$admissions): ?><tr><td colspan="4" class="text-center text-muted py-3">No records.</td></tr><?php endif; ?>
              <?php foreach ($admissions as $a): ?>
              <tr>
                <td><?= e($a['room_no']) ?></td>
                <td><?= e($a['admission_date']) ?></td>
                <td><?= e($a['discharge_date'] ?? '—') ?></td>
                <td><span class="badge-soft <?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<?php else: ?>

  <div class="panel">
    <div class="panel-body p-0">
      <div class="table-responsive">
        <table class="table table-hms mb-0">
          <thead><tr><th>MRN</th><th>Name</th><th>Age / Gender</th><th>Contact</th><th></th></tr></thead>
          <tbody>
            <?php if (!$patients): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No patients yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($patients as $p): ?>
            <tr>
              <td><?= e($p['mrn']) ?></td>
              <td><?= e($p['name']) ?></td>
              <td><?= e($p['age']) ?> / <?= e($p['gender']) ?></td>
              <td><?= e($p['contact']) ?></td>
              <td><a href="?view=<?= $p['id'] ?>" class="btn btn-outline-hms btn-sm">View History</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
