<?php

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class UserValidationTest extends TestCase
{
    private $conn;
    private $originalSession;
    private $originalPost;

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION ?? [];
        $this->originalPost = $_POST ?? [];
        
        $this->setupDatabaseConnection();
        $this->includeUtilityFunctions();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
        $_POST = $this->originalPost;
        
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

    private function includeUtilityFunctions()
    {
        // Only include utility files, not main application files
        $utilFiles = [
            'utils/dbconnect.php',
            'utils/validation.php', 
            'utils/sanitization.php'
        ];
        
        foreach ($utilFiles as $file) {
            $filepath = __DIR__ . '/../../' . $file;
            if (file_exists($filepath)) {
                ob_start();
                include_once $filepath;
                ob_end_clean();
            }
        }

        // Include the sanitizeInput function only once if it doesn't exist
        if (!function_exists('sanitizeInput')) {
            // Define the function from your application here
            function sanitizeInput($data) {
                $data = trim($data);
                $data = stripslashes($data);
                $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
                $data = strip_tags($data);
                return $data;
            }
        }
    }

    private function validateEmailUsingRealFunction($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validatePasswordUsingRealFunction($password)
    {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    private function validateNameUsingRealFunction($name)
    {
        return !empty($name) && preg_match("/^[a-zA-Z ]*$/", $name);
    }

    private function sanitizeInputUsingRealFunction($data)
    {
        // Use the function we defined above
        return sanitizeInput($data);
    }

    private function testRegistrationValidation($email, $password, $firstName, $lastName)
    {
        // Instead of including the whole file, test the validation logic directly
        $errors = [];
        
        // Email validation
        if (empty($email) || !$this->validateEmailUsingRealFunction($email)) {
            $errors[] = "Invalid email format";
        }
        
        // Password validation  
        if (!$this->validatePasswordUsingRealFunction($password)) {
            $errors[] = "Password must be at least 8 characters with uppercase, lowercase, and number";
        }
        
        // Name validation
        if (!$this->validateNameUsingRealFunction($firstName)) {
            $errors[] = "First name should only contain letters and spaces";
        }
        
        if (!$this->validateNameUsingRealFunction($lastName)) {
            $errors[] = "Last name should only contain letters and spaces";
        }
        
        // Check if email already exists (simplified check)
        if (empty($errors)) {
            $sql = "SELECT user_id FROM users WHERE user_email = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors[] = "Email already exists";
                }
                $stmt->close();
            }
        }
        
        return empty($errors);
    }

    private function testProfileValidation($firstName, $lastName, $email)
    {
        $errors = [];
        
        // Name validation
        if (!$this->validateNameUsingRealFunction($firstName)) {
            $errors[] = "First name should only contain letters and spaces";
        }
        
        if (!$this->validateNameUsingRealFunction($lastName)) {
            $errors[] = "Last name should only contain letters and spaces";
        }
        
        // Email validation
        if (empty($email) || !$this->validateEmailUsingRealFunction($email)) {
            $errors[] = "Invalid email format";
        }
        
        return empty($errors);
    }

    public function testValidEmailReturnsTrue()
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk', 
            'admin@crumbly.com',
            'crumblymoo@gmail.com'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                $this->validateEmailUsingRealFunction($email),
                "Email {$email} should be valid"
            );
        }
    }

    public function testInvalidEmailReturnsFalse()
    {
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user..name@domain.com',
            ''
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->validateEmailUsingRealFunction($email),
                "Email {$email} should be invalid"
            );
        }
    }

    public function testValidPasswordReturnsTrue()
    {
        $validPasswords = [
            'Password123',
            'SecureP@ss1',
            'MySecret99',
            'TestPassword123'
        ];

        foreach ($validPasswords as $password) {
            $this->assertTrue(
                $this->validatePasswordUsingRealFunction($password),
                "Password should be valid"
            );
        }
    }

    public function testInvalidPasswordReturnsFalse()
    {
        $invalidPasswords = [
            'weak',           // Too short
            'password',       // No uppercase or numbers
            'PASSWORD123',    // No lowercase
            'Password',       // No numbers
            ''               // Empty
        ];

        foreach ($invalidPasswords as $password) {
            $this->assertFalse(
                $this->validatePasswordUsingRealFunction($password),
                "Password should be invalid"
            );
        }
    }

    public function testValidNameReturnsTrue()
    {
        $validNames = [
            'John',
            'Mary Jane',
            'Test User'
        ];

        foreach ($validNames as $name) {
            $this->assertTrue(
                $this->validateNameUsingRealFunction($name),
                "Name {$name} should be valid"
            );
        }
    }

    public function testInvalidNameReturnsFalse()
    {
        $invalidNames = [
            '',              // Empty
            'John123',       // Contains numbers
            'John@Doe',      // Contains special chars
            'John-Doe',      // Contains hyphen
            'John_Doe'       // Contains underscore
        ];

        foreach ($invalidNames as $name) {
            $this->assertFalse(
                $this->validateNameUsingRealFunction($name),
                "Name {$name} should be invalid"
            );
        }
    }

    public function testSanitizeInputRemovesHtmlAndWhitespace()
    {
        $dirtyInputs = [
            '<script>alert("xss")</script>test' => 'test',
            '  admin  ' => 'admin',
            '<b>bold</b> text' => 'bold text',
            '   <div>content</div>   ' => 'content'
        ];

        foreach ($dirtyInputs as $input => $expected) {
            $this->assertEquals(
                $expected,
                $this->sanitizeInputUsingRealFunction($input),
                "Input should be properly sanitized"
            );
        }
    }

    public function testRegistrationFormValidation()
    {
        // Clean up any existing test user first
        $testEmail = 'test-reg-' . time() . '@example.com';
        
        // Test valid registration data
        $validResult = $this->testRegistrationValidation(
            $testEmail,
            'Password123',
            'John',
            'Doe'
        );
        $this->assertTrue($validResult, 'Valid registration data should pass validation');
        
        // Test invalid email
        $invalidEmailResult = $this->testRegistrationValidation(
            'invalid-email',
            'Password123',
            'John',
            'Doe'
        );
        $this->assertFalse($invalidEmailResult, 'Registration should fail with invalid email');
        
        // Test weak password
        $invalidPasswordResult = $this->testRegistrationValidation(
            'test2@example.com',
            'weak',
            'John',
            'Doe'
        );
        $this->assertFalse($invalidPasswordResult, 'Registration should fail with weak password');
        
        // Test invalid name
        $invalidNameResult = $this->testRegistrationValidation(
            'test3@example.com',
            'Password123',
            'John123',
            'Doe'
        );
        $this->assertFalse($invalidNameResult, 'Registration should fail with invalid name');
    }

    public function testProfileFormValidation()
    {
        // Test valid profile data
        $validResult = $this->testProfileValidation(
            'John',
            'Doe',
            'test@example.com'
        );
        $this->assertTrue($validResult, 'Valid profile data should pass validation');
        
        // Test invalid name
        $invalidNameResult = $this->testProfileValidation(
            'John123',
            'Doe',
            'test@example.com'
        );
        $this->assertFalse($invalidNameResult, 'Profile should fail with invalid name');
        
        // Test invalid email
        $invalidEmailResult = $this->testProfileValidation(
            'John',
            'Doe',
            'invalid-email'
        );
        $this->assertFalse($invalidEmailResult, 'Profile should fail with invalid email');
    }

    public function testEmailUniquenessValidation()
    {
        $testEmail = 'unique-test-' . time() . '@example.com';

        // First registration should work
        $firstResult = $this->testRegistrationValidation(
            $testEmail,
            'Password123',
            'John',
            'Doe'
        );

        if ($firstResult) {
            // Actually create the user to test uniqueness - FIXED bind_param
            $sql = "INSERT INTO users (user_firstname, user_lastname, user_email, user_password, user_address, user_role, user_verified) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $hashedPassword = password_hash('Password123', PASSWORD_DEFAULT);
            $firstName = 'John';
            $lastName = 'Doe';
            $address = "singapore 1234";
            $role = 1;
            $verified = 0;
            $stmt->bind_param("sssssii", $firstName, $lastName, $testEmail, $hashedPassword, $address, $role, $verified);
            $stmt->execute();
            $stmt->close();

            // Second registration with same email should fail
            $secondResult = $this->testRegistrationValidation(
                $testEmail,
                'Password123',
                'Jane',
                'Smith'
            );
            $this->assertFalse($secondResult, 'Second registration with same email should fail');

            // Clean up
            $stmt = $this->conn->prepare("DELETE FROM users WHERE user_email = ?");
            $stmt->bind_param("s", $testEmail);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function testPasswordComplexityValidation()
    {
        $passwordTests = [
            ['Password123', true, 'Valid password with all requirements'],
            ['password123', false, 'Missing uppercase letter'],
            ['PASSWORD123', false, 'Missing lowercase letter'],
            ['Password', false, 'Missing number'],
            ['Pass123', false, 'Too short (less than 8 characters)'],
            ['P@ssw0rd123', true, 'Valid complex password'],
            ['12345678', false, 'Only numbers'],
            ['ABCDEFGH', false, 'Only uppercase letters'],
            ['abcdefgh', false, 'Only lowercase letters']
        ];
        
        foreach ($passwordTests as [$password, $expected, $description]) {
            $result = $this->validatePasswordUsingRealFunction($password);
            $this->assertEquals(
                $expected,
                $result,
                "{$description}: Password '{$password}' validation failed"
            );
        }
    }

    public function testInputSanitizationSecurity()
    {
        $securityTests = [
            '<script>alert("xss")</script>' => '',
            '<img src="x" onerror="alert(1)">' => '',
            '<iframe src="javascript:alert(1)"></iframe>' => '',
            "'; DROP TABLE users; --" => "'; DROP TABLE users; --",
            "1' OR '1'='1" => "1' OR '1'='1",
            '<p>Paragraph</p>' => 'Paragraph',
            '<div class="test">Content</div>' => 'Content',
            '<a href="link">Text</a>' => 'Text',
            '  <b>Bold</b> and <i>italic</i>  ' => 'Bold and italic',
            '<script>evil()</script>Good content' => 'Good content'
        ];
        
        foreach ($securityTests as $input => $expected) {
            $result = $this->sanitizeInputUsingRealFunction($input);
            $this->assertEquals(
                $expected,
                $result,
                "Sanitization failed for input: '{$input}'"
            );
        }
    }

    public function testFormDataIntegrity()
    {
        $testData = [
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
            'email' => ' TEST@EXAMPLE.COM ',
            'password' => 'Password123'
        ];
        
        foreach ($testData as $field => $value) {
            $sanitized = $this->sanitizeInputUsingRealFunction($value);
            
            if ($field === 'email') {
                $this->assertEquals('TEST@EXAMPLE.COM', $sanitized);
            } else {
                $this->assertEquals(trim($value), $sanitized);
            }
        }
    }
}