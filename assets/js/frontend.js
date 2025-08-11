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
            // Use event delegation to avoid double binding
            $(document).off('click.bct').on('click.bct', '#bct-start-session', this.startSession.bind(this));
            $(document).off('click.bct').on('click.bct', '#bct-end-session', this.endSession.bind(this));
            $(document).off('submit.bct').on('submit.bct', '#bct-log-bet-form', this.logBet.bind(this));
            $(document).off('click.bct').on('click.bct', '.bct-nav-tab', this.switchTab.bind(this));
        },
        
        startSession: function(e) {
            e.preventDefault();
            
            const startingBankroll = parseFloat($('#bct-starting-bankroll').val());
            
            if (!startingBankroll || startingBankroll <= 0) {
                alert('Please enter a valid starting bankroll amount.');
                return;
            }
            
            // Simple form submission
            $('<form method="post">')
                .append($('<input type="hidden" name="start_session" value="1">'))
                .append($('<input type="hidden" name="starting_bankroll">').val(startingBankroll))
                .appendTo('body')
                .submit();
        },
        
        endSession: function(e) {
            e.preventDefault();
            
            // Single prompt for ending bankroll
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
            console.log('Loading user data...');
        },
        
        checkActiveSession: function() {
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
