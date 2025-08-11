/**
 * Bubble Craps Session Tracker Frontend JavaScript
 */

(function($) {
    'use strict';

    // Main application object
    const BCTracker = {
        activeSession: null,
        charts: {},
        
        init: function() {
            this.bindEvents();
            this.loadUserData();
            this.checkActiveSession();
        },
        
        bindEvents: function() {
            // Session controls - only bind if elements exist
            $(document).on('click', '#bct-start-session', this.startSession.bind(this));
            $(document).on('click', '#bct-end-session', this.endSession.bind(this));
            $(document).on('submit', '#bct-log-bet-form', this.logBet.bind(this));
            
            // Navigation tabs - only if they exist
            $(document).on('click', '.bct-nav-tab', this.switchTab.bind(this));
        },
        
        startSession: function(e) {
            e.preventDefault();
            
            const startingBankroll = parseFloat($('#bct-starting-bankroll').val());
            
            if (!startingBankroll || startingBankroll <= 0) {
                alert('Please enter a valid starting bankroll amount.');
                return;
            }
            
            // Simple form submission for now
            $('<form method="post">')
                .append($('<input type="hidden" name="start_session" value="1">'))
                .append($('<input type="hidden" name="starting_bankroll">').val(startingBankroll))
                .appendTo('body')
                .submit();
        },
        
        endSession: function(e) {
            e.preventDefault();
            
            // Simple prompt for ending bankroll
            const endingBankroll = prompt('Enter your ending bankroll ($):');
            
            if (endingBankroll === null) {
                return; // User cancelled
            }
            
            const amount = parseFloat(endingBankroll);
            if (isNaN(amount) || amount < 0) {
                alert('Please enter a valid amount.');
                return;
            }
            
            const notes = prompt('Session notes (optional):') || '';
            
            // Submit form to end session
            $('<form method="post">')
                .append($('<input type="hidden" name="end_session" value="1">'))
                .append($('<input type="hidden" name="ending_bankroll">').val(amount))
                .append($('<input type="hidden" name="notes">').val(notes))
                .appendTo('body')
                .submit();
        },
        
        logBet: function(e) {
            e.preventDefault();
            alert('Bet logging functionality coming soon!');
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            const $tab = $(e.currentTarget);
            const target = $tab.data('target');
            
            if (!target) return;
            
            // Update active tab
            $('.bct-nav-tab').removeClass('active');
            $tab.addClass('active');
            
            // Show target content
            $('.bct-tab-content').addClass('bct-hidden');
            $(target).removeClass('bct-hidden');
        },
        
        loadUserData: function() {
            // Basic data loading - will expand later
            console.log('Loading user data...');
        },
        
        checkActiveSession: function() {
            // Check for active session - will expand later
            console.log('Checking active session...');
        }
    };
    
    // Initialize when document is ready
    $(document).ready(() => {
        BCTracker.init();
    });
    
    // Make BCTracker globally available
    window.BCTracker = BCTracker;
    
})(jQuery);
