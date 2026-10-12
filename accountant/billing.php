<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('accountant');

$page_title = 'Billing';
$page_sub   = 'View and settle patient bills';
$active     = 'billing';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $billId = (int)$_POST['bill_id'];
    $method = $_POST['payment_method'];
    $pdo->prepare("UPDATE bills SET payment_status = 'paid', payment_method = ? WHERE id = ?")->execute([$method, $billId]);
    $flash = 'Bill marked as paid.';
}

$filter = $_GET['status'] ?? 'all';
$sql = "SELECT b.*, p.name AS patient_name, p.mrn FROM bills b JOIN patients p ON p.id = b.patient_id";
if (in_array($filter, ['unpaid', 'paid', 'partial'])) {
    $sql .= " WHERE b.payment_status = " . $pdo->quote($filter);
}
$sql .= " ORDER BY b.id DESC";
$bills = $pdo->query($sql)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-success alert-hms alert-auto-hide mb-3"><i class="bi bi-check-circle me-1"></i><?= e($flash) ?></div><?php endif; ?>

<div class="panel mb-3">
  <div class="panel-body py-3 d-flex gap-2 flex-wrap">
    <a href="?status=all" class="btn btn-sm <?= $filter==='all'?'btn-hms':'btn-outline-hms' ?>">All</a>
    <a href="?status=unpaid" class="btn btn-sm <?= $filter==='unpaid'?'btn-hms':'btn-outline-hms' ?>">Unpaid</a>
    <a href="?status=partial" class="btn btn-sm <?= $filter==='partial'?'btn-hms':'btn-outline-hms' ?>">Partial</a>
    <a href="?status=paid" class="btn btn-sm <?= $filter==='paid'?'btn-hms':'btn-outline-hms' ?>">Paid</a>
  </div>
</div>

<div class="panel">
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Patient</th><th>Checkup</th><th>Operation</th><th>Room</th><th>Total</th><th>Status</th><th>Settle</th></tr></thead>
        <tbody>
          <?php if (!$bills): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No bills found.</td></tr>
          <?php endif; ?>
          <?php foreach ($bills as $b): ?>
          <tr>
            <td><?= e($b['patient_name']) ?> <span class="text-muted">(<?= e($b['mrn']) ?>)</span></td>
            <td><?= money($b['checkup_fee']) ?></td>
            <td><?= money($b['operation_charges']) ?></td>
            <td><?= money($b['room_charges']) ?></td>
            <td><strong><?= money($b['total_amount']) ?></strong></td>
            <td><span class="badge-soft <?= e($b['payment_status']) ?>"><?= e(ucfirst($b['payment_status'])) ?></span></td>
            <td>
              <?php if ($b['payment_status'] !== 'paid'): ?>
              <form method="POST" class="d-flex gap-1">
                <input type="hidden" name="bill_id" value="<?= $b['id'] ?>">
                <select name="payment_method" class="form-select form-select-sm" style="width:auto;">
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="online">Online</option>
                </select>
                <button type="submit" name="mark_paid" class="btn btn-hms btn-sm">Mark Paid</button>
              </form>
              <?php else: ?>
                <span class="text-muted" style="font-size:0.8rem;">via <?= e(ucfirst($b['payment_method'])) ?></span>
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
