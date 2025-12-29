<?php
/**
 * Microsoft Entra/365 Integration Class
 * Handles Microsoft Entra ID integration for SSO and staff sync
 * Staff Service is the central hub for Entra integration
 */

class EntraIntegration {
    
    /**
     * Check if Entra integration is enabled for an organisation
     */
    public static function isEnabled($organisationId) {
        $db = getDbConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT entra_enabled, entra_tenant_id, entra_client_id 
                FROM organisations 
                WHERE id = ?
            ");
            $stmt->execute([$organisationId]);
            $org = $stmt->fetch();
            
            if (!$org || !isset($org['entra_enabled'])) {
                return false;
            }
            
            return $org['entra_enabled'] && !empty($org['entra_tenant_id']) && !empty($org['entra_client_id']);
        } catch (PDOException $e) {
            // If column doesn't exist, Entra is not enabled
            if (strpos($e->getMessage(), "Unknown column 'entra_enabled'") !== false) {
                return false;
            }
            throw $e;
        }
    }
    
    /**
     * Get Entra configuration for organisation
     */
    public static function getConfig($organisationId) {
        $db = getDbConnection();
        
        try {
            $stmt = $db->prepare("
                SELECT entra_enabled, entra_tenant_id, entra_client_id 
                FROM organisations 
                WHERE id = ?
            ");
            $stmt->execute([$organisationId]);
            $config = $stmt->fetch();
            
            if (!$config || !isset($config['entra_enabled'])) {
                return [
                    'entra_enabled' => false,
                    'entra_tenant_id' => null,
                    'entra_client_id' => null
                ];
            }
            
            return $config;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column 'entra_enabled'") !== false) {
                return [
                    'entra_enabled' => false,
                    'entra_tenant_id' => null,
                    'entra_client_id' => null
                ];
            }
            throw $e;
        }
    }
    
    /**
     * Enable Entra integration for organisation
     */
    public static function enable($organisationId, $tenantId, $clientId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("
            UPDATE organisations 
            SET entra_enabled = TRUE, 
                entra_tenant_id = ?, 
                entra_client_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$tenantId, $clientId, $organisationId]);
        
        return ['success' => true];
    }
    
    /**
     * Disable Entra integration for organisation
     */
    public static function disable($organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            UPDATE organisations 
            SET entra_enabled = FALSE 
            WHERE id = ?
        ");
        $stmt->execute([$organisationId]);
        
        return ['success' => true];
    }
    
    /**
     * Get OAuth authorization URL
     */
    public static function getAuthorizationUrl($organisationId, $redirectUri) {
        $config = self::getConfig($organisationId);
        
        if (!$config || !$config['entra_enabled']) {
            return null;
        }
        
        $tenantId = $config['entra_tenant_id'];
        $clientId = $config['entra_client_id'];
        
        $params = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => bin2hex(random_bytes(16)) // CSRF protection
        ];
        
        return "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?" . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public static function exchangeCodeForToken($organisationId, $code, $redirectUri) {
        $config = self::getConfig($organisationId);
        
        if (!$config || !$config['entra_enabled']) {
            return ['success' => false, 'message' => 'Entra integration not enabled'];
        }
        
        $tenantId = $config['entra_tenant_id'];
        $clientId = $config['entra_client_id'];
        $clientSecret = getenv('ENTRA_CLIENT_SECRET');
        
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
        
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is deprecated in PHP 8.5+ - resources are automatically closed
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'Failed to exchange code for token'];
        }
        
        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            return ['success' => false, 'message' => 'Invalid token response'];
        }
        
        return ['success' => true, 'token' => $tokenData];
    }
    
    /**
     * Get user info from Microsoft Graph API
     */
    public static function getUserInfo($accessToken) {
        $graphUrl = 'https://graph.microsoft.com/v1.0/me';
        
        $ch = curl_init($graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is deprecated in PHP 8.5+ - resources are automatically closed
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Sync person from Entra ID
     */
    public static function syncPerson($organisationId, $entraUserId, $personId = null) {
        $db = getDbConnection();
        
        // Check if sync record exists
        $stmt = $db->prepare("
            SELECT * FROM entra_sync 
            WHERE organisation_id = ? AND entra_user_id = ?
        ");
        $stmt->execute([$organisationId, $entraUserId]);
        $syncRecord = $stmt->fetch();
        
        if ($syncRecord) {
            // Update existing sync
            $stmt = $db->prepare("
                UPDATE entra_sync 
                SET last_synced_at = NOW(), 
                    sync_status = 'active',
                    sync_error = NULL
                WHERE id = ?
            ");
            $stmt->execute([$syncRecord['id']]);
        } else {
            // Create new sync record
            if (!$personId) {
                return ['success' => false, 'message' => 'Person ID required for new sync'];
            }
            
            $stmt = $db->prepare("
                INSERT INTO entra_sync 
                (person_id, organisation_id, entra_user_id, last_synced_at, sync_status)
                VALUES (?, ?, ?, NOW(), 'active')
            ");
            $stmt->execute([$personId, $organisationId, $entraUserId]);
        }
        
        return ['success' => true];
    }
    
    /**
     * Get sync status for person
     */
    public static function getSyncStatus($personId) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT * FROM entra_sync 
            WHERE person_id = ?
        ");
        $stmt->execute([$personId]);
        return $stmt->fetch();
    }
    
    /**
     * Get application access token (client credentials flow) for bulk operations
     * Requires application permissions: User.Read.All
     */
    public static function getApplicationToken($organisationId) {
        $config = self::getConfig($organisationId);
        
        if (!$config || !$config['entra_enabled']) {
            return ['success' => false, 'message' => 'Entra integration not enabled'];
        }
        
        $tenantId = $config['entra_tenant_id'];
        $clientId = $config['entra_client_id'];
        $clientSecret = getenv('ENTRA_CLIENT_SECRET');
        
        if (!$clientSecret) {
            return ['success' => false, 'message' => 'Client secret not configured'];
        }
        
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
        
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is deprecated in PHP 8.5+ - resources are automatically closed
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'Failed to get application token', 'http_code' => $httpCode, 'response' => $response];
        }
        
        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            return ['success' => false, 'message' => 'Invalid token response'];
        }
        
        return ['success' => true, 'token' => $tokenData['access_token']];
    }
    
    /**
     * Fetch all users from Microsoft Graph API
     * Requires User.Read.All application permission
     */
    public static function fetchAllUsers($organisationId) {
        $tokenResult = self::getApplicationToken($organisationId);
        
        if (!$tokenResult['success']) {
            return ['success' => false, 'message' => $tokenResult['message']];
        }
        
        $accessToken = $tokenResult['token'];
        $allUsers = [];
        $nextLink = 'https://graph.microsoft.com/v1.0/users?$select=id,mail,userPrincipalName,givenName,surname,displayName,jobTitle,department,employeeId,accountEnabled';
        
        // Handle pagination
        while ($nextLink) {
            $ch = curl_init($nextLink);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // curl_close() is deprecated in PHP 8.5+ - resources are automatically closed
            
            if ($httpCode !== 200) {
                return ['success' => false, 'message' => 'Failed to fetch users from Microsoft Graph', 'http_code' => $httpCode];
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['value'])) {
                $allUsers = array_merge($allUsers, $data['value']);
            }
            
            // Check for next page
            $nextLink = $data['@odata.nextLink'] ?? null;
        }
        
        return ['success' => true, 'users' => $allUsers];
    }
    
    /**
     * Sync users from Microsoft Entra ID to Staff Service
     * Creates/updates people records from Entra users
     */
    public static function syncUsersFromEntra($organisationId) {
        require_once SRC_PATH . '/models/Person.php';
        
        // Fetch users from Microsoft Graph
        $fetchResult = self::fetchAllUsers($organisationId);
        
        if (!$fetchResult['success']) {
            return $fetchResult;
        }
        
        $entraUsers = $fetchResult['users'];
        
        if (empty($entraUsers)) {
            return ['success' => true, 'people_created' => 0, 'people_updated' => 0, 'people_skipped' => 0, 'warnings' => ['No users found in Microsoft Entra ID']];
        }
        
        $peopleCreated = 0;
        $peopleUpdated = 0;
        $peopleSkipped = 0;
        $warnings = [];
        $db = getDbConnection();
        
        foreach ($entraUsers as $entraUser) {
            // Skip disabled accounts
            if (isset($entraUser['accountEnabled']) && !$entraUser['accountEnabled']) {
                continue;
            }
            
            $email = $entraUser['mail'] ?? $entraUser['userPrincipalName'] ?? '';
            $firstName = $entraUser['givenName'] ?? '';
            $lastName = $entraUser['surname'] ?? '';
            $employeeId = $entraUser['employeeId'] ?? '';
            
            // Skip if no email
            if (empty($email)) {
                $peopleSkipped++;
                continue;
            }
            
            // Use display name if first/last name not available
            if (empty($firstName) && empty($lastName) && !empty($entraUser['displayName'])) {
                $nameParts = explode(' ', $entraUser['displayName'], 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
            }
            
            // Check if person exists by email
            $existingPerson = null;
            $staffList = Person::searchStaff($organisationId, $email);
            foreach ($staffList as $staff) {
                if (($staff['email'] ?? '') === $email || ($staff['user_email'] ?? '') === $email) {
                    $existingPerson = $staff;
                    break;
                }
            }
            
            if ($existingPerson) {
                // Update existing person
                $updateData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ];
                
                if (!empty($employeeId)) {
                    $updateData['employee_reference'] = $employeeId;
                }
                
                Person::update($existingPerson['id'], $organisationId, $updateData);
                
                // Sync to Entra
                self::syncPerson($organisationId, $entraUser['id'], $existingPerson['id']);
                
                $peopleUpdated++;
            } else {
                // Create new person
                $personData = [
                    'organisation_id' => $organisationId,
                    'person_type' => 'staff',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'employee_reference' => !empty($employeeId) ? $employeeId : null,
                    'is_active' => true
                ];
                
                $result = Person::create($personData);
                
                if ($result['success']) {
                    // Sync to Entra
                    self::syncPerson($organisationId, $entraUser['id'], $result['person_id']);
                    $peopleCreated++;
                } else {
                    $warnings[] = "Failed to create person for {$email}: " . ($result['message'] ?? 'Unknown error');
                    $peopleSkipped++;
                }
            }
        }
        
        return [
            'success' => true,
            'people_created' => $peopleCreated,
            'people_updated' => $peopleUpdated,
            'people_skipped' => $peopleSkipped,
            'warnings' => $warnings
        ];
    }
}

