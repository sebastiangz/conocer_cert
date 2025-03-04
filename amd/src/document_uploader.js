/**
 * Document uploader module for the CONOCER certification plugin.
 *
 * @module     local_conocer_cert/document_uploader
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/notification', 'core/ajax', 'core/templates', 'core/modal_factory', 'core/modal_events'],
function($, Str, Notification, Ajax, Templates, ModalFactory, ModalEvents) {
    
    /**
     * DocumentUploader class.
     *
     * @param {String} formSelector Selector for the form containing file inputs
     * @param {String} fileListContainerSelector Selector for the container where file list will be displayed
     * @param {Object} options Configuration options
     */
    var DocumentUploader = function(formSelector, fileListContainerSelector, options) {
        this.formSelector = formSelector;
        this.fileListContainerSelector = fileListContainerSelector;
        this.options = $.extend({
            allowedTypes: ['application/pdf', 'image/jpeg', 'image/png'],
            maxFileSize: 10485760, // 10MB default
            requiredDocTypes: [],
            candidateId: 0,
            showValidationErrors: true,
            autoUpload: false,
            preventFormSubmit: false
        }, options);
        
        this.files = {};
        this.uploadedFiles = {};
        this.init();
    };
    
    /**
     * Initialize the uploader.
     */
    DocumentUploader.prototype.init = function() {
        this.attachEventHandlers();
        this.loadExistingFiles();
    };
    
    /**
     * Attach event handlers to DOM elements.
     */
    DocumentUploader.prototype.attachEventHandlers = function() {
        var self = this;
        
        // Handle file input change
        $(this.formSelector).find('input[type="file"]').on('change', function(e) {
            var fileInput = $(this);
            var docType = fileInput.data('doc-type');
            
            if (fileInput[0].files.length > 0) {
                var file = fileInput[0].files[0];
                
                if (self.validateFile(file)) {
                    self.files[docType] = file;
                    self.updateFilesList();
                    
                    // Auto upload if option enabled
                    if (self.options.autoUpload) {
                        self.uploadFile(docType, file);
                    }
                } else {
                    // Reset the file input
                    fileInput.val('');
                }
            }
        });
        
        // Handle form submit
        $(this.formSelector).on('submit', function(e) {
            if (self.options.preventFormSubmit) {
                e.preventDefault();
                
                // Check if all required document types are uploaded
                if (self.validateRequiredDocuments()) {
                    // Upload any files not yet uploaded
                    self.uploadAllFiles().then(function() {
                        // After all uploads complete, submit the form
                        $(self.formSelector).off('submit').submit();
                    }).catch(function(error) {
                        Notification.exception(error);
                    });
                }
            } else {
                // Allow form submission but validate required documents
                if (!self.validateRequiredDocuments()) {
                    e.preventDefault();
                }
            }
        });
        
        // Handle upload buttons
        $(document).on('click', '.doc-upload-btn', function(e) {
            e.preventDefault();
            var docType = $(this).data('doc-type');
            
            if (self.files[docType]) {
                self.uploadFile(docType, self.files[docType]);
            }
        });
        
        // Handle delete buttons
        $(document).on('click', '.doc-delete-btn', function(e) {
            e.preventDefault();
            var docType = $(this).data('doc-type');
            var docId = $(this).data('doc-id');
            
            if (docId) {
                self.deleteFile(docId, docType);
            } else if (self.files[docType]) {
                delete self.files[docType];
                self.updateFilesList();
            }
        });
        
        // Handle view document buttons
        $(document).on('click', '.doc-view-btn', function(e) {
            e.preventDefault();
            var viewUrl = $(this).data('view-url');
            
            if (viewUrl) {
                window.open(viewUrl, '_blank');
            }
        });
    };
    
    /**
     * Load existing files from the server.
     */
    DocumentUploader.prototype.loadExistingFiles = function() {
        var self = this;
        
        if (!this.options.candidateId) {
            return;
        }
        
        Ajax.call([{
            methodname: 'local_conocer_cert_get_candidate_documents',
            args: {
                candidateid: this.options.candidateId
            },
            done: function(response) {
                if (response.success && response.documents) {
                    response.documents.forEach(function(doc) {
                        self.uploadedFiles[doc.tipo] = doc;
                    });
                    self.updateFilesList();
                }
            },
            fail: function(err) {
                Notification.exception(err);
            }
        }]);
    };
    
    /**
     * Validate a file against size and type restrictions.
     *
     * @param {File} file The file to validate
     * @return {Boolean} True if file is valid
     */
    DocumentUploader.prototype.validateFile = function(file) {
        // Check file size
        if (file.size > this.options.maxFileSize) {
            if (this.options.showValidationErrors) {
                Str.get_string('error:filetoobig', 'local_conocer_cert', this.formatFileSize(this.options.maxFileSize))
                    .then(function(message) {
                        Notification.alert('', message);
                    })
                    .catch(Notification.exception);
            }
            return false;
        }
        
        // Check file type
        if (this.options.allowedTypes.indexOf(file.type) === -1) {
            if (this.options.showValidationErrors) {
                Str.get_string('error:invalidfiletype', 'local_conocer_cert', this.options.allowedTypes.join(', '))
                    .then(function(message) {
                        Notification.alert('', message);
                    })
                    .catch(Notification.exception);
            }
            return false;
        }
        
        return true;
    };
    
    /**
     * Validate that all required document types have been selected.
     *
     * @return {Boolean} True if all required documents are selected
     */
    DocumentUploader.prototype.validateRequiredDocuments = function() {
        var self = this;
        var allRequired = true;
        var missingDocTypes = [];
        
        this.options.requiredDocTypes.forEach(function(docType) {
            // Check if the document is either selected or already uploaded
            if (!self.files[docType] && !self.uploadedFiles[docType]) {
                allRequired = false;
                missingDocTypes.push(docType);
            }
        });
        
        if (!allRequired && this.options.showValidationErrors && missingDocTypes.length > 0) {
            Str.get_string('required_documents_missing', 'local_conocer_cert', missingDocTypes.join(', '))
                .then(function(message) {
                    Notification.alert('', message);
                })
                .catch(Notification.exception);
        }
        
        return allRequired;
    };
    
    /**
     * Update the files list in the UI.
     */
    DocumentUploader.prototype.updateFilesList = function() {
        var self = this;
        var files = [];
        
        // Combine selected files and uploaded files for display
        $.each(this.files, function(docType, file) {
            if (!self.uploadedFiles[docType]) {
                files.push({
                    docType: docType,
                    docTypeName: M.util.get_string('doc_' + docType, 'local_conocer_cert') || docType,
                    fileName: file.name,
                    fileSize: self.formatFileSize(file.size),
                    isUploaded: false,
                    isPending: true,
                    status: 'pendiente',
                    statusText: M.util.get_string('doc_status_pendiente', 'local_conocer_cert') || 'Pendiente'
                });
            }
        });
        
        $.each(this.uploadedFiles, function(docType, fileInfo) {
            var statusClass = '';
            switch(fileInfo.estado) {
                case 'aprobado':
                    statusClass = 'success';
                    break;
                case 'rechazado':
                    statusClass = 'danger';
                    break;
                default:
                    statusClass = 'warning';
            }
            
            files.push({
                docType: docType,
                docTypeName: M.util.get_string('doc_' + docType, 'local_conocer_cert') || docType,
                fileName: fileInfo.nombre_archivo,
                fileSize: self.formatFileSize(fileInfo.tamanio || 0),
                isUploaded: true,
                isPending: false,
                status: fileInfo.estado || 'pendiente',
                statusText: M.util.get_string('doc_status_' + (fileInfo.estado || 'pendiente'), 'local_conocer_cert') || 'Pendiente',
                statusClass: statusClass,
                docId: fileInfo.id,
                viewUrl: M.cfg.wwwroot + '/local/conocer_cert/document.php?id=' + fileInfo.id + '&action=view',
                comentarios: fileInfo.comentarios
            });
        });
        
        // Render the file list template
        Templates.render('local_conocer_cert/document_list', {
            files: files,
            has_files: files.length > 0
        }).then(function(html) {
            $(self.fileListContainerSelector).html(html);
            
            // Initialize tooltips if Bootstrap is available
            if ($.fn.tooltip) {
                $(self.fileListContainerSelector).find('[data-toggle="tooltip"]').tooltip();
            }
        }).catch(function(err) {
            Notification.exception(err);
        });
    };
    
    /**
     * Upload a file to the server.
     *
     * @param {String} docType Document type
     * @param {File} file File to upload
     * @return {Promise} Promise that resolves when upload is complete
     */
    DocumentUploader.prototype.uploadFile = function(docType, file) {
        var self = this;
        
        // Show loading spinner
        var $docElement = $('.doc-item[data-doc-type="' + docType + '"]');
        $docElement.find('.doc-status').html('<i class="fa fa-spinner fa-spin"></i> ' + M.util.get_string('uploading', 'local_conocer_cert'));
        
        // Create a Promise to handle the file reading and upload process
        return new Promise(function(resolve, reject) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                // File contents are in e.target.result as base64
                var fileContent = e.target.result.split(',')[1]; // Remove the data URL prefix
                
                // Call the upload API function
                Ajax.call([{
                    methodname: 'local_conocer_cert_upload_document',
                    args: {
                        candidateid: self.options.candidateId,
                        tipo: docType,
                        filename: file.name,
                        filecontent: fileContent,
                        mimetype: file.type
                    },
                    done: function(response) {
                        if (response.success) {
                            // Update the uploaded files record
                            self.uploadedFiles[docType] = response.document;
                            // Remove from pending files
                            delete self.files[docType];
                            // Update the UI
                            self.updateFilesList();
                            resolve(response);
                        } else {
                            var error = new Error(response.message || M.util.get_string('error_file_upload', 'local_conocer_cert'));
                            reject(error);
                        }
                    },
                    fail: function(error) {
                        reject(error);
                    }
                }]);
            };
            
            reader.onerror = function() {
                reject(new Error(M.util.get_string('error_reading_file', 'local_conocer_cert')));
            };
            
            // Read the file as a data URL (base64)
            reader.readAsDataURL(file);
        }).catch(function(error) {
            Notification.exception(error);
            // Reset status in UI
            self.updateFilesList();
            throw error;
        });
    };
    
    /**
     * Upload all pending files.
     *
     * @return {Promise} Promise that resolves when all uploads are complete
     */
    DocumentUploader.prototype.uploadAllFiles = function() {
        var self = this;
        var promises = [];
        
        $.each(this.files, function(docType, file) {
            promises.push(self.uploadFile(docType, file));
        });
        
        return Promise.all(promises);
    };
    
    /**
     * Delete a file from the server.
     *
     * @param {Number} docId Document ID
     * @param {String} docType Document type
     * @return {Promise} Promise that resolves when delete is complete
     */
    DocumentUploader.prototype.deleteFile = function(docId, docType) {
        var self = this;
        
        // Confirm deletion with a modal
        return Str.get_strings([
            {key: 'confirmdeletedocument', component: 'local_conocer_cert'},
            {key: 'delete', component: 'core'},
            {key: 'cancel', component: 'core'}
        ]).then(function(strings) {
            return ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: strings[1], // Delete
                body: strings[0], // Confirm message
                buttons: {
                    save: strings[1], // Delete
                    cancel: strings[2] // Cancel
                }
            });
        }).then(function(modal) {
            modal.setSaveButtonText(M.util.get_string('delete', 'core'));
            
            // Set up the delete action when the user confirms
            var deferred = $.Deferred();
            
            modal.getRoot().on(ModalEvents.save, function() {
                // Send delete request using the API
                Ajax.call([{
                    methodname: 'local_conocer_cert_delete_document',
                    args: {
                        documentid: docId
                    },
                    done: function(response) {
                        if (response.success) {
                            // Remove from uploaded files
                            delete self.uploadedFiles[docType];
                            // Update the UI
                            self.updateFilesList();
                            deferred.resolve(response);
                        } else {
                            var error = new Error(response.message || M.util.get_string('error_delete_failed', 'local_conocer_cert'));
                            deferred.reject(error);
                            Notification.exception(error);
                        }
                    },
                    fail: function(error) {
                        deferred.reject(error);
                        Notification.exception(error);
                    }
                }]);
            });
            
            modal.getRoot().on(ModalEvents.cancel, function() {
                deferred.reject(new Error('User cancelled'));
            });
            
            modal.show();
            
            return deferred.promise();
        }).catch(function(error) {
            // Don't show an error if user cancelled
            if (error.message !== 'User cancelled') {
                Notification.exception(error);
            }
            return $.Deferred().reject(error).promise();
        });
    };
    
    /**
     * Format file size in human-readable format.
     *
     * @param {Number} bytes File size in bytes
     * @return {String} Formatted file size
     */
    DocumentUploader.prototype.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };
    
    /**
     * Reset the uploader.
     */
    DocumentUploader.prototype.reset = function() {
        this.files = {};
        this.uploadedFiles = {};
        this.updateFilesList();
        $(this.formSelector).find('input[type="file"]').val('');
    };
    
    /**
     * Get a list of document types that still need to be uploaded.
     * 
     * @return {Array} List of document types that are required but not uploaded
     */
    DocumentUploader.prototype.getMissingDocuments = function() {
        var self = this;
        var missing = [];
        
        this.options.requiredDocTypes.forEach(function(docType) {
            if (!self.files[docType] && !self.uploadedFiles[docType]) {
                missing.push(docType);
            }
        });
        
        return missing;
    };
    
    /**
     * Check if all required documents are uploaded.
     * 
     * @return {Boolean} True if all required documents are uploaded
     */
    DocumentUploader.prototype.isComplete = function() {
        return this.getMissingDocuments().length === 0;
    };
    
    return {
        /**
         * Initialize a new document uploader instance.
         *
         * @param {String} formSelector Selector for the form containing file inputs
         * @param {String} fileListContainerSelector Selector for the container where file list will be displayed
         * @param {Object} options Configuration options
         * @return {DocumentUploader} New DocumentUploader instance
         */
        init: function(formSelector, fileListContainerSelector, options) {
            return new DocumentUploader(formSelector, fileListContainerSelector, options);
        }
    };
});
