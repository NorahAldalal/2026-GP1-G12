<?php

// ================================================================
//  SIRAJ — Admin Analytics
//  Full chart history and weekly data tables for all city areas.
//  Access: Admin only.
// ================================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

requireAdmin();
$activePage = 'analytics';

$areas = db()->query('SELECT * FROM Area ORDER BY AreaName')->fetchAll();

// Check which areas have weekly_reading data
$areasWithData = [];
try {
    $dataCheck = db()->query('SELECT DISTINCT AreaID FROM weekly_reading');
    $areasWithData = array_column($dataCheck->fetchAll(), 'AreaID');
} catch (Exception $e) { $areasWithData = []; }

// Default to first area that has data, or just first area
$defaultAreaId = 0;
foreach ($areas as $a) {
    if (in_array($a['AreaID'], $areasWithData)) { $defaultAreaId = (int)$a['AreaID']; break; }
}
if (!$defaultAreaId && count($areas)) $defaultAreaId = (int)$areas[0]['AreaID'];

$areasJson       = json_encode(array_map(fn($a) => ['id' => (int)$a['AreaID'], 'name' => $a['AreaName'], 'pollution' => $a['Pollution_level']], $areas));
$areasWithDataJson = json_encode(array_map('intval', $areasWithData));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Analytics — SIRAJ Admin</title>
<link rel="stylesheet" href="assets/css/global.css"/>
<link rel="stylesheet" href="assets/css/dashboard.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Layout ───────────────────────────────────────────────── */
.analytics-layout{display:grid;grid-template-columns:260px 1fr;min-height:calc(100vh - 64px);}
@media(max-width:768px){.analytics-layout{grid-template-columns:1fr;}}

/* ── Sidebar ──────────────────────────────────────────────── */
.an-sidebar{background:var(--surface);border-right:1px solid rgba(126,200,227,.10);overflow-y:auto;display:flex;flex-direction:column;}
.an-sidebar-header{padding:20px 16px 12px;border-bottom:1px solid rgba(126,200,227,.10);}
.an-sidebar-title{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--glow);margin-bottom:10px;}
.an-search{width:100%;padding:8px 10px;font-size:13px;background:rgba(255,255,255,.05);border:1px solid rgba(126,200,227,.15);color:var(--text);outline:none;font-family:'Lato',sans-serif;}
body.light-mode .an-search{background:rgba(13,27,46,.04);border-color:rgba(13,27,46,.15);color:var(--text);}
.an-area-list{flex:1;overflow-y:auto;}
.an-area-item{padding:12px 16px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.04);transition:var(--transition);}
.an-area-item:hover{background:rgba(126,200,227,.06);}
.an-area-item.active{background:rgba(74,144,184,.12);border-left:3px solid var(--glow);}
.an-area-name{font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px;}
.an-area-meta{display:flex;align-items:center;gap:8px;}
.data-badge{font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:2px 7px;background:rgba(46,204,138,.15);color:var(--success);border:1px solid rgba(46,204,138,.3);white-space:nowrap;}
.data-badge.no-data{background:rgba(255,255,255,.05);color:var(--text-muted);border-color:rgba(255,255,255,.10);}
body.light-mode .data-badge.no-data{background:rgba(13,27,46,.05);border-color:rgba(13,27,46,.12);}

/* ── Main content ─────────────────────────────────────────── */
.an-main{padding:32px 36px;overflow-y:auto;}
@media(max-width:900px){.an-main{padding:20px 16px;}}
.an-page-title{font-family:'Cinzel',serif;font-size:22px;color:var(--glow-soft);margin-bottom:4px;}
body.light-mode .an-page-title{color:var(--primary);}
.an-page-sub{font-size:13px;color:var(--text-muted);margin-bottom:28px;}

/* ── Area selector bar (top of main) ─────────────────────── */
.area-header-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:28px;padding-bottom:18px;border-bottom:1px solid rgba(126,200,227,.10);}
.area-header-name{font-size:18px;font-weight:700;color:var(--text);}
.area-header-badges{display:flex;align-items:center;gap:8px;}

