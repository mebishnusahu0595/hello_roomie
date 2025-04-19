<?php
// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Function to check if roommate exists
function check_roommate($conn, $roommate_name) {
    $stmt = $conn->prepare("SELECT id FROM students WHERE name = ?");
    $stmt->bind_param("s", $roommate_name);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Function to allocate room for compatible students
function allocate_room($conn, $student1_id, $student2_id) {
    $stmt = $conn->prepare("SELECT id, sex FROM students WHERE id IN (?, ?)");
    $stmt->bind_param("ii", $student1_id, $student2_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 2) {
        return ["success" => false, "message" => "One or both students not found."];
    }

    $students = $result->fetch_all(MYSQLI_ASSOC);
    if ($students[0]['sex'] !== $students[1]['sex']) {
        return ["success" => false, "message" => "Cannot allocate room for students of different genders."];
    }

    $gender = $students[0]['sex'];
    $blocks = ($gender === 'Female') ? ['A', 'B'] : ['C', 'D'];

    $stmt = $conn->prepare("SELECT * FROM room_allocations WHERE student1_id = ? OR student2_id = ?");
    $stmt->bind_param("ii", $student1_id, $student2_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ["success" => false, "message" => "One or both students already have a room allocation."];
    }

    foreach ($blocks as $block) {
        for ($floor = 1; $floor <= 3; $floor++) {
            for ($room = 1; $room <= 15; $room++) {
                $stmt = $conn->prepare("SELECT * FROM room_allocations WHERE block_name = ? AND floor_number = ? AND room_number = ?");
                $stmt->bind_param("sii", $block, $floor, $room);
                $stmt->execute();
                if ($stmt->get_result()->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO room_allocations (block_name, floor_number, room_number, student1_id, student2_id) VALUES (?, ?, ?, ?, NULL)");
                    $stmt->bind_param("siii", $block, $floor, $room, $student1_id);
                    if ($stmt->execute()) {
                        $stmt = $conn->prepare("UPDATE room_allocations SET student2_id = ? WHERE block_name = ? AND floor_number = ? AND room_number = ?");
                        $stmt->bind_param("isii", $student2_id, $block, $floor, $room);
                        $stmt->execute();
                        $room_code = $block . $floor . "-" . str_pad($room, 2, '0', STR_PAD_LEFT);
                        return [
                            "success" => true,
                            "message" => "Room allocated successfully!",
                            "room" => $room_code,
                            "block" => $block,
                            "floor" => $floor,
                            "room_number" => $room
                        ];
                    }
                }
            }
        }
    }

    return ["success" => false, "message" => "No empty rooms available for " . strtolower($gender) . "s at this time."];
}

// Function to get room allocation for a student
function get_student_room($conn, $student_id) {
    $stmt = $conn->prepare("SELECT * FROM room_allocations WHERE student1_id = ? OR student2_id = ?");
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $allocation = $result->fetch_assoc();
        $roommate_id = ($allocation['student1_id'] == $student_id) ? $allocation['student2_id'] : $allocation['student1_id'];
        $roommate_name = "No roommate yet";

        if ($roommate_id) {
            $stmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
            $stmt->bind_param("i", $roommate_id);
            $stmt->execute();
            $roommate_result = $stmt->get_result();
            if ($roommate_result->num_rows > 0) {
                $roommate_name = $roommate_result->fetch_assoc()['name'];
            }
        }

        $room_code = $allocation['block_name'] . $allocation['floor_number'] . "-" . str_pad($allocation['room_number'], 2, '0', STR_PAD_LEFT);
        return [
            "has_room" => true,
            "room_code" => $room_code,
            "block" => $allocation['block_name'],
            "floor" => $allocation['floor_number'],
            "room_number" => $allocation['room_number'],
            "roommate_id" => $roommate_id,
            "roommate_name" => $roommate_name
        ];
    }

    return ["has_room" => false];
}

