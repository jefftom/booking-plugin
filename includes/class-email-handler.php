<?php

class BR_Email_Handler {
    
    public function __construct() {
        add_action('br_send_admin_notification', array($this, 'send_admin_notification'), 10, 1);
        add_action('br_send_guest_confirmation', array($this, 'send_guest_confirmation'), 10, 1);
        add_action('br_send_approval_email', array($this, 'send_approval_email'), 10, 1);
        add_action('br_send_denial_email', array($this, 'send_denial_email'), 10, 1);
        
        // Handle approve/deny actions from email links
        add_action('init', array($this, 'handle_email_actions'));
    }
    
    /**
     * Handle approve/deny actions from email links
     */
    public function handle_email_actions() {
        if (!isset($_GET['br_action']) || !isset($_GET['booking_id']) || !isset($_GET['token'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['br_action']);
        $booking_id = intval($_GET['booking_id']);
        $token = sanitize_text_field($_GET['token']);
        
        // Verify token
        $expected_token = wp_hash($booking_id . 'br_booking_action');
        if ($token !== $expected_token) {
            wp_die('Invalid token');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        
        if ($action === 'approve') {
            $wpdb->update(
                $table_name,
                array('status' => 'approved'),
                array('id' => $booking_id)
            );
            
            // Send approval email
            do_action('br_send_approval_email', $booking_id);
            
            // Redirect to success page
            wp_redirect(add_query_arg('br_status', 'approved', home_url()));
            exit;
            
        } elseif ($action === 'deny') {
            $wpdb->update(
                $table_name,
                array('status' => 'denied'),
                array('id' => $booking_id)
            );
            
            // Send denial email
            do_action('br_send_denial_email', $booking_id);
            
            // Redirect to success page
            wp_redirect(add_query_arg('br_status', 'denied', home_url()));
            exit;
        }
    }
    
    /**
     * Send notification to admin when new booking is created
     */
    public function send_admin_notification($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        if (!$booking) {
            error_log('BR Email Handler: Invalid booking ID ' . $booking_id);
            return false;
        }
        
        // Get the BR admin email setting - NOTE: using br_admin_emails (plural)
        $admin_email = get_option('br_admin_emails', '');
        error_log('BR Email Handler: br_admin_emails option value: "' . $admin_email . '"');
        
        // Only use WordPress admin email if BR setting is completely empty
        if (empty(trim($admin_email))) {
            $admin_email = get_option('admin_email');
            error_log('BR Email Handler: Using WordPress admin email as fallback: ' . $admin_email);
        } else {
            error_log('BR Email Handler: Using BR admin email: ' . $admin_email);
        }
        
        $subject = 'New Booking Request - ' . $booking['guest_name'] . ' (' . date('m/d/Y', strtotime($booking['checkin_date'])) . ' to ' . date('m/d/Y', strtotime($booking['checkout_date'])) . ')';
        
        // Format the booking data properly
        $booking_data = $booking['booking_data'];
        $weeks_info = '';
        
        if (isset($booking_data['weeks']) && is_array($booking_data['weeks'])) {
            $weeks_info = "\n**Selected Weeks:**\n";
            foreach ($booking_data['weeks'] as $week) {
                $start = date('D, M j, Y', strtotime($week['start']));
                $end = date('D, M j, Y', strtotime($week['end']));
                $rate = number_format($week['rate'], 0, ',', '.');
                $weeks_info .= "- {$start} to {$end} - €{$rate}\n";
            }
        }
        
        // Generate approve/deny links with token
        $token = wp_hash($booking_id . 'br_booking_action');
        $approve_url = add_query_arg(array(
            'br_action' => 'approve',
            'booking_id' => $booking_id,
            'token' => $token
        ), home_url());
        
        $deny_url = add_query_arg(array(
            'br_action' => 'deny',
            'booking_id' => $booking_id,
            'token' => $token
        ), home_url());
        
        $body = $this->get_email_template('admin-notification', array_merge($booking, array(
            'weeks_info' => $weeks_info,
            'approve_url' => $approve_url,
            'deny_url' => $deny_url
        )));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        error_log('BR Email Handler: Attempting to send admin email to: ' . $admin_email);
        $sent = wp_mail($admin_email, $subject, $body, $headers);
        error_log('BR Email Handler: Email sent status: ' . ($sent ? 'SUCCESS' : 'FAILED'));
        
        return $sent;
    }
    
    /**
     * Send confirmation email to guest after booking
     */
    public function send_guest_confirmation($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        if (!$booking) {
            return false;
        }
        
        $subject = 'Booking Request Received - ' . $booking['guest_name'];
        
        // Don't include pricing in guest confirmation
        $booking['weeks_info'] = '';
        $booking['total_price'] = '';
        
        $body = $this->get_email_template('guest-confirmation', $booking);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($booking['email'], $subject, $body, $headers);
    }
    
    /**
     * Send approval email to guest
     */
    public function send_approval_email($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        if (!$booking) {
            return false;
        }
        
        $subject = 'Your Booking Request Has Been Approved!';
        
        // Format weeks info
        $booking_data = $booking['booking_data'];
        $weeks_info = '';
        $total_price = 0;
        
        if (isset($booking_data['weeks']) && is_array($booking_data['weeks'])) {
            $weeks_info = "<ul>";
            foreach ($booking_data['weeks'] as $week) {
                $start = date('D, M j, Y', strtotime($week['start']));
                $end = date('D, M j, Y', strtotime($week['end']));
                $rate = number_format($week['rate'], 0, ',', '.');
                $weeks_info .= "<li>{$start} to {$end} - €{$rate}</li>";
                $total_price += $week['rate'];
            }
            $weeks_info .= "</ul>";
        }
        
        $booking['weeks_info'] = $weeks_info;
        $booking['total_price'] = '€' . number_format($total_price, 0, ',', '.');
        
        $body = $this->get_email_template('approval-email', $booking);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($booking['email'], $subject, $body, $headers);
    }
    
    /**
     * Send denial email to guest
     */
    public function send_denial_email($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        if (!$booking) {
            return false;
        }
        
        $subject = 'Update on Your Booking Request';
        $body = $this->get_email_template('denial-email', $booking);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($booking['email'], $subject, $body, $headers);
    }
    
    /**
     * Get booking data
     */
    private function get_booking_data($booking_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'br_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return false;
        }
        
        // Parse booking data
        $booking['booking_data'] = maybe_unserialize($booking['booking_data']);
        
        // Format dates
        $booking['checkin_formatted'] = date('l, F j, Y', strtotime($booking['checkin_date']));
        $booking['checkout_formatted'] = date('l, F j, Y', strtotime($booking['checkout_date']));
        
        // Calculate nights
        $checkin = new DateTime($booking['checkin_date']);
        $checkout = new DateTime($booking['checkout_date']);
        $booking['nights'] = $checkin->diff($checkout)->days;
        
        // Format price
        $booking['total_price_formatted'] = '€' . number_format($booking['total_price'], 0, ',', '.');
        
        return $booking;
    }
    
    /**
     * Get email template
     */
    private function get_email_template($template, $data) {
        // Try multiple possible locations for templates
        $possible_paths = array(
            BR_PLUGIN_DIR . 'includes/templates/' . $template . '-email.php',
            BR_PLUGIN_DIR . 'includes/templates/' . $template . '.php',
            BR_PLUGIN_DIR . 'templates/emails/' . $template . '.php',
            BR_PLUGIN_DIR . 'templates/' . $template . '.php'
        );
        
        $template_path = '';
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $template_path = $path;
                break;
            }
        }
        
        if (empty($template_path)) {
            error_log('BR Email Handler: Template not found in any location for: ' . $template);
            error_log('BR Email Handler: Tried paths: ' . implode(', ', $possible_paths));
            return $this->get_fallback_template($template, $data);
        }
        
        ob_start();
        extract($data);
        include $template_path;
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Get fallback template
     */
    private function get_fallback_template($template, $data) {
        switch ($template) {
            case 'admin-notification':
                return $this->get_fallback_admin_notification($data);
            case 'guest-confirmation':
                return $this->get_fallback_guest_confirmation($data);
            case 'approval-email':
                return $this->get_fallback_approval_email($data);
            case 'denial-email':
                return $this->get_fallback_denial_email($data);
            default:
                return '';
        }
    }
    
    /**
     * Fallback templates
     */
    private function get_fallback_admin_notification($data) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Booking Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>New Booking Request</h2>
        <p><strong>Guest:</strong> ' . esc_html($data['guest_name']) . '</p>
        <p><strong>Email:</strong> ' . esc_html($data['email']) . '</p>
        <p><strong>Phone:</strong> ' . esc_html($data['phone']) . '</p>
        <p><strong>Dates:</strong> ' . esc_html($data['checkin_formatted']) . ' to ' . esc_html($data['checkout_formatted']) . ' (' . $data['nights'] . ' nights)</p>
        ' . $data['weeks_info'] . '
        <p><strong>Total Price:</strong> ' . esc_html($data['total_price_formatted']) . '</p>';
        
        if (!empty($data['message'])) {
            $html .= '<p><strong>Message:</strong> ' . nl2br(esc_html($data['message'])) . '</p>';
        }
        
        $html .= '<p style="margin-top: 30px;">
            <a href="' . esc_url($data['approve_url']) . '" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Approve Booking</a>
            <a href="' . esc_url($data['deny_url']) . '" style="background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none;">Deny Booking</a>
        </p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    private function get_fallback_guest_confirmation($data) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Request Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Thank You for Your Booking Request!</h2>
        <p>Dear ' . esc_html($data['guest_name']) . ',</p>
        <p>We have received your booking request and will review it shortly. You will receive an email once your booking has been processed.</p>
        
        <h3>Booking Details:</h3>
        <p><strong>Check-in:</strong> ' . esc_html($data['checkin_formatted']) . '</p>
        <p><strong>Check-out:</strong> ' . esc_html($data['checkout_formatted']) . '</p>
        <p><strong>Total Nights:</strong> ' . $data['nights'] . '</p>
        ' . $data['weeks_info'] . '
        <p><strong>Total Price:</strong> ' . esc_html($data['total_price']) . '</p>
        
        <p>If you have any questions, please don\'t hesitate to contact us.</p>
        
        <p>Best regards,<br>' . get_bloginfo('name') . '</p>
    </div>
</body>
</html>';
    }
    