/* ── Stat row ─────────────────────────────────────────────── */
.an-stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:30px;}
.an-stat-card{background:rgba(255,255,255,.03);border:1px solid rgba(126,200,227,.10);padding:16px 18px;}
body.light-mode .an-stat-card{background:rgba(13,27,46,.03);border-color:rgba(13,27,46,.10);}
.an-stat-num{font-size:26px;font-weight:800;margin-bottom:4px;}
.an-stat-label{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--text-muted);}

/* ── Chart cards ──────────────────────────────────────────── */
.chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;}
@media(max-width:860px){.chart-grid{grid-template-columns:1fr;}}
.chart-card{background:rgba(255,255,255,.03);border:1px solid rgba(126,200,227,.10);padding:20px;}
body.light-mode .chart-card{background:rgba(13,27,46,.03);border-color:rgba(13,27,46,.10);}
.chart-card-full{grid-column:1/-1;}
.chart-card-title{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--glow);margin-bottom:14px;}
.chart-wrap-sm{position:relative;height:180px;}
.chart-wrap-lg{position:relative;height:280px;}
.chart-loading{display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:var(--text-muted);}

/* ── Table ────────────────────────────────────────────────── */
.table-card{background:rgba(255,255,255,.03);border:1px solid rgba(126,200,227,.10);margin-bottom:28px;}
body.light-mode .table-card{background:rgba(13,27,46,.03);border-color:rgba(13,27,46,.10);}
.table-card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(126,200,227,.10);}
.table-card-header span{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--glow);}
.no-data-msg{text-align:center;padding:32px;font-size:13px;color:var(--text-muted);}

