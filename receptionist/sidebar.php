<div class="sidebar" id="receptionSidebar">
  <div class="brand">
    <div class="mark"><i class="bi bi-heart-pulse-fill"></i></div>
    <div>
      <span>CarePoint</span>
      <small>HMS PLATFORM</small>
    </div>
  </div>

  <div class="sidebar-role">Reception Desk</div>
  <nav>
    <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a href="patients.php" class="<?= $active === 'patients' ? 'active' : '' ?>"><i class="bi bi-people"></i> Patients</a>
    <a href="appointments.php" class="<?= $active === 'appointments' ? 'active' : '' ?>"><i class="bi bi-calendar2-check"></i> Appointments</a>
    <a href="admission.php" class="<?= $active === 'admission' ? 'active' : '' ?>"><i class="bi bi-hospital"></i> Admit Patient</a>
    <a href="discharge.php" class="<?= $active === 'discharge' ? 'active' : '' ?>"><i class="bi bi-door-open"></i> Discharge Patient</a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= base_url() ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div>
