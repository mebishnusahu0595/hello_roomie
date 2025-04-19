<?php
include 'includes/db_connect.php';
include 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['roommate_name'])) {
    $roommate_name = sanitize_input($_POST['roommate_name']);
    
    $exists = check_roommate($conn, $roommate_name);
    
    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>