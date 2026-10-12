<?php
/**
 * Shared header partial.
 * Expects (optionally) before include: $page_title, $page_sub, $active
 */
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub ?? '';
$active     = $active ?? '';
$role       = $_SESSION['role'] ?? '';
$user_name  = $_SESSION['user_name'] ?? 'User';
$base       = base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> · CarePoint HMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">

  <div class="sidebar-backdrop"></div>

  <?php require __DIR__ . '/../' . $role . '/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="menu-toggle"><i class="bi bi-list"></i></button>
        <div>
          <p class="page-title"><?= e($page_title) ?></p>
          <?php if ($page_sub): ?><p class="page-sub mb-0"><?= e($page_sub) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="user-chip">
        <div class="avatar"><?= e(initials($user_name)) ?></div>
        <div>
          <div style="font-size:0.85rem; font-weight:600; line-height:1.1;"><?= e($user_name) ?></div>
          <div class="role-tag"><?= e($role) ?></div>
        </div>
      </div>
    </div>
    <div class="content-area">
