<div class="sidebar" id="adminSidebar">
  <div class="brand">
    <div class="mark"><i class="bi bi-heart-pulse-fill"></i></div>
    <div>
      <span>CarePoint</span>
      <small>HMS PLATFORM</small>
    </div>
  </div>

  <div class="sidebar-role">Admin Panel</div>
  <nav>
    <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a href="doctors.php" class="<?= $active === 'doctors' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Doctors</a>
    <a href="rooms.php" class="<?= $active === 'rooms' ? 'active' : '' ?>"><i class="bi bi-door-open"></i> Room Management</a>
    <a href="users.php" class="<?= $active === 'users' ? 'active' : '' ?>"><i class="bi bi-people"></i> Staff Accounts</a>
    <a href="reports.php" class="<?= $active === 'reports' ? 'active' : '' ?>"><i class="bi bi-bar-chart-line"></i> Reports</a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= base_url() ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div>
