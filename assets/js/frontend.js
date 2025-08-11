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
            this.initCharts();
            this.checkActiveSession();
            
            // Initialize PWA features
            if ('serviceWorker' in navigator) {
                this.initServiceWorker();
            }
        },
        
        bindEvents: function() {
            // Navigation tabs
            $('.bct-nav-tab').on('click', this.switchTab.bind(this));
            
            // Session controls
            $('#bct-start-session').on('click', this.startSession.bind(this));
            $('#bct-end-session').on('click', this.endSession.bind(this));
            $('#bct-pause-session').on('click', this.pauseSession.bind(this));
            
            // Bet logging
            $('#bct-log-bet-form').on('submit', this.logBet.bind(this));
            $('#bct-quick-bet').on('click', '.bct-quick-bet-btn', this.quickBet.bind(this));
            
            // Modals
            $('.bct-modal-close, .bct-modal-overlay').on('click', this.closeModal.bind(this));
            $('.bct-modal').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Photo upload
            $('#bct-upload-photo').on('change', this.handlePhotoUpload.bind(this));
            
            // Feedback form
            $('#bct-feedback-form').on('submit', this.submitFeedback.bind(this));
            
            // Auto-refresh active session data
            setInterval(this.updateActiveSession.bind(this), 30000);
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            const $tab = $(e.currentTarget);
            const target = $tab.data('target');
            
            // Update active tab
            $('.bct-nav-tab').removeClass('active');
            $tab.addClass('active');
            
            // Show target content
            $('.bct-tab-content').addClass('bct-hidden');
            $(target).removeClass('bct-hidden').addClass('bct-fade-in');
            
            // Load tab-specific data
            this.loadTabData(target);
        },
        
        loadTabData: function(target) {
            switch(target) {
                case '#bct-dashboard':
                    this.loadDashboard();
                    break;
                case '#bct-analytics':
                    this.loadAnalytics();
                    break;
                case '#bct-community':
                    this.loadCommunity();
                    break;
                case '#bct-achievements':
                    this.loadAchievements();
                    break;
            }
        },
        
        startSession: function(e) {
            e.preventDefault();
            
            const startingBankroll = parseFloat($('#bct-starting-bankroll').val());
            
            if (!startingBankroll || startingBankroll <= 0) {
                this.showAlert('Please enter a valid starting bankroll amount.', 'warning');
                return;
            }
            
            this.showLoading('#bct-session-controls');
            
            $.post(bct_frontend.ajax_url, {
                action: 'bct_start_session',
                nonce: bct_frontend.nonce,
                starting_bankroll: startingBankroll
            })
            .done((response) => {
                if (response.success) {
                    this.activeSession = {
                        id: response.data.session_id,
                        starting_bankroll: startingBankroll,
                        current_bankroll: startingBankroll,
                        start_time: new Date()
                    };
                    
                    this.updateSessionDisplay();
                    this.showAlert('Session started successfully!', 'success');
                } else {
                    this.showAlert(response.data || 'Failed to start session', 'error');
                }
            })
            .fail(() => {
                this.showAlert('Network error. Please try again.', 'error');
            })
            .always(() => {
                this.hideLoading('#bct-session-controls');
            });
        },
        
        endSession: function(e) {
            e.preventDefault();
            
            if (!this.activeSession) {
                this.showAlert('No active session found.', 'warning');
                return;
            }
            
            const endingBankroll = parseFloat($('#bct-ending-bankroll').val());
            const notes = $('#bct-session-notes').val();
            
            if (isNaN(endingBankroll)) {
                this.showAlert('Please enter your ending bankroll amount.', 'warning');
                return;
            }
            
            this.showLoading('#bct-session-controls');
            
            $.post(bct_frontend.ajax_url, {
                action: 'bct_end_session',
                nonce: bct_frontend.nonce,
                session_id: this.activeSession.id,
                ending_bankroll: endingBankroll,
                notes: notes
            })
            .done((response) => {
                if (response.success) {
                    const netResult = response.data.net_result;
                    this.activeSession = null;
                    
                    this.updateSessionDisplay();
                    this.showSessionSummary(netResult);
                    this.loadUserData(); // Refresh stats
                    
                    // Show achievement check
                    this.checkNewAchievements();
                } else {
                    this.showAlert(response.data || 'Failed to end session', 'error');
                }
            })
            .fail(() => {
                this.showAlert('Network error. Please try again.', 'error');
            })
            .always(() => {
                this.hideLoading('#bct-session-controls');
            });
        },
        
        logBet: function(e) {
            e.preventDefault();
            
            if (!this.activeSession) {
                this.showAlert('Please start a session first.', 'warning');
                return;
            }
            
            const formData = {
                action: 'bct_log_bet',
                nonce: bct_frontend.nonce,
                session_id: this.activeSession.id,
                bet_type: $('#bct-bet-type').val(),
                bet_amount: parseFloat($('#bct-bet-amount').val()),
                bet_result: $('#bct-bet-result').val(),
                payout: parseFloat($('#bct-payout').val()) || 0
            };
            
            if (!formData.bet_type || !formData.bet_amount || !formData.bet_result) {
                this.showAlert('Please fill in all bet details.', 'warning');
                return;
            }
            
            $.post(bct_frontend.ajax_url, formData)
            .done((response) => {
                if (response.success) {
                    // Update current bankroll
                    if (formData.bet_result === 'win') {
                        this.activeSession.current_bankroll += formData.payout - formData.bet_amount;
                    } else if (formData.bet_result === 'lose') {
                        this.activeSession.current_bankroll -= formData.bet_amount;
                    }
                    
                    this.updateSessionDisplay();
                    this.clearBetForm();
                    this.showAlert('Bet logged successfully!', 'success');
                    
                    // Add to bet history display
                    this.addBetToHistory(formData);
                } else {
                    this.showAlert(response.data || 'Failed to log bet', 'error');
                }
            })
            .fail(() => {
                this.showAlert('Network error. Please try again.', 'error');
            });
        },
        
        quickBet: function(e) {
            const $btn = $(e.currentTarget);
            const betType = $btn.data('bet-type');
            const amount = parseFloat($btn.data('amount'));
            
            $('#bct-bet-type').val(betType);
            $('#bct-bet-amount').val(amount);
        },
        
        updateSessionDisplay: function() {
            if (this.activeSession) {
                $('.bct-session-tracker').addClass('bct-session-active');
                $('#bct-current-bankroll').text('$' + this.activeSession.current_bankroll.toFixed(2));
                $('#bct-session-time').text(this.formatDuration(new Date() - this.activeSession.start_time));
                
                const netResult = this.activeSession.current_bankroll - this.activeSession.starting_bankroll;
                $('#bct-net-result').text(this.formatCurrency(netResult));
                $('#bct-net-result').removeClass('bct-stat-positive bct-stat-negative');
                $('#bct-net-result').addClass(netResult >= 0 ? 'bct-stat-positive' : 'bct-stat-negative');
                
                $('#bct-session-inactive').hide();
                $('#bct-session-active').show();
                
                // Enable bet logging
                $('#bct-bet-logger').removeClass('bct-hidden');
            } else {
                $('.bct-session-tracker').removeClass('bct-session-active');
                $('#bct-session-inactive').show();
                $('#bct-session-active').hide();
                $('#bct-bet-logger').addClass('bct-hidden');
            }
        },
        
        checkActiveSession: function() {
            $.post(bct_frontend.ajax_url, {
                action: 'bct_get_session_data',
                nonce: bct_frontend.nonce
            })
            .done((response) => {
                if (response.success && response.data.active_session) {
                    const session = response.data.active_session;
                    this.activeSession = {
                        id: session.id,
                        starting_bankroll: parseFloat(session.starting_bankroll),
                        current_bankroll: parseFloat(session.starting_bankroll), // Will be updated
                        start_time: new Date(session.session_start)
                    };
                    
                    this.updateSessionDisplay();
                }
                
                // Load recent sessions
                this.displayRecentSessions(response.data.recent_sessions || []);
            });
        },
        
        loadUserData: function() {
            $.post(bct_frontend.ajax_url, {
                action: 'bct_get_user_stats',
                nonce: bct_frontend.nonce
            })
            .done((response) => {
                if (response.success) {
                    this.displayUserStats(response.data);
                }
            });
        },
        
        displayUserStats: function(stats) {
            // Update stat cards
            $('#bct-total-sessions').text(stats.sessions.total_sessions || 0);
            $('#bct-total-winnings').text(this.formatCurrency(stats.sessions.total_net_result || 0));
            $('#bct-avg-session').text(this.formatCurrency(stats.sessions.avg_session_result || 0));
            $('#bct-best-session').text(this.formatCurrency(stats.sessions.best_session || 0));
            
            // Update betting patterns
            this.displayBettingPatterns(stats.betting_patterns || []);
            
            // Update recent activity chart
            this.updateActivityChart(stats.recent_activity || []);
        },
        
        displayBettingPatterns: function(patterns) {
            const $container = $('#bct-betting-patterns');
            $container.empty();
            
            patterns.forEach(pattern => {
                const winPercentage = parseFloat(pattern.win_percentage) || 0;
                const roi = ((parseFloat(pattern.total_winnings) - parseFloat(pattern.total_losses)) / parseFloat(pattern.total_wagered) * 100) || 0;
                
                const $pattern = $(`
                    <div class="bct-pattern-item">
                        <div class="bct-pattern-header">
                            <h4>${this.getBetDisplayName(pattern.bet_type)}</h4>
                            <span class="bct-pattern-count">${pattern.bet_count} bets</span>
                        </div>
                        <div class="bct-pattern-stats">
                            <span>Win Rate: ${winPercentage.toFixed(1)}%</span>
                            <span>ROI: ${roi.toFixed(1)}%</span>
                            <span>Total Wagered: ${this.formatCurrency(pattern.total_wagered)}</span>
                        </div>
                    </div>
                `);
                
                $container.append($pattern);
            });
        },
        
        displayRecentSessions: function(sessions) {
            const $container = $('#bct-recent-sessions');
            $container.empty();
            
            if (sessions.length === 0) {
                $container.append('<p class="bct-text-center">No completed sessions yet.</p>');
                return;
            }
            
            sessions.forEach(session => {
                const netResult = parseFloat(session.net_result);
                const date = new Date(session.session_end).toLocaleDateString();
                const duration = session.session_end ? 
                    this.formatDuration(new Date(session.session_end) - new Date(session.session_start)) : 
                    'In Progress';
                
                const $session = $(`
                    <div class="bct-session-item">
                        <div class="bct-session-meta">
                            <span class="bct-session-date">${date}</span>
                            <span class="bct-session-result ${netResult >= 0 ? 'bct-stat-positive' : 'bct-stat-negative'}">
                                ${this.formatCurrency(netResult)}
                            </span>
                        </div>
                        <div class="bct-session-details">
                            Duration: ${duration} | Wagered: ${this.formatCurrency(session.total_wagered)}
                            ${session.notes ? `<br><em>${session.notes}</em>` : ''}
                        </div>
                    </div>
                `);
                
                $container.append($session);
            });
        },
        
        initCharts: function() {
            // Initialize Chart.js charts if Chart is available
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded - charts will not be available');
                return;
            }
            
            this.initActivityChart();
            this.initBettingChart();
        },
        
        initActivityChart: function() {
            const ctx = document.getElementById('bct-activity-chart');
            if (!ctx) return;
            
            this.charts.activity = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Daily Results',
                        data: [],
                        borderColor: bct_frontend.colors.primary_red,
                        backgroundColor: bct_frontend.colors.primary_red + '20',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        },
        
        updateActivityChart: function(data) {
            if (!this.charts.activity) return;
            
            const labels = data.map(item => new Date(item.session_date).toLocaleDateString());
            const values = data.map(item => parseFloat(item.daily_result) || 0);
            
            this.charts.activity.data.labels = labels.reverse();
            this.charts.activity.data.datasets[0].data = values.reverse();
            this.charts.activity.update();
        },
        
        loadDashboard: function() {
            // Dashboard is loaded by default
        },
        
        loadAnalytics: function() {
            // Load advanced analytics
            this.showLoading('#bct-analytics-content');
            
            // This would load more detailed analytics
            setTimeout(() => {
                this.hideLoading('#bct-analytics-content');
            }, 1000);
        },
        
        loadCommunity: function() {
            // Load community features - photos, leaderboard, etc.
            this.loadWinPhotos();
            this.loadLeaderboard();
        },
        
        loadWinPhotos: function() {
            // Load featured win photos
            $.post(bct_frontend.ajax_url, {
                action: 'bct_get_win_photos',
                nonce: bct_frontend.nonce
            })
            .done((response) => {
                if (response.success) {
                    this.displayWinPhotos(response.data);
                }
            });
        },
        
        loadLeaderboard: function() {
            $.post(bct_frontend.ajax_url, {
                action: 'bct_get_leaderboard',
                nonce: bct_frontend.nonce,
                period: 'monthly'
            })
            .done((response) => {
                if (response.success) {
                    this.displayLeaderboard(response.data);
                }
            });
        },
        
        displayLeaderboard: function(players) {
            const $container = $('#bct-leaderboard-list');
            $container.empty();
            
            players.forEach((player, index) => {
                const rank = index + 1;
                const rankClass = rank === 1 ? 'gold' : rank === 2 ? 'silver' : rank === 3 ? 'bronze' : '';
                
                const $item = $(`
                    <div class="bct-leaderboard-item">
                        <div class="bct-rank ${rankClass}">${rank}</div>
                        <div class="bct-player-info">
                            <div class="bct-player-name">${player.display_name}</div>
                            <div class="bct-player-stats">
                                ${player.session_count} sessions | Avg: ${this.formatCurrency(player.avg_session)}
                            </div>
                        </div>
                        <div class="bct-player-winnings">${this.formatCurrency(player.total_winnings)}</div>
                    </div>
                `);
                
                $container.append($item);
            });
        },
        
        loadAchievements: function() {
            // Achievements are typically loaded with user stats
        },
        
        handlePhotoUpload: function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type and size
            if (!file.type.startsWith('image/')) {
                this.showAlert('Please select an image file.', 'warning');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) { // 5MB
                this.showAlert('File size must be less than 5MB.', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'bct_upload_photo');
            formData.append('nonce', bct_frontend.nonce);
            formData.append('photo', file);
            formData.append('win_amount', $('#bct-win-amount').val());
            formData.append('description', $('#bct-photo-description').val());
            
            this.showLoading('#bct-photo-upload');
            
            $.ajax({
                url: bct_frontend.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done((response) => {
                if (response.success) {
                    this.showAlert('Photo uploaded successfully! It will be reviewed before appearing publicly.', 'success');
                    $('#bct-photo-form')[0].reset();
                } else {
                    this.showAlert(response.data || 'Failed to upload photo', 'error');
                }
            })
            .fail(() => {
                this.showAlert('Network error. Please try again.', 'error');
            })
            .always(() => {
                this.hideLoading('#bct-photo-upload');
            });
        },
        
        submitFeedback: function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'bct_submit_feedback',
                nonce: bct_frontend.nonce,
                feedback_type: $('#bct-feedback-type').val(),
                title: $('#bct-feedback-title').val(),
                content: $('#bct-feedback-content').val()
            };
            
            if (!formData.title || !formData.content) {
                this.showAlert('Please fill in all fields.', 'warning');
                return;
            }
            
            $.post(bct_frontend.ajax_url, formData)
            .done((response) => {
                if (response.success) {
                    this.showAlert('Thank you for your feedback! We\'ll review it soon.', 'success');
                    $('#bct-feedback-form')[0].reset();
                    this.closeModal();
                } else {
                    this.showAlert(response.data || 'Failed to submit feedback', 'error');
                }
            })
            .fail(() => {
                this.showAlert('Network error. Please try again.', 'error');
            });
        },
        
        showSessionSummary: function(netResult) {
            const isWin = netResult > 0;
            const message = isWin ? 
                `Congratulations! You won ${this.formatCurrency(netResult)}!` :
                `Session ended with ${this.formatCurrency(netResult)}. Better luck next time!`;
            
            const modalContent = `
                <div class="bct-session-summary">
                    <div class="bct-session-result ${isWin ? 'bct-stat-positive' : 'bct-stat-negative'}">
                        ${this.formatCurrency(netResult)}
                    </div>
                    <p>${message}</p>
                    ${isWin ? '<button class="bct-btn bct-btn-primary" onclick="BCTracker.showPhotoUpload()">Share Your Win!</button>' : ''}
                </div>
            `;
            
            this.showModal('Session Complete', modalContent);
        },
        
        showPhotoUpload: function() {
            this.closeModal();
            $('#bct-photo-modal').removeClass('bct-hidden');
        },
        
        checkNewAchievements: function() {
            // Check for new achievements after session end
            $.post(bct_frontend.ajax_url, {
                action: 'bct_check_achievements',
                nonce: bct_frontend.nonce
            })
            .done((response) => {
                if (response.success && response.data.new_achievements.length > 0) {
                    this.showAchievementNotification(response.data.new_achievements);
                }
            });
        },
        
        showAchievementNotification: function(achievements) {
            achievements.forEach(achievement => {
                const notification = `
                    <div class="bct-achievement-notification bct-slide-up">
                        <div class="bct-achievement-icon">🏆</div>
                        <div class="bct-achievement-text">
                            <strong>Achievement Unlocked!</strong><br>
                            ${achievement.title}
                        </div>
                    </div>
                `;
                
                $('body').append(notification);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    $('.bct-achievement-notification').fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 5000);
            });
        },
        
        addBetToHistory: function(bet) {
            const resultClass = bet.bet_result === 'win' ? 'bct-stat-positive' : 'bct-stat-negative';
            const result = bet.bet_result === 'win' ? `+${this.formatCurrency(bet.payout)}` : `-${this.formatCurrency(bet.bet_amount)}`;
            
            const $betItem = $(`
                <div class="bct-bet-item bct-fade-in">
                    <span class="bct-bet-type">${this.getBetDisplayName(bet.bet_type)}</span>
                    <span class="bct-bet-amount">${this.formatCurrency(bet.bet_amount)}</span>
                    <span class="bct-bet-result ${resultClass}">${result}</span>
                    <span class="bct-bet-time">${new Date().toLocaleTimeString()}</span>
                </div>
            `);
            
            $('#bct-bet-history').prepend($betItem);
            
            // Keep only last 20 bets visible
            $('#bct-bet-history .bct-bet-item').slice(20).remove();
        },
        
        clearBetForm: function() {
            $('#bct-bet-amount').val('');
            $('#bct-payout').val('');
            $('#bct-bet-result').val('');
        },
        
        updateActiveSession: function() {
            if (!this.activeSession) return;
            
            // Update session duration display
            $('#bct-session-time').text(
                this.formatDuration(new Date() - this.activeSession.start_time)
            );
        },
        
        showModal: function(title, content) {
            const modal = `
                <div class="bct-modal-overlay">
                    <div class="bct-modal bct-slide-up">
                        <div class="bct-modal-header">
                            <h3 class="bct-modal-title">${title}</h3>
                            <button class="bct-modal-close">&times;</button>
                        </div>
                        <div class="bct-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
        },
        
        closeModal: function(e) {
            if (e && !$(e.target).hasClass('bct-modal-overlay') && !$(e.target).hasClass('bct-modal-close')) {
                return;
            }
            
            $('.bct-modal-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        showAlert: function(message, type = 'info') {
            const alertClass = `bct-alert-${type}`;
            const alert = `
                <div class="bct-alert ${alertClass} bct-slide-up" style="position: fixed; top: 20px; right: 20px; z-index: 1001; max-width: 400px;">
                    ${message}
                    <button onclick="$(this).parent().fadeOut()" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
                </div>
            `;
            
            $('body').append(alert);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $('.bct-alert').last().fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        showLoading: function(selector) {
            const $container = $(selector);
            $container.append('<div class="bct-loading"><div class="bct-spinner"></div></div>');
        },
        
        hideLoading: function(selector) {
            $(selector).find('.bct-loading').remove();
        },
        
        formatCurrency: function(amount) {
            const formatted = Math.abs(amount).toFixed(2);
            return amount >= 0 ? `+${formatted}` : `-${formatted}`;
        },
        
        formatDuration: function(milliseconds) {
            const seconds = Math.floor(milliseconds / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            
            if (hours > 0) {
                return `${hours}h ${minutes % 60}m`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds % 60}s`;
            } else {
                return `${seconds}s`;
            }
        },
        
        getBetDisplayName: function(betType) {
            const names = {
                'pass_line': 'Pass Line',
                'dont_pass': "Don't Pass",
                'come': 'Come',
                'dont_come': "Don't Come",
                'field': 'Field',
                'place_6': 'Place 6',
                'place_8': 'Place 8',
                'hard_ways': 'Hard Ways',
                'any_seven': 'Any Seven',
                'any_craps': 'Any Craps',
                'odds': 'Odds Bet'
            };
            
            return names[betType] || betType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        // PWA Functions
        initServiceWorker: function() {
            navigator.serviceWorker.register('/wp-content/plugins/bubble-craps-tracker/assets/sw.js')
                .then((registration) => {
                    console.log('SW registered: ', registration);
                })
                .catch((registrationError) => {
                    console.log('SW registration failed: ', registrationError);
                });
        },
        
        // Export data functionality
        exportSessionData: function() {
            $.post(bct_frontend.ajax_url, {
                action: 'bct_export_data',
                nonce: bct_frontend.nonce,
                format: 'csv'
            })
            .done((response) => {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.data.csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'craps_sessions.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }
            });
        },
        
        // Keyboard shortcuts
        initKeyboardShortcuts: function() {
            $(document).keydown((e) => {
                // Only when not in input fields
                if ($(e.target).is('input, textarea, select')) return;
                
                switch(e.which) {
                    case 83: // 'S' - Start session
                        if (!this.activeSession) {
                            $('#bct-start-session').click();
                        }
                        e.preventDefault();
                        break;
                    case 69: // 'E' - End session
                        if (this.activeSession) {
                            $('#bct-end-session').click();
                        }
                        e.preventDefault();
                        break;
                    case 76: // 'L' - Focus bet amount
                        $('#bct-bet-amount').focus();
                        e.preventDefault();
                        break;
                }
            });
        },
        
        // Touch gestures for mobile
        initTouchGestures: function() {
            if (!('ontouchstart' in window)) return;
            
            let startY = null;
            
            $(document).on('touchstart', (e) => {
                startY = e.originalEvent.touches[0].clientY;
            });
            
            $(document).on('touchend', (e) => {
                if (!startY) return;
                
                const endY = e.originalEvent.changedTouches[0].clientY;
                const diff = startY - endY;
                
                // Swipe up to refresh data
                if (diff > 50 && $(window).scrollTop() === 0) {
                    this.loadUserData();
                    this.showAlert('Data refreshed', 'success');
                }
                
                startY = null;
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(() => {
        BCTracker.init();
        BCTracker.initKeyboardShortcuts();
        BCTracker.initTouchGestures();
    });
    
    // Make BCTracker globally available
    window.BCTracker = BCTracker;
    
})(jQuery);
