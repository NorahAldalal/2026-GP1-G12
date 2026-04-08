<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';
requireAdmin();
$activePage = 'map';

$areas = db()->query('SELECT * FROM Area ORDER BY AreaName')->fetchAll();
$lamps = db()->query('SELECT l.*,a.AreaName,a.Latitude,a.Longitude,a.Pollution_level FROM Lamp l JOIN Area a ON l.AreaID=a.AreaID')->fetchAll();

$lampsJson = json_encode(array_map(fn($l)=>[
    'id'=>(int)$l['LampID'],'status'=>$l['Status'],'lux'=>(float)$l['Lux_Value'],
    'lat'=>(float)$l['Latitude']+(float)$l['offset_lat'],'lng'=>(float)$l['Longitude']+(float)$l['offset_lng'],
    'area'=>$l['AreaName'],'pollution'=>$l['Pollution_level'],
],$lamps));
$areasJson = json_encode(array_map(fn($a)=>[
    'id'=>(int)$a['AreaID'],'name'=>$a['AreaName'],'lat'=>(float)$a['Latitude'],'lng'=>(float)$a['Longitude'],'pollution'=>$a['Pollution_level'],
],$areas));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>City Map — SIRAJ Admin</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    .leaflet-popup-content-wrapper{background:var(--primary);color:white;border-radius:12px;}
    .leaflet-popup-tip{background:var(--primary);}
    .leaflet-popup-content{font-family:'Lato',sans-serif;font-size:13px;}
  </style>
</head>
<body class="dashboard-page map-page">
<?php include 'includes/nav.php'; ?>
<div class="map-layout">
  <div class="map-sidebar">
    <div class="map-sidebar-header">
      <div class="map-sidebar-title">All City Areas</div>
      <input type="text" id="area-search" class="map-search" placeholder="Search areas…"/>
    </div>
    <div class="map-areas" id="area-list">
      <?php foreach ($areas as $a): ?>
      <div class="area-item" data-lat="<?= $a['Latitude'] ?>" data-lng="<?= $a['Longitude'] ?>">
        <div class="area-name"><?= htmlspecialchars($a['AreaName']) ?></div>
        <div class="area-meta">
          <span class="pollution-badge <?= $a['Pollution_level'] ?>"><?= $a['Pollution_level'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,.07);">
      <div class="map-sidebar-title">SHOW LAMPS</div>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);margin-bottom:8px;cursor:pointer;"><input type="checkbox" id="show-on" checked style="accent-color:var(--success);width:14px;height:14px;"> Active (On)</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);cursor:pointer;"><input type="checkbox" id="show-off" checked style="accent-color:var(--danger);width:14px;height:14px;"> Offline (Off)</label>
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
const LAMPS=<?= $lampsJson ?>,AREAS=<?= $areasJson ?>;
const map=L.map('map',{center:AREAS.length?[AREAS[0].lat,AREAS[0].lng]:[24.6877,46.7219],zoom:13});
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'&copy; CARTO',maxZoom:20}).addTo(map);
const ml=L.layerGroup().addTo(map);
function makeIcon(s){const c=s==='on'?'#4CAF7D':'#E05C5C',g=s==='on'?'rgba(76,175,125,.5)':'rgba(224,92,92,.5)';return L.divIcon({className:'',html:`<div style="width:13px;height:13px;border-radius:50%;background:${c};border:2px solid white;box-shadow:0 0 9px ${g}"></div>`,iconSize:[13,13],iconAnchor:[6,6],popupAnchor:[0,-8]});}
function renderMarkers(){ml.clearLayers();const on=document.getElementById('show-on').checked,off=document.getElementById('show-off').checked;LAMPS.forEach(l=>{if(l.status==='on'&&!on)return;if(l.status==='off'&&!off)return;const c=l.status==='on'?'#4CAF7D':'#E05C5C';L.marker([l.lat,l.lng],{icon:makeIcon(l.status)}).bindPopup(`<div style="font-family:'Lato',sans-serif;padding:4px;"><strong style="font-size:14px;">💡 Lamp #${l.id}</strong><br><span style="color:${c}">● ${l.status==='on'?'Active':'Offline'}</span><br>Lux: ${l.lux.toFixed(1)}<br>📍 ${l.area}</div>`,{maxWidth:200}).addTo(ml);});}
renderMarkers();
['show-on','show-off'].forEach(id=>document.getElementById(id)?.addEventListener('change',renderMarkers));
document.querySelectorAll('.area-item').forEach(el=>{el.addEventListener('click',function(){document.querySelectorAll('.area-item').forEach(x=>x.classList.remove('active'));this.classList.add('active');map.flyTo([this.dataset.lat,this.dataset.lng],15,{duration:1.2});});});
document.getElementById('area-search')?.addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('.area-item').forEach(el=>{el.style.display=el.querySelector('.area-name').textContent.toLowerCase().includes(q)?'':'none';});});
</script>
</body>
</html>
