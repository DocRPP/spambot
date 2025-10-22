<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Spambot_Admin {
    
    protected $spam_service;
    protected $notices = array();
    
    public function __construct() {
        $this->spam_service = new WP_Spambot_Spam_Service();
        add_action('admin_post_wp_spambot_bulk_action', array($this, 'handle_bulk_action'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Spambot Manager', 'wp-spambot'),
            __('Spambot', 'wp-spambot'),
            'manage_options',
            'wp_spambot',
            array($this, 'render_user_management_page'),
            'dashicons-shield'
        );
        
        add_submenu_page(
            'wp_spambot',
            __('User Management', 'wp-spambot'),
            __('User Management', 'wp-spambot'),
            'manage_options',
            'wp_spambot',
            array($this, 'render_user_management_page')
        );
        
        add_submenu_page(
            'wp_spambot',
            __('Settings', 'wp-spambot'),
            __('Settings', 'wp-spambot'),
            'manage_options',
            'wp_spambot_settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_styles($hook) {
        if (strpos($hook, 'wp_spambot') === false) {
            return;
        }
        
        wp_enqueue_style('wp-spambot-admin', WP_SPAMBOT_PLUGIN_URL . 'assets/css/admin.css', array(), WP_SPAMBOT_VERSION);
    }
    
    public function show_admin_notices() {
        $messages = get_transient('wp_spambot_admin_notices');
        if ($messages) {
            foreach ($messages as $message) {
                printf('<div class="notice notice-%1$s"><p>%2$s</p></div>', esc_attr($message['type']), esc_html($message['message']));
            }
            delete_transient('wp_spambot_admin_notices');
        }
    }
    
    public function render_user_management_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $users_per_page = 20;
        $offset = ($paged - 1) * $users_per_page;
        
        $args = array(
            'number' => $users_per_page,
            'offset' => $offset,
            'orderby' => 'registered',
            'order' => 'DESC',
        );
        
        $user_query = new WP_User_Query($args);
        $total_users = $user_query->get_total();
        $total_pages = ceil($total_users / $users_per_page);
        $users = $user_query->get_results();
        
        include WP_SPAMBOT_PLUGIN_DIR . 'templates/admin-user-management.php';
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include WP_SPAMBOT_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    public function handle_bulk_action() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('wp_spambot_bulk_action');
        
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : array();
        
        if (empty($action) || empty($user_ids)) {
            $this->add_admin_notice(__('No action or users selected.', 'wp-spambot'), 'warning');
            wp_redirect(wp_get_referer());
            exit;
        }
        
        switch ($action) {
            case 'check_spam':
                $this->bulk_check_spam($user_ids);
                break;
            case 'flag_spam':
                $this->bulk_flag_spam($user_ids);
                break;
            case 'delete_users':
                $this->bulk_delete_users($user_ids);
                break;
            default:
                $this->add_admin_notice(__('Invalid action selected.', 'wp-spambot'), 'error');
                break;
        }
        
        wp_redirect(wp_get_referer());
        exit;
    }
    
    protected function bulk_check_spam($user_ids) {
        if (!$this->spam_service->has_active_services()) {
            $this->add_admin_notice(__('No spam services are enabled. Please configure a service on the settings page.', 'wp-spambot'), 'warning');
            return;
        }
        
        $results = array(
            'flagged' => 0,
            'clean' => 0,
            'errors' => 0
        );
        
        foreach ($user_ids as $user_id) {
            $user = get_user_by('id', $user_id);
            if (!$user) {
                $results['errors']++;
                continue;
            }
            $response = $this->spam_service->check_user($user);
            if ($response['is_spam']) {
                update_user_meta($user_id, 'wp_spambot_status', array(
                    'status' => 'flagged',
                    'service' => $response['service'],
                    'confidence' => $response['confidence'],
                    'frequency' => $response['frequency'],
                    'details' => $response['details'],
                    'last_checked' => current_time('mysql'),
                ));
                $results['flagged']++;
            } else {
                update_user_meta($user_id, 'wp_spambot_status', array(
                    'status' => 'clean',
                    'service' => $response['service'],
                    'confidence' => $response['confidence'],
                    'frequency' => $response['frequency'],
                    'details' => $response['details'],
                    'last_checked' => current_time('mysql'),
                ));
                $results['clean']++;
            }
        }
        
        $this->add_admin_notice(
            sprintf(
                __('Spam check complete: %1$s flagged, %2$s clean, %3$s errors.', 'wp-spambot'),
                $results['flagged'],
                $results['clean'],
                $results['errors']
            ),
            'success'
        );
    }
    
    protected function bulk_flag_spam($user_ids) {
        foreach ($user_ids as $user_id) {
            update_user_meta($user_id, 'wp_spambot_status', array(
                'status' => 'flagged',
                'service' => 'manual',
                'confidence' => null,
                'last_checked' => current_time('mysql'),
            ));
        }
        $this->add_admin_notice(__('Selected users have been flagged as spam.', 'wp-spambot'), 'success');
    }
    
    protected function bulk_delete_users($user_ids) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($user_ids as $user_id) {
            wp_delete_user($user_id);
        }
        $this->add_admin_notice(__('Selected users have been deleted.', 'wp-spambot'), 'success');
    }
    
    protected function add_admin_notice($message, $type = 'info') {
        $notices = get_transient('wp_spambot_admin_notices');
        if (!$notices) {
            $notices = array();
        }
        $notices[] = array('message' => $message, 'type' => $type);
        set_transient('wp_spambot_admin_notices', $notices, 30);
    }
}
