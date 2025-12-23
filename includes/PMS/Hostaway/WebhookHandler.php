<?php
namespace RentalSyncEngine\PMS\Hostaway;

use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Core\Settings;

class WebhookHandler {
    public function handle($data) {
        $webhook_secret = Settings::get('rental_sync_engine_ha_webhook_secret', '');
        
        if (!empty($webhook_secret) && !$this->verify_signature($webhook_secret)) {
            Logger::error('ha', 'webhook', 'Invalid webhook signature');
            return array('error' => 'Invalid signature');
        }
        
        try {
            $event_type = $data['type'] ?? '';
            
            switch ($event_type) {
                case 'reservation.created':
                case 'reservation.updated':
                    $handler = Handler::get_instance();
                    $reservation_id = $data['objectId'] ?? '';
                    $handler->pull_booking($reservation_id);
                    break;
                case 'listing.updated':
                    Handler::get_instance()->sync_properties();
                    break;
                case 'calendar.updated':
                    Handler::get_instance()->sync_availability();
                    break;
            }
            
            Logger::success('ha', 'webhook', 'Webhook processed', $data);
            return array('success' => true);
        } catch (\Exception $e) {
            Logger::error('ha', 'webhook', $e->getMessage(), $data);
            return array('error' => $e->getMessage());
        }
    }
    
    private function verify_signature($secret) {
        $signature = $_SERVER['HTTP_X_HOSTAWAY_SIGNATURE'] ?? '';
        $body = file_get_contents('php://input');
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
