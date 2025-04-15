<?php
$pageTitle = "View Assessment";
$breadcrumb = "Pages / VLE - Teacher Portal / Course / View Assessment";
include '../include/header.php';

// Fetch assessment details from the database
if (isset($_GET['id'])) {
    $assessmentId = htmlspecialchars($_GET['id']);
    $query = "SELECT * FROM vle_assessments WHERE assessmentid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $assessmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();

    if (!$assessment) {
        echo "<div class='alert alert-danger text-center'>Assessment not found.</div>";
        include '../include/footer.php';
        exit;
    }

    // Get time remaining
    $dueDate = new DateTime($assessment['due_date']);
    $now = new DateTime();
    $interval = $now->diff($dueDate);
    $isPastDue = $now > $dueDate;
} else {
    echo "<div class='alert alert-danger text-center'>No assessment ID provided.</div>";
    include '../include/footer.php';
    exit;
}
?>

<div class="container">
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <!-- Breadcrumb or additional header content can go here -->
        </div>

        <!-- Main Content -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clipboard-check me-2"></i>
                    <h5 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h5>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row mb-4">
                    <!-- Assessment Type -->
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-primary">
                                <i class="fas fa-file-alt fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-muted">Assessment Type</h6>
                                <p class="mb-0 fs-5"><?php echo ucfirst(htmlspecialchars($assessment['type'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-primary">
                                <i class="fas fa-calendar-alt fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-muted">Due Date</h6>
                                <p class="mb-0 fs-5"><?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Status -->
                <div class="mb-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3 text-<?php echo $isPastDue ? 'danger' : 'warning'; ?>">
                            <i class="fas fa-<?php echo $isPastDue ? 'hourglass-end' : 'hourglass-half'; ?> fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="text-<?php echo $isPastDue ? 'danger' : 'warning'; ?> mb-0">
                                <?php echo $isPastDue ? 'Overdue by:' : 'Time Remaining:'; ?>
                            </h6>
                            <p class="mb-0 fs-5 text-<?php echo $isPastDue ? 'danger' : 'dark'; ?>">
                                <?php
                                if ($interval->days > 0) {
                                    echo $interval->days . ' days, ';
                                }
                                echo $interval->h . ' hours, ' . $interval->i . ' minutes';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3 text-info">
                            <i class="fas fa-info-circle fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Description</h6>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($assessment['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attachment Section -->
                <?php if (!empty($assessment['attachment_path'])): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-primary">
                                <i class="fas fa-paperclip fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Attachments</h6>
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="fas fa-file-pdf text-danger me-3"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Assessment Document</h6>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($assessment['attachment_path']); ?>" class="btn btn-primary btn-sm" download>
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Status -->
                <?php if (!empty($assessment['status'])): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-primary">
                                <i class="fas fa-info-circle fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Status</h6>
                                <span class="badge bg-<?php echo ($assessment['status'] == 'published') ? 'success' : (($assessment['status'] == 'draft') ? 'warning' : 'secondary'); ?> px-3 py-2">
                                    <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Max Score -->
                <?php if (!empty($assessment['max_score'])): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-success">
                                <i class="fas fa-star fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Maximum Score</h6>
                                <p class="mb-0 fs-5"><?php echo htmlspecialchars($assessment['max_score']); ?> points</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-flex mt-4">
                    <a href="course.php?courseid=<?php echo htmlspecialchars($assessment['courseid']); ?>" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back to Course
                    </a>

                    <a href="edit_assessment.php?id=<?php echo $assessmentId; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-1"></i> Edit Assessment
                    </a>

                    <a href="manage_submission.php?assessment_id=<?php echo $assessmentId; ?>" class="btn btn-success">
                        <i class="fas fa-users me-1"></i> View Submissions
                    </a>

                    <?php if ($isPastDue): ?>
                        <div class="ms-auto">
                            <span class="badge bg-danger p-2">
                                <i class="fas fa-exclamation-circle me-1"></i> Past Due Date
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>