<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
    }


    public function index()
    {
        $this->load->view('view', $this->data);
    }
}