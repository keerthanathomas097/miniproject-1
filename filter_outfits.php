<?php
include 'connect.php';

$filters = $_POST['filters'] ?? [];
$conditions = ["o.description_id IS NOT NULL", "o.status = 'approved'"];
$params = [];
$types = "";

// Build query conditions based on filters
foreach ($filters as $type => $values) {
    if (!empty($values)) {
        switch (strtolower($type)) {
            case 'type':
                $conditions[] = "o.type_id IN (" . implode(',', array_map('intval', $values)) . ")";
                break;
            case 'brand':
                $conditions[] = "o.brand_id IN (" . implode(',', array_map('intval', $values)) . ")";
                break;
            case 'size':
                $conditions[] = "o.size_id IN (" . implode(',', array_map('intval', $values)) . ")";
                break;
            case 'occasion':
                $conditions[] = "oo.occasion_id IN (" . implode(',', array_map('intval', $values)) . ")";
                break;
            case 'gender':
                $conditions[] = "o.gender_id IN (" . implode(',', array_map('intval', $values)) . ")";
                break;
            case 'price':
                // Handle price range
                foreach ($values as $priceRange) {
                    if ($priceRange === '0-10000') {
                        $conditions[] = "o.mrp <= 50000"; // Since rental price is 20% of MRP, MRP should be 50000 for 10000 rental
                    } else if ($priceRange === '10001-100000') {
                        $conditions[] = "o.mrp > 50000";
                    }
                }
                break;
        }
    }
}

// Build the main query
$query = "SELECT DISTINCT o.*, d.description_text, s.subcategory_name as brand_name 
          FROM tbl_outfit o
          LEFT JOIN tbl_description d ON o.description_id = d.id
          LEFT JOIN tbl_subcategory s ON o.brand_id = s.id
          LEFT JOIN tbl_outfit_occasion oo ON o.outfit_id = oo.outfit_id";

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($outfit = mysqli_fetch_assoc($result)) {
        $rental_price = $outfit['mrp'] * 0.20;
        
        // Handle image path
        $baseImageNumber = $outfit['image1'];
        $imagePath = '';
        if (!empty($baseImageNumber)) {
            $baseImageNumber = str_replace('_image1.jpg', '', $baseImageNumber);
            $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
        }

        echo '<div class="col-md-4 mb-4">
                <div class="card" data-card-index="' . $outfit['outfit_id'] . '">
                    <div class="card-img-container">';
        
        if (!empty($imagePath)) {
            echo '<img src="' . $imagePath . '" class="card-img-top" alt="Outfit Image">';
        } else {
            echo '<div class="no-image-placeholder">No Image Available</div>';
        }
        
        echo '</div>
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($outfit['description_text']) . '</h5>
                        <p class="card-text">
                            ' . htmlspecialchars($outfit['brand_name']) . '<br>
                            ₹' . number_format($rental_price, 2) . '
                        </p>
                        <button class="btn btn-pink" onclick="window.location.href=\'rentnow.php?id=' . $outfit['outfit_id'] . '\'">Rent Now</button>
                    </div>
                </div>
            </div>';
    }
} else {
    echo '<div class="col-12">
            <div class="alert alert-info">No outfits found matching your filters.</div>
          </div>';
}
?>
