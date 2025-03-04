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
 * Document uploader for CONOCER certification plugin.
 *
 * @module     local_conocer_cert/document_uploader
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/modal_factory', 'core/modal_events'],
    function($, Ajax, Notification, Str, Templates, ModalFactory, ModalEvents) {
        
        /**
         * Constructor for the Document Uploader
         * @param {string} selector - CSS selector for the form container
         * @param {object} config - Configuration options
         */
        var DocumentUploader = function(selector, config) {
            this.selector = selector;
            this.$container = $(selector);
            this.config = $.extend({
                maxFileSizeMB: 10,
                maxPhotoSizeMB: 2,
                allowedFileTypes: {
                    id_oficial: ['application/pdf', 'image/jpeg', 'image/png'],
                    curp_doc: ['application/pdf', 'image/jpeg', 'image/png'],
                    comprobante_domicilio: ['application/pdf', 'image/jpeg', 'image/png'],
                    evidencia_laboral: ['application/pdf', 'image/jpeg', 'image/png'],
                    fotografia: ['image/jpeg', 'image/png'],
                    docs_adicionales: ['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
                }
            }, config || {});
            
            this.init();
        };
        
        /**
         * Initialize the document uploader
         */
        DocumentUploader.prototype.init = function() {
            this.initFileInputs();
            this.initFormSubmit();
            this.setupProgressIndicators();
        };
        
        /**
         * Initialize file input elements
         */
        DocumentUploader.prototype.initFileInputs = function() {
            var self = this;
            
            // Setup file input listeners
            this.$container.find('input[type="file"]').each(function() {
                var $input = $(this);
                var documentType = $input.attr('name');
                
                // Create a preview area after the input
                var $previewArea = $('<div class="file-preview-area mt-2"></div>');
                $input.after($previewArea);
                
                // Add change event listener
                $input.on('change', function(e) {
                    var file = e.target.files[0];
                    if (file) {
                        self.validateAndPreviewFile(file, documentType, $previewArea);
                    } else {
                        $previewArea.empty();
                    }
                });
            });
            
            // Add document type descriptions
            this.addDocumentDescriptions();
        };
        
        /**
         * Add document type descriptions
         */
        DocumentUploader.prototype.addDocumentDescriptions = function() {
            var self = this;
            var documentTypes = [
                'id_oficial', 'curp_doc', 'comprobante_domicilio',
                'evidencia_laboral', 'fotografia', 'docs_adicionales'
            ];
            
            // Add description for each document type
            documentTypes.forEach(function(docType) {
                var $input = self.$container.find('input[name="' + docType + '"]');
                if ($input.length) {
                    var $label = $input.closest('.form-group').find('label');
                    var $description = $('<small class="form-text text-muted"></small>');
                    
                    // Construct string id
                    var stringId = 'doc_' + docType + '_description';
                    
                    // Get description string
                    Str.get_string(stringId, 'local_conocer_cert').done(function(description) {
                        $description.text(description);
                        $label.after($description);
                    }).fail(function() {
                        // If string doesn't exist, use a generic message
                        Str.get_string('document_format_generic', 'local_conocer_cert').done(function(genericDesc) {
                            $description.text(genericDesc);
                            $label.after($description);
                        });
                    });
                    
                    // Add file type information
                    var allowedTypes = self.config.allowedFileTypes[docType] || [];
                    var $typeInfo = $('<small class="form-text text-muted"></small>');
                    
                    if (allowedTypes.includes('application/pdf')) {
                        $typeInfo.append('<span class="badge badge-info mr-1">PDF</span>');
                    }
                    if (allowedTypes.includes('image/jpeg') || allowedTypes.includes('image/png')) {
                        $typeInfo.append('<span class="badge badge-info mr-1">JPG/PNG</span>');
                    }
                    if (allowedTypes.includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
                        $typeInfo.append('<span class="badge badge-info mr-1">DOCX</span>');
                    }
                    
                    // Add file size information
                    var maxSize = (docType === 'fotografia') ? self.config.maxPhotoSizeMB : self.config.maxFileSizeMB;
                    $typeInfo.append('<span class="badge badge-secondary">' + maxSize + 'MB ' + M.util.get_string('maximum', 'moodle') + '</span>');
                    
                    $description.after($typeInfo);
                }
            });
        };
        
        /**
         * Validate and preview a file
         * @param {File} file - The file object
         * @param {string} documentType - Type of document
         * @param {jQuery} $previewArea - The preview area element
         */
        DocumentUploader.prototype.validateAndPreviewFile = function(file, documentType, $previewArea) {
            var self = this;
            var validationResult = this.validateFile(file, documentType);
            
            $previewArea.empty();
            
            if (!validationResult.valid) {
                // Show validation error
                $previewArea.html(
                    '<div class="alert alert-danger">' +
                    '<i class="fa fa-exclamation-circle"></i> ' +
                    validationResult.message +
                    '</div>'
                );
                
                // Clear the file input
                var $input = this.$container.find('input[name="' + documentType + '"]');
                $input.val('');
            } else {
                // Show file preview
                if (file.type.startsWith('image/')) {
                    this.createImagePreview(file, $previewArea);
                } else if (file.type === 'application/pdf') {
                    this.createPDFPreview(file, $previewArea);
                } else {
                    this.createGenericFilePreview(file, $previewArea);
                }
                
                // Add remove button
                var $removeButton = $('<button type="button" class="btn btn-sm btn-danger mt-2">' +
                                     '<i class="fa fa-times"></i> ' + M.util.get_string('remove', 'moodle') +
                                     '</button>');
                
                $removeButton.on('click', function() {
                    var $input = self.$container.find('input[name="' + documentType + '"]');
                    $input.val('');
                    $previewArea.empty();
                });
                
                $previewArea.append($removeButton);
            }
        };
        
        /**
         * Validate a file
         * @param {File} file - The file object
         * @param {string} documentType - Type of document
         * @return {object} Validation result with valid and message properties
         */
        DocumentUploader.prototype.validateFile = function(file, documentType) {
            // Check file type
            var allowedTypes = this.config.allowedFileTypes[documentType] || [];
            if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
                return {
                    valid: false,
                    message: M.util.get_string('error:invalidfiletype', 'local_conocer_cert', allowedTypes.join(', '))
                };
            }
            
            // Check file size
            var maxSizeMB = (documentType === 'fotografia') ? this.config.maxPhotoSizeMB : this.config.maxFileSizeMB;
            var maxSizeBytes = maxSizeMB * 1024 * 1024;
            
            if (file.size > maxSizeBytes) {
                return {
                    valid: false,
                    message: M.util.get_string('error:filetoobig', 'local_conocer_cert', maxSizeMB + 'MB')
                };
            }
            
            // Check file extension
            var extension = file.name.split('.').pop().toLowerCase();
            var blockedExtensions = ['exe', 'bat', 'com', 'cmd', 'scr', 'pif', 'js', 'vbs', 'ps1', 'msi', 'htaccess'];
            
            if (blockedExtensions.includes(extension)) {
                return {
                    valid: false,
                    message: M.util.get_string('error:blockedextension', 'local_conocer_cert')
                };
            }
            
            // Additional validations for specific document types
            if (documentType === 'fotografia' && file.type.startsWith('image/')) {
                return this.validateImage(file, true);
            } else if (documentType === 'id_oficial' && file.type.startsWith('image/')) {
                return this.validateImage(file, false);
            }
            
            return {
                valid: true,
                message: ''
            };
        };
        
        /**
         * Validate an image file
         * @param {File} file - The image file object
         * @param {boolean} isPhoto - Whether this is a photo (for stricter validation)
         * @return {object} Validation result with valid and message properties
         */
        DocumentUploader.prototype.validateImage = function(file, isPhoto) {
            var self = this;
            var result = {
                valid: true,
                message: ''
            };
            
            // Create an image object to check dimensions
            var img = new Image();
            var objectURL = URL.createObjectURL(file);
            
            img.onload = function() {
                URL.revokeObjectURL(objectURL);
                
                // Check minimum dimensions
                var minWidth = isPhoto ? 400 : 300;
                var minHeight = isPhoto ? 400 : 300;
                
                if (img.width < minWidth || img.height < minHeight) {
                    result.valid = false;
                    result.message = isPhoto ? 
                        M.util.get_string('error:phototoosmallorblurry', 'local_conocer_cert') :
                        M.util.get_string('error:idimagetoosmallorblurry', 'local_conocer_cert');
                }
                
                // Check maximum dimensions
                if (img.width > 4000 || img.height > 4000) {
                    result.valid = false;
                    result.message = M.util.get_string('error:imagetoobig', 'local_conocer_cert');
                }
                
                // Check photo aspect ratio if it's a photo
                if (isPhoto) {
                    var ratio = img.width / img.height;
                    if (ratio < 0.7 || ratio > 1.3) {
                        result.valid = false;
                        result.message = M.util.get_string('error:photowrongratio', 'local_conocer_cert');
                    }
                }
            };
            
            img.onerror = function() {
                URL.revokeObjectURL(objectURL);
                result.valid = false;
                result.message = M.util.get_string('error:invalidimage', 'local_conocer_cert');
            };
            
            img.src = objectURL;
            return result;
        };
        
        /**
         * Create an image preview
         * @param {File} file - The image file object
         * @param {jQuery} $previewArea - The preview area element
         */
        DocumentUploader.prototype.createImagePreview = function(file, $previewArea) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var $preview = $(
                    '<div class="image-preview">' +
                    '<img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 200px;" />' +
                    '</div>'
                );
                
                $previewArea.append($preview);
                
                // Add file info
                var fileSize = (file.size / 1024).toFixed(1) + ' KB';
                var $fileInfo = $(
                    '<div class="file-info small text-muted mt-1">' +
                    '<i class="fa fa-file-image-o"></i> ' +
                    file.name + ' (' + fileSize + ')' +
                    '</div>'
                );
                
                $previewArea.append($fileInfo);
            };
            
            reader.readAsDataURL(file);
        };
        
        /**
         * Create a PDF preview
         * @param {File} file - The PDF file object
         * @param {jQuery} $previewArea - The preview area element
         */
        DocumentUploader.prototype.createPDFPreview = function(file, $previewArea) {
            // Create PDF icon preview
            var $preview = $(
                '<div class="pdf-preview text-center py-3">' +
                '<i class="fa fa-file-pdf-o fa-4x text-danger"></i>' +
                '</div>'
            );
            
            $previewArea.append($preview);
            
            // Add file info
            var fileSize = (file.size / 1024).toFixed(1) + ' KB';
            var $fileInfo = $(
                '<div class="file-info small text-muted mt-1">' +
                '<i class="fa fa-file-pdf-o"></i> ' +
                file.name + ' (' + fileSize + ')' +
                '</div>'
            );
            
            $previewArea.append($fileInfo);
        };
        
        /**
         * Create a generic file preview
         * @param {File} file - The file object
         * @param {jQuery} $previewArea - The preview area element
         */
        DocumentUploader.prototype.createGenericFilePreview = function(file, $previewArea) {
            // Create file icon preview
            var $preview = $(
                '<div class="file-preview text-center py-3">' +
                '<i class="fa fa-file-o fa-4x text-primary"></i>' +
                '</div>'
            );
            
            $previewArea.append($preview);
            
            // Add file info
            var fileSize = (file.size / 1024).toFixed(1) + ' KB';
            var $fileInfo = $(
                '<div class="file-info small text-muted mt-1">' +
                '<i class="fa fa-file-o"></i> ' +
                file.name + ' (' + fileSize + ')' +
                '</div>'
            );
            
            $previewArea.append($fileInfo);
        };
        
        /**
         * Initialize form submission
         */
        DocumentUploader.prototype.initFormSubmit = function() {
            var self = this;
            
            this.$container.on('submit', function(e) {
                e.preventDefault();
                
                // Check if there are any files selected
                var hasFiles = false;
                self.$container.find('input[type="file"]').each(function() {
                    if (this.files.length > 0) {
                        hasFiles = true;
                    }
                });
                
                if (!hasFiles) {
                    Notification.alert(
                        M.util.get_string('error', 'moodle'),
                        M.util.get_string('no_files_selected', 'local_conocer_cert'),
                        M.util.get_string('ok', 'moodle')
                    );
                    return;
                }
                
                // Show upload progress
                self.showUploadProgress();
                
                // Submit form via AJAX
                var formData = new FormData(self.$container[0]);
                
                // Add request ID if provided in config
                if (self.config.requestId) {
                    formData.append('request_id', self.config.requestId);
                }
                
                $.ajax({
                    url: M.cfg.wwwroot + '/local/conocer_cert/ajax/upload_documents.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XML