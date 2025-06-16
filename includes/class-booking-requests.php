<?php
/**
 * Core plugin class
 */
class BR_Booking_Requests {
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Add AJAX handlers
        add_action('wp_ajax_br_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_br_calculate_price', array($this, 'ajax_calculate_price'));
        
        add_action('wp_ajax_br_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_br_check_availability', array($this, 'ajax_check_availability'));
        
        // Handle approval/denial via email token
        add_action('init', array($this, 'handle_email_actions'));
    }
    
    /**
     * Get weekly rate based on check-in date
     */
    public static function get_weekly_rate($checkin_date) {
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
        
        $checkin_timestamp = strtotime($checkin_date);
        
        foreach ($pricing_periods as $period) {
            $start_timestamp = strtotime($period['start']);
            $end_timestamp = strtotime($period['end']);
            
            if ($checkin_timestamp >= $start_timestamp && $checkin_timestamp <= $end_timestamp) {
                return apply_filters('booking_weekly_rate', $period['rate'], $checkin_date);
            }
        }
        
        // Default rate if no period matches
        return apply_filters('booking_weekly_rate', 4995, $checkin_date);
    }
    
    /**
     * AJAX handler for price calculation
     */
    public function ajax_calculate_price() {
        check_ajax_referer('br_public_nonce', 'nonce');
        
        $checkin = sanitize_text_field($_POST['checkin']);
        $checkout = sanitize_text_field($_POST['checkout']);
        
        if (empty($checkin) || empty($checkout)) {
            wp_send_json_error(array('message' => __('Please select both dates', 'booking-requests')));
        }
        
        $checkin_date = new DateTime($checkin);
        $checkout_date = new DateTime($checkout);
        $interval = $checkin_date->diff($checkout_date);
        $days = $interval->days;
        
        if ($days < 7) {
            wp_send_json_error(array('message' => __('Minimum stay is 7 nights', 'booking-requests')));
        }
        
        $weekly_rate = self::get_weekly_rate($checkin);
        $total_weeks = ceil($days / 7);
        $total_price = $weekly_rate * $total_weeks;
        
        wp_send_json_success(array(
            'weekly_rate' => $weekly_rate,
            'total_weeks' => $total_weeks,
            'total_price' => $total_price,
            'formatted_rate' => '€' . number_format($weekly_rate, 0, ',', '.'),
            'formatted_total' => '€' . number_format($total_price, 0, ',', '.')
        ));
    }
    
    /**
     * AJAX handler for availability check
     */
    public function ajax_check_availability() {
        check_ajax_referer('br_public_nonce', 'nonce');
        
        $checkin = sanitize_text_field($_POST['checkin']);
        $checkout = sanitize_text_field($_POST['checkout']);
        
        if (empty($checkin) || empty($checkout)) {
            wp_send_json_error(array('message' => __('Please select both dates', 'booking-requests')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        // Check for overlapping approved bookings
        $overlapping = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE status = 'approved' 
            AND NOT (checkin_date >= %s OR checkout_date <= %s)",
            $checkout,
            $checkin
        ));
        
        if ($overlapping > 0) {
            wp_send_json_error(array('message' => __('These dates are not available', 'booking-requests')));
        }
        
        wp_send_json_success(array('available' => true));
    }
    
    /**
     * Handle email approval/denial actions
     */
    public function handle_email_actions() {
        if (!isset($_GET['br_action']) || !isset($_GET['token'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['br_action']);
        $token = sanitize_text_field($_GET['token']);
        
        if (!in_array($action, array('approve', 'deny'))) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        // Find booking by token
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE approval_token = %s AND token_expires > NOW()",
            $token
        ));
        
        if (!$booking) {
            wp_die(__('Invalid or expired link', 'booking-requests'));
        }
        
        // Update status
        $new_status = ($action === 'approve') ? 'approved' : 'denied';
        $wpdb->update(
            $table_name,
            array(
                'status' => $new_status,
                'approval_token' => null,
                'token_expires' => null
            ),
            array('id' => $booking->id)
        );
        
        // Trigger action for email notifications
        do_action('booking_request_' . $new_status, $booking->id);
        
        // Redirect to confirmation page
        $message = ($action === 'approve') 
            ? __('Booking approved successfully!', 'booking-requests')
            : __('Booking denied.', 'booking-requests');
            
        wp_die($message . ' <a href="' . admin_url('admin.php?page=booking-requests') . '">' . __('View all bookings', 'booking-requests') . '</a>');
    }
    
    /**
     * Create a new booking request
     */
    public static function create_booking($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        // Generate approval token
        $token = bin2hex(random_bytes(32));
        $token_expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        // Calculate weekly rate
        $weekly_rate = self::get_weekly_rate($data['checkin_date']);
        
        // Insert booking
        $result = $wpdb->insert(
            $table_name,
            array(
                'first_name' => sanitize_text_field($data['first_name']),
                'last_name' => sanitize_text_field($data['last_name']),
                'email' => sanitize_email($data['email']),
                'phone' => sanitize_text_field($data['phone']),
                'details' => sanitize_textarea_field($data['details']),
                'checkin_date' => sanitize_text_field($data['checkin_date']),
                'checkout_date' => sanitize_text_field($data['checkout_date']),
                'weekly_rate' => $weekly_rate,
                'form_entry_id' => isset($data['form_entry_id']) ? intval($data['form_entry_id']) : null,
                'approval_token' => $token,
                'token_expires' => $token_expires
            )
        );
        
        if ($result) {
            $booking_id = $wpdb->insert_id;
            
            // Trigger action for notifications
            do_action('booking_request_submitted', array_merge($data, array(
                'id' => $booking_id,
                'weekly_rate' => $weekly_rate,
                'approval_token' => $token
            )));
            
            return $booking_id;
        }
        
        return false;
    }
    
    /**
     * Get booking by ID
     */
    public static function get_booking($booking_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $booking_id
        ));
    }
    
    /**
     * Update booking status
     */
    public static function update_booking_status($booking_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $booking_id)
        );
        
        if ($result) {
            do_action('booking_request_' . $status, $booking_id);
        }
        
        return $result;
    }
}