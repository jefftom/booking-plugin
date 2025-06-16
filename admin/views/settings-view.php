<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display any settings errors
settings_errors('br_settings');
?>

<div class="wrap">
    <h1>Booking Requests Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('br_settings', 'br_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="br_admin_emails">Admin Notification Email</label>
                </th>
                <td>
                    <input type="email" name="br_admin_emails" id="br_admin_emails" 
                           value="<?php echo esc_attr(get_option('br_admin_emails', get_option('admin_email'))); ?>" 
                           class="regular-text" />
                    <p class="description">Email address where booking notifications will be sent.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="br_week_start_day">Week Start Day</label>
                </th>
                <td>
                    <select name="br_week_start_day" id="br_week_start_day">
                        <option value="sunday" <?php selected(get_option('br_week_start_day'), 'sunday'); ?>>Sunday</option>
                        <option value="saturday" <?php selected(get_option('br_week_start_day'), 'saturday'); ?>>Saturday</option>
                    </select>
                    <p class="description">Select which day of the week bookings should start on.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="br_approval_required">Approval Required</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="br_approval_required" id="br_approval_required" value="1" 
                               <?php checked(get_option('br_approval_required'), '1'); ?> />
                        Bookings require admin approval
                    </label>
                    <p class="description">If checked, bookings will be pending until approved by admin.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="br_calendar_months_to_show">Months to Show</label>
                </th>
                <td>
                    <input type="number" name="br_calendar_months_to_show" id="br_calendar_months_to_show" 
                           value="<?php echo esc_attr(get_option('br_calendar_months_to_show', '12')); ?>" 
                           min="1" max="24" class="small-text" />
                    <p class="description">Number of months to display in the calendar.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="br_calendar_min_advance_days">Minimum Advance Days</label>
                </th>
                <td>
                    <input type="number" name="br_calendar_min_advance_days" id="br_calendar_min_advance_days" 
                           value="<?php echo esc_attr(get_option('br_calendar_min_advance_days', '1')); ?>" 
                           min="0" max="30" class="small-text" />
                    <p class="description">Minimum number of days in advance bookings can be made.</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Save Settings'); ?>
    </form>
</div>