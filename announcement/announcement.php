<?php
$pageTitle = "Announcement";
$breadcrumb = "Pages / Announcement";
include '../include/header.php';

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../authentication/signin.php");
    exit();
}

// Get the user's role
$role = $_SESSION['role'];

// Fetch announcements from the database
if ($role == 'admin') {
    $stmt = $conn->prepare("SELECT a.announcementid, a.title, a.message, a.created_at, a.target_audience, u.firstname, u.role FROM announcement a JOIN users u ON a.userid = u.userid ORDER BY a.created_at DESC");
} else if ($role == 'teacher') {
    $stmt = $conn->prepare("SELECT a.announcementid, a.title, a.message, a.created_at, a.target_audience, u.firstname, u.role FROM announcement a JOIN users u ON a.userid = u.userid WHERE FIND_IN_SET('All', a.target_audience) OR FIND_IN_SET('Teachers', a.target_audience) ORDER BY a.created_at DESC");
} else if ($role == 'student') {
    $stmt = $conn->prepare("SELECT a.announcementid, a.title, a.message, a.created_at, a.target_audience, u.firstname, u.role FROM announcement a JOIN users u ON a.userid = u.userid WHERE FIND_IN_SET('All', a.target_audience) OR FIND_IN_SET('Students', a.target_audience) ORDER BY a.created_at DESC");
} else if ($role == 'parent') {
    $stmt = $conn->prepare("SELECT a.announcementid, a.title, a.message, a.created_at, a.target_audience, u.firstname, u.role FROM announcement a JOIN users u ON a.userid = u.userid WHERE FIND_IN_SET('All', a.target_audience) OR FIND_IN_SET('Parents', a.target_audience) ORDER BY a.created_at DESC");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <!-- Display Announcements -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Recent Announcements</h4>
                        <?php if ($role == 'admin' || $role == 'teacher'): ?>
                            <a href="add_announcement.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="announcement d-flex justify-content-between align-items-start">
                                <div>
                                    <p><i class="fas fa-user" style="padding-right: 5px;"></i> <?php echo ($row['role'] == 'admin' ? 'Admin' : 'Teacher') . ' ' . ucwords(htmlspecialchars($row['firstname'])); ?></p>
                                    <h5><strong><?php echo htmlspecialchars($row['title']); ?></strong></h5>
                                    <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                                    <small><?php echo htmlspecialchars($row['created_at']); ?></small>
                                </div>
                                <div>
                                    <?php if ($role == 'admin' || $role == 'teacher'): ?>
                                        <a href="edit_announcement.php?id=<?php echo $row['announcementid']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="remove_announcement.php?id=<?php echo $row['announcementid']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr>
                        <?php endwhile; ?>
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