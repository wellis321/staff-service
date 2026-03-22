<?php
/**
 * Team Service API Client
 *
 * Wraps HTTP calls from PMS to the Team Service REST API.
 * Configured via .env:
 *   TEAM_SERVICE_URL=http://localhost:8001
 *   TEAM_SERVICE_API_KEY=<key generated in Team Service settings>
 *
 * All methods return null on failure (e.g. Team Service unreachable).
 */
class TeamServiceClient
{
    private static function baseUrl(): string
    {
        return rtrim(getenv('TEAM_SERVICE_URL') ?: '', '/');
    }

    private static function apiKey(): string
    {
        return getenv('TEAM_SERVICE_API_KEY') ?: '';
    }

    private static function isConfigured(): bool
    {
        return self::baseUrl() !== '' && self::apiKey() !== '';
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private static function get(string $path): ?array
    {
        if (!self::isConfigured()) return null;

        $url = self::baseUrl() . $path;
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => 'Authorization: Bearer ' . self::apiKey() . "\r\n" .
                             'Accept: application/json' . "\r\n",
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;

        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private static function post(string $path, array $payload): ?array
    {
        if (!self::isConfigured()) return null;

        $url  = self::baseUrl() . $path;
        $json = json_encode($payload);
        $ctx  = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Authorization: Bearer ' . self::apiKey() . "\r\n" .
                             'Content-Type: application/json' . "\r\n" .
                             'Content-Length: ' . strlen($json) . "\r\n" .
                             'Accept: application/json' . "\r\n",
                'content' => $json,
                'timeout' => 5,
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
     *
     * @param int $staffId      PMS people.id
     * @param int $orgId        Organisation ID
     * @return array[]|null     Array of team rows, or null if unreachable
     */
    public static function getTeamsForStaff(int $staffId, int $orgId): ?array
    {
        $res = self::get('/api/members.php?member_type=staff'
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
        $res = self::post('/api/members.php', [
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
        int    $staffId,
        ?string $leftAt = null
    ): bool {
        $res = self::post('/api/members.php', [
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
        int    $staffId,
        string $displayName,
        string $displayRef = ''
    ): void {
        self::post('/api/members.php', [
            'action'       => 'refresh_display',
            'member_type'  => 'staff',
            'external_id'  => $staffId,
            'display_name' => $displayName,
            'display_ref'  => $displayRef,
        ]);
    }

    /**
     * Get all active teams for an organisation (for the "add to team" dropdown).
     */
    public static function getTeams(int $orgId): ?array
    {
        $res = self::get('/api/teams.php?organisation_id=' . $orgId);
        return $res['data'] ?? null;
    }

    /**
     * Whether the Team Service is configured in .env.
     */
    public static function enabled(): bool
    {
        return self::isConfigured();
    }
}
