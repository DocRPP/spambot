<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Spambot_Settings {
    
    public function register_settings() {
        register_setting('wp_spambot_settings_group', 'wp_spambot_settings', array($this, 'sanitize_settings'));
        
        add_settings_section(
            'wp_spambot_main_section',
            __('Spam Service Settings', 'wp-spambot'),
            '__return_false',
            'wp_spambot_settings'
        );
        
        add_settings_field(
            'stopforumspam_enabled',
            __('Enable StopForumSpam', 'wp-spambot'),
            array($this, 'render_stopforumspam_enabled_field'),
            'wp_spambot_settings',
            'wp_spambot_main_section'
        );
        
        add_settings_field(
            'stopforumspam_api_key',
            __('StopForumSpam API Key', 'wp-spambot'),
            array($this, 'render_stopforumspam_api_key_field'),
            'wp_spambot_settings',
            'wp_spambot_main_section'
        );
        
        add_settings_field(
            'stopforumspam_threshold',
            __('Spam Threshold', 'wp-spambot'),
            array($this, 'render_stopforumspam_threshold_field'),
            'wp_spambot_settings',
            'wp_spambot_main_section'
        );
    }
    
    public function sanitize_settings($input) {
        $output = array();
        $output['stopforumspam_enabled'] = isset($input['stopforumspam_enabled']) ? (bool) $input['stopforumspam_enabled'] : false;
        $output['stopforumspam_api_key'] = isset($input['stopforumspam_api_key']) ? sanitize_text_field($input['stopforumspam_api_key']) : '';
        $output['stopforumspam_threshold'] = isset($input['stopforumspam_threshold']) ? max(0, intval($input['stopforumspam_threshold'])) : 1;
        
        return $output;
    }
    
    public function render_stopforumspam_enabled_field() {
        $options = get_option('wp_spambot_settings', array());
        $enabled = isset($options['stopforumspam_enabled']) ? (bool) $options['stopforumspam_enabled'] : false;
        ?>
        <label>
            <input type="checkbox" name="wp_spambot_settings[stopforumspam_enabled]" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Enable checking against StopForumSpam database.', 'wp-spambot'); ?>
        </label>
        <?php
    }
    
    public function render_stopforumspam_api_key_field() {
        $options = get_option('wp_spambot_settings', array());
        $api_key = isset($options['stopforumspam_api_key']) ? $options['stopforumspam_api_key'] : '';
        ?>
        <input type="text" name="wp_spambot_settings[stopforumspam_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Optional: provide your StopForumSpam API key for authenticated requests.', 'wp-spambot'); ?></p>
        <?php
    }
    
    public function render_stopforumspam_threshold_field() {
        $options = get_option('wp_spambot_settings', array());
        $threshold = isset($options['stopforumspam_threshold']) ? intval($options['stopforumspam_threshold']) : 1;
        ?>
        <input type="number" name="wp_spambot_settings[stopforumspam_threshold]" value="<?php echo esc_attr($threshold); ?>" min="0" class="small-text" />
        <p class="description"><?php esc_html_e('Number of occurrences required to flag a user as spam. Set to 0 to flag every positive match.', 'wp-spambot'); ?></p>
        <?php
    }
}
