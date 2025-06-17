<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display messages
settings_errors('br_pricing');

// Get saved periods or defaults
$saved_periods = get_option('br_pricing_periods', array());

// Default periods if none saved
if (empty($saved_periods)) {
    $saved_periods = array(
        array('start' => '2025-07-05', 'end' => '2025-09-02', 'daily_price' => 1214),
        array('start' => '2025-09-03', 'end' => '2025-09-09', 'daily_price' => 1071),
        array('start' => '2025-09-10', 'end' => '2025-09-16', 'daily_price' => 928),
        array('start' => '2025-09-17', 'end' => '2025-10-28', 'daily_price' => 814),
        array('start' => '2025-10-29', 'end' => '2026-03-23', 'daily_price' => 714),
        array('start' => '2026-03-24', 'end' => '2026-06-15', 'daily_price' => 814),
        array('start' => '2026-06-16', 'end' => '2026-06-29', 'daily_price' => 928),
        array('start' => '2026-06-30', 'end' => '2026-07-06', 'daily_price' => 1071),
        array('start' => '2026-07-07', 'end' => '2026-08-31', 'daily_price' => 1199),
        array('start' => '2026-09-01', 'end' => '2026-09-07', 'daily_price' => 1071),
        array('start' => '2026-09-08', 'end' => '2026-09-14', 'daily_price' => 928),
        array('start' => '2026-09-15', 'end' => '2026-10-26', 'daily_price' => 814),
        array('start' => '2026-10-27', 'end' => '2027-03-22', 'daily_price' => 757)
    );
}
?>

<div class="wrap">
    <h1><?php _e('Pricing Periods', 'booking-requests'); ?></h1>
    
    <p><?php _e('Define pricing periods with daily rates. Prices will be displayed on the calendar for each available day.', 'booking-requests'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('br_pricing', 'br_pricing_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped" id="pricing-periods-table">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('Order', 'booking-requests'); ?></th>
                    <th><?php _e('Start Date', 'booking-requests'); ?></th>
                    <th><?php _e('End Date', 'booking-requests'); ?></th>
                    <th><?php _e('Daily Price (€)', 'booking-requests'); ?></th>
                    <th><?php _e('Weekly Price (€)', 'booking-requests'); ?></th>
                    <th style="width: 100px;"><?php _e('Actions', 'booking-requests'); ?></th>
                </tr>
            </thead>
            <tbody id="pricing-periods-body">
                <?php foreach ($saved_periods as $index => $period) : ?>
                <tr class="pricing-period-row">
                    <td class="handle" style="cursor: move;">☰</td>
                    <td>
                        <input type="date" name="periods[<?php echo $index; ?>][start]" 
                               value="<?php echo esc_attr($period['start']); ?>" required>
                    </td>
                    <td>
                        <input type="date" name="periods[<?php echo $index; ?>][end]" 
                               value="<?php echo esc_attr($period['end']); ?>" required>
                    </td>
                    <td>
                        <input type="number" name="periods[<?php echo $index; ?>][daily_price]" 
                               value="<?php echo esc_attr($period['daily_price']); ?>" 
                               min="0" step="1" required class="daily-price-input">
                    </td>
                    <td>
                        <span class="weekly-price">€<?php echo number_format($period['daily_price'] * 7, 0, '.', ','); ?></span>
                    </td>
                    <td>
                        <button type="button" class="button button-small remove-period"><?php _e('Remove', 'booking-requests'); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>
            <button type="button" class="button" id="add-period"><?php _e('Add Pricing Period', 'booking-requests'); ?></button>
        </p>
        
        <div class="pricing-summary">
            <h3><?php _e('Pricing Calendar Preview', 'booking-requests'); ?></h3>
            <div id="pricing-calendar"></div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<style>
.pricing-period-row input[type="date"],
.pricing-period-row input[type="number"] {
    width: 100%;
}
.pricing-summary {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
}
#pricing-calendar {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 15px;
}
.pricing-period-preview {
    background: #fff;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.pricing-period-preview strong {
    color: #1a3a52;
}
.weekly-price {
    font-weight: bold;
    color: #28a745;
}
</style>

<script>
jQuery(document).ready(function($) {
    var periodIndex = <?php echo count($saved_periods); ?>;
    
    // Add new period
    $('#add-period').on('click', function() {
        var newRow = `
            <tr class="pricing-period-row">
                <td class="handle" style="cursor: move;">☰</td>
                <td>
                    <input type="date" name="periods[${periodIndex}][start]" required>
                </td>
                <td>
                    <input type="date" name="periods[${periodIndex}][end]" required>
                </td>
                <td>
                    <input type="number" name="periods[${periodIndex}][daily_price]" 
                           min="0" step="1" required class="daily-price-input">
                </td>
                <td>
                    <span class="weekly-price">€0</span>
                </td>
                <td>
                    <button type="button" class="button button-small remove-period">Remove</button>
                </td>
            </tr>
        `;
        $('#pricing-periods-body').append(newRow);
        periodIndex++;
    });
    
    // Remove period
    $(document).on('click', '.remove-period', function() {
        if (confirm('<?php _e('Are you sure you want to remove this pricing period?', 'booking-requests'); ?>')) {
            $(this).closest('tr').remove();
            updatePeriodIndexes();
        }
    });
    
    // Update weekly price when daily price changes
    $(document).on('input', '.daily-price-input', function() {
        var dailyPrice = parseFloat($(this).val()) || 0;
        var weeklyPrice = dailyPrice * 7;
        $(this).closest('tr').find('.weekly-price').text('€' + weeklyPrice.toLocaleString('de-DE'));
        updateCalendarPreview();
    });
    
    // Update indexes after removal
    function updatePeriodIndexes() {
        $('#pricing-periods-body tr').each(function(index) {
            $(this).find('input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/periods\[\d+\]/, 'periods[' + index + ']');
                    $(this).attr('name', name);
                }
            });
        });
    }
    
    // Update calendar preview
    function updateCalendarPreview() {
        var periods = [];
        $('#pricing-periods-body tr').each(function() {
            var start = $(this).find('input[name*="[start]"]').val();
            var end = $(this).find('input[name*="[end]"]').val();
            var price = $(this).find('input[name*="[daily_price]"]').val();
            
            if (start && end && price) {
                periods.push({
                    start: new Date(start),
                    end: new Date(end),
                    price: parseFloat(price)
                });
            }
        });
        
        // Sort periods by start date
        periods.sort(function(a, b) {
            return a.start - b.start;
        });
        
        // Display preview
        var html = '';
        periods.forEach(function(period) {
            var startStr = period.start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            var endStr = period.end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            html += `
                <div class="pricing-period-preview">
                    <strong>${startStr} - ${endStr}</strong><br>
                    €${period.price}/night • €${period.price * 7}/week
                </div>
            `;
        });
        
        $('#pricing-calendar').html(html || '<p>No pricing periods defined</p>');
    }
    
    // Initial preview
    updateCalendarPreview();
    
    // Make table sortable (if jQuery UI is available)
    if ($.fn.sortable) {
        $('#pricing-periods-body').sortable({
            handle: '.handle',
            update: function() {
                updatePeriodIndexes();
            }
        });
    }
});
</script>