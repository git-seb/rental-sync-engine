# Rental Sync Engine

A production-ready WordPress plugin for syncing WooCommerce with multiple Property Management Systems (PMS) supporting two-way booking synchronization.

## Features

### Core Functionality
- **Multi-Platform Support**: Integrates with Rentals United, Hostaway, Hostify, Uplisting, NextPax, and OwnerRez
- **Two-Way Synchronization**: Bidirectional sync for bookings between PMS and WooCommerce
- **Real-Time & Scheduled Syncing**:
  - Listings (properties)
  - Availability (booking calendars)
  - Bookings (reservations and cancellations)
- **Webhook Support**: Real-time updates via webhooks from all supported PMS platforms

### WooCommerce Integration
- Automatic conversion of bookings into WooCommerce orders
- Integration with WooCommerce payment gateways (Stripe, PayPal, etc.)
- Support for WooCommerce multi-vendor plugins (Dokan, WCFM)
- Product creation and management for rental properties

### Architecture
- **Modular Design**: Each PMS platform has its own dedicated handler
- **Extensible**: Easy to add support for additional platforms
- **PSR-4 Autoloading**: Modern PHP standards
- **Dependency Injection**: Clean, testable code structure

### Admin Features
- Comprehensive WordPress admin dashboard
- Easy-to-use settings interface for managing:
  - API authentication credentials for each platform
  - Sync frequency configuration
  - Manual sync triggers
- Real-time sync status monitoring
- Detailed logging system with multiple log levels
- Statistics dashboard showing sync metrics

### Error Handling & Logging
- Robust error handling throughout
- Multi-level logging (debug, info, warning, error)
- Admin interface for viewing and managing logs
- Automatic cleanup of old log entries

## Requirements

- WordPress 6.0 or higher
- WooCommerce 7.0 or higher
- PHP 8.0 or higher
- MySQL 5.6 or higher

## Installation

### Via WordPress Admin

1. Download the plugin ZIP file
2. Navigate to **Plugins > Add New** in WordPress admin
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Upload the `rental-sync-engine` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Rental Sync > Settings** to configure

### Via Composer

```bash
composer require git-seb/rental-sync-engine
```

## Configuration

### 1. Basic Settings

Navigate to **Rental Sync > Settings** to configure:

- **Sync Frequency**: Choose how often to run scheduled syncs (15 minutes to daily)
- **Enable Real-Time Sync**: Sync immediately when orders are created
- **Enable Webhooks**: Allow PMS platforms to push real-time updates
- **Log Level**: Set logging verbosity (debug, info, warning, error)

### 2. PMS Platform Configuration

Navigate to **Rental Sync > PMS Platforms** to set up your integrations:

#### Rentals United
- Username
- Password

#### Hostaway
- API Key
- API Secret
- Webhook Secret

#### Hostify
- API Key
- Webhook Secret

#### Uplisting
- API Token
- Webhook Secret

#### NextPax
- API Key
- Webhook Secret

#### OwnerRez
- Username
- API Token
- Webhook Secret

### 3. Webhook Configuration

Each PMS platform needs to be configured to send webhooks to:

```
https://your-site.com/wp-json/rental-sync-engine/v1/webhook/{platform}
```

Replace `{platform}` with: `rentals_united`, `hostaway`, `hostify`, `uplisting`, `nextpax`, or `ownerrez`

## Usage

### Automatic Synchronization

Once configured, the plugin automatically:

1. **Syncs Listings**: Fetches properties from PMS and creates WooCommerce products
2. **Syncs Availability**: Updates product availability based on booking calendars
3. **Syncs Bookings**: Creates WooCommerce orders from PMS bookings
4. **Pushes Bookings**: Sends new WooCommerce orders to PMS platforms

### Manual Synchronization

Use the **Rental Sync > Dashboard** to trigger manual syncs:

- **Sync Listings**: Fetch and update all property listings
- **Sync Availability**: Update availability for all properties
- **Sync Bookings**: Sync all bookings from PMS platforms

### Monitoring

The dashboard provides:

- **Sync Status**: View last sync time and next scheduled sync
- **Statistics**: Total listings, bookings, and active reservations
- **Logs**: Detailed sync logs with filtering options

## Developer Guide

### Adding a New PMS Platform

1. Create a new handler in `includes/PMS/Handlers/`:

```php
<?php
namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

class YourPMSHandler extends AbstractPMSHandler {
    // Implement required methods
}
```

2. Register the platform in `PMSFactory.php`
3. Add credential fields in `Admin.php`

### Hooks & Filters

```php
// Trigger when a booking should be pushed to PMS
do_action('rental_sync_engine_push_booking', $order_id, $platform);

// Trigger when a booking should be cancelled
do_action('rental_sync_engine_cancel_booking', $booking_id, $platform);
```

### Custom Sync Logic

```php
// Get sync manager instance
$plugin = \RentalSyncEngine\Plugin::get_instance();
$sync_manager = $plugin->get_sync_manager();

// Trigger custom sync
$results = $sync_manager->sync_all_listings();
```

## Database Tables

The plugin creates three custom tables:

- `wp_rental_sync_logs`: Stores sync logs and errors
- `wp_rental_sync_listings`: Maps PMS listings to WooCommerce products
- `wp_rental_sync_bookings`: Maps PMS bookings to WooCommerce orders

## Troubleshooting

### Syncs Not Running

1. Check cron jobs are enabled: `wp cron event list`
2. Verify plugin is activated
3. Check sync frequency settings
4. Review logs for errors

### Authentication Errors

1. Verify API credentials are correct
2. Check API endpoint URLs are accessible
3. Ensure webhook secrets match PMS configuration
4. Review PMS platform documentation

### Webhook Issues

1. Verify webhook URL is publicly accessible
2. Check webhook secret configuration
3. Review webhook logs in PMS dashboard
4. Test webhook signature verification

## Security

- All API credentials are stored securely in WordPress options
- Webhook signatures are verified before processing
- Input sanitization and validation throughout
- SQL injection prevention via prepared statements
- XSS protection on all outputs

## Uninstallation

When the plugin is uninstalled (not just deactivated), it will:

1. Remove all plugin options and settings
2. Clear all transient data
3. Drop custom database tables
4. Clear scheduled cron jobs

## Support

For issues, questions, or feature requests:

- GitHub Issues: https://github.com/git-seb/rental-sync-engine/issues
- Documentation: https://github.com/git-seb/rental-sync-engine/wiki

## Changelog

### Version 1.0.0
- Initial release
- Support for 6 PMS platforms
- Two-way booking synchronization
- WooCommerce integration
- Webhook support
- Admin dashboard
- Comprehensive logging

## License

GPL-2.0-or-later

## Credits

Developed by git-seb