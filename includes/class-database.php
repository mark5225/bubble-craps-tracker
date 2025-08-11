<?php
/**
 * Database Management Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BCT_Database {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sessions table
        $sessions_table = $wpdb->prefix . 'craps_sessions';
        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            session_start datetime DEFAULT CURRENT_TIMESTAMP,
            session_end datetime NULL,
            starting_bankroll decimal(10,2) NOT NULL DEFAULT 0.00,
            ending_bankroll decimal(10,2) NULL,
            total_wagered decimal(10,2) DEFAULT 0.00,
            net_result decimal(10,2) DEFAULT 0.00,
            session_status enum('active','completed','paused') DEFAULT 'active',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_status (session_status),
            KEY session_start (session_start)
        ) $charset_collate;";
        
        // Session bets table
        $bets_table = $wpdb->prefix . 'craps_session_bets';
        $bets_sql = "CREATE TABLE $bets_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id bigint(20) unsigned NOT NULL,
            bet_type varchar(50) NOT NULL,
            bet_amount decimal(10,2) NOT NULL,
            bet_result enum('win','lose','push') NULL,
            payout decimal(10,2) DEFAULT 0.00,
            roll_number int DEFAULT 0,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY bet_type (bet_type),
            KEY bet_result (bet_result),
            FOREIGN KEY (session_id) REFERENCES $sessions_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // User achievements table
        $achievements_table = $wpdb->prefix . 'craps_user_achievements';
        $achievements_sql = "CREATE TABLE $achievements_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            achievement_type varchar(50) NOT NULL,
            achievement_data longtext,
            earned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY achievement_type (achievement_type),
            UNIQUE KEY user_achievement (user_id, achievement_type)
        ) $charset_collate;";
        
        // User comments/feedback table
        $comments_table = $wpdb->prefix . 'craps_user_feedback';
        $comments_sql = "CREATE TABLE $comments_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            feedback_type enum('comment','suggestion','bug','feature_request') DEFAULT 'comment',
            title varchar(200),
            content longtext NOT NULL,
            status enum('pending','reviewed','implemented','dismissed') DEFAULT 'pending',
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY feedback_type (feedback_type),
            KEY status (status)
        ) $charset_collate;";
        
        // Photo uploads table
        $uploads_table = $wpdb->prefix . 'craps_win_photos';
        $uploads_sql = "CREATE TABLE $uploads_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            session_id bigint(20) unsigned NULL,
            file_path varchar(255) NOT NULL,
            file_name varchar(255) NOT NULL,
            win_amount decimal(10,2) DEFAULT 0.00,
            description text,
            is_featured boolean DEFAULT false,
            status enum('pending','approved','rejected') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY is_featured (is_featured),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sessions_sql);
        dbDelta($bets_sql);
        dbDelta($achievements_sql);
        dbDelta($comments_sql);
        dbDelta($uploads_sql);
        
        // Update version
        update_option('bct_db_version', BCT_VERSION);
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'craps_win_photos',
            $wpdb->prefix . 'craps_user_feedback',
            $wpdb->prefix . 'craps_user_achievements',
            $wpdb->prefix . 'craps_session_bets',
            $wpdb->prefix . 'craps_sessions'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('bct_db_version');
    }
}
?>