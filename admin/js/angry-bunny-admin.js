(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle license activation
        $('#angry-bunny-license-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var originalText = $submitButton.val();
            
            $submitButton.prop('disabled', true)
                .val($submitButton.data('processing') || 'Processing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'angry_bunny_license_action',
                    nonce: angryBunny.nonce,
                    license_key: $('#license_key').val(),
                    license_action: $form.find('input[name="action"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'An error occurred. Please try again.');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).val(originalText);
                }
            });
        });

        // Handle test email
        $('#test-email').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.prop('disabled', true)
                .text($button.data('sending') || 'Sending...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'angry_bunny_test_email',
                    nonce: angryBunny.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test email sent successfully!');
                    } else {
                        alert(response.data.message || 'Failed to send test email.');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Handle security scan
        $('#run-scan').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.prop('disabled', true)
                .text($button.data('scanning') || 'Scanning...')
                .addClass('running');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'angry_bunny_run_scan',
                    nonce: angryBunny.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Scan failed. Please try again.');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false)
                        .text(originalText)
                        .removeClass('running');
                }
            });
        });

        // Handle license generation form submission
        $('#generate-license-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');
            const originalButtonText = $submitButton.val();

            $submitButton.val('Generating...').prop('disabled', true);

            $.ajax({
                url: angryBunnyAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'angry_bunny_generate_license',
                    nonce: angryBunnyAdmin.nonce,
                    site_limit: $('#site_limit').val(),
                    owner_email: $('#owner_email').val(),
                    owner_name: $('#owner_name').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert('License generated successfully: ' + response.data.license_key);
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while generating the license.');
                },
                complete: function() {
                    $submitButton.val(originalButtonText).prop('disabled', false);
                }
            });
        });

        // Handle copy API key button
        $('.copy-api-key').on('click', function() {
            const $input = $(this).prev('input');
            $input.select();
            document.execCommand('copy');
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text('Copied!');
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        });

        // Handle license revocation
        $('.revoke-license').on('click', function() {
            if (!confirm('Are you sure you want to revoke this license? This action cannot be undone.')) {
                return;
            }

            const $button = $(this);
            const licenseKey = $button.data('license');
            
            $button.text('Revoking...').prop('disabled', true);

            $.ajax({
                url: angryBunnyAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'angry_bunny_revoke_license',
                    nonce: angryBunnyAdmin.nonce,
                    license_key: licenseKey
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $button.text('Revoke').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred while revoking the license.');
                    $button.text('Revoke').prop('disabled', false);
                }
            });
        });
    });
})(jQuery); 