(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 */

	$(document).ready(function() {
		// Tab switching
		$('.nav-tab').on('click', function(e) {
			e.preventDefault();
			var target = $(this).data('tab');
			
			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			
			$('.tab-content').removeClass('active');
			$('#' + target).addClass('active');
		});
		
		// Select all sites checkbox
		$('#select-all-sites').on('change', function() {
			$('.site-checkbox').prop('checked', $(this).prop('checked'));
		});
		
		// Individual site checkbox
		$('.site-checkbox').on('change', function() {
			var totalCheckboxes = $('.site-checkbox').length;
			var checkedCheckboxes = $('.site-checkbox:checked').length;
			
			$('#select-all-sites').prop('checked', totalCheckboxes === checkedCheckboxes);
		});
		
		// Delete backup
		$('.button-link-delete').on('click', function(e) {
			e.preventDefault();
			if (confirm('Are you sure you want to delete this backup?')) {
				var backupId = $(this).data('backup-id');
				// Implement delete functionality
				console.log('Delete backup:', backupId);
			}
		});
		
		// Form submission with loading state
		$('form').on('submit', function() {
			$(this).find('.spinner').addClass('is-active');
			$(this).find('input[type="submit"]').prop('disabled', true);
		});
	});

})( jQuery );
