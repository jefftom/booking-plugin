<?php
/**
 * Plugin Name: Booking Requests
 * Plugin URI: https://example.com/booking-requests
 * Description: Captures booking requests via Gravity Forms with custom dashboard and email approval workflow
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * Text Domain: booking-requests
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BR_PLUGIN_VERSION', '1.0.0');
define('BR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'br_activate');
function br_activate() {
    // Create database tables
    require_once BR_PLUGIN_DIR . 'includes/class-database.php';
    BR_Database::create_tables();
    
    // Set default options
    add_option('br_admin_email', get_option('admin_email'));
    add_option('br_week_start_day', 'saturday');
    add_option('br_approval_required', '1');
    add_option('br_calendar_months_to_show', '12');
    add_option('br_calendar_min_advance_days', '1');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'br_deactivate');
function br_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Initialize plugin
add_action('plugins_loaded', 'br_init');
function br_init() {
    // Load text domain
    load_plugin_textdomain('booking-requests', false, dirname(BR_PLUGIN_BASENAME) . '/languages');
	
	
	
    
    // Check if required plugins are active
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    if (is_plugin_active('gravityforms/gravityforms.php')) {
        // Gravity Forms is active
    } else {
        add_action('admin_notices', 'br_gravity_forms_missing_notice');
    }
    
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', 'br_elementor_missing_notice');
    }
    
    // Load dependencies - check if files exist first
    $required_files = array(
        'includes/class-database.php',
        'includes/class-admin-menu.php',
        'includes/class-form-handler.php',
        'includes/class-email-handler.php',
        'includes/class-pricing-engine.php',
        'includes/class-booking-calendar-widget.php',
        'includes/functions.php'
    );
    
    foreach ($required_files as $file) {
        $file_path = BR_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log('Booking Requests Plugin: Missing required file - ' . $file);
        }
    }
    
    // Initialize components only if classes exist
    if (class_exists('BR_Database')) {
        new BR_Database();
    }
    if (class_exists('BR_Admin_Menu')) {
        new BR_Admin_Menu();
    }
    if (class_exists('BR_Form_Handler')) {
        new BR_Form_Handler();
    }
    if (class_exists('BR_Email_Handler')) {
        new BR_Email_Handler();
    }
    if (class_exists('BR_Pricing_Engine')) {
        new BR_Pricing_Engine();
    }
    if (class_exists('BR_Booking_Calendar_Widget')) {
        new BR_Booking_Calendar_Widget();
    }
    
    // Load Gravity Forms addon if GF is active
    if (class_exists('GFForms')) {
        $gf_addon_path = BR_PLUGIN_DIR . 'includes/class-gravity-forms-addon.php';
        if (file_exists($gf_addon_path)) {
            require_once $gf_addon_path;
            if (class_exists('BR_Gravity_Forms_Addon')) {
                GFAddOn::register('BR_Gravity_Forms_Addon');
            }
        }
    }
    
    // Load Elementor widgets if Elementor is active
    if (did_action('elementor/loaded')) {
        add_action('elementor/widgets/widgets_registered', 'br_register_elementor_widgets');
    }
}

// Initialize email actions
add_action('init', 'br_init_email_actions');
function br_init_email_actions() {
    // Ensure email handler is loaded and instantiated
    if (class_exists('BR_Email_Handler')) {
        new BR_Email_Handler();
    }
}

// Register Elementor widgets
function br_register_elementor_widgets() {
    // Check if Elementor is available
    if (!class_exists('\Elementor\Widget_Base')) {
        return;
    }
    
    // Register Gravity Forms widget
    $widget_path = BR_PLUGIN_DIR . 'includes/class-elementor-widget.php';
    if (file_exists($widget_path)) {
        require_once $widget_path;
        if (class_exists('BR_Elementor_Widget')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new BR_Elementor_Widget());
        }
    }
    
    // Register Calendar widget
    $calendar_widget_path = BR_PLUGIN_DIR . 'includes/class-elementor-calendar-widget.php';
    if (file_exists($calendar_widget_path)) {
        require_once $calendar_widget_path;
        if (class_exists('BR_Elementor_Calendar_Widget')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new BR_Elementor_Calendar_Widget());
        }
    }
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'br_admin_enqueue_scripts');
function br_admin_enqueue_scripts($hook) {
    // Only load on our admin pages
    if (strpos($hook, 'booking-requests') === false) {
        return;
    }
    
    wp_enqueue_style('br-admin-styles', BR_PLUGIN_URL . 'admin/css/admin-styles.css', array(), BR_PLUGIN_VERSION);
    wp_enqueue_script('br-admin-scripts', BR_PLUGIN_URL . 'admin/js/admin-scripts.js', array('jquery'), BR_PLUGIN_VERSION, true);
    
    // Localize script
    wp_localize_script('br-admin-scripts', 'br_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('br_admin_nonce'),
        'strings' => array(
            'confirm_approve' => __('Are you sure you want to approve this booking?', 'booking-requests'),
            'confirm_deny' => __('Are you sure you want to deny this booking?', 'booking-requests'),
            'confirm_delete' => __('Are you sure you want to delete this booking?', 'booking-requests'),
            'processing' => __('Processing...', 'booking-requests'),
            'error' => __('An error occurred. Please try again.', 'booking-requests')
        )
    ));
}

// Enqueue public scripts and styles
add_action('wp_enqueue_scripts', 'br_public_enqueue_scripts');
function br_public_enqueue_scripts() {
    // Always load public styles
    wp_enqueue_style('br-public-styles', BR_PLUGIN_URL . 'public/css/public-styles.css', array(), BR_PLUGIN_VERSION);
    
    // Conditionally load calendar assets
    if (br_should_load_calendar_assets()) {
        // Use v3 files
        wp_enqueue_style('br-calendar-styles', BR_PLUGIN_URL . 'public/css/booking-calendar-v3.css', array(), BR_PLUGIN_VERSION);
        wp_enqueue_script('br-calendar-script', BR_PLUGIN_URL . 'public/js/booking-calendar-v3.js', array('jquery'), BR_PLUGIN_VERSION, true);
        
        // Get start day setting
        $start_day = get_option('br_week_start_day', 'saturday');
        $start_day_number = $start_day === 'sunday' ? 0 : 6;
        
        // Localize script
        wp_localize_script('br-calendar-script', 'br_calendar', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('br_calendar_nonce'),
            'start_day' => $start_day_number,
            'months_to_show' => get_option('br_calendar_months_to_show', '12'),
            'min_advance_days' => get_option('br_calendar_min_advance_days', '1'),
            'currency_symbol' => '€',
            'strings' => array(
                'select_week' => __('Select Week', 'booking-requests'),
                'week_unavailable' => __('This week is not available', 'booking-requests'),
                'loading' => __('Loading...', 'booking-requests'),
                'error' => __('An error occurred. Please try again.', 'booking-requests'),
                'booking_submitted' => __('Your booking request has been submitted!', 'booking-requests'),
                'please_fill_all' => __('Please fill in all required fields', 'booking-requests')
            )
        ));
    }
    
    // Load pricing calculator for Gravity Forms
    if (br_should_load_pricing_calculator()) {
        wp_enqueue_script('br-pricing-calculator', BR_PLUGIN_URL . 'public/js/pricing-calculator.js', array('jquery'), BR_PLUGIN_VERSION, true);
    }
}

// Check if calendar assets should be loaded
function br_should_load_calendar_assets() {
    // Check if we're on a page with the calendar shortcode
    if (is_singular()) {
        global $post;
        if (has_shortcode($post->post_content, 'booking_calendar')) {
            return true;
        }
    }
    
    // Check if we're in Elementor editor
    if (isset($_GET['elementor-preview'])) {
        return true;
    }
    
    // Check if page uses Elementor and might have our widget
    if (is_singular() && function_exists('elementor_theme_do_location')) {
        return true; // Load on all Elementor pages for now
    }
    
    return false;
}

// Check if pricing calculator should be loaded
function br_should_load_pricing_calculator() {
    // Load on all pages with Gravity Forms
    return class_exists('GFForms');
}

// Admin notices
function br_gravity_forms_missing_notice() {
    ?>
    <div class="notice notice-warning">
        <p><?php _e('Booking Requests: Gravity Forms is not active. Some features may not work properly.', 'booking-requests'); ?></p>
    </div>
    <?php
}

function br_elementor_missing_notice() {
    ?>
    <div class="notice notice-warning">
        <p><?php _e('Booking Requests: Elementor is not active. The Elementor widget will not be available.', 'booking-requests'); ?></p>
    </div>
    <?php
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . BR_PLUGIN_BASENAME, 'br_add_settings_link');
function br_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=booking-requests-settings') . '">' . __('Settings', 'booking-requests') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Register shortcodes
add_shortcode('booking_calendar', 'br_booking_calendar_shortcode');
function br_booking_calendar_shortcode($atts) {
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

// AJAX handlers for admin actions
add_action('wp_ajax_br_approve_booking', 'br_handle_approve_booking');
function br_handle_approve_booking() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'br_admin_nonce')) {
        wp_die('Invalid nonce');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    $booking_id = intval($_POST['booking_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'br_bookings';
    
    $updated = $wpdb->update(
        $table_name,
        array('status' => 'approved'),
        array('id' => $booking_id)
    );
    
    if ($updated !== false) {
        // Send approval email
        do_action('br_send_approval_email', $booking_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update booking');
    }
}

add_action('wp_ajax_br_deny_booking', 'br_handle_deny_booking');
function br_handle_deny_booking() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'br_admin_nonce')) {
        wp_die('Invalid nonce');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    $booking_id = intval($_POST['booking_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'br_bookings';
    
    $updated = $wpdb->update(
        $table_name,
        array('status' => 'denied'),
        array('id' => $booking_id)
    );
    
    if ($updated !== false) {
        // Send denial email
        do_action('br_send_denial_email', $booking_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update booking');
    }
}

add_action('wp_ajax_br_delete_booking', 'br_handle_delete_booking');
function br_handle_delete_booking() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'br_admin_nonce')) {
        wp_die('Invalid nonce');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    $booking_id = intval($_POST['booking_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'br_bookings';
    
    $deleted = $wpdb->delete(
        $table_name,
        array('id' => $booking_id)
    );
    
    if ($deleted) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to delete booking');
    }
}

// Add custom cron schedule for cleanup
add_filter('cron_schedules', 'br_add_cron_schedules');
function br_add_cron_schedules($schedules) {
    $schedules['br_weekly'] = array(
        'interval' => 604800, // 1 week in seconds
        'display' => __('Weekly', 'booking-requests')
    );
    return $schedules;
}

// Schedule cleanup on activation
register_activation_hook(__FILE__, 'br_schedule_cleanup');
function br_schedule_cleanup() {
    if (!wp_next_scheduled('br_cleanup_old_bookings')) {
        wp_schedule_event(time(), 'br_weekly', 'br_cleanup_old_bookings');
    }
}

// Clear scheduled cleanup on deactivation
register_deactivation_hook(__FILE__, 'br_clear_scheduled_cleanup');
function br_clear_scheduled_cleanup() {
    wp_clear_scheduled_hook('br_cleanup_old_bookings');
}

// Cleanup old bookings
add_action('br_cleanup_old_bookings', 'br_do_cleanup_old_bookings');
function br_do_cleanup_old_bookings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'br_bookings';
    
    // Delete denied bookings older than 30 days
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE status = 'denied' AND created_at < %s",
        date('Y-m-d H:i:s', strtotime('-30 days'))
    ));
}

// Add support for WordPress debugging
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', 'br_debug_info');
    function br_debug_info() {
        if (isset($_GET['br_debug']) && current_user_can('manage_options')) {
            echo '<pre>';
            echo 'Booking Requests Debug Info' . PHP_EOL;
            echo '=========================' . PHP_EOL;
            echo 'Plugin Version: ' . BR_PLUGIN_VERSION . PHP_EOL;
            echo 'WordPress Version: ' . get_bloginfo('version') . PHP_EOL;
            echo 'PHP Version: ' . phpversion() . PHP_EOL;
            echo 'Gravity Forms Active: ' . (class_exists('GFForms') ? 'Yes' : 'No') . PHP_EOL;
            echo 'Elementor Active: ' . (did_action('elementor/loaded') ? 'Yes' : 'No') . PHP_EOL;
            echo '</pre>';
            exit;
        }
    }
}