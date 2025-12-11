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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/multisite-backup-admin.js', array('jquery'), $this->version, false);

	}

	public function register_multisite_backup_menu()
	{
		add_menu_page(
			'Multisite Backup',
			'Multisite Backup',
			'manage_options',
			'multisite-backup',
			array($this, 'multisite_backup_page_render'),
			'dashicons-database',
			6
		);
	}

	public function multisite_backup_page_render()
	{
		include_once 'partials/multisite-backup-admin-display.php';
	}

	/**
	 * Handle AJAX backup creation
	 */
	public function handle_backup_creation()
	{
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

		// Create database info file
		$info_file = $db_dir . '/backup_info.json';
		$backup_info = [
			'backup_date' => current_time('mysql'),
			'wordpress_version' => get_bloginfo('version'),
			'sites_included' => $selected_sites,
			'database_prefix' => $wpdb->prefix,
			'multisite' => is_multisite()
		];
		file_put_contents($info_file, json_encode($backup_info, JSON_PRETTY_PRINT));
		$total_size += filesize($info_file);

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

}
