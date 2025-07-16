<?php
require_once 'db.php';
requireLogin();

$user = getCurrentUser();
$today = date('Y-m-d');

// Get upcoming bookings
$upcoming_bookings = db()->fetchAll(
    "SELECT b.*, mt.title as meeting_title, mt.duration 
     FROM bookings b 
     JOIN meeting_types mt ON b.meeting_type_id = mt.id 
     WHERE b.user_id = ? AND b.booking_date >= ? AND b.status = 'confirmed'
     ORDER BY b.booking_date ASC, b.start_time ASC 
     LIMIT 10", 
    [$user['id'], $today]
);

// Get recent bookings
$recent_bookings = db()->fetchAll(
    "SELECT b.*, mt.title as meeting_title, mt.duration 
     FROM bookings b 
     JOIN meeting_types mt ON b.meeting_type_id = mt.id 
     WHERE b.user_id = ? 
     ORDER BY b.created_at DESC 
     LIMIT 5", 
    [$user['id']]
);

// Get meeting types
$meeting_types = db()->fetchAll(
    "SELECT * FROM meeting_types WHERE user_id = ? AND is_active = 1", 
    [$user['id']]
);

// Get stats
$total_bookings = db()->fetchOne(
    "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?", 
    [$user['id']]
)['count'];

$this_month_bookings = db()->fetchOne(
    "SELECT COUNT(*) as count FROM bookings 
     WHERE user_id = ? AND MONTH(booking_date) = MONTH(CURRENT_DATE()) 
     AND YEAR(booking_date) = YEAR(CURRENT_DATE())", 
    [$user['id']]
)['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Calendly Clone</title>
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

        .user-info {
            position: absolute;
            bottom: 2rem;
            left: 0;
            right: 0;
            padding: 0 2rem;
        }

        .user-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #4285f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin: 0 auto 0.5rem;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            color: #1e293b;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .stat-icon.blue { background: #4285f4; }
        .stat-icon.green { background: #34a853; }
        .stat-icon.orange { background: #ff9800; }
        .stat-icon.purple { background: #9c27b0; }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1e293b;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
        }

        /* Booking List */
        .booking-list {
            list-style: none;
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .booking-info h4 {
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .booking-meta {
            color: #64748b;
            font-size: 0.9rem;
        }

        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-confirmed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Meeting Types */
        .meeting-type {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .meeting-type-info h4 {
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .meeting-type-meta {
            color: #64748b;
            font-size: 0.9rem;
        }

        .copy-link {
            background: #f1f5f9;
            color: #4285f4;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .copy-link:hover {
            background: #e2e8f0;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
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
                        <a href="dashboard.php" class="nav-link active">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="availability.php" class="nav-link">
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
            
            <div class="user-info">
                <div class="user-card">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 0.25rem;">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #64748b;">
                        @<?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>! 👋</h1>
                    <p style="color: #64748b; margin-top: 0.5rem;">Here's what's happening with your schedule</p>
                </div>
                <div class="header-actions">
                    <a href="booking.php?user=<?php echo $user['booking_url']; ?>" class="btn btn-secondary">
                        🔗 View Booking Page
                    </a>
                    <a href="availability.php" class="btn btn-primary">
                        ⚙️ Set Availability
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon blue">📊</div>
                    </div>
                    <div class="stat-value"><?php echo $total_bookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon green">📈</div>
                    </div>
                    <div class="stat-value"><?php echo $this_month_bookings; ?></div>
                    <div class="stat-label">This Month</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon orange">⏰</div>
                    </div>
                    <div class="stat-value"><?php echo count($upcoming_bookings); ?></div>
                    <div class="stat-label">Upcoming</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon purple">🎯</div>
                    </div>
                    <div class="stat-value"><?php echo count($meeting_types); ?></div>
                    <div class="stat-label">Meeting Types</div>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Upcoming Meetings</h2>
                        <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">View All</a>
                    </div>
                    
                    <?php if (empty($upcoming_bookings)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📅</div>
                            <h3>No upcoming meetings</h3>
                            <p>Your schedule is clear! Share your booking link to get started.</p>
                        </div>
                    <?php else: ?>
                        <ul class="booking-list">
                            <?php foreach ($upcoming_bookings as $booking): ?>
                                <li class="booking-item">
                                    <div class="booking-info">
                                        <h4><?php echo htmlspecialchars($booking['meeting_title']); ?></h4>
                                        <div class="booking-meta">
                                            <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong> • 
                                            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> at 
                                            <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
                                        </div>
                                    </div>
                                    <span class="booking-status status-confirmed">Confirmed</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Your Meeting Types</h2>
                        <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">+ Add New</a>
                    </div>
                    
                    <?php if (empty($meeting_types)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🎯</div>
                            <h3>No meeting types</h3>
                            <p>Create your first meeting type to start accepting bookings.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($meeting_types as $type): ?>
                            <div class="meeting-type">
                                <div class="meeting-type-info">
                                    <h4><?php echo htmlspecialchars($type['title']); ?></h4>
                                    <div class="meeting-type-meta">
                                        <?php echo $type['duration']; ?> minutes
                                    </div>
                                </div>
                                <button class="copy-link" onclick="copyBookingLink('<?php echo $user['booking_url']; ?>', <?php echo $type['id']; ?>)">
                                    📋 Copy Link
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Recent Activity</h2>
                </div>
                
                <?php if (empty($recent_bookings)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No recent activity</h3>
                        <p>Your booking activity will appear here.</p>
                    </div>
                <?php else: ?>
                    <ul class="booking-list">
                        <?php foreach ($recent_bookings as $booking): ?>
                            <li class="booking-item">
                                <div class="booking-info">
                                    <h4><?php echo htmlspecialchars($booking['meeting_title']); ?></h4>
                                    <div class="booking-meta">
                                        <?php echo htmlspecialchars($booking['guest_name']); ?> • 
                                        <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> • 
                                        Booked <?php echo date('M j', strtotime($booking['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="booking-status status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function copyBookingLink(bookingUrl, meetingTypeId) {
            const link = `${window.location.origin}/booking.php?user=${bookingUrl}&type=${meetingTypeId}`;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(() => {
                    showNotification('Booking link copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = link;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Booking link copied to clipboard!', 'success');
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#4285f4'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                font-weight: 600;
                animation: slideIn 0.3s ease;
            `;
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Auto-refresh upcoming meetings every 5 minutes
        setInterval(() => {
            // In a real application, you might want to fetch updated data via AJAX
            console.log('Checking for updates...');
        }, 300000);
    </script>
</body>
</html>
