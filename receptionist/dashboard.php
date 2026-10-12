<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('receptionist');

$page_title = 'Reception Dashboard';
$page_sub   = 'Front desk overview';
$active     = 'dashboard';

$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$todayAppts    = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$admittedNow   = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status='admitted'")->fetchColumn();
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();

$todayAppointments = $pdo->query("
    SELECT a.*, p.name AS patient_name, p.mrn, u.name AS doctor_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner">
  <h4><i class="bi bi-clipboard2-pulse-fill me-2"></i>Welcome back, <?= e($_SESSION['user_name']) ?></h4>
  <p><?= e(date('l, j F Y')) ?> — front desk overview for today.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-teal"><i class="bi bi-people-fill"></i></div>
      <div><div class="value"><?= (int)$totalPatients ?></div><div class="label">Total Patients</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-coral"><i class="bi bi-calendar2-check-fill"></i></div>
      <div><div class="value"><?= (int)$todayAppts ?></div><div class="label">Today's Appointments</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-amber"><i class="bi bi-hospital-fill"></i></div>
      <div><div class="value"><?= (int)$admittedNow ?></div><div class="label">Currently Admitted</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-green"><i class="bi bi-door-open"></i></div>
      <div><div class="value"><?= (int)$availableRooms ?></div><div class="label">Rooms Available</div></div></div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><a href="patients.php" class="btn btn-hms w-100 py-3"><i class="bi bi-person-plus me-2"></i>New Patient</a></div>
  <div class="col-md-3"><a href="appointments.php" class="btn btn-outline-hms w-100 py-3"><i class="bi bi-calendar-plus me-2"></i>Book Appointment</a></div>
  <div class="col-md-3"><a href="admission.php" class="btn btn-outline-hms w-100 py-3"><i class="bi bi-hospital me-2"></i>Admit Patient</a></div>
  <div class="col-md-3"><a href="discharge.php" class="btn btn-outline-hms w-100 py-3"><i class="bi bi-door-open me-2"></i>Discharge Patient</a></div>
</div>

<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-calendar-event me-2"></i>Today's Appointments</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Fee</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (!$todayAppointments): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No appointments today yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($todayAppointments as $a): ?>
          <tr>
            <td><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></td>
            <td><?= e($a['patient_name']) ?> <span class="text-muted">(<?= e($a['mrn']) ?>)</span></td>
            <td>Dr. <?= e($a['doctor_name']) ?></td>
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
