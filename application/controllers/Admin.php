<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Admin_model');
        //Protecting admin actions with authentication
        $this->require_authentication();
        
    }

    //GET /index.php/admin/stats
    //View API usage stats and active token counts
    public function stats() {
        $api_logs = $this->Admin_model->get_usage_stats(100);
        $active_logins = $this->Admin_model->get_login_stats();
        $endpoint_breakdown = $this->Admin_model->get_endpoint_breakdown(); // Add breakdown

        return $this->output->set_status_header(200)
                    ->set_output(json_encode([
                        'status' => 'success',
                        'data' => [
                            'active_sessions' => $active_logins,
                            'endpoint_popularity' => $endpoint_breakdown,
                            'recent_api_calls' => $api_logs
                        ]
                    ]));
    }

    //POST /index.php/admin/revoke
    //Force revoke a specific users token
    public function revoke() {
        $input = $this->get_json_input();

        if (empty($input['token_to_revoke'])) {
            return $this->output->set_status_header(400)
                        ->set_output(json_encode(['error' => 'Please provide the token_to_revoke.']));
        }

        if ($this->Admin_model->revoke_specific_token($input['token_to_revoke'])) {
            return $this->output->set_status_header(200)
                        ->set_output(json_encode(['message' => 'Token successfully revoked.']));
        }

        return $this->output->set_status_header(404)
                    ->set_output(json_encode(['error' => 'Token not found or already revoked.']));
    }
}