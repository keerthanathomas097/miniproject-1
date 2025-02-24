<?php 
session_start();
include 'connect.php';

// Fetch total user count
$sql = "SELECT COUNT(*) AS user_count FROM tbl_users"; 
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_count = $row['user_count'];
} else {
    $user_count = 0;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    
    
    // Validate input data
    if (!isset($_POST['outfit_id']) || !isset($_POST['status'])) {
        die("Error: Required fields are missing");
    }

    $outfit_id = $_POST['outfit_id'];
    $new_status = strtolower($_POST['status']); // Convert to lowercase immediately
    
    // Validate status values
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        die("Error: Invalid status value");
    }

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE tbl_outfit SET status = ? WHERE outfit_id = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters and execute
    $stmt->bind_param("ss", $new_status, $outfit_id); // Changed to "ss" if outfit_id is string
    
    if ($stmt->execute()) {
        $_SESSION['update_message'] = "Status updated successfully!";
        $stmt->close(); // Close the statement
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $stmt->close(); // Close the statement
        die("Error updating status: " . $stmt->error);
    }
}

// Display status update message if exists
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['update_message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['update_message'] . "</div>";
    unset($_SESSION['update_message']);
}
// ✅ Fetch outfits from database
$query = "SELECT * FROM tbl_outfit";
$result = mysqli_query($conn, $query); 
// Fetch all outfit requests
$query = "SELECT o.outfit_id, u.name, u.email, u.phone, o.type_id, o.size_id, o.brand_id, 
                 o.mrp, o.purchase_year, o.city, o.status, o.created_at, 
                 o.image1, o.image2, o.image3 
          FROM tbl_outfit o 
          JOIN tbl_users u ON o.user_id = u.user_id 
          ORDER BY o.created_at DESC";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Fashion Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Your custom styles here */
</style>
    <style>
        :root { --sidebar-width: 250px; }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #2c3e50;
            color: white;
            padding-top: 20px;
        }
        .main-content { margin-left: var(--sidebar-width); padding: 20px; }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover { background: #34495e; color: #ecf0f1; }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .stat-card { background: linear-gradient(45deg, #3498db, #2980b9); color: white; }
        .table { font-size: 0.9rem; }
        .table thead th { background-color: #f8f9fa; font-weight: 600; }
        .badge { padding: 6px 12px; font-weight: 500; letter-spacing: 0.3px; }
        .table-responsive { overflow-x: auto; }
        img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
        select, button { padding: 5px; margin: 5px; }
        /* Main table container */
  /* Reset any potential conflicting styles */
.admin-dashboard-table table,
.admin-dashboard-table th,
.admin-dashboard-table td,
.admin-dashboard-table tr,
.admin-dashboard-table thead,
.admin-dashboard-table tbody {
    margin: 0;
    padding: 0;
    border: none;
    font-size: 100%;
    font: inherit;
    vertical-align: baseline;
}

/* Main container styles */
.admin-dashboard-table {
    width: 100% !important;
    margin: 20px 0 !important;
    padding: 20px !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1) !important;
    overflow-x: auto !important;
}

/* Table base styles */
.admin-dashboard-table .admin-table {
    width: 100% !important;
    border-collapse: collapse !important;
    background-color: #ffffff !important;
    border: 1px solid #e0e0e0 !important;
}

/* Header styles */
.admin-dashboard-table .admin-table thead tr {
    background-color: #f8f9fa !important;
}

.admin-dashboard-table .admin-table th {
    padding: 15px !important;
    text-align: left !important;
    font-weight: bold !important;
    color: #333333 !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-size: 14px !important;
}

/* Body styles */
.admin-dashboard-table .admin-table tbody tr {
    border-bottom: 1px solid #e0e0e0 !important;
}

.admin-dashboard-table .admin-table tbody tr:hover {
    background-color: #f5f5f5 !important;
}

.admin-dashboard-table .admin-table td {
    padding: 12px 15px !important;
    vertical-align: middle !important;
    font-size: 14px !important;
}

/* Image cell styles */
.admin-dashboard-table .image-cell {
    display: flex !important;
    gap: 5px !important;
    flex-wrap: wrap !important;
}

.admin-dashboard-table .image-cell img {
    width: 50px !important;
    height: 50px !important;
    object-fit: cover !important;
    border-radius: 4px !important;
    border: 1px solid #dee2e6 !important;
}

/* Status badge styles */
.admin-dashboard-table .status-badge {
    padding: 5px 10px !important;
    border-radius: 15px !important;
    font-size: 12px !important;
    font-weight: bold !important;
    text-transform: capitalize !important;
    display: inline-block !important;
}

.admin-dashboard-table .status-badge.approved {
    background-color: #d4edda !important;
    color: #155724 !important;
}

.admin-dashboard-table .status-badge.pending {
    background-color: #fff3cd !important;
    color: #856404 !important;
}

.admin-dashboard-table .status-badge.rejected {
    background-color: #f8d7da !important;
    color: #721c24 !important;
}

/* Action cell styles */
.admin-dashboard-table .action-cell {
    min-width: 150px !important;
}

.admin-dashboard-table .status-select {
    width: 100% !important;
    padding: 6px 10px !important;
    margin-bottom: 5px !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    font-size: 14px !important;
}

.admin-dashboard-table .update-button {
    width: 100% !important;
    padding: 6px 12px !important;
    background-color: #007bff !important;
    color: white !important;
    border: none !important;
    border-radius: 4px !important;
    cursor: pointer !important;
    font-size: 14px !important;
}

.admin-dashboard-table .update-button:hover {
    background-color: #0056b3 !important;
}

/* Responsive styles */
@media screen and (max-width: 1024px) {
    .admin-dashboard-table {
        padding: 10px !important;
    }
    
    .admin-dashboard-table .admin-table {
        min-width: 1000px !important;
    }
}
/* Enhanced table styling */
.admin-table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    background: white !important;
    margin: 20px 0 !important;
    border: 1px solid #e0e0e0 !important;
}

