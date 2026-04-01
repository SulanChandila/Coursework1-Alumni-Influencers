<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Auth.php
class Auth extends API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library(['form_validation', 'session']);
        $this->output->set_content_type('application/json');
    }

    //Register function
    public function register()
    {
        $input = $this->get_json_input();
        if (!$input) {
            $input = [];
        }

        //Empty Field validation
        if (empty($input['email']) || empty($input['password'])) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Email and password are required.']));
        }

        //Validating email domain to be @eastminster.ac.uk
        if (!preg_match('/@eastminster\.ac\.uk$/i', $input['email'])) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Registration failed: You must use a valid @eastminster.ac.uk university email address.']));
        }

        //Validating password strength
        if (strlen($input['password']) < 6) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Registration failed: Password must be at least 6 characters long.']));
        }
        if (!preg_match('/[A-Z]/', $input['password'])) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Registration failed: Password must contain at least one uppercase letter.']));
        }
        if (!preg_match('/[0-9]/', $input['password'])) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Registration failed: Password must contain at least one number.']));
        }

        //Validating if email is existing already
        $email = strtolower(trim($input['email']));

        if ($this->User_model->email_exists($email)) {
            return $this->output->set_status_header(409)->set_output(json_encode(['error' => 'Email already exists.']));
        }

        //Creating user
        $password_hash = password_hash($input['password'], PASSWORD_BCRYPT);
        $verification_token = bin2hex(random_bytes(32));

        //Expiry time for verification
        $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        if ($this->User_model->create_user($email, $password_hash, $verification_token, $verification_expires)) {
            return $this->output->set_status_header(201)
                ->set_output(json_encode([
                    'message' => 'Registration successful. Verify your email.',
                    'debug_token' => $verification_token
                ]));
        } else {
            return $this->output->set_status_header(500)->set_output(json_encode(['error' => 'Failed to create user.']));
        }
    }

    public function domain_check($email)
    {
        if (!strpos($email, '@eastminster.ac.uk')) {
            $this->form_validation->set_message('domain_check', 'Only @eastminster.ac.uk emails are allowed.');
            return FALSE;
        }
        return TRUE;
    }

    public function password_strength($password)
    {
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->form_validation->set_message('password_strength', 'Password must contain an uppercase letter and a number.');
            return FALSE;
        }
        return TRUE;
    }

    //Login function
    public function login()
    {
        $input = $this->get_json_input();

        //Validating empty fields
        if (empty($input['email']) || empty($input['password'])) {
            return $this->output->set_status_header(400)->set_output(json_encode(['error' => 'Email and password are required.']));
        }

        $user = $this->User_model->get_user_by_email(strtolower(trim($input['email'])));

        if ($user && password_verify($input['password'], $user['password_hash'])) {

            if ($user['is_verified'] == 0) {
                return $this->output->set_status_header(403)->set_output(json_encode(['error' => 'Email not verified.']));
            }

            //Secure session management
            $this->session->set_userdata('user_id', $user['id']);
            $this->session->set_userdata('last_activity', time());

            $auth_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $this->User_model->save_auth_token($user['id'], $auth_token, $expires_at);

            return $this->output->set_output(json_encode([
                'status' => 'success',
                'token' => $auth_token,
                'session_active' => TRUE
            ]));
        }

        return $this->output->set_status_header(401)->set_output(json_encode(['error' => 'Invalid credentials.']));
    }

    //Verify function
    public function verify()
    {
        //Reading token
        $input = $this->get_json_input();

        if (empty($input['token'])) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Token is required.']));
        }

        $token = $input['token'];
        //Checking with model
        if ($this->User_model->verify_email($token)) {
            return $this->output->set_status_header(200)
                ->set_output(json_encode(['message' => 'Verified successfully.']));
        }

        return $this->output->set_status_header(400)
            ->set_output(json_encode(['error' => 'Invalid or expired token.']));
    }

    //Logout Function
    public function logout()
    {
        $this->session->sess_destroy();

        $headers = $this->input->request_headers();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);

        if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $this->User_model->revoke_token($matches[1]);
        }

        return $this->output->set_output(json_encode(['message' => 'Logged out successfully.']));
    }

    //Forgot password function
    public function forgot_password()
    {
        $input = $this->get_json_input();

        if (empty($input['email'])) {
            return $this->output->set_status_header(400)->set_output(json_encode(['error' => 'Email is required.']));
        }

        $email = strtolower(trim($input['email']));
        //Generating token for password reset
        if ($this->User_model->email_exists($email)) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->User_model->set_reset_token($email, $token, $expires);

            return $this->output->set_output(json_encode(['message' => 'Reset link sent.', 'debug_token' => $token]));
        }

        return $this->output->set_output(json_encode(['message' => 'Reset link sent.']));
    }

    //Reset password function
    public function reset_password()
    {
        $input = $this->get_json_input();
        if (!$input)
            $input = [];

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('token', 'Token', 'required');
        $this->form_validation->set_rules('password', 'New Password', 'required|min_length[8]|callback_password_strength');

        if ($this->form_validation->run() == FALSE) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['errors' => $this->form_validation->error_array()]));
        }

        $user = $this->User_model->verify_reset_token($input['token']);

        if ($user) {
            $new_hash = password_hash($input['password'], PASSWORD_BCRYPT);
            $this->User_model->update_password($user['id'], $new_hash);

            return $this->output->set_output(json_encode(['message' => 'Password has been reset successfully.']));
        }

        return $this->output->set_status_header(400)
            ->set_output(json_encode(['error' => 'Invalid or expired reset token.']));
    }
}