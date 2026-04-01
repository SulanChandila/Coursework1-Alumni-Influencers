<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Web extends CI_Controller {

    public function login() {
        $this->load->helper('url');
        $this->load->view('login_view');
    }

    public function register() {
        $this->load->helper('url');
        // We will build this view next!
        $this->load->view('register_view');
    }

    public function verify() {
        $this->load->helper('url');
        $this->load->view('verify_view');
    }
}