// Function to calculate match score between two students
function calculate_match_score($student_a, $student_b) {
    if ($student_a['sex'] !== $student_b['sex']) return 0;

    $score = 0;
    $total_possible_score = 0;

    $compare_arrays = function($arr1, $arr2) {
        return count(array_intersect(array_map('trim', explode(',', $arr1)), array_map('trim', explode(',', $arr2))));
    };

    // Compare hobbies and games
    foreach (['hobbies', 'games'] as $key) {
        $common = $compare_arrays($student_a[$key], $student_b[$key]);
        $score += $common * 5;
        $total_possible_score += 20;
    }

    // Compare other preferences
    $preferences = [
        'weekend_preference', 'food_habits', 'watching_preference', 'music_preference',
        'study_habits', 'year_semester', 'share_groceries', 'resolve_issues',
        'communication_preference', 'weekend_outings', 'phone_privacy', 'technical_person'
    ];

    foreach ($preferences as $key) {
        $match_score = ($student_a[$key] === $student_b[$key]) ? 5 : 0;
        $score += $match_score;
        $total_possible_score += 5;
    }

    // Compare music genre if both like music
    if ($student_a['music_preference'] === 'Yes' && $student_b['music_preference'] === 'Yes' && $student_a['music_genre'] === $student_b['music_genre']) {
        $score += 5;
    }
    $total_possible_score += 5;

    // Compare sleep schedule
    if ($student_a['sleep_schedule'] === $student_b['sleep_schedule']) {
        $score += 10;
    } elseif ($student_a['sleep_schedule'] === 'Flexible' || $student_b['sleep_schedule'] === 'Flexible') {
        $score += 5;
    }
    $total_possible_score += 10;

    // Compare cleaning schedule
    if ($student_a['cleaning_schedule'] === $student_b['cleaning_schedule']) {
        $score += 5;
    } elseif ($student_a['cleaning_schedule'] === 'Flexible' || $student_b['cleaning_schedule'] === 'Flexible') {
        $score += 3;
    }
    $total_possible_score += 5;

    // Compare study time
    if ($student_a['study_time'] === $student_b['study_time']) {
        $score += 5;
    } elseif ($student_a['study_time'] === 'Flexible' || $student_b['study_time'] === 'Flexible') {
        $score += 3;
    }
    $total_possible_score += 5;

    // Compare guest preference
    if ($student_a['guest_preference'] === $student_b['guest_preference']) {
        $score += 5;
    } elseif ($student_a['guest_preference'] === 'Neutral' || $student_b['guest_preference'] === 'Neutral') {
        $score += 3;
    }
    $total_possible_score += 5;

    // Compare personality
    if ($student_a['personality'] === $student_b['personality']) {
        $score += 5;
    } elseif ($student_a['personality'] === 'Ambivert' || $student_b['personality'] === 'Ambivert') {
        $score += 3;
    }
    $total_possible_score += 5;

    // Compare hostel block
    if ($student_a['hostel_block'] === $student_b['hostel_block']) {
        $score += 10;
    }
    $total_possible_score += 10;

    // Compare preferred roommate
    if ($student_a['preferred_roommate'] === $student_b['name'] && $student_b['preferred_roommate'] === $student_a['name']) {
        $score += 20;
    } elseif ($student_a['preferred_roommate'] === $student_b['name'] || $student_b['preferred_roommate'] === $student_a['name']) {
        $score += 10;
    }
    $total_possible_score += 20;

    return round(($score / $total_possible_score) * 100);
}

