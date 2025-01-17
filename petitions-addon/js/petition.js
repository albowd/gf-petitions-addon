jQuery(document).ready(function($) {
    // Listen for successful form submission
    $(document).on('gform_confirmation_loaded', function(event, formId) {
        // Find the progress bar for this form
        const progressBar = $('.petition-progress[data-form-id="' + formId + '"]');
        if (!progressBar.length) return;

        // Update the count via AJAX
        updateSignatureCount(formId);
    });

    function updateSignatureCount(formId) {
        $.ajax({
            url: gfPetition.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_signature_count',
                nonce: gfPetition.nonce,
                form_id: formId
            },
            success: function(response) {
                if (!response.success) return;

                const data = response.data;
                const progressBar = $('.petition-progress[data-form-id="' + formId + '"]');
                
                // Update the count text
                progressBar.find('.petition-count').text('Signatures: ' + data.count + ' of ' + data.goal);
                
                // Update the progress bar width
                progressBar.find('.progress').css('width', data.percentage + '%');
                
                // Add animation class
                progressBar.find('.progress').addClass('updating').delay(500).queue(function(next) {
                    $(this).removeClass('updating');
                    next();
                });
            }
        });
    }
});