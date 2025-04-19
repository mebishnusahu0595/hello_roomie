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

// Check for success or error messages in URL
if (isset($_GET['success']) && isset($_GET['message'])) {
    $success_message = urldecode($_GET['message']);
} elseif (isset($_GET['error']) && isset($_GET['message'])) {
    $error_message = urldecode($_GET['message']);
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get room allocation for the user
$room_data = get_student_room($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        .dashboard-container {
            margin-top: 20px;
        }
        
        .room-card {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .room-card h3 {
            margin-top: 0;
            font-size: 24px;
            color: white;
        }
        
        .room-card p {
            margin: 10px 0;
            font-size: 16px;
        }
        
        .room-card .room-number {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 36px;
            font-weight: bold;
            opacity: 0.8;
        }
        
        .room-details {
            display: flex;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .detail-card {
            flex: 1;
            min-width: 250px;
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            margin: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .detail-card h4 {
            color: var(--primary-color);
            margin-top: 0;
        }
        
        .detail-card p {
            margin: 5px 0;
        }
        
        .no-room-message {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .success {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .room-visual {
            margin-top: 30px;
            text-align: center;
        }
        
        .hostel-block {
            display: inline-block;
            width: 200px;
            height: 300px;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin: 0 15px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .block-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .floor {
            height: 33.33%;
            border-bottom: 1px dashed #ccc;
            position: relative;
        }
        
        .floor:last-child {
            border-bottom: none;
        }
        
        .floor-label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            font-size: 12px;
            color: #666;
        }
        
        .user-room {
            position: absolute;
            width: 30px;
            height: 30px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(var(--secondary-color-rgb), 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(var(--secondary-color-rgb), 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(var(--secondary-color-rgb), 0);
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <ul class="background">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul>
    
    <!-- Butterfly Animation Container -->
    <div class="butterfly-container"></div>
    
    <div class="container">
        <header>
            <h1>Hello Romie</h1>
            <p>Your Dashboard</p>
        </header>
        
        <div class="card">
            <h2>Welcome, <?php echo $user_data['name']; ?>!</h2>
            
            <?php if($success_message): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-container">
                <?php if($room_data['has_room']): ?>
                    <div class="room-card">
                        <div class="room-number"><?php echo $room_data['room_code']; ?></div>
                        <h3>Your Room</h3>
                        <p><strong>Block:</strong> <?php echo $room_data['block']; ?></p>
                        <p><strong>Floor:</strong> <?php echo $room_data['floor']; ?></p>
                        <p><strong>Room Number:</strong> <?php echo str_pad($room_data['room_number'], 2, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>Roommate:</strong> <?php echo $room_data['roommate_name']; ?></p>
                    </div>
                    
                    <div class="room-visual">
                        <h3>Your Room Location</h3>
                        <div class="hostel-blocks">
                            <?php 
                            $blocks = ['A', 'B', 'C', 'D'];
                            foreach ($blocks as $block): 
                                $isUserBlock = ($block == $room_data['block']);
                            ?>
                            <div class="hostel-block" style="<?php echo $isUserBlock ? 'border: 2px solid var(--primary-color);' : ''; ?>">
                                <div class="block-label">Block <?php echo $block; ?></div>
                                <?php for ($i = 1; $i <= 3; $i++): 
                                    $isUserFloor = ($isUserBlock && $i == $room_data['floor']);
                                ?>
                                <div class="floor" style="<?php echo $isUserFloor ? 'background-color: rgba(var(--primary-color-rgb), 0.1);' : ''; ?>">
                                    <div class="floor-label">Floor <?php echo $i; ?></div>
                                    <?php if ($isUserFloor): ?>
                                    <div class="user-room" title="Your Room: <?php echo $room_data['room_code']; ?>"></div>
                                    <?php endif; ?>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="room-details">
                        <div class="detail-card">
                            <h4>Hostel Information</h4>
                            <p><strong>Warden Contact:</strong> +91 9876543210</p>
                            <p><strong>Hostel Office:</strong> Block A, Ground Floor</p>
                            <p><strong>Office Hours:</strong> 9:00 AM - 5:00 PM</p>
                        </div>
                        
                        <div class="detail-card">
                            <h4>Facilities</h4>
                            <p><strong>Wi-Fi:</strong> Available 24/7</p>
                            <p><strong>Laundry:</strong> Block B, Ground Floor</p>
                            <p><strong>Mess Timings:</strong> 7:30-9:30 AM, 12:30-2:30 PM, 7:30-9:30 PM</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-room-message">
                        <h3>No Room Allocated Yet</h3>
                        <p>You haven't been allocated a room yet. Go to the matches page to find a compatible roommate and request a room.</p>
                        <a href="match.php" class="btn" style="margin-top: 15px;">Find Roommate</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="match.php" class="btn">Back to Matches</a>
                <a href="index.php" class="btn">Home</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Romie. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
</body>
</html>