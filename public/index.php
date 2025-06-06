<?php
session_start();
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

    <title>Miniproject Landing page</title>

    <style>
      .footer {
        background: linear-gradient(to right, #8b0000, #800000);
        color: #fff;
        padding: 20px 0;
      }

      .footer-container {
        display: flex;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
      }

      .footer-section {
        flex: 1;
        min-width: 200px;
        margin: 10px;
      }

      .footer-section h4 {
        margin-bottom: 10px;
      }

      .footer-section ul {
        list-style-type: none;
        padding: 0;
      }

      .footer-section ul li {
        margin-bottom: 5px;
      }

      .footer-section ul li a {
        color: #fff;
        text-decoration: none;
      }

      .footer-section ul li a:hover {
        text-decoration: underline;
      }

      .social-media {
        display: flex;
        justify-content: flex-start;
        list-style-type: none;
        padding: 0;
      }

      .social-media li {
        margin-right: 10px;
      }

      .social-media li a {
        color: #fff;
        text-decoration: none;
      }

      .social-media li a:hover {
        text-decoration: underline;
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
      <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
          <a class="navbar-brand" href="#">
            <img
              src="C:\Users\HP\Downloads\Frame 2.png"
              alt=""
              class="clover-logo"
            />
            <p class="clover-logo-text"></p>
          </a>
          <button
            class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-target="#navbarNavAltMarkup"
            aria-controls="navbarNavAltMarkup"
            aria-expanded="false"
            aria-label="Toggle navigation"
          >
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav ml-auto align-items-center">
              <a class="nav-link active" id="navItem1" href="outfit.html"
                >RENT OUTFITS <span class="sr-only">(current)</span></a
              >
              <a class="nav-link" id="navItem2" href="lending.php"
                >EARN THROUGH US</a
              >
              <a class="nav-link" id="navItem3" href="outfit.html">MEN</a>
              <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <a class="nav-link" id="navItem4" href="#">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                <a class="nav-link" href="logout.php">LOGOUT</a>
              <?php else: ?>
                <a class="nav-link" id="navItem4" href="ls.php?showModal=true"
                  >SIGN UP</a
                >
              <?php endif; ?>

              <a class="nav-link" id="navItemIcon1" href="#"
                ><i class="bi bi-bag-dash-fill icon-large"></i
              ></a>
              <a class="nav-link" id="navItemIcon2" href="#"
                ><i class="bi bi-person-circle icon-large"></i
              ></a>
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
        <h2>Best Rentals</h2>
        <div class="bento-box-container">
          <div class="bento-box">
            <img
              src="https://img.perniaspopupshop.com/catalog/product/s/a/SAKOM072456_1.jpg?impolicy=listingimagenew"
              alt="Outfit 1"
            />
            <div class="info">
              <h3>Outfit Name 1</h3>
              <p>₹Price</p>
            </div>
          </div>
          <div class="bento-box">
            <img
              src="https://qivii.com/cdn/shop/products/sabyasachi-art-silk-lehenga-choli-for-woman-designer-ghaghra-choli-indian-wedding-bridal-lahnga-choli-party-wear-silk-lengha-choli-ready-to-wear-qivii-1_1024x1024.jpg?v=1726015261"
              alt="Outfit 2"
            />
            <div class="info">
              <h3>Outfit Name 2</h3>
              <p>₹Price</p>
            </div>
          </div>
          <div class="bento-box">
            <img
              src="https://sabyasachi.com/cdn/shop/files/M230425-139-175_768x.jpg?v=1693911464"
              alt="Outfit 3"
            />
            <div class="info">
              <h3>Outfit Name 3</h3>
              <p>₹Price</p>
            </div>
          </div>
          <div class="bento-box">
            <img
              src="https://cdn.pixelbin.io/v2/black-bread-289bfa/t.resize(w:2500)/Manish_1/Evara_Sangeet_Part_1/MM-P-LH-60944-BL-DP_C-XS-1.jpg"
              alt="Outfit 4"
            />
            <div class="info">
              <h3>Outfit Name 4</h3>
              <p>₹Price</p>
            </div>
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
