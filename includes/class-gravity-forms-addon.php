<?php
/**
 * Gravity Forms Add-on
 */
class BR_Gravity_Forms_Addon extends GFAddOn {
    
    protected $_version = '1.0.0';
    protected $_min_gravityforms_version = '2.5';
    protected $_slug = 'booking-requests';
    protected $_path = 'booking-requests/booking-requests.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Booking Requests';
    protected $_short_title = 'Booking Requests';
    
    private static $_instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Initialize
     */
    public function init() {
        parent::init();
        
        // Add custom merge tags
        add_filter('gform_replace_merge_tags', array($this, 'replace_merge_tags'), 10, 7);
        
        // Add form settings
        add_filter('gform_form_settings', array($this, 'form_settings'), 10, 2);
        add_filter('gform_pre_form_settings_save', array($this, 'save_form_settings'));
        
        // Add custom validation
        add_filter('gform_validation', array($this, 'validate_booking_form'));
        
        // Process form submission
        add_action('gform_after_submission', array($this, 'process_booking_submission'), 10, 2);
    }
    
    /**
     * Form settings fields
     */
    public function form_settings($form) {
        $settings = parent::form_settings($form);
        $settings['Booking Requests'] = array(
            'booking_enabled' => '
                <tr>
                    <th>' . __('Enable Booking Requests', 'booking-requests') . '</th>
                    <td>
                        <input type="checkbox" name="br_booking_enabled" id="br_booking_enabled" value="1" ' . checked(rgar($form, 'br_booking_enabled'), 1, false) . ' />
                        <label for="br_booking_enabled">' . __('Process this form as a booking request', 'booking-requests') . '</label>
                    </td>
                </tr>',
            'field_mapping' => '
                <tr class="br-field-mapping" style="' . (rgar($form, 'br_booking_enabled') ? '' : 'display:none;') . '">
                    <th>' . __('Field Mapping', 'booking-requests') . '</th>
                    <td>
                        ' . $this->get_field_mapping_settings($form) . '
                    </td>
                </tr>',
            'pricing_display' => '
                <tr class="br-pricing-display" style="' . (rgar($form, 'br_booking_enabled') ? '' : 'display:none;') . '">
                    <th>' . __('Show Pricing Calculator', 'booking-requests') . '</th>
                    <td>
                        <input type="checkbox" name="br_show_pricing" id="br_show_pricing" value="1" ' . checked(rgar($form, 'br_show_pricing'), 1, false) . ' />
                        <label for="br_show_pricing">' . __('Display pricing calculator when dates are selected', 'booking-requests') . '</label>
                    </td>
                </tr>'
        );
        
        return $settings;
    }
    
    /**
     * Get field mapping settings
     */
    private function get_field_mapping_settings($form) {
        $fields = array(
            'first_name' => __('First Name', 'booking-requests'),
            'last_name' => __('Last Name', 'booking-requests'),
            'email' => __('Email', 'booking-requests'),
            'phone' => __('Phone', 'booking-requests'),
            'details' => __('Details/Message', 'booking-requests'),
            'checkin_date' => __('Check-in Date', 'booking-requests'),
            'checkout_date' => __('Check-out Date', 'booking-requests')
        );
        
        $html = '<table class="gforms_form_settings">';
        
        foreach ($fields as $key => $label) {
            $html .= '<tr>';
            $html .= '<td><label for="br_field_' . $key . '">' . $label . '</label></td>';
            $html .= '<td>';
            $html .= '<select name="br_field_' . $key . '" id="br_field_' . $key . '">';
            $html .= '<option value="">' . __('Select a field', 'booking-requests') . '</option>';
            
            foreach ($form['fields'] as $field) {
                $selected = rgar($form, 'br_field_' . $key) == $field->id ? 'selected="selected"' : '';
                $html .= '<option value="' . $field->id . '" ' . $selected . '>' . GFCommon::get_label($field) . '</option>';
            }
            
            $html .= '</select>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        return $html;
    }
    
    /**
     * Save form settings
     */
    public function save_form_settings($form, $settings) {
        $form['br_booking_enabled'] = rgpost('br_booking_enabled');
        $form['br_show_pricing'] = rgpost('br_show_pricing');
        
        // Save field mappings
        $fields = array('first_name', 'last_name', 'email', 'phone', 'details', 'checkin_date', 'checkout_date');
        foreach ($fields as $field) {
            $form['br_field_' . $field] = rgpost('br_field_' . $field);
        }
        
        return $form;
    }
    
    /**
     * Validate booking form
     */
    public function validate_booking_form($validation_result) {
        $form = $validation_result['form'];
        
        if (!rgar($form, 'br_booking_enabled')) {
            return $validation_result;
        }
        
        $checkin_field_id = rgar($form, 'br_field_checkin_date');
        $checkout_field_id = rgar($form, 'br_field_checkout_date');
        
        $checkin_value = rgpost('input_' . $checkin_field_id);
        $checkout_value = rgpost('input_' . $checkout_field_id);
        
        if ($checkin_value && $checkout_value) {
            $checkin = new DateTime($checkin_value);
            $checkout = new DateTime($checkout_value);
            $days = $checkin->diff($checkout)->days;
            
            // Validate minimum stay
            if ($days < 7) {
                $validation_result['is_valid'] = false;
                foreach ($form['fields'] as &$field) {
                    if ($field->id == $checkout_field_id) {
                        $field->failed_validation = true;
                        $field->validation_message = __('Minimum stay is 7 nights', 'booking-requests');
                    }
                }
            }
            
            // Check availability
            global $wpdb;
            $table_name = $wpdb->prefix . 'booking_requests';
            
            $overlapping = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                WHERE status = 'approved' 
                AND NOT (checkin_date >= %s OR checkout_date <= %s)",
                $checkout_value,
                $checkin_value
            ));
            
            if ($overlapping > 0) {
                $validation_result['is_valid'] = false;
                foreach ($form['fields'] as &$field) {
                    if ($field->id == $checkin_field_id) {
                        $field->failed_validation = true;
                        $field->validation_message = __('These dates are not available', 'booking-requests');
                    }
                }
            }
        }
        
