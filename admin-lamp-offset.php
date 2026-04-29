<?php
// ================================================================
//  SIRAJ — Lamp Offset Manager
//  Allows the admin to set the GPS offset (distance) for each lamp
//  within an area, since all lamps share one GPS unit.
//
//  offset_lat / offset_lng are added to the area's GPS coordinates
//  to position each lamp individually on the map.
//
//  Access: Admin only.
// ================================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/db.php';

requireAdmin();
$activePage = 'offset';

$message     = '';
$messageType = 'success';

// ── Save offsets for all lamps in an area ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_offsets'])) {
    $areaId  = (int)($_POST['area_id'] ?? 0);
    $lampIds = $_POST['lamp_id']     ?? [];
    $lats    = $_POST['offset_lat']  ?? [];
    $lngs    = $_POST['offset_lng']  ?? [];

    if ($areaId && count($lampIds)) {
        $stmt = db()->prepare('
            UPDATE `lamp`
            SET offset_lat = ?, offset_lng = ?
            WHERE LampID = ? AND AreaID = ?
        ');
        foreach ($lampIds as $i => $lampId) {
            $lat = (float)str_replace(',', '.', $lats[$i] ?? 0);
            $lng = (float)str_replace(',', '.', $lngs[$i] ?? 0);
            $stmt->execute([$lat, $lng, (int)$lampId, $areaId]);
        }
        $message = 'Offsets saved successfully for ' . count($lampIds) . ' lamp(s).';
    }
}

// ── Load areas and selected area's lamps ─────────────────────
$areas = db()->query('SELECT * FROM area ORDER BY AreaName')->fetchAll();

$selectedAreaId = (int)($_GET['area_id'] ?? $_POST['area_id'] ?? ($areas[0]['AreaID'] ?? 0));

$selectedArea = null;
$lamps        = [];

