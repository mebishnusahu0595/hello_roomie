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

// Check if both student IDs are provided
if (!isset($_GET['student1']) || !isset($_GET['student2'])) {
    header("Location: match.php");
    exit();
}

$student1_id = $_GET['student1'];
$student2_id = $_GET['student2'];

// Verify that the logged-in user is one of the students
if ($user_id != $student1_id && $user_id != $student2_id) {
    header("Location: match.php");
    exit();
}

// Get student details to determine gender
$stmt = $conn->prepare("SELECT id, name, sex FROM students WHERE id IN (?, ?)");
$stmt->bind_param("ii", $student1_id, $student2_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows < 2) {
    header("Location: match.php?error=1&message=" . urlencode("One or both students not found."));
    exit();
}

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Check if both students are of the same gender
if ($students[0]['sex'] !== $students[1]['sex']) {
    header("Location: match.php?error=1&message=" . urlencode("Cannot allocate room for students of different genders."));
    exit();
}

// Determine which blocks to use based on gender
$gender = $students[0]['sex'];
$blocks = ($gender === 'Female') ? ['A', 'B'] : ['C', 'D'];

// Check if either student already has a room
$stmt = $conn->prepare("SELECT * FROM room_allocations WHERE student1_id = ? OR student2_id = ? OR student1_id = ? OR student2_id = ?");
$stmt->bind_param("iiii", $student1_id, $student1_id, $student2_id, $student2_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $allocation = $result->fetch_assoc();
    $room_code = $allocation['block_name'] . $allocation['floor_number'] . "-" . 
                str_pad($allocation['room_number'], 2, '0', STR_PAD_LEFT);
    header("Location: match.php?error=1&message=" . urlencode("One or both students already have a room allocation. Room: " . $room_code));
    exit();
}

// Get all allocated rooms
$allocated_rooms = [];
$stmt = $conn->prepare("SELECT block_name, floor_number, room_number FROM room_allocations");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $allocated_rooms[$row['block_name']][$row['floor_number']][$row['room_number']] = true;
}

// Initialize rooms if not already done
if (isset($_POST['initialize_rooms'])) {
    $result = initialize_rooms($conn);
    if ($result['success']) {
        echo '<p>Rooms initialized successfully.</p>';
    } else {
        echo '<p>Error initializing rooms: ' . $result['message'] . '</p>';
    }
}

// Handle form submission for room selection
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['allocate_room'])) {
    $block = $_POST['block'];
    $floor = $_POST['floor'];
    $room = $_POST['room'];
    
    // Verify block is valid for gender
    if (!in_array($block, $blocks)) {
        $error_message = "Invalid block selected for your gender.";
    } else {
        // Check if room is already allocated
        $stmt = $conn->prepare("SELECT * FROM room_allocations WHERE block_name = ? AND floor_number = ? AND room_number = ?");
        $stmt->bind_param("sii", $block, $floor, $room);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This room is already allocated. Please select another room.";
        } else {
            // Allocate the room
            $stmt = $conn->prepare("INSERT INTO room_allocations (block_name, floor_number, room_number, student1_id, student2_id, allocation_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("siiis", $block, $floor, $room, $student1_id, $student2_id);
            
            if ($stmt->execute()) {
                $room_code = $block . $floor . "-" . str_pad($room, 2, '0', STR_PAD_LEFT);
                $success_message = "Room allocated successfully! Your room is " . $room_code;
            } else {
                $error_message = "Error allocating room. Please try again.";
            }
        }
    }
}

