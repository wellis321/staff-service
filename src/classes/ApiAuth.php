<?php
/**
 * API Authentication Class
 * Handles API key and session-based authentication for API endpoints
 */

class ApiAuth {
    
    /**
     * Authenticate API request
     * Supports API key (via header) or session-based authentication
     * 
     * @return array|false Returns user data on success, false on failure
     */
    public static function authenticate() {
        // Try API key authentication first
        $apiKey = self::getApiKey();
        
        if ($apiKey) {
            $keyData = self::validateApiKey($apiKey);
            
            if ($keyData) {
                // Get full user data
                $db = getDbConnection();
                $stmt = $db->prepare("SELECT id, organisation_id, email, first_name, last_name FROM users WHERE id = (SELECT user_id FROM api_keys WHERE id = ?)");
                $stmt->execute([$keyData['id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    return $user;
                }
            }
        }
        
        // Fall back to session-based authentication
        if (Auth::isLoggedIn()) {
            return Auth::getUser();
        }
        
        return false;
    }
    
    /**
     * Get all HTTP headers (compatibility function)
     */
    private static function getAllHeaders() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        // Fallback for environments without getallheaders()
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
    
    
    /**
     * Authenticate using API key
     */
    private static function authenticateApiKey($apiKey) {
        $db = getDbConnection();
        
        // Look up API key in database (we'll need an api_keys table)
        $stmt = $db->prepare("
            SELECT ak.*, u.id as user_id, u.organisation_id, u.email, u.first_name, u.last_name
            FROM api_keys ak
            JOIN users u ON ak.user_id = u.id
            WHERE ak.api_key_hash = ? 
            AND ak.is_active = TRUE
            AND u.is_active = TRUE
            AND (ak.expires_at IS NULL OR ak.expires_at > NOW())
        ");
        
        $apiKeyHash = hash('sha256', $apiKey);
        $stmt->execute([$apiKeyHash]);
        $keyData = $stmt->fetch();
        
        if (!$keyData) {
            return false;
        }
        
        // Update last used timestamp
        $updateStmt = $db->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$keyData['id']]);
        
        // Return user data
        return [
            'id' => $keyData['user_id'],
            'organisation_id' => $keyData['organisation_id'],
            'email' => $keyData['email'],
            'first_name' => $keyData['first_name'],
            'last_name' => $keyData['last_name'],
            'api_key_id' => $keyData['id'],
            'api_key_name' => $keyData['name'] ?? 'API Key'
        ];
    }
    
    /**
     * Get authenticated user's organisation ID
     */
    public static function getOrganisationId() {
        $user = self::authenticate();
        return $user['organisation_id'] ?? null;
    }
    
    /**
     * Get API key from request headers (public method)
     */
    public static function getApiKey() {
        // Check Authorization header
        $headers = self::getAllHeaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
            if (preg_match('/ApiKey\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        // Check X-API-Key header
        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }
        
        return null;
    }
    
    /**
     * Validate API key and return key data
     * @param string $apiKey
     * @return array|false Returns key data with organisation_id on success, false on failure
     */
    public static function validateApiKey($apiKey) {
        $db = getDbConnection();
        
        $apiKeyHash = hash('sha256', $apiKey);
        
        $stmt = $db->prepare("
            SELECT ak.*, u.organisation_id
            FROM api_keys ak
            JOIN users u ON ak.user_id = u.id
            WHERE ak.api_key_hash = ? 
            AND ak.is_active = TRUE
            AND u.is_active = TRUE
            AND (ak.expires_at IS NULL OR ak.expires_at > NOW())
        ");
        
        $stmt->execute([$apiKeyHash]);
        $keyData = $stmt->fetch();
        
        if (!$keyData) {
            return false;
        }
        
        // Update last used timestamp
        $updateStmt = $db->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$keyData['id']]);
        
        return [
            'id' => $keyData['id'],
            'organisation_id' => $keyData['organisation_id'],
            'name' => $keyData['name'] ?? 'API Key'
        ];
    }
}

