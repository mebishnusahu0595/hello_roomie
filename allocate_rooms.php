<?php
session_start();
include 'includes/db_connect.php';
include 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle manual allocation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['allocate'])) {
    $student1_id = $_POST['student1_id'];
    $student2_id = $_POST['student2_id'];
    
    $result = allocate_room($conn, $student1_id, $student2_id);
    
    if ($result['success']) {
        $success_message = $result['message'] . " Room: " . $result['room'];
    } else {
        $error_message = $result['message'];
    }
}

// Handle auto allocation for all compatible pairs
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['auto_allocate'])) {
    // Get all students without room allocation
    $stmt = $conn->prepare("
        SELECT id FROM students 
        WHERE id NOT IN (
            SELECT student1_id FROM room_allocations
            UNION
            SELECT student2_id FROM room_allocations WHERE student2_id IS NOT NULL
        )
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $unallocated_students = [];
    while ($row = $result->fetch_assoc()) {
        $unallocated_students[] = $row['id'];
    }
    
    $allocated_count = 0;
    $errors = [];
    
    // Process students in pairs based on compatibility
    for ($i = 0; $i < count($unallocated_students); $i++) {
        // Skip if this student was allocated in a previous iteration
        $stmt = $conn->prepare("
            SELECT * FROM room_allocations 
            WHERE student1_id = ? OR student2_id = ?
        ");
        $stmt->bind_param("ii", $unallocated_students[$i], $unallocated_students[$i]);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            continue;
        }
        
        $best_match_id = null;
        $best_match_score = 0;
        
        // Find the most compatible student
        for ($j = $i + 1; $j < count($unallocated_students); $j++) {
            // Skip if this student was allocated in a previous iteration
            $stmt = $conn->prepare("
                SELECT * FROM room_allocations 
                WHERE student1_id = ? OR student2_id = ?
            ");
            $stmt->bind_param("ii", $unallocated_students[$j], $unallocated_students[$j]);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                continue;
            }
            
            // Get student data
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $unallocated_students[$i]);
            $stmt->execute();
            $student_a = $stmt->get_result()->fetch_assoc();
            
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $unallocated_students[$j]);
            $stmt->execute();
            $student_b = $stmt->get_result()->fetch_assoc();
            
            // Skip if different genders
            if ($student_a['sex'] !== $student_b['sex']) {
                continue;
            }
            
            // Calculate match score
            $score = calculate_match_score($student_a, $student_b);
            
            if ($score > $best_match_score) {
                $best_match_score = $score;
                $best_match_id = $unallocated_students[$j];
            }
        }
        
        // If found a compatible match with score > 60%, allocate room
        if ($best_match_id && $best_match_score >= 60) {
            $result = allocate_room($conn, $unallocated_students[$i], $best_match_id);
            
            if ($result['success']) {
                $allocated_count++;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    if ($allocated_count > 0) {
        $success_message = "Auto-allocation complete! Allocated $allocated_count room(s) successfully.";
    } else {
        $error_message = "No compatible pairs found or no rooms available.";
    }
    
    if (!empty($errors)) {
        $error_message .= " Errors: " . implode(", ", $errors);
    }
}

// Get all students for manual allocation
$stmt = $conn->prepare("SELECT id, name, sex FROM students ORDER BY name");
$stmt->execute();
$students_result = $stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

// Get all room allocations
$stmt = $conn->prepare("
    SELECT ra.*, s1.name as student1_name, s2.name as student2_name, s1.sex as gender
    FROM room_allocations ra
    JOIN students s1 ON ra.student1_id = s1.id
    LEFT JOIN students s2 ON ra.student2_id = s2.id
    ORDER BY ra.block_name, ra.floor_number, ra.room_number
");
$stmt->execute();
$allocations_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Allocation - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        .allocation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .room-card {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .room-card h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .room-card p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .block-section {
            margin-bottom: 30px;
        }
        
        .block-section h2 {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .allocation-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
            margin: 10px;
            min-width: 200px;
        }
        
        .stat-card h3 {
            margin: 0;
            color: white;
        }
        
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-right: 10px;
            width: 100%;
            max-width: 300px;
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
            <p>Room Allocation Management</p>
        </header>
        
        <div class="card">
            <h2>Room Allocation</h2>
            
            <?php if($success_message): ?>
                <div class="success" style="text-align: center; margin-bottom: 20px; padding: 10px; background-color: rgba(76, 175, 80, 0.1); color: #4CAF50; border-radius: 5px;"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
                <div class="error" style="text-align: center; margin-bottom: 20px; padding: 10px; background-color: rgba(244, 67, 54, 0.1); color: #F44336; border-radius: 5px;"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab active" data-tab="dashboard">Dashboard</div>
                <div class="tab" data-tab="allocate">Allocate Rooms</div>
                <div class="tab" data-tab="view">View Allocations</div>
            </div>
            
            <!-- Dashboard Tab -->
            <div class="tab-content active" id="dashboard">
                <?php
                // Count statistics
                $total_rooms = 4 * 3 * 15; // 4 blocks, 3 floors, 15 rooms per floor
                $allocated_rooms = $allocations_result->num_rows;
                $available_rooms = $total_rooms - $allocated_rooms;
                
                // Count by gender
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM room_allocations WHERE block_name IN ('A', 'B')");
                $stmt->execute();
                $female_rooms = $stmt->get_result()->fetch_assoc()['count'];
                
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM room_allocations WHERE block_name IN ('C', 'D')");
                $stmt->execute();
                $male_rooms = $stmt->get_result()->fetch_assoc()['count'];
                
                // Reset result pointer
                $allocations_result->data_seek(0);
                ?>
                
                <div class="allocation-stats">
                    <div class="stat-card">
                        <h3>Total Rooms</h3>
                        <p><?php echo $total_rooms; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Allocated</h3>
                        <p><?php echo $allocated_rooms; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Available</h3>
                        <p><?php echo $available_rooms; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Female Rooms</h3>
                        <p><?php echo $female_rooms; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Male Rooms</h3>
                        <p><?php echo $male_rooms; ?></p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="index.php" class="btn">Back to Home</a>
                </div>
            </div>
            
            <!-- Allocate Rooms Tab -->
            <div class="tab-content" id="allocate">
                <h3>Manual Allocation</h3>
                <form method="post" action="">
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 250px;">
                            <label for="student1_id">Student 1:</label>
                            <select name="student1_id" id="student1_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?> (<?php echo $student['sex']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <label for="student2_id">Student 2:</label>
                            <select name="student2_id" id="student2_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?> (<?php echo $student['sex']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <button type="submit" name="allocate" class="btn">Allocate Room</button>
                    </div>
                </form>
                
                <h3 style="margin-top: 30px;">Auto Allocation</h3>
                <p>This will automatically allocate rooms to compatible pairs of students who don't have rooms yet.</p>
                <form method="post" action="">
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="submit" name="auto_allocate" class="btn">Auto Allocate Rooms</button>
                    </div>
                </form>
            </div>
            
            <!-- View Allocations Tab -->
            <div class="tab-content" id="view">
                <?php
                $blocks = ['A', 'B', 'C', 'D'];
                foreach ($blocks as $block):
                    // Reset result pointer
                    $allocations_result->data_seek(0);
                    $block_allocations = [];
                    while ($row = $allocations_result->fetch_assoc()) {
                        if ($row['block_name'] == $block) {
                            $block_allocations[] = $row;
                        }
                    }
                    
                    if (empty($block_allocations)) {
                        continue;
                    }
                ?>
                <div class="block-section">
                    <h2>Block <?php echo $block; ?> (<?php echo ($block == 'A' || $block == 'B') ? 'Girls' : 'Boys'; ?>)</h2>
                    <div class="allocation-grid">
                        <?php foreach ($block_allocations as $allocation): ?>
                        <div class="room-card">
                            <h3>Room <?php echo $block . $allocation['floor_number'] . '-' . str_pad($allocation['room_number'], 2, '0', STR_PAD_LEFT); ?></h3>
                            <p><strong>Floor:</strong> <?php echo $allocation['floor_number']; ?></p>
                            <p><strong>Student 1:</strong> <?php echo $allocation['student1_name']; ?></p>
                            <p><strong>Student 2:</strong> <?php echo $allocation['student2_name'] ?? 'Not assigned'; ?></p>
                            <p><strong>Allocated:</strong> <?php echo date('M d, Y', strtotime($allocation['allocation_date'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hello Romie. All rights reserved.</p>
    </footer>
    
    <script src="js/animations.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Student selection validation
        const student1Select = document.getElementById('student1_id');
        const student2Select = document.getElementById('student2_id');
        
        student1Select.addEventListener('change', validateStudents);
        student2Select.addEventListener('change', validateStudents);
        
        function validateStudents() {
            const student1 = student1Select.options[student1Select.selectedIndex];
            const student2 = student2Select.options[student2Select.selectedIndex];
            
            if (student1.value && student2.value) {
                // Check if same student
                if (student1.value === student2.value) {
                    alert('Cannot select the same student twice');
                    student2Select.value = '';
                    return;
                }
                
                // Check if different genders
                const student1Gender = student1.text.includes('(Female)') ? 'Female' : 'Male';
                const student2Gender = student2.text.includes('(Female)') ? 'Female' : 'Male';
                
                if (student1Gender !== student2Gender) {
                    alert('Cannot allocate room for students of different genders');
                    student2Select.value = '';
                    return;
                }
            }
        }
    });
    </script>
</body>
</html>