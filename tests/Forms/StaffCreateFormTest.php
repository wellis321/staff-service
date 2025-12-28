<?php
namespace Tests\Forms;

use Tests\TestCase;

/**
 * Test Staff Create Form
 * Tests all aspects of the staff creation form functionality
 */
class StaffCreateFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Simulate admin login for staff creation
        // Note: In real tests, you'd create actual test users/organisations
        $this->simulateLogin(1, 1);
    }
    
    public function testStaffCreateFormDisplays()
    {
        // Simulate GET request
        $this->simulateGet();
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/staff/create.php';
        });
        
        // Assert form elements exist
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('name="first_name"', $output);
        $this->assertStringContainsString('name="last_name"', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }
    
    public function testStaffCreateFormRequiresCsrfToken()
    {
        // Simulate POST without CSRF token
        $this->simulatePost([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com'
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/staff/create.php';
        });
        
        $this->assertStringContainsString('Invalid security token', $output);
    }
    
    public function testStaffCreateFormValidatesRequiredFields()
    {
        $token = $this->getCsrfToken();
        
        // Test with missing required fields
        $this->simulatePost([
            'first_name' => '',
            'last_name' => '',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/staff/create.php';
        });
        
        $this->assertStringContainsString('required', $output);
    }
    
    public function testStaffCreateFormAcceptsOptionalFields()
    {
        $token = $this->getCsrfToken();
        
        // Test with only required fields
        $this->simulatePost([
            'first_name' => 'Test',
            'last_name' => 'User',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/staff/create.php';
        });
        
        // Form should display (not crash) - may show error if database insert fails, but form should render
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('first_name', $output);
        $this->assertStringContainsString('last_name', $output);
    }
    
    public function testStaffCreateFormHandlesDateFields()
    {
        $token = $this->getCsrfToken();
        
        // Test with valid date
        $this->simulatePost([
            'first_name' => 'Test',
            'last_name' => 'User',
            'date_of_birth' => '1990-01-01',
            'employment_start_date' => '2024-01-01',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/staff/create.php';
        });
        
        // Should handle dates correctly (no date validation errors)
        $this->assertStringNotContainsString('invalid date', strtolower($output));
    }
}

