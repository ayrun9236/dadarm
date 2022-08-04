<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-08 오전 10:40
 */
class Store extends MY_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_order_model');
		$this->load->library('form_validation');
	}


	/**
	 * 리스트
	 */
	public function index()
	{
		$this->data['sch_order_store'] = $this->input->get('order_store');
		$this->data['sch_order_status'] = $this->input->get('order_status');
		$this->data['sch_payment_type'] = $this->input->get('order_payment_type');
		$this->data['sch_order_type'] = $this->input->get('order_type');
		$this->data['sch_sdate'] = $this->input->get('order_sdate');
		$this->data['sch_edate'] = $this->input->get('order_edate');

		$searchs = array();
		if ($this->data['sch_order_store'] != '') $searchs['and']['store_no'] = $this->data['sch_order_store'];
		if ($this->data['sch_order_status'] != '') $searchs['and']['order_status'] = $this->data['sch_order_status'];
		if ($this->data['sch_payment_type'] != '') $searchs['and']['payment_type'] = $this->data['sch_payment_type'];
		if ($this->data['sch_order_type'] != '') $searchs['and']['order_type'] = $this->data['sch_order_type'];

		if($this->data['sch_sdate'] == ''){
			$this->data['sch_sdate'] = date("Y-m-d", strtotime('- 31 days'));;
			$this->data['sch_edate'] = date('Y-m-d');
		}

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'o.insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_order_model->store_summary($this->data['sch_sdate'], $this->data['sch_edate'], $searchs);

		$this->data['graph'] = $data;
		$this->load->view('view', $this->data);
	}

	/**
	 * 리스트
	 */
	public function store()
	{
		$this->data['sch_order_store'] = $this->input->get('order_store');
		$this->data['sch_order_status'] = $this->input->get('order_status');
		$this->data['sch_payment_type'] = $this->input->get('order_payment_type');
		$this->data['sch_order_type'] = $this->input->get('order_type');
		$this->data['sch_sdate'] = $this->input->get('order_sdate');
		$this->data['sch_edate'] = $this->input->get('order_edate');

		$searchs = array();
		if ($this->data['sch_order_store'] != '') $searchs['and']['store_no'] = $this->data['sch_order_store'];
		if ($this->data['sch_order_status'] != '') $searchs['and']['order_status'] = $this->data['sch_order_status'];
		if ($this->data['sch_payment_type'] != '') $searchs['and']['payment_type'] = $this->data['sch_payment_type'];
		if ($this->data['sch_order_type'] != '') $searchs['and']['order_type'] = $this->data['sch_order_type'];

		if($this->data['sch_sdate'] == ''){
			$this->data['sch_sdate'] = date("Y-m-d", strtotime('- 31 days'));;
			$this->data['sch_edate'] = date('Y-m-d');
		}

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'o.insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_order_model->order_summary($this->data['sch_sdate'], $this->data['sch_edate'], $searchs);

		$this->data['graph'] = $data;
		$this->load->view('view', $this->data);
	}

}
