<?php
{{ /* Add error reporting for debugging - REMOVE THIS IN PRODUCTION */ }}
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Add security headers
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Check if match_id is provided and numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: match.php");
    exit();
}

// Database connection with error handling
try {
    include 'includes/db_connect.php';
} catch (Exception $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$user_id = (int)$_SESSION['user_id'];
$match_id = (int)$_GET['id'];

// Get user data with error handling
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: login.php");
    exit();
}
$user_data = $result->fetch_assoc();
{{ /* Add a check to ensure user data was fetched */ }}
if (!$user_data) {
    // Optionally log an error here
    die("Error: Could not fetch logged-in user data."); // Stop execution if user data is missing
}


// Get match data with error handling
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: match.php");
    exit();
}
$match_data = $result->fetch_assoc();
{{ /* Add a check to ensure match data was fetched */ }}
if (!$match_data) {
    // Optionally log an error here
    // Redirecting might be better, but die() helps debugging
    die("Error: Could not fetch match data for the specified ID.");
}


{{ /* Restore the function definition */ }}
// Function to calculate match breakdown
function get_match_breakdown($user1, $user2) {
    $breakdown = [
        'course' => ['score' => 0, 'max' => 1, 'match' => false],
        'year_semester' => ['score' => 0, 'max' => 1, 'match' => false],
        'hostel_block' => ['score' => 0, 'max' => 1, 'match' => false],
        'roommate_preference' => ['score' => 0, 'max' => 1, 'mutual' => false, 'one_way' => false],
        'hobbies' => ['score' => 0, 'max' => 1, 'common' => []],
        'games' => ['score' => 0, 'max' => 1, 'common' => []],
        'weekend_preference' => ['score' => 0, 'max' => 1, 'match' => false],
        'watching_preference' => ['score' => 0, 'max' => 1, 'match' => false],
        'music_preference' => ['score' => 0, 'max' => 1, 'match' => false],
        'music_genre' => ['score' => 0, 'max' => 1, 'match' => false],
        'food_habits' => ['score' => 0, 'max' => 1, 'match' => false],
        'sleep_schedule' => ['score' => 0, 'max' => 1, 'match' => false, 'flexible' => false],
        'cleaning_schedule' => ['score' => 0, 'max' => 1, 'match' => false, 'flexible' => false],
        'study_habits' => ['score' => 0, 'max' => 1, 'match' => false],
        'study_time' => ['score' => 0, 'max' => 1, 'match' => false, 'flexible' => false],
        'guest_preference' => ['score' => 0, 'max' => 1, 'match' => false, 'neutral' => false],
        'share_groceries' => ['score' => 0, 'max' => 1, 'match' => false],
        'personality' => ['score' => 0, 'max' => 1, 'match' => false, 'ambivert' => false],
        'resolve_issues' => ['score' => 0, 'max' => 1, 'match' => false],
        'communication_preference' => ['score' => 0, 'max' => 1, 'match' => false],
        'weekend_outings' => ['score' => 0, 'max' => 1, 'match' => false],
        'phone_privacy' => ['score' => 0, 'max' => 1, 'match' => false],
        'technical_person' => ['score' => 0, 'max' => 1, 'match' => false],
        'overall_percentage' => 0
    ];

    // Course Match
    if (isset($user1['course']) && isset($user2['course']) && $user1['course'] === $user2['course']) {
        $breakdown['course']['score'] = 1;
        $breakdown['course']['match'] = true;
    }

    // Year/Semester Match
    if (isset($user1['year_semester']) && isset($user2['year_semester']) && $user1['year_semester'] === $user2['year_semester']) {
        $breakdown['year_semester']['score'] = 1;
        $breakdown['year_semester']['match'] = true;
    }

    // Hostel Block Match
    if (isset($user1['hostel_block']) && isset($user2['hostel_block']) && $user1['hostel_block'] === $user2['hostel_block']) {
        $breakdown['hostel_block']['score'] = 1;
        $breakdown['hostel_block']['match'] = true;
    }

    // Roommate Preference Match
    if (isset($user1['roommate_preference']) && isset($user2['roommate_preference'])) {
        if ($user1['roommate_preference'] === $user2['id'] && $user2['roommate_preference'] === $user1['id']) {
            $breakdown['roommate_preference']['score'] = 1;
            $breakdown['roommate_preference']['mutual'] = true;
        } elseif ($user1['roommate_preference'] === $user2['id'] || $user2['roommate_preference'] === $user1['id']) {
            $breakdown['roommate_preference']['score'] = 0.5;
            $breakdown['roommate_preference']['one_way'] = true;
        }
    }

    // Hobbies Match
    if (!empty($user1['hobbies']) && !empty($user2['hobbies'])) {
        $hobbies1 = explode(',', $user1['hobbies']);
        $hobbies2 = explode(',', $user2['hobbies']);
        $common_hobbies = array_intersect($hobbies1, $hobbies2);
        $breakdown['hobbies']['common'] = $common_hobbies;
        $breakdown['hobbies']['score'] = count($common_hobbies) > 0 ? 1 : 0;
    }

    // Games Match
    if (!empty($user1['games']) && !empty($user2['games'])) {
        $games1 = explode(',', $user1['games']);
        $games2 = explode(',', $user2['games']);
        $common_games = array_intersect($games1, $games2);
        $breakdown['games']['common'] = $common_games;
        $breakdown['games']['score'] = count($common_games) > 0 ? 1 : 0;
    }

    // Weekend Preference Match
    if (isset($user1['weekend_preference']) && isset($user2['weekend_preference']) && $user1['weekend_preference'] === $user2['weekend_preference']) {
        $breakdown['weekend_preference']['score'] = 1;
        $breakdown['weekend_preference']['match'] = true;
    }

    // Watching Preference Match
    if (isset($user1['watching_preference']) && isset($user2['watching_preference']) && $user1['watching_preference'] === $user2['watching_preference']) {
        $breakdown['watching_preference']['score'] = 1;
        $breakdown['watching_preference']['match'] = true;
    }

    // Music Preference Match
    if (isset($user1['music_preference']) && isset($user2['music_preference']) && $user1['music_preference'] === $user2['music_preference']) {
        $breakdown['music_preference']['score'] = 1;
        $breakdown['music_preference']['match'] = true;
    }

    // Music Genre Match
    if (isset($user1['music_genre']) && isset($user2['music_genre']) && $user1['music_genre'] === $user2['music_genre']) {
        $breakdown['music_genre']['score'] = 1;
        $breakdown['music_genre']['match'] = true;
    }

    // Food Habits Match
    if (isset($user1['food_habits']) && isset($user2['food_habits']) && $user1['food_habits'] === $user2['food_habits']) {
        $breakdown['food_habits']['score'] = 1;
        $breakdown['food_habits']['match'] = true;
    }

    // Sleep Schedule Match
    if (isset($user1['sleep_schedule']) && isset($user2['sleep_schedule'])) {
        if ($user1['sleep_schedule'] === $user2['sleep_schedule']) {
            $breakdown['sleep_schedule']['score'] = 1;
            $breakdown['sleep_schedule']['match'] = true;
        } elseif ($user1['sleep_schedule'] === 'Flexible' || $user2['sleep_schedule'] === 'Flexible') {
            $breakdown['sleep_schedule']['score'] = 0.5;
            $breakdown['sleep_schedule']['flexible'] = true;
        }
    }

    // Cleaning Schedule Match
    if (isset($user1['cleaning_schedule']) && isset($user2['cleaning_schedule'])) {
        if ($user1['cleaning_schedule'] === $user2['cleaning_schedule']) {
            $breakdown['cleaning_schedule']['score'] = 1;
            $breakdown['cleaning_schedule']['match'] = true;
        } elseif ($user1['cleaning_schedule'] === 'Flexible' || $user2['cleaning_schedule'] === 'Flexible') {
            $breakdown['cleaning_schedule']['score'] = 0.5;
            $breakdown['cleaning_schedule']['flexible'] = true;
        }
    }

    // Study Habits Match
    if (isset($user1['study_habits']) && isset($user2['study_habits']) && $user1['study_habits'] === $user2['study_habits']) {
        $breakdown['study_habits']['score'] = 1;
        $breakdown['study_habits']['match'] = true;
    }

    // Study Time Match
    if (isset($user1['study_time']) && isset($user2['study_time'])) {
        if ($user1['study_time'] === $user2['study_time']) {
            $breakdown['study_time']['score'] = 1;
            $breakdown['study_time']['match'] = true;
        } elseif ($user1['study_time'] === 'Flexible' || $user2['study_time'] === 'Flexible') {
            $breakdown['study_time']['score'] = 0.5;
            $breakdown['study_time']['flexible'] = true;
        }
    }

    // Guest Preference Match
    if (isset($user1['guest_preference']) && isset($user2['guest_preference'])) {
        if ($user1['guest_preference'] === $user2['guest_preference']) {
            $breakdown['guest_preference']['score'] = 1;
            $breakdown['guest_preference']['match'] = true;
        } elseif ($user1['guest_preference'] === 'Neutral' || $user2['guest_preference'] === 'Neutral') {
            $breakdown['guest_preference']['score'] = 0.5;
            $breakdown['guest_preference']['neutral'] = true;
        }
    }

    // Share Groceries Match
    if (isset($user1['share_groceries']) && isset($user2['share_groceries']) && $user1['share_groceries'] === $user2['share_groceries']) {
        $breakdown['share_groceries']['score'] = 1;
        $breakdown['share_groceries']['match'] = true;
    }

    // Personality Match
    if (isset($user1['personality']) && isset($user2['personality'])) {
        if ($user1['personality'] === $user2['personality']) {
            $breakdown['personality']['score'] = 1;
            $breakdown['personality']['match'] = true;
        } elseif ($user1['personality'] === 'Ambivert' || $user2['personality'] === 'Ambivert') {
            $breakdown['personality']['score'] = 0.5;
            $breakdown['personality']['ambivert'] = true;
        }
    }

    // Resolve Issues Match
    if (isset($user1['resolve_issues']) && isset($user2['resolve_issues']) && $user1['resolve_issues'] === $user2['resolve_issues']) {
        $breakdown['resolve_issues']['score'] = 1;
        $breakdown['resolve_issues']['match'] = true;
    }

    // Communication Preference Match
    if (isset($user1['communication_preference']) && isset($user2['communication_preference']) && $user1['communication_preference'] === $user2['communication_preference']) {
        $breakdown['communication_preference']['score'] = 1;
        $breakdown['communication_preference']['match'] = true;
    }

    // Weekend Outings Match
    if (isset($user1['weekend_outings']) && isset($user2['weekend_outings']) && $user1['weekend_outings'] === $user2['weekend_outings']) {
        $breakdown['weekend_outings']['score'] = 1;
        $breakdown['weekend_outings']['match'] = true;
    }

    // Phone Privacy Match
    if (isset($user1['phone_privacy']) && isset($user2['phone_privacy']) && $user1['phone_privacy'] === $user2['phone_privacy']) {
        $breakdown['phone_privacy']['score'] = 1;
        $breakdown['phone_privacy']['match'] = true;
    }

    // Technical Person Match
    if (isset($user1['technical_person']) && isset($user2['technical_person']) && $user1['technical_person'] === $user2['technical_person']) {
        $breakdown['technical_person']['score'] = 1;
        $breakdown['technical_person']['match'] = true;
    }

    // Calculate Overall Percentage
    $total_score = 0;
    $total_max = 0;
    foreach ($breakdown as $key => $category) {
        // Ensure we only process valid category arrays
        if (is_array($category) && isset($category['score']) && isset($category['max'])) {
            // Check if score and max are numeric before adding
            if (is_numeric($category['score']) && is_numeric($category['max'])) {
                $total_score += $category['score'];
                $total_max += $category['max'];
            }
        }
    }
    // Assign overall percentage only if $total_max is greater than 0
    if ($total_max > 0) {
        $breakdown['overall_percentage'] = round(($total_score / $total_max) * 100, 2);
    } else {
        $breakdown['overall_percentage'] = 0; // Default to 0 if no categories contribute
    }


    return $breakdown;
}
{{ /* End of restored function definition */ }}

