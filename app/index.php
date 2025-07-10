<?php include 'utils/session.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
 /* ——————————————————————
   Banner: responsive & centered
   —————————————————————— */
.banner-section {
  box-sizing: border-box;
  width: 100%;
  margin: 0 auto 50px;
  overflow: hidden;
  position: relative;
  padding: 0;
}

/* mobile → tablet: leave 15 px gutters */
@media (max-width: 991.98px) {
  .banner-section {
    width: calc(100% - 30px);
    margin: 0 auto 50px;
    padding: 0 15px;
  }
}



  </style>
  <?php include 'utils/header.php'; ?>
</head>

<body>
  <?php include 'utils/navbar.php'; ?>

  <div class="container-fluid px-0">
    <div class="container">
      <div class="banner-section">
        <div class="banner-container">
          <img src="images/crumbly-banner_resized.png"
              alt="Crumbly Banner"
              class="banner-image">
        </div>
      </div>
    </div>
  </div>

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
