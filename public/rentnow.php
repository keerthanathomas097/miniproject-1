<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Outfit | Clover Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="rentnow.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row product-details-container">
            <!-- Image Gallery Section -->
            <div class="col-md-6">
                <div class="image-gallery">
                    <div class="thumbnails">
                        <img src="https://imgs-aashniandco.gumlet.io/pub/media/catalog/product/cache/9306e52ccddca80c1fb471fb66a6193f/b/u/buy_nude_floral_embroidery_sharara_set_by_seema_gujral-segdec2323__2_.jpg" class="thumbnail active" onclick="changeImage(this.src)">
                        <img src="https://imgs-aashniandco.gumlet.io/pub/media/catalog/product/cache/9306e52ccddca80c1fb471fb66a6193f/b/u/buy_nude_floral_embroidery_sharara_set_by_seema_gujral-segdec2323__4_.jpg" class="thumbnail" onclick="changeImage(this.src)">
                        <img src="https://imgs-aashniandco.gumlet.io/pub/media/catalog/product/cache/9306e52ccddca80c1fb471fb66a6193f/b/u/buy_nude_floral_embroidery_sharara_set_by_seema_gujral-segdec2323__5_.jpg" class="thumbnail" onclick="changeImage(this.src)">
                        <img src="https://imgs-aashniandco.gumlet.io/pub/media/catalog/product/cache/9306e52ccddca80c1fb471fb66a6193f/b/u/buy_nude_floral_embroidery_sharara_set_by_seema_gujral-segdec2323__5_.jpg" class="thumbnail" onclick="changeImage(this.src)">
                    </div>
                    <div class="main-image">
                        <img src="https://imgs-aashniandco.gumlet.io/pub/media/catalog/product/cache/9306e52ccddca80c1fb471fb66a6193f/b/u/buy_nude_floral_embroidery_sharara_set_by_seema_gujral-segdec2323__2_.jpg" id="mainImage" alt="Product Image">
                    </div>
                </div>
            </div>

            <!-- Product Details and Form Section -->
            <div class="col-md-6">
                <div class="product-info">
                    <h2 class="product-title">Bridal Lehenga</h2>
                    <p class="product-price">₹12,999.00</p>
                    
                    <!-- Size Chart Icon -->
                    <div class="size-chart">
                        <i class="fas fa-ruler" onclick="showSizeChart()"></i>
                        <span>Size Chart</span>
                    </div>

                    <!-- Measurements Form -->
                    <div class="measurements-form">
                        <h4>Enter Your Measurements</h4>
                        <div class="form-group">
                            <label>Height (inches)</label>
                            <input type="number" class="form-control" id="height">
                        </div>
                        <div class="form-group">
                            <label>Shoulder Width (inches)</label>
                            <input type="number" class="form-control" id="shoulder">
                        </div>
                        <div class="form-group">
                            <label>Bust (inches)</label>
                            <input type="number" class="form-control" id="bust">
                        </div>
                        <div class="form-group">
                            <label>Waist (inches)</label>
                            <input type="number" class="form-control" id="waist">
                        </div>
                    </div>

                    <!-- Available Sizes -->
                    <div class="size-selection">
                        <h4>Available Sizes</h4>
                        <div class="size-buttons">
                            <button class="size-btn">XS</button>
                            <button class="size-btn">S</button>
                            <button class="size-btn">M</button>
                            <button class="size-btn">L</button>
                            <button class="size-btn">XL</button>
                        </div>
                    </div>

                    <!-- Rental Duration -->
                    <div class="rental-duration">
                        <h4>Rental Duration</h4>
                        <div class="duration-options">
                            <input type="radio" id="3days" name="duration" value="3">
                            <label for="3days">3 Days</label>

                            <input type="radio" id="5days" name="duration" value="5">
                            <label for="5days">5 Days</label>

                            <input type="radio" id="7days" name="duration" value="7">
                            <label for="7days">7 Days</label>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div class="date-selection">
                        <h4>Select Event Date</h4>
                        <input type="text" id="eventDate" class="form-control datepicker">
                        
                        <div class="rental-dates">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="text" id="startDate" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="text" id="endDate" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button class="submit-btn">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="rentnow.js"></script>
</body>
</html>
