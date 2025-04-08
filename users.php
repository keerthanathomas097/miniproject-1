<?php
session_start();
include 'connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = $_POST['role'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO tbl_users (name, email, phone, role, password, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $name, $email, $phone, $role, $password);
            $stmt->execute();
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['user_id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = $_POST['role'];
            $status = $_POST['is_verified'];
            
            $stmt = $conn->prepare("UPDATE tbl_users SET name=?, email=?, phone=?, role=?, is_verified=? WHERE user_id=?");
            $stmt->bind_param("ssssii", $name, $email, $phone, $role, $status, $id);
            $stmt->execute();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Clover Outfit Rentals</title>
    <!-- Add this to your <head> section if not already there -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <style>
        /* Exact sidebar styling */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
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
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        /* Brand title styling */
        .brand-container {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }
        
        .brand-name {
            font-weight: 600;
            font-size: 22px;
            margin: 0;
            padding: 0;
            letter-spacing: 0.5px;
            color: white;
        }
        
        .brand-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 3px;
            font-weight: 300;
        }
        
        /* Section headers */
        .sidebar-section {
            margin-top: 20px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .sidebar-section-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 500;
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        /* Navigation links */
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav-item {
            margin-bottom: 2px;
        }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.75);
            padding: 10px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 400;
        }
        
        .sidebar-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 500;
        }
        
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        /* Footer */
        .sidebar-footer {
            position: absolute;
            bottom: 15px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            padding: 10px 0;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }

        /* Table styling */
        .table {
            font-size: 14px;
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 500;
            padding: 12px 15px;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        /* Card styling */
        .card {
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .card-body {
            padding: 20px;
        }

        /* Statistics cards */
        .card h6.text-muted {
            font-size: 13px;
            margin-bottom: 8px;
            color: #6c757d !important;
        }

        .card h3 {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Badge styling */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            font-size: 12px;
            border-radius: 4px;
        }

        /* Action buttons */
        .btn-sm {
            padding: 4px 8px;
            margin: 0 2px;
        }

        /* DataTable customization */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px 8px;
        }

        /* Page header */
        .d-flex.justify-content-between {
            margin-bottom: 24px !important;
        }

        .d-flex.justify-content-between h2 {
            font-size: 24px;
            font-weight: 500;
            color: #2c3e50;
        }

        /* Container padding */
        .container-fluid {
            padding: 24px;
        }

        /* Modal customization */
        .modal-content {
            border-radius: 8px;
            border: none;
        }

        .modal-header {
            padding: 16px 24px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #dee2e6;
        }

        /* Form controls */
        .form-control, .form-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <!-- Sidebar HTML for users.php -->
    <div class="sidebar">
        <div class="brand-container">
            <h1 class="brand-name">Clover Outfit Rentals</h1>
            <div class="brand-subtitle">Admin Dashboard</div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">DASHBOARD</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_dashboard.php" class="sidebar-nav-link">
                        <i class="fas fa-home sidebar-icon"></i>
                        Dashboard
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">MANAGEMENT</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="users.php" class="sidebar-nav-link active">
                        <i class="fas fa-users sidebar-icon"></i>
                        Users
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="outfit_management.php" class="sidebar-nav-link">
                        <i class="fas fa-tshirt sidebar-icon"></i>
                        Outfits
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="orders_admin.php" class="sidebar-nav-link">
                        <i class="fas fa-shopping-cart sidebar-icon"></i>
                        Orders
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">ANALYTICS</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_reports.php" class="sidebar-nav-link">
                        <i class="fas fa-chart-bar sidebar-icon"></i>
                        Reports
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">SETTINGS</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_profile.php" class="sidebar-nav-link">
                        <i class="fas fa-user sidebar-icon"></i>
                        Profile
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="settings.php" class="sidebar-nav-link">
                        <i class="fas fa-cog sidebar-icon"></i>
                        Settings
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="logout.php" class="sidebar-nav-link">
                        <i class="fas fa-sign-out-alt sidebar-icon"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            © 2025 Clover Outfit Rentals
        </div>
    </div>

    <!-- Make sure your main content is wrapped in this div -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">User Management</h2>
                <button class="btn btn-primary" style="background-color: #932A2A; border-color: #932A2A;" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>

            <!-- User Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0">
                                <?php
                                $total_query = "SELECT COUNT(*) as total FROM tbl_users";
                                $total_result = $conn->query($total_query);
                                $total = $total_result->fetch_assoc()['total'];
                                echo $total;
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Active Customers</h6>
                            <h3 class="mb-0">
                                <?php
                                $customers_query = "SELECT COUNT(*) as total FROM tbl_users WHERE role = 'user' AND is_verified = 1";
                                $customers_result = $conn->query($customers_query);
                                echo $customers_result->fetch_assoc()['total'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Active Lenders</h6>
                            <h3 class="mb-0">
                                <?php
                                $lenders_query = "SELECT COUNT(*) as total FROM tbl_users WHERE role = 'lender' AND is_verified = 1";
                                $lenders_result = $conn->query($lenders_query);
                                echo $lenders_result->fetch_assoc()['total'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Administrators</h6>
                            <h3 class="mb-0">
                                <?php
                                $admin_query = "SELECT COUNT(*) as total FROM tbl_users WHERE role = 'admin'";
                                $admin_result = $conn->query($admin_query);
                                echo $admin_result->fetch_assoc()['total'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Check if created_at column exists
                                $check_column = "SHOW COLUMNS FROM tbl_users LIKE 'created_at'";
                                $column_result = $conn->query($check_column);
                                
                                if ($column_result->num_rows == 0) {
                                    // If created_at doesn't exist, use a simpler query
                                    $users_query = "SELECT * FROM tbl_users ORDER BY user_id DESC";
                                } else {
                                    $users_query = "SELECT * FROM tbl_users ORDER BY created_at DESC";
                                }
                                
                                $users_result = $conn->query($users_query);
                                
                                if ($users_result === false) {
                                    echo '<tr><td colspan="8">Error fetching users: ' . $conn->error . '</td></tr>';
                                } else {
                                    while ($user = $users_result->fetch_assoc()) {
                                        $status_class = isset($user['is_verified']) && $user['is_verified'] ? 'success' : 'warning';
                                        $status_text = isset($user['is_verified']) && $user['is_verified'] ? 'Active' : 'Pending';
                                        
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
                                            <td>
                                                <?php 
                                                if (isset($user['created_at'])) {
                                                    echo date('M d, Y', strtotime($user['created_at']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-user" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-id="<?php echo $user['user_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                                                        data-role="<?php echo $user['role']; ?>"
                                                        data-status="<?php echo isset($user['is_verified']) ? $user['is_verified'] : '0'; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['role'] !== 'admin') : ?>
                                                <button class="btn btn-sm btn-outline-danger delete-user"
                                                        data-id="<?php echo $user['user_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
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

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #932A2A; color: white;">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addUserForm" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="user">Customer</option>
                                    <option value="lender">Lender</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" style="background-color: #932A2A; border-color: #932A2A;">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #932A2A; color: white;">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editUserForm" method="POST">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="edit_phone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" id="edit_role" required>
                                    <option value="user">Customer</option>
                                    <option value="lender">Lender</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_verified" id="edit_status">
                                    <option value="1">Active</option>
                                    <option value="0">Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" style="background-color: #932A2A; border-color: #932A2A;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> 

    <!-- Add this JavaScript at the bottom of your file -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#usersTable').DataTable({
            "order": [[6, "desc"]], // Sort by joined date by default
            "pageLength": 10,
            "responsive": true
        });

        // Handle edit user modal
        $('.edit-user').click(function() {
            const userData = $(this).data();
            $('#edit_user_id').val(userData.id);
            $('#edit_name').val(userData.name);
            $('#edit_email').val(userData.email);
            $('#edit_phone').val(userData.phone);
            $('#edit_role').val(userData.role);
            $('#edit_status').val(userData.status);
        });

        // Handle delete user
        $('.delete-user').click(function() {
            const userId = $(this).data('id');
            const userName = $(this).data('name');
            
            if (confirm(`Are you sure you want to delete ${userName}?`)) {
                $.post('delete_user.php', { user_id: userId }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting user: ' + response.message);
                    }
                });
            }
        });
    });
    </script> 
</body>
</html> 