/* Header styling */
.admin-table th {
    background-color: #f8f9fa !important;
    padding: 15px 12px !important;
    border-bottom: 2px solid #dee2e6 !important;
    border-right: 1px solid #dee2e6 !important;
    font-weight: 600 !important;
    text-align: left !important;
    white-space: nowrap !important;
    color: #333 !important;
    font-size: 14px !important;
}

/* Column specific widths */
/* Column specific widths */
.admin-table th:nth-child(1), /* Outfit ID */
.admin-table td:nth-child(1) {
    width: 60px !important;
    min-width: 60px !important;
    
}

.admin-table th:nth-child(2), /* User Name */
.admin-table td:nth-child(2) {
    width: 100px !important;
    min-width: 100px !important;
}

.admin-table th:nth-child(3), /* Email */
.admin-table td:nth-child(3) {
    width: 150px !important;
    min-width: 150px !important;
}

.admin-table th:nth-child(4), /* Phone */
.admin-table td:nth-child(4) {
    width: 100px !important;
    min-width: 100px !important;
}

.admin-table th:nth-child(5), /* Category */
.admin-table td:nth-child(5) {
    width: 80px !important;
    min-width: 80px !important;
}

.admin-table th:nth-child(6), /* Size */
.admin-table td:nth-child(6) {
    width: 60px !important;
    min-width: 60px !important;
}

.admin-table th:nth-child(7), /* Brand */
.admin-table td:nth-child(7) {
    width: 90px !important;
    min-width: 90px !important;
}

.admin-table th:nth-child(8), /* MRP */
.admin-table td:nth-child(8) {
    width: 70px !important;
    min-width: 70px !important;
}

.admin-table th:nth-child(9), /* Year */
.admin-table td:nth-child(9) {
    width: 60px !important;
    min-width: 60px !important;
}

.admin-table th:nth-child(10), /* City */
.admin-table td:nth-child(10) {
    width: 80px !important;
    min-width: 80px !important;
}

.admin-table th:nth-child(11), /* Images */
.admin-table td:nth-child(11) {
    width: 120px !important;
    min-width: 120px !important;
}

/* Status cell */
.admin-table td:nth-last-child(2) {
    width: 80px !important;
    min-width: 80px !important;
}

/* Action cell */
.admin-table td:last-child {
    width: 120px !important;
    min-width: 120px !important;
}

