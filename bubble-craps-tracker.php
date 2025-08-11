<?php
/**
 * Plugin Name: Bubble Craps Session Tracker
 * Plugin URI: https:// Initialize the plugin
BubbleCrapsTracker::get_instance();

?>bubble-craps.com
 * Description: Comprehensive session tracking, analytics, and community features for craps players
 * Version: 1.0.0
 * Author: Bubble-Craps.com
 * License: GPL v2 or later
 * Text Domain: bubble-craps-tracker
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BCT_VERSION', '1.0.0');
define('BCT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BCT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BCT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class BubbleCrapsTracker {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('bubble-craps-tracker', false, dirname(BCT_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->includes();
        $this->init_hooks();
        
        // Initialize classes
        BCT_Database::get_instance();
        BCT_Admin::get_instance();
        BCT_Frontend::get_instance();
        BCT_Ajax::get_instance();
    }
    
    private function includes() {
        require_once BCT_PLUGIN_PATH . 'includes/class-database.php';
        require_once BCT_PLUGIN_PATH . 'includes/class-admin.php';
        require_once BCT_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once BCT_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once BCT_PLUGIN_PATH . 'includes/class-session.php';
        require_once BCT_PLUGIN_PATH . 'includes/functions.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('BubbleCrapsTracker', 'uninstall'));
    }
    
    public function activate() {
        BCT_Database::create_tables();
        
        // Create default capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_craps_tracker');
            $role->add_cap('view_craps_analytics');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        // Remove tables and data if needed
        if (get_option('bct_delete_data_on_uninstall', false)) {
            BCT_Database::drop_tables();
        }
    }
}

/**
 * Database Management Class
 */
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

/**
 * Admin Interface Class
 */
class BCT_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Craps Tracker', 'bubble-craps-tracker'),
            __('Craps Tracker', 'bubble-craps-tracker'),
            'manage_craps_tracker',
            'craps-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'craps-tracker',
            __('Analytics', 'bubble-craps-tracker'),
            __('Analytics', 'bubble-craps-tracker'),
            'view_craps_analytics',
            'craps-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'craps-tracker',
            __('User Feedback', 'bubble-craps-tracker'),
            __('User Feedback', 'bubble-craps-tracker'),
            'manage_craps_tracker',
            'craps-feedback',
            array($this, 'feedback_page')
        );
        
        add_submenu_page(
            'craps-tracker',
            __('Settings', 'bubble-craps-tracker'),
            __('Settings', 'bubble-craps-tracker'),
            'manage_options',
            'craps-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'craps-') !== false) {
            wp_enqueue_style('bct-admin', BCT_PLUGIN_URL . 'assets/css/admin.css', array(), BCT_VERSION);
            wp_enqueue_script('bct-admin', BCT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BCT_VERSION, true);
            
            wp_localize_script('bct-admin', 'bct_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bct_admin_nonce')
            ));
        }
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Craps Session Tracker', 'bubble-craps-tracker'); ?></h1>
            
            <div class="bct-admin-dashboard">
                <div class="bct-stats-grid">
                    <div class="bct-stat-card">
                        <h3><?php _e('Total Users', 'bubble-craps-tracker'); ?></h3>
                        <div class="bct-stat-number"><?php echo $this->get_total_users(); ?></div>
                    </div>
                    
                    <div class="bct-stat-card">
                        <h3><?php _e('Active Sessions', 'bubble-craps-tracker'); ?></h3>
                        <div class="bct-stat-number"><?php echo $this->get_active_sessions(); ?></div>
                    </div>
                    
                    <div class="bct-stat-card">
                        <h3><?php _e('Total Sessions', 'bubble-craps-tracker'); ?></h3>
                        <div class="bct-stat-number"><?php echo $this->get_total_sessions(); ?></div>
                    </div>
                    
                    <div class="bct-stat-card">
                        <h3><?php _e('Pending Feedback', 'bubble-craps-tracker'); ?></h3>
                        <div class="bct-stat-number"><?php echo $this->get_pending_feedback(); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics Dashboard', 'bubble-craps-tracker'); ?></h1>
            <div id="bct-analytics-container">
                <!-- Analytics charts and data will be loaded here -->
            </div>
        </div>
        <?php
    }
    
    public function feedback_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('User Feedback', 'bubble-craps-tracker'); ?></h1>
            <div id="bct-feedback-list">
                <!-- Feedback management interface will be loaded here -->
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Tracker Settings', 'bubble-craps-tracker'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bct_settings');
                do_settings_sections('bct_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    private function get_total_users() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}craps_sessions
        ") ?: 0;
    }
    
    private function get_active_sessions() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}craps_sessions 
            WHERE session_status = 'active'
        ") ?: 0;
    }
    
    private function get_total_sessions() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}craps_sessions
        ") ?: 0;
    }
    
    private function get_pending_feedback() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}craps_user_feedback 
            WHERE status = 'pending'
        ") ?: 0;
    }
}

