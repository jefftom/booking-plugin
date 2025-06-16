<?php
/**
 * Elementor Widget Class
 */
class BR_Elementor_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'booking_request_form';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Booking Request Form', 'booking-requests');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-form-horizontal';
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
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Form Settings', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Get available Gravity Forms
        $forms = array();
        if (class_exists('GFAPI')) {
            $gravity_forms = GFAPI::get_forms();
            foreach ($gravity_forms as $form) {
                if (rgar($form, 'br_booking_enabled')) {
                    $forms[$form['id']] = $form['title'];
                }
            }
        }
        
        $this->add_control(
            'form_id',
            [
                'label' => __('Select Form', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $forms,
                'default' => '',
            ]
        );
        
        $this->add_control(
            'show_title',
            [
                'label' => __('Show Form Title', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'booking-requests'),
                'label_off' => __('Hide', 'booking-requests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_description',
            [
                'label' => __('Show Form Description', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'booking-requests'),
                'label_off' => __('Hide', 'booking-requests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_pricing_calculator',
            [
                'label' => __('Show Pricing Calculator', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'booking-requests'),
                'label_off' => __('Hide', 'booking-requests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'ajax_submit',
            [
                'label' => __('Enable AJAX', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'booking-requests'),
                'label_off' => __('No', 'booking-requests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Form Style', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'form_background',
            [
                'label' => __('Background Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-booking-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'form_padding',
            [
                'label' => __('Padding', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .br-booking-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'form_border_radius',
            [
                'label' => __('Border Radius', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .br-booking-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_box_shadow',
                'label' => __('Box Shadow', 'booking-requests'),
                'selector' => '{{WRAPPER}} .br-booking-form',
            ]
        );
        
        $this->end_controls_section();
        
        // Pricing Calculator Style
        $this->start_controls_section(
            'pricing_style_section',
            [
                'label' => __('Pricing Calculator Style', 'booking-requests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_pricing_calculator' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'pricing_background',
            [
                'label' => __('Background Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-pricing-calculator' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pricing_text_color',
            [
                'label' => __('Text Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-pricing-calculator' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pricing_border_color',
            [
                'label' => __('Border Color', 'booking-requests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .br-pricing-calculator' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['form_id'])) {
            echo '<p>' . __('Please select a form in the widget settings.', 'booking-requests') . '</p>';
            return;
        }
        
        if (!class_exists('GFAPI')) {
            echo '<p>' . __('Gravity Forms is required for this widget.', 'booking-requests') . '</p>';
            return;
        }
        
        $form = GFAPI::get_form($settings['form_id']);
        if (!$form || !rgar($form, 'br_booking_enabled')) {
            echo '<p>' . __('Selected form is not configured for booking requests.', 'booking-requests') . '</p>';
            return;
        }
        
        // Add wrapper div
        echo '<div class="br-booking-form-wrapper">';
        
        // Show pricing calculator if enabled
        if ($settings['show_pricing_calculator'] === 'yes' && rgar($form, 'br_show_pricing')) {
            $this->render_pricing_calculator($form);
        }
        
        // Render form
        echo '<div class="br-booking-form">';
        
        $title = $settings['show_title'] === 'yes';
        $description = $settings['show_description'] === 'yes';
        $ajax = $settings['ajax_submit'] === 'yes';
        
        gravity_form(
            $settings['form_id'],
            $title,
            $description,
            false,
            null,
            $ajax,
            1,
            true
        );
        
        echo '</div>';
        echo '</div>';
        
        // Add inline script for date field integration
        $this->add_inline_script($form);
    }
    
    /**
     * Render pricing calculator
     */
    private function render_pricing_calculator($form) {
        ?>
        <div class="br-pricing-calculator" style="display: none;">
            <h3><?php _e('Pricing Details', 'booking-requests'); ?></h3>
            <div class="br-pricing-content">
                <div class="br-pricing-row">
                    <span class="br-pricing-label"><?php _e('Weekly Rate:', 'booking-requests'); ?></span>
                    <span class="br-pricing-value" id="br-weekly-rate">-</span>
                </div>
                <div class="br-pricing-row">
                    <span class="br-pricing-label"><?php _e('Number of Weeks:', 'booking-requests'); ?></span>
                    <span class="br-pricing-value" id="br-total-weeks">-</span>
                </div>
                <div class="br-pricing-row br-pricing-total">
                    <span class="br-pricing-label"><?php _e('Total Price:', 'booking-requests'); ?></span>
                    <span class="br-pricing-value" id="br-total-price">-</span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add inline script
     */
    private function add_inline_script($form) {
        $checkin_field_id = rgar($form, 'br_field_checkin_date');
        $checkout_field_id = rgar($form, 'br_field_checkout_date');
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var checkinField = $('#input_<?php echo $form['id']; ?>_<?php echo $checkin_field_id; ?>');
            var checkoutField = $('#input_<?php echo $form['id']; ?>_<?php echo $checkout_field_id; ?>');
            var calculator = $('.br-pricing-calculator');
            
            function updatePricing() {
                var checkin = checkinField.val();
                var checkout = checkoutField.val();
                
                if (checkin && checkout) {
                    $.ajax({
                        url: br_pricing.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'br_calculate_price',
                            nonce: br_pricing.nonce,
                            checkin: checkin,
                            checkout: checkout
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#br-weekly-rate').text(response.data.formatted_rate);
                                $('#br-total-weeks').text(response.data.total_weeks);
                                $('#br-total-price').text(response.data.formatted_total);
                                calculator.slideDown();
                            } else {
                                calculator.slideUp();
                                if (response.data.message) {
                                    alert(response.data.message);
                                }
                            }
                        }
                    });
                } else {
                    calculator.slideUp();
                }
            }
            
            // Update pricing when dates change
            checkinField.on('change', updatePricing);
            checkoutField.on('change', updatePricing);
            
            // Date validation
            checkinField.on('change', function() {
                var checkin = new Date($(this).val());
                checkoutField.attr('min', $(this).val());
                
                // Set minimum checkout date (7 days later)
                var minCheckout = new Date(checkin);
                minCheckout.setDate(minCheckout.getDate() + 7);
                var minCheckoutStr = minCheckout.toISOString().split('T')[0];
                
                if (checkoutField.val() && new Date(checkoutField.val()) < minCheckout) {
                    checkoutField.val(minCheckoutStr);
                }
            });
        });
        </script>
        <?php
    }
}