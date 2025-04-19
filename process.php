<?php
session_start();
include 'includes/db_connect.php';
include 'includes/functions.php';

// This file handles form submissions from various pages
// It's a central processing point that can redirect to appropriate pages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Determine which form was submitted
    if (isset($_POST['form_type'])) {
        $form_type = $_POST['form_type'];
        
        switch ($form_type) {
            case 'register':
                processRegistration();
                break;
                
            case 'login':
                processLogin();
                break;
                
            case 'edit_profile':
                processProfileEdit();
                break;
                
            default:
                // Invalid form type
                header("Location: index.php");
                exit();
        }
    } else {
        // No form type specified
        header("Location: index.php");
        exit();
    }
} else {
    // Not a POST request
    header("Location: index.php");
    exit();
}

function processRegistration() {
    global $conn;
    
    // Get form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $hostel_block = sanitize_input($_POST['hostel_block']);
    $hobbies = sanitize_input($_POST['hobbies']);
    $games = sanitize_input($_POST['games']);
    $study_habits = sanitize_input($_POST['study_habits']);
    $sleep_schedule = sanitize_input($_POST['sleep_schedule']);
    $preferred_roommate = sanitize_input($_POST['preferred_roommate']);
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email already exists
        $_SESSION['error_message'] = "Email already exists. Please use a different email.";
        header("Location: register.php");
        exit();
    } else {
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO students (name, email, password, hostel_block, hobbies, games, study_habits, sleep_schedule, preferred_roommate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $name, $email, $password, $hostel_block, $hobbies, $games, $study_habits, $sleep_schedule, $preferred_roommate);
        
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            
            // Redirect to match page
            header("Location: match.php");