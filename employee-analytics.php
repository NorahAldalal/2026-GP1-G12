<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

requireEmployee();
$activePage = 'analytics';

$areaId = (int)($_SESSION['user_area'] ?? 0);
if (!$areaId) { header('Location: employee-home.php'); exit; }

$areaStmt = db()->prepare('SELECT * FROM Area WHERE AreaID=?');
$areaStmt->execute([$areaId]);
$area = $areaStmt->fetch();
if (!$area) { header('Location: employee-home.php'); exit; }

$hasData = false;
try {
    $dc = db()->prepare('SELECT COUNT(*) FROM weekly_reading WHERE AreaID=?');
    $dc->execute([$areaId]);
    $hasData = (int)$dc->fetchColumn() > 0;
} catch (Exception $e) { $hasData = false; }

$lampStmt = db()->prepare("SELECT COUNT(*) FROM Lamp WHERE AreaID=?");
$lampStmt->execute([$areaId]);
$totalLamps = (int)$lampStmt->fetchColumn();

$activeLampStmt = db()->prepare("SELECT COUNT(*) FROM Lamp WHERE AreaID=? AND Status='on'");
$activeLampStmt->execute([$areaId]);
$activeLamps = (int)$activeLampStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>My Area Analytics — SIRAJ</title>
<link rel="stylesheet" href="assets/css/global.css"/>
<link rel="stylesheet" href="assets/css/dashboard.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.an-page{max-width:1100px;margin:0 auto;padding:32px 24px;}
@media(max-width:700px){.an-page{padding:18px 12px;}}

.an-page-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid rgba(126,200,227,.10);}
.an-page-title{font-family:'Cinzel',serif;font-size:22px;color:var(--glow-soft);margin-bottom:4px;}
body.light-mode .an-page-title{color:var(--primary);}
.an-page-sub{font-size:13px;color:var(--text-muted);}
body.light-mode .an-page-sub{color:#4a5568;}
.an-header-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}

