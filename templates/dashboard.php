<?php
/**
 * Dashboard Template - Fixed Version
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_session'])) {
        global $wpdb;
        
        $casino_id = isset($_POST['casino_id']) ? intval($_POST['casino_id']) : null;
        $starting_bankroll = floatval($_POST['starting_bankroll']);
        
        if ($starting_bankroll > 0) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'craps_sessions',
                array(
                    'user_id' => $user_id,
                    'casino_id' => $casino_id,
                    'starting_bankroll' => $starting_bankroll,
                    'session_status' => 'active'
                ),
                array('%d', '%d', '%f', '%s')
            );
            
            if ($result) {
                echo '<div class="bct-success-message">✅ Session started successfully! Starting bankroll: $' . number_format($starting_bankroll, 2) . '</div>';
            }
        }
    }
    
    if (isset($_POST['log_session'])) {
        global $wpdb;
        
        $casino_id = isset($_POST['casino_id']) ? intval($_POST['casino_id']) : null;
        $starting_bankroll = floatval($_POST['starting_bankroll']);
        $ending_bankroll = floatval($_POST['ending_bankroll']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if ($starting_bankroll > 0 && $ending_bankroll >= 0) {
            $net_result = $ending_bankroll - $starting_bankroll;
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'craps_sessions',
                array(
                    'user_id' => $user_id,
                    'casino_id' => $casino_id,
                    'starting_bankroll' => $starting_bankroll,
                    'ending_bankroll' => $ending_bankroll,
                    'net_result' => $net_result,
                    'session_status' => 'completed',
                    'session_end' => current_time('mysql'),
                    'notes' => $notes
                ),
                array('%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s')
            );
            
            if ($result) {
                $result_color = $net_result >= 0 ? '#28a745' : '#C51F1F';
                $result_text = ($net_result >= 0 ? '+' : '') . '$' . number_format($net_result, 2);
                echo '<div class="bct-success-message">✅ Session logged! Result: <span style="color: ' . $result_color . '; font-weight: bold;">' . $result_text . '</span></div>';
            }
        }
    }
}

// Get user stats
global $wpdb;
$stats = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        COUNT(CASE WHEN session_status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN session_status = 'active' THEN 1 END) as active_sessions,
        SUM(CASE WHEN session_status = 'completed' THEN net_result ELSE 0 END) as total_net_result,
        AVG(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as avg_session_result,
        MAX(CASE WHEN session_status = 'completed' THEN net_result ELSE NULL END) as best_session
    FROM {$wpdb->prefix}craps_sessions 
    WHERE user_id = %d
", $user_id));

// Get recent sessions
$recent_sessions = $wpdb->get_results($wpdb->prepare("
    SELECT s.*, p.post_title as casino_name
    FROM {$wpdb->prefix}craps_sessions s
    LEFT JOIN {$wpdb->posts} p ON s.casino_id = p.ID
    WHERE s.user_id = %d 
    ORDER BY s.session_start DESC 
    LIMIT 5
", $user_id));
?>

<style>
/* Dashboard CSS */
.bct-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.bct-header {
    background: linear-gradient(135deg, #1D3557 0%, #2a4a6b 100%);
    color: white;
    padding: 40px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(29, 53, 87, 0.2);
}

.bct-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.2rem;
    font-weight: 700;
}

.bct-header p {
    margin: 0;
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

.bct-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.bct-stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-top: 4px solid #C51F1F;
}

.bct-stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    color: #1D3557;
    margin: 10px 0;
}

.bct-stat-positive { color: #28a745 !important; }
.bct-stat-negative { color: #C51F1F !important; }

.bct-stat-label {
    color: #6c757d;
    font-weight: 600;
    font-size: 0.95rem;
}

.bct-action-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.bct-action-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.bct-card-header {
    padding: 25px;
    text-align: center;
}

.bct-card-header.primary {
    background: linear-gradient(135deg, #C51F1F 0%, #a01919 100%);
    color: white;
}

.bct-card-header.secondary {
    background: linear-gradient(135deg, #1D3557 0%, #2a4a6b 100%);
    color: white;
}

.bct-card-header h3 {
    margin: 0 0 10px 0;
    font-size: 1.4rem;
    font-weight: 700;
}

.bct-card-header p {
    margin: 0;
    opacity: 0.9;
}

.bct-btn {
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    margin-top: 15px;
}

.bct-btn-primary {
    background: #C51F1F;
    color: white;
}

.bct-btn-primary:hover {
    background: #a01919;
    transform: translateY(-2px);
}

.bct-btn-secondary {
    background: #6c757d;
    color: white;
}

.bct-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.bct-card-title-header {
    background: #1D3557;
    color: white;
    padding: 20px 30px;
}

.bct-card-title-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
}

.bct-card-content {
    padding: 30px;
}

.bct-sessions-table {
    width: 100%;
    border-collapse: collapse;
}

.bct-sessions-table th,
.bct-sessions-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.bct-sessions-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #1D3557;
}

.bct-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.bct-status-active {
    background: #d4edda;
    color: #155724;
}

.bct-status-completed {
    background: #e2e3e5;
    color: #383d41;
}

.bct-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
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
    border-radius: 8px;
    width: 95%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.bct-modal-header {
    background: #1D3557;
    color: white;
    padding: 25px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bct-modal-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
}

.bct-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 5px;
}

.bct-modal-body {
    padding: 30px;
}

.bct-form-group {
    margin-bottom: 20px;
}

.bct-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1D3557;
}

