<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Bid_model.php
class Bid_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    //Validating monthly win limit
    public function get_monthly_win_count($user_id, $target_date)
    {
        $month = date('m', strtotime($target_date));
        $year = date('Y', strtotime($target_date));

        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'won');
        // Extract month and year from the target_date column
        $this->db->where('MONTH(target_date)', $month);
        $this->db->where('YEAR(target_date)', $year);

        return $this->db->count_all_results('bids');
    }

    //Event attended check
    public function has_attended_event($user_id)
    {
        $this->db->select('attended_event');
        $this->db->where('id', $user_id);
        $query = $this->db->get('users');

        if ($query->num_rows() > 0) {
            $user = $query->row_array();
            return (bool) $user['attended_event'];
        }
        return false;
    }

    //Increase only for Bid price
    public function upsert_bid($user_id, $target_date, $bid_amount)
    {

        //Checking monthly limit
        $has_attended = $this->has_attended_event($user_id);
        $monthly_limit = $has_attended ? 4 : 3;

        if ($this->get_monthly_win_count($user_id, $target_date) >= $monthly_limit) {
            return [
                'success' => false,
                'message' => "Monthly limit reached: You can only be featured {$monthly_limit} times per month based on your event participation status."
            ];
        }

        //Checking an existing bid for the date
        $this->db->where('user_id', $user_id);
        $this->db->where('target_date', $target_date);
        $query = $this->db->get('bids');

        if ($query->num_rows() > 0) {
            $existing_bid = $query->row_array();

            //Increase only rule for bid
            if ($bid_amount <= $existing_bid['bid_amount']) {
                return ['success' => false, 'message' => 'Update failed: New bid must be strictly higher than your current bid of ' . $existing_bid['bid_amount']];
            }

            //Updating bid
            $this->db->where('id', $existing_bid['id']);
            $this->db->update('bids', array(
                'bid_amount' => $bid_amount,
                'status' => 'pending'
            ));
            return ['success' => true, 'message' => 'Bid increased successfully.'];

        } else {
            //For a new bid
            $data = array(
                'user_id' => $user_id,
                'target_date' => $target_date,
                'bid_amount' => $bid_amount,
                'status' => 'pending'
            );
            $this->db->insert('bids', $data);
            return ['success' => true, 'message' => 'New bid placed successfully.'];
        }
    }

    //Bid history with status
    public function get_user_bids($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('target_date', 'DESC'); 
        $query = $this->db->get('bids');
        return $query->result_array();
    }

    //Getting remaining slots for current month
    public function get_remaining_slots($user_id, $target_date = NULL)
    {
        if ($target_date === NULL) {
            $target_date = date('Y-m-d');
        }

        //Retrieving date month
        $month = date('m', strtotime($target_date));
        $year = date('Y', strtotime($target_date));

        $this->db->select('attended_event');
        $user = $this->db->get_where('users', ['id' => $user_id])->row();
        $max_slots = ($user && $user->attended_event == 1) ? 4 : 3;

        //Checking win count for that month
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'won');
        $this->db->where('MONTH(target_date)', $month);
        $this->db->where('YEAR(target_date)', $year);
        $won_count = $this->db->count_all_results('bids');

        //Return count
        return max(0, $max_slots - $won_count);
    }

    //Resolve auction for a date
    public function resolve_bids($target_date)
    {
        //Getting all bids for the date
        $this->db->where('target_date', $target_date);
        $this->db->where('status', 'pending');
        $this->db->order_by('bid_amount', 'DESC');
        $query = $this->db->get('bids');

        $all_pending_bids = $query->result_array();

        if (empty($all_pending_bids)) {
            return false; //If no bids for day
        }

        $winning_bid = null;

        //Looping through bids to find highest
        foreach ($all_pending_bids as $bid) {
            $remaining_slots = $this->get_remaining_slots($bid['user_id'], $target_date);

            if ($remaining_slots > 0) {
                $winning_bid = $bid; 
                break; //Gone through all bids
            }
        }

        //Processing the results
        if ($winning_bid) {

            //Marking winner status
            $this->db->where('id', $winning_bid['id']);
            $this->db->update('bids', array('status' => 'won'));

            //Mailing to winner
            $winner_user = $this->db->get_where('users', ['id' => $winning_bid['user_id']])->row_array();
            if ($winner_user) {
                $this->send_notification_email($winner_user['email'], 'You won the bid!', "Congratulations! You are the featured alumnus for {$target_date}.");
            }

            //marking lost status for others for that date
            $this->db->where('target_date', $target_date);
            $this->db->where('status', 'pending');
            $this->db->where('id !=', $winning_bid['id']);
            $this->db->update('bids', array('status' => 'lost'));
        
        //If no winner was found
        } else {

            //Marking lost status
            $this->db->where('target_date', $target_date);
            $this->db->where('status', 'pending');
            $this->db->update('bids', array('status' => 'lost'));
        }

        $this->db->select('users.email');
        $this->db->from('bids');
        $this->db->join('users', 'users.id = bids.user_id');
        $this->db->where('bids.target_date', $target_date);
        $this->db->where('bids.status', 'lost');
        $losers = $this->db->get()->result_array();

        foreach ($losers as $loser) {
            $this->send_notification_email($loser['email'], 'Bid Update', "Unfortunately, your bid for {$target_date} was not successful. You either did not have the highest bid, or you have already reached your featured limits for this month.");
        }

        return $winning_bid ? $winning_bid : false;
    }

    private function send_notification_email($to_email, $subject, $message)
    {
        $this->load->library('email');

        $config['protocol'] = 'mail';
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8';
        $this->email->initialize($config);

        $this->email->from('noreply@eastminster.ac.uk', 'Eastminster Alumni Portal');
        $this->email->to($to_email);
        $this->email->subject($subject);
        $this->email->message($message);

        $this->email->send();
    }

    //Getting winning users credentials for the Featured Public API
    public function get_featured_user_id($target_date)
    {
        $this->db->select('user_id');
        $this->db->where('target_date', $target_date);
        $this->db->where('status', 'won');
        $query = $this->db->get('bids');

        $row = $query->row_array();
        return $row ? $row['user_id'] : FALSE;
    }

}