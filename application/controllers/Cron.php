<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Bid_model');

        //Allowing CLI only access
        if (!is_cli()) {
            show_error('Direct web access is forbidden. This is a CLI-only script.', 403);
        }
    }

    //Command to run this in CLI - php index.php cron resolve_bids { with date or without}

    public function resolve_bids($specific_date = NULL) {
        //If no date set resolving for today
        $target_date = $specific_date ? $specific_date : date('Y-m-d');
        
        echo "Starting bid resolution for date: {$target_date}...\n";

        $winner = $this->Bid_model->resolve_bids($target_date);

        if ($winner) {
            echo "Success! Winning Bid ID: {$winner['id']} belongs to User ID: {$winner['user_id']} with amount: \${$winner['bid_amount']}\n";
            echo "All other bids for this date have been marked as lost.\n";
        } else {
            echo "No pending bids found for {$target_date}.\n";
        }
    }
}