        $validation_result['form'] = $form;
        return $validation_result;
    }
    
    /**
     * Process booking submission
     */
    public function process_booking_submission($entry, $form) {
        if (!rgar($form, 'br_booking_enabled')) {
            return;
        }
        
        // Get field values
        $booking_data = array(
            'first_name' => rgar($entry, rgar($form, 'br_field_first_name')),
            'last_name' => rgar($entry, rgar($form, 'br_field_last_name')),
            'email' => rgar($entry, rgar($form, 'br_field_email')),
            'phone' => rgar($entry, rgar($form, 'br_field_phone')),
            'details' => rgar($entry, rgar($form, 'br_field_details')),
            'checkin_date' => rgar($entry, rgar($form, 'br_field_checkin_date')),
            'checkout_date' => rgar($entry, rgar($form, 'br_field_checkout_date')),
            'form_entry_id' => $entry['id']
        );
        
        // Create booking
        $booking_id = BR_Booking_Requests::create_booking($booking_data);
        
        if ($booking_id) {
            // Add note to entry
            GFFormsModel::add_note($entry['id'], 0, 'Booking Request', sprintf(__('Booking request created with ID: %d', 'booking-requests'), $booking_id));
            
            // Update entry meta
            gform_update_meta($entry['id'], 'booking_request_id', $booking_id);
        }
    }
    
    /**
     * Replace merge tags
     */
    public function replace_merge_tags($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
        if (strpos($text, '{booking_weekly_rate}') !== false) {
            $checkin = rgar($entry, rgar($form, 'br_field_checkin_date'));
            if ($checkin) {
                $rate = BR_Booking_Requests::get_weekly_rate($checkin);
                $formatted_rate = '€' . number_format($rate, 0, ',', '.');
                $text = str_replace('{booking_weekly_rate}', $formatted_rate, $text);
            }
        }
        
        if (strpos($text, '{booking_total_price}') !== false) {
            $checkin = rgar($entry, rgar($form, 'br_field_checkin_date'));
            $checkout = rgar($entry, rgar($form, 'br_field_checkout_date'));
            
            if ($checkin && $checkout) {
                $checkin_date = new DateTime($checkin);
                $checkout_date = new DateTime($checkout);
                $days = $checkin_date->diff($checkout_date)->days;
                $weeks = ceil($days / 7);
                $rate = BR_Booking_Requests::get_weekly_rate($checkin);
                $total = $rate * $weeks;
                $formatted_total = '€' . number_format($total, 0, ',', '.');
                $text = str_replace('{booking_total_price}', $formatted_total, $text);
            }
        }
        
        return $text;
    }
    
    /**
     * Scripts
     */
    public function scripts() {
        $scripts = array(
            array(
                'handle' => 'br_gf_frontend',
                'src' => BR_PLUGIN_URL . 'public/js/gf-frontend.js',
                'version' => $this->_version,
                'deps' => array('jquery'),
                'enqueue' => array(
                    array('field_types' => array('date'))
                )
            )
        );
        
        return array_merge(parent::scripts(), $scripts);
    }
}