<?php
/**
 * Helper Functions for Bubble Craps Tracker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user statistics
 */
function bct_get_user_statistics($user_id) {
    global $wpdb;
    
    $stats = array();
    
    // Basic session stats
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
    
    $stats['sessions'] = $session_stats;
    
    // Betting pattern analysis
    $bet_stats = $wpdb->get_results($wpdb->prepare("
        SELECT 
            cb.bet_type,
            COUNT(*) as bet_count,
            SUM(cb.bet_amount) as total_wagered,
            SUM(CASE WHEN cb.bet_result = 'win' THEN cb.payout ELSE 0 END) as total_winnings,
            SUM(CASE WHEN cb.bet_result = 'lose' THEN cb.bet_amount ELSE 0 END) as total_losses,
            COUNT(CASE WHEN cb.bet_result = 'win' THEN 1 END) as wins,
            COUNT(CASE WHEN cb.bet_result = 'lose' THEN 1 END) as losses,
            ROUND(COUNT(CASE WHEN cb.bet_result = 'win' THEN 1 END) * 100.0 / COUNT(*), 2) as win_percentage
        FROM {$wpdb->prefix}craps_session_bets cb
        JOIN {$wpdb->prefix}craps_sessions cs ON cb.session_id = cs.id
        WHERE cs.user_id = %d
        GROUP BY cb.bet_type
        ORDER BY total_wagered DESC
    ", $user_id));
    
    $stats['betting_patterns'] = $bet_stats;
    
    // Recent activity
    $recent_sessions = $wpdb->get_results($wpdb->prepare("
        SELECT 
            DATE(session_start) as session_date,
            COUNT(*) as sessions_count,
            SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as daily_result
        FROM {$wpdb->prefix}craps_sessions 
        WHERE user_id = %d 
        AND session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(session_start)
        ORDER BY session_date DESC
    ", $user_id));
    
    $stats['recent_activity'] = $recent_sessions;
    
    // Streaks and achievements
    $stats['achievements'] = bct_calculate_user_achievements($user_id);
    
    return $stats;
}

/**
 * Get site-wide analytics
 */
function bct_get_site_analytics() {
    global $wpdb;
    
    $analytics = array();
    
    // Overall site stats
    $site_stats = $wpdb->get_row("
        SELECT 
            COUNT(DISTINCT user_id) as total_users,
            COUNT(*) as total_sessions,
            COUNT(CASE WHEN session_status = 'completed' THEN 1 END) as completed_sessions,
            COUNT(CASE WHEN session_status = 'active' THEN 1 END) as active_sessions,
            SUM(CASE WHEN session_status = 'completed' THEN total_wagered ELSE 0 END) as total_wagered,
            SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as total_net_result
        FROM {$wpdb->prefix}craps_sessions
    ");
    
    $analytics['site_stats'] = $site_stats;
    
    // Daily activity for last 30 days
    $daily_activity = $wpdb->get_results("
        SELECT 
            DATE(session_start) as activity_date,
            COUNT(*) as sessions_started,
            COUNT(DISTINCT user_id) as unique_users,
            SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as daily_net_result
        FROM {$wpdb->prefix}craps_sessions 
        WHERE session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(session_start)
        ORDER BY activity_date DESC
    ");
    
    $analytics['daily_activity'] = $daily_activity;
    
    // Popular bet types
    $popular_bets = $wpdb->get_results("
        SELECT 
            bet_type,
            COUNT(*) as bet_count,
            SUM(bet_amount) as total_wagered,
            ROUND(AVG(bet_amount), 2) as avg_bet_size,
            COUNT(CASE WHEN bet_result = 'win' THEN 1 END) as wins,
            COUNT(CASE WHEN bet_result = 'lose' THEN 1 END) as losses,
            ROUND(COUNT(CASE WHEN bet_result = 'win' THEN 1 END) * 100.0 / COUNT(*), 2) as win_percentage
        FROM {$wpdb->prefix}craps_session_bets
        GROUP BY bet_type
        ORDER BY bet_count DESC
        LIMIT 10
    ");
    
    $analytics['popular_bets'] = $popular_bets;
    
    // User engagement metrics
    $engagement = $wpdb->get_results("
        SELECT 
            user_id,
            COUNT(*) as session_count,
            SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as total_result,
            MAX(session_start) as last_session,
            DATEDIFF(NOW(), MAX(session_start)) as days_since_last_session
        FROM {$wpdb->prefix}craps_sessions
        GROUP BY user_id
        ORDER BY session_count DESC
        LIMIT 20
    ");
    
    $analytics['top_users'] = $engagement;
    
    return $analytics;
}

/**
 * Calculate user achievements
 */
function bct_calculate_user_achievements($user_id) {
    global $wpdb;
    
    $achievements = array();
    
    // Session milestones
    $session_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}craps_sessions WHERE user_id = %d
    ", $user_id));
    
    $session_milestones = array(1, 5, 10, 25, 50, 100, 250, 500);
    foreach ($session_milestones as $milestone) {
        if ($session_count >= $milestone) {
            $achievements[] = array(
                'type' => 'sessions',
                'milestone' => $milestone,
                'title' => sprintf(__('%d Sessions Played', 'bubble-craps-tracker'), $milestone),
                'achieved' => true
            );
        }
    }
    
    // Winning streaks
    $winning_streak = bct_calculate_winning_streak($user_id);
    if ($winning_streak >= 3) {
        $achievements[] = array(
            'type' => 'winning_streak',
            'milestone' => $winning_streak,
            'title' => sprintf(__('%d Session Winning Streak', 'bubble-craps-tracker'), $winning_streak),
            'achieved' => true
        );
    }
    
    // Big winner
    $biggest_win = $wpdb->get_var($wpdb->prepare("
        SELECT MAX(net_result) FROM {$wpdb->prefix}craps_sessions 
        WHERE user_id = %d AND session_status = 'completed'
    ", $user_id));
    
    $win_milestones = array(100, 500, 1000, 2500, 5000);
    foreach ($win_milestones as $milestone) {
        if ($biggest_win >= $milestone) {
            $achievements[] = array(
                'type' => 'big_winner',
                'milestone' => $milestone,
                'title' => sprintf(__('$%d Single Session Win', 'bubble-craps-tracker'), $milestone),
                'achieved' => true
            );
        }
    }
    
    return $achievements;
}

/**
 * Calculate current winning streak
 */
function bct_calculate_winning_streak($user_id) {
    global $wpdb;
    
    $recent_sessions = $wpdb->get_results($wpdb->prepare("
        SELECT net_result FROM {$wpdb->prefix}craps_sessions 
        WHERE user_id = %d AND session_status = 'completed'
        ORDER BY session_end DESC
        LIMIT 20
    ", $user_id));
    
    $streak = 0;
    foreach ($recent_sessions as $session) {
        if ($session->net_result > 0) {
            $streak++;
        } else {
            break;
        }
    }
    
    return $streak;
}

/**
 * Format currency for display
 */
function bct_format_currency($amount) {
    $formatted = number_format(abs($amount), 2);
    
    if ($amount >= 0) {
        return '+$' . $formatted;
    } else {
        return '-$' . $formatted;
    }
}

/**
 * Get bet type display name
 */
function bct_get_bet_display_name($bet_type) {
    $bet_names = array(
        'pass_line' => __('Pass Line', 'bubble-craps-tracker'),
        'dont_pass' => __("Don't Pass", 'bubble-craps-tracker'),
        'come' => __('Come', 'bubble-craps-tracker'),
        'dont_come' => __("Don't Come", 'bubble-craps-tracker'),
        'field' => __('Field', 'bubble-craps-tracker'),
        'place_6' => __('Place 6', 'bubble-craps-tracker'),
        'place_8' => __('Place 8', 'bubble-craps-tracker'),
        'hard_ways' => __('Hard Ways', 'bubble-craps-tracker'),
        'any_seven' => __('Any Seven', 'bubble-craps-tracker'),
        'any_craps' => __('Any Craps', 'bubble-craps-tracker'),
        'odds' => __('Odds Bet', 'bubble-craps-tracker')
    );
    
    return $bet_names[$bet_type] ?? ucwords(str_replace('_', ' ', $bet_type));
}

/**
 * Check if user can upload photos
 */
function bct_user_can_upload_photos($user_id) {
    // Basic check - can be expanded with premium features
    return is_user_logged_in() && $user_id == get_current_user_id();
}

/**
 * Get user's win photos
 */
function bct_get_user_win_photos($user_id, $limit = 10) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT wp.*, cs.net_result as session_result, cs.session_start
        FROM {$wpdb->prefix}craps_win_photos wp
        LEFT JOIN {$wpdb->prefix}craps_sessions cs ON wp.session_id = cs.id
        WHERE wp.user_id = %d AND wp.status = 'approved'
        ORDER BY wp.created_at DESC
        LIMIT %d
    ", $user_id, $limit));
}

/**
 * Get featured win photos for homepage/community
 */
function bct_get_featured_win_photos($limit = 6) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT wp.*, u.display_name, cs.net_result as session_result
        FROM {$wpdb->prefix}craps_win_photos wp
        JOIN {$wpdb->users} u ON wp.user_id = u.ID
        LEFT JOIN {$wpdb->prefix}craps_sessions cs ON wp.session_id = cs.id
        WHERE wp.is_featured = 1 AND wp.status = 'approved'
        ORDER BY wp.created_at DESC
        LIMIT %d
    ", $limit));
}

/**
 * Save user feedback
 */
function bct_save_user_feedback($user_id, $type, $title, $content) {
    global $wpdb;
    
    return $wpdb->insert(
        $wpdb->prefix . 'craps_user_feedback',
        array(
            'user_id' => $user_id,
            'feedback_type' => $type,
            'title' => $title,
            'content' => $content,
            'status' => 'pending'
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
}

/**
 * Get leaderboard data
 */
function bct_get_leaderboard($type = 'monthly', $limit = 10) {
    global $wpdb;
    
    $date_condition = '';
    switch ($type) {
        case 'weekly':
            $date_condition = "AND session_start >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'monthly':
            $date_condition = "AND session_start >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'all_time':
            $date_condition = "";
            break;
    }
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT 
            u.display_name,
            u.ID as user_id,
            COUNT(cs.id) as session_count,
            SUM(CASE WHEN cs.session_status = 'completed' THEN cs.net_result ELSE 0 END) as total_winnings,
            MAX(cs.net_result) as best_session,
            ROUND(AVG(CASE WHEN cs.session_status = 'completed' THEN cs.net_result ELSE NULL END), 2) as avg_session
        FROM {$wpdb->users} u
        JOIN {$wpdb->prefix}craps_sessions cs ON u.ID = cs.user_id
        WHERE 1=1 {$date_condition}
        GROUP BY u.ID, u.display_name
        HAVING session_count >= 3
        ORDER BY total_winnings DESC
        LIMIT %d
    ", $limit), ARRAY_A);
}

/**
 * Generate session summary for notifications
 */
function bct_get_session_summary($session_id) {
    global $wpdb;
    
    $session = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}craps_sessions WHERE id = %d
    ", $session_id));
    
    if (!$session) {
        return false;
    }
    
    $bets = $wpdb->get_results($wpdb->prepare("
        SELECT bet_type, COUNT(*) as count, SUM(bet_amount) as total_amount
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
?>
