<?php
session_start();
include 'connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle user addition/editing
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
            
            $stmt = $conn->prepare("UPDATE tbl_users SET name=?, email=?, phone=?, role=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $role, $id);
            $stmt->execute();
        }
    }
}

// Fetch verified users
$query = "SELECT user_id, name, email, phone, role FROM tbl_users WHERE is_verified = 1 ORDER BY user_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Fashion Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 250px; }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgb(91, 9, 9);
            color: white;
            padding-top: 20px;
        }
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 20px; 
        }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover { 
            background: rgb(147, 42, 42); 
            color: #ecf0f1; 
        }
        .dashboard-container {
            padding: 2rem;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .card-header {
            background-color: #800020;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem;
        }
        .btn-maroon {
            background-color: #800020;
            color: white;
        }
        .btn-maroon:hover {
            background-color: #600018;
            color: white;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .modal-header {
            background-color: #800020;
            color: white;
        }
        .close {
            color: white;
        }
        .user-action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .table-responsive {
            padding: 1rem;
        }
        .serial-number {
            width: 50px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="text-center mb-4">Fashion Rental</h4>
        <nav>
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a href="user_management.php" class="sidebar-link" style="background-color: rgb(147, 42, 42);"><i class="fas fa-users me-2"></i> User Management</a>
            <a href="outfit_management.php" class="sidebar-link"><i class="fas fa-tshirt me-2"></i> Outfit Management</a>
            <a href="#" class="sidebar-link"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">User Management</h4>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i> Add User
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th class="serial-number">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $serial = 1;
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>
                                        <td>'.$serial.'</td>
                                        <td>'.htmlspecialchars($row['name']).'</td>
                                        <td>'.htmlspecialchars($row['email']).'</td>
                                        <td>'.htmlspecialchars($row['phone']).'</td>
                                        <td>'.ucfirst(htmlspecialchars($row['role'])).'</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary user-action-btn edit-user" 
                                                    data-id="'.$row['user_id'].'"
                                                    data-name="'.htmlspecialchars($row['name']).'"
                                                    data-email="'.htmlspecialchars($row['email']).'"
                                                    data-phone="'.htmlspecialchars($row['phone']).'"
                                                    data-role="'.htmlspecialchars($row['role']).'">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </td>
                                    </tr>';
                                $serial++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="lender">Lender</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-maroon">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Role</label>
                                <select name="role" id="edit_role" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="lender">Lender</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-maroon">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#usersTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 10
            });

            // Handle edit button clicks
            $('.edit-user').click(function() {
                const userData = $(this).data();
                $('#edit_user_id').val(userData.id);
                $('#edit_name').val(userData.name);
                $('#edit_email').val(userData.email);
                $('#edit_phone').val(userData.phone);
                $('#edit_role').val(userData.role);
                $('#editUserModal').modal('show');
            });
        });
    </script>
</body>
</html>
