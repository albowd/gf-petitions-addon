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
                        row.find('td:nth-child(2)').text('0'); // Update total signatures
                        row.find('td:nth-child(3)').text('0 / ' + row.find('td:nth-child(3)').text().split(' / ')[1]); // Update actual/additional
                        row.find('.petition-progress-small div').css('width', '0%'); // Update progress bar
                        row.find('.petition-progress-small').next().text('0%'); // Update progress text
                        
                        // Show success notice
                        const notice = $('<div class="notice notice-success is-dismissible"><p>' + 
                            response.data.message + '</p></div>');
                        $('.wrap h1').after(notice);
                        
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
                    }
                    
                    // Enable button
                    button.prop('disabled', false);
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

        // Handle toggle status (Active/Inactive)
        $('.toggle-status').on('change', function(e) {
            const toggle = $(this);
            const formId = toggle.data('form-id');
            const isActive = toggle.prop('checked');
            const statusText = toggle.closest('.toggle-label').find('.status-text');
            
            // Disable toggle while processing
            toggle.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: gfPetitionAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gf_toggle_form_status',
                    form_id: formId,
                    is_active: isActive ? 1 : 0,
                    nonce: gfPetitionAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status text
                        statusText.text(isActive ? 'Active' : 'Inactive')
                            .css('color', isActive ? '#2ea44f' : '#dc3232');
                        
                        // Show success notice
                        const notice = $('<div class="notice notice-success is-dismissible"><p>' + 
                            (isActive ? 'Form activated.' : 'Form deactivated.') + '</p></div>');
                        $('.wrap h1').after(notice);
                        
                        // Auto-dismiss notice after 3 seconds
                        setTimeout(function() {
                            notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        // Revert toggle if failed
                        toggle.prop('checked', !isActive);
                        statusText.text(!isActive ? 'Active' : 'Inactive')
                            .css('color', !isActive ? '#2ea44f' : '#dc3232');
                        
                        // Show error notice
                        const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                            'Error updating form status.' + '</p></div>');
                        $('.wrap h1').after(notice);
                    }
                    
                    // Enable toggle
                    toggle.prop('disabled', false);
                },
                error: function() {
                    // Revert toggle if failed
                    toggle.prop('checked', !isActive);
                    statusText.text(!isActive ? 'Active' : 'Inactive')
                        .css('color', !isActive ? '#2ea44f' : '#dc3232');
                    
                    // Show error notice
                    const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                        'Error updating form status.' + '</p></div>');
                    $('.wrap h1').after(notice);
                    
                    // Enable toggle
                    toggle.prop('disabled', false);
                }
            });
        });

        // Handle toggle complete status
        $('.toggle-complete').on('change', function(e) {
            const button = $(this);
            const formId = button.data('form-id');
            
            // Disable toggle while processing
            button.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: gfPetitionAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'toggle_petition_complete',
                    nonce: gfPetitionAdmin.nonce,
                    form_id: formId,
                    complete: button.prop('checked')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success notice
                        const notice = $('<div class="notice notice-success is-dismissible"><p>' + 
                            response.data.message + '</p></div>');
                        $('.wrap h1').after(notice);
                        
                        // Update UI
                        const row = button.closest('tr');
                        const progressBar = row.find('.petition-progress-small div');
                        const progressText = row.find('.petition-progress-small').next();
                        const badge = row.find('.completed-badge');
                        
                        if (button.prop('checked')) {
                            progressBar.css('background', '#2ea44f').css('width', '100%');
                            progressText.text('Complete');
                            if (!badge.length) {
                                row.find('td:first strong').after('<span class="completed-badge" style="background: #2ea44f; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">Completed</span>');
                            }
                        } else {
                            const progress = progressBar.closest('td').data('progress');
                            progressBar.css('background', '#c5203a').css('width', Math.min(progress, 100) + '%');
                            progressText.text(progress + '%');
                            badge.remove();
                        }
                        
                        // Auto-dismiss notice after 3 seconds
                        setTimeout(function() {
                            notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        // Show error notice and revert toggle
                        const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                            response.data + '</p></div>');
                        $('.wrap h1').after(notice);
                        button.prop('checked', !button.prop('checked'));
                    }
                    
                    // Enable toggle
                    button.prop('disabled', false);
                },
                error: function() {
                    // Show generic error notice and revert toggle
                    const notice = $('<div class="notice notice-error is-dismissible"><p>' + 
                        'An error occurred while updating the petition.' + '</p></div>');
                    $('.wrap h1').after(notice);
                    button.prop('checked', !button.prop('checked'));
                    
                    // Enable toggle
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