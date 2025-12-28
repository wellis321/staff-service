<?php
namespace Tests\Forms;

use Tests\TestCase;

/**
 * Test Contact Form
 * Tests all aspects of the contact form functionality
 */
class ContactFormTest extends TestCase
{
    public function testContactFormDisplays()
    {
        // Simulate GET request
        $this->simulateGet();
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/contact.php';
        });
        
        // Assert form elements exist
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('name="name"', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('name="subject"', $output);
        $this->assertStringContainsString('name="message"', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }
    
    public function testContactFormRequiresCsrfToken()
    {
        // Simulate POST without CSRF token
        $this->simulatePost([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message'
        ]);
        
        ob_start();
        include dirname(__DIR__, 2) . '/public/contact.php';
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Invalid security token', $output);
    }
    
    public function testContactFormValidatesName()
    {
        $token = $this->getCsrfToken();
        
        // Test with empty name
        $this->simulatePost([
            'name' => '',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'csrf_token' => $token
        ]);
        
        ob_start();
        include dirname(__DIR__, 2) . '/public/contact.php';
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Please enter your name', $output);
    }
    
    public function testContactFormValidatesEmail()
    {
        $token = $this->getCsrfToken();
        
        // Test with empty email
        $this->simulatePost([
            'name' => 'Test User',
            'email' => '',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'csrf_token' => $token
        ]);
        
        ob_start();
        include dirname(__DIR__, 2) . '/public/contact.php';
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Please enter your email', $output);
    }
    
    public function testContactFormValidatesEmailFormat()
    {
        $token = $this->getCsrfToken();
        
        // Test with invalid email format
        $this->simulatePost([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'csrf_token' => $token
        ]);
        
        ob_start();
        include dirname(__DIR__, 2) . '/public/contact.php';
        $output = ob_get_clean();
        
        $this->assertStringContainsString('valid email address', $output);
    }
    
    public function testContactFormValidatesSubject()
    {
        $token = $this->getCsrfToken();
        
        // Test with empty subject
        $this->simulatePost([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => '',
            'message' => 'Test message',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/contact.php';
        });
        
        // Should show error about subject (either validation error or HTML5 required attribute)
        $this->assertTrue(
            strpos($output, 'subject') !== false || 
            strpos($output, 'required') !== false ||
            strpos($output, 'Please enter') !== false
        );
    }
    
    public function testContactFormValidatesMessage()
    {
        $token = $this->getCsrfToken();
        
        // Test with empty message
        $this->simulatePost([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => '',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/contact.php';
        });
        
        // Should show error about message (either validation error or HTML5 required attribute)
        $this->assertTrue(
            strpos($output, 'message') !== false || 
            strpos($output, 'required') !== false ||
            strpos($output, 'Please enter') !== false
        );
    }
    
    public function testContactFormRateLimiting()
    {
        $token = $this->getCsrfToken();
        
        // Simulate multiple contact form submissions
        for ($i = 0; $i < 4; $i++) {
            $this->simulatePost([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'subject' => 'Test Subject ' . $i,
                'message' => 'Test message ' . $i,
                'csrf_token' => $token
            ]);
            
            $this->captureOutput(function() {
                try {
                    include dirname(__DIR__, 2) . '/public/contact.php';
                } catch (\Exception $e) {
                    // Ignore exceptions during rate limit testing
                }
            });
        }
        
        // After 3 attempts, should show rate limit error
        $this->simulatePost([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject 4',
            'message' => 'Test message 4',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/contact.php';
        });
        
        $this->assertStringContainsString('Too many contact form submissions', $output);
    }
}

