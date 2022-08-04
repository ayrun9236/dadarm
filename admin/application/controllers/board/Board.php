<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
class Board extends MY_Controller
{

	protected $board_type = 'basic';

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_board_model');
		$this->load->library('service/service_board');
		$this->load->library('form_validation');

		$this->data['page_content']['menu2'] = 'board';
	}

	/**
	 * 리스트
	 */
	public function index()
	{
		$this->data['type'] = $this->board_type;
		$this->data['page_content']['menu3'] = 'index';
		$this->load->view('view', $this->data);
	}

	/**
	 * 상세정보
	 */
	public function detail($board_no, $mode = '')
	{
		$params = array(
			'page' => 1,
			'per_page' => 1,
			'board_no' => $board_no
		);


		$res = $this->service_board->lists($params);

		$ret = $this->json_output(true, '', $res['list'][0]);
		$this->output->set_output(json_encode($ret));
	}

	public function data()
	{
		$res = $this->input->get();

		if ($res['page'] == '' || $res['page'] < 1) {
			$res['page'] = 1;
		}

		if ($res['per_page'] == '' || $res['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$data = $this->service_board->lists($res);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}


	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'type', 'rules' => 'required', 'label' => '게시판분류'),
			array('field' => 'title', 'rules' => 'required', 'label' => '제목'),
			array('field' => 'contents', 'rules' => 'required', 'label' => '내용'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		if($res['type'] == 'basic') {
			$res['type'] = $res['sub_type'];
			unset($res['sub_type']);
		}

		$this->db->trans_begin();
		$board_no = $this->service_board->create($res);
		if ($board_no > 0) {
			$ret = true;
			$message = '등록을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$_error = $this->service_board->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $board_no);
		$this->output->set_output(json_encode($ret));

	}

	/**
	 * 수정하기
	 */
	public function modify()
	{
		$para_validation = array(
			array('field' => 'title', 'rules' => 'required', 'label' => '제목'),
			array('field' => 'contents', 'rules' => 'required', 'label' => '내용'),
			array('field' => 'board_no', 'rules' => 'required', 'label' => '글번호'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$params = array(
			'sub_type' => $res['sub_type'],
			'title'    => $res['title'],
			'contents' => $res['contents'],
		);

		if(isset($res['sort'])){
			$params['sort'] = $res['sort'];
		}

		$this->db->trans_begin();
		$is_update = $this->service_board->update($res['board_no'], $res);
		if ($is_update > 0) {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_board->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 삭제하기
	 */
	public function delete($board_no)
	{
		$this->db->trans_begin();
		$is_delete = $this->service_board->delete($board_no);
		if ($is_delete) {
			$ret = true;
			$message = '삭제를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_board->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 자가진단 등록하기
	 */
	public function question_save()
	{
		$para_validation = array(
			array('field' => 'board_no', 'rules' => 'required', 'label' => '분류'),
			array('field' => 'selftest_main_no', 'rules' => 'required', 'label' => '자가진단번호'),
			array('field' => 'answer_type', 'rules' => 'required', 'label' => '답변형태'),
			array('field' => 'question_text', 'rules' => 'required', 'label' => '질문내용'),
			array('field' => 'answer_data', 'rules' => 'required', 'label' => '응답항목'),
		);

		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();
		$board_no = $this->service_board->selftest_create($res);
		if ($board_no > 0) {
			$ret = true;
			$message = '등록을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$_error = $this->service_board->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $board_no);
		$this->output->set_output(json_encode($ret));

	}

	/**
	 * 진단 테스트 상세정보
	 */
	public function selftest_detail($board_no, $sort)
	{
		$res = $this->service_board->selftest_detail($board_no, $sort, 'admin');

		$ret = $this->json_output(true, '', $res);
		$this->output->set_output(json_encode($ret));
	}
}
