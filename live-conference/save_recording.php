<?php
session_start();
require_once '../database/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['recording']) || $_FILES['recording']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No recording file received']);
    exit;
}

// Validate tasmik ID
$tasmikid = $_POST['tasmikid'] ?? '';
if (empty($tasmikid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tasmik ID is required']);
    exit;
}

// Create recordings directory if it doesn't exist
$uploadDir = '../recordings/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create recordings directory']);
        exit;
    }
}

// Generate unique filename
$filename = 'tasmik_' . $tasmikid . '_' . date('Ymd_His') . '.webm';
$uploadPath = $uploadDir . $filename;

// Save the file
if (move_uploaded_file($_FILES['recording']['tmp_name'], $uploadPath)) {
    // Check if the tasmik record exists
    $checkStmt = $conn->prepare("SELECT tasmikid FROM tasmik WHERE tasmikid = ?");
    $checkStmt->bind_param("s", $tasmikid);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($checkResult->num_rows === 0) {
        // Tasmik ID doesn't exist, so we'll need to create a new record in the database
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid Tasmik ID']);
        // Delete the uploaded file to avoid orphaned files
        unlink($uploadPath);
        exit;
    }
    
    // Update the tasmik record with the recording path
    $stmt = $conn->prepare("UPDATE tasmik SET recording_path = ? WHERE tasmikid = ?");
    $stmt->bind_param("ss", $filename, $tasmikid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recording saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
        // Delete the uploaded file to avoid orphaned files
        unlink($uploadPath);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save recording file']);
}
?>