<?php
/**
 * Webhook Manager
 *
 * @package RentalSyncEngine\Webhooks
 */

namespace RentalSyncEngine\Webhooks;

use RentalSyncEngine\Core\SyncManager;
use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\PMS\PMSFactory;

/**
 * Webhook Manager Class
 * Handles incoming webhooks from PMS platforms
 */
class WebhookManager {
    /**
     * Sync Manager instance
     *
     * @var SyncManager
     */
    private $sync_manager;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param SyncManager $sync_manager Sync Manager instance
     * @param Logger      $logger Logger instance
     */
    public function __construct($sync_manager, $logger) {
        $this->sync_manager = $sync_manager;
        $this->logger = $logger;
    }

    /**
     * Register webhook REST API routes
     */
    public function register_routes() {
        register_rest_route('rental-sync-engine/v1', '/webhook/(?P<platform>[a-zA-Z_]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Will verify signature in handler
        ));
    }

    /**
     * Handle incoming webhook
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function handle_webhook($request) {
        $platform = $request->get_param('platform');
        $payload = $request->get_body();
        $headers = $request->get_headers();

        try {
            $this->logger->info(
                sprintf('Received webhook from %s', $platform),
                'webhook',
                array('platform' => $platform)
            );

            // Get platform credentials
            $credentials = get_option('rental_sync_engine_pms_credentials', array());
            
            if (empty($credentials[$platform])) {
                throw new \Exception(sprintf('Platform %s not configured', $platform));
            }

            // Verify webhook signature
            $handler = PMSFactory::create($platform, $credentials[$platform]);
            $signature = $this->get_signature_from_headers($headers, $platform);

            if (!$handler->verify_webhook_signature($payload, $signature)) {
                throw new \Exception('Invalid webhook signature');
            }

            // Parse webhook data
            $data = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON payload');
            }

            // Determine webhook type and process
            $webhook_type = $this->determine_webhook_type($data, $platform);

            switch ($webhook_type) {
                case 'booking_created':
                case 'booking_updated':
                    $this->handle_booking_webhook($data, $platform, $handler);
                    break;

                case 'booking_cancelled':
                    $this->handle_booking_cancellation($data, $platform);
                    break;

                case 'listing_updated':
                    $this->handle_listing_webhook($data, $platform, $handler);
                    break;

                case 'availability_updated':
                    $this->handle_availability_webhook($data, $platform, $handler);
                    break;

                default:
                    throw new \Exception(sprintf('Unknown webhook type: %s', $webhook_type));
            }

            return new \WP_REST_Response(array(
                'success' => true,
                'message' => 'Webhook processed successfully',
            ), 200);

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Webhook error for %s: %s', $platform, $e->getMessage()),
                'webhook',
                array('platform' => $platform, 'error' => $e->getMessage())
            );

            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $e->getMessage(),
            ), 400);
        }
    }

    /**
     * Get signature from request headers
     *
     * @param array  $headers Request headers
     * @param string $platform Platform name
     * @return string Signature
     */
    private function get_signature_from_headers($headers, $platform) {
        // Different platforms use different header names
        $signature_headers = array(
            'x_signature',
            'x_webhook_signature',
            'x_hostaway_signature',
            'x_hub_signature',
        );

        foreach ($signature_headers as $header) {
            if (isset($headers[$header])) {
                return is_array($headers[$header]) ? $headers[$header][0] : $headers[$header];
            }
        }

        return '';
    }

    /**
     * Determine webhook type from data
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @return string Webhook type
     */
    private function determine_webhook_type($data, $platform) {
        // Each platform may have different webhook structures
        if (isset($data['event'])) {
            return $data['event'];
        }

        if (isset($data['type'])) {
            return $data['type'];
        }

        if (isset($data['action'])) {
            return $data['action'];
        }

        // Try to infer from data structure
        if (isset($data['reservation']) || isset($data['booking'])) {
            return 'booking_updated';
        }

        if (isset($data['property']) || isset($data['listing'])) {
            return 'listing_updated';
        }

        return 'unknown';
    }

    /**
     * Handle booking webhook
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @param object $handler PMS handler instance
     */
    private function handle_booking_webhook($data, $platform, $handler) {
        // Extract booking data based on platform format
        $booking_data = $this->extract_booking_data($data, $platform);
        
        if (!$booking_data) {
            throw new \Exception('Could not extract booking data from webhook');
        }

        // Normalize booking data
        $normalized_booking = $handler->normalize_booking($booking_data);

        // Create or update WooCommerce order first
        $order_manager = new \RentalSyncEngine\WooCommerce\OrderManager($this->logger);
        $order_id = $order_manager->create_or_update_order($normalized_booking, $platform);

        // Save booking to database with order ID
        $database_manager = new \RentalSyncEngine\Core\DatabaseManager();
        $booking_id = $database_manager->save_booking(array(
            'pms_platform' => $platform,
            'pms_booking_id' => $normalized_booking['id'],
            'pms_listing_id' => $normalized_booking['listing_id'],
            'wc_order_id' => $order_id ?: null,
            'booking_data' => $normalized_booking,
            'booking_status' => $normalized_booking['status'],
            'check_in_date' => $normalized_booking['check_in'],
            'check_out_date' => $normalized_booking['check_out'],
            'guest_name' => $normalized_booking['guest_name'],
            'guest_email' => $normalized_booking['guest_email'],
            'total_amount' => $normalized_booking['total_amount'],
        ));

        $this->logger->info(
            sprintf('Processed booking webhook for booking %s', $normalized_booking['id']),
            'webhook',
            array('platform' => $platform, 'booking_id' => $normalized_booking['id'], 'order_id' => $order_id)
        );
    }

    /**
     * Handle booking cancellation webhook
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     */
    private function handle_booking_cancellation($data, $platform) {
        $booking_id = $this->extract_booking_id($data, $platform);
        
        if (!$booking_id) {
            throw new \Exception('Could not extract booking ID from webhook');
        }

        $database_manager = new \RentalSyncEngine\Core\DatabaseManager();
        $booking = $database_manager->get_booking_by_pms_id($platform, $booking_id);

        if ($booking && $booking['wc_order_id']) {
            $order = wc_get_order($booking['wc_order_id']);
            if ($order) {
                $order->set_status('cancelled');
                $order->save();
            }
        }

        // Update booking status
        $database_manager->save_booking(array(
            'pms_platform' => $platform,
            'pms_booking_id' => $booking_id,
            'pms_listing_id' => $booking['pms_listing_id'],
            'booking_status' => 'cancelled',
            'booking_data' => $booking['booking_data'],
            'check_in_date' => $booking['check_in_date'],
            'check_out_date' => $booking['check_out_date'],
            'guest_name' => $booking['guest_name'],
            'guest_email' => $booking['guest_email'],
            'total_amount' => $booking['total_amount'],
        ));
    }

    /**
     * Handle listing webhook
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @param object $handler PMS handler instance
     */
    private function handle_listing_webhook($data, $platform, $handler) {
        $listing_id = $this->extract_listing_id($data, $platform);
        
        if (!$listing_id) {
            throw new \Exception('Could not extract listing ID from webhook');
        }

        // Fetch latest listing data
        $listing = $handler->get_listing($listing_id);
        $normalized_listing = $handler->normalize_listing($listing);

        // Save to database and update product
        $database_manager = new \RentalSyncEngine\Core\DatabaseManager();
        $order_manager = new \RentalSyncEngine\WooCommerce\OrderManager($this->logger);

        $product_id = $order_manager->create_or_update_product($normalized_listing, $platform);

        if ($product_id) {
            $database_manager->save_listing(array(
                'pms_platform' => $platform,
                'pms_listing_id' => $normalized_listing['id'],
                'wc_product_id' => $product_id,
                'listing_data' => $normalized_listing,
                'sync_status' => 'synced',
            ));
        }
    }

    /**
     * Handle availability webhook
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @param object $handler PMS handler instance
     */
    private function handle_availability_webhook($data, $platform, $handler) {
        $listing_id = $this->extract_listing_id($data, $platform);
        
        if (!$listing_id) {
            throw new \Exception('Could not extract listing ID from webhook');
        }

        // Fetch latest availability
        $availability = $handler->get_availability($listing_id);

        // Update product availability
        $database_manager = new \RentalSyncEngine\Core\DatabaseManager();
        $listing = $database_manager->get_listing_by_pms_id($platform, $listing_id);

        if ($listing && $listing['wc_product_id']) {
            $order_manager = new \RentalSyncEngine\WooCommerce\OrderManager($this->logger);
            $order_manager->update_product_availability($listing['wc_product_id'], $availability);
        }
    }

    /**
     * Extract booking data from webhook payload
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @return array|null
     */
    private function extract_booking_data($data, $platform) {
        // Try common booking data locations
        if (isset($data['booking'])) {
            return $data['booking'];
        }
        if (isset($data['reservation'])) {
            return $data['reservation'];
        }
        if (isset($data['data'])) {
            return $data['data'];
        }
        return $data;
    }

    /**
     * Extract booking ID from webhook payload
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @return string|null
     */
    private function extract_booking_id($data, $platform) {
        $booking_data = $this->extract_booking_data($data, $platform);
        return $booking_data['id'] ?? $booking_data['booking_id'] ?? $booking_data['reservationId'] ?? null;
    }

    /**
     * Extract listing ID from webhook payload
     *
     * @param array  $data Webhook data
     * @param string $platform Platform name
     * @return string|null
     */
    private function extract_listing_id($data, $platform) {
        if (isset($data['property_id'])) {
            return $data['property_id'];
        }
        if (isset($data['listing_id'])) {
            return $data['listing_id'];
        }
        if (isset($data['propertyId'])) {
            return $data['propertyId'];
        }
        if (isset($data['data']['id'])) {
            return $data['data']['id'];
        }
        return null;
    }
}
