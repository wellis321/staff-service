<?php
namespace Tests\Forms;

use Tests\TestCase;

/**
 * Test Registration Form
 * Tests all aspects of the registration form functionality
 */
class RegistrationFormTest extends TestCase
{
    public function testRegistrationFormDisplays()
    {
        // Ensure user is logged out
        $this->simulateLogout();
        
        // Simulate GET request
        $this->simulateGet();
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/register.php';
        });
        
        // Assert form elements exist
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('name="password"', $output);
        $this->assertStringContainsString('name="confirm_password"', $output);
        $this->assertStringContainsString('name="first_name"', $output);
        $this->assertStringContainsString('name="last_name"', $output);
        $this->assertStringContainsString('name="organisation_domain"', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }
    
    public function testRegistrationFormRequiresCsrfToken()
    {
        $this->simulateLogout();
        
        // Simulate POST without CSRF token
        $this->simulatePost([
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'organisation_domain' => 'example.com'
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/register.php';
        });
        
        $this->assertStringContainsString('Invalid security token', $output);
    }
    
    public function testRegistrationFormValidatesRequiredFields()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Test with missing required fields
        $this->simulatePost([
            'email' => '',
            'password' => '',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/register.php';
        });
        
        $this->assertStringContainsString('required', $output);
    }
    
    public function testRegistrationFormValidatesPasswordMatch()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Test with mismatched passwords
        $this->simulatePost([
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'differentpassword',
            'first_name' => 'Test',
            'last_name' => 'User',
            'organisation_domain' => 'example.com',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/register.php';
        });
        
        $this->assertStringContainsString('Passwords do not match', $output);
    }
    
    public function testRegistrationFormValidatesPasswordLength()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Test with password too short
        $this->simulatePost([
            'email' => 'test@example.com',
            'password' => 'short',
            'confirm_password' => 'short',
            'first_name' => 'Test',
            'last_name' => 'User',
            'organisation_domain' => 'example.com',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/register.php';
        });
        
        $this->assertStringContainsString('at least', $output);
    }
    
    public function testRegistrationFormRateLimiting()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Simulate multiple registration attempts
        for ($i = 0; $i < 4; $i++) {
            $this->simulatePost([
                'email' => 'test' . $i . '@example.com',
                'password' => 'password123',
                'confirm_password' => 'password123',
                'first_name' => 'Test',
                'last_name' => 'User',
                'organisation_domain' => 'example.com',
                'csrf_token' => $token
            ]);
            
            $this->captureOutput(function() {
                try {
                    include dirname(__DIR__, 2) . '/public/register.php';
                } catch (\Exception $e) {
                    // Ignore exceptions during rate limit testing
                }
            });
        }
        
        // After 3 attempts, should show rate limit error
        $this->simulatePost([
            'email' => 'test4@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'organisation_domain' => 'example.com',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/register.php';
        });
        
        $this->assertStringContainsString('Too many registration attempts', $output);
    }
}

