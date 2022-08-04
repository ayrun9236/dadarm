<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-08 오전 10:40
 */
class Summary extends MY_Controller
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
		$this->data['sch_sdate'] = $this->input->get('ssdate');
		$this->data['sch_edate'] = $this->input->get('sedate');
		$this->data['sch_view_type'] = $this->input->get('view_type');

		if($this->data['sch_view_type'] == ''){
			$this->data['sch_view_type'] = 'date';
		}

		$searchs = array();
		if ($this->data['sch_order_store'] != '') $searchs['and']['store_no'] = $this->data['sch_order_store'];

		if($this->data['sch_sdate'] == ''){
			$this->data['sch_sdate'] = date("Y-m-d", strtotime('- 31 days'));;
			$this->data['sch_edate'] = date('Y-m-d');
		}

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'o.insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_order_model->data_summary($this->data['sch_sdate'], $this->data['sch_edate'], $searchs, $this->data['sch_view_type']);

		$this->data['summary'] = $data;
		$this->load->view('view', $this->data);
	}

}
