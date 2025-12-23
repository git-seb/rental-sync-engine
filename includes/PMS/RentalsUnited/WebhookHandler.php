<?php
/**
 * Rentals United Webhook Handler
 *
 * @package RentalSyncEngine\PMS\RentalsUnited
 */

namespace RentalSyncEngine\PMS\RentalsUnited;

use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Core\Settings;

/**
 * Class WebhookHandler
 */
class WebhookHandler {
    
    /**
     * Handle webhook
     *
     * @param array $data Webhook data
     * @return array Response
     */
    public function handle($data) {
        // Verify webhook signature if secret is configured
        $webhook_secret = Settings::get('rental_sync_engine_ru_webhook_secret', '');
        
        if (!empty($webhook_secret)) {
            if (!$this->verify_signature($webhook_secret)) {
                Logger::error('ru', 'webhook', 'Invalid webhook signature');
                return array('error' => 'Invalid signature');
            }
        }
        
        // Process webhook event
        try {
            $event_type = $data['event_type'] ?? '';
            
            switch ($event_type) {
                case 'reservation_created':
                case 'reservation_updated':
                    $handler = Handler::get_instance();
                    $booking_id = $data['reservation_id'] ?? '';
                    $handler->pull_booking($booking_id);
                    break;
                    
                case 'property_updated':
                    $handler = Handler::get_instance();
                    $handler->sync_properties();
                    break;
                    
                case 'availability_updated':
                    $handler = Handler::get_instance();
                    $handler->sync_availability();
                    break;
            }
            
            Logger::success('ru', 'webhook', 'Webhook processed', $data);
            return array('success' => true);
        } catch (\Exception $e) {
            Logger::error('ru', 'webhook', $e->getMessage(), $data);
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $secret Webhook secret
     * @return bool True if valid, false otherwise
     */
    private function verify_signature($secret) {
        $signature = $_SERVER['HTTP_X_RU_SIGNATURE'] ?? '';
        $body = file_get_contents('php://input');
        $expected = hash_hmac('sha256', $body, $secret);
        
        return hash_equals($expected, $signature);
    }
}
