<?php
include 'utils/session.php';

// Get the category from URL parameter
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Validate the category to prevent SQL injection
$valid_categories = ['Cakes', 'Tarts', 'Cookies', 'Cupcakes', 'Muffins', 'Puffs'];
if (!in_array($category, $valid_categories)) {
    // Tell PHP/Nginx “this is a 404” and stop
    http_response_code(404);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php
// Include header and navbar
include 'utils/header.php';
include 'utils/navbar.php';

// Include database connection
include_once "utils/dbconnect.php";

// Initialize products array
$products = [];

// Fetch products by category
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM ssdgroup11db.products 
                           WHERE prod_category = ? 
                           ORDER BY 
                               CASE WHEN prod_stock > 0 THEN 0 ELSE 1 END, -- In-stock items first
                               prod_id ASC");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}

// Get the category image and description
$category_images = [
    'Cakes' => 'icon-cake.png',
    'Tarts' => 'icon-tart.png',
    'Cookies' => 'icon-cookie.png',
    'Cupcakes' => 'icon-cupcake.png',
    'Muffins' => 'icon-muffin.png',
    'Puffs' => 'icon-puff.png'
];

$category_descriptions = [
    'Cakes' => 'Indulge in our delicious assortment of cakes, from classic chocolate to innovative flavors.',
    'Tarts' => 'Enjoy our selection of sweet and savory tarts with buttery crusts and fresh fillings.',
    'Cookies' => 'Discover our wide variety of cookies, from chewy to crispy, with various flavors.',
    'Cupcakes' => 'Treat yourself to our gourmet cupcakes topped with creamy frosting and decorations.',
    'Muffins' => 'Start your day with our freshly baked muffins in both sweet and savory options.',
    'Puffs' => 'Try our light and airy puffs, filled with delightful creams and custards.'
];

$category_image = isset($category_images[$category]) ? $category_images[$category] : 'product-placeholder.png';
$category_description = isset($category_descriptions[$category]) ? $category_descriptions[$category] : '';
?>

<main>
    <!-- Category Banner -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="images/<?php echo $category_image; ?>" alt="<?php echo htmlspecialchars($category); ?>"
                        style="width: 100px; height: 100px;">
                </div>
                <div class="col-md-10">
                    <h1 class="display-4"><?php echo htmlspecialchars($category); ?></h1>
                    <p class="lead"><?php echo htmlspecialchars($category_description); ?></p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($category); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Navigation -->
    <section class="py-4 overflow-hidden">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-header d-flex flex-wrap justify-content-between mb-4">
                        <h2 class="section-title">Browse Categories</h2>
                        <div class="d-flex align-items-center">
                            <div class="swiper-buttons">
                                <button class="swiper-prev category-carousel-prev btn btn-yellow">❮</button>
                                <button class="swiper-next category-carousel-next btn btn-yellow">❯</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="category-carousel swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($valid_categories as $cat): ?>
                                <a href="category.php?category=<?php echo $cat; ?>"
                                    class="nav-link category-item swiper-slide text-center <?php echo ($cat === $category) ? 'active' : ''; ?>">
                                    <img src="images/<?php echo $category_images[$cat]; ?>"
                                        alt="<?php echo htmlspecialchars($cat); ?> Thumbnail"
                                        style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                                    <h3 class="category-title"><?php echo htmlspecialchars($cat); ?></h3>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="py-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-header d-flex justify-content-between mb-4">
                        <h2 class="section-title">Browse <?php echo htmlspecialchars($category); ?></h2>
                        <div class="d-flex align-items-center">
                            <select class="form-select me-3" id="sortProducts">
                                <option value="default">Sort by: Default</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="newest">Newest First</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4" id="products-grid">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center py-5">
                        <p>No products found in this category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        // Define INCLUDED constant for product-card.php
                        if (!defined('INCLUDED')) {
                            define('INCLUDED', true);
                        }
                        include 'product-card.php';
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Swiper for category carousel
            new Swiper(".category-carousel", {
                slidesPerView: 6,
                spaceBetween: 20,
                navigation: {
                    nextEl: ".category-carousel-next",
                    prevEl: ".category-carousel-prev",
                },
                breakpoints: {
                    0: { slidesPerView: 2 },
                    576: { slidesPerView: 3 },
                    768: { slidesPerView: 4 },
                    992: { slidesPerView: 5 },
                    1200: { slidesPerView: 6 }
                }
            });

            // Sorting functionality
            const sortSelect = document.getElementById('sortProducts');
            const productsGrid = document.getElementById('products-grid');

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    const products = Array.from(productsGrid.querySelectorAll('.col'));

                    switch (this.value) {
                        case 'price-low':
                            products.sort((a, b) => {
                                const priceA = parseFloat(a.querySelector('.price').innerText.replace('$', ''));
                                const priceB = parseFloat(b.querySelector('.price').innerText.replace('$', ''));
                                return priceA - priceB;
                            });
                            break;
                        case 'price-high':
                            products.sort((a, b) => {
                                const priceA = parseFloat(a.querySelector('.price').innerText.replace('$', ''));
                                const priceB = parseFloat(b.querySelector('.price').innerText.replace('$', ''));
                                return priceB - priceA;
                            });
                            break;
                        case 'newest':
                            break;
                        default:
                            break;
                    }

                    // Clear the grid and re-append sorted products
                    productsGrid.innerHTML = '';
                    products.forEach(product => {
                        productsGrid.appendChild(product);
                    });
                });
            }

            // Initialize Swiper for "You May Also Like" carousel
            new Swiper(".products-carousel", {
                slidesPerView: 5,
                spaceBetween: 30,
                navigation: {
                    nextEl: ".products-carousel-next",
                    prevEl: ".products-carousel-prev",
                },
                breakpoints: {
                    0: { slidesPerView: 1 },
                    576: { slidesPerView: 2 },
                    768: { slidesPerView: 3 },
                    992: { slidesPerView: 4 },
                    1200: { slidesPerView: 5 }
                }
            });
              function showNotification(type, message) {
                const n = document.createElement('div');
                n.className = `alert alert-${type==='success'?'success':'danger'} notification`;
                n.textContent = message;
                document.body.appendChild(n);
                setTimeout(()=> n.remove(),3000);
            }
            
            function updateCartDisplay() {
                setTimeout(()=> window.location.reload(),1000);
            }

            document.querySelectorAll('.add-to-cart').forEach(btn => {
                btn.addEventListener('click', e => {
                e.preventDefault();
                const id  = btn.dataset.productId;
                const qty = btn.closest('.product-item')
                                .querySelector('input[name="quantity"]')?.value || 1;
                const fd  = new FormData();
                fd.append('product_id', id);
                fd.append('quantity', qty);

                fetch('add-to-cart.php', { method:'POST', body:fd })
                    .then(r=> r.json())
                    .then(json=> {
                    if (json.success) showNotification('success', json.message);
                    else             showNotification('error',   json.message);
                    updateCartDisplay();
                    })
                    .catch(err=> {
                    console.error(err);
                    showNotification('error','Could not add to cart.');
                    });
                });
            });
        });
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

    <?php include 'utils/footer.php'; ?>
    </body>

    </html>