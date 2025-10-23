<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Spambot {
    
    protected $admin;
    protected $settings;
    protected $spam_service;
    
    public function __construct() {
        $this->admin = new WP_Spambot_Admin();
        $this->settings = new WP_Spambot_Settings();
        $this->spam_service = new WP_Spambot_Spam_Service();
    }
    
    public function run() {
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_init', array($this->settings, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_notices', array($this->admin, 'show_admin_notices'));
        add_filter('registration_errors', array($this, 'validate_registration'), 10, 3);
    }
    
    public function validate_registration($errors, $sanitized_user_login, $user_email) {
        $result = $this->spam_service->check_registration_email($user_email, $sanitized_user_login);
        if (!$result['allowed'] && !empty($result['error'])) {
            $errors->add('spam_detected', $result['error']);
        }
        return $errors;
    }
}
