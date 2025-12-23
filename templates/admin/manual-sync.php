<?php
/**
 * Admin Manual Sync Template
 *
 * @package RentalSyncEngine
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap rental-sync-engine-manual-sync">
    <h1><?php _e('Manual Sync', 'rental-sync-engine'); ?></h1>
    
    <p><?php _e('Use this page to manually trigger synchronization with your PMS providers.', 'rental-sync-engine'); ?></p>
    
    <div class="sync-providers">
        <div class="provider-sync-card">
            <h3><?php _e('Rentals United', 'rental-sync-engine'); ?></h3>
            <div class="sync-buttons">
                <button class="button button-primary sync-trigger" data-provider="ru" data-type="all">
                    <?php _e('Sync All', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ru" data-type="properties">
                    <?php _e('Sync Properties', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ru" data-type="availability">
                    <?php _e('Sync Availability', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ru" data-type="bookings">
                    <?php _e('Sync Bookings', 'rental-sync-engine'); ?>
                </button>
            </div>
            <div class="sync-status" id="sync-status-ru"></div>
        </div>
        
        <div class="provider-sync-card">
            <h3><?php _e('OwnerRez', 'rental-sync-engine'); ?></h3>
            <div class="sync-buttons">
                <button class="button button-primary sync-trigger" data-provider="or" data-type="all">
                    <?php _e('Sync All', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="or" data-type="properties">
                    <?php _e('Sync Properties', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="or" data-type="availability">
                    <?php _e('Sync Availability', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="or" data-type="bookings">
                    <?php _e('Sync Bookings', 'rental-sync-engine'); ?>
                </button>
            </div>
            <div class="sync-status" id="sync-status-or"></div>
        </div>
        
        <div class="provider-sync-card">
            <h3><?php _e('Uplisting', 'rental-sync-engine'); ?></h3>
            <div class="sync-buttons">
                <button class="button button-primary sync-trigger" data-provider="ul" data-type="all">
                    <?php _e('Sync All', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ul" data-type="properties">
                    <?php _e('Sync Properties', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ul" data-type="availability">
                    <?php _e('Sync Availability', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ul" data-type="bookings">
                    <?php _e('Sync Bookings', 'rental-sync-engine'); ?>
                </button>
            </div>
            <div class="sync-status" id="sync-status-ul"></div>
        </div>
        
        <div class="provider-sync-card">
            <h3><?php _e('Hostaway', 'rental-sync-engine'); ?></h3>
            <div class="sync-buttons">
                <button class="button button-primary sync-trigger" data-provider="ha" data-type="all">
                    <?php _e('Sync All', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ha" data-type="properties">
                    <?php _e('Sync Properties', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ha" data-type="availability">
                    <?php _e('Sync Availability', 'rental-sync-engine'); ?>
                </button>
                <button class="button sync-trigger" data-provider="ha" data-type="bookings">
                    <?php _e('Sync Bookings', 'rental-sync-engine'); ?>
                </button>
            </div>
            <div class="sync-status" id="sync-status-ha"></div>
        </div>
    </div>
</div>
