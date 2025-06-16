<?php
/**
 * Elementor Calendar Widget Class
 */
class BR_Elementor_Calendar_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'booking_calendar';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Booking Calendar', 'booking-requests');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-calendar';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['general'];
    }
    
    /**
     * Register widget controls
     */
    protected function _register_controls() {
        // Calendar Settings
        $this->start_controls_section(
            'calendar_settings',
            [
                'label' => __('Calendar Settings', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'months_to_show',
            [
                'label' => __('Months to Display', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 24,
                'default' => 12,
            ]
        );
        
        $this->add_control(
            'start_day',
            [
                'label' => __('Week Start Day', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'saturday' => __('Saturday', 'booking-requests'),
                    'sunday' => __('Sunday', 'booking-requests'),
                    'monday' => __('Monday', 'booking-requests'),
                ],
                'default' => 'saturday',
            ]
        );
        
        $this->add_control(
            'show_form',
            [
                'label' => __('Show Booking Form', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'booking-requests'),
                'label_off' => __('Hide', 'booking-requests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Calendar
        $this->start_controls_section(
            'calendar_style',
            [
                'label' => __('Calendar Style', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'calendar_background',
            [
                'label' => __('Background Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-calendar-month' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'header_background',
            [
                'label' => __('Header Background', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-calendar-month-header' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'available_color',
            [
                'label' => __('Available Week Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-week-start.br-available' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'booked_color',
            [
                'label' => __('Booked Week Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-week-start.br-booked' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'selected_color',
            [
                'label' => __('Selected Week Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-calendar-day.br-selected' => 'background-color: {{VALUE}} !important',
                ],
            ]
        );
        
        $this->add_control(
            'price_color',
            [
                'label' => __('Price Text Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-day-price' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Form
        $this->start_controls_section(
            'form_style',
            [
                'label' => __('Form Style', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_form' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'form_background',
            [
                'label' => __('Form Background', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-calendar-booking-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'form_padding',
            [
                'label' => __('Form Padding', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .br-calendar-booking-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_background',
            [
                'label' => __('Submit Button Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-submit-btn' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_background',
            [
                'label' => __('Submit Button Hover Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-submit-btn:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Typography Section
        $this->start_controls_section(
            'typography_section',
            [
                'label' => __('Typography', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'month_header_typography',
                'label' => __('Month Header', 'booking-requests'),
                'selector' => '{{WRAPPER}} .br-calendar-month-header h4',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'day_typography',
                'label' => __('Day Numbers', 'booking-requests'),
                'selector' => '{{WRAPPER}} .br-day-number',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'label' => __('Price Text', 'booking-requests'),
                'selector' => '{{WRAPPER}} .br-day-price',
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Convert day name to number for the shortcode
        $day_map = array(
            'saturday' => 6,
            'sunday' => 0,
            'monday' => 1
        );
        
        $start_day_name = $settings['start_day'];
        $start_day_number = isset($day_map[$start_day_name]) ? $day_map[$start_day_name] : 6;
        
        // Build shortcode attributes
        $attributes = array(
            'months' => $settings['months_to_show'],
            'start_day' => $start_day_name, // Keep the name for the shortcode
            'show_form' => $settings['show_form']
        );
        
        // Add data attribute for JavaScript
        echo '<div data-start-day="' . $start_day_number . '">';
        
        $shortcode = '[booking_calendar';
        foreach ($attributes as $key => $value) {
            $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
        echo '</div>';
    }
}