if ($selectedAreaId) {
    $aStmt = db()->prepare('SELECT * FROM area WHERE AreaID = ?');
    $aStmt->execute([$selectedAreaId]);
    $selectedArea = $aStmt->fetch();

    $lStmt = db()->prepare('
        SELECT LampID, Status, Lux_Value, offset_lat, offset_lng
        FROM `lamp`
        WHERE AreaID = ?
        ORDER BY LampID ASC
    ');
    $lStmt->execute([$selectedAreaId]);
    $lamps = $lStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Set-Offsets — SIRAJ Admin</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/dashboard.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    /* ── Page layout ──────────────────────────────────────── */
    .offset-layout {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 0;
      min-height: calc(100vh - 64px - 48px);
    }
    @media (max-width: 900px) { .offset-layout { grid-template-columns: 1fr; } }

    /* ── Left panel: area selector ────────────────────────── */
    .offset-sidebar {
      background: var(--surface);
      border-right: 1px solid rgba(126,200,227,.10);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .offset-sidebar-top {
      padding: 22px 18px 14px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .offset-sidebar-heading {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--glow);
      margin-bottom: 12px;
    }
    .area-list { overflow-y: auto; flex: 1; }
    .area-list-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 13px 18px;
      border-bottom: 1px solid rgba(255,255,255,.04);
      border-left: 3px solid transparent;
      cursor: pointer;
      text-decoration: none;
      transition: all .2s;
    }
    .area-list-item:hover { background: rgba(126,200,227,.06); }
    .area-list-item.active {
      background: rgba(74,144,184,.10);
      border-left-color: var(--glow);
    }
    .area-list-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 4px;
    }
    .area-list-count {
      font-size: 11px;
      color: var(--text-muted);
    }
    .area-lamp-count {
      font-size: 11px;
      font-weight: 700;
      color: var(--glow);
      background: rgba(126,200,227,.10);
      padding: 2px 8px;
      border: 1px solid rgba(126,200,227,.20);
    }

    /* ── Right panel: lamp offset editor ─────────────────── */
    .offset-main { padding: 32px 36px; overflow-y: auto; }
    @media (max-width: 900px) { .offset-main { padding: 20px 16px; } }

    /* ── Page header ──────────────────────────────────────── */
    .offset-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .offset-title {
      font-family: 'Cinzel', serif;
      font-size: 20px;
      color: var(--glow-soft);
      margin-bottom: 6px;
    }
    body.light-mode .offset-title { color: var(--primary); }
    .offset-sub { font-size: 13px; color: var(--text-muted); line-height: 1.6; max-width: 560px; }

    /* ── Info box ─────────────────────────────────────────── */
    .info-box {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px 18px;
      background: rgba(74,144,184,.08);
      border: 1px solid rgba(74,144,184,.20);
      margin-bottom: 24px;
    }
    .info-box p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.7; }
    .info-box strong { color: var(--glow); }

    /* ── Map preview ──────────────────────────────────────── */
    .map-preview-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(126,200,227,.10);
      margin-bottom: 24px;
      overflow: hidden;
    }
    body.light-mode .map-preview-card {
      background: rgba(13,27,46,.03);
      border-color: rgba(13,27,46,.10);
    }
    .map-preview-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 18px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .map-preview-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--glow);
    }
    #map-preview { height: 400px; width: 100%; }

    /* ── Lamp table ───────────────────────────────────────── */
    .lamp-offset-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(126,200,227,.10);
      margin-bottom: 24px;
      overflow: hidden;
    }
    body.light-mode .lamp-offset-card {
      background: rgba(13,27,46,.03);
      border-color: rgba(13,27,46,.10);
    }
    .lamp-offset-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 20px;
      border-bottom: 1px solid rgba(126,200,227,.10);
    }
    .lamp-offset-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--glow);
    }

    /* ── Offset table ─────────────────────────────────────── */
    .offset-table { width: 100%; border-collapse: collapse; }
    .offset-table th {
      text-align: left;
      padding: 12px 16px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--text-muted);
      background: rgba(255,255,255,.02);
      border-bottom: 1px solid rgba(126,200,227,.08);
    }
    body.light-mode .offset-table th {
      background: rgba(13,27,46,.03);
      border-bottom-color: rgba(13,27,46,.08);
    }
    .offset-table td {
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255,255,255,.04);
      font-size: 13px;
      color: var(--text);
      vertical-align: middle;
    }
    body.light-mode .offset-table td {
      border-bottom-color: rgba(13,27,46,.06);
    }
    .offset-table tr:last-child td { border-bottom: none; }
    .offset-table tr:hover td { background: rgba(126,200,227,.04); }

    /* ── Offset inputs ────────────────────────────────────── */
    .offset-input-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .offset-label-sm {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-muted);
      letter-spacing: .5px;
      width: 28px;
      flex-shrink: 0;
    }
    .offset-input {
      width: 110px;
      padding: 8px 10px;
      font-size: 13px;
      font-family: 'Lato', sans-serif;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(126,200,227,.18);
      color: var(--text);
      outline: none;
      transition: border-color .2s;
      -moz-appearance: textfield;
    }
    .offset-input::-webkit-outer-spin-button,
    .offset-input::-webkit-inner-spin-button { -webkit-appearance: none; }
    .offset-input:focus { border-color: rgba(126,200,227,.5); }
    body.light-mode .offset-input {
      background: rgba(13,27,46,.05);
      border-color: rgba(13,27,46,.18);
      color: var(--text);
    }
    body.light-mode .offset-input:focus { border-color: rgba(13,27,46,.4); }

    /* ── Status dot ───────────────────────────────────────── */
    .lamp-dot {
      display: inline-block;
      width: 9px; height: 9px;
      border-radius: 50%;
      margin-right: 7px;
      flex-shrink: 0;
    }
    .lamp-dot.on  { background: var(--success); box-shadow: 0 0 6px rgba(46,204,138,.5); }
    .lamp-dot.off { background: var(--danger);  box-shadow: 0 0 6px rgba(224,92,92,.4); }

    /* ── Reset button ─────────────────────────────────────── */
    .reset-link {
      font-size: 11px;
      color: var(--text-muted);
      cursor: pointer;
      text-decoration: underline;
      background: none;
      border: none;
      padding: 0;
      font-family: inherit;
    }
    .reset-link:hover { color: var(--danger); }

    /* ── Empty state ──────────────────────────────────────── */
    .offset-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 300px;
      color: var(--text-muted);
      gap: 12px;
      font-size: 14px;
      text-align: center;
    }
    .offset-empty-icon { font-size: 40px; opacity: .3; }

    /* ── Leaflet dark override ────────────────────────────── */
    .leaflet-popup-content-wrapper { background: var(--primary); color: white; }
    .leaflet-popup-tip { background: var(--primary); }
  
