<?php
namespace GFPetition;

if (!defined('ABSPATH')) exit;

class Display {
    private $addon;

    public function __construct($addon) {
        $this->addon = $addon;
    }

    public function init() {
        add_action('gform_get_form_filter', array($this, 'maybe_add_progress_bar'), 10, 2);
        add_action('gform_confirmation', array($this, 'maybe_add_social_share'), 10, 4);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue necessary scripts for the petition functionality
     */
    public function enqueue_scripts($form = '', $is_ajax = false) {
        wp_enqueue_script(
            'gf-petition-script', 
            GF_PETITION_URL . 'js/petition.js', 
            array('jquery'), 
            GF_PETITION_VERSION, 
            true
        );
        
        wp_localize_script('gf-petition-script', 'gfPetition', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gf_petition_nonce')
        ));
    }

    /**
     * Add progress bar to petition forms if enabled
     */
    public function maybe_add_progress_bar($form_string, $form) {
        $settings = $this->addon->get_form_settings($form);
        
        if (empty($settings['enable_petition']) || empty($settings['show_progress'])) {
            return $form_string;
        }

        $progress_html = $this->get_progress_bar_html($form['id']);
        return $progress_html . $form_string;
    }

    /**
     * Generate HTML for the progress bar
     */
public function get_progress_bar_html($form_id) {
        $form = \GFAPI::get_form($form_id);
        if (!$form) {
            return '';
        }

        $settings = $this->addon->get_form_settings($form);
        $current_count = $this->addon->get_counter()->get_signature_count($form_id);
        $goal = !empty($settings['petition_goal']) ? absint($settings['petition_goal']) : 0;
        $is_complete = !empty($settings['mark_complete']);
        
        // If current count exceeds goal and not complete, adjust goal to next milestone
        if (!$is_complete && $current_count >= $goal) {
            if ($current_count < 100) {
                $goal = ceil($current_count / 10) * 10; // Round to next 10
            } elseif ($current_count < 1000) {
                $goal = ceil($current_count / 100) * 100; // Round to next 100
            } elseif ($current_count < 10000) {
                $goal = ceil($current_count / 500) * 500; // Round to next 500
            } else {
                $goal = ceil($current_count / 1000) * 1000; // Round to next 1000
            }
        }

        if ($goal === 0) {
            return '';
        }

        // If complete, force 100% width, otherwise calculate normally
        $percentage = $is_complete ? 100 : min(($current_count / $goal) * 100, 100);
        
        $progress_color = $is_complete ? 
            'background:linear-gradient(90deg, #2ea44f, #3cc76f)' : // Green gradient for completed
            'background:linear-gradient(90deg, #c5203a, #e84258)';  // Red gradient for in progress
        
        $shadow_color = $is_complete ? 
            'rgba(46, 164, 79, 0.3)' : // Green shadow
            'rgba(197, 32, 58, 0.3)';  // Red shadow

        $status_text = $is_complete ? 
            sprintf(esc_html__('Target Achieved! %d signatures', 'gf-petition-addon'), $current_count) :
            sprintf(esc_html__('Signatures: %d of %d', 'gf-petition-addon'), $current_count, $goal);
        
        $html = '<div class="petition-progress" style="display:block !important; border-radius: 8px;" data-form-id="' . esc_attr($form_id) . '">';
        $html .= '<p style="color: #333; font-size: 16px; font-weight: 600;" class="petition-count">' . $status_text . '</p>';
        $html .= '<div class="progress-bar" style="display:block !important; background:#f5f7fa; height:12px; width:100%; border-radius:10px; position:relative; overflow:hidden;">';
        $html .= '<div class="progress" style="display:block !important; position:absolute; left:0; top:0; ' . $progress_color . '; box-shadow: 0 0 10px ' . $shadow_color . '; width:' . esc_attr($percentage) . '%; height:100%; transition: all 0.5s ease;">';
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Add social sharing buttons to confirmation message
     */
    public function maybe_add_social_share($confirmation, $form, $entry, $ajax) {
        $settings = $this->addon->get_form_settings($form);
        
        if (empty($settings['enable_petition']) || empty($settings['enable_social_share'])) {
            return $confirmation;
        }

        // Get current signature count (including additional signatures)
        $current_count = $this->addon->get_counter()->get_signature_count($form['id']); // Using getter method

        // Get share message and replace placeholders
        $share_message = !empty($settings['share_message']) 
            ? $settings['share_message'] 
            : 'I just signed this petition! Join me and {signature_count} others in making a difference.';
        
        $share_message = str_replace('{signature_count}', $current_count, $share_message);
        $share_message = urlencode($share_message);
        
        // Get current page URL
        $page_url = urlencode(get_permalink());
        
        // Generate social share buttons HTML
        $social_html = $this->get_social_share_html($page_url, $share_message);

        // Add social buttons to confirmation message
        if (is_string($confirmation)) {
            $confirmation .= $social_html;
        } elseif (is_array($confirmation) && isset($confirmation['message'])) {
            $confirmation['message'] .= $social_html;
        }

        return $confirmation;
    }

    /**
     * Generate HTML for social share buttons
     */
    private function get_social_share_html($page_url, $share_message) {
        $html = '<div class="petition-social-share" style="margin-top: 20px; padding: 20px; background: #f8f8f8; border-radius: 8px;">';
        $html .= '<p style="font-weight: bold; margin-bottom: 15px;">' . esc_html__('Share this petition:', 'gf-petition-addon') . '</p>';
        $html .= '<div class="share-buttons" style="display: flex; gap: 10px;">';
        
        // Facebook
        $html .= sprintf(
            '<a href="https://www.facebook.com/sharer/sharer.php?u=%s&quote=%s" target="_blank" class="share-button facebook" style="display: inline-flex; align-items: center; padding: 8px 16px; background: #1877f2; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">%s</a>',
            $page_url,
            $share_message,
            esc_html__('Share on Facebook', 'gf-petition-addon')
        );
        
        // Twitter
        $html .= sprintf(
            '<a href="https://twitter.com/intent/tweet?text=%s&url=%s" target="_blank" class="share-button twitter" style="display: inline-flex; align-items: center; padding: 8px 16px; background: #1da1f2; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">%s</a>',
            $share_message,
            $page_url,
            esc_html__('Share on Twitter', 'gf-petition-addon')
        );
        
        // LinkedIn
        $html .= sprintf(
            '<a href="https://www.linkedin.com/shareArticle?mini=true&url=%s&title=%s" target="_blank" class="share-button linkedin" style="display: inline-flex; align-items: center; padding: 8px 16px; background: #0a66c2; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">%s</a>',
            $page_url,
            $share_message,
            esc_html__('Share on LinkedIn', 'gf-petition-addon')
        );
        
        $html .= '</div></div>';
        
        return $html;
    }
}