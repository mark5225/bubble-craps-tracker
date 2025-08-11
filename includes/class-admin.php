<?php
/**
 * Admin Interface Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
            'manage_options',
            'craps-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'craps-tracker',
            __('Analytics', 'bubble-craps-tracker'),
            __('Analytics', 'bubble-craps-tracker'),
            'view_options',
            'craps-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'craps-tracker',
            __('User Feedback', 'bubble-craps-tracker'),
            __('User Feedback', 'bubble-craps-tracker'),
            'manage_options',
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
                
                <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h3><?php _e('Quick Links', 'bubble-craps-tracker'); ?></h3>
                    <p><strong><?php _e('Frontend URL:', 'bubble-craps-tracker'); ?></strong> 
                       <a href="<?php echo home_url('/craps-tracker/'); ?>" target="_blank">
                           <?php echo home_url('/craps-tracker/'); ?>
                       </a>
                    </p>
                    <p><strong><?php _e('Shortcode:', 'bubble-craps-tracker'); ?></strong> [craps_tracker]</p>
                </div>
            </div>
        </div>
        
        <style>
            .bct-admin-dashboard {
                margin-top: 20px;
            }
            .bct-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            .bct-stat-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
            }
            .bct-stat-card h3 {
                margin: 0 0 10px 0;
                color: #1D3557;
                font-size: 14px;
                text-transform: uppercase;
            }
            .bct-stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #C51F1F;
            }
        </style>
        <?php
    }
    
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics Dashboard', 'bubble-craps-tracker'); ?></h1>
            <div id="bct-analytics-container">
                <p><?php _e('Advanced analytics will be available in Phase 2.', 'bubble-craps-tracker'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function feedback_page() {
        global $wpdb;
        $feedback_table = $wpdb->prefix . 'craps_user_feedback';
        
        // Handle feedback actions
        if (isset($_POST['action']) && $_POST['action'] === 'update_feedback') {
            $feedback_id = intval($_POST['feedback_id']);
            $status = sanitize_text_field($_POST['status']);
            
            $wpdb->update(
                $feedback_table,
                array('status' => $status),
                array('id' => $feedback_id),
                array('%s'),
                array('%d')
            );
            
            echo '<div class="notice notice-success"><p>Feedback updated!</p></div>';
        }
        
        $feedback_items = $wpdb->get_results("
            SELECT f.*, u.display_name 
            FROM $feedback_table f 
            JOIN {$wpdb->users} u ON f.user_id = u.ID 
            ORDER BY f.created_at DESC 
            LIMIT 50
        ");
        ?>
        <div class="wrap">
            <h1><?php _e('User Feedback', 'bubble-craps-tracker'); ?></h1>
            
            <?php if ($feedback_items): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'bubble-craps-tracker'); ?></th>
                            <th><?php _e('Type', 'bubble-craps-tracker'); ?></th>
                            <th><?php _e('Title', 'bubble-craps-tracker'); ?></th>
                            <th><?php _e('Content', 'bubble-craps-tracker'); ?></th>
                            <th><?php _e('Status', 'bubble-craps-tracker'); ?></th>
                            <th><?php _e('Date', 'bubble-craps-tracker'); ?></th>
                            <th><?php _e('Actions', 'bubble-craps-tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->display_name); ?></td>
                                <td><?php echo esc_html($item->feedback_type); ?></td>
                                <td><?php echo esc_html($item->title); ?></td>
                                <td><?php echo esc_html(substr($item->content, 0, 100)) . '...'; ?></td>
                                <td>
                                    <span class="status-<?php echo $item->status; ?>">
                                        <?php echo ucfirst($item->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($item->created_at)); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="update_feedback">
                                        <input type="hidden" name="feedback_id" value="<?php echo $item->id; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending" <?php selected($item->status, 'pending'); ?>>Pending</option>
                                            <option value="reviewed" <?php selected($item->status, 'reviewed'); ?>>Reviewed</option>
                                            <option value="implemented" <?php selected($item->status, 'implemented'); ?>>Implemented</option>
                                            <option value="dismissed" <?php selected($item->status, 'dismissed'); ?>>Dismissed</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No feedback yet.', 'bubble-craps-tracker'); ?></p>
            <?php endif; ?>
        </div>
        
        <style>
            .status-pending { color: #856404; background: #fff3cd; padding: 2px 6px; border-radius: 4px; }
            .status-reviewed { color: #155724; background: #d4edda; padding: 2px 6px; border-radius: 4px; }
            .status-implemented { color: #0c5460; background: #cce7f0; padding: 2px 6px; border-radius: 4px; }
            .status-dismissed { color: #721c24; background: #f8d7da; padding: 2px 6px; border-radius: 4px; }
        </style>
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
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Plugin Status', 'bubble-craps-tracker'); ?></th>
                        <td>
                            <span style="color: green; font-weight: bold;">✅ Active</span>
                            <p class="description"><?php _e('The plugin is active and ready to use.', 'bubble-craps-tracker'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Database Tables', 'bubble-craps-tracker'); ?></th>
                        <td>
                            <?php
                            global $wpdb;
                            $tables = array(
                                'craps_sessions',
                                'craps_session_bets',
                                'craps_user_achievements',
                                'craps_user_feedback',
                                'craps_win_photos'
                            );
                            
                            foreach ($tables as $table) {
                                $full_table_name = $wpdb->prefix . $table;
                                $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
                                echo '<p>' . ($exists ? '✅' : '❌') . ' ' . $full_table_name . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
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
?>