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
?>

<div class="col">
    <div class="product-item">
    
        
        <a href="#" class="btn-wishlist">
            <svg width="24" height="24"><use xlink:href="#heart"></use></svg>
        </a>
        
        <figure>
            <a href="product.php?id=<?php echo $product['prod_id']; ?>" 
               title="<?php echo htmlspecialchars($product['prod_name']); ?>">
                <img src="<?php echo !empty($product['prod_image']) ? 'images/products/' . $product['prod_image'] : 'images/product-placeholder.png'; ?>"
                     class="tab-image" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
        </figure>
        
        <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
        <span class="qty">1 Unit</span>
        <span class="price">$<?php echo number_format($product['prod_price'], 2); ?></span>
        
        <div class="d-flex align-items-center justify-content-between">
            <div class="input-group product-qty">
                <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                        <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                    </button>
                </span>
                
                <input type="text" 
                       id="quantity_<?php echo $product['prod_id']; ?><?php echo isset($category_name) ? '_' . strtolower($category_name) : ''; ?>" 
                       name="quantity" 
                       class="form-control input-number" 
                       value="1">
                       
                <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                        <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                    </button>
                </span>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="#" class="nav-link add-to-cart" data-product-id="<?php echo $product['prod_id']; ?>">
                    Add to Cart <svg width="16" height="16"><use xlink:href="#cart"></use></svg>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    Login to Buy <svg width="16" height="16"><use xlink:href="#user"></use></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>