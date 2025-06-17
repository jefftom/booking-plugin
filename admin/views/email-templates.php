<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display messages
settings_errors('br_email_templates');

// Get saved templates or defaults
$admin_template = get_option('br_email_template_admin_notification', '');
$guest_template = get_option('br_email_template_guest_confirmation', '');
$approval_template = get_option('br_email_template_approval', '');
$denial_template = get_option('br_email_template_denial', '');
?>

<div class="wrap">
    <h1><?php _e('Email Templates', 'booking-requests'); ?></h1>
    
    <p><?php _e('Customize the email templates sent for booking notifications. Use the available placeholders to include dynamic content.', 'booking-requests'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('br_email_templates', 'br_email_templates_nonce'); ?>
        
        <div class="email-template-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#admin-notification" class="nav-tab nav-tab-active" data-tab="admin-notification">Admin Notification</a>
                <a href="#guest-confirmation" class="nav-tab" data-tab="guest-confirmation">Guest Confirmation</a>
                <a href="#approval" class="nav-tab" data-tab="approval">Booking Approval</a>
                <a href="#denial" class="nav-tab" data-tab="denial">Booking Denial</a>
            </h2>
            
            <!-- Admin Notification Template -->
            <div id="admin-notification" class="template-tab active">
                <h3><?php _e('Admin Notification Email', 'booking-requests'); ?></h3>
                <p class="description"><?php _e('Sent to admin when a new booking request is submitted.', 'booking-requests'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Available Placeholders', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <code>{guest_name}</code>, <code>{email}</code>, <code>{phone}</code>, 
                            <code>{checkin_date}</code>, <code>{checkout_date}</code>, <code>{nights}</code>,
                            <code>{total_price}</code>, <code>{services_list}</code>, <code>{message}</code>,
                            <code>{approve_url}</code>, <code>{deny_url}</code>, <code>{admin_url}</code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="admin_notification_template"><?php _e('Email Template', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <?php 
                            $settings = array(
                                'textarea_name' => 'admin_notification_template',
                                'media_buttons' => false,
                                'textarea_rows' => 20,
                                'teeny' => false,
                                'quicktags' => true
                            );
                            
                            $default_admin = '
<h2>New Booking Request</h2>

<h3>Guest Information</h3>
<p>
<strong>Name:</strong> {guest_name}<br>
<strong>Email:</strong> {email}<br>
<strong>Phone:</strong> {phone}
</p>

<h3>Booking Details</h3>
<p>
<strong>Check-in:</strong> {checkin_date}<br>
<strong>Check-out:</strong> {checkout_date}<br>
<strong>Nights:</strong> {nights}<br>
<strong>Total Price:</strong> {total_price}
</p>

{services_list}

<h3>Guest Message</h3>
<p>{message}</p>

<p>
<a href="{approve_url}" style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Approve Booking</a>
<a href="{deny_url}" style="background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none;">Deny Booking</a>
</p>

<p><a href="{admin_url}">View in Admin Dashboard</a></p>';
                            
                            wp_editor($admin_template ?: $default_admin, 'admin_notification_template', $settings);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Guest Confirmation Template -->
            <div id="guest-confirmation" class="template-tab" style="display: none;">
                <h3><?php _e('Guest Confirmation Email', 'booking-requests'); ?></h3>
                <p class="description"><?php _e('Sent to guest after submitting a booking request.', 'booking-requests'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Available Placeholders', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <code>{guest_name}</code>, <code>{checkin_date}</code>, <code>{checkout_date}</code>, 
                            <code>{nights}</code>, <code>{total_price}</code>, <code>{services_list}</code>,
                            <code>{site_name}</code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="guest_confirmation_template"><?php _e('Email Template', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <?php 
                            $default_guest = '
<h2>Thank You for Your Booking Request!</h2>

<p>Dear {guest_name},</p>

<p>We have received your booking request and will review it shortly. You will receive an email once your booking has been processed.</p>

<h3>Your Booking Details</h3>
<p>
<strong>Check-in:</strong> {checkin_date}<br>
<strong>Check-out:</strong> {checkout_date}<br>
<strong>Total Nights:</strong> {nights}<br>
<strong>Total Price:</strong> {total_price}
</p>

{services_list}

<p>If you have any questions, please don\'t hesitate to contact us.</p>

<p>Best regards,<br>{site_name}</p>';
                            
                            wp_editor($guest_template ?: $default_guest, 'guest_confirmation_template', $settings);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Approval Template -->
            <div id="approval" class="template-tab" style="display: none;">
                <h3><?php _e('Booking Approval Email', 'booking-requests'); ?></h3>
                <p class="description"><?php _e('Sent to guest when booking is approved.', 'booking-requests'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Available Placeholders', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <code>{guest_name}</code>, <code>{checkin_date}</code>, <code>{checkout_date}</code>, 
                            <code>{nights}</code>, <code>{total_price}</code>, <code>{services_list}</code>,
                            <code>{site_name}</code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="approval_template"><?php _e('Email Template', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <?php 
                            $default_approval = '
<h2 style="color: #28a745;">Your Booking Has Been Approved!</h2>

<p>Dear {guest_name},</p>

<p>Great news! Your booking request has been approved.</p>

<h3>Confirmed Booking Details</h3>
<p>
<strong>Check-in:</strong> {checkin_date}<br>
<strong>Check-out:</strong> {checkout_date}<br>
<strong>Total Nights:</strong> {nights}<br>
<strong>Total Price:</strong> {total_price}
</p>

{services_list}

<p>We look forward to welcoming you!</p>

<p>Best regards,<br>{site_name}</p>';
                            
                            wp_editor($approval_template ?: $default_approval, 'approval_template', $settings);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Denial Template -->
            <div id="denial" class="template-tab" style="display: none;">
                <h3><?php _e('Booking Denial Email', 'booking-requests'); ?></h3>
                <p class="description"><?php _e('Sent to guest when booking is denied.', 'booking-requests'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Available Placeholders', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <code>{guest_name}</code>, <code>{site_name}</code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="denial_template"><?php _e('Email Template', 'booking-requests'); ?></label>
                        </th>
                        <td>
                            <?php 
                            $default_denial = '
<h2>Update on Your Booking Request</h2>

<p>Dear {guest_name},</p>

<p>Thank you for your interest in booking with us. Unfortunately, we are unable to accommodate your request for the selected dates.</p>

<p>We encourage you to check our availability for alternative dates. We would love to welcome you at another time.</p>

<p>If you have any questions, please feel free to contact us.</p>

<p>Best regards,<br>{site_name}</p>';
                            
                            wp_editor($denial_template ?: $default_denial, 'denial_template', $settings);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<style>
.template-tab {
    background: #fff;
    border: 1px solid #ccc;
    padding: 20px;
    margin-top: -1px;
}
.nav-tab-wrapper {
    margin-bottom: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        $('.template-tab').hide();
        $('#' + tab).show();
    });
});
</script>