{{ /* Restore the function call and variable definitions */ }}
// Get match breakdown
$breakdown = get_match_breakdown($user_data, $match_data);
$match_score = $breakdown['overall_percentage'] ?? 0; // Use null coalescing for safety

// Define category groups for display
$category_groups = [
    'Personal Compatibility' => ['course', 'year_semester', 'hostel_block', /*'room_number',*/ 'roommate_preference'], // room_number might not be a direct comparison factor
    'Interests & Hobbies' => ['hobbies', 'games', 'weekend_preference', 'watching_preference', 'music_preference', 'music_genre', 'food_habits'],
    'Living Habits' => ['sleep_schedule', 'cleaning_schedule', 'study_habits', 'study_time', 'guest_preference', 'share_groceries'],
    'Social & Communication' => ['personality', 'resolve_issues', 'communication_preference', 'weekend_outings', 'phone_privacy', 'technical_person']
];

// Category friendly names
$category_names = [
    'hobbies' => 'Hobbies',
    'games' => 'Games',
    'weekend_preference' => 'Weekend Activities',
    'food_habits' => 'Food Habits',
    'watching_preference' => 'Entertainment Preference',
    'music_preference' => 'Music Interest',
    'music_genre' => 'Music Genre',
    'sleep_schedule' => 'Sleep Schedule',
    'cleaning_schedule' => 'Cleaning Habits',
    'study_habits' => 'Study Environment',
    'study_time' => 'Study Time',
    'guest_preference' => 'Guest Preference',
    'share_groceries' => 'Sharing Groceries',
    'personality' => 'Personality Type',
    'resolve_issues' => 'Conflict Resolution',
    'communication_preference' => 'Communication Style',
    'weekend_outings' => 'Weekend Outings',
    'phone_privacy' => 'Phone Privacy',
    'technical_person' => 'Technical Interest',
    'hostel_block' => 'Hostel Block',
    'course' => 'Course',
    'year_semester' => 'Year/Semester',
    'roommate_preference' => 'Roommate Preference'
    // 'room_number' => 'Room Number' // Add if needed
];
{{ /* Remove the dummy variable assignments */ }}

