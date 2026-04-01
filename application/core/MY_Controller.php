<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API_Controller extends CI_Controller {

    //Holding the id of current user
    public $current_user_id = null;

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->output->set_content_type('application/json');

        //Security Headers & CORS for Helmet.js Equivalent
        $this->output->set_header('X-Frame-Options: DENY'); //Prevents clickjacking
        $this->output->set_header('X-XSS-Protection: 1; mode=block'); //XSS filter
        $this->output->set_header('X-Content-Type-Options: nosniff'); //Prevents MIME sniffing
        $this->output->set_header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); //Forces HTTPS
        $this->output->set_header('Access-Control-Allow-Origin: *'); //CORS config
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        $this->output->set_header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    //authentication protection
    protected function require_authentication() {
        $headers = $this->input->request_headers();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');

        if (empty($auth_header) || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $this->output->set_status_header(401)
                 ->set_output(json_encode(['error' => 'Missing or invalid Authorization header.']))
                 ->_display();
            exit();
        }

        $token = $matches[1];
        $user = $this->User_model->get_user_by_token($token);

        if (!$user) {
            $this->output->set_status_header(401)
                 ->set_output(json_encode(['error' => 'Invalid, expired, or revoked token.']))
                 ->_display();
            exit();
        }

        //Validating correct token
        $this->current_user_id = $user['id'];
        
        //Rate limiting
        $this->enforce_rate_limit($this->input->ip_address());

        //Log API Usage
        $this->log_api_usage($this->current_user_id);
    }

    // Helper to enforce rate limiting (60 requests per minute)
    protected function enforce_rate_limit($ip_address) {
        $one_minute_ago = date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        $this->db->where('ip_address', $ip_address);
        $this->db->where('accessed_at >=', $one_minute_ago); 
        $recent_requests = $this->db->count_all_results('api_usage_logs');

        if ($recent_requests > 60) {
            $this->output->set_status_header(429) // HTTP 429: Too Many Requests
                 ->set_output(json_encode(['error' => 'Rate limit exceeded. Please try again in a minute.']))
                 ->_display();
            exit();
        }
    }

    //Helper function to log usage
    private function log_api_usage($user_id) {
        $data = array(
            'user_id' => $user_id,
            'endpoint' => $this->uri->uri_string(),
            'method' => $this->input->method(TRUE),
            'ip_address' => $this->input->ip_address()
        );
        $this->db->insert('api_usage_logs', $data);
    }

    protected function get_json_input() {
        $stream = $this->input->raw_input_stream;
        $data = json_decode($stream, true);
        return $this->security->xss_clean($data);
    }
}