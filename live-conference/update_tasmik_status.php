<?php
// filepath: c:\Users\Asus\OneDrive\Desktop\FYP\(4) Development\TasmikPro\live-conference\update_tasmik_status.php
session_start();

// Database connection
require_once '../database/db_connection.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to perform this action']);
    exit();
}

// Get the data from the POST request
$meetingLink = isset($_POST['meetingLink']) ? $_POST['meetingLink'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$feedback = isset($_POST['feedback']) ? $_POST['feedback'] : '';

// Validate the data
if (empty($meetingLink) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Meeting link and status are required']);
    exit();
}

// Validate status
if ($status !== 'accepted' && $status !== 'repeated') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // Get the tasmik ID from the zoom_meetings table
    $query = "SELECT z.tasmikid, z.studentid 
              FROM zoom_meetings z 
              WHERE z.meeting_link = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $meetingLink);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Meeting not found']);
        exit();
    }
    
    $meeting = $result->fetch_assoc();
    $tasmikId = $meeting['tasmikid'];
    $studentId = $meeting['studentid'];
    
    // Update the tasmik status and feedback
    $updateQuery = "UPDATE tasmik 
                    SET status = ?, 
                        teacher_feedback = ?, 
                        updated_at = NOW() 
                    WHERE tasmikid = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sss", $status, $feedback, $tasmikId);
    $success = $stmt->execute();
    
    if ($success) {
        // Create an activity log entry
        $teacherId = $_SESSION['userid'];
        $action = ($status === 'accepted') ? 'accepted' : 'marked for repeat';
        $description = "Tasmik session ID #$tasmikId was $action after live conference";
        
        $logQuery = "INSERT INTO activity_log (user_id, action_type, description, related_id, related_type) 
                     VALUES (?, 'tasmik_update', ?, ?, 'tasmik')";
        $stmt = $conn->prepare($logQuery);
        $stmt->bind_param("sss", $teacherId, $description, $tasmikId);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tasmik status updated successfully',
            'tasmikId' => $tasmikId,
            'status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update tasmik status']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>