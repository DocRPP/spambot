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
        
        $settings = get_option('wp_spambot_settings', array());
        $default_per_page = isset($settings['users_per_page']) ? intval($settings['users_per_page']) : 20;
        
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $users_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : $default_per_page;
        $users_per_page = max(1, min(500, $users_per_page));
        $offset = ($paged - 1) * $users_per_page;
        
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'registered';
        $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), array('ASC', 'DESC')) ? strtoupper($_GET['order']) : 'DESC';
        
        $filter_role = isset($_GET['filter_role']) ? sanitize_text_field($_GET['filter_role']) : '';
        $filter_spam_status = isset($_GET['filter_spam_status']) ? sanitize_text_field($_GET['filter_spam_status']) : '';
        $filter_spam_reason = isset($_GET['filter_spam_reason']) ? sanitize_text_field($_GET['filter_spam_reason']) : '';
        $filter_posts_min = isset($_GET['filter_posts_min']) ? absint($_GET['filter_posts_min']) : '';
        $filter_posts_max = isset($_GET['filter_posts_max']) ? absint($_GET['filter_posts_max']) : '';
        
        $args = array(
            'number' => $users_per_page,
            'offset' => $offset,
            'orderby' => $this->get_orderby_field($orderby),
            'order' => $order,
        );
        
        if (!empty($filter_role)) {
            $args['role'] = $filter_role;
        }
        
        $meta_query = array();
        
        if (!empty($filter_spam_status)) {
            if ($filter_spam_status === 'flagged') {
                $meta_query[] = array(
                    'key' => 'wp_spambot_is_flagged',
                    'value' => '1',
                    'compare' => '='
                );
            } elseif ($filter_spam_status === 'clean') {
                $meta_query[] = array(
                    'key' => 'wp_spambot_is_flagged',
                    'value' => '0',
                    'compare' => '='
                );
                $meta_query[] = array(
                    'key' => 'wp_spambot_status',
                    'compare' => 'EXISTS'
                );
            } elseif ($filter_spam_status === 'unchecked') {
                $meta_query[] = array(
                    'key' => 'wp_spambot_status',
                    'compare' => 'NOT EXISTS'
                );
            }
        }
        
        $apply_reason_filter = !empty($filter_spam_reason) && $filter_spam_status !== 'unchecked';
        if ($apply_reason_filter) {
            if ($filter_spam_reason === 'email_only') {
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wp_spambot_flag_email',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'wp_spambot_flag_username',
                        'value' => '0',
                        'compare' => '='
                    )
                );
            } elseif ($filter_spam_reason === 'username_only') {
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wp_spambot_flag_username',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'wp_spambot_flag_email',
                        'value' => '0',
                        'compare' => '='
                    )
                );
            } elseif ($filter_spam_reason === 'both') {
                $meta_query[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wp_spambot_flag_email',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'wp_spambot_flag_username',
                        'value' => '1',
                        'compare' => '='
                    )
                );
            }
        }
        
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        if (!empty($filter_posts_min) || !empty($filter_posts_max)) {
            $users = array_filter($users, function($user) use ($filter_posts_min, $filter_posts_max) {
                $post_count = count_user_posts($user->ID);
                if (!empty($filter_posts_min) && $post_count < $filter_posts_min) {
                    return false;
                }
                if (!empty($filter_posts_max) && $post_count > $filter_posts_max) {
                    return false;
                }
                return true;
            });
        }
        
        if ($orderby === 'posts') {
            usort($users, function($a, $b) use ($order) {
                $count_a = count_user_posts($a->ID);
                $count_b = count_user_posts($b->ID);
                if ($order === 'ASC') {
                    return $count_a - $count_b;
                } else {
                    return $count_b - $count_a;
                }
            });
        }
        
        $total_users = $user_query->get_total();
        $total_pages = ceil($total_users / $users_per_page);
        
        $current_url = remove_query_arg(array('paged'));
        
        include WP_SPAMBOT_PLUGIN_DIR . 'templates/admin-user-management.php';
    }
    
    protected function get_orderby_field($orderby) {
        $allowed = array(
            'username' => 'user_login',
            'email' => 'user_email',
            'registered' => 'registered',
            'role' => 'display_name',
            'posts' => 'ID'
        );
        
        return isset($allowed[$orderby]) ? $allowed[$orderby] : 'registered';
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
                    'factors' => isset($response['factors']) ? $response['factors'] : array(),
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
                    'factors' => isset($response['factors']) ? $response['factors'] : array(),
                    'last_checked' => current_time('mysql'),
                ));
                $results['clean']++;
            }
            $email_factor = !empty($response['factors']['email']);
            $username_factor = !empty($response['factors']['username']);
            update_user_meta($user_id, 'wp_spambot_is_flagged', $response['is_spam'] ? 1 : 0);
            update_user_meta($user_id, 'wp_spambot_flag_email', $email_factor ? 1 : 0);
            update_user_meta($user_id, 'wp_spambot_flag_username', $username_factor ? 1 : 0);
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
        $reported_count = 0;
        $report_failures = array();
        
        foreach ($user_ids as $user_id) {
            $user = get_user_by('id', $user_id);
            if (!$user) {
                continue;
            }
            
            update_user_meta($user_id, 'wp_spambot_status', array(
                'status' => 'flagged',
                'service' => 'manual',
                'confidence' => null,
                'frequency' => null,
                'details' => array(),
                'factors' => array('email' => false, 'username' => false),
                'last_checked' => current_time('mysql'),
            ));
            update_user_meta($user_id, 'wp_spambot_is_flagged', 1);
            update_user_meta($user_id, 'wp_spambot_flag_email', 0);
            update_user_meta($user_id, 'wp_spambot_flag_username', 0);
            
            $result = $this->spam_service->report_spam_to_stopforumspam($user);
            if ($result['success']) {
                $reported_count++;
            } else {
                $report_failures[] = array(
                    'user' => $user->user_login,
                    'message' => isset($result['message']) ? $result['message'] : __('Unknown error while reporting to StopForumSpam.', 'wp-spambot'),
                );
            }
        }
        
        $message = sprintf(
            __('Selected users have been flagged as spam. %1$d reported to StopForumSpam.', 'wp-spambot'),
            $reported_count
        );
        
        $this->add_admin_notice($message, 'success');
        
        if (!empty($report_failures)) {
            $failure_count = count($report_failures);
            $failure_summaries = array();
            foreach (array_slice($report_failures, 0, 3) as $failure) {
                $failure_summaries[] = sprintf(
                    __('%1$s (%2$s)', 'wp-spambot'),
                    $failure['user'],
                    $failure['message']
                );
            }
            if ($failure_count > 3) {
                $failure_summaries[] = sprintf(__('and %d more', 'wp-spambot'), $failure_count - 3);
            }
            $error_message = sprintf(
                _n(
                    'Could not report %1$d user to StopForumSpam: %2$s',
                    'Could not report %1$d users to StopForumSpam: %2$s',
                    $failure_count,
                    'wp-spambot'
                ),
                $failure_count,
                implode('; ', $failure_summaries)
            );
            $this->add_admin_notice($error_message, 'warning');
        }
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
