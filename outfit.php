<?php 
session_start();
$userName = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

include 'connect.php';  // Make sure this path is correct

// Add this right after your include 'connect.php' line
$debug_query = "SELECT COUNT(*) as total_count FROM tbl_outfit WHERE status = 'approved'";
$debug_result = mysqli_query($conn, $debug_query);
$debug_row = mysqli_fetch_assoc($debug_result);
echo "<!-- Total approved outfits: " . $debug_row['total_count'] . " -->";

// Get filters from URL
$occasion_filter = isset($_GET['occasion']) ? $_GET['occasion'] : null;
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : null;

// At the top of rentnow.php, after session_start()
$is_on_lend = isset($_GET['on_lend']) && $_GET['on_lend'] == '1';

// Get the availability date for on-lend outfits
$available_date = '';
if ($is_on_lend) {
    $date_query = "SELECT created_at 
                   FROM tbl_orders 
                   WHERE outfit_id = ? 
                   AND order_status = 'CONFIRMED'
                   ORDER BY created_at DESC 
                   LIMIT 1";
    $stmt = $conn->prepare($date_query);
    $stmt->bind_param("i", $outfit_id);
    $stmt->execute();
    $date_result = $stmt->get_result();
    $date_data = $date_result->fetch_assoc();
    
    if ($date_data) {
        $end_date = new DateTime($date_data['created_at']);
        $end_date->modify('+16 days'); // 14 days rental + 2 days processing
        $available_date = $end_date->format('M d, Y');
    }
}

// Add this debug section before your main query
echo "<!-- Debug: Checking subcategory IDs and data -->";
$debug_cats = "SELECT id, subcategory_name, category_id FROM tbl_subcategory WHERE LOWER(subcategory_name) IN ('female', 'wedding')";
$debug_cats_result = mysqli_query($conn, $debug_cats);
echo "<!-- Available subcategories: ";
while ($row = mysqli_fetch_assoc($debug_cats_result)) {
    echo "{$row['subcategory_name']}(ID:{$row['id']},Cat:{$row['category_id']}), ";
}
echo " -->";

// Check actual outfit mappings
$debug_outfits = "SELECT o.outfit_id, o.gender_id, o.occasion_id, 
                         g.subcategory_name as gender, 
                         oc.subcategory_name as occasion
                  FROM tbl_outfit o
                  LEFT JOIN tbl_subcategory g ON o.gender_id = g.id
                  LEFT JOIN tbl_subcategory oc ON o.occasion_id = oc.id
                  WHERE o.status = 'approved'";
$debug_outfits_result = mysqli_query($conn, $debug_outfits);
echo "<!-- Outfit mappings: ";
while ($row = mysqli_fetch_assoc($debug_outfits_result)) {
    echo "Outfit#{$row['outfit_id']}(Gender:{$row['gender']},Occasion:{$row['occasion']}), ";
}
echo " -->";

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rent Attire</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="style2.css">
  <link rel="stylesheet" href="styles/navbar.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
  <nav class="navbar navbar-expand-lg main-navbar">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <img src="images/logo.png" alt="Clover Logo" height="60">
        <div>
          <h1 class="company-name">Clover</h1>
          <p class="company-subtitle">Outfit Rentals</p>
        </div>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
    <div class="nav-links ms-auto">
    <a href="outfit.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'outfit.php' && empty($_GET) ? 'active-link' : ''; ?>">RENT OUTFITS</a>
    <a href="lending.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lending.php' ? 'active-link' : ''; ?>">EARN THROUGH US</a>
    <a href="outfit.php?gender=male" class="nav-link <?php echo isset($_GET['gender']) && $_GET['gender'] === 'male' ? 'active-link' : ''; ?>">MEN</a>
    <a href="outfit.php?occasion=wedding" class="nav-link <?php echo isset($_GET['occasion']) && $_GET['occasion'] === 'wedding' ? 'active-link' : ''; ?>">BRIDAL</a>
    
    <?php if ($isLoggedIn): ?>
        <span class="nav-link user-name">Welcome, <?php echo htmlspecialchars($userName); ?></span>
    <div class="nav-icons">
        <a href="cart.php" class="icon-link">
            <i class="bi bi-bag"></i>
        </a>
            <div class="dropdown">
                <a class="icon-link" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <a href="ls.php?showModal=true" class="nav-link">SIGN UP</a>
        <?php endif; ?>
    
        <a href="index.php" class="icon-link">
            <i class="bi bi-house"></i>
        </a>
