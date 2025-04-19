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
$success_message = '';
$error_message = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $hostel_block = sanitize_input($_POST['hostel_block']);
    $hobbies = sanitize_input($_POST['hobbies']);
    $games = sanitize_input($_POST['games']);
    $study_habits = sanitize_input($_POST['study_habits']);
    $sleep_schedule = sanitize_input($_POST['sleep_schedule']);
    $preferred_roommate = sanitize_input($_POST['preferred_roommate']);
    
    // Check if email already exists (for another user)
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Email already exists. Please use a different email.";
    } else {
        // Update user data
        $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, hostel_block = ?, hobbies = ?, games = ?, study_habits = ?, sleep_schedule = ?, preferred_roommate = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $name, $email, $hostel_block, $hobbies, $games, $study_habits, $sleep_schedule, $preferred_roommate, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $_SESSION['user_name'] = $name;
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
        } else {
            $error_message = "Error updating profile: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Hello Roomie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
</head>
<body>
    <!-- Background Animation -->
    <ul class="background">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>
    
    <!-- Butterfly Animation Container -->
    <div class="butterfly-container"></div>
    
    <div class="container">
        <header>
            <h1>Hello Roomie</h1>
            <p>Edit Your Profile</p>
        </header>
        
        <div class="card">
            <h2>Edit Your Profile</h2>
            
            <?php if($success_message): ?>
                <div class="success" style="text-align: center; margin-bottom: 20px;"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
                <div class="error" style="text-align: center; margin-bottom: 20px;"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form id="edit-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo $user_data['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $user_data['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hostel_block">Hostel Block</label>
                    <select id="hostel_block" name="hostel_block" required>
                        <option value="">Select Hostel Block</option>
                        <option value="A Block" <?php if($user_data['hostel_block'] == 'A Block') echo 'selected'; ?>>A Block</option>
                        <option value="B Block" <?php if($user_data['hostel_block'] == 'B Block') echo 'selected'; ?>>B Block</option>
                        <option value="C Block" <?php if($user_data['hostel_block'] == 'C Block') echo 'selected'; ?>>C Block</option>
                        <option value="D Block" <?php if($user_data['hostel_block'] == 'D Block') echo 'selected'; ?>>D Block</option>
                        <option value="E Block" <?php if($user_data['hostel_block'] == 'E Block') echo 'selected'; ?>>E Block</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="hobbies">Your Hobbies (comma separated)</label>
                    <textarea id="hobbies" name="hobbies" rows="3" placeholder="e.g., reading, music, sports, cooking" required><?php echo $user_data['hobbies']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="games">Games You Play (comma separated)</label>
                    <textarea id="games" name="games" rows="3" placeholder="e.g., chess, football, video games" required><?php echo $user_data['games']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="study_habits">Study Habits</label>
                    <select id="study_habits" name="study_habits" required>
                        <option value="">Select Study Habit</option>
                        <option value="Morning person" <?php if($user_data['study_habits'] == 'Morning person') echo 'selected'; ?>>Morning person</option>
                        <option value="Night owl" <?php if($user_data['study_habits'] == 'Night owl') echo 'selected'; ?>>Night owl</option>
                        <option value="Regular intervals" <?php if($user_data['study_habits'] == 'Regular intervals') echo 'selected'; ?>>Regular intervals</option>
                        <option value="Weekend studier" <?php if($user_data['study_habits'] == 'Weekend studier') echo 'selected'; ?>>Weekend studier</option>
                        <option value="Last minute" <?php if($user_data['study_habits'] == 'Last minute') echo 'selected'; ?>>Last minute</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sleep_schedule">Sleep Schedule</label>
                    <select id="sleep_schedule" name="sleep_schedule" required>
                        <option value="">Select Sleep Schedule</option>
                        <option value="Early sleeper (before 10 PM)" <?php if($user_data['sleep_schedule'] == 'Early sleeper (before 10 PM)') echo 'selected'; ?>>Early sleeper (before 10 PM)</option>
                        <option value="Average (10 PM - 12 AM)" <?php if($user_data['sleep_schedule'] == 'Average (10 PM - 12 AM)') echo 'selected'; ?>>Average (10 PM - 12 AM)</option>
                        <option value="Late sleeper (after 12 AM)" <?php if($user_data['sleep_schedule'] == 'Late sleeper (after 12 AM)') echo 'selected'; ?>>Late sleeper (after 12 AM)</option>
                        <option value="Irregular" <?php if($user_data['sleep_schedule'] == 'Irregular') echo 'selected'; ?>>Irregular</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="preferred_roommate">Preferred Roommate (if any)</label>
                    <input type="text" id="preferred_roommate" name="preferred_roommate" value="<?php echo $user_data['preferred_roommate']; ?>" placeholder="Enter name if you have someone specific in mind">
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn">Update Profile</button>
                    <a href="match.php" class="btn">Back to Matches</a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hostel Roommate Matcher. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
    <script src="js/valid.js"></script>
</body>
</html>