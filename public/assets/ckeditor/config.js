/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	
        config.extraPlugins = '';
        config.extraPlugins += (config.extraPlugins.length == 0 ? '' : ',') + 'ckeditor_wiris';
        config.allowedContent = true;
		
        config.language = 'en';
	config.uiColor = '';
	config.height = 100;
	config.toolbarCanCollapse = true;
	
        config.filebrowserBrowseUrl = '../../assets/ckfinder/ckfinder.html';
        config.filebrowserImageBrowseUrl = '../../assets/ckfinder/ckfinder.html?type=Images';
        config.filebrowserFlashBrowseUrl = '../../assets/ckfinder/ckfinder.html?type=Flash';
        config.filebrowserUploadUrl = '../../assets/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
        config.filebrowserImageUploadUrl = '../../assets/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
        config.filebrowserFlashUploadUrl = '../../assets/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
};
