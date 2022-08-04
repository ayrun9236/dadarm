<?php

/**
 * 회원관련
 */
require_once APPPATH . '/libraries/RestController.php';

class User extends RestController
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('cookie');
		$this->load->library('service/service_user');
	}

	/*
     * token 인증 제외 처리
     */
	protected function auth_method_check()
	{
		$result = array('signup_post', 'signin_post', 'recoverpassword_email_post', 'recoverpassword_post');
		return $result;
	}

	/**
	 * 등록하기
	 */
	public function signup_post()
	{
		$para_validation = array(
			array('field' => 'user_name', 'rules' => 'required', 'label' => '이름'),
//			array('field' => 'login_id', 'rules' => 'required', 'label' => '로그인id'),
//			array('field' => 'login_password', 'rules' => 'required', 'label' => '비밀번호'),
			array('field' => 'regist_type', 'rules' => 'required', 'label' => '가입타입'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$res['email'] = $res['login_id'];

		if(strtoupper($res['regist_type']) == 'EMAIL' && $res['login_id'] == ''){
			$this->response_error('로그인id를 입력해 주세요', self::HTTP_UNAUTHORIZED);
		}

		if(strtoupper($res['regist_type']) == 'EMAIL' && $res['login_password'] == ''){
			$this->response_error('비밀번호를 입력해 주세요', self::HTTP_UNAUTHORIZED);
		}


		$this->db->trans_begin();
		$regist_no = $this->service_user->create($res);
		if ($regist_no > 0) {
			$this->db->trans_commit();
			$this->response(array('data' => $regist_no), self::HTTP_OK);
		} else {
			$this->db->trans_rollback();
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, $_error->code);
		}
	}

	public function signin_post()
	{
		$para_validation = array(
			array('field' => 'regist_type', 'rules' => 'required', 'label' => '가입타입'),
//			array('field' => 'login_id', 'rules' => 'required', 'label' => '로그인id'),
			array('field' => 'login_password', 'rules' => 'required', 'label' => '비밀번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		// 이메일 가입자일 경우에 로그인 id 체크
		if(strtoupper($res['regist_type']) == 'EMAIL' && $res['login_id'] == ''){
			$this->response_error('로그인id를 입력해 주세요', self::HTTP_UNAUTHORIZED);
		}
		
		$login_data = $this->service_user->signin($res);

		if ($login_data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $login_data), self::HTTP_OK);
		}
	}

	public function family_get()
	{
		$data = $this->service_user->family_list($this->auth_user->user_no);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function child_post()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '아이이름'),
			array('field' => 'birthday', 'rules' => 'required', 'label' => '생년월일'),
			array('field' => 'gender', 'rules' => 'required', 'label' => '성별'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->child_proc($this->auth_user->user_no, $res);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function child_delete($child_no)
	{
		$this->db->trans_begin();

		$data = $this->service_user->child_delete_proc($this->auth_user->user_no, $child_no);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => array()), self::HTTP_OK);
		}
	}

	public function save_post()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '이름'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->modify_proc($this->auth_user->user_no, $res);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $this->service_user->detail($this->auth_user->user_no, 'token')), self::HTTP_OK);
		}
	}

	public function password_post()
	{
		$para_validation = array(
			array('field' => 'now', 'rules' => 'required', 'label' => '현재 비밀번호'),
			array('field' => 'new', 'rules' => 'required', 'label' => '신규 비밀번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->password_proc($this->auth_user->user_no, $res);

		if ($data === false) {
			$_error = $this->service_user->get_error();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $this->service_user->detail($this->auth_user->user_no, 'token')), self::HTTP_OK);
		}
	}

	public function recoverpassword_email_post()
	{
		$para_validation = array(
			array('field' => 'email', 'rules' => 'required', 'label' => '이메일'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->recoverpassword_email_proc($res);

		if ($data === false) {
			$_error = $this->service_user->get_error();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function recoverpassword_post()
	{
		$para_validation = array(
			array('field' => 'check', 'rules' => 'required', 'label' => '이메일'),
			array('field' => 'password', 'rules' => 'required', 'label' => '비밀번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->recoverpassword_proc($res);

		if ($data === false) {
			$_error = $this->service_user->get_error();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function child_introduce_list_get()
	{
		$data = $this->service_user->child_introduce_list($this->auth_user->user_no);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function block_list_get()
	{
		$data = $this->service_user->block_list($this->auth_user->user_no);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function block_delete($block_user_no)
	{
		$data = $this->service_user->block_delete_proc($this->auth_user->user_no, $block_user_no);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => array()), self::HTTP_OK);
		}
	}

	public function child_introduce_post()
	{
		$para_validation = array(
			array('field' => 'child_no', 'rules' => 'required', 'label' => '아이'),
			array('field' => 'title', 'rules' => 'required', 'label' => '제목'),
			array('field' => 'contents', 'rules' => 'required', 'label' => '소개내용'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->child_introduce_proc($this->auth_user->user_no, $res);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $this->service_user->detail($this->auth_user->user_no)), self::HTTP_OK);
		}
	}

	public function privacy_get()
	{
		$data = $this->service_user->privacy($this->auth_user->user_no);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function privacy_post()
	{
		$para_validation = array(
			array('field' => 'profile_setting', 'rules' => 'required', 'label' => '프로필 설정'),
			array('field' => 'group_setting_private', 'rules' => 'required', 'label' => '그룹 설정'),
			array('field' => 'community_setting_private', 'rules' => 'required', 'label' => '커뮤니티 설정'),
		);

		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_user->privacy_proc($this->auth_user->user_no, $res);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $this->service_user->detail($this->auth_user->user_no)), self::HTTP_OK);
		}
	}

	public function push_get()
	{
		$data = $this->service_user->push($this->auth_user->user_no);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function push_post()
	{
		$res = $this->post();
		$data = $this->service_user->push_proc($this->auth_user->user_no, $res);

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $this->service_user->detail($this->auth_user->user_no)), self::HTTP_OK);
		}
	}

	public function detail_get()
	{
		$data = $this->service_user->detail($this->auth_user->user_no, 'info');

		if ($data === false) {
			$_error = $this->service_user->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}
}