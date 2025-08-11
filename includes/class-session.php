<?php
/**
 * Session Management Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BCT_Session {
    
    public static function get_active_session($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE user_id = %d AND session_status = 'active'
        ", $user_id));
    }
    
    public static function get_user_sessions($user_id, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE user_id = %d 
            ORDER BY session_start DESC 
            LIMIT %d
        ", $user_id, $limit));
    }
    
    public static function get_session_bets($session_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_session_bets 
            WHERE session_id = %d 
            ORDER BY created_at ASC
        ", $session_id));
    }
    
    public static function create_session($user_id, $starting_bankroll) {
        global $wpdb;
        
        // Check for existing active session
        $existing = self::get_active_session($user_id);
        if ($existing) {
            return false;
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'craps_sessions',
            array(
                'user_id' => $user_id,
                'starting_bankroll' => $starting_bankroll,
                'session_status' => 'active'
            ),
            array('%d', '%f', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function end_session($session_id, $ending_bankroll, $notes = '') {
        global $wpdb;
        
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE id = %d AND session_status = 'active'
        ", $session_id));
        
        if (!$session) {
            return false;
        }
        
        $net_result = $ending_bankroll - $session->starting_bankroll;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'craps_sessions',
            array(
                'session_end' => current_time('mysql'),
                'ending_bankroll' => $ending_bankroll,
                'net_result' => $net_result,
                'session_status' => 'completed',
                'notes' => $notes
            ),
            array('id' => $session_id),
            array('%s', '%f', '%f', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    public static function log_bet($session_id, $bet_type, $bet_amount, $bet_result, $payout = 0) {
        global $wpdb;
        
        // Verify session is active
        $session = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}craps_sessions 
            WHERE id = %d AND session_status = 'active'
        ", $session_id));
        
        if (!$session) {
            return false;
        }
        
        // Insert bet
        $result = $wpdb->insert(
            $wpdb->prefix . 'craps_session_bets',
            array(
                'session_id' => $session_id,
                'bet_type' => $bet_type,
                'bet_amount' => $bet_amount,
                'bet_result' => $bet_result,
                'payout' => $payout
            ),
            array('%d', '%s', '%f', '%s', '%f')
        );
        
        if ($result) {
            // Update session totals
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}craps_sessions 
                SET total_wagered = total_wagered + %f 
                WHERE id = %d
            ", $bet_amount, $session_id));
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function get_session_summary($session_id) {
        global $wpdb;
        
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE id = %d
        ", $session_id));
        
        if (!$session) {
            return false;
        }
        
        $bets = $wpdb->get_results($wpdb->prepare("
            SELECT 
                bet_type, 
                COUNT(*) as count, 
                SUM(bet_amount) as total_amount,
                SUM(CASE WHEN bet_result = 'win' THEN payout ELSE 0 END) as total_winnings,
                SUM(CASE WHEN bet_result = 'lose' THEN bet_amount ELSE 0 END) as total_losses
            FROM {$wpdb->prefix}craps_session_bets 
            WHERE session_id = %d 
            GROUP BY bet_type
            ORDER BY count DESC
        ", $session_id));
        
        return array(
            'session' => $session,
            'bets' => $bets,
            'duration' => $session->session_end ? 
                human_time_diff(strtotime($session->session_start), strtotime($session->session_end)) : 
                human_time_diff(strtotime($session->session_start), current_time('timestamp'))
        );
    }
    
    public static function get_user_statistics($user_id) {
        global $wpdb;
        
        $session_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN session_status = 'completed' THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN session_status = 'active' THEN 1 END) as active_sessions,
                SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as total_net_result,
                AVG(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as avg_session_result,
                SUM(CASE WHEN session_status = 'completed' THEN total_wagered ELSE 0 END) as total_wagered,
                MAX(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as best_session,
                MIN(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as worst_session
            FROM {$wpdb->prefix}craps_sessions 
            WHERE user_id = %d
        ", $user_id));
        
        return $session_stats;
    }
    
    public static function validate_bet_type($bet_type) {
        $valid_types = array(
            'pass_line',
            'dont_pass',
            'come',
            'dont_come',
            'field',
            'place_6',
            'place_8',
            'place_4',
            'place_5',
            'place_9',
            'place_10',
            'hard_ways',
            'hard_4',
            'hard_6',
            'hard_8',
            'hard_10',
            'any_seven',
            'any_craps',
            'odds',
            'big_6',
            'big_8'
        );
        
        return in_array($bet_type, $valid_types);
    }
    
    public static function validate_bet_result($result) {
        return in_array($result, array('win', 'lose', 'push'));
    }
    
    public static function calculate_payout($bet_type, $bet_amount, $result) {
        if ($result !== 'win') {
            return 0;
        }
        
        // Standard craps payouts
        $payouts = array(
            'pass_line' => 1,      // 1:1
            'dont_pass' => 1,      // 1:1
            'come' => 1,           // 1:1
            'dont_come' => 1,      // 1:1
            'field' => 1,          // 1:1 (2,3,4,9,10,11,12 - varies by number)
            'place_6' => 1.167,    // 7:6
            'place_8' => 1.167,    // 7:6
            'place_4' => 1.8,      // 9:5
            'place_5' => 1.4,      // 7:5
            'place_9' => 1.4,      // 7:5
            'place_10' => 1.8,     // 9:5
            'hard_4' => 7,         // 7:1
            'hard_6' => 9,         // 9:1
            'hard_8' => 9,         // 9:1
            'hard_10' => 7,        // 7:1
            'any_seven' => 4,      // 4:1
            'any_craps' => 7,      // 7:1
            'odds' => 1,           // Varies, default 1:1
            'big_6' => 1,          // 1:1
            'big_8' => 1           // 1:1
        );
        
        $multiplier = $payouts[$bet_type] ?? 1;
        return $bet_amount * $multiplier;
    }
    
    public static function get_popular_bet_types() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                bet_type,
                COUNT(*) as bet_count,
                SUM(bet_amount) as total_wagered,
                AVG(bet_amount) as avg_bet_amount
            FROM {$wpdb->prefix}craps_session_bets
            GROUP BY bet_type
            ORDER BY bet_count DESC
            LIMIT 10
        ");
    }
}
?>