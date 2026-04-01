<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Extending API controller to get Security headers
class Featured extends API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bid_model');
        $this->load->model('Profile_model');

        $this->output->set_content_type('application/json');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        //Security from SPAMs
        $this->enforce_rate_limit($this->input->ip_address());

    
    }

    public function index($date = NULL)
    {
        //Default date
        if ($date === NULL) {
            $date = date('Y-m-d');
        }

        //Finding winner for the date to be displayed in Features
        $featured_user_id = $this->Bid_model->get_featured_user_id($date);

        if (!$featured_user_id) {
            return $this->output->set_status_header(404)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => "No featured alumnus found for {$date}."
                ]));
        }

        //Retrieving profile info of the winner
        $profile = $this->Profile_model->get_profile($featured_user_id);

        if ($profile) {
            //Retrieving degree, qualitifcations and work history
            $profile['degrees'] = $this->Profile_model->get_degrees($featured_user_id);
            $profile['qualifications'] = $this->Profile_model->get_qualifications($featured_user_id);
            $profile['employment_history'] = $this->Profile_model->get_employment($featured_user_id);

            return $this->output->set_status_header(200)
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => "Featured Alumnus for {$date}",
                    'data' => $profile
                ]));
        }

        return $this->output->set_status_header(500)
            ->set_output(json_encode([
                'status' => 'error',
                'message' => 'Featured profile data could not be loaded.'
            ]));
    }
}