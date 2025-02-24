<?php
include 'connect.php';

$filters = $_POST['filters'];

$query = "SELECT DISTINCT o.*, d.description_text, s.subcategory_name as brand_name 
          FROM tbl_outfit o
          LEFT JOIN tbl_description d ON o.description_id = d.id
          LEFT JOIN tbl_subcategory s ON o.brand_id = s.id
          LEFT JOIN tbl_outfit_occasion oo ON o.outfit_id = oo.outfit_id
          WHERE o.description_id IS NOT NULL";

// Add conditions for each filter type
foreach($filters as $type => $values) {
    if (!empty($values)) {
        $ids = implode(',', array_map('intval', $values));
        switch($type) {
            case 'gender':
                $query .= " AND o.gender_id IN ($ids)";
                break;
            case 'type':
                $query .= " AND o.type_id IN ($ids)";
                break;
            case 'price range':
                $query .= " AND o.price_range_id IN ($ids)";
                break;
            case 'brand':
                $query .= " AND o.brand_id IN ($ids)";
                break;
            case 'occasion':
                $query .= " AND oo.occasion_id IN ($ids)";
                break;
        }
    }
}

$result = mysqli_query($conn, $query);

if($result && $result->num_rows > 0) {
    while($outfit = mysqli_fetch_assoc($result)) {
        $rental_price = $outfit['mrp'] * 0.20;
        
        echo '<div class="col-md-4">
                <div class="card" data-card-index="'.$outfit['outfit_id'].'">
                    <img src="uploads/'.$outfit['image1'].'" class="card-img-top" alt="Product Image">
                    <div class="card-body">
                        <h5 class="card-title">'.htmlspecialchars($outfit['description_text']).'</h5>
                        <p class="card-text">
                            Brand: '.htmlspecialchars($outfit['brand_name']).'<br>
                            Rental Price: ₹'.number_format($rental_price, 2).'
                        </p>
                        <button class="btn btn-pink" onclick="window.location.href=\'rentnow.php?id='.$outfit['outfit_id'].'\'">Rent Now</button>
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
