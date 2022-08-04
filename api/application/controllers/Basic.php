<?php

/**
 * 회원관련
 */
require_once APPPATH . '/libraries/RestController.php';

class Basic extends RestController
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('service/service_common');
	}

	public function code_get($code)
	{
		$code = strtoupper($code);
		$searchs = array('parent_code' => $code);
		$possible_codein = array('BOARD_TYPE');
		if(!in_array($code, $possible_codein)){
			$this->response(array('data' => array()), self::HTTP_OK);
		}

		$searchs['is_view'] = 1;
		$data = $this->service_common->get_codes($searchs);

		if ($data === false) {
			$_error = $this->service_common->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function data_get()
	{
		$ret = array();
		$codes = array('BOARD_VIEW_TYPE','BOARD_DATA_SUB_TYPE','SELFTEST_TYPE','USER_PROFILE_BOARD_TYPE','BOARD_ETC_USER');
		foreach ($codes as $code) {
			$searchs = array('parent_code' => strtoupper($code));
			$searchs['is_view'] = 1;

			$data = $this->service_common->get_codes($searchs);

			if ($data === false) {
				$data = array();
			}

			$ret[$code] = $data;
		}

		$this->response(array('data' => $ret), self::HTTP_OK);

	}
}