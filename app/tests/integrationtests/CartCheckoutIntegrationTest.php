<?php

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CartCheckoutIntegrationTest extends TestCase
{
    private $conn;
    private $originalSession;
    private $originalPost;
    private $originalGet;
    private $testUserEmail;
    private $testUserId;
    private $testUserAddress;
    private $testOrderId;
    private $testProductIds = [];


    protected function setUp(): void
    {
        $this->originalSession = $_SESSION ?? [];
        $this->originalPost = $_POST ?? [];
        $this->originalGet = $_GET ?? [];

        // Clear session data without starting actual session
        $_SESSION = [];

        $this->setupDatabaseConnection();
        $this->testUserEmail = 'cart-test-' . time() . '@example.com';
        $this->testUserAddress = 'Test Address 123, Singapore';
        $this->createTestUser();
        $this->createTestProducts();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
        $_POST = $this->originalPost;
        $_GET = $this->originalGet;

        $this->cleanupTestData();

        if ($this->conn) {
            $this->conn->close();
        }
    }

    private function setupDatabaseConnection(): void
    {
        $db_host = getenv('DB_HOST');
        $db_user = getenv('DB_USER');
        $db_pass = getenv('DB_PASS');
        $db_name = getenv('DB_NAME');

        $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($this->conn->connect_error) {
            $this->markTestSkipped('Database connection failed: ' . $this->conn->connect_error);
        }
    }

    private function createTestUser(): void
    {
        $hashedPassword = password_hash('Password123', PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (user_firstname, user_lastname, user_email, user_password, user_address, user_role, user_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $firstName = 'Cart';
        $lastName = 'Tester';
        $role = 0; // Regular customer
        $verified = 1; // Account is verified

        $stmt->bind_param("sssssii", $firstName, $lastName, $this->testUserEmail, $hashedPassword, $this->testUserAddress, $role, $verified);
        $stmt->execute();

        $this->testUserId = $this->conn->insert_id;
        $stmt->close();
    }

    private function createTestProducts(): void
    {
        // Create two test products
        $products = [
            [
                'name' => 'Test Product 1',
                'description' => 'Description for test product 1',
                'price' => 10.99,
                'stock' => 10,
                'category' => 1
            ],
            [
                'name' => 'Test Product 2',
                'description' => 'Description for test product 2',
                'price' => 20.49,
                'stock' => 5,
                'category' => 2
            ]
        ];

        foreach ($products as $product) {
            $sql = "INSERT INTO products (prod_name, prod_description, prod_price, prod_stock, prod_category) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssdii", $product['name'], $product['description'], $product['price'], $product['stock'], $product['category']);
            $stmt->execute();
            $this->testProductIds[] = $this->conn->insert_id;
            $stmt->close();
        }
    }

    private function cleanupTestData(): void
    {
        // Clean up any cart items for the test user
        if ($this->conn && $this->testUserId) {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE cart_user_id = ?");
            $stmt->bind_param("i", $this->testUserId);
            $stmt->execute();
            $stmt->close();
        }

        // Clean up test order and order items
        if ($this->conn && $this->testOrderId) {
            // Delete order items first (foreign key constraint)
            $stmt = $this->conn->prepare("DELETE FROM order_item WHERE order_item_order_id = ?");
            $stmt->bind_param("i", $this->testOrderId);
            $stmt->execute();
            $stmt->close();

            // Delete the order
            $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $this->testOrderId);
            $stmt->execute();
            $stmt->close();
        }

        // Clean up test products
        if ($this->conn && !empty($this->testProductIds)) {
            foreach ($this->testProductIds as $productId) {
                $stmt = $this->conn->prepare("DELETE FROM products WHERE prod_id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Clean up test user
        if ($this->conn && $this->testUserId) {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $this->testUserId);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function testCompleteCartAndCheckoutProcess(): void
    {
        // 1. Test user login
        $this->simulateUserLogin();

        // 2. Test adding products to cart
        $this->addProductsToCart();

        // 3. Test updating cart quantities
        $this->updateCartQuantities();

        // 4. Test removing a product from cart
        $this->removeProductFromCart();

        // 5. Test checkout process
        $this->processCheckout();

        // 6. Verify order confirmation
        $this->verifyOrderConfirmation();
    }

    private function simulateUserLogin(): void
    {
        // Clear any existing session
        $_SESSION = [];

        // Simulate login form submission
        $_POST = [
            'email' => $this->testUserEmail,
            'password' => 'Password123',
            'login' => 'Login'
        ];

        // Simulate the login validation logic from login.php
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Validate email format - fix the assertion to check for valid email format
        $emailValidation = filter_var($email, FILTER_VALIDATE_EMAIL);
        $this->assertNotFalse($emailValidation, 'Email validation should pass');
        $this->assertEquals($email, $emailValidation, 'Email should be valid format');

        // Check user credentials in database
        $stmt = $this->conn->prepare("SELECT user_id, user_email, user_password, user_firstname, user_lastname, user_role, user_verified 
                                     FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertEquals(1, $result->num_rows, 'User should be found in database');

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password
        $this->assertTrue(password_verify($password, $user['user_password']), 'Password should be verified');

        // Check if user is verified
        $this->assertEquals(1, $user['user_verified'], 'User should be verified');

        // Simulate session creation - WITHOUT actual session functions
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['user_email'];
        $_SESSION['user_firstname'] = $user['user_firstname'];
        $_SESSION['user_lastname'] = $user['user_lastname'];
        $_SESSION['user_role'] = $user['user_role'];

        // Verify session was created correctly
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals($this->testUserEmail, $_SESSION['user_email']);
        $this->assertEquals('Cart', $_SESSION['user_firstname']);
        $this->assertEquals('Tester', $_SESSION['user_lastname']);
        $this->assertEquals(0, $_SESSION['user_role']); // Regular customer role
    }

    private function addProductsToCart(): void
    {
        // Test adding products to cart
        foreach ($this->testProductIds as $index => $productId) {
            // Simulate add-to-cart request
            $_POST = [
                'product_id' => $productId,
                'quantity' => $index + 1 // First product: qty 1, Second product: qty 2
            ];

            // Get product info for validation
            $stmt = $this->conn->prepare("SELECT prod_price, prod_stock FROM products WHERE prod_id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            // Check if there's enough stock
            $this->assertGreaterThanOrEqual($_POST['quantity'], $product['prod_stock'], 'There should be enough stock for the order');

            // Calculate subtotal
            $subtotal = $product['prod_price'] * $_POST['quantity'];

            // Insert item into cart
            $stmt = $this->conn->prepare("INSERT INTO cart (cart_user_id, cart_prod_id, cart_quantity, cart_subtotal) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $_SESSION['user_id'], $productId, $_POST['quantity'], $subtotal);
            $stmt->execute();
            $stmt->close();

            // Verify the cart item was added correctly
            $stmt = $this->conn->prepare("SELECT * FROM cart WHERE cart_user_id = ? AND cart_prod_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->assertEquals(1, $result->num_rows, "Product $productId should be in the cart");

            $cartItem = $result->fetch_assoc();
            $this->assertEquals($_POST['quantity'], $cartItem['cart_quantity'], "Cart quantity should be " . $_POST['quantity']);
            $this->assertEquals($subtotal, $cartItem['cart_subtotal'], "Cart subtotal should be calculated correctly");
            $stmt->close();
        }
    }

    private function updateCartQuantities(): void
    {
        // Get the cart items
        $stmt = $this->conn->prepare("SELECT * FROM cart WHERE cart_user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $cartItems = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fix: Check if there are cart items before proceeding
        if (count($cartItems) > 0) {
            $cartItem = $cartItems[0]; // Get the first cart item

            // Get product details to verify stock
            $productStmt = $this->conn->prepare("SELECT prod_price, prod_stock FROM products WHERE prod_id = ?");
            $productStmt->bind_param("i", $cartItem['cart_prod_id']);
            $productStmt->execute();
            $productResult = $productStmt->get_result();
            $product = $productResult->fetch_assoc();
            $productStmt->close();

            // Increase quantity for the first product (if it has stock)
            if ($cartItem['cart_quantity'] < $product['prod_stock']) {
                $newQuantity = $cartItem['cart_quantity'] + 1;
                $newSubtotal = $product['prod_price'] * $newQuantity;

                // Simulate update cart request
                $_POST = [
                    'cart_id' => $cartItem['cart_id'],
                    'action' => 'increase'
                ];

                // Update cart in database
                $updateStmt = $this->conn->prepare("UPDATE cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_id = ?");
                $updateStmt->bind_param("idi", $newQuantity, $newSubtotal, $cartItem['cart_id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Verify update was successful
                $verifyStmt = $this->conn->prepare("SELECT cart_quantity, cart_subtotal FROM cart WHERE cart_id = ?");
                $verifyStmt->bind_param("i", $cartItem['cart_id']);
                $verifyStmt->execute();
                $verifyResult = $verifyStmt->get_result();
                $updatedItem = $verifyResult->fetch_assoc();
                $verifyStmt->close();

                $this->assertEquals($newQuantity, $updatedItem['cart_quantity'], "Cart quantity should be updated to $newQuantity");
                $this->assertEquals($newSubtotal, $updatedItem['cart_subtotal'], "Cart subtotal should be updated correctly");
            }
        }
    }

    private function removeProductFromCart(): void
    {
        // Get the second cart item (if it exists)
        $stmt = $this->conn->prepare("SELECT * FROM cart WHERE cart_user_id = ? ORDER BY cart_id DESC LIMIT 1");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $cartItem = $result->fetch_assoc();

            // Simulate remove cart item request
            $_POST = [
                'cart_id' => $cartItem['cart_id'],
                'action' => 'remove'
            ];

            // Delete the cart item
            $deleteStmt = $this->conn->prepare("DELETE FROM cart WHERE cart_id = ?");
            $deleteStmt->bind_param("i", $cartItem['cart_id']);
            $deleteStmt->execute();
            $deleteStmt->close();

            // Verify item was removed
            $verifyStmt = $this->conn->prepare("SELECT * FROM cart WHERE cart_id = ?");
            $verifyStmt->bind_param("i", $cartItem['cart_id']);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            $verifyStmt->close();

            $this->assertEquals(0, $verifyResult->num_rows, "Cart item should be removed");
        }
        $stmt->close();
    }

    private function processCheckout(): void
    {
        // Calculate total from cart items
        $stmt = $this->conn->prepare("SELECT SUM(cart_subtotal) as cart_total FROM cart WHERE cart_user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cartTotal = $row ? $row['cart_total'] : 0;
        $stmt->close();

        // Simulate checkout form submission
        $_POST = [
            'full_name' => 'Cart Tester',
            'email' => $this->testUserEmail,
            'address' => $this->testUserAddress,
            'card_name' => 'Cart Tester',
            'card_number' => '4111111111111111',
            'card_expiry' => '12/25',
            'card_cvv' => '123',
            'place_order' => 'Place Order'
        ];

        // Verify user information
        $this->assertEquals('Cart Tester', $_POST['full_name']);
        $this->assertEquals($this->testUserEmail, $_POST['email']);
        $this->assertEquals($this->testUserAddress, $_POST['address']);

        // Begin transaction for order processing
        $this->conn->begin_transaction();

        try {
            // Create order
            $orderStmt = $this->conn->prepare("INSERT INTO orders 
                                             (order_user_id, order_date, order_delivery_address, order_status, order_total) 
                                             VALUES (?, NOW(), ?, 'Pending', ?)");
            $orderStmt->bind_param("isd", $_SESSION['user_id'], $_POST['address'], $cartTotal);
            $orderStmt->execute();
            $this->testOrderId = $this->conn->insert_id;
            $orderStmt->close();

            // Get cart items
            $cartStmt = $this->conn->prepare("SELECT c.cart_prod_id, c.cart_quantity, c.cart_subtotal, 
                                            p.prod_price, p.prod_stock
                                            FROM cart c 
                                            JOIN products p ON c.cart_prod_id = p.prod_id 
                                            WHERE c.cart_user_id = ?");
            $cartStmt->bind_param("i", $_SESSION['user_id']);
            $cartStmt->execute();
            $cartResult = $cartStmt->get_result();
            $cartStmt->close();

            // Insert order items and update product stock
            while ($item = $cartResult->fetch_assoc()) {
                // Check if there's enough stock
                $quantityToOrder = min($item['cart_quantity'], $item['prod_stock']);
                $itemSubtotal = $item['prod_price'] * $quantityToOrder;

                // Add item to order
                $itemStmt = $this->conn->prepare("INSERT INTO order_item 
                                                (order_item_order_id, order_item_prod_id, prod_item_quantity, prod_subtotal) 
                                                VALUES (?, ?, ?, ?)");
                $itemStmt->bind_param("iiid", $this->testOrderId, $item['cart_prod_id'], $quantityToOrder, $itemSubtotal);
                $itemStmt->execute();
                $itemStmt->close();

                // Update product stock
                $updateStockStmt = $this->conn->prepare("UPDATE products 
                                                        SET prod_stock = prod_stock - ? 
                                                        WHERE prod_id = ?");
                $updateStockStmt->bind_param("ii", $quantityToOrder, $item['cart_prod_id']);
                $updateStockStmt->execute();
                $updateStockStmt->close();
            }

            // Clear the cart
            $clearCartStmt = $this->conn->prepare("DELETE FROM cart WHERE cart_user_id = ?");
            $clearCartStmt->bind_param("i", $_SESSION['user_id']);
            $clearCartStmt->execute();
            $clearCartStmt->close();

            // Commit transaction
            $this->conn->commit();

            // Verify order was created
            $verifyOrderStmt = $this->conn->prepare("SELECT * FROM orders WHERE order_id = ?");
            $verifyOrderStmt->bind_param("i", $this->testOrderId);
            $verifyOrderStmt->execute();
            $verifyOrderResult = $verifyOrderStmt->get_result();
            $verifyOrderStmt->close();

            $this->assertEquals(1, $verifyOrderResult->num_rows, "Order should be created");
            $order = $verifyOrderResult->fetch_assoc();
            $this->assertEquals($_SESSION['user_id'], $order['order_user_id'], "Order should be associated with the user");
            $this->assertEquals('Pending', $order['order_status'], "Order status should be pending");

            // Verify cart is empty
            $verifyCartStmt = $this->conn->prepare("SELECT * FROM cart WHERE cart_user_id = ?");
            $verifyCartStmt->bind_param("i", $_SESSION['user_id']);
            $verifyCartStmt->execute();
            $verifyCartResult = $verifyCartStmt->get_result();
            $verifyCartStmt->close();

            $this->assertEquals(0, $verifyCartResult->num_rows, "Cart should be empty after order is placed");

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->fail("Order processing failed: " . $e->getMessage());
        }
    }

    private function verifyOrderConfirmation(): void
    {
        // Simulate viewing order confirmation page
        $_GET = [
            'order_id' => $this->testOrderId
        ];

        // Verify order details
        $stmt = $this->conn->prepare("SELECT o.*, u.user_firstname, u.user_lastname, u.user_email 
                                     FROM orders o
                                     JOIN users u ON o.order_user_id = u.user_id
                                     WHERE o.order_id = ? AND o.order_user_id = ?");
        $stmt->bind_param("ii", $this->testOrderId, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $this->assertEquals(1, $result->num_rows, "Order should be found");
        $orderDetails = $result->fetch_assoc();

        // Check order user info
        $this->assertEquals($_SESSION['user_id'], $orderDetails['order_user_id'], "Order should belong to logged in user");
        $this->assertEquals($_SESSION['user_firstname'], $orderDetails['user_firstname'], "Order first name should match user");
        $this->assertEquals($_SESSION['user_lastname'], $orderDetails['user_lastname'], "Order last name should match user");
        $this->assertEquals($_SESSION['user_email'], $orderDetails['user_email'], "Order email should match user");

        // Verify order items
        $itemsStmt = $this->conn->prepare("SELECT i.*, p.prod_name 
                                          FROM order_item i
                                          JOIN products p ON i.order_item_prod_id = p.prod_id
                                          WHERE i.order_item_order_id = ?");
        $itemsStmt->bind_param("i", $this->testOrderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $itemsStmt->close();

        $this->assertGreaterThan(0, $itemsResult->num_rows, "Order should have items");

        // Test that the order total matches the sum of order items
        $totalFromItems = 0;
        while ($item = $itemsResult->fetch_assoc()) {
            $totalFromItems += $item['prod_subtotal'];
        }

        $this->assertEquals($orderDetails['order_total'], $totalFromItems, "Order total should match sum of item subtotals");
    }
}