/* Add this to ensure text doesn't wrap awkwardly */
.admin-table td {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

/* Add this for email cells to show ellipsis for long emails */
.admin-table td:nth-child(3) {
    text-overflow: ellipsis !important;
    overflow: hidden !important;
}

/* Status select and button styling */
.status-select {
    width: 100% !important;
    padding: 6px 8px !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    margin-bottom: 5px !important;
}

.update-button {
    width: 100% !important;
    padding: 6px 12px !important;
    background-color:rgb(2, 33, 66) !important;
    color: white !important;
    border: none !important;
    border-radius: 4px !important;
    cursor: pointer !important;
}

/* Container styling */
.admin-dashboard-table {
    margin: 20px !important;
    padding: 20px !important;
    background: white !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    overflow-x: auto !important;
}

/* Responsive handling */
@media screen and (max-width: 1200px) {
    .admin-table {
        min-width: 1200px !important;
    }
}
/* Carousel Container */
/* Updated Carousel Container Styles */
/* Modal container */
#carouselModal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

/* Modal carousel content */
.modal-carousel {
    width: 90%;  /* Increased width */
    height: 90vh; /* Increased height */
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
}

/* Modal image */
#modalCarouselImage {
    max-width: 90%;  /* Increased from previous value */
    max-height: 90vh; /* Increased from previous value */
    width: auto;
    height: auto;
    object-fit: contain;
}

/* Navigation buttons in modal */
#carouselModal .prev-btn,
#carouselModal .next-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    padding: 20px;  /* Increased padding */
    font-size: 24px;  /* Increased font size */
    z-index: 1001;
    width: 60px;  /* Set explicit width */
    height: 60px;  /* Set explicit height */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

#carouselModal .prev-btn { left: 30px; }
#carouselModal .next-btn { right: 30px; }

/* Close button */
#closeCarouselModal {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
}

/* Ensure small carousel in table remains small */
.carousel-container {
    position: relative;
    width: 100px;  /* Size for table view */
    height: 60px;
    overflow: hidden;
}

