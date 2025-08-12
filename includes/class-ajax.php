<?php
/**
 * AJAX Handler Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BCT_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Frontend AJAX actions
        add_action('wp_ajax_bct_start_session', array($this, 'start_session'));
        add_action('wp_ajax_bct_end_session', array($this, 'end_session'));
        add_action('wp_ajax_bct_log_bet', array($this, 'log_bet'));
        add_action('wp_ajax_bct_get_session_data', array($this, 'get_session_data'));
        add_action('wp_ajax_bct_get_user_stats', array($this, 'get_user_stats'));
        add_action('wp_ajax_bct_submit_feedback', array($this, 'submit_feedback'));
        
        // Admin AJAX actions
        add_action('wp_ajax_bct_get_analytics_data', array($this, 'get_analytics_data'));
        add_action('wp_ajax_bct_manage_feedback', array($this, 'manage_feedback'));
		
		add_action('wp_ajax_bct_get_casinos', array($this, 'get_casinos'));
		add_action('wp_ajax_nopriv_bct_get_casinos', array($this, 'get_casinos'));
    }
    
    public function start_session() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $starting_bankroll = floatval($_POST['starting_bankroll'] ?? 0);
        
        if ($starting_bankroll <= 0) {
            wp_send_json_error('Invalid starting bankroll amount');
        }
        
        global $wpdb;
        
        // Check for active session
        $active_session = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}craps_sessions 
            WHERE user_id = %d AND session_status = 'active'
        ", $user_id));
        
        if ($active_session) {
            wp_send_json_error('You already have an active session');
        }
        
        // Create new session
        $result = $wpdb->insert(
            $wpdb->prefix . 'craps_sessions',
            array(
                'user_id' => $user_id,
                'starting_bankroll' => $starting_bankroll,
                'session_status' => 'active'
            ),
            array('%d', '%f', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array(
                'session_id' => $wpdb->insert_id,
                'message' => 'Session started successfully'
            ));
        } else {
            wp_send_json_error('Failed to start session');
        }
    }
    
    public function end_session() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $session_id = intval($_POST['session_id'] ?? 0);
        $ending_bankroll = floatval($_POST['ending_bankroll'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        global $wpdb;
        
        // Verify session ownership
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE id = %d AND user_id = %d AND session_status = 'active'
        ", $session_id, $user_id));
        
        if (!$session) {
            wp_send_json_error('Session not found or already ended');
        }
        
        // Calculate net result
        $net_result = $ending_bankroll - $session->starting_bankroll;
        
        // Update session
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
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Session ended successfully',
                'net_result' => $net_result
            ));
        } else {
            wp_send_json_error('Failed to end session');
        }
    }
    
    public function log_bet() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $bet_type = sanitize_text_field($_POST['bet_type'] ?? '');
        $bet_amount = floatval($_POST['bet_amount'] ?? 0);
        $bet_result = sanitize_text_field($_POST['bet_result'] ?? '');
        $payout = floatval($_POST['payout'] ?? 0);
        
        if (empty($bet_type) || $bet_amount <= 0 || empty($bet_result)) {
            wp_send_json_error('Missing required bet information');
        }
        
        global $wpdb;
        
        // Verify session
        $user_id = get_current_user_id();
        $session = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}craps_sessions 
            WHERE id = %d AND user_id = %d AND session_status = 'active'
        ", $session_id, $user_id));
        
        if (!$session) {
            wp_send_json_error('Invalid or inactive session');
        }
        
        // Insert bet record
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
            
            wp_send_json_success('Bet logged successfully');
        } else {
            wp_send_json_error('Failed to log bet');
        }
    }
    
    public function get_session_data() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Get active session
        $active_session = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE user_id = %d AND session_status = 'active'
        ", $user_id));
        
        // Get recent sessions
        $recent_sessions = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}craps_sessions 
            WHERE user_id = %d AND session_status = 'completed'
            ORDER BY session_end DESC 
            LIMIT 10
        ", $user_id));
        
        wp_send_json_success(array(
            'active_session' => $active_session,
            'recent_sessions' => $recent_sessions
        ));
    }
    
    public function get_user_stats() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        
        if (function_exists('bct_get_user_statistics')) {
            $stats = bct_get_user_statistics($user_id);
        } else {
            // Fallback basic stats
            global $wpdb;
            $stats = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(*) as total_sessions,
                    COUNT(CASE WHEN session_status = 'completed' THEN 1 END) as completed_sessions,
                    SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as total_net_result,
                    AVG(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as avg_session_result,
                    MAX(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as best_session
                FROM {$wpdb->prefix}craps_sessions 
                WHERE user_id = %d
            ", $user_id));
            
            $stats = array('sessions' => $stats);
        }
        
        wp_send_json_success($stats);
    }
    
    public function submit_feedback() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $feedback_type = sanitize_text_field($_POST['feedback_type'] ?? 'comment');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = sanitize_textarea_field($_POST['content'] ?? '');
        
        if (empty($title) || empty($content)) {
            wp_send_json_error('Title and content are required');
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'craps_user_feedback',
            array(
                'user_id' => $user_id,
                'feedback_type' => $feedback_type,
                'title' => $title,
                'content' => $content,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Thank you for your feedback!');
        } else {
            wp_send_json_error('Failed to submit feedback');
        }
    }
    
    public function get_analytics_data() {
        check_ajax_referer('bct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (function_exists('bct_get_site_analytics')) {
            $analytics = bct_get_site_analytics();
        } else {
            // Fallback basic analytics
            global $wpdb;
            $analytics = array(
                'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}craps_sessions"),
                'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}craps_sessions"),
                'active_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}craps_sessions WHERE session_status = 'active'")
            );
        }
        
        wp_send_json_success($analytics);
    }
    
    public function manage_feedback() {
        check_ajax_referer('bct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $action = sanitize_text_field($_POST['feedback_action'] ?? '');
        $feedback_id = intval($_POST['feedback_id'] ?? 0);
        
        global $wpdb;
        
        switch ($action) {
            case 'approve':
                $result = $wpdb->update(
                    $wpdb->prefix . 'craps_user_feedback',
                    array('status' => 'reviewed'),
                    array('id' => $feedback_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            case 'dismiss':
                $result = $wpdb->update(
                    $wpdb->prefix . 'craps_user_feedback',
                    array('status' => 'dismissed'),
                    array('id' => $feedback_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            default:
                wp_send_json_error('Invalid action');
                return;
        }
        
        if ($result !== false) {
            wp_send_json_success('Feedback updated successfully');
        } else {
            wp_send_json_error('Failed to update feedback');
        }
    }
	
	/**
     * AJAX handler for casino search
     * ADD THIS METHOD TO includes/class-ajax.php (before the closing })
     */
    public function get_casinos() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        
        $casinos = bct_get_casinos_for_session();
        
        // Filter by search term
        if (!empty($search)) {
            $casinos = array_filter($casinos, function($casino) use ($search) {
                return stripos($casino['name'], $search) !== false || 
                       stripos($casino['location'], $search) !== false;
            });
        }
        
        // Filter by location
        if (!empty($location) && $location !== 'all') {
            $casinos = array_filter($casinos, function($casino) use ($location) {
                return stripos(strtolower($casino['location']), str_replace('-', ' ', $location)) !== false;
            });
        }
        
        wp_send_json_success($casinos);
    }
}
?>