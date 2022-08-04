<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
class Event extends MY_Controller
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

		$this->db->trans_begin();
		$is_update = $this->service_board->update($res['board_no'], $params);
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
}
