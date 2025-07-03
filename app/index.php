<?php include 'utils/session.php'; ?>

<!DOCTYPE html>

<?php include 'utils/header.php'; ?>
<?php include 'utils/navbar.php'; ?>

<section class="banner-section" style="background-image: url('images/crumbly-banner.png');
         background-size: cover;
         background-position: center;
         width: 100%;
         min-height: 600px;
         position: relative;
         padding-top: 40%; /* This creates a responsive aspect ratio */
         margin-bottom: 20px;">
</section>

<section class="py-5 overflow-hidden">
  <div class="container-fluid">

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
  </div>
</section>

<section class="py-5">
  <div class="container-fluid">
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
</section>

<?php include 'new-arrivals.php'; ?>

<?php include 'utils/footer.php'; ?>

</body>
</html>