.an-stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:30px;}
.an-stat-card{background:rgba(255,255,255,.03);border:1px solid rgba(126,200,227,.10);padding:16px 18px;}
body.light-mode .an-stat-card{background:rgba(13,27,46,.03);border-color:rgba(13,27,46,.12);}
.an-stat-num{font-size:26px;font-weight:800;margin-bottom:4px;}
.an-stat-label{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--text-muted);}
body.light-mode .an-stat-label{color:#4a5568;}

.chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;}
@media(max-width:760px){.chart-grid{grid-template-columns:1fr;}}
.chart-card{background:rgba(255,255,255,.03);border:1px solid rgba(126,200,227,.10);padding:20px;}
body.light-mode .chart-card{background:rgba(13,27,46,.03);border-color:rgba(13,27,46,.12);}
.chart-card-full{grid-column:1/-1;}
.chart-card-title{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--glow);margin-bottom:14px;}
body.light-mode .chart-card-title{color:#2d7aab;}
.chart-wrap-sm{position:relative;height:180px;}
.chart-wrap-lg{position:relative;height:280px;}
.chart-loading{display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:var(--text-muted);text-align:center;padding:16px;}
body.light-mode .chart-loading{color:#4a5568;}

.table-card{background:rgba(255,255,255,.03);border:1px solid rgba(126,200,227,.10);margin-bottom:28px;}
body.light-mode .table-card{background:rgba(13,27,46,.03);border-color:rgba(13,27,46,.12);}
.table-card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(126,200,227,.10);}
.table-card-header span{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--glow);}
body.light-mode .table-card-header span{color:#2d7aab;}
.no-data-msg{text-align:center;padding:32px;font-size:13px;color:var(--text-muted);}
body.light-mode .no-data-msg{color:#4a5568;}

.an-notice{display:flex;align-items:flex-start;gap:14px;padding:18px 20px;background:rgba(240,180,41,.08);border:1px solid rgba(240,180,41,.22);margin-bottom:28px;}
.an-notice-icon{font-size:20px;flex-shrink:0;}
.an-notice p{font-size:13px;color:var(--text-muted);margin:0;}
body.light-mode .an-notice p{color:#4a5568;}
.an-notice strong{color:var(--warning);}

.area-pill{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(255,255,255,.04);border:1px solid rgba(126,200,227,.14);font-size:12px;}
body.light-mode .area-pill{background:rgba(13,27,46,.04);border-color:rgba(13,27,46,.12);color:#1a2744;}
</style>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="an-page">

  <div class="an-page-header">
    <div>
      <div class="an-page-title"><?= htmlspecialchars($area['AreaName']) ?> — Analytics</div>
      <div class="an-page-sub" style="display:flex;align-items:center;gap:10px;margin-top:6px;">
        <span class="area-pill">
          <span class="pollution-badge <?= $area['Pollution_level'] ?>"><?= $area['Pollution_level'] ?></span>
          Pollution Level
        </span>
        <?php if ($hasData): ?>
        <span style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:3px 9px;background:rgba(46,204,138,.12);color:var(--success);border:1px solid rgba(46,204,138,.3);">● Data Available</span>
        <?php else: ?>
        <span style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:3px 9px;background:rgba(255,255,255,.05);color:var(--text-muted);border:1px solid rgba(255,255,255,.10);">No Data Yet</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="an-header-actions">
      <a href="analytics-export.php?area_id=<?= $areaId ?>" class="btn btn-accent btn-sm" style="text-decoration:none;">↓ Export (.csv)</a>
    </div>
  </div>

  <?php if (!$hasData): ?>
  <div class="an-notice">
    <div class="an-notice-icon">⚠️</div>
    <div><p><strong>No weekly data yet for your area.</strong><br>Analytics are generated automatically by the weekly aggregation script. Ask your administrator to run <code>cron/weekly-aggregate.php</code>.</p></div>
  </div>
  <?php endif; ?>

  <div class="an-stat-row">
    <div class="an-stat-card">
      <div class="an-stat-num" style="color:var(--accent);"><?= $totalLamps ?></div>
      <div class="an-stat-label">My Lamps</div>
    </div>
    <div class="an-stat-card">
      <div class="an-stat-num" style="color:var(--success);"><?= $activeLamps ?></div>
      <div class="an-stat-label">Active</div>
    </div>
    <div class="an-stat-card">
      <div class="an-stat-num" style="color:var(--danger);"><?= $totalLamps - $activeLamps ?></div>
      <div class="an-stat-label">Offline</div>
    </div>
    <div class="an-stat-card">
      <div class="an-stat-num" style="color:var(--glow);" id="stat-weeks">—</div>
      <div class="an-stat-label">Weeks of Data</div>
    </div>
    <div class="an-stat-card">
      <div class="an-stat-num" style="color:var(--success);" id="stat-avg-ambient">—</div>
      <div class="an-stat-label">Avg Ambient (lux)</div>
    </div>
    <div class="an-stat-card">
      <div class="an-stat-num" style="color:var(--secondary);" id="stat-readings">—</div>
      <div class="an-stat-label">Total Readings</div>
    </div>
  </div>

  <div class="chart-grid">
    <div class="chart-card chart-card-full">
      <div class="chart-card-title">Ambient Light Trend — Full History</div>
      <div class="chart-wrap-lg">
        <div class="chart-loading" id="line-loading">Loading…</div>
        <canvas id="line-chart" style="display:none;"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <div class="chart-card-title">Last 4 Weeks — Avg Ambient</div>
      <div class="chart-wrap-sm">
        <div class="chart-loading" id="bar-loading">Loading…</div>
        <canvas id="bar-chart" style="display:none;"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <div class="chart-card-title">Ambient vs Lux — Last 8 Weeks</div>
      <div class="chart-wrap-sm">
        <div class="chart-loading" id="compare-loading">Loading…</div>
        <canvas id="compare-chart" style="display:none;"></canvas>
      </div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-card-header">
      <span>Weekly Data Table</span>
      <a href="analytics-export.php?area_id=<?= $areaId ?>" class="btn btn-accent btn-sm" style="text-decoration:none;">↓ Export (.csv)</a>
    </div>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Week</th><th>Avg Ambient Light</th><th>Avg Lux Value</th><th>Total Readings</th><th>Pollution Level</th></tr></thead>
        <tbody id="data-tbody"><tr><td colspan="5" class="no-data-msg">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>

  <div style="padding-bottom:32px;"></div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
const AID = <?= $areaId ?>;

function gc() {
  const lm = document.body.classList.contains('light-mode');
  return {
    grid:    lm ? 'rgba(13,27,46,.12)'   : 'rgba(255,255,255,.12)',
    text:    lm ? 'rgba(13,27,46,.80)'   : 'rgba(255,255,255,.85)',
    bar:     lm ? 'rgba(14,60,120,.75)'  : 'rgba(126,200,227,.80)',
    line1:   lm ? '#0e3c78'              : '#7EC8E3',
    line1bg: lm ? 'rgba(14,60,120,.10)' : 'rgba(126,200,227,.18)',
    line2:   lm ? '#1a6aab'             : '#4A90B8',
    line2bg: lm ? 'rgba(26,106,171,.06)': 'rgba(74,144,184,.08)',
    amb:     lm ? 'rgba(14,60,120,.65)' : 'rgba(126,200,227,.75)',
    lux:     lm ? 'rgba(26,106,171,.60)': 'rgba(74,144,184,.60)',
  };
}

function showChartError(id, msg) {
  const el = document.getElementById(id);
  if (el) { el.innerHTML = msg; el.style.display = 'flex'; }
}

fetch(`api/chart-data.php?area_id=${AID}&weeks=all`)
  .then(r => r.json())
  .then(data => {
    if (data.error === 'table_missing') {
      ['line-loading','bar-loading','compare-loading'].forEach(id => showChartError(id, 'Run <strong>weekly_reading.sql</strong> first.'));
      document.getElementById('data-tbody').innerHTML = '<tr><td colspan="5" class="no-data-msg">Import weekly_reading.sql in phpMyAdmin first.</td></tr>';
      return;
    }
    if (!data.labels || data.labels.length === 0) {
      const msg = 'No weekly data yet.<br><small>Run cron/weekly-aggregate.php</small>';
      ['line-loading','bar-loading','compare-loading'].forEach(id => showChartError(id, msg));
      document.getElementById('data-tbody').innerHTML = '<tr><td colspan="5" class="no-data-msg">No data yet.</td></tr>';
      return;
    }

    const c = gc();
    const totalReadings = data.table.reduce((s,r) => s+r.reading_count, 0);
    const avgAmbient    = data.ambient.reduce((s,v) => s+v, 0) / data.ambient.length;
    document.getElementById('stat-weeks').textContent       = data.labels.length;
    document.getElementById('stat-avg-ambient').textContent = avgAmbient.toFixed(1);
    document.getElementById('stat-readings').textContent    = totalReadings.toLocaleString();

    const opts = (legend) => ({
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:legend,labels:{color:c.text,font:{size:12}}}, tooltip:{callbacks:{label:ctx=>` ${ctx.parsed.y.toFixed(1)} lux`}} },
      scales:{ x:{ticks:{color:c.text,font:{size:10},maxRotation:38},grid:{color:c.grid}}, y:{ticks:{color:c.text,font:{size:10}},grid:{color:c.grid},beginAtZero:false} }
    });

    document.getElementById('line-loading').style.display='none';
    const lc=document.getElementById('line-chart'); lc.style.display='block';
    new Chart(lc,{type:'line',data:{labels:data.labels,datasets:[
      {label:'Avg Ambient Light',data:data.ambient,borderColor:c.line1,backgroundColor:c.line1bg,borderWidth:2.5,tension:.4,fill:true,pointRadius:4,pointBackgroundColor:c.line1},
      {label:'Avg Lux Value',data:data.lux,borderColor:c.line2,backgroundColor:c.line2bg,borderWidth:2,tension:.4,fill:false,pointRadius:3,borderDash:[5,3]}
    ]},options:opts(true)});

    document.getElementById('bar-loading').style.display='none';
    const bc=document.getElementById('bar-chart'); bc.style.display='block';
    new Chart(bc,{type:'bar',data:{labels:data.labels.slice(-4),datasets:[{label:'Avg Ambient',data:data.ambient.slice(-4),backgroundColor:c.bar,borderColor:'#4A90B8',borderWidth:1}]},options:opts(false)});

    document.getElementById('compare-loading').style.display='none';
    const cc=document.getElementById('compare-chart'); cc.style.display='block';
    new Chart(cc,{type:'bar',data:{labels:data.labels.slice(-8),datasets:[
      {label:'Ambient',data:data.ambient.slice(-8),backgroundColor:c.amb,borderColor:c.line1,borderWidth:1},
      {label:'Lux',data:data.lux.slice(-8),backgroundColor:c.lux,borderColor:c.line2,borderWidth:1}
    ]},options:opts(true)});

    const pc={'Low':'#2ECC8A','Medium':'#F0B429','High':'#E05C5C'};
    const pb={'Low':'rgba(46,204,138,.12)','Medium':'rgba(240,180,41,.12)','High':'rgba(224,92,92,.12)'};
    document.getElementById('data-tbody').innerHTML = data.table.map(r => {
      const p=r.pollution_level||'';
      const badge=p?`<span style="display:inline-block;padding:3px 10px;font-size:11px;font-weight:700;background:${pb[p]||'rgba(0,0,0,.06)'};color:${pc[p]||'inherit'};border:1px solid ${pc[p]||'#ccc'}22;letter-spacing:.5px">${p}</span>`:'-';
      return `<tr><td>${r.week_label}</td><td><strong>${r.avg_ambient.toFixed(1)}</strong> lux</td><td>${r.avg_lux.toFixed(1)} lux</td><td>${r.reading_count.toLocaleString()}</td><td>${badge}</td></tr>`;
    }).join('');
  })
  .catch(() => {
    ['line-loading','bar-loading','compare-loading'].forEach(id => showChartError(id, 'Could not load. Check weekly_reading.sql is imported.'));
    document.getElementById('data-tbody').innerHTML = '<tr><td colspan="5" class="no-data-msg">Could not load data.</td></tr>';
  });
</script>
</body>
</html>