    private function get_fallback_approval_email($data) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Your Booking Has Been Approved!</h2>
        <p>Dear ' . esc_html($data['guest_name']) . ',</p>
        <p>Great news! Your booking request has been approved.</p>
        
        <h3>Confirmed Booking Details:</h3>
        <p><strong>Check-in:</strong> ' . esc_html($data['checkin_formatted']) . '</p>
        <p><strong>Check-out:</strong> ' . esc_html($data['checkout_formatted']) . '</p>
        <p><strong>Total Nights:</strong> ' . $data['nights'] . '</p>
        ' . $data['weeks_info'] . '
        <p><strong>Total Price:</strong> ' . esc_html($data['total_price']) . '</p>
        
        <p>We look forward to welcoming you!</p>
        
        <p>Best regards,<br>' . get_bloginfo('name') . '</p>
    </div>
</body>
</html>';
    }
    
    private function get_fallback_denial_email($data) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Update on Your Booking Request</h2>
        <p>Dear ' . esc_html($data['guest_name']) . ',</p>
        <p>Thank you for your interest in booking with us. Unfortunately, we are unable to accommodate your request for the selected dates.</p>
        
        <p>We encourage you to check our availability for alternative dates. We would love to welcome you at another time.</p>
        
        <p>If you have any questions, please feel free to contact us.</p>
        
        <p>Best regards,<br>' . get_bloginfo('name') . '</p>
    </div>
</body>
</html>';
    }
}