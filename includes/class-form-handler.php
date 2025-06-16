<?php

class BR_Form_Handler {
    
    public function __construct() {
        // AJAX handlers for calendar widget
        add_action('wp_ajax_br_submit_calendar_booking', array($this, 'handle_calendar_booking'));
        add_action('wp_ajax_nopriv_br_submit_calendar_booking', array($this, 'handle_calendar_booking'));
        
        // AJAX handlers for Gravity Forms
        add_action('wp_ajax_br_submit_booking', array($this, 'handle_booking_submission'));
        add_action('wp_ajax_nopriv_br_submit_booking', array($this, 'handle_booking_submission'));
        
        // Gravity Forms submission handler
        add_action('gform_after_submission', array($this, 'handle_gravity_form_submission'), 10, 2);
        
        // Debug handler
        add_action('wp_ajax_br_test_form_handler', array($this, 'test_form_handler'));
        add_action('wp_ajax_nopriv_br_test_form_handler', array($this, 'test_form_handler'));
    }
    
    /**
     * Test form handler for debugging
     */
    public function test_form_handler() {
        // Log all received data
        error_log('BR Form Handler Test - POST data: ' . print_r($_POST, true));
        error_log('BR Form Handler Test - Nonce: ' . ($_POST['nonce'] ?? 'not set'));
        
        $response = array(
            'received_data' => $_POST,
            'nonce_valid' => wp_verify_nonce($_POST['nonce'] ?? '', 'br_calendar_nonce'),
            'required_fields' => array(
                'guest_name' => !empty($_POST['guest_name']),
                'email' => !empty($_POST['email']),
                'phone' => !empty($_POST['phone']),
                'checkin_date' => !empty($_POST['checkin_date']),
                'checkout_date' => !empty($_POST['checkout_date'])
            ),
            'email_valid' => is_email($_POST['email'] ?? ''),
            'database_table_exists' => $this->check_database_table()
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Check if database table exists
     */
    private function check_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    /**
     * Handle booking submission from Gravity Forms
     */
    public function handle_booking_submission() {
        // This is triggered by the Gravity Forms addon
        // Implementation depends on your Gravity Forms setup
        
        // Verify nonce if using custom AJAX
        if (!wp_verify_nonce($_POST['nonce'], 'br_booking_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Process the booking from Gravity Forms data
        $this->process_gravity_forms_booking($_POST);
    }
    
    /**
     * Handle Gravity Forms submission
     */
    public function handle_gravity_form_submission($entry, $form) {
        // Check if this is a booking form based on form ID or form class
        // You can set this in the Gravity Forms addon settings
        $booking_form_ids = get_option('br_gravity_forms_ids', array());
        
        if (!in_array($form['id'], $booking_form_ids)) {
            return;
        }
        
        // Map Gravity Forms fields to booking data
        $booking_data = $this->map_gravity_forms_data($entry, $form);
        
        // Save booking
        $booking_id = $this->save_booking($booking_data);
        
        if ($booking_id) {
            // Send notifications
            do_action('br_send_admin_notification', $booking_id);
            do_action('br_send_guest_confirmation', $booking_id);
        }
    }
    
    /**
     * Handle booking submission from calendar widget
     */
    public function handle_calendar_booking() {
        // Debug - log what we're receiving
        error_log('BR Form Handler - Received POST data: ' . print_r($_POST, true));
        error_log('BR Form Handler - Looking for name fields:');
        error_log('  first_name: ' . ($_POST['first_name'] ?? 'not set'));
        error_log('  last_name: ' . ($_POST['last_name'] ?? 'not set'));
        error_log('  firstname: ' . ($_POST['firstname'] ?? 'not set'));
        error_log('  lastname: ' . ($_POST['lastname'] ?? 'not set'));
        error_log('  guest_name: ' . ($_POST['guest_name'] ?? 'not set'));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'br_calendar_nonce')) {
            error_log('BR Form Handler - Invalid nonce: ' . ($_POST['nonce'] ?? 'not set'));
            wp_send_json_error('Invalid nonce');
        }
        
        // Check database table
        if (!$this->check_database_table()) {
            error_log('BR Form Handler - Database table does not exist');
            wp_send_json_error('Database error: Table not found. Please contact administrator.');
        }
        
        // Log which fields are present
        $field_status = array();
        $expected_fields = array('guest_name', 'email', 'phone', 'checkin_date', 'checkout_date');
        foreach ($expected_fields as $field) {
            $field_status[$field] = isset($_POST[$field]) ? 'present' : 'missing';
            if (isset($_POST[$field])) {
                $field_status[$field] .= ' (value: ' . $_POST[$field] . ')';
            }
        }
        error_log('BR Form Handler - Field status: ' . print_r($field_status, true));
        
        // Check for alternative field names
        $field_mapping = array(
            'guest_name' => array('guest_name', 'name', 'full_name', 'fullname'),
            'email' => array('email', 'email_address'),
            'phone' => array('phone', 'phone_number', 'telephone'),
            'checkin_date' => array('checkin_date', 'checkin', 'check_in_date', 'start_date'),
            'checkout_date' => array('checkout_date', 'checkout', 'check_out_date', 'end_date')
        );
        
        // Special handling for first/last name combination
        if (!empty($_POST['first_name']) && !empty($_POST['last_name'])) {
            $_POST['guest_name'] = trim($_POST['first_name'] . ' ' . $_POST['last_name']);
            error_log('BR Form Handler - Combined name: ' . $_POST['guest_name']);
        } elseif (!empty($_POST['firstname']) && !empty($_POST['lastname'])) {
            $_POST['guest_name'] = trim($_POST['firstname'] . ' ' . $_POST['lastname']);
            error_log('BR Form Handler - Combined name: ' . $_POST['guest_name']);
        }
        
        // Map fields
        $mapped_data = array();
        foreach ($field_mapping as $standard_field => $possible_names) {
            foreach ($possible_names as $possible_name) {
                if (!empty($_POST[$possible_name])) {
                    $mapped_data[$standard_field] = $_POST[$possible_name];
                    break;
                }
            }
        }
        
        error_log('BR Form Handler - Mapped data: ' . print_r($mapped_data, true));
        
        // Validate required fields
        $required_fields = array('guest_name', 'email', 'phone', 'checkin_date', 'checkout_date');
        $missing_fields = array();
        foreach ($required_fields as $field) {
            if (empty($mapped_data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log('BR Form Handler - Missing fields: ' . implode(', ', $missing_fields));
            wp_send_json_error('Please fill in all required fields. Missing: ' . implode(', ', $missing_fields));
        }
        
        // Use mapped data for the rest of the processing
        $_POST = array_merge($_POST, $mapped_data);
        
        // Validate email
        if (!is_email($_POST['email'])) {
            wp_send_json_error('Please enter a valid email address');
        }
        
        // Validate dates
        $checkin = DateTime::createFromFormat('Y-m-d', $_POST['checkin_date']);
        $checkout = DateTime::createFromFormat('Y-m-d', $_POST['checkout_date']);
        
        if (!$checkin || !$checkout) {
            error_log('BR Form Handler - Invalid date format. Checkin: ' . $_POST['checkin_date'] . ', Checkout: ' . $_POST['checkout_date']);
            wp_send_json_error('Invalid date format');
        }
        
        // Validate it's exactly 6 nights (Sunday to Saturday)
        $nights = $checkin->diff($checkout)->days;
        if ($nights !== 6) {
            error_log('BR Form Handler - Invalid number of nights: ' . $nights);
            wp_send_json_error('Bookings must be for exactly 6 nights (Sunday to Saturday). You selected ' . $nights . ' nights.');
        }
        
        // Validate checkin is Sunday
        if ($checkin->format('w') != 0) {
            error_log('BR Form Handler - Check-in not on Sunday. Day: ' . $checkin->format('l'));
            wp_send_json_error('Check-in must be on Sunday. You selected ' . $checkin->format('l'));
        }
        
        // Parse weeks data
        $weeks_data = array();
        if (!empty($_POST['weeks_data'])) {
            $weeks_data = json_decode(stripslashes($_POST['weeks_data']), true);
            if (!is_array($weeks_data)) {
                $weeks_data = array();
            }
        }
        
        // Calculate total price from weeks data
        $total_price = 0;
        foreach ($weeks_data as $week) {
            if (isset($week['rate'])) {
                $total_price += floatval($week['rate']);
            }
        }
        
        // If no weeks data, use the submitted total price
        if (empty($weeks_data) && !empty($_POST['total_price'])) {
            $total_price = floatval($_POST['total_price']);
        }
        
        // Prepare booking data
        $booking_data = array(
            'guest_name' => sanitize_text_field($_POST['guest_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'checkin_date' => $checkin->format('Y-m-d'),
            'checkout_date' => $checkout->format('Y-m-d'),
            'total_price' => $total_price,
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'booking_data' => maybe_serialize(array(
                'weeks' => $weeks_data,
                'form_source' => 'calendar_widget',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ))
        );
        
        error_log('BR Form Handler - Attempting to save booking: ' . print_r($booking_data, true));
        
        // Insert booking
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        
        $inserted = $wpdb->insert($table_name, $booking_data);
        
        if (!$inserted) {
            error_log('BR Form Handler - Database error: ' . $wpdb->last_error);
            wp_send_json_error('Failed to save booking. Database error: ' . $wpdb->last_error);
        }
        
        $booking_id = $wpdb->insert_id;
        
        // Send admin notification
        do_action('br_send_admin_notification', $booking_id);
        
        // Send guest confirmation email
        do_action('br_send_guest_confirmation', $booking_id);
        
        // Log the booking
        error_log('BR Form Handler: New booking created - ID: ' . $booking_id . ', Guest: ' . $booking_data['guest_name']);
        
        wp_send_json_success(array(
            'message' => 'Your booking request has been submitted successfully! You will receive a confirmation email shortly.',
            'booking_id' => $booking_id,
            'debug' => array(
                'nights' => $nights,
                'checkin_day' => $checkin->format('l'),
                'total_price' => $total_price
            )
        ));
    }
    
    /**
     * Process Gravity Forms booking data
     */
    private function process_gravity_forms_booking($data) {
        // Implementation depends on your Gravity Forms field mapping
        // This is a placeholder for the actual implementation
        
        $booking_data = array(
            'guest_name' => sanitize_text_field($data['guest_name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'checkin_date' => sanitize_text_field($data['checkin_date'] ?? ''),
            'checkout_date' => sanitize_text_field($data['checkout_date'] ?? ''),
            'total_price' => floatval($data['total_price'] ?? 0),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'booking_data' => maybe_serialize(array(
                'form_source' => 'gravity_forms',
                'form_id' => $data['form_id'] ?? '',
                'entry_id' => $data['entry_id'] ?? ''
            ))
        );
        
        $booking_id = $this->save_booking($booking_data);
        
        if ($booking_id) {
            // Send notifications
            do_action('br_send_admin_notification', $booking_id);
            do_action('br_send_guest_confirmation', $booking_id);
        }
        
        return $booking_id;
    }
    
    /**
     * Map Gravity Forms data to booking data
     */
    private function map_gravity_forms_data($entry, $form) {
        // Get field mappings from settings
        $field_mappings = get_option('br_gravity_forms_field_mappings', array());
        
        // Default mapping - you'll need to customize this based on your form
        $booking_data = array(
            'guest_name' => rgar($entry, $field_mappings['guest_name'] ?? '1'),
            'email' => rgar($entry, $field_mappings['email'] ?? '2'),
            'phone' => rgar($entry, $field_mappings['phone'] ?? '3'),
            'checkin_date' => rgar($entry, $field_mappings['checkin_date'] ?? '4'),
            'checkout_date' => rgar($entry, $field_mappings['checkout_date'] ?? '5'),
            'message' => rgar($entry, $field_mappings['message'] ?? '6'),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'booking_data' => maybe_serialize(array(
                'form_source' => 'gravity_forms',
                'form_id' => $form['id'],
                'entry_id' => $entry['id'],
                'form_title' => $form['title']
            ))
        );
        
        // Calculate price based on dates
        $pricing_engine = new BR_Pricing_Engine();
        $booking_data['total_price'] = $pricing_engine->calculate_total_price(
            $booking_data['checkin_date'],
            $booking_data['checkout_date']
        );
        
        return $booking_data;
    }
    
    /**
     * Save booking to database
     */
    private function save_booking($booking_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        
        // Check if table exists first
        if (!$this->check_database_table()) {
            error_log('BR Form Handler: Cannot save booking - table does not exist');
            return false;
        }
        
        $inserted = $wpdb->insert($table_name, $booking_data);
        
        if (!$inserted) {
            error_log('BR Form Handler: Failed to save booking - ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Validate booking dates against pricing periods
     */
    private function validate_booking_dates($checkin_date, $checkout_date) {
        $pricing_engine = new BR_Pricing_Engine();
        
        // Check if dates fall within valid pricing periods
        $checkin = new DateTime($checkin_date);
        $checkout = new DateTime($checkout_date);
        
        // Get pricing data
        $pricing_data = $pricing_engine->get_pricing_for_dates($checkin_date, $checkout_date);
        
        if (empty($pricing_data)) {
            return false;
        }
        
        // Additional validation can be added here
        return true;
    }
    
    /**
     * Check if dates are available
     */
    public function check_availability($checkin_date, $checkout_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        
        // Check if table exists
        if (!$this->check_database_table()) {
            return true; // If no table, assume available
        }
        
        // Check for overlapping approved bookings
        $overlap = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE status = 'approved' 
            AND (
                (checkin_date <= %s AND checkout_date > %s) OR
                (checkin_date < %s AND checkout_date >= %s) OR
                (checkin_date >= %s AND checkout_date <= %s)
            )",
            $checkin_date, $checkin_date,
            $checkout_date, $checkout_date,
            $checkin_date, $checkout_date
        ));
        
        return $overlap == 0;
    }
}

// Add debug test to functions
add_action('wp_footer', function() {
    if (is_page() && current_user_can('manage_options')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Test form handler
            if (window.location.hash === '#test-form') {
                console.log('Testing form handler...');
                
                $.post(br_calendar.ajax_url, {
                    action: 'br_test_form_handler',
                    nonce: br_calendar.nonce,
                    guest_name: 'Test User',
                    email: 'test@example.com',
                    phone: '123-456-7890',
                    checkin_date: '2025-06-15',
                    checkout_date: '2025-06-21'
                }).done(function(response) {
                    console.log('Form handler test response:', response);
                }).fail(function(xhr) {
                    console.log('Form handler test failed:', xhr.responseText);
                });
            }
            
            // Log form submission
            $(document).on('submit', '.br-booking-form', function(e) {
                console.log('=== FORM SUBMISSION DEBUG ===');
                console.log('Form action:', $(this).attr('action'));
                console.log('Form method:', $(this).attr('method'));
                console.log('Form data:', $(this).serialize());
                
                // Log each field
                $(this).find('input, textarea, select').each(function() {
                    console.log('Field:', $(this).attr('name'), '=', $(this).val());
                });
            });
        });
        </script>
        <?php
    }
});
?>