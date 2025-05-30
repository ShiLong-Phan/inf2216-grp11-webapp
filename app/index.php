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
            <a href="#" class="btn-link text-decoration-none">View All Categories →</a>
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
            <a href="index.html" class="nav-link category-item swiper-slide text-center">
              <img src="images/icon-cake.png" alt="Category Thumbnail"
                style="width: 50px; height: 50px; display: block; margin: 0 auto;">
              <h3 class="category-title">Cakes</h3>
            </a>
            <a href="index.html" class="nav-link category-item swiper-slide text-center">
              <img src="images/icon-tart.png" alt="Category Thumbnail"
                style="width: 50px; height: 50px; display: block; margin: 0 auto;">
              <h3 class="category-title">Tarts</h3>
            </a>
            <a href="index.html" class="nav-link category-item swiper-slide text-center">
              <img src="images/icon-cookie.png" alt="Category Thumbnail"
                style="width: 50px; height: 50px; display: block; margin: 0 auto;">
              <h3 class="category-title">Cookies</h3>
            </a>
            <a href="index.html" class="nav-link category-item swiper-slide text-center">
              <img src="images/icon-cupcake.png" alt="Category Thumbnail"
                style="width: 50px; height: 50px; display: block; margin: 0 auto;">
              <h3 class="category-title">Cupcakes</h3>
            </a>
            <a href="index.html" class="nav-link category-item swiper-slide text-center">
              <img src="images/icon-muffin.png" alt="Category Thumbnail"
                style="width: 50px; height: 50px; display: block; margin: 0 auto;">
              <h3 class="category-title">Muffins</h3>
            </a>
            <a href="index.html" class="nav-link category-item swiper-slide text-center">
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
  </div>
</section>

<section class="py-5">
  <div class="container-fluid">
    <div class="row">

      <div class="col-md-6">
        <div class="banner-ad bg-danger mb-3"
          style="background: url('images/ad-image-3.png');background-repeat: no-repeat;background-position: right bottom;">
          <div class="banner-content p-5">

            <div class="categories text-primary fs-3 fw-bold">Upto 25% Off</div>
            <h3 class="banner-title">Luxa Dark Chocolate</h3>
            <p>Very tasty & creamy vanilla flavour creamy muffins.</p>
            <a href="#" class="btn btn-dark text-uppercase">Show Now</a>

          </div>

        </div>
      </div>
      <div class="col-md-6">
        <div class="banner-ad bg-info"
          style="background: url('images/ad-image-4.png');background-repeat: no-repeat;background-position: right bottom;">
          <div class="banner-content p-5">

            <div class="categories text-primary fs-3 fw-bold">Upto 25% Off</div>
            <h3 class="banner-title">Creamy Muffins</h3>
            <p>Very tasty & creamy vanilla flavour creamy muffins.</p>
            <a href="#" class="btn btn-dark text-uppercase">Show Now</a>

          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<section class="py-5 overflow-hidden">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">

        <div class="section-header d-flex flex-wrap justify-content-between my-5">

          <h2 class="section-title">Best selling products</h2>

          <div class="d-flex align-items-center">
            <a href="#" class="btn-link text-decoration-none">View All Categories →</a>
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

            <div class="product-item swiper-slide">
              <span class="badge bg-success position-absolute m-3">-15%</span>
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoes.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <span class="badge bg-success position-absolute m-3">-15%</span>
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoketchup.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <span class="badge bg-success position-absolute m-3">-15%</span>
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <span class="badge bg-success position-absolute m-3">-15%</span>
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>
            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoes.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoketchup.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

          </div>
        </div>
        <!-- / products-carousel -->

      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container-fluid">

    <div class="bg-secondary py-5 my-5 rounded-5"
      style="background: url('images/bg-leaves-img-pattern.png') no-repeat;">
      <div class="container my-5">
        <div class="row">
          <div class="col-md-6 p-5">
            <div class="section-header">
              <h2 class="section-title display-4">Get <span class="text-primary">25% Discount</span> on your first
                purchase</h2>
            </div>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Dictumst amet, metus, sit massa posuere
              maecenas. At tellus ut nunc amet vel egestas.</p>
          </div>
          <div class="col-md-6 p-5">
            <form>
              <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control form-control-lg" name="name" id="name" placeholder="Name">
              </div>
              <div class="mb-3">
                <label for="" class="form-label">Email</label>
                <input type="email" class="form-control form-control-lg" name="email" id="email"
                  placeholder="abc@mail.com">
              </div>
              <div class="form-check form-check-inline mb-3">
                <label class="form-check-label" for="subscribe">
                  <input class="form-check-input" type="checkbox" id="subscribe" value="subscribe">
                  Subscribe to the newsletter</label>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-dark btn-lg">Submit</button>
              </div>
            </form>

          </div>

        </div>

      </div>
    </div>

  </div>
</section>

