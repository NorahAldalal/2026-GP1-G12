<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireEmployee();
$activePage = 'reports';
$userId     = currentUserId();
$areaId     = (int)($_SESSION['user_area'] ?? 0);

// ── Fetch this employee's reports ─────────────────────────
$filter = $_GET['filter'] ?? 'all';

$sql = '
    SELECT
        r.ReportID,
        r.Details,
        r.Status,
        r.CreatedAt,
        r.UpdatedAt,
        l.LampID,
        l.Status  AS LampStatus,
        l.Lux_Value,
        a.AreaName
    FROM Report r
    JOIN Lamp l ON r.LampID  = l.LampID
    JOIN Area a ON l.AreaID  = a.AreaID
    WHERE r.UserID = ?
';
$params = [$userId];
if ($filter !== 'all') {
    $sql .= ' AND r.Status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.CreatedAt DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// ── Count by status ───────────────────────────────────────
$counts = db()->prepare('
    SELECT Status, COUNT(*) as cnt
    FROM Report WHERE UserID = ?
    GROUP BY Status
');
$counts->execute([$userId]);
$countData = ['pending' => 0, 'in_progress' => 0, 'resolved' => 0];
foreach ($counts->fetchAll() as $row) {
    $countData[$row['Status']] = (int)$row['cnt'];
}
$totalReports = array_sum($countData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Reports — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
  <style>
    /* ── Report Cards ── */
    .reports-list { display: flex; flex-direction: column; gap: 16px; }

    .report-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: 24px 28px;
      box-shadow: var(--shadow-sm);
      border-left: 5px solid transparent;
      transition: var(--transition);
      position: relative;
    }
    .report-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .report-card.pending     { border-left-color: var(--warning); }
    .report-card.in_progress { border-left-color: var(--accent); }
    .report-card.resolved    { border-left-color: var(--success); }

    .report-card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .report-card-left { display: flex; align-items: center; gap: 14px; }
    .report-number {
      font-family: 'Cinzel', serif;
      font-size: 13px;
      color: var(--secondary);
      white-space: nowrap;
    }
    .lamp-info { }
    .lamp-id-text {
      font-size: 16px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 3px;
    }
    .lamp-area-text {
      font-size: 13px;
      color: var(--secondary);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .report-meta {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }

    .report-details {
      font-size: 14px;
      color: var(--primary);
      line-height: 1.7;
      background: rgba(33,43,58,0.04);
      border-radius: var(--radius-sm);
      padding: 14px 16px;
      margin-bottom: 14px;
      border: 1px solid rgba(33,43,58,0.07);
    }
    .report-details-label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--secondary);
      font-weight: 700;
      margin-bottom: 8px;
    }

    .report-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      color: var(--secondary);
      flex-wrap: wrap;
      gap: 8px;
    }
    .report-dates { display: flex; gap: 20px; flex-wrap: wrap; }
    .date-item { display: flex; flex-direction: column; gap: 2px; }
    .date-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--secondary); }
    .date-val   { font-size: 13px; color: var(--primary); font-weight: 700; }

    /* Status badge bigger version */
    .report-status-big {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .report-status-big.pending     { background: rgba(245,197,66,.15); color: #9a7200;  border: 1px solid rgba(245,197,66,.35); }
    .report-status-big.in_progress { background: rgba(74,144,184,.15); color: #1a5a8a; border: 1px solid rgba(74,144,184,.35); }
    .report-status-big.resolved    { background: rgba(76,175,125,.15); color: #2e7d52; border: 1px solid rgba(76,175,125,.35); }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 80px 40px;
      background: white;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-sm);
    }
    .empty-icon { font-size: 64px; margin-bottom: 20px; display: block; }
    .empty-title { font-family: 'Cinzel', serif; font-size: 20px; color: var(--primary); margin-bottom: 10px; }
    .empty-text  { font-size: 14px; color: var(--secondary); margin-bottom: 28px; line-height: 1.7; }

    /* Summary cards */
    .summary-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 14px;
      margin-bottom: 24px;
    }
    .summary-card {
      background: white;
      border-radius: var(--radius-md);
      padding: 18px 20px;
      text-align: center;
      box-shadow: var(--shadow-sm);
      cursor: pointer;
      transition: var(--transition);
      border: 2px solid transparent;
      text-decoration: none;
      display: block;
    }
    .summary-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .summary-card.active-filter { border-color: var(--accent); }
    .summary-num  { font-family: 'Cinzel', serif; font-size: 28px; font-weight: 700; line-height: 1; margin-bottom: 6px; }
    .summary-lbl  { font-size: 11px; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; }
  </style>
</head>
<body class="dashboard-page">

<?php include 'includes/nav.php'; ?>

<div class="dashboard-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Fault Reports</h1>
      <p>All reports you have submitted — track their status here</p>
    </div>
    <a href="employee-status.php" class="btn btn-danger">
      Submit New Report
    </a>
  </div>

  <!-- Summary Cards (clickable filters) -->
  <div class="summary-cards">
    <a href="?filter=all"
       class="summary-card <?= $filter==='all' ? 'active-filter' : '' ?>">
      <div class="summary-num" style="color:var(--primary);"><?= $totalReports ?></div>
      <div class="summary-lbl">Total</div>
    </a>
    <a href="?filter=pending"
       class="summary-card <?= $filter==='pending' ? 'active-filter' : '' ?>">
      <div class="summary-num" style="color:#9a7200;"><?= $countData['pending'] ?></div>
      <div class="summary-lbl">⏳ Pending</div>
    </a>
    <a href="?filter=in_progress"
       class="summary-card <?= $filter==='in_progress' ? 'active-filter' : '' ?>">
      <div class="summary-num" style="color:var(--accent);"><?= $countData['in_progress'] ?></div>
      <div class="summary-lbl">🔧 In Progress</div>
    </a>
    <a href="?filter=resolved"
       class="summary-card <?= $filter==='resolved' ? 'active-filter' : '' ?>">
      <div class="summary-num" style="color:var(--success);"><?= $countData['resolved'] ?></div>
      <div class="summary-lbl">✅ Resolved</div>
    </a>
  </div>

  <!-- Reports List -->
  <?php if (empty($reports)): ?>

    <div class="empty-state">
      <span class="empty-icon">📋</span>
      <div class="empty-title">
        <?= $filter === 'all' ? 'No Reports Yet' : 'No ' . ucfirst(str_replace('_',' ',$filter)) . ' Reports' ?>
      </div>
      <p class="empty-text">
        <?php if ($filter === 'all'): ?>
          You haven't submitted any fault reports yet.<br>
          If you notice a lamp issue, report it from the Status page.
        <?php else: ?>
          No reports with this status. <a href="?filter=all">View all reports</a>
        <?php endif; ?>
      </p>
      <?php if ($filter === 'all'): ?>
        <a href="employee-status.php" class="btn btn-danger">🚨 Report a Fault</a>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div class="reports-list">
      <?php foreach ($reports as $r):
        $statusLabels = [
            'pending'     => ['⏳', 'Pending'],
            'in_progress' => ['🔧', 'In Progress'],
            'resolved'    => ['✅', 'Resolved'],
        ];
        [$statusIcon, $statusText] = $statusLabels[$r['Status']];
      ?>
      <div class="report-card <?= $r['Status'] ?>">

        <!-- Card Header -->
        <div class="report-card-header">
          <div class="report-card-left">
            <!-- Report number -->
            <div style="background:rgba(33,43,58,0.06);border-radius:var(--radius-sm);padding:8px 12px;text-align:center;min-width:52px;">
              <div style="font-size:10px;color:var(--secondary);text-transform:uppercase;letter-spacing:1px;">Report</div>
              <div style="font-family:'Cinzel',serif;font-size:16px;font-weight:700;color:var(--primary);">#<?= $r['ReportID'] ?></div>
            </div>
            <!-- Lamp info -->
            <div class="lamp-info">
              <div class="lamp-id-text">💡 Lamp #<?= $r['LampID'] ?></div>
              <div class="lamp-area-text">
                <?= htmlspecialchars($r['AreaName']) ?>
                &nbsp;·&nbsp;
                <span class="status-dot <?= $r['LampStatus'] ?>"></span>
                <?= ucfirst($r['LampStatus']) ?>
                &nbsp;·&nbsp;
                Lux: <?= number_format((float)$r['Lux_Value'], 1) ?>
              </div>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="report-status-big <?= $r['Status'] ?>">
            <?= $statusIcon ?> <?= $statusText ?>
          </div>
        </div>

        <!-- Fault Details -->
        <div class="report-details">
          <div class="report-details-label">📝 Fault Description</div>
          <?= nl2br(htmlspecialchars($r['Details'])) ?>
        </div>

        <!-- Footer: Dates -->
        <div class="report-footer">
          <div class="report-dates">
            <div class="date-item">
              <span class="date-label">Submitted</span>
              <span class="date-val"><?= date('d M Y — H:i', strtotime($r['CreatedAt'])) ?></span>
            </div>
            <?php if ($r['UpdatedAt'] !== $r['CreatedAt']): ?>
            <div class="date-item">
              <span class="date-label">Last Updated</span>
              <span class="date-val"><?= date('d M Y — H:i', strtotime($r['UpdatedAt'])) ?></span>
            </div>
            <?php endif; ?>
          </div>

          <!-- Status message -->
          <div style="font-size:13px;">
            <?php if ($r['Status'] === 'pending'): ?>
              <span style="color:#9a7200;">Awaiting admin review</span>
            <?php elseif ($r['Status'] === 'in_progress'): ?>
              <span style="color:var(--accent);">Admin is working on this</span>
            <?php else: ?>
              <span style="color:var(--success);">Issue has been resolved</span>
            <?php endif; ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/main.js"></script>
</body>
</html>
