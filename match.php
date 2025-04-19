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
$user_name = $_SESSION['user_name'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get potential matches (only students of the same gender)
$stmt = $conn->prepare("SELECT * FROM students WHERE id != ? AND sex = ?");
$stmt->bind_param("is", $user_id, $user_data['sex']);
$stmt->execute();
$result = $stmt->get_result();

$matches = array();
while ($row = $result->fetch_assoc()) {
    $match_score = calculate_match_score($user_data, $row);
    $matches[] = array(
        'id' => $row['id'],
        'name' => $row['name'],
        'hostel_block' => $row['hostel_block'],
        'hobbies' => $row['hobbies'],
        'games' => $row['games'],
        'study_habits' => $row['study_habits'],
        'sleep_schedule' => $row['sleep_schedule'],
        'score' => $match_score
    );
}

// Sort matches by score (highest first)
usort($matches, function($a, $b) {
    return $b['score'] - $a['score'];
});

// Check for preferred roommate
$preferred_roommate = $user_data['preferred_roommate'];
$preferred_match = null;

if (!empty($preferred_roommate)) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE name = ? AND id != ?");
    $stmt->bind_param("si", $preferred_roommate, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $preferred_data = $result->fetch_assoc();
        $preferred_score = calculate_match_score($user_data, $preferred_data);
        $preferred_match = array(
            'id' => $preferred_data['id'],
            'name' => $preferred_data['name'],
            'hostel_block' => $preferred_data['hostel_block'],
            'hobbies' => $preferred_data['hobbies'],
            'games' => $preferred_data['games'],
            'study_habits' => $preferred_data['study_habits'],
            'sleep_schedule' => $preferred_data['sleep_schedule'],
            'score' => $preferred_score
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Matches - Hostel Roommate Matcher</title>
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
            <h1>Hello Romie</h1>
            <p>Welcome, <?php echo $user_name; ?>!</p>
        </header>
        
        <?php if(isset($_GET['success']) && isset($_GET['message'])): ?>
        <div class="card" style="background-color: rgba(76, 175, 80, 0.1); border-left: 4px solid #4CAF50;">
            <p style="color: #4CAF50; font-weight: bold;"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error']) && isset($_GET['message'])): ?>
        <div class="card" style="background-color: rgba(244, 67, 54, 0.1); border-left: 4px solid #F44336;">
            <p style="color: #F44336; font-weight: bold;"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Your Profile</h2>
            <div style="display: flex; flex-wrap: wrap; margin-top: 20px;">
                <div style="flex: 1; min-width: 250px; margin-right: 20px;">
                    <p><strong>Name:</strong> <?php echo $user_data['name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $user_data['email']; ?></p>
                    <p><strong>Hostel Block:</strong> <?php echo $user_data['hostel_block']; ?></p>
                    <p><strong>Study Habits:</strong> <?php echo $user_data['study_habits']; ?></p>
                    <p><strong>Sleep Schedule:</strong> <?php echo $user_data['sleep_schedule']; ?></p>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <p><strong>Hobbies:</strong> <?php echo $user_data['hobbies']; ?></p>
                    <p><strong>Games:</strong> <?php echo $user_data['games']; ?></p>
                    <p><strong>Preferred Roommate:</strong> <?php echo !empty($user_data['preferred_roommate']) ? $user_data['preferred_roommate'] : 'None specified'; ?></p>
                </div>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="edit_profile.php" class="btn">Edit Profile</a>
                <a href="index.php" class="btn">Home</a> 
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>
        
        <!-- For preferred roommate section -->
        <?php if ($preferred_match): ?>
        <div class="card match-animation">
            <h2>Your Preferred Roommate</h2>
            <div class="match-result">
                <h3><?php echo $preferred_match['name']; ?></h3>
                <p class="match-score" style="font-size: 1.2em; color: #4CAF50; font-weight: bold; background: #f0f8f0; padding: 5px 10px; border-radius: 5px; display: inline-block;">Match Score: <?php echo $preferred_match['score']; ?>%</p>
                <div style="display: flex; flex-wrap: wrap; margin-top: 20px;">
                    <div style="flex: 1; min-width: 250px; margin-right: 20px;">
                        <p><strong>Hostel Block:</strong> <?php echo $preferred_match['hostel_block']; ?></p>
                        <p><strong>Study Habits:</strong> <?php echo $preferred_match['study_habits']; ?></p>
                        <p><strong>Sleep Schedule:</strong> <?php echo $preferred_match['sleep_schedule']; ?></p>
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <p><strong>Hobbies:</strong> <?php echo $preferred_match['hobbies']; ?></p>
                        <p><strong>Games:</strong> <?php echo $preferred_match['games']; ?></p>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="match_details.php?id=<?php echo $preferred_match['id']; ?>" class="btn">View Detailed Match</a>
                    <a href="allocate_room.php?student1=<?php echo $user_id; ?>&student2=<?php echo $preferred_match['id']; ?>" class="btn" style="margin-left: 10px; background-color: #4CAF50;">Request Room</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- For top matches section -->
        <div class="card">
            <h2>Your Top Matches</h2>
            
            <?php if (count($matches) > 0): ?>
                <?php foreach (array_slice($matches, 0, 5) as $index => $match): ?>
                    <div class="match-result" style="animation-delay: <?php echo $index * 0.2; ?>s;">
                        <h3><?php echo $match['name']; ?></h3>
                        <p class="match-score" style="font-size: 1.2em; color: #4CAF50; font-weight: bold; background: #f0f8f0; padding: 5px 10px; border-radius: 5px; display: inline-block;">Match Score: <?php echo $match['score']; ?>%</p>
                        <div style="display: flex; flex-wrap: wrap; margin-top: 20px;">
                            <div style="flex: 1; min-width: 250px; margin-right: 20px;">
                                <p><strong>Hostel Block:</strong> <?php echo $match['hostel_block']; ?></p>
                                <p><strong>Study Habits:</strong> <?php echo $match['study_habits']; ?></p>
                                <p><strong>Sleep Schedule:</strong> <?php echo $match['sleep_schedule']; ?></p>
                            </div>
                            <div style="flex: 1; min-width: 250px;">
                                <p><strong>Hobbies:</strong> <?php echo $match['hobbies']; ?></p>
                                <p><strong>Games:</strong> <?php echo $match['games']; ?></p>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="match_details.php?id=<?php echo $match['id']; ?>" class="btn">View Detailed Match</a>
                            <a href="allocate_room.php?student1=<?php echo $user_id; ?>&student2=<?php echo $match['id']; ?>" class="btn" style="margin-left: 10px; background-color: #4CAF50;">Request Room</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center;">No matches found. Be the first to register!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Romie. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
    <script>
        // Create match animation when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createMatchAnimation();
        });
    </script>
</body>
</html>