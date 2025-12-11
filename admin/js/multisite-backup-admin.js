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
		
		// Handle create backup form submission
		$('#create-backup-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $submitBtn = $form.find('input[type="submit"]');
			var $spinner = $form.find('.spinner');
			
			// Validate form
			var selectedSites = $form.find('input[name="selected_sites[]"]:checked').length;
			if (selectedSites === 0) {
				alert('Please select at least one site to backup.');
				return false;
			}
			
			// Show loading state
			$spinner.addClass('is-active');
			$submitBtn.prop('disabled', true).val('Creating Backup...');
			
			// Prepare form data
			var formData = new FormData(this);
			formData.append('action', 'multisite_backup_create');
			
			// Submit via AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						// Show success message
						$('.multisite-backup-container').prepend(
							'<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>'
						);
						
						// Reset form
						$form[0].reset();
						$('#select-all-sites').prop('checked', false);
						
						// Refresh backup history if on that tab
						if ($('#backup-history').hasClass('active')) {
							location.reload();
						}
					} else {
						// Show error message
						$('.multisite-backup-container').prepend(
							'<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>'
						);
					}
				},
				error: function() {
					$('.multisite-backup-container').prepend(
						'<div class="notice notice-error is-dismissible"><p>An error occurred. Please try again.</p></div>'
					);
				},
				complete: function() {
					// Reset loading state
					$spinner.removeClass('is-active');
					$submitBtn.prop('disabled', false).val('Create Backup');
				}
			});
		});
		
		// General form submission with loading state (for other forms)
		$('form:not(#create-backup-form)').on('submit', function() {
			$(this).find('.spinner').addClass('is-active');
			$(this).find('input[type="submit"]').prop('disabled', true);
		});
	});

})( jQuery );
