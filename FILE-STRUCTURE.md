# Rental Sync Engine - File Structure

```
rental-sync-engine/
├── rental-sync-engine.php          # Main plugin file
├── uninstall.php                   # Cleanup on uninstall
├── composer.json                   # Composer configuration
├── README.md                       # Main documentation
├── CONFIGURATION.md                # Configuration examples
├── CHANGELOG.md                    # Version history
├── .gitignore                      # Git ignore rules
│
├── includes/                       # Plugin core files
│   ├── Plugin.php                  # Main plugin class
│   │
│   ├── Core/                       # Core functionality
│   │   ├── Admin.php              # WordPress admin interface
│   │   ├── CronManager.php        # Scheduled sync management
│   │   ├── DatabaseManager.php    # Database operations
│   │   ├── Installer.php          # Activation/deactivation
│   │   ├── Logger.php             # Logging system
│   │   └── SyncManager.php        # Sync coordination
│   │
│   ├── PMS/                        # PMS platform integrations
│   │   ├── AbstractPMSHandler.php # Base handler class
│   │   ├── PMSFactory.php         # Handler factory
│   │   │
│   │   └── Handlers/               # Platform-specific handlers
│   │       ├── RentalsUnitedHandler.php
│   │       ├── HostawayHandler.php
│   │       ├── HostifyHandler.php
│   │       ├── UplistingHandler.php
│   │       ├── NextPaxHandler.php
│   │       └── OwnerRezHandler.php
│   │
│   ├── Webhooks/                   # Webhook handling
│   │   └── WebhookManager.php     # REST API endpoints
│   │
│   └── WooCommerce/                # WooCommerce integration
│       └── OrderManager.php        # Order/product management
│
├── assets/                         # Frontend assets
│   ├── css/
│   │   └── admin.css              # Admin styles
│   └── js/
│       └── admin.js               # Admin JavaScript
│
└── languages/                      # Translations (empty, ready for i18n)
```

## Key Components

### Main Plugin File (`rental-sync-engine.php`)
- Plugin header with metadata
- Dependency checks (WooCommerce, PHP version)
- Initialization hooks
- Activation/deactivation hooks

### Core Classes

#### `Plugin.php`
- Singleton pattern for plugin instance
- Dependency injection container
- Initializes all subsystems
- Manages asset enqueueing

#### `Core/Installer.php`
- Database table creation
- Default options setup
- Cron job scheduling
- Cleanup on deactivation

#### `Core/Admin.php`
- WordPress admin menu integration
- Settings page rendering
- Platform configuration UI
- Dashboard with statistics
- Logs viewer
- AJAX handlers for manual syncs

#### `Core/Logger.php`
- Multi-level logging (debug, info, warning, error)
- Database storage of logs
- Log retrieval and filtering
- Old log cleanup

#### `Core/DatabaseManager.php`
- CRUD operations for listings
- CRUD operations for bookings
- Database queries with prepared statements
- Data normalization

#### `Core/SyncManager.php`
- Orchestrates all sync operations
- Listing synchronization
- Availability synchronization
- Booking synchronization
- Push bookings to PMS
- Error handling and logging

#### `Core/CronManager.php`
- WordPress cron integration
- Custom cron intervals
- Scheduled sync execution
- Cron rescheduling

### PMS Integration

#### `PMS/AbstractPMSHandler.php`
- Base class for all PMS handlers
- Common API request methods
- Abstract methods for implementation
- Data normalization interface

#### `PMS/PMSFactory.php`
- Factory pattern for creating handlers
- Supported platform registry
- Platform name mapping

#### `PMS/Handlers/*`
- Platform-specific implementations
- API authentication
- Data fetching (listings, bookings, availability)
- Data pushing (create/update bookings)
- Webhook signature verification
- Data normalization to common format

### Webhook System

#### `Webhooks/WebhookManager.php`
- REST API route registration
- Webhook payload parsing
- Signature verification
- Event routing (booking, listing, availability)
- Real-time sync triggers

