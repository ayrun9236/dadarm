<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
require_once APPPATH . 'controllers/etc/Board.php';
class Helpful extends Board
{
	function __construct()
	{
		parent::__construct();
		$this->board_type = 'helpful';
	}
}
