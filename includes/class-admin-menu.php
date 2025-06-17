<?php

class BR_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers for admin actions
        add_action('wp_ajax_br_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_br_calculate_price', array($this, 'ajax_calculate_price'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            'Booking Requests',
            'Booking Requests',
            'manage_options',
            'booking-requests',
            array($this, 'render_bookings_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'booking-requests',
            'All Bookings',
            'All Bookings',
            'manage_options',
            'booking-requests',
            array($this, 'render_bookings_page')
        );
        
        add_submenu_page(
            'booking-requests',
            'Add New Booking',
            'Add New',
            'manage_options',
            'booking-requests-add',
            array($this, 'render_add_booking_page')
        );
        
        add_submenu_page(
            'booking-requests',
            'Settings',
            'Settings',
            'manage_options',
            'booking-requests-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'booking-requests',
            'Email Templates',
            'Email Templates',
            'manage_options',
            'booking-requests-email-templates',
            array($this, 'render_email_templates_page')
        );
        
        add_submenu_page(
            'booking-requests',
            'Pricing Periods',
            'Pricing',
            'manage_options',
            'booking-requests-pricing',
            array($this, 'render_pricing_page')
        );
        
        // Hidden page for editing
        add_submenu_page(
            null,
            'Edit Booking',
            'Edit Booking',
            'manage_options',
            'booking-requests-edit',
            array($this, 'render_edit_booking_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'booking-requests') === false) {
            return;
        }
        
        wp_enqueue_style('br-admin-styles', BR_PLUGIN_URL . 'admin/css/admin-styles.css', array(), BR_PLUGIN_VERSION);
        wp_enqueue_script('br-admin-scripts', BR_PLUGIN_URL . 'admin/js/admin-scripts.js', array('jquery', 'jquery-ui-datepicker'), BR_PLUGIN_VERSION, true);
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        wp_localize_script('br-admin-scripts', 'br_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('br_admin_nonce'),
            'strings' => array(
                'confirm_approve' => __('Are you sure you want to approve this booking?', 'booking-requests'),
                'confirm_deny' => __('Are you sure you want to deny this booking?', 'booking-requests'),
                'confirm_delete' => __('Are you sure you want to delete this booking?', 'booking-requests'),
                'processing' => __('Processing...', 'booking-requests'),
                'error' => __('An error occurred. Please try again.', 'booking-requests'),
                'dates_not_available' => __('Selected dates are not available', 'booking-requests'),
                'calculating' => __('Calculating...', 'booking-requests')
            )
        ));
    }
    
    /**
     * Render bookings page
     */
    public function render_bookings_page() {
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['booking_id'])) {
            $this->handle_booking_action($_GET['action'], $_GET['booking_id']);
        }
        
        // Get bookings
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        
        $args = array(
            'status' => $status,
            'limit' => $per_page,
            'offset' => ($paged - 1) * $per_page
        );
        
        $bookings = BR_Database::get_bookings($args);
        $total_bookings = BR_Database::get_bookings_count($status);
        
        // Load view
        include BR_PLUGIN_DIR . 'admin/views/bookings-list.php';
    }
    
    /**
     * Render add booking page
     */
    public function render_add_booking_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['br_booking_nonce'], 'br_save_booking')) {
            $this->handle_save_booking();
        }
        
        $booking = null;
        $action = 'add';
        include BR_PLUGIN_DIR . 'admin/views/booking-form.php';
    }
    
    /**
     * Render edit booking page
     */
    public function render_edit_booking_page() {
        if (!isset($_GET['booking_id'])) {
            wp_die('Booking ID is required');
        }
        
        $booking_id = intval($_GET['booking_id']);
        $booking = BR_Database::get_booking($booking_id);
        
        if (!$booking) {
            wp_die('Booking not found');
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['br_booking_nonce'], 'br_save_booking')) {
            $this->handle_save_booking($booking_id);
            // Reload booking data
            $booking = BR_Database::get_booking($booking_id);
        }
        
        // Unserialize additional services
        $booking->additional_services = maybe_unserialize($booking->additional_services);
        $booking->booking_data = maybe_unserialize($booking->booking_data);
        
        $action = 'edit';
        include BR_PLUGIN_DIR . 'admin/views/booking-form.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Save settings
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['br_settings_nonce'], 'br_settings')) {
            $this->save_settings();
        }
        
        // Load view
        include BR_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Handle booking actions
     */
    private function handle_booking_action($action, $booking_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'booking_action')) {
            wp_die('Invalid nonce');
        }
        
        switch ($action) {
            case 'approve':
                BR_Database::update_booking_status($booking_id, 'approved');
                do_action('br_send_approval_email', $booking_id);
                wp_redirect(admin_url('admin.php?page=booking-requests&message=approved'));
                exit;
                
            case 'deny':
                BR_Database::update_booking_status($booking_id, 'denied');
                do_action('br_send_denial_email', $booking_id);
                wp_redirect(admin_url('admin.php?page=booking-requests&message=denied'));
                exit;
                
            case 'delete':
                BR_Database::delete_booking($booking_id);
                wp_redirect(admin_url('admin.php?page=booking-requests&message=deleted'));
                exit;
        }
    }
    
    /**
     * Handle save booking (add/edit)
     */
    private function handle_save_booking($booking_id = null) {
        // Validate dates
        $checkin = DateTime::createFromFormat('Y-m-d', $_POST['checkin_date']);
        $checkout = DateTime::createFromFormat('Y-m-d', $_POST['checkout_date']);
        
        if (!$checkin || !$checkout) {
            add_settings_error('br_booking', 'invalid_dates', 'Invalid date format', 'error');
            return;
        }
        
        // Validate minimum 3 nights
        $nights = $checkin->diff($checkout)->days;
        if ($nights < 3) {
            add_settings_error('br_booking', 'min_nights', 'Minimum stay is 3 nights', 'error');
            return;
        }
        
        // Check availability (exclude current booking if editing)
        if (!BR_Database::check_availability($_POST['checkin_date'], $_POST['checkout_date'], $booking_id)) {
            add_settings_error('br_booking', 'not_available', 'Selected dates are not available', 'error');
            return;
        }
        
        // Calculate price
        $pricing_engine = new BR_Pricing_Engine();
        $total_price = $pricing_engine->calculate_total_price(
            $_POST['checkin_date'],
            $_POST['checkout_date']
        );
        
        // Prepare data
        $booking_data = array(
            'guest_name' => sanitize_text_field($_POST['guest_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'checkin_date' => $checkin->format('Y-m-d'),
            'checkout_date' => $checkout->format('Y-m-d'),
            'total_price' => $total_price,
            'message' => sanitize_textarea_field($_POST['message']),
            'additional_services' => $_POST['additional_services'] ?? array(),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        if ($booking_id) {
            // Update existing booking
            $updated = BR_Database::update_booking($booking_id, $booking_data);
            if ($updated !== false) {
                add_settings_error('br_booking', 'booking_updated', 'Booking updated successfully', 'updated');
            }
        } else {
            // Create new booking
            $new_booking_id = BR_Database::create_booking($booking_data);
            if ($new_booking_id) {
                // Send notifications
                do_action('br_send_admin_notification', $new_booking_id);
                do_action('br_send_guest_confirmation', $new_booking_id);
                
                wp_redirect(admin_url('admin.php?page=booking-requests-edit&booking_id=' . $new_booking_id . '&message=created'));
                exit;
            }
        }
    }
    
    /**
     * Render email templates page
     */
    public function render_email_templates_page() {
        // Save templates
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['br_email_templates_nonce'], 'br_email_templates')) {
            $this->save_email_templates();
        }
        
        // Load view
        include BR_PLUGIN_DIR . 'admin/views/email-templates.php';
    }
    
    /**
     * Render pricing periods page
     */
    public function render_pricing_page() {
        // Save pricing
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['br_pricing_nonce'], 'br_pricing')) {
            $this->save_pricing_periods();
        }
        
        // Load view
        include BR_PLUGIN_DIR . 'admin/views/pricing-periods.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $options = array(
            'br_admin_emails' => sanitize_textarea_field($_POST['br_admin_emails']),
            'br_week_start_day' => sanitize_text_field($_POST['br_week_start_day']),
            'br_approval_required' => isset($_POST['br_approval_required']) ? '1' : '0',
            'br_calendar_months_to_show' => intval($_POST['br_calendar_months_to_show']),
            'br_calendar_min_advance_days' => intval($_POST['br_calendar_min_advance_days'])
        );
        
        foreach ($options as $key => $value) {
            update_option($key, $value);
        }
        
        add_settings_error('br_settings', 'settings_updated', 'Settings saved successfully!', 'updated');
    }
    
    /**
     * Save email templates
     */
    private function save_email_templates() {
        $templates = array(
            'br_email_template_admin_notification' => wp_kses_post($_POST['admin_notification_template']),
            'br_email_template_guest_confirmation' => wp_kses_post($_POST['guest_confirmation_template']),
            'br_email_template_approval' => wp_kses_post($_POST['approval_template']),
            'br_email_template_denial' => wp_kses_post($_POST['denial_template'])
        );
        
        foreach ($templates as $key => $value) {
            update_option($key, $value);
        }
        
        add_settings_error('br_email_templates', 'templates_updated', 'Email templates saved successfully!', 'updated');
    }
    
    /**
     * Save pricing periods
     */
    private function save_pricing_periods() {
        $periods = array();
        
        if (isset($_POST['periods']) && is_array($_POST['periods'])) {
            foreach ($_POST['periods'] as $period) {
                if (!empty($period['start']) && !empty($period['end']) && !empty($period['daily_price'])) {
                    $periods[] = array(
                        'start' => sanitize_text_field($period['start']),
                        'end' => sanitize_text_field($period['end']),
                        'daily_price' => floatval($period['daily_price']),
                        'weekly_price' => floatval($period['daily_price']) * 7
                    );
                }
            }
        }
        
        update_option('br_pricing_periods', $periods);
        add_settings_error('br_pricing', 'pricing_updated', 'Pricing periods saved successfully!', 'updated');
    }
    
    /**
     * AJAX check availability
     */
    public function ajax_check_availability() {
        if (!wp_verify_nonce($_POST['nonce'], 'br_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $checkin = sanitize_text_field($_POST['checkin_date']);
        $checkout = sanitize_text_field($_POST['checkout_date']);
        $exclude_id = !empty($_POST['exclude_id']) ? intval($_POST['exclude_id']) : null;
        
        $available = BR_Database::check_availability($checkin, $checkout, $exclude_id);
        
        wp_send_json_success(array('available' => $available));
    }
    
    /**
     * AJAX calculate price
     */
    public function ajax_calculate_price() {
        if (!wp_verify_nonce($_POST['nonce'], 'br_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $checkin = sanitize_text_field($_POST['checkin_date']);
        $checkout = sanitize_text_field($_POST['checkout_date']);
        
        $pricing_engine = new BR_Pricing_Engine();
        $total_price = $pricing_engine->calculate_total_price($checkin, $checkout);
        
        wp_send_json_success(array(
            'total_price' => $total_price,
            'formatted_price' => '€' . number_format($total_price, 0, ',', '.')
        ));
    }
}