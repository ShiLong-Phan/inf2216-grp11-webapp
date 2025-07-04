<?php
// Include your database connection file
include "utils/dbconnect.php";

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if database connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<!-- Database connection successful -->";

// Prepare the statement:
$stmt = $conn->prepare("SELECT * FROM ssdgroup11db.products");

// Check if statement preparation was successful
if (!$stmt) {
    die("Statement preparation failed: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

// Debug: Print the number of rows returned
echo "<!-- Query executed. Number of rows returned: " . $result->num_rows . " -->";

// Store all products for later use in different tabs
$all_products = [];
$categories = [
    'Cakes' => [],
    'Tarts' => [],
    'Cookies' => [],
    'Cupcakes' => [],
    'Muffins' => [],
    'Puffs' => []
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_products[] = $row;

        // Sort products into their respective categories
        if (isset($categories[$row['prod_category']])) {
            $categories[$row['prod_category']][] = $row;
        }
    }
    // Debug: Print the count of products in each category
    echo "<!-- Total products: " . count($all_products) . " -->";
    foreach ($categories as $category => $products) {
        echo "<!-- Category '$category': " . count($products) . " products -->";
    }
} else {
    echo "<!-- Query execution failed -->";
}

// Check if we have any products at all
if (empty($all_products)) {
    echo '<div class="alert alert-warning">No products found in the database. Please check your database connection and data.</div>';
}
?>

<nav>
    <div class="nav nav-tabs" id="nav-tab" role="tablist">
        <a href="#" class="nav-link text-uppercase fs-6 active" id="nav-all-tab" data-bs-toggle="tab"
            data-bs-target="#nav-all">All</a>
        <a href="#" class="nav-link text-uppercase fs-6" id="nav-cakes-tab" data-bs-toggle="tab"
            data-bs-target="#nav-cakes">Cakes</a>
        <a href="#" class="nav-link text-uppercase fs-6" id="nav-tarts-tab" data-bs-toggle="tab"
            data-bs-target="#nav-tarts">Tarts</a>
        <a href="#" class="nav-link text-uppercase fs-6" id="nav-cookies-tab" data-bs-toggle="tab"
            data-bs-target="#nav-cookies">Cookies</a>
        <a href="#" class="nav-link text-uppercase fs-6" id="nav-cupcakes-tab" data-bs-toggle="tab"
            data-bs-target="#nav-cupcakes">Cupcakes</a>
        <a href="#" class="nav-link text-uppercase fs-6" id="nav-muffins-tab" data-bs-toggle="tab"
            data-bs-target="#nav-muffins">Muffins</a>
        <a href="#" class="nav-link text-uppercase fs-6" id="nav-puffs-tab" data-bs-toggle="tab"
            data-bs-target="#nav-puffs">Puffs</a>
    </div>
</nav>
</div>
<div class="tab-content" id="nav-tabContent">
    <!-- ALL PRODUCTS TAB -->
    <div class="tab-pane fade show active" id="nav-all" role="tabpanel" aria-labelledby="nav-all-tab">
        <div class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
            <?php foreach ($all_products as $product): ?>
                <div class="col">
                    <div class="product-item">
                        <?php if (rand(1, 3) == 1): // Randomly show discount badge for demo ?>
                            <span class="badge bg-success position-absolute m-3">-<?php echo rand(10, 30); ?>%</span>
                        <?php endif; ?>
                        <a href="#" class="btn-wishlist"><svg width="24" height="24">
                                <use xlink:href="#heart"></use>
                            </svg></a>
                        <figure>
                            <a href="product.php?id=<?php echo $product['prod_id']; ?>"
                                title="<?php echo htmlspecialchars($product['prod_name']); ?>">
                                <img src="<?php echo !empty($product['prod_image']) ? 'images/products/' . $product['prod_image'] : 'images/product-placeholder.png'; ?>"
                                    class="tab-image" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                            </a>
                        </figure>
                        <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                        <span class="qty">1 Unit</span>
                        <span class="price">$<?php echo number_format($product['prod_price'], 2); ?></span>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="input-group product-qty">
                                <span class="input-group-btn">
                                    <button type="button" class="quantity-left-minus btn btn-danger btn-number"
                                        data-type="minus">
                                        <svg width="16" height="16">
                                            <use xlink:href="#minus"></use>
                                        </svg>
                                    </button>
                                </span>
                                <input type="text" id="quantity_<?php echo $product['prod_id']; ?>" name="quantity"
                                    class="form-control input-number" value="1">
                                <span class="input-group-btn">
                                    <button type="button" class="quantity-right-plus btn btn-success btn-number"
                                        data-type="plus">
                                        <svg width="16" height="16">
                                            <use xlink:href="#plus"></use>
                                        </svg>
                                    </button>
                                </span>
                            </div>
                            <a href="#" class="nav-link add-to-cart"
                                data-product-id="<?php echo $product['prod_id']; ?>">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CATEGORY-SPECIFIC TABS -->
    <?php foreach ($categories as $category_name => $category_products): ?>
        <div class="tab-pane fade" id="nav-<?php echo strtolower($category_name); ?>" role="tabpanel"
            aria-labelledby="nav-<?php echo strtolower($category_name); ?>-tab">
            <div class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
                <?php if (empty($category_products)): ?>
                    <div class="col-12 text-center py-5">
                        <p>No products found in this category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($category_products as $product): ?>
                        <div class="col">
                            <div class="product-item">
                                <?php if (rand(1, 3) == 1): ?>
                                    <span class="badge bg-success position-absolute m-3">-<?php echo rand(10, 30); ?>%</span>
                                <?php endif; ?>
                                <a href="#" class="btn-wishlist"><svg width="24" height="24">
                                        <use xlink:href="#heart"></use>
                                    </svg></a>
                                <figure>
                                    <a href="product.php?id=<?php echo $product['prod_id']; ?>"
                                        title="<?php echo htmlspecialchars($product['prod_name']); ?>">
                                        <img src="<?php echo !empty($product['prod_image']) ? 'images/products/' . $product['prod_image'] : 'images/product-placeholder.png'; ?>"
                                            class="tab-image" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                                    </a>
                                </figure>
                                <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                                <span class="qty">1 Unit</span>
                                <span class="price">$<?php echo number_format($product['prod_price'], 2); ?></span>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="input-group product-qty">
                                        <span class="input-group-btn">
                                            <button type="button" class="quantity-left-minus btn btn-danger btn-number"
                                                data-type="minus">
                                                <svg width="16" height="16">
                                                    <use xlink:href="#minus"></use>
                                                </svg>
                                            </button>
                                        </span>
                                        <input type="text"
                                            id="quantity_<?php echo $product['prod_id']; ?>_<?php echo strtolower($category_name); ?>"
                                            name="quantity" class="form-control input-number" value="1">
                                        <span class="input-group-btn">
                                            <button type="button" class="quantity-right-plus btn btn-success btn-number"
                                                data-type="plus">
                                                <svg width="16" height="16">
                                                    <use xlink:href="#plus"></use>
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                    <a href="#" class="nav-link add-to-cart"
                                        data-product-id="<?php echo $product['prod_id']; ?>">Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Add JavaScript to handle quantity changes and add to cart functionality
    document.addEventListener('DOMContentLoaded', function () {
        // Quantity buttons functionality
        const minusButtons = document.querySelectorAll('.quantity-left-minus');
        const plusButtons = document.querySelectorAll('.quantity-right-plus');

        minusButtons.forEach(button => {
            button.addEventListener('click', function () {
                const input = this.closest('.product-qty').querySelector('input');
                let value = parseInt(input.value);
                if (value > 1) {
                    value--;
                    input.value = value;
                }
            });
        });

        plusButtons.forEach(button => {
            button.addEventListener('click', function () {
                const input = this.closest('.product-qty').querySelector('input');
                let value = parseInt(input.value);
                value++;
                input.value = value;
            });
        });

        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                const quantityInput = this.closest('.product-item').querySelector('input[name="quantity"]');
                const quantity = quantityInput ? quantityInput.value : 1;

                // Here you would typically add AJAX code to add the item to cart
                console.log(`Adding product ID ${productId} with quantity ${quantity} to cart`);
                alert(`Product added to cart! (ID: ${productId}, Quantity: ${quantity})`);
            });
        });
    });

    // Log all products to console for debugging
    console.log('All Products:', <?php echo json_encode($all_products); ?>);
    console.log('Products by Category:', <?php echo json_encode($categories); ?>);

    // Show database connection status in console
    console.log('Database connection status: Success');
    console.log('Total products found: <?php echo count($all_products); ?>');
    <?php foreach ($categories as $category => $products): ?>
        console.log('Category "<?php echo $category; ?>": <?php echo count($products); ?> products');
    <?php endforeach; ?>
</script>