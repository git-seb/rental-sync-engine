# Rental Sync Engine

A robust WordPress plugin for synchronizing rental properties with multiple Property Management Systems (PMS) including Rentals United, OwnerRez, Uplisting, and Hostaway. Features two-way synchronization, webhook support, and seamless WooCommerce integration.

## Features

- **Multi-PMS Support**: Integrate with Rentals United, OwnerRez, Uplisting, and Hostaway
- **Two-Way Synchronization**: 
  - Properties: Sync property listings and details
  - Availability: Real-time calendar synchronization
  - Bookings: Bidirectional booking management
- **WooCommerce Integration**: 
  - Automatic conversion of PMS bookings to WooCommerce orders
  - Property metadata and guest information management
  - Order status synchronization
- **Webhook Support**: Real-time updates for supported PMS platforms
- **Scheduled Syncs**: Automatic hourly/daily synchronization via WordPress cron
- **Admin Interface**:
  - Dashboard with sync statistics
  - Settings page for API credentials
  - Sync logs viewer with filtering
  - Manual sync triggers
- **PSR-4 Architecture**: Modular, maintainable code structure
- **Error Handling**: Comprehensive logging and retry mechanisms
- **Rate Limiting**: Built-in API rate limit management

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Composer (for dependency management)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/git-seb/rental-sync-engine.git
cd rental-sync-engine
```

2. Install dependencies:
```bash
composer install
```

3. Upload the plugin folder to your WordPress `/wp-content/plugins/` directory

4. Activate the plugin through the 'Plugins' menu in WordPress

5. Navigate to **Rental Sync > Settings** to configure your PMS API credentials

## Configuration

### Rentals United
1. Go to **Rental Sync > Settings > Rentals United**
2. Enable the integration
3. Enter your API username and password
4. Configure webhook secret (optional)
5. Add webhook URL to Rentals United dashboard: `https://yoursite.com/rental-sync-webhook/rentals-united`

### OwnerRez
1. Go to **Rental Sync > Settings > OwnerRez**
2. Enable the integration
3. Enter your API token from OwnerRez dashboard
4. Configure webhook secret (optional)
5. Add webhook URL: `https://yoursite.com/rental-sync-webhook/ownerrez`

### Uplisting
1. Go to **Rental Sync > Settings > Uplisting**
2. Enable the integration
3. Enter your API key
4. Configure webhook secret (optional)
5. Add webhook URL: `https://yoursite.com/rental-sync-webhook/uplisting`

### Hostaway
1. Go to **Rental Sync > Settings > Hostaway**
2. Enable the integration
3. Enter your Client ID and Client Secret
4. Configure webhook secret (optional)
5. Add webhook URL: `https://yoursite.com/rental-sync-webhook/hostaway`

## Usage

### Automatic Synchronization
The plugin automatically syncs data based on your configured sync frequency (hourly/daily). All enabled PMS providers are synchronized during each scheduled run.

### Manual Synchronization
1. Navigate to **Rental Sync > Manual Sync**
2. Select the provider and sync type (All, Properties, Availability, or Bookings)
3. Click the sync button
4. Monitor the status and results in real-time

### Viewing Sync Logs
1. Go to **Rental Sync > Sync Logs**
2. Filter by provider, status, or date
3. Review detailed sync activity and error messages

## Architecture

### Directory Structure
```
rental-sync-engine/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── Core/
│   │   ├── ApiClient.php
│   │   ├── Logger.php
│   │   ├── PMSHandlerInterface.php
│   │   ├── Settings.php
│   │   ├── SyncScheduler.php
│   │   └── WebhookRouter.php
│   ├── Integration/
│   │   └── WooCommerceIntegration.php
│   └── PMS/
│       ├── Hostaway/
│       ├── OwnerRez/
│       ├── RentalsUnited/
│       └── Uplisting/
├── templates/
│   └── admin/
├── composer.json
└── rental-sync-engine.php
```

### PSR-4 Autoloading
All classes follow PSR-4 autoloading standards with the `RentalSyncEngine` namespace.

### Hooks and Filters

#### Actions
- `rental_sync_engine_push_booking` - Triggered when a booking needs to be pushed to PMS
- `rental_sync_engine_cancel_booking` - Triggered when a booking is cancelled
- `rental_sync_engine_hourly_sync` - Scheduled sync action

#### Filters
- `rental_sync_engine_property_data` - Filter property data before saving
- `rental_sync_engine_booking_data` - Filter booking data before creating order

## Development

### Running Tests
```bash
composer test
```

### Code Standards
```bash
composer phpcs
```

### Adding a New PMS Provider
1. Create a new directory under `includes/PMS/YourProvider/`
2. Implement `Client.php` extending `Core\ApiClient`
3. Implement `Handler.php` implementing `Core\PMSHandlerInterface`
4. Implement `WebhookHandler.php` for webhook support
5. Register the provider in the main plugin class

## Database Tables

### rental_sync_logs
Stores all synchronization activity logs.

### rental_sync_property_mappings
Maps PMS property IDs to WooCommerce product IDs.

### rental_sync_booking_mappings
Maps PMS booking IDs to WooCommerce order IDs.

## API Documentation References

- **Rentals United**: https://developer.rentalsunited.com
- **OwnerRez**: https://api.ownerrez.com/help/v2
- **Uplisting**: https://support.uplisting.io/docs/api
- **Hostaway**: https://api.hostaway.com/documentation

## Support

For issues, questions, or contributions, please visit:
https://github.com/git-seb/rental-sync-engine

## License

GPL v2 or later

## Credits

Developed for seamless rental property management across multiple platforms.