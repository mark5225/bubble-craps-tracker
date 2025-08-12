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
            wp_enqueue_script('bct-frontend', BCT_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), '1.0.4', true);
            
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
            
            <?php if (isset($_POST['end_session'])): ?>
                <?php
                global $wpdb;
                $user_id = get_current_user_id();
                
                // Get active session
                $active_session = $wpdb->get_row($wpdb->prepare("
                    SELECT * FROM {$wpdb->prefix}craps_sessions 
                    WHERE user_id = %d AND session_status = 'active'
                ", $user_id));
                
                if ($active_session) {
                    $ending_bankroll = floatval($_POST['ending_bankroll']);
                    $notes = sanitize_textarea_field($_POST['notes']);
                    $net_result = $ending_bankroll - $active_session->starting_bankroll;
                    
                    // Update session
                    $wpdb->update(
                        $wpdb->prefix . 'craps_sessions',
                        array(
                            'session_end' => current_time('mysql'),
                            'ending_bankroll' => $ending_bankroll,
                            'net_result' => $net_result,
                            'session_status' => 'completed',
                            'notes' => $notes
                        ),
                        array('id' => $active_session->id),
                        array('%s', '%f', '%f', '%s', '%s'),
                        array('%d')
                    );
                    
                    $result_color = $net_result >= 0 ? '#28a745' : '#C51F1F';
                    $result_text = ($net_result >= 0 ? '+' : '') . '$' . number_format($net_result, 2);
                    
                    echo '<div class="bct-success">✅ Session ended! Result: <span style="color: ' . $result_color . '; font-weight: bold;">' . $result_text . '</span></div>';
                } else {
                    echo '<div class="bct-error">❌ No active session found.</div>';
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
                WHERE user_id = %d
            ", $user_id));
            ?>
            
            <div class="bct-stats-grid">
                <div class="bct-stat-card">
                    <div class="bct-stat-label"><?php _e('Total Sessions', 'bubble-craps-tracker'); ?></div>
                    <div class="bct-stat-number"><?php echo $stats->total_sessions ?: 0; ?></div>
                </div>
                <div class="bct-stat-card">
                    <div class="bct-stat-label"><?php _e('Completed Sessions', 'bubble-craps-tracker'); ?></div>
                    <div class="bct-stat-number"><?php echo $stats->completed_sessions ?: 0; ?></div>
                </div>
                <div class="bct-stat-card">
                    <div class="bct-stat-label"><?php _e('Net Winnings', 'bubble-craps-tracker'); ?></div>
                    <div class="bct-stat-number" style="color: <?php echo ($stats->total_net_result >= 0) ? '#28a745' : '#C51F1F'; ?>;">
                        <?php echo ($stats->total_net_result >= 0 ? '+' : '') . '$' . number_format($stats->total_net_result ?: 0, 2); ?>
                    </div>
                </div>
                <div class="bct-stat-card">
                    <div class="bct-stat-label"><?php _e('Average Session', 'bubble-craps-tracker'); ?></div>
                    <div class="bct-stat-number">
                        <?php echo ($stats->avg_session_result >= 0 ? '+' : '') . '$' . number_format($stats->avg_session_result ?: 0, 2); ?>
                    </div>
                </div>
            </div>
            
            <div class="bct-card">
                <h2><?php _e('Start New Session', 'bubble-craps-tracker'); ?></h2>
                <form method="post">
                    <label for="starting_bankroll"><?php _e('Starting Bankroll ($):', 'bubble-craps-tracker'); ?></label>
                    <input type="number" id="starting_bankroll" name="starting_bankroll" 
                           class="bct-input" placeholder="100.00" min="1" step="0.01" required>
                    <button type="submit" name="start_session" class="bct-btn">
                        <?php _e('Start Session', 'bubble-craps-tracker'); ?>
                    </button>
                </form>
            </div>
            
            <div class="bct-card">
                <h2><?php _e('Recent Sessions', 'bubble-craps-tracker'); ?></h2>
                <?php
                $sessions = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$wpdb->prefix}craps_sessions 
                    WHERE user_id = %d 
                    ORDER BY session_start DESC 
                    LIMIT 10
                ", $user_id));
                
                if ($sessions): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #1D3557;">
                                <th style="padding: 10px; text-align: left;"><?php _e('Date', 'bubble-craps-tracker'); ?></th>
                                <th style="padding: 10px; text-align: left;"><?php _e('Starting Bankroll', 'bubble-craps-tracker'); ?></th>
                                <th style="padding: 10px; text-align: left;"><?php _e('Ending Bankroll', 'bubble-craps-tracker'); ?></th>
                                <th style="padding: 10px; text-align: left;"><?php _e('Result', 'bubble-craps-tracker'); ?></th>
                                <th style="padding: 10px; text-align: left;"><?php _e('Status', 'bubble-craps-tracker'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;">
                                        <?php echo date('M j, Y g:i A', strtotime($session->session_start)); ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        $<?php echo number_format($session->starting_bankroll, 2); ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php echo $session->ending_bankroll ? '$' . number_format($session->ending_bankroll, 2) : '-'; ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php if ($session->session_status === 'completed' && $session->net_result !== null): ?>
                                            <span style="color: <?php echo $session->net_result >= 0 ? '#28a745' : '#C51F1F'; ?>; font-weight: bold;">
                                                <?php echo ($session->net_result >= 0 ? '+' : '') . '$' . number_format($session->net_result, 2); ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <span style="background: <?php echo $session->session_status === 'active' ? '#28a745' : '#6c757d'; ?>; 
                                                     color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                            <?php echo ucfirst($session->session_status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No sessions yet. Start your first session above!', 'bubble-craps-tracker'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="bct-card">
                <h2><?php _e('Quick Actions', 'bubble-craps-tracker'); ?></h2>
                <p><?php _e('Advanced features like bet logging, analytics, and community features will be available soon!', 'bubble-craps-tracker'); ?></p>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="bct-btn" style="opacity: 0.6;" disabled>
                        <?php _e('Log Bet', 'bubble-craps-tracker'); ?>
                    </button>
                    <button class="bct-btn" style="opacity: 0.6;" disabled>
                        <?php _e('View Analytics', 'bubble-craps-tracker'); ?>
                    </button>
                    <button class="bct-btn" style="opacity: 0.6;" disabled>
                        <?php _e('Share Win Photo', 'bubble-craps-tracker'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function is_tracker_page() {
        return get_query_var('craps_tracker_page') || 
               has_shortcode(get_post()->post_content ?? '', 'craps_tracker') || 
               has_shortcode(get_post()->post_content ?? '', 'craps_dashboard');
    }
}
?>
