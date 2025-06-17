<?php

class BR_Email_Handler {
    
    private $service_labels = array(
        'taxi_service' => 'Taxi service from and to the airport',
        'greeting_service' => 'Greeting service – to welcome you to the Villa and overview the region',
        'welcome_hamper' => 'Welcome hamper on arrival',
        'sailing' => 'Sailing',
        'motorboat_hire' => 'Motorboat hire',
        'flight_bookings' => 'Flight bookings (from the UK)',
        'car_hire' => 'Car hire booking service'
    );
    
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
        
        if ($action === 'approve') {
            BR_Database::update_booking_status($booking_id, 'approved');
            do_action('br_send_approval_email', $booking_id);
            wp_redirect(add_query_arg('br_status', 'approved', home_url()));
            exit;
            
        } elseif ($action === 'deny') {
            BR_Database::update_booking_status($booking_id, 'denied');
            do_action('br_send_denial_email', $booking_id);
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
            return false;
        }
        
        // Get admin emails
        $admin_emails = get_option('br_admin_emails', get_option('admin_email'));
        
        // Convert to array if multiple emails (comma or newline separated)
        if (strpos($admin_emails, ',') !== false || strpos($admin_emails, "\n") !== false) {
            $admin_emails = preg_split('/[,\n]/', $admin_emails);
            $admin_emails = array_map('trim', $admin_emails);
            $admin_emails = array_filter($admin_emails, 'is_email');
        }
        
        $subject = sprintf(
            'New Booking Request - %s (%s to %s)',
            $booking['guest_name'],
            date('m/d/Y', strtotime($booking['checkin_date'])),
            date('m/d/Y', strtotime($booking['checkout_date']))
        );
        
        // Generate approve/deny links
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
        
        $booking['approve_url'] = $approve_url;
        $booking['deny_url'] = $deny_url;
        
