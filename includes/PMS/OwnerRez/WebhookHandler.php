<?php
/**
 * OwnerRez Webhook Handler
 *
 * @package RentalSyncEngine\PMS\OwnerRez
 */

namespace RentalSyncEngine\PMS\OwnerRez;

use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Core\Settings;

/**
 * Class WebhookHandler
 */
class WebhookHandler {
    
    public function handle($data) {
        $webhook_secret = Settings::get('rental_sync_engine_or_webhook_secret', '');
        
        if (!empty($webhook_secret)) {
            if (!$this->verify_signature($webhook_secret)) {
                Logger::error('or', 'webhook', 'Invalid webhook signature');
                return array('error' => 'Invalid signature');
            }
        }
        
        try {
            $event_type = $data['eventType'] ?? '';
            
            switch ($event_type) {
                case 'booking.created':
                case 'booking.modified':
                    $handler = Handler::get_instance();
                    $booking_id = $data['bookingId'] ?? '';
                    $handler->pull_booking($booking_id);
                    break;
                    
                case 'property.modified':
                    $handler = Handler::get_instance();
                    $handler->sync_properties();
                    break;
                    
                case 'availability.changed':
                    $handler = Handler::get_instance();
                    $handler->sync_availability();
                    break;
            }
            
            Logger::success('or', 'webhook', 'Webhook processed', $data);
            return array('success' => true);
        } catch (\Exception $e) {
            Logger::error('or', 'webhook', $e->getMessage(), $data);
            return array('error' => $e->getMessage());
        }
    }
    
    private function verify_signature($secret) {
        $signature = $_SERVER['HTTP_X_OWNERREZ_SIGNATURE'] ?? '';
        $body = file_get_contents('php://input');
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
