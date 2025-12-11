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
				url: multisite_backup_ajax.ajax_url,
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
			// $(this).find('input[type="submit"]').prop('disabled', true);
		});
		
		// Import page functionality
		if ($('#import-backup-form').length) {
			// File upload preview with scanning
			$('#backup_file').on('change', function() {
				var file = this.files[0];
				if (file) {
					if (file.type !== 'application/zip' && !file.name.endsWith('.zip')) {
						Swal.fire({
							icon: 'warning',
							title: 'Invalid File Type',
							text: 'Please select a ZIP file.',
							confirmButtonColor: '#0073aa'
						});
						$(this).val('');
						return;
					}
					
					// Show initial file info and start scanning
					var fileSize = file.size;
					var fileName = file.name;
					
					$('#import-preview').show();
					$('#backup-info').html(`
						<div style="background: #f0f6fc; padding: 15px; border-radius: 5px;">
							<h4>📁 Selected File:</h4>
							<p><strong>Name:</strong> ${fileName}</p>
							<p><strong>Size:</strong> ${formatFileSize(fileSize)}</p>
							<p><strong>Type:</strong> ZIP Archive</p>
							<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 3px;">
								<div class="scanning-indicator">
									<span class="spinner is-active" style="float: left; margin-right: 10px;"></span>
									<strong>🔍 Scanning backup file...</strong>
									<p style="margin: 5px 0 0 0; font-size: 14px;">Analyzing backup contents and validating format</p>
								</div>
							</div>
						</div>
					`);
					
					// Start scanning the backup file
					scanBackupFile(file);
				} else {
					$('#import-preview').hide();
				}
			});
			
			// Import form submission
			$('#import-backup-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var file = $('#backup_file')[0].files[0];
				
				if (!file) {
					Swal.fire({
						icon: 'warning',
						title: 'No File Selected',
						text: 'Please select a backup file to import.',
						confirmButtonColor: '#0073aa'
					});
					return;
				}
				
				// Get scan results
				var scanResults = $form.data('scan-results');
				
				// Check if file was scanned
				if (!scanResults) {
					Swal.fire({
						icon: 'warning',
						title: 'File Not Scanned',
						text: 'Please wait for the file scan to complete before importing.',
						confirmButtonColor: '#0073aa'
					});
					return;
				}
				
				// Ensure scanResults has required properties with defaults
				scanResults.backup_type = scanResults.backup_type || 'unknown';
				scanResults.components = scanResults.components || [];
				scanResults.warnings = scanResults.warnings || [];
				scanResults.errors = scanResults.errors || [];
				
				// Check if backup format is valid
				if (!scanResults.format_valid) {
					Swal.fire({
						icon: 'error',
						title: 'Invalid Backup Format',
						html: `
							<div style="text-align: left;">
								<p>The selected file does not appear to be a valid backup:</p>
								<ul style="padding-left: 20px; margin: 10px 0;">
									${scanResults.errors.map(error => '<li>' + error + '</li>').join('')}
								</ul>
								<p style="margin-top: 15px;">Please select a valid backup file created by this plugin.</p>
							</div>
						`,
						confirmButtonColor: '#d33'
					});
					return;
				}
				
				// Show import mode selection popup first
				showImportModeSelection(file, scanResults, $form);
			});
		}
		
		// Function to scan backup file
		function scanBackupFile(file) {
			var formData = new FormData();
			formData.append('action', 'multisite_backup_scan');
			formData.append('backup_file', file);
			formData.append('scan_nonce', $('#scan_nonce').val());
			
			$.ajax({
				url: multisite_backup_ajax.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						displayScanResults(response.data);
					} else {
						displayScanError(response.data.message);
					}
				},
				error: function() {
					displayScanError('Failed to scan backup file. Please try again.');
				}
			});
		}
		
		// Function to display scan results
		function displayScanResults(scanData) {
			// Ensure scanData has required properties with defaults
			scanData = scanData || {};
			scanData.backup_type = scanData.backup_type || 'unknown';
			scanData.components = scanData.components || [];
			scanData.warnings = scanData.warnings || [];
			scanData.errors = scanData.errors || [];
			
			var statusIcon = scanData.format_valid ? '✅' : '❌';
			var statusText = scanData.format_valid ? 'Valid Backup Format' : 'Invalid Backup Format';
			var statusColor = scanData.format_valid ? '#d4edda' : '#f8d7da';
			
			var componentsHtml = '';
			if (scanData.components.length > 0) {
				componentsHtml = `
					<h5 style="margin: 15px 0 5px 0;">📦 Backup Components:</h5>
					<ul style="margin: 5px 0; padding-left: 20px;">
						${scanData.components.map(comp => '<li>' + comp + '</li>').join('')}
					</ul>
				`;
			}
			
			var warningsHtml = '';
			if (scanData.warnings.length > 0) {
				warningsHtml = `
					<div style="background: #fff3cd; padding: 10px; border-radius: 3px; margin-top: 10px;">
						<h5 style="margin: 0 0 5px 0; color: #856404;">⚠️ Warnings:</h5>
						<ul style="margin: 5px 0; padding-left: 20px; font-size: 14px;">
							${scanData.warnings.map(warning => '<li>' + warning + '</li>').join('')}
						</ul>
					</div>
				`;
			}
			
			var errorsHtml = '';
			if (scanData.errors.length > 0) {
				errorsHtml = `
					<div style="background: #f8d7da; padding: 10px; border-radius: 3px; margin-top: 10px;">
						<h5 style="margin: 0 0 5px 0; color: #721c24;">❌ Errors:</h5>
						<ul style="margin: 5px 0; padding-left: 20px; font-size: 14px;">
							${scanData.errors.map(error => '<li>' + error + '</li>').join('')}
						</ul>
					</div>
				`;
			}
			
			var backupInfoHtml = '';
			if (scanData.backup_date || scanData.wordpress_version || scanData.sites_count > 0 || scanData.backup_type) {
				var backupTypeText = scanData.backup_type ? scanData.backup_type.charAt(0).toUpperCase() + scanData.backup_type.slice(1) : 'Unknown';
				
				backupInfoHtml = `
					<h5 style="margin: 15px 0 5px 0;">ℹ️ Backup Information:</h5>
					<ul style="margin: 5px 0; padding-left: 20px;">
						${scanData.backup_date ? '<li><strong>Created:</strong> ' + scanData.backup_date + '</li>' : ''}
						${scanData.wordpress_version ? '<li><strong>WordPress Version:</strong> ' + scanData.wordpress_version + '</li>' : ''}
						${scanData.sites_count > 0 ? '<li><strong>Sites Included:</strong> ' + scanData.sites_count + '</li>' : ''}
						<li><strong>Backup Type:</strong> ${backupTypeText}</li>
					</ul>
				`;
			}
			
			$('#backup-info').html(`
				<div style="background: #f0f6fc; padding: 15px; border-radius: 5px;">
					<h4>📁 Selected File:</h4>
					<p><strong>Name:</strong> ${scanData.filename}</p>
					<p><strong>Size:</strong> ${formatFileSize(scanData.size)}</p>
					<p><strong>Type:</strong> ZIP Archive</p>
					
					<div style="background: ${statusColor}; padding: 10px; border-radius: 3px; margin-top: 15px;">
						<h5 style="margin: 0; color: ${scanData.format_valid ? '#155724' : '#721c24'};">${statusIcon} ${statusText}</h5>
					</div>
					
					${componentsHtml}
					${backupInfoHtml}
					${warningsHtml}
					${errorsHtml}
				</div>
			`);
			
			// Store scan results for import confirmation
			$('#import-backup-form').data('scan-results', scanData);
		}
		
		// Function to display scan error
		function displayScanError(errorMessage) {
			$('#backup-info').html(`
				<div style="background: #f8d7da; padding: 15px; border-radius: 5px;">
					<h4 style="color: #721c24;">❌ Scan Failed</h4>
					<p><strong>Error:</strong> ${errorMessage}</p>
					<p style="font-size: 14px; margin-top: 10px;">
						Please ensure the file is a valid ZIP backup created by this plugin or a compatible backup tool.
					</p>
				</div>
			`);
		}

		// Function to show import mode selection
		function showImportModeSelection(file, scanResults, $form) {
			// Build scan results summary for display
			var scanSummaryHtml = '';
			if (scanResults.components.length > 0) {
				scanSummaryHtml += `
					<h4 style="margin: 15px 0 5px 0; color: #0073aa;">📦 What will be imported:</h4>
					<ul style="padding-left: 20px; margin: 5px 0;">
						${scanResults.components.map(comp => '<li>' + comp + '</li>').join('')}
					</ul>
				`;
			}
			
			if (scanResults.backup_date || scanResults.wordpress_version) {
				scanSummaryHtml += `
					<h4 style="margin: 15px 0 5px 0; color: #0073aa;">ℹ️ Backup Details:</h4>
					<ul style="padding-left: 20px; margin: 5px 0;">
						${scanResults.backup_date ? '<li><strong>Created:</strong> ' + scanResults.backup_date + '</li>' : ''}
						${scanResults.wordpress_version ? '<li><strong>WordPress Version:</strong> ' + scanResults.wordpress_version + '</li>' : ''}
						${scanResults.sites_count > 0 ? '<li><strong>Sites:</strong> ' + scanResults.sites_count + '</li>' : ''}
					</ul>
				`;
			}
			
			var warningsHtml = '';
			if (scanResults.warnings.length > 0) {
				warningsHtml = `
					<div style="background: #fff3cd; padding: 10px; border-radius: 3px; margin-top: 15px;">
						<strong>⚠️ Warnings:</strong>
						<ul style="margin: 5px 0; padding-left: 20px; font-size: 14px;">
							${scanResults.warnings.map(warning => '<li>' + warning + '</li>').join('')}
						</ul>
					</div>
				`;
			}
			
			Swal.fire({
				title: 'Select Import Mode',
				html: `
					<div style="text-align: left; margin: 20px 0;">
						<h4 style="margin-bottom: 10px; color: #0073aa;">📁 File: ${file.name}</h4>
						<p><strong>Size:</strong> ${formatFileSize(file.size)}</p>
						<p><strong>Type:</strong> ${scanResults.backup_type ? scanResults.backup_type.charAt(0).toUpperCase() + scanResults.backup_type.slice(1) : 'Unknown'} Backup</p>
						
						${scanSummaryHtml}
						${warningsHtml}
						
						<h4 style="margin: 20px 0 10px 0; color: #0073aa;">⚙️ Choose Import Mode:</h4>
						<div style="margin: 15px 0;">
							<label class="import-mode-option selected">
								<input type="radio" name="import_mode_popup" value="merge" checked>
								<strong>🔄 Merge with existing data</strong>
								<small>Add imported content alongside existing content. Safest option.</small>
							</label>
							<label class="import-mode-option">
								<input type="radio" name="import_mode_popup" value="replace">
								<strong>⚠️ Replace existing data</strong>
								<small>Overwrite existing content with imported content. Use with caution.</small>
							</label>
						</div>
					</div>
				`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#0073aa',
				cancelButtonColor: '#6c757d',
				confirmButtonText: '➡️ Continue to Confirmation',
				cancelButtonText: '❌ Cancel',
				width: '700px',
				didOpen: () => {
					// Add click handlers for radio button labels
					const labels = document.querySelectorAll('.import-mode-option');
					labels.forEach(label => {
						label.addEventListener('click', function() {
							// Update selected styling
							labels.forEach(l => l.classList.remove('selected'));
							this.classList.add('selected');
							
							// Check the radio button
							const radio = this.querySelector('input[type="radio"]');
							if (radio) {
								radio.checked = true;
							}
						});
					});
				}
			}).then((result) => {
				if (result.isConfirmed) {
					// Get selected import mode
					const selectedMode = document.querySelector('input[name="import_mode_popup"]:checked').value;
					showImportConfirmation(file, scanResults, selectedMode, $form);
				}
			});
		}
		
		// Function to show final import confirmation
		function showImportConfirmation(file, scanResults, importMode, $form) {
			const importModeTexts = {
				'merge': 'Merge with existing data',
				'replace': 'Replace existing data'
			};
			
			const importModeText = importModeTexts[importMode] || 'Unknown';
			
			// Build scan results summary for confirmation
			var scanSummaryHtml = '';
			if (scanResults.components.length > 0) {
				scanSummaryHtml += `
					<h4 style="margin: 15px 0 5px 0; color: #0073aa;">📦 What will be imported:</h4>
					<ul style="padding-left: 20px; margin: 5px 0;">
						${scanResults.components.map(comp => '<li>' + comp + '</li>').join('')}
					</ul>
				`;
			}
			
			if (scanResults.backup_date || scanResults.wordpress_version) {
				scanSummaryHtml += `
					<h4 style="margin: 15px 0 5px 0; color: #0073aa;">ℹ️ Backup Details:</h4>
					<ul style="padding-left: 20px; margin: 5px 0;">
						${scanResults.backup_date ? '<li><strong>Created:</strong> ' + scanResults.backup_date + '</li>' : ''}
						${scanResults.wordpress_version ? '<li><strong>WordPress Version:</strong> ' + scanResults.wordpress_version + '</li>' : ''}
						${scanResults.sites_count > 0 ? '<li><strong>Sites:</strong> ' + scanResults.sites_count + '</li>' : ''}
					</ul>
				`;
			}
			
			var warningsHtml = '';
			if (scanResults.warnings.length > 0) {
				warningsHtml = `
					<div style="background: #fff3cd; padding: 10px; border-radius: 3px; margin-top: 15px;">
						<strong>⚠️ Warnings:</strong>
						<ul style="margin: 5px 0; padding-left: 20px; font-size: 14px;">
							${scanResults.warnings.map(warning => '<li>' + warning + '</li>').join('')}
						</ul>
					</div>
				`;
			}
			
			Swal.fire({
				title: 'Confirm Backup Import',
				html: `
					<div style="text-align: left; margin: 20px 0;">
						<h4 style="margin-bottom: 10px; color: #0073aa;">📁 File: ${file.name}</h4>
						<p><strong>Size:</strong> ${formatFileSize(file.size)}</p>
						<p><strong>Type:</strong> ${scanResults.backup_type ? scanResults.backup_type.charAt(0).toUpperCase() + scanResults.backup_type.slice(1) : 'Unknown'} Backup</p>
						
						<h4 style="margin: 20px 0 10px 0; color: #0073aa;">⚙️ Import Mode: ${importModeText}</h4>
						
						${scanSummaryHtml}
						${warningsHtml}
						
						<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 20px;">
							<strong>⚠️ Important Warning:</strong>
							<ul style="margin: 10px 0; padding-left: 20px;">
								<li>This will modify your current WordPress installation</li>
								<li>Make sure you have a backup of your current site</li>
								<li>The import process cannot be undone</li>
								<li>Large files may take several minutes to process</li>
							</ul>
						</div>
					</div>
				`,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#0073aa',
				confirmButtonText: '⚠️ Import Backup',
				cancelButtonText: '❌ Cancel',
				width: '700px'
			}).then((result) => {
				if (result.isConfirmed) {
					// Store the selected import mode for the import process
					$form.data('selected-import-mode', importMode);
					startImportProcess($form);
				}
			});
		}

		// Function to start import process
		function startImportProcess($form) {
			// Show progress popup
			Swal.fire({
				title: 'Importing Backup',
				html: `
					<div style="text-align: center; padding: 20px;">
						<div class="backup-progress-container">
							<div class="backup-progress-bar">
								<div class="backup-progress-fill" id="import-progress-fill"></div>
							</div>
							<div id="import-progress-text" style="margin-top: 15px; font-weight: bold;">Uploading backup file...</div>
							<div id="import-progress-details" style="margin-top: 10px; color: #666; font-size: 14px;">Please wait while we process your backup</div>
						</div>
					</div>
				`,
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false,
				didOpen: () => {
					simulateImportProgress();
				}
			});
			
			// Prepare form data
			var formData = new FormData($form[0]);
			formData.append('action', 'multisite_backup_import');
			
			// Add the selected import mode from the popup
			var selectedImportMode = $form.data('selected-import-mode') || 'merge';
			formData.append('import_mode', selectedImportMode);
			
			// Submit via AJAX
			$.ajax({
				url: multisite_backup_ajax.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						Swal.fire({
							icon: 'success',
							title: 'Import Completed Successfully!',
							html: `
								<div style="text-align: center; padding: 20px;">
									<p style="font-size: 16px; margin-bottom: 20px;">${response.data.message}</p>
									<div style="background: #f0f6fc; padding: 15px; border-radius: 5px;">
										<strong>✅ Import completed successfully</strong><br>
										<small>Your backup has been imported into the system</small>
									</div>
								</div>
							`,
							confirmButtonColor: '#0073aa'
						}).then(() => {
							// Reset form
							$form[0].reset();
							$('#import-preview').hide();
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Import Failed',
							text: response.data.message,
							confirmButtonColor: '#d33'
						});
					}
				},
				error: function() {
					Swal.fire({
						icon: 'error',
						title: 'Import Failed',
						text: 'An unexpected error occurred during import. Please try again.',
						confirmButtonColor: '#d33'
					});
				}
			});
		}
		
		// Function to simulate import progress
		function simulateImportProgress() {
			var progressSteps = [
				{ percent: 15, text: 'Uploading backup file...', details: 'Transferring file to server' },
				{ percent: 30, text: 'Validating backup...', details: 'Checking file integrity and format' },
				{ percent: 45, text: 'Extracting archive...', details: 'Uncompressing backup contents' },
				{ percent: 60, text: 'Importing database...', details: 'Restoring database tables and data' },
				{ percent: 75, text: 'Importing files...', details: 'Copying themes, plugins, and media' },
				{ percent: 90, text: 'Updating configurations...', details: 'Applying settings and permissions' },
				{ percent: 100, text: 'Finalizing import...', details: 'Cleaning up and completing process' }
			];
			
			var currentStep = 0;
			
			var progressInterval = setInterval(function() {
				if (currentStep < progressSteps.length) {
					var step = progressSteps[currentStep];
					
					$('#import-progress-fill').css('width', step.percent + '%');
					$('#import-progress-text').text(step.text);
					$('#import-progress-details').text(step.details);
					
					currentStep++;
				} else {
					clearInterval(progressInterval);
				}
			}, 1000); // Update every 1 second for import (slower process)
		}
		
		// Helper function to format file size
		function formatFileSize(bytes) {
			if (bytes === 0) return '0 Bytes';
			var k = 1024;
			var sizes = ['Bytes', 'KB', 'MB', 'GB'];
			var i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
		}

	});
})( jQuery );