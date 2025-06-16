<?php

class BR_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
            'Settings',
            'Settings',
            'manage_options',
            'booking-requests-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'booking-requests',
            'Email Test',
            'Email Test',
            'manage_options',
            'booking-requests-email-test',
            array($this, 'render_email_test_page')
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
        wp_enqueue_script('br-admin-scripts', BR_PLUGIN_URL . 'admin/js/admin-scripts.js', array('jquery'), BR_PLUGIN_VERSION, true);
        
        wp_localize_script('br-admin-scripts', 'br_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('br_admin_nonce')
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
     * Render email test page
     */
    public function render_email_test_page() {
        ?>
        <div class="wrap">
            <h1>Booking Requests - Email Test</h1>
            
            <div class="card">
                <h2>Email Configuration</h2>
                <p><strong>Admin Email:</strong> <?php echo get_option('br_admin_email', get_option('admin_email')); ?></p>
                <p><strong>WordPress Admin Email:</strong> <?php echo get_option('admin_email'); ?></p>
                <p><strong>Site Name:</strong> <?php echo get_bloginfo('name'); ?></p>
            </div>
            
            <div class="card">
                <h2>Email Tests</h2>
                <p>
                    <button type="button" class="button button-primary" onclick="runEmailTest('simple')">Test Simple Email</button>
                    <button type="button" class="button button-primary" onclick="runEmailTest('create_booking')">Create Test Booking</button>
                    <button type="button" class="button button-primary" onclick="runEmailTest('admin_notification')">Test Admin Notification</button>
                    <button type="button" class="button button-primary" onclick="runEmailTest('guest_confirmation')">Test Guest Confirmation</button>
                </p>
                <div id="test-results"></div>
            </div>
            
            <script>
            function runEmailTest(test) {
                jQuery('#test-results').html('<p>Running test...</p>');
                
                jQuery.post(ajaxurl, {
                    action: 'br_run_email_test',
                    test: test,
                    nonce: '<?php echo wp_create_nonce('br_email_test'); ?>'
                }, function(response) {
                    if (response.success) {
                        jQuery('#test-results').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        jQuery('#test-results').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                });
            }
            </script>
        </div>
        <?php
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
     * Save settings
     */
    private function save_settings() {
        $options = array(
            'br_admin_email' => sanitize_email($_POST['br_admin_email']),
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
}