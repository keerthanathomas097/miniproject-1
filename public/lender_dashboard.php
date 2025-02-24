<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lender Dashboard | Fashion Share</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --light: #ecf0f1;
            --success: #27ae60;
            --warning: #f1c40f;
            --danger: #e74c3c;
        }

        body {
            background-color: #f5f6fa;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background-color: var(--primary);
            color: white;
            padding: 20px;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 30px;
        }

        .menu-item {
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu-item:hover {
            background-color: var(--secondary);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }

        .recent-activities {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .status-active {
            background-color: var(--success);
            color: white;
        }

        .status-pending {
            background-color: var(--warning);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Fashion Share</h2>
            <p>Lender Dashboard</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item">
                <i class="fas fa-home"></i> Dashboard
            </div>
            <div class="menu-item">
                <i class="fas fa-tshirt"></i> My Outfits
            </div>
            <div class="menu-item">
                <i class="fas fa-exchange-alt"></i> Rentals
            </div>
            <div class="menu-item">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </div>
            <div class="menu-item">
                <i class="fas fa-user"></i> Profile
            </div>
            <div class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Welcome back, [Lender Name]!</h1>
            <p>Here's your outfit lending overview</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Active Listings</h3>
                <div class="value">24</div>
            </div>
            <div class="stat-card">
                <h3>Current Rentals</h3>
                <div class="value">8</div>
            </div>
            <div class="stat-card">
                <h3>Total Earnings</h3>
                <div class="value">$2,450</div>
            </div>
            <div class="stat-card">
                <h3>Rating</h3>
                <div class="value">4.8 ⭐</div>
            </div>
        </div>

        <div class="recent-activities">
            <h2>Recent Activities</h2>
            <div class="activity-item">
                <div>
                    <h4>Blue Cocktail Dress</h4>
                    <p>Rented by Sarah M.</p>
                </div>
                <span class="status status-active">Active</span>
            </div>
            <div class="activity-item">
                <div>
                    <h4>Designer Suit</h4>
                    <p>Return pending from John D.</p>
                </div>
                <span class="status status-pending">Pending</span>
            </div>
            <div class="activity-item">
                <div>
                    <h4>Evening Gown</h4>
                    <p>New rental request from Emma W.</p>
                </div>
                <button class="btn btn-primary">Review Request</button>
            </div>
        </div>
    </div>
</body>
</html>