<?php
/**
 * Plugin Name: Bubble Craps Session Tracker
 * Plugin URI: https://bubble-craps.com
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

// Initialize the plugin
BubbleCrapsTracker::get_instance();
?>