// Function to get detailed breakdown of matching score
function get_match_breakdown($student_a, $student_b) {
    $breakdown = [];
    $total_score = 0;
    $total_possible_score = 0;

    $safe_get = function($array, $key, $default = '') {
        return $array[$key] ?? $default;
    };

    $compare_arrays = function($arr1, $arr2) {
        return array_intersect(array_map('trim', explode(',', $arr1)), array_map('trim', explode(',', $arr2)));
    };

    $evaluate = function($match, $max_score) use (&$total_score, &$total_possible_score, &$breakdown) {
        $score = $match ? $max_score : 0;
        $total_score += $score;
        $total_possible_score += $max_score;
        return ['score' => $score, 'max' => $max_score, 'percentage' => ($max_score > 0) ? round(($score / $max_score) * 100) : 0];
    };

    // Compare hobbies and games
    foreach (['hobbies', 'games'] as $key) {
        $common = $compare_arrays($safe_get($student_a, $key), $safe_get($student_b, $key));
        $breakdown[$key] = ['score' => count($common) * 5, 'max' => 20, 'percentage' => round((count($common) * 5 / 20) * 100), 'common' => implode(', ', $common)];
        $total_score += $breakdown[$key]['score'];
        $total_possible_score += 20;
    }

    // Compare other preferences
    $preferences = [
        'weekend_preference', 'food_habits', 'watching_preference', 'music_preference',
        'study_habits', 'year_semester', 'share_groceries', 'resolve_issues',
        'communication_preference', 'weekend_outings', 'phone_privacy', 'technical_person'
    ];

    foreach ($preferences as $key) {
        $breakdown[$key] = $evaluate($safe_get($student_a, $key) === $safe_get($student_b, $key), 5);
    }

    // Compare music genre if both like music
    $music_genre_score = ($student_a['music_preference'] === 'Yes' && $student_b['music_preference'] === 'Yes' && $student_a['music_genre'] === $student_b['music_genre']) ? 5 : 0;
    $breakdown['music_genre'] = $evaluate($music_genre_score > 0, 5);

    // Compare sleep schedule
    $sleep_score = ($student_a['sleep_schedule'] === $student_b['sleep_schedule']) ? 10 : (($student_a['sleep_schedule'] === 'Flexible' || $student_b['sleep_schedule'] === 'Flexible') ? 5 : 0);
    $breakdown['sleep_schedule'] = $evaluate($sleep_score > 0, 10);

    // Compare cleaning schedule
    $cleaning_score = ($student_a['cleaning_schedule'] === $student_b['cleaning_schedule']) ? 5 : (($student_a['cleaning_schedule'] === 'Flexible' || $student_b['cleaning_schedule'] === 'Flexible') ? 3 : 0);
    $breakdown['cleaning_schedule'] = $evaluate($cleaning_score > 0, 5);

    // Compare study time
    $study_time_score = ($student_a['study_time'] === $student_b['study_time']) ? 5 : (($student_a['study_time'] === 'Flexible' || $student_b['study_time'] === 'Flexible') ? 3 : 0);
    $breakdown['study_time'] = $evaluate($study_time_score > 0, 5);

    // Compare guest preference
    $guest_score = ($student_a['guest_preference'] === $student_b['guest_preference']) ? 5 : (($student_a['guest_preference'] === 'Neutral' || $student_b['guest_preference'] === 'Neutral') ? 3 : 0);
    $breakdown['guest_preference'] = $evaluate($guest_score > 0, 5);

    // Compare personality
    $personality_score = ($student_a['personality'] === $student_b['personality']) ? 5 : (($student_a['personality'] === 'Ambivert' || $student_b['personality'] === 'Ambivert') ? 3 : 0);
    $breakdown['personality'] = $evaluate($personality_score > 0, 5);

    // Compare hostel block
    $block_score = ($student_a['hostel_block'] === $student_b['hostel_block']) ? 10 : 0;
    $breakdown['hostel_block'] = $evaluate($block_score > 0, 10);

    // Compare preferred roommate
    $roommate_score = ($student_a['preferred_roommate'] === $student_b['name'] && $student_b['preferred_roommate'] === $student_a['name']) ? 20 : (($student_a['preferred_roommate'] === $student_b['name'] || $student_b['preferred_roommate'] === $student_a['name']) ? 10 : 0);
    $breakdown['preferred_roommate'] = $evaluate($roommate_score > 0, 20);

    $overall_percentage = ($total_possible_score > 0) ? round(($total_score / $total_possible_score) * 100) : 0;
    return ['breakdown' => $breakdown, 'total_score' => $total_score, 'total_possible_score' => $total_possible_score, 'overall_percentage' => $overall_percentage];
}

