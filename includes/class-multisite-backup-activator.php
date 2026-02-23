<?php

/**
 * Fired during plugin activation
 *
 * @link       https://thurntisme.com
 * @since      1.0.0
 *
 * @package    Multisite_Backup
 * @subpackage Multisite_Backup/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Multisite_Backup
 * @subpackage Multisite_Backup/includes
 * @author     Thủy Nguyễn Thế <nguyenthethuy.qnam@gmail.com>
 */
class Multisite_Backup_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$path = get_option('backup_storage_path', '');
		$default = WP_CONTENT_DIR . '/multisite-backups';
		if ($path === '') {
			add_option('backup_storage_path', $default);
		} elseif ($path === WP_CONTENT_DIR . '/backups') {
			update_option('backup_storage_path', $default);
		}
	}

}
