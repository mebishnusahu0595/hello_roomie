<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Hello Roomie - Find Your Perfect Roommate</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        /* Apply background image to the body */
        body {
            /* Update the background image URL */
            background-image: url('images/hostel_room.jpg'); /* Double-check this path/filename */
            background-color: #333; /* Add a fallback background color */
            background-size: cover; /* Cover the entire viewport */
            background-position: center center; /* Center the image */
            background-repeat: no-repeat;
            background-attachment: fixed; /* Keep background fixed during scroll */
            position: relative; /* Needed for z-index stacking */
            z-index: 0; /* Base layer */
            font-family: 'Poppins', sans-serif; /* Default body font */
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
            background-color: transparent !important; /* Explicitly set container background to transparent */
        }

        /* --- Override Animation Colors (No Blue) --- */
        .background li {
            /* Assuming the original animation uses background-color */
            /* Replace potential blue with other colors */
            /* You might need to adjust this based on how css/animations.css actually sets the color */
            animation-timing-function: linear; /* Keep timing */
        }

        /* Example: Override colors for specific list items if they are set individually */
        .background li:nth-child(1) { background-color: rgba(156, 39, 176, 0.2); } /* Purple */
        .background li:nth-child(2) { background-color: rgba(255, 152, 0, 0.2); } /* Orange */
        .background li:nth-child(3) { background-color: rgba(76, 175, 80, 0.2); }  /* Green */
        .background li:nth-child(4) { background-color: rgba(244, 67, 54, 0.2); }   /* Red */
        .background li:nth-child(5) { background-color: rgba(255, 235, 59, 0.2); } /* Yellow */
        .background li:nth-child(6) { background-color: rgba(121, 85, 72, 0.2); }   /* Brown */
        .background li:nth-child(7) { background-color: rgba(96, 125, 139, 0.2); } /* Blue Grey */
        .background li:nth-child(8) { background-color: rgba(233, 30, 99, 0.2); }   /* Pink */
        .background li:nth-child(9) { background-color: rgba(0, 150, 136, 0.2); }   /* Teal */
        .background li:nth-child(10) { background-color: rgba(103, 58, 183, 0.2); } /* Deep Purple */
        /* --- End Override --- */


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
            padding-top: 20px; /* Add some space from the top */
            padding-bottom: 20px; /* Add some space from the bottom */
            max-width: 1200px; /* Optional: constrain width */
            margin: 0 auto; /* Center container */
        }

        /* Glassmorphism base for cards/sections */
        .glass-effect {
            background: rgba(0, 0, 0, 0.4); /* Dark semi-transparent background */
            backdrop-filter: blur(10px); /* The blur effect */
            -webkit-backdrop-filter: blur(10px); /* Safari support */
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            color: #fff; /* Ensure text is white/light */
            padding: 30px; /* Padding inside the glass */
            margin-bottom: 30px; /* Space between sections */
            transition: box-shadow 0.3s ease, transform 0.3s ease; /* Add transform to transition for smooth lift */
        }
        /* Optional: Slight lift effect on card hover */
        .glass-effect:hover {
             box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
             transform: translateY(-5px); /* Add upward movement on hover */
        }


        /* Adjust hero section for better focus */
        .hero {
            text-align: center;
            /* Apply glass effect */
            background: rgba(0, 0, 0, 0.5); /* Slightly darker for hero */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            color: #fff;
            padding: 80px 20px; /* Increased padding */
            margin-bottom: 30px;
        }
        .hero h1 {
            font-size: 3.8em; /* Slightly larger title */
            color: #fff; /* White title */
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7); /* Text shadow for contrast */
            font-family: 'Montserrat', sans-serif; /* Heading font */
            font-weight: 700; /* Bold Montserrat */
        }
        .hero p.subtitle { /* Added a class for the main subtitle */
            font-size: 1.4em;
            color: #eee;
            margin-bottom: 40px; /* More space before button */
            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
        }
        .hero .btn.get-started { /* Style the main button */
            padding: 15px 35px;
            font-size: 1.2em;
            background-color: var(--secondary-color); /* Use a distinct color */
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hero .btn.get-started:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        /* Optional: Style for secondary links if needed */
        .secondary-links {
            margin-top: 20px;
            font-size: 0.9em;
        }
        .secondary-links a {
            color: #ccc;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .secondary-links a:hover {
            color: #fff;
        }

        /* Adjust other sections for readability over the background */
        /* Apply glass effect using the base class */
        .features, .how-it-works, .why-choose-us, .testimonials, .media-section {
             /* Inherit from .glass-effect */
        }

        /* Specific heading styles within glass sections */
        .features h2, .how-it-works h2, .why-choose-us h2, .testimonials h2, .media-section h2 {
            text-align: center;
            color: var(--primary-color); /* Keep brand color for headings */
            margin-bottom: 25px; /* Increased margin */
            font-size: 2.2em; /* Slightly larger headings */
            font-family: 'Montserrat', sans-serif; /* Heading font */
            font-weight: 700; /* Bold Montserrat */
        }
        .features p, .how-it-works p, .why-choose-us p, .testimonials p, .media-section p, .step-item p, .testimonial-item p, .feature-item p {
             color: #eee; /* Lighter text for better contrast on dark glass */
             line-height: 1.7; /* Improve readability */
             /* font-family: 'Poppins', sans-serif; /* Already default */
        }
        .step-item h3, .testimonial-item h4, .feature-item h3 {
            color: #fff; /* White headings for steps/items */
            margin-bottom: 8px;
            font-family: 'Montserrat', sans-serif; /* Sub-heading font */
            font-weight: 600; /* Semi-bold Montserrat */
        }
        .testimonial-item cite {
            display: block;
            margin-top: 10px;
            font-style: normal;
            color: #ccc;
            font-size: 0.9em;
        }

        /* Layout for multi-column sections */
        .grid-layout {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around; /* Adjust as needed */
            gap: 20px; /* Space between grid items */
        }
        .grid-item {
            flex-basis: calc(33.333% - 20px); /* Adjust for 3 columns, considering gap */
            min-width: 250px; /* Prevent items from becoming too small */
            text-align: center;
        }
        .grid-item .icon { /* Style for icons */
             font-size: 2.5em;
             color: var(--secondary-color);
             margin-bottom: 15px;
        }


        /* Footer adjustments */
        footer {
            position: relative; /* Ensure footer is also above animations */
            z-index: 3;
            color: #ccc;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 15px 0;
        }

    </style>
</head>
<body>
    <!-- Background Animation (Order matters for z-index) -->
    <ul class="background">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul>

    <!-- Butterfly Animation Container -->
    <div class="butterfly-container"></div>

    <!-- Main Content Container -->
    <div class="container">
        <header class="hero form-appear">
            <h1>Welcome to Hello Roomie</h1>
            <p class="subtitle">The smart way to find your ideal hostel roommate.</p>

            <a href="main_app.php" class="btn get-started">Get Started</a>

            <div class="secondary-links">
                <span>Already have an account? <a href="login.php">Login</a></span>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span> | <a href="main_app.php">Go to App</a></span>
                <?php endif; ?>
            </div>
        </header>

        <section class="features glass-effect card form-appear" style="animation-delay: 0.2s;">
            <h2>About Hello Roomie</h2>
            <p style="text-align: center; max-width: 800px; margin: 0 auto 20px auto;">
                Hello Roomie is an innovative platform designed to simplify the process of finding compatible roommates. By leveraging advanced algorithms and user preferences, the app matches individuals based on key compatibility factors, such as lifestyle choices, cleanliness habits, sleep patterns, and social activities.
            </p>
        </section>

      
        <section class="why-choose-us glass-effect card form-appear" style="animation-delay: 0.3s;">
            <h2>Why Choose Us?</h2>
            <div class="grid-layout">
                <div class="grid-item feature-item">
                    <div class="icon">🎯</div> 
                    <h3>Smart Matching</h3>
                    <p>Our algorithm focuses on deep compatibility factors beyond just interests.</p>
                </div>
                <div class="grid-item feature-item">
                     <div class="icon">🔒</div>
                    <h3>Safe & Secure</h3>
                    <p>We prioritize user safety with profile verification options (future feature).</p>
                </div>
                <div class="grid-item feature-item">
                     <div class="icon">🤝</div>
                    <h3>Community Focused</h3>
                    <p>Designed specifically for the hostel environment and student needs.</p>
                </div>
            </div>
        </section>

        <section class="how-it-works glass-effect card form-appear" style="animation-delay: 0.4s;">
            <h2>How Room Allocation Works</h2>
             <p style="text-align: center; max-width: 800px; margin: 0 auto 20px auto;">
                Final distribution of rooms is based on a percentage calculation of roommate compatibility. Students are matched based on their questionnaire answers, aiming for the highest compatibility. Those with similar compatibility scores may be grouped, ensuring a harmonious living environment.
            </p>
            <div class="grid-layout" style="margin-top: 20px;"> 
                <div class="grid-item step-item">
                     <div class="icon">📝</div>
                    <h3>1. Register & Profile</h3>
                    <p>Sign up and fill out your preferences and habits.</p>
                </div>
                <div class="grid-item step-item">
                     <div class="icon">⚙️</div>
                    <h3>2. Smart Matching</h3>
                    <p>Our algorithm calculates compatibility scores.</p>
                </div>
                <div class="grid-item step-item">
                     <div class="icon">🔑</div>
                    <h3>3. Room Allocation</h3>
                    <p>Compatible pairs are allocated rooms together.</p>
                </div>
            </div>
        </section>

       
        <section class="testimonials glass-effect card form-appear" style="animation-delay: 0.5s;">
            <h2>What Our Users Say</h2>
            <div class="grid-layout">
                <div class="grid-item testimonial-item">
                    <p>"Finding a roommate used to be stressful, but Hello Roomie made it so easy! Matched with someone who has the exact same study schedule."</p>
                    <cite>- None</cite>
                </div>
                <div class="grid-item testimonial-item">
                    <p>"I was worried about living with a stranger, but the compatibility score was spot on. My roommate and I get along great."</p>
                    <cite>- None</cite>
                </div>
                <div class="grid-item testimonial-item">
                    <p>"The focus on lifestyle habits really works. Finally, a roommate who's as tidy as I am!"</p>
                    <cite>- None</cite>
                </div>
            </div>
        </section>


    </div> <!-- End .container -->

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Roomie. All rights reserved.</p>
    </footer>

    <script src="js/animations.js"></script>
    <!-- Removed canvas script -->
    <!-- Removed music player script for simplicity, add back if needed -->

</body>
</html>