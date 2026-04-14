<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Schedule & Location - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .schedule-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .schedule-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .info-section {
            padding: 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            color: #212529;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .reminder-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .btn-print {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-print:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="schedule-card">
            <div class="schedule-header">
                <div class="mb-3">
                    <i class="bi bi-check-circle" style="font-size: 4rem;"></i>
                </div>
                <h1 class="mb-2">Internship Schedule & Location Details</h1>
                <p class="mb-0">Your internship has been approved! Here are your details.</p>
            </div>
            
            <div class="info-section">
                <div class="row">
                    <!-- Schedule Column -->
                    <div class="col-md-6">
                        <h4 class="mb-4">
                            <i class="bi bi-clock-history text-success"></i> Internship Schedule
                        </h4>
                        
                        <div class="info-card">
                            <div class="info-label">
                                <i class="bi bi-calendar-event"></i> Start Date
                            </div>
                            <div class="info-value" id="start_date">Loading...</div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">
                                <i class="bi bi-hourglass-split"></i> Duration
                            </div>
                            <div class="info-value" id="duration">Loading...</div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">
                                <i class="bi bi-calendar-week"></i> Working Days
                            </div>
                            <div class="info-value" id="working_days">Loading...</div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">
                                <i class="bi bi-alarm"></i> Working Hours
                            </div>
                            <div class="info-value" id="working_hours">Loading...</div>
                        </div>
                    </div>
                    
                    <!-- Location Column -->
                    <div class="col-md-6">
                        <h4 class="mb-4">
                            <i class="bi bi-geo-alt-fill text-primary"></i> Location Details
                        </h4>
                        
                        <div class="info-card" style="border-left-color: #007bff;">
                            <div class="info-label">
                                <i class="bi bi-hospital"></i> Pharmacy Name
                            </div>
                            <div class="info-value" id="pharmacy_name">Loading...</div>
                        </div>
                        
                        <div class="info-card" style="border-left-color: #007bff;">
                            <div class="info-label">
                                <i class="bi bi-pin-map"></i> Address
                            </div>
                            <div class="info-value" id="pharmacy_address">Loading...</div>
                        </div>
                        
                        <div class="info-card" style="border-left-color: #007bff;">
                            <div class="info-label">
                                <i class="bi bi-person-badge"></i> Contact Person
                            </div>
                            <div class="info-value" id="contact_person">Loading...</div>
                        </div>
                        
                        <div class="info-card" style="border-left-color: #007bff;">
                            <div class="info-label">
                                <i class="bi bi-telephone"></i> Contact Number
                            </div>
                            <div class="info-value" id="contact_number">Loading...</div>
                        </div>
                        
                        <div class="info-card" style="border-left-color: #007bff;">
                            <div class="info-label">
                                <i class="bi bi-envelope"></i> Email
                            </div>
                            <div class="info-value" id="contact_email">Loading...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Special Instructions -->
                <div class="reminder-box" id="special_instructions_box" style="display: none;">
                    <h5 class="mb-3">
                        <i class="bi bi-exclamation-triangle"></i> Important Reminders
                    </h5>
                    <ul class="mb-0">
                        <li>Bring a valid ID and your approval letter on your first day</li>
                        <li>Dress code: Business casual or scrubs (will be provided)</li>
                        <li>Arrive 15 minutes early for orientation</li>
                        <li>Bring a notebook and pen for training sessions</li>
                        <li>Contact HR if you have any questions or concerns</li>
                        <li id="special_instructions_item" style="display: none;"></li>
                    </ul>
                </div>
                
                <!-- Action Buttons -->
                <div class="text-center mt-4 no-print">
                    <button class="btn btn-print me-2" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Details
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary" style="border-radius: 25px; padding: 0.75rem 2rem;">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Get application_id from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const applicationId = urlParams.get('application_id');
        
        if (applicationId) {
            // Fetch schedule details
            fetch(`get_notifications.php?action=get_schedule&application_id=${applicationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.schedule) {
                        const schedule = data.schedule;
                        
                        // Populate schedule fields
                        document.getElementById('start_date').textContent = 
                            new Date(schedule.internship_start_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                        document.getElementById('duration').textContent = schedule.internship_duration;
                        document.getElementById('working_days').textContent = schedule.working_days;
                        document.getElementById('working_hours').textContent = schedule.working_hours;
                        
                        // Populate location fields
                        document.getElementById('pharmacy_name').textContent = schedule.pharmacy_name;
                        document.getElementById('pharmacy_address').textContent = schedule.pharmacy_address;
                        document.getElementById('contact_person').textContent = schedule.contact_person;
                        document.getElementById('contact_number').textContent = schedule.contact_number;
                        document.getElementById('contact_email').textContent = schedule.contact_email;
                        
                        // Show special instructions if available
                        if (schedule.special_instructions) {
                            document.getElementById('special_instructions_item').textContent = schedule.special_instructions;
                            document.getElementById('special_instructions_item').style.display = 'list-item';
                        }
                        document.getElementById('special_instructions_box').style.display = 'block';
                    } else {
                        alert('Failed to load schedule details: ' + (data.error || 'Unknown error'));
                        window.location.href = 'dashboard.php';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading schedule details');
                    window.location.href = 'dashboard.php';
                });
        } else {
            alert('No application ID provided');
            window.location.href = 'dashboard.php';
        }
    </script>
</body>
</html>
