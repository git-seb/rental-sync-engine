<?php
/**
 * Sync Manager Class
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

use RentalSyncEngine\PMS\PMSFactory;
use RentalSyncEngine\WooCommerce\OrderManager;

/**
 * Sync Manager Class
 * Coordinates synchronization between PMS platforms and WooCommerce
 */
class SyncManager {
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Database Manager instance
     *
     * @var DatabaseManager
     */
    private $database_manager;

    /**
     * Order Manager instance
     *
     * @var OrderManager
     */
    private $order_manager;

    /**
     * Constructor
     *
     * @param Logger          $logger Logger instance
     * @param DatabaseManager $database_manager Database Manager instance
     * @param OrderManager    $order_manager Order Manager instance
     */
    public function __construct($logger, $database_manager, $order_manager) {
        $this->logger = $logger;
        $this->database_manager = $database_manager;
        $this->order_manager = $order_manager;
    }

    /**
     * Sync all listings from all enabled PMS platforms
     *
     * @return array Sync results
     */
    public function sync_all_listings() {
        $this->logger->info('Starting sync for all listings', 'sync');

        $results = array(
            'success' => 0,
            'failed' => 0,
            'platforms' => array(),
        );

        $credentials = get_option('rental_sync_engine_pms_credentials', array());

        foreach ($credentials as $platform => $config) {
            if (empty($config['enabled'])) {
                continue;
            }

            try {
                $handler = PMSFactory::create($platform, $config);
                $platform_result = $this->sync_listings_for_platform($handler, $platform);
                
                $results['success'] += $platform_result['success'];
                $results['failed'] += $platform_result['failed'];
                $results['platforms'][$platform] = $platform_result;
                
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Failed to sync listings for %s: %s', $platform, $e->getMessage()),
                    'sync',
                    array('platform' => $platform, 'error' => $e->getMessage())
                );
                $results['failed']++;
                $results['platforms'][$platform] = array(
                    'success' => 0,
                    'failed' => 1,
                    'error' => $e->getMessage(),
                );
            }
        }

        $this->logger->info(
            sprintf('Completed sync for all listings: %d success, %d failed', $results['success'], $results['failed']),
            'sync'
        );

