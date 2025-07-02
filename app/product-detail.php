<?php
// Start session if not already started
include "utils/session.php";

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$productId = $_GET['id'];
$product = null;
$relatedProducts = [];

// Include database connection
include_once "utils/dbconnect.php";

if ($conn) {
    // Get product details
    $stmt = $conn->prepare("SELECT * 
                          FROM ssdgroup11db.products
                          WHERE prod_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Product not found
        header("Location: index.php");
        exit;
    }

    $product = $result->fetch_assoc();
    $stmt->close();

    // Get related products (same category)
    if ($product['prod_category']) {
        $relatedStmt = $conn->prepare("SELECT * FROM ssdgroup11db.products 
                                     WHERE prod_category = ? AND prod_id != ? 
                                     ORDER BY prod_date_added DESC LIMIT 4");
        $relatedStmt->bind_param("ii", $product['prod_category'], $productId);
        $relatedStmt->execute();
        $relatedResult = $relatedStmt->get_result();

        while ($relatedProduct = $relatedResult->fetch_assoc()) {
            $relatedProducts[] = $relatedProduct;
        }

        $relatedStmt->close();
    }
}

// Include header and navbar
include "utils/header.php";
include "utils/navbar.php";
?>

<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="star" viewBox="0 0 16 16">
        <path
            d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z" />
    </symbol>
    <symbol id="cart" viewBox="0 0 24 24">
        <path
            d="M4.2 12.8h16.507a.8.8 0 0 0 .798-.711l1.2-8.4a.8.8 0 0 0-.799-.889H3.2l-.4-2.4H0v1.6h1.6l2.4 14.4a3.2 3.2 0 1 0 6.296.811h5.408a3.2 3.2 0 1 0 2.894-1.837H4.2v-1.6h12a.8.8 0 0 0 0-1.6H4.2v-.274zM20.747 4.4l-.96 6.72H4.88L3.68 4.4h17.067zM6.4 19.6a1.6 1.6 0 1 1 0-3.2 1.6 1.6 0 0 1 0 3.2zm9.6-1.6a1.6 1.6 0 1 1 3.2 0 1.6 1.6 0 0 1-3.2 0z" />
    </symbol>
    <symbol id="heart" viewBox="0 0 24 24">
        <path
            d="M12 20.4c-.514 0-1.027-.196-1.419-.589L3.06 12.289a5.601 5.601 0 0 1 0-7.924 5.601 5.601 0 0 1 7.92 0L12 5.384l1.017-1.019a5.601 5.601 0 0 1 7.921 0 5.601 5.601 0 0 1 0 7.924l-7.52 7.523A2 2 0 0 1 12 20.4zm-5.372-16.8a4.068 4.068 0 0 0-2.848 1.154 4.068 4.068 0 0 0 0 5.746l7.52 7.523a.533.533 0 0 0 .752 0l7.52-7.523a4.068 4.068 0 0 0 0-5.746 4.068 4.068 0 0 0-5.746 0l-1.393 1.393a.533.533 0 0 1-.754 0L10.286 4.754a4.068 4.068 0 0 0-2.848-1.154h-.808z" />
    </symbol>
    <symbol id="plus" viewBox="0 0 24 24">
        <path d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z" />
    </symbol>
    <symbol id="minus" viewBox="0 0 24 24">
        <path d="M19 11H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2Z" />
    </symbol>
</svg>

<main>
    <!-- Breadcrumb -->
    <section class="py-3 bg-light">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <?php if (isset($product['category_name'])): ?>
                        <li class="breadcrumb-item"><a
                                href="category.php?id=<?php echo $product['prod_category']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
                        </li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($product['prod_name']); ?>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Product Details -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Product Image -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0 text-center">
                            <?php if (!empty($product['prod_image'])): ?>
                                <img src="images/products/<?php echo $product['prod_image']; ?>"
                                    alt="<?php echo htmlspecialchars($product['prod_name']); ?>"
                                    class="img-fluid product-detail-img">
                            <?php else: ?>
                                <img src="images/product-placeholder.png" alt="Product placeholder"
                                    class="img-fluid product-detail-img">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-md-6">
                    <div class="ps-md-4">
                        <h1 class="mb-3"><?php echo htmlspecialchars($product['prod_name']); ?></h1>

                        <div class="d-flex align-items-center mb-3">
                            <div class="rating me-2">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <svg class="text-warning" width="16" height="16">
                                        <use xlink:href="#star"></use>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-muted small">(5.0) Based on reviews</span>
                        </div>

                        <h2 class="h3 fw-normal mb-4">$<?php echo number_format($product['prod_price'], 2); ?></h2>

                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="text-muted">
                                <?php echo nl2br(htmlspecialchars($product['prod_description'])); ?>
                            </p>
                        </div>


                        <div class="mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-4 col-6">
                                    <?php if (isset($product['prod_stock']) && $product['prod_stock'] > 0): ?>
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <div class="input-group product-qty">
                                            <span class="input-group-btn">
                                                <button type="button" class="quantity-left-minus btn btn-danger"
                                                    data-type="minus">
                                                    <svg width="16" height="16">
                                                        <use xlink:href="#minus"></use>
                                                    </svg>
                                                </button>
                                            </span>

                                            <input type="text" id="quantity" name="quantity"
                                                class="form-control input-number" value="1" min="1"
                                                max="<?php echo intval($product['prod_stock']); ?>">

                                            <span class="input-group-btn">
                                                <button type="button" class="quantity-right-plus btn btn-success"
                                                    data-type="plus">
                                                    <svg width="16" height="16">
                                                        <use xlink:href="#plus"></use>
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $product['prod_stock']; ?> items available
                                        </small>
                                    <?php else: ?>
                                        
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <!-- Not logged in users see login button -->
                                <a href="login.php" class="btn btn-primary btn-lg flex-grow-1">
                                    Login to Buy
                                </a>
                            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 0): ?>
                                <?php if (isset($product['prod_stock']) && $product['prod_stock'] > 0): ?>
                                    <button type="button" class="btn btn-primary btn-lg flex-grow-1 add-to-cart"
                                        data-product-id="<?php echo $product['prod_id']; ?>"
                                        data-max-stock="<?php echo intval($product['prod_stock']); ?>">
                                        <svg width="18" height="18" class="me-2">
                                            <use xlink:href="#cart"></use>
                                        </svg>
                                        Add to Cart
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-lg">
                                        <svg width="18" height="18">
                                            <use xlink:href="#heart"></use>
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <!-- Out of stock message -->
                                    <button type="button" class="btn btn-secondary btn-lg flex-grow-1" disabled>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <ul class="list-unstyled mb-0">
                                <?php if (isset($product['category_name'])): ?>
                                    <li class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Category:</span>
                                        <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    </li>
                                <?php endif; ?>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Date Added:</span>
                                    <span><?php echo date('F j, Y', strtotime($product['prod_date_added'])); ?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">Product ID:</span>
                                    <span>#<?php echo $product['prod_id']; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="mb-4">Related Products</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <?php
                        // Set the $product variable for the product-card.php template
                        $product = $relatedProduct;

                        // Include the product card
                        define('INCLUDED', true);
                        include "product-card.php";
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get elements once
        const quantityInput = document.getElementById('quantity');
        const addToCartBtn = document.querySelector('.add-to-cart');
        
        if (!quantityInput) return;
        
        const maxStock = parseInt(quantityInput.getAttribute('max')) || 1;
        
        // Use direct event listeners on parent elements to avoid event bubbling issues
        document.querySelector('.product-qty').addEventListener('click', function(e) {
            // Only proceed if we clicked on one of the buttons or their SVG children
            const button = e.target.closest('.btn-number');
            if (!button) return;
            
            e.preventDefault();
            
            const type = button.getAttribute('data-type');
            const currentValue = parseInt(quantityInput.value);
            
            if (type === 'minus') {
                // Decrease quantity by exactly 1
                if (!isNaN(currentValue) && currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            } else if (type === 'plus') {
                // Increase quantity by exactly 1
                if (!isNaN(currentValue) && currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                }
            }
        });
        
        // Validate input when changed directly
        quantityInput.addEventListener('change', function () {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) {
                this.value = 1;
            } else if (val > maxStock) {
                this.value = maxStock;
            }
        });
        
        // Add to cart functionality - keep this part unchanged
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function () {
                const productId = this.getAttribute('data-product-id');
                const quantity = parseInt(quantityInput.value);
                
                if (isNaN(quantity) || quantity < 1) {
                    alert('Please select a valid quantity');
                    return;
                }
                
                // Show loading state
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
                
                // Send AJAX request to add to cart
                fetch('add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${quantity}`
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button
                    this.disabled = false;
                    this.innerHTML = '<svg width="18" height="18" class="me-2"><use xlink:href="#cart"></use></svg>Add to Cart';
                    
                    if (data.success) {
                        // Create bootstrap toast
                        const toastContainer = document.createElement('div');
                        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                        toastContainer.style.zIndex = '11';
                        
                        toastContainer.innerHTML = `
                            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <strong>Success!</strong> Item added to your cart.
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(toastContainer);
                        
                        const toastEl = toastContainer.querySelector('.toast');
                        const toast = new bootstrap.Toast(toastEl);
                        toast.show();
                        
                        // Refresh cart icon/counter if needed
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.disabled = false;
                    this.innerHTML = '<svg width="18" height="18" class="me-2"><use xlink:href="#cart"></use></svg>Add to Cart';
                    alert('An error occurred while adding to cart. Please try again.');
                });
            });
        }
    });
</script>

<style>
    .product-detail-img {
        max-height: 500px;
        object-fit: contain;
    }

    .input-number {
        text-align: center;
    }

    .product-info {
        border-top: 1px solid #dee2e6;
        padding-top: 1.5rem;
    }
</style>

<?php
// Include footer
include "utils/footer.php";
?>