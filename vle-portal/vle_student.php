<?php $pageTitle = "VLE"; 
$breadcrumb = "Pages / VLE"; 
include '../include/header.php'; 
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        
        <!-- VLE Dashboard -->
        <div class="row">
            <!-- Welcome Message -->
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Welcome to Your Learning Portal</h4>
                    </div>
                    <div class="card-body">
                        <p>Welcome back, <?php echo isset($userName) ? $userName : 'Student'; ?>! You have <span class="badge badge-info">3</span> upcoming deadlines and <span class="badge badge-warning">5</span> new messages.</p>
                    </div>
                </div>
            </div>
            
            <!-- Course Progress -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Your Progress</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Hifz Al Quran</span>
                            <span>75%</span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Maharat Al Quran</span>
                            <span>45%</span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <!-- <div class="d-flex justify-content-between mb-3">
                            <span>Database Design</span>
                            <span>30%</span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                        </div> -->
                    </div>
                </div>
            </div>
            
            <!-- Calendar -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h4 class="card-title">Upcoming Deadlines</h4>
                        <button class="btn btn-sm btn-primary"><i class="fas fa-calendar-alt mr-1"></i> View Calendar</button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Tasmik: Surah Al-Baqarah</h6>
                                    <small class="text-muted">Hifz Al Quran</small>
                                </div>
                                <span class="badge badge-danger">2 days left</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Quiz: Qiraat</h6>
                                    <small class="text-muted">Maharat Al Quran</small>
                                </div>
                                <span class="badge badge-warning">5 days left</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Assignment: Tahriri</h6>
                                    <small class="text-muted">Hifz Al Quran</small>
                                </div>
                                <span class="badge badge-info">1 week left</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Courses Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">My Courses</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            // Sample courses array - in a real implementation, this would come from a database
                            $courses = [
                                [
                                    'title' => 'Hifz Al Quran',
                                    'instructor' => 'Ustaz Abdul Rahman',
                                    'image' => '../assets/img/courses/programming.jpg',
                                    'progress' => 75,
                                    'color' => 'success'
                                ],
                                [
                                    'title' => 'Maharat Al Quran',
                                    'instructor' => 'Ustazah Aisha, Ustazah Maryam',
                                    'image' => '../assets/img/courses/webdev.jpg',
                                    'progress' => 45,
                                    'color' => 'primary'
                                ],
                                // [
                                //     'title' => 'Database Design',
                                //     'instructor' => 'Dr. Robert Johnson',
                                //     'image' => '../assets/img/courses/database.jpg',
                                //     'progress' => 30,
                                //     'color' => 'info'
                                // ],
                                // [
                                //     'title' => 'UX Design Principles',
                                //     'instructor' => 'Sarah Williams',
                                //     'image' => '../assets/img/courses/uxdesign.jpg',
                                //     'progress' => 10,
                                //     'color' => 'warning'
                                // ]
                            ];
                            
                            // Display courses
                            foreach($courses as $course) {
                                ?>
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo $course['image']; ?>" class="card-img-top" alt="<?php echo $course['title']; ?>" onerror="this.src='../assets/img/blogpost.jpg'">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $course['title']; ?></h5>
                                            <p class="card-text text-muted">Instructor: <?php echo $course['instructor']; ?></p>
                                            <div class="progress mt-3 mb-1" style="height: 5px;">
                                                <div class="progress-bar bg-<?php echo $course['color']; ?>" role="progressbar" style="width: <?php echo $course['progress']; ?>%" aria-valuenow="<?php echo $course['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $course['progress']; ?>% Complete</small>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="#" class="btn btn-primary btn-sm btn-block"><i class="fas fa-play mr-1"></i> Continue Learning</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Learning Resources and Forums -->
        <div class="row mt-4">
            <!-- Resources -->
            <!-- <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Learning Resources</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-file-pdf mr-3 text-danger"></i>
                                <div>
                                    <h6 class="mb-0">Introduction to JavaScript</h6>
                                    <small class="text-muted">PDF Document • 2.4MB</small>
                                </div>
                                <a href="#" class="btn btn-sm btn-outline-primary ml-auto"><i class="fas fa-download mr-1"></i> Download</a>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-video mr-3 text-primary"></i>
                                <div>
                                    <h6 class="mb-0">CSS Grid Tutorial</h6>
                                    <small class="text-muted">Video • 18:34</small>
                                </div>
                                <a href="#" class="btn btn-sm btn-outline-primary ml-auto"><i class="fas fa-play mr-1"></i> Watch</a>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-link mr-3 text-info"></i>
                                <div>
                                    <h6 class="mb-0">SQL Basics Cheat Sheet</h6>
                                    <small class="text-muted">External Resource</small>
                                </div>
                                <a href="#" class="btn btn-sm btn-outline-primary ml-auto"><i class="fas fa-external-link-alt mr-1"></i> Visit</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div> -->
            
            <!-- Forums -->
            <!-- <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h4 class="card-title">Recent Forum Discussions</h4>
                        <button class="btn btn-sm btn-primary"><i class="fas fa-comments mr-1"></i> All Forums</button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Help with JavaScript Functions</h6>
                                    <span class="badge badge-primary">5 replies</span>
                                </div>
                                <small class="text-muted">Posted by Alex T. • 2 hours ago</small>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Database Normalization Question</h6>
                                    <span class="badge badge-primary">12 replies</span>
                                </div>
                                <small class="text-muted">Posted by Maria L. • 1 day ago</small>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-0">Group Project Teams Formation</h6>
                                    <span class="badge badge-primary">8 replies</span>
                                </div>
                                <small class="text-muted">Posted by Instructor • 3 days ago</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div> -->
        </div>
        
        <!-- Announcements Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Announcements</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-bullhorn mr-2"></i> System Maintenance</h5>
                            <p>The VLE will be unavailable on Sunday, March 16th from 2:00 AM to 4:00 AM for scheduled maintenance.</p>
                            <small class="text-muted">Posted: March 14, 2025</small>
                        </div>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-calendar-alt mr-2"></i> New Course Available</h5>
                            <p>A new course on "Advanced Data Visualization" is now open for enrollment. Limited spots available!</p>
                            <small class="text-muted">Posted: March 10, 2025</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>