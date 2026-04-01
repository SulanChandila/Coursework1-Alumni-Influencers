<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    //Get usage stats
    public function get_usage_stats($limit = 50) {
        $this->db->select('api_usage_logs.id, api_usage_logs.ip_address, api_usage_logs.endpoint, api_usage_logs.method, api_usage_logs.accessed_at, users.email');
        $this->db->from('api_usage_logs');
        $this->db->join('users', 'users.id = api_usage_logs.user_id', 'left'); 
        $this->db->order_by('api_usage_logs.accessed_at', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get();
        
        if (!$query) {
            return []; 
        }
        return $query->result_array();
    }

    //Count the number of times specific endpoints were accessed
    public function get_endpoint_breakdown() {
        $this->db->select('endpoint, COUNT(*) as total_hits');
        $this->db->group_by('endpoint');
        $this->db->order_by('total_hits', 'DESC');
        $query = $this->db->get('api_usage_logs');
        
        if (!$query) {
            return []; 
        }
        return $query->result_array();
    }

    //Get login stats
    public function get_login_stats() {
        //Counting active tokens
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        return $this->db->count_all_results('auth_tokens');
    }

    //Revoke a specific token
    public function revoke_specific_token($token) {
        $this->db->where('token', $token);
        $this->db->delete('auth_tokens');
        return $this->db->affected_rows() > 0;
    }
}