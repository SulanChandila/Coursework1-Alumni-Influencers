<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_client_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Fetch an active API client by their API Key
     */
    public function get_client_by_key($api_key) {
        $query = $this->db->get_where('api_clients', [
            'api_key' => $api_key,
            'is_active' => 1
        ]);
        
        return $query->row(); // Returns the database row as an object, or NULL
    }
}