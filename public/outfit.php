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
  
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container">
    <a class="navbar-brand" href="#">
      <img src="C:\Users\HP\Downloads\Frame 2.png" alt="" class="clover-logo">
      <p class="clover-logo-text"> Clover <br> Outfit Rentals</p>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav ml-auto align-items-center">
        <a class="nav-link active " id="navItem1" href="outfit.html">RENT OUTFITS <span class="sr-only"></span></a>
        <a class="nav-link" id="navItem2"  href="lending.php">EARN THROUGH US</a>
        <a class="nav-link" id="navItem3"  href="outfit.html">BRIDAL</a>
        <a class="nav-link" id="navItem4" href="ls.php?showModal=true">SIGN UP</a>




        <a class="nav-link" id="navItemIcon1"  href="#"><i class=" bi bi-bag-dash-fill icon-large"></i></a>
        <a class="nav-link" id="navItemIcon2"  href="#"><i class="bi bi-person-circle icon-large"></i></a>
        <a class="nav-link" id="navItemIcon2"  href="#"><i class="fa-solid fa-house"></i></a>

        


       
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
          <!-- Gender Filter -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="genderHeader">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#genderFilter" aria-expanded="false" aria-controls="genderFilter">
                Gender
              </button>
            </h2>
            <div id="genderFilter" class="accordion-collapse collapse" aria-labelledby="genderHeader" data-bs-parent="#filters">
              <div class="accordion-body">
                <input type="checkbox" id="genderAll"> All<br>
                <input type="checkbox" id="genderMale"> Male<br>
                <input type="checkbox" id="genderFemale"> Female<br>
              </div>
            </div>
          </div>


          <!-- Type Filter -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="typeHeader">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#typeFilter" aria-expanded="false" aria-controls="typeFilter">
                Type
              </button>
            </h2>
            <div id="typeFilter" class="accordion-collapse collapse" aria-labelledby="typeHeader" data-bs-parent="#filters">
              <div class="accordion-body">
                <input type="checkbox" id="typeAll"> All<br>
                <input type="checkbox" id="typeSuits"> Suits<br>
                <input type="checkbox" id="typeTuxedo"> Tuxedo<br>
                <input type="checkbox" id="typeBridalLehengas"> Bridal Lehengas<br>
                <input type="checkbox" id="typeDesignerLehengas"> Designer Lehengas<br>
                <input type="checkbox" id="typeGowns"> Gowns<br>
                <input type="checkbox" id="typeSarees"> Sarees<br>

              </div>
            </div>
          </div>


          <!-- Price Filter -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="priceHeader">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#priceFilter" aria-expanded="false" aria-controls="priceFilter">
                Price
              </button>
            </h2>
            <div id="priceFilter" class="accordion-collapse collapse" aria-labelledby="priceHeader" data-bs-parent="#filters">
              <div class="accordion-body">
                <input type="checkbox" id="priceAll"> All<br>
                <input type="checkbox" id="price5000"> Below ₹5000<br>
                <input type="checkbox" id="price10000"> Below ₹10000<br>
                <input type="checkbox" id="price15000"> Below ₹15000<br>
                <input type="checkbox" id="priceAbove15000"> Above ₹15000<br>
              </div>
            </div>
          </div>


          <!-- Brand Filter -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="brandHeader">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#brandFilter" aria-expanded="false" aria-controls="brandFilter">
                Brand
              </button>
            </h2>
            <div id="brandFilter" class="accordion-collapse collapse" aria-labelledby="brandHeader" data-bs-parent="#filters">
              <div class="accordion-body">
                <input type="checkbox" id="brandSabyasaachi"> Sabyasaachi<br>
                <input type="checkbox" id="brandAurora"> Aurora<br>
                <input type="checkbox" id="brandLDS"> LDS<br>
                <input type="checkbox" id="brandFloral"> Floral<br>
                <input type="checkbox" id="brandMayFlower"> May Flower<br>
                <input type="checkbox" id="brandOthers"> May Flower<br>

              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header" id="occassionHeader">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#occassionFilter" aria-expanded="false" aria-controls="brandFilter">
                Occassion
              </button>
            </h2>
            <div id="occassionFilter" class="accordion-collapse collapse" aria-labelledby="occassionHeader" data-bs-parent="#filters">
              <div class="accordion-body">
                <input type="checkbox" id="occassionAll"> All<br>
                <input type="checkbox" id="occassionWedding">Wedding<br>
                <input type="checkbox" id="occassionShoots">Shoots<br>
                <input type="checkbox" id="occassionPrewed">Pre-Wedding<br>
                <input type="checkbox" id="occassionCocktail">Cocktail Party <br>
                <input type="checkbox" id="occassionOther"> Other<br>
               
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Main Content -->
      <div class="col-md-9 p-3 main-content">
        <div class="row" id="productGrid">
          <!-- Example products -->
          <div class="col-md-4">
            <div class="card" data-card-index="0">
              <img src="https://i.pinimg.com/736x/1e/a8/2f/1ea82fa04128a676156c76000e116e5a.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="1">
              <img src="https://i.etsystatic.com/19614853/r/il/99be21/6143062177/il_794xN.6143062177_soh0.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="2">
              <img src="https://i.pinimg.com/736x/d1/e9/a9/d1e9a90a52df539063246651ca25bd01.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="3">
              <img src="https://i.pinimg.com/736x/fd/2a/44/fd2a448485defb14da0ac1e99b6f1b24.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="4">
              <img src="https://i.pinimg.com/736x/5f/a8/55/5fa855a32bbbaf38f26d4256be3032de.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="5">
              <img src="https://i.pinimg.com/736x/a5/dd/8c/a5dd8c51b12fdd76e2bedc43ae4062b7.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="6">
              <img src="https://i.pinimg.com/736x/a5/dd/8c/a5dd8c51b12fdd76e2bedc43ae4062b7.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="7">
              <img src="https://i.pinimg.com/736x/a5/dd/8c/a5dd8c51b12fdd76e2bedc43ae4062b7.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card" data-card-index="8">
              <img src="https://i.pinimg.com/736x/a5/dd/8c/a5dd8c51b12fdd76e2bedc43ae4062b7.jpg" class="card-img-top" alt="Product Image">
              <div class="card-body">
                <h5 class="card-title">Bridal Lehenga</h5>
                <p class="card-text">₹12,999.00</p>
                <button class="btn btn-pink" onclick="window.location.href='rentnow.php?id=${productId}'">Rent Now</button>
              </div>
            </div>
          </div>


          <!-- Add more product cards dynamically (10 total) -->
          <!-- Duplicate this card as needed with varying details -->
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


