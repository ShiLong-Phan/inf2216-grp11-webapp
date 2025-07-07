<?php include 'utils/session.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* Banner styles to ensure proper containment */
    .banner-section {
      width: 100vw;
      /* Use viewport width instead of percentage */
      max-width: 100vw;
      overflow: hidden;
      position: relative;
      margin-left: calc(-50vw + 50%);
      /* Technique to break out of containers */
      margin-right: calc(-50vw + 50%);
      margin-bottom: 50px;
    }

    .banner-container {
      width: 100%;
      margin: 0;
      padding: 0;
      display: block;
    }

    .banner-image {
      width: 100%;
      height: auto;
      display: block;
      object-fit: cover;
    }

    /* Remove extra margins and padding */
    .container-fluid.px-0 {
      padding-left: 0 !important;
      padding-right: 0 !important;
      overflow-x: hidden;
      /* Prevent horizontal scroll */
      max-width: none !important;
      /* Override Bootstrap max-width */
    }

    /* Force banner to break out of any containers */
    @media (min-width: 1200px) {
      .container-fluid.px-0 {
        max-width: none !important;
      }
    }
  </style>
  <?php include 'utils/header.php'; ?>
</head>

<body>
  <?php include 'utils/navbar.php'; ?>

  <!-- Main container with no padding for banner -->
  <div class="container-fluid px-0">
    <!-- Banner section - full width -->
    <div class="banner-section">
      <div class="banner-container">
        <img src="images/crumbly-banner.png" alt="Crumbly Banner" class="banner-image">
      </div>
    </div>

    <!-- Content container with padding -->
    <div class="container">
      <!-- Category section -->
      <div class="row">
        <div class="col-md-12">
          <div class="section-header d-flex flex-wrap justify-content-between mb-5">
            <h2 class="section-title">Category</h2>
            <div class="d-flex align-items-center">
              <a href="category.php?category=Cakes" class="btn-link text-decoration-none">View All Categories →</a>
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
              <a href="category.php?category=Cakes" class="nav-link category-item swiper-slide text-center">
                <img src="images/icon-cake.png" alt="Category Thumbnail"
                  style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                <h3 class="category-title">Cakes</h3>
              </a>
              <a href="category.php?category=Tarts" class="nav-link category-item swiper-slide text-center">
                <img src="images/icon-tart.png" alt="Category Thumbnail"
                  style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                <h3 class="category-title">Tarts</h3>
              </a>
              <a href="category.php?category=Cookies" class="nav-link category-item swiper-slide text-center">
                <img src="images/icon-cookie.png" alt="Category Thumbnail"
                  style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                <h3 class="category-title">Cookies</h3>
              </a>
              <a href="category.php?category=Cupcakes" class="nav-link category-item swiper-slide text-center">
                <img src="images/icon-cupcake.png" alt="Category Thumbnail"
                  style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                <h3 class="category-title">Cupcakes</h3>
              </a>
              <a href="category.php?category=Muffins" class="nav-link category-item swiper-slide text-center">
                <img src="images/icon-muffin.png" alt="Category Thumbnail"
                  style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                <h3 class="category-title">Muffins</h3>
              </a>
              <a href="category.php?category=Puffs" class="nav-link category-item swiper-slide text-center">
                <img src="images/icon-puff.png" alt="Category Thumbnail"
                  style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                <h3 class="category-title">Puffs</h3>
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="bootstrap-tabs product-tabs">
            <div class="tabs-header d-flex justify-content-between border-bottom my-5">
              <h3>Our Products</h3>
              <?php include 'get-all-product.php' ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'utils/footer.php'; ?>
</body>

</html>