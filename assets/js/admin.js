/**
 * Rental Sync Engine Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle manual sync buttons
        $('.quick-actions button[data-action]').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('action');
            var $result = $('#sync-result');
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $result
                .removeClass('success error')
                .addClass('loading')
                .html('<span class="rental-sync-spinner"></span>' + rentalSyncEngine.strings.syncInProgress)
                .show();
            
            // Make AJAX request
            $.ajax({
                url: rentalSyncEngine.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rental_sync_manual_sync',
                    sync_action: action,
                    nonce: rentalSyncEngine.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    
                    if (response.success) {
                        var message = rentalSyncEngine.strings.syncComplete;
                        if (response.data.success || response.data.failed) {
                            message += ' (' + response.data.success + ' success, ' + response.data.failed + ' failed)';
                        }
                        
                        $result
                            .removeClass('loading error')
                            .addClass('success')
                            .html(message);
                    } else {
                        $result
                            .removeClass('loading success')
                            .addClass('error')
                            .html(rentalSyncEngine.strings.syncError + ' ' + (response.data.message || ''));
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $result
                        .removeClass('loading success')
                        .addClass('error')
                        .html(rentalSyncEngine.strings.syncError + ' ' + error);
                }
            });
        });

        // Handle clear logs button
        $('#clear-logs').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear old logs?')) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: rentalSyncEngine.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rental_sync_clear_logs',
                    nonce: rentalSyncEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cleared ' + response.data.deleted + ' old log entries.');
                        location.reload();
                    } else {
                        alert('Failed to clear logs: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text('Clear Old Logs');
                    }
                },
                error: function() {
                    alert('Failed to clear logs. Please try again.');
                    $button.prop('disabled', false).text('Clear Old Logs');
                }
            });
        });

        // Toggle platform credentials visibility
        $('.platform-card h2 input[type="checkbox"]').on('change', function() {
            var $credentials = $(this).closest('.platform-card').find('.platform-credentials');
            if ($(this).is(':checked')) {
                $credentials.slideDown();
            } else {
                $credentials.slideUp();
            }
        }).trigger('change');
    });

})(jQuery);
