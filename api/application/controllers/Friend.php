<?php

/**
 * 친구관련
 */
require_once APPPATH . '/libraries/RestController.php';

class Friend extends RestController
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('service/service_friend');
	}

	/*
     * token 인증 제외 처리
     */
	protected function auth_method_check() {
		$result = array('');
		return $result;
	}

	public function list_get()
	{
		$mode = $this->get('mode');
		$search_text = $this->get('search_text');

		$params = array(
			'mode' => $mode,
			'page' => 1,
			'user_no' => $this->auth_user->user_no,
			'per_page' => 10,
			'search' => $search_text,
		);

		$data = $this->service_friend->lists($params);
		$this->response(array('data' => $data), self::HTTP_OK);

	}
	//todo 사용자번호 암호화
	public function info_get($friend_no)
	{
		$data = $this->service_friend->friend_detail($friend_no, $this->auth_user->user_no);
		$this->response(array('data' => $data), self::HTTP_OK);

	}

	public function add_post()
	{
		$para_validation = array(
			array('field' => 'email', 'rules' => 'required', 'label' => '메일주소 또는 닉네임'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_friend->add_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_friend->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function modify_post()
	{
		$para_validation = array(
			array('field' => 'friend_no', 'rules' => 'required', 'label' => '친구번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_friend->modify_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_friend->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function friend_delete($friend_no)
	{
		$this->db->trans_begin();
		$data = $this->service_friend->delete_proc($this->auth_user->user_no,$friend_no);
		if ($data === false) {
			$_error = $this->service_friend->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function comfirm_put($friend_no)
	{
		$this->db->trans_begin();
		$data = $this->service_friend->comfirm_proc($this->auth_user->user_no,$friend_no);
		if ($data === false) {
			$_error = $this->service_friend->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

}