<?php
session_start();
include 'includes/db_connect.php';
include 'includes/functions.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, password FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc(); // User data is fetched into $row

        // Verify password
        // Use $row instead of $user here (This is likely line 22)
        if (password_verify($password, $row['password'])) {
            // Password is correct, start session
            // Use $row instead of $user for session variables
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];

            // Redirect to the main application page (e.g., matches page)
            header("Location: match.php"); // Changed from potential index.php or main_app.php
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }
    } else {
        $error_message = "No account found with that email. Please register.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        body {
            background-image: url('images/study_background.jpg'); /* Path to your background image */
            background-color: #f4f4f4; /* Fallback color */
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
            z-index: 0;
            font-family: 'Poppins', sans-serif;
            padding-top: 40px; /* Adjust padding */
            padding-bottom: 40px;
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
            max-width: 500px; /* Keep login form relatively compact but not too narrow */
            margin: 40px auto; /* Center container with margin */
            padding: 0 15px; /* Add horizontal padding */
        }

        /* Adjust card style for better visibility */
        .card {
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
            backdrop-filter: blur(5px); /* Optional blur */
            -webkit-backdrop-filter: blur(5px);
            padding: 30px 40px; /* Adjust padding */
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px; /* Space from header */
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease; /* Transitions for hover effects */
        }
        /* Add hover effect for the card */
        .card:hover {
            background-color: rgba(255, 255, 255, 0.95); /* Slightly more opaque white on hover */
            transform: scale(1.01); /* Subtle scale up effect */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); /* Slightly enhance shadow */
        }

        /* Adjust header style */
        header {
            text-align: center;
            margin-bottom: 20px;
            color: #333; /* Darker text for better contrast */
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7); /* Light shadow */
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

        /* Style form elements if needed (inherited from style.css mostly) */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #444; /* Slightly darker label text */
        }
        .form-group input {
            width: 100%;
            padding: 12px; /* Slightly larger padding */
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box; /* Include padding in width */
        }
        .btn { /* Ensure button style is consistent */
            padding: 12px 30px;
            font-size: 1em;
        }

        /* Footer styling */
        footer {
            position: relative;
            z-index: 3;
            text-align: center;
            margin-top: 40px;
            padding: 15px 0;
            background-color: rgba(0, 0, 0, 0.6); /* Semi-transparent dark background */
            color: #ccc; /* Light text */
        }
    </style>
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
            <p>Login to your account</p>
        </header>

        <div class="card">
            <h2>Login</h2>

            <?php if($error_message): ?>
                <div class="error" style="text-align: center; margin-bottom: 20px;"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="btn">Login</button>
                    <p style="margin-top: 15px;">Don't have an account? <a href="register.php" style="color: var(--primary-color);">Register here</a></p>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Romie. All rights reserved.</p>
    </footer>

    <script src="js/animations.js"></script>
</body>
</html>