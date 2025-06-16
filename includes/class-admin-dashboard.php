<?php
/**
 * Admin Dashboard Class
 */
class BR_Admin_Dashboard {
    
    /**
     * Initialize admin dashboard
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // AJAX handlers
        add_action('wp_ajax_br_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_br_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_br_export_bookings', array($this, 'ajax_export_bookings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Booking Requests', 'booking-requests'),
            __('Booking Requests', 'booking-requests'),
            'manage_options',
            'booking-requests',
            array($this, 'render_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'booking-requests',
            __('All Bookings', 'booking-requests'),
            __('All Bookings', 'booking-requests'),
            'manage_options',
            'booking-requests',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'booking-requests',
            __('Settings', 'booking-requests'),
            __('Settings', 'booking-requests'),
            'manage_options',
            'booking-requests-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $this->render_booking_details();
            return;
        }
        
        include BR_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Render booking details page
     */
    public function render_booking_details() {
        $booking_id = intval($_GET['id']);
        $booking = BR_Booking_Requests::get_booking($booking_id);
        
        if (!$booking) {
            wp_die(__('Booking not found', 'booking-requests'));
        }
        
        include BR_PLUGIN_PATH . 'admin/views/booking-details.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // All form handling is now in the settings.php view file
        include BR_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'booking-requests') {
            return;
        }
        
        if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'br_admin_action')) {
                return;
            }
            
            $booking_id = intval($_GET['id']);
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'approve':
                    BR_Booking_Requests::update_booking_status($booking_id, 'approved');
                    wp_redirect(add_query_arg(array('updated' => 'approved'), remove_query_arg(array('action', 'id', '_wpnonce'))));
                    exit;
                    
                case 'deny':
                    BR_Booking_Requests::update_booking_status($booking_id, 'denied');
                    wp_redirect(add_query_arg(array('updated' => 'denied'), remove_query_arg(array('action', 'id', '_wpnonce'))));
                    exit;
                    
                case 'delete':
                    $this->delete_booking($booking_id);
                    wp_redirect(add_query_arg(array('deleted' => '1'), remove_query_arg(array('action', 'id', '_wpnonce'))));
                    exit;
            }
        }
    }
    
    /**
     * AJAX handler for status update
     */
    public function ajax_update_status() {
        check_ajax_referer('br_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        if (!in_array($status, array('pending', 'approved', 'denied'))) {
            wp_send_json_error(array('message' => __('Invalid status', 'booking-requests')));
        }
        
        $result = BR_Booking_Requests::update_booking_status($booking_id, $status);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Status updated', 'booking-requests')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update status', 'booking-requests')));
        }
    }
    
    /**
     * AJAX handler for bulk actions
     */
    public function ajax_bulk_action() {
        check_ajax_referer('br_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $booking_ids = array_map('intval', $_POST['booking_ids']);
        
        if (empty($booking_ids)) {
            wp_send_json_error(array('message' => __('No bookings selected', 'booking-requests')));
        }
        
        $count = 0;
        
        switch ($action) {
            case 'approve':
                foreach ($booking_ids as $id) {
                    if (BR_Booking_Requests::update_booking_status($id, 'approved')) {
                        $count++;
                    }
                }
                break;
                
            case 'deny':
                foreach ($booking_ids as $id) {
                    if (BR_Booking_Requests::update_booking_status($id, 'denied')) {
                        $count++;
                    }
                }
                break;
                
            case 'delete':
                foreach ($booking_ids as $id) {
                    if ($this->delete_booking($id)) {
                        $count++;
                    }
                }
                break;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d bookings updated', 'booking-requests'), $count),
            'count' => $count
        ));
    }
    
    /**
     * AJAX handler for export
     */
    public function ajax_export_bookings() {
        check_ajax_referer('br_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        $bookings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");
        
        // Create CSV
        $csv = array();
        $csv[] = array(
            'ID',
            'Guest Name',
            'Email',
            'Phone',
            'Check-in',
            'Check-out',
            'Weekly Rate',
            'Status',
            'Submitted',
            'Details'
        );
        
        foreach ($bookings as $booking) {
            $csv[] = array(
                $booking->id,
                $booking->first_name . ' ' . $booking->last_name,
                $booking->email,
                $booking->phone,
                $booking->checkin_date,
                $booking->checkout_date,
                '€' . number_format($booking->weekly_rate, 0, ',', '.'),
                ucfirst($booking->status),
                $booking->submitted_at,
                $booking->details
            );
        }
        
        $filename = 'booking-requests-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        
        exit;
    }
    
    /**
     * Delete booking
     */
    private function delete_booking($booking_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        return $wpdb->delete($table_name, array('id' => $booking_id));
    }
    
    /**
     * Get bookings with filters
     */
    public static function get_bookings($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_requests';
        
        $defaults = array(
            'status' => '',
            'search' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'submitted_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'checkin_date >= %s';
            $values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'checkout_date <= %s';
            $values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        $order_clause = sprintf('%s %s', sanitize_sql_orderby($args['orderby']), $args['order']);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        if (!empty($values)) {
            $count_query = $wpdb->prepare($count_query, $values);
        }
        $total = $wpdb->get_var($count_query);
        
        // Get bookings
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $order_clause LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $bookings = $wpdb->get_results($query);
        
        return array(
            'bookings' => $bookings,
            'total' => $total
        );
    }
}