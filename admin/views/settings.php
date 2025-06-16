<?php
/**
 * Settings Page View
 */

// Handle form submission BEFORE any output
if (isset($_POST['submit']) && isset($_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'br_settings_nonce')) {
        // Save all settings
        update_option('br_email_from', sanitize_email($_POST['email_from']));
        update_option('br_email_from_name', sanitize_text_field($_POST['email_from_name']));
        update_option('br_minimum_days', intval($_POST['minimum_days']));
        update_option('br_admin_emails', sanitize_textarea_field($_POST['admin_emails']));
        update_option('br_week_start_day', sanitize_text_field($_POST['week_start_day']));
        update_option('br_min_advance_days', intval($_POST['min_advance_days']));
        
        $saved = true;
    } else {
        $error = __('Security check failed. Please try again.', 'booking-requests');
    }
}
?>

<div class="wrap">
    <h1><?php _e('Booking Requests Settings', 'booking-requests'); ?></h1>
    
    <?php if (isset($saved) && $saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully!', 'booking-requests'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('br_settings_nonce', '_wpnonce'); ?>
        
        <!-- Email Settings -->
        <div class="br-settings-section">
            <h2><?php _e('Email Settings', 'booking-requests'); ?></h2>
            
            <table class="form-table br-settings">
                <tr>
                    <th scope="row">
                        <label for="email_from"><?php _e('From Email', 'booking-requests'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="email_from" name="email_from" 
                               value="<?php echo esc_attr(get_option('br_email_from', get_option('admin_email'))); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('Email address that booking emails will be sent from.', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="email_from_name"><?php _e('From Name', 'booking-requests'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="email_from_name" name="email_from_name" 
                               value="<?php echo esc_attr(get_option('br_email_from_name', get_bloginfo('name'))); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('Name that will appear as the sender of booking emails.', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="admin_emails"><?php _e('Admin Notification Emails', 'booking-requests'); ?></label>
                    </th>
                    <td>
                        <textarea id="admin_emails" name="admin_emails" rows="3" class="large-text"><?php 
                            echo esc_textarea(get_option('br_admin_emails', get_option('admin_email'))); 
                        ?></textarea>
                        <p class="description">
                            <?php _e('Email addresses that will receive new booking notifications. Separate multiple emails with commas.', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Booking Settings -->
        <div class="br-settings-section">
            <h2><?php _e('Booking Settings', 'booking-requests'); ?></h2>
            
            <table class="form-table br-settings">
                <tr>
                    <th scope="row">
                        <label for="minimum_days"><?php _e('Minimum Stay (Days)', 'booking-requests'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="minimum_days" name="minimum_days" 
                               value="<?php echo esc_attr(get_option('br_minimum_days', 7)); ?>" 
                               min="1" max="30" class="small-text" />
                        <p class="description">
                            <?php _e('Minimum number of days required for a booking.', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="week_start_day"><?php _e('Week Start Day', 'booking-requests'); ?></label>
                    </th>
                    <td>
                        <?php
                        $current_day = get_option('br_week_start_day', 'saturday');
                        ?>
                        <select id="week_start_day" name="week_start_day">
                            <?php
                            $days = array(
                                'saturday' => __('Saturday', 'booking-requests'),
                                'sunday' => __('Sunday', 'booking-requests'),
                                'monday' => __('Monday', 'booking-requests')
                            );
                            foreach ($days as $value => $label):
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_day, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Which day should weekly rentals start on? (Most vacation rentals use Saturday)', 'booking-requests'); ?>
                        </p>
                        <p class="description" style="color: #666;">
                            <?php printf(__('Current saved value: %s', 'booking-requests'), '<strong>' . esc_html($current_day) . '</strong>'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="min_advance_days"><?php _e('Minimum Advance Booking (Days)', 'booking-requests'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="min_advance_days" name="min_advance_days" 
                               value="<?php echo esc_attr(get_option('br_min_advance_days', 1)); ?>" 
                               min="0" max="30" class="small-text" />
                        <p class="description">
                            <?php _e('How many days in advance must bookings be made?', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Test Email Section -->
        <div class="br-settings-section">
            <h2><?php _e('Test Emails', 'booking-requests'); ?></h2>
            
            <table class="form-table br-settings">
                <tr>
                    <th scope="row">
                        <?php _e('Send Test Email', 'booking-requests'); ?>
                    </th>
                    <td>
                        <div class="br-test-email-controls">
                            <select id="test_email_type">
                                <option value="admin_notification"><?php _e('Admin Notification', 'booking-requests'); ?></option>
                                <option value="approval"><?php _e('Guest Approval', 'booking-requests'); ?></option>
                                <option value="denial"><?php _e('Guest Denial', 'booking-requests'); ?></option>
                            </select>
                            
                            <input type="email" id="test_email_recipient" 
                                   placeholder="<?php _e('Recipient email', 'booking-requests'); ?>" 
                                   value="<?php echo esc_attr(get_option('admin_email')); ?>" />
                            
                            <button type="button" class="button" id="send_test_email">
                                <?php _e('Send Test', 'booking-requests'); ?>
                            </button>
                            
                            <span class="br-test-email-status"></span>
                        </div>
                        <p class="description">
                            <?php _e('Send a test email to verify your email settings are working correctly.', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Custom Calendar Section -->
        <div class="br-settings-section">
            <h2><?php _e('Custom Calendar', 'booking-requests'); ?></h2>
            
            <p><?php _e('Use the custom booking calendar to display a full year of availability with week-based selection.', 'booking-requests'); ?></p>
            
            <table class="form-table br-settings">
                <tr>
                    <th scope="row">
                        <?php _e('Shortcode', 'booking-requests'); ?>
                    </th>
                    <td>
                        <code>[booking_calendar]</code>
                        <p class="description">
                            <?php _e('Basic calendar with default settings', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Shortcode with Options', 'booking-requests'); ?>
                    </th>
                    <td>
                        <code>[booking_calendar months="12" start_day="sunday" show_form="yes"]</code>
                        <p class="description">
                            <?php _e('Parameters:', 'booking-requests'); ?><br>
                            • <strong>months</strong>: <?php _e('Number of months to display (1-24)', 'booking-requests'); ?><br>
                            • <strong>start_day</strong>: <?php _e('Week start day (saturday/sunday/monday)', 'booking-requests'); ?><br>
                            • <strong>show_form</strong>: <?php _e('Show booking form (yes/no)', 'booking-requests'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Elementor Widget', 'booking-requests'); ?>
                    </th>
                    <td>
                        <p><?php _e('Look for "Booking Calendar" in the Elementor widgets panel.', 'booking-requests'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Pricing Periods -->
        <div class="br-settings-section">
            <h2><?php _e('Pricing Periods', 'booking-requests'); ?></h2>
            
            <p><?php _e('Current pricing periods are defined in the plugin code. Future versions will allow editing these through the interface.', 'booking-requests'); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Start Date', 'booking-requests'); ?></th>
                        <th><?php _e('End Date', 'booking-requests'); ?></th>
                        <th><?php _e('Weekly Rate', 'booking-requests'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pricing_periods = array(
                        array('start' => '2025-07-05', 'end' => '2025-09-02', 'rate' => 8495),
                        array('start' => '2025-09-03', 'end' => '2025-09-09', 'rate' => 7495),
                        array('start' => '2025-09-10', 'end' => '2025-09-16', 'rate' => 6495),
                        array('start' => '2025-09-17', 'end' => '2025-10-28', 'rate' => 5695),
                        array('start' => '2025-10-29', 'end' => '2026-03-23', 'rate' => 4995),
                        array('start' => '2026-03-24', 'end' => '2026-06-15', 'rate' => 5695),
                        array('start' => '2026-06-16', 'end' => '2026-06-29', 'rate' => 6495),
                        array('start' => '2026-06-30', 'end' => '2026-07-06', 'rate' => 7495),
                        array('start' => '2026-07-07', 'end' => '2026-08-31', 'rate' => 8395),
                        array('start' => '2026-09-01', 'end' => '2026-09-07', 'rate' => 7495),
                        array('start' => '2026-09-08', 'end' => '2026-09-14', 'rate' => 6495),
                        array('start' => '2026-09-15', 'end' => '2026-10-26', 'rate' => 5695),
                        array('start' => '2026-10-27', 'end' => '2027-03-22', 'rate' => 5295)
                    );
                    
                    foreach ($pricing_periods as $period):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($period['start'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($period['end'])); ?></td>
                            <td><?php echo br_format_price($period['rate']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" 
                   value="<?php _e('Save Settings', 'booking-requests'); ?>">
        </p>
        
        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc;">
            <h4>Debug Info:</h4>
            <pre><?php 
            echo "br_week_start_day: " . get_option('br_week_start_day', 'not set') . "\n";
            echo "POST data: " . print_r($_POST, true);
            ?></pre>
        </div>
        <?php endif; ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Test email functionality
    $('#send_test_email').on('click', function() {
        var $button = $(this);
        var $status = $('.br-test-email-status');
        var type = $('#test_email_type').val();
        var recipient = $('#test_email_recipient').val();
        
        if (!recipient) {
            alert('<?php _e('Please enter a recipient email address', 'booking-requests'); ?>');
            return;
        }
        
        $button.prop('disabled', true);
        $status.html('<span class="spinner is-active" style="float: none;"></span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'br_send_test_email',
                nonce: '<?php echo wp_create_nonce('br_test_email'); ?>',
                email_type: type,
                recipient: recipient
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<span style="color: #46b450;">✓ <?php _e('Email sent successfully!', 'booking-requests'); ?></span>');
                } else {
                    $status.html('<span style="color: #dc3232;">✗ <?php _e('Failed to send email', 'booking-requests'); ?></span>');
                }
            },
            error: function() {
                $status.html('<span style="color: #dc3232;">✗ <?php _e('Error sending email', 'booking-requests'); ?></span>');
            },
            complete: function() {
                $button.prop('disabled', false);
                setTimeout(function() {
                    $status.html('');
                }, 5000);
            }
        });
    });
});
</script>