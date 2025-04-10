<?php
$pageTitle = "Dashboard";
$breadcrumb = "Pages / Dashboard";
include './include/header.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="index.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Pages</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Dashboard</a>
                </li>
            </ul>
        </div>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        </div>
        <!-- Add your code here -->
    </div>
</div>

<?php include './include/footer.php'; ?>