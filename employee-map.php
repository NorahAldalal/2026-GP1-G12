<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireEmployee();
$activePage = 'map';
$areaId = (int)($_SESSION['user_area'] ?? 0);
if (!$areaId) { header('Location: employee-home.php'); exit; }

$areaStmt = db()->prepare('SELECT * FROM Area WHERE AreaID=?');
$areaStmt->execute([$areaId]); $area = $areaStmt->fetch();

// Check if this area has weekly data
$hasData = false;
try {
    $dc = db()->prepare('SELECT COUNT(*) FROM weekly_reading WHERE AreaID=?');
    $dc->execute([$areaId]);
    $hasData = (int)$dc->fetchColumn() > 0;
} catch(Exception $e) { $hasData = false; }

$lampsStmt = db()->prepare('SELECT l.*,a.AreaName,a.Latitude,a.Longitude,a.Pollution_level FROM Lamp l JOIN Area a ON l.AreaID=a.AreaID WHERE l.AreaID=?');
$lampsStmt->execute([$areaId]); $lamps = $lampsStmt->fetchAll();

$lampsJson = json_encode(array_map(fn($l)=>['id'=>(int)$l['LampID'],'status'=>$l['Status'],'lux'=>(float)$l['Lux_Value'],'lat'=>(float)$l['Latitude']+(float)$l['offset_lat'],'lng'=>(float)$l['Longitude']+(float)$l['offset_lng'],'area'=>$l['AreaName']],$lamps));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>My Area Map — SIRAJ</title>
<link rel="stylesheet" href="assets/css/global.css"/>
<link rel="stylesheet" href="assets/css/dashboard.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.leaflet-popup-content-wrapper{background:var(--primary);color:white;}
.leaflet-popup-tip{background:var(--primary);}
.chart-label{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--glow);margin-bottom:10px;}
.chart-canvas-wrap{position:relative;height:130px;}
.chart-loading{display:flex;align-items:center;justify-content:center;height:130px;font-size:12px;color:var(--text-muted);}
.view-more-btn{display:block;width:100%;margin-top:10px;padding:8px;background:rgba(74,144,184,.10);border:1px solid rgba(74,144,184,.25);color:var(--glow);font-size:12px;font-weight:700;font-family:'Lato',sans-serif;cursor:pointer;transition:var(--transition);}
.view-more-btn:hover{background:rgba(74,144,184,.20);}
.analytics-modal{position:fixed;inset:0;z-index:600;background:rgba(3,8,18,.85);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s;}
.analytics-modal.open{opacity:1;pointer-events:all;}
.analytics-modal-box{background:rgba(10,20,40,.97);border:1px solid rgba(126,200,227,.14);width:min(880px,95vw);max-height:88vh;overflow-y:auto;position:relative;transform:scale(.92) translateY(20px);transition:transform .35s cubic-bezier(.175,.885,.32,1.275);padding:36px;}
.analytics-modal.open .analytics-modal-box{transform:scale(1) translateY(0);}
body.light-mode .analytics-modal-box{background:rgba(255,255,255,.98);border-color:rgba(13,27,46,.10);}
.mcls{position:absolute;top:14px;right:16px;width:30px;height:30px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);}
.mcls:hover{background:rgba(224,92,92,.15);color:#ff9494;}
.modal-atitle{font-family:'Cinzel',serif;font-size:20px;color:var(--glow-soft);margin-bottom:6px;}
body.light-mode .modal-atitle{color:var(--primary);}
.modal-asub{font-size:13px;color:var(--text-muted);margin-bottom:28px;}
.line-wrap{position:relative;height:270px;margin-bottom:28px;}
.no-data-msg{text-align:center;padding:22px;font-size:13px;color:var(--text-muted);}
.data-badge{font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:2px 7px;background:rgba(46,204,138,.15);color:var(--success);border:1px solid rgba(46,204,138,.3);white-space:nowrap;flex-shrink:0;}
.data-badge.no-data{background:rgba(255,255,255,.05);color:var(--text-muted);border-color:rgba(255,255,255,.10);}
body.light-mode .data-badge.no-data{background:rgba(13,27,46,.05);border-color:rgba(13,27,46,.12);}
</style>
</head>
<body class="dashboard-page map-page">
<?php include 'includes/nav.php'; ?>
<div class="map-layout">
  <!-- SIDEBAR -->
  <div class="map-sidebar">
    <div class="map-sidebar-header">
      <div class="map-sidebar-title">My Assigned Area</div>
    </div>
    <div class="map-areas">
      <div class="area-item active">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
          <div class="area-name"><?= htmlspecialchars($area['AreaName']) ?></div>
          <?php if ($hasData): ?>
          <span class="data-badge" title="Analytics data available">Data</span>
          <?php else: ?>
          <span class="data-badge no-data" title="No analytics data yet">No Data</span>
          <?php endif; ?>
        </div>
        <div class="area-meta">
          <span class="pollution-badge <?= $area['Pollution_level'] ?>"><?= $area['Pollution_level'] ?></span>
          <?= count($lamps) ?> lamps
        </div>
      </div>
    </div>


    <!-- Filters -->
    <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,.07);">
      <div class="map-sidebar-title">FILTER</div>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);margin-bottom:8px;cursor:pointer;"><input type="checkbox" id="show-on" checked style="accent-color:var(--success);"> Active</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);cursor:pointer;"><input type="checkbox" id="show-off" checked style="accent-color:var(--danger);"> Offline</label>
    </div>
    <div style="padding:14px 16px;">
      <a href="employee-status.php" class="btn btn-danger btn-sm btn-full">Report a Fault</a>
    </div>
  </div>

  <!-- MAP -->
  <div class="map-view">
    <div id="map"></div>
    <div class="map-legend">
      <div class="legend-item"><div class="legend-dot" style="background:var(--success)"></div>Active</div>
      <div class="legend-item"><div class="legend-dot" style="background:var(--danger)"></div>Offline</div>
    </div>
  </div>
