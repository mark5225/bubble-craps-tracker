<?php
/**
 * Plugin Name: Bubble Craps Tracker
 * Description: Track and analyze bubble craps winnings across casinos
 * Version: 1.0.0
 * Author: Albuquerque's Finest Web Design
 * License: GPL v2 or later
 */

defined('ABSPATH') or die('Direct access not allowed');

class BubbleCrapsTracker {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->defineConstants();
        $this->loadDependencies();
        $this->initHooks();
        
        // Initialize components
        add_action('init', array($this, 'initComponents'));
    }
    
    private function defineConstants() {
        define('BCT_VERSION', '1.0.0');
        define('BCT_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('BCT_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('BCT_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }
    
    private function loadDependencies() {
        require_once BCT_PLUGIN_DIR . 'includes/class-database.php';
        require_once BCT_PLUGIN_DIR . 'includes/class-public.php';
        require_once BCT_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once BCT_PLUGIN_DIR . 'includes/class-tracker.php';
        require_once BCT_PLUGIN_DIR . 'includes/class-dashboard.php';
        require_once BCT_PLUGIN_DIR . 'includes/class-admin.php';
        require_once BCT_PLUGIN_DIR . 'includes/class-rest-api.php';
    }
    
    private function initHooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_init', array($this, 'checkTables'));
    }
    
    public function initComponents() {
        BubbleCrapsTracker_Public::init();
        BubbleCrapsTracker_Shortcodes::init();
        BubbleCrapsTracker_Tracker::init();
        BubbleCrapsTracker_Dashboard::init();
        BubbleCrapsTracker_Admin::init();
        BubbleCrapsTracker_REST_API::init();
    }
    
    public function activate() {
        // Create database tables
        BubbleCrapsTracker_Database::createTables();
        
        // Set version
        update_option('bct_version', BCT_VERSION);
        
        // Set default settings if they don't exist
        if (!get_option('bct_settings')) {
            update_option('bct_settings', array(
                'time_limit' => 24,
                'enable_stats' => true,
                'enable_location' => true,
                'machine_types' => array(
                    'Single Machine',
                    'Stadium Bubble Craps',
                    'Crapless Bubble Craps',
                    'Roll to Win',
                    'Casino Wizard',
                    'Other Electronic'
                ),
                'custom_css' => ''
            ));
        }
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function checkTables() {
        $tables_status = BubbleCrapsTracker_Database::verifyTables();
        
        if ($tables_status !== true) {
            // Tables are missing, try to recreate them
            BubbleCrapsTracker_Database::createTables();
            
            // Check again and show admin notice if still missing
            $tables_status = BubbleCrapsTracker_Database::verifyTables();
            if ($tables_status !== true) {
                add_action('admin_notices', function() use ($tables_status) {
                    echo '<div class="error"><p>';
                    echo 'Bubble Craps Tracker: Database tables are missing or incomplete. Please deactivate and reactivate the plugin.';
                    echo '</p></div>';
                });
            }
        }
    }
}

// Initialize plugin
function BubbleCrapsTracker() {
    return BubbleCrapsTracker::getInstance();
}

// Start the plugin
BubbleCrapsTracker();