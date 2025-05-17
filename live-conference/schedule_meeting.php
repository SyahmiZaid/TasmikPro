<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Retrieve userid from session
$userid = $_SESSION['userid'] ?? null;

if (!$userid) {
    echo json_encode(['success' => false, 'message' => 'User ID is missing from the session.']);
    exit;
}

// Fetch teacherid using userid
$query = "SELECT teacherid FROM teacher WHERE userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID not found for the given User ID.']);
    exit;
}

$row = $result->fetch_assoc();
$teacherID = $row['teacherid'];

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tasmikid = $_POST['tasmikid'] ?? null;
    $studentid = $_POST['studentid'] ?? null;
    $date = $_POST['date'] ?? null;
    $time = $_POST['time'] ?? null;

    // Validate required parameters
    if (!$tasmikid || !$studentid || !$date || !$time) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        exit;
    }

    $scheduled_at = $date . ' ' . $time . ':00';

    // Generate a unique meeting ID
    $dayMonth = date("dm"); // Format: DDMM
    $likePattern = "MT" . $dayMonth . "_%";

    $query = "SELECT COUNT(*) AS count FROM zoom_meetings WHERE meeting_id LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $ascendingNumber = $row['count'] + 1;

    // Format the count as two digits (e.g., 01, 02, etc.)
    $formattedNumber = str_pad($ascendingNumber, 2, "0", STR_PAD_LEFT);

    // Generate the meeting ID
    $meeting_id = "MT" . $dayMonth . "_" . $formattedNumber;

    // Generate the meeting link
    $uniqueSuffix = time(); // Current timestamp
    $meeting_link = "https://meet.jit.si/tasmik_room_" . $teacherID . "_" . $uniqueSuffix;

    // Insert into database
    $query = "INSERT INTO zoom_meetings (tasmikid, studentid, teacherid, meeting_id, meeting_link, scheduled_at) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $tasmikid, $studentid, $teacherID, $meeting_id, $meeting_link, $scheduled_at);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Meeting scheduled successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to schedule the meeting.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;
