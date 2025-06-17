<?php

class BR_Database {
    
    private static $table_name = 'br_bookings';
    
    public function __construct() {
        // Hook into plugin activation
        register_activation_hook(BR_PLUGIN_BASENAME, array($this, 'create_tables'));
        add_action('plugins_loaded', array($this, 'check_db_version'));
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            guest_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            checkin_date date NOT NULL,
            checkout_date date NOT NULL,
            total_price decimal(10,2) NOT NULL,
            message text DEFAULT NULL,
            additional_services text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            booking_data longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY checkin_date (checkin_date),
            KEY checkout_date (checkout_date),
            KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Update version
        update_option('br_db_version', '2.0');
    }
    
    /**
     * Check and update database version
     */
    public function check_db_version() {
        $current_version = get_option('br_db_version', '1.0');
        
        if (version_compare($current_version, '2.0', '<')) {
            $this->upgrade_database();
        }
    }
    
    /**
     * Upgrade database schema
     */
    private function upgrade_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Check if additional_services column exists
        $column_exists = $wpdb->get_var(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table_name' 
            AND COLUMN_NAME = 'additional_services'"
        );
        
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN additional_services TEXT DEFAULT NULL AFTER message");
        }
        
        // Check if updated_at column exists
        $updated_at_exists = $wpdb->get_var(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table_name' 
            AND COLUMN_NAME = 'updated_at'"
        );
        
        if (!$updated_at_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }
        
        // Update version
        update_option('br_db_version', '2.0');
    }
    
    /**
     * Get all bookings
     */
    public static function get_bookings($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $defaults = array(
            'status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '1=1';
        
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }
        
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= $wpdb->prepare(
                ' AND (guest_name LIKE %s OR email LIKE %s OR phone LIKE %s)',
                $search, $search, $search
            );
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get booking by ID
     */
    public static function get_booking($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Create new booking
     */
    public static function create_booking($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $defaults = array(
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'additional_services' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Serialize additional services if array
        if (is_array($data['additional_services'])) {
            $data['additional_services'] = maybe_serialize($data['additional_services']);
        }
        
        // Serialize booking data if array
        if (isset($data['booking_data']) && is_array($data['booking_data'])) {
            $data['booking_data'] = maybe_serialize($data['booking_data']);
        }
        
        $inserted = $wpdb->insert($table_name, $data);
        
        if ($inserted) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update booking
     */
    public static function update_booking($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Don't update these fields
        unset($data['id']);
        unset($data['created_at']);
        
        // Serialize additional services if array
        if (isset($data['additional_services']) && is_array($data['additional_services'])) {
            $data['additional_services'] = maybe_serialize($data['additional_services']);
        }
        
        // Serialize booking data if array
        if (isset($data['booking_data']) && is_array($data['booking_data'])) {
            $data['booking_data'] = maybe_serialize($data['booking_data']);
        }
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $id)
        );
    }
    
    /**
     * Update booking status
     */
    public static function update_booking_status($id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $id)
        );
    }
    
    /**
     * Delete booking
     */
    public static function delete_booking($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id)
        );
    }
    
    /**
     * Get bookings count
     */
    public static function get_bookings_count($status = '', $search = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $where = '1=1';
        
        if (!empty($status)) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }
        
        if (!empty($search)) {
            $search = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                ' AND (guest_name LIKE %s OR email LIKE %s OR phone LIKE %s)',
                $search, $search, $search
            );
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
    }
    
    /**
     * Check if dates are available
     */
    public static function check_availability($checkin_date, $checkout_date, $exclude_booking_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE status IN ('pending', 'approved')
            AND (
                (checkin_date <= %s AND checkout_date > %s) OR
                (checkin_date < %s AND checkout_date >= %s) OR
                (checkin_date >= %s AND checkout_date <= %s)
            )",
            $checkin_date, $checkin_date,
            $checkout_date, $checkout_date,
            $checkin_date, $checkout_date
        );
        
        if ($exclude_booking_id) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_booking_id);
        }
        
        $conflict = $wpdb->get_var($query);
        
        return $conflict == 0;
    }
    
    /**
     * Get booked dates in range
     */
    public static function get_booked_dates($start_date, $end_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT checkin_date, checkout_date, status FROM $table_name 
            WHERE status IN ('pending', 'approved')
            AND checkout_date >= %s 
            AND checkin_date <= %s",
            $start_date,
            $end_date
        ));
        
        $booked_dates = array();
        foreach ($bookings as $booking) {
            $current = new DateTime($booking->checkin_date);
            $end = new DateTime($booking->checkout_date);
            
            while ($current < $end) {
                $booked_dates[] = array(
                    'date' => $current->format('Y-m-d'),
                    'status' => $booking->status
                );
                $current->modify('+1 day');
            }
        }
        
        return $booked_dates;
    }
}