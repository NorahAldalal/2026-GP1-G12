<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireEmployee();
$activePage = 'map';
$areaId = (int)($_SESSION['user_area'] ?? 0);

if (!$areaId) { header('Location: employee-home.php'); exit; }

$area  = db()->prepare('SELECT * FROM Area WHERE AreaID=?');
$area->execute([$areaId]); $area = $area->fetch();

$lamps = db()->prepare('SELECT l.*,a.AreaName,a.Latitude,a.Longitude,a.Pollution_level FROM Lamp l JOIN Area a ON l.AreaID=a.AreaID WHERE l.AreaID=?');
$lamps->execute([$areaId]); $lamps = $lamps->fetchAll();

$lampsJson = json_encode(array_map(fn($l)=>[
    'id'=>(int)$l['LampID'],'status'=>$l['Status'],'lux'=>(float)$l['Lux_Value'],
    'lat'=>(float)$l['Latitude']+(float)$l['offset_lat'],'lng'=>(float)$l['Longitude']+(float)$l['offset_lng'],
    'area'=>$l['AreaName'],
],$lamps));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Area Map — SIRAJ</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    .leaflet-popup-content-wrapper{background:var(--primary);color:white;border-radius:12px;}
    .leaflet-popup-tip{background:var(--primary);}
  </style>
</head>
<body class="dashboard-page map-page">
<?php include 'includes/nav.php'; ?>
<div class="map-layout">
  <div class="map-sidebar">
    <div class="map-sidebar-header">
      <div class="map-sidebar-title">My Assigned Area</div>
    </div>
    <div class="map-areas">
      <div class="area-item active">
        <div class="area-name">📍 <?= htmlspecialchars($area['AreaName']) ?></div>
        <div class="area-meta"><span class="pollution-badge <?= $area['Pollution_level'] ?>"><?= $area['Pollution_level'] ?></span> <?= count($lamps) ?> lamps</div>
      </div>
    </div>
    <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,.07);">
      <div class="map-sidebar-title">FILTER</div>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);margin-bottom:8px;cursor:pointer;"><input type="checkbox" id="show-on" checked style="accent-color:var(--success);"> Active</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);cursor:pointer;"><input type="checkbox" id="show-off" checked style="accent-color:var(--danger);"> Offline</label>
    </div>
    <div style="padding:14px 16px;">
      <a href="employee-status.php" class="btn btn-danger btn-sm btn-full">🚨 Report a Fault</a>
    </div>
  </div>
  <div class="map-view">
    <div id="map"></div>
    <div class="map-legend">
      <div class="legend-item"><div class="legend-dot" style="background:var(--success)"></div>Active</div>
      <div class="legend-item"><div class="legend-dot" style="background:var(--danger)"></div>Offline</div>
    </div>
  </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/main.js"></script>
<script>
const LAMPS=<?= $lampsJson ?>;
const map=L.map('map',{center:[<?= $area['Latitude'] ?>,<?= $area['Longitude'] ?>],zoom:15});
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'&copy; CARTO',maxZoom:20}).addTo(map);
const ml=L.layerGroup().addTo(map);
function makeIcon(s){const c=s==='on'?'#4CAF7D':'#E05C5C',g=s==='on'?'rgba(76,175,125,.5)':'rgba(224,92,92,.5)';return L.divIcon({className:'',html:`<div style="width:14px;height:14px;border-radius:50%;background:${c};border:2px solid white;box-shadow:0 0 9px ${g}"></div>`,iconSize:[14,14],iconAnchor:[7,7],popupAnchor:[0,-9]});}
function renderMarkers(){ml.clearLayers();const on=document.getElementById('show-on').checked,off=document.getElementById('show-off').checked;LAMPS.forEach(l=>{if(l.status==='on'&&!on)return;if(l.status==='off'&&!off)return;const c=l.status==='on'?'#4CAF7D':'#E05C5C';L.marker([l.lat,l.lng],{icon:makeIcon(l.status)}).bindPopup(`<div style="font-family:'Lato',sans-serif;padding:4px;"><strong>💡 Lamp #${l.id}</strong><br><span style="color:${c}">● ${l.status==='on'?'Active':'Offline'}</span><br>Lux: ${l.lux.toFixed(1)}</div>`,{maxWidth:180}).addTo(ml);});}
renderMarkers();
['show-on','show-off'].forEach(id=>document.getElementById(id)?.addEventListener('change',renderMarkers));
</script>
</body>
</html>
