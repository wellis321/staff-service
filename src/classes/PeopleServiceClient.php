<?php
/**
 * People Service API Client (used by PMS/Staff Service)
 *
 * Connection settings are read per-organisation from organisation_settings,
 * falling back to .env values:
 *   PEOPLE_SERVICE_URL=http://localhost:8002
 *   PEOPLE_SERVICE_API_KEY=<key from People Service Admin → Settings>
 */
class PeopleServiceClient
{
    private static function baseUrl(int $orgId): string
    {
        return rtrim(
            OrgSettings::get($orgId, 'people_service_url', getenv('PEOPLE_SERVICE_URL') ?: PEOPLE_SERVICE_URL),
            '/'
        );
    }

    private static function apiKey(int $orgId): string
    {
        return OrgSettings::get($orgId, 'people_service_api_key', getenv('PEOPLE_SERVICE_API_KEY') ?: '');
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

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Look up the remote org ID for a given domain.
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
     * Provision a new organisation on the People Service.
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
        $baseUrl = rtrim(getenv('PEOPLE_SERVICE_URL') ?: PEOPLE_SERVICE_URL, '/');
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
}
