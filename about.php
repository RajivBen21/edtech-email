<?php
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .developer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .developer-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-top: 4px solid #2b6cb0;
        }

        .developer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(43, 108, 176, 0.2);
        }

        .developer-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #2b6cb0, #4299e1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: 600;
            border: 5px solid #ebf8ff;
            overflow: hidden;
            position: relative;
        }

        .developer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .developer-avatar.no-image {
            /* Gradient background for cards without images */
            background: linear-gradient(135deg, #2b6cb0, #4299e1);
        }

        .developer-name {
            font-size: 20px;
            font-weight: 600;
            color: #1a365d;
            margin-bottom: 8px;
        }

        .developer-role {
            color: #2b6cb0;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .developer-bio {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .developer-contact {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 13px;
            color: #4a5568;
        }

        .developer-contact a {
            color: #2b6cb0;
            text-decoration: none;
            transition: color 0.3s;
        }

        .developer-contact a:hover {
            color: #1a365d;
            text-decoration: underline;
        }

        .about-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #2b6cb0;
        }

        .about-section h2 {
            color: #1a365d;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .about-section p {
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .tech-badge {
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #bee3f8;
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, #2b6cb0, transparent);
            margin: 40px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo">
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="about.php" class="active">About Us</a>
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <div class="container">
        <!-- System Information -->
        <div class="about-section">
            <h2>About the System</h2>
            <p>
                The <strong>LDCU EdTech Google Workspace for Education</strong> is a comprehensive platform designed to streamline 
                the management of email accounts, workspace licenses, account retrievals, and training requests for 
                Liceo de Cagayan University's Educational Technology Center.
            </p>
            <p>
                This system provides an efficient, user-friendly interface for tracking and managing various educational 
                technology services, ensuring seamless operations and better service delivery to faculty, staff, and students.
            </p>
        </div>


        

        <!-- Development Team -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" style="font-size: 28px; text-align: center;">Meet the Development Team</h2>
            </div>

            <div class="developer-grid">
                <!-- Developer 1 - WITH PICTURE -->
                <div class="developer-card">
                    <div class="developer-avatar">
                        <img src="images/developers/developer1.jpg" alt="Bern Francis Butawan">
                    </div>
                    <div class="developer-name">Bern Francis Butawan</div>
                    <div class="developer-role">Developer</div>
                    <div class="developer-contact">
                        <span>bfbutawan27538@liceo.edu.ph</span>
                    </div>
                </div>

                <!-- Developer 2 - WITH PICTURE -->
                <div class="developer-card">
                    <div class="developer-avatar">
                        <img src="images/developers/developer2.jpg" alt="Rajiv Ben Alferez">
                    </div>
                    <div class="developer-name">Rajiv Ben Alferez</div>
                    <div class="developer-role">Developer</div>
                    <div class="developer-contact">
                        <span>ralferez50938@liceo.edu.ph</span>
                    </div>
                </div>

                <div class="developer-card">
                    <div class="developer-avatar">
                        <img src="images/developers/developer3.jpg" alt="Karen Cleo Aninion">
                    </div>
                    <div class="developer-name">Karen Cleo Aninion</div>
                    <div class="developer-role">Supervisor</div>
                    <div class="developer-contact">
                        <span>kcaninion@liceo.edu.ph</span>
                    </div>
                </div>

                <!-- Add more developers as needed -->
            </div>
        </div>

        <!-- Contact & Support -->
        <div class="about-section">
            <h2>Contact & Support</h2>
            <p>
                <strong>Educational Technology Center</strong><br>
                Liceo de Cagayan University<br>
                R.N. Pelaez Blvd., Kauswagan, Cagayan de Oro City<br>
                <br>
                Phone: (088) 858-4093<br>
                Email: edtech@liceo.edu.ph<br>
            </p>
        </div>

        <!-- Footer -->
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #1a365d, #2b6cb0); color: white;">
            <p style="margin: 0; font-size: 14px;">
                &copy; <?php echo date('Y'); ?> Liceo de Cagayan University - Educational Technology Center<br>
                <small style="opacity: 0.8;">Version 1.0.0</small>
            </p>
        </div>
    </div>
</body>
</html>