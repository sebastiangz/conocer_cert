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
     * @param {String} formId ID of the form containing file inputs
     * @param {String} fileListContainerId ID of the container where file list will be displayed
     * @param {Object} options Configuration options
     */
    var DocumentUploader = function(formId, fileListContainerId, options) {
        this.formId = formId;
        this.fileListContainerId = fileListContainerId;
        this.options = $.extend({
            allowedTypes: ['application/pdf', 'image/jpeg', 'image/png'],
            maxFileSize: 10485760, // 10MB default
            requiredDocTypes: [],
            uploadUrl: M.cfg.wwwroot + '/local/conocer_cert/upload_document.php',
            deleteUrl: M.cfg.wwwroot + '/local/conocer_cert/delete_document.php',
            candidateId: 0,
            showValidationErrors: true
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
        $('#' + this.formId).find('input[type="file"]').on('change', function(e) {
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
        $('#' + this.formId).on('submit', function(e) {
            if (self.options.preventFormSubmit) {
                e.preventDefault();
                
                // Check if all required document types are uploaded
                if (self.validateRequiredDocuments()) {
                    // Upload any files not yet uploaded
                    self.uploadAllFiles().then(function() {
                        // After all uploads complete, submit the form
                        $(this).off('submit').submit();
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
                    });
            }
            return false;
        }
        
        // Check file type
        if (this.options.allowedTypes.indexOf(file.type) === -1) {
            if (this.options.showValidationErrors) {
                Str.get_string('error:invalidfiletype', 'local_conocer_cert', this.options.allowedTypes.join(', '))
                    .then(function(message) {
                        Notification.alert('', message);
                    });
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
        
        this.options.requiredDocTypes.forEach(function(docType) {
            // Check if the document is either selected or already uploaded
            if (!self.files[docType] && !self.uploadedFiles[docType]) {
                allRequired = false;
                
                if (self.options.showValidationErrors) {
                    Str.get_string('required_document_missing', 'local_conocer_cert', docType)
                        .then(function(message) {
                            Notification.alert('', message);
                        });
                }
            }
        });
        
        return allRequired;
    };
    
    /**
     * Update the files list in the UI.
     */
    DocumentUploader.prototype.updateFilesList = function() {
        var self = this;
        var files = [];
        
        // Combine selected files and uploaded files
        $.each(this.files, function(docType, file) {
            if (!self.uploadedFiles[docType]) {
                files.push({
                    docType: docType,
                    fileName: file.name,
                    fileSize: self.formatFileSize(file.size),
                    isUploaded: false,
                    status: 'pendiente'
                });
            }
        });
        
        $.each(this.uploadedFiles, function(docType, fileInfo) {
            files.push({
                docType: docType,
                fileName: fileInfo.nombre_archivo,
                fileSize: self.formatFileSize(fileInfo.tamanio || 0),
                isUploaded: true,
                status: fileInfo.estado || 'pendiente',
                docId: fileInfo.id,
                viewUrl: M.cfg.wwwroot + '/local/conocer_cert/document.php?id=' + fileInfo.id + '&action=view'
            });
        });
        
        // Render the file list template
        if (files.length > 0) {
            Templates.render('local_conocer_cert/document_list', {
                files: files,
                has_files: true
            }).then(function(html) {
                $('#' + self.fileListContainerId).html(html);
            }).fail(function(err) {
                Notification.exception(err);
            });
        } else {
            // Show no files message
            Templates.render('local_conocer_cert/document_list', {
                files: [],
                has_files: false
            }).then(function(html) {
                $('#' + self.fileListContainerId).html(html);
            }).fail(function(err) {
                Notification.exception(err);
            });
        }
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
        var formData = new FormData();
        
        formData.append('documento', file);
        formData.append('tipo', docType);
        formData.append('candidato_id', this.options.candidateId);
        formData.append('sesskey', M.cfg.sesskey);
        
        // Show loading spinner
        var $docElement = $('.doc-item[data-doc-type="' + docType + '"]');
        $docElement.find('.doc-status').html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');
        
        return $.ajax({
            url: this.options.uploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).then(function(response) {
            if (response.success) {
                // Update the uploaded files record
                self.uploadedFiles[docType] = response.document;
                // Remove from pending files
                delete self.files[docType];
                // Update the UI
                self.updateFilesList();
                
                return response;
            } else {
                throw new Error(response.message || M.util.get_string('error_file_upload', 'local_conocer_cert'));
            }
        }).catch(function(err) {
            Notification.exception(err);
            // Reset status in UI
            self.updateFilesList();
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
        
        return $.when.apply($, promises);
    };
    
    /**
     * Delete a file from the server.
     *
     * @param {Number} docId Document ID
     * @param {String} docType Document type
     */
    DocumentUploader.prototype.deleteFile = function(docId, docType) {
        var self = this;
        
        // Confirm deletion
        Str.get_string('confirmdeletedocument', 'local_conocer_cert').then(function(confirmMessage) {
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: Str.get_string('delete'),
                body: confirmMessage
            }).then(function(modal) {
                modal.setSaveButtonText(Str.get_string('delete'));
                
                // Handle delete confirmation
                modal.getRoot().on(ModalEvents.save, function() {
                    // Send delete request
                    Ajax.call([{
                        methodname: 'local_conocer_cert_delete_document',
                        args: {
                            documentid: docId,
                            sesskey: M.cfg.sesskey
                        },
                        done: function(response) {
                            if (response.success) {
                                // Remove from uploaded files
                                delete self.uploadedFiles[docType];
                                // Update the UI
                                self.updateFilesList();
                            } else {
                                Notification.alert('', response.message || M.util.get_string('error_delete_failed', 'local_conocer_cert'));
                            }
                        },
                        fail: function(err) {
                            Notification.exception(err);
                        }
                    }]);
                });
                
                modal.show();
            });
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
        $('#' + this.formId).find('input[type="file"]').val('');
    };
    
    return {
        /**
         * Initialize the document uploader.
         *
         * @param {String} formId ID of the form containing file inputs
         * @param {String} fileListContainerId ID of the container where file list will be displayed
         * @param {Object} options Configuration options
         * @return {DocumentUploader} DocumentUploader instance
         */
        init: function(formId, fileListContainerId, options) {
            return new DocumentUploader(formId, fileListContainerId, options);
        }
    };
});
