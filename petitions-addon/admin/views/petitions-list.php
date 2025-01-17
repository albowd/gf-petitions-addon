<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Active Petitions', 'gf-petition-addon'); ?></h1>
    
    <a href="<?php echo admin_url('admin.php?page=gf_new_form'); ?>" class="page-title-action">
        <?php _e('Add New', 'gf-petition-addon'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (empty($petition_forms)) : ?>
        <div class="notice notice-info">
            <p><?php _e('No active petitions found. Enable petition mode in your Gravity Form settings to see them here.', 'gf-petition-addon'); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Form Title', 'gf-petition-addon'); ?></th>
                    <th><?php _e('Total Signatures', 'gf-petition-addon'); ?></th>
                    <th><?php _e('Actual / Additional', 'gf-petition-addon'); ?></th>
                    <th><?php _e('Goal', 'gf-petition-addon'); ?></th>
                    <th><?php _e('Progress', 'gf-petition-addon'); ?></th>
                    <th><?php _e('Features', 'gf-petition-addon'); ?></th>
                    <th><?php _e('Actions', 'gf-petition-addon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($petition_forms as $form) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($form['title']); ?></strong>
                        </td>
                        <td><?php echo number_format($form['total_signatures']); ?></td>
                        <td><?php echo number_format($form['actual_signatures']); ?> / <?php echo number_format($form['additional_signatures']); ?></td>
                        <td><?php echo number_format($form['goal']); ?></td>
                        <td>
                            <div class="petition-progress-small" style="background:#f5f7fa; height:8px; width:100px; border-radius:4px; overflow:hidden;">
                                <div style="background:#c5203a; width:<?php echo esc_attr(min($form['progress'], 100)); ?>%; height:100%;"></div>
                            </div>
                            <?php echo $form['progress']; ?>%
                        </td>
                        <td>
                            <?php
                            $features = array();
                            // After the features check, add this:
if ($form['auto_increase']) $features[] = __('Auto-increase', 'gf-petition-addon');
if ($form['social_share']) $features[] = __('Social Share', 'gf-petition-addon');
if (!empty($settings['mark_complete'])) $features[] = __('Completed', 'gf-petition-addon');
                            echo implode(', ', $features);
                            ?>
                        </td>
                        <td class="action-links">
                            <a href="<?php echo admin_url('admin.php?page=gf_edit_forms&id=' . $form['id']); ?>" class="button button-small">
                                <?php _e('Edit Form', 'gf-petition-addon'); ?>
                            </a>
                            <a href="<?php echo $this->get_form_settings_url($form['id']); ?>" class="button button-small">
                                <?php _e('Settings', 'gf-petition-addon'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=gf_entries&id=' . $form['id']); ?>" class="button button-small">
                                <?php _e('View Entries', 'gf-petition-addon'); ?>
                            </a>
                            <button class="button button-small reset-signatures" data-form-id="<?php echo esc_attr($form['id']); ?>">
                                <?php _e('Reset Count', 'gf-petition-addon'); ?>
                            </button>
                            <button class="button button-small export-signatures" data-form-id="<?php echo esc_attr($form['id']); ?>">
                                <?php _e('Export CSV', 'gf-petition-addon'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>