?>
<!DOCTYPE html
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        :root {
            --primary-color: #4e54c8; /* Define primary color */
            --secondary-color: #8f94fb; /* Define secondary color */
        }
        {{ /* Add body background styles */ }}
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('images/canvas_3.jpg'); /* Path to your background image */
            background-size: cover; /* Cover the entire page */
            background-position: center; /* Center the image */
            background-repeat: no-repeat; /* Do not repeat the image */
            background-attachment: fixed; /* Keep the background fixed during scroll */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        /* Match Percentage Styles */
        .match-percentage-container {
            text-align: center;
            margin: 30px 0;
        }
        /* Circle Progress */
        .circle-progress {
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        .circular-chart {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }
        .circle-bg {
            fill: none;
            stroke: #eee;
            stroke-width: 3.8;
        }
        .circle {
            fill: none;
            stroke-width: 3.8;
            stroke-linecap: round;
            stroke: var(--primary-color);
            animation: progress 1.5s ease-out forwards;
        }
        .percentage {
            fill: var(--primary-color);
            font-size: 0.5em;
            text-anchor: middle;
            font-weight: bold;
            animation: fadeIn 1s;
        }
        /* Bar Progress */
        .bar-progress {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        .bar-container {
            height: 30px;
            background-color: #eee;
            border-radius: 15px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            border-radius: 15px;
            transition: width 1.5s ease-out;
        }
        .bar-percentage {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
        }
        /* Counter Progress */
        .counter-progress {
            font-size: 3em;
            font-weight: bold;
            color: var(--primary-color);
        }
        /* Match Breakdown */
        .breakdown-container {
            margin-top: 40px;
        }
        .category-group {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .category-group h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .breakdown-item {
            margin-bottom: 20px;
        }
        .breakdown-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .breakdown-title h4 {
            margin: 0;
        }
        .breakdown-title span {
            font-weight: bold;
        }
        .breakdown-bar {
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
        }
        .breakdown-fill {
            height: 100%;
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            border-radius: 5px;
            width: 0;
            transition: width 1s ease-out;
        }
        .common-items {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .common-item {
            background-color: rgba(78, 84, 200, 0.1);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-size: 0.9em;
            animation: fadeIn 0.5s;
        }
        .profile-comparison {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }
        .profile-card {
            flex: 1;
            min-width: 300px;
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .profile-card h3 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 15px;
            text-align: center;
        }
        .profile-item {
            margin-bottom: 10px;
        }
        .profile-item strong {
            display: inline-block;
            min-width: 150px;
        }
        @keyframes progress {
            0% {
                stroke-dasharray: 0 100;
            }
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        /* Hover Animations */
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(78, 84, 200, 0.2);
            transition: all 0.3s ease;
        }

        {{ /* Add styles for the back button */ }}
        .back-button {
            display: inline-block;
            margin-top: 30px; /* Add space above the button */
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 25px; /* Rounded corners */
            font-weight: 500;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .back-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px); /* Slight lift on hover */
            box-shadow: 0 6px 15px rgba(78, 84, 200, 0.3);
        }
        {{ /* Center the button container if needed */ }}
        .button-container {
             text-align: center; /* Center the button */
             width: 100%; /* Ensure container takes full width */
        }

    </style>
</head>
<body>
    <!-- Background Animation -->
    
    <!-- <ul class="background">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul> -->
    <!-- Butterfly Animation Container -->
    
    <!-- <div class="butterfly-container"></div> -->

    <div class="container">
        <header>
            <h1>Hello Romie</h1>
            <p>Match Details</p>
        </header>
        
        <div class="card" style="display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; z-index: 1000 !important; border: 5px solid blue !important;">
            <h2>Match Compatibility with <?php echo htmlspecialchars($match_data['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="match-percentage-container">
                <!-- Circle Progress Animation -->
                <div class="circle-progress">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo max(0, min(100, $match_score)); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <text x="18" y="20.35" class="percentage"><?php echo number_format($match_score, 2); ?>%</text>
                    </svg>
                </div>
            </div>
            <div class="profile-comparison">
                <div class="profile-card">
                    <h3>Your Profile</h3>
                    <div class="profile-item"><strong>Name:</strong> <?php echo htmlspecialchars($user_data['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-item"><strong>Course:</strong> <?php echo isset($user_data['course']) ? htmlspecialchars($user_data['course'], ENT_QUOTES, 'UTF-8') : 'Not specified'; ?></div>
                    <div class="profile-item"><strong>Year/Semester:</strong> <?php echo htmlspecialchars($user_data['year_semester'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-item"><strong>Hostel Block:</strong> <?php echo htmlspecialchars($user_data['hostel_block'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-item"><strong>Room Number:</strong> <?php echo isset($user_data['room_number']) ? htmlspecialchars($user_data['room_number'], ENT_QUOTES, 'UTF-8') : 'Not specified'; ?></div>
                </div>
                <div class="profile-card">
                    <h3><?php echo htmlspecialchars($match_data['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>'s Profile</h3>
                    <div class="profile-item"><strong>Name:</strong> <?php echo htmlspecialchars($match_data['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-item"><strong>Course:</strong> <?php echo isset($match_data['course']) ? htmlspecialchars($match_data['course'], ENT_QUOTES, 'UTF-8') : 'Not specified'; ?></div>
                    <div class="profile-item"><strong>Year/Semester:</strong> <?php echo htmlspecialchars($match_data['year_semester'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-item"><strong>Hostel Block:</strong> <?php echo htmlspecialchars($match_data['hostel_block'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-item"><strong>Room Number:</strong> <?php echo isset($match_data['room_number']) ? htmlspecialchars($match_data['room_number'], ENT_QUOTES, 'UTF-8') : 'Not specified'; ?></div>
                </div>
            </div>
            <div class="breakdown-container">
                <h3>Compatibility Breakdown</h3>
                <?php foreach ($category_groups as $group_name => $categories): ?>
                <div class="category-group">
                    <h3><?php echo htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php foreach ($categories as $category): ?>
                    <?php if (!isset($breakdown[$category])) continue; ?>
                    <div class="breakdown-item">
                        <div class="breakdown-title">
                            <h4><?php echo htmlspecialchars($category_names[$category] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></h4>
                            <span><?php echo isset($breakdown[$category]['score']) ? htmlspecialchars(number_format($breakdown[$category]['score'], 2), ENT_QUOTES, 'UTF-8') : '0.00'; ?>/<?php echo isset($breakdown[$category]['max']) ? htmlspecialchars($breakdown[$category]['max'], ENT_QUOTES, 'UTF-8') : '0'; ?></span>
                        </div>
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" style="width: <?php echo isset($breakdown[$category]['score']) && isset($breakdown[$category]['max']) && $breakdown[$category]['max'] > 0 ? max(0, min(100, ($breakdown[$category]['score'] / $breakdown[$category]['max']) * 100)) : 0; ?>%;"></div>
                        </div>
                        <?php if ($category == 'hobbies' && !empty($breakdown[$category]['common'] ?? [])): ?>
                        <div class="common-items">
                            <?php foreach ($breakdown[$category]['common'] as $hobby): ?>
                            <div class="common-item"><?php echo htmlspecialchars($hobby, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'games' && !empty($breakdown[$category]['common'] ?? [])): ?>
                        <div class="common-items">
                            <?php foreach ($breakdown[$category]['common'] as $game): ?>
                            <div class="common-item"><?php echo htmlspecialchars($game, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'weekend_preference' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both prefer: <?php echo htmlspecialchars($user_data['weekend_preference'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'watching_preference' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both prefer: <?php echo htmlspecialchars($user_data['watching_preference'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'music_preference' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo ($user_data['music_preference'] ?? 'No') == 'Yes' ? 'enjoy' : 'don\'t prefer'; ?> music</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'music_genre' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both enjoy: <?php echo htmlspecialchars($user_data['music_genre'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?> music</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'sleep_schedule' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both are: <?php echo htmlspecialchars($user_data['sleep_schedule'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php elseif ($category == 'sleep_schedule' && ($breakdown[$category]['flexible'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">One has flexible schedule</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'cleaning_schedule' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both clean: <?php echo htmlspecialchars($user_data['cleaning_schedule'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php elseif ($category == 'cleaning_schedule' && ($breakdown[$category]['flexible'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">One has flexible cleaning schedule</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'study_habits' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both prefer: <?php echo htmlspecialchars($user_data['study_habits'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'study_time' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both study: <?php echo htmlspecialchars($user_data['study_time'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php elseif ($category == 'study_time' && ($breakdown[$category]['flexible'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">One has flexible study time</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'guest_preference' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo strtolower(htmlspecialchars($user_data['guest_preference'] ?? 'Unknown', ENT_QUOTES, 'UTF-8')); ?> having guests over</div>
                        </div>
                        <?php elseif ($category == 'guest_preference' && ($breakdown[$category]['neutral'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">One is neutral about guests</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'share_groceries' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo ($user_data['share_groceries'] ?? 'No') == 'Yes' ? 'willing to' : 'prefer not to'; ?> share groceries</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'personality' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both are: <?php echo htmlspecialchars($user_data['personality'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php elseif ($category == 'personality' && ($breakdown[$category]['ambivert'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">One is Ambivert (compatible with both)</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'resolve_issues' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo ($user_data['resolve_issues'] ?? 'No') == 'Yes' ? 'believe in resolving issues' : 'prefer to avoid confrontation'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'communication_preference' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both prefer: <?php echo htmlspecialchars($user_data['communication_preference'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'weekend_outings' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo ($user_data['weekend_outings'] ?? 'No') == 'Yes' ? 'enjoy' : 'don\'t prefer'; ?> weekend outings</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'phone_privacy' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo ($user_data['phone_privacy'] ?? 'No') == 'Yes' ? 'are okay with' : 'prefer not'; ?> going out for calls</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'technical_person' && ($breakdown[$category]['match'] ?? false)): ?>
                        <div class="common-items">
                            <div class="common-item">Both <?php echo ($user_data['technical_person'] ?? 'No') == 'Yes' ? 'are' : 'are not'; ?> technical people</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($category == 'hostel_block' && ($breakdown[$category]['match']?? false)):?>
                        <div class="common-items">
                            <div class="common-item">Both prefer: <?php echo htmlspecialchars($user_data['hostel_block']?? 'Unknown', ENT_QUOTES, 'UTF-8');?> block</div>
                        </div>
                        <?php endif;?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                </div>
            </div>
            
            </div>
        </div>
        
    
        <div class="button-container">
             <a href="match.php" class="back-button">Go Back to Matches</a>
        </div>
    </div>

    <script>
        // Enhanced random hover effects for breakdown items
        document.querySelectorAll('.breakdown-item').forEach(item => {
            // Store original width for pulse animation
            const fill = item.querySelector('.breakdown-fill');
            if (fill) {
                fill.style.setProperty('--original-width', fill.style.width);
            }

            item.addEventListener('mouseenter', () => {
                const effects = [
                    'glow', 'float', 'pulse', 'rainbow', 
                    'bounce', 'shake', 'wiggle', 'spin'
                ];
                const randomEffect = effects[Math.floor(Math.random() * effects.length)];
                
                item.classList.add(`hover-${randomEffect}`);
                
                setTimeout(() => {
                    item.classList.remove(`hover-${randomEffect}`);
                }, 1000);
            });
        });

        // Add new animation definitions
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                25% { transform: rotate(2deg); }
                75% { transform: rotate(-2deg); }
                100% { transform: rotate(0deg); }
            }
            .hover-glow { animation: glow 1s; }
            .hover-float { animation: float 1s; }
            .hover-spin { animation: spin 0.5s; }
        `;
        document.head.appendChild(style);

        // Animate breakdown bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const fills = document.querySelectorAll('.breakdown-fill');
            fills.forEach(fill => {
                const width = fill.style.width;
                fill.style.width = '0';
                setTimeout(() => {
                    fill.style.width = width;
                }, 100);
            });
        });
    </script>
    