</div>



<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/main.js"></script>
<script>
const LAMPS=<?= $lampsJson ?>, AID=<?= $areaId ?>;
const map=L.map('map',{center:[<?= $area['Latitude'] ?>,<?= $area['Longitude'] ?>],zoom:15});
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'&copy; CARTO',maxZoom:20}).addTo(map);
const ml=L.layerGroup().addTo(map);
function makeIcon(s){const c=s==='on'?'#4CAF7D':'#E05C5C',g=s==='on'?'rgba(76,175,125,.5)':'rgba(224,92,92,.5)';return L.divIcon({className:'',html:`<div style="width:14px;height:14px;border-radius:50%;background:${c};border:2px solid white;box-shadow:0 0 9px ${g}"></div>`,iconSize:[14,14],iconAnchor:[7,7],popupAnchor:[0,-9]});}
function renderMarkers(){ml.clearLayers();const on=document.getElementById('show-on').checked,off=document.getElementById('show-off').checked;LAMPS.forEach(l=>{if(l.status==='on'&&!on)return;if(l.status==='off'&&!off)return;const c=l.status==='on'?'#4CAF7D':'#E05C5C';L.marker([l.lat,l.lng],{icon:makeIcon(l.status)}).bindPopup(`<div style="padding:4px"><strong>Lamp #${l.id}</strong><br><span style="color:${c}">● ${l.status==='on'?'Active':'Offline'}</span><br>Lux: ${l.lux.toFixed(1)}</div>`,{maxWidth:180}).addTo(ml);});}
renderMarkers();
['show-on','show-off'].forEach(id=>document.getElementById(id)?.addEventListener('change',renderMarkers));

let miniChart=null, lineChart=null;
function gc(){const lm=document.body.classList.contains('light-mode');return{grid:lm?'rgba(13,27,46,.15)':'rgba(255,255,255,.12)',text:lm?'rgba(13,27,46,.85)':'rgba(255,255,255,.85)',bar:'rgba(74,144,184,.75)',line1:'#7EC8E3',line1bg:'rgba(126,200,227,.12)',line2:'#4A90B8',line2bg:'rgba(74,144,184,.06)'};}

