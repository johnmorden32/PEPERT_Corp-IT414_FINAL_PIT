<?php
session_start();

// Database configuration
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'pepert_corps_db';

// Connect to MySQL
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);

// Fetch Registered RFIDs
$rfids = [];
$rfidResult = $conn->query("SELECT rfid_data, rfid_status FROM rfid_reg ORDER BY rfid_data ASC");
if ($rfidResult && $rfidResult->num_rows > 0) {
    while ($row = $rfidResult->fetch_assoc()) {
        $rfids[] = $row;
    }
}

// Fetch logs from rfid_logs
$logs = [];
$logResult = $conn->query("SELECT * FROM rfid_logs ORDER BY time_log DESC");
if ($logResult && $logResult->num_rows > 0) {
    while ($row = $logResult->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Merge unregistered scans from session (if any)
if (isset($_SESSION['unregistered']) && !empty($_SESSION['unregistered'])) {
    foreach ($_SESSION['unregistered'] as $unreg) {
        $logs[] = $unreg;
    }
    // Sort logs by latest time
    usort($logs, function($a, $b) {
        return strtotime($b['time_log']) - strtotime($a['time_log']);
    });
}

// Status label helper
function statusLabel($status){
    if ($status === 1 || $status === "1") return "1";   // Login
    if ($status === 0 || $status === "0") return "0";   // Logout
    if (is_null($status) || $status === "" || strtolower($status) === "null") return "RFID NOT FOUND"; // Unregistered
    return "RFID NOT FOUND";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RFID Logs</title>
<meta http-equiv="refresh" content="5">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #111; color: #eee; display: flex; flex-direction: column; min-height: 100vh; }
.container { flex: 1; display: flex; padding: 20px; gap: 20px; width: 100%; }
.left-panel, .right-panel { background: #222; padding: 15px; border-radius: 8px; }
.left-panel { width: 250px; flex-shrink: 0; }
.left-panel h3 { color: #4CAF50; margin-bottom: 15px; text-align: left; }
.rfid-item { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; font-size: 15px; }
.toggle { position: relative; display: inline-block; width: 45px; height: 22px; }
.toggle input { display: none; }
.slider { position: absolute; cursor: default; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 22px; transition: .3s; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background: white; border-radius: 50%; transition: .3s; }
input:checked + .slider { background-color: #4CAF50; }
input:checked + .slider:before { transform: translateX(23px); }

.right-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    overflow-y: auto;
}

table {
    width: 95%;
    border-collapse: collapse;
    background: #222;
    text-align: center;
    border: none;
    margin-top: 0;
}

th, td {
    padding: 10px;
    border-bottom: 1px solid #333;
}

th { 
    background: #333; 
    color: #fff; 
    position: sticky;
    top: 0;
}

.status-login { color: #4CAF50; font-weight: bold; }
.status-logout { color: #2196F3; font-weight: bold; }
.status-notfound { color: #E53935; font-weight: bold; }
.row-notfound { background-color: rgba(229, 57, 53, 0.1); } /* Red tint */

.log-num {
    width: 50px;
    text-align: center;
    font-weight: bold;
    color: #fff;
}

@media (max-width:768px) {
    .container { flex-direction: column; }
    .left-panel { width: 100%; margin-bottom: 15px; }
    .right-panel { width: 100%; }
}
</style>
</head>
<body>

<div class="container">

    <div class="left-panel">
        <h3>RFID</h3>
        <?php foreach($rfids as $r): ?>
        <div class="rfid-item">
            <span><?= htmlspecialchars($r['rfid_data']) ?></span>
            <label class="toggle">
                <input type="checkbox" <?= ($r['rfid_status'] == 1) ? "checked" : "" ?> disabled>
                <span class="slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="right-panel">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>RFID</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if(!empty($logs)):
                    $count = 1;
                    foreach($logs as $row):
                        $statusText = statusLabel($row['rfid_status']);
                        if($statusText === "1") {
                            $statusClass = 'status-login';
                            $rowClass = '';
                        } elseif($statusText === "0") {
                            $statusClass = 'status-logout';
                            $rowClass = '';
                        } else {
                            $statusClass = 'status-notfound';
                            $rowClass = 'row-notfound';
                        }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="log-num"><?= $count++ ?></td>
                    <td><?= htmlspecialchars($row['rfid_data']) ?></td>
                    <td class="<?= $statusClass ?>"><?= $statusText ?></td>
                    <td><?= date("F j, Y g:i A", strtotime($row['time_log'])) ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4">No logs available</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
