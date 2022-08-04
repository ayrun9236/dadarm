<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-08 오전 10:40
 */
class Store_product extends MY_Controller
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

		$data = $this->service_product->store_product_lists($res);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}


	/**
	 * 상세정보
	 */
	public function detail($product_no)
	{
		$res = $this->service_product->detail($product_no);

		$ret = $this->json_output(true, '', $res);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 수정하기
	 */
	public function modify()
	{
		$para_validation = array(
			array('field' => 'product_nos[]', 'rules' => 'required', 'label' => '제품번호'),
			array('field' => 'store', 'rules' => 'required', 'label' => '매장정보'),
			array('field' => 'mode', 'rules' => 'required', 'label' => '설정상태'),
			array('field' => 'mode_value', 'rules' => 'required', 'label' => '설정값'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$is_update = $this->service_product->store_product_modify($res);

		$ret = true;
		$message = '설정을 완료하였습니다.';

		$ret = $this->json_output($ret, $message, '');
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 삭제하기
	 */
	public function delete($product_no)
	{
		$this->db->trans_begin();
		$is_delete = $this->service_product->delete($product_no);
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
