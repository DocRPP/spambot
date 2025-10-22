<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Spambot {
    
    protected $admin;
    protected $settings;
    
    public function __construct() {
        $this->admin = new WP_Spambot_Admin();
        $this->settings = new WP_Spambot_Settings();
    }
    
    public function run() {
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_init', array($this->settings, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_notices', array($this->admin, 'show_admin_notices'));
    }
}
