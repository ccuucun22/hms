<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$page_title = 'Admin Dashboard';
$page_sub   = 'Hospital-wide overview';
$active     = 'dashboard';

$totalPatients   = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalDoctors    = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status='active'")->fetchColumn();
$admittedNow     = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status='admitted'")->fetchColumn();
$todayAppts      = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$totalRooms      = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupiedRooms   = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn();
$totalRevenue    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status='paid'")->fetchColumn();
$pendingRevenue  = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status!='paid'")->fetchColumn();

$recentAdmissions = $pdo->query("
    SELECT a.id, p.name AS patient_name, r.room_no, d.id AS doc_id, u.name AS doctor_name, a.admission_date, a.status
    FROM admissions a
    JOIN patients p ON p.id = a.patient_id
    JOIN rooms r ON r.id = a.room_id
    JOIN doctors d ON d.id = a.doctor_id
    JOIN users u ON u.id = d.user_id
    ORDER BY a.id DESC LIMIT 6
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner">
  <h4><i class="bi bi-grid-1x2-fill me-2"></i>Welcome back, <?= e($_SESSION['user_name']) ?></h4>
  <p>Here's what's happening across the hospital today — <?= e(date('l, j F Y')) ?>.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="icon-box bg-teal"><i class="bi bi-people-fill"></i></div>
      <div><div class="value"><?= (int)$totalPatients ?></div><div class="label">Total Patients</div></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="icon-box bg-coral"><i class="bi bi-person-badge-fill"></i></div>
      <div><div class="value"><?= (int)$totalDoctors ?></div><div class="label">Active Doctors</div></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="icon-box bg-amber"><i class="bi bi-hospital-fill"></i></div>
      <div><div class="value"><?= (int)$occupiedRooms ?> / <?= (int)$totalRooms ?></div><div class="label">Rooms Occupied</div></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="icon-box bg-slate"><i class="bi bi-calendar2-check-fill"></i></div>
      <div><div class="value"><?= (int)$todayAppts ?></div><div class="label">Today's Appointments</div></div>
    </div>
  </div>
</div>

<div class="panel mb-4">
  <div class="panel-header"><h5><i class="bi bi-graph-up-arrow me-2"></i>Financial Snapshot</h5></div>
  <div class="panel-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="stat-card">
          <div class="icon-box bg-green"><i class="bi bi-cash-stack"></i></div>
          <div><div class="value"><?= money($totalRevenue) ?></div><div class="label">Total Revenue Collected</div></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card">
          <div class="icon-box bg-coral"><i class="bi bi-hourglass-split"></i></div>
          <div><div class="value"><?= money($pendingRevenue) ?></div><div class="label">Pending / Unpaid Bills</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <h5><i class="bi bi-clock-history me-2"></i>Recent Admissions</h5>
    <a href="rooms.php" class="btn btn-outline-hms btn-sm">View Room Status</a>
  </div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead>
          <tr><th>Patient</th><th>Room</th><th>Doctor</th><th>Admission Date</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (!$recentAdmissions): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No admissions yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentAdmissions as $a): ?>
          <tr>
            <td><?= e($a['patient_name']) ?></td>
            <td><?= e($a['room_no']) ?></td>
            <td>Dr. <?= e($a['doctor_name']) ?></td>
            <td><?= e($a['admission_date']) ?></td>
            <td><span class="badge-soft <?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
