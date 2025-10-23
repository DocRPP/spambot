<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Spambot_Spam_Service {
    
    protected $settings;
    
    public function __construct() {
        $this->refresh_settings();
    }
    
    protected function refresh_settings() {
        $this->settings = get_option('wp_spambot_settings', array());
    }
    
    public function has_active_services() {
        $this->refresh_settings();
        return !empty($this->settings['stopforumspam_enabled']);
    }
    
    public function check_user($user) {
        $this->refresh_settings();
        $is_spam = false;
        $confidence = 0;
        $service = 'none';
        $details = array();
        $frequency = 0;
        $factors = array(
            'email' => false,
            'username' => false,
        );
        
        if (!empty($this->settings['stopforumspam_enabled'])) {
            $result = $this->check_stopforumspam($user);
            $details = $result['details'];
            $frequency = $result['frequency'];
            $confidence = $result['confidence'];
            $factors = isset($result['factors']) ? $result['factors'] : $factors;
            $service = 'stopforumspam';
            if ($result['is_spam']) {
                $is_spam = true;
            }
        }
        
        return array(
            'is_spam' => $is_spam,
            'confidence' => $confidence,
            'service' => $service,
            'details' => $details,
            'frequency' => $frequency,
            'factors' => $factors,
        );
    }
    
    protected function check_stopforumspam($user) {
        $api_url = 'https://api.stopforumspam.org/api';
        
        $params = array(
            'json' => 1,
            'confidence' => 1,
        );
        
        if (!empty($user->user_email)) {
            $params['email'] = $user->user_email;
        }
        
        if (!empty($user->user_login)) {
            $params['username'] = $user->user_login;
        }
        
        if (!empty($this->settings['stopforumspam_api_key'])) {
            $params['api_key'] = $this->settings['stopforumspam_api_key'];
        }
        
        $response = wp_remote_get(add_query_arg($params, $api_url), array(
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'is_spam' => false,
                'confidence' => 0,
                'frequency' => 0,
                'details' => array(),
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['success']) || (int) $data['success'] !== 1) {
            return array(
                'is_spam' => false,
                'confidence' => 0,
                'frequency' => 0,
                'details' => array(),
            );
        }
        
        $is_spam = false;
        $confidence = 0;
        $frequency = 0;
        $threshold = isset($this->settings['stopforumspam_threshold']) ? max(0, intval($this->settings['stopforumspam_threshold'])) : 1;
        $details = array();
        $factors = array(
            'email' => false,
            'username' => false,
        );
        
        $email_data = isset($data['email']) ? $data['email'] : array();
        $username_data = isset($data['username']) ? $data['username'] : array();
        
        if (!empty($email_data) && !empty($email_data['appears'])) {
            $details['email'] = array(
                'frequency' => isset($email_data['frequency']) ? (int) $email_data['frequency'] : 0,
                'confidence' => isset($email_data['confidence']) ? (float) $email_data['confidence'] : 0,
                'lastseen' => isset($email_data['lastseen']) ? $email_data['lastseen'] : '',
            );
        }
        
        if (!empty($username_data) && !empty($username_data['appears'])) {
            $details['username'] = array(
                'frequency' => isset($username_data['frequency']) ? (int) $username_data['frequency'] : 0,
                'confidence' => isset($username_data['confidence']) ? (float) $username_data['confidence'] : 0,
                'lastseen' => isset($username_data['lastseen']) ? $username_data['lastseen'] : '',
            );
        }
        
        if (!empty($details)) {
            foreach ($details as $key => $detail) {
                $frequency = max($frequency, $detail['frequency']);
                $confidence = max($confidence, $detail['confidence']);
                if ($detail['frequency'] > 0) {
                    if (0 === $threshold || $detail['frequency'] >= $threshold) {
                        $is_spam = true;
                        $factors[$key] = true;
                    }
                }
            }
        }
        
        return array(
            'is_spam' => $is_spam,
            'confidence' => $confidence,
            'frequency' => $frequency,
            'details' => $details,
            'factors' => $factors,
        );
    }
    
    public function check_registration_email($email, $username = '') {
        if (empty($email)) {
            return array('allowed' => false, 'error' => __('Email address is required.', 'wp-spambot'));
        }
        
        $this->refresh_settings();
        
        if (!empty($this->settings['trusted_email_only_enabled'])) {
            if (!$this->is_trusted_email_provider($email)) {
                return array('allowed' => false, 'error' => __('Registration is limited to trusted email providers. Please use a different email address.', 'wp-spambot'));
            }
        }
        
        if (!empty($this->settings['stopforumspam_registration_enabled'])) {
            $result = $this->check_stopforumspam_registration($email, $username);
            if (!$result['allowed']) {
                return $result;
            }
        }
        
        return array('allowed' => true, 'error' => '');
    }
    
    protected function check_stopforumspam_registration($email, $username = '') {
        $api_url = 'https://api.stopforumspam.org/api';
        
        $params = array(
            'json' => 1,
            'confidence' => 1,
        );
        
        if (!empty($email)) {
            $params['email'] = $email;
        }
        
        if (!empty($username)) {
            $params['username'] = $username;
        }
        
        if (!empty($this->settings['stopforumspam_api_key'])) {
            $params['api_key'] = $this->settings['stopforumspam_api_key'];
        }
        
        $response = wp_remote_get(add_query_arg($params, $api_url), array(
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return array('allowed' => true, 'error' => '');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['success']) || (int) $data['success'] !== 1) {
            return array('allowed' => true, 'error' => '');
        }
        
        $confidence_threshold = isset($this->settings['stopforumspam_registration_confidence']) ? (float) $this->settings['stopforumspam_registration_confidence'] : 95;
        
        $email_data = isset($data['email']) ? $data['email'] : array();
        
        if (!empty($email_data) && !empty($email_data['appears'])) {
            $email_confidence = isset($email_data['confidence']) ? (float) $email_data['confidence'] : 0;
            if ($email_confidence >= $confidence_threshold) {
                return array('allowed' => false, 'error' => __('Your email address has been flagged. Please use another email address to register.', 'wp-spambot'));
            }
        }
        
        return array('allowed' => true, 'error' => '');
    }
    
    protected function is_trusted_email_provider($email) {
        $this->refresh_settings();
        $trusted_providers = array();
        if (!empty($this->settings['trusted_email_providers']) && is_array($this->settings['trusted_email_providers'])) {
            $trusted_providers = $this->settings['trusted_email_providers'];
        }
        
        if (empty($trusted_providers)) {
            return true;
        }
        
        $email_parts = explode('@', $email);
        if (count($email_parts) !== 2) {
            return false;
        }
        
        $domain = strtolower(trim($email_parts[1]));
        
        foreach ($trusted_providers as $provider) {
            if (strtolower($provider) === $domain) {
                return true;
            }
        }
        
        return false;
    }
}
