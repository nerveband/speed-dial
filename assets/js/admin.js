/**
 * Speed Dial Admin JavaScript
 */

jQuery(document).ready(function($) {
	'use strict';
	
	// Number input validation
	$('#number').on('input', function() {
		// Remove non-digits
		var value = $(this).val().replace(/\D/g, '');
		$(this).val(value);
		
		// Check max length
		var maxDigits = SDAdmin.maxDigits || 16;
		if (value.length > maxDigits) {
			$(this).val(value.substr(0, maxDigits));
		}
	});
	
	// URL normalization hint
	$('#url').on('blur', function() {
		var url = $(this).val();
		if (url && !url.match(/^https?:\/\//i)) {
			$(this).next('.description').html('<strong>Note:</strong> URL will be normalized to https://' + url);
		}
	});
	
	// Bulk action confirmation
	$('#doaction, #doaction2').on('click', function(e) {
		var action = $(this).prev('select').val();
		
		if (action === 'delete') {
			var checked = $('input[name="numbers[]"]:checked').length;
			if (checked > 0) {
				if (!confirm(SDAdmin.i18n.confirmBulkDelete)) {
					e.preventDefault();
					return false;
				}
			} else {
				alert(SDAdmin.i18n.noSelection);
				e.preventDefault();
				return false;
			}
		}
	});
	
	// CSV file validation
	$('#csv_file').on('change', function() {
		var file = this.files[0];
		if (file) {
			// Check file extension
			var ext = file.name.split('.').pop().toLowerCase();
			if (ext !== 'csv') {
				alert(SDAdmin.i18n.invalidFile);
				$(this).val('');
				return false;
			}
			
			// Check file size (max 5MB)
			if (file.size > 5 * 1024 * 1024) {
				alert('File size must be less than 5MB');
				$(this).val('');
				return false;
			}
		}
	});
	
	// Settings page enhancements
	if ($('#sd_auto_redirect').length) {
		// Show/hide redirect delay based on auto redirect setting
		function toggleRedirectDelay() {
			if ($('#sd_auto_redirect').is(':checked')) {
				$('#sd_redirect_delay_ms').closest('tr').show();
			} else {
				$('#sd_redirect_delay_ms').closest('tr').hide();
			}
		}
		
		toggleRedirectDelay();
		$('#sd_auto_redirect').on('change', toggleRedirectDelay);
	}
	
	// Copy to clipboard for shortcode
	$('.sd-copy-shortcode').on('click', function() {
		var shortcode = '[speed-dial]';
		
		// Create temporary input
		var $temp = $('<input>');
		$('body').append($temp);
		$temp.val(shortcode).select();
		document.execCommand('copy');
		$temp.remove();
		
		// Show feedback
		$(this).text('Copied!');
		setTimeout(() => {
			$(this).text('Copy Shortcode');
		}, 2000);
	});
	
	// Theme preview
	$('#sd_theme').on('change', function() {
		var theme = $(this).val();
		// Could load a preview here if needed
		console.log('Theme changed to:', theme);
	});
	
	// Search box enhancement
	$('#sd-search-input').on('keypress', function(e) {
		if (e.which === 13) {
			$('#search-submit').click();
		}
	});
	
	// Auto-dismiss notices after 5 seconds
	setTimeout(function() {
		$('.notice.is-dismissible:not(.notice-error)').fadeOut('slow');
	}, 5000);
});