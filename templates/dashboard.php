<?php
/**
 * MINIMAL TEST Dashboard - Just Modal + Button
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
?>

<!-- Enhanced Casino Selection Modal -->
<div id="bct-casino-modal" class="bct-modal-overlay">
    <div class="bct-modal">
        <div class="bct-modal-header">
            <h2 class="bct-modal-title">Log Your Session</h2>
            <button class="bct-modal-close" onclick="BCTracker.closeModal()">&times;</button>
        </div>
        
        <div class="bct-modal-body">
            <!-- Step 1: Select Casino -->
            <div class="bct-step">
                <div class="bct-step-title">
                    <span class="bct-step-number">1</span>
                    Where did you play?
                </div>
                
                <div class="bct-casino-search">
                    <input type="text" 
                           id="bct-casino-search" 
                           class="bct-search-input" 
                           placeholder="Search casinos..."
                           onkeyup="BCTracker.filterCasinos()">
                </div>
                
                <div class="bct-casino-grid">
                    <?php 
                    $casinos = bct_get_casinos_for_session();
                    if (!empty($casinos)): 
                        foreach ($casinos as $casino): 
                            // Get bubble craps details
                            $bubble_types = get_post_meta($casino['id'], '_custom-checkbox', true);
                            $min_bet = get_post_meta($casino['id'], '_custom-radio-3', true);
                            ?>
                            <div class="bct-casino-card" 
                                 data-casino-id="<?php echo $casino['id']; ?>"
                                 onclick="BCTracker.selectCasino(this, <?php echo htmlspecialchars(json_encode($casino)); ?>)">
                                
                                <div class="bct-casino-image">
                                    <img src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='80' viewBox='0 0 100 80'><rect width='100' height='80' fill='%23f8f9fa'/><text x='50' y='45' text-anchor='middle' fill='%236c757d' font-size='24'>🏢</text></svg>" alt="<?php echo esc_attr($casino['name']); ?>">
                                    
                                    <?php if ($casino['has_bubble']): ?>
                                        <div class="bct-casino-badge">
                                            <?php if (is_array($bubble_types) && count($bubble_types) > 1): ?>
                                                <span class="bct-badge-bubble"><?php echo count($bubble_types); ?> Machines</span>
                                            <?php else: ?>
                                                <span class="bct-badge-bubble">Bubble Craps</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="bct-casino-content">
                                    <h4 class="bct-casino-name"><?php echo esc_html($casino['name']); ?></h4>
                                    <p class="bct-casino-location"><?php echo esc_html($casino['location']); ?></p>
                                    
                                    <div class="bct-casino-details">
                                        <?php if ($min_bet && $min_bet !== 'N/A or Unknown'): ?>
                                            <span class="bct-detail-tag">Min: <?php echo esc_html($min_bet); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($casino['has_tables']): ?>
                                            <span class="bct-detail-tag">Tables</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; 
                    else: ?>
                        <div class="bct-no-casinos">
                            <p>No casinos found. <a href="https://www.bubble-craps.com/all-listings/add-listing/" target="_blank">Add a casino</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 2: Session Details -->
            <div class="bct-step">
                <div class="bct-step-title">
                    <span class="bct-step-number">2</span>
                    How did your session go?
                </div>
                
                <div class="bct-form-grid">
                    <div class="bct-form-group">
                        <label class="bct-form-label">Starting Bankroll ($)</label>
                        <input type="number" id="bct-starting-bankroll" class="bct-form-input" 
                               placeholder="100.00" min="1" step="0.01" required>
                    </div>
                    <div class="bct-form-group">
                        <label class="bct-form-label">Ending Bankroll ($)</label>
                        <input type="number" id="bct-ending-bankroll" class="bct-form-input" 
                               placeholder="150.00" min="0" step="0.01" required>
                    </div>
                </div>
                
                <div class="bct-form-group">
                    <label class="bct-form-label">Session Notes (optional)</label>
                    <textarea id="bct-notes" class="bct-form-textarea" 
                              placeholder="How did the session go? Any memorable moments?"></textarea>
                </div>
            </div>
        </div>
        
        <div class="bct-log-session-card">
    <div class="bct-card-header">
        <h2>Log Your Latest Session</h2>
        <p>Record your bubble craps session and track your progress</p>
    </div>
    <button id="bct-open-modal" class="bct-btn bct-btn-primary bct-btn-large" onclick="BCTracker.openModal()">
        📝 Log Session
    </button>
</div>
    </div>
</div>

<style>
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
    width: 500px;
    padding: 20px;
}

.bct-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.bct-modal-header button {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}


/* Modern Dashboard CSS - Matching Bubble-Craps.com Design */

/* Base Styles */
.bct-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    line-height: 1.6;
}

/* Header Card */
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