</div>  
    </div>
  </nav>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-3 p-3 bg-beige sidebar-fixed">
        <h4 class="text-pink">Filters</h4>
        <div class="search-container mb-4">
            <input type="text" class="search-input" id="outfitSearchInput" placeholder="Search outfits...">
            <i class="fas fa-search search-icon"></i>
        </div>
        <div class="accordion" id="filters">
          <?php
          // First fetch all categories
          $category_query = "SELECT * FROM tbl_category";
          $category_result = mysqli_query($conn, $category_query);
          
          while($category = mysqli_fetch_assoc($category_result)) {
              $category_id = $category['id'];
              $category_name = $category['category_name'];
              
              // Create accordion item for each category
              echo '<div class="accordion-item">
                      <h2 class="accordion-header" id="'.$category_name.'Header">
                          <button class="accordion-button collapsed" type="button" 
                                  data-bs-toggle="collapse" 
                                  data-bs-target="#'.$category_name.'Filter" 
                                  aria-expanded="false" 
                                  aria-controls="'.$category_name.'Filter">
                              '.$category_name.'
                          </button>
                      </h2>
                      <div id="'.$category_name.'Filter" 
                           class="accordion-collapse collapse" 
                           aria-labelledby="'.$category_name.'Header">
                          <div class="accordion-body">';
              
              // Fetch subcategories for this category
              $subcategory_query = "SELECT * FROM tbl_subcategory WHERE category_id = ?";
              $stmt = $conn->prepare($subcategory_query);
              $stmt->bind_param("i", $category_id);
              $stmt->execute();
              $subcategory_result = $stmt->get_result();
              
              while($subcategory = mysqli_fetch_assoc($subcategory_result)) {
                  echo '<div class="form-check">
                          <input class="form-check-input filter-checkbox" 
                                 type="checkbox" 
                                 value="'.$subcategory['id'].'" 
                                 id="'.$category_name.$subcategory['id'].'" 
                                 data-type="'.strtolower($category_name).'">
                          <label class="form-check-label" 
                                 for="'.$category_name.$subcategory['id'].'">
                              '.$subcategory['subcategory_name'].'
                          </label>
                      </div>';
              }
              
              echo '</div></div></div>';
          }
          ?>
          <div class="accordion-item">
              <h2 class="accordion-header" id="priceHeader">
                  <button class="accordion-button collapsed" type="button" 
                          data-bs-toggle="collapse" 
                          data-bs-target="#priceFilter" 
                          aria-expanded="false" 
                          aria-controls="priceFilter">
                      Price Range
                  </button>
              </h2>
              <div id="priceFilter" class="accordion-collapse collapse" aria-labelledby="priceHeader">
                  <div class="accordion-body">
                      <div class="form-check">
                          <input class="form-check-input price-range" type="checkbox" 
                                 value="0-1000" id="price1" data-type="price">
                          <label class="form-check-label" for="price1">
                              ₹0 - ₹1,000
                          </label>
                      </div>
                      <div class="form-check">
                          <input class="form-check-input price-range" type="checkbox" 
                                 value="1001-2000" id="price2" data-type="price">
                          <label class="form-check-label" for="price2">
                              ₹1,001 - ₹2,000
                          </label>
                      </div>
                      <!-- Add more price ranges as needed -->
                  </div>
              </div>
          </div>
        </div>
      </div>


      <!-- Main Content -->
      <div class="col-md-9 p-3 main-content">
        <!-- Add "Currently on lend" button -->
        <div class="mb-4 text-end">
          <a href="currently_on_lend.php" class="btn btn-outline-dark">
            <i class="fas fa-exchange-alt me-2"></i> Currently on lend
          </a>
        </div>
        
        <div class="row" id="productGrid">
          <?php
          // Main query here
          $query = "SELECT DISTINCT o.* FROM tbl_outfit o
                   INNER JOIN tbl_subcategory gs ON o.gender_id = gs.id
                   WHERE o.status = 'approved'";

          // Debug the gender value from URL
          echo "<!-- Debug: Gender from URL = " . (isset($_GET['gender']) ? $_GET['gender'] : 'not set') . " -->";

          if (isset($_GET['gender']) && $_GET['gender'] === 'male') {
              // First, let's debug what gender values exist
              $debug_gender = "SELECT id, subcategory_name FROM tbl_subcategory WHERE category_id = (SELECT id FROM tbl_category WHERE category_name = 'Gender')";
              $debug_result = mysqli_query($conn, $debug_gender);
              echo "<!-- Available genders in database: ";
              while($row = mysqli_fetch_assoc($debug_result)) {
                  echo "{$row['id']}:{$row['subcategory_name']}, ";
              }
              echo " -->";
              
              // Add the gender condition
              $query .= " AND gs.subcategory_name IN ('Male', 'male', 'MEN', 'Men', 'men')";
          }

          // Debug the final query
          echo "<!-- Debug: Final Query = " . $query . " -->";

          $result = mysqli_query($conn, $query);

          // Debug the result
          if ($result) {
              echo "<!-- Debug: Number of results = " . mysqli_num_rows($result) . " -->";
          } else {
              echo "<!-- Debug: Query failed - " . mysqli_error($conn) . " -->";
          }
          
          if($result && $result->num_rows > 0) {
              while($outfit = mysqli_fetch_assoc($result)) {
                  // Calculate rental price (20% of MRP)
                  $rental_price = $outfit['mrp'] * 0.20;
                  
                  // Handle image display
                  $baseImageNumber = $outfit['image1'];
                  $imagePath = '';
                  if (!empty($baseImageNumber)) {
                      $baseImageNumber = str_replace('_image1.jpg', '', $baseImageNumber);
                      $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                  }
                  
                  // Get description and brand name
                  $description_query = "SELECT d.description_text FROM tbl_description d WHERE d.id = ?";
                  $stmt = $conn->prepare($description_query);
                  $stmt->bind_param("i", $outfit['description_id']);
                  $stmt->execute();
                  $description_result = $stmt->get_result();
                  $description = $description_result->fetch_assoc();
                  $description_text = (!empty($description) && !empty($description['description_text'])) ? $description['description_text'] : 'Outfit #'.$outfit['outfit_id'];
                  
                  $brand_query = "SELECT s.subcategory_name FROM tbl_subcategory s WHERE s.id = ?";
                  $stmt = $conn->prepare($brand_query);
                  $stmt->bind_param("i", $outfit['brand_id']);
                  $stmt->execute();
                  $brand_result = $stmt->get_result();
                  $brand = $brand_result->fetch_assoc();
                  $brand_name = (!empty($brand) && !empty($brand['subcategory_name'])) ? $brand['subcategory_name'] : '';
                  
                  // Check if outfit is currently on lend - using the actual table structure
                  // We'll consider an outfit on lend if it has any CONFIRMED orders
                  $lend_query = "SELECT COUNT(*) as is_on_lend FROM tbl_orders 
                                WHERE outfit_id = ? AND order_status = 'CONFIRMED'";
                  $stmt = $conn->prepare($lend_query);
                  $stmt->bind_param("i", $outfit['outfit_id']);
                  $stmt->execute();
                  $lend_result = $stmt->get_result();
                  $lend_data = $lend_result->fetch_assoc();
                  
                  $is_on_lend = ($lend_data['is_on_lend'] > 0);
                  $available_date = "";
                  
                  if ($is_on_lend) {
                      // Since we don't have an end_date, we'll use created_at + 14 days as an estimated return date
                      $order_date_query = "SELECT created_at FROM tbl_orders 
                                         WHERE outfit_id = ? AND order_status = 'CONFIRMED'
                                         ORDER BY created_at DESC LIMIT 1";
                      $stmt = $conn->prepare($order_date_query);
                      $stmt->bind_param("i", $outfit['outfit_id']);
                      $stmt->execute();
                      $date_result = $stmt->get_result();
                      $date_data = $date_result->fetch_assoc();
                      
                      if ($date_data) {
                          // Default rental period of 14 days + 2 days for processing
                          $end_date = new DateTime($date_data['created_at']);
                          $end_date->modify('+16 days');
                          $available_date = $end_date->format('M d, Y');
                      }
                  }
                  
                  echo '<div class="col-md-4 mb-4">
                          <div class="card" data-card-index="'.$outfit['outfit_id'].'">';
                  
                  // Show "On Lend" badge if the outfit is rented
                  if ($is_on_lend) {
                      echo '<div class="on-lend-badge"><i class="fas fa-clock mr-1"></i> On Lend</div>';
                  }
                  
                  echo '<div class="card-img-container">';
                  
                  if (!empty($imagePath) && file_exists($imagePath)) {
                      echo '<img src="'.$imagePath.'" class="card-img-top" alt="Outfit Image">';
                  } else {
                      echo '<div class="no-image-placeholder">Outfit #'.$outfit['outfit_id'].'</div>';
                  }
                  
                  echo '</div>
                              <div class="card-body">
                              <h5 class="card-title">'.htmlspecialchars($description_text).'</h5>
                              <p class="card-text">';
                  
                  if (!empty($brand_name)) {
                      echo htmlspecialchars($brand_name).'<br>';
                  }
                  
                  echo '₹'.number_format($rental_price, 2);
                  
                  echo '</p>';
                  
                  // Change button text and behavior based on outfit availability
                  if ($is_on_lend) {
                      // Use the btn-pink class instead of btn-outline-secondary
                      echo '<button class="btn btn-pink" onclick="window.location.href=\'rentnow.php?id='.$outfit['outfit_id'].'&on_lend=1\'">View</button>';
                      
                      // Show availability date AFTER the button
                      if (!empty($available_date)) {
                          echo '<div class="mt-2"><span class="availability-date">Available after: '.$available_date.'</span></div>';
                      }
                  } else {
                      echo '<button class="btn btn-pink" onclick="window.location.href=\'rentnow.php?id='.$outfit['outfit_id'].'\'">Rent Now</button>';
                  }
                  
                  echo '</div>
                          </div>
                      </div>';
              }
          } else {
              echo '<div class="col-12">
                      <div class="alert alert-info">No outfits available for rent at the moment.</div>
                    </div>';
          }
          ?>
        </div>
      </div>
    </div>
    <!-- Add a back-to-top button -->
