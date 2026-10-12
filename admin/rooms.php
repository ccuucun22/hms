<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$page_title = 'Room Management';
$page_sub   = 'Wards, rooms and live occupancy';
$active     = 'rooms';
$flash = '';
$flashType = 'success';

// ---- Handle: Add / Update room (same form serves both) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $roomNo = trim($_POST['room_no']);
    $type   = $_POST['room_type'];
    $rate   = (float)$_POST['per_day_rate'];

    $check = $pdo->prepare("SELECT id FROM rooms WHERE room_no = ? AND id != ?");
    $check->execute([$roomNo, $roomId]);

    if ($check->fetch()) {
        $flash = 'A room with this number already exists.';
        $flashType = 'danger';
    } elseif ($roomId > 0) {
        $pdo->prepare("UPDATE rooms SET room_no = ?, room_type = ?, per_day_rate = ? WHERE id = ?")
            ->execute([$roomNo, $type, $rate, $roomId]);
        $flash = 'Room updated successfully.';
    } else {
        $pdo->prepare("INSERT INTO rooms (room_no, room_type, per_day_rate) VALUES (?, ?, ?)")
            ->execute([$roomNo, $type, $rate]);
        $flash = 'Room added successfully.';
    }

    if ($flashType !== 'danger') {
        header('Location: rooms.php?saved=1');
        exit;
    }
}
if (isset($_GET['saved'])) { $flash = 'Saved successfully.'; }

// ---- Handle: Toggle maintenance ----
if (isset($_GET['maintenance'])) {
    $id = (int)$_GET['maintenance'];
    $pdo->prepare("UPDATE rooms SET status = IF(status='maintenance','available','maintenance') WHERE id = ? AND status != 'occupied'")->execute([$id]);
    header('Location: rooms.php');
    exit;
}

// ---- Handle: Delete room ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE room_id = ?");
    $check->execute([$id]);
    $statusCheck = $pdo->prepare("SELECT status FROM rooms WHERE id = ?");
    $statusCheck->execute([$id]);

    if ($statusCheck->fetchColumn() === 'occupied') {
        header('Location: rooms.php?err=' . urlencode('Cannot delete — this room currently has a patient. Discharge first.'));
        exit;
    } elseif ($check->fetchColumn() > 0) {
        header('Location: rooms.php?err=' . urlencode('Cannot delete — this room has admission history. Mark it under Maintenance instead.'));
        exit;
    }
    $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
    header('Location: rooms.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) { $flash = 'Room deleted successfully.'; }
if (isset($_GET['err'])) { $flash = $_GET['err']; $flashType = 'danger'; }

// ---- Load room for editing, if requested ----
$editRoom = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRoom = $stmt->fetch();
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_type, room_no")->fetchAll();
$summary = $pdo->query("SELECT status, COUNT(*) c FROM rooms GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-hms alert-auto-hide mb-3"><?= e($flash) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="stat-card"><div class="icon-box bg-green"><i class="bi bi-door-open"></i></div>
      <div><div class="value"><?= (int)($summary['available'] ?? 0) ?></div><div class="label">Available</div></div></div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card"><div class="icon-box bg-coral"><i class="bi bi-door-closed"></i></div>
      <div><div class="value"><?= (int)($summary['occupied'] ?? 0) ?></div><div class="label">Occupied</div></div></div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card"><div class="icon-box bg-amber"><i class="bi bi-tools"></i></div>
      <div><div class="value"><?= (int)($summary['maintenance'] ?? 0) ?></div><div class="label">Under Maintenance</div></div></div>
  </div>
</div>

<!-- ===== Add / Edit Room (top) ===== -->
<div class="panel mb-4">
  <div class="panel-header">
    <h5><i class="bi bi-<?= $editRoom ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $editRoom ? 'Edit Room — ' . e($editRoom['room_no']) : 'Add Room' ?></h5>
    <?php if ($editRoom): ?><a href="rooms.php" class="btn btn-outline-hms btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <div class="panel-body">
    <form method="POST">
      <input type="hidden" name="room_id" value="<?= $editRoom ? (int)$editRoom['id'] : '' ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Room Number</label>
          <input type="text" name="room_no" class="form-control" placeholder="e.g. P-203" value="<?= e($editRoom['room_no'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Room Type</label>
          <select name="room_type" class="form-select" required>
            <?php foreach (['General' => 'General Ward', 'Private' => 'Private Room', 'ICU' => 'ICU', 'Emergency' => 'Emergency'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($editRoom['room_type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Per Day Rate (Rs.)</label>
          <input type="number" step="0.01" name="per_day_rate" class="form-control" value="<?= $editRoom ? e((string)$editRoom['per_day_rate']) : '' ?>" required>
        </div>
      </div>
      <button type="submit" name="save_room" class="btn btn-hms mt-3"><?= $editRoom ? 'Update Room' : 'Add Room' ?></button>
    </form>
  </div>
</div>

<!-- ===== All Rooms (below) ===== -->
<div class="panel">
  <div class="panel-header"><h5><i class="bi bi-hospital me-2"></i>All Rooms</h5></div>
  <div class="panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hms mb-0">
        <thead><tr><th>Room No.</th><th>Type</th><th>Per Day Rate</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$rooms): ?><tr><td colspan="5" class="text-center text-muted py-4">No rooms added yet.</td></tr><?php endif; ?>
          <?php foreach ($rooms as $r): ?>
          <tr class="<?= (int)($editRoom['id'] ?? 0) === (int)$r['id'] ? 'table-active' : '' ?>">
            <td><strong><?= e($r['room_no']) ?></strong></td>
            <td><?= e($r['room_type']) ?></td>
            <td><?= money($r['per_day_rate']) ?></td>
            <td><span class="badge-soft <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td class="d-flex gap-1">
              <a href="?edit=<?= $r['id'] ?>" class="btn btn-outline-hms btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
              <?php if ($r['status'] !== 'occupied'): ?>
                <a href="?maintenance=<?= $r['id'] ?>" class="btn btn-outline-hms btn-sm">
                  <?= $r['status'] === 'maintenance' ? 'Mark Available' : 'Maintenance' ?>
                </a>
                <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete room <?= e(addslashes($r['room_no'])) ?>?');"><i class="bi bi-trash3"></i></a>
              <?php else: ?>
                <span class="text-muted align-self-center" style="font-size:0.8rem;">In use</span>
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
