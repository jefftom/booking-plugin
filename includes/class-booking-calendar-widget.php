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
            'start_day' => get_option('br_week_start_day', 'sunday'),
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
        
        // Get pricing data
        $pricing_engine = new BR_Pricing_Engine();
        $pricing_data = $pricing_engine->get_pricing_for_dates($start_date, $end_date);
        
        // Get booked dates
        $booked_dates = BR_Database::get_booked_dates($start_date, $end_date);
        
        // Format booked dates for frontend
        $formatted_booked_dates = array();
        foreach ($booked_dates as $date_info) {
            if ($date_info['status'] === 'approved') {
                // Only show approved bookings as booked
                $formatted_booked_dates[] = $date_info['date'];
            }
        }
        
        wp_send_json_success(array(
            'pricing_data' => $pricing_data,
            'booked_dates' => $formatted_booked_dates,
            'min_nights' => 3 // Minimum 3 nights
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
     * Get calendar configuration
     */
    public function get_calendar_config() {
        return array(
            'start_day' => get_option('br_week_start_day', 'sunday'),
            'months_to_show' => get_option('br_calendar_months_to_show', '12'),
            'min_advance_days' => get_option('br_calendar_min_advance_days', '1'),
            'min_nights' => 3,
            'currency_symbol' => '€'
        );
    }
}