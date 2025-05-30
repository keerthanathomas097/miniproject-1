<?php
session_start();
include 'connect.php';

// Simply check if logged in (no redirect)
$logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];

// Only set these variables if user is logged in
$username = $logged_in ? $_SESSION['username'] : '';
$user_role = $logged_in ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="stylesheet"
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
      integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="style.css" />
    <script
      src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
      integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
      integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
      integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV"
      crossorigin="anonymous"
    ></script>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&family=Marcellus&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/navbar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Miniproject Landing page</title>

    <style>
      .footer {
        background: linear-gradient(to right, #8b0000, #800000);
        color: #fff;
        padding: 40px 0;
        font-family: 'Jost', sans-serif;
      }

      .footer-container {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
      }

      .footer-section {
        padding: 0 15px;
      }

      .footer-section h4 {
        color: #fff;
        font-size: 1.2rem;
        margin-bottom: 20px;
        font-weight: 500;
        position: relative;
      }

      .footer-section h4:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 30px;
        height: 2px;
        background-color: #fff;
      }

      .footer-section p {
        margin-bottom: 10px;
        font-size: 0.9rem;
        line-height: 1.6;
      }

      .footer-section ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
      }

      .footer-section ul li {
        margin-bottom: 10px;
      }

      .footer-section ul li a {
        color: #fff;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
      }

      .footer-section ul li a:hover {
        color: #f0f0f0;
        text-decoration: none;
      }

      .social-media {
        display: flex;
        gap: 15px;
      }

      .social-media li a {
        font-size: 1.1rem;
      }

      @media (max-width: 992px) {
        .footer-container {
          grid-template-columns: repeat(3, 1fr);
        }
      }

      @media (max-width: 768px) {
        .footer-container {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 576px) {
        .footer-container {
          grid-template-columns: 1fr;
        }
        
        .footer-section {
          text-align: center;
        }
        
        .footer-section h4:after {
          left: 50%;
          transform: translateX(-50%);
        }
        
        .social-media {
          justify-content: center;
        }
      }

      .best-rentals {
          padding: 60px 0;
          background-color: #f8f9fa;
      }

      .section-title {
          text-align: center;
          margin-bottom: 40px;
          color: #800020;
          font-family: 'Marcellus', serif;
          font-size: 2.5rem;
          font-weight: 600;
          position: relative;
      }

      .section-title:after {
          content: '';
          display: block;
          width: 60px;
          height: 3px;
          background: #800020;
          margin: 15px auto;
      }

      .outfit-card {
          position: relative;
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 4px 15px rgba(0,0,0,0.1);
          transition: all 0.3s ease;
          background: white;
          height: 400px; /* Fixed height for uniform appearance */
      }

      .outfit-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      }

      .outfit-image {
          display: block;
          position: relative;
          height: 100%;
          width: 100%;
          overflow: hidden;
      }

      .outfit-image img {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform 0.5s ease;
      }

      .outfit-card:hover .outfit-image img {
          transform: scale(1.05);
      }

      .no-image {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: #f0f0f0;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #666;
          font-style: italic;
      }

      .no-outfits {
          text-align: center;
          padding: 40px;
          color: #666;
          font-style: italic;
      }

      @media (max-width: 768px) {
          .section-title {
              font-size: 2rem;
          }
          
          .outfit-card {
              height: 350px;
          }
      }
    </style>
  </head>
  <body>
    <?php
    // Add welcome alert if user just logged in
    if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome']) {
        echo "<script>
            alert('Welcome " . htmlspecialchars($_SESSION['username']) . "! You have successfully signed in.');
        </script>";
        unset($_SESSION['show_welcome']); // So it only shows once
    }
    ?>

    <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
      <symbol id="calendar" viewBox="0 0 24 24">
        <path
          d="M19 4h-1V2h-2v2H8V2H6v2H5C3.9 4 3 4.9 3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"
        />
      </symbol>
      <symbol id="shopping-bag" viewBox="0 0 24 24">
        <path
          d="M16 6V4a4 4 0 0 0-8 0v2H4v16h16V6h-4zM10 4a2 2 0 1 1 4 0v2h-4V4zm8 18H6V8h12v14z"
        />
      </symbol>
      <symbol id="heart" viewBox="0 0 24 24">
        <path
          d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
        />
      </symbol>
      <symbol id="truck" viewBox="0 0 24 24">
        <path
          d="M20 8h-3V4H3v13h2.18a3 3 0 0 0 5.64 0h2.36a3 3 0 0 0 5.64 0H21v-4l-1-5zM6.5 18a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM17.5 18a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM5 12V6h10v6H5zm11 0V9h2.75l.6 3H16z"
        />
      </symbol>
    </svg>

    <div class="main-body">
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
              <a href="outfit.php" class="nav-link">RENT OUTFITS</a>
              <a href="lending.php" class="nav-link">EARN THROUGH US</a>
              <a href="outfit.php?gender=male" class="nav-link">MEN</a>
              <a href="outfit.php?occasion=wedding" class="nav-link">BRIDAL</a>
              <?php if(!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']): ?>
                <a href="ls.php?showModal=true" class="nav-link">SIGN UP</a>
              <?php endif; ?>
              
              <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <span class="nav-link"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
                <a href="ls.php?showModal=true" class="nav-link">LOGIN</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </nav>
      <div class="heading">
        <h1>Clover Outfit Rentals</h1>

        <p>
          Discover a smarter way to enjoy luxury fashion with Clover Outfit
          Rentals. Rent stunning outfits for any occasion while earning passive
          income from your wardrobe.
        </p>
      </div>
      <div class="swiper">
        <div class="swiper-wrapper">
          <!-- Slide 1 -->
          <div class="swiper-slide">
            <a href="outfit.php" target="_blank">
              <img
                src="https://i.pinimg.com/736x/7d/82/83/7d8283f28c7f300338d6e162e3ed5614.jpg"
                alt="Image 1"
              />
            </a>
            <div class="slider-text">
              <div class="slider-text-heading">RENT OUTFITS</div>
              <div class="slider-text-subtext">
                Rent premium quality luxuary outfits for your memorable moments.
              </div>
              <a href="#" class="slider-text-link">DISCOVER NOW</a>
            </div>
          </div>
          <!-- Slide 2 -->
          <div class="swiper-slide">
            <a href="outfit.php" target="_blank">
              <img
                src="https://i.pinimg.com/736x/8a/cc/26/8acc267bebebf7a2fd1b1267af65dd97.jpg"
                alt="Image 2"
              />
            </a>
            <div class="slider-text">
              <div class="slider-text-heading">RENT OUTFITS</div>
              <div class="slider-text-subtext">
                Rent premium quality luxuary outfits for your memorable moments.
              </div>
              <a href="#" class="slider-text-link">DISCOVER NOW</a>
            </div>
          </div>
          <!-- Slide 3 -->
          <div class="swiper-slide">
            <a href="outfit.php" target="_blank">
              <img
                src="https://i.pinimg.com/736x/d1/e9/a9/d1e9a90a52df539063246651ca25bd01.jpg"
                alt="Image 3"
              />
            </a>
            <div class="slider-text">
              <div class="slider-text-heading">RENT OUTFITS</div>
              <div class="slider-text-subtext">
                Rent premium quality luxuary outfits for your memorable moments.
              </div>
              <a href="#" class="slider-text-link">DISCOVER NOW</a>
            </div>
          </div>
          <!-- Slide 4 -->
          <div class="swiper-slide">
            <a href="outfit.php" target="_blank">
              <img
                src="https://i.pinimg.com/736x/25/79/30/2579304cf6fdf3748683189e3d104319.jpg"
                alt="Image 4"
              />
            </a>
            <div class="slider-text">
              <div class="slider-text-heading">RENT OUTFITS</div>
              <div class="slider-text-subtext">
                Rent premium quality luxuary outfits for your memorable moments.
              </div>
            </div>
          </div>
          <!-- Slide 5 -->
          <div class="swiper-slide">
            <a href="outfit.php" target="_blank">
              <img
                src="https://i.pinimg.com/736x/a8/95/c3/a895c3cc6e0b5643ece0eb6640dce887.jpg"
                alt="Image 5"
              />
            </a>
            <div class="slider-text">
              <div class="slider-text-heading">RENT OUTFITS</div>
              <div class="slider-text-subtext">
                Rent premium quality luxuary outfits for your memorable moments.
              </div>
            </div>
          </div>
        </div>
        <!-- Navigation Buttons -->
        <div class="swiper-button-next" id="swiper-button-next-id"></div>
        <div class="swiper-button-prev" id="swiper-button-prev-id"></div>
      </div>
      <section class="features py-5">
        <div class="container">
          <div class="row">
            <div
              class="col-md-3 text-center"
              data-aos="fade-in"
              data-aos-delay="0"
            >
              <div class="py-5">
                <svg width="38" height="38" viewBox="0 0 24 24">
                  <use xlink:href="#calendar"></use>
                </svg>
                <h4 class="element-title text-capitalize my-3">
                  Select a Style
                </h4>
                <p>
                  Pick your perfect style from our collection of designer
                  outfits and accessories.
                </p>
              </div>
            </div>
            <div
              class="col-md-3 text-center"
              data-aos="fade-in"
              data-aos-delay="300"
            >
              <div class="py-5">
                <svg width="38" height="38" viewBox="0 0 24 24">
                  <use xlink:href="#shopping-bag"></use>
                </svg>
                <h4 class="element-title text-capitalize my-3">
                  Book your Outfit
                </h4>
                <p>
                  Book your look for 3, 5, 7 or 10 days. Outfit will be altered
                  to your size and dry cleaned before delivery.
                </p>
              </div>
            </div>
            <div
              class="col-md-3 text-center"
              data-aos="fade-in"
              data-aos-delay="600"
            >
              <div class="py-5">
                <svg width="38" height="38" viewBox="0 0 24 24">
                  <use xlink:href="#heart"></use>
                </svg>
                <h4 class="element-title text-capitalize my-3">Flaunt It</h4>
                <p>
                  Flaunt your look with that perfect outfit chosen by you and
                  enjoy the compliments
                </p>
              </div>
            </div>
            <div
              class="col-md-3 text-center"
              data-aos="fade-in"
              data-aos-delay="900"
            >
              <div class="py-5">
                <svg width="38" height="38" viewBox="0 0 24 24">
                  <use xlink:href="#truck"></use>
                </svg>
                <h4 class="element-title text-capitalize my-3">Return It</h4>
                <p>
                  Pack the outfit and we'll pick it up a day after your occasion
                  or the dates chosen by you.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="best-rentals">
        <div class="container">
          <h2 class="section-title">Best Rentals</h2>
          <div class="row g-4">
            <?php
            // Query to fetch 4 published outfits with their details
            $best_outfits_query = "SELECT o.* 
                                  FROM tbl_outfit o
                                  WHERE o.status = 'approved' AND o.image1 IS NOT NULL
                                  ORDER BY o.outfit_id DESC
                                  LIMIT 4";
            
            $best_outfits_result = mysqli_query($conn, $best_outfits_query);
            
            if ($best_outfits_result && mysqli_num_rows($best_outfits_result) > 0) {
                while ($outfit = mysqli_fetch_assoc($best_outfits_result)) {
                    // Handle image path
                    $imagePath = '';
                    if (!empty($outfit['image1'])) {
                        $baseImageNumber = str_replace('_image1.jpg', '', $outfit['image1']);
                        $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                    }
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="outfit-card">
                            <?php if (!empty($imagePath) && file_exists($imagePath)): ?>
                                <a href="rentnow.php?id=<?php echo $outfit['outfit_id']; ?>" class="outfit-image">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                         alt="Outfit">
                                </a>
                            <?php else: ?>
                                <div class="no-image">No Image Available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><p class="no-outfits">No outfits available at the moment.</p></div>';
            }
            ?>
          </div>
        </div>
      </section>

      <footer class="footer">
        <div class="footer-container">
          <div class="footer-section">
            <h4>About Us</h4>
            <p>Learn more about our story and mission.</p>
          </div>
          <div class="footer-section">
            <h4>Services</h4>
            <ul>
              <li><a href="#">Bridal Wear</a></li>
              <li><a href="#">Groom Wear</a></li>
              <li><a href="#">Accessories</a></li>
              <li><a href="#">Custom Tailoring</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Contact</h4>
            <p>Email: contact@weddingoutfit.com</p>
            <p>Phone: +123 456 7890</p>
          </div>
          <div class="footer-section">
            <h4>Follow Us</h4>
            <ul class="social-media">
              <li><a href="#">Facebook</a></li>
              <li><a href="#">Instagram</a></li>
              <li><a href="#">Twitter</a></li>
              <li><a href="#">Pinterest</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Customer Support</h4>
            <p>FAQs</p>
            <p>Shipping & Returns</p>
            <p>Privacy Policy</p>
            <p>Terms & Conditions</p>
          </div>
        </div>
      </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script>
      // Initialize Swiper
      const swiper = new Swiper(".swiper", {
        loop: true, // Infinite loop
        slidesPerView: 3, // Show 3 slides at a time
        spaceBetween: 60,
        speed: 1000, // Space between slides
        navigation: {
          nextEl: ".swiper-button-next", // Next button
          prevEl: ".swiper-button-prev", // Previous button
        },
      });
    </script>
  </body>
</html>
