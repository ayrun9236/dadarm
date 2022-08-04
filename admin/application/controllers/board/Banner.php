<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
class Banner extends MY_Controller
{

	protected $board_type;

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_board_model');
		$this->load->library('service/service_board');
		$this->load->library('form_validation');
	}

	/**
	 * 리스트
	 */
	public function index()
	{
		$this->load->view('view', $this->data);
	}

	/**
	 * 상세정보
	 */
	public function detail($board_no, $mode = '')
	{
		$res = $this->service_board->detail($board_no, $mode);

		$ret = $this->json_output(true, '', $res);
		$this->output->set_output(json_encode($ret));
	}

}
