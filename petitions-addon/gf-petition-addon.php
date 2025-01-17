<?php
/**
 * Plugin Name: GF Petition Extension
 * Description: Adds petition functionality to Gravity Forms
 * Version: 1.0
 * Author: Al Bowd
 * Text Domain: gf-petition-addon
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('GF_PETITION_VERSION', '1.0');
define('GF_PETITION_PATH', plugin_dir_path(__FILE__));
define('GF_PETITION_URL', plugin_dir_url(__FILE__));

// Initialize plugin on init hook
add_action('init', 'gf_petition_init');
add_action('plugins_loaded', 'gf_petition_load_textdomain');

/**
 * Load plugin text domain
 */
function gf_petition_load_textdomain() {
    load_plugin_textdomain('gf-petition-addon', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Initialize plugin
 */
function gf_petition_init() {
    if (!class_exists('GFCommon')) {
        add_action('admin_notices', 'gf_petition_gravity_forms_notice');
        return;
    }

    // Check required files
    $required_files = array(
        'includes/class-petition-addon.php',
        'includes/class-petition-counter.php',
        'includes/class-petition-display.php',
        'admin/class-petition-admin.php'
    );

    $missing_files = array();
    foreach ($required_files as $file) {
        if (!file_exists(GF_PETITION_PATH . $file)) {
            $missing_files[] = $file;
        }
    }

    if (!empty($missing_files)) {
        add_action('admin_notices', function() use ($missing_files) {
            gf_petition_missing_files_notice($missing_files);
        });
        return;
    }

    // Load required files
    require_once GF_PETITION_PATH . 'includes/class-petition-addon.php';
    require_once GF_PETITION_PATH . 'includes/class-petition-counter.php';
    require_once GF_PETITION_PATH . 'includes/class-petition-display.php';
    require_once GF_PETITION_PATH . 'admin/class-petition-admin.php';

    // Initialize the plugin if Gravity Forms is active
    if (class_exists('GFForms')) {
        GFForms::include_addon_framework();
        
        if (!function_exists('gf_petition_addon')) {
            function gf_petition_addon() {
                return GFPetition\PetitionAddOn::get_instance();
            }
        }

        GFAddOn::register('GFPetition\PetitionAddOn');
        $GLOBALS['gf_petition_addon'] = gf_petition_addon();
    }
}

/**
 * Admin notice for missing Gravity Forms
 */
function gf_petition_gravity_forms_notice() {
    ?>
    <div class="error notice">
        <p><?php _e('Gravity Forms Petition Add-On requires Gravity Forms to be installed and activated.', 'gf-petition-addon'); ?></p>
    </div>
    <?php
}

/**
 * Admin notice for missing files
 */
function gf_petition_missing_files_notice($missing_files) {
    ?>
    <div class="error notice">
        <p><?php _e('Gravity Forms Petition Add-On is missing required files:', 'gf-petition-addon'); ?></p>
        <ul>
            <?php foreach ($missing_files as $file): ?>
                <li><?php echo esc_html($file); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}