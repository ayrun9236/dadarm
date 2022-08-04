<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-05-27 오전 11:40
 */
class Menu_order extends MY_Controller
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
		$this->data['summary'] = $this->_data();
		$this->load->view('view', $this->data);
	}


	protected function _data(){
		$this->data['sch_order_store'] = $this->input->get('order_store');
		$this->data['sch_sdate'] = $this->input->get('ssdate');
		$this->data['sch_edate'] = $this->input->get('sedate');
		$this->data['sch_product_type'] = $this->input->get('product_type');

		$searchs = array();
		if ($this->data['sch_order_store'] != '') $searchs['and']['store_no'] = $this->data['sch_order_store'];

		if($this->data['sch_sdate'] == ''){
			$this->data['sch_sdate'] = date("Y-m-d", strtotime('- 6 days'));;
			$this->data['sch_edate'] = date('Y-m-d');
		}

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'o.insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_order_model->menu_summary($this->data['sch_sdate'], $this->data['sch_edate'], $searchs, $this->data['sch_product_type']);
		return $data;
	}

	public function excel_down(){

		$down_data = $this->_data();

		set_time_limit(0);
		ini_set('memory_limit','-1');
		$this->load->library('common/excelxml');

		$this->excelxml->docAuthor('slowraw');

		$sheet = $this->excelxml->addSheet('sheet1');

		$format = $this->excelxml->addStyle('StyleHeader');
		$format->fontSize(11);
		$format->fontBold();
		$format->fontFamily('Nanum Gothic');
		$format->bgColor('#dedede');
		$format->fontColor('#333');
		$format->alignHorizontal('Center');
		$format->alignVertical('Center');
		$format->border('ALL','1','#ccc','Continuous');

		$format = $this->excelxml->addStyle('StyleBody');
		$format->alignHorizontal('Center');
		$format->alignWraptext();
		$format->fontSize(10);

		$format = $this->excelxml->addStyle('StyleBody_left');
		$format->alignHorizontal('Left');
		$format->fontSize(10);


		$format = $this->excelxml->addStyle('StyleNumberFormat');
		$format->numberFormat('#,##0_ ');
		$format->alignHorizontal('Right');
		$format->fontSize(10);


		$sheet->writeString(1,1,'카테고리','StyleHeader');
		$sheet->writeString(1,2,'메뉴','StyleHeader');
		$sheet->writeString(1,3,'판매수량','StyleHeader');
		$sheet->writeString(1,4,'취소건수','StyleHeader');
		$sheet->writeString(1,5,'판매금액','StyleHeader');
		$sheet->writeString(1,6,'취소금액','StyleHeader');
		$sheet->writeString(1,7,'합계','StyleHeader');
		$sheet->writeString(1,8,'판매율','StyleHeader');

		$filename = 'summary_list.xls';

		foreach ($down_data as $key => $value) {
			$_row_num = $key+2;

			$sheet->writeString($_row_num,1,$value->name,'StyleBody');
			$sheet->writeString($_row_num,2,$value->product_name,'StyleBody_left');
			$sheet->writeNumber($_row_num,3,$value->order_count,'StyleNumberFormat');
			$sheet->writeNumber($_row_num,4,$value->cancel_count,'StyleNumberFormat');
			$sheet->writeNumber($_row_num,5,$value->order_price,'StyleNumberFormat');
			$sheet->writeNumber($_row_num,6,$value->cancel_price,'StyleNumberFormat');
			$sheet->writeNumber($_row_num,7,$value->order_price-$value->cancel_price,'StyleNumberFormat');
			$sheet->writeNumber($_row_num,8,$value->order_rate,'StyleNumberFormat');

		}

		$sheet->columnWidth(1,'50');
		$sheet->columnWidth(2,'100');
		$sheet->columnWidth(3,'70');
		$sheet->columnWidth(4,'70');
		$sheet->columnWidth(5,'70');
		$sheet->columnWidth(6,'70');
		$sheet->columnWidth(7,'100');
		$sheet->columnWidth(8,'100');

		$this->excelxml->sendHeaders($filename);
		$this->excelxml->writeData();
	}

}
