<?php
session_start();
include 'connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Fetch users with a simple query
$query = "SELECT 
    user_id,
    name,
    email,
    phone,
    role,
    is_verified
FROM tbl_users 
ORDER BY user_id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Clover Outfit Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 240px;
            background-color: #932A2A;
            color: white;
            padding: 20px;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .table th {
            font-weight: 500;
            color: #495057;
            background-color: #f8f9fa;
        }
        .badge {
            padding: 6px 12px;
            font-weight: 500;
        }
        .brand-name {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .brand-subtitle {
            font-size: 13px;
            opacity: 0.7;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="mb-4">
            <div class="brand-name">Clover Outfit Rentals</div>
            <div class="brand-subtitle">Admin Dashboard</div>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a class="nav-link active" href="users.php">
                <i class="fas fa-users me-2"></i> Users
            </a>
            <a class="nav-link" href="orders_admin.php">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a class="nav-link" href="outfit_management.php">
                <i class="fas fa-tshirt me-2"></i> Outfits
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">User Management</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result) {
                                    while ($user = $result->fetch_assoc()) {
                                        $status_class = $user['is_verified'] ? 'success' : 'warning';
                                        $status_text = $user['is_verified'] ? 'Active' : 'Pending';
                                        
                                        $role_class = '';
                                        switch($user['role']) {
                                            case 'admin':
                                                $role_class = 'danger';
                                                break;
                                            case 'lender':
                                                $role_class = 'primary';
                                                break;
                                            default:
                                                $role_class = 'info';
                                        }
                                ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $role_class; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                <?php 
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 