.bct-form-input,
.bct-form-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
}

.bct-form-input:focus,
.bct-form-textarea:focus {
    outline: none;
    border-color: #C51F1F;
}

.bct-form-textarea {
    min-height: 100px;
    resize: vertical;
}

.bct-casino-select {
    margin-bottom: 25px;
}

.bct-casino-list {
    max-height: 200px;
    overflow-y: auto;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
}

.bct-casino-option {
    padding: 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 5px;
    transition: background 0.2s ease;
}

.bct-casino-option:hover {
    background: #f8f9fa;
}

.bct-casino-option.selected {
    background: #C51F1F;
    color: white;
}

@media (max-width: 768px) {
    .bct-container { padding: 15px; }
    .bct-header { padding: 25px 20px; }
    .bct-action-cards { grid-template-columns: 1fr; }
    .bct-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="bct-container">
    <!-- Header -->
    <div class="bct-header">
        <h1>Welcome back, <?php echo esc_html($current_user->display_name); ?>!</h1>
        <p>Track your craps sessions and analyze your performance.</p>
    </div>

    <!-- Stats Grid -->
    <div class="bct-stats-grid">
        <div class="bct-stat-card">
            <div class="bct-stat-label">Total Sessions</div>
            <div class="bct-stat-number"><?php echo $stats->total_sessions ?: 0; ?></div>
        </div>
        <div class="bct-stat-card">
            <div class="bct-stat-label">Active Sessions</div>
            <div class="bct-stat-number"><?php echo $stats->active_sessions ?: 0; ?></div>
        </div>
        <div class="bct-stat-card">
            <div class="bct-stat-label">Net Winnings</div>
            <div class="bct-stat-number <?php echo ($stats->total_net_result >= 0) ? 'bct-stat-positive' : 'bct-stat-negative'; ?>">
                <?php echo ($stats->total_net_result >= 0 ? '+' : '') . '$' . number_format($stats->total_net_result ?: 0, 2); ?>
            </div>
        </div>
        <div class="bct-stat-card">
            <div class="bct-stat-label">Best Session</div>
            <div class="bct-stat-number bct-stat-positive">
                <?php echo $stats->best_session ? '+$' . number_format($stats->best_session, 2) : '$0.00'; ?>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="bct-action-cards">
        <div class="bct-action-card">
            <div class="bct-card-header primary">
                <h3>Log Past Session</h3>
                <p>Record a session you already played</p>
                <button type="button" class="bct-btn bct-btn-primary" onclick="openLogModal()">
                    📝 Log Session
                </button>
            </div>
        </div>
        
        <div class="bct-action-card">
            <div class="bct-card-header secondary">
                <h3>Start Live Session</h3>
                <p>Begin tracking a new session</p>
                <button type="button" class="bct-btn bct-btn-secondary" onclick="openStartModal()">
                    🎲 Start Session
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Sessions -->
    <div class="bct-card">
        <div class="bct-card-title-header">
            <h3>Recent Sessions</h3>
        </div>
        <div class="bct-card-content">
            <?php if ($recent_sessions): ?>
                <table class="bct-sessions-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Casino</th>
                            <th>Starting</th>
                            <th>Result</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_sessions as $session): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($session->session_start)); ?></td>
                            <td><?php echo $session->casino_name ?: 'Not specified'; ?></td>
                            <td>$<?php echo number_format($session->starting_bankroll, 2); ?></td>
                            <td>
                                <?php if ($session->session_status === 'completed' && $session->net_result !== null): ?>
                                    <span class="<?php echo $session->net_result >= 0 ? 'bct-stat-positive' : 'bct-stat-negative'; ?>">
                                        <?php echo ($session->net_result >= 0 ? '+' : '') . '$' . number_format($session->net_result, 2); ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="bct-status-badge bct-status-<?php echo $session->session_status; ?>">
                                    <?php echo ucfirst($session->session_status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No sessions yet. Start tracking your craps games above!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Log Session Modal -->
<div id="log-session-modal" class="bct-modal-overlay">
    <div class="bct-modal">
        <div class="bct-modal-header">
            <h3 class="bct-modal-title">Log Past Session</h3>
            <button class="bct-modal-close" onclick="closeLogModal()">&times;</button>
        </div>
        <div class="bct-modal-body">
            <form method="post">
                <input type="hidden" name="log_session" value="1">
                
                <div class="bct-casino-select">
                    <label class="bct-form-label">Casino (optional)</label>
                    <div class="bct-casino-list">
                        <div class="bct-casino-option" data-casino-id="" onclick="selectCasino(this, '')">
                            <strong>Not specified</strong>
                        </div>
                        <?php 
                        $casinos = bct_get_casinos_for_session();
                        foreach ($casinos as $casino): ?>
                            <div class="bct-casino-option" data-casino-id="<?php echo $casino['id']; ?>" 
                                 onclick="selectCasino(this, '<?php echo $casino['id']; ?>')">
                                <strong><?php echo esc_html($casino['name']); ?></strong><br>
                                <small><?php echo esc_html($casino['location']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="casino_id" id="selected-casino-id">
                </div>
                
                <div class="bct-form-group">
                    <label class="bct-form-label" for="starting_bankroll">Starting Bankroll ($) *</label>
                    <input type="number" id="starting_bankroll" name="starting_bankroll" 
                           class="bct-form-input" placeholder="100.00" min="1" step="0.01" required>
                </div>
                
                <div class="bct-form-group">
                    <label class="bct-form-label" for="ending_bankroll">Ending Bankroll ($) *</label>
                    <input type="number" id="ending_bankroll" name="ending_bankroll" 
                           class="bct-form-input" placeholder="150.00" min="0" step="0.01" required>
                </div>
                
                <div class="bct-form-group">
                    <label class="bct-form-label" for="notes">Session Notes</label>
                    <textarea id="notes" name="notes" class="bct-form-textarea" 
                              placeholder="How did the session go? Any memorable moments?"></textarea>
                </div>
                
                <button type="submit" class="bct-btn bct-btn-primary" style="width: 100%;">
                    Save Session
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Start Session Modal -->
<div id="start-session-modal" class="bct-modal-overlay">
    <div class="bct-modal">
        <div class="bct-modal-header">
            <h3 class="bct-modal-title">Start New Session</h3>
            <button class="bct-modal-close" onclick="closeStartModal()">&times;</button>
        </div>
        <div class="bct-modal-body">
            <form method="post">
                <input type="hidden" name="start_session" value="1">
                
                <div class="bct-casino-select">
                    <label class="bct-form-label">Casino (optional)</label>
                    <div class="bct-casino-list">
                        <div class="bct-casino-option" data-casino-id="" onclick="selectStartCasino(this, '')">
                            <strong>Not specified</strong>
                        </div>
                        <?php foreach ($casinos as $casino): ?>
                            <div class="bct-casino-option" data-casino-id="<?php echo $casino['id']; ?>" 
                                 onclick="selectStartCasino(this, '<?php echo $casino['id']; ?>')">
                                <strong><?php echo esc_html($casino['name']); ?></strong><br>
                                <small><?php echo esc_html($casino['location']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="casino_id" id="start-selected-casino-id">
                </div>
                
                <div class="bct-form-group">
                    <label class="bct-form-label" for="start_bankroll">Starting Bankroll ($) *</label>
                    <input type="number" id="start_bankroll" name="starting_bankroll" 
                           class="bct-form-input" placeholder="100.00" min="1" step="0.01" required>
                </div>
                
                <button type="submit" class="bct-btn bct-btn-primary" style="width: 100%;">
                    Start Session
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openLogModal() {
    document.getElementById('log-session-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLogModal() {
    document.getElementById('log-session-modal').classList.remove('active');
    document.body.style.overflow = '';
}

function openStartModal() {
    document.getElementById('start-session-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeStartModal() {
    document.getElementById('start-session-modal').classList.remove('active');
    document.body.style.overflow = '';
}

function selectCasino(element, casinoId) {
    // Remove selection from all options in this modal
    const modal = element.closest('.bct-modal');
    modal.querySelectorAll('.bct-casino-option').forEach(opt => opt.classList.remove('selected'));
    
    // Select this option
    element.classList.add('selected');
    document.getElementById('selected-casino-id').value = casinoId;
}

function selectStartCasino(element, casinoId) {
    // Remove selection from all options in this modal
    const modal = element.closest('.bct-modal');
    modal.querySelectorAll('.bct-casino-option').forEach(opt => opt.classList.remove('selected'));
    
    // Select this option
    element.classList.add('selected');
    document.getElementById('start-selected-casino-id').value = casinoId;
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLogModal();
        closeStartModal();
    }
});

// Close modals on overlay click
document.querySelectorAll('.bct-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeLogModal();
            closeStartModal();
        }
    });
});
</script>