### WooCommerce Integration

#### `WooCommerce/OrderManager.php`
- WooCommerce product creation from listings
- Product attribute management
- Image handling and upload
- Order creation from bookings
- Booking data extraction from orders
- Status mapping (PMS ↔ WooCommerce)
- Integration with payment gateways
- Multi-vendor plugin support hooks

## Database Tables

### `wp_rental_sync_logs`
```sql
- id (bigint)
- log_type (varchar)
- log_level (varchar)
- message (text)
- context (longtext)
- created_at (datetime)
```

### `wp_rental_sync_listings`
```sql
- id (bigint)
- pms_platform (varchar)
- pms_listing_id (varchar)
- wc_product_id (bigint)
- listing_data (longtext)
- last_synced (datetime)
- sync_status (varchar)
- created_at (datetime)
- updated_at (datetime)
```

### `wp_rental_sync_bookings`
```sql
- id (bigint)
- pms_platform (varchar)
- pms_booking_id (varchar)
- pms_listing_id (varchar)
- wc_order_id (bigint)
- booking_data (longtext)
- booking_status (varchar)
- check_in_date (date)
- check_out_date (date)
- guest_name (varchar)
- guest_email (varchar)
- total_amount (decimal)
- last_synced (datetime)
- created_at (datetime)
- updated_at (datetime)
```

## WordPress Options

- `rental_sync_engine_version`: Plugin version
- `rental_sync_engine_settings`: General settings array
- `rental_sync_engine_pms_credentials`: PMS platform credentials

## Cron Hooks

- `rental_sync_engine_sync_listings`: Scheduled listing sync
- `rental_sync_engine_sync_availability`: Scheduled availability sync
- `rental_sync_engine_sync_bookings`: Scheduled booking sync

## REST API Endpoints

- `POST /wp-json/rental-sync-engine/v1/webhook/{platform}`: Webhook receiver

## WordPress Hooks

### Actions
- `rental_sync_engine_push_booking`: Trigger booking push to PMS
- `rental_sync_engine_cancel_booking`: Trigger booking cancellation

### Filters
- `cron_schedules`: Add custom cron intervals

## AJAX Actions

- `rental_sync_manual_sync`: Manual sync trigger
- `rental_sync_clear_logs`: Clear old logs

## Admin Menu Structure

- **Rental Sync** (main menu)
  - Dashboard (sync status, statistics, quick actions)
  - Settings (sync frequency, webhooks, logging)
  - PMS Platforms (API credentials)
  - Sync Logs (view logs, clear old entries)

## Supported PMS Platforms

1. **Rentals United**
   - API Type: REST
   - Auth: Basic (username/password)

2. **Hostaway**
   - API Type: REST
   - Auth: OAuth2 (client credentials)

3. **Hostify**
   - API Type: REST
   - Auth: API Key

4. **Uplisting**
   - API Type: REST
   - Auth: Bearer Token

5. **NextPax**
   - API Type: REST
   - Auth: API Key

6. **OwnerRez**
   - API Type: REST
   - Auth: Bearer Token + Username

## Development Guidelines

### Adding a New PMS Platform

1. Create handler class in `includes/PMS/Handlers/YourPMSHandler.php`
2. Extend `AbstractPMSHandler`
3. Implement all abstract methods
4. Register in `PMSFactory::$platforms`
5. Add credential fields in `Admin::get_platform_fields()`
6. Add platform name in `PMSFactory::get_platform_names()`

### Code Standards

- PSR-4 autoloading
- WordPress Coding Standards
- PHPDoc comments for all classes and methods
- Input sanitization and output escaping
- Prepared statements for database queries
- Nonce verification for forms and AJAX

### Security Practices

- Capability checks (`manage_options`)
- Nonce verification
- Input sanitization
- Output escaping
- SQL injection prevention
- XSS protection
- Webhook signature verification