// Auto-load chart for employee's area
(function(){
  const loading=document.getElementById('chart-loading'),canvas=document.getElementById('mini-bar-chart');
  fetch(`api/chart-data.php?area_id=${AID}&weeks=4`)
    .then(r=>r.json()).then(data=>{
      loading.style.display='none';
      if(data.error==='table_missing'){loading.innerHTML='Run <strong>weekly_reading.sql</strong> first!';loading.style.display='flex';return;}
      if(!data.labels||data.labels.length===0){loading.innerHTML='No data yet.<br><small>Run cron/weekly-aggregate.php to generate data.</small>';loading.style.display='flex';return;}
      canvas.style.display='block';
      const c=gc();
      miniChart=new Chart(canvas,{type:'bar',data:{labels:data.labels,datasets:[{label:'Avg Ambient',data:data.ambient,backgroundColor:c.bar,borderColor:'#4A90B8',borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>` ${ctx.parsed.y.toFixed(1)} lux`}}},scales:{x:{ticks:{color:c.text,font:{size:9},maxRotation:35},grid:{color:c.grid}},y:{ticks:{color:c.text,font:{size:10}},grid:{color:c.grid},beginAtZero:false}}}});
    }).catch(e=>{
      const el=document.getElementById('chart-loading');
      el.innerHTML='Could not load. <br><small>Make sure weekly_reading.sql was imported.</small>';
      el.style.display='flex';
    });
})();

document.getElementById('view-more-btn').addEventListener('click',()=>openModal());
document.getElementById('modal-close').addEventListener('click',closeModal);
document.getElementById('analytics-modal').addEventListener('click',function(e){if(e.target===this)closeModal();});
function closeModal(){document.getElementById('analytics-modal').classList.remove('open');if(lineChart){lineChart.destroy();lineChart=null;}}

function openModal(){
  const modal=document.getElementById('analytics-modal');
  document.getElementById('modal-tbody').innerHTML='<tr><td colspan="5" class="no-data-msg">Loading…</td></tr>';
  if(lineChart){lineChart.destroy();lineChart=null;}
  modal.classList.add('open');
  fetch(`api/chart-data.php?area_id=${AID}&weeks=all`)
    .then(r=>r.json()).then(data=>{
      if(!data.labels||data.labels.length===0){document.getElementById('modal-tbody').innerHTML='<tr><td colspan="5" class="no-data-msg">No weekly data yet.</td></tr>';return;}
      const c=gc();
      lineChart=new Chart(document.getElementById('modal-line-chart'),{type:'line',data:{labels:data.labels,datasets:[{label:'Avg Ambient Light',data:data.ambient,borderColor:c.line1,backgroundColor:c.line1bg,borderWidth:2.5,tension:0.4,fill:true,pointRadius:4,pointBackgroundColor:c.line1},{label:'Avg Lux Value',data:data.lux,borderColor:c.line2,backgroundColor:c.line2bg,borderWidth:2,tension:0.4,fill:false,pointRadius:3,borderDash:[5,3]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:c.text,font:{size:12}}},tooltip:{callbacks:{label:ctx=>` ${ctx.parsed.y.toFixed(1)} lux`}}},scales:{x:{ticks:{color:c.text,font:{size:11},maxRotation:38},grid:{color:c.grid}},y:{ticks:{color:c.text,font:{size:11}},grid:{color:c.grid}}}}});
      document.getElementById('modal-tbody').innerHTML=data.table.map(r=>{
        const pc={'Low':'#2ECC8A','Medium':'#F0B429','High':'#E05C5C'};
        const pb={'Low':'rgba(46,204,138,.12)','Medium':'rgba(240,180,41,.12)','High':'rgba(224,92,92,.12)'};
        const p=r.pollution_level||'';
        const badge=p?`<span style="display:inline-block;padding:3px 10px;font-size:11px;font-weight:700;background:${pb[p]||'rgba(0,0,0,.06)'};color:${pc[p]||'inherit'};border:1px solid ${pc[p]||'#ccc'}22;letter-spacing:.5px">${p}</span>`:'—';
        return `<tr><td>${r.week_label}</td><td><strong>${r.avg_ambient.toFixed(1)}</strong> lux</td><td>${r.avg_lux.toFixed(1)} lux</td><td>${r.reading_count.toLocaleString()}</td><td>${badge}</td></tr>`;
      }).join('');
    }).catch(e=>{
      document.getElementById('modal-tbody').innerHTML='<tr><td colspan="5" class="no-data-msg">Could not load data. Make sure <strong>weekly_reading.sql</strong> is imported in phpMyAdmin.</td></tr>';
    });
}
</script>
</body>
</html>