/* ── Empty / loading states ───────────────────────────────── */
.an-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:300px;color:var(--text-muted);gap:10px;font-size:14px;}
.an-empty .icon{font-size:36px;opacity:.4;}
</style>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="analytics-layout">

  <!-- ── SIDEBAR ──────────────────────────────────────────── -->
  <div class="an-sidebar">
    <div class="an-sidebar-header">
      <div class="an-sidebar-title">City Areas</div>
      <input type="text" id="area-search" class="an-search" placeholder="Search areas…"/>
    </div>
    <div class="an-area-list" id="area-list">
      <?php foreach ($areas as $a): ?>
      <div class="an-area-item<?= $a['AreaID'] == $defaultAreaId ? ' active' : '' ?>"
           data-id="<?= $a['AreaID'] ?>"
           data-name="<?= htmlspecialchars($a['AreaName'], ENT_QUOTES) ?>"
           data-pollution="<?= $a['Pollution_level'] ?>">
        <div class="an-area-name"><?= htmlspecialchars($a['AreaName']) ?></div>
        <div class="an-area-meta">
          <span class="pollution-badge <?= $a['Pollution_level'] ?>"><?= $a['Pollution_level'] ?></span>
          <?php if (in_array($a['AreaID'], $areasWithData)): ?>
          <span class="data-badge">Data</span>
          <?php else: ?>
          <span class="data-badge no-data">No Data</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Export all -->
    <div style="padding:14px 16px;border-top:1px solid rgba(126,200,227,.10);">
      <div class="an-sidebar-title" style="margin-bottom:8px;">Export</div>
      <a id="sidebar-export-area" href="analytics-export.php?area_id=<?= $defaultAreaId ?>"
         class="btn btn-accent btn-sm btn-full" style="text-decoration:none;display:flex;align-items:center;gap:6px;justify-content:center;margin-bottom:8px;">
        ↓ Export Selected Area (.csv)
      </a>
      <a href="analytics-export.php?all=1"
         class="btn btn-sm btn-full" style="text-decoration:none;display:flex;align-items:center;gap:6px;justify-content:center;background:rgba(240,180,41,.10);border:1px solid rgba(240,180,41,.25);color:var(--warning);">
        ↓ Export All Areas (.csv)
      </a>
    </div>
  </div>

  <!-- ── MAIN ─────────────────────────────────────────────── -->
  <div class="an-main" id="an-main">

    <div id="area-header" class="area-header-bar">
      <div>
        <div class="an-page-title" id="area-display-name">Select an Area</div>
        <div class="an-page-sub">Weekly ambient light analytics</div>
      </div>
      <div class="area-header-badges" id="area-header-badges"></div>
    </div>

    <!-- Stat row -->
    <div class="an-stat-row" id="stat-row" style="display:none;">
      <div class="an-stat-card">
        <div class="an-stat-num" style="color:var(--glow);" id="stat-weeks">—</div>
        <div class="an-stat-label">Weeks of Data</div>
      </div>
      <div class="an-stat-card">
        <div class="an-stat-num" style="color:var(--success);" id="stat-avg-ambient">—</div>
        <div class="an-stat-label">Avg Ambient (lux)</div>
      </div>
      <div class="an-stat-card">
        <div class="an-stat-num" style="color:var(--accent);" id="stat-avg-lux">—</div>
        <div class="an-stat-label">Avg Lux Value</div>
      </div>
      <div class="an-stat-card">
        <div class="an-stat-num" style="color:var(--secondary);" id="stat-readings">—</div>
        <div class="an-stat-label">Total Readings</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="chart-grid" id="chart-grid" style="display:none;">
      <div class="chart-card chart-card-full">
        <div class="chart-card-title">Ambient Light Trend — All Weeks</div>
        <div class="chart-wrap-lg"><canvas id="line-chart"></canvas></div>
      </div>
      <div class="chart-card">
        <div class="chart-card-title">Last 4 Weeks — Avg Ambient</div>
        <div class="chart-wrap-sm"><canvas id="bar-chart"></canvas></div>
      </div>
      <div class="chart-card">
        <div class="chart-card-title">Ambient vs Lux — Last 4 Weeks</div>
        <div class="chart-wrap-sm"><canvas id="compare-chart"></canvas></div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-card" id="table-card" style="display:none;">
      <div class="table-card-header">
        <span>Weekly Data Table</span>
        <a id="table-export-btn" href="#" class="btn btn-accent btn-sm" style="text-decoration:none;">↓ Export (.csv)</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Week</th><th>Avg Ambient Light</th><th>Avg Lux Value</th><th>Total Readings</th><th>Pollution Level</th></tr></thead>
          <tbody id="data-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- Empty state -->
    <div class="an-empty" id="empty-state">
      <div class="icon">📊</div>
      <div>Select an area from the sidebar to view its analytics.</div>
    </div>

  </div><!-- /an-main -->
</div><!-- /analytics-layout -->

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
const AREAS_WITH_DATA = <?= $areasWithDataJson ?>;
let lineChart = null, barChart = null, compareChart = null;
let currentAreaId = <?= $defaultAreaId ?>;

function gc() {
  const lm = document.body.classList.contains('light-mode');
  return {
    grid: lm ? 'rgba(13,27,46,.15)' : 'rgba(255,255,255,.12)',
    text: lm ? 'rgba(13,27,46,.85)' : 'rgba(255,255,255,.85)',
    bar:  'rgba(74,144,184,.75)',
    line1:'#7EC8E3', line1bg:'rgba(126,200,227,.12)',
    line2:'#4A90B8', line2bg:'rgba(74,144,184,.06)',
  };
}

function destroyCharts() {
  [lineChart, barChart, compareChart].forEach(c => { if (c) c.destroy(); });
  lineChart = barChart = compareChart = null;
}

function showLoading() {
  document.getElementById('stat-row').style.display   = 'none';
  document.getElementById('chart-grid').style.display = 'none';
  document.getElementById('table-card').style.display = 'none';
  document.getElementById('empty-state').style.display = 'flex';
  document.getElementById('empty-state').innerHTML =
    '<div class="icon" style="font-size:24px;opacity:.5;">⏳</div><div>Loading analytics…</div>';
}

