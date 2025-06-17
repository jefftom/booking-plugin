<?php

class BR_Pricing_Engine {
    
    private $pricing_periods;
    
    public function __construct() {
        $this->pricing_periods = $this->get_pricing_periods();
    }
    
    /**
     * Get pricing periods
     */
    private function get_pricing_periods() {
        // Try to get from database first
        $saved_periods = get_option('br_pricing_periods', array());
        
        if (!empty($saved_periods)) {
            return $saved_periods;
        }
        
        // Default periods if none saved
        return array(
            array(
                'start' => '2025-07-05',
                'end' => '2025-09-02',
                'daily_price' => 1214,
                'weekly_price' => 8498  // 1214 * 7
            ),
            array(
                'start' => '2025-09-03',
                'end' => '2025-09-09',
                'daily_price' => 1071,
                'weekly_price' => 7497  // 1071 * 7
            ),
            array(
                'start' => '2025-09-10',
                'end' => '2025-09-16',
                'daily_price' => 928,
                'weekly_price' => 6496   // 928 * 7
            ),
            array(
                'start' => '2025-09-17',
                'end' => '2025-10-28',
                'daily_price' => 814,
                'weekly_price' => 5698   // 814 * 7
            ),
            array(
                'start' => '2025-10-29',
                'end' => '2026-03-23',
                'daily_price' => 714,
                'weekly_price' => 4998   // 714 * 7
            ),
            array(
                'start' => '2026-03-24',
                'end' => '2026-06-15',
                'daily_price' => 814,
                'weekly_price' => 5698   // 814 * 7
            ),
            array(
                'start' => '2026-06-16',
                'end' => '2026-06-29',
                'daily_price' => 928,
                'weekly_price' => 6496   // 928 * 7
            ),
            array(
                'start' => '2026-06-30',
                'end' => '2026-07-06',
                'daily_price' => 1071,
                'weekly_price' => 7497  // 1071 * 7
            ),
            array(
                'start' => '2026-07-07',
                'end' => '2026-08-31',
                'daily_price' => 1199,
                'weekly_price' => 8393  // 1199 * 7
            ),
            array(
                'start' => '2026-09-01',
                'end' => '2026-09-07',
                'daily_price' => 1071,
                'weekly_price' => 7497  // 1071 * 7
            ),
            array(
                'start' => '2026-09-08',
                'end' => '2026-09-14',
                'daily_price' => 928,
                'weekly_price' => 6496   // 928 * 7
            ),
            array(
                'start' => '2026-09-15',
                'end' => '2026-10-26',
                'daily_price' => 814,
                'weekly_price' => 5698   // 814 * 7
            ),
            array(
                'start' => '2026-10-27',
                'end' => '2027-03-22',
                'daily_price' => 757,
                'weekly_price' => 5299   // 757 * 7
            )
        );
    }
    
    /**
     * Calculate total price for date range
     */
    public function calculate_total_price($checkin_date, $checkout_date) {
        $checkin = new DateTime($checkin_date);
        $checkout = new DateTime($checkout_date);
        
        // Calculate number of nights
        $nights = $checkin->diff($checkout)->days;
        
        // Minimum 3 nights
        if ($nights < 3) {
            return 0;
        }
        
        // Calculate total price by summing daily rates
        $total = 0;
        $current = clone $checkin;
        
        while ($current < $checkout) {
            $daily_rate = $this->get_daily_rate_for_date($current->format('Y-m-d'));
            $total += $daily_rate;
            $current->modify('+1 day');
        }
        
        // Round to nearest euro
        return round($total);
    }
    
    /**
     * Get daily rate for a specific date
     */
    public function get_daily_rate_for_date($date) {
        $check_date = new DateTime($date);
        
        foreach ($this->pricing_periods as $period) {
            $period_start = new DateTime($period['start']);
            $period_end = new DateTime($period['end']);
            
            if ($check_date >= $period_start && $check_date <= $period_end) {
                return $period['daily_price'];
            }
        }
        
        // Default rate if date is outside defined periods
        return 714; // Default to low season rate
    }
    
    /**
     * Get weekly rate for a specific date (for display purposes)
     */
    public function get_weekly_rate_for_date($date) {
        $check_date = new DateTime($date);
        
        foreach ($this->pricing_periods as $period) {
            $period_start = new DateTime($period['start']);
            $period_end = new DateTime($period['end']);
            
            if ($check_date >= $period_start && $check_date <= $period_end) {
                return $period['weekly_price'];
            }
        }
        
        // Default rate if date is outside defined periods
        return 4995;
    }
    
    /**
     * Get pricing for date range (for calendar display)
     */
    public function get_pricing_for_dates($start_date, $end_date) {
        $pricing = array();
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        while ($current <= $end) {
            $date_str = $current->format('Y-m-d');
            $pricing[$date_str] = array(
                'date' => $date_str,
                'daily_rate' => $this->get_daily_rate_for_date($date_str),
                'weekly_rate' => $this->get_weekly_rate_for_date($date_str),
                'available' => true
            );
            
            $current->modify('+1 day');
        }
        
        return $pricing;
    }
    
    /**
     * Check if date is in valid booking period
     */
    public function is_date_in_valid_period($date) {
        $check_date = new DateTime($date);
        
        foreach ($this->pricing_periods as $period) {
            $period_start = new DateTime($period['start']);
            $period_end = new DateTime($period['end']);
            
            if ($check_date >= $period_start && $check_date <= $period_end) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get all pricing periods
     */
    public function get_all_pricing_periods() {
        return $this->pricing_periods;
    }
    
    /**
     * Get price breakdown for a booking
     */
    public function get_price_breakdown($checkin_date, $checkout_date) {
        $breakdown = array();
        $current = new DateTime($checkin_date);
        $checkout = new DateTime($checkout_date);
        
        while ($current < $checkout) {
            $date_str = $current->format('Y-m-d');
            $breakdown[] = array(
                'date' => $date_str,
                'day' => $current->format('l'),
                'rate' => $this->get_daily_rate_for_date($date_str)
            );
            $current->modify('+1 day');
        }
        
        return $breakdown;
    }
}