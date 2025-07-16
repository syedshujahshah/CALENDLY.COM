<?php
require_once 'db.php';
requireLogin();

$user = getCurrentUser();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_availability') {
        // Delete existing availability
        db()->delete('availability', 'user_id = ?', [$user['id']]);
        
        // Insert new availability
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if (isset($_POST[$day . '_enabled'])) {
                $start_time = $_POST[$day . '_start'];
                $end_time = $_POST[$day . '_end'];
                
                if ($start_time && $end_time) {
                    db()->insert('availability', [
                        'user_id' => $user['id'],
                        'day_of_week' => $day,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'is_available' => 1
                    ]);
                }
            }
        }
        
        $message = 'Availability updated successfully!';
    }
}

// Get current availability
$availability = [];
$current_availability = db()->fetchAll(
    "SELECT * FROM availability WHERE user_id = ? ORDER BY 
     FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')", 
    [$user['id']]
);

foreach ($current_availability as $avail) {
    $availability[$avail['day_of_week']] = [
        'start_time' => $avail['start_time'],
        'end_time' => $avail['end_time'],
        'is_available' => $avail['is_available']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Availability - Calendly Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #333;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 2rem 0;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4285f4;
            text-decoration: none;
            padding: 0 2rem;
            margin-bottom: 2rem;
            display: block;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 2rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #f1f5f9;
            color: #4285f4;
            border-right: 3px solid #4285f4;
        }

        .nav-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #64748b;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #bbf7d0;
        }

        /* Availability Form */
        .availability-form {
            max-width: 800px;
        }

        .day-row {
            display: grid;
            grid-template-columns: 120px 1fr 120px 120px 60px;
            gap: 1rem;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .day-row:last-child {
            border-bottom: none;
        }

        .day-label {
            font-weight: 600;
            color: #1e293b;
        }

        .time-inputs {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .time-input {
            padding: 0.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9rem;
            width: 100px;
        }

        .time-input:focus {
            outline: none;
            border-color: #4285f4;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .checkbox {
            width: 20px;
            height: 20px;
            accent-color: #4285f4;
        }

        .unavailable {
            opacity: 0.5;
        }

        .unavailable .time-input {
            background: #f8fafc;
            cursor: not-allowed;
        }

        .btn {
            padding: 0.75rem 2rem;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
        }

        .form-actions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
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

        /* Quick Setup */
        .quick-setup {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .quick-setup h3 {
            margin-bottom: 1rem;
            color: #1e293b;
        }

        .quick-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .quick-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .quick-btn:hover {
            border-color: #4285f4;
            background: #f0f7ff;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .day-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: left;
            }

            .time-inputs {
                justify-content: space-between;
            }

            .quick-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <a href="index.php" class="logo">📅 Calendly</a>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="availability.php" class="nav-link active">
                            <span class="nav-icon">📅</span>
                            Availability
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="booking.php?user=<?php echo $user['booking_url']; ?>" class="nav-link">
                            <span class="nav-icon">🔗</span>
                            Booking Page
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <span class="nav-icon">⚙️</span>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <span class="nav-icon">🚪</span>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Set Your Availability</h1>
                <p>Configure when you're available for meetings</p>
            </div>

            <?php if ($message): ?>
                <div class="success-message">
                    ✅ <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="quick-setup">
                    <h3>Quick Setup</h3>
                    <div class="quick-buttons">
                        <button class="quick-btn" onclick="setBusinessHours()">Business Hours (9 AM - 5 PM)</button>
                        <button class="quick-btn" onclick="setExtendedHours()">Extended Hours (8 AM - 8 PM)</button>
                        <button class="quick-btn" onclick="setWeekdaysOnly()">Weekdays Only</button>
                        <button class="quick-btn" onclick="clearAll()">Clear All</button>
                    </div>
                </div>

                <form method="POST" class="availability-form">
                    <input type="hidden" name="action" value="update_availability">
                    
                    <h2 class="card-title">Weekly Hours</h2>
                    
                    <?php
                    $days = [
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday', 
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday'
                    ];
                    
                    foreach ($days as $day_key => $day_name):
                        $is_available = isset($availability[$day_key]);
                        $start_time = $is_available ? substr($availability[$day_key]['start_time'], 0, 5) : '09:00';
                        $end_time = $is_available ? substr($availability[$day_key]['end_time'], 0, 5) : '17:00';
                    ?>
                        <div class="day-row" id="<?php echo $day_key; ?>_row">
                            <div class="day-label"><?php echo $day_name; ?></div>
                            <div class="time-inputs">
                                <input type="time" 
                                       name="<?php echo $day_key; ?>_start" 
                                       value="<?php echo $start_time; ?>" 
                                       class="time-input"
                                       <?php echo !$is_available ? 'disabled' : ''; ?>>
                                <span>to</span>
                                <input type="time" 
                                       name="<?php echo $day_key; ?>_end" 
                                       value="<?php echo $end_time; ?>" 
                                       class="time-input"
                                       <?php echo !$is_available ? 'disabled' : ''; ?>>
                            </div>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       name="<?php echo $day_key; ?>_enabled" 
                                       class="checkbox"
                                       <?php echo $is_available ? 'checked' : ''; ?>
                                       onchange="toggleDay('<?php echo $day_key; ?>')">
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Save Availability</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2 class="card-title">Preview</h2>
                <p style="color: #64748b; margin-bottom: 1rem;">This is how your availability will appear to visitors:</p>
                
                <div id="availability-preview">
                    <!-- Preview will be generated by JavaScript -->
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleDay(day) {
            const checkbox = document.querySelector(`input[name="${day}_enabled"]`);
            const row = document.getElementById(`${day}_row`);
            const timeInputs = row.querySelectorAll('.time-input');
            
            if (checkbox.checked) {
                row.classList.remove('unavailable');
                timeInputs.forEach(input => input.disabled = false);
            } else {
                row.classList.add('unavailable');
                timeInputs.forEach(input => input.disabled = true);
            }
            
            updatePreview();
        }

        function setBusinessHours() {
            const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            const weekend = ['saturday', 'sunday'];
            
            // Enable weekdays with business hours
            weekdays.forEach(day => {
                document.querySelector(`input[name="${day}_enabled"]`).checked = true;
                document.querySelector(`input[name="${day}_start"]`).value = '09:00';
                document.querySelector(`input[name="${day}_end"]`).value = '17:00';
                toggleDay(day);
            });
            
            // Disable weekends
            weekend.forEach(day => {
                document.querySelector(`input[name="${day}_enabled"]`).checked = false;
                toggleDay(day);
            });
            
            updatePreview();
        }

        function setExtendedHours() {
            const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            
            weekdays.forEach(day => {
                document.querySelector(`input[name="${day}_enabled"]`).checked = true;
                document.querySelector(`input[name="${day}_start"]`).value = '08:00';
                document.querySelector(`input[name="${day}_end"]`).value = '20:00';
                toggleDay(day);
            });
            
            updatePreview();
        }

        function setWeekdaysOnly() {
            const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            const weekend = ['saturday', 'sunday'];
            
            weekdays.forEach(day => {
                document.querySelector(`input[name="${day}_enabled"]`).checked = true;
                toggleDay(day);
            });
            
            weekend.forEach(day => {
                document.querySelector(`input[name="${day}_enabled"]`).checked = false;
                toggleDay(day);
            });
            
            updatePreview();
        }

        function clearAll() {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            days.forEach(day => {
                document.querySelector(`input[name="${day}_enabled"]`).checked = false;
                toggleDay(day);
            });
            
            updatePreview();
        }

        function updatePreview() {
            const preview = document.getElementById('availability-preview');
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            let previewHTML = '<div style="display: grid; gap: 0.5rem;">';
            
            days.forEach((day, index) => {
                const checkbox = document.querySelector(`input[name="${day}_enabled"]`);
                const startTime = document.querySelector(`input[name="${day}_start"]`).value;
                const endTime = document.querySelector(`input[name="${day}_end"]`).value;
                
                if (checkbox.checked && startTime && endTime) {
                    const start = formatTime(startTime);
                    const end = formatTime(endTime);
                    
                    previewHTML += `
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: #f8fafc; border-radius: 6px;">
                            <span style="font-weight: 600;">${dayNames[index]}</span>
                            <span style="color: #4285f4;">${start} - ${end}</span>
                        </div>
                    `;
                } else {
                    previewHTML += `
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: #f8fafc; border-radius: 6px; opacity: 0.5;">
                            <span style="font-weight: 600;">${dayNames[index]}</span>
                            <span style="color: #64748b;">Unavailable</span>
                        </div>
                    `;
                }
            });
            
            previewHTML += '</div>';
            preview.innerHTML = previewHTML;
        }

        function formatTime(time24) {
            const [hours, minutes] = time24.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state for all days
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            days.forEach(day => {
                const checkbox = document.querySelector(`input[name="${day}_enabled"]`);
                if (!checkbox.checked) {
                    toggleDay(day);
                }
            });
            
            updatePreview();
            
            // Add event listeners for time changes
            document.querySelectorAll('.time-input').forEach(input => {
                input.addEventListener('change', updatePreview);
            });
        });
    </script>
</body>
</html>
