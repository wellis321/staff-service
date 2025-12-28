<?php
/**
 * Rate Limiter Class
 * Provides rate limiting functionality to prevent brute force attacks and API abuse
 */

class RateLimiter {
    
    /**
     * Check if action is allowed based on rate limit
     * 
     * @param string $key Unique identifier (e.g., IP address, user ID, API key)
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public static function check($key, $maxAttempts, $windowSeconds) {
        $db = getDbConnection();
        
        // Check if rate_limits table exists
        if (!self::tableExists()) {
            // If table doesn't exist, allow the request (rate limiting disabled)
            return [
                'allowed' => true,
                'remaining' => $maxAttempts,
                'reset_at' => time() + $windowSeconds
            ];
        }
        
        // Clean up old entries (older than 24 hours)
        self::cleanup();
        
        try {
            // Get or create rate limit record
            $stmt = $db->prepare("
                SELECT attempts, first_attempt_at, reset_at
                FROM rate_limits
                WHERE rate_key = ? AND reset_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$key]);
            $record = $stmt->fetch();
            
            $now = time();
            
            if ($record) {
                // Record exists and is within window
                $attempts = (int)$record['attempts'];
                $resetAt = strtotime($record['reset_at']);
                
                if ($attempts >= $maxAttempts) {
                    // Rate limit exceeded
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'reset_at' => $resetAt
                    ];
                }
                
                // Increment attempts
                $stmt = $db->prepare("
                    UPDATE rate_limits
                    SET attempts = attempts + 1
                    WHERE rate_key = ? AND reset_at > NOW()
                ");
                $stmt->execute([$key]);
                
                return [
                    'allowed' => true,
                    'remaining' => $maxAttempts - $attempts - 1,
                    'reset_at' => $resetAt
                ];
            } else {
                // Create new record
                $resetAt = $now + $windowSeconds;
                $stmt = $db->prepare("
                    INSERT INTO rate_limits (rate_key, attempts, first_attempt_at, reset_at)
                    VALUES (?, 1, NOW(), FROM_UNIXTIME(?))
                    ON DUPLICATE KEY UPDATE
                        attempts = 1,
                        first_attempt_at = NOW(),
                        reset_at = FROM_UNIXTIME(?)
                ");
                $stmt->execute([$key, $resetAt, $resetAt]);
                
                return [
                    'allowed' => true,
                    'remaining' => $maxAttempts - 1,
                    'reset_at' => $resetAt
                ];
            }
        } catch (PDOException $e) {
            // If table doesn't exist or other database error, allow the request
            error_log("RateLimiter check error: " . $e->getMessage());
            return [
                'allowed' => true,
                'remaining' => $maxAttempts,
                'reset_at' => time() + $windowSeconds
            ];
        }
    }
    
    /**
     * Reset rate limit for a key
     * 
     * @param string $key
     * @return void
     */
    public static function reset($key) {
        if (!self::tableExists()) {
            return;
        }
        
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("DELETE FROM rate_limits WHERE rate_key = ?");
            $stmt->execute([$key]);
        } catch (Exception $e) {
            // Silently fail if table doesn't exist
            error_log("RateLimiter reset error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if rate_limits table exists
     * 
     * @return bool
     */
    private static function tableExists() {
        try {
            $db = getDbConnection();
            $stmt = $db->query("SHOW TABLES LIKE 'rate_limits'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Clean up old rate limit records
     * 
     * @return void
     */
    private static function cleanup() {
        if (!self::tableExists()) {
            return;
        }
        
        try {
            $db = getDbConnection();
            // Delete records older than 24 hours
            $stmt = $db->prepare("DELETE FROM rate_limits WHERE reset_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
        } catch (Exception $e) {
            // Silently fail cleanup if table doesn't exist or other error
            error_log("RateLimiter cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    public static function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

