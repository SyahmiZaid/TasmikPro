<?php
session_start();
include '../database/db_connection.php'; // Include the database connection file

// Check if the user's ID is stored in the session
if (!isset($_SESSION['userid'])) {
    echo "<div class='alert alert-danger text-center'>User not logged in. <a href='../index.php' class='btn btn-primary btn-round mt-3'>Back to Dashboard</a></div>";
    exit;
}

// Assuming the user's ID and user's role are stored in the session
$userid = $_SESSION['userid'];
$role = $_SESSION['role'];

// Fetch user details and role name from the database
$query = "SELECT u.userid, u.email, u.firstname, u.lastname, u.role, u.profile_image 
          FROM users u 
          WHERE u.userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center'>User not found. <a href='../index.php' class='btn btn-primary btn-round mt-3'>Back to Dashboard</a></div>";
    exit;
}

$indexPage = '';

switch ($role) {
    case 'admin':
        $indexPage = '../admin/index.php';
        break;
    case 'teacher':
        $indexPage = '../teacher/index.php';
        break;
    case 'student':
        $indexPage = '../student/index.php';
        break;
    case 'parent':
        $indexPage = '../parent/index.php';
        break;
    default:
        $indexPage = './index.php'; // Default to a general index page if role is not recognized
        break;
}

$user = $result->fetch_assoc();

