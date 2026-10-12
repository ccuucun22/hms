<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('doctor');

$page_title = 'My Appointments';
$page_sub   = 'All checkups booked with you';
$active     = 'appointments';

$doc = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
$doc->execute([$_SESSION['user_id']]);
$doctor = $doc->fetch();
$doctorId = $doctor['id'] ?? 0;

if (isset($_GET['mark'])) {
    $id = (int)$_GET['mark'];
    $status = $_GET['mark_status'] === 'cancelled' ? 'cancelled' : 'completed';
    $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?")->execute([$status, $id, $doctorId]);
    header('Location: appointments.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$sql = "SELECT a.*, p.name AS patient_name, p.mrn FROM appointments a JOIN patients p ON p.id = a.patient_id WHERE a.doctor_id = ?";
$params = [$doctorId];
if (in_array($filter, ['scheduled', 'completed', 'cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="panel mb-3">
  <div class="panel-body py-3 d-flex gap-2 flex-wrap">
    <a href="?status=all" class="btn btn-sm <?= $filter==='all'?'btn-hms':'btn-outline-hms' ?>">All</a>
    <a href="?status=scheduled" class="btn btn-sm <?= $filter==='scheduled'?'btn-hms':'btn-outline-hms' ?>">Scheduled</a>
    <a href="?status=completed" class="btn btn-sm <?= $filter==='completed'?'btn-hms':'btn-outline-hms' ?>">Completed</a>
    <a href="?status=cancelled" class="btn btn-sm <?= $filter==='cancelled'?'btn-hms':'btn-outline-hms' ?>">Cancelled</a>
  </div>
</div>

<div class="panel">
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>MRN</th><th>Fee</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$appointments): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No appointments found.</td></tr>
          <?php endif; ?>
          <?php foreach ($appointments as $a): ?>
          <tr>
            <td><?= e(date('d M Y', strtotime($a['appointment_date']))) ?></td>
            <td><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></td>
            <td><?= e($a['patient_name']) ?></td>
            <td><?= e($a['mrn']) ?></td>
            <td><?= money($a['checkup_fee']) ?></td>
            <td><span class="badge-soft <?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
            <td>
              <?php if ($a['status'] === 'scheduled'): ?>
                <a href="?mark=<?= $a['id'] ?>&mark_status=completed" class="btn btn-outline-hms btn-sm">Mark Done</a>
                <a href="?mark=<?= $a['id'] ?>&mark_status=cancelled" class="btn btn-outline-hms btn-sm">Cancel</a>
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
