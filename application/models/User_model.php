<?php

defined('BASEPATH') OR exit('No direct script access allowed');


//User model file
class User_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    //Email double checking for existence validation
    public function email_exists($email)
    {
        $query = $this->db->get_where('users', array('email' => $email));
        return $query->num_rows() > 0;
    }

    //Adding a user with verification toekn generated
    public function create_user($email, $password_hash, $verification_token)
    {
        $data = array(
            'email' => $email,
            'password_hash' => $password_hash,
            'verification_token' => $verification_token,
            'verification_expires' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'is_verified' => 0, //verified status 
            'created_at' => date('Y-m-d H:i:s')
        );
        return $this->db->insert('users', $data);
    }
    
    //Retrieving user
    public function get_user_by_email($email)
    {
        return $this->db->get_where('users', array('email' => $email))->row_array();
    }

    //Managing the API token status
    public function save_auth_token($user_id, $token, $expires_at)
    {
        $data = array(
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expires_at,
            'is_revoked' => 0
        );
        return $this->db->insert('auth_tokens', $data);
    }

    //Logic for verifying email
    public function verify_email($token)
    {
        $this->db->where('verification_token', $token);
        $this->db->where('verification_expires >=', date('Y-m-d H:i:s')); //checking if token is expired
        $query = $this->db->get('users');
        if ($query->num_rows() == 1) {
            $user = $query->row();
            $this->db->where('id', $user->id);
            return $this->db->update('users', array( //Updating verification status
                'is_verified' => 1,
                'verification_token' => NULL,
                'verification_expires' => NULL
            ));
        }
        return FALSE;
    }
    
    //Identifying user by token
    public function get_user_by_token($token)
    {
        $this->db->select('users.*');
        $this->db->from('auth_tokens');
        $this->db->join('users', 'users.id = auth_tokens.user_id');
        $this->db->where('auth_tokens.token', $token);
        $this->db->where('auth_tokens.is_revoked', 0);
        $this->db->where('auth_tokens.expires_at >=', date('Y-m-d H:i:s'));
        return $this->db->get()->row_array();
    }

    //Force revoking a token of a user
    public function revoke_token($token)
    {
        $this->db->where('token', $token);
        return $this->db->update('auth_tokens', array('is_revoked' => 1));
    }

    //Token generation for password reset
    public function set_reset_token($email, $token, $expires_at)
    {
        $this->db->where('email', $email);
        return $this->db->update('users', array(
            'reset_token' => $token,
            'reset_expires' => $expires_at
        ));
    }
    
    //Verifying the password reset token
    public function verify_reset_token($token)
    {
        $this->db->where('reset_token', $token);
        $this->db->where('reset_expires >=', date('Y-m-d H:i:s'));
        return $this->db->get('users')->row_array();
    }

    //Updating password for change password and reset password
    public function update_password($user_id, $new_password_hash)
    {
        $this->db->where('id', $user_id);
        return $this->db->update('users', array(
            'password_hash' => $new_password_hash,
            'reset_token' => NULL,
            'reset_expires' => NULL
        ));
    }

}