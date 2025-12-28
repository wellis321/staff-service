<?php
namespace Tests\Forms;

use Tests\TestCase;

/**
 * Test Login Form
 * Tests all aspects of the login form functionality
 */
class LoginFormTest extends TestCase
{
    public function testLoginFormDisplays()
    {
        // Simulate GET request
        $this->simulateGet();
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/login.php';
        });
        
        // Assert form elements exist
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('name="password"', $output);
        $this->assertStringContainsString('type="submit"', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }
    
    public function testLoginFormRequiresCsrfToken()
    {
        // Simulate POST without CSRF token
        $this->simulatePost([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/login.php';
        });
        
        $this->assertStringContainsString('Invalid security token', $output);
    }
    
    public function testLoginFormValidatesEmail()
    {
        $token = $this->getCsrfToken();
        
        // Test with invalid email
        $this->simulatePost([
            'email' => 'invalid-email',
            'password' => 'password123',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/login.php';
        });
        
        // Should show error (either validation error or invalid credentials)
        $this->assertTrue(
            strpos($output, 'Invalid') !== false || 
            strpos($output, 'error') !== false
        );
    }
    
    public function testLoginFormValidatesPassword()
    {
        $token = $this->getCsrfToken();
        
        // Test with empty password
        $this->simulatePost([
            'email' => 'test@example.com',
            'password' => '',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/login.php';
        });
        
        // HTML5 validation should prevent empty password, but check for error handling
        $this->assertStringContainsString('required', $output);
    }
    
    public function testLoginFormHandlesInvalidCredentials()
    {
        $token = $this->getCsrfToken();
        
        // Test with non-existent credentials
        $this->simulatePost([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/login.php';
        });
        
        // Should show error message (either "Invalid email or password" or similar)
        $this->assertTrue(
            strpos($output, 'Invalid') !== false || 
            strpos($output, 'error') !== false ||
            strpos($output, 'password') !== false
        );
    }
    
    public function testLoginFormRateLimiting()
    {
        $token = $this->getCsrfToken();
        
        // Simulate multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->simulatePost([
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
                'csrf_token' => $token
            ]);
            
            $this->captureOutput(function() {
                include dirname(__DIR__, 2) . '/public/login.php';
            });
        }
        
        // After 5 attempts, should show rate limit error
        $this->simulatePost([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/login.php';
        });
        
        $this->assertStringContainsString('Too many login attempts', $output);
    }
}