<section class="py-5 overflow-hidden">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">

        <div class="section-header d-flex justify-content-between">

          <h2 class="section-title">Most popular products</h2>

          <div class="d-flex align-items-center">
            <a href="#" class="btn-link text-decoration-none">View All Categories →</a>
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

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoes.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoketchup.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>
            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoes.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-tomatoketchup.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

            <div class="product-item swiper-slide">
              <a href="#" class="btn-wishlist"><svg width="24" height="24">
                  <use xlink:href="#heart"></use>
                </svg></a>
              <figure>
                <a href="index.html" title="Product Title">
                  <img src="images/thumb-bananas.png" class="tab-image">
                </a>
              </figure>
              <h3>Sunstar Fresh Melon Juice</h3>
              <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary">
                  <use xlink:href="#star-solid"></use>
                </svg> 4.5</span>
              <span class="price">$18.00</span>
              <div class="d-flex align-items-center justify-content-between">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16">
                        <use xlink:href="#minus"></use>
                      </svg>
                    </button>
                  </span>
                  <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                      <svg width="16" height="16">
                        <use xlink:href="#plus"></use>
                      </svg>
                    </button>
                  </span>
                </div>
                <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
              </div>
            </div>

          </div>
        </div>
        <!-- / products-carousel -->

      </div>
    </div>
  </div>
</section>

<?php include 'new-arrivals.php'; ?>

<section id="latest-blog" class="py-5">
  <div class="container-fluid">
    <div class="row">
      <div class="section-header d-flex align-items-center justify-content-between my-5">
        <h2 class="section-title">Our Recent Blog</h2>
        <div class="btn-wrap align-right">
          <a href="#" class="d-flex align-items-center nav-link">Read All Articles <svg width="24" height="24">
              <use xlink:href="#arrow-right"></use>
            </svg></a>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-4">
        <article class="post-item card border-0 shadow-sm p-3">
          <div class="image-holder zoom-effect">
            <a href="#">
              <img src="images/post-thumb-1.jpg" alt="post" class="card-img-top">
            </a>
          </div>
          <div class="card-body">
            <div class="post-meta d-flex text-uppercase gap-3 my-2 align-items-center">
              <div class="meta-date"><svg width="16" height="16">
                  <use xlink:href="#calendar"></use>
                </svg>22 Aug 2021</div>
              <div class="meta-categories"><svg width="16" height="16">
                  <use xlink:href="#category"></use>
                </svg>tips & tricks</div>
            </div>
            <div class="post-header">
              <h3 class="post-title">
                <a href="#" class="text-decoration-none">Top 10 casual look ideas to dress up your kids</a>
              </h3>
              <p>Lorem ipsum dolor sit amet, consectetur adipi elit. Aliquet eleifend viverra enim tincidunt donec
                quam. A in arcu, hendrerit neque dolor morbi...</p>
            </div>
          </div>
        </article>
      </div>
      <div class="col-md-4">
        <article class="post-item card border-0 shadow-sm p-3">
          <div class="image-holder zoom-effect">
            <a href="#">
              <img src="images/post-thumb-2.jpg" alt="post" class="card-img-top">
            </a>
          </div>
          <div class="card-body">
            <div class="post-meta d-flex text-uppercase gap-3 my-2 align-items-center">
              <div class="meta-date"><svg width="16" height="16">
                  <use xlink:href="#calendar"></use>
                </svg>25 Aug 2021</div>
              <div class="meta-categories"><svg width="16" height="16">
                  <use xlink:href="#category"></use>
                </svg>trending</div>
            </div>
            <div class="post-header">
              <h3 class="post-title">
                <a href="#" class="text-decoration-none">Latest trends of wearing street wears supremely</a>
              </h3>
              <p>Lorem ipsum dolor sit amet, consectetur adipi elit. Aliquet eleifend viverra enim tincidunt donec
                quam. A in arcu, hendrerit neque dolor morbi...</p>
            </div>
          </div>
        </article>
      </div>
      <div class="col-md-4">
        <article class="post-item card border-0 shadow-sm p-3">
          <div class="image-holder zoom-effect">
            <a href="#">
              <img src="images/post-thumb-3.jpg" alt="post" class="card-img-top">
            </a>
          </div>
          <div class="card-body">
            <div class="post-meta d-flex text-uppercase gap-3 my-2 align-items-center">
              <div class="meta-date"><svg width="16" height="16">
                  <use xlink:href="#calendar"></use>
                </svg>28 Aug 2021</div>
              <div class="meta-categories"><svg width="16" height="16">
                  <use xlink:href="#category"></use>
                </svg>inspiration</div>
            </div>
            <div class="post-header">
              <h3 class="post-title">
                <a href="#" class="text-decoration-none">10 Different Types of comfortable clothes ideas for women</a>
              </h3>
              <p>Lorem ipsum dolor sit amet, consectetur adipi elit. Aliquet eleifend viverra enim tincidunt donec
                quam. A in arcu, hendrerit neque dolor morbi...</p>
            </div>
          </div>
        </article>
      </div>
    </div>
  </div>
</section>

<section class="py-5 my-5">
  <div class="container-fluid">

    <div class="bg-warning py-5 rounded-5" style="background-image: url('images/bg-pattern-2.png') no-repeat;">
      <div class="container">
        <div class="row">
          <div class="col-md-4">
            <img src="images/phone.png" alt="phone" class="image-float img-fluid">
          </div>
          <div class="col-md-8">
            <h2 class="my-5">Shop faster with foodmart App</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sagittis sed ptibus liberolectus nonet
              psryroin. Amet sed lorem posuere sit iaculis amet, ac urna. Adipiscing fames semper erat ac in
              suspendisse iaculis. Amet blandit tortor praesent ante vitae. A, enim pretiummi senectus magna. Sagittis
              sed ptibus liberolectus non et psryroin.</p>
            <div class="d-flex gap-2 flex-wrap">
              <img src="images/app-store.jpg" alt="app-store">
              <img src="images/google-play.jpg" alt="google-play">
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>



      <?php include 'utils/footer.php'; ?>
</body>
</html>