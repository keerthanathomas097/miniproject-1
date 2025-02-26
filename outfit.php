<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

include 'connect.php';  // Make sure this path is correct
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rent Attire</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
      <div class="collapse navbar-collapse" id="navbarContent">
        <div class="nav-links ms-auto">
          <a href="outfit.php" class="nav-link active-link">RENT OUTFITS</a>
          <a href="lending.php" class="nav-link">EARN THROUGH US</a>
          <a href="men.php" class="nav-link">MEN</a>
          <a href="bridal.php" class="nav-link">BRIDAL</a>
          <a href="ls.php?showModal=true" class="nav-link">SIGN UP</a>
          <div class="nav-icons">
            <a href="cart.php" class="icon-link">
              <i class="bi bi-bag"></i>
            </a>
            <a href="profile.php" class="icon-link">
              <i class="bi bi-person"></i>
            </a>
            <a href="index.php" class="icon-link">
              <i class="bi bi-house"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-3 p-3 bg-beige sidebar-fixed">
        <h4 class="text-pink">Filters</h4>
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
        </div>
      </div>


      <!-- Main Content -->
      <div class="col-md-9 p-3 main-content">
        <div class="row" id="productGrid">
          <?php
          // Fetch published outfits with their descriptions
          
          
          $query = "SELECT o.*, d.description_text, s.subcategory_name as brand_name 
                    FROM tbl_outfit o
                    LEFT JOIN tbl_description d ON o.description_id = d.id
                    LEFT JOIN tbl_subcategory s ON o.brand_id = s.id
                    WHERE o.description_id IS NOT NULL";
                    
          $result = mysqli_query($conn, $query);
          
          if($result->num_rows > 0) {
              while($outfit = mysqli_fetch_assoc($result)) {
                  // Calculate rental price (20% of MRP)
                  $rental_price = $outfit['mrp'] * 0.20;
                  
                  echo '<div class="col-md-4">
                          <div class="card" data-card-index="'.$outfit['outfit_id'].'">
                              <img src="uploads/'.$outfit['image1'].'" class="card-img-top" alt="Product Image">
                              <div class="card-body">
                                  <h5 class="card-title">'.htmlspecialchars($outfit['description_text']).'</h5>
                                  <p class="card-text">
                                       '.htmlspecialchars($outfit['brand_name']).'<br>
                                       ₹'.number_format($rental_price, 2).'
                                  </p>
                                  <button class="btn btn-pink" onclick="window.location.href=\'rentnow.php?id='.$outfit['outfit_id'].'\'">Rent Now</button>
                              </div>
                          </div>
                      </div>';
              }
          } else {
              echo '<div class="col-12">
                      <div class="alert alert-info">No published outfits available.</div>
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

    function filterOutfits() {
        let filters = {};
        
        // Initialize filter categories dynamically
        $('.filter-checkbox').each(function() {
            const type = $(this).data('type');
            if (!filters[type]) {
                filters[type] = [];
            }
        });

        // Collect checked filters
        $('.filter-checkbox:checked').each(function() {
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
});
</script>


