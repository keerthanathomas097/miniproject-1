<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

$userId = $_SESSION['id'];

// Fetch user details
$userQuery = "SELECT * FROM tbl_users WHERE user_id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Fetch user's outfits (if they are a lender)
$outfitsQuery = "SELECT o.*, d.description_text 
                 FROM tbl_outfit o 
                 LEFT JOIN tbl_description d ON o.description_id = d.id 
                 WHERE o.email = ? 
                 ORDER BY o.created_at DESC";
$outfitsStmt = $conn->prepare($outfitsQuery);
$outfitsStmt->bind_param("s", $user['email']);
$outfitsStmt->execute();
$outfits = $outfitsStmt->get_result();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    
    $updateQuery = "UPDATE tbl_users SET name = ?, phone = ? WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssi", $name, $phone, $userId);
    
    if ($updateStmt->execute()) {
        $success_message = "Profile updated successfully!";
        $_SESSION['username'] = $name;
        $user['name'] = $name;
        $user['phone'] = $phone;
    } else {
        $error_message = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Clover Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/navbar.css">
    <style>
        :root {
            --primary-color: rgb(91, 9, 9);
            --primary-hover: rgb(147, 42, 42);
            --secondary-color: #6c757d;
            --background-color: #f8f9fa;
            --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: linear-gradient(145deg, white, #f8f9fa);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), transparent);
            opacity: 0.1;
            border-radius: 0 20px 0 100%;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(145deg, #f8f9fa, white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 4px solid white;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-avatar i {
            font-size: 3.5rem;
            color: var(--primary-color);
        }

        .nav-pills .nav-link {
            color: var(--secondary-color);
            border-radius: 12px;
            padding: 12px 25px;
            margin: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-pills .nav-link:hover {
            background-color: rgba(91, 9, 9, 0.1);
            transform: translateX(5px);
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(91, 9, 9, 0.2);
        }

        .nav-pills .nav-link i {
            font-size: 1.2rem;
        }

        .profile-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(91, 9, 9, 0.15);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(91, 9, 9, 0.2);
        }

        .outfit-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .outfit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .outfit-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .outfit-card:hover .outfit-image {
            transform: scale(1.05);
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .verification-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.2);
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
        }

        .tab-content {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Include your navbar here -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Clover Logo" height="60">
                <div>
                    <h1 class="company-name">Clover</h1>
                    <p class="company-subtitle">Outfit Rentals</p>
                </div>
            </a>

            <div class="nav-links ms-auto">
                <a href="outfit.php" class="nav-link">RENT OUTFITS</a>
                <a href="lending.php" class="nav-link">EARN THROUGH US</a>
                <a href="outfit.php?gender=male" class="nav-link">MEN</a>
                <a href="outfit.php?occasion=wedding" class="nav-link">BRIDAL</a>
                
                <div class="nav-icons">
                    <a href="cart.php" class="icon-link position-relative">
                        <i class="bi bi-bag"></i>
                        <span id="cartCount" class="cart-badge badge rounded-pill bg-danger" style="display: none;">0</span>
                    </a>
                    <a href="profile.php" class="icon-link active">
                        <i class="bi bi-person"></i>
                    </a>
                    <a href="index.php" class="icon-link">
                        <i class="bi bi-house"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center">
                    <div class="profile-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                </div>
                <div class="col">
                    <h2><?php echo htmlspecialchars($user['name']); ?>
                        <?php if ($user['is_verified']): ?>
                            <span class="verification-badge">
                                <i class="bi bi-check-circle-fill"></i> Verified
                            </span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted mb-0">
                        <i class="bi bi-person-badge"></i> 
                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="nav flex-column nav-pills" role="tablist">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#profile-info">
                        <i class="bi bi-person-circle"></i> Profile Information
                    </button>
                    <?php if ($user['role'] === 'lender'): ?>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#my-outfits">
                        <i class="bi bi-handbag"></i> My Outfits
                    </button>
                    <?php endif; ?>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#rental-history">
                        <i class="bi bi-clock-history"></i> Rental History
                    </button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#account-settings">
                        <i class="bi bi-gear"></i> Account Settings
                    </button>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content profile-content">
                    <!-- Profile Information Tab -->
                    <div class="tab-pane fade show active" id="profile-info">
                        <h3><i class="bi bi-person-lines-fill"></i> Profile Information</h3>
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="" class="mt-4">
                            <div class="mb-4">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- My Outfits Tab (for Lenders) -->
                    <?php if ($user['role'] === 'lender'): ?>
                    <div class="tab-pane fade" id="my-outfits">
                        <h3>My Outfits</h3>
                        <div class="row g-4">
                            <?php while ($outfit = $outfits->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="outfit-card position-relative">
                                        <img src="uploads/<?php echo htmlspecialchars($outfit['image1']); ?>" 
                                             class="outfit-image" alt="Outfit">
                                        <span class="status-badge status-<?php echo strtolower($outfit['status']); ?>">
                                            <?php echo ucfirst($outfit['status']); ?>
                                        </span>
                                        <div class="p-3">
                                            <h5><?php echo htmlspecialchars($outfit['description_text']); ?></h5>
                                            <p class="text-muted mb-2">MRP: ₹<?php echo number_format($outfit['mrp'], 2); ?></p>
                                            <p class="text-muted mb-0">Listed: 
                                                <?php echo date('M d, Y', strtotime($outfit['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Rental History Tab -->
                    <div class="tab-pane fade" id="rental-history">
                        <h3>Rental History</h3>
                        <!-- Add rental history content here -->
                        <p class="text-muted">No rental history available.</p>
                    </div>

                    <!-- Account Settings Tab -->
                    <div class="tab-pane fade" id="account-settings">
                        <h3>Account Settings</h3>
                        <div class="mb-4">
                            <h5>Change Password</h5>
                            <form method="POST" action="change_password.php">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                        <div class="mb-4">
                            <h5>Delete Account</h5>
                            <p class="text-muted">Once you delete your account, there is no going back.</p>
                            <button class="btn btn-danger" onclick="confirmDelete()">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add smooth transitions for tab changes
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelector('.profile-content').style.opacity = '0';
            setTimeout(() => {
                document.querySelector('.profile-content').style.opacity = '1';
            }, 150);
        });
    });

    function confirmDelete() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            window.location.href = 'delete_account.php';
        }
    }
    </script>
</body>
</html> 