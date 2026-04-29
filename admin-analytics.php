<?php
// ================================================================
//  SIRAJ — Admin Analytics
//  Standalone analytics page showing weekly ambient light data
//  for all city areas with interactive charts and export.
//  Access: Admin only.
// ================================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

requireAdmin();
$activePage = 'analytics';

$areas = db()->query('SELECT * FROM Area ORDER BY AreaName')->fetchAll();

// Which areas have weekly data
$areasWithData = [];
try {
    $dataCheck     = db()->query('SELECT DISTINCT AreaID FROM weekly_reading');
    $areasWithData = array_column($dataCheck->fetchAll(), 'AreaID');
} catch (Exception $e) { $areasWithData = []; }

// Default to first area that has data
$defaultAreaId = 0;
foreach ($areas as $a) {
    if (in_array($a['AreaID'], $areasWithData)) {
        $defaultAreaId = (int)$a['AreaID'];
        break;
    }
}
if (!$defaultAreaId && count($areas)) {
    $defaultAreaId = (int)$areas[0]['AreaID'];
}

$areasJson         = json_encode(array_map(fn($a) => [
    'id'        => (int)$a['AreaID'],
    'name'      => $a['AreaName'],
    'pollution' => $a['Pollution_level'],
], $areas));
$areasWithDataJson = json_encode(array_map('intval', $areasWithData));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Analytics — SIRAJ Admin</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    /* ── Layout ─────────────────────────────────────────── */
    .an-layout {
      display: grid;
      grid-template-columns: 280px 1fr;
      min-height: calc(100vh - 64px);
    }
    @media (max-width: 768px) { .an-layout { grid-template-columns: 1fr; } }

    /* ── Sidebar ─────────────────────────────────────────── */
    .an-sidebar {
      background: var(--surface);
      border-right: 1px solid rgba(126,200,227,.10);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .an-sidebar-top {
      padding: 22px 18px 14px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .an-sidebar-heading {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--glow);
      margin-bottom: 12px;
    }
    .an-search {
      width: 100%;
      padding: 9px 12px;
      font-size: 13px;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(126,200,227,.15);
      color: var(--text);
      outline: none;
      font-family: 'Lato', sans-serif;
      transition: border-color .2s;
    }
    .an-search:focus { border-color: rgba(126,200,227,.4); }
    body.light-mode .an-search {
      background: rgba(13,27,46,.04);
      border-color: rgba(13,27,46,.15);
      color: var(--text);
    }

    /* ── Area list ───────────────────────────────────────── */
    .an-area-list { flex: 1; overflow-y: auto; }
    .an-area-item {
      padding: 13px 18px;
      cursor: pointer;
      border-bottom: 1px solid rgba(255,255,255,.04);
      border-left: 3px solid transparent;
      transition: all .2s;
    }
    .an-area-item:hover { background: rgba(126,200,227,.06); }
    .an-area-item.active {
      background: rgba(74,144,184,.10);
      border-left-color: var(--glow);
    }
    .an-area-name { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
    .an-area-row  { display: flex; align-items: center; gap: 7px; }
    .data-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--success); flex-shrink: 0;
    }
    .data-dot.empty { background: rgba(255,255,255,.2); }
    .data-dot-label { font-size: 10px; color: var(--text-muted); }
