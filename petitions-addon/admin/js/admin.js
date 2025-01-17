(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle export signatures
        $('.export-signatures').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const formId = button.data('form-id');
            
            // Disable button while processing
            button.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: gfPetitionAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'export_signatures',
                    nonce: gfPetitionAdmin.nonce,
                    form_id: formId
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(response) {
                    // Create download link
                    const url = window.URL.createObjectURL(new Blob([response]));
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'petition-signatures-' + formId + '.csv';
                    
                    // Trigger download
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    // Enable button
                    button.prop('disabled', false);
                },
                error: function() {
                    // Show generic error notice
                    const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                        'An error occurred while exporting signatures.' + '</p></div>');
                    $('.wrap h1').after(notice);
                    
                    // Enable button
                    button.prop('disabled', false);
                }
            });
        });

        // Handle reset signature count
        $('.reset-signatures').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const formId = button.data('form-id');

            // Show confirmation dialog
            if (!confirm(gfPetitionAdmin.confirmReset)) {
                return;
            }

            // Disable button while processing
            button.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: gfPetitionAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'reset_signatures',
                    nonce: gfPetitionAdmin.nonce,
                    form_id: formId
                },
                success: function(response) {
                    if (response.success) {
                        // Update the signature count display
                        const row = button.closest('tr');
                        row.find('td:nth-child(2)').text('0'); // Update signatures column
                        row.find('.petition-progress-small div').css('width', '0%'); // Update progress bar
                        row.find('td:nth-child(4)').text('0%'); // Update progress percentage
                        
                        // Show success notice
                        const notice = $('<div class="notice notice-success is-dismissible"><p>' + 
                            response.data.message + '</p></div>');
                        $('.wrap h1').after(notice);
                        
                        // Enable button
                        button.prop('disabled', false);
                        
                        // Auto-dismiss notice after 3 seconds
                        setTimeout(function() {
                            notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        // Show error notice
                        const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                            response.data + '</p></div>');
                        $('.wrap h1').after(notice);
                        
                        // Enable button
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    // Show generic error notice
                    const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                        'An error occurred while resetting signatures.' + '</p></div>');
                    $('.wrap h1').after(notice);
                    
                    // Enable button
                    button.prop('disabled', false);
                }
            });
        });

        // Handle notice dismissal
        $(document).on('click', '.notice-dismiss', function(e) {
            e.preventDefault();
            $(this).closest('.notice').fadeOut(function() {
                $(this).remove();
            });
        });

        // Add hover effect to buttons
        $('.action-links .button').hover(
            function() {
                $(this).css('opacity', '0.8');
            },
            function() {
                $(this).css('opacity', '1');
            }
        );
    });

})(jQuery);