// Get student names for display
$student1_name = $students[0]['id'] == $student1_id ? $students[0]['name'] : $students[1]['name'];
$student2_name = $students[0]['id'] == $student2_id ? $students[0]['name'] : $students[1]['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Selection - Hello Romie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/mind-map.css">
    <style>
        .room-selection-container {
            margin-top: 20px;
            position: relative;
        }
        
        /* Mind Map Visualization Styles */
        .mind-map-container {
            position: relative;
            width: 100%;
            height: 500px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .hostel-block {
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10;
        }
        
        .hostel-block:hover {
            transform: scale(1.1);
        }
        
        .hostel-block.male {
            background: linear-gradient(135deg, #4E54C8, #8F94FB);
        }
        
        .hostel-block.female {
            background: linear-gradient(135deg, #FF5F6D, #FFC371);
        }
        
        .hostel-block.active {
            transform: scale(1.2);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .hostel-block.inactive {
            opacity: 0.5;
            filter: grayscale(70%);
            pointer-events: none;
        }
        
        .connection-line {
            position: absolute;
            height: 3px;
            background-color: rgba(150, 150, 150, 0.5);
            transform-origin: left center;
            z-index: 5;
        }
        
        .floor-node {
            position: absolute;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            opacity: 0;
            transform: scale(0);
            z-index: 9;
        }
        
        .floor-node.visible {
            opacity: 1;
            transform: scale(1);
        }
        
        .floor-node:hover {
            transform: scale(1.1);
        }
        
        .floor-node.active {
            transform: scale(1.15);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .rooms-container {
            position: relative;
            margin-top: 20px;
            transition: all 0.5s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .rooms-container.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .room-card {
            position: relative;
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-card.selected {
            background-color: rgba(76, 175, 80, 0.2);
            border: 2px solid #4CAF50;
        }
        
        .room-card.allocated {
            background-color: rgba(244, 67, 54, 0.2);
            cursor: not-allowed;
        }
        
        .room-card.allocated::after {
            content: "Allocated";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            background-color: #F44336;
            color: white;
            padding: 5px 30px;
            font-size: 12px;
            font-weight: bold;
            width: 150px;
            text-align: center;
        }
        
        .room-number {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .room-info {
            margin-top: 5px;
            font-size: 14px;
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
        
        .room-selection-form {
            margin-top: 20px;
            text-align: center;
        }
        
        .room-selection-info {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .room-selection-info p {
            margin: 5px 0;
        }
        
        .auto-allocate-btn {
            background-color: var(--secondary-color);
            margin-left: 10px;
        }
        
        /* Mind Map Animation */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes drawLine {
            from { width: 0; }
            to { width: 100%; }
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.5s forwards;
        }
        
        .animate-draw-line {
            animation: drawLine 0.8s forwards;
        }
        
        /* Particle effects */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
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
            <p>Room Selection</p>
        </header>
        
        <div class="card">
            <h2>Select Your Room</h2>
            
            <?php if($success_message): ?>
                <div class="success"><?php echo $success_message; ?></div>
                <div style="text-align: center;">
                    <a href="match.php" class="btn">Back to Matches</a>
                </div>
            <?php else: ?>
            
            <?php if($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="room-selection-info">
                <p><strong>Students:</strong> <?php echo $student1_name; ?> and <?php echo $student2_name; ?></p>
                <p><strong>Gender:</strong> <?php echo $gender; ?></p>
                <p><strong>Available Blocks:</strong> <?php echo implode(", ", $blocks); ?></p>
            </div>
            
            <div class="room-selection-container">
                <div class="block-tabs">
                    <?php foreach ($blocks as $block): ?>
                        <div class="block-tab" data-block="<?php echo $block; ?>"><?php echo "Block " . $block; ?></div>
                    <?php endforeach; ?>
                </div>
                
                <div class="floor-tabs">
                    <div class="floor-tab" data-floor="1">Floor 1</div>
                    <div class="floor-tab" data-floor="2">Floor 2</div>
                    <div class="floor-tab" data-floor="3">Floor 3</div>
                </div>
                
                <div class="rooms-grid">
                    <?php for ($i = 1; $i <= 15; $i++): ?>
                        <div class="room-card" data-room="<?php echo $i; ?>">
                            <div class="room-number">Room <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></div>
                            <div class="room-info">Click to select</div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <form class="room-selection-form" method="post" action="">
                    <input type="hidden" id="block" name="block" value="">
                    <input type="hidden" id="floor" name="floor" value="">
                    <input type="hidden" id="room" name="room" value="">
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" name="allocate_room" class="btn" id="allocate-btn" disabled>Allocate Selected Room</button>
                        <a href="allocate_room.php?student1=<?php echo $student1_id; ?>&student2=<?php echo $student2_id; ?>" class="btn auto-allocate-btn">Auto Allocate Room</a>
                        <a href="match.php" class="btn" style="margin-left: 10px; background-color: #ccc; color: #333;">Cancel</a>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default active tabs
        const blockTabs = document.querySelectorAll('.block-tab');
        const floorTabs = document.querySelectorAll('.floor-tab');
        const roomCards = document.querySelectorAll('.room-card');
        const allocateBtn = document.getElementById('allocate-btn');
        const blockInput = document.getElementById('block');
        const floorInput = document.getElementById('floor');
        const roomInput = document.getElementById('room');
        
        let selectedBlock = null;
        let selectedFloor = null;
        let selectedRoom = null;
        
        // Initialize with first block and floor selected
        if (blockTabs.length > 0) {
            blockTabs[0].classList.add('active');
            selectedBlock = blockTabs[0].getAttribute('data-block');
            blockInput.value = selectedBlock;
        }
        
        if (floorTabs.length > 0) {
            floorTabs[0].classList.add('active');
            selectedFloor = floorTabs[0].getAttribute('data-floor');
            floorInput.value = selectedFloor;
        }
        
        // Update room status based on allocated rooms
        function updateRoomStatus() {
            if (!selectedBlock || !selectedFloor) return;
            
            // Reset all rooms
            roomCards.forEach(card => {
                card.classList.remove('allocated');
                card.classList.remove('selected');
            });
            
            // Mark allocated rooms
            <?php foreach ($blocks as $block): ?>
                <?php for ($floor = 1; $floor <= 3; $floor++): ?>
                    <?php for ($room = 1; $room <= 15; $room++): ?>
                        <?php if (isset($allocated_rooms[$block][$floor][$room])): ?>
                        if (selectedBlock === '<?php echo $block; ?>' && selectedFloor === '<?php echo $floor; ?>') {
                            const allocatedRoom = document.querySelector(`.room-card[data-room="<?php echo $room; ?>"]`);
                            if (allocatedRoom) {
                                allocatedRoom.classList.add('allocated');
                            }
                        }
                        <?php endif; ?>
                    <?php endfor; ?>
                <?php endfor; ?>
            <?php endforeach; ?>
            
            // Mark selected room if any
            if (selectedRoom) {
                const roomCard = document.querySelector(`.room-card[data-room="${selectedRoom}"]`);
                if (roomCard && !roomCard.classList.contains('allocated')) {
                    roomCard.classList.add('selected');
                }
            }
        }
        
        // Block tab click event
        blockTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                blockTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                selectedBlock = this.getAttribute('data-block');
                blockInput.value = selectedBlock;
                selectedRoom = null;
                roomInput.value = '';
                allocateBtn.disabled = true;
                updateRoomStatus();
            });
        });
        
        // Floor tab click event
        floorTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                floorTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                selectedFloor = this.getAttribute('data-floor');
                floorInput.value = selectedFloor;
                selectedRoom = null;
                roomInput.value = '';
                allocateBtn.disabled = true;
                updateRoomStatus();
            });
        });
        
        // Room card click event
        roomCards.forEach(card => {
            card.addEventListener('click', function() {
                if (this.classList.contains('allocated')) return;
                
                roomCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedRoom = this.getAttribute('data-room');
                roomInput.value = selectedRoom;
                allocateBtn.disabled = false;
            });
        });
        
        // Initialize particles and animations
        createParticles();
        
        // Automatically select the first block to start the selection process
        if (hostelBlocks.length > 0) {
            setTimeout(() => {
                hostelBlocks[0].click();
            }, 500);
        }
    });
    </script>');
                roomInput.value = selectedRoom;
                allocateBtn.disabled = false;
            });
        });
        
        // Initialize room status
        updateRoomStatus();
    });
    </script>
</body>
</html>