<?php
// We removed session_start() because config/database.php handles it!
require_once('../config/database.php');

date_default_timezone_set('Asia/Manila');

// Grab the ID instead of the name (Fallback to admin 1 for testing)
$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 1; 

$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$sqlScript = "-- NC Garments ERP Database Backup\n";
$sqlScript .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS = 0;\n\n"; 

foreach ($tables as $table) {
    // 🔥 THE FIX: Tell the SQL file to delete the old table before creating the new one!
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
    
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    $sqlScript .= $row[1] . ";\n\n";
    
    $result = $conn->query("SELECT * FROM $table");
    $columnCount = $result->field_count;
    
    for ($i = 0; $i < $columnCount; $i++) {
        while ($row = $result->fetch_row()) {
            $sqlScript .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                $row[$j] = $row[$j];
                if (isset($row[$j])) {
                    $sqlScript .= '"' . $conn->real_escape_string($row[$j]) . '"';
                } else {
                    $sqlScript .= 'NULL';
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ',';
                }
            }
            $sqlScript .= ");\n";
        }
    }
    $sqlScript .= "\n";
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS = 1;\n";

$bytes = strlen($sqlScript);
$file_size = number_format($bytes / 1048576, 2) . ' MB';
if ($bytes < 1048576) {
    $file_size = number_format($bytes / 1024, 2) . ' KB';
}

$filename = 'nc_garments_backup_' . date('Ymd_His') . '.sql';

// INSERT using admin_id
$stmt = $conn->prepare("INSERT INTO backup_log (filename, file_size, action_type, admin_id, status) VALUES (?, ?, 'Export', ?, 'Successful')");
$stmt->bind_param("ssi", $filename, $file_size, $admin_id);
$stmt->execute();

// Clear any accidental whitespace or invisible characters before sending headers
if (ob_get_length()) ob_clean();

// Force Browser Download
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"".$filename."\""); 
echo $sqlScript;
exit;
?>
