# Rental Sync Engine - Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          WordPress Admin Interface                           │
├─────────────────────────────────────────────────────────────────────────────┤
│  Dashboard  │  Settings  │  Sync Logs  │  Manual Sync                        │
└──────┬──────┴─────┬──────┴──────┬──────┴──────┬───────────────────────────────┘
       │            │             │             │
       ▼            ▼             ▼             ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          Core Plugin Components                              │
├──────────────┬──────────────┬──────────────┬──────────────┬─────────────────┤
│   Settings   │    Logger    │   Webhook    │     Sync     │  WooCommerce    │
│              │              │    Router    │  Scheduler   │   Integration   │
└──────┬───────┴──────┬───────┴──────┬───────┴──────┬───────┴─────────┬───────┘
       │              │              │              │                 │
       │              │              │              │                 │
       │              │              ▼              │                 │
       │              │      ┌──────────────┐      │                 │
       │              │      │  Webhook     │      │                 │
       │              │      │  Endpoints   │      │                 │
       │              │      └──────┬───────┘      │                 │
       │              │             │              │                 │
       ▼              ▼             ▼              ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         PMS Handler Layer                                    │
├──────────────┬──────────────┬──────────────┬──────────────┐                 │
│   Rentals    │   OwnerRez   │  Uplisting   │   Hostaway   │                 │
│   United     │              │              │              │                 │
│              │              │              │              │                 │
│ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │                 │
│ │  Client  │ │ │  Client  │ │ │  Client  │ │ │  Client  │ │                 │
│ └──────────┘ │ └──────────┘ │ └──────────┘ │ └──────────┘ │                 │
│ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │                 │
│ │ Handler  │ │ │ Handler  │ │ │ Handler  │ │ │ Handler  │ │                 │
│ └──────────┘ │ └──────────┘ │ └──────────┘ │ └──────────┘ │                 │
│ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │                 │
│ │ Webhook  │ │ │ Webhook  │ │ │ Webhook  │ │ │ Webhook  │ │                 │
│ └──────────┘ │ └──────────┘ │ └──────────┘ │ └──────────┘ │                 │
└──────┬───────┴──────┬───────┴──────┬───────┴──────┬───────┘                 │
       │              │              │              │                          │
       ▼              ▼              ▼              ▼                          │
┌─────────────────────────────────────────────────────────────────────────────┤
│                    External PMS API Endpoints                                │
├──────────────┬──────────────┬──────────────┬──────────────┐                 │
│  Rentals     │  OwnerRez    │  Uplisting   │  Hostaway    │                 │
│  United API  │  API v2      │  API v1      │  API v1      │                 │
└──────────────┴──────────────┴──────────────┴──────────────┘                 │
                                                                                │
┌─────────────────────────────────────────────────────────────────────────────┤
│                         Database Tables                                      │
├──────────────────────┬──────────────────────┬──────────────────────┐        │
│  wp_rental_sync_logs │  wp_rental_sync_     │  wp_rental_sync_     │        │
│                      │  property_mappings   │  booking_mappings    │        │
│  - Sync activity     │  - PMS ↔ WC Product  │  - PMS ↔ WC Order   │        │
│  - Error logs        │  - Sync status       │  - Booking status    │        │
└──────────────────────┴──────────────────────┴──────────────────────┘        │
                                                                                │
┌─────────────────────────────────────────────────────────────────────────────┤
│                         WooCommerce                                          │
├────────────────────────────────┬────────────────────────────────────────────┤
│  Products (Properties)         │  Orders (Bookings)                         │
│  - Property details            │  - Guest information                       │
│  - Pricing                     │  - Stay dates                              │
│  - Availability calendar       │  - Payment details                         │
└────────────────────────────────┴────────────────────────────────────────────┘
```

## Data Flow

### Property Sync (PMS → WooCommerce)
```
PMS API → Client → Handler → WooCommerceIntegration → WC Product
                     ↓
                  Logger → Database
                     ↓
              Property Mapping → Database
```

### Booking Sync (PMS → WooCommerce)
```
PMS API / Webhook → Client / WebhookHandler → Handler → WooCommerceIntegration
                                                ↓
                                             Logger → Database
                                                ↓
                                        Booking Mapping → Database
                                                ↓
                                           WC Order Created
```

### Booking Push (WooCommerce → PMS)
```
WC Order → WooCommerceIntegration → Handler → Client → PMS API
                                       ↓
                                    Logger → Database
                                       ↓
                               Booking Mapping → Database
```

## Component Responsibilities

### Core Components
- **Settings**: Manage API credentials and plugin configuration
- **Logger**: Record all sync operations and errors
- **WebhookRouter**: Route incoming webhooks to appropriate handlers
- **SyncScheduler**: Manage scheduled and manual synchronization
- **WooCommerceIntegration**: Bridge between PMS data and WooCommerce

### PMS Handlers (per provider)
- **Client**: API communication and authentication
- **Handler**: Orchestrate sync operations and data mapping
- **WebhookHandler**: Process real-time webhook events

### Database Tables
- **Logs**: Complete audit trail of all operations
- **Property Mappings**: Link PMS properties to WC products
- **Booking Mappings**: Link PMS bookings to WC orders

## Authentication Methods

| PMS Provider     | Method                    | Credentials                |
|------------------|---------------------------|----------------------------|
| Rentals United   | Basic Auth                | Username + Password        |
| OwnerRez         | Bearer Token              | API Token                  |
| Uplisting        | Bearer Token              | API Key                    |
| Hostaway         | OAuth 2.0                 | Client ID + Client Secret  |

## Synchronization Types

1. **Properties**: Fetch property listings and create/update WC products
2. **Availability**: Update product availability based on PMS calendars
3. **Bookings**: 
   - Pull: Create WC orders from PMS bookings
   - Push: Send WC orders to PMS as bookings

## Security Layers

1. **WordPress Nonces**: AJAX request verification
2. **Capability Checks**: `manage_options` required for admin functions
3. **Input Sanitization**: All user inputs sanitized
4. **Prepared Statements**: SQL injection protection
5. **Webhook Signatures**: HMAC-SHA256 verification
6. **Rate Limiting**: API call throttling

## Error Handling

- All API calls wrapped in try-catch blocks
- Detailed error logging to database
- User-friendly error messages in admin
- Automatic retry mechanisms (ready for implementation)
- Failed sync tracking and reporting
