<?php
require_once __DIR__ . '/includes/auth.php';

// If already logged in, go straight to the right dashboard.
if (is_logged_in()) {
    redirect_by_role($_SESSION['role']);
}

$error = '';
if (isset($_GET['expired'])) {
    $error = 'Your session has ended. Please sign in again.';
} elseif (isset($_GET['error'])) {
    $error = $_GET['error'] === '2'
        ? 'This account has been deactivated. Contact the administrator.'
        : 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CarePoint HMS — Hospital Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">

  <div class="auth-aside">
    <div class="mark"><i class="bi bi-heart-pulse-fill"></i></div>
    <h1>Run your hospital from one connected system.</h1>
    <p>CarePoint HMS brings patients, appointments, admissions, rooms and billing together — with every role seeing exactly what they need.</p>

    <div class="feature-list">
      <div class="item"><i class="bi bi-calendar2-check"></i> Book checkups &amp; auto-generate consultation fees</div>
      <div class="item"><i class="bi bi-hospital"></i> Allocate rooms on admission, free them on discharge</div>
      <div class="item"><i class="bi bi-scissors"></i> Track operation charges separately from checkups</div>
      <div class="item"><i class="bi bi-receipt"></i> One combined, itemised bill at discharge</div>
    </div>

    <svg class="pulse-line" viewBox="0 0 600 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <polyline points="0,40 80,40 100,15 120,65 140,40 220,40 240,10 260,70 280,40 600,40"
        fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <h2 class="brand-font">Welcome back</h2>
      <p class="sub">Sign in to your CarePoint HMS account</p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-hms mb-3"><i class="bi bi-exclamation-circle me-1"></i> <?= e($error) ?></div>
      <?php endif; ?>

      <div id="login-alert"></div>

      <form action="login_process.php" method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" placeholder="you@carepoint.com" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-hms w-100 mt-2">Sign In</button>
      </form>

      <div class="demo-creds">
        <strong><i class="bi bi-info-circle me-1"></i>Demo accounts (password: <code>password123</code>)</strong>
        Admin: <code>admin@carepoint.com</code><br>
        Doctor: <code>ahmed.khan@carepoint.com</code><br>
        Receptionist: <code>reception@carepoint.com</code><br>
        Accountant: <code>accounts@carepoint.com</code>
      </div>
    </div>
  </div>

</div>

</body>
</html>
