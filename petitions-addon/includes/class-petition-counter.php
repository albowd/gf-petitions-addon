<?php
namespace GFPetition;

if (!defined('ABSPATH')) exit;

class Counter {
    private $addon;

    public function __construct($addon) {
        $this->addon = $addon;
    }

    public function init() {
        add_action('gform_after_submission', array($this, 'update_petition_count'), 10, 2);
        add_action('wp_ajax_update_signature_count', array($this, 'ajax_update_signature_count'));
        add_action('wp_ajax_nopriv_update_signature_count', array($this, 'ajax_update_signature_count'));
    }

    /**
     * Updates the petition count after form submission
     */
    public function update_petition_count($entry, $form) {
        $settings = $this->addon->get_form_settings($form);
        if (empty($settings['enable_petition'])) {
            return;
        }

        $count_key = 'petition_signatures_' . $form['id'];
        $current_count = get_option($count_key, 0);
        $new_count = $current_count + 1;
        update_option($count_key, $new_count);

        // Check if auto-increase is enabled
        if (!empty($settings['auto_increase'])) {
            $current_goal = absint($settings['petition_goal']);
            
            // If we've reached 80% of the goal
            if ($new_count >= ($current_goal * 0.8)) {
                // Calculate new goal
                $new_goal = $this->get_next_goal($current_goal);
                
                // Update the form settings with new goal
                $settings['petition_goal'] = $new_goal;
                $this->addon->get_plugin_instance()->save_form_settings($form, $settings);
            }
        }
    }

    /**
     * AJAX handler for updating signature count
     */
    public function ajax_update_signature_count() {
        check_ajax_referer('gf_petition_nonce', 'nonce');
        
        $form_id = intval($_POST['form_id']);
        $form = \GFAPI::get_form($form_id);
        if (!$form) {
            wp_send_json_error();
        }

        $settings = $this->addon->get_form_settings($form);
        $total_count = $this->get_signature_count($form_id);
        $goal = !empty($settings['petition_goal']) ? absint($settings['petition_goal']) : 0;
        
        wp_send_json_success(array(
            'count' => $total_count,
            'goal' => $goal,
            'percentage' => $goal ? min(($total_count / $goal) * 100, 100) : 0
        ));
    }

    /**
     * Calculate the next goal based on current goal
     */
    private function get_next_goal($current_goal) {
        if ($current_goal < 100) {
            return ceil($current_goal / 10) * 10 + 10; // Round to next 10
        } elseif ($current_goal < 1000) {
            return ceil($current_goal / 100) * 100 + 100; // Round to next 100
        } elseif ($current_goal < 10000) {
            return ceil($current_goal / 500) * 500 + 500; // Round to next 500
        } else {
            return ceil($current_goal / 1000) * 1000 + 1000; // Round to next 1000
        }
    }

    /**
     * Get signature count for a form
     */
    public function get_signature_count($form_id, $include_additional = true) {
        $count_key = 'petition_signatures_' . $form_id;
        $actual_count = get_option($count_key, 0);
        
        if (!$include_additional) {
            return $actual_count;
        }
        
        // Get additional signatures from form settings
        $form = \GFAPI::get_form($form_id);
        if (!$form) {
            return $actual_count;
        }
        
        $settings = $this->addon->get_form_settings($form);
        $additional = !empty($settings['additional_signatures']) ? absint($settings['additional_signatures']) : 0;
        
        return $actual_count + $additional;
    }
    
    /**
     * Get actual signature count without additional signatures
     */
    public function get_actual_signature_count($form_id) {
        return $this->get_signature_count($form_id, false);
    }

    /**
     * Reset signature count for a form
     */
    public function reset_signature_count($form_id) {
        $count_key = 'petition_signatures_' . $form_id;
        return update_option($count_key, 0);
    }
}