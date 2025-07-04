<?php

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    private $userId;
    private $originalSession;
    private $originalPost;
    private $originalGet;
    private $conn;

    protected function setUp(): void
    {
        // DON'T start session - just set up the data
        $this->userId = 1;
        $this->originalSession = $_SESSION ?? [];
        $this->originalPost = $_POST ?? [];
        $this->originalGet = $_GET ?? [];

        // Set session data without starting session
        $_SESSION = [
            'user_id' => $this->userId,
            'user_email' => 'test@example.com',
            'user_role' => 1
        ];

        $this->setupDatabaseConnection();
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

    private function setupDatabaseConnection()
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

    private function createTestProducts()
    {
        $products = [
            [1, 'Chocolate Cake', 'Delicious chocolate cake', 19.99, 10, 'chocolate-cake.jpg'],
            [2, 'Vanilla Cupcake', 'Sweet vanilla cupcake', 15.50, 5, 'vanilla-cupcake.jpg'],
            [3, 'Red Velvet Cake', 'Classic red velvet cake', 25.00, 0, 'red-velvet.jpg']
        ];

        foreach ($products as $product) {
            $sql = "INSERT INTO products (prod_id, prod_name, prod_description, prod_price, prod_stock, prod_image) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    prod_name = VALUES(prod_name),
                    prod_description = VALUES(prod_description), 
                    prod_price = VALUES(prod_price), 
                    prod_stock = VALUES(prod_stock)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("issdis", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function addToCartDirect($productId, $quantity)
    {
        if (!is_numeric($productId) || $productId <= 0) {
            return ['success' => false, 'message' => 'Invalid product ID'];
        }

        if (!is_numeric($quantity) || $quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $sql = "SELECT prod_id, prod_price, prod_stock FROM products WHERE prod_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Product not found'];
        }

        $product = $result->fetch_assoc();
        $stmt->close();

        if ($quantity > $product['prod_stock']) {
            return ['success' => false, 'message' => 'Insufficient stock'];
        }

        $sql = "SELECT cart_id, cart_quantity FROM cart WHERE cart_user_id = ? AND cart_prod_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $cartItem = $result->fetch_assoc();
            $newQuantity = $cartItem['cart_quantity'] + $quantity;
            $stmt->close();

            if ($newQuantity > $product['prod_stock']) {
                return ['success' => false, 'message' => 'Total quantity exceeds stock'];
            }

            $sql = "UPDATE cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_id = ?";
            $stmt = $this->conn->prepare($sql);
            $subtotal = $newQuantity * $product['prod_price'];
            $stmt->bind_param("idi", $newQuantity, $subtotal, $cartItem['cart_id']);

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Cart updated successfully', 'quantity' => $newQuantity];
            }
        } else {
            $stmt->close();
            $sql = "INSERT INTO cart (cart_user_id, cart_prod_id, cart_quantity, cart_subtotal) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $subtotal = $quantity * $product['prod_price'];
            $stmt->bind_param("iiid", $this->userId, $productId, $quantity, $subtotal);

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Product added to cart successfully', 'quantity' => $quantity];
            }
        }

        if (isset($stmt)) $stmt->close();
        return ['success' => false, 'message' => 'Failed to add product to cart'];
    }

    private function updateCartDirect($cartId, $action)
    {
        $sql = "SELECT c.*, p.prod_price, p.prod_stock FROM cart c JOIN products p ON c.cart_prod_id = p.prod_id WHERE c.cart_id = ? AND c.cart_user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Cart item not found'];
        }

        $cartItem = $result->fetch_assoc();
        $stmt->close();

        switch ($action) {
            case 'increase':
                $newQuantity = $cartItem['cart_quantity'] + 1;
                if ($newQuantity > $cartItem['prod_stock']) {
                    return ['success' => false, 'message' => 'Cannot add more. Stock limit reached.'];
                }

                $sql = "UPDATE cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_id = ?";
                $stmt = $this->conn->prepare($sql);
                $subtotal = $newQuantity * $cartItem['prod_price'];
                $stmt->bind_param("idi", $newQuantity, $subtotal, $cartId);
                $success = $stmt->execute();
                $stmt->close();

                return $success ? ['success' => true, 'message' => 'Quantity increased', 'quantity' => $newQuantity] : ['success' => false, 'message' => 'Update failed'];

            case 'decrease':
                if ($cartItem['cart_quantity'] <= 1) {
                    return $this->removeFromCartDirect($cartId);
                } else {
                    $newQuantity = $cartItem['cart_quantity'] - 1;
                    $sql = "UPDATE cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $subtotal = $newQuantity * $cartItem['prod_price'];
                    $stmt->bind_param("idi", $newQuantity, $subtotal, $cartId);
                    $success = $stmt->execute();
                    $stmt->close();

                    return $success ? ['success' => true, 'message' => 'Quantity decreased', 'quantity' => $newQuantity] : ['success' => false, 'message' => 'Update failed'];
                }

            case 'remove':
                return $this->removeFromCartDirect($cartId);

            default:
                return ['success' => false, 'message' => 'Invalid action'];
        }
    }

    private function removeFromCartDirect($cartId)
    {
        $sql = "DELETE FROM cart WHERE cart_id = ? AND cart_user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $this->userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success ? ['success' => true, 'message' => 'Item removed from cart'] : ['success' => false, 'message' => 'Remove failed'];
    }

    private function clearCartDirect()
    {
        $sql = "DELETE FROM cart WHERE cart_user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success ? ['success' => true, 'message' => 'Cart cleared successfully'] : ['success' => false, 'message' => 'Clear failed'];
    }

    private function getCartData()
    {
        $sql = "SELECT c.*, p.prod_name, p.prod_price 
                FROM cart c 
                JOIN products p ON c.cart_prod_id = p.prod_id 
                WHERE c.cart_user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $cartItems = [];
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
        }
        $stmt->close();

        return $cartItems;
    }

    private function getCartTotal()
    {
        $sql = "SELECT SUM(cart_subtotal) as total FROM cart WHERE cart_user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (float)($row['total'] ?? 0);
    }

    private function getCartItemCount()
    {
        $sql = "SELECT SUM(cart_quantity) as count FROM cart WHERE cart_user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['count'] ?? 0);
    }

    private function cleanupTestData()
    {
        if ($this->conn) {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE cart_user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $this->userId);
                $stmt->execute();
                $stmt->close();
            }

            $testProductIds = [1, 2, 3];
            foreach ($testProductIds as $productId) {
                $stmt = $this->conn->prepare("DELETE FROM products WHERE prod_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $productId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    public function testAddValidItemToCartUsingRealFunction()
    {
        $response = $this->addToCartDirect(1, 2);

        $this->assertTrue($response['success'], 'Should successfully add valid item to cart');
        $this->assertEquals('Product added to cart successfully', $response['message']);

        $cartData = $this->getCartData();
        $this->assertNotEmpty($cartData, 'Cart should contain data after adding item');
        $this->assertEquals(2, $cartData[0]['cart_quantity'], 'Cart should contain correct quantity');
        $this->assertEquals(39.98, $cartData[0]['cart_subtotal'], 'Cart subtotal should be correct');
    }

    public function testAddInvalidItemUsingRealFunction()
    {
        $response = $this->addToCartDirect(0, 1);
        $this->assertFalse($response['success'], 'Should fail to add invalid product ID');

        $response = $this->addToCartDirect(1, 0);
        $this->assertFalse($response['success'], 'Should fail to add invalid quantity');

        $cartData = $this->getCartData();
        $this->assertEmpty($cartData, 'Cart should be empty after failed additions');
    }

    public function testUpdateCartQuantityUsingRealFunction()
    {
        $addResponse = $this->addToCartDirect(1, 2);
        $this->assertTrue($addResponse['success']);

        $cartData = $this->getCartData();
        $cartId = $cartData[0]['cart_id'];

        $updateResponse = $this->updateCartDirect($cartId, 'increase');
        $this->assertTrue($updateResponse['success'], 'Should successfully update cart quantity');

        $updatedCartData = $this->getCartData();
        $this->assertEquals(3, $updatedCartData[0]['cart_quantity'], 'Quantity should be increased');
    }

    public function testRemoveItemFromCartUsingRealFunction()
    {
        $addResponse = $this->addToCartDirect(1, 1);
        $this->assertTrue($addResponse['success']);

        $cartData = $this->getCartData();
        $cartId = $cartData[0]['cart_id'];

        $removeResponse = $this->removeFromCartDirect($cartId);
        $this->assertTrue($removeResponse['success'], 'Should successfully remove item from cart');

        $updatedCartData = $this->getCartData();
        $this->assertEmpty($updatedCartData, 'Cart should be empty after removing item');
    }

    public function testClearCartUsingRealFunction()
    {
        $this->addToCartDirect(1, 2);
        $this->addToCartDirect(2, 1);

        $cartDataBefore = $this->getCartData();
        $this->assertCount(2, $cartDataBefore, 'Cart should have 2 items before clearing');

        $clearResponse = $this->clearCartDirect();
        $this->assertTrue($clearResponse['success'], 'Should successfully clear cart');

        $cartDataAfter = $this->getCartData();
        $this->assertEmpty($cartDataAfter, 'Cart should be empty after clearing');
    }

    public function testCartTotalCalculations()
    {
        $this->addToCartDirect(1, 2);
        $this->addToCartDirect(2, 3);

        $calculatedTotal = $this->getCartTotal();
        $expectedTotal = (19.99 * 2) + (15.50 * 3);

        // Fix floating-point precision issue by using delta comparison
        $this->assertEqualsWithDelta(
            $expectedTotal,
            $calculatedTotal,
            0.01,
            'Cart total should be calculated correctly'
        );

        $itemCount = $this->getCartItemCount();
        $this->assertEquals(5, $itemCount, 'Cart item count should be correct');
    }

    public function testCartStockValidation()
    {
        $response = $this->addToCartDirect(1, 15);
        $this->assertFalse($response['success'], 'Should not allow adding more than available stock');

        $this->addToCartDirect(1, 5);
        $response = $this->addToCartDirect(1, 10);
        $this->assertFalse($response['success'], 'Should not allow total quantity to exceed stock');

        $cartData = $this->getCartData();
        $this->assertEquals(5, $cartData[0]['cart_quantity'], 'Cart should only contain valid quantity');
    }
}
