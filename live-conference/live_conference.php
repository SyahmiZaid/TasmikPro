<?php
$pageTitle = "Live Conference";
$breadcrumb = "Pages / Schedule Live Conference / Live Conference";
include '../include/header.php';

// Get the meeting link from the query parameter
if (!isset($_GET['room'])) {
    die("Error: Meeting link not specified.");
}
$meetingLink = htmlspecialchars($_GET['room']);

// Extract the room name from the full meeting link
$parsedUrl = parse_url($meetingLink);
$roomName = ltrim($parsedUrl['path'], '/'); // Extract the room name

// Debugging: Output the room name
echo "<!-- Debug: Room Name = $roomName -->";
?>

<!-- Include SweetAlert and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://meet.jit.si/external_api.js"></script>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <!-- Jitsi Meeting Embed -->
            <div id="jitsi-meeting-container" style="width: 100%; height: 600px;"></div>
        </div>

        <script type="text/javascript">
            // Initialize the Jitsi Meet API and embed the meeting
            const domain = 'meet.jit.si';
            const options = {
                roomName: '<?php echo $roomName; ?>', // Use the extracted room name
                width: '100%',
                height: 600,
                parentNode: document.querySelector('#jitsi-meeting-container'),
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false,
                },
                interfaceConfigOverwrite: {
                    filmStripOnly: false,
                },
            };

            const api = new JitsiMeetExternalAPI(domain, options);
            console.log('Jitsi API initialized:', api); // Debugging: Check if API is initialized

            // Add a custom "End Meeting" button
            api.addListener('videoConferenceJoined', () => {
                console.log('videoConferenceJoined event triggered'); // Debugging: Check if event is triggered

                const endMeetingButton = document.createElement('button');
                endMeetingButton.innerText = 'End Meeting';
                endMeetingButton.style.position = 'absolute';
                endMeetingButton.style.top = '10px';
                endMeetingButton.style.right = '10px';
                endMeetingButton.style.zIndex = '1000';
                endMeetingButton.style.padding = '10px 20px';
                endMeetingButton.style.backgroundColor = '#ff0000';
                endMeetingButton.style.color = '#fff';
                endMeetingButton.style.border = 'none';
                endMeetingButton.style.cursor = 'pointer';

                endMeetingButton.addEventListener('click', () => {
                    console.log('End Meeting button clicked');

                    // Get meeting details from URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const roomParam = urlParams.get('room');
                    
                    // Extract meeting ID from the room name if possible
                    let meetingId = '';
                    if (roomParam && roomParam.includes('tasmik_room_')) {
                        meetingId = roomParam.split('tasmik_room_')[1];
                    }

                    Swal.fire({
                        title: 'End Meeting & Provide Feedback',
                        html: `
                            <div class="form-group mb-3">
                                <label for="tasmik-status" class="text-left d-block mb-1">Tasmik Status:</label>
                                <select id="tasmik-status" class="form-control">
                                    <option value="accepted">Accepted</option>
                                    <option value="repeated">Repeated</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="feedback-text" class="text-left d-block mb-1">Feedback:</label>
                                <textarea id="feedback-text" class="form-control" rows="4" placeholder="Provide feedback about the student's recitation..."></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Submit & End Meeting',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        preConfirm: () => {
                            return {
                                status: document.getElementById('tasmik-status').value,
                                feedback: document.getElementById('feedback-text').value
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: 'Submitting feedback...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Notify all participants that the meeting is ending
                            api.executeCommand('sendEndpointTextMessage', '', 'The meeting has ended. Please leave.');
                            
                            // Get the meeting ID and tasmik ID from the query parameters or sessionStorage
                            const urlParams = new URLSearchParams(window.location.search);
                            const roomParam = urlParams.get('room');
                            
                            // Send feedback and status to server via AJAX
                            $.ajax({
                                url: 'update_tasmik_status.php',
                                method: 'POST',
                                data: {
                                    meetingLink: roomParam,
                                    status: result.value.status,
                                    feedback: result.value.feedback
                                },
                                dataType: 'json',
                                success: function(response) {
                                    // Disconnect the moderator
                                    api.executeCommand('hangup');
                                    
                                    if (response.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Feedback Submitted',
                                            text: 'Tasmik status has been updated successfully.',
                                            confirmButtonColor: '#28a745'
                                        }).then(() => {
                                            // Redirect back to the schedule page
                                            window.location.href = 'live_conference_schedule.php';
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.message || 'Failed to update tasmik status.',
                                            confirmButtonColor: '#dc3545'
                                        }).then(() => {
                                            // Redirect anyway
                                            window.location.href = 'live_conference_schedule.php';
                                        });
                                    }
                                },
                                error: function() {
                                    // Disconnect the moderator
                                    api.executeCommand('hangup');
                                    
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'An error occurred while submitting feedback. Please try again.',
                                        confirmButtonColor: '#dc3545'
                                    }).then(() => {
                                        window.location.href = 'live_conference_schedule.php';
                                    });
                                }
                            });
                        }
                    });
                });

                console.log('Adding End Meeting button'); // Debugging: Check if button is being added
                document.body.appendChild(endMeetingButton);
            });

            // Check if the current user is a moderator
            api.addListener('videoConferenceJoined', () => {
                api.isModerator().then(isModerator => {
                    if (isModerator) {
                        console.log('You are the moderator.'); // Debugging: Check moderator status
                    } else {
                        console.log('You are not the moderator.'); // Debugging: Check moderator status
                    }
                }).catch(error => {
                    console.error('Error checking moderator status:', error); // Debugging: Log errors
                });
            });

            // Debugging: Log participants info
            api.addListener('participantJoined', participant => {
                console.log('Participant joined:', participant); // Debugging: Log participant details
            });

            api.addListener('participantLeft', participant => {
                console.log('Participant left:', participant); // Debugging: Log participant details
            });
        </script>
    </div>
</div>

<?php include '../include/footer.php'; ?>