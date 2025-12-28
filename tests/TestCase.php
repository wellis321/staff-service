<?php
namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base Test Case
 * Provides common functionality for all tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected $testDb;
    protected $testOrganisationId;
    protected $testUserId;
    protected $testPersonId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear session
        $_SESSION = [];
        
        // Clear POST/GET data
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        
        // Ensure $_SERVER is properly initialized
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost';
        }
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '/';
        }
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }
        
        // Create test data if needed
        $this->setUpTestData();
    }
    
    protected function tearDown(): void
    {
        // Clean up any open output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Clean up test data
        $this->tearDownTestData();
        
        parent::tearDown();
    }
    
    /**
     * Set up test data (can be overridden)
     */
    protected function setUpTestData(): void
    {
        // Override in child classes if needed
    }
    
    /**
     * Tear down test data (can be overridden)
     */
    protected function tearDownTestData(): void
    {
        // Override in child classes if needed
    }
    
    /**
     * Simulate a POST request
     */
    protected function simulatePost(array $data, array $files = []): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;
        $_FILES = $files;
    }
    
    /**
     * Simulate a GET request
     */
    protected function simulateGet(array $data = []): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $data;
    }
    
    /**
     * Get CSRF token for testing
     */
    protected function getCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Simulate logged in user
     */
    protected function simulateLogin(int $userId, int $organisationId): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['organisation_id'] = $organisationId;
        $_SESSION['logged_in'] = true;
    }
    
    /**
     * Simulate logged out user
     */
    protected function simulateLogout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['organisation_id']);
        unset($_SESSION['logged_in']);
    }
    
    /**
     * Capture output from included PHP file
     * Handles nested output buffers properly
     */
    protected function captureOutput(callable $callback): string
    {
        // Start output buffering
        ob_start();
        
        try {
            // Execute callback and capture output
            $callback();
            
            // Get output
            $output = ob_get_contents();
            
            // Clean up
            ob_end_clean();
            
            return $output ?: '';
        } catch (\Exception $e) {
            // Clean up on exception
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        } catch (\Throwable $e) {
            // Clean up on any throwable
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
    }
    
    /**
     * Assert form validation error
     */
    protected function assertFormError(string $expectedError, callable $formHandler): void
    {
        $output = $this->captureOutput($formHandler);
        $this->assertStringContainsString($expectedError, $output, 'Expected form error not found');
    }
    
    /**
     * Assert form success
     */
    protected function assertFormSuccess(string $expectedSuccess, callable $formHandler): void
    {
        $output = $this->captureOutput($formHandler);
        $this->assertStringContainsString($expectedSuccess, $output, 'Expected success message not found');
    }
}

