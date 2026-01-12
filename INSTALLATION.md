# Installation Guide

## Prerequisites

Before installing the Rental Sync Engine plugin, ensure your environment meets these requirements:

- **WordPress**: Version 5.8 or higher
- **WooCommerce**: Version 5.0 or higher
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.6+ or MariaDB 10.0+

## Installation Steps

### Option 1: Standard WordPress Installation (Recommended)

1. **Download the plugin**
   - Clone or download from: https://github.com/git-seb/rental-sync-engine

2. **Upload to WordPress**
   - Copy the entire `rental-sync-engine` folder to `/wp-content/plugins/`
   - Or upload as a ZIP file through WordPress admin

3. **Activate the plugin**
   - Go to WordPress Admin → Plugins
   - Find "Rental Sync Engine" and click "Activate"

4. **Verify activation**
   - Check for "Rental Sync" menu item in WordPress admin
   - Confirm database tables were created

### Option 2: Git Clone (For Developers)

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/git-seb/rental-sync-engine.git
```

Then activate through WordPress admin.

## Post-Installation Setup

### 1. Initial Configuration

After activation, navigate to **Rental Sync → Settings**:

1. **General Settings**
   - Enable sync: Yes
   - Sync frequency: Hourly (recommended)
   - Log retention: 30 days
   - Debug mode: No (enable only for troubleshooting)

### 2. Configure PMS Providers

#### Rentals United Setup
1. Go to **Settings → Rentals United** tab
2. Enable the integration
3. Enter your Rentals United credentials:
   - Username: Your RU username
   - Password: Your RU password
   - Webhook Secret: (Generate a random string for security)
4. Copy webhook URL displayed on the page
5. Add webhook URL to your Rentals United dashboard

#### OwnerRez Setup
1. Go to **Settings → OwnerRez** tab
2. Enable the integration
3. Obtain API token from OwnerRez:
   - Login to OwnerRez
   - Go to Settings → API
   - Generate new API token
4. Enter API token in plugin settings
5. Configure webhook secret
6. Add webhook URL to OwnerRez

#### Uplisting Setup
1. Go to **Settings → Uplisting** tab
2. Enable the integration
3. Obtain API key from Uplisting:
   - Login to Uplisting
   - Go to Settings → Integrations
   - Generate API key
4. Enter API key in plugin settings
5. Configure webhook secret
6. Add webhook URL to Uplisting

#### Hostaway Setup
1. Go to **Settings → Hostaway** tab
2. Enable the integration
3. Create OAuth application in Hostaway:
   - Login to Hostaway
   - Go to Settings → API
   - Create new OAuth app
4. Enter Client ID and Client Secret
5. Configure webhook secret
6. Add webhook URL to Hostaway

### 3. First Sync

After configuring at least one PMS provider:

1. Go to **Rental Sync → Manual Sync**
2. Click "Sync All" for your configured provider
3. Monitor the sync progress
4. Check **Sync Logs** for detailed results
5. Verify properties appear in WooCommerce products

### 4. Verify Installation

Check that everything is working:

1. **Dashboard Check**
   - Go to **Rental Sync → Dashboard**
   - Confirm provider status shows "Enabled"
   - Check sync statistics

2. **Properties Check**
   - Go to WooCommerce → Products
   - Verify properties were imported
   - Check product metadata

3. **Logs Check**
   - Go to **Rental Sync → Sync Logs**
   - Confirm successful sync operations
   - No error messages

## Webhook Configuration

For each PMS provider, you need to configure webhooks:

### Webhook URLs
- Rentals United: `https://yoursite.com/rental-sync-webhook/rentals-united`
- OwnerRez: `https://yoursite.com/rental-sync-webhook/ownerrez`
- Uplisting: `https://yoursite.com/rental-sync-webhook/uplisting`
- Hostaway: `https://yoursite.com/rental-sync-webhook/hostaway`

### Webhook Events to Subscribe
Configure your PMS to send webhooks for:
- Property/Listing created/updated
- Booking/Reservation created/updated
- Availability/Calendar updated
- Booking cancellations

### Testing Webhooks
1. Make a change in your PMS (e.g., update property)
2. Check **Sync Logs** for webhook activity
3. Verify changes reflected in WooCommerce

## Scheduled Syncs

The plugin automatically schedules periodic syncs:

1. **Verify Cron Setup**
   ```bash
   wp cron event list
   ```
   Look for `rental_sync_engine_hourly_sync`

2. **Manual Cron Trigger** (for testing)
   ```bash
   wp cron event run rental_sync_engine_hourly_sync
   ```

## Troubleshooting

### Plugin Won't Activate
- **Issue**: Activation error
- **Solution**: 
  - Check WooCommerce is installed and active
  - Verify PHP version is 7.4+
  - Check PHP error logs for specific errors

### No Sync Activity
- **Issue**: Sync doesn't start
- **Solution**:
  - Enable debug mode in settings
  - Check API credentials are correct
  - Verify PMS API is accessible
  - Check PHP error logs

### Database Errors
- **Issue**: Database table creation fails
- **Solution**:
  - Check database user has CREATE TABLE permission
  - Manually run table creation queries
  - Contact hosting provider

### Webhook Not Working
- **Issue**: Webhooks not received
- **Solution**:
  - Verify webhook URL is accessible externally
  - Check webhook secret matches
  - Test webhook endpoint with curl
  - Check firewall settings

### Properties Not Syncing
- **Issue**: Properties don't appear in WooCommerce
- **Solution**:
  - Check API credentials
  - Review sync logs for errors
  - Enable debug mode
  - Test API connection manually

## Uninstallation

To completely remove the plugin:

1. **Deactivate Plugin**
   - WordPress Admin → Plugins → Deactivate

2. **Delete Plugin Files**
   - Delete the plugin folder

3. **Remove Database Tables** (Optional)
   ```sql
   DROP TABLE wp_rental_sync_logs;
   DROP TABLE wp_rental_sync_property_mappings;
   DROP TABLE wp_rental_sync_booking_mappings;
   ```

4. **Remove Plugin Options** (Optional)
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE 'rental_sync_engine%';
   ```

## Getting Help

- **Documentation**: Check README.md and IMPLEMENTATION_SUMMARY.md
- **Issues**: https://github.com/git-seb/rental-sync-engine/issues
- **Logs**: Enable debug mode and check sync logs
- **Support**: Contact plugin maintainers

## Next Steps

After successful installation:

1. Review the [README.md](README.md) for detailed usage instructions
2. Check [ARCHITECTURE.md](ARCHITECTURE.md) for technical details
3. Set up scheduled syncs according to your needs
4. Configure WooCommerce booking settings
5. Test end-to-end booking flow
6. Set up email notifications for bookings
7. Configure payment gateways in WooCommerce

## Security Recommendations

1. Use strong, unique webhook secrets
2. Keep API credentials secure
3. Enable HTTPS on your WordPress site
4. Regularly update WordPress and WooCommerce
5. Monitor sync logs for suspicious activity
6. Limit admin access to trusted users
7. Keep plugin updated

## Performance Optimization

1. Set appropriate sync frequency (hourly recommended)
2. Configure log retention to prevent database bloat
3. Use WordPress caching plugins
4. Optimize WooCommerce for performance
5. Monitor server resources during sync operations

---

**Installation Complete!** 🎉

Your Rental Sync Engine is now ready to synchronize properties and bookings across multiple PMS platforms.
