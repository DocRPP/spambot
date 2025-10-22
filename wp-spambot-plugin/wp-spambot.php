<?php
/**
 * Plugin Name: WP Spambot
 * Plugin URI: https://github.com/yourusername/wp-spambot
 * Description: WordPress plugin to detect and manage spam user accounts using external spam databases like StopForumSpam
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-spambot
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_SPAMBOT_VERSION', '1.0.0');
define('WP_SPAMBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_SPAMBOT_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WP_SPAMBOT_PLUGIN_DIR . 'includes/class-wp-spambot.php';
require_once WP_SPAMBOT_PLUGIN_DIR . 'includes/class-wp-spambot-admin.php';
require_once WP_SPAMBOT_PLUGIN_DIR . 'includes/class-wp-spambot-spam-service.php';
require_once WP_SPAMBOT_PLUGIN_DIR . 'includes/class-wp-spambot-settings.php';

function wp_spambot_init() {
    $plugin = new WP_Spambot();
    $plugin->run();
}

add_action('plugins_loaded', 'wp_spambot_init');

register_activation_hook(__FILE__, 'wp_spambot_activate');
register_deactivation_hook(__FILE__, 'wp_spambot_deactivate');

function wp_spambot_activate() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    $default_settings = array(
        'stopforumspam_enabled' => false,
        'stopforumspam_api_key' => '',
        'stopforumspam_threshold' => 1,
    );
    
    if (!get_option('wp_spambot_settings')) {
        add_option('wp_spambot_settings', $default_settings);
    }
}

function wp_spambot_deactivate() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
}
