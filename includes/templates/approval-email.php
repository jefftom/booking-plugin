<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Approved</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #27ae60;
            color: #fff;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .checkmark {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
        }
        .booking-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .booking-details h3 {
            margin-top: 0;
            color: #27ae60;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .total-price {
            font-size: 24px;
            color: #27ae60;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .next-steps {
            background-color: #e8f5e9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .next-steps h3 {
            margin-top: 0;
            color: #27ae60;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 8px 8px;
        }
        .footer p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="checkmark">✓</div>
            <h1>Your Booking is Approved!</h1>
        </div>
        
        <div class="content">
            <p>Dear <?php echo esc_html($guest_name); ?>,</p>
            
            <p>Great news! Your booking request has been approved. We're looking forward to welcoming you!</p>
            
            <div class="booking-details">
                <h3>Confirmed Booking Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Booking Reference:</span>
                    <span>#<?php echo $id; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span><?php echo esc_html($checkin_formatted); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span><?php echo esc_html($checkout_formatted); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Nights:</span>
                    <span><?php echo $nights; ?> nights</span>
                </div>
                
                <?php if (!empty($weeks_info)): ?>
                    <div style="margin-top: 20px;">
                        <strong>Confirmed Weeks:</strong>
                        <?php echo $weeks_info; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="total-price">
                Total Amount Due: <?php echo isset($total_price) ? esc_html($total_price) : esc_html($total_price_formatted); ?>
            </div>
            
            <div class="next-steps">
                <h3>Next Steps</h3>
                <ol>
                    <li>Payment instructions will be sent separately</li>
                    <li>Please complete payment within 48 hours to secure your booking</li>
                    <li>You will receive a final confirmation once payment is received</li>
                    <li>Check-in details will be sent closer to your arrival date</li>
                </ol>
            </div>
            
            <p>If you have any questions about your booking, please don't hesitate to contact us.</p>
            
            <p>We look forward to hosting you!</p>
        </div>
        
        <div class="footer">
            <p><strong><?php echo get_bloginfo('name'); ?></strong></p>
            <p>This is an automated confirmation. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>