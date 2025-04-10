<?php
require_once __DIR__ . '/../database/db_connection.php'; // Corrected file path

if (isset($_GET['id'])) {
    $documentId = $_GET['id'];

    // Fetch the document path from the database
    $stmt = $conn->prepare("SELECT path FROM document WHERE documentid = ?");
    $stmt->bind_param("s", $documentId);
    $stmt->execute();
    $stmt->bind_result($documentPath);
    $stmt->fetch();
    $stmt->close();

    if ($documentPath) {
        // Delete the document record from the database
        $stmt = $conn->prepare("DELETE FROM document WHERE documentid = ?");
        $stmt->bind_param("s", $documentId);
        if ($stmt->execute()) {
            // Delete the file from the server
            if (file_exists($documentPath)) {
                unlink($documentPath);
            }
            echo "Document deleted successfully.";
        } else {
            echo "Error deleting document: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Document not found.";
    }
} else {
    echo "No document ID specified.";
}

$conn->close();
?>