        $body = $this->get_email_template('admin-notification', $booking);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($admin_emails, $subject, $body, $headers);
    }
    
    /**
     * Send confirmation email to guest after booking
     */
    public function send_guest_confirmation($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        if (!$booking) {
            return false;
        }
        
        $subject = 'Booking Request Received - ' . get_bloginfo('name');
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
        $booking = BR_Database::get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Convert to array
        $booking_array = (array) $booking;
        
        // Parse booking data
        $booking_array['booking_data'] = maybe_unserialize($booking_array['booking_data']);
        $booking_array['additional_services'] = maybe_unserialize($booking_array['additional_services']);
        
        // Format dates
        $booking_array['checkin_formatted'] = date('l, F j, Y', strtotime($booking_array['checkin_date']));
        $booking_array['checkout_formatted'] = date('l, F j, Y', strtotime($booking_array['checkout_date']));
        
        // Calculate nights
        $checkin = new DateTime($booking_array['checkin_date']);
        $checkout = new DateTime($booking_array['checkout_date']);
        $booking_array['nights'] = $checkin->diff($checkout)->days;
        
        // Format price
        $booking_array['total_price_formatted'] = '€' . number_format($booking_array['total_price'], 0, '.', ',');
        
        // Format additional services
        $booking_array['services_html'] = '';
        if (!empty($booking_array['additional_services']) && is_array($booking_array['additional_services'])) {
            $services_list = array();
            foreach ($booking_array['additional_services'] as $service) {
                if (isset($this->service_labels[$service])) {
                    $services_list[] = $this->service_labels[$service];
                }
            }
            if (!empty($services_list)) {
                $booking_array['services_html'] = '<ul><li>' . implode('</li><li>', $services_list) . '</li></ul>';
            }
        }
        
        return $booking_array;
    }
    
    /**
     * Get email template
     */
    private function get_email_template($template, $data) {
        // Check if we have a saved template
        $saved_template = '';
        switch ($template) {
            case 'admin-notification':
                $saved_template = get_option('br_email_template_admin_notification', '');
                break;
            case 'guest-confirmation':
                $saved_template = get_option('br_email_template_guest_confirmation', '');
                break;
            case 'approval-email':
                $saved_template = get_option('br_email_template_approval', '');
                break;
            case 'denial-email':
                $saved_template = get_option('br_email_template_denial', '');
                break;
        }
        
        if (!empty($saved_template)) {
            // Use saved template with placeholders
            return $this->parse_template($saved_template, $data);
        }
        
        // Otherwise use default template
        extract($data);
        ob_start();
        
        // Get template content
        switch ($template) {
            case 'admin-notification':
                ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Booking Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e9ecef; border-radius: 0 0 8px 8px; }
        .booking-details { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .detail-label { font-weight: bold; color: #6c757d; }
        .btn { display: inline-block; padding: 12px 30px; margin: 0 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-approve { background-color: #28a745; color: white; }
        .btn-deny { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Booking Request</h1>
    </div>
    <div class="content">
        <div class="booking-details">
            <h3>Guest Information</h3>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span><?php echo esc_html($guest_name); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span><?php echo esc_html($email); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span><?php echo esc_html($phone); ?></span>
            </div>
        </div>
        
        <div class="booking-details">
            <h3>Booking Details</h3>
            <div class="detail-row">
                <span class="detail-label">Check-in:</span>
                <span><?php echo esc_html($checkin_formatted); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-out:</span>
                <span><?php echo esc_html($checkout_formatted); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Nights:</span>
                <span><?php echo $nights; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Price:</span>
                <span style="font-size: 20px; color: #28a745;"><?php echo esc_html($total_price_formatted); ?></span>
            </div>
        </div>
        
        <?php if (!empty($services_html)): ?>
        <div class="booking-details">
            <h3>Additional Services Requested</h3>
            <?php echo $services_html; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
        <div class="booking-details">
            <h3>Guest Message</h3>
            <p><?php echo nl2br(esc_html($message)); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($approve_url); ?>" class="btn btn-approve">Approve Booking</a>
            <a href="<?php echo esc_url($deny_url); ?>" class="btn btn-deny">Deny Booking</a>
        </div>
        
        <p style="text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=booking-requests-edit&booking_id=' . $id); ?>">View in Admin Dashboard</a>
        </p>
    </div>
</body>
</html>
                <?php
                break;
                
            case 'guest-confirmation':
                ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e9ecef; border-radius: 0 0 8px 8px; }
        .booking-details { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thank You for Your Booking Request!</h1>
    </div>
    <div class="content">
        <p>Dear <?php echo esc_html($guest_name); ?>,</p>
        <p>We have received your booking request and will review it shortly. You will receive an email once your booking has been processed.</p>
        
        <div class="booking-details">
            <h3>Your Booking Details</h3>
            <p><strong>Check-in:</strong> <?php echo esc_html($checkin_formatted); ?></p>
            <p><strong>Check-out:</strong> <?php echo esc_html($checkout_formatted); ?></p>
            <p><strong>Total Nights:</strong> <?php echo $nights; ?></p>
            <p><strong>Total Price:</strong> <?php echo esc_html($total_price_formatted); ?></p>
            
            <?php if (!empty($services_html)): ?>
            <h4>Additional Services Requested:</h4>
            <?php echo $services_html; ?>
            <?php endif; ?>
        </div>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        <p>Best regards,<br><?php echo get_bloginfo('name'); ?></p>
    </div>
</body>
</html>
                <?php
                break;
                
            case 'approval-email':
                ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e9ecef; border-radius: 0 0 8px 8px; }
        .booking-details { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Your Booking Has Been Approved!</h1>
    </div>
    <div class="content">
        <p>Dear <?php echo esc_html($guest_name); ?>,</p>
        <p>Great news! Your booking request has been approved.</p>
        
        <div class="booking-details">
            <h3>Confirmed Booking Details</h3>
            <p><strong>Check-in:</strong> <?php echo esc_html($checkin_formatted); ?></p>
            <p><strong>Check-out:</strong> <?php echo esc_html($checkout_formatted); ?></p>
            <p><strong>Total Nights:</strong> <?php echo $nights; ?></p>
            <p><strong>Total Price:</strong> <?php echo esc_html($total_price_formatted); ?></p>
            
            <?php if (!empty($services_html)): ?>
            <h4>Additional Services Confirmed:</h4>
            <?php echo $services_html; ?>
            <?php endif; ?>
        </div>
        
        <p>We look forward to welcoming you!</p>
        <p>Best regards,<br><?php echo get_bloginfo('name'); ?></p>
    </div>
</body>
</html>
                <?php
                break;
                
            case 'denial-email':
                ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e9ecef; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Update on Your Booking Request</h1>
    </div>
    <div class="content">
        <p>Dear <?php echo esc_html($guest_name); ?>,</p>
        <p>Thank you for your interest in booking with us. Unfortunately, we are unable to accommodate your request for the selected dates.</p>
        <p>We encourage you to check our availability for alternative dates. We would love to welcome you at another time.</p>
        <p>If you have any questions, please feel free to contact us.</p>
        <p>Best regards,<br><?php echo get_bloginfo('name'); ?></p>
    </div>
</body>
</html>
                <?php
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Parse template placeholders
     */
    private function parse_template($template, $data) {
        // Prepare placeholders
        $placeholders = array(
            '{guest_name}' => esc_html($data['guest_name']),
            '{email}' => esc_html($data['email']),
            '{phone}' => esc_html($data['phone']),
            '{checkin_date}' => esc_html($data['checkin_formatted']),
            '{checkout_date}' => esc_html($data['checkout_formatted']),
            '{nights}' => $data['nights'],
            '{total_price}' => esc_html($data['total_price_formatted']),
            '{message}' => nl2br(esc_html($data['message'] ?? '')),
            '{site_name}' => get_bloginfo('name'),
            '{services_list}' => $data['services_html'] ? '<h3>Additional Services Requested</h3>' . $data['services_html'] : '',
            '{approve_url}' => esc_url($data['approve_url'] ?? ''),
            '{deny_url}' => esc_url($data['deny_url'] ?? ''),
            '{admin_url}' => admin_url('admin.php?page=booking-requests-edit&booking_id=' . ($data['id'] ?? ''))
        );
        
        // Replace placeholders
        $html = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        
        // Wrap in HTML structure
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #1a3a52; }
        .booking-details { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        a { color: #1976d2; }
        .btn { display: inline-block; padding: 12px 30px; margin: 0 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    ' . $html . '
</body>
</html>';
    }
}