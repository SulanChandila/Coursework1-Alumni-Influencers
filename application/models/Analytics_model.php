<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * PRIVATE HELPER: Dynamically applies filter WHERE clauses and JOINs.
     * This prevents repeating the same filter logic in every function below.
     */
    private function _apply_filters($filters, $main_table_alias)
    {
        if (empty($filters))
            return;

        $has_degree_filter = !empty($filters['programme']) || !empty($filters['year']);
        $has_job_filter = !empty($filters['industry']);

        // 1. Join Degrees table if needed
        if ($has_degree_filter && $main_table_alias !== 'd') {
            // INNER JOIN ensures we only get data for users who actually have the filtered degree
            $this->db->join('degrees d', "d.user_id = {$main_table_alias}.user_id", 'inner');
        }

        // 2. Join Employment History table if needed
        if ($has_job_filter && $main_table_alias !== 'eh') {
            $this->db->join('employment_history eh', "eh.user_id = {$main_table_alias}.user_id", 'inner');
        }

        // 3. Apply the specific WHERE clauses
        if (!empty($filters['programme']) && $filters['programme'] !== 'all') {
            $this->db->where('d.degree_name', $filters['programme']);
        }

        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            $this->db->where('YEAR(d.completion_date)', $filters['year']);
        }

        if (!empty($filters['industry']) && $filters['industry'] !== 'all') {
            // Using LIKE allows "Software Engineer" to match "Senior Software Engineer"
            $this->db->like('eh.role', $filters['industry']);
        }
    }

    // 1. Skills Gap (Top Certifications)
    public function get_top_certifications($filters = [], $limit = 10)
    {
        // Assuming your table has an 'id' primary key. If not, swap pq.id with pq.user_id
        $this->db->select('pq.title as credential_name, COUNT(DISTINCT pq.user_id) as total_earned');
        $this->db->from('professional_qualifications pq');

        $this->_apply_filters($filters, 'pq');

        $this->db->group_by('pq.title');
        $this->db->order_by('total_earned', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    // 2. Most Common Job Titles
    public function get_common_job_titles($filters = [], $limit = 10)
    {
        $this->db->select('eh.role as job_title, COUNT(DISTINCT eh.user_id) as alumni_count');
        $this->db->from('employment_history eh');

        $this->_apply_filters($filters, 'eh');

        $this->db->where('eh.role !=', '');
        $this->db->group_by('eh.role');
        $this->db->order_by('alumni_count', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    // 3. Top Employers
    public function get_top_employers($filters = [], $limit = 5)
    {
        $this->db->select('eh.company, COUNT(DISTINCT eh.user_id) as employee_count');
        $this->db->from('employment_history eh');

        $this->_apply_filters($filters, 'eh');

        $this->db->where('eh.company !=', '');
        $this->db->group_by('eh.company');
        $this->db->order_by('employee_count', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    // 4. Most Popular Degree Programs
    public function get_popular_degrees($filters = [])
    {
        $this->db->select('d.degree_name, COUNT(DISTINCT d.user_id) as alumni_count');
        $this->db->from('degrees d');

        $this->_apply_filters($filters, 'd');

        $this->db->where('d.degree_name !=', '');
        $this->db->group_by('d.degree_name');
        $this->db->order_by('alumni_count', 'DESC');
        return $this->db->get()->result_array();
    }

    // 5. Certification Growth Timeline (Line Chart)
    public function get_certification_timeline($filters = [], $limit = 12)
    {
        $this->db->select("DATE_FORMAT(pq.completion_date, '%Y-%m') as month_year, COUNT(DISTINCT pq.user_id) as cert_count", FALSE);
        $this->db->from('professional_qualifications pq');

        $this->_apply_filters($filters, 'pq');

        $this->db->where('pq.completion_date IS NOT NULL', NULL, FALSE);
        $this->db->group_by('month_year');
        $this->db->order_by('month_year', 'ASC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    // 6. Top Awarding Bodies (Doughnut Chart)
    public function get_top_awarding_bodies($filters = [], $limit = 5)
    {
        $this->db->select('pq.awarding_body, COUNT(DISTINCT pq.user_id) as count');
        $this->db->from('professional_qualifications pq');

        $this->_apply_filters($filters, 'pq');

        $this->db->where('pq.awarding_body !=', '');
        $this->db->where('pq.awarding_body IS NOT NULL', NULL, FALSE);
        $this->db->group_by('pq.awarding_body');
        $this->db->order_by('count', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    // 7. Emerging Skills Radar (Radar Chart)
    public function get_emerging_skills($filters = [], $limit = 6)
    {
        $this->db->select('pq.title as skill_name, COUNT(DISTINCT pq.user_id) as skill_count');
        $this->db->from('professional_qualifications pq');

        $this->_apply_filters($filters, 'pq');

        $this->db->group_by('pq.title');
        $this->db->order_by('skill_count', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }
    
    // Get ALumni List
    public function get_alumni_list($filters = [])
    {
        $this->db->select("
        u.email,
        CONCAT(p.first_name, ' ', p.last_name) as full_name,
        p.linkedin_url,
        d.degree_name as programme,
        YEAR(d.completion_date) as graduation_year,
        eh.role as industry_role
    ", FALSE);

        $this->db->from('users u');
        $this->db->join('profiles p', 'p.user_id = u.id', 'left');
        $this->db->join('degrees d', 'd.user_id = u.id', 'left');
        $this->db->join('employment_history eh', 'eh.user_id = u.id', 'left');

        if (!empty($filters['programme']) && $filters['programme'] !== 'all') {
            $this->db->where('d.degree_name', $filters['programme']);
        }

        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            $this->db->where('YEAR(d.completion_date)', $filters['year']);
        }

        if (!empty($filters['industry']) && $filters['industry'] !== 'all') {
            $this->db->like('eh.role', $filters['industry']);
        }

        $this->db->group_by([
            'u.id',
            'u.email',
            'p.first_name',
            'p.last_name',
            'p.linkedin_url',
            'd.degree_name',
            'd.completion_date',
            'eh.role'
        ]);

        $this->db->order_by('p.first_name', 'ASC');
        return $this->db->get()->result_array();
    }
}