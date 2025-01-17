<?php
namespace GFPetition;

if (!defined('ABSPATH')) exit;

// Reference GFForms from global namespace
\GFForms::include_addon_framework();

class PetitionAddOn extends \GFAddOn {
    protected $_version = GF_PETITION_VERSION;
    protected $_min_gravityforms_version = '2.5';
    protected $_slug = 'petitionaddon';
    protected $_path = 'petitionaddon/petitionaddon.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Petition Add-On';
    protected $_short_title = 'Petition Add-On';
    private static $_instance = null;
    protected $counter;
    protected $display;
    protected $admin;

    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function init() {
        parent::init();
        
        // Initialize components
        $this->counter = new Counter($this);
        $this->display = new Display($this);
        $this->admin = new Admin($this);
        
        // Load components
        $this->counter->init();
        $this->display->init();
        $this->admin->init();
    }

    public function get_counter() {
        return $this->counter;
    }

    public function form_settings_fields($form) {
        return array(
            array(
                'title'  => esc_html__('Petition Settings', 'gf-petition-addon'),
                'fields' => array(
                    array(
                        'label'   => esc_html__('Enable Petition', 'gf-petition-addon'),
                        'type'    => 'checkbox',
                        'name'    => 'enable_petition',
                        'tooltip' => esc_html__('Transform this form into a petition', 'gf-petition-addon'),
                        'choices' => array(
                            array(
                                'label' => esc_html__('Enable', 'gf-petition-addon'),
                                'name'  => 'enable_petition',
                            )
                        )
                    ),
                    array(
                        'label'   => esc_html__('Petition Goal', 'gf-petition-addon'),
                        'type'    => 'text',
                        'name'    => 'petition_goal',
                        'tooltip' => esc_html__('Set the target number of signatures', 'gf-petition-addon'),
                        'class'   => 'small',
                    ),
                    array(
                        'label'   => esc_html__('Additional Signatures', 'gf-petition-addon'),
                        'type'    => 'text',
                        'name'    => 'additional_signatures',
                        'tooltip' => esc_html__('Add this number to the actual signature count for display purposes', 'gf-petition-addon'),
                        'class'   => 'small',
                    ),
                    array(
                        'label'   => esc_html__('Display Progress', 'gf-petition-addon'),
                        'type'    => 'checkbox',
                        'name'    => 'show_progress',
                        'tooltip' => esc_html__('Show signature progress bar', 'gf-petition-addon'),
                        'choices' => array(
                            array(
                                'label' => esc_html__('Show', 'gf-petition-addon'),
                                'name'  => 'show_progress',
                            )
                        )
                    ),
                    array(
                        'label'   => esc_html__('Auto-Increase Goal', 'gf-petition-addon'),
                        'type'    => 'checkbox',
                        'name'    => 'auto_increase',
                        'tooltip' => esc_html__('Automatically increase the goal when 80% is reached', 'gf-petition-addon'),
                        'choices' => array(
                            array(
                                'label' => esc_html__('Enable', 'gf-petition-addon'),
                                'name'  => 'auto_increase',
                            )
                        )
                    ),

                    // NEW - mark as complete:

                    array(
                        'label'   => esc_html__('Mark as Complete', 'gf-petition-addon'),
                        'type'    => 'checkbox',
                        'name'    => 'mark_complete',
                        'tooltip' => esc_html__('Mark this petition as successfully completed', 'gf-petition-addon'),
                        'choices' => array(
                            array(
                                'label' => esc_html__('Complete', 'gf-petition-addon'),
                                'name'  => 'mark_complete',
                            )
                        )
                    ),

                    array(
                        'label'   => esc_html__('Social Sharing', 'gf-petition-addon'),
                        'type'    => 'checkbox',
                        'name'    => 'enable_social_share',
                        'tooltip' => esc_html__('Show social sharing buttons after signing', 'gf-petition-addon'),
                        'choices' => array(
                            array(
                                'label' => esc_html__('Enable', 'gf-petition-addon'),
                                'name'  => 'enable_social_share',
                            )
                        )
                    ),

                    array(
                        'label'   => esc_html__('Share Message', 'gf-petition-addon'),
                        'type'    => 'textarea',
                        'name'    => 'share_message',
                        'tooltip' => esc_html__('Message to be shared on social media (use {signature_count} for current count)', 'gf-petition-addon'),
                        'class'   => 'medium',
                        'default_value' => 'I just signed this petition! Join me and {signature_count} others in making a difference.'
                    )
                )
            )
        );
    }

    /**
     * Get form settings
     * 
     * @param array $form The form object
     * @return array The form settings
     */
    public function get_form_settings($form) {
        return parent::get_form_settings($form);
    }

    /**
     * Save form settings
     * 
     * @param array $form The form object
     * @param array $settings The settings to save
     * @return array The updated form object
     */
    public function save_form_settings($form, $settings) {
        return parent::save_form_settings($form, $settings);
    }

    /**
     * Get plugin instance
     * 
     * @return PetitionAddOn Instance of the plugin
     */
    public function get_plugin_instance() {
        return $this;
    }

    /**
     * Scripts to be loaded in the admin
     */
    public function scripts() {
        $scripts = array(
            array(
                'handle'    => 'gf_petition_admin',
                'src'       => GF_PETITION_URL . 'admin/js/admin.js',
                'version'   => $this->_version,
                'deps'      => array('jquery'),
                'enqueue'   => array(
                    array('admin_page' => array('form_settings', 'plugin_settings', 'plugin_page'))
                )
            )
        );

        return array_merge(parent::scripts(), $scripts);
    }

    /**
     * Styles to be loaded in the admin
     */
    public function styles() {
        $styles = array(
            array(
                'handle'  => 'gf_petition_admin',
                'src'     => GF_PETITION_URL . 'admin/css/admin.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array('admin_page' => array('form_settings', 'plugin_settings', 'plugin_page'))
                )
            )
        );

        return array_merge(parent::styles(), $styles);
    }
}