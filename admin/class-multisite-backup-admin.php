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

			// Generate backup filename
			$timestamp = current_time('Y-m-d_H-i-s');
			$backup_filename = "backup_{$backup_type}_{$timestamp}.zip";
			$backup_path = $backup_dir . '/' . $backup_filename;

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

			// Simulate backup creation (replace with actual backup logic)
			sleep(2); // Simulate processing time

			// Update backup status
			$this->update_backup_status($backup_id, 'completed');

			return [
				'success' => true,
				'message' => 'Backup created successfully! ' . count($selected_sites) . ' sites backed up.'
			];

		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => 'Backup creation failed: ' . $e->getMessage()
			];
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
