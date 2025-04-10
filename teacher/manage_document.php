<?php
$pageTitle = "Manage Document";
$breadcrumb = "Pages / Manage Document";
include '../include/header.php';
require_once __DIR__ . '/../database/db_connection.php'; // Corrected file path

// Ensure the uploads directory exists and has the correct permissions
$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
} else {
    chmod($uploadsDir, 0777); // Ensure the directory has the correct permissions
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    $documentName = $_FILES['document']['name'];
    $documentTmpName = $_FILES['document']['tmp_name'];
    $documentSize = $_FILES['document']['size'];
    $documentError = $_FILES['document']['error'];
    $documentType = $_FILES['document']['type'];

    $documentExt = explode('.', $documentName);
    $documentActualExt = strtolower(end($documentExt));

    $allowed = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx');

    if (in_array($documentActualExt, $allowed)) {
        if ($documentError === 0) {
            if ($documentSize < 10000000) { // 10MB limit
                $documentNewName = uniqid('', true) . "." . $documentActualExt;
                $documentDestination = $uploadsDir . '/' . $documentNewName;
                if (move_uploaded_file($documentTmpName, $documentDestination)) {
                    // Generate unique document ID
                    $document_prefix = "DOC";
                    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(documentid, 4) AS UNSIGNED)) AS max_id FROM document");
                    $stmt->execute();
                    $stmt->bind_result($max_id);
                    $stmt->fetch();
                    $stmt->close();
                    $new_id = $max_id + 1;
                    $document_id = $document_prefix . str_pad($new_id, 3, '0', STR_PAD_LEFT);

                    // Insert document info into the database
                    $stmt = $conn->prepare("INSERT INTO document (documentid, userid, name, path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssss", $document_id, $_SESSION['userid'], $documentName, $documentDestination);
                    if ($stmt->execute()) {
                        $success_message = "Document uploaded successfully.";
                    } else {
                        $error_message = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Failed to move uploaded file.";
                }
            } else {
                $error_message = "Your file is too big!";
            }
        } else {
            $error_message = "There was an error uploading your file!";
        }
    } else {
        $error_message = "You cannot upload files of this type!";
    }
}

// Fetch documents from the database
$documents = $conn->query("SELECT * FROM document ORDER BY uploaded_at DESC");
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <!-- Document Upload Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Upload Document</h4>
                    </div>
                    <div class="card-body">
                        <div id="message"></div>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php elseif (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="document">Choose Document:</label>
                                <input type="file" class="form-control" id="document" name="document" required>
                                <button type="button" class="btn btn-primary" style="margin-top: 10px;" onclick="uploadDocument()">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Document List -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Documents List</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="documentsTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th style="background-color: #343a40; color: white;">Name</th>
                                        <th style="background-color: #343a40; color: white;">Uploaded At</th>
                                        <th style="background-color: #343a40; color: white;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($document = $documents->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($document['name']); ?></td>
                                            <td><?php echo htmlspecialchars($document['uploaded_at']); ?></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars(str_replace(__DIR__, '', $document['path'])); ?>" class="btn btn-info btn-sm" target="_blank">View</a>
                                                <a href="download_document.php?path=<?php echo urlencode($document['path']); ?>" class="btn btn-success btn-sm">Download</a>
                                                <button class="btn btn-danger btn-sm" onclick="deleteDocument('<?php echo $document['documentid']; ?>')">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resources Management Section -->
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Learning Resources</h4>
                                <div>
                                    <button class="btn btn-sm btn-success mr-2"><i class="fas fa-plus mr-1"></i> Add Resource</button>
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-file-export mr-1"></i> Export List</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="resourceTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="hifz-tab" data-toggle="tab" href="#hifz-resources" role="tab" aria-controls="hifz-resources" aria-selected="true">Hifz Al Quran</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="maharat-tab" data-toggle="tab" href="#maharat-resources" role="tab" aria-controls="maharat-resources" aria-selected="false">Maharat Al Quran</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="common-tab" data-toggle="tab" href="#common-resources" role="tab" aria-controls="common-resources" aria-selected="false">Common Resources</a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-3" id="resourceTabsContent">
                                    <div class="tab-pane fade show active" id="hifz-resources" role="tabpanel" aria-labelledby="hifz-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Resource Title</th>
                                                        <th>Type</th>
                                                        <th>Created Date</th>
                                                        <th>Visibility</th>
                                                        <th>Downloads</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Surah Al-Baqarah Memorization Guide</td>
                                                        <td><span class="badge badge-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</span></td>
                                                        <td>Feb 12, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>87</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Memorization Techniques Video</td>
                                                        <td><span class="badge badge-primary"><i class="fas fa-video mr-1"></i> Video</span></td>
                                                        <td>Mar 5, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>124</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tahriri Submission Guidelines</td>
                                                        <td><span class="badge badge-info"><i class="fas fa-file-word mr-1"></i> Document</span></td>
                                                        <td>Mar 18, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>56</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="maharat-resources" role="tabpanel" aria-labelledby="maharat-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Resource Title</th>
                                                        <th>Type</th>
                                                        <th>Created Date</th>
                                                        <th>Visibility</th>
                                                        <th>Downloads</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Tajweed Rules Reference Guide</td>
                                                        <td><span class="badge badge-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</span></td>
                                                        <td>Jan 25, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>153</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Qiraat Demonstration Audio</td>
                                                        <td><span class="badge badge-warning"><i class="fas fa-file-audio mr-1"></i> Audio</span></td>
                                                        <td>Feb 18, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>98</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="common-resources" role="tabpanel" aria-labelledby="common-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Resource Title</th>
                                                        <th>Type</th>
                                                        <th>Created Date</th>
                                                        <th>Visibility</th>
                                                        <th>Downloads</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Student Handbook 2025</td>
                                                        <td><span class="badge badge-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</span></td>
                                                        <td>Jan 10, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>234</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Academic Calendar</td>
                                                        <td><span class="badge badge-info"><i class="fas fa-calendar mr-1"></i> Calendar</span></td>
                                                        <td>Jan 5, 2025</td>
                                                        <td><span class="badge badge-success">Public</span></td>
                                                        <td>189</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></button>
                                                                <button type="button" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
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

<?php
$conn->close();
?>

<script>
    function uploadDocument() {
        var form = document.getElementById('uploadForm');
        var formData = new FormData(form);

        fetch('manage_document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Handle the response from the server
                console.log(data);
                document.getElementById('message').innerHTML = data;
                location.reload(); // Reload the page to show the uploaded document
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function deleteDocument(documentId) {
        if (confirm('Are you sure you want to delete this document?')) {
            fetch('delete_document.php?id=' + documentId)
                .then(response => response.text())
                .then(data => {
                    // Handle the response from the server
                    console.log(data);
                    document.getElementById('message').innerHTML = data;
                    location.reload(); // Reload the page to show the updated document list
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    }

    $(document).ready(function() {
        $('#documentsTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });
    });
</script>