# Changelog

All notable changes to the Rental Sync Engine plugin will be documented in this file.

## [1.1.0] - 2025-01-12

### Changed
- **Removed Composer dependency requirement** - Plugin now works out of the box without requiring Composer installation
- Replaced GuzzleHTTP with WordPress native HTTP API (`wp_remote_get`, `wp_remote_post`, etc.)
- Implemented custom PSR-4 autoloader to replace Composer autoloader
- Updated installation documentation to remove Composer steps
- Plugin is now fully self-contained and ready for shared hosting environments

### Technical Details
- Replaced `GuzzleHttp\Client` with WordPress HTTP API functions
- Created custom autoloader at `includes/autoload.php`
- All HTTP requests now use `wp_remote_*()` functions
- Maintained rate limiting and error handling functionality
- No external dependencies required for deployment

## [1.0.0] - 2025-12-23

### Added
- Initial release of Rental Sync Engine plugin
- Complete integration with Rentals United PMS
  - XML-based API client with Basic Auth
  - Property, availability, and booking synchronization
  - Webhook handler for real-time updates
- Complete integration with OwnerRez PMS
  - RESTful API client with Bearer token authentication
  - Full CRUD operations for properties and bookings
  - Webhook support for event notifications
- Complete integration with Uplisting PMS
  - API client with Bearer token authentication
  - Property and booking management
  - Real-time webhook processing
- Complete integration with Hostaway PMS
  - OAuth 2.0 authentication with client credentials
  - Listing and reservation management
  - Event-based webhook system
- WooCommerce integration
  - Automatic product creation from PMS properties
  - Order generation from PMS bookings
  - Guest information and metadata mapping
  - Bidirectional synchronization
- Webhook routing system
  - Centralized webhook endpoint management
  - Provider-specific routing
  - Signature verification for security
- Admin dashboard
  - Sync statistics and status overview
  - Provider status cards
  - Recent sync activity log
- Settings interface
  - Tabbed configuration for each PMS provider
  - General plugin settings
  - Webhook URL display
  - Credential management
- Sync logging system
  - Database-backed log storage
  - Filterable log viewer
  - Status indicators (success/error/warning)
  - Automatic log cleanup
- Manual sync functionality
  - On-demand sync triggers per provider
  - Granular sync type selection (properties/availability/bookings)
  - Real-time status updates via AJAX
- Scheduled synchronization
  - WordPress cron integration
  - Configurable sync frequency
  - Automatic execution
- Core infrastructure
  - PSR-4 autoloading
  - Base API client with rate limiting
  - PMS handler interface
  - Modular architecture
- Database tables
  - Sync logs table
  - Property mappings table
  - Booking mappings table
- Security features
  - Nonce verification for AJAX requests
  - Capability checks for admin functions
  - Input sanitization
  - SQL injection protection
  - Webhook signature verification
- Documentation
  - Comprehensive README
  - Implementation summary
  - Inline code documentation
  - API integration details

### Technical Details
- WordPress 5.8+ compatibility
- WooCommerce 5.0+ compatibility
- PHP 7.4+ requirement
- WordPress native HTTP API for external requests
- Custom PSR-4 autoloader for class loading
- PSR standards compliance

### Files Added
- Main plugin file with activation/deactivation hooks
- Core classes: ApiClient, Logger, Settings, SyncScheduler, WebhookRouter
- PMS handlers for all four platforms
- WooCommerce integration class
- Admin templates: dashboard, settings, logs, manual sync
- CSS and JavaScript assets
- Composer configuration
- Comprehensive documentation

[1.0.0]: https://github.com/git-seb/rental-sync-engine/releases/tag/v1.0.0
