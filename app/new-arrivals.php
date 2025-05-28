<?php
// Include your database connection file
include_once "utils/dbconnect.php";

// Check if database connection is successful
if (!$conn) {
    die("<!-- Database connection failed in new-arrivals.php: " . mysqli_connect_error() . " -->");
}

// Get the most recent products (new arrivals) sorted by date added
$stmt = $conn->prepare("SELECT * FROM ssdgroup11db.products ORDER BY prod_date_added DESC LIMIT 8");

if (!$stmt) {
    die("<!-- Statement preparation failed in new-arrivals.php: " . $conn->error . " -->");
}

$stmt->execute();
$result = $stmt->get_result();

// Store new arrivals for display
$new_arrivals = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $new_arrivals[] = $row;
    }
    echo "<!-- New arrivals query executed. Number of products found: " . count($new_arrivals) . " -->";
} else {
    echo "<!-- New arrivals query execution failed -->";
}
?>

<section class="py-5 overflow-hidden">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="section-header d-flex justify-content-between">
          <h2 class="section-title">Just arrived</h2>
          <div class="d-flex align-items-center">
            <a href="#" class="btn-link text-decoration-none">View All Categories →</a>
            <div class="swiper-buttons">
              <button class="swiper-prev products-carousel-prev btn btn-primary">❮</button>
              <button class="swiper-next products-carousel-next btn btn-primary">❯</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="products-carousel swiper">
          <div class="swiper-wrapper">
            <?php if (empty($new_arrivals)): ?>
              <div class="col-12 text-center py-5">
                <p>No new arrivals found.</p>
              </div>
            <?php else: ?>
              <?php foreach ($new_arrivals as $product): ?>
                <div class="product-item swiper-slide">
                  <?php if (rand(1, 3) == 1): // Randomly show discount badge for demo ?>
                    <span class="badge bg-success position-absolute m-3">-<?php echo rand(10, 30); ?>%</span>
                  <?php endif; ?>
                  <a href="#" class="btn-wishlist"><svg width="24" height="24">
                      <use xlink:href="#heart"></use>
                    </svg></a>
                  <figure>
                    <a href="product.php?id=<?php echo $product['prod_id']; ?>" title="<?php echo htmlspecialchars($product['prod_name']); ?>">
                      <img src="<?php echo !empty($product['prod_image']) ? 'images/products/' . $product['prod_image'] : 'images/product-placeholder.png'; ?>" 
                           class="tab-image" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                    </a>
                  </figure>
                  <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                  <span class="qty">1 Unit</span>
                  <span class="rating"><svg width="24" height="24" class="text-primary">
                      <use xlink:href="#star-solid"></use>
                    </svg> <?php echo number_format(rand(35, 50) / 10, 1); ?></span>
                  <span class="price">$<?php echo number_format($product['prod_price'], 2); ?></span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                      <span class="input-group-btn">
                        <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                          <svg width="16" height="16">
                            <use xlink:href="#minus"></use>
                          </svg>
                        </button>
                      </span>
                      <input type="text" id="na_quantity_<?php echo $product['prod_id']; ?>" name="quantity" class="form-control input-number" value="1">
                      <span class="input-group-btn">
                        <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                          <svg width="16" height="16">
                            <use xlink:href="#plus"></use>
                          </svg>
                        </button>
                      </span>
                    </div>
                    <a href="#" class="nav-link add-to-cart" data-product-id="<?php echo $product['prod_id']; ?>">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <!-- / products-carousel -->
      </div>
    </div>
  </div>
</section>

<script>
// Add to cart functionality specifically for new arrivals
document.addEventListener('DOMContentLoaded', function() {
  // This code only affects elements within the new-arrivals section
  const newArrivalsSection = document.querySelector('section:has(.section-title:contains("Just arrived"))');
  if (!newArrivalsSection) return;
  
  const minusButtons = newArrivalsSection.querySelectorAll('.quantity-left-minus');
  const plusButtons = newArrivalsSection.querySelectorAll('.quantity-right-plus');
  
  minusButtons.forEach(button => {
    button.addEventListener('click', function() {
      const input = this.closest('.product-qty').querySelector('input');
      let value = parseInt(input.value);
      if (value > 1) {
        value--;
        input.value = value;
      }
    });
  });
  
  plusButtons.forEach(button => {
    button.addEventListener('click', function() {
      const input = this.closest('.product-qty').querySelector('input');
      let value = parseInt(input.value);
      value++;
      input.value = value;
    });
  });
  
  const addToCartButtons = newArrivalsSection.querySelectorAll('.add-to-cart');
  addToCartButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const productId = this.getAttribute('data-product-id');
      const quantityInput = this.closest('.product-item').querySelector('input[name="quantity"]');
      const quantity = quantityInput ? quantityInput.value : 1;
      
      console.log(`Adding new arrival product ID ${productId} with quantity ${quantity} to cart`);
      alert(`Product added to cart! (ID: ${productId}, Quantity: ${quantity})`);
    });
  });
});
</script>