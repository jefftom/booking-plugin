<?php

class BR_Booking_Calendar_Widget {
    
    public function __construct() {
        // Register shortcode
        add_shortcode('booking_calendar', array($this, 'render_shortcode'));
        
        // AJAX handlers for calendar data
        add_action('wp_ajax_br_get_calendar_data', array($this, 'ajax_get_calendar_data'));
        add_action('wp_ajax_nopriv_br_get_calendar_data', array($this, 'ajax_get_calendar_data'));
        
        // AJAX handler for booking submission (handled by form handler)
        add_action('wp_ajax_br_submit_calendar_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_nopriv_br_submit_calendar_booking', array($this, 'ajax_submit_booking'));
    }
    
    /**
     * Render calendar shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'start_day' => get_option('br_week_start_day', 'saturday'),
            'months' => get_option('br_calendar_months_to_show', '12'),
            'min_advance' => get_option('br_calendar_min_advance_days', '1')
        ), $atts);
        
        ob_start();
        ?>
        <div class="br-calendar-widget" 
             data-start-day="<?php echo esc_attr($atts['start_day']); ?>"
             data-months="<?php echo esc_attr($atts['months']); ?>"
             data-min-advance="<?php echo esc_attr($atts['min_advance']); ?>">
            <div class="br-calendar-loading"><?php _e('Loading calendar...', 'booking-requests'); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to get calendar data
     */
    public function ajax_get_calendar_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'br_calendar_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // Get start day setting
        $start_day = get_option('br_week_start_day', 'saturday');
        $start_day_number = $start_day === 'sunday' ? 0 : 6;
        
        // Get pricing data
        $pricing_engine = new BR_Pricing_Engine();
        $pricing_data = array();
        
        // Generate dates for each week start day (Sunday or Saturday)
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        // Move to first start day
        while ($current->format('w') != $start_day_number) {
            $current->modify('+1 day');
        }
        
        // Generate pricing for each week
        while ($current <= $end) {
            $week_end = clone $current;
            $week_end->modify('+6 days');
            
            $rate = $pricing_engine->calculate_total_price(
                $current->format('Y-m-d'),
                $week_end->format('Y-m-d')
            );
            
            if ($rate > 0) {
                $pricing_data[$current->format('Y-m-d')] = array(
                    'rate' => $rate,
                    'available' => $this->check_availability($current->format('Y-m-d'), $week_end->format('Y-m-d'))
                );
            }
            
            $current->modify('+7 days');
        }
        
        // Get booked dates
        $booked_dates = $this->get_booked_dates($start_date, $end_date);
        
        wp_send_json_success(array(
            'pricing_data' => $pricing_data,
            'booked_dates' => $booked_dates,
            'start_day' => $start_day_number,
            'debug' => array(
                'configured_start_day' => $start_day,
                'day_number' => $start_day_number,
                'first_week' => key($pricing_data),
                'total_weeks' => count($pricing_data)
            )
        ));
    }
    
    /**
     * AJAX handler for booking submission
     * Note: This is now handled by BR_Form_Handler class
     */
    public function ajax_submit_booking() {
        // This method is here for backwards compatibility
        // The actual handling is done by BR_Form_Handler::handle_calendar_booking()
        
        if (class_exists('BR_Form_Handler')) {
            $form_handler = new BR_Form_Handler();
            $form_handler->handle_calendar_booking();
        } else {
            wp_send_json_error('Form handler not available');
        }
    }
    
    /**
     * Check availability for dates
     */
    private function check_availability($start_date, $end_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        
        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE status IN ('pending', 'approved')
            AND (
                (checkin_date <= %s AND checkout_date > %s) OR
                (checkin_date < %s AND checkout_date >= %s) OR
                (checkin_date >= %s AND checkout_date <= %s)
            )",
            $start_date, $start_date,
            $end_date, $end_date,
            $start_date, $end_date
        ));
        
        return $conflict == 0;
    }
    
    /**
     * Get booked dates
     */
    private function get_booked_dates($start_date, $end_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'br_bookings';
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT checkin_date, checkout_date FROM $table_name 
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
                $booked_dates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }
        }
        
        return array_unique($booked_dates);
    }
    
    /**
     * Get calendar configuration
     */
    public function get_calendar_config() {
        return array(
            'start_day' => get_option('br_week_start_day', 'saturday'),
            'months_to_show' => get_option('br_calendar_months_to_show', '12'),
            'min_advance_days' => get_option('br_calendar_min_advance_days', '1'),
            'currency_symbol' => '€'
        );
    }
    
    /**
     * Enqueue calendar assets
     * Note: This is now handled by the main plugin file
     */
    public function enqueue_assets() {
        // Assets are enqueued in booking-requests.php
        // This method kept for backwards compatibility
    }
}