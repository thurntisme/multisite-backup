<?php

/**
 * Provide a admin area view for the import functionality
 *
 * This file is used to markup the admin-facing aspects of the import plugin.
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
    if ($_POST['action'] === 'import_backup' && wp_verify_nonce($_POST['import_nonce'], 'multisite_backup_import_action')) {
        $backup_file = $_FILES['backup_file'] ?? null;
        // Here you would implement the actual import logic
        $import_result = multisite_backup_import_backup($backup_file);

        if ($import_result) {
            echo '<div class="notice notice-success is-dismissible"><p>Backup imported successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Backup import failed. Please try again.</p></div>';
        }
    }
}

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="multisite-backup-container">
        <!-- Import Backup Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Import Backup</h2>
            </div>
            <div class="inside">
                <form method="post" action="" enctype="multipart/form-data" id="import-backup-form">
                    <?php wp_nonce_field('multisite_backup_import_action', 'import_nonce'); ?>
                    <?php wp_nonce_field('multisite_backup_scan_action', 'scan_nonce'); ?>
                    <input type="hidden" name="action" value="import_backup">
                    <input type="hidden" name="import_mode" id="import_mode" value="merge">

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="backup_file">Backup File</label>
                            </th>
                            <td>
                                <input type="file" name="backup_file" id="backup_file" accept=".zip"
                                    class="regular-text" required>
                                <p class="description">Select a backup ZIP file to import. Maximum file size:
                                    <?php echo size_format(wp_max_upload_size()); ?>
                                </p>
                            </td>
                        </tr>


                    </table>

                    <div class="import-preview" id="import-preview" style="display: none;">
                        <h3>Backup Preview</h3>
                        <div id="backup-info"></div>
                    </div>

                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary"
                            value="Import Backup">
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>
        </div>

        <!-- Import History Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Import History</h2>
            </div>
            <div class="inside">
                <?php
                $import_history = multisite_backup_get_import_history();
                if (empty($import_history)): ?>
                    <p>No imports found.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Backup File</th>
                                <th scope="col">Import Mode</th>

                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($import_history as $import): ?>
                                <tr>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', $import['timestamp'])); ?></td>
                                    <td><?php echo esc_html($import['filename']); ?></td>
                                    <td><?php echo esc_html(ucfirst($import['mode'])); ?></td>

                                    <td>
                                        <span class="status status-<?php echo esc_attr($import['status']); ?>">
                                            <?php echo esc_html(ucfirst($import['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($import['status'] === 'completed'): ?>
                                            <a href="#" class="button button-small"
                                                data-import-id="<?php echo esc_attr($import['id']); ?>">View Details</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Import Instructions -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Import Instructions</h2>
            </div>
            <div class="inside">
                <div class="import-instructions">
                    <h4>📋 Before You Import:</h4>
                    <ul>
                        <li><strong>⚠️ Create a backup</strong> of your current site before importing</li>
                        <li><strong>🔍 Verify the backup file</strong> is from a compatible WordPress multisite
                            installation</li>
                        <li><strong>💾 Ensure sufficient disk space</strong> for the import process</li>
                        <li><strong>⏰ Allow extra time</strong> for large backup files</li>
                    </ul>

                    <h4>📦 Supported Backup Formats:</h4>
                    <ul>
                        <li>ZIP files created by this plugin</li>
                        <li>Standard WordPress multisite backups</li>
                        <li>Database SQL files (when importing database only)</li>
                    </ul>

                    <h4>🔧 Import Modes Explained:</h4>
                    <ul>
                        <li><strong>Merge:</strong> Adds imported content alongside existing content. Safest option.
                        </li>
                        <li><strong>Replace:</strong> Overwrites existing content with imported content. Use with
                            caution.</li>
                    </ul>

                    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <strong>⚠️ Important Security Note:</strong>
                        <p>Only import backup files from trusted sources. Importing malicious backups can compromise
                            your site security.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

/**
 * Helper function to import backup
 * This would typically be in a separate class/file
 */
function multisite_backup_import_backup($backup_file)
{
    // Placeholder implementation
    // In a real implementation, this would:
    // 1. Validate the backup file
    // 2. Extract the backup contents
    // 3. Import all backup components
    // 4. Update site configurations

    return true; // Simulate success for now
}

/**
 * Helper function to get import history
 * This would typically be in a separate class/file
 */
function multisite_backup_get_import_history()
{
    // Placeholder implementation
    return [
        [
            'id' => 1,
            'timestamp' => time() - 86400,
            'filename' => 'backup_full_2024-12-10_14-30-25.zip',
            'mode' => 'merge',

            'status' => 'completed'
        ],
        [
            'id' => 2,
            'timestamp' => time() - 172800,
            'filename' => 'backup_database_2024-12-08_10-15-30.zip',
            'mode' => 'replace',

            'status' => 'failed'
        ]
    ];
}