body.light-mode .data-dot-label { color: #4a5568; }

    /* ── Sidebar export ──────────────────────────────────── */
    .an-sidebar-export {
      padding: 14px 18px;
      border-top: 1px solid rgba(126,200,227,.10);
      display: flex;
      flex-direction: column;
      gap: 7px;
    }

    /* ── Main content ────────────────────────────────────── */
    .an-main { padding: 32px 36px; overflow-y: auto; }
    @media (max-width: 900px) { .an-main { padding: 20px 16px; } }

    /* ── Area header ─────────────────────────────────────── */
    .an-area-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 28px;
      padding-bottom: 22px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .an-area-title {
      font-family: 'Cinzel', serif;
      font-size: 22px;
      color: var(--glow-soft);
      margin-bottom: 8px;
    }
    body.light-mode .an-area-title { color: var(--primary); }
    .an-area-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    /* ── Stat grid ───────────────────────────────────────── */
    .an-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 14px;
      margin-bottom: 30px;
    }
    .an-stat {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(126,200,227,.10);
      padding: 18px 20px;
      position: relative;
      overflow: hidden;
    }
    .an-stat::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
    }
    .an-stat.c-glow::before   { background: var(--glow); }
    .an-stat.c-success::before { background: var(--success); }
    .an-stat.c-accent::before  { background: var(--accent); }
    .an-stat.c-warn::before    { background: var(--warning); }
    body.light-mode .an-stat {
      background: rgba(13,27,46,.03);
      border-color: rgba(13,27,46,.10);
    }
    .an-stat-num   { font-size: 28px; font-weight: 800; margin-bottom: 5px; line-height: 1; }
    .an-stat-label { font-size: 10px; font-weight: 600; letter-spacing: .8px; text-transform: uppercase; color: var(--text-muted); }

    /* ── Chart grid ──────────────────────────────────────── */
    .an-chart-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
      margin-bottom: 26px;
    }
    @media (max-width: 860px) { .an-chart-grid { grid-template-columns: 1fr; } }
    .an-chart-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(126,200,227,.10);
      padding: 22px 20px;
    }
    body.light-mode .an-chart-card {
      background: rgba(13,27,46,.03);
      border-color: rgba(13,27,46,.10);
    }
    .an-chart-card.full { grid-column: 1 / -1; }
    .an-chart-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--glow);
      margin-bottom: 16px;
    }
    .an-chart-wrap-lg { position: relative; height: 280px; }
    .an-chart-wrap-sm { position: relative; height: 190px; }

    /* ── Table card ──────────────────────────────────────── */
    .an-table-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(126,200,227,.10);
      margin-bottom: 32px;
      overflow: hidden;
    }
    body.light-mode .an-table-card {
      background: rgba(13,27,46,.03);
      border-color: rgba(13,27,46,.10);
    }
    .an-table-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .an-table-header-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--glow);
    }

    /* ── Empty / loading states ──────────────────────────── */
    .an-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 340px;
      color: var(--text-muted);
      gap: 12px;
      font-size: 14px;
      text-align: center;
      line-height: 1.7;
    }
    .an-empty .an-empty-icon { font-size: 40px; opacity: .3; }
    .no-data-msg { text-align: center; padding: 30px; font-size: 13px; color: var(--text-muted); }
  
