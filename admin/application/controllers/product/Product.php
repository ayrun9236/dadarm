<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-08 오전 10:40
 */
class Product extends MY_Controller
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
		//이벤트 상품여부, 오렌지주스 도 이벤트용으로 따로 등록,
		$this->load->view('view', $this->data);
	}


	public function data(){
		$res = $this->input->get();

		$data = $this->service_product->lists($res);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'product_name', 'rules' => 'required', 'label' => '제품명'),
			array('field' => 'product_eng_name', 'rules' => 'required', 'label' => '영문 제품명'),
			array('field' => 'product_type', 'rules' => 'required|integer', 'label' => '구분'),
			array('field' => 'product_price', 'rules' => 'required|integer', 'label' => '가격'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();
		$product_no = $this->service_product->create($res);
		if ($product_no > 0) {
			$ret = true;
			$message = '신규등록을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_product->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $product_no);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 상세정보
	 */
	public function detail($product_no)
	{
		$search = array(
			'page' => 1,
			'per_page' => 1,
			'product_no' => $product_no
		);
		$res = $this->service_product->lists($search);

		$ret = $this->json_output(true, '', $res['list'][0]);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 수정하기
	 */
	public function modify()
	{
		$para_validation = array(
			array('field' => 'product_name', 'rules' => 'required', 'label' => '제품명'),
			array('field' => 'product_eng_name', 'rules' => 'required', 'label' => '영문 제품명'),
			array('field' => 'product_type', 'rules' => 'required|integer', 'label' => '구분'),
			array('field' => 'product_price', 'rules' => 'required|integer', 'label' => '가격'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();
		$is_modify = $this->service_product->modify($res['product_no'], $res);
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

		$ret = $this->json_output($ret, $message, $res['product_no']);
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
