<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('accountant');

$page_title = 'Accounts Dashboard';
$page_sub   = 'Billing overview';
$active     = 'dashboard';

$unpaidCount  = $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status != 'paid'")->fetchColumn();
$unpaidAmount = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status != 'paid'")->fetchColumn();
$todayRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status='paid' AND DATE(created_at) = CURDATE()")->fetchColumn();
$monthRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status='paid' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

$recentBills = $pdo->query("
    SELECT b.*, p.name AS patient_name, p.mrn FROM bills b
    JOIN patients p ON p.id = b.patient_id
    ORDER BY b.id DESC LIMIT 8
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner">
  <h4><i class="bi bi-receipt-cutoff me-2"></i>Welcome back, <?= e($_SESSION['user_name']) ?></h4>
  <p><?= e(date('l, j F Y')) ?> — billing overview for today.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-coral"><i class="bi bi-exclamation-circle"></i></div>
      <div><div class="value"><?= (int)$unpaidCount ?></div><div class="label">Unpaid / Partial Bills</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-amber"><i class="bi bi-hourglass-split"></i></div>
      <div><div class="value"><?= money($unpaidAmount) ?></div><div class="label">Outstanding Amount</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-teal"><i class="bi bi-cash"></i></div>
      <div><div class="value"><?= money($todayRevenue) ?></div><div class="label">Collected Today</div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card"><div class="icon-box bg-green"><i class="bi bi-cash-stack"></i></div>
      <div><div class="value"><?= money($monthRevenue) ?></div><div class="label">Collected This Month</div></div></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <h5><i class="bi bi-receipt me-2"></i>Recent Bills</h5>
    <a href="billing.php" class="btn btn-outline-hms btn-sm">View All Billing</a>
  </div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Patient</th><th>Checkup</th><th>Operation</th><th>Room</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (!$recentBills): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No bills yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentBills as $b): ?>
          <tr>
            <td><?= e($b['patient_name']) ?> <span class="text-muted">(<?= e($b['mrn']) ?>)</span></td>
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
