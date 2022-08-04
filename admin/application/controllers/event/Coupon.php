<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
class Coupon extends MY_Controller
{

	protected $coupon_type;

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_coupon_model');
		$this->load->library('service/service_coupon');
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
	public function detail($coupon_no, $mode = '')
	{
		$res = $this->service_coupon->detail($coupon_no, $mode);

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

		$data = $this->service_coupon->lists($res);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}


	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'coupon_name', 'rules' => 'required', 'label' => '쿠폰명'),
			array('field' => 'discount_price', 'rules' => 'required', 'label' => '할인금액'),
			array('field' => 'order_min_price', 'rules' => 'required', 'label' => '최소 주문금액'),
			array('field' => 'order_type', 'rules' => 'required', 'label' => '주문타입'),
			array('field' => 'publish_type', 'rules' => 'required', 'label' => '발급타입'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$this->db->trans_begin();
		$coupon_no = $this->service_coupon->create($res);
		if ($coupon_no > 0) {
			$ret = true;
			$message = '등록을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$_error = $this->service_coupon->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $coupon_no);
		$this->output->set_output(json_encode($ret));

	}

	/**
	 * 수정하기
	 */
	public function modify()
	{
		$para_validation = array(
			array('field' => 'coupon_master_no', 'rules' => 'required', 'label' => '쿠폰번호'),
			array('field' => 'coupon_name', 'rules' => 'required', 'label' => '쿠폰명'),
			array('field' => 'discount_price', 'rules' => 'required', 'label' => '할인금액'),
			array('field' => 'order_min_price', 'rules' => 'required', 'label' => '최소 주문금액'),
			array('field' => 'order_type', 'rules' => 'required', 'label' => '주문타입'),
			array('field' => 'publish_type', 'rules' => 'required', 'label' => '발급타입'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$this->db->trans_begin();
		$is_update = $this->service_coupon->update($res['coupon_master_no'], $res);
		if ($is_update > 0) {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_coupon->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $res['coupon_master_no']);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 삭제하기
	 */
	public function delete($coupon_no)
	{
		$this->db->trans_begin();
		$is_delete = $this->service_coupon->delete($coupon_no);
		if ($is_delete) {
			$ret = true;
			$message = '삭제를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_coupon->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}
}
