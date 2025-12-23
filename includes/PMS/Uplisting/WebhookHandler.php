<?php
namespace RentalSyncEngine\PMS\Uplisting;

use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Core\Settings;

class WebhookHandler {
    public function handle($data) {
        $webhook_secret = Settings::get('rental_sync_engine_ul_webhook_secret', '');
        
        if (!empty($webhook_secret) && !$this->verify_signature($webhook_secret)) {
            Logger::error('ul', 'webhook', 'Invalid webhook signature');
            return array('error' => 'Invalid signature');
        }
        
        try {
            $event_type = $data['event'] ?? '';
            
            switch ($event_type) {
                case 'booking.created':
                case 'booking.updated':
                    $handler = Handler::get_instance();
                    $booking_id = $data['booking_id'] ?? '';
                    $handler->pull_booking($booking_id);
                    break;
                case 'property.updated':
                    Handler::get_instance()->sync_properties();
                    break;
                case 'calendar.updated':
                    Handler::get_instance()->sync_availability();
                    break;
            }
            
            Logger::success('ul', 'webhook', 'Webhook processed', $data);
            return array('success' => true);
        } catch (\Exception $e) {
            Logger::error('ul', 'webhook', $e->getMessage(), $data);
            return array('error' => $e->getMessage());
        }
    }
    
    private function verify_signature($secret) {
        $signature = $_SERVER['HTTP_X_UPLISTING_SIGNATURE'] ?? '';
        $body = file_get_contents('php://input');
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
