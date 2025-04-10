<?php
$pageTitle = "View Assessment";
$breadcrumb = "Pages / <a href='../student/index.php' class='no-link-style'>Dashboard</a> / View Assessment";
include '../include/header.php';

// Fetch assessment details from the database
if (isset($_GET['id'])) {
    $assessmentId = htmlspecialchars($_GET['id']);
    // Correct table name: vle_assessments
    $query = "SELECT * FROM vle_assessments WHERE assessmentid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $assessmentId); // Use "s" for string type
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();

    if (!$assessment) {
        echo "<div class='alert alert-danger'>Assessment not found.</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>No assessment ID provided.</div>";
    exit;
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo htmlspecialchars($assessment['title']); ?></h4>
        </div>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <p class="text-muted"><?php echo htmlspecialchars($assessment['description']); ?></p>
        </div>
        <div class="card">
            <div class="card-body">
                <p><strong>Type:</strong> <?php echo ucfirst(htmlspecialchars($assessment['type'])); ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $assessment['status'] == 'published' ? 'success' : ($assessment['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                        <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                    </span>
                </p>
                <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                <?php if ($assessment['type'] == 'quiz' && !empty($assessment['duration_minutes'])): ?>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($assessment['duration_minutes']); ?> minutes</p>
                <?php endif; ?>
                <?php if (!empty($assessment['attachment_path'])): ?>
                    <p><strong>Attachment:</strong> 
                        <a href="<?php echo htmlspecialchars($assessment['attachment_path']); ?>" class="btn btn-primary" download>
                            Download Attachment
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>