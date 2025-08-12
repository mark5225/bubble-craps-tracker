/**
 * Bubble Craps Session Tracker Frontend JavaScript
 */

(function($) {
    'use strict';

    // Main application object
    const BCTracker = {
        activeSession: null,
        charts: {},
        selectedCasino: null,
        selectedRating: 0,
        selectedMood: '',
        selectedStrategy: '',
        
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
            
            $('#bct-casino-modal').off('click.bct').on('click.bct', function(e) {
                if (e.target === this) BCTracker.closeModal();
            });
        },
        
        // MODAL FUNCTIONALITY
        openModal: function() {
            console.log('openModal called');
            $('#bct-casino-modal').addClass('active');
            $('body').css('overflow', 'hidden');
        },
        
        closeModal: function() {
            console.log('closeModal called');
            $('#bct-casino-modal').removeClass('active');
            $('body').css('overflow', '');
            this.resetModal();
        },
        
        resetModal: function() {
            this.selectedCasino = null;
            this.selectedRating = 0;
            this.selectedMood = '';
            this.selectedStrategy = '';
            
            $('#bct-starting-bankroll, #bct-ending-bankroll, #bct-hours, #bct-minutes, #bct-notes').val('');
            $('#bct-log-session-btn, #bct-start-session-btn').prop('disabled', true);
            $('.bct-casino-item, .bct-star, .bct-mood-chip, .bct-strategy-option').removeClass('selected active');
            $('#bct-casino-preview').hide();
        },
        
        selectCasino: function(element, casinoData) {
            $('.bct-casino-item').removeClass('selected');
            $(element).addClass('selected');
            
            this.selectedCasino = {
                id: $(element).data('casino-id'),
                name: casinoData ? casinoData.name : $(element).find('h4').text(),
                location: casinoData ? casinoData.location : $(element).find('.bct-casino-location').text(),
                data: casinoData
            };
            
            if (casinoData) {
                this.showCasinoPreview(casinoData);
            }
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
            
            // For log session modal
            if ($('#bct-log-session-btn').length) {
                const hasValidBankrolls = startingBankroll > 0 && endingBankroll >= 0;
                $('#bct-log-session-btn').prop('disabled', !(hasCasino && hasValidBankrolls));
            }
            
            // For start session modal
            if ($('#bct-start-session-btn').length) {
                const hasValidBankroll = startingBankroll > 0;
                $('#bct-start-session-btn').prop('disabled', !(hasCasino && hasValidBankroll));
            }
        },
        
        handleBankrollKeypress: function(event) {
            if (event.key === 'Enter') {
                if ($('#bct-log-session-btn').length) {
                    this.submitSession();
                } else {
                    this.startSessionFromModal();
                }
            } else {
                setTimeout(() => this.validateForm(), 100);
            }
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
        },
        
        // LEGACY FUNCTIONALITY (keep for compatibility)
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