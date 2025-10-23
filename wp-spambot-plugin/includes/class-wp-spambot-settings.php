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
        
        add_settings_section(
            'wp_spambot_display_section',
            __('Display Settings', 'wp-spambot'),
            '__return_false',
            'wp_spambot_settings'
        );
        
        add_settings_field(
            'users_per_page',
            __('Users Per Page', 'wp-spambot'),
            array($this, 'render_users_per_page_field'),
            'wp_spambot_settings',
            'wp_spambot_display_section'
        );
        
        add_settings_section(
            'wp_spambot_registration_section',
            __('Registration Protection', 'wp-spambot'),
            '__return_false',
            'wp_spambot_settings'
        );
        
        add_settings_field(
            'stopforumspam_registration_enabled',
            __('Enable Registration Check', 'wp-spambot'),
            array($this, 'render_stopforumspam_registration_enabled_field'),
            'wp_spambot_settings',
            'wp_spambot_registration_section'
        );
        
        add_settings_field(
            'stopforumspam_registration_confidence',
            __('Registration Confidence Threshold', 'wp-spambot'),
            array($this, 'render_stopforumspam_registration_confidence_field'),
            'wp_spambot_settings',
            'wp_spambot_registration_section'
        );
        
        add_settings_field(
            'trusted_email_only_enabled',
            __('Restrict to Trusted Email Providers', 'wp-spambot'),
            array($this, 'render_trusted_email_only_enabled_field'),
            'wp_spambot_settings',
            'wp_spambot_registration_section'
        );
        
        add_settings_field(
            'trusted_email_providers',
            __('Trusted Email Providers', 'wp-spambot'),
            array($this, 'render_trusted_email_providers_field'),
            'wp_spambot_settings',
            'wp_spambot_registration_section'
        );
    }
    
    public function sanitize_settings($input) {
        $output = array();
        $output['stopforumspam_enabled'] = isset($input['stopforumspam_enabled']) ? (bool) $input['stopforumspam_enabled'] : false;
        $output['stopforumspam_api_key'] = isset($input['stopforumspam_api_key']) ? sanitize_text_field($input['stopforumspam_api_key']) : '';
        $output['stopforumspam_threshold'] = isset($input['stopforumspam_threshold']) ? max(0, intval($input['stopforumspam_threshold'])) : 1;
        
        $users_per_page = isset($input['users_per_page']) ? intval($input['users_per_page']) : 20;
        $output['users_per_page'] = max(1, min(500, $users_per_page));
        
        $output['stopforumspam_registration_enabled'] = isset($input['stopforumspam_registration_enabled']) ? (bool) $input['stopforumspam_registration_enabled'] : false;
        $confidence = isset($input['stopforumspam_registration_confidence']) ? intval($input['stopforumspam_registration_confidence']) : 95;
        $output['stopforumspam_registration_confidence'] = max(0, min(100, $confidence));
        
        $output['trusted_email_only_enabled'] = isset($input['trusted_email_only_enabled']) ? (bool) $input['trusted_email_only_enabled'] : false;
        $trusted_providers = array();
        if (!empty($input['trusted_email_providers'])) {
            $raw_providers = is_array($input['trusted_email_providers']) ? $input['trusted_email_providers'] : preg_split('/[\r\n,]+/', $input['trusted_email_providers']);
            if (is_array($raw_providers)) {
                foreach ($raw_providers as $domain) {
                    $domain = sanitize_text_field(strtolower(trim($domain)));
                    if (empty($domain)) {
                        continue;
                    }
                    $domain = preg_replace('/[^a-z0-9\.-]/', '', $domain);
                    if ('' === $domain) {
                        continue;
                    }
                    if (preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*$/i', $domain)) {
                        $trusted_providers[] = $domain;
                    }
                }
            }
        }
        $output['trusted_email_providers'] = array_values(array_unique($trusted_providers));
        
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
    
    public function render_users_per_page_field() {
        $options = get_option('wp_spambot_settings', array());
        $users_per_page = isset($options['users_per_page']) ? intval($options['users_per_page']) : 20;
        $choices = apply_filters('wp_spambot_users_per_page_choices', array(10, 20, 50, 100, 200));
        ?>
        <select name="wp_spambot_settings[users_per_page]">
            <?php foreach ($choices as $choice) :
                $choice = intval($choice);
                if ($choice <= 0) {
                    continue;
                }
            ?>
            <option value="<?php echo esc_attr($choice); ?>" <?php selected($users_per_page, $choice); ?>><?php echo esc_html(number_format_i18n($choice)); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Default number of users displayed per page on the management screen.', 'wp-spambot'); ?></p>
        <?php
    }
    
    public function render_stopforumspam_registration_enabled_field() {
        $options = get_option('wp_spambot_settings', array());
        $enabled = isset($options['stopforumspam_registration_enabled']) ? (bool) $options['stopforumspam_registration_enabled'] : false;
        ?>
        <label>
            <input type="checkbox" name="wp_spambot_settings[stopforumspam_registration_enabled]" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Block registrations when StopForumSpam reports the email as high confidence spam.', 'wp-spambot'); ?>
        </label>
        <?php
    }
    
    public function render_stopforumspam_registration_confidence_field() {
        $options = get_option('wp_spambot_settings', array());
        $confidence = isset($options['stopforumspam_registration_confidence']) ? intval($options['stopforumspam_registration_confidence']) : 95;
        ?>
        <input type="number" name="wp_spambot_settings[stopforumspam_registration_confidence]" value="<?php echo esc_attr($confidence); ?>" min="0" max="100" class="small-text" />
        <p class="description"><?php esc_html_e('Registrations will be blocked when the StopForumSpam confidence is equal to or greater than this value.', 'wp-spambot'); ?></p>
        <?php
    }
    
    public function render_trusted_email_only_enabled_field() {
        $options = get_option('wp_spambot_settings', array());
        $enabled = isset($options['trusted_email_only_enabled']) ? (bool) $options['trusted_email_only_enabled'] : false;
        ?>
        <label>
            <input type="checkbox" name="wp_spambot_settings[trusted_email_only_enabled]" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Allow registrations only from the trusted providers listed below.', 'wp-spambot'); ?>
        </label>
        <?php
    }
    
    public function render_trusted_email_providers_field() {
        $options = get_option('wp_spambot_settings', array());
        $providers = array();
        if (isset($options['trusted_email_providers'])) {
            $providers = is_array($options['trusted_email_providers']) ? $options['trusted_email_providers'] : array();
        }
        $value = implode("\n", array_map('esc_html', $providers));
        ?>
        <textarea name="wp_spambot_settings[trusted_email_providers]" rows="5" cols="50" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php esc_html_e('Enter one domain per line (e.g., gmail.com). Registrations will be limited to these providers when enabled.', 'wp-spambot'); ?></p>
        <?php
    }
}