body.light-mode .an-sidebar { background: #f0f4f8; }
body.light-mode .an-area-name { color: #1a2744; }
body.light-mode .an-sidebar-title { color: #2d7aab; }
body.light-mode .an-area-item { border-bottom-color: rgba(13,27,46,.08); }
body.light-mode .an-area-item:hover { background: rgba(13,27,46,.04); }
body.light-mode .an-area-item.active { background: rgba(74,144,184,.15); }
body.light-mode .an-search { background: #fff; color: #1a2744; }
</style>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="an-layout">

  <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
  <div class="an-sidebar">
    <div class="an-sidebar-top">
      <div class="an-sidebar-heading">City Areas</div>
      <input type="text" id="area-search" class="an-search" placeholder="Search areas…"/>
    </div>

    <div class="an-area-list" id="area-list">
      <?php foreach ($areas as $a):
        $hasData = in_array($a['AreaID'], $areasWithData);
      ?>
      <div class="an-area-item <?= $a['AreaID'] == $defaultAreaId ? 'active' : '' ?>"
           data-id="<?= $a['AreaID'] ?>"
           data-name="<?= htmlspecialchars($a['AreaName'], ENT_QUOTES) ?>"
           data-pollution="<?= $a['Pollution_level'] ?>">
        <div class="an-area-name"><?= htmlspecialchars($a['AreaName']) ?></div>
        <div class="an-area-row">
          <span class="pollution-badge <?= $a['Pollution_level'] ?>"><?= $a['Pollution_level'] ?></span>
          <span class="data-dot <?= $hasData ? '' : 'empty' ?>"></span>
          <span class="data-dot-label"><?= $hasData ? 'Data available' : 'No data yet' ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="an-sidebar-export">
      <div class="an-sidebar-heading" style="margin-bottom:0;">Export</div>
      <a id="sidebar-export-btn" href="analytics-export.php?area_id=<?= $defaultAreaId ?>"
         class="btn btn-accent btn-sm btn-full" style="text-decoration:none;text-align:center;">
        ↓ Export Selected Area
      </a>
      <a href="analytics-export.php?all=1"
         class="btn btn-sm btn-full"
         style="text-decoration:none;text-align:center;background:rgba(240,180,41,.08);border:1px solid rgba(240,180,41,.22);color:var(--warning);">
        ↓ Export All Areas
      </a>
    </div>
  </div>

  <!-- ══ MAIN ═════════════════════════════════════════════ -->
  <div class="an-main" id="an-main">

    <!-- Area header -->
    <div class="an-area-header" id="an-area-header">
      <div>
        <div class="an-area-title" id="an-area-title">Select an Area</div>
        <div class="an-area-meta" id="an-area-meta"></div>
      </div>
      <div id="an-header-actions"></div>
    </div>

    <!-- Stats -->
    <div class="an-stats" id="an-stats" style="display:none;">
      <div class="an-stat c-glow">
        <div class="an-stat-num" style="color:var(--glow);" id="s-weeks">—</div>
        <div class="an-stat-label">Weeks of Data</div>
      </div>
      <div class="an-stat c-success">
        <div class="an-stat-num" style="color:var(--success);" id="s-ambient">—</div>
        <div class="an-stat-label">Avg Ambient (lux)</div>
      </div>
      <div class="an-stat c-accent">
        <div class="an-stat-num" style="color:var(--accent);" id="s-lux">—</div>
        <div class="an-stat-label">Avg Lux Value</div>
      </div>
      <div class="an-stat c-warn">
        <div class="an-stat-num" style="color:var(--warning);" id="s-readings">—</div>
        <div class="an-stat-label">Total Readings</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="an-chart-grid" id="an-chart-grid" style="display:none;">
      <div class="an-chart-card full">
        <div class="an-chart-label">Ambient Light Trend — All Weeks</div>
        <div class="an-chart-wrap-lg"><canvas id="chart-line"></canvas></div>
      </div>
      <div class="an-chart-card">
        <div class="an-chart-label">Last 4 Weeks — Avg Ambient</div>
        <div class="an-chart-wrap-sm"><canvas id="chart-bar"></canvas></div>
      </div>
      <div class="an-chart-card">
        <div class="an-chart-label">Ambient vs Lux — Last 8 Weeks</div>
        <div class="an-chart-wrap-sm"><canvas id="chart-compare"></canvas></div>
      </div>
    </div>

    <!-- Table -->
    <div class="an-table-card" id="an-table-card" style="display:none;">
      <div class="an-table-header">
        <span class="an-table-header-label">Weekly Data Table</span>
        <a id="table-export-btn" href="#" class="btn btn-accent btn-sm" style="text-decoration:none;">↓ Export (.xls)</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Week</th>
              <th>Avg Ambient Light</th>
              <th>Avg Lux Value</th>
              <th>Total Readings</th>
              <th>Pollution Level</th>
            </tr>
          </thead>
          <tbody id="an-tbody">
            <tr><td colspan="5" class="no-data-msg">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty state -->
    <div class="an-empty" id="an-empty">
      <div class="an-empty-icon">📊</div>
      <div>Select an area from the sidebar to view its analytics.</div>
    </div>

  </div><!-- /an-main -->
</div><!-- /an-layout -->

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
const AREAS_WITH_DATA = <?= $areasWithDataJson ?>;
let chartLine = null, chartBar = null, chartCompare = null;
let currentAreaId = <?= $defaultAreaId ?>;

// ── Chart color helper ────────────────────────────────────────
function gc() {
  const lm = document.body.classList.contains('light-mode');
  return {
    grid:    lm ? 'rgba(13,27,46,.12)'    : 'rgba(255,255,255,.10)',
    text:    lm ? 'rgba(13,27,46,.80)'    : 'rgba(255,255,255,.80)',
    bar:     'rgba(74,144,184,.70)',
    line1:   '#7EC8E3',
    line1bg: 'rgba(126,200,227,.10)',
    line2:   '#4A90B8',
    line2bg: 'rgba(74,144,184,.06)',
  };
}

// ── Destroy all chart instances ───────────────────────────────
function destroyCharts() {
  [chartLine, chartBar, chartCompare].forEach(c => c && c.destroy());
  chartLine = chartBar = chartCompare = null;
}

// ── Show empty/loading state ──────────────────────────────────
function showEmpty(html) {
  document.getElementById('an-stats').style.display      = 'none';
  document.getElementById('an-chart-grid').style.display = 'none';
  document.getElementById('an-table-card').style.display = 'none';
  const el = document.getElementById('an-empty');
  el.style.display  = 'flex';
  el.innerHTML      = `<div class="an-empty-icon">📭</div><div>${html}</div>`;
}

// ── Pollution badge HTML ──────────────────────────────────────
function pollutionBadge(p) {
  const colors = { Low: '#2ECC8A', Medium: '#F0B429', High: '#E05C5C' };
  const bgs    = { Low: 'rgba(46,204,138,.12)', Medium: 'rgba(240,180,41,.12)', High: 'rgba(224,92,92,.12)' };
  if (!p) return '—';
  return `<span style="display:inline-block;padding:3px 10px;font-size:11px;font-weight:700;
    background:${bgs[p]||'rgba(0,0,0,.06)'};color:${colors[p]||'inherit'};
    border:1px solid ${colors[p]||'#ccc'}33;letter-spacing:.5px;">${p}</span>`;
}

// ── Load area data ────────────────────────────────────────────
function loadArea(areaId, areaName, areaPolli) {
  currentAreaId = areaId;
  destroyCharts();
  showEmpty('<div style="font-size:18px;opacity:.3;margin-bottom:8px;">⏳</div>Loading analytics…');

  // Update header
  document.getElementById('an-area-title').textContent = areaName + ' — Analytics';
  document.getElementById('an-area-meta').innerHTML    = pollutionBadge(areaPolli) +
    `&nbsp;<span style="font-size:12px;color:var(--text-muted);">Pollution Level</span>`;
  document.getElementById('an-header-actions').innerHTML =
    `<a href="analytics-export.php?area_id=${areaId}" class="btn btn-accent btn-sm" style="text-decoration:none;">↓ Export (.xls)</a>`;

  // Update export links
  document.getElementById('sidebar-export-btn').href = `analytics-export.php?area_id=${areaId}`;
  document.getElementById('table-export-btn').href   = `analytics-export.php?area_id=${areaId}`;

  fetch(`api/chart-data.php?area_id=${areaId}&weeks=all`)
    .then(r => r.json())
    .then(render)
    .catch(() => showEmpty('Could not load data. Make sure <strong>weekly_reading.sql</strong> is imported in phpMyAdmin.'));
}

// ── Render data ───────────────────────────────────────────────
function render(data) {
  if (data.error === 'table_missing') {
    showEmpty('Run <strong>weekly_reading.sql</strong> in phpMyAdmin first.');
    return;
  }
  if (!data.labels || data.labels.length === 0) {
    showEmpty('No weekly data yet.<br><small>Run <code>cron/weekly-aggregate.php</code> to generate data.</small>');
    return;
  }

  document.getElementById('an-empty').style.display = 'none';

  // ── Stats ──────────────────────────────────────────────
  const totalReadings = data.table.reduce((s, r) => s + r.reading_count, 0);
  const avgAmbient    = data.ambient.reduce((s, v) => s + v, 0) / data.ambient.length;
  const avgLux        = data.lux.reduce((s, v) => s + v, 0) / data.lux.length;
  document.getElementById('s-weeks').textContent   = data.labels.length;
  document.getElementById('s-ambient').textContent = avgAmbient.toFixed(1);
  document.getElementById('s-lux').textContent     = avgLux.toFixed(1);
  document.getElementById('s-readings').textContent = totalReadings.toLocaleString();
  document.getElementById('an-stats').style.display = 'grid';

  const c = gc();

  // ── Line chart ─────────────────────────────────────────
  chartLine = new Chart(document.getElementById('chart-line'), {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [
        { label: 'Avg Ambient Light', data: data.ambient, borderColor: c.line1, backgroundColor: c.line1bg, borderWidth: 2.5, tension: 0.4, fill: true,  pointRadius: 4, pointBackgroundColor: c.line1 },
        { label: 'Avg Lux Value',     data: data.lux,     borderColor: c.line2, backgroundColor: c.line2bg, borderWidth: 2,   tension: 0.4, fill: false, pointRadius: 3, borderDash: [5, 3] },
      ]
    },
    options: chartOptions(c, true)
  });

  // ── Bar chart (last 4 weeks) ───────────────────────────
  chartBar = new Chart(document.getElementById('chart-bar'), {
    type: 'bar',
    data: {
      labels: data.labels.slice(-4),
      datasets: [{ label: 'Avg Ambient', data: data.ambient.slice(-4), backgroundColor: c.bar, borderColor: '#4A90B8', borderWidth: 1, borderRadius: 2 }]
    },
    options: chartOptions(c, false, true)
  });

  // ── Compare chart ──────────────────────────────────────
  chartCompare = new Chart(document.getElementById('chart-compare'), {
    type: 'bar',
    data: {
      labels: data.labels.slice(-8),
      datasets: [
        { label: 'Ambient', data: data.ambient.slice(-8), backgroundColor: 'rgba(126,200,227,.55)', borderColor: c.line1, borderWidth: 1, borderRadius: 2 },
        { label: 'Lux',     data: data.lux.slice(-8),     backgroundColor: 'rgba(74,144,184,.40)',  borderColor: c.line2, borderWidth: 1, borderRadius: 2 },
      ]
    },
    options: chartOptions(c)
  });

  document.getElementById('an-chart-grid').style.display = 'grid';

  // ── Table ──────────────────────────────────────────────
  document.getElementById('an-tbody').innerHTML = data.table.map(r =>
    `<tr>
      <td>${r.week_label}</td>
      <td><strong>${r.avg_ambient.toFixed(1)}</strong> lux</td>
      <td>${r.avg_lux.toFixed(1)} lux</td>
      <td>${r.reading_count.toLocaleString()}</td>
      <td>${pollutionBadge(r.pollution_level)}</td>
    </tr>`
  ).join('');
  document.getElementById('an-table-card').style.display = 'block';
}

// ── Shared chart options factory ──────────────────────────────
function chartOptions(c, hasLegend = false, noLegend = false) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: !noLegend && (hasLegend || false), labels: { color: c.text, font: { size: 12 } } },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y.toFixed(1)} lux` } }
    },
    scales: {
      x: { ticks: { color: c.text, font: { size: 10 }, maxRotation: 38 }, grid: { color: c.grid } },
      y: { ticks: { color: c.text, font: { size: 10 } }, grid: { color: c.grid }, beginAtZero: false }
    }
  };
}

// ── Area sidebar click ────────────────────────────────────────
document.querySelectorAll('.an-area-item').forEach(el => {
  el.addEventListener('click', function () {
    document.querySelectorAll('.an-area-item').forEach(x => x.classList.remove('active'));
    this.classList.add('active');
    loadArea(parseInt(this.dataset.id), this.dataset.name, this.dataset.pollution);
  });
});

// ── Search ────────────────────────────────────────────────────
document.getElementById('area-search').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.an-area-item').forEach(el => {
    el.style.display = el.querySelector('.an-area-name').textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// ── Auto-load default area on page open ──────────────────────
<?php if ($defaultAreaId): ?>
(function () {
  const el = document.querySelector(`.an-area-item[data-id="<?= $defaultAreaId ?>"]`);
  if (el) loadArea(<?= $defaultAreaId ?>, el.dataset.name, el.dataset.pollution);
})();
<?php else: ?>
document.getElementById('an-empty').innerHTML =
  '<div class="an-empty-icon">📭</div><div>No areas found. Add areas in the database first.</div>';
<?php endif; ?>
</script>
</body>
</html>