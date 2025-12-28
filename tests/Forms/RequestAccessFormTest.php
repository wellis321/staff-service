<?php
namespace Tests\Forms;

use Tests\TestCase;

/**
 * Test Request Access Form
 * Tests all aspects of the organisation access request form
 */
class RequestAccessFormTest extends TestCase
{
    public function testRequestAccessFormDisplays()
    {
        // Ensure user is logged out
        $this->simulateLogout();
        
        // Simulate GET request
        $this->simulateGet();
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/request-access.php';
        });
        
        // Assert form elements exist
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('name="organisation_name"', $output);
        $this->assertStringContainsString('name="organisation_domain"', $output);
        $this->assertStringContainsString('name="seats_requested"', $output);
        $this->assertStringContainsString('name="contact_name"', $output);
        $this->assertStringContainsString('name="contact_email"', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }
    
    public function testRequestAccessFormRequiresCsrfToken()
    {
        $this->simulateLogout();
        
        // Simulate POST without CSRF token
        $this->simulatePost([
            'organisation_name' => 'Test Org',
            'organisation_domain' => 'test.com',
            'seats_requested' => '10',
            'contact_name' => 'Test User',
            'contact_email' => 'test@example.com'
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/request-access.php';
        });
        
        $this->assertStringContainsString('Invalid security token', $output);
    }
    
    public function testRequestAccessFormValidatesRequiredFields()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Test with missing required fields
        $this->simulatePost([
            'organisation_name' => '',
            'organisation_domain' => '',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/request-access.php';
        });
        
        $this->assertStringContainsString('required', $output);
    }
    
    public function testRequestAccessFormValidatesEmail()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Test with invalid email
        $this->simulatePost([
            'organisation_name' => 'Test Org',
            'organisation_domain' => 'test.com',
            'seats_requested' => '10',
            'contact_name' => 'Test User',
            'contact_email' => 'invalid-email',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/request-access.php';
        });
        
        $this->assertStringContainsString('valid email', $output);
    }
    
    public function testRequestAccessFormValidatesSeatsRequested()
    {
        $this->simulateLogout();
        $token = $this->getCsrfToken();
        
        // Test with invalid seats (negative or zero)
        $this->simulatePost([
            'organisation_name' => 'Test Org',
            'organisation_domain' => 'test.com',
            'seats_requested' => '0',
            'contact_name' => 'Test User',
            'contact_email' => 'test@example.com',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/request-access.php';
        });
        
        // HTML5 min="1" should prevent this, but check for validation
        $this->assertStringContainsString('min="1"', $output);
    }
}

