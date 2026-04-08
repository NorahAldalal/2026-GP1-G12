<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireAdmin();
$activePage = 'home';

$totalLamps    = db()->query('SELECT COUNT(*) FROM Lamp')->fetchColumn();
$activeLamps   = db()->query("SELECT COUNT(*) FROM Lamp WHERE Status='on'")->fetchColumn();
$offLamps      = $totalLamps - $activeLamps;
$totalAreas    = db()->query('SELECT COUNT(*) FROM Area')->fetchColumn();
$totalEmployees= db()->query("SELECT COUNT(*) FROM User WHERE Role='employee'")->fetchColumn();
$pendingReports= db()->query("SELECT COUNT(*) FROM Report WHERE Status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Home — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="dashboard-content">
  <div class="home-welcome">
    <h1 class="welcome-title">Welcome, <span><?= htmlspecialchars($_SESSION['user_name']) ?></span> ✦</h1>
    <p class="welcome-sub">Smart Street Lighting Control Center — Full City Overview</p>
  </div>

  <!-- Stats -->
  <div class="stat-cards">
    <div class="stat-card"><div class="stat-num" style="color:var(--accent);"><?= $totalLamps ?></div><div class="stat-label">Total Lamps</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success);"><?= $activeLamps ?></div><div class="stat-label">Active</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger);"><?= $offLamps ?></div><div class="stat-label">Offline</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--accent);"><?= $totalAreas ?></div><div class="stat-label">City Areas</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--secondary);"><?= $totalEmployees ?></div><div class="stat-label">Employees</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--warning);"><?= $pendingReports ?></div><div class="stat-label">Pending Reports</div></div>
  </div>

  <!-- Nav Cards -->
  <div class="home-nav-cards">
    <a href="admin-status.php"  class="home-nav-card"><span class="card-icon">💡</span><h3 class="card-title">Streetlight Status</h3><p class="card-desc">All lamps across all city areas</p></a>
    <a href="admin-map.php"     class="home-nav-card"><span class="card-icon">🗺️</span><h3 class="card-title">City Map</h3><p class="card-desc">Interactive map of all lamp locations</p></a>
    <a href="admin-users.php"   class="home-nav-card"><span class="card-icon">👥</span><h3 class="card-title">Employees</h3><p class="card-desc">Manage employee accounts & assignments</p></a>
    <a href="admin-reports.php" class="home-nav-card"><span class="card-icon">📋</span><h3 class="card-title">Fault Reports</h3><p class="card-desc">View and manage employee reports</p></a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
