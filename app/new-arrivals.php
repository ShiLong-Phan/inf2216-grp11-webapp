<?php
// Include database connection file
include_once "utils/dbconnect.php";

// Check if database connection is successful
if (!$conn) {
    die("<!-- Database connection failed in new-arrivals.php: " . mysqli_connect_error() . " -->");
}

// Only define INCLUDED if it's not already defined
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}
// Get the most recent products (new arrivals) sorted by date added
// Show in-stock products first, then sort by date
$stmt = $conn->prepare("SELECT * FROM ssdgroup11db.products 
                        ORDER BY 
                          CASE WHEN prod_stock > 0 THEN 0 ELSE 1 END, -- In-stock items first
                          prod_date_added DESC 
                        LIMIT 8");

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
          <h2 class="section-title">New arrivals</h2>
          <div class="d-flex align-items-center">
            <a href="#" class="btn-link text-decoration-none">View All Products →</a>
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
                <?php
                // Check if product is out of stock
                $isOutOfStock = isset($product['prod_stock']) && $product['prod_stock'] <= 0;
                ?>
                <div class="product-item swiper-slide">
                  <?php if ($isOutOfStock): ?>
                    <div class="out-of-stock-badge">
                      <span class="badge bg-danger">Out of Stock</span>
                    </div>
                  <?php endif; ?>
                  
                  <figure>
                    <a href="product-detail.php?id=<?php echo $product['prod_id']; ?>" title="<?php echo htmlspecialchars($product['prod_name']); ?>">
                      <img src="<?php echo !empty($product['prod_image']) 
                        ? $product['prod_image'] 
                        : 'images/product-placeholder.png'; ?>"
                        class="tab-image <?php echo $isOutOfStock ? 'grayscale' : ''; ?>"
                        alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                    </a>
                  </figure>
                  
                  <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                  <span class="qty">1 Unit</span>
                  <span class="price">$<?php echo number_format($product['prod_price'], 2); ?></span>
                  
                  <div class="d-flex align-items-center justify-content-center flex-wrap gap-1">
                    <?php if (!$isOutOfStock): ?>
                      <!-- Only show quantity controls and add to cart if in stock -->
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
                      
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <a href="#" class="add-to-cart nav-link" data-product-id="<?php echo $product['prod_id']; ?>">
                        <span class="add-to-cart-content">
                          Add to Cart
                          <svg width="16" height="16"><use xlink:href="#cart"></use></svg>
                        </span>
                      </a>
                    <?php else: ?>
                      <a href="login.php" class="nav-link">
                        Login to Buy
                        <svg width="16" height="16"><use xlink:href="#user"></use></svg>
                      </a>
                    <?php endif; ?>
                    <?php else: ?>
                      <!-- Placeholder div that mimics the appearance of the controls -->
                      <div class="d-flex w-100 justify-content-center">
                        <span class="out-of-stock-message">Currently Unavailable</span>
                      </div>
                    <?php endif; ?>
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

<style>
  .out-of-stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 5;
  }
  
  .out-of-stock-message {
    color: #dc3545;
    font-weight: bold;
    font-size: 0.9rem;
    text-align: center;
    margin-top: 5px;
  }
  
  .grayscale {
    filter: grayscale(50%);
    opacity: 0.9;
  }

  .add-to-cart {
    display: inline-block;
    font-size: 0.85rem;
  }

  .add-to-cart-content {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .add-to-cart-content svg {
    flex-shrink: 0;
    height: 14px;
    width: 14px;
  }

  .add-to-cart:hover {
  text-decoration: underline;
  }
  
  .notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 250px;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    opacity: 0.9;
  }
</style>

<script>  
  plusButtons.forEach(button => {
    button.addEventListener('click', function() {
      const input = this.closest('.product-qty').querySelector('input');
      let value = parseInt(input.value);
      value++;
      input.value = value;
    });
  });
  
  // Add to cart functionality using AJAX
  const addToCartButtons = document.querySelectorAll('.products-carousel .add-to-cart');
  addToCartButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const productId = this.getAttribute('data-product-id');
      const quantityInput = this.closest('.product-item').querySelector('input[name="quantity"]');
      const quantity = quantityInput ? quantityInput.value : 1;
      
      // Create form data for the AJAX request
      const formData = new FormData();
      formData.append('product_id', productId);
      formData.append('quantity', quantity);
      
      // Send AJAX request to add-to-cart.php
      fetch('add-to-cart.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Show success message
          showNotification('success', data.message);
          
          // Update cart total if needed
          updateCartDisplay();
        } else {
          // Show error message
          showNotification('error', data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while adding to cart');
      });
    });
  });
  
  // Function to show notification
  function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} notification`;
    notification.textContent = message;
    
    // Add to the document
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
      notification.remove();
    }, 1500);
  }
  
  // Function to update cart display (total, count, etc.)
  function updateCartDisplay() {
    // You can add AJAX call here to get updated cart data if needed
    // This example just reloads the page after a short delay
    setTimeout(() => {
      window.location.reload();
    }, 1000);
  }
</script>