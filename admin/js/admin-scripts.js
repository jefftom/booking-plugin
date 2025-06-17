jQuery(document).ready(function($) {
    'use strict';
    
    // Handle booking actions (approve, deny, delete)
    $('.br-action-button').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var action = $button.data('action');
        var bookingId = $button.data('booking-id');
        var confirmMessage = '';
        
        switch(action) {
            case 'approve':
                confirmMessage = br_admin.strings.confirm_approve;
                break;
            case 'deny':
                confirmMessage = br_admin.strings.confirm_deny;
                break;
            case 'delete':
                confirmMessage = br_admin.strings.confirm_delete;
                break;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        $button.prop('disabled', true).text(br_admin.strings.processing);
        
        $.ajax({
            url: br_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'br_' + action + '_booking',
                booking_id: bookingId,
                nonce: br_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || br_admin.strings.error);
                    $button.prop('disabled', false).text($button.data('original-text'));
                }
            },
            error: function() {
                alert(br_admin.strings.error);
                $button.prop('disabled', false).text($button.data('original-text'));
            }
        });
    });
    
    // Store original button text
    $('.br-action-button').each(function() {
        $(this).data('original-text', $(this).text());
    });
    
    // Initialize datepickers if on booking form page
    if ($('.datepicker').length) {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            onSelect: function(dateText, inst) {
                var $this = $(this);
                
                // If selecting check-in date, update check-out minDate
                if ($this.attr('id') === 'checkin_date') {
                    var checkinDate = new Date(dateText);
                    var minCheckout = new Date(checkinDate);
                    minCheckout.setDate(minCheckout.getDate() + 3); // Minimum 3 nights
                    
                    $('#checkout_date').datepicker('option', 'minDate', minCheckout);
                    
                    // Clear checkout date if it's now invalid
                    if ($('#checkout_date').val()) {
                        var currentCheckout = new Date($('#checkout_date').val());
                        if (currentCheckout < minCheckout) {
                            $('#checkout_date').val('');
                        }
                    }
                }
                
                checkAvailability();
            }
        });
        
        // Set initial minDate for checkout if checkin is already selected
        if ($('#checkin_date').val()) {
            var checkinDate = new Date($('#checkin_date').val());
            var minCheckout = new Date(checkinDate);
            minCheckout.setDate(minCheckout.getDate() + 3);
            $('#checkout_date').datepicker('option', 'minDate', minCheckout);
        }
    }
    
    // Check availability function
    function checkAvailability() {
        var checkin = $('#checkin_date').val();
        var checkout = $('#checkout_date').val();
        
        if (!checkin || !checkout) return;
        
        var checkinDate = new Date(checkin);
        var checkoutDate = new Date(checkout);
        var nights = Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        
        if (nights < 3) {
            $('#availability-status').html('<span style="color: red;">Minimum stay is 3 nights</span>');
            return;
        }
        
        $('#availability-status').html('<span style="color: #666;">Checking availability...</span>');
        
        $.post(br_admin.ajax_url, {
            action: 'br_check_availability',
            nonce: br_admin.nonce,
            checkin_date: checkin,
            checkout_date: checkout,
            exclude_id: $('#booking_id').val() || null
        }, function(response) {
            if (response.success && response.data.available) {
                $('#availability-status').html('<span style="color: green;">✓ Available (' + nights + ' nights)</span>');
            } else {
                $('#availability-status').html('<span style="color: red;">✗ Not Available</span>');
            }
        });
    }
    
    // Calculate price button
    $('#calculate-price').on('click', function() {
        var checkin = $('#checkin_date').val();
        var checkout = $('#checkout_date').val();
        
        if (!checkin || !checkout) {
            alert('Please select both check-in and check-out dates');
            return;
        }
        
        var checkinDate = new Date(checkin);
        var checkoutDate = new Date(checkout);
        var nights = Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        
        if (nights < 3) {
            alert('Minimum stay is 3 nights');
            return;
        }
        
        $('#total-price-display').text(br_admin.strings.calculating);
        
        $.post(br_admin.ajax_url, {
            action: 'br_calculate_price',
            nonce: br_admin.nonce,
            checkin_date: checkin,
            checkout_date: checkout
        }, function(response) {
            if (response.success) {
                $('#total-price-display').text(response.data.formatted_price);
            } else {
                $('#total-price-display').text('Error calculating price');
            }
        });
    });
    
    // Auto-check availability on page load if dates are set
    if ($('#checkin_date').val() && $('#checkout_date').val()) {
        checkAvailability();
    }
    
    // Settings page - validate email addresses
    $('#br_admin_emails').on('blur', function() {
        var emails = $(this).val().split(/[,\n]/);
        var invalidEmails = [];
        
        emails.forEach(function(email) {
            email = email.trim();
            if (email && !isValidEmail(email)) {
                invalidEmails.push(email);
            }
        });
        
        if (invalidEmails.length > 0) {
            $(this).after('<p class="error-message" style="color: red;">Invalid email(s): ' + invalidEmails.join(', ') + '</p>');
        } else {
            $(this).next('.error-message').remove();
        }
    });
    
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Enhance the bookings list table
    if ($('.wp-list-table').length) {
        // Add hover effect
        $('.wp-list-table tbody tr').hover(
            function() {
                $(this).addClass('hover');
            },
            function() {
                $(this).removeClass('hover');
            }
        );
        
        // Make entire row clickable
        $('.wp-list-table tbody tr').on('click', function(e) {
            if ($(e.target).is('a, button, input')) {
                return;
            }
            var editLink = $(this).find('a:contains("Edit")').attr('href');
            if (editLink) {
                window.location.href = editLink;
            }
        });
    }
    
    // Add loading overlay for form submissions
    $('form').on('submit', function() {
        var $submitButton = $(this).find('input[type="submit"]');
        $submitButton.prop('disabled', true).val('Processing...');
    });
    
    // Tooltips for service count
    $('[title]').tooltip();
});