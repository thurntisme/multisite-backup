<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://thurntisme.com
 * @since      1.0.0
 *
 * @package    Multisite_Backup
 * @subpackage Multisite_Backup/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Handle form submissions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create_backup' && wp_verify_nonce($_POST['backup_nonce'], 'multisite_backup_action')) {
        $selected_sites = isset($_POST['selected_sites']) ? array_map('intval', $_POST['selected_sites']) : [];
        $backup_type = sanitize_text_field($_POST['backup_type']);
        
        // Here you would implement the actual backup logic
        $backup_result = multisite_backup_create_backup($selected_sites, $backup_type);
        
        if ($backup_result) {
            echo '<div class="notice notice-success is-dismissible"><p>Backup created successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Backup creation failed. Please try again.</p></div>';
        }
    }
}

// Get all sites in the network
$sites = get_sites(['number' => 0]);
$backup_history = multisite_backup_get_history();

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="multisite-backup-container">
        <!-- Navigation Tabs -->
        <nav class="nav-tab-wrapper">
            <a href="#backup-create" class="nav-tab nav-tab-active" data-tab="backup-create">Create Backup</a>
            <a href="#backup-history" class="nav-tab" data-tab="backup-history">Backup History</a>
            <a href="#backup-settings" class="nav-tab" data-tab="backup-settings">Settings</a>
        </nav>

        <!-- Create Backup Tab -->
        <div id="backup-create" class="tab-content active">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Create New Backup</h2>
                </div>
                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('multisite_backup_action', 'backup_nonce'); ?>
                        <input type="hidden" name="action" value="create_backup">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="backup_type">Backup Type</label>
                                </th>
                                <td>
                                    <select name="backup_type" id="backup_type" class="regular-text">
                                        <option value="full">Full Backup (Files + Database)</option>
                                        <option value="database">Database Only</option>
                                        <option value="files">Files Only</option>
                                    </select>
                                    <p class="description">Choose what to include in your backup.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label>Select Sites</label>
                                </th>
                                <td>
                                    <div class="site-selection">
                                        <div class="site-selection-header">
                                            <label>
                                                <input type="checkbox" id="select-all-sites"> 
                                                <strong>Select All Sites</strong>
                                            </label>
                                        </div>
                                        <div class="site-list">
                                            <?php foreach ($sites as $site): ?>
                                                <label class="site-item">
                                                    <input type="checkbox" name="selected_sites[]" value="<?php echo esc_attr($site->blog_id); ?>" class="site-checkbox">
                                                    <div class="site-info">
                                                        <strong><?php echo esc_html(get_blog_option($site->blog_id, 'blogname')); ?></strong>
                                                        <span class="site-url"><?php echo esc_html($site->domain . $site->path); ?></span>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <p class="description">Select which sites to include in the backup.</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Create Backup">
                            <span class="spinner"></span>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Backup History Tab -->
        <div id="backup-history" class="tab-content">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Backup History</h2>
                </div>
                <div class="inside">
                    <?php if (empty($backup_history)): ?>
                        <p>No backups found. <a href="#backup-create" class="nav-tab-link" data-tab="backup-create">Create your first backup</a>.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Sites</th>
                                    <th scope="col">Size</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backup_history as $backup): ?>
                                    <tr>
                                        <td><?php echo esc_html(date('Y-m-d H:i:s', $backup['timestamp'])); ?></td>
                                        <td>
                                            <span class="backup-type backup-type-<?php echo esc_attr($backup['type']); ?>">
                                                <?php echo esc_html(ucfirst($backup['type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($backup['site_count']); ?> sites</td>
                                        <td><?php echo esc_html(size_format($backup['size'])); ?></td>
                                        <td>
                                            <span class="status status-<?php echo esc_attr($backup['status']); ?>">
                                                <?php echo esc_html(ucfirst($backup['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url($backup['download_url']); ?>" class="button button-small">Download</a>
                                            <a href="#" class="button button-small button-link-delete" data-backup-id="<?php echo esc_attr($backup['id']); ?>">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="backup-settings" class="tab-content">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Backup Settings</h2>
                </div>
                <div class="inside">
                    <form method="post" action="options.php">
                        <?php settings_fields('multisite_backup_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="backup_storage_path">Storage Path</label>
                                </th>
                                <td>
                                    <input type="text" name="backup_storage_path" id="backup_storage_path" 
                                           value="<?php echo esc_attr(get_option('backup_storage_path', WP_CONTENT_DIR . '/backups')); ?>" 
                                           class="regular-text">
                                    <p class="description">Directory where backups will be stored.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="backup_retention_days">Retention Period</label>
                                </th>
                                <td>
                                    <input type="number" name="backup_retention_days" id="backup_retention_days" 
                                           value="<?php echo esc_attr(get_option('backup_retention_days', 30)); ?>" 
                                           min="1" max="365" class="small-text">
                                    <span>days</span>
                                    <p class="description">How long to keep backups before automatic deletion.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="backup_compression">Compression</label>
                                </th>
                                <td>
                                    <select name="backup_compression" id="backup_compression">
                                        <option value="zip" <?php selected(get_option('backup_compression', 'zip'), 'zip'); ?>>ZIP</option>
                                        <option value="tar" <?php selected(get_option('backup_compression', 'zip'), 'tar'); ?>>TAR</option>
                                        <option value="tar.gz" <?php selected(get_option('backup_compression', 'zip'), 'tar.gz'); ?>>TAR.GZ</option>
                                    </select>
                                    <p class="description">Compression format for backup files.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="backup_email_notifications">Email Notifications</label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="backup_email_notifications" id="backup_email_notifications" 
                                               value="1" <?php checked(get_option('backup_email_notifications', 0), 1); ?>>
                                        Send email notifications when backups complete
                                    </label>
                                    <br><br>
                                    <input type="email" name="backup_notification_email" 
                                           value="<?php echo esc_attr(get_option('backup_notification_email', get_option('admin_email'))); ?>" 
                                           class="regular-text" placeholder="admin@example.com">
                                    <p class="description">Email address for backup notifications.</p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>


    
</div>

<?php

/**
 * Helper function to create a backup
 * This would typically be in a separate class/file
 */
function multisite_backup_create_backup($selected_sites, $backup_type) {
    // Placeholder implementation
    // In a real implementation, this would:
    // 1. Create backup directory
    // 2. Export databases for selected sites
    // 3. Copy files if needed
    // 4. Create compressed archive
    // 5. Store backup metadata
    
    return true; // Simulate success for now
}

/**
 * Helper function to get backup history
 * This would typically be in a separate class/file
 */
function multisite_backup_get_history() {
    // Placeholder implementation
    // In a real implementation, this would query the database or file system
    // for existing backups and return their metadata
    
    return [
        [
            'id' => 1,
            'timestamp' => time() - 86400,
            'type' => 'full',
            'site_count' => 3,
            'size' => 1024 * 1024 * 50, // 50MB
            'status' => 'completed',
            'download_url' => '#'
        ],
        [
            'id' => 2,
            'timestamp' => time() - 172800,
            'type' => 'database',
            'site_count' => 2,
            'size' => 1024 * 1024 * 5, // 5MB
            'status' => 'completed',
            'download_url' => '#'
        ]
    ];
}