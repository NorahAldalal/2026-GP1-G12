<?php
// ============================================================
//  SIRAJ — Weekly Aggregation Script
//  Run this every Monday via cron job:
//  0 2 * * 1 /usr/bin/php /path/to/Siraj/cron/weekly-aggregate.php
//
//  What it does:
//    1. Gets last week's Monday → Sunday date range
//    2. For each area, calculates average ambientLight from lampreading
//    3. Also averages Lux_Value from lamp table
//    4. Inserts/updates the result in weekly_reading table
// ============================================================

require_once __DIR__ . '/../config/db.php';

// ── Date range: last week Mon–Sun ─────────────────────────
$today     = new DateTime();
$lastMonday = clone $today;
$lastMonday->modify('last monday');
$lastMonday->modify('-6 days'); // go back to the Monday before last

// Actually: get the Monday of last week
$weekDay = (int)$today->format('N'); // 1=Mon, 7=Sun
$daysToLastMonday = $weekDay + 6;    // days since last Monday
$lastMonday = clone $today;
$lastMonday->modify("-{$daysToLastMonday} days");
$lastSunday = clone $lastMonday;
$lastSunday->modify('+6 days');

$weekStart = $lastMonday->format('Y-m-d');
$weekEnd   = $lastSunday->format('Y-m-d');

echo "[" . date('Y-m-d H:i:s') . "] Aggregating week: $weekStart → $weekEnd\n";

// ── Get all areas ─────────────────────────────────────────
$areas = db()->query('SELECT AreaID, AreaName FROM `area`')->fetchAll();

$inserted = 0;
$skipped  = 0;

foreach ($areas as $area) {
    $areaId   = $area['AreaID'];
    $areaName = $area['AreaName'];

    // Skip if already aggregated for this week
    $exists = db()->prepare('SELECT WeekID FROM `weekly_reading` WHERE AreaID=? AND WeekStart=?');
    $exists->execute([$areaId, $weekStart]);
    if ($exists->fetch()) {
        echo "  [SKIP] Area #{$areaId} ({$areaName}) — already aggregated.\n";
        $skipped++;
        continue;
    }

    // Calculate average ambientLight from lampreading for this area/week
    $stmt = db()->prepare('
        SELECT
            AVG(lr.ambientLight) AS avg_ambient,
            COUNT(lr.readingID)  AS reading_count
        FROM `lampreading` lr
        JOIN `lamp` l ON lr.LampID = l.LampID
        WHERE l.AreaID = ?
          AND lr.readingTime >= ?
          AND lr.readingTime <= CONCAT(?, " 23:59:59")
    ');
    $stmt->execute([$areaId, $weekStart, $weekEnd]);
    $ambientData = $stmt->fetch();

    // Average Lux from lamp table (current snapshot)
    $luxStmt = db()->prepare('SELECT AVG(Lux_Value) AS avg_lux FROM `lamp` WHERE AreaID = ?');
    $luxStmt->execute([$areaId]);
    $luxData = $luxStmt->fetch();

    $avgAmbient    = round((float)($ambientData['avg_ambient'] ?? 0), 2);
    $avgLux        = round((float)($luxData['avg_lux'] ?? 0), 2);
    $readingCount  = (int)($ambientData['reading_count'] ?? 0);

    // Insert into weekly_reading
    db()->prepare('
        INSERT INTO `weekly_reading` (AreaID, WeekStart, WeekEnd, AvgAmbient, AvgLux, ReadingCount)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            AvgAmbient   = VALUES(AvgAmbient),
            AvgLux       = VALUES(AvgLux),
            ReadingCount = VALUES(ReadingCount)
    ')->execute([$areaId, $weekStart, $weekEnd, $avgAmbient, $avgLux, $readingCount]);

    echo "  [OK]   Area #{$areaId} ({$areaName}) — Ambient: {$avgAmbient}, Lux: {$avgLux}, Readings: {$readingCount}\n";
    $inserted++;
}

echo "\n✓ Done. Inserted/Updated: {$inserted}, Skipped: {$skipped}\n";
