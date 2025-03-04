// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Dashboard controller for CONOCER certification plugin.
 *
 * @module     local_conocer_cert/dashboard_controller
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/modal_factory', 'core/modal_events'],
    function($, Ajax, Templates, Notification, Str, ModalFactory, ModalEvents) {
        
        /**
         * Constructor for the Dashboard Controller
         * @param {string} dashboardType - Type of dashboard (candidate, evaluator, company, admin)
         * @param {object} config - Configuration options
         */
        var DashboardController = function(dashboardType, config) {
            this.dashboardType = dashboardType;
            this.config = config || {};
            this.init();
        };
        
        /**
         * Initialize the dashboard controller
         */
        DashboardController.prototype.init = function() {
            // Initialize common components
            this.initNotifications();
            
            // Initialize specific dashboard components
            switch (this.dashboardType) {
                case 'candidate':
                    this.initCandidateDashboard();
                    break;
                case 'evaluator':
                    this.initEvaluatorDashboard();
                    break;
                case 'company':
                    this.initCompanyDashboard();
                    break;
                case 'admin':
                    this.initAdminDashboard();
                    break;
            }
            
            // Setup refresh functionality
            this.setupRefresh();
        };
        
        /**
         * Initialize notifications components
         */
        DashboardController.prototype.initNotifications = function() {
            var self = this;
            
            // Mark notification as read when clicked
            $('.notifications-list .unread').on('click', function(e) {
                if (!$(e.target).closest('a').length) {
                    var notificationId = $(this).data('notification-id');
                    self.markNotificationAsRead(notificationId, $(this));
                }
            });
            
            // Mark all notifications as read
            $('.mark-all-read-btn').on('click', function(e) {
                e.preventDefault();
                self.markAllNotificationsAsRead();
            });
        };
        
        /**
         * Mark a notification as read
         * @param {int} notificationId - ID of the notification
         * @param {jQuery} $element - jQuery element of the notification
         */
        DashboardController.prototype.markNotificationAsRead = function(notificationId, $element) {
            var request = {
                methodname: 'local_conocer_cert_mark_notification_read',
                args: {
                    notification_id: notificationId
                }
            };
            
            Ajax.call([request])[0].done(function() {
                // Update UI to reflect read status
                $element.removeClass('unread');
                $element.find('.badge-primary').remove();
                
                // Update notification count
                var $countBadge = $('.notifications-count-badge');
                var currentCount = parseInt($countBadge.text(), 10);
                if (currentCount > 0) {
                    $countBadge.text(currentCount - 1);
                    if (currentCount - 1 === 0) {
                        $countBadge.hide();
                    }
                }
            }).fail(Notification.exception);
        };
        
        /**
         * Mark all notifications as read
         */
        DashboardController.prototype.markAllNotificationsAsRead = function() {
            var request = {
                methodname: 'local_conocer_cert_mark_all_notifications_read',
                args: {}
            };
            
            Ajax.call([request])[0].done(function() {
                // Update UI to reflect all read
                $('.notifications-list .unread').removeClass('unread');
                $('.notifications-list .badge-primary').remove();
                $('.notifications-count-badge').text('0').hide();
            }).fail(Notification.exception);
        };
        
        /**
         * Initialize candidate dashboard specific components
         */
        DashboardController.prototype.initCandidateDashboard = function() {
            var self = this;
            
            // Document upload buttons
            $('.upload-document-btn').on('click', function(e) {
                e.preventDefault();
                var requestId = $(this).data('request-id');
                self.openDocumentUploadModal(requestId);
            });
            
            // Process progress indicators
            this.initProgressBars();
        };
        
        /**
         * Initialize progress bars for certification progress
         */
        DashboardController.prototype.initProgressBars = function() {
            $('.certification-progress').each(function() {
                var $progressContainer = $(this);
                var currentStage = $progressContainer.data('current-stage');
                var stages = $progressContainer.data('stages').split(',');
                
                // Calculate progress
                var stageIndex = stages.indexOf(currentStage);
                if (stageIndex !== -1) {
                    var progressPercent = Math.round((stageIndex / (stages.length - 1)) * 100);
                    $progressContainer.find('.progress-bar').css('width', progressPercent + '%');
                    $progressContainer.find('.progress-bar').text(progressPercent + '%');
                }
            });
        };
        
        /**
         * Open document upload modal
         * @param {int} requestId - ID of the certification request
         */
        DashboardController.prototype.openDocumentUploadModal = function(requestId) {
            var self = this;
            
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: Str.get_string('upload_documents', 'local_conocer_cert'),
                body: Templates.render('local_conocer_cert/document_upload_form', {
                    request_id: requestId
                })
            }).done(function(modal) {
                modal.show();
                
                // Initialize file upload in the modal
                require(['local_conocer_cert/document_uploader'], function(DocumentUploader) {
                    new DocumentUploader('#document-upload-form', {
                        requestId: requestId
                    });
                });
                
                // Handle form submission
                modal.getRoot().on(ModalEvents.save, function(e) {
                    e.preventDefault();
                    
                    // Submit the form via AJAX - handled by document_uploader.js
                    $('#document-upload-form').submit();
                    
                    // Close modal and refresh page on success
                    modal.hide();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                });
            });
        };
        
        /**
         * Initialize evaluator dashboard specific components
         */
        DashboardController.prototype.initEvaluatorDashboard = function() {
            // Highlight urgent evaluations
            this.highlightUrgentItems();
            
            // Handle evaluate button clicks
            $('.evaluate-candidate-btn').on('click', function(e) {
                e.preventDefault();
                var candidateId = $(this).data('candidate-id');
                window.location.href = M.cfg.wwwroot + '/local/conocer_cert/evaluator/evaluate.php?id=' + candidateId;
            });
        };
        
        /**
         * Highlight urgent items based on days pending
         */
        DashboardController.prototype.highlightUrgentItems = function() {
            $('.days-pending').each(function() {
                var days = parseInt($(this).data('days'), 10);
                if (days > 5) {
                    $(this).addClass('text-danger font-weight-bold');
                } else if (days > 3) {
                    $(this).addClass('text-warning font-weight-bold');
                }
            });
        };
        
        /**
         * Initialize company dashboard specific components
         */
        DashboardController.prototype.initCompanyDashboard = function() {
            // Company statistics charts
            if ($('#company-statistics-chart').length) {
                this.initCompanyStatisticsChart();
            }
            
            // Company competency management
            $('.manage-competency-btn').on('click', function(e) {
                e.preventDefault();
                var competencyId = $(this).data('competency-id');
                window.location.href = M.cfg.wwwroot + '/local/conocer_cert/company/manage_competency.php?id=' + competencyId;
            });
        };
        
        /**
         * Initialize company statistics chart
         */
        DashboardController.prototype.initCompanyStatisticsChart = function() {
            require(['core/chart_builder'], function(ChartBuilder) {
                var chartData = JSON.parse($('#company-statistics-chart').attr('data-chart'));
                
                ChartBuilder.make([{
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                }]);
            });
        };
        
        /**
         * Initialize admin dashboard specific components
         */
        DashboardController.prototype.initAdminDashboard = function() {
            // Admin statistics charts
            if ($('#admin-statistics-chart').length) {
                this.initAdminStatisticsChart();
            }
            
            // Handle assign evaluator buttons
            $('.assign-evaluator-btn').on('click', function(e) {
                e.preventDefault();
                var candidateId = $(this).data('candidate-id');
                window.location.href = M.cfg.wwwroot + '/local/conocer_cert/admin/assign_evaluator.php?id=' + candidateId;
            });
            
            // Handle approve company buttons
            $('.approve-company-btn').on('click', function(e) {
                e.preventDefault();
                var companyId = $(this).data('company-id');
                window.location.href = M.cfg.wwwroot + '/local/conocer_cert/admin/approve_company.php?id=' + companyId;
            });
        };
        
        /**
         * Initialize admin statistics chart
         */
        DashboardController.prototype.initAdminStatisticsChart = function() {
            require(['core/chart_builder'], function(ChartBuilder) {
                var chartData = JSON.parse($('#admin-statistics-chart').attr('data-chart'));
                
                ChartBuilder.make([{
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                }]);
            });
        };
        
        /**
         * Setup automatic refresh of dashboard data
         */
        DashboardController.prototype.setupRefresh = function() {
            var self = this;
            
            // Refresh notification count every 5 minutes
            if (this.config.enableAutoRefresh !== false) {
                setInterval(function() {
                    self.refreshNotificationCount();
                }, 300000); // 5 minutes
            }
            
            // Manual refresh button
            $('.refresh-dashboard-btn').on('click', function(e) {
                e.preventDefault();
                window.location.reload();
            });
        };
        
        /**
         * Refresh notification count
         */
        DashboardController.prototype.refreshNotificationCount = function() {
            var request = {
                methodname: 'local_conocer_cert_get_notification_count',
                args: {}
            };
            
            Ajax.call([request])[0].done(function(response) {
                var $countBadge = $('.notifications-count-badge');
                
                if (response.count > 0) {
                    $countBadge.text(response.count).show();
                } else {
                    $countBadge.text('0').hide();
                }
            }).fail(function(error) {
                // Silently fail - not critical
                console.error('Failed to refresh notification count', error);
            });
        };
        
        return {
            /**
             * Initialize the module.
             *
             * @param {string} dashboardType - Type of dashboard (candidate, evaluator, company, admin)
             * @param {object} config - Configuration options
             * @return {DashboardController} The initialized instance
             */
            init: function(dashboardType, config) {
                return new DashboardController(dashboardType, config);
            }
        };
    });