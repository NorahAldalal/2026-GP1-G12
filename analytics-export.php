<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { ob_end_clean(); header('Location: login.php'); exit; }
require_once __DIR__ . '/config/db.php';

$isAdmin       = ($_SESSION['user_role'] ?? '') === 'admin';
$requestedArea = (int)($_GET['area_id'] ?? 0);
$exportAll     = isset($_GET['all']) && $isAdmin;

try {
    if ($isAdmin) {
        if ($exportAll) {
            $areas = db()->query('SELECT AreaID, AreaName FROM area ORDER BY AreaName')->fetchAll();
        } else {
            if (!$requestedArea) { ob_end_clean(); echo 'Missing area_id'; exit; }
            $s = db()->prepare('SELECT AreaID, AreaName FROM area WHERE AreaID=?');
            $s->execute([$requestedArea]); $areas = $s->fetchAll();
        }
    } else {
        $myArea = (int)($_SESSION['user_area'] ?? 0);
        $s = db()->prepare('SELECT AreaID, AreaName FROM area WHERE AreaID=?');
        $s->execute([$myArea]); $areas = $s->fetchAll();
    }

    if (empty($areas)) { ob_end_clean(); echo 'No areas found'; exit; }

    // Collect all rows
    $allRows = [];
    foreach ($areas as $area) {
        $s = db()->prepare('
            SELECT wr.WeekStart, wr.AvgLux, a.AreaName, a.Pollution_level
            FROM weekly_reading wr
            JOIN area a ON wr.AreaID = a.AreaID
            WHERE wr.AreaID = ?
            ORDER BY wr.WeekStart ASC
        ');
        $s->execute([$area['AreaID']]);
        foreach ($s->fetchAll() as $row) {
            $allRows[] = $row;
        }
    }

    // Build Excel XML (SpreadsheetML) — opens in Excel with proper sheet name
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:x="urn:schemas-microsoft-com:office:excel">
    <Styles>
        <Style ss:ID="header">
            <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/>
            <Interior ss:Color="#1E3050" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center"/>
        </Style>
        <Style ss:ID="rowEven">
            <Interior ss:Color="#EEF3F8" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="rowOdd">
            <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="date">
            <NumberFormat ss:Format="YYYY-MM-DD"/>
        </Style>
    </Styles>
    <Worksheet ss:Name="Ambient Light Data">
    <Table ss:DefaultColumnWidth="120">
        <Column ss:Width="160"/>
        <Column ss:Width="130"/>
        <Column ss:Width="110"/>
        <Column ss:Width="130"/>
        <Row>
            <Cell ss:StyleID="header"><Data ss:Type="String">Area</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">Week Starting</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">Avg Lux</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">Pollution Level</Data></Cell>
        </Row>';

    foreach ($allRows as $i => $row) {
        $style = ($i % 2 === 0) ? 'rowEven' : 'rowOdd';
        $lux   = number_format((float)$row['AvgLux'], 1);
        $xml  .= '<Row>
            <Cell ss:StyleID="'.$style.'"><Data ss:Type="String">'.htmlspecialchars($row['AreaName']).'</Data></Cell>
            <Cell ss:StyleID="'.$style.'"><Data ss:Type="String">'.htmlspecialchars($row['WeekStart']).'</Data></Cell>
            <Cell ss:StyleID="'.$style.'"><Data ss:Type="Number">'.$lux.'</Data></Cell>
            <Cell ss:StyleID="'.$style.'"><Data ss:Type="String">'.htmlspecialchars($row['Pollution_level']).'</Data></Cell>
        </Row>';
    }

    $xml .= '</Table></Worksheet></Workbook>';

    // Send Excel file
    ob_end_clean();
    $slug = $exportAll ? 'All-Areas' : preg_replace('/\W/', '-', $areas[0]['AreaName']);
    $filename = 'SIRAJ-Analytics-' . $slug . '-' . date('Y-m-d') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');
    echo $xml;

} catch (Exception $e) {
    ob_end_clean();
    echo 'Error: ' . $e->getMessage();
}