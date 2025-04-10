<?php
$pageTitle = "Profile";
$breadcrumb = "Pages / <a href='../parent/view_profile.php' class='no-link-style'>Profile</a>";
include '../include/header.php';
require_once '../database/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid = $_SESSION['userid'];
$role = $_SESSION['role'];

// Database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user base information
$stmt = $conn->prepare("SELECT u.userid, u.email, u.role, u.firstname, u.lastname, u.createdat, u.profile_image 
                       FROM users u WHERE u.userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get role-specific information
$roleSpecificData = [];
switch ($role) {
    case 'admin':
        $stmt = $conn->prepare("SELECT a.adminid FROM admin a WHERE a.userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();
        break;
    case 'teacher':
        $stmt = $conn->prepare("SELECT t.teacherid, t.halaqahid, t.gender, h.halaqahname 
                              FROM teacher t 
                              LEFT JOIN halaqah h ON t.halaqahid = h.halaqahid 
                              WHERE t.userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();
        break;
    case 'student':
        $stmt = $conn->prepare("SELECT s.studentid, s.halaqahid, s.parentid, s.form, s.class, s.ic, s.gender, 
                              h.halaqahname, CONCAT(p_user.firstname, ' ', p_user.lastname) as parent_name 
                              FROM student s 
                              LEFT JOIN halaqah h ON s.halaqahid = h.halaqahid 
                              LEFT JOIN parent p ON s.parentid = p.parentid 
                              LEFT JOIN users p_user ON p.userid = p_user.userid 
                              WHERE s.userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();
        break;
    case 'parent':
        $stmt = $conn->prepare("SELECT p.parentid, p.contact FROM parent p WHERE p.userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();

        // Get children information
        $stmt = $conn->prepare("SELECT s.studentid, CONCAT(u.firstname, ' ', u.lastname) as student_name, 
                              s.form, s.class, h.halaqahname 
                              FROM student s 
                              JOIN users u ON s.userid = u.userid 
                              LEFT JOIN halaqah h ON s.halaqahid = h.halaqahid 
                              WHERE s.parentid = ?");
        $stmt->bind_param("s", $roleSpecificData['parentid']);
        $stmt->execute();
        $childrenResult = $stmt->get_result();
        $children = [];
        while ($child = $childrenResult->fetch_assoc()) {
            $children[] = $child;
        }
        $roleSpecificData['children'] = $children;
        break;
}

// Get documents uploaded by the user
$stmt = $conn->prepare("SELECT documentid, name, path, uploaded_at FROM document WHERE userid = ? ORDER BY uploaded_at DESC LIMIT 5");
$stmt->bind_param("s", $userid);
$stmt->execute();
$docsResult = $stmt->get_result();
$documents = [];
while ($doc = $docsResult->fetch_assoc()) {
    $documents[] = $doc;
}

$conn->close();

// Function to handle profile image or display default user icon
function getProfileImage($image)
{
    if (empty($image) || !file_exists($image)) {
        // Return user icon HTML instead of an image path
        return '<i class="fas fa-user-circle fa-5x"></i>';
    }
    // Return actual image tag if we have a valid image
    return '<img src="' . $image . '" alt="Profile Picture" class="avatar-img rounded-circle">';
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card card-profile">
                    <div class="card-header" style="background-image: url('../assets/img/profilebg.jpg')">
                        <div class="profile-picture">
                            <div class="avatar avatar-xl">
                                <?php echo getProfileImage($user['profile_image']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="user-profile text-center">
                            <div class="name"><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></div>
                            <div class="job"><?php echo ucfirst($user['role']); ?></div>
                            <div class="desc">Member since <?php echo date('F Y', strtotime($user['createdat'])); ?></div>
                            <div class="social-media">
                                <a href="edit_profile.php" class="btn btn-primary btn-rounded px-4">
                                    <i class="fa fa-edit mr-2"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Contact Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5 col-md-4"><strong>Email:</strong></div>
                            <div class="col-7 col-md-8"><?php echo $user['email']; ?></div>
                        </div>
                        <div class="separator-dashed"></div>

                        <?php if ($role === 'parent' && isset($roleSpecificData['contact'])): ?>
                            <div class="row">
                                <div class="col-5 col-md-4"><strong>Contact:</strong></div>
                                <div class="col-7 col-md-8"><?php echo $roleSpecificData['contact']; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-5 col-md-4"><strong>User ID:</strong></div>
                            <div class="col-7 col-md-8"><?php echo $user['userid']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?php echo ucfirst($role); ?> Details</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($role === 'admin'): ?>
                            <div class="row">
                                <div class="col-md-4"><strong>Admin ID:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['adminid'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> As an admin, you have access to manage users, announcements, and system settings.
                            </div>

                        <?php elseif ($role === 'teacher'): ?>
                            <div class="row">
                                <div class="col-md-4"><strong>Teacher ID:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['teacherid'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>Halaqah:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['halaqahname'] ?? 'Not Assigned'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>Gender:</strong></div>
                                <div class="col-md-8"><?php echo ucfirst($roleSpecificData['gender'] ?? 'Not Specified'); ?></div>
                            </div>

                        <?php elseif ($role === 'student'): ?>
                            <div class="row">
                                <div class="col-md-4"><strong>Student ID:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['studentid'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>Form/Class:</strong></div>
                                <div class="col-md-8">
                                    <?php echo ($roleSpecificData['form'] ?? 'Not Specified') . ' / ' . ($roleSpecificData['class'] ?? 'Not Specified'); ?>
                                </div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>IC Number:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['ic'] ?? 'Not Specified'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>Gender:</strong></div>
                                <div class="col-md-8"><?php echo ucfirst($roleSpecificData['gender'] ?? 'Not Specified'); ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>Halaqah:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['halaqahname'] ?? 'Not Assigned'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>
                            <div class="row">
                                <div class="col-md-4"><strong>Parent:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['parent_name'] ?? 'Not Specified'; ?></div>
                            </div>

                        <?php elseif ($role === 'parent'): ?>
                            <div class="row">
                                <div class="col-md-4"><strong>Parent ID:</strong></div>
                                <div class="col-md-8"><?php echo $roleSpecificData['parentid'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="separator-dashed"></div>

                            <h5 class="mt-3 mb-3">Children Information</h5>
                            <?php if (!empty($roleSpecificData['children'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Form/Class</th>
                                                <th>Halaqah</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleSpecificData['children'] as $child): ?>
                                                <tr>
                                                    <td><?php echo $child['student_name']; ?></td>
                                                    <td><?php echo $child['form'] . '/' . $child['class']; ?></td>
                                                    <td><?php echo $child['halaqahname'] ?? 'Not Assigned'; ?></td>
                                                    <td>
                                                        <a href="../student/view_student_progress.php?id=<?php echo $child['studentid']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fa fa-chart-line"></i> View Progress
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No children information available.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents Section -->
                <!-- <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">My Documents</h4>
                            <a href="../document/upload_document.php" class="btn btn-primary btn-round ml-auto">
                                <i class="fa fa-plus"></i> Upload New
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($documents)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Uploaded</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?php echo $doc['name']; ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo $doc['path']; ?>" target="_blank" class="btn btn-primary btn-sm">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="../document/download.php?id=<?php echo $doc['documentid']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($documents) >= 5): ?>
                                <div class="text-center mt-3">
                                    <a href="../document/view_all_documents.php" class="btn btn-sm btn-default">View All Documents</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                You haven't uploaded any documents yet. <a href="../document/upload_document.php">Upload now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div> -->

                <?php if ($role === 'student'): ?>
                    <!-- Tasmik Progress for Students -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Tasmik Progress</h4>
                        </div>
                        <div class="card-body">
                            <?php
                            // Reopen connection for this section
                            $conn = new mysqli($servername, $username, $password, $dbname);
                            $stmt = $conn->prepare("SELECT tasmik_date, juzuk, start_page, end_page, status 
                                              FROM tasmik 
                                              WHERE studentid = ? 
                                              ORDER BY tasmik_date DESC LIMIT 5");
                            $stmt->bind_param("s", $roleSpecificData['studentid']);
                            $stmt->execute();
                            $tasmikResult = $stmt->get_result();

                            if ($tasmikResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Juzuk</th>
                                                <th>Pages</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($tasmik = $tasmikResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('d M Y', strtotime($tasmik['tasmik_date'])); ?></td>
                                                    <td><?php echo $tasmik['juzuk']; ?></td>
                                                    <td><?php echo $tasmik['start_page'] . ' - ' . $tasmik['end_page']; ?></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        if ($tasmik['status'] == 'approved') $statusClass = 'success';
                                                        if ($tasmik['status'] == 'rejected') $statusClass = 'danger';
                                                        ?>
                                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                                            <?php echo ucfirst($tasmik['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="../student/view_all_tasmik.php" class="btn btn-sm btn-default">View All Records</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No tasmik records available.</div>
                            <?php endif;
                            $conn->close();
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($role === 'teacher'): ?>
                    <!-- Halaqah Students for Teachers -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">My Halaqah Students</h4>
                        </div>
                        <div class="card-body">
                            <?php
                            // Reopen connection for this section
                            $conn = new mysqli($servername, $username, $password, $dbname);
                            $stmt = $conn->prepare("SELECT s.studentid, CONCAT(u.firstname, ' ', u.lastname) as student_name, 
                                              s.form, s.class 
                                              FROM student s 
                                              JOIN users u ON s.userid = u.userid 
                                              WHERE s.halaqahid = ? 
                                              LIMIT 5");
                            $stmt->bind_param("s", $roleSpecificData['halaqahid']);
                            $stmt->execute();
                            $studentsResult = $stmt->get_result();

                            if ($studentsResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Form/Class</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $student['student_name']; ?></td>
                                                    <td><?php echo $student['form'] . '/' . $student['class']; ?></td>
                                                    <td>
                                                        <a href="../teacher/view_student_details.php?id=<?php echo $student['studentid']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fa fa-user"></i> View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="../teacher/view_all_students.php" class="btn btn-sm btn-default">View All Students</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No students assigned to your halaqah yet.</div>
                            <?php endif;
                            $conn->close();
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>