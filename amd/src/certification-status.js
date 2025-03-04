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
 * Certification status handler for CONOCER certification plugin.
 *
 * @module     local_conocer_cert/certification_status
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/config'],
    function($, Ajax, Templates, Notification, Str, ModalFactory, ModalEvents, Config) {
        
        /**
         * Constructor for the CertificationStatus module
         * @param {string} selector - Selector for the certification container
         * @param {object} options - Configuration options
         */
        var CertificationStatus = function(selector, options) {
            this.selector = selector;
            this.options = $.extend({
                updateUrl: Config.wwwroot + '/local/conocer_cert/ajax/update_status.php',
                checkUrl: Config.wwwroot + '/local/conocer_cert/ajax/check_status.php',
                autoRefresh: true,
                refreshInterval: 60000, // 1 minute
                animateChanges: true,
                enableActions: true,
                processId: 0
            }, options);
            
            this.init();
        };
        
        /**
         * Initialize the CertificationStatus module
         */
        CertificationStatus.prototype.init = function() {
            this.$container = $(this.selector);
            
            if (!this.$container.length) {
                console.warn('Certification container not found:', this.selector);
                return;
            }
            
            // Get process ID from the container if not provided in options
            if (!this.options.processId) {
                this.options.processId = this.$container.data('process-id');
            }
            
            // Setup event handlers
            this.setupEventHandlers();
            
            // Setup auto refresh if enabled
            if (this.options.autoRefresh && this.options.processId) {
                this.setupAutoRefresh();
            }
            
            // Initialize progress visualization
            this.initProgressVisualization();
        };
        
        /**
         * Setup event handlers
         */
        CertificationStatus.prototype.setupEventHandlers = function() {
            var self = this;
            
            // Handle status update button clicks
            this.$container.on('click', '.update-status-btn', function(e) {
                e.preventDefault();
                var newStatus = $(this).data('status');
                var currentStage = self.$container.find('.certification-progress').data('current-stage');
                
                self.confirmStatusUpdate(newStatus, currentStage);
            });
            
            // Handle document approval/rejection buttons
            this.$container.on('click', '.document-action-btn', function(e) {
                e.preventDefault();
                var docId = $(this).data('doc-id');
                var action = $(this).data('action');
                
                self.handleDocumentAction(docId, action);
            });
            
            // Handle evaluation form submission
            this.$container.on('submit', '#evaluation-form', function(e) {
                e.preventDefault();
                self.submitEvaluation($(this));
            });
        };
        
        /**
         * Initialize progress visualization
         */
        CertificationStatus.prototype.initProgressVisualization = function() {
            var $progress = this.$container.find('.certification-progress');
            
            if (!$progress.length) {
                return;
            }
            
            var currentStage = $progress.data('current-stage');
            var stages = $progress.data('stages').split(',');
            
            // Calculate progress percentage
            var stageIndex = stages.indexOf(currentStage);
            if (stageIndex !== -1) {
                var progressPercent = Math.round((stageIndex / (stages.length - 1)) * 100);
                $progress.find('.progress-bar').css('width', progressPercent + '%').attr('aria-valuenow', progressPercent);
            }
            
            // Highlight current stage
            $progress.find('.step-circle[data-stage="' + currentStage + '"]').addClass('current');
            $progress.find('.step-name[data-stage="' + currentStage + '"]').addClass('current');
            
            // Mark completed stages
            for (var i = 0; i < stageIndex; i++) {
                $progress.find('.step-circle[data-stage="' + stages[i] + '"]').addClass('completed');
                $progress.find('.step-name[data-stage="' + stages[i] + '"]').addClass('completed');
            }
        };
        
        /**
         * Setup automatic refresh
         */
        CertificationStatus.prototype.setupAutoRefresh = function() {
            var self = this;
            
            // Set interval to check for status updates
            this.refreshInterval = setInterval(function() {
                self.checkStatusUpdate();
            }, this.options.refreshInterval);
        };
        
        /**
         * Check for status updates
         */
        CertificationStatus.prototype.checkStatusUpdate = function() {
            var self = this;
            
            if (!this.options.processId) {
                return;
            }
            
            var request = {
                methodname: 'local_conocer_cert_check_process_status',
                args: {
                    process_id: this.options.processId
                }
            };
            
            Ajax.call([request])[0].done(function(response) {
                if (response.status && response.status !== self.$container.find('.certification-progress').data('current-stage')) {
                    // Status has changed, refresh the UI
                    self.refreshCertificationStatus();
                }
            }).fail(function(error) {
                // Silently fail - not critical
                console.error('Failed to check status update', error);
            });
        };
        
        /**
         * Refresh certification status UI
         */
        CertificationStatus.prototype.refreshCertificationStatus = function() {
            var self = this;
            
            // Get updated certification data
            var request = {
                methodname: 'local_conocer_cert_get_certification_data',
                args: {
                    process_id: this.options.processId
                }
            };
            
            Ajax.call([request])[0].done(function(response) {
                // Update progress bar
                Templates.render('local_conocer_cert/certification_progress', response.progress_data)
                    .done(function(html) {
                        var $oldProgress = self.$container.find('.certification-progress-container');
                        var $newProgress = $(html);
                        
                        if (self.options.animateChanges) {
                            $oldProgress.fadeOut(300, function() {
                                $oldProgress.replaceWith($newProgress);
                                $newProgress.hide().fadeIn(300);
                                self.initProgressVisualization();
                            });
                        } else {
                            $oldProgress.replaceWith($newProgress);
                            self.initProgressVisualization();
                        }
                    });
                
                // Update status indicators
                self.$container.find('.status-indicator').each(function() {
                    var $indicator = $(this);
                    var statusType = $indicator.data('status-type');
                    
                    if (response.status_data && response.status_data[statusType]) {
                        Templates.render('local_conocer_cert/status_indicator', response.status_data[statusType])
                            .done(function(html) {
                                if (self.options.animateChanges) {
                                    $indicator.fadeOut(300, function() {
                                        $indicator.replaceWith(html);
                                        $(html).hide().fadeIn(300);
                                    });
                                } else {
                                    $indicator.replaceWith(html);
                                }
                            });
                    }
                });
                
                // Show notification about the update
                if (response.notification) {
                    Notification.addNotification({
                        message: response.notification,
                        type: 'info'
                    });
                }
            }).fail(Notification.exception);
        };
        
        /**
         * Confirm status update
         * @param {string} newStatus - New status to set
         * @param {string} currentStage - Current stage of the process
         */
        CertificationStatus.prototype.confirmStatusUpdate = function(newStatus, currentStage) {
            var self = this;
            
            // Get confirmation strings based on status transition
            var confirmKey = 'confirm_status_change_' + currentStage + '_to_' + newStatus;
            var fallbackConfirmKey = 'confirm_status_change';
            
            Str.get_strings([
                {key: confirmKey, component: 'local_conocer_cert'},
                {key: fallbackConfirmKey, component: 'local_conocer_cert'},
                {key: 'status_update', component: 'local_conocer_cert'},
                {key: 'confirm', component: 'local_conocer_cert'},
                {key: 'cancel', component: 'local_conocer_cert'}
            ]).done(function(strings) {
                var confirmMessage = strings[0] !== '[["' + confirmKey + '","local_conocer_cert"]]' ? strings[0] : strings[1];
                var title = strings[2];
                
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: confirmMessage
                }).done(function(modal) {
                    modal.setSaveButtonText(strings[3]);
                    modal.getRoot().on(ModalEvents.save, function() {
                        self.updateCertificationStatus(newStatus);
                    });
                    modal.show();
                });
            }).fail(Notification.exception);
        };
        
        /**
         * Update certification status
         * @param {string} newStatus - New status to set
         */
        CertificationStatus.prototype.updateCertificationStatus = function(newStatus) {
            var self = this;
            
            // Show loading indicator
            this.$container.find('.certification-progress-container').addClass('loading');
            
            var request = {
                methodname: 'local_conocer_cert_update_process_status',
                args: {
                    process_id: this.options.processId,
                    status: newStatus,
                    sesskey: Config.sesskey
                }
            };
            
            Ajax.call([request])[0].done(function(response) {
                if (response.success) {
                    // Show success message
                    Notification.addNotification({
                        message: response.message,
                        type: 'success'
                    });
                    
                    // Refresh the UI
                    self.refreshCertificationStatus();
                } else {
                    // Show error message
                    Notification.addNotification({
                        message: response.message,
                        type: 'error'
                    });
                }
                
                // Remove loading indicator
                self.$container.find('.certification-progress-container').removeClass('loading');
            }).fail(function(error) {
                Notification.exception(error);
                self.$container.find('.certification-progress-container').removeClass('loading');
            });
        };
        
        /**
         * Handle document action (approve/reject)
         * @param {int} docId - Document ID
         * @param {string} action - Action to perform (approve/reject)
         */
        CertificationStatus.prototype.handleDocumentAction = function(docId, action) {
            var self = this;
            
            // If action is rejection, show comment modal
            if (action === 'reject') {
                this.showDocumentCommentModal(docId, action);
                return;
            }
            
            // For approval, proceed directly
            var request = {
                methodname: 'local_conocer_cert_update_document_status',
                args: {
                    document_id: docId,
                    status: action === 'approve' ? 'aprobado' : 'rechazado',
                    comments: '',
                    sesskey: Config.sesskey
                }
            };
            
            Ajax.call([request])[0].done(function(response) {
                if (response.success) {
                    // Update document status in UI
                    var $doc = self.$container.find('.document-item[data-doc-id="' + docId + '"]');
                    
                    Templates.render('local_conocer_cert/document_status', {
                        status: action === 'approve' ? 'aprobado' : 'rechazado',
                        status_text: action === 'approve' ? M.util.get_string('approved', 'local_conocer_cert') : M.util.get_string('rejected', 'local_conocer_cert'),
                        status_class: action === 'approve' ? 'success' : 'danger'
                    }).done(function(html) {
                        $doc.find('.document-status').html(html);
                        
                        // Disable action buttons
                        $doc.find('.document-action-btn').prop('disabled', true);
                        
                        // Show success message
                        Notification.addNotification({
                            message: response.message,
                            type: 'success'
                        });
                    });
                } else {
                    // Show error message
                    Notification.addNotification({
                        message: response.message,
                        type: 'error'
                    });
                }
            }).fail(Notification.exception);
        };
        
        /**
         * Show document comment modal for rejection
         * @param {int} docId - Document ID
         * @param {string} action - Action being performed
         */
        CertificationStatus.prototype.showDocumentCommentModal = function(docId, action) {
            var self = this;
            
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: Str.get_string('document_rejection_reason', 'local_conocer_cert'),
                body: Templates.render('local_conocer_cert/document_comment_form', {
                    document_id: docId
                })
            }).done(function(modal) {
                modal.setSaveButtonText(Str.get_string('reject', 'local_conocer_cert'));
                
                modal.getRoot().on(ModalEvents.save, function(e) {
                    e.preventDefault();
                    
                    var comments = modal.getRoot().find('#document-comments').val();
                    
                    var request = {
                        methodname: 'local_conocer_cert_update_document_status',
                        args: {
                            document_id: docId,
                            status: 'rechazado',
                            comments: comments,
                            sesskey: Config.sesskey
                        }
                    };
                    
                    Ajax.call([request])[0].done(function(response) {
                        if (response.success) {
                            // Update document status in UI
                            var $doc = self.$container.find('.document-item[data-doc-id="' + docId + '"]');
                            
                            Templates.render('local_conocer_cert/document_status', {
                                status: 'rechazado',
                                status_text: M.util.get_string('rejected', 'local_conocer_cert'),
                                status_class: 'danger'
                            }).done(function(html) {
                                $doc.find('.document-status').html(html);
                                
                                // Add comment to UI
                                if (comments) {
                                    $doc.find('.document-comments').text(comments).parent().removeClass('d-none');
                                }
                                
                                // Disable action buttons
                                $doc.find('.document-action-btn').prop('disabled', true);
                                
                                // Show success message
                                Notification.addNotification({
                                    message: response.message,
                                    type: 'success'
                                });
                                
                                // Close modal
                                modal.hide();
                            });
                        } else {
                            // Show error message
                            Notification.addNotification({
                                message: response.message,
                                type: 'error'
                            });
                        }
                    }).fail(Notification.exception);
                });
                
                modal.show();
            });
        };
        
        /**
         * Submit evaluation form
         * @param {jQuery} $form - jQuery form element
         */
        CertificationStatus.prototype.submitEvaluation = function($form) {
            var self = this;
            var formData = $form.serializeArray();
            var processId = this.options.processId;
            
            // Show loading indicator
            $form.find('button[type="submit"]').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> ' + M.util.get_string('submitting', 'local_conocer_cert'));
            
            // Convert form data to object
            var data = {};
            $.each(formData, function(i, field) {
                data[field.name] = field.value;
            });
            
            // Add process ID and sesskey
            data.process_id = processId;
            data.sesskey = Config.sesskey;
            
            // Submit evaluation
            var request = {
                methodname: 'local_conocer_cert_submit_evaluation',
                args: data
            };
            
            Ajax.call([request])[0].done(function(response) {
                if (response.success) {
                    // Show success message
                    Notification.addNotification({
                        message: response.message,
                        type: 'success'
                    });
                    
                    // Disable form
                    $form.find('input, textarea, select, button').prop('disabled', true);
                    
                    // Refresh the certification status
                    self.refreshCertificationStatus();
                    
                    // Show completion message in form area
                    Templates.render('local_conocer_cert/evaluation_complete', {
                        result: data.resultado,
                        result_text: data.resultado === 'aprobado' ? 
                            M.util.get_string('approved', 'local_conocer_cert') : 
                            M.util.get_string('rejected', 'local_conocer_cert'),
                        result_class: data.resultado === 'aprobado' ? 'success' : 'danger'
                    }).done(function(html) {
                        $form.parent().prepend(html);
                    });
                } else {
                    // Show error message
                    Notification.addNotification({
                        message: response.message,
                        type: 'error'
                    });
                    
                    // Re-enable submit button
                    $form.find('button[type="submit"]').prop('disabled', false)
                        .html(M.util.get_string('submit_evaluation', 'local_conocer_cert'));
                }
            }).fail(function(error) {
                Notification.exception(error);
                
                // Re-enable submit button
                $form.find('button[type="submit"]').prop('disabled', false)
                    .html(M.util.get_string('submit_evaluation', 'local_conocer_cert'));
            });
        };
        
        /**
         * Clean up (e.g., when leaving the page)
         */
        CertificationStatus.prototype.destroy = function() {
            // Clear any intervals
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            // Remove event handlers
            this.$container.off('click', '.update-status-btn');
            this.$container.off('click', '.document-action-btn');
            this.$container.off('submit', '#evaluation-form');
        };
        
        /**
         * Public API
         */
        return {
            /**
             * Initialize the module.
             *
             * @param {string} selector - Selector for the certification container
             * @param {object} options - Configuration options
             * @return {CertificationStatus} The initialized instance
             */
            init: function(selector, options) {
                return new CertificationStatus(selector, options);
            }
        };
    });