/* ── Light mode sidebar fixes ─────────────────────────────── */
body.light-mode .offset-sidebar { background: #f0f4f8; }
body.light-mode .offset-sidebar-top { border-bottom-color: rgba(13,27,46,.10); }
body.light-mode .offset-sidebar-heading { color: #2d7aab; }
body.light-mode .offset-sidebar-top p { color: #4a5568; }
body.light-mode .area-list-item { border-bottom-color: rgba(13,27,46,.08); }
body.light-mode .area-list-item:hover { background: rgba(13,27,46,.04); }
body.light-mode .area-list-item.active { background: rgba(74,144,184,.15); border-left-color: #2d7aab; }
body.light-mode .area-list-name { color: #1a2744; }
body.light-mode .area-list-count { color: #4a5568; }

/* ── Light mode main content fixes ───────────────────────── */
body.light-mode .offset-title { color: #0D1B2E; }
body.light-mode .offset-sub { color: #4a5568; }
body.light-mode .lamp-offset-card { background: rgba(13,27,46,.02); border-color: rgba(13,27,46,.12); }
body.light-mode .lamp-offset-label { color: #2d7aab; }
body.light-mode .lamp-offset-header { border-bottom-color: rgba(13,27,46,.10); }
body.light-mode .offset-table th { color: #4a5568; background: rgba(13,27,46,.04); border-bottom-color: rgba(13,27,46,.10); }
body.light-mode .offset-table td { color: #1a2744; border-bottom-color: rgba(13,27,46,.07); }
body.light-mode .offset-table tr:hover td { background: rgba(13,27,46,.04); }
body.light-mode .offset-input { background: #fff; border-color: rgba(13,27,46,.20); color: #1a2744; }
body.light-mode .offset-input:focus { border-color: #2d7aab; }
body.light-mode .offset-label-sm { color: #4a5568; }
body.light-mode .map-preview-card { background: rgba(13,27,46,.02); border-color: rgba(13,27,46,.12); }
body.light-mode .map-preview-header { border-bottom-color: rgba(13,27,46,.10); }
body.light-mode .map-preview-label { color: #2d7aab; }
body.light-mode .info-box { background: rgba(13,27,46,.04); border-color: rgba(13,27,46,.15); }
body.light-mode .info-box p { color: #4a5568; }
body.light-mode .info-box strong { color: #2d7aab; }
body.light-mode .reset-link { color: #4a5568; }
body.light-mode .reset-link:hover { color: var(--danger); }
body.light-mode .offset-empty { color: #4a5568; }
</style>
</head>
<body class="dashboard-page">
<?php include 'includes/nav.php'; ?>

<div class="offset-layout">

  <!-- ══ SIDEBAR: Area Selector ═══════════════════════════ -->
  <div class="offset-sidebar">
    <div class="offset-sidebar-top">
      <div class="offset-sidebar-heading">Select Area</div>
      <p style="font-size:12px;color:var(--text-muted);margin:0;line-height:1.5;">
        Choose an area to edit its lamp offsets.
      </p>
    </div>
    <div class="area-list">
      <?php
      // Count lamps per area
      $lampCounts = [];
      $lcStmt = db()->query('SELECT AreaID, COUNT(*) AS cnt FROM lamp GROUP BY AreaID');
      foreach ($lcStmt->fetchAll() as $row) {
          $lampCounts[$row['AreaID']] = $row['cnt'];
      }
      foreach ($areas as $a):
        $cnt = $lampCounts[$a['AreaID']] ?? 0;
      ?>
      <a href="?area_id=<?= $a['AreaID'] ?>"
         class="area-list-item <?= $a['AreaID'] == $selectedAreaId ? 'active' : '' ?>">
        <div>
          <div class="area-list-name"><?= htmlspecialchars($a['AreaName']) ?></div>
          <div class="area-list-count"><?= $cnt ?> lamp<?= $cnt !== 1 ? 's' : '' ?></div>
        </div>
        <div>
          <span class="pollution-badge <?= $a['Pollution_level'] ?>"><?= $a['Pollution_level'] ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══ MAIN: Offset Editor ══════════════════════════════ -->
  <div class="offset-main">

    <?php if ($selectedArea): ?>

    <!-- Page header -->
    <div class="offset-header">
      <div>
        <div class="offset-title"><?= htmlspecialchars($selectedArea['AreaName']) ?> — Set-Offset Page</div>
       
      </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> visible" style="margin-bottom:20px;">
      <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Info box -->
    <!-- Offset Reference Table -->
    <div class="lamp-offset-card" style="margin-bottom:20px;">
      <div class="lamp-offset-header">
        <span class="lamp-offset-label">Offset Reference — Area Base: <?= $selectedArea['Latitude'] ?>, <?= $selectedArea['Longitude'] ?></span>
      </div>
      <div style="overflow-x:auto;">
        <table class="offset-table">
          <thead>
            <tr>
              <th>Offset Value</th>
              <th>Direction</th>
              <th>Approx. Distance</th>
              <th>Example Use</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>+0.0001 Lat</td><td>▲ North</td><td>≈ 11 m</td><td>Lamp very close to GPS unit</td></tr>
            <tr><td>+0.0005 Lat</td><td>▲ North</td><td>≈ 55 m</td><td>Lamp one block north</td></tr>
            <tr><td>+0.0010 Lat</td><td>▲ North</td><td>≈ 111 m</td><td>Lamp two blocks north</td></tr>
            <tr><td>-0.0001 Lat</td><td>▼ South</td><td>≈ 11 m</td><td>Lamp slightly south</td></tr>
            <tr><td>+0.0001 Lng</td><td>▶ East</td><td>≈ 9 m</td><td>Lamp slightly east</td></tr>
            <tr><td>+0.0005 Lng</td><td>▶ East</td><td>≈ 46 m</td><td>Lamp across the street east</td></tr>
            <tr><td>-0.0005 Lng</td><td>◀ West</td><td>≈ 46 m</td><td>Lamp across the street west</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (empty($lamps)): ?>
    <!-- No lamps -->
    <div class="offset-empty">
      <div class="offset-empty-icon">💡</div>
      <div>No lamps found in this area.<br>
        <span style="font-size:12px;">Add lamps to this area in the database first.</span>
      </div>
    </div>

    <?php else: ?>

    <!-- Map preview -->
    <div class="map-preview-card">
      <div class="map-preview-header">
        <span class="map-preview-label">Live Preview — Lamp Positions</span>
        <span style="font-size:11px;color:var(--text-muted);">Click a lamp name to highlight it on the map</span>
      </div>
      <div id="map-preview"></div>
    </div>

    <!-- Offset form -->
    <form method="POST" id="offset-form">
      <input type="hidden" name="save_offsets" value="1"/>
      <input type="hidden" name="area_id" value="<?= $selectedAreaId ?>"/>

      <div class="lamp-offset-card">
        <div class="lamp-offset-header">
          <span class="lamp-offset-label">Lamp Offsets — <?= count($lamps) ?> Lamp<?= count($lamps) !== 1 ? 's' : '' ?></span>
          <div style="display:flex;gap:10px;align-items:center;">
            <button type="button" class="reset-link" onclick="resetAll()">Reset all to 0</button>
            <button type="submit" class="btn btn-accent btn-sm">Save All Offsets</button>
          </div>
        </div>

        <div style="overflow-x:auto;">
          <table class="offset-table">
            <thead>
              <tr>
                <th>Lamp</th>
                <th>Status</th>
                <th>Lux</th>
                <th colspan="2">Offset Latitude</th>
                <th colspan="2">Offset Longitude</th>
                <th style="text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody id="lamp-tbody">
              <?php foreach ($lamps as $i => $lamp): ?>
              <tr data-lamp="<?= $lamp['LampID'] ?>">
                <td>
                  <input type="hidden" name="lamp_id[]" value="<?= $lamp['LampID'] ?>"/>
                  <strong style="color:var(--glow-soft);cursor:pointer;text-decoration:underline dotted;"
                          onclick="highlightLamp(<?= $lamp['LampID'] ?>)"
                          title="Click to highlight on map">Lamp #<?= $lamp['LampID'] ?></strong>
                </td>
                <td>
                  <span class="lamp-dot <?= $lamp['Status'] ?>"></span>
                  <?= ucfirst($lamp['Status']) ?>
                </td>
                <td style="color:var(--text-muted);"><?= number_format((float)$lamp['Lux_Value'], 1) ?></td>
                <td>
                  <div class="offset-input-group">
                    <span class="offset-label-sm">Lat</span>
                    <input type="number"
                           class="offset-input lat-input"
                           name="offset_lat[]"
                           value="<?= number_format((float)$lamp['offset_lat'], 7, '.', '') ?>"
                           step="0.0000001"
                           placeholder="0.0000000"
                           oninput="updatePreview()"/>
                  </div>
                </td>
                <td style="padding-left:0;">
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                    <?= (float)$lamp['offset_lat'] >= 0 ? '▲ North' : '▼ South' ?>
                  </div>
                </td>
                <td>
                  <div class="offset-input-group">
                    <span class="offset-label-sm">Lng</span>
                    <input type="number"
                           class="offset-input lng-input"
                           name="offset_lng[]"
                           value="<?= number_format((float)$lamp['offset_lng'], 7, '.', '') ?>"
                           step="0.0000001"
                           placeholder="0.0000000"
                           oninput="updatePreview()"/>
                  </div>
                </td>
                <td style="padding-left:0;">
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                    <?= (float)$lamp['offset_lng'] >= 0 ? '▶ East' : '◀ West' ?>
                  </div>
                </td>
                <td style="text-align:center;">
                  <button type="button"
                          class="reset-link"
                          onclick="resetRow(this)">Reset</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>


      </div>
    </form>

    <?php endif; // lamps exist ?>

    <?php else: ?>
    <!-- No area selected -->
    <div class="offset-empty">
      <div class="offset-empty-icon">🗺️</div>
      <div>Select an area from the sidebar to edit lamp offsets.</div>
    </div>
    <?php endif; ?>

  </div><!-- /offset-main -->
</div><!-- /offset-layout -->

<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/main.js"></script>

<?php if ($selectedArea && !empty($lamps)): ?>
<script>
const BASE_LAT = <?= $selectedArea['Latitude'] ?>;
const BASE_LNG = <?= $selectedArea['Longitude'] ?>;
const LAMPS_DATA = <?= json_encode(array_map(fn($l) => [
    'id'     => $l['LampID'],
    'status' => $l['Status'],
    'lat'    => (float)$l['offset_lat'],
    'lng'    => (float)$l['offset_lng'],
], $lamps)) ?>;

// ── Map init ──────────────────────────────────────────────────
const map = L.map('map-preview', {
  center: [BASE_LAT, BASE_LNG],
  zoom: 17,
  zoomControl: true,
});

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
  attribution: '&copy; CARTO',
  maxZoom: 20,
}).addTo(map);

// ── Base GPS marker (the GPS unit) ────────────────────────────
L.circleMarker([BASE_LAT, BASE_LNG], {
  radius: 8,
  color: '#F0B429',
  fillColor: 'rgba(240,180,41,.3)',
  fillOpacity: 1,
  weight: 2,
}).bindPopup('<strong>📡 GPS Unit</strong><br>Area base coordinates')
  .addTo(map);

// ── Lamp markers (updated live as user types) ─────────────────
const markerLayer = L.layerGroup().addTo(map);

function makeIcon(status, highlighted = false) {
  let color, glow;
  if (highlighted) {
    color = '#F0B429'; glow = 'rgba(240,180,41,.7)';
  } else if (status === 'on') {
    color = '#4CAF7D'; glow = 'rgba(76,175,125,.5)';
  } else {
    color = '#E05C5C'; glow = 'rgba(224,92,92,.4)';
  }
  const size = highlighted ? 16 : 12;
  return L.divIcon({
    className: '',
    html: `<div style="width:${size}px;height:${size}px;border-radius:50%;background:${color};border:2px solid white;box-shadow:0 0 10px ${glow};transition:all .3s;"></div>`,
    iconSize: [size, size],
    iconAnchor: [size/2, size/2],
    popupAnchor: [0, -size/2],
  });
}

// ── Highlight a specific lamp on the map ─────────────────────
// Turns it yellow and opens its popup when clicked from the table
let highlightedLampId = null;
const lampMarkers = {};

function highlightLamp(lampId) {
  // Reset previous highlight
  if (highlightedLampId && lampMarkers[highlightedLampId]) {
    const prev = lampMarkers[highlightedLampId];
    const prevLamp = LAMPS_DATA.find(l => l.id == highlightedLampId);
    prev.setIcon(makeIcon(prevLamp?.status || 'off', false));
    prev.closePopup();
  }

  // Highlight selected lamp
  highlightedLampId = lampId;
  if (lampMarkers[lampId]) {
    lampMarkers[lampId].setIcon(makeIcon(null, true)); // yellow
    lampMarkers[lampId].openPopup();

    const row = document.querySelector(`#lamp-tbody tr[data-lamp="${lampId}"]`);
    const latIn = parseFloat(row?.querySelector('.lat-input')?.value) || 0;
    const lngIn = parseFloat(row?.querySelector('.lng-input')?.value) || 0;
    map.panTo([BASE_LAT + latIn, BASE_LNG + lngIn]);
  }
}

// ── Build markers from current input values ───────────────────
function updatePreview() {
  markerLayer.clearLayers();

  const rows = document.querySelectorAll('#lamp-tbody tr');
  rows.forEach(row => {
    const lampId  = row.dataset.lamp;
    const latIn   = parseFloat(row.querySelector('.lat-input').value) || 0;
    const lngIn   = parseFloat(row.querySelector('.lng-input').value) || 0;
    const lamp    = LAMPS_DATA.find(l => l.id == lampId);
    const status  = lamp?.status || 'off';
    const finalLat = BASE_LAT + latIn;
    const finalLng = BASE_LNG + lngIn;

    const isHighlighted = (lampId == highlightedLampId);
    const marker = L.marker([finalLat, finalLng], { icon: makeIcon(status, isHighlighted) })
      .bindPopup(`<div style="padding:4px;"><strong>Lamp #${lampId}</strong><br>
        Offset Lat: ${latIn.toFixed(7)}<br>
        Offset Lng: ${lngIn.toFixed(7)}<br>
        Final: ${finalLat.toFixed(6)}, ${finalLng.toFixed(6)}</div>`, { maxWidth: 220 })
      .addTo(markerLayer);
    lampMarkers[lampId] = marker;
  });
}

// ── Reset helpers ─────────────────────────────────────────────
function resetRow(btn) {
  const row = btn.closest('tr');
  row.querySelector('.lat-input').value = '0.0000000';
  row.querySelector('.lng-input').value = '0.0000000';
  updatePreview();
}

function resetAll() {
  if (!confirm('Reset all offsets to 0? This will need to be saved.')) return;
  document.querySelectorAll('.lat-input, .lng-input').forEach(inp => inp.value = '0.0000000');
  updatePreview();
}

// ── Initial render ────────────────────────────────────────────
updatePreview();
</script>
<?php endif; ?>
</body>
</html>
