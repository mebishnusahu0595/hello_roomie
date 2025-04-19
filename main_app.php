<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello Roomie</title>
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
            <p>Find your perfect roommate based on shared interests and lifestyle</p>
        </header>
        
        <div class="card form-appear">
            <h2>Welcome to Hello Roomie</h2>
            <p>Our intelligent matching system helps you find the perfect hostel roommate based on your hobbies, life Style, study habits, and sleep schedule.</p>
            <p>Get started by registering your profile and preferences, and we'll help you find your ideal roommate!</p>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="register.php" class="btn">Register Now</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="match.php" class="btn">View Matches</a>
                    <a href="dashboard.php" class="btn">Dashboard</a> <!-- Example: Add Dashboard link -->
                    <a href="logout.php" class="btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login</a>
                <?php endif; ?>
                <a href="index.php" class="btn" style="background-color: var(--secondary-color);">About Us</a> <!-- Link back to new homepage -->
            </div>
        </div>
        
        <div class="card form-appear" style="animation-delay: 0.3s;">
            <h2>How It Works</h2>
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: 20px;">
                <div style="flex: 1; min-width: 250px; margin: 10px; text-align: center;">
                    <div style="font-size: 40px; color: var(--primary-color);">1</div>
                    <h3>Register</h3>
                    <p>Create your profile with your preferences, hobbies, and lifestyle details.</p>
                </div>
                <div style="flex: 1; min-width: 250px; margin: 10px; text-align: center;">
                    <div style="font-size: 40px; color: var(--primary-color);">2</div>
                    <h3>Match</h3>
                    <p>Our system analyzes your profile to find compatible roommates.</p>
                </div>
                <div style="flex: 1; min-width: 250px; margin: 10px; text-align: center;">
                    <div style="font-size: 40px; color: var(--primary-color);">3</div>
                    <h3>Connect</h3>
                    <p>View your matches and connect with potential roommates.</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hostel Roommate Matcher. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
    <audio id="background-music" src="audio/epic-background-music-322632.mp3" autoplay loop></audio>
    <div id="music-control" class="music-wave"></div>
    <script>
        const audio = document.getElementById('background-music');
        const musicControl = document.getElementById('music-control');
        let userInteracted = false;
    
        function playAudioOnInteraction() {
            if (!userInteracted) {
                audio.play();
                audio.volume = 0.5;
                userInteracted = true;
            }
        }
    
        musicControl.addEventListener('click', function() {
            playAudioOnInteraction();
            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        });
    
        // Also play on any user interaction with the page
        document.body.addEventListener('click', playAudioOnInteraction, { once: true });
    </script>
</body>
</html>