function showEmpty(msg) {
  document.getElementById('empty-state').style.display = 'flex';
  document.getElementById('empty-state').innerHTML =
    `<div class="icon">📭</div><div>${msg}</div>`;
}

function loadArea(areaId, areaName, areaPolli) {
  currentAreaId = areaId;
  destroyCharts();
  showLoading();

  // Update header
  document.getElementById('area-display-name').textContent = areaName + ' — Analytics';
  const pc = {'Low':'#2ECC8A','Medium':'#F0B429','High':'#E05C5C'};
  const pb = {'Low':'rgba(46,204,138,.12)','Medium':'rgba(240,180,41,.12)','High':'rgba(224,92,92,.12)'};
  document.getElementById('area-header-badges').innerHTML =
    `<span style="display:inline-block;padding:4px 12px;font-size:11px;font-weight:700;background:${pb[areaPolli]||'rgba(0,0,0,.06)'};color:${pc[areaPolli]||'inherit'};border:1px solid ${pc[areaPolli]||'#ccc'}22;letter-spacing:.5px">${areaPolli} Pollution</span>`;

  // Update export links
  document.getElementById('sidebar-export-area').href = `analytics-export.php?area_id=${areaId}`;
  document.getElementById('table-export-btn').href    = `analytics-export.php?area_id=${areaId}`;

  fetch(`api/chart-data.php?area_id=${areaId}&weeks=all`)
    .then(r => r.json())
    .then(data => {
      document.getElementById('empty-state').style.display = 'none';

      if (data.error === 'table_missing') {
        showEmpty('Run <strong>weekly_reading.sql</strong> in phpMyAdmin first, then re-run the aggregation script.');
        return;
      }
      if (!data.labels || data.labels.length === 0) {
        showEmpty('No weekly data yet for this area.<br><small>Run <code>cron/weekly-aggregate.php</code> to generate data.</small>');
        return;
      }

      // ── Stats ──────────────────────────────────────────────
      const totalReadings = data.table.reduce((s, r) => s + r.reading_count, 0);
      const avgAmbient    = data.ambient.reduce((s, v) => s + v, 0) / data.ambient.length;
      const avgLux        = data.lux.reduce((s, v) => s + v, 0) / data.lux.length;
      document.getElementById('stat-weeks').textContent       = data.labels.length;
      document.getElementById('stat-avg-ambient').textContent = avgAmbient.toFixed(1);
      document.getElementById('stat-avg-lux').textContent     = avgLux.toFixed(1);
      document.getElementById('stat-readings').textContent    = totalReadings.toLocaleString();
      document.getElementById('stat-row').style.display       = 'grid';

      const c = gc();

      // ── Line chart (all weeks) ──────────────────────────────
      lineChart = new Chart(document.getElementById('line-chart'), {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [
            { label: 'Avg Ambient Light', data: data.ambient, borderColor: c.line1, backgroundColor: c.line1bg, borderWidth: 2.5, tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: c.line1 },
            { label: 'Avg Lux Value',     data: data.lux,     borderColor: c.line2, backgroundColor: c.line2bg, borderWidth: 2,   tension: 0.4, fill: false, pointRadius: 3, borderDash: [5, 3] },
          ]
        },
        options: { responsive: true, maintainAspectRatio: false,
          plugins: { legend: { labels: { color: c.text, font: { size: 12 } } }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y.toFixed(1)} lux` } } },
          scales: { x: { ticks: { color: c.text, font: { size: 11 }, maxRotation: 38 }, grid: { color: c.grid } }, y: { ticks: { color: c.text, font: { size: 11 } }, grid: { color: c.grid } } }
        }
      });

      // ── Bar chart (last 4 weeks) ────────────────────────────
      const last4labels  = data.labels.slice(-4);
      const last4ambient = data.ambient.slice(-4);
      barChart = new Chart(document.getElementById('bar-chart'), {
        type: 'bar',
        data: { labels: last4labels, datasets: [{ label: 'Avg Ambient', data: last4ambient, backgroundColor: c.bar, borderColor: '#4A90B8', borderWidth: 1 }] },
        options: { responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y.toFixed(1)} lux` } } },
          scales: { x: { ticks: { color: c.text, font: { size: 10 }, maxRotation: 35 }, grid: { color: c.grid } }, y: { ticks: { color: c.text, font: { size: 10 } }, grid: { color: c.grid }, beginAtZero: false } }
        }
      });

      // ── Compare chart (last 8 weeks, ambient vs lux) ────────
      const last8labels  = data.labels.slice(-8);
      const last8ambient = data.ambient.slice(-8);
      const last8lux     = data.lux.slice(-8);
      compareChart = new Chart(document.getElementById('compare-chart'), {
        type: 'bar',
        data: {
          labels: last8labels,
          datasets: [
            { label: 'Ambient', data: last8ambient, backgroundColor: 'rgba(126,200,227,.6)', borderColor: c.line1, borderWidth: 1 },
            { label: 'Lux',     data: last8lux,     backgroundColor: 'rgba(74,144,184,.45)',  borderColor: c.line2, borderWidth: 1 },
          ]
        },
        options: { responsive: true, maintainAspectRatio: false,
          plugins: { legend: { labels: { color: c.text, font: { size: 11 } } }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y.toFixed(1)} lux` } } },
          scales: { x: { ticks: { color: c.text, font: { size: 9 }, maxRotation: 40 }, grid: { color: c.grid } }, y: { ticks: { color: c.text, font: { size: 10 } }, grid: { color: c.grid } } }
        }
      });

      document.getElementById('chart-grid').style.display = 'grid';

      // ── Table ───────────────────────────────────────────────
      const pc2 = {'Low':'#2ECC8A','Medium':'#F0B429','High':'#E05C5C'};
      const pb2 = {'Low':'rgba(46,204,138,.12)','Medium':'rgba(240,180,41,.12)','High':'rgba(224,92,92,.12)'};
      document.getElementById('data-tbody').innerHTML = data.table.map(r => {
        const p = r.pollution_level || '';
        const badge = p ? `<span style="display:inline-block;padding:3px 10px;font-size:11px;font-weight:700;background:${pb2[p]||'rgba(0,0,0,.06)'};color:${pc2[p]||'inherit'};border:1px solid ${pc2[p]||'#ccc'}22;letter-spacing:.5px">${p}</span>` : '—';
        return `<tr><td>${r.week_label}</td><td><strong>${r.avg_ambient.toFixed(1)}</strong> lux</td><td>${r.avg_lux.toFixed(1)} lux</td><td>${r.reading_count.toLocaleString()}</td><td>${badge}</td></tr>`;
      }).join('');
      document.getElementById('table-card').style.display = 'block';
    })
    .catch(() => showEmpty('Could not load data. Make sure <strong>weekly_reading.sql</strong> is imported.'));
}

// ── Sidebar interactions ───────────────────────────────────────
document.querySelectorAll('.an-area-item').forEach(el => {
  el.addEventListener('click', function () {
    document.querySelectorAll('.an-area-item').forEach(x => x.classList.remove('active'));
    this.classList.add('active');
    loadArea(parseInt(this.dataset.id), this.dataset.name, this.dataset.pollution);
  });
});

document.getElementById('area-search').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.an-area-item').forEach(el => {
    el.style.display = el.querySelector('.an-area-name').textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// ── Auto-load default area ─────────────────────────────────────
<?php if ($defaultAreaId): ?>
(function () {
  const el = document.querySelector(`.an-area-item[data-id="<?= $defaultAreaId ?>"]`);
  if (el) loadArea(<?= $defaultAreaId ?>, el.dataset.name, el.dataset.pollution);
})();
<?php else: ?>
document.getElementById('empty-state').innerHTML =
  '<div class="icon">📭</div><div>No areas found. Add areas and lamps in the database first.</div>';
<?php endif; ?>
</script>
</body>
</html>