<button id="backToTop" class="btn btn-pink" style="display: none; position: fixed; bottom: 20px; right: 20px;">Top</button>
<!-- Modal -->
<!-- <div id="rentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-body">
            <img id="modalMainImage" src="" alt="Product Image" class="modal-main-image">
            <div class="rental-thumbnail-container"></div>
            <div class="rental-product-details"></div>
        </div>
    </div>
</div> -->

  </div>
</div>






  </div>
  

</body>
</html>

<script>
$(document).ready(function() {
    // Initialize Bootstrap accordion
    var accordionItems = document.querySelectorAll('.accordion-item');
    accordionItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (e.target.classList.contains('accordion-button')) {
                var collapse = new bootstrap.Collapse(this.querySelector('.accordion-collapse'));
            }
        });
    });

    // Filter functionality
    $('.filter-checkbox').on('change', function() {
        filterOutfits();
    });

    $('.price-range').on('change', function() {
        filterOutfits();
    });

    function filterOutfits() {
        let filters = {};
        
        // Initialize filter categories
        $('.filter-checkbox, .price-range').each(function() {
            const type = $(this).data('type');
            if (!filters[type]) {
                filters[type] = [];
            }
        });

        // Collect checked filters
        $('.filter-checkbox:checked, .price-range:checked').each(function() {
            const type = $(this).data('type');
            const value = $(this).val();
            filters[type].push(value);
        });

        $.ajax({
            url: 'filter_outfits.php',
            method: 'POST',
            data: { filters: filters },
            success: function(response) {
                $('#productGrid').html(response);
            }
        });
    }

    // Add search functionality
    $('#outfitSearchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('#productGrid .card').each(function() {
            const cardTitle = $(this).find('.card-title').text().toLowerCase();
            const cardText = $(this).find('.card-text').text().toLowerCase();
            const matchesSearch = cardTitle.includes(searchTerm) || cardText.includes(searchTerm);
            
            $(this).closest('.col-md-4').toggle(matchesSearch);
        });
        
        // Show message if no results
        const visibleCards = $('#productGrid .col-md-4:visible').length;
        if (visibleCards === 0 && searchTerm !== '') {
            if ($('#no-results-message').length === 0) {
                $('#productGrid').append('<div id="no-results-message" class="col-12 text-center py-4"><p class="text-muted">No outfits found matching "' + searchTerm + '"</p></div>');
            } else {
                $('#no-results-message p').text('No outfits found matching "' + searchTerm + '"');
            }
        } else {
            $('#no-results-message').remove();
        }
    });
});
</script>