// Debug statement to check if halaqahid is set
// if (isset($_SESSION['halaqahid'])) {
//     echo "Halaqah ID: " . $_SESSION['halaqahid'];
// } else {
//     echo "Halaqah ID not set in session.";
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>TasmikPro</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <style>
        .no-link-style {
            color: inherit;
            text-decoration: none;
        }
    </style>
    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["../assets/css/fonts.min.css"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />

    <!-- Custom CSS for hover effect -->
    <link rel="stylesheet" href="../assets/css/demo.css" />
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <!-- Logo Header -->
                <div class="logo-header" data-background-color="dark">
                    <a href="<?php echo $indexPage; ?>" class="logo">
                        <!-- <img src="../assets/img/TasmiPro/TasmikPro.png" alt="navbar brand" class="navbar-brand"
                            height="50" /> -->
                        <img src="../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
                            height="20" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>
                </div>
                <!-- End Logo Header -->
            </div>
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
                        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == basename($indexPage) ? 'active' : ''; ?>">
                            <a href="<?php echo $indexPage; ?>">
                                <i class="fas fa-home"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../announcement/announcement.php' ? 'active' : ''; ?>">
                            <a href="../announcement/announcement.php">
                                <i class="fas fa-bullhorn"></i>
                                <p>Announcement</p>
                            </a>
                        </li>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Components</h4>
                        </li>
                        <?php if ($user['role'] == 'admin') { ?>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../admin/student_progress.php' ? 'active' : ''; ?>">
                                <a href="../admin/student_progress.php">
                                    <i class="fas fa-chart-line"></i> <!-- Icon for Student Progress -->
                                    <p>Student Progress</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../admin/manage_student.php' ? 'active' : ''; ?>">
                                <a href="../admin/manage_student.php">
                                    <i class="fas fa-user-graduate"></i> <!-- Icon for Manage Student -->
                                    <p>Manage Student</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../admin/manage_teacher.php' ? 'active' : ''; ?>">
                                <a href="../admin/manage_teacher.php">
                                    <i class="fas fa-chalkboard-teacher"></i> <!-- Icon for Manage Teacher -->
                                    <p>Manage Teacher</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../admin/manage_halaqah.php' ? 'active' : ''; ?>">
                                <a href="../admin/manage_halaqah.php">
                                    <i class="fas fa-users"></i> <!-- Icon for Manage Halaqah -->
                                    <p>Manage Halaqah</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../teacher/manage_document.php' ? 'active' : ''; ?>">
                                <a href="../teacher/manage_document.php">
                                    <i class="fas fa-file-alt"></i> <!-- Icon for Manage Document -->
                                    <p>Manage Document</p>
                                </a>
                            </li>
                        <?php } elseif ($user['role'] == 'teacher') { ?>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../teacher/student_progress.php' ? 'active' : ''; ?>">
                                <a href="../teacher/student_progress.php">
                                    <i class="fas fa-chart-line"></i> <!-- Icon for Student Progress -->
                                    <p>Student Progress</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../teacher/manage_tasmik.php' ? 'active' : ''; ?>">
                                <a href="../teacher/manage_tasmik.php">
                                    <i class="fas fa-tasks"></i> <!-- Icon for Manage Tasmik -->
                                    <p>Manage Tasmik</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../teacher/manage_student.php' ? 'active' : ''; ?>">
                                <a href="../teacher/manage_student.php">
                                    <i class="fas fa-user-graduate"></i> <!-- Icon for Manage Student -->
                                    <p>Manage Student</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../vle-portal/vle_teacher.php' ? 'active' : ''; ?>">
                                <a href="../vle-portal/vle_teacher.php">
                                    <i class="fas fa-chalkboard-teacher"></i> <!-- Icon for Manage VLE -->
                                    <p>Manage Virtual Learning</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../teacher/live_conference.php' ? 'active' : ''; ?>">
                                <a href="../teacher/live_conference.php">
                                    <i class="fas fa-video"></i> <!-- Icon for Live Conference -->
                                    <p>Live Conference</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../teacher/manage_document.php' ? 'active' : ''; ?>">
                                <a href="../teacher/manage_document.php">
                                    <i class="fas fa-file-alt"></i> <!-- Icon for Manage Document -->
                                    <p>Manage Document</p>
                                </a>
                            </li>
                        <?php } elseif ($user['role'] == 'student') { ?>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../student/tasmik_form.php' ? 'active' : ''; ?>">
                                <a href="../student/tasmik_form.php">
                                    <i class="fas fa-book"></i> <!-- Icon for Tasmik -->
                                    <p>Tasmik</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../vle-portal/vle_student.php' ? 'active' : ''; ?>">
                                <a href="../vle-portal/vle_student.php">
                                    <i class="fas fa-chalkboard-teacher"></i> <!-- Icon for VLE -->
                                    <p>Virtual Learning</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../student/tasmik_progress.php' ? 'active' : ''; ?>">
                                <a href="../student/tasmik_progress.php">
                                    <i class="fas fa-chart-line"></i> <!-- Icon for Tasmik Progress -->
                                    <p>Tasmik Progress</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../student/recorded_session.php' ? 'active' : ''; ?>">
                                <a href="../student/recorded_session.php">
                                    <i class="fas fa-video"></i> <!-- Icon for Recorded Session -->
                                    <p>Recorded Session</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../student/document.php' ? 'active' : ''; ?>">
                                <a href="../student/document.php">
                                    <i class="fas fa-file-alt"></i> <!-- Icon for Document -->
                                    <p>Document</p>
                                </a>
                            </li>
                        <?php } elseif ($user['role'] == 'parent') { ?>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../parent/student_progress.php' ? 'active' : ''; ?>">
                                <a href="../parent/student_progress.php">
                                    <i class="fas fa-chart-line"></i> <!-- Icon for Student Progress -->
                                    <p>Student Progress</p>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '../student/document.php' ? 'active' : ''; ?>">
                                <a href="../student/document.php">
                                    <i class="fas fa-file-alt"></i> <!-- Icon for Document -->
                                    <p>Document</p>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="dark">
                        <a href="<?php echo $indexPage; ?>" class="logo">
                            <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
                                height="20" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                    <!-- End Logo Header -->
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <!-- Breadcrumb -->
                        <nav aria-label="breadcrumb" class="d-none d-lg-block">
                            <ol class="breadcrumb" style="display: flex; margin-top: 20px; color: black; text-decoration: none;">
                                <?php
                                $breadcrumbItems = explode(' / ', $breadcrumb);
                                echo "<li class='nav-home'><a href='$indexPage' class='no-link-style' style='color: black;'><i class='icon-home'>&nbsp;&nbsp;</i></a></li>";
                                foreach ($breadcrumbItems as $index => $item) {
                                    echo "<li class='separator' style='color: black;'><i class='icon-arrow-right'>&nbsp;</i></li>";
                                    if ($index == count($breadcrumbItems) - 1) {
                                        echo "<li class='nav-item active' aria-current='page' style='color: black;'>&nbsp;$item&nbsp;&nbsp;</li>";
                                    } else {
                                        echo "<li class='nav-item' style='color: black;'>&nbsp;$item&nbsp;&nbsp;</li>";
                                    }
                                }
                                ?>
                            </ol>
                        </nav>
                        <!-- End Breadcrumb -->
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-bell"></i>
                                    <span class="notification">1</span>
                                </a>
                                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                                    <li>
                                        <div class="dropdown-title">
                                            You have 4 new notifications
                                        </div>
                                    </li>
                                    <li>
                                        <div class="notif-scroll scrollbar-outer">
                                            <div class="notif-center">
                                                <!-- 

                                                    Notification Item KIV
                                                
                                                -->
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <a class="see-all" href="javascript:void(0);">See all notifications<i
                                                class="fa fa-angle-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="../assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                                    </div>
                                    <span class="profile-username">
                                        <!-- <span class="op-7">Hi,</span> -->
                                        <span class="fw-bold"> <?php echo ucwords($user['role']); ?>, <?php echo ucwords($user['firstname']); ?></span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn" style="background-color: white;">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box" style="background-color: white;">
                                                <div class="avatar-lg" style="background-color: white;">
                                                    <img src="../assets/img/profile.jpg" alt="image profile"
                                                        class="avatar-img rounded" />
                                                </div>
                                                <div class="u-text" style="background-color: white;">
                                                    <h4><?php echo ucwords($user['firstname']); ?></h4>
                                                    <p class="text-muted"><?php echo $user['email']; ?></p>
                                                    <a href="../parent/view_profile.php" class="btn btn-xs btn-secondary btn-sm"">View Profile</a>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <!-- <div class=" dropdown-divider">
                                                </div> -->
                                                <!-- <a class="dropdown-item" href="#">Account Setting</a> -->
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#" onclick="confirmLogout(event)">Logout</a>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>