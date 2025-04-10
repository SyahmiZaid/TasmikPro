document.addEventListener('DOMContentLoaded', function() {
    // Toggle role between Teacher and Student for demonstration
    const userRole = document.getElementById('user-role');
    userRole.addEventListener('click', function() {
        if (userRole.textContent === 'Teacher') {
            userRole.textContent = 'Student';
        } else {
            userRole.textContent = 'Teacher';
        }
    });
    
    // Initialize local video stream
    let localStream = null;
    const localVideo = document.getElementById('localVideo');
    
    // Media controls functionality
    const micBtn = document.getElementById('micBtn');
    const videoBtn = document.getElementById('videoBtn');
    const shareBtn = document.getElementById('shareBtn');
    const recordBtn = document.getElementById('recordBtn');
    const endCallBtn = document.getElementById('endCallBtn');
    
    // Request camera and microphone permissions
    async function initMedia() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            localVideo.srcObject = localStream;
        } catch (err) {
            console.error('Error accessing media devices:', err);
            alert('Could not access camera or microphone. Please check your permissions.');
        }
    }
    
    // Initialize media when page loads
    initMedia();
    
    // Mute/unmute functionality
    micBtn.addEventListener('click', function() {
        if (localStream) {
            const audioTracks = localStream.getAudioTracks();
            if (audioTracks.length > 0) {
                const enabled = !audioTracks[0].enabled;
                audioTracks[0].enabled = enabled;
                
                if (enabled) {
                    micBtn.innerHTML = '<i class="fas fa-microphone"></i><span class="tooltiptext">Mute</span>';
                } else {
                    micBtn.innerHTML = '<i class="fas fa-microphone-slash"></i><span class="tooltiptext">Unmute</span>';
                }
            }
        }
    });
    
    // Video on/off functionality
    videoBtn.addEventListener('click', function() {
        if (localStream) {
            const videoTracks = localStream.getVideoTracks();
            if (videoTracks.length > 0) {
                const enabled = !videoTracks[0].enabled;
                videoTracks[0].enabled = enabled;
                
                if (enabled) {
                    videoBtn.innerHTML = '<i class="fas fa-video"></i><span class="tooltiptext">Turn off video</span>';
                } else {
                    videoBtn.innerHTML = '<i class="fas fa-video-slash"></i><span class="tooltiptext">Turn on video</span>';
                }
            }
        }
    });
    
    // Screen sharing simulation
    shareBtn.addEventListener('click', function() {
        alert('Screen sharing requested. This feature requires additional setup in a real application.');
    });
    
    // Recording simulation
    let isRecording = false;
    recordBtn.addEventListener('click', function() {
        isRecording = !isRecording;
        
        if (isRecording) {
            recordBtn.style.backgroundColor = '#dc3545';
            recordBtn.innerHTML = '<i class="fas fa-stop-circle"></i><span class="tooltiptext">Stop recording</span>';
            alert('Recording started. (Simulated)');
        } else {
            recordBtn.style.backgroundColor = '';
            recordBtn.innerHTML = '<i class="fas fa-record-vinyl"></i><span class="tooltiptext">Start recording</span>';
            alert('Recording stopped. (Simulated)');
        }
    });
    
    // End call simulation
    endCallBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to end the conference?')) {
            alert('Conference ended. Thank you for participating.');
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
        }
    });
    
    // Star rating functionality
    const stars = document.querySelectorAll('.star-rating i');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            
            // Reset all stars
            stars.forEach(s => s.className = 'far fa-star');
            
            // Fill stars up to the clicked one
            for (let i = 0; i < rating; i++) {
                stars[i].className = 'fas fa-star';
            }
        });
    });
    
    // Save feedback button
    const saveFeedbackBtn = document.getElementById('save-feedback');
    const studentSelect = document.getElementById('student-select');
    const feedbackText = document.getElementById('feedback-text');
    
    saveFeedbackBtn.addEventListener('click', function() {
        const selectedStudent = studentSelect.options[studentSelect.selectedIndex].text;
        const feedback = feedbackText.value.trim();
        
        if (studentSelect.value === '' || feedback === '') {
            alert('Please select a student and provide feedback.');
            return;
        }
        
        // Count filled stars to get rating
        const filledStars = document.querySelectorAll('.star-rating i.fas').length;
        
        alert(`Feedback for ${selectedStudent} saved successfully with a rating of ${filledStars}/5!`);
        
        // In a real application, this would send the data to a server
        // Here we just reset the form
        studentSelect.value = '';
        feedbackText.value = '';
        stars.forEach(s => s.className = 'far fa-star');
    });
    
    // Chat input functionality
    const chatInput = document.querySelector('.chat-input input');
    const chatSendBtn = document.querySelector('.chat-input button');
    const chatMessages = document.querySelector('.chat-messages');
    
    function sendMessage() {
        const message = chatInput.value.trim();
        if (message === '') return;
        
        const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        const messageHTML = `
            <div class="message outgoing">
                <div class="message-content">
                    ${message}
                    <div class="message-meta">You - ${currentTime}</div>
                </div>
            </div>
        `;
        
        chatMessages.insertAdjacentHTML('beforeend', messageHTML);
        chatInput.value = '';
        
        // Scroll to bottom of chat
    }
} );