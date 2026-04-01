<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Bid.php
class Bid extends API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bid_model');
        // Secure all bidding routes
        $this->require_authentication();
    }

    //GET /api/bids
    //Fetching bid history
    public function index()
    {
        $user_id = $this->current_user_id;

        $bids = $this->Bid_model->get_user_bids($user_id);

        $today = date('Y-m-d');
        $remaining_slots = $this->Bid_model->get_remaining_slots($user_id, $today);

        return $this->output->set_status_header(200)
            ->set_output(json_encode([
                'remaining_slots' => $remaining_slots,
                'data' => $bids
            ]));
    }

    //POST /api/bids/place
    //Place a new blind bid or update an existing one for a specific day
    public function place()
    {
        $input = $this->get_json_input();

        if (empty($input['target_date']) || empty($input['bid_amount'])) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Target date and bid amount are required.']));
        }

        $target_date = $input['target_date'];
        $bid_amount = (float) $input['bid_amount'];

        if ($bid_amount <= 0) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'Bid amount must be greater than zero.']));
        }

        $today = date('Y-m-d');
        if ($target_date <= $today) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'You can only place bids for future dates.']));
        }

        //Validating maxed out wins for month
        $remaining_slots = $this->Bid_model->get_remaining_slots($this->current_user_id, $target_date);

        if ($remaining_slots <= 0) {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => 'You have already won your maximum featured slots for this month.']));
        }

        //Allowing bids if slots are left
        $result = $this->Bid_model->upsert_bid($this->current_user_id, $target_date, $bid_amount);

        if ($result['success']) {
            return $this->output->set_status_header(200)
                ->set_output(json_encode([
                    'message' => $result['message'],
                    'target_date' => $target_date,
                    'bid_amount' => $bid_amount,
                    'remaining_slots' => $remaining_slots
                ]));
        } else {
            return $this->output->set_status_header(400)
                ->set_output(json_encode(['error' => $result['message']]));
        }
    }
}