<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://thurntisme.com
 * @since      1.0.0
 *
 * @package    Multisite_Backup
 * @subpackage Multisite_Backup/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Multisite_Backup
 * @subpackage Multisite_Backup/admin
 * @author     Thủy Nguyễn Thế <nguyenthethuy.qnam@gmail.com>
 */
class Multisite_Backup_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		// Only enqueue styles on multisite main site
		if (!is_multisite() || !is_main_site()) {
			return;
		}

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Multisite_Backup_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Multisite_Backup_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/multisite-backup-admin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		// Only enqueue scripts on multisite main site
		if (!is_multisite() || !is_main_site()) {
			return;
		}

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Multisite_Backup_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Multisite_Backup_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// Enqueue SweetAlert2
		wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', false);

		// Enqueue plugin script
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/multisite-backup-admin.js', array('jquery', 'sweetalert2'), $this->version, false);

		// Localize script for AJAX
		wp_localize_script($this->plugin_name, 'multisite_backup_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('multisite_backup_nonce')
		));

	}

	public function register_multisite_backup_menu()
	{
		// Only show menu if site is in multisite mode and this is the main site
		if (!is_multisite() || !is_main_site()) {
			// Show admin notice if on multisite but not main site
			if (is_multisite() && !is_main_site()) {
				add_action('admin_notices', array($this, 'show_main_site_notice'));
			}
			return;
		}

		// Main menu page
		add_menu_page(
			'Multisite Backup',
			'Multisite Backup',
			'manage_options',
			'multisite-backup',
			array($this, 'multisite_backup_export_page_render'),
			'dashicons-database',
			6
		);

		// Export submenu (default)
		add_submenu_page(
			'multisite-backup',
			'Export Backup',
			'Export',
			'manage_options',
			'multisite-backup',
			array($this, 'multisite_backup_export_page_render')
		);

		// Import submenu
		add_submenu_page(
			'multisite-backup',
			'Import Backup',
			'Import',
			'manage_options',
			'multisite-backup-import',
			array($this, 'multisite_backup_import_page_render')
		);
	}

	public function multisite_backup_export_page_render()
	{
		// Security check: Only allow access on multisite main site
		if (!is_multisite() || !is_main_site()) {
			wp_die(__('Access denied. This feature is only available on the main site of a multisite network.'));
		}

		include_once 'partials/multisite-backup-admin-display.php';
	}

	public function multisite_backup_import_page_render()
	{
		// Security check: Only allow access on multisite main site
		if (!is_multisite() || !is_main_site()) {
			wp_die(__('Access denied. This feature is only available on the main site of a multisite network.'));
		}

		include_once 'partials/multisite-backup-import-display.php';
	}

	/**
	 * Show admin notice for non-main sites
	 */
	public function show_main_site_notice()
	{
		// Only show on plugin-related pages or if user has manage_options capability
		if (!current_user_can('manage_options')) {
			return;
		}

		$main_site_url = network_site_url();
		$main_site_admin_url = network_admin_url('admin.php?page=multisite-backup');

		echo '<div class="notice notice-info is-dismissible">';
		echo '<p><strong>Multisite Backup:</strong> This plugin is only available on the main site of your multisite network.</p>';
		echo '<p><a href="' . esc_url($main_site_admin_url) . '" class="button button-primary">Go to Main Site Backup</a></p>';
		echo '</div>';
	}

	/**
	 * Handle AJAX request to get sites list
	 */
	public function handle_get_sites()
	{
		// Check multisite and main site requirements
		if (!is_multisite() || !is_main_site()) {
			wp_send_json_error(['message' => 'Access denied. This feature is only available on the main site of a multisite network.']);
		}

		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Insufficient permissions.']);
		}

		// Get all sites in the network
		$sites = get_sites(array(
			'number' => 0, // Get all sites
			'orderby' => 'domain',
			'order' => 'ASC'
		));

		$sites_data = array();
		foreach ($sites as $site) {
			// Skip main site for import target selection
			if ($site->blog_id == 1) {
				continue;
			}

			switch_to_blog($site->blog_id);

			$sites_data[] = array(
				'id' => $site->blog_id,
				'name' => get_bloginfo('name'),
				'url' => get_bloginfo('url'),
				'domain' => $site->domain,
				'path' => $site->path,
				'is_main' => false // Always false since we're excluding main site
			);

			restore_current_blog();
		}

		wp_send_json_success(['sites' => $sites_data]);
	}

	/**
	 * Handle AJAX backup creation
	 */
	public function handle_backup_creation()
	{
		// Check multisite and main site requirements
		if (!is_multisite() || !is_main_site()) {
			wp_send_json_error(['message' => 'Access denied. This feature is only available on the main site of a multisite network.']);
		}

		// Verify nonce
		if (!wp_verify_nonce($_POST['backup_nonce'], 'multisite_backup_action')) {
			wp_send_json_error(['message' => 'Security check failed.']);
		}

		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Insufficient permissions.']);
		}

		// Validate and sanitize input
		$selected_sites = isset($_POST['selected_sites']) ? array_map('intval', $_POST['selected_sites']) : [];
		$backup_type = sanitize_text_field($_POST['backup_type']);

		if (empty($selected_sites)) {
			wp_send_json_error(['message' => 'Please select at least one site to backup.']);
		}

		// Create backup
		$backup_result = $this->create_backup($selected_sites, $backup_type);

		if ($backup_result['success']) {
			wp_send_json_success(['message' => $backup_result['message']]);
		} else {
			wp_send_json_error(['message' => $backup_result['message']]);
		}
	}

	/**
	 * Create backup functionality
	 */
	private function create_backup($selected_sites, $backup_type)
	{
		try {
			// Get backup directory
			$backup_dir = wp_upload_dir()['basedir'] . '/multisite-backups';
			if (!file_exists($backup_dir)) {
				wp_mkdir_p($backup_dir);
			}

			// Generate backup filename and paths
			$timestamp = current_time('Y-m-d_H-i-s');
			$backup_filename = "backup_{$backup_type}_{$timestamp}.zip";
			$backup_path = $backup_dir . '/' . $backup_filename;
			$temp_dir = $backup_dir . '/temp_' . $timestamp;

			// Create temporary directory
			wp_mkdir_p($temp_dir);

			// Initialize backup process
			$backup_data = [
				'sites' => $selected_sites,
				'type' => $backup_type,
				'timestamp' => time(),
				'filename' => $backup_filename,
				'status' => 'in-progress'
			];

			// Store backup metadata
			$backup_id = $this->store_backup_metadata($backup_data);

			// Create backup based on type
			$backup_size = 0;

			switch ($backup_type) {
				case 'full':
					$backup_size = $this->create_full_backup($selected_sites, $temp_dir);
					break;
				case 'database':
					$backup_size = $this->create_database_backup($selected_sites, $temp_dir);
					break;
				case 'files':
					$backup_size = $this->create_files_backup($selected_sites, $temp_dir);
					break;
			}

			// Create backup info file at root level
			$backup_size += $this->create_backup_info_file($selected_sites, $backup_type, $temp_dir);

			// Create ZIP archive
			$zip_result = $this->create_zip_archive($temp_dir, $backup_path);

			if (!$zip_result) {
				throw new Exception('Failed to create ZIP archive');
			}

			// Get final backup size
			$final_size = file_exists($backup_path) ? filesize($backup_path) : $backup_size;

			// Clean up temporary directory
			$this->delete_directory($temp_dir);

			// Update backup metadata with size and completion
			$this->update_backup_metadata($backup_id, [
				'status' => 'completed',
				'size' => $final_size,
				'path' => $backup_path
			]);

			return [
				'success' => true,
				'message' => 'Backup created successfully! ' . count($selected_sites) . ' sites backed up. Size: ' . size_format($final_size)
			];

		} catch (Exception $e) {
			// Clean up on error
			if (isset($temp_dir) && file_exists($temp_dir)) {
				$this->delete_directory($temp_dir);
			}

			// Update backup status to failed
			if (isset($backup_id)) {
				$this->update_backup_status($backup_id, 'failed');
			}

			return [
				'success' => false,
				'message' => 'Backup creation failed: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Create full backup (database + files)
	 */
	private function create_full_backup($selected_sites, $temp_dir)
	{
		$total_size = 0;
		$total_size += $this->create_database_backup($selected_sites, $temp_dir);
		$total_size += $this->create_files_backup($selected_sites, $temp_dir);
		return $total_size;
	}

	/**
	 * Create database backup
	 */
	private function create_database_backup($selected_sites, $temp_dir)
	{
		global $wpdb;

		$db_dir = $temp_dir . '/database';
		wp_mkdir_p($db_dir);

		$total_size = 0;

		// Export main site tables if site 1 is selected
		if (in_array(1, $selected_sites)) {
			$main_sql_file = $db_dir . '/main_site.sql';
			$total_size += $this->export_site_database(1, $main_sql_file);
		}

		// Export individual site databases
		foreach ($selected_sites as $site_id) {
			if ($site_id == 1)
				continue; // Already handled above

			$site_sql_file = $db_dir . "/site_{$site_id}.sql";
			$total_size += $this->export_site_database($site_id, $site_sql_file);
		}

		// Export users table (shared across multisite)
		$users_sql_file = $db_dir . '/users.sql';
		$total_size += $this->export_users_table($users_sql_file);



		return $total_size;
	}

	/**
	 * Export individual site database
	 */
	private function export_site_database($site_id, $output_file)
	{
		global $wpdb;

		// Switch to site
		if ($site_id > 1) {
			switch_to_blog($site_id);
		}

		$site_prefix = $wpdb->prefix;
		if ($site_id > 1) {
			$site_prefix = $wpdb->base_prefix . $site_id . '_';
		}

		// Get site-specific tables
		$tables = $wpdb->get_results("SHOW TABLES LIKE '{$site_prefix}%'", ARRAY_N);

		$sql_content = "-- WordPress Multisite Backup\n";
		$sql_content .= "-- Site ID: {$site_id}\n";
		$sql_content .= "-- Date: " . current_time('mysql') . "\n\n";

		foreach ($tables as $table) {
			$table_name = $table[0];

			// Skip users tables for individual sites (handled separately)
			if ($site_id > 1 && (strpos($table_name, 'users') !== false || strpos($table_name, 'usermeta') !== false)) {
				continue;
			}

			$sql_content .= $this->export_table_structure($table_name);
			$sql_content .= $this->export_table_data($table_name);
		}

		// Restore original site
		if ($site_id > 1) {
			restore_current_blog();
		}

		file_put_contents($output_file, $sql_content);
		return filesize($output_file);
	}

	/**
	 * Export users table
	 */
	private function export_users_table($output_file)
	{
		global $wpdb;

		$sql_content = "-- WordPress Users and User Meta\n";
		$sql_content .= "-- Date: " . current_time('mysql') . "\n\n";

		// Export users table
		$users_table = $wpdb->base_prefix . 'users';
		$sql_content .= $this->export_table_structure($users_table);
		$sql_content .= $this->export_table_data($users_table);

		// Export usermeta table
		$usermeta_table = $wpdb->base_prefix . 'usermeta';
		$sql_content .= $this->export_table_structure($usermeta_table);
		$sql_content .= $this->export_table_data($usermeta_table);

		file_put_contents($output_file, $sql_content);
		return filesize($output_file);
	}

	/**
	 * Export table structure
	 */
	private function export_table_structure($table_name)
	{
		global $wpdb;

		$sql = "\n-- Table structure for `{$table_name}`\n";
		$sql .= "DROP TABLE IF EXISTS `{$table_name}`;\n";

		$create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
		if ($create_table) {
			$sql .= $create_table[1] . ";\n\n";
		}

		return $sql;
	}

	/**
	 * Export table data
	 */
	private function export_table_data($table_name)
	{
		global $wpdb;

		$sql = "-- Data for table `{$table_name}`\n";

		$rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);

		if (!empty($rows)) {
			$columns = array_keys($rows[0]);
			$sql .= "INSERT INTO `{$table_name}` (`" . implode('`, `', $columns) . "`) VALUES\n";

			$values = [];
			foreach ($rows as $row) {
				$escaped_values = [];
				foreach ($row as $value) {
					if (is_null($value)) {
						$escaped_values[] = 'NULL';
					} else {
						$escaped_values[] = "'" . $wpdb->_real_escape($value) . "'";
					}
				}
				$values[] = '(' . implode(', ', $escaped_values) . ')';
			}

			$sql .= implode(",\n", $values) . ";\n\n";
		}

		return $sql;
	}

	/**
	 * Create files backup
	 */
	private function create_files_backup($selected_sites, $temp_dir)
	{
		$files_dir = $temp_dir . '/files';
		wp_mkdir_p($files_dir);

		$total_size = 0;

		// Backup wp-content directory structure
		$wp_content_backup = $files_dir . '/wp-content';
		wp_mkdir_p($wp_content_backup);

		// Copy themes
		$themes_dir = get_theme_root();
		if (file_exists($themes_dir)) {
			$total_size += $this->copy_directory($themes_dir, $wp_content_backup . '/themes');
		}

		// Copy plugins
		$plugins_dir = WP_PLUGIN_DIR;
		if (file_exists($plugins_dir)) {
			$total_size += $this->copy_directory($plugins_dir, $wp_content_backup . '/plugins');
		}

		// Copy uploads for each selected site
		foreach ($selected_sites as $site_id) {
			if ($site_id > 1) {
				switch_to_blog($site_id);
			}

			$upload_dir = wp_upload_dir();
			$site_uploads_dir = $upload_dir['basedir'];

			if (file_exists($site_uploads_dir)) {
				$backup_uploads_dir = $wp_content_backup . '/uploads';
				if ($site_id > 1) {
					$backup_uploads_dir .= '/sites/' . $site_id;
				}

				$total_size += $this->copy_directory($site_uploads_dir, $backup_uploads_dir);
			}

			if ($site_id > 1) {
				restore_current_blog();
			}
		}

		// Copy wp-config.php (sanitized version)
		$this->create_sanitized_wp_config($files_dir . '/wp-config-backup.php');

		return $total_size;
	}

	/**
	 * Create backup info file at root level
	 */
	private function create_backup_info_file($selected_sites, $backup_type, $temp_dir)
	{
		global $wpdb;

		// Create backup info file at root level
		$info_file = $temp_dir . '/backup_info.json';
		$backup_info = [
			'backup_date' => current_time('mysql'),
			'backup_timestamp' => time(),
			'wordpress_version' => get_bloginfo('version'),
			'sites_included' => $selected_sites,
			'sites_count' => count($selected_sites),
			'database_prefix' => $wpdb->prefix,
			'multisite' => is_multisite(),
			'backup_type' => $backup_type,
			'plugin_version' => '1.0.0', // You can make this dynamic if needed
			'created_by' => 'Multisite Backup Plugin',
			'format_version' => '1.0',
			'php_version' => PHP_VERSION,
			'mysql_version' => $wpdb->db_version()
		];

		file_put_contents($info_file, json_encode($backup_info, JSON_PRETTY_PRINT));

		return filesize($info_file);
	}

	/**
	 * Copy directory recursively
	 */
	private function copy_directory($source, $destination)
	{
		$total_size = 0;

		if (!file_exists($source)) {
			return 0;
		}

		wp_mkdir_p($destination);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$target_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

			if ($item->isDir()) {
				wp_mkdir_p($target_path);
			} else {
				// Skip large files (over 100MB) and certain file types
				if ($item->getSize() > 100 * 1024 * 1024) {
					continue;
				}

				$extension = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
				$skip_extensions = ['log', 'tmp', 'cache', 'lock'];

				if (!in_array($extension, $skip_extensions)) {
					copy($item->getRealPath(), $target_path);
					$total_size += $item->getSize();
				}
			}
		}

		return $total_size;
	}

	/**
	 * Create sanitized wp-config.php backup
	 */
	private function create_sanitized_wp_config($output_file)
	{
		$wp_config_path = ABSPATH . 'wp-config.php';

		if (!file_exists($wp_config_path)) {
			return;
		}

		$config_content = file_get_contents($wp_config_path);

		// Remove sensitive information
		$sensitive_patterns = [
			'/define\s*\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('DB_PASSWORD', '***REMOVED***');",
			'/define\s*\(\s*[\'"]AUTH_KEY[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('AUTH_KEY', '***REMOVED***');",
			'/define\s*\(\s*[\'"]SECURE_AUTH_KEY[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('SECURE_AUTH_KEY', '***REMOVED***');",
			'/define\s*\(\s*[\'"]LOGGED_IN_KEY[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('LOGGED_IN_KEY', '***REMOVED***');",
			'/define\s*\(\s*[\'"]NONCE_KEY[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('NONCE_KEY', '***REMOVED***');",
			'/define\s*\(\s*[\'"]AUTH_SALT[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('AUTH_SALT', '***REMOVED***');",
			'/define\s*\(\s*[\'"]SECURE_AUTH_SALT[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('SECURE_AUTH_SALT', '***REMOVED***');",
			'/define\s*\(\s*[\'"]LOGGED_IN_SALT[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('LOGGED_IN_SALT', '***REMOVED***');",
			'/define\s*\(\s*[\'"]NONCE_SALT[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;/' => "define('NONCE_SALT', '***REMOVED***');"
		];

		foreach ($sensitive_patterns as $pattern => $replacement) {
			$config_content = preg_replace($pattern, $replacement, $config_content);
		}

		// Add backup notice
		$backup_notice = "<?php\n// This is a sanitized backup of wp-config.php\n// Sensitive information has been removed for security\n// Generated on: " . current_time('mysql') . "\n\n";
		$config_content = str_replace('<?php', $backup_notice, $config_content);

		file_put_contents($output_file, $config_content);
	}

	/**
	 * Create ZIP archive
	 */
	private function create_zip_archive($source_dir, $output_file)
	{
		if (!class_exists('ZipArchive')) {
			throw new Exception('ZipArchive class not available');
		}

		$zip = new ZipArchive();
		$result = $zip->open($output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		if ($result !== TRUE) {
			throw new Exception('Cannot create ZIP file: ' . $result);
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			$file_path = $file->getRealPath();
			$relative_path = substr($file_path, strlen($source_dir) + 1);

			if ($file->isDir()) {
				$zip->addEmptyDir($relative_path);
			} else {
				$zip->addFile($file_path, $relative_path);
			}
		}

		$zip->close();

		return file_exists($output_file);
	}

	/**
	 * Delete directory recursively
	 */
	private function delete_directory($dir)
	{
		if (!file_exists($dir)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $file) {
			if ($file->isDir()) {
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}

		rmdir($dir);
	}

	/**
	 * Update backup metadata
	 */
	private function update_backup_metadata($backup_id, $updates)
	{
		$backups = get_option('multisite_backup_history', []);
		if (isset($backups[$backup_id])) {
			$backups[$backup_id] = array_merge($backups[$backup_id], $updates);
			update_option('multisite_backup_history', $backups);
		}
	}

	/**
	 * Store backup metadata
	 */
	private function store_backup_metadata($backup_data)
	{
		$backups = get_option('multisite_backup_history', []);
		$backup_id = time() . '_' . wp_rand(1000, 9999);
		$backup_data['id'] = $backup_id;
		$backups[$backup_id] = $backup_data;
		update_option('multisite_backup_history', $backups);
		return $backup_id;
	}

	/**
	 * Update backup status
	 */
	private function update_backup_status($backup_id, $status)
	{
		$backups = get_option('multisite_backup_history', []);
		if (isset($backups[$backup_id])) {
			$backups[$backup_id]['status'] = $status;
			update_option('multisite_backup_history', $backups);
		}
	}


	/**
	 * Handle AJAX backup scan
	 */
	public function handle_backup_scan()
	{
		// Check multisite and main site requirements
		if (!is_multisite() || !is_main_site()) {
			wp_send_json_error(['message' => 'Access denied. This feature is only available on the main site of a multisite network.']);
		}

		// Verify nonce
		if (!wp_verify_nonce($_POST['scan_nonce'], 'multisite_backup_scan_action')) {
			wp_send_json_error(['message' => 'Security check failed.']);
		}

		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Insufficient permissions.']);
		}

		// Validate file upload
		if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
			wp_send_json_error(['message' => 'No backup file uploaded or upload error occurred.']);
		}

		$backup_file = $_FILES['backup_file'];

		// Validate file type
		$file_type = wp_check_filetype($backup_file['name']);
		if ($file_type['ext'] !== 'zip') {
			wp_send_json_error(['message' => 'Invalid file type. Please upload a ZIP file.']);
		}

		// Scan backup file
		$scan_result = $this->scan_backup_file($backup_file);

		if ($scan_result['success']) {
			wp_send_json_success($scan_result);
		} else {
			wp_send_json_error(['message' => $scan_result['message']]);
		}
	}

	/**
	 * Handle AJAX backup import
	 */
	public function handle_backup_import()
	{
		// Check multisite and main site requirements
		if (!is_multisite() || !is_main_site()) {
			wp_send_json_error(['message' => 'Access denied. This feature is only available on the main site of a multisite network.']);
		}

		// Verify nonce
		if (!wp_verify_nonce($_POST['import_nonce'], 'multisite_backup_import_action')) {
			wp_send_json_error(['message' => 'Security check failed.']);
		}

		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Insufficient permissions.']);
		}

		// Validate file upload
		if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
			wp_send_json_error(['message' => 'No backup file uploaded or upload error occurred.']);
		}

		$backup_file = $_FILES['backup_file'];
		$import_mode = sanitize_text_field($_POST['import_mode']);
		$target_sites = isset($_POST['target_sites']) ? json_decode(stripslashes($_POST['target_sites']), true) : array();

		// Validate target sites - if empty, check if only main site exists
		if (empty($target_sites) || !is_array($target_sites)) {
			// Check if there are any sub-sites in the network
			$all_sites = get_sites(array('number' => 0));
			$has_sub_sites = false;

			foreach ($all_sites as $site) {
				if ($site->blog_id != 1) {
					$has_sub_sites = true;
					break;
				}
			}

			if ($has_sub_sites) {
				// Sub-sites exist but none selected
				wp_send_json_error(['message' => 'Please select at least one target site for import.']);
			} else {
				// Only main site exists, use it automatically
				$target_sites = array(
					array(
						'id' => 1,
						'name' => get_bloginfo('name'),
						'url' => get_bloginfo('url'),
						'is_main' => true
					)
				);
			}
		}

		// Validate file type
		$file_type = wp_check_filetype($backup_file['name']);
		if ($file_type['ext'] !== 'zip') {
			wp_send_json_error(['message' => 'Invalid file type. Please upload a ZIP file.']);
		}

		// Import backup
		$import_result = $this->import_backup($backup_file, $import_mode, $target_sites);

		if ($import_result['success']) {
			wp_send_json_success(['message' => $import_result['message']]);
		} else {
			wp_send_json_error(['message' => $import_result['message']]);
		}
	}

	/**
	 * Scan backup file to validate format and contents
	 */
	private function scan_backup_file($backup_file)
	{
		try {
			// Create temporary directory for scanning
			$scan_dir = wp_upload_dir()['basedir'] . '/multisite-scans';
			if (!file_exists($scan_dir)) {
				wp_mkdir_p($scan_dir);
			}

			// Move uploaded file to temporary location
			$timestamp = current_time('Y-m-d_H-i-s');
			$scan_filename = 'scan_' . $timestamp . '.zip';
			$scan_path = $scan_dir . '/' . $scan_filename;

			if (!move_uploaded_file($backup_file['tmp_name'], $scan_path)) {
				throw new Exception('Failed to move uploaded file for scanning');
			}

			// Open ZIP file
			$zip = new ZipArchive();
			$result = $zip->open($scan_path);

			if ($result !== TRUE) {
				unlink($scan_path);
				throw new Exception('Failed to open ZIP file: ' . $result);
			}

			// Analyze ZIP contents
			$scan_results = [
				'success' => true,
				'filename' => $backup_file['name'],
				'size' => $backup_file['size'],
				'format_valid' => false,
				'backup_type' => 'unknown',
				'components' => [],
				'sites_count' => 0,
				'backup_date' => null,
				'wordpress_version' => null,
				'warnings' => [],
				'errors' => []
			];

			// Check for expected directory structure
			$has_database = false;
			$has_files = false;
			$database_files = [];
			$backup_info = null;
			$files_found = [];
			$total_files = 0;

			for ($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				$total_files++;

				// Skip directories (they end with /)
				if (substr($filename, -1) === '/') {
					continue;
				}

				$files_found[] = $filename;

				// Check for backup_info.json at root level
				if ($filename === 'backup_info.json') {
					$backup_info_content = $zip->getFromIndex($i);
					if ($backup_info_content) {
						$backup_info = json_decode($backup_info_content, true);
					}
				}

				// Check for database directory and files
				if (strpos($filename, 'database/') === 0) {
					// Only count as having database if we find actual SQL files, not just the directory
					if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
						$has_database = true;
						$database_files[] = basename($filename);
					}
				}



				// Check for files directory
				if (strpos($filename, 'files/') === 0) {
					$has_files = true;
				}
			}

			$zip->close();
			unlink($scan_path);

			// Determine backup type and validate format
			// First check if backup_info.json provides the type
			if ($backup_info && isset($backup_info['backup_type'])) {
				$scan_results['backup_type'] = $backup_info['backup_type'];
				$scan_results['format_valid'] = true;
			} elseif ($has_database && $has_files) {
				$scan_results['backup_type'] = 'full';
				$scan_results['format_valid'] = true;
			} elseif ($has_database && !$has_files) {
				$scan_results['backup_type'] = 'database';
				$scan_results['format_valid'] = true;
			} elseif (!$has_database && $has_files) {
				$scan_results['backup_type'] = 'files';
				$scan_results['format_valid'] = true;
			} else {
				$scan_results['errors'][] = 'Invalid backup format: No recognizable backup structure found';
			}

			// Extract backup information
			if ($backup_info) {
				$scan_results['backup_date'] = $backup_info['backup_date'] ?? null;
				$scan_results['wordpress_version'] = $backup_info['wordpress_version'] ?? null;
				$scan_results['sites_count'] = count($backup_info['sites_included'] ?? []);

				// Check WordPress version compatibility
				$current_wp_version = get_bloginfo('version');
				if ($scan_results['wordpress_version'] && version_compare($scan_results['wordpress_version'], $current_wp_version, '>')) {
					$scan_results['warnings'][] = 'Backup was created with a newer WordPress version (' . $scan_results['wordpress_version'] . ') than current (' . $current_wp_version . ')';
				}
			}

			// Determine components
			if ($has_database) {
				$db_count = count($database_files);
				if ($db_count > 0) {
					// Show specific SQL files found
					$file_list = implode(', ', array_map(function ($file) {
						return str_replace('.sql', '', $file);
					}, $database_files));
					$scan_results['components'][] = 'Database (' . $db_count . ' SQL files: ' . $file_list . ')';
				} else {
					$scan_results['components'][] = 'Database (structure only)';
				}
			}
			if ($has_files) {
				$scan_results['components'][] = 'Files (themes, plugins, uploads)';
			}

			// Add warnings for missing components
			if (!$has_database) {
				$scan_results['warnings'][] = 'No database backup found - site content and settings will not be restored';
			}
			if (!$has_files) {
				$scan_results['warnings'][] = 'No files backup found - themes, plugins, and media will not be restored';
			}

			// Add debug information (can be removed later)
			if (count($database_files) === 0) {
				$database_related_files = array_filter($files_found, function ($file) {
					return strpos($file, 'database/') === 0;
				});

				$debug_info = [];
				$debug_info[] = 'Total files in ZIP: ' . $total_files;
				$debug_info[] = 'Files in database folder: ' . count($database_related_files);

				if (count($database_related_files) > 0) {
					foreach ($database_related_files as $file) {
						$ext = pathinfo($file, PATHINFO_EXTENSION);
						$debug_info[] = '- ' . $file . ' (ext: ' . $ext . ', is_sql: ' . ($ext === 'sql' ? 'yes' : 'no') . ')';
					}
				} else {
					$debug_info[] = 'No files found in database/ folder';
				}

				$scan_results['warnings'][] = 'Debug: ' . implode(' | ', $debug_info);
			}

			// Check if this looks like a plugin-generated backup
			if (!$backup_info) {
				$scan_results['warnings'][] = 'Backup metadata not found - this may not be a backup created by this plugin';
			}

			return $scan_results;

		} catch (Exception $e) {
			// Clean up on error
			if (isset($scan_path) && file_exists($scan_path)) {
				unlink($scan_path);
			}

			return [
				'success' => false,
				'message' => 'Backup scan failed: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Import backup functionality
	 */
	private function import_backup($backup_file, $import_mode, $target_sites = array())
	{
		try {
			// Create import directory
			$import_dir = wp_upload_dir()['basedir'] . '/multisite-imports';
			if (!file_exists($import_dir)) {
				wp_mkdir_p($import_dir);
			}

			// Move uploaded file
			$timestamp = current_time('Y-m-d_H-i-s');
			$import_filename = 'import_' . $timestamp . '.zip';
			$import_path = $import_dir . '/' . $import_filename;

			if (!move_uploaded_file($backup_file['tmp_name'], $import_path)) {
				throw new Exception('Failed to move uploaded file');
			}

			// Extract backup
			$extract_dir = $import_dir . '/extract_' . $timestamp;
			wp_mkdir_p($extract_dir);

			$zip = new ZipArchive();
			$result = $zip->open($import_path);

			if ($result !== TRUE) {
				throw new Exception('Failed to open backup file: ' . $result);
			}

			$zip->extractTo($extract_dir);
			$zip->close();

			// Store import metadata
			$import_data = [
				'filename' => $backup_file['name'],
				'mode' => $import_mode,
				'target_sites' => $target_sites,
				'timestamp' => time(),
				'status' => 'in-progress'
			];

			$import_id = $this->store_import_metadata($import_data);

			// Process full import (all components)
			$this->import_database($extract_dir, $import_mode);
			$this->import_files($extract_dir, $import_mode);
			$this->import_users($extract_dir, $import_mode);
			$this->import_settings($extract_dir, $import_mode);

			// Clean up
			$this->delete_directory($extract_dir);
			unlink($import_path);

			// Update import status
			$this->update_import_status($import_id, 'completed');

			return [
				'success' => true,
				'message' => 'Backup imported successfully! All components have been imported to ' . count($target_sites) . ' site(s).'
			];

		} catch (Exception $e) {
			// Clean up on error
			if (isset($extract_dir) && file_exists($extract_dir)) {
				$this->delete_directory($extract_dir);
			}
			if (isset($import_path) && file_exists($import_path)) {
				unlink($import_path);
			}

			// Update import status to failed
			if (isset($import_id)) {
				$this->update_import_status($import_id, 'failed');
			}

			return [
				'success' => false,
				'message' => 'Import failed: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Import database from backup
	 */
	private function import_database($extract_dir, $import_mode)
	{
		global $wpdb;

		$db_dir = $extract_dir . '/database';
		if (!file_exists($db_dir)) {
			return;
		}

		// Import SQL files
		$sql_files = glob($db_dir . '/*.sql');

		foreach ($sql_files as $sql_file) {
			$sql_content = file_get_contents($sql_file);

			if ($import_mode === 'replace') {
				// Drop existing tables before import (be very careful with this)
				// This is a simplified implementation - in production, you'd want more sophisticated handling
			}

			// Execute SQL (in chunks for large files)
			$this->execute_sql($sql_content);
		}
	}

	/**
	 * Import files from backup
	 */
	private function import_files($extract_dir, $import_mode)
	{
		$files_dir = $extract_dir . '/files';
		if (!file_exists($files_dir)) {
			return;
		}

		$wp_content_backup = $files_dir . '/wp-content';
		if (file_exists($wp_content_backup)) {
			// Import themes
			if (file_exists($wp_content_backup . '/themes')) {
				$this->copy_directory($wp_content_backup . '/themes', get_theme_root());
			}

			// Import plugins
			if (file_exists($wp_content_backup . '/plugins')) {
				$this->copy_directory($wp_content_backup . '/plugins', WP_PLUGIN_DIR);
			}

			// Import uploads
			if (file_exists($wp_content_backup . '/uploads')) {
				$upload_dir = wp_upload_dir();
				$this->copy_directory($wp_content_backup . '/uploads', $upload_dir['basedir']);
			}
		}
	}

	/**
	 * Import users from backup
	 */
	private function import_users($extract_dir, $import_mode)
	{
		// Placeholder for user import functionality
		// This would involve parsing user data and creating/updating user accounts
	}

	/**
	 * Import settings from backup
	 */
	private function import_settings($extract_dir, $import_mode)
	{
		// Placeholder for settings import functionality
		// This would involve importing WordPress options and configurations
	}

	/**
	 * Execute SQL content
	 */
	private function execute_sql($sql_content)
	{
		global $wpdb;

		// Split SQL into individual queries
		$queries = explode(';', $sql_content);

		foreach ($queries as $query) {
			$query = trim($query);
			if (!empty($query)) {
				$wpdb->query($query);
			}
		}
	}

	/**
	 * Store import metadata
	 */
	private function store_import_metadata($import_data)
	{
		$imports = get_option('multisite_backup_import_history', []);
		$import_id = time() . '_' . wp_rand(1000, 9999);
		$import_data['id'] = $import_id;
		$imports[$import_id] = $import_data;
		update_option('multisite_backup_import_history', $imports);
		return $import_id;
	}

	/**
	 * Update import status
	 */
	private function update_import_status($import_id, $status)
	{
		$imports = get_option('multisite_backup_import_history', []);
		if (isset($imports[$import_id])) {
			$imports[$import_id]['status'] = $status;
			update_option('multisite_backup_import_history', $imports);
		}
	}
}