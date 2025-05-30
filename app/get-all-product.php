<?php
// Include your database connection file
include "utils/dbconnect.php";

// Define INCLUDED constant to prevent direct access to the product-card.php
define('INCLUDED', true);

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
                <?php include "product-card.php"; ?>
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
                        <?php include "product-card.php"; ?>
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

        // Add to cart functionality using AJAX
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function (e) {
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
            }, 3000);
        }

        // Function to update cart display (total, count, etc.)
        function updateCartDisplay() {
            // You can add AJAX call here to get updated cart data if needed
            // This example just reloads the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
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

<style>
    /* Notification styles */
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