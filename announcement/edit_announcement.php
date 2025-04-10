<?php
$pageTitle = "Edit Announcement";
$breadcrumb = "Pages / Announcement / Edit Announcement";
include '../include/header.php';

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../authentication/signin.php");
    exit();
}

// Get the user's role
$role = $_SESSION['role'];

// Only allow admin and teacher to access this page
if ($role != 'admin' && $role != 'teacher') {
    header("Location: announcement.php");
    exit();
}

// Get the announcement ID from the URL
$announcement_id = $_GET['id'];

// Fetch the existing announcement data
$stmt = $conn->prepare("SELECT title, message, target_audience FROM announcement WHERE announcementid = ?");
$stmt->bind_param("s", $announcement_id);
$stmt->execute();
$stmt->bind_result($title, $message, $target_audience);
$stmt->fetch();
$stmt->close();

// Handle form submission for editing announcements
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $target_audience = implode(', ', $_POST['target_audience']); // Combine selected values into a string

    // Update the announcement in the database
    $stmt = $conn->prepare("UPDATE announcement SET title = ?, message = ?, target_audience = ? WHERE announcementid = ?");
    $stmt->bind_param("ssss", $title, $message, $target_audience, $announcement_id);

    if ($stmt->execute()) {
        $success_message = "Announcement updated successfully.";
        echo '<meta http-equiv="refresh" content="1;url=announcement.php">';
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <!-- Announcement Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit Announcement Form</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php elseif (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                                <meta http-equiv="refresh" content="1;url=announcement.php">
                            </div>
                        <?php endif; ?>
                        <form action="edit_announcement.php?id=<?php echo $announcement_id; ?>" method="POST">
                            <div class="form-group">
                                <label for="title">Title:</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message:</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="target_audience">Target Audience:</label>
                                <div class="selectgroup selectgroup-pills">
                                    <?php if ($role == 'admin'): ?>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="target_audience[]" value="All" class="selectgroup-input" <?php echo (strpos($target_audience, 'All') !== false) ? 'checked' : ''; ?> />
                                            <span class="selectgroup-button">All</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="target_audience[]" value="Teachers" class="selectgroup-input" <?php echo (strpos($target_audience, 'Teachers') !== false) ? 'checked' : ''; ?> />
                                            <span class="selectgroup-button">Teachers</span>
                                        </label>
                                    <?php endif; ?>
                                    <label class="selectgroup-item">
                                        <input type="checkbox" name="target_audience[]" value="Students" class="selectgroup-input" <?php echo (strpos($target_audience, 'Students') !== false) ? 'checked' : ''; ?> />
                                        <span class="selectgroup-button">Students</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="checkbox" name="target_audience[]" value="Parents" class="selectgroup-input" <?php echo (strpos($target_audience, 'Parents') !== false) ? 'checked' : ''; ?> />
                                        <span class="selectgroup-button">Parents</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Edit</button>
                            <a href="announcement.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>

<?php
$conn->close();
?>