<style>
.card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    height: 100%;
    transition: transform 0.2s;
    padding: 0;
    background-color: white;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.card-img-container {
    position: relative;
    width: 100%;
    height: 490px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    padding: 0;
    margin: 0;
}

.card-img-top {
    width: 100%;
    height: 600px;
    object-fit: cover;
    object-position: top;
    padding: 0;
    margin: 0;
    background-color: white;
}

.no-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    color: #6c757d;
    font-size: 16px;
    text-align: center;
}

.card-body {
    padding: 1rem;
    background: white;
    margin-top: 0;
    border-top: 1px solid #eee;
}

.card-title {
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
    color: #333;
    font-weight: 500;
}

.card-text {
    color: #666;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.btn-pink {
    width: 100%;
    padding: 0.5rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

.search-container {
    position: relative;
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border-radius: 30px;
    border: 1px solid #e9ecef;
    font-size: 14px;
    transition: all 0.3s;
    background-color: #f7f7f7;
}

.search-input:focus {
    outline: none;
    border-color: #d9b199;
    box-shadow: 0 0 0 3px rgba(217, 177, 153, 0.2);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

/* Updated On Lend Badge - Maroon color */
.on-lend-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: rgba(128, 0, 32, 0.85); /* Maroon color */
    color: white;
    padding: 6px 12px;
    border-radius: 30px;
    font-weight: 500;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    z-index: 10;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    text-transform: uppercase;
}

.availability-date {
    color: #495057;
    font-size: 0.85rem;
    font-weight: 500;
    display: block;
    font-style: italic;
    border-left: 3px solid #dc3545;
    padding-left: 8px;
    text-align: center;
}

.form-disabled {
    opacity: 0.7;
    pointer-events: none;
    user-select: none;
}

.form-disabled input,
.form-disabled button,
.form-disabled select,
.form-disabled textarea {
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
    cursor: not-allowed !important;
}

.lend-notice {
    background-color: rgba(128, 0, 32, 0.1);
    border: 1px solid rgba(128, 0, 32, 0.3);
    color: #800020;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
}

.back-button {
    display: block;
    width: 100%;
    padding: 12px;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 18px;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    margin-top: 20px;
}

.back-button:hover {
    background-color: #5a6268;
    color: white;
    text-decoration: none;
}
</style>

<!-- Add this temporarily to check the uploads directory -->
<?php
$uploadsDir = 'uploads/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    echo "<!-- Files in uploads directory: ";
    print_r($files);
    echo " -->";
}
?>

