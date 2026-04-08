<?php
require_once __DIR__ . '/includes/security.php';
// employee-home.php
require_once __DIR__ . '/config/db.php';
requireEmployee();
$activePage = 'home';
$areaId = (int)($_SESSION['user_area'] ?? 0);

$area = null;
if ($areaId) {
    $s = db()->prepare('SELECT * FROM Area WHERE AreaID=?');
    $s->execute([$areaId]);
    $area = $s->fetch();
}
$totalLamps  = $areaId ? db()->prepare('SELECT COUNT(*) FROM Lamp WHERE AreaID=?') : null;
$activeLamps = 0; $offLamps = 0;
if ($areaId && $totalLamps) {
    $totalLamps->execute([$areaId]);
    $total = $totalLamps->fetchColumn();
    $s2 = db()->prepare("SELECT COUNT(*) FROM Lamp WHERE AreaID=? AND Status='on'"); $s2->execute([$areaId]);
    $activeLamps = $s2->fetchColumn();
    $offLamps = $total - $activeLamps;
} else { $total = 0; }

$myReports = db()->prepare('SELECT COUNT(*) FROM Report WHERE UserID=?');
$myReports->execute([currentUserId()]);
$myReportCount = $myReports->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Employee Home — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>
<div class="dashboard-content">
  <div class="home-welcome">
    <h1 class="welcome-title">Welcome, <span><?= htmlspecialchars($_SESSION['user_name']) ?></span> ✦</h1>
    <p class="welcome-sub">
      <?= $area ? '📍 Assigned to: <strong>' . htmlspecialchars($area['AreaName']) . '</strong>' : 'No area assigned yet. Contact your admin.' ?>
    </p>
  </div>

  <?php if ($area): ?>
  <div class="stat-cards">
    <div class="stat-card"><div class="stat-num" style="color:var(--accent);"><?= $total ?></div><div class="stat-label">My Lamps</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success);"><?= $activeLamps ?></div><div class="stat-label">Active</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger);"><?= $offLamps ?></div><div class="stat-label">Offline</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--warning);"><?= $myReportCount ?></div><div class="stat-label">My Reports</div></div>
  </div>

  <div class="home-nav-cards">
    <a href="employee-status.php" class="home-nav-card"><span class="card-icon">💡</span><h3 class="card-title">Lamp Status</h3><p class="card-desc">Monitor your area's lamps and report faults</p></a>
    <a href="employee-map.php"    class="home-nav-card"><span class="card-icon">🗺️</span><h3 class="card-title">Area Map</h3><p class="card-desc">View your assigned area on the map</p></a>
  </div>
  <?php else: ?>
  <div class="alert alert-warning visible" style="max-width:500px;margin:0 auto;text-align:center;">
    ⚠️ You have not been assigned to an area yet. Please contact your administrator.
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