/**
 * Frontend Interface Class
 */
class BCT_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'template_redirect'));
        add_shortcode('craps_tracker', array($this, 'tracker_shortcode'));
        add_shortcode('craps_dashboard', array($this, 'dashboard_shortcode'));
    }
    
    public function enqueue_scripts() {
        if ($this->is_tracker_page()) {
            wp_enqueue_style('bct-frontend', BCT_PLUGIN_URL . 'assets/css/frontend.css', array(), BCT_VERSION);
            wp_enqueue_script('bct-frontend', BCT_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), BCT_VERSION, true);
            
            wp_localize_script('bct-frontend', 'bct_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bct_frontend_nonce'),
                'user_id' => get_current_user_id(),
                'colors' => array(
                    'primary_red' => '#C51F1F',
                    'primary_navy' => '#1D3557'
                )
            ));
        }
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^craps-tracker/?$',
            'index.php?craps_tracker_page=dashboard',
            'top'
        );
        
        add_rewrite_rule(
            '^craps-tracker/([^/]+)/?$',
            'index.php?craps_tracker_page=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%craps_tracker_page%', '([^&]+)');
    }
    
    public function template_redirect() {
        $page = get_query_var('craps_tracker_page');
        
        if ($page) {
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/craps-tracker/')));
                exit;
            }
            
            $this->load_tracker_template($page);
            exit;
        }
    }
    
    private function load_tracker_template($page) {
        $template_file = BCT_PLUGIN_PATH . "templates/{$page}.php";
        
        if (!file_exists($template_file)) {
            $template_file = BCT_PLUGIN_PATH . 'templates/dashboard.php';
        }
        
        get_header();
        include $template_file;
        get_footer();
    }
    
    public function tracker_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the session tracker.', 'bubble-craps-tracker') . '</p>';
        }
        
        ob_start();
        include BCT_PLUGIN_PATH . 'templates/tracker-widget.php';
        return ob_get_clean();
    }
    
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'bubble-craps-tracker') . '</p>';
        }
        
        ob_start();
        include BCT_PLUGIN_PATH . 'templates/dashboard-widget.php';
        return ob_get_clean();
    }
    
    private function is_tracker_page() {
        return get_query_var('craps_tracker_page') || 
               has_shortcode(get_post()->post_content ?? '', 'craps_tracker') || 
               has_shortcode(get_post()->post_content ?? '', 'craps_dashboard');
    }
}

/**
 * AJAX Handler Class
 */
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
        
        // Admin AJAX actions
        add_action('wp_ajax_bct_get_analytics_data', array($this, 'get_analytics_data'));
        add_action('wp_ajax_bct_manage_feedback', array($this, 'manage_feedback'));
    }
    
    public function start_session() {
        check_ajax_referer('bct_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $starting_bankroll = floatval($_POST['starting_bankroll'] ?? 0);
        
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
        
        global $wpdb;
        
        // Verify session
        $user_id = get_current_user_id();
        $session = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}craps_sessions 
            WHERE id = %d AND user_id = %d AND session_status = 'active'
        ", $session_id, $user_id));
        
        if (!$session) {
            wp_send_json_error('Invalid session');
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
        $stats = bct_get_user_statistics($user_id);
        
        wp_send_json_success($stats);
    }
    
    public function get_analytics_data() {
        check_ajax_referer('bct_admin_nonce', 'nonce');
        
        if (!current_user_can('view_craps_analytics')) {
            wp_die('Unauthorized');
        }
        
        $analytics = bct_get_site_analytics();
        wp_send_json_success($analytics);
    }
    
    public function manage_feedback() {
        check_ajax_referer('bct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_craps_tracker')) {
            wp_die('Unauthorized');
        }
        
        $action = sanitize_text_field($_POST['feedback_action'] ?? '');
        $feedback_id = intval($_POST['feedback_id'] ?? 0);
        
        global $wpdb;
        
        switch ($action) {
            case 'approve':
                $wpdb->update(
                    $wpdb->prefix . 'craps_user_feedback',
                    array('status' => 'reviewed'),
                    array('id' => $feedback_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            case 'dismiss':
                $wpdb->update(
                    $wpdb->prefix . 'craps_user_feedback',
                    array('status' => 'dismissed'),
                    array('id' => $feedback_id),
                    array('%s'),
                    array('%d')
                );
                break;
        }
        
        wp_send_json_success('Feedback updated');
    }
}

/**
 * Session Management Class
 */
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
}

//