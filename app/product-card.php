<?php
/**
 * Product Card Component
 * 
 * This file displays a single product card.
 * 
 * @param array $product - The product data
 * @param string $category_name - Optional category name for category-specific IDs
 */

// Ensure this file is not accessed directly
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Check if product is out of stock
$isOutOfStock = isset($product['prod_stock']) && $product['prod_stock'] <= 0;
?>

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

    /* Subtle grayscale for image only */
    .grayscale {
        filter: grayscale(50%);
        opacity: 0.9;
    }
</style>

<div class="col">
    <div class="product-item">
        <?php if ($isOutOfStock): ?>
            <div class="out-of-stock-badge">
                <span class="badge bg-danger">Out of Stock</span>
            </div>
        <?php endif; ?>

        <figure>
            <a href="product-detail.php?id=<?php echo $product['prod_id']; ?>"
                title="<?php echo htmlspecialchars($product['prod_name']); ?>">
                <img src="<?php echo !empty($product['prod_image']) ? $product['prod_image'] : 'images/product-placeholder.png'; ?>"
                    class="tab-image <?php echo $isOutOfStock ? 'grayscale' : ''; ?>"
                    alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
            </a>
        </figure>

        <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
        <span class="qty">1 Unit</span>
        <span class="price">$<?php echo number_format($product['prod_price'], 2); ?></span>

        <div class="d-flex align-items-center justify-content-between">
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

                    <input type="text"
                        id="quantity_<?php echo $product['prod_id']; ?><?php echo isset($category_name) ? '_' . strtolower($category_name) : ''; ?>"
                        name="quantity" class="form-control input-number" value="1">

                    <span class="input-group-btn">
                        <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                            <svg width="16" height="16">
                                <use xlink:href="#plus"></use>
                            </svg>
                        </button>
                    </span>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="#" class="nav-link add-to-cart" data-product-id="<?php echo $product['prod_id']; ?>">
                        Add to Cart <svg width="16" height="16">
                            <use xlink:href="#cart"></use>
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        Login to Buy <svg width="16" height="16">
                            <use xlink:href="#user"></use>
                        </svg>
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
</div>