.carousel-container .carousel img {
    width: 100px;
    height: 60px;
    object-fit: cover;
}
.update-button {
    display: inline-block;
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.update-button:hover {
    background-color: #0056b3;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 4px;
    display: inline-block;
}

.pending { background-color: #ffd700; }
.approved { background-color: #90ee90; }
.rejected { background-color: #ffcccb; }

.alert {
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
}
/* Wrap table inside a scrollable container */
.admin-table-container {
    width: 100%;
    overflow-x: auto; /* Enables horizontal scrolling if needed */
}

/* Make table fit screen */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px; /* Reduce font size to fit content */
}

/* Table Header Styling */
.admin-table thead th {
    background-color: #f4f4f4;
    padding: 10px;
    text-align: left;
    white-space: nowrap; /* Prevents header text from wrapping */
}

/* Table Cell Styling */
.admin-table td {
    padding: 8px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap; /* Prevents text from wrapping */
}

/* Responsive images inside table */
.admin-table img {
    max-width: 60px; /* Adjust image size */
    height: auto;
    display: block;
}

/* Styling for the status dropdown */
.status-select {
    padding: 5px;
    font-size: 12px;
}

/* Update button styling */
.update-button {
    padding: 6px 10px;
    font-size: 12px;
    cursor: pointer;
}

/* Hide overflow for large content */
.admin-table td.action-cell {
    white-space: nowrap;
}

/* Reduce button and select width */
.action-cell form {
    display: flex;
    gap: 5px;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .admin-table {
        font-size: 12px; /* Reduce font size on smaller screens */
    }
    
    .admin-table img {
        max-width: 40px; /* Smaller images on mobile */
    }
    
    .status-select,
    .update-button {
        font-size: 10px;
        padding: 4px;
    }
}

/* Ensure table fits inside the screen */
.admin-table-container {
    width: 100%;
    overflow-x: auto;
}

/* Make table fully responsive */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Ensures columns distribute evenly */
    font-size: 12px;
}

/* Table Header Styling */
.admin-table thead th {
    background-color: #f4f4f4;
    padding: 8px;
    text-align: left;
    white-space: nowrap;
    font-size: 13px;
}

/* Table Cell Styling */
.admin-table td {
    padding: 6px;
    text-align: left;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis; /* Truncate text with '...' */
    white-space: nowrap;
}

/* Reduce column widths */
.admin-table th:nth-child(1), /* Outfit ID */
.admin-table td:nth-child(1),
.admin-table th:nth-child(6), /* Size */
.admin-table td:nth-child(6),
.admin-table th:nth-child(9), /* Year */
.admin-table td:nth-child(9) {
    width: 5%; /* Narrow columns */
}

/* Make columns with longer text take more space */
.admin-table th:nth-child(2), /* User Name */
.admin-table td:nth-child(2),
.admin-table th:nth-child(3), /* Email */
.admin-table td:nth-child(3),
.admin-table th:nth-child(10), /* City */
.admin-table td:nth-child(10) {
    width: 15%;
}

/* Responsive images */
.admin-table img {
    max-width: 50px;
    height: auto;
    display: block;
}

/* Responsive dropdown & button */
.status-select {
    padding: 4px;
    font-size: 11px;
    max-width: 80px;
}

.update-button {
    padding: 4px 6px;
    font-size: 11px;
}

/* Responsive layout for smaller screens */
@media (max-width: 1024px) {
    .admin-table {
        font-size: 11px;
    }

    .admin-table th, .admin-table td {
        padding: 4px;
    }

    .admin-table img {
        max-width: 40px;
    }

    .status-select {
        font-size: 10px;
    }

    .update-button {
        font-size: 10px;
        padding: 3px 5px;
    }
}

/* Mobile-friendly adjustments */
@media (max-width: 768px) {
    .admin-table {
        font-size: 10px;
    }

    .admin-table img {
        max-width: 30px;
    }

    /* Stack some columns into two rows */
    .admin-table td:nth-child(3),
    .admin-table td:nth-child(4),
    .admin-table td:nth-child(5),
    .admin-table td:nth-child(10) {
        display: block;
        width: 100%;
        text-align: left;
    }
}

    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="text-center mb-4">Fashion Rental</h4>
        <nav>
            <a href="#" class="sidebar-link"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a href="#" class="sidebar-link"><i class="fas fa-users me-2"></i> User Management</a>
            <a href="outfit_management.php" class="sidebar-link"><i class="fas fa-tshirt me-2"></i> Outfit Management</a>
            <a href="#" class="sidebar-link"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2>Dashboard Overview</h2>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5><i class="fas fa-users me-2"></i> Total Users</h5>
                            <h3><?php echo number_format($user_count); ?></h3> 
                        </div>
                    </div>
                </div>
            </div>

            <h2>Admin Dashboard - Outfit Requests</h2>
            <div class="table-dashboard-table">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Outfit ID</th>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Brand</th>
                            <th>MRP</th>
                            <th>Year</th>
                            <th>City</th>
                            <th>Images</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['outfit_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php 
                                $type_query = "SELECT subcategory_name FROM tbl_subcategory WHERE id = ?";
                                $stmt = $conn->prepare($type_query);
                                $stmt->bind_param("i", $row['type_id']);
                                $stmt->execute();
                                $type_result = $stmt->get_result();
                                $type = $type_result->fetch_assoc();
                                echo $type['subcategory_name']; 
                            ?></td>
                            <td><?php 
                                $size_query = "SELECT subcategory_name FROM tbl_subcategory WHERE id = ?";
                                $stmt = $conn->prepare($size_query);
                                $stmt->bind_param("i", $row['size_id']);
                                $stmt->execute();
                                $size_result = $stmt->get_result();
                                $size = $size_result->fetch_assoc();
                                echo $size['subcategory_name']; 
                            ?></td>
                            <td><?php 
                                $brand_query = "SELECT subcategory_name FROM tbl_subcategory WHERE id = ?";
                                $stmt = $conn->prepare($brand_query);
                                $stmt->bind_param("i", $row['brand_id']);
                                $stmt->execute();
                                $brand_result = $stmt->get_result();
                                $brand = $brand_result->fetch_assoc();
                                echo $brand['subcategory_name']; 
                            ?></td>
                            <td><?php echo $row['mrp']; ?></td>
                            <td><?php echo $row['purchase_year']; ?></td>
                            <td><?php echo $row['city']; ?></td>
                            <td>
    <div class="carousel-container">
        <div class="carousel" id="carousel-<?php echo $row['outfit_id']; ?>">
            <img src="uploads/<?php echo $row['image1']; ?>" alt="Image 1" onclick="openModal(this, 'uploads/<?php echo $row['image1']; ?>', 'uploads/<?php echo $row['image2']; ?>', 'uploads/<?php echo $row['image3']; ?>')">
            <img src="uploads/<?php echo $row['image2']; ?>" alt="Image 2" onclick="openModal(this, 'uploads/<?php echo $row['image1']; ?>', 'uploads/<?php echo $row['image2']; ?>', 'uploads/<?php echo $row['image3']; ?>')">
            <img src="uploads/<?php echo $row['image3']; ?>" alt="Image 3" onclick="openModal(this, 'uploads/<?php echo $row['image1']; ?>', 'uploads/<?php echo $row['image2']; ?>', 'uploads/<?php echo $row['image3']; ?>')">
        </div>
        <button class="prev-btn" onclick="prevSlide('carousel-<?php echo $row['outfit_id']; ?>')">&#10094;</button>
        <button class="next-btn" onclick="nextSlide('carousel-<?php echo $row['outfit_id']; ?>')">&#10095;</button>
    </div>
</td>


<td>
    <span class="status-badge <?php echo htmlspecialchars(strtolower($row['status'])); ?>">
        <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
    </span>
</td>

<td class="action-cell">
    <form method="post" action="lending_request_mail.php" class="status-form">
        <input type="hidden" name="outfit_id" value="<?php echo htmlspecialchars($row['outfit_id']); ?>">
        <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($row['email']); ?>">
        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($row['name']); ?>">

        <select name="status" class="status-select">
            <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo ($row['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo ($row['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
        </select>
        <p></p>
        <button type="submit" name="update_status" class="update-button">Update</button>
    </form>
</td>

<script>
function updateStatusAndSendEmail(outfitId, status) {
    const formData = new FormData();
    formData.append('outfit_id', outfitId);
    formData.append('status', status);
    formData.append('update_status', true);

    fetch('status_update_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data); // Show success/error message
        location.reload(); // Refresh the page to show updated status
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
    });
}
function updateStatusAndSendEmail(outfitId, status) {
    const formData = new FormData();
    formData.append('outfit_id', outfitId);
    formData.append('status', status);
    formData.append('update_status', true);

    fetch('status_update_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data); // Show success/error message
        location.reload(); // Refresh the page to show updated status
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
    });
}
</script>
    </form>
</td>
               </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Enlarged Image Carousel Modal -->
<div id="carouselModal">
    <span id="closeCarouselModal" class="close">&times;</span>
    <button class="prev-btn" id="prevModalImg">&#10094;</button>
    <div class="modal-carousel">
        <img id="modalCarouselImage" src="" alt="Enlarged Preview">
    </div>
    <button class="next-btn" id="nextModalImg">&#10095;</button>
</div>




<script>

// Small carousel functionality
function nextSlide(carouselId) {
    const carousel = document.getElementById(carouselId);
    const images = carousel.getElementsByTagName('img');
    const firstImage = images[0];
    carousel.appendChild(firstImage);
}

function prevSlide(carouselId) {
    const carousel = document.getElementById(carouselId);
    const images = carousel.getElementsByTagName('img');
    const lastImage = images[images.length - 1];
    carousel.insertBefore(lastImage, images[0]);
}

// Modal carousel functionality
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("carouselModal");
    const modalImage = document.getElementById("modalCarouselImage");
    const closeModal = document.getElementById("closeCarouselModal");
    const prevModalBtn = document.getElementById("prevModalImg");
    const nextModalBtn = document.getElementById("nextModalImg");
    
    let currentImages = [];
    let currentIndex = 0;

    // Function to open modal
    window.openModal = function(clickedImg, ...imagePaths) {
        currentImages = imagePaths;
        currentIndex = currentImages.indexOf(clickedImg.src);
        modalImage.src = currentImages[currentIndex];
        modal.style.display = "flex";
    };

    // Previous button click handler
    prevModalBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
        modalImage.src = currentImages[currentIndex];
    });

    // Next button click handler
    nextModalBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        currentIndex = (currentIndex + 1) % currentImages.length;
        modalImage.src = currentImages[currentIndex];
    });

    // Close modal handlers
    closeModal.addEventListener("click", () => modal.style.display = "none");
    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });

    // Keyboard navigation
    document.addEventListener("keydown", function(e) {
        if (modal.style.display === "flex") {
            if (e.key === "ArrowLeft") prevModalBtn.click();
            if (e.key === "ArrowRight") nextModalBtn.click();
            if (e.key === "Escape") modal.style.display = "none";
        }
    });
});
// Add this to your admin dashboard's JavaScript

</script>
</body>
</html>
