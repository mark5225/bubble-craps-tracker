<?php
defined('ABSPATH') or die('Direct access not allowed');
?>

<div class="wrap">
    <div class="bubble-craps-admin-dashboard">
        <div class="admin-header">
            <h2>Bubble Craps Admin Dashboard</h2>
            <div class="admin-status"></div>
            <div class="admin-actions">
                <button id="export-data" class="admin-button">Export Data</button>
                <button id="refresh-data" class="admin-button">Refresh Data</button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Winnings</h3>
                <div class="stat-value" id="total-winnings-value">Loading...</div>
            </div>
            <div class="stat-card">
                <h3>Total Entries</h3>
                <div class="stat-value" id="total-entries-value">Loading...</div>
            </div>
            <div class="stat-card">
                <h3>Average Win</h3>
                <div class="stat-value" id="average-win-value">Loading...</div>
            </div>
            <div class="stat-card">
                <h3>Active Casinos</h3>
                <div class="stat-value" id="active-casinos-value">Loading...</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-wrapper">
                <h3>Daily Winnings Trend (Last 30 Days)</h3>
                <div class="chart-container">
                    <canvas id="winnings-trend"></canvas>
                </div>
            </div>
            <div class="chart-wrapper">
                <h3>Machine Type Distribution</h3>
                <div class="chart-container">
                    <canvas id="machine-dist"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="data-section">
            <h3>Entry Management</h3>
            <div class="filters">
                <input type="text" id="search-filter" placeholder="Search...">
                <select id="casino-filter">
                    <option value="">All Casinos</option>
                </select>
                <select id="date-filter">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="all" selected>All Time</option>
                </select>
            </div>
            <div class="table-container">
                <table id="entries-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Casino</th>
                            <th>Amount</th>
                            <th>Machine Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="pagination"></div>
        </div>
    </div>
</div>

<?php
// Add help tab
$screen = get_current_screen();
$screen->add_help_tab(array(
    'id'       => 'bct-admin-overview',
    'title'    => 'Overview',
    'content'  => '
        <h2>Bubble Craps Tracker Admin Dashboard</h2>
        <p>This dashboard provides comprehensive management tools for the Bubble Craps Tracker plugin:</p>
        <ul>
            <li><strong>Stats Overview:</strong> View total winnings, entries, and averages</li>
            <li><strong>Charts:</strong> Analyze trends and machine type distribution</li>
            <li><strong>Entry Management:</strong> Search, filter, and manage all entries</li>
            <li><strong>Data Export:</strong> Export all data to CSV format</li>
        </ul>
    '
));