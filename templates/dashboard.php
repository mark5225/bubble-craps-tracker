<?php
/**
 * Dashboard Template for Bubble Craps Session Tracker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$active_session = BCT_Session::get_active_session($user_id);
$user_stats = bct_get_user_statistics($user_id);
?>

<div class="bct-container">
    <!-- Header -->
    <div class="bct-header">
        <h1><?php printf(__('Welcome back, %s!', 'bubble-craps-tracker'), $current_user->display_name); ?></h1>
        <p><?php _e('Track your craps sessions, analyze your performance, and compete with other players.', 'bubble-craps-tracker'); ?></p>
    </div>

    <!-- Navigation Tabs -->
    <div class="bct-nav-tabs">
        <button class="bct-nav-tab active" data-target="#bct-dashboard">
            <?php _e('Dashboard', 'bubble-craps-tracker'); ?>
        </button>
        <button class="bct-nav-tab" data-target="#bct-analytics">
            <?php _e('Analytics', 'bubble-craps-tracker'); ?>
        </button>
        <button class="bct-nav-tab" data-target="#bct-community">
            <?php _e('Community', 'bubble-craps-tracker'); ?>
        </button>
        <button class="bct-nav-tab" data-target="#bct-achievements">
            <?php _e('Achievements', 'bubble-craps-tracker'); ?>
        </button>
    </div>

    <!-- Dashboard Tab Content -->
    <div id="bct-dashboard" class="bct-tab-content">
        
        <!-- Session Tracker -->
        <div class="bct-session-tracker <?php echo $active_session ? 'bct-session-active' : ''; ?>">
            <?php if ($active_session): ?>
                <!-- Active Session Display -->
                <div id="bct-session-active">
                    <h2><?php _e('Active Session', 'bubble-craps-tracker'); ?></h2>
                    <div class="bct-bankroll-display">
                        <div><?php _e('Current Bankroll', 'bubble-craps-tracker'); ?></div>
                        <div id="bct-current-bankroll">$<?php echo number_format($active_session->starting_bankroll, 2); ?></div>
                        <div id="bct-net-result" class="bct-stat-positive">+$0.00</div>
                    </div>
                    <div class="bct-session-info">
                        <span><?php _e('Started:', 'bubble-craps-tracker'); ?> <?php echo date('g:i A', strtotime($active_session->session_start)); ?></span>
                        <span><?php _e('Duration:', 'bubble-craps-tracker'); ?> <span id="bct-session-time">0m</span></span>
                    </div>
                    <div class="bct-session-controls">
                        <button id="bct-end-session" class="bct-btn bct-btn-primary bct-btn-lg">
                            <?php _e('End Session', 'bubble-craps-tracker'); ?>
                        </button>
                        <button id="bct-pause-session" class="bct-btn bct-btn-secondary">
                            <?php _e('Pause Session', 'bubble-craps-tracker'); ?>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Start New Session -->
                <div id="bct-session-inactive">
                    <h2><?php _e('Start New Session', 'bubble-craps-tracker'); ?></h2>
                    <p><?php _e('Enter your starting bankroll to begin tracking your session.', 'bubble-craps-tracker'); ?></p>
                    <div class="bct-form-group">
                        <label class="bct-label" for="bct-starting-bankroll">
                            <?php _e('Starting Bankroll ($)', 'bubble-craps-tracker'); ?>
                        </label>
                        <input type="number" id="bct-starting-bankroll" class="bct-input" 
                               placeholder="100.00" min="1" step="0.01" required>
                    </div>
                    <div class="bct-session-controls">
                        <button id="bct-start-session" class="bct-btn bct-btn-primary bct-btn-lg">
                            <?php _e('Start Session', 'bubble-craps-tracker'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bet Logger (shown only during active session) -->
        <div id="bct-bet-logger" class="bct-card <?php echo !$active_session ? 'bct-hidden' : ''; ?>">
            <div class="bct-card-header">
                <h3 class="bct-card-title"><?php _e('Log Bet', 'bubble-craps-tracker'); ?></h3>
                <p class="bct-card-subtitle"><?php _e('Record your bets in real-time', 'bubble-craps-tracker'); ?></p>
            </div>
            
            <form id="bct-log-bet-form">
                <div class="bct-bet-logger">
                    <div class="bct-form-group">
                        <label class="bct-label"><?php _e('Bet Type', 'bubble-craps-tracker'); ?></label>
                        <select id="bct-bet-type" class="bct-select" required>
                            <option value=""><?php _e('Select Bet', 'bubble-craps-tracker'); ?></option>
                            <option value="pass_line"><?php _e('Pass Line', 'bubble-craps-tracker'); ?></option>
                            <option value="dont_pass"><?php _e("Don't Pass", 'bubble-craps-tracker'); ?></option>
                            <option value="come"><?php _e('Come', 'bubble-craps-tracker'); ?></option>
                            <option value="dont_come"><?php _e("Don't Come", 'bubble-craps-tracker'); ?></option>
                            <option value="field"><?php _e('Field', 'bubble-craps-tracker'); ?></option>
                            <option value="place_6"><?php _e('Place 6', 'bubble-craps-tracker'); ?></option>
                            <option value="place_8"><?php _e('Place 8', 'bubble-craps-tracker'); ?></option>
                            <option value="hard_ways"><?php _e('Hard Ways', 'bubble-craps-tracker'); ?></option>
                            <option value="any_seven"><?php _e('Any Seven', 'bubble-craps-tracker'); ?></option>
                            <option value="any_craps"><?php _e('Any Craps', 'bubble-craps-tracker'); ?></option>
                            <option value="odds"><?php _e('Odds Bet', 'bubble-craps-tracker'); ?></option>
                        </select>
                    </div>
                    
                    <div class="bct-form-group">
                        <label class="bct-label"><?php _e('Amount ($)', 'bubble-craps-tracker'); ?></label>
                        <input type="number" id="bct-bet-amount" class="bct-input" 
                               placeholder="25.00" min="0.01" step="0.01" required>
                    </div>
                    
                    <div class="bct-form-group">
                        <label class="bct-label"><?php _e('Result', 'bubble-craps-tracker'); ?></label>
                        <select id="bct-bet-result" class="bct-select" required>
                            <option value=""><?php _e('Select Result', 'bubble-craps-tracker'); ?></option>
                            <option value="win"><?php _e('Win', 'bubble-craps-tracker'); ?></option>
                            <option value="lose"><?php _e('Lose', 'bubble-craps-tracker'); ?></option>
                            <option value="push"><?php _e('Push', 'bubble-craps-tracker'); ?></option>
                        </select>
                    </div>
                    
                    <div class="bct-form-group">
                        <label class="bct-label"><?php _e('Payout ($)', 'bubble-craps-tracker'); ?></label>
                        <input type="number" id="bct-payout" class="bct-input" 
                               placeholder="25.00" min="0" step="0.01">
                    </div>
                    
                    <button type="submit" class="bct-btn bct-btn-primary">
                        <?php _e('Log Bet', 'bubble-craps-tracker'); ?>
                    </button>
                </div>
            </form>

            <!-- Quick Bet Buttons -->
            <div id="bct-quick-bet" class="bct-mt-2">
                <h4><?php _e('Quick Bets', 'bubble-craps-tracker'); ?></h4>
                <div class="bct-session-controls">
                    <button class="bct-quick-bet-btn bct-btn bct-btn-outline bct-btn-sm" 
                            data-bet-type="pass_line" data-amount="25">
                        <?php _e('Pass $25', 'bubble-craps-tracker'); ?>
                    </button>
                    <button class="bct-quick-bet-btn bct-btn bct-btn-outline bct-btn-sm" 
                            data-bet-type="field" data-amount="10">
                        <?php _e('Field $10', 'bubble-craps-tracker'); ?>
                    </button>
                    <button class="bct-quick-bet-btn bct-btn bct-btn-outline bct-btn-sm" 
                            data-bet-type="place_6" data-amount="30">
                        <?php _e('Place 6 $30', 'bubble-craps-tracker'); ?>
                    </button>
                    <button class="bct-quick-bet-btn bct-btn bct-btn-outline bct-btn-sm" 
                            data-bet-type="place_8" data-amount="30">
                        <?php _e('Place 8 $30', 'bubble-craps-tracker'); ?>
                    </button>
                </div>
            </div>

            <!-- Bet History -->
            <div class="bct-mt-2">
                <h4><?php _e('Recent Bets', 'bubble-craps-tracker'); ?></h4>
                <div id="bct-bet-history" class="bct-session-list" style="max-height: 200px;">
                    <p class="bct-text-center"><?php _e('No bets logged yet.', 'bubble-craps-tracker'); ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="bct-stats-grid">
            <div class="bct-stat-card">
                <div class="bct-stat-label"><?php _e('Total Sessions', 'bubble-craps-tracker'); ?></div>
                <div id="bct-total-sessions" class="bct-stat-number">
                    <?php echo $user_stats['sessions']->total_sessions ?? 0; ?>
                </div>
            </div>
            
            <div class="bct-stat-card">
                <div class="bct-stat-label"><?php _e('Net Winnings', 'bubble-craps-tracker'); ?></div>
                <div id="bct-total-winnings" class="bct-stat-number <?php 
                    $net = $user_stats['sessions']->total_net_result ?? 0;
                    echo $net >= 0 ? 'bct-stat-positive' : 'bct-stat-negative'; 
                ?>">
                    <?php echo bct_format_currency($net); ?>
                </div>
            </div>
            
            <div class="bct-stat-card">
                <div class="bct-stat-label"><?php _e('Average Session', 'bubble-craps-tracker'); ?></div>
                <div id="bct-avg-session" class="bct-stat-number">
                    <?php echo bct_format_currency($user_stats['sessions']->avg_session_result ?? 0); ?>
                </div>
            </div>
            
            <div class="bct-stat-card">
                <div class="bct-stat-label"><?php _e('Best Session', 'bubble-craps-tracker'); ?></div>
                <div id="bct-best-session" class="bct-stat-number bct-stat-positive">
                    <?php echo bct_format_currency($user_stats['sessions']->best_session ?? 0); ?>
                </div>
            </div>
        </div>

        <!-- Recent Sessions and Activity Chart -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
            
            <!-- Recent Sessions -->
            <div class="bct-card">
                <div class="bct-card-header">
                    <h3 class="bct-card-title"><?php _e('Recent Sessions', 'bubble-craps-tracker'); ?></h3>
                    <a href="/craps-tracker/sessions" class="bct-btn bct-btn-outline bct-btn-sm">
                        <?php _e('View All', 'bubble-craps-tracker'); ?>
                    </a>
                </div>
                <div id="bct-recent-sessions" class="bct-session-list">
                    <!-- Sessions loaded via JavaScript -->
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="bct-card">
                <div class="bct-card-header">
                    <h3 class="bct-card-title"><?php _e('30-Day Activity', 'bubble-craps-tracker'); ?></h3>
                </div>
                <div class="bct-chart-container">
                    <canvas id="bct-activity-chart" class="bct-chart-canvas"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- Analytics Tab Content -->
    <div id="bct-analytics" class="bct-tab-content bct-hidden">
        <div id="bct-analytics-content">
            
            <!-- Betting Patterns -->
            <div class="bct-card">
                <div class="bct-card-header">
                    <h3 class="bct-card-title"><?php _e('Betting Patterns', 'bubble-craps-tracker'); ?></h3>
                    <p class="bct-card-subtitle"><?php _e('Analyze your favorite bets and their performance', 'bubble-craps-tracker'); ?></p>
                </div>
                <div id="bct-betting-patterns">
                    <!-- Patterns loaded via JavaScript -->
                </div>
            </div>

            <!-- Advanced Charts -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
                <div class="bct-card">
                    <div class="bct-card-header">
                        <h3 class="bct-card-title"><?php _e('Win/Loss Distribution', 'bubble-craps-tracker'); ?></h3>
                    </div>
                    <div class="bct-chart-container">
                        <canvas id="bct-winloss-chart" class="bct-chart-canvas"></canvas>
                    </div>
                </div>

                <div class="bct-card">
                    <div class="bct-card-header">
                        <h3 class="bct-card-title"><?php _e('Betting Frequency', 'bubble-craps-tracker'); ?></h3>
                    </div>
                    <div class="bct-chart-container">
                        <canvas id="bct-frequency-chart" class="bct-chart-canvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="bct-card bct-mt-2">
                <div class="bct-card-header">
                    <h3 class="bct-card-title"><?php _e('Export Data', 'bubble-craps-tracker'); ?></h3>
                </div>
                <div class="bct-session-controls">
                    <button onclick="BCTracker.exportSessionData(
