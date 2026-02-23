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
                                        <?php
                                        $available_sites = [];
                                        if (!empty($sites)) {
                                            foreach ($sites as $s) {
                                                if ((int)$s->blog_id !== 1) {
                                                    $available_sites[] = $s;
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="site-selection-header">
                                            <strong>Select Sites to Backup:</strong>
                                            <?php if (!empty($available_sites)): ?>
                                                <span class="description">(<?php echo count($available_sites); ?> site<?php echo count($available_sites) !== 1 ? 's' : ''; ?> available)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="site-list">
                                            <?php if (empty($available_sites)): ?>
                                                <div class="notice notice-warning inline">
                                                    <p><strong>No sites available for backup.</strong></p>
                                                    <p>Please ensure you have sites in your multisite network.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($available_sites as $site): ?>
                                                    <label class="site-item">
                                                        <input type="radio" name="selected_site" value="<?php echo esc_attr($site->blog_id); ?>" class="site-checkbox">
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
                                        Select exactly one site to include in the backup.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Create Backup" <?php echo empty($available_sites) ? 'disabled' : ''; ?>>
                            <span class="spinner"></span>
                            <?php if (empty($available_sites)): ?>
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
                    <?php
                    $per_page = 10;
                    $current_page_num = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
                    $history_page = multisite_backup_get_history_page($current_page_num, $per_page);
                    $backup_history = $history_page['items'];
                    $total_items = $history_page['total'];
                    $total_pages = max(1, (int) ceil($total_items / $per_page));
                    ?>
                    <?php if (empty($backup_history)): ?>
                        <p>No backups found. <a href="#backup-create" class="nav-tab-link" data-tab="backup-create">Create your first backup</a>.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column column-cb check-column">
                                        <input type="checkbox" id="cb-select-all-1" />
                                    </th>
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
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" class="backup-select" name="selected_backups[]" value="<?php echo esc_attr($backup['id']); ?>" />
                                        </th>
                                        <td><?php echo esc_html(date('Y-m-d H:i:s', $backup['timestamp'])); ?></td>
                                        <td>
                                            <span class="backup-type backup-type-<?php echo esc_attr($backup['type']); ?>">
                                                <?php echo esc_html(ucfirst($backup['type'])); ?>
                                            </span>
                                        </td>
                                    <td><?php echo esc_html(isset($backup['sites_paths']) ? $backup['sites_paths'] : ''); ?></td>
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
                        <?php if ($total_pages > 0): ?>
                            <div class="tablenav">
                                <div class="alignleft actions">
                                    <button type="button" class="button button-secondary" id="delete-selected-backups">Delete Selected</button>
                                </div>
                                <div class="tablenav-pages">
                                    <?php
                                    $base_url = add_query_arg(array('tab' => 'backup-history'), admin_url('admin.php?page=multisite-backup'));
                                    $prev_page = max(1, $current_page_num - 1);
                                    $next_page = min($total_pages, $current_page_num + 1);
                                    ?>
                                    <span class="displaying-num"><?php echo intval($total_items); ?> items</span>
                                    <span class="pagination-links">
                                        <?php if ($current_page_num > 1): ?>
                                            <a class="first-page button" href="<?php echo esc_url(add_query_arg('history_page', 1, $base_url)); ?>">&laquo;</a>
                                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg('history_page', $prev_page, $base_url)); ?>">&lsaquo;</a>
                                        <?php else: ?>
                                            <span class="tablenav-pages-navspan button disabled">&laquo;</span>
                                            <span class="tablenav-pages-navspan button disabled">&lsaquo;</span>
                                        <?php endif; ?>
                                        <span class="paging-input">
                                            <span class="current-page"><?php echo intval($current_page_num); ?></span>
                                            of
                                            <span class="total-pages"><?php echo intval($total_pages); ?></span>
                                        </span>
                                        <?php if ($current_page_num < $total_pages): ?>
                                            <a class="next-page button" href="<?php echo esc_url(add_query_arg('history_page', $next_page, $base_url)); ?>">&rsaquo;</a>
                                            <a class="last-page button" href="<?php echo esc_url(add_query_arg('history_page', $total_pages, $base_url)); ?>">&raquo;</a>
                                        <?php else: ?>
                                            <span class="tablenav-pages-navspan button disabled">&rsaquo;</span>
                                            <span class="tablenav-pages-navspan button disabled">&raquo;</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
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
                                           value="<?php echo esc_attr(get_option('backup_storage_path', WP_CONTENT_DIR . '/multisite-backups')); ?>" 
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
    $storage_path = get_option('backup_storage_path', WP_CONTENT_DIR . '/multisite-backups');
    $base_url = '';
    if (strpos($storage_path, WP_CONTENT_DIR) === 0) {
        $relative = ltrim(str_replace(WP_CONTENT_DIR, '', $storage_path), '/\\');
        $base_url = trailingslashit(content_url()) . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    } else {
        $uploads = wp_upload_dir();
        $base_url = $uploads['baseurl'] . '/multisite-backups';
    }
    
    foreach ($backups as $backup_id => $backup_data) {
        $paths = [];
        if (isset($backup_data['sites']) && is_array($backup_data['sites'])) {
            foreach ($backup_data['sites'] as $sid) {
                $path = '';
                if (function_exists('get_site')) {
                    $site = get_site($sid);
                    if ($site && isset($site->path)) {
                        $path = $site->path;
                    }
                }
                if ($path === '') {
                    $siteurl = get_blog_option($sid, 'siteurl');
                    if ($siteurl) {
                        $p = parse_url($siteurl, PHP_URL_PATH);
                        $path = $p ? $p : '/';
                    } else {
                        $path = '/';
                    }
                }
                $paths[] = $path;
            }
        }
        $formatted_backups[] = [
            'id' => $backup_data['id'],
            'timestamp' => $backup_data['timestamp'],
            'type' => $backup_data['type'],
            'site_count' => count($backup_data['sites']),
            'size' => isset($backup_data['size']) ? $backup_data['size'] : 1024 * 1024 * 10,
            'status' => $backup_data['status'],
            'download_url' => isset($backup_data['filename']) ? rtrim($base_url, '/'). '/' . $backup_data['filename'] : '#',
            'sites_paths' => implode(', ', $paths)
        ];
    }
    
    // Sort by timestamp (newest first)
    usort($formatted_backups, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $formatted_backups;
}

function multisite_backup_get_history_page($page, $per_page) {
    $all = multisite_backup_get_history();
    $total = count($all);
    $page = max(1, (int)$page);
    $per_page = max(1, (int)$per_page);
    $offset = ($page - 1) * $per_page;
    $items = array_slice($all, $offset, $per_page);
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page
    ];
}
