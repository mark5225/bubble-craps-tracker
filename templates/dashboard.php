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

<!-- SIMPLE MODAL FOR TESTING -->
<div id="bct-casino-modal" class="bct-modal-overlay">
    <div class="bct-modal">
        <div class="bct-modal-header">
            <h2>Test Modal</h2>
            <button onclick="BCTracker.closeModal()">&times;</button>
        </div>
        <div class="bct-modal-body">
            <p>Modal is working!</p>
        </div>
    </div>
</div>

<div class="bct-container">
    <h1>Welcome back, <?php echo $current_user->display_name; ?>!</h1>
    
    <!-- SIMPLE BUTTON FOR TESTING -->
    <button id="bct-open-modal" onclick="BCTracker.openModal()" style="background: #C51F1F; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 1.2rem; cursor: pointer;">
        Log Session (TEST)
    </button>
    
    <p>If you can see this button and it opens the modal, then we know the basic functionality works.</p>
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