<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Spambot_Spam_Service {
    
    protected $settings;
    
    public function __construct() {
        $this->settings = get_option('wp_spambot_settings', array());
    }
    
    public function has_active_services() {
        return !empty($this->settings['stopforumspam_enabled']);
    }
    
    public function check_user($user) {
        $is_spam = false;
        $confidence = 0;
        $service = 'none';
        $details = array();
        $frequency = 0;
        
        if (!empty($this->settings['stopforumspam_enabled'])) {
            $result = $this->check_stopforumspam($user);
            $details = $result['details'];
            $frequency = $result['frequency'];
            $confidence = $result['confidence'];
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
            foreach ($details as $detail) {
                $frequency = max($frequency, $detail['frequency']);
                $confidence = max($confidence, $detail['confidence']);
                if ($detail['frequency'] > 0) {
                    if (0 === $threshold || $detail['frequency'] >= $threshold) {
                        $is_spam = true;
                    }
                }
            }
        }
        
        return array(
            'is_spam' => $is_spam,
            'confidence' => $confidence,
            'frequency' => $frequency,
            'details' => $details,
        );
    }
}
