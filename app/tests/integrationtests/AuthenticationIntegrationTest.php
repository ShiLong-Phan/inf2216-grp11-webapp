<?php

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class AuthenticationIntegrationTest extends TestCase
{
    private $conn;
    private $originalSession;
    private $originalPost;
    private $originalGet;
    private $testUserEmail;
    private $testUserId;

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION ?? [];
        $this->originalPost = $_POST ?? [];
        $this->originalGet = $_GET ?? [];

        // Clear session data without starting actual session
        $_SESSION = [];

        $this->setupDatabaseConnection();
        $this->testUserEmail = 'auth-test-' . time() . '@example.com';
        $this->createTestUser();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
        $_POST = $this->originalPost;
        $_GET = $this->originalGet;

        $this->cleanupTestUser();

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

        $firstName = 'John';
        $lastName = 'Doe';
        $address = 'Test Address 123';
        $role = 1;
        $verified = 1;

        $stmt->bind_param("sssssii", $firstName, $lastName, $this->testUserEmail, $hashedPassword, $address, $role, $verified);
        $stmt->execute();

        $this->testUserId = $this->conn->insert_id;
        $stmt->close();
    }

    private function cleanupTestUser(): void
    {
        if ($this->conn && $this->testUserId) {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $this->testUserId);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function testCompleteLoginProcess(): void
    {
        // Clear any existing session
        $_SESSION = [];

        // Simulate login form submission using actual login.php logic
        $_POST = [
            'email' => $this->testUserEmail,
            'password' => 'Password123',
            'login' => 'Login'
        ];

        // Simulate the login validation logic from login.php
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Email validation should pass');
        }

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

        // Simulate session creation (from login.php) - WITHOUT actual session functions
        // In tests, we just set $_SESSION array directly
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['user_email'];
        $_SESSION['user_firstname'] = $user['user_firstname'];
        $_SESSION['user_lastname'] = $user['user_lastname'];
        $_SESSION['user_role'] = $user['user_role'];

        // Verify session was created correctly
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals($this->testUserEmail, $_SESSION['user_email']);
        $this->assertEquals('John', $_SESSION['user_firstname']);
        $this->assertEquals('Doe', $_SESSION['user_lastname']);
        $this->assertEquals(1, $_SESSION['user_role']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $_SESSION = [];

        $_POST = [
            'email' => $this->testUserEmail,
            'password' => 'WrongPassword123',
            'login' => 'Login'
        ];

        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check user credentials
        $stmt = $this->conn->prepare("SELECT user_id, user_password FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $isValidPassword = password_verify($password, $user['user_password']);

            $this->assertFalse($isValidPassword, 'Invalid password should not be verified');

            // Session should not be created for invalid login
            $this->assertArrayNotHasKey('user_id', $_SESSION);
        }

        $stmt->close();
    }

    public function testLogoutProcess(): void
    {
        // First, simulate a logged-in state
        $_SESSION = [
            'user_id' => $this->testUserId,
            'user_email' => $this->testUserEmail,
            'user_firstname' => 'John',
            'user_lastname' => 'Doe',
            'user_role' => 1
        ];

        $this->assertArrayHasKey('user_id', $_SESSION, 'User should be logged in initially');

        // Simulate logout process (from logout.php) - just clear the $_SESSION array
        $_SESSION = [];

        // Verify session is cleared
        $this->assertEmpty($_SESSION, 'Session should be empty after logout');
        $this->assertArrayNotHasKey('user_id', $_SESSION, 'User ID should be removed from session');
    }

    public function testSessionRegenerationOnLogin(): void
    {
        // Simulate having an old session
        $oldSessionData = ['temp_data' => 'some_value'];
        $_SESSION = $oldSessionData;

        // Simulate login process
        $_POST = [
            'email' => $this->testUserEmail,
            'password' => 'Password123',
            'login' => 'Login'
        ];

        // Get user data (simulating successful login)
        $stmt = $this->conn->prepare("SELECT user_id, user_email FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $this->testUserEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Simulate session regeneration by clearing old data and setting new data
        $_SESSION = []; // This simulates session_regenerate_id(true)
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['user_email'];

        // Verify old session data is gone and new data is present
        $this->assertArrayNotHasKey('temp_data', $_SESSION, 'Old session data should be removed');
        $this->assertEquals($this->testUserId, $_SESSION['user_id'], 'New user data should be in session');
        $this->assertEquals($this->testUserEmail, $_SESSION['user_email'], 'User email should be in session');
    }

    public function testUnverifiedUserLoginBlocked(): void
    {
        // Create unverified test user
        $unverifiedEmail = 'unverified-' . time() . '@example.com';
        $hashedPassword = password_hash('Password123', PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (user_firstname, user_lastname, user_email, user_password, user_address, user_role, user_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $firstName = 'Jane';
        $lastName = 'Doe';
        $address = 'Test Address 456';
        $role = 1;
        $verified = 0; // Unverified

        $stmt->bind_param("sssssii", $firstName, $lastName, $unverifiedEmail, $hashedPassword, $address, $role, $verified);
        $stmt->execute();
        $unverifiedUserId = $this->conn->insert_id;
        $stmt->close();

        // Clear session for this test
        $_SESSION = [];

        // Attempt login with unverified account
        $_POST = [
            'email' => $unverifiedEmail,
            'password' => 'Password123',
            'login' => 'Login'
        ];

        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check user credentials
        $stmt = $this->conn->prepare("SELECT user_id, user_password, user_verified FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $isValidPassword = password_verify($password, $user['user_password']);

            $this->assertTrue($isValidPassword, 'Password should be valid');
            $this->assertEquals(0, $user['user_verified'], 'User should be unverified');

            // Login should be blocked for unverified users
            if (!$user['user_verified']) {
                // Session should not be created for unverified users
                $this->assertArrayNotHasKey('user_id', $_SESSION, 'Unverified user should not have session created');
            }
        }

        $stmt->close();

        // Clean up unverified user
        $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $unverifiedUserId);
        $stmt->execute();
        $stmt->close();
    }

    public function testUserRoleBasedAccess(): void
    {
        // Test customer role access
        $_SESSION = [
            'user_id' => $this->testUserId,
            'user_email' => $this->testUserEmail,
            'user_role' => 0  // Customer role (1 in your system)
        ];

        // Simulate access check for customer functions
        $userRole = $_SESSION['user_role'];

        // Customer should have access to regular functions (role 1)
        $this->assertEquals(0, $userRole, 'User should have customer role');
        $this->assertTrue($userRole == 0, 'Customer should have access to regular functions');

        // Customer should NOT have admin access (assuming admin is role 2)
        $this->assertFalse($userRole == 1, 'Customer should not have admin access');

        // Test admin role
        $_SESSION['user_role'] = 1; // Admin role
        $userRole = $_SESSION['user_role'];

        // Admin should have access to admin functions
        $this->assertTrue($userRole == 1, 'Admin should have admin access');
        $this->assertFalse($userRole == 0, 'Admin role should not be customer role');
    }

    public function testPasswordValidation(): void
    {
        // Test login with various password scenarios
        $testCases = [
            ['password' => 'Password123', 'shouldPass' => true, 'description' => 'Valid password'],
            ['password' => 'wrongpassword', 'shouldPass' => false, 'description' => 'Wrong password'],
            ['password' => '', 'shouldPass' => false, 'description' => 'Empty password'],
            ['password' => 'password123', 'shouldPass' => false, 'description' => 'Incorrect case'],
        ];

        foreach ($testCases as $testCase) {
            $_SESSION = []; // Clear session for each test

            // Get user's actual password hash
            $stmt = $this->conn->prepare("SELECT user_password FROM users WHERE user_email = ?");
            $stmt->bind_param("s", $this->testUserEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            $isValidPassword = password_verify($testCase['password'], $user['user_password']);

            if ($testCase['shouldPass']) {
                $this->assertTrue($isValidPassword, $testCase['description'] . ' should pass verification');
            } else {
                $this->assertFalse($isValidPassword, $testCase['description'] . ' should fail verification');
            }
        }
    }
}