// Function to get top matches for a student
function get_top_matches($conn, $student_id, $limit = 1) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ["success" => false, "message" => "Student not found."];
    }

    $student = $result->fetch_assoc();
    $stmt = $conn->prepare("
        SELECT s.* FROM students s
        LEFT JOIN room_allocations r ON s.id = r.student1_id OR s.id = r.student2_id
        WHERE s.id != ? AND s.sex = ? AND r.id IS NULL
    ");
    $stmt->bind_param("is", $student_id, $student['sex']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ["success" => false, "message" => "No potential matches found."];
    }

    $matches = [];
    while ($potential_match = $result->fetch_assoc()) {
        $score = calculate_match_score($student, $potential_match);
        if ($score > 0) {
            $matches[] = ["student" => $potential_match, "score" => $score];
        }
    }

    usort($matches, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    return ["success" => true, "matches" => array_slice($matches, 0, $limit)];
}

// Function to allocate room for matched students
function allocate_room_for_match($conn, $student1_id, $student2_id) {
    $stmt = $conn->prepare("SELECT id, sex FROM students WHERE id IN (?, ?)");
    $stmt->bind_param("ii", $student1_id, $student2_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 2) {
        return ["success" => false, "message" => "One or both students not found."];
    }

    $students = $result->fetch_all(MYSQLI_ASSOC);
    if ($students[0]['sex'] !== $students[1]['sex']) {
        return ["success" => false, "message" => "Cannot match students of different genders."];
    }

    $stmt = $conn->prepare("SELECT * FROM room_allocations WHERE student1_id = ? OR student2_id = ?");
    $stmt->bind_param("ii", $student1_id, $student2_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return allocate_room($conn, $student1_id, $student2_id);
    }

    return allocate_room($conn, $student1_id, $student2_id);
}

// Function to display match details
function display_match_details($conn, $student1_id, $student2_id) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id IN (?, ?)");
    $stmt->bind_param("ii", $student1_id, $student2_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 2) {
        return ["success" => false, "message" => "One or both students not found."];
    }

    $students = $result->fetch_all(MYSQLI_ASSOC);
    $student1 = $students[0]['id'] === $student1_id ? $students[0] : $students[1];
    $student2 = $students[0]['id'] === $student2_id ? $students[0] : $students[1];

    $match_score = calculate_match_score($student1, $student2);
    $match_details = get_match_breakdown($student1, $student2);
    $room_info = get_student_room($conn, $student1_id);

    return [
        "success" => true,
        "student1" => $student1,
        "student2" => $student2,
        "match_score" => $match_score,
        "match_details" => $match_details,
        "room_info" => $room_info
    ];
}

// Function to initialize rooms dynamically
function initialize_rooms($conn, $blocks = ['A', 'B', 'C', 'D'], $floors = 3, $rooms_per_floor = 15) {
    $sql = "INSERT INTO Rooms (block, floor, room_number, capacity) VALUES ";
    $values = [];

    foreach ($blocks as $block) {
        for ($floor = 1; $floor <= $floors; $floor++) {
            for ($room = 1; $room <= $rooms_per_floor; $room++) {
                $values[] = "('$block', $floor, $room, 0)";
            }
        }
    }

    $sql .= implode(", ", $values);

    if ($conn->query($sql) === TRUE) {
        return ["success" => true, "message" => "Rooms initialized successfully."];
    } else {
        return ["success" => false, "message" => "Error initializing rooms: " . $conn->error];
    }
}
?>