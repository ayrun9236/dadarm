<?php

/**
 * 게시물관련
 */
require_once APPPATH . '/libraries/RestController.php';

class Post extends RestController
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('service/service_post');
	}

	/*
     * token 인증 제외 처리
     */
	protected function auth_method_check() {
		$result = array('selftest_post','post_list_get','share_check_post','guest_add_comment_post','list_get');
		return $result;
	}

	public function user_list_get(){
		$this->post_list_get();
	}

	public function comments_get(){
		$post_no = $this->get('post_no');

		$params = array(
			'post_no' => $post_no,
			'target_table' => 'p',
		);

		$data = $this->service_post->comment_lists($params, $this->auth_user->user_no);
		$this->response(array('data' => $data), self::HTTP_OK);
	}

	public function list_get(){
		$this->post_list_get();
	}
	public function post_list_get()
	{
		$type = strtoupper($this->get('type'));
		$sub_type = $this->get('sub_type');
		$search_text = $this->get('search_text');
		$pag_size = $this->general->default($this->get('page_size'),10);
		$friend_no = $this->general->default($this->get('ltype_no'),0);
		$page = $this->general->default($this->get('page'),1);

		if($pag_size > 20) {
			$pag_size = 20;
		}

		if($type == '') {
			$type = 'PROFILE';
		}

		$params = array(
			'type' => $type,
			'sub_type' => $sub_type,
			'page' => $page,
			'per_page' => $pag_size,
			'user_no' => 0,
			'search' => $search_text,
			'is_private_check' => 1,
		);

		$user_no = 0;
		if(isset($this->auth_user->user_no)){
			$user_no = $this->auth_user->user_no;
		}

		if($type == 'PROFILE'){
			$this->auth_check();
			$params['user_no'] = $this->auth_user->user_no;
			$params['is_private_check'] = 0;
		}
		else if($type == 'FRIEND'){
			$this->auth_check();

			$params['friend_no'] = $friend_no;
			$params['user_no'] = $this->auth_user->user_no;
		}
		elseif($type == 'CHILD_PRAISE'){
			$params['type'] = 'PROFILE';
			$params['sub_type'] = 'THANKYOU-NOTE';
		}
		elseif($type == 'PROMISE'){
			$params['type'] = 'PROFILE';
			$params['sub_type'] = 'PROMISE';
		}
		elseif($type == 'SHARE'){
			$params['post_no'] = $params['post_no'];
			$params['is_private_check'] = 0;
		}

		$data = $this->service_post->lists($params, $user_no);
		$this->response(array('data' => $data), self::HTTP_OK);

	}

	public function add_post()
	{
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
			array('field' => 'view_type_no', 'rules' => 'required', 'label' => '공개여부'),
//			array('field' => 'board_type', 'rules' => 'required', 'label' => '분류'),
			array('field' => 'title', 'rules' => 'required', 'label' => '제목'),
			array('field' => 'contents', 'rules' => 'required', 'label' => '내용'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		if(isset($res['user_group_no']) && $res['user_group_no']> 0){
			$this->load->library('service/service_group');
			$data = $this->service_group->add_post_proc($this->auth_user->user_no,$res);
		}
		else if(isset($res['friend_no']) && $res['friend_no']> 0){
			$this->load->library('service/service_friend');
			//권한 체크
			$auth = $this->service_friend->friend_detail($res['friend_no'], $this->auth_user->user_no);
			if($auth['is_post_write'] === false){
				$this->response_error('친구의 공간에 글쓰기 권한이 없습니다.', self::HTTP_BAD_REQUEST);
			}

			$res['board_type'] = 'PROFILE';
			$res['profile_user_no'] = $res['friend_no'];
			$data = $this->service_post->add_proc($this->auth_user->user_no,$res);
		}
		else if($res['board_type'] == 'BOARD_RECOMMEND') {
			$data = $this->service_post->add_user_recommend_proc($this->auth_user->user_no,$res);
		}
		else {
			if(isset($res['add_data']) && $res['board_head_type_no'] === 444){
				$res['new_add_data'] = array();
				foreach ($res['add_data'] as $item){
					$res['new_add_data'][] = json_decode($item, true);
				}

				$res['add_data'] = $res['new_add_data'];
			}

			$res['profile_user_no'] = $this->auth_user->user_no;
			$data = $this->service_post->add_proc($this->auth_user->user_no,$res);
		}

		if ($data === false) {
			if(isset($res['user_group_no']) && $res['user_group_no']> 0) {
				$_error = $this->service_group->get_error();
			}
			else{
				$_error = $this->service_post->get_error();
			}

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function selftest_post()
	{
		$para_validation = array(
			array('field' => 'board_no', 'rules' => 'required', 'label' => '자가진단'),
			array('field' => 'selectedAnswers[]', 'rules' => 'required', 'label' => '선택문항'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$user_no = 0;
		if(isset($this->auth_user->user_no)){
			$user_no = $this->auth_user->user_no;
		}

		$ret = $this->service_post->selftest_result($res);
		if($user_no > 0){
			$res['post_no'] = 0;
			$res['title'] = $res['test_type'] . '자가진단';
			$res['contents'] = $ret['comment'].(isset($ret['add_comment']) ? "\r\n".$ret['add_comment']:'');
			// todo $res['board_head_type_no'] = USER_BOARD_TYPE['SELFTEST'];
			$res['board_type'] = 'PROFILE';
			$res['board_head_type_no'] = 112;
			$res['board_head_sub_type'] = $res['test_type'];
			$res['target_table'] = 'board';
			$res['target_no'] = $res['board_no'];
			$res['profile_user_no'] = $user_no;
			$res['add_data'] = array (
				'score' => array('data' => $ret['data']),
				'answers' => $res['selectedAnswers'],
			);

			$data = $this->service_post->add_proc($this->auth_user->user_no,$res);
			if ($data === false) {
				$_error = $this->service_post->get_error();

				$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
			}
		}

		$this->response(array('data' => $ret), self::HTTP_OK);

	}

	public function block_post()
	{
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
			array('field' => 'type', 'rules' => 'required', 'label' => '타입'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_post->block_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_post->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function declaration_post()
	{
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
			array('field' => 'type', 'rules' => 'required', 'label' => '타입'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_post->declaration_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_post->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function post_delete($group_post_no)
	{
		$data = $this->service_post->post_delete_proc($this->auth_user->user_no,$group_post_no);

		if ($data === false) {
			$_error = $this->service_post->get_error();

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
		$data = $this->service_post->add_like_proc($this->auth_user->user_no,$res['post_no']);

		if ($data === false) {
			$_error = $this->service_post->get_error();

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
		$data = $this->service_post->add_comment_proc($this->auth_user->user_no,$res);

		if ($data === false) {
			$_error = $this->service_post->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function guest_add_comment_post()
	{
		$para_validation = array(
			array('field' => 'no', 'rules' => 'required', 'label' => '댓글번호'),
			array('field' => 'name', 'rules' => 'required', 'label' => '이름'),
			array('field' => 'comment', 'rules' => 'required', 'label' => '댓글'),
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
			array('field' => 'parent_no', 'rules' => 'required', 'label' => '부모글번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_post->add_comment_proc(0,$res);

		if ($data === false) {
			$_error = $this->service_post->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function comment_delete($comment_no)
	{
		$this->db->trans_begin();
		$data = $this->service_post->delete_comment_proc($this->auth_user->user_no,$comment_no);
		if ($data === false) {
			$_error = $this->service_post->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function center_review_delete($review_no)
	{
		$this->db->trans_begin();
		$data = $this->service_post->center_review_delete($this->auth_user->user_no,$review_no);
		if ($data === false) {
			$_error = $this->service_post->get_error();
			$this->db->trans_rollback();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->db->trans_commit();

			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

	public function share_get($post_no){
		$data = $this->service_post->share_link($this->auth_user->user_no, $post_no);
		if ($data === false) {
			$_error = $this->service_post->get_error();

			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}


	public function share_check_post(){
		$para_validation = array(
			array('field' => 'post_no', 'rules' => 'required', 'label' => '글번호'),
			array('field' => 'user_check_no', 'rules' => 'required', 'label' => '입력 비밀번호'),
//			array('field' => 'check_no', 'rules' => 'required', 'label' => '글 확인비밀번호'),
		);
		$this->parameter_validate($para_validation);

		$res = $this->post();
		$data = $this->service_post->share_check($res);
		if ($data === false) {
			$_error = $this->service_post->get_error();
			$this->response_error($_error->message, self::HTTP_BAD_REQUEST);
		} else {
			$this->response(array('data' => $data), self::HTTP_OK);
		}
	}

}