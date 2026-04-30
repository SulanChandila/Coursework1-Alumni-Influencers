<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ar_app extends API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        
        // Ensure responses are always JSON
        $this->output->set_content_type('application/json');
    }

    public function get_alumni_of_day() {
        
        // ==========================================
        // 1. SECURITY LAYER: API Key Validation
        // ==========================================
        $api_key = $this->input->get_request_header('x-api-key', TRUE);

        if (!$api_key) {
            return $this->output
                ->set_status_header(401)
                ->set_output(json_encode(['status' => 'error', 'message' => 'API Key missing']));
        }

        $client = $this->db->get_where('api_clients', ['api_key' => $api_key, 'is_active' => 1])->row();

        // CHECK SCOPES: Does this key have the 'read:alumni_of_day' scope?
        if (!$client || strpos($client->scopes, 'read:alumni_of_day') === false) {
            
            // Log the failed access attempt
            $this->_log_usage($client ? $client->id : null, '/ar_app/get_alumni_of_day');

            return $this->output
                ->set_status_header(403)
                ->set_output(json_encode([
                    'status' => 'error', 
                    'message' => 'Access Denied: Your API key does not have the read:alumni_of_day scope.'
                ]));
        }

        // Log the successful API key validation
        $this->_log_usage($client->id, '/ar_app/get_alumni_of_day');

        // ==========================================
        // 2. LOGIC LAYER: Get Today's Bid Winner
        // ==========================================
        $today = date('Y-m-d'); // Gets the current date dynamically

        // Join bids and profiles tables to find today's winner
        $this->db->select('p.first_name, p.last_name, p.bio, p.profile_image_url, p.linkedin_url, b.bid_amount');
        $this->db->from('bids b');
        $this->db->join('profiles p', 'p.user_id = b.user_id');
        $this->db->where('b.target_date', $today);
        $this->db->where('b.status', 'won');
        $this->db->limit(1); // Ensure we only get one user for "Alumni of the day"

        $query = $this->db->get();
        $alumni = $query->row();

        // If no one won a bid for today, return a fallback message
        if (!$alumni) {
            return $this->output
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'No alumni of the day found for today.',
                    'date' => $today
                ]));
        }

        // Return the dynamic data for the AR App
        return $this->output
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => 'success',
                'data' => [
                    'alumni_name'       => $alumni->first_name . ' ' . $alumni->last_name,
                    'bio'               => $alumni->bio,
                    'profile_image_url' => $alumni->profile_image_url,
                    'linkedin_url'      => $alumni->linkedin_url,
                    'winning_bid'       => $alumni->bid_amount,
                    'date_featured'     => $today
                ]
            ]));
    }

    /**
     * Helper to write to the api_usage_logs table based on DB schema
     */
    private function _log_usage($client_id, $endpoint) {
        $data = [
            'user_id'     => $client_id, 
            'endpoint'    => $endpoint,
            'method'      => $this->input->server('REQUEST_METHOD'),
            'ip_address'  => $this->input->ip_address(),
            'accessed_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('api_usage_logs', $data);
    }
}