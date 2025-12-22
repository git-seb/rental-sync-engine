# Example Configuration for Rental Sync Engine

This file provides example configurations for each supported PMS platform.

## Rentals United

```php
// Configuration via WordPress Admin or programmatically:
$credentials = array(
    'rentals_united' => array(
        'enabled' => true,
        'username' => 'your_username',
        'password' => 'your_password',
    ),
);
update_option('rental_sync_engine_pms_credentials', $credentials);
```

**Webhook URL:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/rentals_united`

## Hostaway

```php
$credentials = array(
    'hostaway' => array(
        'enabled' => true,
        'api_key' => 'your_api_key',
        'api_secret' => 'your_api_secret',
        'webhook_secret' => 'your_webhook_secret',
    ),
);
update_option('rental_sync_engine_pms_credentials', $credentials);
```

**Webhook URL:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/hostaway`

**To get credentials:**
1. Log in to your Hostaway account
2. Go to Settings > API Keys
3. Create a new API application
4. Copy the Client ID (api_key) and Client Secret (api_secret)
5. Generate a webhook secret for signature verification

## Hostify

```php
$credentials = array(
    'hostify' => array(
        'enabled' => true,
        'api_key' => 'your_api_key',
        'webhook_secret' => 'your_webhook_secret',
    ),
);
update_option('rental_sync_engine_pms_credentials', $credentials);
```

**Webhook URL:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/hostify`

**To get credentials:**
1. Log in to Hostify dashboard
2. Navigate to Integrations > API
3. Generate a new API key
4. Save the webhook secret

## Uplisting

```php
$credentials = array(
    'uplisting' => array(
        'enabled' => true,
        'api_token' => 'your_api_token',
        'webhook_secret' => 'your_webhook_secret',
    ),
);
update_option('rental_sync_engine_pms_credentials', $credentials);
```

**Webhook URL:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/uplisting`

**To get credentials:**
1. Log in to Uplisting
2. Go to Settings > API
3. Generate a new API token
4. Configure webhook secret

## NextPax

```php
$credentials = array(
    'nextpax' => array(
        'enabled' => true,
        'api_key' => 'your_api_key',
        'webhook_secret' => 'your_webhook_secret',
    ),
);
update_option('rental_sync_engine_pms_credentials', $credentials);
```

**Webhook URL:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/nextpax`

## OwnerRez

```php
$credentials = array(
    'ownerrez' => array(
        'enabled' => true,
        'username' => 'your_username',
        'token' => 'your_api_token',
        'webhook_secret' => 'your_webhook_secret',
    ),
);
update_option('rental_sync_engine_pms_credentials', $credentials);
```

**Webhook URL:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/ownerrez`

**To get credentials:**
1. Log in to OwnerRez
2. Go to Settings > API Access
3. Create a new Personal Access Token
4. Note your username and token

## General Settings

```php
$settings = array(
    'sync_frequency' => 'hourly', // Options: fifteen_minutes, thirty_minutes, hourly, twicedaily, daily
    'enable_real_time_sync' => true,
    'enable_webhooks' => true,
    'log_level' => 'info', // Options: debug, info, warning, error
);
update_option('rental_sync_engine_settings', $settings);
```

## Programmatic Sync

```php
// Get plugin instance
$plugin = \RentalSyncEngine\Plugin::get_instance();
$sync_manager = $plugin->get_sync_manager();

// Sync all listings
$results = $sync_manager->sync_all_listings();

// Sync availability
$results = $sync_manager->sync_all_availability();

// Sync bookings
$results = $sync_manager->sync_all_bookings();

// Push a specific booking to PMS
$success = $sync_manager->push_booking_to_pms($order_id, 'hostaway');
```

## Webhook Configuration

### Setting up Webhooks in PMS Platforms

For each platform, configure the webhook URL in their dashboard:

1. **Webhook URL Format:** `https://your-site.com/wp-json/rental-sync-engine/v1/webhook/{platform}`
2. **Events to Subscribe:**
   - Booking Created
   - Booking Updated
   - Booking Cancelled
   - Listing Updated
   - Availability Changed

### Webhook Security

All webhooks are verified using HMAC signatures. Make sure to:
1. Set the webhook secret in the plugin configuration
2. Use the same secret in the PMS platform webhook configuration
3. Use HTTPS for production environments

## WooCommerce Integration

### Multi-Vendor Setup (Dokan/WCFM)

The plugin automatically works with multi-vendor plugins. When a listing is synced:
1. A WooCommerce product is created
2. If using Dokan or WCFM, assign vendors to products manually or programmatically
3. Bookings will be tied to the vendor's product

### Payment Gateways

Configure your preferred payment gateways in WooCommerce:
- Stripe
- PayPal
- Other WooCommerce-compatible gateways

The plugin creates orders that work with all standard WooCommerce payment gateways.

## Cron Schedule

The plugin uses WordPress cron to schedule syncs. Default schedule:
- Listings: Every hour
- Availability: Every hour
- Bookings: Every hour

You can change the frequency in **Rental Sync > Settings**.

For production environments, consider using server-level cron:

```bash
# Disable WordPress cron in wp-config.php
define('DISABLE_WP_CRON', true);

# Add to server crontab
*/15 * * * * wget -q -O - https://your-site.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

## Troubleshooting

### Enable Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Set log level to debug in plugin settings
$settings['log_level'] = 'debug';
update_option('rental_sync_engine_settings', $settings);
```

### Check Logs

View logs at: **Rental Sync > Sync Logs**

Or query directly:

```php
$plugin = \RentalSyncEngine\Plugin::get_instance();
$logger = $plugin->get_logger();
$logs = $logger->get_logs(array(
    'log_level' => 'error',
    'limit' => 50,
));
```

### Manually Trigger Cron

```bash
# Using WP-CLI
wp cron event run rental_sync_engine_sync_listings
wp cron event run rental_sync_engine_sync_availability
wp cron event run rental_sync_engine_sync_bookings
```
