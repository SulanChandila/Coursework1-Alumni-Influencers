<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Analytics extends API_Controller
{
    public function __construct()
    {
        parent::__construct();

        //Loading database and analytics model
        $this->load->database();
        $this->load->model('Analytics_model');
    }


    public function dashboard_data()
    {
        //Checking analytics scope and user authentication
        $client = $this->require_scope('read:analytics');
        $this->require_authentication();

        //Logging API usage
        $this->_log_usage($client->id, '/analytics/dashboard_data');

        //Getting filter values from query string
        $filters = [
            'programme' => $this->input->get('programme', TRUE),
            'year'      => $this->input->get('year', TRUE),
            'industry'  => $this->input->get('industry', TRUE)
        ];

        //Getting all dashboard analytics data
        $skills_gap_data    = $this->Analytics_model->get_top_certifications($filters);
        $job_titles_data    = $this->Analytics_model->get_common_job_titles($filters);
        $top_employers_data = $this->Analytics_model->get_top_employers($filters);
        $degrees_data       = $this->Analytics_model->get_popular_degrees($filters);
        $cert_timeline      = $this->Analytics_model->get_certification_timeline($filters);
        $awarding_bodies    = $this->Analytics_model->get_top_awarding_bodies($filters);
        $skills_radar       = $this->Analytics_model->get_emerging_skills($filters);

        //Returning dashboard response
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data' => [
                    'skills_gap'      => $skills_gap_data,
                    'job_titles'      => $job_titles_data,
                    'top_employers'   => $top_employers_data,
                    'popular_degrees' => $degrees_data,
                    'cert_timeline'   => $cert_timeline,
                    'awarding_bodies' => $awarding_bodies,
                    'skills_radar'    => $skills_radar
                ]
            ]));
    }


    public function alumni_list()
    {
        //Checking analytics scope and user authentication
        $client = $this->require_scope('read:analytics');
        $this->require_authentication();

        //Logging API usage
        $this->_log_usage($client->id, '/analytics/alumni_list');

        //Getting filter values from query string
        $filters = [
            'programme' => $this->input->get('programme', TRUE),
            'year'      => $this->input->get('year', TRUE),
            'industry'  => $this->input->get('industry', TRUE)
        ];

        //Getting filtered alumni list
        $alumni = $this->Analytics_model->get_alumni_list($filters);

        //Returning alumni response
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data'   => $alumni
            ]));
    }


    private function _log_usage($client_id, $endpoint)
    {
        //Preparing log data
        $data = [
            'user_id'     => $client_id,
            'endpoint'    => $endpoint,
            'method'      => $this->input->server('REQUEST_METHOD'),
            'ip_address'  => $this->input->ip_address(),
            'accessed_at' => date('Y-m-d H:i:s')
        ];

        //Inserting usage log
        $this->db->insert('api_usage_logs', $data);
    }
}