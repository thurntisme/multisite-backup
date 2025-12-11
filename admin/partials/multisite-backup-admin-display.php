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

// Determine active tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'backup-create';
$valid_tabs = ['backup-create', 'backup-history', 'backup-settings'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'backup-create';
}

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="multisite-backup-container">
        <!-- Navigation Tabs -->
        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url(add_query_arg('tab', 'backup-create', admin_url('admin.php?page=multisite-backup'))); ?>" 
               class="nav-tab <?php echo $current_tab === 'backup-create' ? 'nav-tab-active' : ''; ?>" 
               data-tab="backup-create">Create Backup</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'backup-history', admin_url('admin.php?page=multisite-backup'))); ?>" 
               class="nav-tab <?php echo $current_tab === 'backup-history' ? 'nav-tab-active' : ''; ?>" 
               data-tab="backup-history">Backup History</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'backup-settings', admin_url('admin.php?page=multisite-backup'))); ?>" 
               class="nav-tab <?php echo $current_tab === 'backup-settings' ? 'nav-tab-active' : ''; ?>" 
               data-tab="backup-settings">Settings</a>
        </nav>

        <!-- Create Backup Tab -->
        <div id="backup-create" class="tab-content <?php echo $current_tab === 'backup-create' ? 'active' : ''; ?>">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Create New Backup</h2>
                </div>
                <div class="inside">
                    <form method="post" action="" id="create-backup-form">
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
                                            <strong>Select Sites to Backup:</strong>
                                            <?php if (!empty($sites)): ?>
                                                <span class="description">(<?php echo count($sites); ?> site<?php echo count($sites) !== 1 ? 's' : ''; ?> available)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="site-list">
                                            <?php if (empty($sites)): ?>
                                                <div class="notice notice-warning inline">
                                                    <p><strong>No sites available for backup.</strong></p>
                                                    <p>Please ensure you have sites in your multisite network.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($sites as $site): ?>
                                                    <label class="site-item">
                                                        <input type="checkbox" name="selected_sites[]" value="<?php echo esc_attr($site->blog_id); ?>" class="site-checkbox">
                                                        <div class="site-info">
                                                            <strong><?php echo esc_html(get_blog_option($site->blog_id, 'blogname')); ?></strong>
                                                            <span class="site-url"><?php echo esc_html($site->domain . $site->path); ?></span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description">
                                        Select which sites to include in the backup. 
                                        <?php if (!empty($sites)): ?>
                                            <strong>All sites are selected by default.</strong> Uncheck any sites you don't want to backup.
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Create Backup" <?php echo empty($sites) ? 'disabled' : ''; ?>>
                            <span class="spinner"></span>
                            <?php if (empty($sites)): ?>
                                <p class="description" style="color: #d63638;">Cannot create backup: No sites available in the network.</p>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Backup History Tab -->
        <div id="backup-history" class="tab-content <?php echo $current_tab === 'backup-history' ? 'active' : ''; ?>">
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
        <div id="backup-settings" class="tab-content <?php echo $current_tab === 'backup-settings' ? 'active' : ''; ?>">
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
                                               value="1" <?php checked(get_option('backup_email_notifications', 0), 1); ?> />
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
    $backups = get_option('multisite_backup_history', []);
    $formatted_backups = [];
    
    foreach ($backups as $backup_id => $backup_data) {
        $formatted_backups[] = [
            'id' => $backup_data['id'],
            'timestamp' => $backup_data['timestamp'],
            'type' => $backup_data['type'],
            'site_count' => count($backup_data['sites']),
            'size' => isset($backup_data['size']) ? $backup_data['size'] : 1024 * 1024 * 10, // Default 10MB
            'status' => $backup_data['status'],
            'download_url' => isset($backup_data['filename']) ? wp_upload_dir()['baseurl'] . '/multisite-backups/' . $backup_data['filename'] : '#'
        ];
    }
    
    // Sort by timestamp (newest first)
    usort($formatted_backups, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $formatted_backups;
}