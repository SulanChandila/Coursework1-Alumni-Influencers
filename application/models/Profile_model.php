<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//Profile_model.php
class Profile_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    //Retrieving profile
    public function get_profile($user_id) {
        $query = $this->db->get_where('profiles', array('user_id' => $user_id));
        return $query->row_array();
    }

    //Updating profiel
    public function upsert_profile($user_id, $data) {
        $existing = $this->get_profile($user_id);

        if ($existing) {
            // Update existing profile
            $this->db->where('user_id', $user_id);
            return $this->db->update('profiles', $data);
        } else {
            // Insert new profile
            $data['user_id'] = $user_id;
            return $this->db->insert('profiles', $data);
        }
    }

    //Uploading image
    public function update_profile_image($user_id, $image_url) {
        $this->db->where('user_id', $user_id);
        return $this->db->update('profiles', array('profile_image_url' => $image_url));
    }

    //Degree handling
    public function add_degree($data) {
        $this->db->insert('degrees', $data);
        return $this->db->insert_id(); // Returns the new ID
    }

    public function delete_degree($degree_id, $user_id) {
        $this->db->where('id', $degree_id);
        $this->db->where('user_id', $user_id); // Security: Ensure they own it!
        $this->db->delete('degrees');
        return $this->db->affected_rows() > 0;
    }

    public function get_degrees($user_id) {
        $query = $this->db->get_where('degrees', array('user_id' => $user_id));
        return $query->result_array();
    }

    //QUalifications handling
    public function add_qualification($data) {
        $this->db->insert('professional_qualifications', $data);
        return $this->db->insert_id();
    }

    public function delete_qualification($id, $user_id) {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('professional_qualifications');
        return $this->db->affected_rows() > 0;
    }

    public function get_qualifications($user_id) {
        $query = $this->db->get_where('professional_qualifications', array('user_id' => $user_id));
        return $query->result_array();
    }

    //Employment handling
    public function add_employment($data) {
        $this->db->insert('employment_history', $data);
        return $this->db->insert_id();
    }

    public function delete_employment($id, $user_id) {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('employment_history');
        return $this->db->affected_rows() > 0;
    }

    public function get_employment($user_id) {
        $this->db->order_by('start_date', 'DESC'); // Order by newest job first
        $query = $this->db->get_where('employment_history', array('user_id' => $user_id));
        return $query->result_array();
    }
}