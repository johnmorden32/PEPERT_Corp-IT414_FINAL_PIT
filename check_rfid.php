<?php
session_start(); 

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'pepert_corps_db';

// Connect to DB
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo "ERROR|DBCON";
    exit;
}

// Get UID from request
$uid = isset($_GET['uid']) ? strtoupper(trim($_GET['uid'])) : '';
if ($uid === '') {
    echo "ERROR|NOUID";
    exit;
}

// Check if UID exists in rfid_reg
$stmt = $conn->prepare("SELECT rfid_status FROM rfid_reg WHERE rfid_data = ? LIMIT 1");
$stmt->bind_param("s", $uid);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    // ✅ Registered card found
    $row = $res->fetch_assoc();
    $currentStatus = intval($row['rfid_status']);
    $stmt->close();

    // Toggle status (1 ↔ 0)
    $newStatus = ($currentStatus === 1) ? 0 : 1;

    // Update status in rfid_reg
    $update = $conn->prepare("UPDATE rfid_reg SET rfid_status = ? WHERE rfid_data = ?");
    $update->bind_param("is", $newStatus, $uid);
    $update->execute();
    $update->close();

    // Insert into rfid_logs
    $log = $conn->prepare("INSERT INTO rfid_logs (time_log, rfid_data, rfid_status) VALUES (NOW(), ?, ?)");
    $log->bind_param("si", $uid, $newStatus);
    $log->execute();
    $log->close();

    echo "FOUND|$newStatus";

} else {
    // ⚠️ Unregistered card — insert into logs with NULL status
    $stmt->close();

    $insert = $conn->prepare("INSERT INTO rfid_logs (time_log, rfid_data, rfid_status) VALUES (NOW(), ?, NULL)");
    $insert->bind_param("s", $uid);
    $insert->execute();
    $insert->close();

    echo "NOTFOUND|$uid";
}

$conn->close();
?>
