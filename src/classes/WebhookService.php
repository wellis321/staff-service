<?php
/**
 * Webhook Service
 * Delivers webhook events to all active subscribers for an organisation.
 */

class WebhookService {

    /**
     * Fire an event to all active subscribers for the given organisation.
     *
     * @param string $event          e.g. 'person.deactivated', 'organisation.deactivated'
     * @param array  $data           Event payload data
     * @param int    $organisationId Organisation whose subscribers to notify
     */
    public static function fire($event, $data, $organisationId) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
                SELECT id, url, secret
                FROM webhook_subscriptions
                WHERE organisation_id = ?
                  AND is_active = 1
                  AND JSON_CONTAINS(events, ?)
            ");
            $stmt->execute([$organisationId, json_encode($event)]);
            $subscribers = $stmt->fetchAll();

            if (empty($subscribers)) {
                return;
            }

            $payload = json_encode([
                'event'           => $event,
                'timestamp'       => date('c'),
                'organisation_id' => $organisationId,
                'data'            => $data,
            ]);

            foreach ($subscribers as $subscriber) {
                self::deliver($subscriber, $payload, $event);
            }
        } catch (Exception $e) {
            error_log('WebhookService::fire error for event ' . $event . ': ' . $e->getMessage());
        }
    }

    /**
     * Deliver a signed payload to a single subscriber and record the outcome.
     */
    private static function deliver(array $subscriber, $payload, $event) {
        $signature = hash_hmac('sha256', $payload, $subscriber['secret']);

        $ch = curl_init($subscriber['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Signature: ' . $signature,
            'X-Webhook-Event: ' . $event,
        ]);

        curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        $db  = getDbConnection();
        $now = date('Y-m-d H:i:s');

        if ($httpCode >= 200 && $httpCode < 300) {
            $stmt = $db->prepare("
                UPDATE webhook_subscriptions
                SET last_triggered_at = ?, last_success_at = ?, failure_count = 0
                WHERE id = ?
            ");
            $stmt->execute([$now, $now, $subscriber['id']]);
        } else {
            $stmt = $db->prepare("
                UPDATE webhook_subscriptions
                SET last_triggered_at = ?, last_failure_at = ?, failure_count = failure_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$now, $now, $subscriber['id']]);
            error_log('Webhook delivery failed to ' . $subscriber['url'] . ': HTTP ' . $httpCode . ($curlError ? ' — ' . $curlError : ''));
        }
    }
}
