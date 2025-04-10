<?php
$pageTitle = "View User";
$breadcrumb = "Pages / <a href='../admin/manage_student.php' class='no-link-style'>Manage Student</a> / View User";
include '../include/header.php';
require_once '../database/db_connection.php';

// Get the user ID and role from the query parameters
$userId = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : 0;
$role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : '';

// Validate role
$validRoles = ['student', 'parent', 'teacher']; // Add more roles as needed
if (!in_array($role, $validRoles)) {
    echo "<div class='container'><div class='alert alert-danger'>Invalid role specified.</div></div>";
    include '../include/footer.php';
    exit;
}

// Fetch user details based on the role
if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT 
            student.studentid, 
            users.firstname,
            users.lastname,
            CONCAT(users.firstname, ' ', users.lastname) AS name, 
            users.email,
            users.createdat, 
            users.profile_image,
            student.halaqahid,
            student.parentid,
            student.form,
            student.class,
            student.ic,
            student.gender
        FROM 
            student
        JOIN 
            users 
        ON 
            student.userid = users.userid
        WHERE 
            student.studentid = ?
    ");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // If student has a parent ID, fetch parent details
    $parentDetails = null;
    if ($user && !empty($user['parentid'])) {
        $parentStmt = $conn->prepare("
            SELECT 
                parent.parentid, 
                users.firstname,
                users.lastname,
                CONCAT(users.firstname, ' ', users.lastname) AS name, 
                users.email, 
                parent.contact
            FROM 
                parent
            JOIN 
                users 
            ON 
                parent.userid = users.userid
            WHERE 
                parent.parentid = ?
        ");
        $parentStmt->bind_param("s", $user['parentid']);
        $parentStmt->execute();
        $parentResult = $parentStmt->get_result();
        $parentDetails = $parentResult->fetch_assoc();
    }

    // Fetch halaqah name if halaqahid exists
    $halaqahName = null;
    if ($user && !empty($user['halaqahid'])) {
        $halaqahStmt = $conn->prepare("
            SELECT halaqahname FROM halaqah WHERE halaqahid = ?
        ");
        $halaqahStmt->bind_param("s", $user['halaqahid']);
        $halaqahStmt->execute();
        $halaqahResult = $halaqahStmt->get_result();
        $halaqahData = $halaqahResult->fetch_assoc();
        $halaqahName = $halaqahData ? $halaqahData['halaqahname'] : 'Not Assigned';
    }
} elseif ($role === 'parent') {
    $stmt = $conn->prepare("
        SELECT 
            parent.parentid, 
            users.firstname,
            users.lastname,
            CONCAT(users.firstname, ' ', users.lastname) AS name, 
            users.email,
            users.createdat, 
            users.profile_image,
            parent.contact
        FROM 
            parent
        JOIN 
            users 
        ON 
            parent.userid = users.userid
        WHERE 
            parent.parentid = ?
    ");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Get this parent's children (students)
    $childrenStmt = $conn->prepare("
        SELECT 
            student.studentid,
            CONCAT(users.firstname, ' ', users.lastname) AS name,
            student.form,
            student.class
        FROM 
            student
        JOIN 
            users 
        ON 
            student.userid = users.userid
        WHERE 
            student.parentid = ?
    ");
    $childrenStmt->bind_param("s", $userId);
    $childrenStmt->execute();
    $childrenResult = $childrenStmt->get_result();
    $children = [];
    while ($child = $childrenResult->fetch_assoc()) {
        $children[] = $child;
    }
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("
        SELECT 
            teacher.teacherid, 
            users.firstname,
            users.lastname,
            CONCAT(users.firstname, ' ', users.lastname) AS name, 
            users.email,
            users.createdat, 
            users.profile_image,
            teacher.halaqahid,
            teacher.gender
        FROM 
            teacher
        JOIN 
            users 
        ON 
            teacher.userid = users.userid
        WHERE 
            teacher.teacherid = ?
    ");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Fetch halaqah name if halaqahid exists
    $halaqahName = null;
    if ($user && !empty($user['halaqahid'])) {
        $halaqahStmt = $conn->prepare("
            SELECT halaqahname FROM halaqah WHERE halaqahid = ?
        ");
        $halaqahStmt->bind_param("s", $user['halaqahid']);
        $halaqahStmt->execute();
        $halaqahResult = $halaqahStmt->get_result();
        $halaqahData = $halaqahResult->fetch_assoc();
        $halaqahName = $halaqahData ? $halaqahData['halaqahname'] : 'Not Assigned';
    }
}

if (!$user) {
    echo "<div class='container'><div class='alert alert-danger'>User not found.</div></div>";
    include '../include/footer.php';
    exit;
}

// Get role icon
$roleIcon = '';
if ($role === 'student') {
    $roleIcon = 'fas fa-user-graduate';
} elseif ($role === 'parent') {
    $roleIcon = 'fas fa-user-friends';
} elseif ($role === 'teacher') {
    $roleIcon = 'fas fa-chalkboard-teacher';
}
?>

<link rel="stylesheet" href="../assets/css/header-container.css" />
<link rel="stylesheet" href="style.css" />

<div class="container">
    <div class="page-inner">
        <!-- Custom header section with animation -->
        <div class="custom-header-container">
            <div class="custom-header-bg"></div>
            <div class="custom-header-overlay"></div>
            <div class="custom-header-content">
                <h1 class="custom-header-title">User Profile</h1>
                <p class="custom-header-subtitle" style="color: cadetblue; margin-top: -10px">View and manage user information</p>
            </div>
        </div>

        <!-- Container for the rest of the content -->
        <div class="content-container">
            <div class="profile-container">
                <div class="user-profile-header">
                    <div class="user-avatar">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-image">
                        <?php else: ?>
                            <i class="<?php echo $roleIcon; ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <h1 class="user-name"><?php echo strtoupper(htmlspecialchars($user['firstname'] . ' ' . $user['lastname'])); ?></h1>
                        <p class="user-id">
                            <?php
                            if ($role === 'student') {
                                echo 'Student ID: ' . htmlspecialchars($user['studentid']);
                            } elseif ($role === 'parent') {
                                echo 'Parent ID: ' . htmlspecialchars($user['parentid']);
                            } elseif ($role === 'teacher') {
                                echo 'Teacher ID: ' . htmlspecialchars($user['teacherid']);
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <?php if ($role === 'student'): ?>
                        <div class="col-md-4">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-user"></i>
                                    <h3 class="info-section-title">Personal Details</h3>
                                </div>
                                <div class="info-section-content">
                                    <div class="info-item">
                                        <div class="info-label">Student ID</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['studentid']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Email</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Registered Date</div>
                                        <div class="info-value"><?php echo date('d M Y', strtotime($user['createdat'])); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Gender</div>
                                        <div class="info-value">
                                            <?php if (strtolower($user['gender']) === 'male'): ?>
                                                <span class="badge badge-primary badge-custom">Male</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger badge-custom">Female</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">IC</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['ic']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-school"></i>
                                    <h3 class="info-section-title">School Information</h3>
                                </div>
                                <div class="info-section-content">
                                    <div class="info-item">
                                        <div class="info-label">Form</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['form']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Class</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['class']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Halaqah ID</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['halaqahid']); ?></div>
                                    </div>
                                    <?php if (isset($halaqahName)): ?>
                                        <div class="info-item">
                                            <div class="info-label">Halaqah Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($halaqahName); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-user-friends"></i>
                                    <h3 class="info-section-title">Parent Information</h3>
                                </div>
                                <div class="info-section-content">
                                    <?php if ($parentDetails): ?>
                                        <div class="info-item">
                                            <div class="info-label">Parent ID</div>
                                            <div class="info-value"><?php echo htmlspecialchars($parentDetails['parentid']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Parent Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($parentDetails['name']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?php echo htmlspecialchars($parentDetails['email']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Contact</div>
                                            <div class="info-value">
                                                <?php if (!empty($parentDetails['contact'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($parentDetails['contact']); ?>" class="text-decoration-none">
                                                        <i class="fas fa-phone-alt mr-2 text-muted"></i><?php echo htmlspecialchars($parentDetails['contact']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">No parent information available</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($role === 'parent'): ?>
                        <div class="col-md-6">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-user"></i>
                                    <h3 class="info-section-title">Personal Details</h3>
                                </div>
                                <div class="info-section-content">
                                    <div class="info-item">
                                        <div class="info-label">Parent ID</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['parentid']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Email</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Registered Date</div>
                                        <div class="info-value"><?php echo date('d M Y', strtotime($user['createdat'])); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Contact</div>
                                        <div class="info-value">
                                            <?php if (!empty($user['contact'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($user['contact']); ?>" class="text-decoration-none">
                                                    <i class="fas fa-phone-alt mr-2 text-muted"></i><?php echo htmlspecialchars($user['contact']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Not provided</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-child"></i>
                                    <h3 class="info-section-title">Children Information</h3>
                                </div>
                                <div class="info-section-content">
                                    <?php if (!empty($children)): ?>
                                        <ul class="children-list">
                                            <?php foreach ($children as $child): ?>
                                                <li>
                                                    <a href="view_user.php?id=<?php echo htmlspecialchars($child['studentid']); ?>&role=student" class="text-decoration-none">
                                                        <span class="student-name"><?php echo htmlspecialchars($child['name']); ?></span>
                                                        <span class="student-class">Form <?php echo htmlspecialchars($child['form']); ?>, Class <?php echo htmlspecialchars($child['class']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="alert alert-info">No children registered</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($role === 'teacher'): ?>
                        <div class="col-md-6">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-user"></i>
                                    <h3 class="info-section-title">Personal Details</h3>
                                </div>
                                <div class="info-section-content">
                                    <div class="info-item">
                                        <div class="info-label">Teacher ID</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['teacherid']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Email</div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Registered Date</div>
                                        <div class="info-value"><?php echo date('d M Y', strtotime($user['createdat'])); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Gender</div>
                                        <div class="info-value">
                                            <?php if (strtolower($user['gender']) === 'male'): ?>
                                                <span class="badge badge-primary badge-custom">Male</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger badge-custom">Female</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <div class="info-section-header">
                                    <i class="fas fa-book"></i>
                                    <h3 class="info-section-title">Teaching Information</h3>
                                </div>
                                <div class="info-section-content">
                                    <div class="info-item">
                                        <div class="info-label">Halaqah ID</div>
                                        <div class="info-value"><?php echo !empty($user['halaqahid']) ? htmlspecialchars($user['halaqahid']) : '<span class="text-muted">Not assigned</span>'; ?></div>
                                    </div>
                                    <?php if (isset($halaqahName)): ?>
                                        <div class="info-item">
                                            <div class="info-label">Halaqah Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($halaqahName); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Replace the existing back button with this code -->
                <div class="action-buttons">
                    <?php
                    // Determine which page to go back to based on the user's role
                    $backPage = '../admin/manage_student.php';
                    if ($role === 'teacher') {
                        $backPage = '../admin/manage_teacher.php';
                    } 
                    // Check if there's a specific 'from' parameter in the URL
                    if (isset($_GET['from']) && !empty($_GET['from'])) {
                        $backPage = htmlspecialchars($_GET['from']);
                    }
                    ?>
                    <a href="<?php echo $backPage; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                    <a href="edit_user.php?id=<?php echo $userId; ?>&role=<?php echo $role; ?>" class="btn btn-primary">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>