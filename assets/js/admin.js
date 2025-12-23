/**
 * Rental Sync Engine Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Settings tabs
        $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            var target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        });
        
        // Manual sync triggers
        $('.sync-trigger').on('click', function() {
            var $button = $(this);
            var provider = $button.data('provider');
            var syncType = $button.data('type');
            var $status = $('#sync-status-' + provider);
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $status.removeClass('success error').addClass('loading show');
            $status.text(rentalSyncEngine.i18n.syncStarted);
            
            // Make AJAX request
            $.ajax({
                url: rentalSyncEngine.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rental_sync_manual_trigger',
                    provider: provider,
                    sync_type: syncType,
                    nonce: rentalSyncEngine.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    
                    if (response.success) {
                        $status.removeClass('loading').addClass('success');
                        
                        var results = response.data;
                        var message = rentalSyncEngine.i18n.syncCompleted + ': ';
                        
                        if (results.properties) {
                            message += 'Properties: ' + (results.properties.success || 0) + ' synced. ';
                        }
                        if (results.availability) {
                            message += 'Availability: ' + (results.availability.success || 0) + ' synced. ';
                        }
                        if (results.bookings) {
                            message += 'Bookings: ' + (results.bookings.success || 0) + ' synced.';
                        }
                        
                        $status.text(message);
                        
                        // Hide status after 10 seconds
                        setTimeout(function() {
                            $status.removeClass('show');
                        }, 10000);
                    } else {
                        $status.removeClass('loading').addClass('error');
                        $status.text(rentalSyncEngine.i18n.syncFailed + ': ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $status.removeClass('loading').addClass('error');
                    $status.text(rentalSyncEngine.i18n.syncFailed + ': ' + error);
                }
            });
        });
        
        // Dashboard helper methods
        if (typeof window.rentalSyncDashboard !== 'undefined') {
            // Add any dashboard-specific JavaScript here
        }
        
    });
    
})(jQuery);
