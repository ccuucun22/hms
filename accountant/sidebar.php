<div class="sidebar" id="accountantSidebar">
  <div class="brand">
    <div class="mark"><i class="bi bi-heart-pulse-fill"></i></div>
    <div>
      <span>CarePoint</span>
      <small>HMS PLATFORM</small>
    </div>
  </div>

  <div class="sidebar-role">Accounts Panel</div>
  <nav>
    <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a href="billing.php" class="<?= $active === 'billing' ? 'active' : '' ?>"><i class="bi bi-receipt"></i> Billing</a>
    <a href="reports.php" class="<?= $active === 'reports' ? 'active' : '' ?>"><i class="bi bi-bar-chart-line"></i> Reports</a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= base_url() ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div>
