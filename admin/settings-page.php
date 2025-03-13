<?php
defined('ABSPATH') or die('Direct access not allowed');

// Save settings if form is submitted
if (isset($_POST['bct_save_settings']) && check_admin_referer('bct_settings_nonce')) {
    $settings = array(
        'time_limit' => intval($_POST['time_limit']),
        'enable_stats' => isset($_POST['enable_stats']),
        'enable_location' => isset($_POST['enable_location']),
        'machine_types' => explode("\n", trim($_POST['machine_types'])),
        'custom_css' => sanitize_textarea_field($_POST['custom_css'])
    );
    
    update_option('bct_settings', $settings);
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$settings = get_option('bct_settings', array(
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
?>

<div class="wrap">
    <h1>Bubble Craps Tracker Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('bct_settings_nonce'); ?>
        
        <div class="settings-grid">
            <!-- General Settings -->
            <div class="settings-card">
                <h2>General Settings</h2>
                
                <div class="settings-field">
                    <label for="time_limit">Time Limit Between Entries (hours)</label>
                    <input type="number" id="time_limit" name="time_limit" 
                           value="<?php echo esc_attr($settings['time_limit']); ?>" min="0" max="72">
                    <p class="description">Number of hours users must wait between submissions (0-72)</p>
                </div>
                
                <div class="settings-field">
                    <label>
                        <input type="checkbox" name="enable_stats" 
                               <?php checked($settings['enable_stats']); ?>>
                        Enable Global Statistics Dashboard
                    </label>
                    <p class="description">Show combined statistics across all casinos</p>
                </div>
                
                <div class="settings-field">
                    <label>
                        <input type="checkbox" name="enable_location" 
                               <?php checked($settings['enable_location']); ?>>
                        Enable Location Features
                    </label>
                    <p class="description">Show state/city filters and location-based statistics</p>
                </div>
            </div>

            <!-- Machine Types -->
            <div class="settings-card">
                <h2>Machine Types</h2>
                
                <div class="settings-field">
                    <label for="machine_types">Available Machine Types</label>
                    <textarea id="machine_types" name="machine_types" rows="8"><?php 
                        echo esc_textarea(implode("\n", $settings['machine_types'])); 
                    ?></textarea>
                    <p class="description">Enter one machine type per line</p>
                </div>
            </div>

            <!-- Customization -->
            <div class="settings-card">
                <h2>Customization</h2>
                
                <div class="settings-field">
                    <label for="custom_css">Custom CSS</label>
                    <textarea id="custom_css" name="custom_css" rows="10"><?php 
                        echo esc_textarea($settings['custom_css']); 
                    ?></textarea>
                    <p class="description">Add custom CSS styles for the tracker and dashboard</p>
                </div>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="bct_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>

    <!-- Usage Instructions -->
    <div class="settings-card">
        <h2>Usage Instructions</h2>
        
        <h3>Shortcodes</h3>
        <ul>
            <li><code>[bubble_craps_tracker]</code> - Displays the winnings tracker form</li>
            <li><code>[bubble_craps_dashboard]</code> - Displays the global statistics dashboard</li>
        </ul>

        <h3>Template Tags</h3>
        <p>You can also use these PHP functions in your theme:</p>
        <pre>
&lt;?php 
// Display the tracker
if (function_exists('bubble_craps_tracker')) {
    bubble_craps_tracker();
}

// Display the dashboard
if (function_exists('bubble_craps_dashboard')) {
    bubble_craps_dashboard();
}
?&gt;
        </pre>
        
        <h3>Action Hooks</h3>
        <ul>
            <li><code>bct_before_entry_submit</code> - Fires before an entry is submitted</li>
            <li><code>bct_after_entry_submit</code> - Fires after an entry is submitted</li>
            <li><code>bct_entry_deleted</code> - Fires when an entry is deleted</li>
        </ul>

        <h3>Filter Hooks</h3>
        <ul>
            <li><code>bct_machine_types</code> - Modify available machine types</li>
            <li><code>bct_entry_data</code> - Modify entry data before saving</li>
            <li><code>bct_dashboard_data</code> - Modify dashboard data before display</li>
        </ul>
    </div>
</div>

<?php
// Add help tab
$screen = get_current_screen();
$screen->add_help_tab(array(
    'id'       => 'bct-settings-overview',
    'title'    => 'Settings Overview',
    'content'  => '
        <h2>Bubble Craps Tracker Settings</h2>
        <p>Configure the plugin behavior and customize its appearance:</p>
        <ul>
            <li><strong>General Settings:</strong> Configure basic plugin behavior</li>
            <li><strong>Machine Types:</strong> Manage available machine types</li>
            <li><strong>Customization:</strong> Add custom CSS styles</li>
        </ul>
        <p>Use the shortcodes and template tags to display the tracker and dashboard on your site.</p>
    '
));