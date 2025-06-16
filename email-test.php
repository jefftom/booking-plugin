<?php
/**
 * Email Test Script for Booking Requests Plugin
 * 
 * Upload this file to your plugin directory and access it directly to test emails
 * URL: https://yourdomain.com/wp-content/plugins/booking-requests/email-test.php
 */

// Load WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Test mode
$test_mode = isset($_GET['test']) ? $_GET['test'] : '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Requests - Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .test-section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #fff; padding: 10px; overflow-x: auto; }
        a.button { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; margin: 10px 10px 10px 0; }
    </style>
</head>
<body>
    <h1>Booking Requests - Email Test</h1>
    
    <div class="test-section">
        <h2>Email Configuration</h2>
        <p><strong>Admin Email:</strong> <?php echo get_option('br_admin_email', get_option('admin_email')); ?></p>
        <p><strong>WordPress Admin Email:</strong> <?php echo get_option('admin_email'); ?></p>
        <p><strong>Site Name:</strong> <?php echo get_bloginfo('name'); ?></p>
    </div>
    
    <div class="test-section">
        <h2>Available Tests</h2>
        <a href="?test=simple" class="button">Test Simple Email</a>
        <a href="?test=create_booking" class="button">Create Test Booking</a>
        <a href="?test=admin_notification" class="button">Test Admin Notification</a>
        <a href="?test=guest_confirmation" class="button">Test Guest Confirmation</a>
    </div>
    
    <?php if ($test_mode): ?>
        <div class="test-section">
            <h2>Test Results</h2>
            <?php
            switch($test_mode) {
                case 'simple':
                    $to = get_option('admin_email');
                    $subject = 'Booking Requests - Simple Email Test';
                    $body = 'This is a simple test email from the Booking Requests plugin.';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    
                    $sent = wp_mail($to, $subject, $body, $headers);
                    
                    if ($sent) {
                        echo '<p class="success">✓ Simple email sent successfully to ' . $to . '</p>';
                    } else {
                        echo '<p class="error">✗ Failed to send simple email</p>';
                        
                        // Check for mail errors
                        global $phpmailer;
                        if (isset($phpmailer->ErrorInfo) && !empty($phpmailer->ErrorInfo)) {
                            echo '<p class="error">Error: ' . $phpmailer->ErrorInfo . '</p>';
                        }
                    }
                    break;
                    
                case 'create_booking':
                    // Create a test booking
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'br_bookings';
                    
                    $booking_data = array(
                        'guest_name' => 'Test Guest',
                        'email' => get_option('admin_email'),
                        'phone' => '123-456-7890',
                        'checkin_date' => date('Y-m-d', strtotime('next Sunday')),
                        'checkout_date' => date('Y-m-d', strtotime('next Sunday +6 days')),
                        'total_price' => 4995,
                        'message' => 'This is a test booking created by the email test script.',
                        'status' => 'pending',
                        'created_at' => current_time('mysql'),
                        'booking_data' => maybe_serialize(array(
                            'weeks' => array(
                                array(
                                    'start' => date('Y-m-d', strtotime('next Sunday')),
                                    'end' => date('Y-m-d', strtotime('next Sunday +6 days')),
                                    'rate' => 4995
                                )
                            ),
                            'form_source' => 'test_script'
                        ))
                    );
                    
                    $inserted = $wpdb->insert($table_name, $booking_data);
                    
                    if ($inserted) {
                        $booking_id = $wpdb->insert_id;
                        echo '<p class="success">✓ Test booking created with ID: ' . $booking_id . '</p>';
                        
                        // Trigger emails
                        do_action('br_send_admin_notification', $booking_id);
                        do_action('br_send_guest_confirmation', $booking_id);
                        
                        echo '<p>Admin notification and guest confirmation emails have been triggered.</p>';
                    } else {
                        echo '<p class="error">✗ Failed to create test booking</p>';
                        echo '<p class="error">Database error: ' . $wpdb->last_error . '</p>';
                    }
                    break;
                    
                case 'admin_notification':
                    // Find the latest booking
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'br_bookings';
                    $latest_booking = $wpdb->get_row("SELECT id FROM $table_name ORDER BY id DESC LIMIT 1");
                    
                    if ($latest_booking) {
                        do_action('br_send_admin_notification', $latest_booking->id);
                        echo '<p class="success">✓ Admin notification triggered for booking #' . $latest_booking->id . '</p>';
                    } else {
                        echo '<p class="error">✗ No bookings found. Create a test booking first.</p>';
                    }
                    break;
                    
                case 'guest_confirmation':
                    // Find the latest booking
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'br_bookings';
                    $latest_booking = $wpdb->get_row("SELECT id FROM $table_name ORDER BY id DESC LIMIT 1");
                    
                    if ($latest_booking) {
                        do_action('br_send_guest_confirmation', $latest_booking->id);
                        echo '<p class="success">✓ Guest confirmation triggered for booking #' . $latest_booking->id . '</p>';
                    } else {
                        echo '<p class="error">✗ No bookings found. Create a test booking first.</p>';
                    }
                    break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="test-section">
        <h2>Debug Information</h2>
        <h3>Active Plugins:</h3>
        <pre><?php
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin) {
            echo $plugin . "\n";
        }
        ?></pre>
        
        <h3>Email Actions Registered:</h3>
        <pre><?php
        global $wp_filter;
        
        echo "br_send_admin_notification hooks:\n";
        if (isset($wp_filter['br_send_admin_notification'])) {
            foreach ($wp_filter['br_send_admin_notification'] as $priority => $hooks) {
                echo "  Priority $priority:\n";
                foreach ($hooks as $hook) {
                    if (is_array($hook['function'])) {
                        echo "    - " . get_class($hook['function'][0]) . '::' . $hook['function'][1] . "\n";
                    } else {
                        echo "    - " . $hook['function'] . "\n";
                    }
                }
            }
        } else {
            echo "  No hooks registered\n";
        }
        
        echo "\nbr_send_guest_confirmation hooks:\n";
        if (isset($wp_filter['br_send_guest_confirmation'])) {
            foreach ($wp_filter['br_send_guest_confirmation'] as $priority => $hooks) {
                echo "  Priority $priority:\n";
                foreach ($hooks as $hook) {
                    if (is_array($hook['function'])) {
                        echo "    - " . get_class($hook['function'][0]) . '::' . $hook['function'][1] . "\n";
                    } else {
                        echo "    - " . $hook['function'] . "\n";
                    }
                }
            }
        } else {
            echo "  No hooks registered\n";
        }
        ?></pre>
    </div>
    
    <p><a href="<?php echo admin_url('admin.php?page=booking-requests'); ?>">← Back to Booking Requests</a></p>
</body>
</html>