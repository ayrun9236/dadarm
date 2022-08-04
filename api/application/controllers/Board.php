<?php

/**
 * 게시판관련
 */
require_once APPPATH . '/libraries/RestController.php';

class Board extends RestController
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('service/service_board');
	}

	/*
     * token 인증 제외 처리
     */
	protected function auth_method_check() {
		$result = array('list_get', 'selftest_detail_get','center_list_get');
		return $result;
	}

	public function list_get()
	{
		$type = $this->get('type');
		$sub_type = $this->get('sub_type');
		$search_text = $this->get('search_text');
		$mode = $this->get('mode');
		$pag_size = $this->general->default($this->get('page_size'),10);
		$page = $this->general->default($this->get('page'),1);
		$board_no = $this->general->default($this->get('board_no'),0);

		if($pag_size > 20) {
			$pag_size = 20;
		}

		$user_no = 0;
		if(isset($this->auth_user->user_no)){
			$user_no = $this->auth_user->user_no;
		}

		if($mode == 'main'){
			$params = array(
				'type' => 'DATA',
				'page' => 1,
				'per_page' => 3,
				'is_view' => 1,
				'user_no' => $user_no
			);
			$data_data = $this->service_board->lists($params);

			$params = array(
				'type' => 'LECTURE',
				'page' => 1,
				'per_page' => 4,
				'is_view' => 1,
				'user_no' => $user_no
			);
			$lecture_data = $this->service_board->lists($params);

			$params = array(
				'type' => 'HAPPY',
				'page' => 1,
				'per_page' => 4,
				'user_no' => $user_no
			);
			$happy_data = $this->service_board->lists($params);

			// 그룹
			$this->load->library('service/service_group');
			$params = array(
				'page' => 1,
				'per_page' => 5,
			);
			$group_data = $this->service_group->lists($params);

			$params = array(
				'type' => 'BANNER',
				'page' => 1,
				'per_page' => 3,
				'is_view' => 1,
				'user_no' => $user_no
			);
			$banner_data = $this->service_board->lists($params);
			$data = array('data' => $data_data['list'], 'lecture' => $lecture_data['list'], 'group' => $group_data['list'], 'happy' => $happy_data['list'], 'banner' => $banner_data['list']);
		}
		else if ($board_no > 0){
			$params = array(
				'board_no' => $board_no,
				'page' => $page,
				'per_page' => $pag_size,
				'is_view' => 1,
				'user_no' => $user_no
			);
			$data = $this->service_board->lists($params);
		}
		else{
			$params = array(
				'type' => $type,
				'sub_type' => $sub_type,
				'page' => $page,
				'per_page' => $pag_size,
				'is_view' => 1,
				'search' => $search_text,
				'user_no' => $user_no
			);

			if($type == 'SELFTEST'){
				$params['per_page'] = 100;
			}
			$data = $this->service_board->lists($params);
		}

		$this->response(array('data' => $data), self::HTTP_OK);

	}

	public function center_list_get()
	{
		$page = $this->general->default($this->input->get('page'), 1);
		$location_lat = $this->general->default($this->input->get('location_lat'), 0);
		$location_lng = $this->general->default($this->input->get('location_lng'), 0);
		$search_text = $this->get('search_text');

		$params = array(
			'page' => $page,
			'per_page' => 50,
			'search' => $search_text,
			'location_lat' => $location_lat,
			'location_lng' => $location_lng,
		);

		$data = $this->service_board->center_lists($params);

		$this->response(array('data' => $data), self::HTTP_OK);

	}

	public function center_review_get($center_no)
	{
		$page = $this->general->default($this->input->get('page'), 1);
		//$center_no = $this->general->default($this->input->get('center_no'), 0);

		$params = array(
			'center_no' => $center_no,
		);

		$data = $this->service_board->center_review($params);

		$this->response(array('data' => $data), self::HTTP_OK);
	}

	public function selftest_detail_get($board_no, $sort = 1)
	{
		$data = $this->service_board->selftest_detail($board_no, $sort);

		$this->response(array('data' => $data), self::HTTP_OK);
	}

	public function detail_get($board_no)
	{
		$data = $this->service_board->detail($board_no);

		$this->response(array('data' => $data), self::HTTP_OK);
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

	public function like_post()
	{
		$para_validation = array(
			array('field' => 'board_no', 'rules' => 'required', 'label' => '자가진단'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();

		$ret = $this->service_board->like_create($this->auth_user->user_no, $res);
		if ($ret === false) {
			$_error = $this->service_board->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		}

		$this->response(array('data' => $ret), self::HTTP_OK);

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
		$data = $this->service_board->add_comment_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_board->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function comment_delete($comment_no)
	{
		$this->db->trans_begin();
		$data = $this->service_board->delete_comment_proc($this->auth_user->user_no, $comment_no);
		if ($data === false) {
			$_error = $this->service_board->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function block_post()
	{
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$res['type'] = 'comment';
		$data = $this->service_board->block_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_board->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function declaration_post()
	{
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$res['type'] = 'comment';
		$data = $this->service_board->declaration_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_board->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

}