/* Log Session Card */
.bct-log-session-card {
    background: linear-gradient(135deg, #C51F1F 0%, #a01919 100%);
    color: white;
    padding: 40px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(197, 31, 31, 0.2);
}

.bct-card-header h2 {
    margin: 0 0 10px 0;
    font-size: 1.8rem;
    font-weight: 700;
}

.bct-card-header p {
    margin: 0 0 25px 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Buttons */
.bct-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    font-family: inherit;
}

.bct-btn-primary {
    background: #C51F1F;
    color: white;
}

.bct-btn-primary:hover {
    background: #a01919;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(197, 31, 31, 0.3);
}

.bct-btn-secondary {
    background: #6c757d;
    color: white;
}

.bct-btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.bct-btn-large {
    padding: 18px 36px;
    font-size: 1.2rem;
}

.bct-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* Modal Styles */
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
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(-30px);
    transition: transform 0.3s ease;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.bct-modal-overlay.active .bct-modal {
    transform: translateY(0);
}

.bct-modal-header {
    background: linear-gradient(135deg, #1D3557 0%, #2a4a6b 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 8px 8px 0 0;
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

.bct-modal-footer {
    padding: 25px 30px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

/* Steps */
.bct-step {
    margin-bottom: 35px;
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

/* Casino Search */
.bct-casino-search {
    margin-bottom: 25px;
}

.bct-search-input {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.bct-search-input:focus {
    outline: none;
    border-color: #C51F1F;
    box-shadow: 0 0 0 3px rgba(197, 31, 31, 0.1);
}

/* Casino Grid */
.bct-casino-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px 0;
}

.bct-casino-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.bct-casino-card:hover {
    border-color: #C51F1F;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.bct-casino-card.selected {
    border-color: #C51F1F;
    background: #fff3cd;
    box-shadow: 0 6px 20px rgba(197, 31, 31, 0.2);
}

.bct-casino-image {
    position: relative;
    height: 120px;
    overflow: hidden;
}

.bct-casino-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bct-casino-badge {
    position: absolute;
    top: 10px;
    right: 10px;
}

.bct-badge-bubble {
    background: #C51F1F;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.bct-casino-content {
    padding: 20px;
}

.bct-casino-name {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1D3557;
}

.bct-casino-location {
    margin: 0 0 12px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.bct-casino-details {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.bct-detail-tag {
    background: #e9ecef;
    color: #495057;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
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

.bct-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1D3557;
    font-size: 0.95rem;
}

.bct-form-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.bct-form-input:focus {
    outline: none;
    border-color: #C51F1F;
    box-shadow: 0 0 0 3px rgba(197, 31, 31, 0.1);
}

.bct-form-textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    min-height: 100px;
    resize: vertical;
    box-sizing: border-box;
    font-family: inherit;
    transition: border-color 0.3s ease;
}

.bct-form-textarea:focus {
    outline: none;
    border-color: #C51F1F;
    box-shadow: 0 0 0 3px rgba(197, 31, 31, 0.1);
}

/* Stats Grid */
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
    transition: transform 0.3s ease;
}

.bct-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.bct-stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    color: #1D3557;
    margin: 10px 0;
}

.bct-stat-label {
    color: #6c757d;
    font-weight: 600;
    font-size: 0.95rem;
}

.bct-stat-positive {
    color: #28a745 !important;
}

.bct-stat-negative {
    color: #C51F1F !important;
}

/* Content Cards */
.bct-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.bct-card-header {
    background: #1D3557;
    color: white;
    padding: 20px 30px;
    border-bottom: none;
}

.bct-card-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
}

.bct-card-content {
    padding: 30px;
}

/* Empty States */
.bct-no-casinos {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    grid-column: 1 / -1;
}

.bct-no-casinos p {
    margin: 0;
    font-size: 1.1rem;
}

.bct-no-casinos a {
    color: #C51F1F;
    text-decoration: none;
    font-weight: 600;
}

.bct-no-casinos a:hover {
    text-decoration: underline;
}

/* Success Messages */
.bct-success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #c3e6cb;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .bct-container {
        padding: 15px;
    }
    
    .bct-header,
    .bct-log-session-card {
        padding: 25px 20px;
    }
    
    .bct-header h1 {
        font-size: 1.8rem;
    }
    
    .bct-modal {
        width: 95%;
        margin: 20px 10px;
        max-height: 90vh;
    }
    
    .bct-modal-header,
    .bct-modal-body,
    .bct-modal-footer {
        padding: 20px;
    }
    
    .bct-casino-grid {
        grid-template-columns: 1fr;
        max-height: 300px;
    }
    
    .bct-form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .bct-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .bct-modal-footer {
        flex-direction: column;
    }
    
    .bct-btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .bct-header h1 {
        font-size: 1.5rem;
    }
    
    .bct-card-header h2 {
        font-size: 1.5rem;
    }
    
    .bct-stat-number {
        font-size: 1.8rem;
    }
    
    .bct-casino-card {
        min-width: 100%;
    }
}
</style>

<script>
// MINIMAL BCTracker for testing
window.BCTracker = {
    openModal: function() {
        console.log('openModal called');
        const modal = document.getElementById('bct-casino-modal');
        if (modal) {
            modal.classList.add('active');
            console.log('Modal opened');
        } else {
            console.log('Modal not found!');
        }
    },
    
    closeModal: function() {
        console.log('closeModal called');
        const modal = document.getElementById('bct-casino-modal');
        if (modal) {
            modal.classList.remove('active');
            console.log('Modal closed');
        }
    }
};

console.log('BCTracker loaded:', window.BCTracker);
</script>

