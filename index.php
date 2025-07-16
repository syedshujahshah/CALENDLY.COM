<?php
require_once 'db.php';
startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendly Clone - Easy Scheduling Ahead</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4285f4;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #4285f4;
        }

        .auth-buttons {
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
            background: transparent;
            color: #4285f4;
            border: 2px solid #4285f4;
        }

        .btn-secondary:hover {
            background: #4285f4;
            color: white;
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 4rem 0;
            text-align: center;
            color: white;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-top: 2rem;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .google-btn, .microsoft-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .google-btn {
            background: #4285f4;
            color: white;
        }

        .microsoft-btn {
            background: #00a1f1;
            color: white;
        }

        .google-btn:hover, .microsoft-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .hero-image {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .booking-preview {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            color: #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .calendar-day:hover {
            background: #e3f2fd;
        }

        .calendar-day.selected {
            background: #4285f4;
            color: white;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .time-slot {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .time-slot:hover {
            border-color: #4285f4;
            background: #e3f2fd;
        }

        /* Features Section */
        .features {
            padding: 4rem 0;
            background: rgba(255, 255, 255, 0.95);
            margin: 2rem 0;
            border-radius: 20px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: #4285f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }

        /* Trusted By Section */
        .trusted-by {
            text-align: center;
            padding: 2rem 0;
            color: white;
        }

        .company-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .company-logo {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .nav-links {
                display: none;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .google-btn, .microsoft-btn {
                width: 100%;
                max-width: 300px;
            }
        }

        .email-signup {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .email-signup a {
            color: #e3f2fd;
            text-decoration: none;
        }

        .email-signup a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="index.php" class="logo">📅 Calendly</a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Log in</a>
                    <a href="register.php" class="btn btn-primary">Get started</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1>Easy scheduling ahead</h1>
                        <p>Join 20 million professionals who easily book meetings with the #1 scheduling tool.</p>
                        
                        <div class="hero-buttons">
                            <a href="register.php" class="google-btn">
                                <span>🔍</span> Sign up with Google
                            </a>
                            <a href="register.php" class="microsoft-btn">
                                <span>🏢</span> Sign up with Microsoft
                            </a>
                        </div>
                        
                        <div class="email-signup">
                            <p>OR</p>
                            <a href="register.php">Sign up free with email</a>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">No credit card required</p>
                        </div>
                    </div>
                    
                    <div class="hero-image">
                        <div class="booking-preview">
                            <h3>📋 Share your booking page</h3>
                            <div style="display: flex; align-items: center; gap: 1rem; margin: 1rem 0;">
                                <div style="width: 40px; height: 40px; background: #4285f4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">👤</div>
                                <div>
                                    <strong>ACME Inc.</strong>
                                    <p style="font-size: 0.9rem; color: #666;">Select a Date & Time</p>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <div class="calendar-grid">
                                        <div class="calendar-day">S</div>
                                        <div class="calendar-day">M</div>
                                        <div class="calendar-day">T</div>
                                        <div class="calendar-day">W</div>
                                        <div class="calendar-day">T</div>
                                        <div class="calendar-day">F</div>
                                        <div class="calendar-day">S</div>
                                        <div class="calendar-day">28</div>
                                        <div class="calendar-day">29</div>
                                        <div class="calendar-day">30</div>
                                        <div class="calendar-day">31</div>
                                        <div class="calendar-day">1</div>
                                        <div class="calendar-day">2</div>
                                        <div class="calendar-day">3</div>
                                        <div class="calendar-day">4</div>
                                        <div class="calendar-day">5</div>
                                        <div class="calendar-day">6</div>
                                        <div class="calendar-day">7</div>
                                        <div class="calendar-day">8</div>
                                        <div class="calendar-day">9</div>
                                        <div class="calendar-day">10</div>
                                        <div class="calendar-day selected">22</div>
                                        <div class="calendar-day">23</div>
                                        <div class="calendar-day">24</div>
                                        <div class="calendar-day">25</div>
                                        <div class="calendar-day">26</div>
                                        <div class="calendar-day">27</div>
                                        <div class="calendar-day">28</div>
                                    </div>
                                </div>
                                
                                <div class="time-slots">
                                    <div class="time-slot">10:00am</div>
                                    <div class="time-slot" style="background: #4285f4; color: white;">11:00am</div>
                                    <div class="time-slot">1:00pm</div>
                                    <div class="time-slot">3:30pm</div>
                                    <div class="time-slot">4:00pm</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features" id="features">
            <div class="container">
                <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 1rem;">Why choose our platform?</h2>
                <p style="text-align: center; font-size: 1.1rem; color: #666;">Everything you need to schedule meetings efficiently</p>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <h3>Easy Scheduling</h3>
                        <p>Set your availability and let others book time with you automatically.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🔗</div>
                        <h3>Custom Links</h3>
                        <p>Get your personalized booking link to share with clients and colleagues.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📧</div>
                        <h3>Email Notifications</h3>
                        <p>Automatic email confirmations and reminders for all your meetings.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3>Mobile Friendly</h3>
                        <p>Works perfectly on all devices - desktop, tablet, and mobile.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3>Instant Booking</h3>
                        <p>Real-time availability checking and instant booking confirmation.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h3>Smart Dashboard</h3>
                        <p>Manage all your meetings from one beautiful, intuitive dashboard.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="trusted-by">
            <div class="container">
                <h3>Trusted by more than 100,000 of the world's leading organizations</h3>
                <div class="company-logos">
                    <div class="company-logo">🚀 DOORDASH</div>
                    <div class="company-logo">🏠 LYFT</div>
                    <div class="company-logo">🧭 COMPASS</div>
                    <div class="company-logo">💄 L'ORÉAL</div>
                    <div class="company-logo">📋 ZENDESK</div>
                    <div class="company-logo">📦 DROPBOX</div>
                    <div class="company-logo">⚙️ GONG</div>
                    <div class="company-logo">🚢 CARNIVAL</div>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Interactive calendar preview
        document.querySelectorAll('.calendar-day').forEach(day => {
            if (day.textContent && !isNaN(day.textContent)) {
                day.addEventListener('click', function() {
                    document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');
                });
            }
        });

        // Interactive time slots
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(s => {
                    s.style.background = '';
                    s.style.color = '';
                });
                this.style.background = '#4285f4';
                this.style.color = 'white';
            });
        });
    </script>
</body>
</html>
