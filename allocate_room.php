<?php
session_start();
include 'includes/db_connect.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if both student IDs are provided
if (!isset($_GET['student1']) || !isset($_GET['student2'])) {
    header("Location: match.php");
    exit();
}

$student1_id = $_GET['student1'];
$student2_id = $_GET['student2'];

// Verify that the logged-in user is one of the students
if ($user_id != $student1_id && $user_id != $student2_id) {
    header("Location: match.php");
    exit();
}

// Automatically allocate room for the students
// We're only allocating an empty room for the first student
// The second student ID is passed but will not be used in the updated function
$result = allocate_room($conn, $student1_id, $student2_id);

// Redirect with appropriate message
if ($result['success']) {
    header("Location: match.php?success=1&message=" . urlencode("Room allocated successfully! Your room is " . $result['room']));
} else {
    header("Location: match.php?error=1&message=" . urlencode($result['message']));
}
exit();
?>