<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

requireLogin();

$areaId = (int)($_GET['area_id'] ?? 0);
$weeks  = $_GET['weeks'] ?? '4';

if (!$areaId) {
    echo json_encode(['error' => 'area_id required']); exit;
}

// Employees can only access their own area
if (!isAdmin()) {
    $myArea = (int)($_SESSION['user_area'] ?? 0);
    if ($myArea !== $areaId) {
        echo json_encode(['error' => 'Access denied']); exit;
    }
}

// Check if weekly_reading table exists
try {
    $check = db()->query("SHOW TABLES LIKE 'weekly_reading'")->fetch();
    if (!$check) {
        echo json_encode([
            'error'   => 'table_missing',
            'message' => 'weekly_reading table not found. Please run weekly_reading.sql first.',
            'labels'  => [], 'ambient' => [], 'lux' => [], 'table' => []
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]); exit;
}

// Fetch weekly readings
$limit = ($weeks === 'all') ? 52 : max(1, min(52, (int)$weeks));

$stmt = db()->prepare("
    SELECT wr.WeekStart, wr.WeekEnd, wr.AvgAmbient, wr.AvgLux, wr.ReadingCount, a.AreaName, a.Pollution_level
    FROM weekly_reading wr
    JOIN area a ON wr.AreaID = a.AreaID
    WHERE wr.AreaID = ?
    ORDER BY wr.WeekStart DESC
    LIMIT $limit
");
$stmt->execute([$areaId]);
$rows = array_reverse($stmt->fetchAll());

$labels = $ambientData = $luxData = $tableData = [];

foreach ($rows as $row) {
    $label = date('d M', strtotime($row['WeekStart'])) . ' – ' . date('d M', strtotime($row['WeekEnd']));
    $labels[]      = $label;
    $ambientData[] = (float)$row['AvgAmbient'];
    $luxData[]     = (float)$row['AvgLux'];
    $tableData[]   = [
        'week_label'    => $label,
        'avg_ambient'   => (float)$row['AvgAmbient'],
        'avg_lux'       => (float)$row['AvgLux'],
        'reading_count'    => (int)$row['ReadingCount'],
        'pollution_level'  => $row['Pollution_level'] ?? '',
    ];
}

echo json_encode([
    'area_id'  => $areaId,
    'labels'   => $labels,
    'ambient'  => $ambientData,
    'lux'      => $luxData,
    'table'    => $tableData,
    'area_name'=> $rows ? $rows[0]['AreaName'] : '',
]);
