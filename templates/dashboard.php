<?php
/**
 * Enhanced Log Session Dashboard - Bubble Craps Focused
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
$user_stats = bct_get_user_statistics($user_id);

// Handle session logging
if (isset($_POST['log_session']) && $_POST['log_session'] == '1') {
    $casino_id = intval($_POST['casino_id'] ?? 0);
    $starting_bankroll = floatval($_POST['starting_bankroll'] ?? 0);
    $ending_bankroll = floatval($_POST['ending_bankroll'] ?? 0);
    $duration_hours = intval($_POST['duration_hours'] ?? 0);
    $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
    $table_rating = intval($_POST['table_rating'] ?? 0);
    $table_mood = sanitize_text_field($_POST['table_mood'] ?? '');
    $strategy_used = sanitize_text_field($_POST['strategy_used'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    
    if ($starting_bankroll > 0 && $ending_bankroll >= 0) {
        global $wpdb;
        
        // Calculate session duration in minutes
        $total_minutes = ($duration_hours * 60) + $duration_minutes;
        $net_result = $ending_bankroll - $starting_bankroll;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'craps_sessions',
            array(
                'user_id' => $user_id,
                'casino_id' => $casino_id ?: null,
                'starting_bankroll' => $starting_bankroll,
                'ending_bankroll' => $ending_bankroll,
                'net_result' => $net_result,
                'session_status' => 'completed',
                'session_start' => current_time('mysql'),
                'session_end' => current_time('mysql'),
                'notes' => $notes
            ),
            array('%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            // Store additional session metadata
            $session_id = $wpdb->insert_id;
            if ($table_rating > 0) {
                update_post_meta($session_id, 'table_rating', $table_rating);
            }
            if ($table_mood) {
                update_post_meta($session_id, 'table_mood', $table_mood);
            }
            if ($strategy_used) {
                update_post_meta($session_id, 'strategy_used', $strategy_used);
            }
            if ($total_minutes > 0) {
                update_post_meta($session_id, 'session_duration_minutes', $total_minutes);
            }
            
            wp_redirect($_SERVER['REQUEST_URI'] . '?logged=1');
            exit;
        }
    }
}

// Show success message
$just_logged = isset($_GET['logged']) && $_GET['logged'] == '1';
?>

<!-- Casino/Strategy Selection Modal -->
<div id="bct-log-modal" class="bct-modal-overlay">
    <div class="bct-modal">
        <div class="bct-modal-header">
            <h2 class="bct-modal-title">Log Your Session</h2>
            <button class="bct-modal-close" onclick="BCTracker.closeModal()">&times;</button>
        </div>
        
        <div class="bct-modal-body">
            <!-- Step 1: Select Casino -->
            <div class="bct-step" id="step-casino">
                <div class="bct-step-title">
                    <span class="bct-step-number">1</span>
                    Where did you play?
                </div>
                
                <div class="bct-casino-search">
                    <span class="bct-search-icon">🔍</span>
                    <input type="text" 
                           id="bct-casino-search" 
                           class="bct-search-input" 
                           placeholder="Search for casino name..."
                           onkeyup="BCTracker.filterCasinos()">
                </div>
                
                <div class="bct-location-filter">
                    <div class="bct-location-chip active" data-location="all">All States</div>
                    <?php 
                    $locations = bct_get_casino_locations();
                    foreach (array_slice($locations, 0, 5) as $location): ?>
                        <div class="bct-location-chip" data-location="<?php echo esc_attr($location['slug']); ?>">
                            <?php echo esc_html($location['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="bct-casino-list">
                    <?php 
                    $casinos = bct_get_casinos_for_session();
                    if (!empty($casinos)): 
                        foreach ($casinos as $casino): 
                            // Get bubble craps details from custom fields
                            $bubble_types = get_post_meta($casino['id'], '_custom-checkbox', true);
                            $min_bet = get_post_meta($casino['id'], '_custom-radio-3', true);
                            $has_rewards = get_post_meta($casino['id'], '_custom-radio', true);
                            ?>
                            <div class="bct-casino-item" 
                                 data-casino-id="<?php echo $casino['id']; ?>" 
                                 data-location="<?php echo esc_attr(strtolower($casino['location'])); ?>"
                                 onclick="BCTracker.selectCasino(this, <?php echo htmlspecialchars(json_encode($casino)); ?>)">
                                <div class="bct-casino-info">
                                    <h4><?php echo esc_html($casino['name']); ?></h4>
                                    <div class="bct-casino-location"><?php echo esc_html($casino['location']); ?></div>
                                    <div class="bct-casino-details">
                                        <?php if ($min_bet && $min_bet !== 'N/A or Unknown'): ?>
                                            <span class="bct-detail">Min Bet: <?php echo esc_html($min_bet); ?></span>
                                        <?php endif; ?>
                                        <?php if ($has_rewards === 'Yes'): ?>
                                            <span class="bct-detail">✓ Rewards</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="bct-casino-badges">
                                    <?php if ($casino['has_bubble']): ?>
                                        <?php if (is_array($bubble_types)): ?>
                                            <?php foreach ($bubble_types as $type): ?>
                                                <?php if ($type !== 'none'): ?>
                                                    <span class="bct-badge bubble"><?php echo esc_html(ucfirst($type)); ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="bct-badge bubble">Bubble Craps</span>
                                        <?php endif; ?>
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
                
                <!-- Selected Casino Preview -->
                <div id="bct-casino-preview" class="bct-casino-preview" style="display: none;">
                    <div class="bct-preview-header">
                        <img id="bct-casino-image" src="" alt="" class="bct-casino-photo">
                        <div class="bct-preview-info">
                            <h3 id="bct-casino-name"></h3>
                            <p id="bct-casino-location-preview"></p>
                            <div id="bct-bubble-details"></div>
                        </div>
                        <div class="bct-preview-actions">
                            <button type="button" class="bct-btn bct-btn-outline bct-btn-sm" onclick="alert('Casino edit functionality coming soon!')">
                                ✏️ Update Info
                            </button>
                        </div>
                    </div>
                    <div class="bct-preview-stats">
                        <span class="bct-stat">Your last visit: <strong>Never</strong></span>
                        <span class="bct-stat">Community: <strong>12 sessions logged</strong></span>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Session Details -->
            <div class="bct-step" id="step-details">
                <div class="bct-step-title">
                    <span class="bct-step-number">2</span>
                    How did your session go?
                </div>
                
                <div class="bct-form-grid">
                    <div class="bct-form-group">
                        <label>Starting Bankroll ($)</label>
                        <input type="number" id="bct-starting-bankroll" class="bct-input" 
                               min="1" step="0.01" placeholder="100.00" required>
                    </div>
                    <div class="bct-form-group">
                        <label>Ending Bankroll ($)</label>
                        <input type="number" id="bct-ending-bankroll" class="bct-input" 
                               min="0" step="0.01" placeholder="150.00" required>
                    </div>
                </div>
                
                <div class="bct-form-grid">
                    <div class="bct-form-group">
                        <label>Session Duration</label>
                        <div class="bct-duration-inputs">
                            <input type="number" id="bct-hours" class="bct-input-small" 
                                   min="0" max="12" placeholder="2"> hours
                            <input type="number" id="bct-minutes" class="bct-input-small" 
                                   min="0" max="59" placeholder="30"> minutes
                        </div>
                    </div>
                    <div class="bct-form-group">
                        <label>How were the machines?</label>
                        <div class="bct-rating">
                            <span class="bct-star" data-rating="1">⭐</span>
                            <span class="bct-star" data-rating="2">⭐</span>
                            <span class="bct-star" data-rating="3">⭐</span>
                            <span class="bct-star" data-rating="4">⭐</span>
                            <span class="bct-star" data-rating="5">⭐</span>
                        </div>
                    </div>
                </div>
                
                <div class="bct-form-group">
                    <label>Table Mood</label>
                    <div class="bct-mood-chips">
                        <div class="bct-mood-chip" data-mood="hot">🔥 Hot</div>
                        <div class="bct-mood-chip" data-mood="cold">🧊 Cold</div>
                        <div class="bct-mood-chip" data-mood="streaky">📈 Streaky</div>
                        <div class="bct-mood-chip" data-mood="consistent">📊 Consistent</div>
                        <div class="bct-mood-chip" data-mood="choppy">🌊 Choppy</div>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Strategy -->
            <div class="bct-step" id="step-strategy">
                <div class="bct-step-title">
                    <span class="bct-step-number">3</span>
                    Did you use a strategy?
                </div>
                
                <div class="bct-strategy-selection">
                    <div class="bct-strategy-option" data-strategy="none">
                        <div class="bct-strategy-card">
                            <h4>No Strategy</h4>
                            <p>Just played by feel</p>
                        </div>
                    </div>
                    <div class="bct-strategy-option" data-strategy="pass-line">
                        <div class="bct-strategy-card">
                            <h4>Pass Line Only</h4>
                            <p>Stick to the basics - lowest house edge</p>
                            <a href="#" class="bct-strategy-link">Learn more →</a>
                        </div>
                    </div>
                    <div class="bct-strategy-option" data-strategy="martingale">
                        <div class="bct-strategy-card">
                            <h4>Martingale System</h4>
                            <p>Double bet after losses</p>
                            <a href="#" class="bct-strategy-link">Learn more →</a>
                        </div>
                    </div>
                    <div class="bct-strategy-option" data-strategy="iron-cross">
                        <div class="bct-strategy-card">
                            <h4>Iron Cross</h4>
                            <p>Cover multiple numbers</p>
                            <a href="#" class="bct-strategy-link">Learn more →</a>
                        </div>
                    </div>
                    <div class="bct-strategy-option" data-strategy="dont-pass">
                        <div class="bct-strategy-card">
                            <h4>Don't Pass</h4>
                            <p>Bet against the shooter</p>
                            <a href="#" class="bct-strategy-link">Learn more →</a>
                        </div>
                    </div>
                    <div class="bct-strategy-option" data-strategy="custom">
                        <div class="bct-strategy-card">
                            <h4>Custom Strategy</h4>
                            <p>Your own approach</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Notes & Photo -->
            <div class="bct-step" id="step-notes">
                <div class="bct-step-title">
                    <span class="bct-step-number">4</span>
                    Any notes or photos?
                </div>
                
                <div class="bct-form-group">
                    <label>Session Notes (optional)</label>
                    <textarea id="bct-notes" class="bct-textarea" 
                              placeholder="Hot streak on the pass line! Dealer was friendly. Machine next to me was ice cold..."></textarea>
                </div>
                
                <div class="bct-form-group">
                    <label>Win Photo (optional)</label>
                    <div class="bct-photo-upload">
                        <div class="bct-photo-placeholder">
                            📸 <span>Photo upload coming soon!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bct-modal-footer">
            <button class="bct-btn bct-btn-secondary" onclick="BCTracker.closeModal()">
                Cancel
            </button>
            <button class="bct-btn bct-btn-primary" 
                    id="bct-log-session-btn" 
                    onclick="BCTracker.submitSession()" 
                    disabled>
                Log Session
            </button>
        </div>
    </div>
</div>

<div class="bct-container">
    <!-- Header -->
    <div class="bct-header">
        <h1><?php printf(__('Welcome back, %s!', 'bubble-craps-tracker'), $current_user->display_name); ?></h1>
        <p><?php _e('Track your bubble craps sessions and improve your game.', 'bubble-craps-tracker'); ?></p>
    </div>

    <?php if ($just_logged): ?>
        <div class="bct-success-message">
            ✅ Session logged successfully! Great job tracking your play.
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="bct-main-content">
        
        <!-- Log Session CTA -->
        <div class="bct-log-session-cta">
            <h2><?php _e('Log Your Latest Session', 'bubble-craps-tracker'); ?></h2>
            <p><?php _e('Record how your bubble craps session went and track your progress over time.', 'bubble-craps-tracker'); ?></p>
            <button id="bct-open-log-modal" class="bct-btn bct-btn-primary bct-btn-lg" onclick="BCTracker.openModal()">
                📝 <?php _e('Log Session', 'bubble-craps-tracker'); ?>
            </button>
        </div>

        <!-- Quick Stats -->
        <div class="bct-stats-grid">
            <div class="bct-stat-card">
                <div class="bct-stat-number">
                    <?php echo $user_stats['sessions']->total_sessions ?? 0; ?>
                </div>
                <div class="bct-stat-label"><?php _e('Sessions Logged', 'bubble-craps-tracker'); ?></div>
            </div>
            
            <div class="bct-stat-card">
                <div class="bct-stat-number <?php 
                    $net = $user_stats['sessions']->total_net_result ?? 0;
                    echo $net >= 0 ? 'bct-stat-positive' : 'bct-stat-negative'; 
                ?>">
                    <?php echo bct_format_currency($net); ?>
                </div>
                <div class="bct-stat-label"><?php _e('Net Winnings', 'bubble-craps-tracker'); ?></div>
            </div>
            
            <div class="bct-stat-card">
                <div class="bct-stat-number">
                    <?php echo bct_format_currency($user_stats['sessions']->avg_session_result ?? 0); ?>
                </div>
                <div class="bct-stat-label"><?php _e('Average Session', 'bubble-craps-tracker'); ?></div>
            </div>
            
            <div class="bct-stat-card">
                <div class="bct-stat-number bct-stat-positive">
                    <?php echo bct_format_currency($user_stats['sessions']->best_session ?? 0); ?>
                </div>
                <div class="bct-stat-label"><?php _e('Best Session', 'bubble-craps-tracker'); ?></div>
            </div>
        </div>

        <!-- Favorite Casinos -->
        <?php 
        $casino_stats = bct_get_user_casino_stats($user_id);
        if (!empty($casino_stats)): ?>
            <div class="bct-card">
                <div class="bct-card-header">
                    <h3 class="bct-card-title">🏆 <?php _e('Your Favorite Casinos', 'bubble-craps-tracker'); ?></h3>
                </div>
                <div class="bct-favorite-casinos">
                    <?php foreach (array_slice($casino_stats, 0, 3) as $stat): 
                        $win_rate = $stat->completed_sessions > 0 ? 
                            round(($stat->winning_sessions / $stat->completed_sessions) * 100, 1) : 0;
                    ?>
                        <div class="bct-favorite-casino">
                            <div class="bct-casino-header">
                                <h4><?php echo esc_html($stat->casino_name ?: 'Unknown Casino'); ?></h4>
                                <span class="bct-session-count"><?php echo $stat->session_count; ?> sessions</span>
                            </div>
                            <div class="bct-casino-stats">
                                <span class="bct-win-rate">Win Rate: <?php echo $win_rate; ?>%</span>
                                <span class="bct-avg-result <?php echo $stat->avg_result >= 0 ? 'positive' : 'negative'; ?>">
                                    Avg: <?php echo bct_format_currency($stat->avg_result); ?>
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
                <h3 class="bct-card-title">📋 <?php _e('Recent Sessions', 'bubble-craps-tracker'); ?></h3>
            </div>
            <div class="bct-session-list">
                <?php 
                global $wpdb;
                $recent_sessions = $wpdb->get_results($wpdb->prepare("
                    SELECT s.*, p.post_title as casino_name
                    FROM {$wpdb->prefix}craps_sessions s
                    LEFT JOIN {$wpdb->posts} p ON s.casino_id = p.ID
                    WHERE s.user_id = %d AND s.session_status = 'completed'
                    ORDER BY s.session_start DESC 
                    LIMIT 10
                ", $user_id));
                
                if ($recent_sessions): 
                    foreach ($recent_sessions as $session): 
                        $strategy = get_post_meta($session->id, 'strategy_used', true);
                        $table_mood = get_post_meta($session->id, 'table_mood', true);
                        ?>
                        <div class="bct-session-item">
                            <div class="bct-session-meta">
                                <div class="bct-session-date">
                                    <?php echo date('M j, Y', strtotime($session->session_start)); ?>
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
                                <?php if ($strategy): ?>
                                    • Strategy: <?php echo esc_html(ucfirst(str_replace('-', ' ', $strategy))); ?>
                                <?php endif; ?>
                                <?php if ($table_mood): ?>
                                    • Table was <?php echo esc_html($table_mood); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; 
                else: ?>
                    <div class="bct-empty-state">
                        <h4>No sessions logged yet!</h4>
                        <p>Start tracking your bubble craps sessions to see patterns and improve your game.</p>
                        <button class="bct-btn bct-btn-primary" onclick="BCTracker.openModal()">
                            Log Your First Session
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
/* Enhanced Modal Styles */
.bct-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
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
    border-radius: 16px;
    width: 95%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(-50px);
    transition: transform 0.3s ease;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.bct-modal-overlay.active .bct-modal {
    transform: translateY(0);
}

