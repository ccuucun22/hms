<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$page_title = 'Reports';
$page_sub   = 'Revenue breakdown and billing summary';
$active     = 'reports';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT b.*, p.name AS patient_name
    FROM bills b JOIN patients p ON p.id = b.patient_id
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$from, $to]);
$bills = $stmt->fetchAll();

$totals = ['checkup_fee' => 0, 'operation_charges' => 0, 'room_charges' => 0, 'total_amount' => 0];
foreach ($bills as $b) {
    $totals['checkup_fee']       += $b['checkup_fee'];
    $totals['operation_charges'] += $b['operation_charges'];
    $totals['room_charges']      += $b['room_charges'];
    $totals['total_amount']      += $b['total_amount'];
}

require __DIR__ . '/../includes/header.php';
?>

<div class="panel mb-4">
  <div class="panel-body">
    <form class="row g-3 align-items-end" method="GET">
      <div class="col-sm-4">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
      </div>
      <div class="col-sm-4">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
      </div>
      <div class="col-sm-4">
        <button class="btn btn-hms w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-teal"><i class="bi bi-clipboard2-pulse"></i></div>
      <div><div class="value"><?= money($totals['checkup_fee']) ?></div><div class="label">Checkup Fees</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-coral"><i class="bi bi-scissors"></i></div>
      <div><div class="value"><?= money($totals['operation_charges']) ?></div><div class="label">Operation Charges</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-amber"><i class="bi bi-door-open"></i></div>
      <div><div class="value"><?= money($totals['room_charges']) ?></div><div class="label">Room Charges</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-green"><i class="bi bi-cash-stack"></i></div>
      <div><div class="value"><?= money($totals['total_amount']) ?></div><div class="label">Total Billed</div></div></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-receipt me-2"></i>Bills in Range</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Date</th><th>Patient</th><th>Checkup</th><th>Operation</th><th>Room</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (!$bills): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No bills in this date range.</td></tr>
          <?php endif; ?>
          <?php foreach ($bills as $b): ?>
          <tr>
            <td><?= e(date('d M Y', strtotime($b['created_at']))) ?></td>
            <td><?= e($b['patient_name']) ?></td>
            <td><?= money($b['checkup_fee']) ?></td>
            <td><?= money($b['operation_charges']) ?></td>
            <td><?= money($b['room_charges']) ?></td>
            <td><strong><?= money($b['total_amount']) ?></strong></td>
            <td><span class="badge-soft <?= e($b['payment_status']) ?>"><?= e(ucfirst($b['payment_status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