<?php if ($is_on_lend): ?>
    <div class="lend-notice">
        <i class="fas fa-clock mr-2"></i> This outfit is currently on lend and will be available after: <?php echo $available_date; ?>
    </div>
<?php endif; ?>

<form id="measurementForm" method="POST" action="save_measurements.php" class="<?php echo $is_on_lend ? 'form-disabled' : ''; ?>">
    <!-- Your existing form content -->
</form>

<?php if ($is_on_lend): ?>
    <!-- Hide the Add to Cart and Proceed buttons for on-lend outfits -->
    <style>
        #addToCartBtn, #proceedButton {
            display: none;
        }
    </style>
    
    <!-- Add a back button -->
    <a href="outfit.php" class="back-button">
        <i class="fas fa-arrow-left mr-2"></i> Back to Outfits
    </a>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isOnLend = <?php echo $is_on_lend ? 'true' : 'false'; ?>;
    
    if (isOnLend) {
        // Disable all form inputs
        const form = document.getElementById('measurementForm');
        const inputs = form.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            input.disabled = true;
        });
        
        // Hide the Add to Cart and Proceed buttons
        const addToCartBtn = document.getElementById('addToCartBtn');
        const proceedButton = document.getElementById('proceedButton');
        
        if (addToCartBtn) addToCartBtn.style.display = 'none';
        if (proceedButton) proceedButton.style.display = 'none';
    }
});
</script>


