=== Bubble Craps Tracker ===
Contributors: yourname
Tags: casino, gambling, statistics, tracking
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track and analyze bubble craps winnings across multiple casinos with detailed statistics and visualizations.

== Description ==

Bubble Craps Tracker is a comprehensive solution for tracking and analyzing electronic craps machine performance across different casinos. Perfect for casino review websites and gambling communities.

Features:

* Track winnings by casino and machine type
* Global statistics dashboard
* Location-based analytics
* Machine type performance comparison
* User-friendly submission interface
* Admin management dashboard
* Data export capabilities
* Customizable appearance

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/bubble-craps-tracker`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->BC Tracker screen to configure the plugin
4. Place `[bubble_craps_tracker]` shortcode in your posts or pages
5. Place `[bubble_craps_dashboard]` shortcode to display the statistics dashboard

== Frequently Asked Questions ==

= How do I display the tracker on a page? =

Use the shortcode `[bubble_craps_tracker]` in your post or page content.

= Can users submit multiple entries? =

By default, users must wait 24 hours between submissions for the same casino. This can be adjusted in the settings.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release

/* File: /public/css/tracker.css */
.winnings-tracker {
    margin-top: 20px;
    padding: 15px;
    border: 2px solid #D50000;
    background-color: #FFEDED;
    border-radius: 8px;
    font-family: Arial, sans-serif;
}

.milestone-progress {
    margin-bottom: 20px;
    padding: 15px;
    background-color: white;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.progress-bar {
    background-color: #f5f5f5;
    height: 25px;
    border-radius: 12px;
    overflow: hidden;
    margin: 10px 0;
    position: relative;
}

.progress {
    background-color: #D50000;
    height: 100%;
    transition: width 0.5s ease-in-out;
    min-width: 20px;
    position: relative;
}

.progress-amount {
    position: absolute;
    right: 10px;
    color: white;
    line-height: 25px;
    font-weight: bold;
}

.milestone-info {
    text-align: right;
    color: #666;
    font-size: 14px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    background-color: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
}

.stat-icon {
    font-size: 24px;
    margin-right: 15px;
}

.stat-info {
    flex-grow: 1;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #D50000;
}

.tabs {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
}

.tab-button {
    padding: 10px;
    background-color: #D50000;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    flex: 1;
    margin: 0 5px;
    text-align: center;
}

.tab-button.active {
    background-color: #B20000;
}

.tab-content {
    display: none;
    padding: 15px;
    background-color: #FFF;
    border-radius: 8px;
    border: 1px solid #DDD;
}

.tab-content.active {
    display: block !important;
}

.winnings-input-box {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.currency {
    font-weight: bold;
}

.winnings-input, .machine-type {
    padding: 10px;
    border: 1px solid #CCC;
    border-radius: 5px;
    flex: 1;
}

.submit-winnings {
    background-color: #D50000;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    min-width: 150px;
}

.submit-winnings:hover:not([disabled]) {
    background-color: #B20000;
}

.submit-winnings[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

.restriction-message {
    color: #D50000;
    font-size: 14px;
    margin-top: 10px;
}

.loading-indicator {
    text-align: center;
    margin: 10px 0;
    color: #666;
}

.entries-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.entries-list li {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.entry-date {
    color: #666;
    font-size: 14px;
}

.admin-actions {
    display: flex;
    gap: 5px;
}

.admin-actions button {
    padding: 3px 8px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    color: white;
}

.admin-actions .delete-entry {
    background-color: #dc3545;
}

.admin-actions .delete-entry:hover {
    background-color: #c82333;
}

.alert-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 10px 20px;
    border-radius: 5px;
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

.alert-success {
    background-color: #28a745;
    color: white;
}

.alert-error {
    background-color: #dc3545;
    color: white;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .winnings-input-box {
        flex-direction: column;
    }
    
    .quick-stats {
        grid-template-columns: 1fr;
    }
}