.bct-modal-header {
    background: linear-gradient(135deg, #1D3557 0%, #2A4A6B 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bct-modal-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.bct-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 35px;
    height: 35px;
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
    max-height: 60vh;
    overflow-y: auto;
}

.bct-step {
    margin-bottom: 40px;
}

.bct-step-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1D3557;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.bct-step-number {
    background: #C51F1F;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    margin-right: 12px;
}

/* Casino Search & Selection */
.bct-casino-search {
    position: relative;
    margin-bottom: 20px;
}

.bct-search-input {
    width: 100%;
    padding: 15px 20px 15px 50px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.bct-search-input:focus {
    outline: none;
    border-color: #C51F1F;
    box-shadow: 0 0 0 3px rgba(197, 31, 31, 0.1);
}

.bct-search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 18px;
}

.bct-location-filter {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.bct-location-chip {
    padding: 8px 16px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
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
    max-height: 300px;
    overflow-y: auto;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: #f8f9fa;
}

.bct-casino-item {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bct-casino-item:hover {
    background: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
    font-size: 1.1rem;
}

.bct-casino-location {
    color: #6c757d;
    font-size: 0.95rem;
    margin-bottom: 8px;
}

.bct-casino-details {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.bct-detail {
    background: #e9ecef;
    color: #495057;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.bct-casino-badges {
    display: flex;
    gap: 8px;
    flex-direction: column;
    align-items: flex-end;
}

.bct-badge {
    background: #28a745;
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
}

.bct-badge.bubble {
    background: #C51F1F;
}

/* Casino Preview */
.bct-casino-preview {
    background: white;
    border: 2px solid #C51F1F;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.bct-preview-header {
    display: flex;
    gap: 15px;
    align-items: flex-start;
    margin-bottom: 15px;
}

.bct-casino-photo {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #f8f9fa;
}

.bct-preview-info {
    flex: 1;
}

.bct-preview-info h3 {
    margin: 0 0 5px 0;
    color: #1D3557;
}

.bct-preview-info p {
    margin: 0 0 10px 0;
    color: #6c757d;
}

#bct-bubble-details {
    font-size: 0.9rem;
    color: #495057;
}

.bct-preview-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bct-preview-stats {
    display: flex;
    gap: 20px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
    font-size: 0.9rem;
    color: #6c757d;
}

/* Form Elements */
.bct-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.bct-form-group {
    margin-bottom: 20px;
}

.bct-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1D3557;
}

.bct-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.bct-input:focus {
    outline: none;
    border-color: #C51F1F;
    box-shadow: 0 0 0 3px rgba(197, 31, 31, 0.1);
}

.bct-duration-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bct-input-small {
    width: 60px;
    padding: 10px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    text-align: center;
    font-size: 1rem;
}

.bct-input-small:focus {
    outline: none;
    border-color: #C51F1F;
}

.bct-textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    min-height: 100px;
    resize: vertical;
    box-sizing: border-box;
    font-family: inherit;
}

.bct-textarea:focus {
    outline: none;
    border-color: #C51F1F;
    box-shadow: 0 0 0 3px rgba(197, 31, 31, 0.1);
}

/* Rating Stars */
.bct-rating {
    display: flex;
    gap: 5px;
}

.bct-star {
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.3;
    transition: opacity 0.2s ease;
}

.bct-star:hover,
.bct-star.active {
    opacity: 1;
}

/* Mood Chips */
.bct-mood-chips {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.bct-mood-chip {
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    background: white;
    color: #6c757d;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.bct-mood-chip:hover,
.bct-mood-chip.active {
    border-color: #C51F1F;
    background: #C51F1F;
    color: white;
}

/* Strategy Selection */
.bct-strategy-selection {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.bct-strategy-option {
    cursor: pointer;
}

.bct-strategy-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    background: white;
    transition: all 0.3s ease;
    height: 100%;
}

.bct-strategy-card:hover,
.bct-strategy-option.selected .bct-strategy-card {
    border-color: #C51F1F;
    background: #fff3cd;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(197, 31, 31, 0.15);
}

.bct-strategy-card h4 {
    margin: 0 0 8px 0;
    color: #1D3557;
    font-size: 1.1rem;
}

.bct-strategy-card p {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.bct-strategy-link {
    color: #C51F1F;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
}

.bct-strategy-link:hover {
    text-decoration: underline;
}

/* Photo Upload */
.bct-photo-upload {
    border: 2px dashed #e9ecef;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    color: #6c757d;
    background: #f8f9fa;
}

.bct-photo-placeholder {
    font-size: 1.1rem;
}

/* Modal Footer */
.bct-modal-footer {
    padding: 25px 30px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
}

/* Main Content Styles */
.bct-log-session-cta {
    background: linear-gradient(135deg, #C51F1F 0%, #A01919 100%);
    color: white;
    padding: 40px;
    border-radius: 16px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 8px 25px rgba(197, 31, 31, 0.3);
}

.bct-log-session-cta h2 {
    margin: 0 0 10px 0;
    font-size: 1.8rem;
}

.bct-log-session-cta p {
    margin: 0 0 25px 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.bct-success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #c3e6cb;
    font-weight: 600;
}

.bct-favorite-casinos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.bct-favorite-casino {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.bct-casino-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.bct-casino-header h4 {
    margin: 0;
    color: #1D3557;
}

.bct-session-count {
    background: #C51F1F;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.bct-casino-stats {
    display: flex;
    gap: 15px;
    font-size: 0.9rem;
}

.bct-win-rate {
    color: #495057;
}

.bct-avg-result.positive {
    color: #28a745;
    font-weight: 600;
}

.bct-avg-result.negative {
    color: #C51F1F;
    font-weight: 600;
}

.bct-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.bct-empty-state h4 {
    color: #1D3557;
    margin-bottom: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .bct-modal {
        width: 95%;
        margin: 10px;
        max-height: 95vh;
    }

    .bct-modal-header,
    .bct-modal-body,
    .bct-modal-footer {
        padding: 20px;
    }

    .bct-form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .bct-strategy-selection {
        grid-template-columns: 1fr;
    }

    .bct-favorite-casinos {
        grid-template-columns: 1fr;
    }

    .bct-preview-header {
        flex-direction: column;
        gap: 10px;
    }

    .bct-casino-stats {
        flex-direction: column;
        gap: 5px;
    }

    .bct-mood-chips {
        justify-content: center;
    }
}
</style>

<script>
// Enhanced BCTracker for Log Session functionality
(function($) {
    'use strict';

    window.BCTracker = {
        selectedCasino: null,
        selectedRating: 0,
        selectedMood: '',
        selectedStrategy: '',
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Location filter clicks
            $(document).off('click.bct').on('click.bct', '.bct-location-chip', function() {
                $('.bct-location-chip').removeClass('active');
                $(this).addClass('active');
                BCTracker.filterCasinos();
            });
            
            // Rating stars
            $(document).off('click.bct').on('click.bct', '.bct-star', function() {
                const rating = parseInt($(this).data('rating'));
                BCTracker.selectedRating = rating;
                $('.bct-star').removeClass('active');
                for(let i = 1; i <= rating; i++) {
                    $(`.bct-star[data-rating="${i}"]`).addClass('active');
                }
                BCTracker.validateForm();
            });
            
            // Mood chips
            $(document).off('click.bct').on('click.bct', '.bct-mood-chip', function() {
                $('.bct-mood-chip').removeClass('active');
                $(this).addClass('active');
                BCTracker.selectedMood = $(this).data('mood');
                BCTracker.validateForm();
            });
            
            // Strategy selection
            $(document).off('click.bct').on('click.bct', '.bct-strategy-option', function() {
                $('.bct-strategy-option').removeClass('selected');
                $(this).addClass('selected');
                BCTracker.selectedStrategy = $(this).data('strategy');
                BCTracker.validateForm();
            });
            
            // Form validation on input
            $(document).off('input.bct').on('input.bct', '#bct-starting-bankroll, #bct-ending-bankroll', function() {
                BCTracker.validateForm();
            });
            
            // Close modal on escape or overlay click
            $(document).off('keydown.bct').on('keydown.bct', function(e) {
                if (e.key === 'Escape') BCTracker.closeModal();
            });
            
            $('#bct-log-modal').off('click.bct').on('click.bct', function(e) {
                if (e.target === this) BCTracker.closeModal();
            });
        },
        
        openModal: function() {
            $('#bct-log-modal').addClass('active');
            $('body').css('overflow', 'hidden');
        },
        
        closeModal: function() {
            $('#bct-log-modal').removeClass('active');
            $('body').css('overflow', '');
            this.resetModal();
        },
        
        resetModal: function() {
            this.selectedCasino = null;
            this.selectedRating = 0;
            this.selectedMood = '';
            this.selectedStrategy = '';
            
            $('#bct-starting-bankroll, #bct-ending-bankroll, #bct-hours, #bct-minutes, #bct-notes').val('');
            $('#bct-log-session-btn').prop('disabled', true);
            $('.bct-casino-item, .bct-star, .bct-mood-chip, .bct-strategy-option').removeClass('selected active');
            $('#bct-casino-preview').hide();
        },
        
        selectCasino: function(element, casinoData) {
            $('.bct-casino-item').removeClass('selected');
            $(element).addClass('selected');
            
            this.selectedCasino = {
                id: $(element).data('casino-id'),
                name: casinoData.name,
                location: casinoData.location,
                data: casinoData
            };
            
            this.showCasinoPreview(casinoData);
            this.validateForm();
        },
        
        showCasinoPreview: function(casino) {
            $('#bct-casino-name').text(casino.name);
            $('#bct-casino-location-preview').text(casino.location);
            
            // Set placeholder image
            $('#bct-casino-image').attr('src', 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="60" viewBox="0 0 80 60"><rect width="80" height="60" fill="%23f8f9fa"/><text x="40" y="35" text-anchor="middle" fill="%236c757d" font-size="12">🏢</text></svg>');
            
            // Build bubble craps details
            let details = [];
            if (casino.has_bubble) {
                if (casino.bubble_types && casino.bubble_types.length > 0) {
                    details.push(`${casino.bubble_types.length} Bubble Craps machine(s)`);
                } else {
                    details.push('Bubble Craps available');
                }
            }
            if (casino.has_tables) {
                details.push('Traditional tables available');
            }
            
            $('#bct-bubble-details').html(details.join('<br>'));
            $('#bct-casino-preview').show();
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
        
        validateForm: function() {
            const startingBankroll = parseFloat($('#bct-starting-bankroll').val());
            const endingBankroll = parseFloat($('#bct-ending-bankroll').val());
            const hasCasino = this.selectedCasino !== null;
            const hasValidBankrolls = startingBankroll > 0 && endingBankroll >= 0;
            
            $('#bct-log-session-btn').prop('disabled', !(hasCasino && hasValidBankrolls));
        },
        
        submitSession: function() {
            const startingBankroll = parseFloat($('#bct-starting-bankroll').val());
            const endingBankroll = parseFloat($('#bct-ending-bankroll').val());
            const hours = parseInt($('#bct-hours').val()) || 0;
            const minutes = parseInt($('#bct-minutes').val()) || 0;
            const notes = $('#bct-notes').val();
            
            if (!this.selectedCasino || !startingBankroll || endingBankroll < 0) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Create and submit form
            const form = $('<form method="post">')
                .append($('<input type="hidden" name="log_session">').val('1'))
                .append($('<input type="hidden" name="casino_id">').val(this.selectedCasino.id))
                .append($('<input type="hidden" name="starting_bankroll">').val(startingBankroll))
                .append($('<input type="hidden" name="ending_bankroll">').val(endingBankroll))
                .append($('<input type="hidden" name="duration_hours">').val(hours))
                .append($('<input type="hidden" name="duration_minutes">').val(minutes))
                .append($('<input type="hidden" name="table_rating">').val(this.selectedRating))
                .append($('<input type="hidden" name="table_mood">').val(this.selectedMood))
                .append($('<input type="hidden" name="strategy_used">').val(this.selectedStrategy))
                .append($('<input type="hidden" name="notes">').val(notes))
                .appendTo('body');
                
            form.submit();
        }
    };
    
    $(document).ready(() => {
        BCTracker.init();
    });
    
})(jQuery);
</script>