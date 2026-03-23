<?php
/**
 * Team Service API Client
 *
 * Wraps HTTP calls from PMS to the Team Service REST API.
 *
 * Connection settings are read per-organisation from the `organisation_settings`
 * table (configured via Admin → Integrations), falling back to .env values:
 *   TEAM_SERVICE_URL=http://localhost:8001
 *   TEAM_SERVICE_API_KEY=<key generated in Team Service settings>
 *
 * All methods return null/false on failure (e.g. Team Service unreachable).
 */
class TeamServiceClient
{
    // ── Per-org config ────────────────────────────────────────────────────────

    private static function baseUrl(int $orgId): string
    {
        return rtrim(
            OrgSettings::get($orgId, 'team_service_url', getenv('TEAM_SERVICE_URL') ?: ''),
            '/'
        );
    }

    private static function apiKey(int $orgId): string
    {
        return OrgSettings::get($orgId, 'team_service_api_key', getenv('TEAM_SERVICE_API_KEY') ?: '');
    }

    public static function enabled(int $orgId): bool
    {
        return self::baseUrl($orgId) !== '' && self::apiKey($orgId) !== '';
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private static function get(int $orgId, string $path): ?array
    {
        if (!self::enabled($orgId)) return null;

        $url = self::baseUrl($orgId) . $path;
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => 'Authorization: Bearer ' . self::apiKey($orgId) . "\r\n" .
                                   'Accept: application/json' . "\r\n",
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;

        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private static function post(int $orgId, string $path, array $payload): ?array
    {
        if (!self::enabled($orgId)) return null;

        $url  = self::baseUrl($orgId) . $path;
        $json = json_encode($payload);
        $ctx  = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => 'Authorization: Bearer ' . self::apiKey($orgId) . "\r\n" .
                                   'Content-Type: application/json' . "\r\n" .
                                   'Content-Length: ' . strlen($json) . "\r\n" .
                                   'Accept: application/json' . "\r\n",
                'content'       => $json,
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;

        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Get all teams a staff member belongs to.
     */
    public static function getTeamsForStaff(int $staffId, int $orgId): ?array
    {
        $res = self::get($orgId, '/api/members.php?member_type=staff'
                         . '&external_id=' . $staffId
                         . '&organisation_id=' . $orgId);
        return $res['data'] ?? null;
    }

    /**
     * Add a staff member to a team.
     */
    public static function addStaffToTeam(
        int    $teamId,
        int    $orgId,
        int    $staffId,
        string $displayName,
        string $displayRef  = '',
        ?int   $roleId      = null,
        bool   $isPrimary   = false,
        ?string $joinedAt   = null
    ): bool {
        $res = self::post($orgId, '/api/members.php', [
            'action'          => 'add',
            'team_id'         => $teamId,
            'organisation_id' => $orgId,
            'member_type'     => 'staff',
            'external_id'     => $staffId,
            'display_name'    => $displayName,
            'display_ref'     => $displayRef,
            'team_role_id'    => $roleId,
            'is_primary_team' => $isPrimary,
            'joined_at'       => $joinedAt,
        ]);
        return !empty($res['success']);
    }

    /**
     * Remove a staff member from a team.
     */
    public static function removeStaffFromTeam(
        int    $teamId,
        int    $orgId,
        int    $staffId,
        ?string $leftAt = null
    ): bool {
        $res = self::post($orgId, '/api/members.php', [
            'action'      => 'remove',
            'team_id'     => $teamId,
            'member_type' => 'staff',
            'external_id' => $staffId,
            'left_at'     => $leftAt,
        ]);
        return !empty($res['success']);
    }

    /**
     * Push a name change to the Team Service (refreshes cached display_name).
     */
    public static function refreshDisplayName(
        int    $orgId,
        int    $staffId,
        string $displayName,
        string $displayRef = ''
    ): void {
        self::post($orgId, '/api/members.php', [
            'action'       => 'refresh_display',
            'member_type'  => 'staff',
            'external_id'  => $staffId,
            'display_name' => $displayName,
            'display_ref'  => $displayRef,
        ]);
    }

    /**
     * Get all active staff memberships for an org, with team names.
     * Returns a flat array; each row has team_id, team_name, external_id, display_name, etc.
     * Used by the staff list page to build the grouped-by-team view.
     */
    public static function getAllStaffMemberships(int $orgId): ?array
    {
        $res = self::get($orgId, '/api/members.php?all=1&member_type=staff&organisation_id=' . $orgId);
        return $res['data'] ?? null;
    }

    /**
     * Get all active teams for an organisation (for the "add to team" dropdown).
     */
    public static function getTeams(int $orgId): ?array
    {
        $res = self::get($orgId, '/api/teams.php?organisation_id=' . $orgId);
        return $res['data'] ?? null;
    }

    /**
     * Look up the remote org ID for a given domain.
     * Returns the remote org_id or null if not found / service unreachable.
     */
    public static function orgLookup(string $url, string $apiKey, string $domain): ?int
    {
        $url = rtrim($url, '/') . '/api/org-lookup.php?domain=' . urlencode($domain);
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => 'Authorization: Bearer ' . $apiKey . "\r\nAccept: application/json\r\n",
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;
        $data = json_decode($body, true);
        return isset($data['org_id']) ? (int) $data['org_id'] : null;
    }

    /**
     * Provision a new organisation on the Team Service.
     * Uses PROVISION_SECRET — not an org API key.
     * Returns the remote org_id on success, null on failure.
     */
    public static function provisionOrg(
        string $name,
        string $domain,
        string $firstName,
        string $lastName,
        string $email,
        string $password
    ): ?int {
        $baseUrl = rtrim(getenv('TEAM_SERVICE_URL') ?: TEAM_SERVICE_URL, '/');
        $secret  = PROVISION_SECRET;
        if (!$baseUrl || !$secret) return null;

        $payload = json_encode(compact('name', 'domain', 'firstName', 'lastName', 'email', 'password'));
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => 'Authorization: Bearer ' . $secret . "\r\n" .
                                   'Content-Type: application/json' . "\r\n" .
                                   'Content-Length: ' . strlen($payload) . "\r\n",
                'content'       => $payload,
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($baseUrl . '/api/org-provision.php', false, $ctx);
        if ($body === false) return null;
        $data = json_decode($body, true);
        return !empty($data['success']) ? (int) $data['org_id'] : null;
    }

    /**
     * Test connection for a specific URL and key (used by settings page).
     */
    public static function testConnection(string $url, string $apiKey): bool
    {
        $url = rtrim($url, '/') . '/api/teams.php?organisation_id=0';
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => 'Authorization: Bearer ' . $apiKey . "\r\n" .
                                   'Accept: application/json' . "\r\n",
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        // Any JSON response (even an error) means the service is reachable
        return $body !== false && json_decode($body) !== null;
    }
}
