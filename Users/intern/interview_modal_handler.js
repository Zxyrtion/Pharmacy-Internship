// Interview Modal Handler for Intern Dashboard

// Handle notification click to show interview details
document.addEventListener('DOMContentLoaded', function() {
    // Handle notification item clicks
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            const notificationType = this.dataset.type;
            const relatedId = this.dataset.relatedId;
            
            // Mark notification as read
            markNotificationAsRead(notificationId);
            
            // Handle different notification types
            if (notificationType === 'interview_scheduled') {
                // Load and show interview details
                loadInterviewDetails(relatedId);
            } else if (notificationType === 'internship_schedule' || notificationType === 'internship_approved') {
                // Redirect to schedule view or show schedule modal
                window.location.href = 'view_schedule.php';
            }
            
            // Close notification dropdown
            document.getElementById('notificationDropdown').classList.remove('show');
        });
    });
});

// Mark notification as read
function markNotificationAsRead(notificationId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Load interview details and show modal
function loadInterviewDetails(applicationId) {
    fetch('get_interview_details.php?application_id=' + applicationId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.interview) {
                showInterviewModal(data.interview);
            } else {
                alert('Unable to load interview details.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading interview details.');
        });
}

// Show interview modal with details
function showInterviewModal(interview) {
    // Populate modal with interview data
    document.getElementById('modal_interview_date').textContent = interview.date_formatted;
    document.getElementById('modal_interview_time').textContent = interview.time_formatted;
    
    // Set interview type
    const typeContainer = document.getElementById('modal_interview_type');
    if (interview.type === 'personal') {
        typeContainer.innerHTML = '<span class="badge bg-primary"><i class="bi bi-person-fill"></i> Personal Interview</span>';
        document.getElementById('modal_location_section').style.display = 'block';
        document.getElementById('modal_online_section').style.display = 'none';
        document.getElementById('modal_interview_location').textContent = interview.location || 'N/A';
    } else {
        typeContainer.innerHTML = '<span class="badge bg-success"><i class="bi bi-camera-video-fill"></i> Online Interview</span>';
        document.getElementById('modal_location_section').style.display = 'none';
        document.getElementById('modal_online_section').style.display = 'block';
        document.getElementById('modal_meeting_link').href = interview.meeting_link;
        document.getElementById('modal_meeting_link_text').textContent = interview.meeting_link;
    }
    
    // Set notes
    const notesSection = document.getElementById('modal_notes_section');
    if (interview.notes) {
        notesSection.style.display = 'block';
        document.getElementById('modal_interview_notes').textContent = interview.notes;
    } else {
        notesSection.style.display = 'none';
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('interviewDetailsModal'));
    modal.show();
}
