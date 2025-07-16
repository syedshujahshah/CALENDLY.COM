<?php
require_once 'db.php';
startSession();

$error = '';
$success = '';
$step = 1;

// Get user from URL parameter
$booking_url = $_GET['user'] ?? '';
$meeting_type_id = $_GET['type'] ?? null;

if (empty($booking_url)) {
    header('Location: index.php');
    exit;
}

// Get host user
$host = db()->fetchOne("SELECT * FROM users WHERE booking_url = ?", [$booking_url]);
if (!$host) {
    header('Location: index.php');
    exit;
}

// Get meeting types
$meeting_types = db()->fetchAll(
    "SELECT * FROM meeting_types WHERE user_id = ? AND is_active = 1", 
    [$host['id']]
);

// Get selected meeting type
$selected_meeting_type = null;
if ($meeting_type_id) {
    $selected_meeting_type = db()->fetchOne(
        "SELECT * FROM meeting_types WHERE id = ? AND user_id = ?", 
        [$meeting_type_id, $host['id']]
    );
}

// Get availability
$availability = db()->fetchAll(
    "SELECT * FROM availability WHERE user_id = ? AND is_available = 1", 
    [$host['id']]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'select_meeting_type') {
            $meeting_type_id = $_POST['meeting_type_id'];
            $selected_meeting_type = db()->fetchOne(
                "SELECT * FROM meeting_types WHERE id = ? AND user_id = ?", 
                [$meeting_type_id, $host['id']]
            );
            $step = 2;
        } elseif ($_POST['action'] === 'book_meeting') {
            $guest_name = sanitize($_POST['guest_name']);
            $guest_email = sanitize($_POST['guest_email']);
            $guest_phone = sanitize($_POST['guest_phone'] ?? '');
            $booking_date = $_POST['booking_date'];
            $start_time = $_POST['start_time'];
            $notes = sanitize($_POST['notes'] ?? '');
            $meeting_type_id = $_POST['meeting_type_id'];
            
            // Validate
            if (empty($guest_name) || empty($guest_email) || empty($booking_date) || empty($start_time)) {
                $error = 'Please fill in all required fields.';
            } else {
                // Calculate end time
                $meeting_type = db()->fetchOne("SELECT * FROM meeting_types WHERE id = ?", [$meeting_type_id]);
                $end_time = date('H:i:s', strtotime($start_time) + ($meeting_type['duration'] * 60));
                
                // Check if slot is available
                $existing = db()->fetchOne(
                    "SELECT id FROM bookings WHERE user_id = ? AND booking_date = ? AND start_time = ? AND status = 'confirmed'",
                    [$host['id'], $booking_date, $start_time]
                );
                
                if ($existing) {
                    $error = 'This time slot is no longer available.';
                } else {
                    // Create booking
                    $booking_id = db()->insert('bookings', [
                        'user_id' => $host['id'],
                        'meeting_type_id' => $meeting_type_id,
                        'guest_name' => $guest_name,
                        'guest_email' => $guest_email,
                        'guest_phone' => $guest_phone,
                        'booking_date' => $booking_date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'notes' => $notes,
                        'status' => 'confirmed'
                    ]);
                    
                    if ($booking_id) {
                        // Send confirmation email (simplified)
                        $subject = "Meeting Confirmed - " . $meeting_type['title'];
                        $message = "
                            <h2>Meeting Confirmed!</h2>
                            <p>Your meeting with " . htmlspecialchars($host['full_name']) . " has been confirmed.</p>
                            <p><strong>Meeting:</strong> " . htmlspecialchars($meeting_type['title']) . "</p>
                            <p><strong>Date:</strong> " . date('F j, Y', strtotime($booking_date)) . "</p>
                            <p><strong>Time:</strong> " . date('g:i A', strtotime($start_time)) . "</p>
                            <p><strong>Duration:</strong> " . $meeting_type['duration'] . " minutes</p>
                        ";
                        
                        sendEmail($guest_email, $subject, $message);
                        
                        $success = 'Meeting booked successfully! Check your email for confirmation.';
                        $step = 3;
                    } else {
                        $error = 'Failed to book meeting. Please try again.';
                    }
                }
            }
        }
    }
}

