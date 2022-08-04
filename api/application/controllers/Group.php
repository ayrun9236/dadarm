<?php

/**
 * 그룹관련
 */
require_once APPPATH . '/libraries/RestController.php';

class Group extends RestController
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('service/service_group');
	}

	/*
     * token 인증 제외 처리
     */
	protected function auth_method_check() {
		$result = array('list_get');
		return $result;
	}

	public function list_get()
	{
		$search_text = $this->get('search_text');
		$type = $this->get('type');

		$params = array(
			'page' => 1,
			'per_page' => 10,
			'search' => $search_text,
		);

		if(isset($this->auth_user->user_no)){
			$params['user_search_mode'] = 'left outer';
			$params['user_no'] = $this->auth_user->user_no;
		}

		if($type == 'profile'){
			$params['user_search_mode'] = 'inner';
			$params['user_no'] = $this->auth_user->user_no;
		}

		$data = $this->service_group->lists($params);
		$this->response(array('data' => $data), self::HTTP_OK);

	}

	public function comments_get(){
		$post_no = $this->get('post_no');

		$params = array(
			'post_no' => $post_no,
			'target_table' => 'g',
		);

		$data = $this->service_group->comment_lists($params, $this->auth_user->user_no);
		$this->response(array('data' => $data), self::HTTP_OK);
	}

	public function post_list_get()
	{
		$search_text = $this->get('search_text');
		$group_no = $this->get('ltype_no');
//		$pag_size = $this->general->default($this->get('page_size'),10);
		$page = $this->general->default($this->get('page'),1);

		$params = array(
			'page' => $page,
			'per_page' => 10,
			'user_group_no' => $group_no,
			'search' => $search_text,
		);

		$data = $this->service_group->post_lists($params, $this->auth_user->user_no);

		$this->response(array('data' => $data), self::HTTP_OK);

	}

	public function add_post()
	{
		$para_validation = array(
			array('field' => 'user_group_no', 'rules' => 'required', 'label' => '그룹번호'),
			array('field' => 'group_name', 'rules' => 'required', 'label' => '그룹명'),
			array('field' => 'is_private', 'rules' => 'required', 'label' => '공개여부'),
			array('field' => 'group_desc', 'rules' => 'required', 'label' => '그룹설명'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_group->add_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function join_post()
	{
		$para_validation = array(
			array('field' => 'user_group_no', 'rules' => 'required', 'label' => '그룹번호')
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_group->join_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function invate_post()
	{
		$para_validation = array(
			array('field' => 'email', 'rules' => 'required', 'label' => '메일주소'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_group->invate_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function confirm_post()
	{
		$para_validation = array(
			array('field' => 'user_group_no', 'rules' => 'required', 'label' => '그룹번호'),
			array('field' => 'user_member_no', 'rules' => 'required', 'label' => '승인할 멤버'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_group->confirm_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function invate_put($group_no)
	{
		$data = $this->service_group->invate_confirm_proc($this->auth_user->user_no,$group_no);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function post_delete($group_post_no)
	{
		$data = $this->service_group->post_delete_proc($this->auth_user->user_no,$group_post_no);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function add_like_post()
	{
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_group->add_like_proc($this->auth_user->user_no,$res['post_no']);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}


	public function add_comment_post()
	{
		$para_validation = array(
			array('field' => 'no', 'rules' => 'required', 'label' => '댓글번호'),
			array('field' => 'comment', 'rules' => 'required', 'label' => '댓글'),
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
			array('field' => 'parent_no', 'rules' => 'required', 'label' => '부모글번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_group->add_comment_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function comment_delete($comment_no)
	{
		$this->db->trans_begin();
		$data = $this->service_group->delete_comment_proc($this->auth_user->user_no,$comment_no);
		if ($data === false) {
			$_error = $this->service_group->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function delete_delete($group_no)
	{
		$this->db->trans_begin();
		$data = $this->service_group->delete_proc($this->auth_user->user_no, $group_no);
		if ($data === false) {
			$_error = $this->service_group->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function detail_get($group_no)
	{
		$data = $this->service_group->detail($group_no, $this->auth_user->user_no);

		if ($data === false) {
			$_error = $this->service_group->get_error();

			$this->response_error($_error->message, self::HTTP_UNAUTHORIZED);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}
}