<?php
/**
 * Dashboard Template for Bubble Craps Session Tracker
 * COMPLETE REPLACEMENT FILE for templates/dashboard.php
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

// Handle simple form submission for session start with casino
if (isset($_POST['start_session']) && $_POST['start_session'] == '1') {
    $starting_bankroll = floatval($_POST['starting_bankroll'] ?? 0);
    $casino_id = intval($_POST['casino_id'] ?? 0);
    
    if ($starting_bankroll > 0) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'craps_sessions',
            array(
                'user_id' => $user_id,
                'casino_id' => $casino_id ?: null,
                'starting_bankroll' => $starting_bankroll,
                'session_status' => 'active'
            ),
            array('%d', '%d', '%f', '%s')
        );
        
        if ($result) {
            // Refresh the page to show active session
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Handle session end
if (isset($_POST['end_session']) && $_POST['end_session'] == '1') {
    if ($active_session) {
        $ending_bankroll = floatval($_POST['ending_bankroll'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $net_result = $ending_bankroll - $active_session->starting_bankroll;
        
        global $wpdb;
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
        
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}

// Get casino info if active session has one
$session_casino = null;
if ($active_session && $active_session->casino_id) {
    $session_casino = bct_get_casino_info($active_session->casino_id);
}
?>

<!-- Casino Selection Modal -->
<div id="bct-casino-modal" class="bct-modal-overlay">
    <div class="bct-modal">
        <div class="bct-modal-header">
            <h2 class="bct-modal-title">Start New Session</h2>
            <button class="bct-modal-close" onclick="BCTracker.closeModal()">&times;</button>
        </div>
        
        <div class="bct-modal-body">
            <!-- Step 1: Select Casino -->
            <div class="bct-step">
                <div class="bct-step-title">
                    <span class="bct-step-number">1</span>
                    Select Casino
                </div>
                
                <div class="bct-casino-search">
                    <span class="bct-search-icon">🔍</span>
                    <input type="text" 
                           id="bct-casino-search" 
                           class="bct-search-input" 
                           placeholder="Search for casino name..."
                           onkeyup="BCTracker.filterCasinos()">
                </div>
                
                <div class="bct-location-filter" id="bct-location-filter">
                    <div class="bct-session-controls">
                        <button id="bct-open-modal" class="bct-btn bct-btn-primary bct-btn-lg" onclick="BCTracker.openModal()">
                            <?php _e('Start New Session', 'bubble-craps-tracker'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
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

        <!-- Your Casino Performance -->
        <?php 
        $casino_stats = bct_get_user_casino_stats($user_id);
        if (!empty($casino_stats)): ?>
            <div class="bct-card">
                <div class="bct-card-header">
                    <h3 class="bct-card-title"><?php _e('Your Casino Performance', 'bubble-craps-tracker'); ?></h3>
                </div>
                <div class="bct-casino-performance">
                    <?php foreach ($casino_stats as $stat): 
                        $win_rate = $stat->completed_sessions > 0 ? 
                            round(($stat->winning_sessions / $stat->completed_sessions) * 100, 1) : 0;
                    ?>
                        <div class="bct-casino-perf-item">
                            <div class="bct-casino-name">
                                <strong><?php echo esc_html($stat->casino_name ?: 'Unknown Casino'); ?></strong>
                                <span class="bct-session-count"><?php echo $stat->session_count; ?> sessions</span>
                            </div>
                            <div class="bct-casino-metrics">
                                <span class="bct-win-rate">Win Rate: <?php echo $win_rate; ?>%</span>
                                <span class="bct-avg-result <?php echo $stat->avg_result >= 0 ? 'positive' : 'negative'; ?>">
                                    Avg: <?php echo bct_format_currency($stat->avg_result); ?>
                                </span>
                                <span class="bct-best-session">
                                    Best: <?php echo bct_format_currency($stat->best_session); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Sessions -->
        <div class="bct-card">
            <div class="bct-card-header">
                <h3 class="bct-card-title"><?php _e('Recent Sessions', 'bubble-craps-tracker'); ?></h3>
                <a href="/craps-tracker/sessions" class="bct-btn bct-btn-outline bct-btn-sm">
                    <?php _e('View All', 'bubble-craps-tracker'); ?>
                </a>
            </div>
            <div id="bct-recent-sessions" class="bct-session-list">
                <?php 
                global $wpdb;
                $recent_sessions = $wpdb->get_results($wpdb->prepare("
                    SELECT s.*, p.post_title as casino_name
                    FROM {$wpdb->prefix}craps_sessions s
                    LEFT JOIN {$wpdb->posts} p ON s.casino_id = p.ID
                    WHERE s.user_id = %d AND s.session_status = 'completed'
                    ORDER BY s.session_end DESC 
                    LIMIT 10
                ", $user_id));
                
                if ($recent_sessions): 
                    foreach ($recent_sessions as $session): ?>
                        <div class="bct-session-item">
                            <div class="bct-session-meta">
                                <div class="bct-session-date">
                                    <?php echo date('M j, Y g:i A', strtotime($session->session_start)); ?>
                                    <?php if ($session->casino_name): ?>
                                        <span class="bct-session-casino">at <?php echo esc_html($session->casino_name); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="bct-session-result <?php echo $session->net_result >= 0 ? 'bct-stat-positive' : 'bct-stat-negative'; ?>">
                                    <?php echo bct_format_currency($session->net_result); ?>
                                </div>
                            </div>
                            <div class="bct-session-details">
                                Started: $<?php echo number_format($session->starting_bankroll, 2); ?>
                                • Ended: $<?php echo number_format($session->ending_bankroll, 2); ?>
                                • Duration: <?php echo human_time_diff(strtotime($session->session_start), strtotime($session->session_end)); ?>
                            </div>
                        </div>
                    <?php endforeach; 
                else: ?>
                    <p class="bct-text-center"><?php _e('No completed sessions yet.', 'bubble-craps-tracker'); ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Analytics Tab Content -->
    <div id="bct-analytics" class="bct-tab-content bct-hidden">
        <div class="bct-card">
            <div class="bct-card-header">
                <h3 class="bct-card-title"><?php _e('Analytics Coming Soon', 'bubble-craps-tracker'); ?></h3>
            </div>
            <p><?php _e('Advanced analytics features will be available in Phase 2.', 'bubble-craps-tracker'); ?></p>
        </div>
    </div>

    <!-- Community Tab Content -->
    <div id="bct-community" class="bct-tab-content bct-hidden">
        <div class="bct-card">
            <div class="bct-card-header">
                <h3 class="bct-card-title"><?php _e('Community Features Coming Soon', 'bubble-craps-tracker'); ?></h3>
            </div>
            <p><?php _e('Photo sharing, leaderboards, and community features will be available in Phase 3.', 'bubble-craps-tracker'); ?></p>
        </div>
    </div>

    <!-- Achievements Tab Content -->
    <div id="bct-achievements" class="bct-tab-content bct-hidden">
        <div class="bct-card">
            <div class="bct-card-header">
                <h3 class="bct-card-title"><?php _e('Achievements Coming Soon', 'bubble-craps-tracker'); ?></h3>
            </div>
            <p><?php _e('Achievement system and badges will be available in Phase 4.', 'bubble-craps-tracker'); ?></p>
        </div>
    </div>

</div>

<style>
/* Modal Styles */
.bct-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.bct-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.bct-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    transform: translateY(-50px);
    transition: transform 0.3s ease;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.bct-modal-overlay.active .bct-modal {
    transform: translateY(0);
}

