<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-01 오전 10:35
 */
class MY_Controller extends CI_Controller
{

    public $user_info = null;
	protected $today;

    function __construct() {
        parent::__construct();

        $this->load->library('admin/auth');

		if(strrpos(strtolower($_SERVER['REQUEST_URI']), '/login') === false) {
			$this->auth->check_grant();
		}

		$this->user_info = $this->auth->info();

        // view 셋팅
        $this->load->library('webview');
        $this->data = $this->webview->basic_setting();
		$this->data['today'] = date('Y-m-d');
    }

    function get_key($data){
        $key = $this->mcrypt->encrypt($data);
        $this->output->set_output(json_encode(array('key' => $key)));
    }

    function json_output($isSuccess, $message = '', $result = array()){
		$ret = array(
			'success' => $isSuccess,
			'message' => $message,
			'result' => $result,
		);

		return $ret;
	}

	function get_codes($searchs = array()){
		$this->load->model('admin_code_model');

		if(isset($searchs['code']) && isset($searchs['parent_code'])){
			$ret = $this->admin_code_model->sub_codes($searchs['parent_code'], $searchs['code']);
		}

		return $ret ;
	}
}