        return $results;
    }

    /**
     * Sync listings for a specific platform
     *
     * @param object $handler PMS handler instance
     * @param string $platform Platform name
     * @return array Sync results
     */
    private function sync_listings_for_platform($handler, $platform) {
        $result = array('success' => 0, 'failed' => 0);

        try {
            $listings = $handler->get_listings();

            foreach ($listings as $listing) {
                try {
                    // Save listing to database
                    $listing_id = $this->database_manager->save_listing(array(
                        'pms_platform' => $platform,
                        'pms_listing_id' => $listing['id'],
                        'listing_data' => $listing,
                        'sync_status' => 'synced',
                    ));

                    // Create or update WooCommerce product
                    $product_id = $this->order_manager->create_or_update_product($listing, $platform);

                    // Update listing with WooCommerce product ID
                    if ($product_id) {
                        $this->database_manager->save_listing(array(
                            'pms_platform' => $platform,
                            'pms_listing_id' => $listing['id'],
                            'wc_product_id' => $product_id,
                            'listing_data' => $listing,
                            'sync_status' => 'synced',
                        ));
                    }

                    $result['success']++;
                    
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf('Failed to sync listing %s: %s', $listing['id'], $e->getMessage()),
                        'sync',
                        array('platform' => $platform, 'listing_id' => $listing['id'])
                    );
                    $result['failed']++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to get listings from %s: %s', $platform, $e->getMessage()),
                'sync',
                array('platform' => $platform)
            );
            throw $e;
        }

        return $result;
    }

    /**
     * Sync availability for all listings
     *
     * @return array Sync results
     */
    public function sync_all_availability() {
        $this->logger->info('Starting sync for all availability', 'sync');

        $results = array(
            'success' => 0,
            'failed' => 0,
        );

        $credentials = get_option('rental_sync_engine_pms_credentials', array());

        foreach ($credentials as $platform => $config) {
            if (empty($config['enabled'])) {
                continue;
            }

            try {
                $handler = PMSFactory::create($platform, $config);
                $listings = $this->database_manager->get_listings(array('pms_platform' => $platform));

                foreach ($listings as $listing) {
                    try {
                        $availability = $handler->get_availability($listing['pms_listing_id']);
                        
                        // Update product availability/stock in WooCommerce
                        if ($listing['wc_product_id']) {
                            $this->order_manager->update_product_availability(
                                $listing['wc_product_id'],
                                $availability
                            );
                            $results['success']++;
                        }
                        
                    } catch (\Exception $e) {
                        $this->logger->error(
                            sprintf('Failed to sync availability for listing %s: %s', $listing['pms_listing_id'], $e->getMessage()),
                            'sync'
                        );
                        $results['failed']++;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Failed to sync availability for %s: %s', $platform, $e->getMessage()),
                    'sync'
                );
            }
        }

        return $results;
    }

    /**
     * Sync bookings from all PMS platforms
     *
     * @return array Sync results
     */
    public function sync_all_bookings() {
        $this->logger->info('Starting sync for all bookings', 'sync');

        $results = array(
            'success' => 0,
            'failed' => 0,
        );

        $credentials = get_option('rental_sync_engine_pms_credentials', array());

        foreach ($credentials as $platform => $config) {
            if (empty($config['enabled'])) {
                continue;
            }

            try {
                $handler = PMSFactory::create($platform, $config);
                $bookings = $handler->get_bookings();

                foreach ($bookings as $booking) {
                    try {
                        // Save booking to database
                        $booking_id = $this->database_manager->save_booking(array(
                            'pms_platform' => $platform,
                            'pms_booking_id' => $booking['id'],
                            'pms_listing_id' => $booking['listing_id'],
                            'booking_data' => $booking,
                            'booking_status' => $booking['status'],
                            'check_in_date' => $booking['check_in'],
                            'check_out_date' => $booking['check_out'],
                            'guest_name' => $booking['guest_name'] ?? '',
                            'guest_email' => $booking['guest_email'] ?? '',
                            'total_amount' => $booking['total_amount'] ?? 0,
                        ));

                        // Create or update WooCommerce order
                        $order_id = $this->order_manager->create_or_update_order($booking, $platform);

                        // Update booking with WooCommerce order ID
                        if ($order_id) {
                            $this->database_manager->save_booking(array(
                                'pms_platform' => $platform,
                                'pms_booking_id' => $booking['id'],
                                'pms_listing_id' => $booking['listing_id'],
                                'wc_order_id' => $order_id,
                                'booking_data' => $booking,
                                'booking_status' => $booking['status'],
                                'check_in_date' => $booking['check_in'],
                                'check_out_date' => $booking['check_out'],
                                'guest_name' => $booking['guest_name'] ?? '',
                                'guest_email' => $booking['guest_email'] ?? '',
                                'total_amount' => $booking['total_amount'] ?? 0,
                            ));
                        }

                        $results['success']++;
                        
                    } catch (\Exception $e) {
                        $this->logger->error(
                            sprintf('Failed to sync booking %s: %s', $booking['id'], $e->getMessage()),
                            'sync'
                        );
                        $results['failed']++;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Failed to sync bookings for %s: %s', $platform, $e->getMessage()),
                    'sync'
                );
            }
        }

        return $results;
    }

    /**
     * Push a booking to PMS
     *
     * @param int    $order_id WooCommerce order ID
     * @param string $platform Target platform
     * @return bool Success status
     */
    public function push_booking_to_pms($order_id, $platform) {
        try {
            $credentials = get_option('rental_sync_engine_pms_credentials', array());
            
            if (empty($credentials[$platform])) {
                throw new \Exception(sprintf('Platform %s not configured', $platform));
            }

            $handler = PMSFactory::create($platform, $credentials[$platform]);
            $order = wc_get_order($order_id);

            if (!$order) {
                throw new \Exception(sprintf('Order %d not found', $order_id));
            }

            $booking_data = $this->order_manager->prepare_booking_data_from_order($order);
            $pms_booking = $handler->create_booking($booking_data);

            // Save booking to database
            $this->database_manager->save_booking(array(
                'pms_platform' => $platform,
                'pms_booking_id' => $pms_booking['id'],
                'pms_listing_id' => $booking_data['listing_id'],
                'wc_order_id' => $order_id,
                'booking_data' => $pms_booking,
                'booking_status' => $pms_booking['status'],
                'check_in_date' => $booking_data['check_in'],
                'check_out_date' => $booking_data['check_out'],
                'guest_name' => $booking_data['guest_name'],
                'guest_email' => $booking_data['guest_email'],
                'total_amount' => $booking_data['total_amount'],
            ));

            $this->logger->info(
                sprintf('Successfully pushed booking to %s', $platform),
                'sync',
                array('order_id' => $order_id, 'platform' => $platform)
            );

            return true;
            
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to push booking to %s: %s', $platform, $e->getMessage()),
                'sync',
                array('order_id' => $order_id, 'platform' => $platform)
            );
            return false;
        }
    }
}
