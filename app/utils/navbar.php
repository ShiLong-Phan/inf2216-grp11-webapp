<?php
// Initialize cart total
$cartTotal = 0;

// If user is logged in, fetch their cart total
if (isset($_SESSION['user_id'])) {
    include_once "dbconnect.php"; // Make sure this path is correct

    if ($conn) {
        // Prepare and execute query to get cart items for the logged-in user
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT cart_id, cart_quantity, cart_subtotal FROM ssdgroup11db.cart WHERE cart_user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Calculate total
        while ($row = $result->fetch_assoc()) {
            $cartTotal += $row['cart_subtotal'];
        }

        $stmt->close();
    }
}
?>

<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <defs>
        <symbol xmlns="http://www.w3.org/2000/svg" id="link" viewBox="0 0 24 24">
            <path fill="currentColor" <symbol xmlns="http://www.w3.org/2000/svg" id="arrow-right" viewBox="0 0 24 24">
                <path fill="currentColor"
                    d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="category" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="calendar" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16Zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="plus" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="minus" viewBox="0 0 24 24">
            <path fill="currentColor" d="M19 11H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M8.5 19a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 8.5 19ZM19 16H7a1 1 0 0 1 0-2h8.491a3.013 3.013 0 0 0 2.885-2.176l1.585-5.55A1 1 0 0 0 19 5H6.74a3.007 3.007 0 0 0-2.82-2H3a1 1 0 0 0 0 2h.921a1.005 1.005 0 0 1 .962.725l.155.545v.005l1.641 5.742A3 3 0 0 0 7 18h12a1 1 0 0 0 0-2Zm-1.326-9l-1.22 4.274a1.005 1.005 0 0 1-.963.726H8.754l-.255-.892L7.326 7ZM16.5 19a1.5 1.5 0 1 0 1.5 1.5a1.5 1.5 0 0 0-1.5-1.5Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="check" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M18.71 7.21a1 1 0 0 0-1.42 0l-7.45 7.46l-3.13-3.14A1 1 0 1 0 5.29 13l3.84 3.84a1 1 0 0 0 1.42 0l8.16-8.16a1 1 0 0 0 0-1.47Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="trash" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M10 18a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1ZM20 6h-4V5a3 3 0 0 0-3-3h-2a3 3 0 0 0-3 3v1H4a1 1 0 0 0 0 2h1v11a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8h1a1 1 0 0 0 0-2ZM10 5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1h-4Zm7 14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V8h10Zm-3-1a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-outline" viewBox="0 0 15 15">
            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                d="M7.5 9.804L5.337 11l.413-2.533L4 6.674l2.418-.37L7.5 4l1.082 2.304l2.418.37l-1.75 1.793L9.663 11L7.5 9.804Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-solid" viewBox="0 0 15 15">
            <path fill="currentColor"
                d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="search" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
            <path fill="currentColor"
                d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z" />
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="close" viewBox="0 0 15 15">
            <path fill="currentColor"
                d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z" />
        </symbol>
    </defs>
</svg>

<div class="preloader-wrapper">
    <div class="preloader">
    </div>
</div>

<?php include 'cart.php'; ?>


<header>
    <div class="container-fluid">
        <div class="row py-3 border-bottom">

            <div class="col-sm-2 col-lg-2 text-center text-sm-start">
                <div>
                    <a href="index.php">
                        <img src="images/logo.png" alt="logo" class="img-fluid" style="width: 120px; height: 120px;">
                    </a>
                </div>
            </div>


            <div
                class="col-sm-16 col-lg-10 d-flex justify-content-end gap-5 align-items-center mt-4 mt-sm-0 justify-content-center justify-content-sm-end">

                <ul class="d-flex justify-content-end list-unstyled m-0">
                    <li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="profile.php" class="rounded-circle bg-light p-2 mx-1">
                                <svg width="24" height="24" viewBox="0 0 24 24">
                                    <use xlink:href="#user"></use>
                                </svg>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="rounded-circle bg-light p-2 mx-1">
                                <svg width="24" height="24" viewBox="0 0 24 24">
                                    <use xlink:href="#user"></use>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </li>

                    <li class="d-lg-none">
                        <a href="#" class="rounded-circle bg-light p-2 mx-1" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
                            <svg width="24" height="24" viewBox="0 0 24 24">
                                <use xlink:href="#cart"></use>
                            </svg>
                        </a>
                    </li>
                    <li class="d-lg-none">
                        <a href="#" class="rounded-circle bg-light p-2 mx-1" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSearch" aria-controls="offcanvasSearch">
                            <svg width="24" height="24" viewBox="0 0 24 24">
                                <use xlink:href="#search"></use>
                            </svg>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                        <li class="nav-item ms-3">
                            <a class="nav-link" href="/admin_dashboard.php">Admin</a>
                        </li>
                    <?php endif; ?>


                    <!-- logout -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="logout-btn ms-3">
                            <a href="../logout.php" class="rounded-circle bg-light p-2 mx-1" data-bs-toggle="offcanvas">
                                <i class="fa fa-sign-out" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="cart text-end d-none d-lg-block dropdown">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="d-flex align-items-center">
                            <button class="border-0 bg-transparent d-flex flex-column gap-2 lh-1" type="button"
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
                                <div class="d-flex align-items-center">
                                    <span class="fs-6 text-muted me-1">Cart</span>
                                    <i class="fa fa-caret-down text-muted"></i>
                                </div>
                                <span class="cart-total fs-5 fw-bold">$<?php echo number_format($cartTotal, 2); ?></span>
                            </button>
                        </div>
                    <?php else: ?>
                        <a href="login.php"
                            class="border-0 bg-transparent d-flex flex-column gap-2 lh-1 text-decoration-none">
                            <div class="d-flex align-items-center">
                                <span class="fs-6 text-muted me-1">Cart</span>
                                <i class="fa fa-caret-down text-muted"></i>
                            </div>
                            <span class="fs-5 fw-bold">Login to view</span>
                        </a>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>
    <div class="container-fluid">
        <div class="row py-3">
            <div class="d-flex  justify-content-center justify-content-sm-between align-items-center">
                <nav class="main-menu d-flex navbar navbar-expand-lg">

                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                        aria-labelledby="offcanvasNavbarLabel">

                        <div class="offcanvas-header justify-content-center">
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</header>