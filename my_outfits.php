<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connect.php';

// Check if user is logged in and is a lender
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header("Location: ls.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch lender details
$lender_query = "SELECT name, email FROM tbl_users WHERE user_id = ?";
$lender_stmt = $conn->prepare($lender_query);

if ($lender_stmt === false) {
    die("Error preparing lender query: " . $conn->error);
}

$lender_stmt->bind_param("i", $user_id);
if (!$lender_stmt->execute()) {
    die("Error executing lender query: " . $lender_stmt->error);
}

$lender_result = $lender_stmt->get_result();
$lender = $lender_result->fetch_assoc();
$lender_stmt->close();

// For debugging - check if lender data is retrieved
if (empty($lender)) {
    echo "<!-- Debug: Lender data not found for user_id: $user_id -->";
}

// Fetch all outfits for this lender
$outfits_query = "SELECT o.outfit_id, o.status, o.mrp, o.image1, o.created_at, o.purchase_year, o.city, 
                  d.description_text, b.subcategory_name as brand_name, t.subcategory_name as type_name,
                  s.subcategory_name as size_name, g.subcategory_name as gender_name,
                  oc.subcategory_name as occasion_name
                  FROM tbl_outfit o 
                  LEFT JOIN tbl_description d ON o.description_id = d.id
                  LEFT JOIN tbl_subcategory b ON o.brand_id = b.id
                  LEFT JOIN tbl_subcategory t ON o.type_id = t.id
                  LEFT JOIN tbl_subcategory s ON o.size_id = s.id
                  LEFT JOIN tbl_subcategory g ON o.gender_id = g.id
                  LEFT JOIN tbl_subcategory oc ON o.occasion_id = oc.id
                  WHERE o.email = ? 
                  ORDER BY o.created_at DESC";

$outfits_stmt = $conn->prepare($outfits_query);

if ($outfits_stmt === false) {
    die("Error preparing outfits query: " . $conn->error);
}

$outfits_stmt->bind_param("s", $lender['email']);
if (!$outfits_stmt->execute()) {
    die("Error executing outfits query: " . $outfits_stmt->error);
}

$outfits_result = $outfits_stmt->get_result();
$outfits = $outfits_result->fetch_all(MYSQLI_ASSOC);
$outfits_stmt->close();

// Get stats
$total_outfits = count($outfits);
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($outfits as $outfit) {
    $status = strtolower($outfit['status']);
    if ($status == 'pending') $pending_count++;
    else if ($status == 'approved') $approved_count++;
    else if ($status == 'rejected') $rejected_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collection | Fashion Share</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: rgb(91, 9, 9);
            --secondary: rgb(147, 42, 42);
            --accent: rgb(217, 177, 153);
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --gray: #6c757d;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
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
            overflow-y: auto;
            z-index: 100;
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
            display: flex;
            align-items: center;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
            color: white;
            text-decoration: none;
        }

        .menu-item:hover {
            background-color: var(--secondary);
            text-decoration: none;
            color: white;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
            position: relative;
        }

        .page-title {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .page-description {
            color: var(--gray);
            font-size: 16px;
            max-width: 600px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 600;
            color: var(--primary);
        }

        .icon-total { color: var(--primary); }
        .icon-approved { color: var(--success); }
        .icon-pending { color: var(--warning); }
        .icon-rejected { color: var(--danger); }

        .filter-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--dark);
            font-weight: 600;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray);
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: var(--light);
            font-size: 14px;
        }

        .outfit-collection {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .outfit-item {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .outfit-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }

        .outfit-image-wrapper {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .outfit-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .outfit-item:hover .outfit-image {
            transform: scale(1.1);
        }

        .outfit-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .status-approved {
            background-color: var(--success);
            color: white;
        }

        .status-pending {
            background-color: var(--warning);
            color: var(--dark);
        }

        .status-rejected {
            background-color: var(--danger);
            color: white;
        }

        .outfit-details {
            padding: 20px;
        }

        .outfit-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 50px;
        }

        .outfit-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: var(--gray);
        }

        .meta-item i {
            color: var(--secondary);
        }

        .divider {
            height: 1px;
            background-color: #eee;
            margin: 15px 0;
        }

        .outfit-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-mrp {
            color: var(--gray);
            text-decoration: line-through;
            font-size: 14px;
        }

        .price-rental {
            font-weight: 700;
            color: var(--primary);
            font-size: 20px;
        }

        .outfit-date {
            font-size: 12px;
            color: var(--gray);
            margin-top: 10px;
            text-align: right;
        }

        .no-outfits {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-outfits i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-outfits h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .no-outfits p {
            color: var(--gray);
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-add-outfit {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--accent);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-add-outfit:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            gap: 10px;
        }

        .page-item {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background-color: white;
            color: var(--dark);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .page-item:hover, .page-item.active {
            background-color: var(--accent);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
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
            <a href="lender_dashboard.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="lending.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-plus-circle"></i> Lend Outfit
            </a>
            <a href="my_outfits.php" class="menu-item" style="text-decoration: none; color: white; background-color: var(--secondary);">
                <i class="fas fa-tshirt"></i> My Collection
            </a>
            <a href="current_rentals.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-exchange-alt"></i> Rentals
            </a>
            <div class="menu-item">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </div>
            <a href="lender_profile.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-user"></i> Profile 
            </a>
            <div class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Fashion Collection</h1>
            <p class="page-description">
                View and manage all the outfits in your lending collection. Expand your inventory to increase your earning potential.
            </p>
        </div>

        <!-- Stats cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon icon-total">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="stat-title">Total Items</div>
                <div class="stat-value"><?php echo $total_outfits; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-title">Approved</div>
                <div class="stat-value"><?php echo $approved_count; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-title">Pending</div>
                <div class="stat-value"><?php echo $pending_count; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-title">Rejected</div>
                <div class="stat-value"><?php echo $rejected_count; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-container">
            <h3 class="filter-title">Filter Collection</h3>
            <div class="filters">
                <div class="filter-group">
                    <label for="status-filter" class="filter-label">Status</label>
                    <select id="status-filter" class="filter-select">
                        <option value="all">All Statuses</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category-filter" class="filter-label">Category</label>
                    <select id="category-filter" class="filter-select">
                        <option value="all">All Categories</option>
                        <!-- Populate dynamically if needed -->
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort-filter" class="filter-label">Sort By</label>
                    <select id="sort-filter" class="filter-select">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="price-low">Price: Low to High</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (empty($outfits)): ?>
            <div class="no-outfits">
                <i class="fas fa-tshirt"></i>
                <h3>Your Collection is Empty</h3>
                <p>You haven't listed any outfits yet. Start sharing your fashion collection today and earn money from items you don't wear regularly.</p>
                <a href="lending.php" class="btn-add-outfit">
                    <i class="fas fa-plus-circle"></i> Add Your First Outfit
                </a>
            </div>
        <?php else: ?>
            <div class="outfit-collection">
                <?php foreach ($outfits as $outfit): 
                    // Calculate rental price (20% of MRP)
                    $rental_price = $outfit['mrp'] * 0.20;
                    
                    // Handle image path
                    $imagePath = '';
                    if (!empty($outfit['image1'])) {
                        $baseImageNumber = str_replace('_image1.jpg', '', $outfit['image1']);
                        $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                    }
                ?>
                    <div class="outfit-item" data-status="<?php echo strtolower($outfit['status']); ?>">
                        <div class="outfit-image-wrapper">
                            <?php if (!empty($imagePath) && file_exists($imagePath)): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($outfit['description_text'] ?? 'Fashion outfit'); ?>" class="outfit-image">
                            <?php else: ?>
                                <div class="outfit-image" style="background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-tshirt" style="font-size: 50px; color: #ddd;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="outfit-status-badge status-<?php echo strtolower($outfit['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($outfit['status'])); ?>
                            </div>
                        </div>
                        <div class="outfit-details">
                            <h3 class="outfit-name"><?php echo htmlspecialchars($outfit['description_text'] ?? 'Untitled Outfit'); ?></h3>
                            
                            <div class="outfit-meta">
                                <div class="meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo htmlspecialchars($outfit['brand_name'] ?? 'Unknown Brand'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-venus-mars"></i>
                                    <span><?php echo htmlspecialchars($outfit['gender_name'] ?? 'Unisex'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-ruler"></i>
                                    <span><?php echo htmlspecialchars($outfit['size_name'] ?? 'Standard'); ?></span>
                                </div>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="outfit-pricing">
                                <div class="price-mrp">MRP: ₹<?php echo number_format($outfit['mrp'], 2); ?></div>
                                <div class="price-rental">₹<?php echo number_format($rental_price, 2); ?>/day</div>
                            </div>
                            
                            <div class="outfit-date">
                                Listed on <?php echo date('M d, Y', strtotime($outfit['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Optional pagination -->
            <?php if (count($outfits) > 12): ?>
            <div class="pagination">
                <a href="#" class="page-item active">1</a>
                <a href="#" class="page-item">2</a>
                <a href="#" class="page-item">3</a>
                <a href="#" class="page-item">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>

    <script>
        // Filter functionality
        document.getElementById('status-filter').addEventListener('change', filterOutfits);
        document.getElementById('category-filter').addEventListener('change', filterOutfits);
        document.getElementById('sort-filter').addEventListener('change', filterOutfits);
        
        function filterOutfits() {
            const statusFilter = document.getElementById('status-filter').value;
            const outfitItems = document.querySelectorAll('.outfit-item');
            
            outfitItems.forEach(item => {
                if (statusFilter === 'all' || item.dataset.status === statusFilter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Make outfit cards clickable to view details
        document.querySelectorAll('.outfit-item').forEach(item => {
            item.addEventListener('click', function() {
                const outfitId = this.querySelector('.outfit-actions a').getAttribute('href').split('=')[1];
                window.location.href = 'outfit_details.php?id=' + outfitId;
            });
        });

        // Page load event for debugging
        window.addEventListener('load', function() {
            console.log('Collection page loaded successfully');
        });
    </script>
</body>
</html>