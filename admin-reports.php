<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireAdmin();
$activePage = 'reports';
$adminId    = currentUserId();
$msg = '';

// ── Update report status ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['report_id'], $_POST['status'])) {
    $allowed = ['pending','in_progress','resolved'];
    $status  = $_POST['status'];
    if (in_array($status, $allowed)) {
        db()->prepare('UPDATE Report SET Status=? WHERE ReportID=?')
           ->execute([$status, (int)$_POST['report_id']]);
        $msg = 'Report status updated.';
    }
}

// ── Fetch all reports for admin's employees ───────────────
$filter = $_GET['filter'] ?? 'all';
$sql = 'SELECT r.*, l.LampID, l.Status AS LampStatus, a.AreaName,
               u.EmployeeName, u.EmployeeCode
        FROM Report r
        JOIN Lamp l  ON r.LampID  = l.LampID
        JOIN Area a  ON l.AreaID  = a.AreaID
        JOIN `employee` u ON r.EmployeeID = u.EmployeeID
        WHERE u.AdminID = ?';
$params = [$adminId];
if ($filter !== 'all') { $sql .= ' AND r.Status = ?'; $params[] = $filter; }
$sql .= ' ORDER BY r.CreatedAt DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$counts = db()->prepare('SELECT Status, COUNT(*) as cnt FROM `report` r JOIN `employee` u ON r.EmployeeID=u.EmployeeID WHERE u.AdminID=? GROUP BY Status');
$counts->execute([$adminId]);
$countData = ['pending'=>0,'in_progress'=>0,'resolved'=>0];
foreach ($counts->fetchAll() as $row) $countData[$row['Status']] = $row['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Fault Reports — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="dashboard-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Fault Reports</h1>
      <p>Reports submitted by employees about lamp faults</p>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success visible" style="margin-bottom:16px;"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Status summary -->
  <div class="stat-cards" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-num" style="color:var(--warning);"><?= $countData['pending'] ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--accent);"><?= $countData['in_progress'] ?></div><div class="stat-label">In Progress</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success);"><?= $countData['resolved'] ?></div><div class="stat-label">Resolved</div></div>
  </div>

  <!-- Filter -->
  <div class="filter-bar">
    <?php foreach (['all'=>'All Reports','pending'=>'Pending','in_progress'=>'In Progress','resolved'=>'Resolved'] as $val => $label): ?>
      <a href="?filter=<?= $val ?>" class="filter-btn <?= $filter===$val?'active':'' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Reports Table -->
  <div class="reports-table-wrap">
    <?php if (empty($reports)): ?>
      <div style="padding:60px;text-align:center;color:var(--secondary);">
        <div style="font-size:40px;margin-bottom:12px;">📋</div>
        <p>No reports found.</p>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Lamp ID</th>
            <th>Area</th>
            <th>Reported By</th>
            <th>Details</th>
            <th>Status</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
          <tr>
            <td data-label="#" style="color:var(--secondary);font-size:12px;">#<?= $r['ReportID'] ?></td>
            <td data-label="Date" style="font-size:12px;color:var(--secondary);white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($r['CreatedAt'])) ?></td>
            <td data-label="Lamp">
              <div style="font-weight:700;">Lamp #<?= $r['LampID'] ?></div>
              <div style="font-size:11px;margin-top:2px;"><span class="status-dot <?= $r['LampStatus'] ?>"></span> <?= ucfirst($r['LampStatus']) ?></div>
            </td>
            <td data-label="Area" style="font-size:13px;"><?= htmlspecialchars($r['AreaName']) ?></td>
            <td data-label="Employee">
              <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($r['EmployeeName']) ?></div>
              <?php if ($r['EmployeeCode']): ?>
                <div style="font-size:11px;color:var(--secondary);"><?= htmlspecialchars($r['EmployeeCode']) ?></div>
              <?php endif; ?>
            </td>
            <td data-label="Details" style="max-width:220px;">
              <div style="font-size:13px;line-height:1.5;color:var(--primary);"><?= htmlspecialchars(mb_strimwidth($r['Details'], 0, 100, '…')) ?></div>
              <?php if (mb_strlen($r['Details']) > 100): ?>
                <button class="btn btn-outline btn-sm" style="margin-top:6px;font-size:11px;" onclick="showDetails(<?= htmlspecialchars(json_encode($r['Details'])) ?>)">Read more</button>
              <?php endif; ?>
            </td>
            <td data-label="Status"><span class="report-badge <?= $r['Status'] ?>"><?= str_replace('_',' ', ucfirst($r['Status'])) ?></span></td>
            <td data-label="Update">
              <form method="POST" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="report_id" value="<?= $r['ReportID'] ?>"/>
                <select name="status" class="form-select" style="padding:6px 10px;font-size:12px;width:130px;">
                  <option value="pending"     <?= $r['Status']==='pending'     ?'selected':'' ?>>Pending</option>
                  <option value="in_progress" <?= $r['Status']==='in_progress' ?'selected':'' ?>>In Progress</option>
                  <option value="resolved"    <?= $r['Status']==='resolved'    ?'selected':'' ?>>Resolved</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Details Modal -->
<div class="modal-overlay" id="details-modal">
  <div class="modal-box" style="max-width:460px;">
    <button class="modal-close">✕</button>
    <div class="modal-title">Report Details</div>
    <div style="margin-top:16px;font-size:14px;line-height:1.8;color:var(--primary);white-space:pre-wrap;" id="details-text"></div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
function showDetails(text) {
  document.getElementById('details-text').textContent = text;
  openModal('details-modal');
}
</script>
</body>
</html>