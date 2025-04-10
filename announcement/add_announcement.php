<?php
$pageTitle = "Make Announcement";
$breadcrumb = "Pages / Announcement / Add Announcement";
include '../include/header.php';

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../authentication/signin.php");
    exit();
}

// Get the user's role and ID
$role = $_SESSION['role'];
$userid = $_SESSION['userid'];

// Only allow admin and teacher to access this page
if ($role != 'admin' && $role != 'teacher') {
    header("Location: announcement.php");
    exit();
}

// Handle form submission for making announcements
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $created_at = date("Y-m-d H:i:s");

    // Generate unique announcement ID
    $announcement_prefix = "ANN";
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(announcementid, 4) AS UNSIGNED)) AS max_id FROM announcement");
    $stmt->execute();
    $stmt->bind_result($max_id);
    $stmt->fetch();
    $stmt->close();
    $new_id = $max_id + 1;
    $announcement_id = $announcement_prefix . str_pad($new_id, 3, '0', STR_PAD_LEFT);

    // Ensure teachers can only select "Students" and "Parents"
    if ($role == 'teacher') {
        $target_audience = array_intersect($_POST['target_audience'], ['Students', 'Parents']);
    } else {
        $target_audience = $_POST['target_audience'];
    }

    $target_audience = implode(', ', $target_audience); // Combine selected values into a string

    // Insert the announcement into the database
    $stmt = $conn->prepare("INSERT INTO announcement (announcementid, title, message, created_at, target_audience, userid) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $announcement_id, $title, $message, $created_at, $target_audience, $userid);

    if ($stmt->execute()) {
        $success_message = "Announcement created successfully.";
        header("refresh:1;url=announcement.php");
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
                        <h4 class="card-title">Announcement Form</h4>
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
                        <form action="add_announcement.php" method="POST">
                            <div class="form-group">
                                <label for="title">Title:</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message:</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="target_audience">Target Audience:</label>
                                <div class="selectgroup selectgroup-pills">
                                    <?php if ($role == 'admin'): ?>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="target_audience[]" value="All" class="selectgroup-input" />
                                            <span class="selectgroup-button">All</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="target_audience[]" value="Teachers" class="selectgroup-input" />
                                            <span class="selectgroup-button">Teachers</span>
                                        </label>
                                    <?php endif; ?>
                                    <label class="selectgroup-item">
                                        <input type="checkbox" name="target_audience[]" value="Students" class="selectgroup-input" />
                                        <span class="selectgroup-button">Students</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="checkbox" name="target_audience[]" value="Parents" class="selectgroup-input" />
                                        <span class="selectgroup-button">Parents</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
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