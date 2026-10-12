<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('doctor');

$page_title = 'Doctor Dashboard';
$page_sub   = 'Your schedule and patients at a glance';
$active     = 'dashboard';

$doc = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
$doc->execute([$_SESSION['user_id']]);
$doctor = $doc->fetch();
$doctorId = $doctor['id'] ?? 0;

$todayCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
$todayCount->execute([$doctorId]);
$todayCount = $todayCount->fetchColumn();

$totalPatients = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
$totalPatients->execute([$doctorId]);
$totalPatients = $totalPatients->fetchColumn();

$admittedNow = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE doctor_id = ? AND status = 'admitted'");
$admittedNow->execute([$doctorId]);
$admittedNow = $admittedNow->fetchColumn();

$todayList = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, p.mrn
    FROM appointments a JOIN patients p ON p.id = a.patient_id
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");
$todayList->execute([$doctorId]);
$todayList = $todayList->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner">
  <h4><i class="bi bi-heart-pulse-fill me-2"></i>Welcome back, <?= e($_SESSION['user_name']) ?></h4>
  <p><?= e(date('l, j F Y')) ?> — here's your schedule and patients at a glance.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="stat-card"><div class="icon-box bg-teal"><i class="bi bi-calendar2-check-fill"></i></div>
      <div><div class="value"><?= (int)$todayCount ?></div><div class="label">Today's Appointments</div></div></div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card"><div class="icon-box bg-coral"><i class="bi bi-people-fill"></i></div>
      <div><div class="value"><?= (int)$totalPatients ?></div><div class="label">Total Patients Seen</div></div></div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card"><div class="icon-box bg-amber"><i class="bi bi-hospital-fill"></i></div>
      <div><div class="value"><?= (int)$admittedNow ?></div><div class="label">Currently Admitted</div></div></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-calendar-event me-2"></i>Today's Schedule</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Time</th><th>Patient</th><th>MRN</th><th>Checkup Fee</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (!$todayList): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No appointments scheduled for today.</td></tr>
          <?php endif; ?>
          <?php foreach ($todayList as $a): ?>
          <tr>
            <td><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></td>
            <td><?= e($a['patient_name']) ?></td>
            <td><?= e($a['mrn']) ?></td>
            <td><?= money($a['checkup_fee']) ?></td>
            <td><span class="badge-soft <?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
