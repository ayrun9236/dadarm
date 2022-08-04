<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
require_once APPPATH . 'controllers/etc/Board.php';
class Etc extends Board
{

    function __construct()
    {
        parent::__construct();
    }

	/**
	 * 리스트
	 */
	public function index()
	{
		$this->data['type'] = 'BOARD_ETC_USER';
		$this->data['page_content']['menu2'] = 'etc';
		$this->data['page_content']['menu3'] = 'index';
		$this->load->view('view', $this->data);
	}

}