// Generate available time slots for a given date
function getAvailableSlots($host_id, $date, $meeting_duration, $availability) {
    $day_of_week = strtolower(date('l', strtotime($date)));
    $slots = [];
    
    // Find availability for this day
    $day_availability = null;
    foreach ($availability as $avail) {
        if ($avail['day_of_week'] === $day_of_week) {
            $day_availability = $avail;
            break;
        }
    }
    
    if (!$day_availability) {
        return $slots;
    }
    
    // Generate time slots
    $start = strtotime($day_availability['start_time']);
    $end = strtotime($day_availability['end_time']);
    $duration = $meeting_duration * 60; // Convert to seconds
    
    for ($time = $start; $time < $end; $time += $duration) {
        $slot_time = date('H:i:s', $time);
        
        // Check if slot is already booked
        $existing = db()->fetchOne(
            "SELECT id FROM bookings WHERE user_id = ? AND booking_date = ? AND start_time = ? AND status = 'confirmed'",
            [$host_id, $date, $slot_time]
        );
        
        if (!$existing) {
            $slots[] = $slot_time;
        }
    }
    
    return $slots;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Meeting - <?php echo htmlspecialchars($host['full_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .booking-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .booking-header {
            background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .host-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .host-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .booking-content {
            padding: 2rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .step.active {
            background: #4285f4;
            color: white;
        }

        .step.completed {
            background: #34a853;
            color: white;
        }

        .step.inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }

        .success {
            background: #efe;
            color: #363;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #cfc;
        }

        /* Meeting Type Selection */
        .meeting-types {
            display: grid;
            gap: 1rem;
        }

        .meeting-type-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .meeting-type-card:hover {
            border-color: #4285f4;
            background: #f8fafc;
        }

        .meeting-type-card.selected {
            border-color: #4285f4;
            background: #f0f7ff;
        }

        .meeting-type-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .meeting-type-meta {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Calendar and Time Selection */
        .datetime-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .calendar-section,
        .time-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .calendar-header {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .calendar-day:hover {
            background: #e3f2fd;
        }

        .calendar-day.available {
            background: white;
            border: 1px solid #e2e8f0;
        }

        .calendar-day.selected {
            background: #4285f4;
            color: white;
        }

        .calendar-day.unavailable {
            color: #cbd5e1;
            cursor: not-allowed;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .time-slot {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .time-slot:hover {
            border-color: #4285f4;
            background: #f0f7ff;
        }

        .time-slot.selected {
            background: #4285f4;
            color: white;
            border-color: #4285f4;
        }

        /* Guest Information Form */
        .guest-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #4285f4;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #4285f4;
            color: white;
        }

        .btn-primary:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #4285f4;
            border: 2px solid #4285f4;
        }

        .btn-secondary:hover {
            background: #4285f4;
            color: white;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        /* Success Page */
        .success-page {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .booking-summary {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            font-weight: 600;
            color: #64748b;
        }

        .summary-value {
            color: #1e293b;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .datetime-selection {
                grid-template-columns: 1fr;
            }

            .time-slots {
                grid-template-columns: 1fr;
            }

            .calendar {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="booking-header">
            <div class="host-info">
                <div class="host-avatar">
                    <?php echo strtoupper(substr($host['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <h1><?php echo htmlspecialchars($host['full_name']); ?></h1>
                    <p>Book a meeting</p>
                </div>
            </div>
        </div>

        <div class="booking-content">
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'inactive'; ?>">
                    <span>1</span> Select Meeting Type
                </div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'inactive'; ?>">
                    <span>2</span> Choose Date & Time
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : 'inactive'; ?>">
                    <span>3</span> Enter Details
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- Step 1: Select Meeting Type -->
                <form method="POST">
                    <input type="hidden" name="action" value="select_meeting_type">
                    
                    <h2 style="margin-bottom: 1.5rem; text-align: center;">Select a meeting type</h2>
                    
                    <div class="meeting-types">
                        <?php foreach ($meeting_types as $type): ?>
                            <label class="meeting-type-card">
                                <input type="radio" name="meeting_type_id" value="<?php echo $type['id']; ?>" 
                                       style="display: none;" required>
                                <div class="meeting-type-title"><?php echo htmlspecialchars($type['title']); ?></div>
                                <div class="meeting-type-meta">
                                    ⏱️ <?php echo $type['duration']; ?> minutes
                                    <?php if ($type['description']): ?>
                                        <br><?php echo htmlspecialchars($type['description']); ?>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Continue</button>
                    </div>
                </form>

            <?php elseif ($step === 2): ?>
                <!-- Step 2: Select Date and Time -->
                <form method="POST" id="datetime-form">
                    <input type="hidden" name="action" value="book_meeting">
                    <input type="hidden" name="meeting_type_id" value="<?php echo $selected_meeting_type['id']; ?>">
                    <input type="hidden" name="booking_date" id="selected_date">
                    <input type="hidden" name="start_time" id="selected_time">
                    
                    <h2 style="margin-bottom: 1.5rem; text-align: center;">
                        <?php echo htmlspecialchars($selected_meeting_type['title']); ?> 
                        (<?php echo $selected_meeting_type['duration']; ?> min)
                    </h2>
                    
                    <div class="datetime-selection">
                        <div class="calendar-section">
                            <h3 class="section-title">Select a date</h3>
                            <div class="calendar" id="calendar">
                                <!-- Calendar will be generated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="time-section">
                            <h3 class="section-title">Available times</h3>
                            <div class="time-slots" id="time-slots">
                                <p style="text-align: center; color: #64748b;">Select a date to see available times</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 1rem;">Guest Information</h3>
                        
                        <div class="guest-form">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="guest_name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="guest_email" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="guest_phone" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-input form-textarea" 
                                          placeholder="Please share anything that will help prepare for our meeting."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="history.back()" class="btn btn-secondary">Back</button>
                        <button type="submit" class="btn btn-primary" id="book-btn" disabled>Book Meeting</button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <!-- Step 3: Confirmation -->
                <div class="success-page">
                    <div class="success-icon">✅</div>
                    <h2>Meeting Booked Successfully!</h2>
                    <p>You'll receive a confirmation email shortly with all the details.</p>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-primary">Back to Home</a>
                        <a href="booking.php?user=<?php echo $host['booking_url']; ?>" class="btn btn-secondary">Book Another</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Meeting type selection
        document.querySelectorAll('input[name="meeting_type_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.meeting-type-card').forEach(card => {
                    card.classList.remove('selected');
                });
                this.closest('.meeting-type-card').classList.add('selected');
            });
        });

        // Calendar and time slot functionality
        <?php if ($step === 2): ?>
        const availability = <?php echo json_encode($availability); ?>;
        const hostId = <?php echo $host['id']; ?>;
        const meetingDuration = <?php echo $selected_meeting_type['duration']; ?>;
        
        function generateCalendar() {
            const calendar = document.getElementById('calendar');
            const today = new Date();
            const currentMonth = today.getMonth();
            const currentYear = today.getFullYear();
            
            // Add headers
            const headers = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            headers.forEach(header => {
                const headerDiv = document.createElement('div');
                headerDiv.className = 'calendar-header';
                headerDiv.textContent = header;
                calendar.appendChild(headerDiv);
            });
            
            // Generate days for next 30 days
            for (let i = 0; i < 30; i++) {
                const date = new Date(today);
                date.setDate(today.getDate() + i);
                
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.textContent = date.getDate();
                
                const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'lowercase' });
                const isAvailable = availability.some(avail => avail.day_of_week === dayOfWeek);
                
                if (isAvailable) {
                    dayDiv.classList.add('available');
                    dayDiv.addEventListener('click', () => selectDate(date));
                } else {
                    dayDiv.classList.add('unavailable');
                }
                
                calendar.appendChild(dayDiv);
            }
        }
        
        function selectDate(date) {
            // Remove previous selection
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
            });
            
            // Add selection to clicked day
            event.target.classList.add('selected');
            
            // Set hidden input
            document.getElementById('selected_date').value = date.toISOString().split('T')[0];
            
            // Load time slots
            loadTimeSlots(date);
        }
        
        function loadTimeSlots(date) {
            const timeSlotsContainer = document.getElementById('time-slots');
            const dateStr = date.toISOString().split('T')[0];
            
            // This would normally be an AJAX call, but for simplicity we'll generate slots client-side
            const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'lowercase' });
            const dayAvailability = availability.find(avail => avail.day_of_week === dayOfWeek);
            
            if (!dayAvailability) {
                timeSlotsContainer.innerHTML = '<p style="text-align: center; color: #64748b;">No available times for this date</p>';
                return;
            }
            
            // Generate time slots (simplified - in real app, check against existing bookings)
            const slots = generateTimeSlots(dayAvailability.start_time, dayAvailability.end_time, meetingDuration);
            
            timeSlotsContainer.innerHTML = '';
            slots.forEach(slot => {
                const slotDiv = document.createElement('div');
                slotDiv.className = 'time-slot';
                slotDiv.textContent = formatTime(slot);
                slotDiv.addEventListener('click', () => selectTime(slot));
                timeSlotsContainer.appendChild(slotDiv);
            });
        }
        
        function generateTimeSlots(startTime, endTime, duration) {
            const slots = [];
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            
            while (start < end) {
                slots.push(start.toTimeString().substr(0, 5));
                start.setMinutes(start.getMinutes() + duration);
            }
            
            return slots;
        }
        
        function selectTime(time) {
            // Remove previous selection
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Add selection to clicked slot
            event.target.classList.add('selected');
            
            // Set hidden input
            document.getElementById('selected_time').value = time + ':00';
            
            // Enable book button
            document.getElementById('book-btn').disabled = false;
        }
        
        function formatTime(time24) {
            const [hours, minutes] = time24.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
        
        // Initialize calendar
        generateCalendar();
        <?php endif; ?>
    </script>
</body>
</html>
