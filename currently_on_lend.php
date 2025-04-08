<?php 
session_start();
$userName = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

include 'connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Currently On Lend | Clover Outfit Rentals</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="style2.css">
  <link rel="stylesheet" href="styles/navbar.css">
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
        <a href="outfit.php" class="nav-link">RENT OUTFITS</a>
        <a href="lending.php" class="nav-link">EARN THROUGH US</a>
        <a href="outfit.php?gender=male" class="nav-link">MEN</a>
        <a href="outfit.php?occasion=wedding" class="nav-link">BRIDAL</a>
        
        <?php if ($isLoggedIn): ?>
            <span class="nav-link user-name">Welcome, <?php echo htmlspecialchars($userName); ?></span>
        <?php else: ?>
            <a href="ls.php?showModal=true" class="nav-link">SIGN UP</a>
        <?php endif; ?>
        
        <div class="nav-icons">
            <a href="cart.php" class="icon-link">
                <i class="bi bi-bag"></i>
            </a>
            <?php if ($isLoggedIn): ?>
                <div class="dropdown">
                    <a class="icon-link" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            <a href="index.php" class="icon-link">
                <i class="bi bi-house"></i>
            </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row mb-4">
      <div class="col-12">
        <h2 class="text-center">Currently On Lend</h2>
        <p class="text-center text-muted">These outfits are currently being rented and will be available again soon.</p>
        <div class="text-center mb-4">
          <a href="outfit.php" class="btn btn-outline-dark">
            <i class="fas fa-arrow-left me-2"></i> Back to Available Outfits
          </a>
        </div>
      </div>
    </div>

    <div class="row" id="productGrid">
      <?php
      // Fetch outfits that are currently rented
      $query = "SELECT DISTINCT o.*, d.description_text, s.subcategory_name as brand_name,
                       MAX(DATE_FORMAT(m.end_date, '%Y-%m-%d')) as end_date
                FROM tbl_outfit o
                JOIN tbl_orders ord ON o.outfit_id = ord.outfit_id
                LEFT JOIN tbl_description d ON o.description_id = d.id
                LEFT JOIN tbl_subcategory s ON o.brand_id = s.id
                LEFT JOIN tbl_measurements m ON m.outfit_id = ord.outfit_id AND m.user_id = ord.user_id
                WHERE ord.order_status = 'CONFIRMED'
                GROUP BY o.outfit_id, o.description_id, o.brand_id, d.description_text, s.subcategory_name
                ORDER BY MAX(m.end_date) ASC";

      $result = mysqli_query($conn, $query);
      
      if($result && $result->num_rows > 0) {
          while($outfit = mysqli_fetch_assoc($result)) {
              // Handle image display
              $baseImageNumber = $outfit['image1'];
              $imagePath = '';
              if (!empty($baseImageNumber)) {
                  $baseImageNumber = str_replace('_image1.jpg', '', $baseImageNumber);
                  $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
              }
              
              // Calculate the "Available by" date (end_date + 2 days instead of 4)
              $end_date = new DateTime($outfit['end_date']);
              $available_by = clone $end_date;
              $available_by->modify('+2 days'); // Change from +4 days to +2 days
              $available_by_formatted = $available_by->format('d M Y');
              
              echo '<div class="col-md-4 mb-4">
                      <div class="card">
                          <div class="ribbon ribbon-top-right"><span>On Lend</span></div>
                          <div class="card-img-container">';
                          
                          if (!empty($imagePath) && file_exists($imagePath)) {
                              echo '<img src="'.$imagePath.'" class="card-img-top" alt="Outfit Image">';
                          } else {
                              echo '<div class="no-image-placeholder">No Image Available</div>';
                          }
                          
                          echo '</div>
                          <div class="card-body">
                              <h5 class="card-title">'.htmlspecialchars($outfit['description_text']).'</h5>
                              <p class="card-text mb-1">
                                   '.htmlspecialchars($outfit['brand_name']).'
                              </p>
                              <div class="available-by p-2 mt-3 mb-2 rounded bg-light text-center">
                                  <span class="badge bg-warning text-dark p-2 mb-2 d-block">
                                      <i class="fas fa-calendar-check me-1"></i> Available by: '.$available_by_formatted.'
                                  </span>
                              </div>
                          </div>
                      </div>
                  </div>';
          }
      } else {
          echo '<div class="col-12">
                  <div class="alert alert-info">No outfits are currently on lend.</div>
                </div>';
      }
      ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
.card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    height: 100%;
    transition: transform 0.2s;
    padding: 0;
    background-color: white;
    position: relative;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.card-img-container {
    position: relative;
    width: 100%;
    height: 400px;
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
    height: 500px;
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

/* Ribbon styling */
.ribbon {
   width: 150px;
   height: 150px;
   overflow: hidden;
   position: absolute;
   z-index: 1;
}

.ribbon-top-right {
   top: -10px;
   right: -10px;
}

.ribbon-top-right::before,
.ribbon-top-right::after {
   border-top-color: transparent;
   border-right-color: transparent;
}

.ribbon-top-right::before {
   top: 0;
   left: 0;
}

.ribbon-top-right::after {
   bottom: 0;
   right: 0;
}

.ribbon-top-right span {
   left: -8px;
   top: 30px;
   transform: rotate(45deg);
}

.ribbon span {
   position: absolute;
   display: block;
   width: 225px;
   padding: 8px 0;
   background-color: #d9534f;
   box-shadow: 0 5px 10px rgba(0,0,0,.1);
   color: #fff;
   font-size: 13px;
   font-weight: 600;
   text-shadow: 0 1px 1px rgba(0,0,0,.2);
   text-transform: uppercase;
   text-align: center;
}

.available-by {
    background-color: #f8f9fa;
    border-left: 3px solid #ffc107;
}
</style> 