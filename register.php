<?php
session_start();
include 'includes/db_connect.php';
include 'includes/functions.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Personal details
    $name = sanitize_input($_POST['name']);
    $age = sanitize_input($_POST['age']);
    $contact = sanitize_input($_POST['contact']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $sex = sanitize_input($_POST['sex']);
    $course = sanitize_input($_POST['course']);
    if($course === 'Other' && isset($_POST['other_course'])) {
        $course = sanitize_input($_POST['other_course']);
    }
    $year_semester = sanitize_input($_POST['year_semester']);
    
    // Hobbies and interests
    $hobbies = sanitize_input($_POST['hobbies']);
    $weekend_preference = sanitize_input($_POST['weekend_preference']);
    $food_habits = sanitize_input($_POST['food_habits']);
    $watching_preference = sanitize_input($_POST['watching_preference']);
    $music_preference = sanitize_input($_POST['music_preference']);
    $music_genre = isset($_POST['music_genre']) ? sanitize_input($_POST['music_genre']) : '';
    
    // Living habits
    $sleep_schedule = sanitize_input($_POST['sleep_schedule']);
    $cleaning_schedule = sanitize_input($_POST['cleaning_schedule']);
    $study_habits = sanitize_input($_POST['study_habits']);
    $study_time = sanitize_input($_POST['study_time']);
    $guest_preference = sanitize_input($_POST['guest_preference']);
    $share_groceries = sanitize_input($_POST['share_groceries']);
    
    // Lifestyle preferences
    $personality = sanitize_input($_POST['personality']);
    
    // Social habits
    $resolve_issues = sanitize_input($_POST['resolve_issues']);
    $communication_preference = sanitize_input($_POST['communication_preference']);
    $weekend_outings = sanitize_input($_POST['weekend_outings']);
    $phone_privacy = sanitize_input($_POST['phone_privacy']);
    $technical_person = sanitize_input($_POST['technical_person']);
    
    // Additional fields
    $hostel_block = sanitize_input($_POST['hostel_block']);
    $games = sanitize_input($_POST['games']);
    $preferred_roommate = sanitize_input($_POST['preferred_roommate']);
    
    // Validate password
    if ($password != $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email already exists. Please use a different email or login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user data
            $stmt = $conn->prepare("INSERT INTO students (name, age, contact_number, email, password, sex, course, year_semester, 
                hobbies, weekend_preference, food_habits, watching_preference, music_preference, music_genre, 
                sleep_schedule, cleaning_schedule, study_habits, study_time, guest_preference, share_groceries, 
                personality, resolve_issues, communication_preference, weekend_outings, phone_privacy, technical_person, 
                hostel_block, games, preferred_roommate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssssssssssssssssssssss", 
                $name, $age, $contact, $email, $hashed_password, $sex, $course, $year_semester,
                $hobbies, $weekend_preference, $food_habits, $watching_preference, $music_preference, $music_genre,
                $sleep_schedule, $cleaning_schedule, $study_habits, $study_time, $guest_preference, $share_groceries,
                $personality, $resolve_issues, $communication_preference, $weekend_outings, $phone_privacy, $technical_person,
                $hostel_block, $games, $preferred_roommate);
            
            if ($stmt->execute()) {
                // Redirect to login page after successful registration
                header("Location: login.php?registration=success"); // Ensure it redirects to login
                exit();
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        {{ /* Add background image styles to the body */ }}
        body {
            background-image: url('images/study_background.jpg'); /* Path to your new background image */
            background-color: #f4f4f4; /* Fallback color */
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
            z-index: 0;
            font-family: 'Poppins', sans-serif;
            padding-top: 20px; /* Add padding to prevent content touching edge */
            padding-bottom: 20px;
        }

        /* Ensure animation layers are behind content but above the background image */
        .background { /* The animated squares */
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            margin: 0;
            padding: 0;
            list-style: none;
            overflow: hidden;
            z-index: 1; /* Above body background */
            background-color: transparent !important;
        }

        .butterfly-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 2; /* Above square animation */
            overflow: hidden;
        }

        /* Ensure main content container is above animations */
        .container {
            position: relative; /* Needed for z-index */
            z-index: 3; /* Above animations */
            max-width: 800px; /* Max width for the form container */
            margin: 20px auto; /* Center container with margin */
            padding: 0 15px; /* Add horizontal padding */
        }

        /* Adjust card style for better visibility over the new background */
        .card {
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
            backdrop-filter: blur(5px); /* Optional blur */
            -webkit-backdrop-filter: blur(5px);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px; /* Space from header */
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease; /* Add transitions for hover effects */
        }
        {{ /* Add hover effect for the card */ }}
        .card:hover {
            background-color: rgba(255, 255, 255, 0.95); /* Slightly more opaque white on hover */
            transform: scale(1.01); /* Subtle scale up effect */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); /* Slightly enhance shadow */
        }

        /* Adjust header style if needed */
        header {
            text-align: center;
            margin-bottom: 20px;
            color: #333; /* Darker text for better contrast if background is light */
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7); /* Light shadow for definition */
        }
        header h1 {
             font-size: 2.5em;
             margin-bottom: 5px;
             color : rgba(63, 65, 166, 0.7);
        }
        header p {
            font-size: 1.1em;
            color: #555;
        }


        .form-section {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd; /* Lighter border */
            padding-bottom: 20px;
        }
        
        .form-section h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .radio-option input {
            margin-right: 5px;
        }
        
        .preference-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
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
            <p>Find your perfect roommate</p>
        </header>

        <div class="card">
            <h2>Register</h2>

            <?php if($error_message): ?>
                <div class="error" style="text-align: center; margin-bottom: 20px;"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if($success_message): ?>
                <div class="success" style="text-align: center; margin-bottom: 20px;"><?php echo $success_message; ?></div>
                <div style="text-align: center;">
                    <a href="login.php" class="btn">Login Now</a>
                </div>
            <?php else: ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Personal Details Section -->
                <div class="form-section">
                    <h3>Personal Details</h3>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="tel" id="contact" name="contact" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Sex</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="sex" value="Male" required> Male</label>
                            <label class="radio-option"><input type="radio" name="sex" value="Female"> Female</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Course</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="course" value="BDS" required> BDS</label>
                            <label class="radio-option"><input type="radio" name="course" value="MDS"> MDS</label>
                            <label class="radio-option"><input type="radio" name="course" value="Intern"> Intern</label>
                            <label class="radio-option"><input type="radio" name="course" value="Other" id="otherCourse"> Other</label>
                        </div>
                        <div id="otherCourseContainer" style="display: none; margin-top: 10px;">
                            <input type="text" name="other_course" id="otherCourseInput" placeholder="Enter your course name" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
                        </div>
                    </div>
                    <script>
                        document.getElementById('otherCourse').addEventListener('change', function() {
                            var container = document.getElementById('otherCourseContainer');
                            if(this.checked) {
                                container.style.display = 'block';
                            } else {
                                container.style.display = 'none';
                            }
                        });
                    </script>
                    
                    <div class="form-group">
                        <label class="preference-label">Year/Semester</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="year_semester" value="1stSem" required> 1st Sem</label>
                            <label class="radio-option"><input type="radio" name="year_semester" value="2ndSem"> 2nd Sem</label>
                            <label class="radio-option"><input type="radio" name="year_semester" value="3rdSem"> 3rd Sem</label>
                            <label class="radio-option"><input type="radio" name="year_semester" value="4thSem"> 4th Sem</label>
                            <label class="radio-option"><input type="radio" name="year_semester" value="Intern"> Intern</label>
                        </div>
                    </div>
                </div>
                
                <!-- Interests/Dis-interests Section -->
                <div class="form-section">
                    <h3>Interests/Dis-interests</h3>
                    
                    <div class="form-group">
                        <label for="hobbies">Hobbies</label>
                        <input type="text" id="hobbies" name="hobbies" placeholder="e.g., Reading, Gaming, Cooking">
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">How do you prefer to spend your weekends?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="weekend_preference" value="Outdoor" required> Outdoor</label>
                            <label class="radio-option"><input type="radio" name="weekend_preference" value="Indoor"> Indoor</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">What is your food habits?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="food_habits" value="Veg" required> Veg</label>
                            <label class="radio-option"><input type="radio" name="food_habits" value="Non-Veg"> Non-Veg</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">What do you prefer watching?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="watching_preference" value="Movies" required> Movies</label>
                            <label class="radio-option"><input type="radio" name="watching_preference" value="Series"> Series</label>
                            <label class="radio-option"><input type="radio" name="watching_preference" value="Both"> Both</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Do you like listening to music?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="music_preference" value="Yes" required> Yes</label>
                            <label class="radio-option"><input type="radio" name="music_preference" value="No"> No</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">What is your favorite music genre?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="music_genre" value="Retro"> Retro</label>
                            <label class="radio-option"><input type="radio" name="music_genre" value="Techno"> Techno</label>
                            <label class="radio-option"><input type="radio" name="music_genre" value="Bollywood"> Bollywood</label>
                            <label class="radio-option"><input type="radio" name="music_genre" value="Western Music"> Western Music</label>
                            <label class="radio-option"><input type="radio" name="music_genre" value="Regional"> Regional</label>
                        </div>
                    </div>
                </div>
                
                <!-- Living Habits Section -->
                <div class="form-section">
                    <h3>Living Habits</h3>
                    
                    <div class="form-group">
                        <label class="preference-label">What's your Sleep schedule?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="sleep_schedule" value="Night Owl" required> Night Owl</label>
                            <label class="radio-option"><input type="radio" name="sleep_schedule" value="Early Bird"> Early Bird</label>
                            <label class="radio-option"><input type="radio" name="sleep_schedule" value="Flexible"> Flexible</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Cleaning schedule?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="cleaning_schedule" value="Daily" required> Daily</label>
                            <label class="radio-option"><input type="radio" name="cleaning_schedule" value="Weekly"> Weekly</label>
                            <label class="radio-option"><input type="radio" name="cleaning_schedule" value="Flexible"> Flexible</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Studying Habits?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="study_habits" value="Quiet environment" required> Quiet environment</label>
                            <label class="radio-option"><input type="radio" name="study_habits" value="Group discussion"> Group discussion</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Study Time?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="study_time" value="Late Night" required> Late Night</label>
                            <label class="radio-option"><input type="radio" name="study_time" value="Early Morning"> Early Morning</label>
                            <label class="radio-option"><input type="radio" name="study_time" value="Flexible"> Flexible</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">How do you feel having a guest/friends over?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="guest_preference" value="Like" required> Like</label>
                            <label class="radio-option"><input type="radio" name="guest_preference" value="Neutral"> Neutral</label>
                            <label class="radio-option"><input type="radio" name="guest_preference" value="Dislike"> Dislike</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Do you prefer sharing groceries and food?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="share_groceries" value="Yes" required> Yes</label>
                            <label class="radio-option"><input type="radio" name="share_groceries" value="No"> No</label>
                        </div>
                    </div>
                </div>
                
                <!-- Lifestyle Preference Section -->
                <div class="form-section">
                    <h3>Lifestyle Preference</h3>
                    
                    <div class="form-group">
                        <label class="preference-label">Describe your personality as-</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="personality" value="Introvert" required> Introvert</label>
                            <label class="radio-option"><input type="radio" name="personality" value="Extrovert"> Extrovert</label>
                            <label class="radio-option"><input type="radio" name="personality" value="Ambivert"> Ambivert</label>
                        </div>
                    </div>
                </div>
                
                <!-- Social Habits Section -->
                <div class="form-section">
                    <h3>Social Habits</h3>
                    
                    <div class="form-group">
                        <label class="preference-label">Do you believe in resolving the issues?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="resolve_issues" value="Yes" required> Yes</label>
                            <label class="radio-option"><input type="radio" name="resolve_issues" value="No"> No</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">How do you prefer to communicate about issues?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="communication_preference" value="Verbally(inperson)" required> Verbally (in person)</label>
                            <label class="radio-option"><input type="radio" name="communication_preference" value="Texting"> Texting</label>
                            <label class="radio-option"><input type="radio" name="communication_preference" value="Without conveying"> Without conveying</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Do you like going out in weekend?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="weekend_outings" value="Yes" required> Yes</label>
                            <label class="radio-option"><input type="radio" name="weekend_outings" value="No"> No</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Will you be okay going out of room while talking on phone?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="phone_privacy" value="Yes" required> Yes</label>
                            <label class="radio-option"><input type="radio" name="phone_privacy" value="No"> No</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="preference-label">Are you technical person?</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="technical_person" value="Yes" required> Yes</label>
                            <label class="radio-option"><input type="radio" name="technical_person" value="No"> No</label>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information Section -->
                <div class="form-section">
                    <h3>Additional Information</h3>
                    
                    <div class="form-group">
                        <label for="hostel_block">Hostel Block</label>
                        <input type="text" id="hostel_block" name="hostel_block" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="games">Games You Play</label>
                        <input type="text" id="games" name="games" placeholder="e.g., Chess, PUBG, Valorant">
                    </div>
                    
                    <div class="form-group">
                        <label for="preferred_roommate">Preferred Roommate (Optional)</label>
                        <input type="text" id="preferred_roommate" name="preferred_roommate" placeholder="Enter name if you have someone specific in mind">
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn">Register</button>
                    <p style="margin-top: 15px;">Already have an account? <a href="login.php" style="color: var(--primary-color);">Login here</a></p>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Romie. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
</body>
</html>