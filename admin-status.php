<?php
require_once __DIR__ . '/includes/security.php';
// admin-status.php — All lamps all areas
require_once __DIR__ . '/config/db.php';
requireAdmin();
$activePage = 'status';

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = 'SELECT l.*, a.AreaName, a.Pollution_level FROM Lamp l JOIN Area a ON l.AreaID=a.AreaID WHERE 1=1';
$params = [];
if ($filter === 'on')  { $sql .= " AND l.Status='on'"; }
if ($filter === 'off') { $sql .= " AND l.Status='off'"; }
if ($search) { $sql .= ' AND (a.AreaName LIKE ? OR l.LampID LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= ' ORDER BY a.AreaName, l.LampID';
$stmt = db()->prepare($sql); $stmt->execute($params);
$lamps = $stmt->fetchAll();

$totalOn  = db()->query("SELECT COUNT(*) FROM Lamp WHERE Status='on'")->fetchColumn();
$totalOff = db()->query("SELECT COUNT(*) FROM Lamp WHERE Status='off'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Streetlight Status — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>
<div class="dashboard-content">
  <div class="page-header">
    <div class="page-header-left"><h1>Streetlight Status</h1><p>All lamps across all city areas — Updated: <?= date('H:i:s') ?></p></div>
    <button id="refresh-status" class="btn btn-outline">🔄 Refresh</button>
  </div>

  <div class="status-bar">
    <div class="status-badge on-badge"><span class="status-dot on"></span> <?= $totalOn ?> Active</div>
    <div class="status-badge off-badge"><span class="status-dot off"></span> <?= $totalOff ?> Offline</div>
  </div>

  <form method="GET">
    <div class="filter-bar">
      <button type="submit" name="filter" value="all"  class="filter-btn <?= $filter==='all' ?'active':'' ?>">All</button>
      <button type="submit" name="filter" value="on"   class="filter-btn <?= $filter==='on'  ?'active':'' ?>">● Active</button>
      <button type="submit" name="filter" value="off"  class="filter-btn <?= $filter==='off' ?'active':'' ?>">● Offline</button>
      <input type="text" name="search" class="search-input" placeholder="Search area or lamp…" value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()"/>
    </div>
  </form>

  <?php if (empty($lamps)): ?>
    <div style="text-align:center;padding:60px;color:var(--secondary);"><div style="font-size:40px;margin-bottom:12px;">💡</div><p>No lamps found.</p></div>
  <?php else: ?>
  <div class="lamp-grid">
    <?php foreach ($lamps as $l): $isOn=$l['Status']==='on'; ?>
    <div class="lamp-card <?= $isOn?'is-on':'is-off' ?>">
      <div class="lamp-id">Lamp #<?= $l['LampID'] ?></div>
      <div class="lamp-area">📍 <?= htmlspecialchars($l['AreaName']) ?></div>
      <div class="lamp-status-row"><span class="status-dot <?= $l['Status'] ?>"></span><?= $isOn?'On':'Off' ?></div>
      <div class="lamp-lux">Lux: <strong><?= number_format((float)$l['Lux_Value'],1) ?></strong></div>
      <div style="margin-top:8px;"><span class="pollution-badge <?= $l['Pollution_level'] ?>"><?= $l['Pollution_level'] ?></span></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
