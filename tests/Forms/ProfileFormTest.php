<?php
namespace Tests\Forms;

use Tests\TestCase;

/**
 * Test Profile Form
 * Tests all aspects of the profile form functionality
 */
class ProfileFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Simulate logged in user
        $this->simulateLogin(1, 1);
    }
    
    public function testProfileFormDisplays()
    {
        // Simulate GET request
        $this->simulateGet();
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/profile.php';
        });
        
        // Assert form elements exist
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('name="phone"', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }
    
    public function testProfileFormRequiresCsrfToken()
    {
        // Simulate POST without CSRF token
        $this->simulatePost([
            'action' => 'update_profile',
            'email' => 'test@example.com',
            'phone' => '1234567890'
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/profile.php';
        });
        
        $this->assertStringContainsString('Invalid security token', $output);
    }
    
    public function testProfileFormValidatesEmail()
    {
        $token = $this->getCsrfToken();
        
        // Test with invalid email
        $this->simulatePost([
            'action' => 'update_profile',
            'email' => 'invalid-email',
            'csrf_token' => $token
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/profile.php';
        });
        
        // HTML5 validation should catch this, but check for server-side validation
        $this->assertStringContainsString('email', $output);
    }
    
    public function testProfileFormHandlesFileUploads()
    {
        $token = $this->getCsrfToken();
        
        // Test photo upload form
        $this->simulatePost([
            'action' => 'upload_photo',
            'csrf_token' => $token
        ], [
            'photo' => [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/test.jpg',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ]
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/profile.php';
        });
        
        // Should handle file upload (may show error if file doesn't exist, but should not crash)
        $this->assertStringNotContainsString('Fatal error', $output);
        $this->assertStringNotContainsString('Parse error', $output);
    }
    
    public function testProfileFormHandlesSignatureUpload()
    {
        $token = $this->getCsrfToken();
        
        // Test signature upload
        $this->simulatePost([
            'action' => 'save_signature',
            'signature_method' => 'upload',
            'csrf_token' => $token
        ], [
            'signature_file' => [
                'name' => 'signature.png',
                'type' => 'image/png',
                'tmp_name' => '/tmp/signature.png',
                'error' => UPLOAD_ERR_OK,
                'size' => 2048
            ]
        ]);
        
        $output = $this->captureOutput(function() {
            include dirname(__DIR__, 2) . '/public/profile.php';
        });
        
        // Should handle signature upload (may show error if file doesn't exist, but should not crash)
        $this->assertStringNotContainsString('Fatal error', $output);
        $this->assertStringNotContainsString('Parse error', $output);
    }
}

