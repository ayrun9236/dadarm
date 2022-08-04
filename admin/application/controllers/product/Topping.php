<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-08 오전 10:40
 */
class Topping extends MY_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_product_model');
		$this->load->library('service/service_product');
		$this->load->library('form_validation');
	}


	/**
	 * 리스트
	 */
	public function index()
	{
		$this->load->view('view', $this->data);
	}


	public function data(){
		$res = $this->input->get();

		if ($res['page'] == '' || $res['page'] < 1) {
			$res['page'] = 1;
		}

		if ($res['per_page'] == '' || $res['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$data = $this->service_product->topping_lists($res);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '토핑명'),
			array('field' => 'code', 'rules' => 'required', 'label' => '토핑코드'),
			array('field' => 'kcal', 'rules' => 'required', 'label' => '칼로리'),
			array('field' => 'price', 'rules' => 'required|integer', 'label' => '가격'),
		);

		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();
		$topping_no = $this->service_product->topping_create($res);
		if ($topping_no > 0) {
			$ret = true;
			$message = '신규등록을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_product->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $topping_no);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 수정하기
	 */
	public function modify()
	{

		$para_validation = array(
			array('field' => 'no', 'rules' => 'required', 'label' => '토핑번호'),
			array('field' => 'name', 'rules' => 'required', 'label' => '토핑명'),
			array('field' => 'code', 'rules' => 'required', 'label' => '토핑코드'),
			array('field' => 'is_view', 'rules' => 'required', 'label' => '노출여부'),
			array('field' => 'price', 'rules' => 'required|integer', 'label' => '가격'),
		);

		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();
		$is_modify = $this->service_product->topping_modify($res['no'], $res);
		if ($is_modify) {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_product->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $res['no']);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 삭제하기
	 */
	public function delete($topping_no)
	{
		$this->db->trans_begin();
		$is_delete = $this->service_product->topping_delete($topping_no);
		if ($is_delete) {
			$ret = true;
			$message = '삭제를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_product->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}

}
