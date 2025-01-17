<?php
namespace GFPetition;

if (!defined('ABSPATH')) exit;

class Admin {
    private $addon;

    public function __construct($addon) {
        $this->addon = $addon;
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_petitions_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_reset_signatures', array($this, 'ajax_reset_signatures'));
        add_action('wp_ajax_export_signatures', array($this, 'ajax_export_signatures'));
    }

    /**
     * Add the Petitions menu item to WordPress admin
     */
    public function add_petitions_menu() {
        add_menu_page(
            __('Petitions', 'gf-petition-addon'),
            __('Petitions', 'gf-petition-addon'),
            'manage_options',
            'gf-petitions',
            array($this, 'render_petitions_page'),
            'dashicons-clipboard',
            20
        );
    }

    /**
     * Enqueue admin-specific scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_gf-petitions') {
            return;
        }

        wp_enqueue_style(
            'gf-petition-admin-style',
            GF_PETITION_URL . 'admin/css/admin.css',
            array(),
            GF_PETITION_VERSION
        );

        wp_enqueue_script(
            'gf-petition-admin-script',
            GF_PETITION_URL . 'admin/js/admin.js',
            array('jquery'),
            GF_PETITION_VERSION,
            true
        );

        wp_localize_script('gf-petition-admin-script', 'gfPetitionAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gf_petition_admin_nonce'),
            'confirmReset' => __('Are you sure you want to reset the signature count? This cannot be undone.', 'gf-petition-addon')
        ));
    }

    /**
     * Render the petitions admin page
     */
    public function render_petitions_page() {
        // Get all forms
        $forms = \GFAPI::get_forms();
        $petition_forms = array();
        
        // Filter for forms with petitions enabled
        foreach ($forms as $form) {
            $settings = $this->addon->get_form_settings($form);
            if (!empty($settings['enable_petition'])) {
                $actual_count = $this->addon->get_counter()->get_actual_signature_count($form['id']);
                $total_count = $this->addon->get_counter()->get_signature_count($form['id']);
                $additional = !empty($settings['additional_signatures']) ? absint($settings['additional_signatures']) : 0;
                $goal = !empty($settings['petition_goal']) ? absint($settings['petition_goal']) : 0;
                
                $petition_forms[] = array(
                    'id' => $form['id'],
                    'title' => $form['title'],
                    'actual_signatures' => $actual_count,
                    'additional_signatures' => $additional,
                    'total_signatures' => $total_count,
                    'goal' => $goal,
                    'progress' => $goal ? round(($total_count / $goal) * 100, 1) : 0,
                    'auto_increase' => !empty($settings['auto_increase']),
                    'social_share' => !empty($settings['enable_social_share']),
                    'is_complete' => !empty($settings['mark_complete'])
                );
            }
        }
        
        // Include the admin view template
        include GF_PETITION_PATH . 'admin/views/petitions-list.php';
    }

    /**
     * AJAX handler for resetting signature count
     */
    public function ajax_reset_signatures() {
        check_ajax_referer('gf_petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'gf-petition-addon'));
        }

        $form_id = intval($_POST['form_id']);
        $count_key = 'petition_signatures_' . $form_id;
        
        if (update_option($count_key, 0)) {
            wp_send_json_success(array(
                'message' => __('Signature count has been reset successfully.', 'gf-petition-addon')
            ));
        } else {
            wp_send_json_error(__('Failed to reset signature count.', 'gf-petition-addon'));
        }
    }

    /**
     * AJAX handler for exporting signatures
     */
    public function ajax_export_signatures() {
        check_ajax_referer('gf_petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'gf-petition-addon'));
        }

        $form_id = intval($_POST['form_id']);
        
        // Get form
        $form = \GFAPI::get_form($form_id);
        if (!$form) {
            wp_send_json_error(__('Form not found.', 'gf-petition-addon'));
        }

        // Get entries
        $search_criteria = array();
        $sorting = array('key' => 'date_created', 'direction' => 'DESC');
        $entries = \GFAPI::get_entries($form_id, $search_criteria, $sorting);

        if (is_wp_error($entries)) {
            wp_send_json_error(__('Error retrieving entries.', 'gf-petition-addon'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="petition-signatures-' . $form_id . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output handle
        $output = fopen('php://output', 'w');

        // Get form fields for headers
        $headers = array('Entry ID', 'Date', 'IP');
        $field_ids = array(); // Store field IDs to maintain order

        foreach ($form['fields'] as $field) {
            if ($field['displayOnly']) {
                continue;
            }

            // Handle complex fields (like name fields)
            if (is_array($field->inputs)) {
                foreach ($field->inputs as $input) {
                    $headers[] = $field['label'] . ' (' . $input['label'] . ')';
                    $field_ids[] = $input['id'];
                }
            } else {
                $headers[] = $field['label'];
                $field_ids[] = $field['id'];
            }
        }

        // Write headers
        fputcsv($output, $headers);

        // Write entries
        foreach ($entries as $entry) {
            $row = array(
                $entry['id'],
                $entry['date_created'],
                $entry['ip']
            );

            // Add field values in the same order as headers
            foreach ($field_ids as $field_id) {
                $value = rgar($entry, (string)$field_id);
                
                // Handle special field types if needed
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $row[] = $value;
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Helper method to get form settings URL
     */
    public function get_form_settings_url($form_id) {
        return admin_url('admin.php?page=gf_edit_forms&view=settings&subview=' . 
            $this->addon->get_slug() . '&id=' . $form_id);
    }
}