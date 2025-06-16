jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize pricing calculator for all booking forms
    function initializePricingCalculator() {
        // Find all Gravity Forms with booking enabled
        $('.gform_wrapper').each(function() {
            var $form = $(this);
            var formId = $form.attr('id').replace('gform_wrapper_', '');
            
            // Check if this form has date fields
            var $checkinField = $form.find('input[type="date"][name*="checkin"], .ginput_container_date input').first();
            var $checkoutField = $form.find('input[type="date"][name*="checkout"], .ginput_container_date input').last();
            
            if ($checkinField.length && $checkoutField.length) {
                setupDateValidation($checkinField, $checkoutField);
                setupPricingCalculation($checkinField, $checkoutField, $form);
            }
        });
    }
    
    // Setup date validation
    function setupDateValidation($checkinField, $checkoutField) {
        // Set minimum date to today
        var today = new Date().toISOString().split('T')[0];
        $checkinField.attr('min', today);
        
        // Update checkout minimum when checkin changes
        $checkinField.on('change', function() {
            var checkinDate = new Date($(this).val());
            if (checkinDate) {
                // Set minimum checkout date (minimum stay)
                var minCheckout = new Date(checkinDate);
                minCheckout.setDate(minCheckout.getDate() + parseInt(br_pricing.minimum_days));
                
                var minCheckoutStr = minCheckout.toISOString().split('T')[0];
                $checkoutField.attr('min', minCheckoutStr);
                
                // If current checkout is before minimum, update it
                var currentCheckout = new Date($checkoutField.val());
                if (currentCheckout < minCheckout || !$checkoutField.val()) {
                    $checkoutField.val(minCheckoutStr);
                }
            }
        });
        
        // Validate minimum stay on checkout change
        $checkoutField.on('change', function() {
            validateMinimumStay($checkinField, $checkoutField);
        });
    }
    
    // Setup pricing calculation
    function setupPricingCalculation($checkinField, $checkoutField, $form) {
        var $pricingContainer = null;
        var calculatorHtml = `
            <div class="br-pricing-calculator" style="display: none;">
                <div class="br-pricing-header">
                    <h4>Pricing Summary</h4>
                </div>
                <div class="br-pricing-content">
                    <div class="br-pricing-row">
                        <span class="br-pricing-label">Check-in:</span>
                        <span class="br-pricing-value br-checkin-display">-</span>
                    </div>
                    <div class="br-pricing-row">
                        <span class="br-pricing-label">Check-out:</span>
                        <span class="br-pricing-value br-checkout-display">-</span>
                    </div>
                    <div class="br-pricing-row">
                        <span class="br-pricing-label">Duration:</span>
                        <span class="br-pricing-value br-duration-display">-</span>
                    </div>
                    <div class="br-pricing-row br-pricing-divider">
                        <span class="br-pricing-label">Weekly Rate:</span>
                        <span class="br-pricing-value br-weekly-rate">-</span>
                    </div>
                    <div class="br-pricing-row br-pricing-total">
                        <span class="br-pricing-label">Total Price:</span>
                        <span class="br-pricing-value br-total-price">-</span>
                    </div>
                </div>
                <div class="br-pricing-footer">
                    <p class="br-pricing-note">* Prices are subject to availability</p>
                </div>
            </div>
        `;
        
        // Insert pricing calculator after the form fields
        var $lastField = $checkoutField.closest('.gfield');
        if ($lastField.length) {
            $pricingContainer = $(calculatorHtml);
            $lastField.after($pricingContainer);
        }
        
        // Calculate pricing when dates change
        function calculatePricing() {
            var checkin = $checkinField.val();
            var checkout = $checkoutField.val();
            
            if (!checkin || !checkout) {
                if ($pricingContainer) {
                    $pricingContainer.slideUp();
                }
                return;
            }
            
            // Show loading state
            if ($pricingContainer) {
                $pricingContainer.find('.br-weekly-rate, .br-total-price').html('<span class="br-loading">Calculating...</span>');
                $pricingContainer.slideDown();
            }
            
            // Make AJAX request
            $.ajax({
                url: br_pricing.ajax_url,
                type: 'POST',
                data: {
                    action: 'br_calculate_price',
                    nonce: br_pricing.nonce,
                    checkin: checkin,
                    checkout: checkout
                },
                success: function(response) {
                    if (response.success) {
                        updatePricingDisplay(response.data, checkin, checkout);
                        
                        // Check availability
                        checkAvailability(checkin, checkout);
                    } else {
                        handlePricingError(response.data.message);
                    }
                },
                error: function() {
                    handlePricingError('Unable to calculate pricing. Please try again.');
                }
            });
        }
        
        // Update pricing display
        function updatePricingDisplay(data, checkin, checkout) {
            if (!$pricingContainer) return;
            
            var checkinDate = new Date(checkin);
            var checkoutDate = new Date(checkout);
            var nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
            
            $pricingContainer.find('.br-checkin-display').text(formatDate(checkinDate));
            $pricingContainer.find('.br-checkout-display').text(formatDate(checkoutDate));
            $pricingContainer.find('.br-duration-display').text(nights + ' ' + (nights === 1 ? 'night' : 'nights'));
            $pricingContainer.find('.br-weekly-rate').text(data.formatted_rate);
            $pricingContainer.find('.br-total-price').text(data.formatted_total);
            
            // Add success animation
            $pricingContainer.find('.br-pricing-content').addClass('br-pricing-updated');
            setTimeout(function() {
                $pricingContainer.find('.br-pricing-content').removeClass('br-pricing-updated');
            }, 300);
        }
        
        // Check availability
        function checkAvailability(checkin, checkout) {
            $.ajax({
                url: br_pricing.ajax_url,
                type: 'POST',
                data: {
                    action: 'br_check_availability',
                    nonce: br_pricing.nonce,
                    checkin: checkin,
                    checkout: checkout
                },
                success: function(response) {
                    if (!response.success) {
                        showAvailabilityWarning(response.data.message);
                    } else {
                        hideAvailabilityWarning();
                    }
                }
            });
        }
        
        // Handle pricing errors
        function handlePricingError(message) {
            if ($pricingContainer) {
                $pricingContainer.find('.br-pricing-content').html(
                    '<div class="br-pricing-error">' + message + '</div>'
                );
            }
        }
        
        // Show availability warning
        function showAvailabilityWarning(message) {
            var $warning = $form.find('.br-availability-warning');
            if (!$warning.length) {
                $warning = $('<div class="br-availability-warning"></div>');
                $pricingContainer.after($warning);
            }
            $warning.html('<p>' + message + '</p>').slideDown();
        }
        
        // Hide availability warning
        function hideAvailabilityWarning() {
            $form.find('.br-availability-warning').slideUp();
        }
        
        // Bind events
        $checkinField.on('change', calculatePricing);
        $checkoutField.on('change', calculatePricing);
        
        // Initial calculation if dates are already set
        if ($checkinField.val() && $checkoutField.val()) {
            calculatePricing();
        }
    }
    
    // Validate minimum stay
    function validateMinimumStay($checkinField, $checkoutField) {
        var checkin = new Date($checkinField.val());
        var checkout = new Date($checkoutField.val());
        
        if (checkin && checkout) {
            var nights = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
            var minimumDays = parseInt(br_pricing.minimum_days);
            
            if (nights < minimumDays) {
                showValidationError($checkoutField, 'Minimum stay is ' + minimumDays + ' nights');
                
                // Auto-correct the checkout date
                var minCheckout = new Date(checkin);
                minCheckout.setDate(minCheckout.getDate() + minimumDays);
                $checkoutField.val(minCheckout.toISOString().split('T')[0]);
            } else {
                hideValidationError($checkoutField);
            }
        }
    }
    
    // Show validation error
    function showValidationError($field, message) {
        var $container = $field.closest('.ginput_container');
        var $error = $container.find('.br-validation-error');
        
        if (!$error.length) {
            $error = $('<div class="br-validation-error"></div>');
            $container.append($error);
        }
        
        $error.text(message).show();
    }
    
    // Hide validation error
    function hideValidationError($field) {
        $field.closest('.ginput_container').find('.br-validation-error').hide();
    }
    
    // Format date for display
    function formatDate(date) {
        var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Initialize on page load
    initializePricingCalculator();
    
    // Reinitialize on Gravity Forms render (for AJAX forms)
    $(document).on('gform_post_render', function() {
        initializePricingCalculator();
    });
    
    // Handle Elementor popup forms
    $(document).on('elementor/popup/show', function() {
        setTimeout(initializePricingCalculator, 100);
    });
});