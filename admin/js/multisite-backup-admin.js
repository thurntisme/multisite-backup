(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 */

	$(document).ready(function() {
		// Tab switching with URL updates
		$('.nav-tab').on('click', function(e) {
			e.preventDefault();
			var target = $(this).data('tab');
			
			// Update URL with tab parameter
			var url = new URL(window.location);
			url.searchParams.set('tab', target);
			window.history.pushState({}, '', url);
			
			// Switch tabs
			switchToTab(target);
		});
		
		// Function to switch tabs
		function switchToTab(target) {
			$('.nav-tab').removeClass('nav-tab-active');
			$('.nav-tab[data-tab="' + target + '"]').addClass('nav-tab-active');
			
			$('.tab-content').removeClass('active');
			$('#' + target).addClass('active');
			
			// Update page title
			var tabTitle = $('.nav-tab[data-tab="' + target + '"]').text();
			document.title = 'Multisite Backup - ' + tabTitle;
		}
		
		// Handle browser back/forward buttons
		window.addEventListener('popstate', function(e) {
			var url = new URL(window.location);
			var tab = url.searchParams.get('tab') || 'backup-create';
			switchToTab(tab);
		});
		
		// Initialize tab on page load (sync with server-side state)
		$(document).ready(function() {
			// The active tab is already set by PHP, just ensure JavaScript state matches
			var activeTab = $('.nav-tab-active').data('tab') || 'backup-create';
			
			// Update page title
			var tabTitle = $('.nav-tab-active').text();
			if (tabTitle) {
				document.title = 'Multisite Backup - ' + tabTitle;
			}
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
			
			// Validate form
			var selectedSites = $form.find('input[name="selected_sites[]"]:checked');
			if (selectedSites.length === 0) {
				Swal.fire({
					icon: 'warning',
					title: 'No Sites Selected',
					text: 'Please select at least one site to backup.',
					confirmButtonColor: '#0073aa'
				});
				return false;
			}
			
			// Get form data for confirmation
			var backupType = $form.find('#backup_type').val();
			var backupTypeText = $form.find('#backup_type option:selected').text();
			
			// Build sites list for confirmation
			var sitesList = [];
			selectedSites.each(function() {
				var siteItem = $(this).closest('.site-item');
				var siteName = siteItem.find('.site-info strong').text();
				var siteUrl = siteItem.find('.site-url').text();
				sitesList.push('<li><strong>' + siteName + '</strong><br><small>' + siteUrl + '</small></li>');
			});
			
			// Build export details based on backup type
			var exportDetails = [];
			if (backupType === 'full' || backupType === 'database') {
				exportDetails.push('📊 Database tables and content');
				exportDetails.push('👥 User accounts and permissions');
				exportDetails.push('⚙️ Site settings and options');
			}
			if (backupType === 'full' || backupType === 'files') {
				exportDetails.push('🎨 Themes and customizations');
				exportDetails.push('🔌 Plugins and configurations');
				exportDetails.push('📁 Media files and uploads');
				exportDetails.push('📄 Configuration files (sanitized)');
			}
			
			// Show confirmation popup
			Swal.fire({
				title: 'Confirm Backup Export',
				html: `
					<div style="text-align: left; margin: 20px 0;">
						<h4 style="margin-bottom: 10px; color: #0073aa;">📦 Backup Type: ${backupTypeText}</h4>
						
						<h4 style="margin: 20px 0 10px 0; color: #0073aa;">🌐 Sites to Export (${selectedSites.length}):</h4>
						<ul style="max-height: 150px; overflow-y: auto; padding-left: 20px; margin: 10px 0;">
							${sitesList.join('')}
						</ul>
						
						<h4 style="margin: 20px 0 10px 0; color: #0073aa;">📋 What will be exported:</h4>
						<ul style="padding-left: 20px; margin: 10px 0;">
							${exportDetails.map(item => '<li>' + item + '</li>').join('')}
						</ul>
						
						<div style="background: #f0f6fc; padding: 15px; border-radius: 5px; margin-top: 20px;">
							<strong>⚠️ Important Notes:</strong>
							<ul style="margin: 10px 0; padding-left: 20px;">
								<li>Sensitive data (passwords, keys) will be removed from config files</li>
								<li>Large files (>100MB) and temporary files will be skipped</li>
								<li>The backup process may take several minutes depending on site size</li>
							</ul>
						</div>
					</div>
				`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#0073aa',
				cancelButtonColor: '#d33',
				confirmButtonText: '✅ Start Backup',
				cancelButtonText: '❌ Cancel',
				width: '600px',
				customClass: {
					popup: 'backup-confirmation-popup'
				}
			}).then((result) => {
				if (result.isConfirmed) {
					// Start the backup process
					startBackupProcess($form);
				}
			});
		});
		
		// Function to start backup process with progress
		function startBackupProcess($form) {
			// Prepare form data
			var formData = new FormData($form[0]);
			formData.append('action', 'multisite_backup_create');
			
			// Show progress popup
			Swal.fire({
				title: 'Creating Backup',
				html: `
					<div style="text-align: center; padding: 20px;">
						<div class="backup-progress-container">
							<div class="backup-progress-bar">
								<div class="backup-progress-fill" id="progress-fill"></div>
							</div>
							<div id="progress-text" style="margin-top: 15px; font-weight: bold;">Initializing backup...</div>
							<div id="progress-details" style="margin-top: 10px; color: #666; font-size: 14px;">Please wait while we prepare your backup</div>
						</div>
					</div>
				`,
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false,
				didOpen: () => {
					// Start progress simulation with backup type
					var backupType = $form.find('#backup_type').val();
					simulateBackupProgress(backupType);
				}
			});
			
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
						Swal.fire({
							icon: 'success',
							title: 'Backup Created Successfully!',
							html: `
								<div style="text-align: center; padding: 20px;">
									<p style="font-size: 16px; margin-bottom: 20px;">${response.data.message}</p>
									<div style="background: #f0f6fc; padding: 15px; border-radius: 5px;">
										<strong>✅ Backup completed successfully</strong><br>
										<small>You can find your backup in the Backup History tab</small>
									</div>
								</div>
							`,
							confirmButtonColor: '#0073aa',
							confirmButtonText: 'View Backup History'
						}).then(() => {
							// Reset form and redirect to history tab
							$form[0].reset();
							$('#select-all-sites').prop('checked', false);
							
							// Redirect to backup history tab
							var historyUrl = $('.nav-tab[data-tab="backup-history"]').attr('href');
							window.location.href = historyUrl;
						});
					} else {
						// Show error message
						Swal.fire({
							icon: 'error',
							title: 'Backup Failed',
							text: response.data.message,
							confirmButtonColor: '#d33'
						});
					}
				},
				error: function() {
					Swal.fire({
						icon: 'error',
						title: 'Backup Failed',
						text: 'An unexpected error occurred. Please try again.',
						confirmButtonColor: '#d33'
					});
				}
			});
		}
		
		// Function to simulate backup progress
		function simulateBackupProgress(backupType) {
			var progress = 0;
			var progressSteps = [];
			
			// Define progress steps based on backup type
			switch(backupType) {
				case 'full':
					progressSteps = [
						{ percent: 8, text: 'Preparing backup directory...', details: 'Creating temporary folders and validating permissions' },
						{ percent: 20, text: 'Exporting database...', details: 'Backing up tables and user data' },
						{ percent: 35, text: 'Copying theme files...', details: 'Backing up active and installed themes' },
						{ percent: 50, text: 'Copying plugin files...', details: 'Backing up plugins and configurations' },
						{ percent: 70, text: 'Copying media files...', details: 'Backing up uploads and media library' },
						{ percent: 85, text: 'Copying configuration files...', details: 'Backing up wp-config and other settings' },
						{ percent: 95, text: 'Creating archive...', details: 'Compressing files into ZIP archive' },
						{ percent: 100, text: 'Finalizing backup...', details: 'Cleaning up and saving metadata' }
					];
					break;
					
				case 'database':
					progressSteps = [
						{ percent: 15, text: 'Preparing backup directory...', details: 'Creating temporary folders and validating permissions' },
						{ percent: 35, text: 'Exporting site databases...', details: 'Backing up site-specific tables and content' },
						{ percent: 55, text: 'Exporting user data...', details: 'Backing up users and user metadata' },
						{ percent: 75, text: 'Creating backup info...', details: 'Generating database metadata and structure info' },
						{ percent: 90, text: 'Creating archive...', details: 'Compressing database files into ZIP archive' },
						{ percent: 100, text: 'Finalizing backup...', details: 'Cleaning up and saving metadata' }
					];
					break;
					
				case 'files':
					progressSteps = [
						{ percent: 10, text: 'Preparing backup directory...', details: 'Creating temporary folders and validating permissions' },
						{ percent: 25, text: 'Copying theme files...', details: 'Backing up active and installed themes' },
						{ percent: 45, text: 'Copying plugin files...', details: 'Backing up plugins and configurations' },
						{ percent: 70, text: 'Copying media files...', details: 'Backing up uploads and media library' },
						{ percent: 85, text: 'Copying configuration files...', details: 'Backing up wp-config and other settings' },
						{ percent: 95, text: 'Creating archive...', details: 'Compressing files into ZIP archive' },
						{ percent: 100, text: 'Finalizing backup...', details: 'Cleaning up and saving metadata' }
					];
					break;
					
				default:
					// Fallback to full backup steps
					progressSteps = [
						{ percent: 10, text: 'Preparing backup directory...', details: 'Creating temporary folders and validating permissions' },
						{ percent: 25, text: 'Processing backup...', details: 'Backing up selected components' },
						{ percent: 50, text: 'Copying files...', details: 'Backing up themes, plugins, and media' },
						{ percent: 75, text: 'Finalizing data...', details: 'Completing backup process' },
						{ percent: 95, text: 'Creating archive...', details: 'Compressing files into ZIP archive' },
						{ percent: 100, text: 'Backup complete...', details: 'Cleaning up and saving metadata' }
					];
			}
			
			var currentStep = 0;
			
			var progressInterval = setInterval(function() {
				if (currentStep < progressSteps.length) {
					var step = progressSteps[currentStep];
					progress = step.percent;
					
					$('#progress-fill').css('width', progress + '%');
					$('#progress-text').text(step.text);
					$('#progress-details').text(step.details);
					
					currentStep++;
				} else {
					clearInterval(progressInterval);
				}
			}, 800); // Update every 800ms
		}
		
		// General form submission with loading state (for other forms)
		$('form:not(#create-backup-form)').on('submit', function() {
			$(this).find('.spinner').addClass('is-active');
			$(this).find('input[type="submit"]').prop('disabled', true);
		});
	});

})( jQuery );
