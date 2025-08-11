<?php
/**
 * Frontend Interface Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            // Fallback if template doesn't exist
            echo $this->get_basic_tracker_html();
        }
        
        get_footer();
    }
    
    public function tracker_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the session tracker.', 'bubble-craps-tracker') . '</p>';
        }
        
        $template_file = BCT_PLUGIN_PATH . 'templates/tracker-widget.php';
        
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        } else {
            return $this->get_basic_tracker_html();
        }
    }
    
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'bubble-craps-tracker') . '</p>';
        }
        
        $template_file = BCT_PLUGIN_PATH . 'templates/dashboard-widget.php';
        
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        } else {
            return $this->get_basic_tracker_html();
        }
    }
    
    private function get_basic_tracker_html() {
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <style>
            .bct-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .bct-header {
                background: #1D3557;
                color: white;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .bct-card {
                background: white;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .bct-btn {
                background: #C51F1F;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 1rem;
                text-decoration: none;
                display: inline-block;
            }
            .bct-btn:hover {
                background: #a01919;
            }
            .bct-input {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e9ecef;
                border-radius: 8px;
                font-size: 1rem;
                margin-bottom: 15px;
                box-sizing: border-box;
            }
            .bct-success {
                background: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .bct-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .bct-stat-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                border-top: 4px solid #C51F1F;
            }
            .bct-stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #1D3557;
                margin: 10px 0;
            }
            .bct-stat-label {
                color: #6c757d;
                font-weight: 600;
            }
        </style>
        
        <div class="bct-container">
            <div class="bct-header">
                <h1><?php printf(__('Welcome back, %s!', 'bubble-craps-tracker'), $current_user->display_name); ?></h1>
                <p><?php _e('Track your craps sessions and analyze your performance.', 'bubble-craps-tracker'); ?></p>
            </div>
            
            <?php if (isset($_POST['start_session'])): ?>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'craps_sessions';
                
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => get_current_user_id(),
                        'starting_bankroll' => floatval($_POST['starting_bankroll']),
                        'session_status' => 'active'
                    ),
                    array('%d', '%f', '%s')
                );
                
                if ($result) {
                    echo '<div class="bct-success">✅ Session started successfully! Starting bankroll: $' . number_format(floatval($_POST['starting_bankroll']), 2) . '</div>';
                } else {
                    echo '<div class="bct-error">❌ Failed to start session. Please try again.</div>';
                }
                ?>
            <?php endif; ?>
            
            <!-- User Stats -->
            <?php
            global $wpdb;
            $user_id = get_current_user_id();
            $stats = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(*) as total_sessions,
                    COUNT(CASE WHEN session_status = 'completed' THEN 1 END) as completed_sessions,
                    SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as total_net_result,
                    AVG(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as avg_session_result
                FROM {$wpdb->prefix}craps_sessions 
                WHERE user_id = %