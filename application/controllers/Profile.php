<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Profile.php
class Profile extends API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Profile_model');
        $this->load->library(['session', 'upload']);
        $this->load->helper(['url', 'form', 'security']);

        //Validating Token authentication
        $this->require_authentication();

        //Handling timeout for a session
        if ($this->session->userdata('user_id')) {
            $last_activity = $this->session->userdata('last_activity');
            $timeout_duration = 1800; //set for 30mins

            //Checking id user is idle
            if (time() - $last_activity > $timeout_duration) {
                $this->session->sess_destroy(); //If so destroying session
                
                //Session expired message for 401
                $this->output->set_status_header(401)
                     ->set_output(json_encode(['error' => 'Your session has expired due to inactivity. Please log in again.']))
                     ->_display();
                exit;
            }

            $this->session->set_userdata('last_activity', time());
        }
    }

    //Retrieve profile details
    public function index()
    {
        $profile = $this->Profile_model->get_profile($this->current_user_id);

        if ($profile) {
            $profile['degrees'] = $this->Profile_model->get_degrees($this->current_user_id);
            $profile['qualifications'] = $this->Profile_model->get_qualifications($this->current_user_id);
            $profile['employment_history'] = $this->Profile_model->get_employment($this->current_user_id);

            return $this->output->set_status_header(200)->set_output(json_encode(['data' => $profile]));
        } else {
            return $this->output->set_status_header(404)->set_output(json_encode(['message' => 'Profile not found. Please create one.']));
        }
    }

    //Clean sanitization of input from XSS
    private function get_clean_input()
    {
        $input = $this->get_json_input();
        if (empty($input)) {
            // TRUE flag applies CI's native XSS filtering to all POST data
            $input = $this->input->post(NULL, TRUE);
        } else {
            // Apply XSS filtering to JSON payloads
            $input = $this->security->xss_clean($input);
        }
        return $input;
    }

    //helper function for response handling
    private function handle_response($status_code, $message, $is_error = false, $extra_data = [])
    {
        $response = $is_error ? ['error' => $message] : ['message' => $message];
        if (!empty($extra_data)) {
            $response = array_merge($response, $extra_data);
        }
        return $this->output->set_status_header($status_code)->set_output(json_encode($response));
    }

    //Profile update (name,linked in url,bio)
    public function update()
    {
        $input = $this->get_clean_input();
        //Can't keep empty names
        if (empty($input['first_name']) || empty($input['last_name'])) {
            return $this->handle_response(400, 'First name and last name are required.', true);
        }
        //Checking for valid LinkedIn url domain
        if (!empty($input['linkedin_url'])) {
            if (!filter_var($input['linkedin_url'], FILTER_VALIDATE_URL) || strpos($input['linkedin_url'], 'linkedin.com') === false) {
                return $this->handle_response(400, 'URL must be a valid LinkedIn link.', true);
            }
        }

        $update_data = array(
            'first_name' => trim($input['first_name']),
            'last_name' => trim($input['last_name']),
            'bio' => isset($input['bio']) ? trim($input['bio']) : NULL,
            'linkedin_url' => isset($input['linkedin_url']) ? trim($input['linkedin_url']) : NULL
        );

        if ($this->Profile_model->upsert_profile($this->current_user_id, $update_data)) {
            return $this->handle_response(200, 'Profile updated successfully.', false, $update_data);
        }

        return $this->handle_response(500, 'Failed to update profile.', true);
    }

    //Upload image function
    public function upload_image()
    {
        $upload_path = FCPATH . 'uploads/profiles/';

        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, TRUE);
        }

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'gif|jpg|jpeg|png'; //Accepting types
        $config['max_size'] = 2048;
        $config['encrypt_name'] = TRUE; 

        $this->load->library('upload');
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('profile_image')) {
            $error = strip_tags($this->upload->display_errors());
            return $this->handle_response(400, $error, true);
        } else {
            $upload_data = $this->upload->data();
            $image_url = base_url('uploads/profiles/' . $upload_data['file_name']);

            $this->Profile_model->update_profile_image($this->current_user_id, $image_url);

            return $this->handle_response(200, 'Image uploaded successfully.', false, ['image_url' => $image_url]);
        }
    }

    // Adding degree function
    public function add_degree()
    {
        $input = $this->get_clean_input();
        //Empty field validation
        if (empty($input['degree_name']) || empty($input['institution']) || empty($input['completion_date'])) {
            return $this->handle_response(400, 'Degree name, institution, and completion date are required.', true);
        }
        //Validating id user enters a future date for complettion
        if (strtotime($input['completion_date']) > time()) {
            return $this->handle_response(400, 'Completion date cannot be in the future.', true);
        }
        //Validating URL domain
        if (!empty($input['official_url']) && !filter_var($input['official_url'], FILTER_VALIDATE_URL)) {
            return $this->handle_response(400, 'Official URL must be a valid link.', true);
        }

        $data = array(
            'user_id' => $this->current_user_id,
            'degree_name' => trim($input['degree_name']),
            'institution' => trim($input['institution']),
            'official_url' => isset($input['official_url']) ? trim($input['official_url']) : NULL,
            'completion_date' => $input['completion_date']
        );

        $new_id = $this->Profile_model->add_degree($data);
        return $this->handle_response(201, 'Degree added successfully.', false, ['degree_id' => $new_id]);
    }

    //Delete degree function
    public function delete_degree($id)
    {
        if ($this->Profile_model->delete_degree($id, $this->current_user_id)) {
            return $this->handle_response(200, 'Degree deleted successfully.', false);
        }
        return $this->handle_response(404, 'Degree not found or unauthorized.', true);
    }

    //Add Qualification function
    public function add_qualification()
    {
        $input = $this->get_clean_input();

        if (empty($input['type']) || empty($input['title']) || empty($input['awarding_body']) || empty($input['completion_date'])) {
            return $this->handle_response(400, 'Type, title, awarding body, and date are required.', true);
        }

        if (strtotime($input['completion_date']) > time()) {
            return $this->handle_response(400, 'Completion date cannot be in the future.', true);
        }

        if (!empty($input['url']) && !filter_var($input['url'], FILTER_VALIDATE_URL)) {
            return $this->handle_response(400, 'Certificate URL must be a valid link.', true);
        }

        $allowed_types = ['certification', 'licence', 'short_course'];
        if (!in_array($input['type'], $allowed_types)) {
            return $this->handle_response(400, 'Type must be certification, licence, or short_course.', true);
        }

        $data = array(
            'user_id' => $this->current_user_id,
            'type' => $input['type'],
            'title' => trim($input['title']),
            'awarding_body' => trim($input['awarding_body']),
            'url' => isset($input['url']) ? trim($input['url']) : NULL,
            'completion_date' => $input['completion_date']
        );

        $new_id = $this->Profile_model->add_qualification($data);
        return $this->handle_response(201, 'Qualification added.', false, ['id' => $new_id]);
    }

    //Delete qualitfications
    public function delete_qualification($id)
    {
        if ($this->Profile_model->delete_qualification($id, $this->current_user_id)) {
            return $this->handle_response(200, 'Qualification deleted.', false);
        }
        return $this->handle_response(404, 'Not found or unauthorized.', true);
    }

    //Add work experience
    public function add_employment()
    {
        $input = $this->get_clean_input();

        if (empty($input['company']) || empty($input['role']) || empty($input['start_date'])) {
            return $this->handle_response(400, 'Company, role, and start date are required.', true);
        }
        //Validating start date
        if (strtotime($input['start_date']) > time()) {
            return $this->handle_response(400, 'Start date cannot be in the future.', true);
        }

        $data = array(
            'user_id' => $this->current_user_id,
            'company' => trim($input['company']),
            'role' => trim($input['role']),
            'start_date' => $input['start_date'],
            'end_date' => isset($input['end_date']) && !empty($input['end_date']) ? $input['end_date'] : NULL
        );

        $new_id = $this->Profile_model->add_employment($data);
        return $this->handle_response(201, 'Employment record added.', false, ['id' => $new_id]);
    }

    //Delete work experience
    public function delete_employment($id)
    {
        if ($this->Profile_model->delete_employment($id, $this->current_user_id)) {
            return $this->handle_response(200, 'Employment record deleted.', false);
        }
        return $this->handle_response(404, 'Not found or unauthorized.', true);
    }

    //Change password for logged in user - no need token
    public function change_password()
    {
        $input = $this->get_clean_input();

        if (empty($input['current_password']) || empty($input['new_password'])) {
            return $this->handle_response(400, 'Both current and new passwords are required.', true);
        }

        $this->load->model('User_model');
        $user = $this->db->get_where('users', ['id' => $this->current_user_id])->row_array();

        if (!$user || !password_verify($input['current_password'], $user['password_hash'])) {
            return $this->handle_response(400, 'Incorrect current password.', true);
        }

        $new_password = $input['new_password'];

        if (strlen($new_password) < 6) {
            return $this->handle_response(400, 'Password change failed: Password must be at least 6 characters long.', true);
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            return $this->handle_response(400, 'Password change failed: Password must contain at least one uppercase letter.', true);
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            return $this->handle_response(400, 'Password change failed: Password must contain at least one number.', true);
        }

        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $this->User_model->update_password($this->current_user_id, $new_hash);

        return $this->handle_response(200, 'Password updated successfully.', false);
    }
}