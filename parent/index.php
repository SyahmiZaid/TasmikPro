<?php
$pageTitle = "Dashboard";
$breadcrumb = "Pages / <a href='../parent/index.php' class='no-link-style'>Dashboard</a>";
include '../include/header.php';
?>

<div class="container">
    <link rel="stylesheet" href="../assets/css/header-container.css" />
    <div class="page-inner">
        <!-- Custom header section with animation -->
        <div class="custom-header-container">
            <div class="custom-header-bg"></div>
            <div class="custom-header-overlay"></div>
            <div class="custom-header-content">
                <h1 class="custom-header-title">Hello Users!</h1>
                <p class="custom-header-subtitle">We are on a mission to help developers like you build successful projects for FREE.</p>
            </div>
            <button class="announcements-btn" style="margin-top: 45px;">
                <span class="icon">📢</span>
                Announcements
            </button>
        </div>

        <!-- Container for the rest of the content with normal padding -->
        <div class="content-container">
            <!-- Page header moved below custom header -->
            <div class="page-header">
                <!-- <h4 class="page-title"><?php echo $pageTitle; ?></h4> -->
            </div>

            <!-- Add your additional dashboard content here -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Dashboard content -->
                    <div class="row" style="margin-top: -80px; position: relative; z-index: 5;">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="icon-pie-chart text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Number</p>
                                                <h4 class="card-title">150GB</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="icon-wallet text-success"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Revenue</p>
                                                <h4 class="card-title">$ 1,345</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="icon-close text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Errors</p>
                                                <h4 class="card-title">23</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="icon-social-twitter text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Followers</p>
                                                <h4 class="card-title">+45K</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>