.bct-modal-header {
    background: #1D3557;
    color: white;
    padding: 20px 30px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bct-modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.bct-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.bct-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.bct-modal-body {
    padding: 30px;
}

.bct-step {
    margin-bottom: 30px;
}

.bct-step-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1D3557;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.bct-step-number {
    background: #C51F1F;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 700;
    margin-right: 10px;
}

.bct-casino-search {
    position: relative;
    margin-bottom: 20px;
}

.bct-search-input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.bct-search-input:focus {
    outline: none;
    border-color: #C51F1F;
}

.bct-search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 16px;
}

.bct-location-filter {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.bct-location-chip {
    padding: 6px 12px;
    border: 2px solid #e9ecef;
    border-radius: 20px;
    background: white;
    color: #6c757d;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.bct-location-chip:hover,
.bct-location-chip.active {
    border-color: #C51F1F;
    background: #C51F1F;
    color: white;
}

.bct-casino-list {
    max-height: 250px;
    overflow-y: auto;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.bct-casino-item {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bct-casino-item:hover {
    background: white;
}

.bct-casino-item.selected {
    background: #fff3cd;
    border-left: 4px solid #C51F1F;
}

.bct-casino-item:last-child {
    border-bottom: none;
}

.bct-casino-info h4 {
    margin: 0 0 5px 0;
    color: #1D3557;
    font-weight: 600;
}

.bct-casino-location {
    color: #6c757d;
    font-size: 0.9rem;
}

.bct-casino-badges {
    display: flex;
    gap: 5px;
    flex-direction: column;
    align-items: flex-end;
}

.bct-badge {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.bct-badge.bubble {
    background: #C51F1F;
}

.bct-bankroll-input {
    width: 100%;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1.1rem;
    text-align: center;
    font-weight: 600;
    color: #1D3557;
    box-sizing: border-box;
}

.bct-bankroll-input:focus {
    outline: none;
    border-color: #C51F1F;
}

.bct-modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.bct-add-casino {
    text-align: center;
    padding: 20px;
    border-top: 1px solid #e9ecef;
    margin-top: 15px;
}

.bct-add-casino a {
    color: #C51F1F;
    text-decoration: none;
    font-weight: 600;
}

.bct-add-casino a:hover {
    text-decoration: underline;
}

/* Session Casino Display */
.bct-session-casino {
    text-align: center;
    margin-bottom: 15px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.bct-casino-label {
    color: rgba(255, 255, 255, 0.8);
    margin-right: 8px;
}

.bct-casino-location {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-left: 8px;
}

/* Casino Performance Styles */
.bct-casino-performance {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.bct-casino-perf-item {
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.bct-casino-name {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.bct-session-count {
    color: #6c757d;
    font-size: 0.9rem;
}

.bct-casino-metrics {
    display: flex;
    gap: 20px;
    font-size: 0.9rem;
}

.bct-casino-metrics .positive {
    color: #28a745;
}

.bct-casino-metrics .negative {
    color: #C51F1F;
}

/* Recent Sessions Casino Name */
.bct-session-item .bct-session-casino {
    color: #6c757d;
    font-size: 0.9rem;
    margin-left: 10px;
    background: none;
    padding: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .bct-modal {
        width: 95%;
        margin: 20px;
        max-height: 85vh;
    }

    .bct-modal-header,
    .bct-modal-body,
    .bct-modal-footer {
        padding: 20px;
    }

    .bct-location-filter {
        gap: 8px;
    }

    .bct-location-chip {
        font-size: 0.8rem;
        padding: 5px 10px;
    }

    .bct-modal-footer {
        flex-direction: column;
    }

    .bct-casino-metrics {
        flex-direction: column;
        gap: 5px;
    }
}

.bct-hidden {
    display: none;
}
</style>

<script>
// Enhanced BCTracker object with casino functionality
(function($) {
    'use strict';

    window.BCTracker = {
        selectedCasino: null,
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).off('click.bct').on('click.bct', '#bct-end-session', this.endSession.bind(this));
            $(document).off('click.bct').on('click.bct', '.bct-nav-tab', this.switchTab.bind(this));
            
            // Location filter clicks
            $(document).off('click.bct').on('click.bct', '.bct-location-chip', function() {
                $('.bct-location-chip').removeClass('active');
                $(this).addClass('active');
                BCTracker.filterCasinos();
            });
            
            // Close modal on escape or overlay click
            $(document).off('keydown.bct').on('keydown.bct', function(e) {
                if (e.key === 'Escape') BCTracker.closeModal();
            });
            
            $('#bct-casino-modal').off('click.bct').on('click.bct', function(e) {
                if (e.target === this) BCTracker.closeModal();
            });
        },
        
        openModal: function() {
            $('#bct-casino-modal').addClass('active');
            $('body').css('overflow', 'hidden');
        },
        
        closeModal: function() {
            $('#bct-casino-modal').removeClass('active');
            $('body').css('overflow', '');
            this.resetModal();
        },
        
        resetModal: function() {
            this.selectedCasino = null;
            $('#bct-starting-bankroll').val('');
            $('#bct-start-session-btn').prop('disabled', true);
            $('.bct-casino-item').removeClass('selected');
        },
        
        selectCasino: function(element) {
            $('.bct-casino-item').removeClass('selected');
            $(element).addClass('selected');
            
            this.selectedCasino = {
                id: $(element).data('casino-id'),
                name: $(element).find('h4').text(),
                location: $(element).find('.bct-casino-location').text()
            };
            
            this.validateForm();
        },
        
        filterCasinos: function() {
            const searchTerm = $('#bct-casino-search').val().toLowerCase();
            const activeLocation = $('.bct-location-chip.active').data('location');
            
            $('.bct-casino-item').each(function() {
                const $item = $(this);
                const name = $item.find('h4').text().toLowerCase();
                const location = $item.find('.bct-casino-location').text().toLowerCase();
                const itemLocation = $item.data('location') || location;
                
                const matchesSearch = name.includes(searchTerm) || location.includes(searchTerm);
                const matchesLocation = activeLocation === 'all' || 
                    itemLocation.includes(activeLocation.replace('-', ' '));
                
                $item.toggle(matchesSearch && matchesLocation);
            });
        },
        
        handleBankrollKeypress: function(event) {
            if (event.key === 'Enter') {
                this.startSessionFromModal();
            } else {
                setTimeout(() => this.validateForm(), 100);
            }
        },
        
        validateForm: function() {
            const bankroll = parseFloat($('#bct-starting-bankroll').val());
            const hasValidBankroll = bankroll && bankroll > 0;
            const hasCasino = this.selectedCasino !== null;
            
            $('#bct-start-session-btn').prop('disabled', !(hasValidBankroll && hasCasino));
        },
        
        startSessionFromModal: function() {
            const bankroll = parseFloat($('#bct-starting-bankroll').val());
            
            if (!this.selectedCasino || !bankroll || bankroll <= 0) {
                alert('Please select a casino and enter a valid bankroll amount.');
                return;
            }
            
            // Create and submit form
            const form = $('<form method="post">')
                .append($('<input type="hidden" name="start_session">').val('1'))
                .append($('<input type="hidden" name="casino_id">').val(this.selectedCasino.id))
                .append($('<input type="hidden" name="starting_bankroll">').val(bankroll))
                .appendTo('body');
                
            form.submit();
        },
        
        endSession: function(e) {
            e.preventDefault();
            
            const endingBankroll = prompt('Enter your ending bankroll ($):');
            if (endingBankroll === null) return;
            
            const amount = parseFloat(endingBankroll);
            if (isNaN(amount) || amount < 0) {
                alert('Please enter a valid amount.');
                return;
            }
            
            const notes = prompt('Session notes (optional):') || '';
            
            $('<form method="post">')
                .append($('<input type="hidden" name="end_session">').val('1'))
                .append($('<input type="hidden" name="ending_bankroll">').val(amount))
                .append($('<input type="hidden" name="notes">').val(notes))
                .appendTo('body')
                .submit();
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            const $tab = $(e.currentTarget);
            const target = $tab.data('target');
            
            if (!target) return;
            
            $('.bct-nav-tab').removeClass('active');
            $tab.addClass('active');
            
            $('.bct-tab-content').addClass('bct-hidden');
            $(target).removeClass('bct-hidden');
        }
    };
    
    $(document).ready(() => {
        BCTracker.init();
    });
    
})(jQuery);
</script>bct-location-chip active" data-location="all">All States</div>
                    <?php 
                    $locations = bct_get_casino_locations();
                    foreach (array_slice($locations, 0, 5) as $location): ?>
                        <div class="bct-location-chip" data-location="<?php echo esc_attr($location['slug']); ?>">
                            <?php echo esc_html($location['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="bct-casino-list" id="bct-casino-list">
                    <?php 
                    $casinos = bct_get_casinos_for_session();
                    if (!empty($casinos)): 
                        foreach ($casinos as $casino): ?>
                            <div class="bct-casino-item" 
                                 data-casino-id="<?php echo $casino['id']; ?>" 
                                 data-location="<?php echo esc_attr(strtolower($casino['location'])); ?>"
                                 onclick="BCTracker.selectCasino(this)">
                                <div class="bct-casino-info">
                                    <h4><?php echo esc_html($casino['name']); ?></h4>
                                    <div class="bct-casino-location"><?php echo esc_html($casino['location']); ?></div>
                                </div>
                                <div class="bct-casino-badges">
                                    <?php if ($casino['has_bubble']): ?>
                                        <span class="bct-badge bubble">Bubble Craps</span>
                                    <?php endif; ?>
                                    <?php if ($casino['has_tables']): ?>
                                        <span class="bct-badge">Tables</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; 
                    else: ?>
                        <div class="bct-no-results">
                            <p>No casinos found. <a href="https://www.bubble-craps.com/all-listings/add-listing/" target="_blank">Add a casino listing</a></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="bct-add-casino">
                    Don't see your casino? <a href="https://www.bubble-craps.com/all-listings/add-listing/" target="_blank">Add a new listing</a>
                </div>
            </div>
            
            <!-- Step 2: Starting Bankroll -->
            <div class="bct-step">
                <div class="bct-step-title">
                    <span class="bct-step-number">2</span>
                    Starting Bankroll
                </div>
                
                <input type="number" 
                       id="bct-starting-bankroll" 
                       class="bct-bankroll-input" 
                       placeholder="Enter amount (e.g., 250.00)" 
                       min="1" 
                       step="0.01"
                       onkeypress="BCTracker.handleBankrollKeypress(event)">
            </div>
        </div>
        
        <div class="bct-modal-footer">
            <button class="bct-btn bct-btn-secondary" onclick="BCTracker.closeModal()">
                Cancel
            </button>
            <button class="bct-btn bct-btn-primary" 
                    id="bct-start-session-btn" 
                    onclick="BCTracker.startSessionFromModal()" 
                    disabled>
                Start Session
            </button>
        </div>
    </div>
</div>

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
                    
                    <?php if ($session_casino): ?>
                        <div class="bct-session-casino">
                            <span class="bct-casino-label"><?php _e('Playing at:', 'bubble-craps-tracker'); ?></span>
                            <strong><?php echo esc_html($session_casino['name']); ?></strong>
                            <span class="bct-casino-location"><?php echo esc_html($session_casino['location']); ?></span>
                        </div>
                    <?php endif; ?>
                    
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
                    <p><?php _e('Select your casino and enter your starting bankroll to begin tracking.', 'bubble-craps-tracker'); ?></p>
                    <div class="