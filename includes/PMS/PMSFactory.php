<?php
/**
 * PMS Factory
 *
 * @package RentalSyncEngine\PMS
 */

namespace RentalSyncEngine\PMS;

use RentalSyncEngine\PMS\Handlers\RentalsUnitedHandler;
use RentalSyncEngine\PMS\Handlers\HostawayHandler;
use RentalSyncEngine\PMS\Handlers\HostifyHandler;
use RentalSyncEngine\PMS\Handlers\UplistingHandler;
use RentalSyncEngine\PMS\Handlers\NextPaxHandler;
use RentalSyncEngine\PMS\Handlers\OwnerRezHandler;

/**
 * PMS Factory Class
 * Creates PMS handler instances based on platform name
 */
class PMSFactory {
    /**
     * Supported platforms
     *
     * @var array
     */
    private static $platforms = array(
        'rentals_united' => RentalsUnitedHandler::class,
        'hostaway' => HostawayHandler::class,
        'hostify' => HostifyHandler::class,
        'uplisting' => UplistingHandler::class,
        'nextpax' => NextPaxHandler::class,
        'ownerrez' => OwnerRezHandler::class,
    );

    /**
     * Create a PMS handler instance
     *
     * @param string $platform Platform name
     * @param array  $credentials Platform credentials
     * @return AbstractPMSHandler
     * @throws \Exception If platform is not supported
     */
    public static function create($platform, $credentials) {
        if (!isset(self::$platforms[$platform])) {
            throw new \Exception(sprintf('Platform %s is not supported', $platform));
        }

        $class = self::$platforms[$platform];
        return new $class($credentials);
    }

    /**
     * Get list of supported platforms
     *
     * @return array
     */
    public static function get_supported_platforms() {
        return array_keys(self::$platforms);
    }

    /**
     * Get platform display names
     *
     * @return array
     */
    public static function get_platform_names() {
        return array(
            'rentals_united' => 'Rentals United',
            'hostaway' => 'Hostaway',
            'hostify' => 'Hostify',
            'uplisting' => 'Uplisting',
            'nextpax' => 'NextPax',
            'ownerrez' => 'OwnerRez',
        );
    }
}
