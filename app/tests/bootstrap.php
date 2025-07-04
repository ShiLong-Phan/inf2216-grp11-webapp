<?php

// Try to include PHPUnit autoloader
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    '/usr/share/php/PHPUnit/autoload.php',
    '/usr/local/lib/php/PHPUnit/autoload.php'
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

// If PHPUnit classes still aren't available, try manual inclusion
if (!class_exists('PHPUnit\Framework\TestCase')) {
    // Manual inclusion for basic PHPUnit functionality
    abstract class TestCase
    {
        protected function setUp(): void {}
        protected function tearDown(): void {}
        
        protected function assertTrue($condition, $message = '') {
            if (!$condition) {
                throw new Exception($message ?: 'Assertion failed: expected true');
            }
            echo ".";
        }
        
        protected function assertFalse($condition, $message = '') {
            if ($condition) {
                throw new Exception($message ?: 'Assertion failed: expected false');
            }
            echo ".";
        }
        
        protected function assertEquals($expected, $actual, $message = '') {
            if ($expected !== $actual) {
                throw new Exception($message ?: "Assertion failed: expected '$expected', got '$actual'");
            }
            echo ".";
        }
        
        protected function assertNotEquals($expected, $actual, $message = '') {
            if ($expected === $actual) {
                throw new Exception($message ?: "Assertion failed: expected not '$expected'");
            }
            echo ".";
        }
    }
    
    // Alias for PHPUnit namespace
    class_alias('TestCase', 'PHPUnit\Framework\TestCase');
}