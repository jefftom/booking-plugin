<?php

class BR_Database {
    
    private static $table_name = 'br_bookings';
    
    public function __construct() {
        // Hook into plugin activation
        register_activation_hook(BR_PLUGIN_BASENAME, array($this, 'create_tables'));
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
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            booking_data longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY checkin_date (checkin_date),
            KEY checkout_date (checkout_date),
            KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add version option
        add_option('br_db_version', '1.0');
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
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '1=1';
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
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
    public static function get_bookings_count($status = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $where = '1=1';
        if (!empty($status)) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
    }
}