# Changelog

All notable changes to the Rental Sync Engine plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-22

### Added
- Initial release of Rental Sync Engine
- Support for 6 PMS platforms:
  - Rentals United
  - Hostaway
  - Hostify
  - Uplisting
  - NextPax
  - OwnerRez
- Two-way booking synchronization
- Automatic listing sync from PMS to WooCommerce
- Availability calendar synchronization
- Real-time webhook support for all platforms
- WooCommerce integration:
  - Automatic product creation from listings
  - Booking to order conversion
  - Support for payment gateways
  - Multi-vendor plugin compatibility (Dokan, WCFM)
- Comprehensive admin interface:
  - Dashboard with sync status and statistics
  - Settings page with sync configuration
  - PMS platform credential management
  - Detailed sync logs with filtering
  - Manual sync triggers
- Modular architecture with PSR-4 autoloading
- Custom database tables for:
  - Sync logs
  - Listing mappings
  - Booking mappings
- Cron-based scheduled synchronization with custom intervals
- Error handling and logging system
- Webhook signature verification for security
- Complete WordPress and WooCommerce best practices
- Proper sanitization and validation
- AJAX functionality for admin actions
- Responsive admin CSS
- Comprehensive documentation and examples

### Security
- Webhook signature verification
- Input sanitization and validation
- SQL injection prevention
- XSS protection
- Secure credential storage

## [Unreleased]

### Planned Features
- Booking conflict detection
- Advanced pricing rules synchronization
- Multi-currency support enhancement
- Custom field mapping
- Bulk operations for listings
- Export/import functionality
- Performance optimizations for large datasets
- Integration with additional PMS platforms
- Advanced reporting and analytics
- Automatic image optimization
- Support for property amenities taxonomies
- Integration with Google Calendar
- SMS notifications for bookings
- Email templates customization
