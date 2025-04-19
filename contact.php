<?php
session_start();
include 'includes/db_connect.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if match_id is provided
if (!isset($_GET['id'])) {
    header("Location: match.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$match_id = $_GET['id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get match data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: match.php");
    exit();
}

$match_data = $result->fetch_assoc();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = sanitize_input($_POST['message']);
    
    // Create a contact request
    $stmt = $conn->prepare("INSERT INTO contact_requests (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $user_id, $match_id, $message);
    
    if ($stmt->execute()) {
        $success_message = "Your message has been sent to " . $match_data['name'] . ". They will contact you if they're interested!";
    } else {
        $error_message = "Error sending message. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Potential Roommate - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        .contact-form {
            margin-top: 20px;
        }
        
        .contact-info {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .contact-info h3 {
            color: var(--primary-color);
            margin-top: 0;
        }
        
        .contact-info p {
            margin-bottom: 10px;
        }
        
        .contact-info strong {
            display: inline-block;
            min-width: 120px;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 150px;
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
            <p>Contact Potential Roommate</p>
        </header>
        
        <div class="card">
            <h2>Contact <?php echo $match_data['name']; ?></h2>
            
            <?php if($success_message): ?>
                <div class="success"><?php echo $success_message; ?></div>
                <div style="text-align: center;">
                    <a href="match.php" class="btn">Back to Matches</a>
                </div>
            <?php else: ?>
            
            <?php if($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="contact-info">
                <h3>About <?php echo $match_data['name']; ?></h3>
                <p><strong>Course:</strong> <?php echo $match_data['course']; ?></p>
                <p><strong>Year/Semester:</strong> <?php echo $match_data['year_semester']; ?></p>
                <p><strong>Hostel Block:</strong> <?php echo $match_data['hostel_block']; ?></p>
                <p><strong>Email:</strong> <?php echo $match_data['email']; ?></p>
                <p><strong>Contact:</strong> <?php echo $match_data['contact_number']; ?></p>
            </div>
            
            <div class="contact-form">
                <p>Send a message to <?php echo $match_data['name']; ?> to express your interest in becoming roommates:</p>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $match_id); ?>" method="post">
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" required placeholder="Hi <?php echo $match_data['name']; ?>, I saw we have a high compatibility score and I'm interested in discussing the possibility of becoming roommates. Let me know if you're interested!"></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" class="btn">Send Message</button>
                        <a href="match_details.php?id=<?php echo $match_id; ?>" class="btn" style="margin-left: 10px; background-color: #ccc; color: #333;">Back to Match Details</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Romie. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
</body>
</html>